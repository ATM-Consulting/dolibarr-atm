<?php
/* Copyright (C) 2019 Open-DSI            <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/banking4dolibarr/manualreconciliation.php
 * \brief   File to for manage the manual reconciliation of a bank record downloaded
 */

if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
dol_include_once('/banking4dolibarr/lib/opendsi_common.lib.php');
dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
dol_include_once('/banking4dolibarr/class/html.formbanking4dolibarr.class.php');
dol_include_once('/advancedictionaries/class/dictionary.class.php');
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

$conf->dol_hide_topmenu = true;
$conf->dol_hide_leftmenu = true;

$langs->loadLangs(array('banking4dolibarr@banking4dolibarr', 'banks', 'companies', 'other'));
if (!empty($conf->accounting->enabled)) $langs->load('accountancy');

$id	            	= GETPOST('id', 'int');
$row_id         	= GETPOST('row_id', 'int');
$create_element_id 	= GETPOST('b4d_create_element_id', 'int');
$check_deposit_id 	= GETPOST('check_deposit_id', 'int');
$standing_order_id 	= GETPOST('standing_order_id', 'int');
$action         	= GETPOST('action','alpha');
$confirm        	= GETPOST('confirm','alpha');

// Security check
if ($user->societe_id > 0 || !$user->rights->banking4dolibarr->bank_records->lire) accessforbidden();
$result = restrictedArea($user, 'banque', $id, 'bank_account&bank_account', '', '');

// Load Account
$ret = 0;
$account = new Account($db);
if ($id > 0) $ret = $account->fetch($id);
if ($ret > 0) {
    $account->fetch_thirdparty();
} elseif ($ret < 0) {
    dol_print_error('', $account->error, $account->errors);
} else {
    $langs->load("errors");
    accessforbidden($langs->trans('BankAccount') . ' : ' . $langs->trans('ErrorRecordNotFound'));
}

// Security check
if ($account->clos != 0 || !$user->rights->banque->consolidate) accessforbidden();

// Load object
$ret = 0;
$payment_mode_id = 0;
$category_infos = array();
$object = new BudgetInsightBankRecord($db);
if ($row_id > 0) $ret = $object->fetch($row_id, '', 0, 0, 0, 1);
if ($ret > 0) {
    $payment_mode_id = $object->getDolibarrPaymentModeId($object->record_type, 1);
	if ($object->id_category > 0) {
		// Load bank record category from the dictionary
		$bank_record_categories_dictionary_line = Dictionary::getDictionaryLine($db, 'banking4dolibarr', 'banking4dolibarrbankrecordcategories');
		$result = $bank_record_categories_dictionary_line->fetch($object->id_category);
		if ($result > 0) {
			$category_infos = $bank_record_categories_dictionary_line->fields;
			if ($category_infos['id_parent_category'] > 0) {
				// Load the parent bank record category from the dictionary
				$bank_record_categories_dictionary_line = Dictionary::getDictionaryLine($db, 'banking4dolibarr', 'banking4dolibarrbankrecordcategories');
				$result = $bank_record_categories_dictionary_line->fetch($category_infos['id_parent_category']);
				if ($result > 0) {
					$category_infos['label'] = $bank_record_categories_dictionary_line->fields['label'] . ' - ' . $category_infos['label'];
				} else {
					$category_infos['label'] = $langs->trans('Unknown') . ' - ' . $category_infos['label'];
					if ($result < 0) {
						setEventMessages($bank_record_categories_dictionary_line->error, $bank_record_categories_dictionary_line->errors, 'errors');
					}
				}
			}
		} elseif ($result < 0) {
			setEventMessages($bank_record_categories_dictionary_line->error, $bank_record_categories_dictionary_line->errors, 'errors');
		}
	}
} elseif ($ret < 0) {
    dol_print_error('', $object->error, $object->errors);
} else {
    $langs->load("errors");
    accessforbidden($langs->trans('Banking4DolibarrBankRecord') . ' : ' . $langs->trans('ErrorRecordNotFound'));
}

// Security check
if ($object->status != BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED) accessforbidden($langs->trans('Banking4DolibarrErrorLinkLineNotUnlinked', $object->id));
if ($object->deleted_date !== "") accessforbidden();

$payment_mode = GETPOST('payment_mode', 'int');
$manual_reconciliation_type = GETPOST('manual_reconciliation_type', 'alpha');
if ($manual_reconciliation_type == -1) $manual_reconciliation_type = '';

$budgetinsight = new BudgetInsight($db);
$manual_reconciliation_types = $budgetinsight->getManualReconciliationTypes($user);
$unpaid_types = $budgetinsight->getManualReconciliationUnpaidTypes($user);
$create_element_infos = $budgetinsight->getManualReconciliationCreateElementInfos($user);

// Security check
if (!empty($manual_reconciliation_type) && !isset($manual_reconciliation_types[$manual_reconciliation_type])) accessforbidden($langs->trans('Banking4DolibarrErrorUnknownManuelReconciliationType', $manual_reconciliation_type));

$isV9p = version_compare(DOL_VERSION, "9.0.0") >= 0;
$isV11p = version_compare(DOL_VERSION, "11.0.0") >= 0;
$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

// Initialize technical object to manage context to save list fields
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'b4dbankrecordsmanualreconciliation';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('b4dbankrecordsmanualreconciliation'));

/*
 * Actions
 */

$param = '&id=' . $id . '&row_id=' . $row_id;
if (!empty($manual_reconciliation_type)) $param .= '&manual_reconciliation_type=' . urlencode($manual_reconciliation_type);
if (!empty($payment_mode)) $param .= '&payment_mode=' . urlencode($payment_mode);
$bank_id_linked_to_multi_bank_record = 0;
$close_box = false;

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
elseif ($reshook == 0) {
    $error = 0;
    if ($action == 'save' || $action == 'saveandcreate') {
        if (empty($manual_reconciliation_type)) {
            setEventMessage($langs->trans('Banking4DolibarrErrorManualReconciliationTypeEmpty'), 'errors');
            $error++;
        } elseif (!empty($manual_reconciliation_types[$manual_reconciliation_type]['payment_mode_require']) && !($payment_mode_id > 0) && empty($payment_mode)) {
            setEventMessage($langs->trans('Banking4DolibarrErrorPaymentModeEmpty'), 'errors');
            $error++;
        }

        if (!$error) {
            $budgetinsight_static = new BudgetInsight($db);
            $statement_number = $budgetinsight_static->getStatementNumberFromDate($object->record_date);

            if ($manual_reconciliation_type == 'bank_transaction') {
                $toselect       = GETPOST('toselect', 'array');
                $nb_selected = is_array($toselect) ? count($toselect) : 0;

                // Mass actions. Controls on number of lines checked.
                $maxformassaction = (empty($conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS) ? 1000 : $conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
                if ($nb_selected < 1) {
                    setEventMessages($langs->trans("NoRecordSelected"), null, "errors");
                    $error++;
                } elseif ($nb_selected > $maxformassaction) {
                    setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
                    $error++;
                }

                if (!$error) {
                    $idx = 0;
                    $total_amount = price2num($object->remaining_amount_to_link, 'MT');
                    $sum_amount = 0;
                    $accountline_static = new AccountLine($db);
                    $db->begin();
                    foreach ($toselect as $toselectid) {
						$idx++;
						$result = $accountline_static->fetch($toselectid);
                        if ($result > 0) {
                            if ($bank_id_linked_to_multi_bank_record == 0) {
                                $result = $budgetinsight->isBankLineAlreadyLinked($toselectid);
                                if ($result > 0) {
                                    $bank_id_linked_to_multi_bank_record = $toselectid;
                                    $result = $object->link($user, $toselectid);
                                    if ($result > 0) {
                                        // Test if sum of all bank record is equal to the amount of the bank line linked and reconcile all bank record linked
                                        $result = $budgetinsight->isBankLineAmountReconciledWithSumBankRecordAmount($bank_id_linked_to_multi_bank_record);
                                        if ($result > 0) {
                                            $sn = $budgetinsight->getStatementNumberFromDate($accountline_static->dateo);
                                            $result = $budgetinsight->reconcileAllBankRecordLinkedToBankLine($user, $sn, $bank_id_linked_to_multi_bank_record);
                                        }
                                        if ($result < 0) {
                                            setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
                                            $error++;
                                            break;
                                        }
                                    } elseif ($result < 0) {
                                        setEventMessages($object->error, $object->errors, 'errors');
                                        $error++;
                                        break;
                                    }
                                } elseif ($result < 0) {
                                    setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
                                    $error++;
                                    break;
                                } else {
                                    $sum_amount += $accountline_static->amount;
                                    $result = $object->link($user, $toselectid);
                                    if ($result > 0 && $idx == $nb_selected && $total_amount == price2num($sum_amount, 'MT')) {
                                        $linked_lines = $object->getLinkedLinesToReconcile();
                                        if ($linked_lines < 0) {
                                            $result = $linked_lines;
                                        } else {
                                            foreach ($linked_lines as $line_id) {
                                                $result = $object->reconcile($user, $statement_number, $line_id, 1, 1);
                                                if ($result < 0) break;
                                            }
                                        }
                                    }
                                    if ($result < 0) {
                                        setEventMessages($object->error, $object->errors, 'errors');
                                        $error++;
                                        break;
                                    }
                                }
                            } else {
                                setEventMessage($langs->trans('Banking4DolibarrErrorCannotLinkManyBankLineWhenABankLineIsAlreadyLinked'), "errors");
                                $error++;
                                break;
                            }
                        } elseif ($result < 0) {
                            setEventMessages($accountline_static->error, $accountline_static->errors, "errors");
                            $error++;
                            break;
                        } else {
                            $langs->load("errors");
                            setEventMessage($langs->trans('ErrorRecordNotFound') . ' - ID: ' . $toselectid, "errors");
                            $error++;
                            break;
                        }
                    }
                    if (!$error) {
                        $db->commit();
                    } else {
                        $db->rollback();
                    }
                }
            } elseif ($manual_reconciliation_type == 'unpaid_element') {
				$element_reconciled = array();
				foreach ($_POST as $line_key => $line_amount) {
					if (preg_match('/^reconciled_amount_(\w+)_(\d+)$/', $line_key, $matches)) {
						if ($line_amount != 0) {
							$element_type = $matches[1];
							$element_id = $matches[2];

							$sql = "SELECT ul.amount FROM " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list as ul" .
								" WHERE ul.entity IN (" . $conf->entity . ")" .
								" AND ul.element_type = '" . $db->escape($element_type) . "'" .
								" AND ul.element_id = " . $element_id;
							$resql = $db->query($sql);
							if (!$resql) {
								dol_print_error($db);
								$error++;
								break;
							}

							if ($obj = $db->fetch_object($resql)) {
								if (!isset($element_reconciled[$element_type])) $element_reconciled[$element_type] = array();
								$element_reconciled[$element_type][$element_id] = array('amount' => $line_amount, 'multicurrency_amount' => 0);
							} else {
								setEventMessage($langs->trans('Banking4DolibarrErrorLineNotFound', isset($unpaid_types[$element_type]) ? $unpaid_types[$element_type] : $element_type, $element_id, $line_amount), "errors");
								$error++;
								break;
							}
						}
					}
				}

				if (!$error) {
					$idx = 0;
					$total_amount = price2num($object->remaining_amount_to_link, 'MT');
					$sum_amount = 0;
					$nb_selected = count($element_reconciled);
					$payment_id = $payment_mode_id > 0 ? $payment_mode_id : $payment_mode;
					$payment_mode_code = dol_getIdFromCode($db, $payment_id, 'c_paiement', 'id', 'code', 1);
					$db->begin();

					$all_bank_line_ids = array();
					foreach ($element_reconciled as $element_type => $amount_infos) {
						$bank_line_ids = $budgetinsight_static->createPayment($user, $id, $element_type, $object->record_date, $payment_id, $amount_infos);
						if (is_array($bank_line_ids)) {
							foreach ($bank_line_ids as $bank_line_id) {
								$result = $object->link($user, $bank_line_id);
								if ($result < 0) break;
							}
							if ($result < 0) {
								setEventMessages($object->error, $object->errors, 'errors');
								$error++;
								break;
							}
							foreach ($amount_infos as $element_id => $amounts) {
								$sum_amount += $amounts['amount'];
							}
							$all_bank_line_ids = array_merge($all_bank_line_ids, $bank_line_ids);
							$idx++;
						} else {
							setEventMessages($budgetinsight_static->error, $budgetinsight_static->errors, "errors");
							$error++;
							break;
						}
					}
					if (!$error && $idx == $nb_selected && $total_amount == price2num($sum_amount, 'MT')) {
						$result = $object->getLinkedLinesToReconcile();
						if (is_array($result)) {
							$linked_lines = $result;
							foreach ($linked_lines as $line_id) {
								$result = $object->reconcile($user, $statement_number, $line_id, 1, 1);
								if ($result < 0) break;
							}
						}
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}
					if (!$error && $payment_mode_code == 'CHQ' && $total_amount > 0) {
						// Deposit receipt check to the bank
						$deposit_receipt_date = dol_mktime(0, 0, 0, GETPOST("deposit_receipt_datemonth", 'int'), GETPOST("deposit_receipt_dateday", 'int'), GETPOST("deposit_receipt_dateyear", 'int'));
						$result = $budgetinsight_static->createCheckReceiptPayment($user, $id, $deposit_receipt_date, $all_bank_line_ids);
						if ($result < 0) {
							setEventMessages($budgetinsight_static->error, $budgetinsight_static->errors, 'errors');
							$error++;
						}
					}

					if (!$error) {
						$db->commit();
					} else {
						$db->rollback();
					}
                }
            } elseif ($manual_reconciliation_type == 'bank_transfer') {
                $account_id = GETPOST('account_id','int');
                $label = GETPOST('label', 'alpha');
                $account_amount = GETPOST('account_amount','int');

                $langs->load("errors");

                if (empty($label)) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Description")), null, 'errors');
                    $error++;
                }
                if (!($account_id > 0)) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities($object->amount > 0 ? "TransferFrom" : "TransferTo")), null, 'errors');
                    $error++;
                }

                if (!$error) {
                    $account2 = new Account($db);
                    $account2->fetch($account_id);

                    if ($object->amount < 0) {
                        $account_from = $account;
                        $account_to = $account2;
                        $amount_from = -1 * $object->amount;
                        $amount_to = price2num($account_amount, 'MT');
                    } else {
                        $account_from = $account2;
                        $account_to = $account;
                        $amount_from = price2num($account_amount, 'MT');
                        $amount_to = $object->amount;
                    }

                    if ($account_to->currency_code == $account_from->currency_code) {
                        if ($object->amount < 0) $amount_to = $amount_from;
                        else $amount_from = $amount_to;
                    } elseif (empty($amount_to)) {
                        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities($object->amount > 0 ? "Banking4DolibarrAmountFromOtherCurrency" : "AmountToOthercurrency")), null, 'errors');
                        $error++;
                    }

                    if (($account_to->id != $account_from->id) && empty($error)) {
                        $db->begin();

                        $bank_line_id_from = 0;
                        $bank_line_id_to = 0;
                        $result = 0;

                        if ($isV11p) {
                            $linkToBankLine = '/compta/bank/line.php';
                        } else {
                            $linkToBankLine = '/compta/bank/ligne.php';
                        }

                        // By default, electronic transfert from bank to bank
                        $typefrom = 'PRE';
                        $typeto = 'VIR';
                        if ($account_to->courant == Account::TYPE_CASH || $account_from->courant == Account::TYPE_CASH) {
                            // This is transfer of change
                            $typefrom = 'LIQ';
                            $typeto = 'LIQ';
                        }

                        if (!$error) {
                            $bank_line_id_from = $account_from->addline($object->record_date, $typefrom, $label, -1 * $amount_from, '', '', $user);
                            if (!($bank_line_id_from > 0)) {
                                setEventMessages($account_from->error, $account_from->errors, 'errors');
                                $error++;
                            }
                        }
                        if (!$error) {
                            $bank_line_id_to = $account_to->addline($object->record_date, $typeto, $label, $amount_to, '', '', $user);
                            if (!($bank_line_id_to > 0)) {
                                setEventMessages($account_to->error, $account_to->errors, 'errors');
                                $error++;
                            }
                        }

                        if (!$error) {
                            $result = $account_from->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT . $linkToBankLine . '?rowid=', '(banktransfert)', 'banktransfert');
                            if (!($result > 0)) {
                                setEventMessages($account_from->error, $account_from->errors, 'errors');
                                $error++;
                            }
                        }
                        if (!$error) {
                            $result = $account_to->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT . $linkToBankLine . '?rowid=', '(banktransfert)', 'banktransfert');
                            if (!($result > 0)) {
                                setEventMessages($account_to->error, $account_to->errors, 'errors');
                                $error++;
                            }
                        }

                        if (!$error) {
                            $result = $object->reconcile($user, $statement_number,$object->amount < 0 ? $bank_line_id_from : $bank_line_id_to);
                            if ($result < 0) {
                                setEventMessages($object->error, $object->errors, 'errors');
                                $error++;
                            }
                        }

                        if (!$error) {
                            if ($object->amount > 0) $mesgs = $langs->trans("Banking4DolibarrTransferFromDone", '<a href="bankentries_list.php?id=' . $account_from->id . '">' . $account_from->label . "</a>", price($amount_from), $langs->transnoentities("Currency" . $account_from->currency_code));
                            else $mesgs = $langs->trans("Banking4DolibarrTransferToDone", '<a href="bankentries_list.php?id=' . $account_to->id . '">' . $account_to->label . "</a>", price($amount_to), $langs->transnoentities("Currency" . $account_to->currency_code));
                            setEventMessages($mesgs, null, 'mesgs');
                            $db->commit();
                        } else {
                            $db->rollback();
                        }
                    } else {
                        $error++;
                        setEventMessages($langs->trans("ErrorFromToAccountsMustDiffers"), null, 'errors');
                    }
                }
			} elseif ($manual_reconciliation_type == 'salaries') {
				$fk_user = GETPOST("fk_user", 'int') > 0 ? GETPOST("fk_user", "int") : -1;
				$datesp = dol_mktime(12, 0, 0, GETPOST("datespmonth", 'int'), GETPOST("datespday", 'int'), GETPOST("datespyear", 'int'));
				$dateep = dol_mktime(12, 0, 0, GETPOST("dateepmonth", 'int'), GETPOST("dateepday", 'int'), GETPOST("dateepyear", 'int'));
				$label = GETPOST('label', 'alpha');
				$note = GETPOST('note','restricthtml');
				$amount = GETPOST('amount', 'int');
				$num_payment = GETPOST('num_payment', 'alphanohtml');
				$project_id = GETPOST('fk_project', 'int');

				$fuser = new User($db);
				$datep = empty($object->vdate) ? $object->record_date : $object->vdate;
				$datev = $object->record_date;
				$type_payment = $payment_mode_id > 0 ? $payment_mode_id : $payment_mode;
				$amount = price2num($amount, 'MT');

				if (empty($datesp) || empty($dateep) || empty($datep) || empty($datev)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
					$error++;
				}
				if (!($fk_user > 0)) {
					$langs->loadLangs(array("errors", "hrm"));
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Employee")), null, 'errors');
					$error++;
				}
				if (empty($amount)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
					$error++;
				}
				if (!($id > 0)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
					$error++;
				}

				if (!$error) {
					// Set user current salary as ref salary for the payment
					$result = $fuser->fetch($fk_user);
					if ($result < 0) {
						setEventMessages($fuser->error, $fuser->errors, 'errors');
						$error++;
					} elseif ($result == 0) {
						setEventMessages($langs->trans('User') . ' : ' . $langs->trans('ErrorRecordNotFound'), null, 'errors');
						$error++;
					}
				}

				$db->begin();

				if (!$error && $isV14p) {
					require_once DOL_DOCUMENT_ROOT . '/salaries/class/salary.class.php';
					$salary = new Salary($db);
					$salary->accountid = $id;
					$salary->fk_user = $fuser->id;
					$salary->datev = $datev;
					$salary->datep = $datep;
					$salary->amount = $amount;
					$salary->label = $label;
					$salary->datesp = $datesp;
					$salary->dateep = $dateep;
					$salary->note = $note;
					$salary->type_payment = $type_payment;
					$salary->fk_user_author = $user->id;
					$salary->fk_project = $project_id;
					$salary->salary = $fuser->salary;

					// Fill array 'array_options' with data from add form
					$ret = $extrafields->setOptionalsFromPost(null, $salary);
					if ($ret < 0) {
						$error++;
					}

					$ret = $salary->create($user);
					if ($ret < 0) {
						setEventMessages($salary->error, $salary->errors, 'errors');
						$error++;
					}
				}

				if (!$error) {
					if ($isV11p) {
						require_once DOL_DOCUMENT_ROOT . '/salaries/class/paymentsalary.class.php';
					} else {
						require_once DOL_DOCUMENT_ROOT . '/compta/salaries/class/paymentsalary.class.php';
					}

					$paymentsalary = new PaymentSalary($db);
					if ($isV14p) {
						$paymentsalary->chid = $salary->id;
						$paymentsalary->datepaye = $datep;
						$paymentsalary->amounts = array($salary->id => $amount); // Tableau de montant
						$paymentsalary->paiementtype = $type_payment;
						$paymentsalary->num_payment = $num_payment;
						$paymentsalary->note = $note;
						$paymentsalary->note_private = $note;
					} else {
						$paymentsalary->accountid = $id;
						$paymentsalary->fk_user = $fk_user;
						$paymentsalary->datev = $datev;
						$paymentsalary->datep = $datep;
						$paymentsalary->amount = $amount;
						$paymentsalary->label = $label;
						$paymentsalary->datesp = $datesp;
						$paymentsalary->dateep = $dateep;
						$paymentsalary->note = $note;
						$paymentsalary->type_payment = $type_payment;
						$paymentsalary->num_payment = $num_payment;
						$paymentsalary->fk_user_author = $user->id;
						$paymentsalary->fk_project = $project_id;
						$paymentsalary->salary = $fuser->salary;
					}

					$payment_salary_id = $paymentsalary->create($user, 1);
					if (!($payment_salary_id > 0)) {
						setEventMessages($paymentsalary->error, $paymentsalary->errors, 'errors');
						$error++;
					}

					if (!$error) {
						$result = $paymentsalary->fetch($payment_salary_id);
						if ($result < 0) {
							setEventMessages($paymentsalary->error, $paymentsalary->errors, 'errors');
							$error++;
						} elseif ($result == 0) {
							$langs->load("errors");
							setEventMessage($langs->trans('Salary') . ' : ' . $langs->trans('ErrorRecordNotFound'), 'errors');
						}
					}

					if (!$error && $isV14p) {
						$result = $paymentsalary->addPaymentToBank($user, 'payment_salary', '(SalaryPayment)', $id, '', '');
						if (!($result > 0)) {
							setEventMessages($paymentsalary->error, $paymentsalary->errors, 'errors');
							$error++;
						}
						else {
							$result = $paymentsalary->fetch($payment_salary_id);
							if ($result < 0) {
								setEventMessages($paymentsalary->error, $paymentsalary->errors, 'errors');
								$error++;
							} elseif ($result == 0) {
								$langs->load("errors");
								setEventMessage($langs->trans('Salary') . ' : ' . $langs->trans('ErrorRecordNotFound'), 'errors');
							}
						}
					}

					if (!$error) {
						$payment_salary_reconciled = price2num(-$paymentsalary->amount, 'MT') == price2num($object->remaining_amount_to_link, 'MT');
						$result = $object->link($user, $paymentsalary->fk_bank);
						if ($result > 0 && $payment_salary_reconciled) {
							$linked_lines = $object->getLinkedLinesToReconcile();
							if ($linked_lines < 0) {
								$result = $linked_lines;
							} else {
								foreach ($linked_lines as $line_id) {
									$result = $object->reconcile($user, $statement_number, $line_id, 1, 1);
									if ($result < 0) break;
								}
							}
						}
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}

					if (!$error) {
						setEventMessage($langs->trans($payment_salary_reconciled ? "Banking4DolibarrPaymentSalaryDoneAndReconciled" : "Banking4DolibarrPaymentSalaryDone"));
					}
				}

				if (!$error) {
					$db->commit();
				} else {
					$db->rollback();
				}
			} elseif ($manual_reconciliation_type == 'social_contribution') {
				$actioncode = GETPOST('actioncode', 'alpha');
                $label = GETPOST('label', 'alpha');
				$dateech = dol_mktime(GETPOST('echhour'), GETPOST('echmin'), GETPOST('echsec'), GETPOST('echmonth'), GETPOST('echday'), GETPOST('echyear'));
				$dateperiod = dol_mktime(GETPOST('periodhour'), GETPOST('periodmin'), GETPOST('periodsec'), GETPOST('periodmonth'), GETPOST('periodday'), GETPOST('periodyear'));
				$amount = GETPOST('amount', 'int');
				$project_id = GETPOST('fk_project', 'int');

				require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
				if (! empty($conf->projet->enabled))
				{
					include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
				}
				if (! empty($conf->accounting->enabled)) {
					include_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
				}

				$langs->loadLangs(array('compta', 'bills', 'banks'));

                $social_contribution = new ChargeSociales($db);
				$social_contribution->type				= $actioncode;
				$social_contribution->lib				= $label;
				$social_contribution->date_ech			= $dateech;
				$social_contribution->periode			= $dateperiod;
				$social_contribution->amount			= price2num($amount);
				$social_contribution->mode_reglement_id	= $payment_mode_id > 0 ? $payment_mode_id : $payment_mode;
				$social_contribution->fk_account		= $id;
				$social_contribution->fk_project		= $project_id;

				if (empty($social_contribution->type)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
					$error++;
				}
				if (empty($social_contribution->date_ech)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateDue")), null, 'errors');
					$error++;
				}
				if (empty($social_contribution->periode)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Period")), null, 'errors');
					$error++;
				}
				if (empty($social_contribution->amount)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
					$error++;
				}

                if (!$error) {
                    $db->begin();

					$social_contribution_id = $social_contribution->create($user);
					if (!($social_contribution_id > 0)) {
						setEventMessages($social_contribution->error, $social_contribution->errors, 'errors');
						$error++;
					}

					if (!$error) {
						$bank_line_ids = $budgetinsight_static->createPayment($user, $id, $social_contribution->element, $object->record_date, $social_contribution->mode_reglement_id, array($social_contribution->id => array('amount' => -$social_contribution->amount, 'multicurrency_amount' => 0)));
						if (is_array($bank_line_ids)) {
							$bank_line_ids = array_values($bank_line_ids);
							$social_contribution_reconciled = -price2num($social_contribution->amount, 'MT') == price2num($object->remaining_amount_to_link, 'MT');
							$result = $object->link($user, $bank_line_ids[0]);
							if ($result > 0 && $social_contribution_reconciled) {
								$linked_lines = $object->getLinkedLinesToReconcile();
								if ($linked_lines < 0) {
									$result = $linked_lines;
								} else {
									foreach ($linked_lines as $line_id) {
										$result = $object->reconcile($user, $statement_number, $line_id, 1, 1);
										if ($result < 0) break;
									}
								}
							}
							if ($result < 0) {
								setEventMessages($object->error, $object->errors, 'errors');
								$error++;
							}
						} else {
							setEventMessages($budgetinsight_static->error, $budgetinsight_static->errors, "errors");
							$error++;
						}
					}

					if (!$error) {
						setEventMessage($langs->trans($social_contribution_reconciled ? "Banking4DolibarrSocialContributionDoneAndReconciled" : "Banking4DolibarrSocialContributionDone"));
						$db->commit();
					} else {
						$db->rollback();
					}
                }
			} elseif ($manual_reconciliation_type == 'vat') {
				// $dateep = dol_mktime(12, 0, 0, GETPOST("dateepmonth", 'int'), GETPOST("dateepday", 'int'), GETPOST("dateepyear", 'int'));
				$label = GETPOST('label', 'alpha');
				$note = GETPOST('note','restricthtml');
				$type = GETPOST('type', 'int');
				$amount = GETPOST('amount', 'int');
				$num_payment = GETPOST('num_payment', 'alpha');

				$datep = empty($object->vdate) ? $object->record_date : $object->vdate;
				$datev = $object->record_date;
				$type_payment = $payment_mode_id > 0 ? $payment_mode_id : $payment_mode;
				$amount = price2num(($type == 1 ? -1 : 1) * abs($amount), 'MT');

				if (empty($datep) || empty($datev)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
					$error++;
				}
				if (empty($amount)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
					$error++;
				}
				if (!($id > 0)) {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
					$error++;
				}

				if (!$error) {
					$db->begin();

					require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
					$vat = new Tva($db);
					$vat->accountid = $id;
					$vat->fk_account = $id;
					$vat->datep = $datep;
					$vat->datev = $datev;
					$vat->amount = $amount;
					$vat->label = $label;
					$vat->note = $note;
					$vat->type_payment = $type_payment;
					$vat->num_payment = $num_payment;
					$vat->fk_user_author = $user->id;

					$vat_amount= 0;
					$vat_fk_bank= 0;
					if ($isV14p) {
						$ret = $vat->create($user);
						if ($ret < 0) {
							setEventMessages($vat->error, $vat->errors, 'errors');
							$error++;
						}

						if (!$error) {
							// Create a line of payments
							require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/paymentvat.class.php';
							$paiementvat = new PaymentVAT($db);
							$paiementvat->chid = $vat->id;
							$paiementvat->datepaye = $datep;
							$paiementvat->amounts = array($vat->id => $amount); // Tableau de montant
							$paiementvat->paiementtype = $type_payment;
							$paiementvat->num_payment = $num_payment;
							$paiementvat->note = $note;
							$paiementvat->note_private = $paiementvat->note;

							if (!$error) {
								$payment_vat_id = $paiementvat->create($user, 1);
								if (!($payment_vat_id > 0)) {
									setEventMessages($paiementvat->error, $paiementvat->errors, 'errors');
									$error++;
								}
							}

							if (!$error) {
								$result = $paiementvat->addPaymentToBank($user, 'payment_vat', '(VATPayment)', $id, '', '');
								if (!($result > 0)) {
									setEventMessages($paiementvat->error, $paiementvat->errors, 'errors');
									$error++;
								}
							}

							if (!$error) {
								$result = $paiementvat->fetch($payment_vat_id);
								if ($result < 0) {
									setEventMessages($paiementvat->error, $paiementvat->errors, 'errors');
									$error++;
								} elseif ($result == 0) {
									$langs->load("errors");
									setEventMessage($langs->trans('VAT') . ' : ' . $langs->trans('ErrorRecordNotFound'), 'errors');
								} else {
									$vat_amount = $paiementvat->amount;
									$vat_fk_bank = $paiementvat->fk_bank;
								}
							}
						}
					} else {
						$payment_vat_id = $vat->addPayment($user);
						if (!($payment_vat_id > 0)) {
							setEventMessages($vat->error, $vat->errors, 'errors');
							$error++;
						}

						if (!$error) {
							$result = $vat->fetch($payment_vat_id);
							if ($result < 0) {
								setEventMessages($vat->error, $vat->errors, 'errors');
								$error++;
							} elseif ($result == 0) {
								$langs->load("errors");
								setEventMessage($langs->trans('VAT') . ' : ' . $langs->trans('ErrorRecordNotFound'), 'errors');
							} else {
								$vat_amount = $vat->amount;
								$vat_fk_bank = $vat->fk_bank;
							}
						}
					}

					if (!$error) {
						$payment_vat_reconciled = price2num($vat_amount > 0 ? -abs($vat_amount) : abs($vat_amount), 'MT') == price2num($object->remaining_amount_to_link, 'MT');
						$result = $object->link($user, $vat_fk_bank);
						if ($result > 0 && $payment_vat_reconciled) {
							$linked_lines = $object->getLinkedLinesToReconcile();
							if ($linked_lines < 0) {
								$result = $linked_lines;
							} else {
								foreach ($linked_lines as $line_id) {
									$result = $object->reconcile($user, $statement_number, $line_id, 1, 1);
									if ($result < 0) break;
								}
							}
						}
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}

					if (!$error) {
						setEventMessage($langs->trans($payment_vat_reconciled ? "Banking4DolibarrPaymentVATDoneAndReconciled" : "Banking4DolibarrPaymentVATDone"));
						$db->commit();
					} else {
						$db->rollback();
					}
				}
            } elseif ($manual_reconciliation_type == 'various_payment') {
				$label = GETPOST('label', 'alpha');
				$sens = GETPOST('sens', 'int');
				$amount = GETPOST('amount', 'int');
				$num_payment = GETPOST('num_payment', 'alpha');
				$projectid = GETPOST('projectid', 'int');
				$category_transaction = GETPOST('category_transaction', 'alpha');
				$accountancy_code = GETPOST('accountancy_code', 'alpha');
				$subledger_account = GETPOST('subledger_account', 'alpha');

				$label = trim($label);
				$datep = $object->record_date;
				$datev = empty($object->vdate) ? $object->record_date : $object->vdate;
				$amount = price2num($amount, 'MT');
				$operation = $payment_mode_id > 0 ? $payment_mode_id : $payment_mode;
				$category_transaction = $category_transaction != -1 ? $category_transaction : "";
				$accountancy_code = $accountancy_code != -1 ? $accountancy_code : "";

				if (empty($label)) {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
					$error++;
				}
				if (empty($datep) || empty($datev)) {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
					$error++;
				}
				if (empty($amount)) {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
					$error++;
				}
				if (!empty($conf->accounting->enabled) && empty($accountancy_code)) {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountAccounting")), null, 'errors');
					$error++;
				}

				if (!$error) {
					$db->begin();

					$use_various_payment_card = !empty($conf->global->EASYA_VERSION) ||
						(!$isV9p && $conf->global->MAIN_FEATURES_LEVEL >= 1) ||
						($isV9p && empty($conf->global->BANK_USE_OLD_VARIOUS_PAYMENT));

					if ($use_various_payment_card) {
						require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/paymentvarious.class.php';
						$paymentvarious = new PaymentVarious($db);
						$paymentvarious->ref = '';
						$paymentvarious->accountid = $id;
						$paymentvarious->datep = $datep;
						$paymentvarious->datev = $datev;
						$paymentvarious->amount = $amount;
						$paymentvarious->label = $label;
						//$paymentvarious->note=GETPOST("note",'none');
						$paymentvarious->type_payment = $operation;
						$paymentvarious->num_payment = $num_payment;
						$paymentvarious->fk_user_author = $user->id;
						$paymentvarious->sens = $sens;
						$paymentvarious->fk_project = $projectid;
						$paymentvarious->category_transaction = $category_transaction;
						$paymentvarious->accountancy_code = $accountancy_code;
						$paymentvarious->subledger_account = $subledger_account != -1 ? $subledger_account : "";

						$payment_various_id = $paymentvarious->create($user);
						if (!($payment_various_id > 0)) {
							setEventMessages($paymentvarious->error, $paymentvarious->errors, 'errors');
							$error++;
						}

						if (!$error) {
							$result = $paymentvarious->fetch($payment_various_id);
							if ($result < 0) {
								setEventMessages($paymentvarious->error, $paymentvarious->errors, 'errors');
								$error++;
							} elseif ($result == 0) {
								$error++;
								$langs->load("errors");
								setEventMessage($langs->trans('VariousPayment') . ' : ' . $langs->trans('ErrorRecordNotFound'), 'errors');
							}
						}

						$bank_id = $paymentvarious->fk_bank;
					} else {
						$bank_id = $account->addline($datep, $operation, $label, $amount * ($sens > 0 ? 1 : -1), $num_payment, $category_transaction, $user, '', '', $accountancy_code);
						if ($bank_id < 0) {
							setEventMessages($account->error, $account->errors, 'errors');
							$error++;
						}
					}

					if (!$error) {
						$payment_various_reconciled = price2num($amount * ($sens > 0 ? 1 : -1), 'MT') == price2num($object->remaining_amount_to_link, 'MT');
						$result = $object->link($user, $bank_id);
						if ($result > 0 && $payment_various_reconciled) {
							$linked_lines = $object->getLinkedLinesToReconcile();
							if ($linked_lines < 0) {
								$result = $linked_lines;
							} else {
								foreach ($linked_lines as $line_id) {
									$result = $object->reconcile($user, $statement_number, $line_id, 1, 1);
									if ($result < 0) break;
								}
							}
						}
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}

					if (!$error) {
						setEventMessage($langs->trans($payment_various_reconciled ? "Banking4DolibarrPaymentVariousDoneAndReconciled" : "Banking4DolibarrPaymentVariousDone"));
						$db->commit();
					} else {
						$db->rollback();
					}
				}
			} elseif (preg_match('/^create_element_(.*)/i', $manual_reconciliation_type, $matches)) {
				$element_type = $matches[1];
				$element_id = $create_element_id > 0 ? $create_element_id : 0;

				$element_amount = 0;
				$unpaid_element_found = false;
				if ($element_id > 0) {
					// Test if the element exist in the unpaid element
					$sql = "SELECT amount FROM " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list" .
						" WHERE entity IN (" . $conf->entity . ")" .
						" AND element_type = '" . $db->escape($element_type) . "'" .
						" AND element_id = " . $element_id;
					$resql = $db->query($sql);
					if (!$resql) {
						dol_print_error($db);
						$error++;
					} elseif ($obj = $db->fetch_object($resql)) {
						$unpaid_element_found = true;
						$element_amount = price2num($obj->amount, 'MT');
					}
					$db->free($resql);
				}

				if (!$unpaid_element_found) {
					setEventMessage($langs->trans('Banking4DolibarrErrorElementNotCreatedOrNotValidated'), "errors");
					$error++;
				}

				if (!$error) {
					$payment_id = $payment_mode_id > 0 ? $payment_mode_id : $payment_mode;
					$db->begin();

					$remaining_amount = price2num($object->remaining_amount_to_link, 'MT');
					if ($element_type == 'invoice_supplier') $billing_amount = max($remaining_amount, $element_amount);
					else $billing_amount = min($remaining_amount, $element_amount);

					$bank_line_ids = $budgetinsight_static->createPayment($user, $id, $element_type, $object->record_date, $payment_id, array($element_id => array('amount' => $billing_amount, 'multicurrency_amount' => 0)));
					if (is_array($bank_line_ids)) {
						$result = $object->link($user, $bank_line_ids[0]);
						if ($result > 0 && $remaining_amount == $element_amount) {
							$linked_lines = $object->getLinkedLinesToReconcile();
							if ($linked_lines < 0) {
								$result = $linked_lines;
							} else {
								foreach ($linked_lines as $line_id) {
									$result = $object->reconcile($user, $statement_number, $line_id, 1, 1);
									if ($result < 0) break;
								}
							}
						}
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					} else {
						setEventMessages($budgetinsight_static->error, $budgetinsight_static->errors, "errors");
						$error++;
					}
					if (!$error) {
						$payment_mode_code = dol_getIdFromCode($db, $payment_id, 'c_paiement', 'id', 'code', 1);
						if ($payment_mode_code == 'CHQ' && $object->remaining_amount_to_link > 0) {
							$result = $budgetinsight_static->createCheckReceiptPayment($user, $id, $object->record_date, $bank_line_ids);
							if ($result < 0) {
								setEventMessages($budgetinsight_static->error, $budgetinsight_static->errors, 'errors');
								$error++;
							}
						}
					}

					if (!$error) {
						$db->commit();
						if ($action == 'saveandcreate') {
							$create_element_id = 0;
						} else {
							$close_box = true;
						}
					} else {
						$db->rollback();
					}
				}
			} elseif ($manual_reconciliation_type == 'chequereceipt') {
				if ($check_deposit_id > 0) {
					$db->begin();

					require_once DOL_DOCUMENT_ROOT . '/compta/paiement/cheque/class/remisecheque.class.php';
					$remisecheque = new RemiseCheque($db);
					$result = $remisecheque->fetch($check_deposit_id);
					if ($result > 0 && $remisecheque->statut == 0) $result = $remisecheque->validate($user);
					if ($result < 0) {
						setEventMessages($remisecheque->error, $remisecheque->errors, 'errors');
						$error++;
					} elseif ($result > 0) {
						// Get nb bank line linked
						$sql = 'SELECT b.rowid' .
							" FROM " . MAIN_DB_PREFIX . "bank AS b" .
							" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS brl ON brl.fk_bank = b.rowid" .
							' WHERE b.fk_bordereau = ' . $check_deposit_id .
							' AND brl.rowid IS NULL' .
							' GROUP BY b.rowid';

						$resql = $db->query($sql);
						if (!$resql) {
							dol_print_error($db);
							$error++;
						} else {
							$fk_bank_list = array();
							while ($obj = $db->fetch_object($resql)) {
								$fk_bank_list[] = $obj->rowid;
							}
							$db->free($resql);
						}
					} else {
						$langs->load("errors");
						setEventMessage($langs->trans("ErrorRecordNotFound"), 'errors');
						$error++;
					}

					if (!$error) {
						$idx = 0;
						$nb_lines = count($fk_bank_list);
						foreach ($fk_bank_list as $fk_bank) {
							$idx++;
							if ($idx == $nb_lines) {
								$result = $object->reconcile($user, $statement_number, $fk_bank, 0);
							} else {
								$result = $object->link($user, $fk_bank);
							}
							if ($result < 0) {
								setEventMessages($object->error, $object->errors, 'errors');
								$error++;
								break;
							}
						}
					}

					if (!$error) {
						// Define output language
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id'])) $newlang = $_REQUEST['lang_id'];
						//if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}
						$result = $remisecheque->generatePdf($remisecheque->modelpdf, $outputlangs);
						if ($result < 0) {
							setEventMessages($remisecheque->error, $remisecheque->errors, 'errors');
							$error++;
						}
					}

					if (!$error) {
						$db->commit();
					} else {
						$db->rollback();
					}
				} else {
					setEventMessages($langs->trans("NoRecordSelected"), null, "errors");
					$error++;
				}
			} elseif ($manual_reconciliation_type == 'widthdraw') {
				if ($standing_order_id > 0) {
					$amount = 0;
					$db->begin();

					require_once DOL_DOCUMENT_ROOT . '/compta/prelevement/class/bonprelevement.class.php';
					$bonprelevement = new BonPrelevement($db, "");
					$result = $bonprelevement->fetch($standing_order_id);
					if ($result == -1) {
						$langs->load("errors");
						setEventMessage($langs->trans("ErrorRecordNotFound"), 'errors');
						$error++;
					} elseif ($result == -2) {
						setEventMessage('Error ' . $this->db->lasterror(), 'errors');
						$error++;
					} elseif ($result > 0) {
						if ($bonprelevement->statut == 1) {
							$old_prelevement_id_bankaccount = $conf->global->PRELEVEMENT_ID_BANKACCOUNT;
							$conf->global->PRELEVEMENT_ID_BANKACCOUNT = $account->id;
							$result = $bonprelevement->set_infocredit($user, $object->record_date);
							if ($result < 0) {
								if ($result == -1025) $bonprelevement->errors[] = "Open SQL transaction impossible";
								elseif ($result == -1026) $bonprelevement->errors[] = "Already fetched";
								elseif ($result == -1027) $bonprelevement->errors[] = "Date de credit < Date de trans";
								else $bonprelevement->errors[] = "Error when set info credit: $result";
								$this->error = $bonprelevement->error;
								setEventMessages($bonprelevement->error, $bonprelevement->errors, 'errors');
								$error++;
							}
							$conf->global->PRELEVEMENT_ID_BANKACCOUNT = $old_prelevement_id_bankaccount;
						} elseif ($bonprelevement->statut == 2 && $bonprelevement->date_credit != $object->record_date) {
							$sql = " UPDATE " . MAIN_DB_PREFIX . "prelevement_bons ";
							$sql .= " SET date_credit = '" . $db->idate($object->record_date) . "'";
							$sql .= " WHERE rowid = " . $standing_order_id;
							$sql .= " AND entity = " . $conf->entity;
							$resql = $db->query($sql);
							if (!$resql) {
								dol_print_error($db);
								$error++;
							}
						}
					} else {
						$bonprelevement->errors[] = "Error when fetch widthdraw (ID: $standing_order_id): $result";
						setEventMessages($bonprelevement->error, $bonprelevement->errors, 'errors');
						$error++;
					}

					if (!$error) {
						// Get nb bank line linked
						$sql = 'SELECT bu.fk_bank, b.amount' .
							" FROM " . MAIN_DB_PREFIX . "bank_url AS bu" .
							" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS brl ON brl.fk_bank = bu.fk_bank" .
							" LEFT JOIN " . MAIN_DB_PREFIX . "bank AS b ON b.rowid = bu.fk_bank" .
							' WHERE bu.url_id = ' . $standing_order_id .
							" AND bu.type = 'withdraw'" .
							' AND brl.rowid IS NULL' .
							' GROUP BY bu.fk_bank';

						$resql = $db->query($sql);
						if (!$resql) {
							dol_print_error($db);
							$error++;
						} else {
							$fk_bank_list = array();
							while ($obj = $db->fetch_object($resql)) {
								$fk_bank_list[] = $obj->fk_bank;
								$amount += $obj->amount;
							}
							$db->free($resql);
						}
					}

					if (!$error) {
						$idx = 0;
						$nb_lines = count($fk_bank_list);
						foreach ($fk_bank_list as $fk_bank) {
							$idx++;
							$result = $object->link($user, $fk_bank);
							if ($result < 0) {
								setEventMessages($object->error, $object->errors, 'errors');
								$error++;
								break;
							}
						}
					}

					if (!$error) {
						$standing_order_reconciled = price2num($amount, 'MT') == price2num($object->remaining_amount_to_link, 'MT');
						if ($result > 0 && $standing_order_reconciled) {
							$linked_lines = $object->getLinkedLinesToReconcile();
							if ($linked_lines < 0) {
								$result = $linked_lines;
							} else {
								foreach ($linked_lines as $line_id) {
									$result = $object->reconcile($user, $statement_number, $line_id, 1, 1);
									if ($result < 0) break;
								}
							}
						}
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}

					if (!$error) {
						$db->commit();
					} else {
						$db->rollback();
					}
				} else {
					setEventMessages($langs->trans("NoRecordSelected"), null, "errors");
					$error++;
				}
			}
        }
    }
}
if (!$error && $action == 'save') {
    // Refresh remaining amount to link
    $object->fetchRemaingAmountToLink();

    if (price2num($object->remaining_amount_to_link, 'MT') == 0 || $bank_id_linked_to_multi_bank_record > 0 || $close_box) {
        // Close the box and refresh the bank record line list
        print '<html><head></head><body><script type="text/javascript">window.parent.b4d_close_manual_reconciliation_box();</script></body></html>';
        exit(0);
    }
}


/*
 * View
 */

$form = new Form($db);
$css_files = array(
	'/banking4dolibarr/css/banking4dolibarr.css.php',
	'/banking4dolibarr/css/banking4dolibarr.manualreconciliation.css.php',
);
if (!empty($conf->revolutionpro->enabled)) {
	$css_files[] = '/banking4dolibarr/css/revolution_fix.css.php';
}
if (!empty($conf->breadcrumb->enabled)) {
    $css_files[] = '/banking4dolibarr/css/breadcrumb_fix.css.php';
}

llxHeader('', '', '', '', 0, 0, array(), $css_files);

print '<form method="POST" id="b4d_save_form" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" id="b4d_save_form_action" value="list">';
print '<input type="hidden" name="id" value="' . $id . '">';
print '<input type="hidden" name="row_id" id="row_id" value="' . $row_id . '">';

print '<table class="tagtable liste">' . "\n";

// Fields title
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Description"));
print_liste_field_titre($langs->trans("Comment"));
print_liste_field_titre($langs->trans("DateOperationShort"), '', '', '', '', 'align="center"');
print_liste_field_titre($langs->trans("DateValueShort"), '', '', '', '', 'align="center"');
print_liste_field_titre($langs->trans("Type"), '', '', '', '', 'align="center"');
print_liste_field_titre($langs->trans("Banking4DolibarrCategory"));
print_liste_field_titre($langs->trans("Debit"), '', '', '', '', '', '', '', "right ");
print_liste_field_titre($langs->trans("Credit"), '', '', '', '', '', '', '', "right ");
print '</tr>' . "\n";

print '<tr class="oddeven">';
print '<td>';
print $object->label;
print "</td>\n";
print '<td>';
print $object->comment;
print "</td>\n";
print '<td align="center">';
if (!empty($object->record_date)) print dol_print_date($object->record_date, 'day');
print "</td>\n";
print '<td align="center">';
if (!empty($object->vdate)) print dol_print_date($object->vdate, 'day');
elseif (!empty($object->record_date)) print dol_print_date($object->record_date, 'day');
print "</td>\n";
print '<td align="center">';
print $object->LibType($object->record_type);
if (!empty($manual_reconciliation_types[$manual_reconciliation_type]['payment_mode_require']) && !($payment_mode_id > 0)) {
    print ' : ';
    $form->select_types_paiements($payment_mode, 'payment_mode', $object->remaining_amount_to_link < 0 ? 'DBIT' : 'CRDT', 0, 1);
}
print "</td>\n";
print '<td>';
if ($object->id_category > 0) {
	if (isset($category_infos["label"])) {
		print $category_infos["label"];
	} else {
		print $langs->trans('Unknown');
	}
}
print "</td>\n";
print '<td class="nowrap right">';
if ($object->remaining_amount_to_link < 0) {
    print price($object->remaining_amount_to_link * -1);
}
print "</td>\n";
print '<td class="nowrap right">';
if ($object->remaining_amount_to_link > 0) {
    print price($object->remaining_amount_to_link);
}
print "</td>\n";
print "</tr>\n";

print '</table>' . "\n";

print '<br>' . "\n";

print '<div class="center">' . "\n";
print $langs->trans('Banking4DolibarrManuelReconciliationType') . ' : ';
$manual_reconciliation_types_arr = array();
foreach ($manual_reconciliation_types as $k => $v) {
    $manual_reconciliation_types_arr[$k] = $v['label'];
}
print $form->selectarray('manual_reconciliation_type', $manual_reconciliation_types_arr, $manual_reconciliation_type, 1, 0, 0, '', '', 0, 0, '', 'minwidth200');
print <<<SCRIPT
<script type="text/javascript">
$(document).ready(function(){
    $('#manual_reconciliation_type').on('change', function() {
        $('#b4d_save_form_action ').val('update_manual_reconciliation_type');
        $('#b4d_save_form').submit();
    });
});
</script>
SCRIPT;
print '</div>' . "\n";

print '<br>' . "\n";

if (preg_match('/^create_element_(.*)/i', $manual_reconciliation_type, $matches)) {
	$element_type = $matches[1];
    $element_infos = $create_element_infos[$element_type];

    $url_create = '';
    if (!empty($element_infos) && !empty($element_infos['card_path'])) {
        $payment_id = $payment_mode_id > 0 ? $payment_mode_id : $payment_mode;
		$url_create = dol_buildpath($element_infos['card_path'], 1) . '?action=create&b4d_embed_page_id='.$row_id.'&b4d_payment_mode='.$payment_id.'&b4d_bank_account='.$account->id;
    }

    if ($create_element_id > 0) {
    	$url = dol_buildpath($element_infos['card_path'], 1) . '?b4d_embed_page_id='.$row_id.'&id='.$create_element_id;
	} else {
		$url = $url_create;
	}

    if (empty($url)) {
        setEventMessage($langs->trans('Banking4DolibarrErrorCardNotFound'), 'errors');
    } else {
    	$ActionsBanking4Dolibarr = new ActionsBanking4Dolibarr($db);
    	$page_urls = json_encode($ActionsBanking4Dolibarr->get_embed_page_urls($element_type));
    	$element_id_parameter_names = json_encode($element_infos['id_parameter_names']);

        print '<iframe id="create_element_box_iframe" src="' . $url . '" style="width: 100%; border: none;"></iframe>';
		print '<input type="hidden" name="b4d_create_element_id" id="b4d_create_element_id" value="">';
        print <<<SCRIPT
    <script type="text/javascript">
    	window.b4d_embed_page_id = $row_id;
        $(document).ready(function(){
            var create_element_box_iframe = $('#create_element_box_iframe');
            var manual_reconciliation_type_action = $('#manual_reconciliation_type_action');
            var height = window.innerHeight - create_element_box_iframe.position().top - manual_reconciliation_type_action.outerHeight(true);
            create_element_box_iframe.css({'height' : height + 'px'});
			
            var b4d_embed_urls = $.makeArray($page_urls);
            iframeURLChange(document.getElementById("create_element_box_iframe"), function (newURL) {
				var _this = document.getElementById("create_element_box_iframe");
				var _href = _this.contentWindow.location.href;
				var is_embed_url = false;

				$.map(b4d_embed_urls, function(url) {
					if (_href.indexOf(url) >= 0) is_embed_url = true;
				});

				if (!is_embed_url) {
					_this.contentWindow.location.href = "$url_create";
				} else {
				    var params = getJsonFromUrl(_this.contentWindow.location.href);
				    var element_id = '';
				    if (typeof params.id !== 'undefined') element_id = params.id;
				    else {
				        var b4d_id_parameter_names = $.makeArray($element_id_parameter_names);
				        $.each(b4d_id_parameter_names, function(idx, id_parameter_name) {
							if (typeof params[id_parameter_name] !== 'undefined') {
							    element_id = params[id_parameter_name];
							    return false;
							}
						});
				    }
				    $('#b4d_create_element_id').val(element_id);
				}
            });

            /*
            	Code by : Hristiyan Dodov
            	https://stackoverflow.com/questions/2429045/iframe-src-change-event-detection
             */
			function iframeURLChange(iframe, callback) {
				var unloadHandler = function () {
					// Timeout needed because the URL changes immediately after
					// the `unload` event is dispatched.
					setTimeout(function () {
						callback(iframe.contentWindow.location.href);
					}, 0);
				};
				
				function attachUnload() {
					// Remove the unloadHandler in case it was already attached.
					// Otherwise, the change will be dispatched twice.
					iframe.contentWindow.removeEventListener("unload", unloadHandler);
					iframe.contentWindow.addEventListener("unload", unloadHandler);
				}
				
				iframe.addEventListener("load", attachUnload);
				attachUnload();
			}

			/*
            	Code by : Jan Turoň
            	https://stackoverflow.com/questions/8486099/how-do-i-parse-a-url-query-parameters-in-javascript
             */
			function getJsonFromUrl(url) {
				if(!url) url = location.href;
				var question = url.indexOf("?");
				var hash = url.indexOf("#");
				if(hash==-1 && question==-1) return {};
				if(hash==-1) hash = url.length;
				var query = question==-1 || hash==question+1 ? url.substring(hash) : 
				url.substring(question+1,hash);
				var result = {};
				query.split("&").forEach(function(part) {
					if(!part) return;
					part = part.split("+").join(" "); // replace every + with space, regexp-free version
					var eq = part.indexOf("=");
					var key = eq>-1 ? part.substr(0,eq) : part;
					var val = eq>-1 ? decodeURIComponent(part.substr(eq+1)) : "";
					var from = key.indexOf("[");
					if(from==-1) result[decodeURIComponent(key)] = val;
					else {
						var to = key.indexOf("]",from);
						var index = decodeURIComponent(key.substring(from+1,to));
						key = decodeURIComponent(key.substring(0,from));
						if(!result[key]) result[key] = [];
						if(!index) result[key].push(val);
						else result[key][index] = val;
					}
				});
				return result;
			}
        });
    </script>
SCRIPT;
    }
} else {
    print '<div id="manual_reconciliation_type_content" style="overflow: auto;">' . "\n";
    if (!empty($manual_reconciliation_type)) {
        // Output template part (modules that overwrite templates must declare this into descriptor)
        $dirtpls = array_merge($conf->modules_parts['tpl'], array('/banking4dolibarr/tpl'));
        foreach ($dirtpls as $reldir) {
            $res = @include dol_buildpath($reldir . '/b4d_manual_reconciliation_' . $manual_reconciliation_type . '.tpl.php');
            if ($res) {
                break;
            }
        }
        if (!$res) setEventMessage($langs->trans('Banking4DolibarrErrorUnknownManuelReconciliationType', $manual_reconciliation_type), 'errors');
    }
    print '</div>' . "\n";
    print <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function() {
			$('body').css({'height' : Math.floor(window.innerHeight - 5) + 'px'});        

            var manual_reconciliation_type_content = $('#manual_reconciliation_type_content');
            var manual_reconciliation_type_action = $('#manual_reconciliation_type_action');
            var result_lines = $('#result_lines');
            var height = window.innerHeight - manual_reconciliation_type_content.position().top - manual_reconciliation_type_action.outerHeight(true);
        
			manual_reconciliation_type_content.css({'height' : height + 'px'});        
            if (result_lines.length) {
            	var result_table = result_lines.closest('table');
            	var result_foot = $('#result_foot');
				var content_height = height;
            	var head_height = result_lines.offset().top - manual_reconciliation_type_content.offset().top;
            	var table_bottom = parseFloat(result_table.css('padding-bottom').replace("px", "")) + parseFloat(result_table.css('border-bottom-width').replace("px", "")) + parseFloat(result_table.css('margin-bottom').replace("px", ""));
            	var foot_height = result_foot.length ? result_foot.outerHeight(true) : 0;
                var tbody_height = Math.floor(content_height - head_height - table_bottom - foot_height);

                result_lines.css({'max-height': tbody_height + 'px'});
                result_lines.find('a').prop('target', '_blank');
            }
        });
    </script>
SCRIPT;
}

print '</form>' . "\n";

print '<div id="manual_reconciliation_type_action" class="tabsAction tabsActionNoBottom">';
print '<div class="inline-block divButAction noMarginBottom"><a class="butAction" href="javascript:window.parent.b4d_close_manual_reconciliation_box();" >' . $langs->trans('Banking4DolibarrCloseBox') . '</a></div>';
print '<div class="inline-block divButAction noMarginBottom"><a class="butAction" href="javascript:$(\'#b4d_save_form_action\').val(\'save\');$(\'#b4d_save_form\').submit();" >' . $langs->trans('Save') . '</a></div>';
print '<div class="inline-block divButAction noMarginBottom" id="b4d_save_and_create" style="display: none;"><a class="butAction" href="javascript:$(\'#b4d_save_form_action\').val(\'saveandcreate\');$(\'#b4d_save_form\').submit();" >' . $langs->trans('Banking4DolibarrSaveAndCreate') . '</a></div>';
print '</div>';


llxFooter();

$db->close();
