<?php
/* Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/banking4dolibarr/class/budgetinsight.class.php
 * \ingroup banking4dolibarr
 * \brief
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
if (!class_exists('ComposerAutoloaderInite5f8183b6b110d1bbf5388358e7ebc94', false)) dol_include_once('/banking4dolibarr/vendor/autoload.php');
dol_include_once('/banking4dolibarr/lib/banking4dolibarr.lib.php');
dol_include_once('/advancedictionaries/class/dictionary.class.php');
use OAuth\Common\Storage\DoliStorage;
use OAuth\OAuth2\Token\StdOAuth2Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * Class BudgetInsight
 *
 * Put here description of your class
 */
class BudgetInsight
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array   Cache of Dolibarr bank accounts list (infos: iban)
     */
    static public $dolibarr_bank_accounts_cached;
    /**
     * @var DictionaryLine[]    Cache of banks list
     */
    static public $banks_cached;
    /**
     * @var DictionaryLine[]    Cache of bank accounts list
     */
    static public $bank_accounts_cached;
    /**
     * @var DictionaryLine[]    Cache of bank account types list
     */
    static public $bank_account_types_cached;

	/**
	 * @var Client  Client REST handler
	 */
	public $client;
    /**
     * @var string  API URI for Budget Insight
     */
    public $api_uri;
    /**
     * @var string  Webview language for Budget Insight
     */
    public $webview_language;
    /**
     * @var string  Client ID for Budget Insight
     */
    public $client_id;
	/**
	 * @var string  Key for Budget Insight
	 */
	public $key;
	/**
	 * @var string  Customer ID for Budget Insight
	 */
	public $customer_id;
	/**
	 * @var int  Number of maximum bank linked
	 */
	public $bank_quota;
	/**
	 * @var int  Number of maximum bank account linked
	 */
	public $bank_account_quota;
	/**
	 * @var string  Bridge url for Budget Insight
	 */
	public $bridge_url;
    /**
     * @var string  Code for Budget Insight
     */
    public $code;

    const SERVICE_NAME = 'BudgetInsight';

	const METHOD_GET = 'GET';
	const METHOD_HEAD = 'HEAD';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PUT = 'PUT';
	const METHOD_PATCH = 'PATCH';
	const METHOD_POST = 'POST';
	const METHOD_OPTIONS = 'OPTIONS';

    const DEFAULT_REQUEST_LIMIT = 100;
    const MAX_REQUEST_LIMIT = 500;
    const DEFAULT_AUTO_LINK_LIMIT = 100;

    const PROCESS_KEY_REFRESH_BANK_RECORDS = 'RBR';
    const PROCESS_KEY_LINK_BANK_RECORDS = 'LBR';

    const STATEMENT_NUMBER_RULE_DAILY = 1;
    const STATEMENT_NUMBER_RULE_WEEKLY = 2;
    const STATEMENT_NUMBER_RULE_MONTHLY = 3;
    const STATEMENT_NUMBER_RULE_QUARTERLY = 4;
    const STATEMENT_NUMBER_RULE_FOUR_MONTHLY = 5;
    const STATEMENT_NUMBER_RULE_YEARLY = 6;

	const REFRESH_BANK_RECORDS_RULE_DEBIT_CREDIT = 1;
	const REFRESH_BANK_RECORDS_RULE_DEBIT = 2;
	const REFRESH_BANK_RECORDS_RULE_CREDIT = 3;

	/**
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
	    global $conf;
        $this->db = $db;

        if (!empty($conf->global->BANKING4DOLIBARR_MODULE_KEY)) {
			dol_include_once('/banking4dolibarr/class/module_key/opendsimodulekeyb4d.class.php');
			$result = OpenDsiModuleKeyB4D::decode($conf->global->BANKING4DOLIBARR_MODULE_KEY);
			if (!empty($result['error'])) {
				setEventMessage($result['error'], 'errors');
			} else {
				$module_key_infos = $result['key'];
				$this->api_uri = $module_key_infos->api_url;
				$this->webview_language = $module_key_infos->webview_language;
				$this->client_id = $module_key_infos->client_id;
				$this->key = $module_key_infos->key;
				$this->customer_id = $module_key_infos->customer_id;
				$this->bank_quota = !empty($module_key_infos->bank_quota) ? $module_key_infos->bank_quota : 0;
				$this->bank_account_quota = !empty($module_key_infos->bank_account_quota) ? $module_key_infos->bank_account_quota : 0;
				$this->bridge_url = $module_key_infos->bridge_url;
			}
		}
	}

    /**
     *  Connect to the EDEDOC API
     *
     * @return	int		                <0 if KO, >0 if OK
     */
    public function connection()
    {
        global $conf, $langs;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $this->errors = array();
        $this->client = null;

        if (empty($this->api_uri)) {
            $langs->load('banking4dolibarr@banking4dolibarr');
            $this->errors[] = $langs->trans("Banking4DolibarrErrorModuleNotConfigured");
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        try {
            $this->client = new Client([
                // Base URI is used with relative requests
                'base_uri' => $this->api_uri,
                // You can set any number of default request options.
                'timeout' => ($conf->global->BANKING4DOLIBARR_API_TIMEOUT > 0 ? $conf->global->BANKING4DOLIBARR_API_TIMEOUT : 10),
            ]);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     *  Load code
     *
     * @return	int	            <0 if KO, >0 if OK
     */
    public function fetchCode()
    {
        global $langs;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $langs->load('banking4dolibarr@banking4dolibarr');
        $this->errors = array();
        $this->code = '';

        $code = $this->getTemporaryCode();
        if (is_numeric($code) && $code < 0) {
            return -1;
        }

        $this->code = $code;

        return 1;
    }

	/**
	 *  Refresh all bank accounts from Budget Insight
	 *
     * @param   User    $user       User who make the action
     * @return	int	                <0 if KO, >0 if OK
	 */
    public function refreshBankAccounts($user)
    {
        global $langs, $conf;
        dol_syslog(__METHOD__ . " user_id={$user->id}", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");
        $this->error = '';
        $this->errors = array();

        $result = $this->loadBanks();
        if ($result < 0) return -1;
        $result = $this->loadBankAccounts();
        if ($result < 0) return -1;
        $result = $this->loadBankAccountTypes();
        if ($result < 0) return -1;

        $results = $this->_sendToApi(self::METHOD_GET, '/users/me/accounts', [
            GuzzleHttp\RequestOptions::QUERY => [
                'expand' => 'connection',
            ]
        ]);
        if (!is_array($results)) {
            return -1;
        }

        $result = $this->refreshBankRecordCategories($user);
        if ($result < 0) {
            return -1;
        }

        if (is_array($results['accounts'])) {
			// Bank quota
			$bank_list_tmp = array();
			$banks_quota_list = array();
			foreach ($results['accounts'] as $key => $account) {
				if (empty($this->bank_quota) || in_array($account['id_connection'], $bank_list_tmp) || count($bank_list_tmp) < $this->bank_quota) {
					$bank_list_tmp[] = $account['id_connection'];
					$banks_quota_list[$key] = $account;
				}
			}
			if (count($results['accounts']) != count($banks_quota_list)) {
				$this->errors[] = $langs->trans('Banking4DolibarrWarningBankQuota', $this->bank_quota);
			}

			// Bank account quota
			if (!empty($this->bank_account_quota)) {
				$bank_accounts_list = array_slice($banks_quota_list, 0, $this->bank_account_quota);
				if (count($banks_quota_list) != count($bank_accounts_list)) {
					$this->errors[] = $langs->trans('Banking4DolibarrWarningBankAccountQuota', $this->bank_account_quota);
				}
			} else {
				$bank_accounts_list = $banks_quota_list;
			}

			$banks_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbanks');
            $bank_accounts_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankaccounts');
            $bank_account_types_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankaccounttypes');
            $now = dol_now();
            $bank_accounts_defined = array();
            foreach ($bank_accounts_list as $account) {
				if (!empty($account['connection']['state'])) {
					$bank_link_connection =  dol_buildpath('/banking4dolibarr/admin/accounts.php', 1) . "?action=b4d_manage_bank_accounts";
					$this->errors[] = $langs->trans('Banking4DolibarrErrorBankConnection') . ' :<br>';
					$this->errors[] = $langs->trans('Banking4DolibarrErrorApi_' . strtolower($account['connection']['state']), $bank_link_connection) . "<br>";
					return -1;
				} elseif (!empty($account['connection']['error']) || !empty($account['connection']['error_message'])) {
					$bank_link_connection =  dol_buildpath('/banking4dolibarr/admin/accounts.php', 1) . "?action=b4d_manage_bank_accounts";
					$this->errors[] = $langs->trans('Banking4DolibarrErrorBankConnection') . ' :<br>';
					if (!empty($account['connection']['error'])) {
						$this->errors[] = $langs->trans('Banking4DolibarrErrorApi_' . strtolower($account['connection']['error']), $bank_link_connection) . "<br>";
					}
					if (!empty($account['connection']['error_message'])) {
						$this->errors[] = $account['connection']['error_message'] . "<br>";
					}
					return -1;
				} elseif (!empty($account['error'])) {
                    $this->errors[] = $langs->trans('Banking4DolibarrErrorBankAccount') . ' :<br>';
                    $this->errors[] = $langs->trans('Banking4DolibarrErrorApi_' . strtolower($account['error'])) . "<br>";
                    return -1;
                }

                if (!isset(self::$banks_cached[$account['id_connection']])) {
                    $result = $this->refreshBanks($user);
                    if ($result < 0) {
                        return -1;
                    }
                }
                if (!empty($account['id_type']) && !isset(self::$bank_account_types_cached[$account['id_type']])) {
                    $result = $this->refreshBankAccountTypes($user);
                    if ($result < 0) {
                        return -1;
                    }
                }

                $line = isset(self::$bank_accounts_cached[$account['id']]) ? self::$bank_accounts_cached[$account['id']] : $bank_accounts_dictionary->getNewDictionaryLine();
				$line->is_rowid_defined_by_code = true;

                $error = 0;
                $line->db->begin();

                $bank_account_id = !empty($line->fields['fk_bank_account']) ? $line->fields['fk_bank_account'] : 0;
                if (!empty($conf->global->BANKING4DOLIBARR_AUTO_LINK_BANK_ACCOUNT)) {
                    $result = $this->findDolibarrBankAccount($account['iban']);
                    if ($result < 0) $error++;
                    elseif ($bank_account_id == 0 || $result > 0) $bank_account_id = $result;

                    if (!$error && !empty($conf->global->BANKING4DOLIBARR_AUTO_CREATE_BANK_ACCOUNT) && $bank_account_id == 0) {
                        require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                        $bank_account = new Account($line->db);

                        $bank_infos = isset(self::$banks_cached[$account['id_connection']]) ? self::$banks_cached[$account['id_connection']] : $banks_dictionary->getNewDictionaryLine();
                        $bank_account_type_infos = isset(self::$bank_account_types_cached[$account['id_type']]) ? self::$bank_account_types_cached[$account['id_type']] : $bank_account_types_dictionary->getNewDictionaryLine();

                        $bank_account->ref = !empty($bank_infos->fields['slug']) ? $bank_infos->fields['slug'] . $account['id'] : '';
                        $bank_account->label = dol_trunc((!empty($bank_infos->fields['slug']) ? $bank_infos->fields['slug'] . $bank_infos->id . ' - ' : '') . $account['name'], 27);
                        $bank_account->courant = isset($bank_account_type_infos->fields['type_of_bank_account']) && $bank_account_type_infos->fields['type_of_bank_account'] !== "" ? $bank_account_type_infos->fields['type_of_bank_account'] :
                            (!empty($conf->global->BANKING4DOLIBARR_BANK_ACCOUNT_DEFAULT_TYPE) ? $conf->global->BANKING4DOLIBARR_BANK_ACCOUNT_DEFAULT_TYPE : Account::TYPE_CURRENT);
                        $bank_account->date_solde = $now;
                        $bank_account->country_id = !empty($conf->global->BANKING4DOLIBARR_BANK_ACCOUNT_DEFAULT_COUNTRY_ID) ? $conf->global->BANKING4DOLIBARR_BANK_ACCOUNT_DEFAULT_COUNTRY_ID : 1;
                        $bank_account->clos = 0;
                        $bank_account->currency_code = !empty($account['currency']['id']) ? $account['currency']['id'] : '';
                        $bank_account->bank = !empty($bank_infos->fields['label']) ? $bank_infos->fields['label'] : '';
                        $bank_account->code_banque = !empty($bank_infos->fields['code']) ? $bank_infos->fields['code'] : '';
                        $bank_account->bic = $account['bic'];
                        $bank_account->iban = $account['iban'];

                        $result = $bank_account->create($user);
                        if ($result < 0) {
                            $this->error = $bank_account->error;
                            $this->errors = $bank_account->errors;
                            dol_syslog(__METHOD__ . " - Create bank account. Error: " . $this->errorsToString(), LOG_ERR);
                            $error++;
                        } else $bank_account_id = $result;
                    }
                }

                $fields = [
                    'label' => $account['name'],
                    'type_id' => $account['id_type'],
                    'currency_code' => !empty($account['currency']['id']) ? $account['currency']['id'] : '',
                    'bank_id' => $account['id_connection'],
                    'bic' => $account['bic'],
                    'iban' => $account['iban'],
                    'fk_bank_account' => $bank_account_id,
                    'last_update' => isset($account['last_update']) ? strtotime($account['last_update']) : dol_now(),
                    'datas' => json_encode($account),
                ];

                if (!$error) {
                    if ($line->id > 0) {
                        $result = $line->update($fields, $user);
                    } elseif (!($line->id < 0)) {
                        $line->id = $account['id'];
						$line->dictionary->is_rowid_defined_by_code = true;
                        $result = $line->insert($fields, $user);
                    }

                    $active = (!isset($results['deleted']) || $results['deleted']) && (!isset($results['disabled']) || $results['disabled']) ? 1 : 0;
                    if ($result > 0 && $active != $line->active) $result = $line->active($active, $user);

                    $bank_accounts_defined[$line->id] = $line->id;

                    if ($result < 0) {
                        $this->error = $line->error;
                        $this->errors = $line->errors;
                        dol_syslog(__METHOD__ . " - Create/Update line. Data: " . json_encode($fields) . " Error: " . $this->errorsToString(), LOG_ERR);
                        $error++;
                    }
                }

                if ($error) {
                    $line->db->rollback();
                    return -1;
                } else {
                    $line->db->commit();
                }
            }

            // Disable all line not downloaded
            $disable_lines = array_diff_key(self::$bank_accounts_cached, $bank_accounts_defined);
            if (count($disable_lines) > 0) {
                foreach ($disable_lines as $line) {
                    if ($line->id > 0 && !in_array($line->id, $bank_accounts_defined)) {
                        $result = $line->active(0, $user);
                        if ($result < 0) {
                            $this->error = $line->error;
                            $this->errors = $line->errors;
                            dol_syslog(__METHOD__ . " - Disable line not downloaded. Id line: " . $line->id . ". Error: " . $this->errorsToString(), LOG_ERR);
                            return -1;
                        }
                    }
                }
            }
        }

        return 1;
    }

    /**
     *  Refresh all bank records from Budget Insight of a bank account
     *
     * @param   User            $user               User who make the action
     * @param   int             $bank_account_id    Id of the bank account
     * @param   int             $start_date         Refresh data starting to this date
     * @param   int             $offset             Offset of first result returned
     * @param   string          $state              UUID for test of unique process
	 * @param   int             $first_date         Refresh data starting to this date for the first download
     * @return	int|array	                        <0 if KO, else infos the status of the refresh of the records : array('offset', 'total')
     */
    public function refreshBankRecords($user, $bank_account_id, $start_date, $offset=0, $state='', $first_date=0)
    {
        global $conf, $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, bank_account_id=$bank_account_id, start_date=$start_date, offset=$offset, state=$state", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");
        $this->error = '';
        $this->errors = array();

		$limit = min(self::MAX_REQUEST_LIMIT, max(0, !empty($conf->global->BANKING4DOLIBARR_REQUEST_LIMIT) ? $conf->global->BANKING4DOLIBARR_REQUEST_LIMIT : self::DEFAULT_REQUEST_LIMIT));
		$count = 0;
		$warning = 0;
        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
		$first_date = $first_date > 0 ? $first_date : 0;
		$start_date = $start_date > 0 ? $start_date : 0;
        $offset = $offset > 0 ? $offset : 0;
        if (empty($state)) $state = str_replace('.', '', uniqid('', true));

        $process_key = self::PROCESS_KEY_REFRESH_BANK_RECORDS . '_' . $bank_account_id;

        $result = $this->setProcessFlag($process_key, $state, true);
        if ($result < 0) {
            return -1;
        }

        try {
            $error = 0;

            $remote_bank_account_id = $this->getRemoteBankAccountID($bank_account_id);
            if ($remote_bank_account_id < 0) {
                $error++;
            }

            if (!$error) {
                if (empty($start_date) && empty($first_date)) {
                    $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrStartDate'));
                    $error++;
                }
                if (empty($remote_bank_account_id)) {
                    $this->errors[] = $langs->trans("Banking4DolibarrBankAccountNotLinked");
                    $error++;
                }
                if ($error) {
                    dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                }
            }

            if (empty($offset)) {
                $results = $this->_sendToApi(self::METHOD_GET, '/users/me/accounts/' . $remote_bank_account_id, [
                    GuzzleHttp\RequestOptions::QUERY => [
                        'expand' => 'connection',
                    ]
                ]);
                if (!is_array($results)) {
                    $error++;
                } else {
                    $_SESSION['banking4dolibarr_process'][$process_key]['account_balance'] = $results['balance'];
					if (!empty($results['connection']['state'])) {
						$bank_link_connection =  dol_buildpath('/banking4dolibarr/admin/accounts.php', 1) . "?action=b4d_manage_bank_accounts";
						$this->errors[] = $langs->trans('Banking4DolibarrErrorBankConnection') . ' :<br>';
						$this->errors[] = $langs->trans('Banking4DolibarrErrorApi_' . strtolower($results['connection']['state']), $bank_link_connection) . "<br>";
						$error++;
					} elseif (!empty($results['connection']['error']) || !empty($results['connection']['error_message'])) {
						$bank_link_connection =  dol_buildpath('/banking4dolibarr/admin/accounts.php', 1) . "?action=b4d_manage_bank_accounts";
						$this->errors[] = $langs->trans('Banking4DolibarrErrorBankConnection') . ' :<br>';
						if (!empty($results['connection']['error'])) {
							$this->errors[] = $langs->trans('Banking4DolibarrErrorApi_' . strtolower($results['connection']['error']), $bank_link_connection) . "<br>";
						}
						if (!empty($results['connection']['error_message'])) {
							$this->errors[] = $results['connection']['error_message'] . "<br>";
						}
						$error++;
					} elseif (!empty($results['error'])) {
						$this->errors[] = $langs->trans('Banking4DolibarrErrorBankAccount') . ' :<br>';
						$this->errors[] = $langs->trans('Banking4DolibarrErrorApi_' . strtolower($results['error'])) . "<br>";
						$error++;
					}
                }
            }

            if (!$error) {
            	$filter = [
					'limit' => $limit,
					'offset' => $offset,
					'all' => 'true',
				];
            	if (!empty($first_date)) {
					$filter['min_date'] = dol_print_date($first_date, 'standard');
				} else {
					$filter['last_update'] = dol_print_date($start_date, 'standard');
				}
				if ($conf->global->BANKING4DOLIBARR_REFRESH_BANK_RECORDS_RULES == self::REFRESH_BANK_RECORDS_RULE_DEBIT) {
					$filter['max_value'] = 0;
				} elseif ($conf->global->BANKING4DOLIBARR_REFRESH_BANK_RECORDS_RULES == self::REFRESH_BANK_RECORDS_RULE_CREDIT) {
					$filter['min_value'] = 0;
				}
                $results = $this->_sendToApi(self::METHOD_GET, '/users/me/accounts/' . $remote_bank_account_id . '/transactions', [
                    GuzzleHttp\RequestOptions::QUERY => $filter
                ]);
                if (!is_array($results)) {
                    $error++;
                }

                if (!$error) {
                    if (empty($offset)) {
                        $_SESSION['banking4dolibarr_process'][$process_key]['warning'] = 0;
                        $_SESSION['banking4dolibarr_process'][$process_key]['date'] = dol_now();
                        $_SESSION['banking4dolibarr_process'][$process_key]['max_last_update_date'] = $start_date;
                    }

                    $bank_record = new BudgetInsightBankRecord($this->db);
                    $bank_record_update = new BudgetInsightBankRecord($this->db);

                    $result = $bank_record->loadBankRecordCategories();
                    if ($result < 0) {
                        $error++;
                    }

                    if (!$error && is_array($results['transactions'])) {
                        $count = count($results['transactions']);
                        $now = $_SESSION['banking4dolibarr_process'][$process_key]['date'];
                        foreach ($results['transactions'] as $transactions) {
                            if (!empty($transactions['id_category']) && !isset(BudgetInsightBankRecord::$bank_record_categories_cached[$transactions['id_category']])) {
                                $result = $this->refreshBankRecordCategories($user);
                                if ($result < 0) {
                                    $error++;
                                    break;
                                }
                            }

                            $record_date = DateTime::createFromFormat('Y-m-d', $transactions['date']);
                            $record_date = is_object($record_date) ? $record_date->getTimestamp() : 0;
                            $rdate = DateTime::createFromFormat('Y-m-d', $transactions['rdate']);
                            $rdate = is_object($rdate) ? $rdate->getTimestamp() : 0;
                            $bdate = DateTime::createFromFormat('Y-m-d', $transactions['bdate']);
                            $bdate = is_object($bdate) ? $bdate->getTimestamp() : 0;
                            $vdate = DateTime::createFromFormat('Y-m-d', $transactions['vdate']);
                            $vdate = is_object($vdate) ? $vdate->getTimestamp() : 0;
                            $date_scraped = DateTime::createFromFormat('Y-m-d H:i:s', $transactions['date_scraped']);
                            $date_scraped = is_object($date_scraped) ? $date_scraped->getTimestamp() : 0;
                            $last_date_update = DateTime::createFromFormat('Y-m-d H:i:s', $transactions['last_update']);
                            $last_date_update = is_object($last_date_update) ? $last_date_update->getTimestamp() : 0;
                            $deleted_date = DateTime::createFromFormat('Y-m-d H:i:s', $transactions['deleted']);
                            $deleted_date = is_object($deleted_date) ? $deleted_date->getTimestamp() : 0;
                            $data = json_encode($transactions);

                            $bank_record->id_record = $transactions['id'];
                            $bank_record->id_account = $transactions['id_account'];
                            $bank_record->label = $transactions['original_wording'];
                            $bank_record->comment = $transactions['comment'];
                            $bank_record->id_category = $transactions['id_category'];
                            $bank_record->record_date = $record_date;
                            $bank_record->rdate = $rdate;
                            $bank_record->bdate = $bdate;
                            $bank_record->vdate = $vdate;
                            $bank_record->date_scraped = $date_scraped;
                            $bank_record->record_type = $transactions['type'];
                            $bank_record->original_country = $transactions['country'];
                            $bank_record->original_amount = $transactions['original_value'];
                            $bank_record->original_currency = $transactions['original_currency']['id'];
                            $bank_record->commission = $transactions['commission'];
                            $bank_record->commission_currency = $transactions['commission_currency']['id'];
                            $bank_record->amount = $transactions['value'];
                            $bank_record->coming = $transactions['coming'];
                            $bank_record->deleted_date = $deleted_date;
                            $bank_record->last_update_date = $last_date_update;
                            $bank_record->date_creation = $now;
                            $bank_record->datas = $data;

                            $result = $bank_record->insert($user);
                            if ($result == 0) {
                                $result = $bank_record_update->fetch('', '', $bank_record->id_record, $bank_record->id_account);
                                if ($result > 0) {
                                    if ($bank_record_update->last_update_date != $bank_record->last_update_date) {
                                        if ($bank_record_update->status != BudgetInsightBankRecord::BANK_RECORD_STATUS_RECONCILED) {
                                            $bank_record_update->label = $bank_record->label;
                                            $bank_record_update->comment = $bank_record->comment;
                                            $bank_record_update->id_category = $bank_record->id_category;
                                            $bank_record_update->record_date = $bank_record->record_date;
                                            $bank_record_update->rdate = $bank_record->rdate;
                                            $bank_record_update->bdate = $bank_record->bdate;
                                            $bank_record_update->vdate = $bank_record->vdate;
                                            $bank_record_update->date_scraped = $bank_record->date_scraped;
                                            $bank_record_update->record_type = $bank_record->record_type;
                                            $bank_record_update->original_country = $bank_record->original_country;
                                            $bank_record_update->original_amount = $bank_record->original_amount;
                                            $bank_record_update->original_currency = $bank_record->original_currency;
                                            $bank_record_update->commission = $bank_record->commission;
                                            $bank_record_update->commission_currency = $bank_record->commission_currency;
                                            $bank_record_update->amount = $bank_record->amount;
                                            $bank_record_update->coming = $bank_record->coming;
                                            $bank_record_update->deleted_date = $bank_record->deleted_date;
                                            $bank_record_update->last_update_date = $bank_record->last_update_date;
                                            $bank_record_update->datas = $bank_record->datas;
                                            $bank_record_update->date_modification = $now;

                                            $result = $bank_record_update->update($user);
                                        } else {
                                            dol_syslog(__METHOD__ . ' - Update on a downloaded bank record who is already linked. Record ID: ' . $bank_record->id_record . ' Data: ' . $data, LOG_WARNING);
                                            $_SESSION['banking4dolibarr_process'][$process_key]['warning'] = $_SESSION['banking4dolibarr_process'][$process_key]['warning'] + 1;
                                            $warning = $_SESSION['banking4dolibarr_process'][$process_key]['warning'];
                                        }
                                    }
                                }
                                if ($result < 0) {
                                    $this->error = $bank_record_update->error;
                                    $this->errors = array_merge($this->errors, $bank_record_update->errors);
                                    $error++;
                                    break;
                                }
                            } elseif ($result < 0) {
                                $this->error = $bank_record->error;
                                $this->errors = array_merge($this->errors, $bank_record->errors);
                                $error++;
                                break;
                            }

                            $_SESSION['banking4dolibarr_process'][$process_key]['max_last_update_date'] = max($_SESSION['banking4dolibarr_process'][$process_key]['max_last_update_date'], $last_date_update);
                        }
                    }
                }

                if (!$error) {
                    $offset = $offset + $count;
                    if ($count < $limit && $offset > 0) {
						$result = $this->setProcessLastUpdateDate($process_key, $_SESSION['banking4dolibarr_process'][$process_key]['max_last_update_date']);
						if ($result < 0) {
							$error++;
						}
					}
                }
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $error++;
        }

        $additional_text = '';
        if ($warning > 0) {
            $additional_text .= $langs->trans('Banking4DolibarrWarningUpdateBankRecordWhoIsAlreadyLinked', $warning) . "<br>";
        }

        if ($count < $limit) {
            require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
            $langs->load('banks');
            $account = new Account($this->db);
            $result = $account->fetch($bank_account_id);
            if ($result < 0) {
                $this->error = $account->error;
                $this->errors = array_merge($this->errors, $account->errors);
                $error++;
            } elseif ($result == 0) {
                $langs->load('errors');
                $this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ' . $langs->trans('BankAccount') . ' (' . $bank_account_id . ')';
                $error++;
            } else {
                $account->array_options['options_b4d_account_balance'] = $_SESSION['banking4dolibarr_process'][$process_key]['account_balance'];
                $account->array_options['options_b4d_account_update_date'] = $_SESSION['banking4dolibarr_process'][$process_key]['date'];
                $result = $account->insertExtraFields();
                if ($result < 0) {
                    $this->error = $account->error;
                    $this->errors = array_merge($this->errors, $account->errors);
                    $error++;
                }
            }
        }

        if ($error || $count < $limit) {
            $result = $this->closeRefreshBankRecords($bank_account_id, $state);
            if ($result < 0) $error++;
            if ($error) return -1;
        }

        return array('offset' => $offset, 'finish' => $count < $limit, 'state' => $state, 'additional_text' => $additional_text);
    }

    /**
     *  Is Refresh all bank records from Budget Insight of a bank account is started
     *
     * @param   int     $bank_account_id    Id of the bank account
     * @param   string  $state              UUID for test of unique process
     * @return	int	                        <0 if KO, =0 if No, >0 if Yes
     */
    public function isRefreshBankRecordsStarted($bank_account_id, $state=null)
    {
        global $langs;
        dol_syslog(__METHOD__ . " bank_account_id=$bank_account_id, state=$state", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        if (isset($state)) $state = trim($state);

        // Check parameters
        if (empty($bank_account_id)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }
        $process_key = self::PROCESS_KEY_REFRESH_BANK_RECORDS . '_' . $bank_account_id;

        $result = $this->isProcessFlagSet($process_key, $state);

        return $result > 0 ? 1 : ($result < 0 ? -1 : 0);
    }

    /**
     *  Close Refresh all bank records from Budget Insight of a bank account process
     *
     * @param   int         $bank_account_id    Id of the bank account
     * @param   string      $state              UUID for test of unique process
     * @param   bool        $forced             Force the update of the flag
     * @return	int	                            <0 if KO, =0 if No, >0 if Yes
     */
    public function closeRefreshBankRecords($bank_account_id, $state=null, $forced=false)
    {
        global $langs;
        dol_syslog(__METHOD__ . " bank_account_id=$bank_account_id, state=$state, forced=$forced", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        if (isset($state)) $state = trim($state);

        // Check parameters
        if (empty($bank_account_id) || (isset($state) && empty($state))) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $process_key = self::PROCESS_KEY_REFRESH_BANK_RECORDS . '_' . $bank_account_id;

        $result = $this->isRefreshBankRecordsStarted($bank_account_id, $state);
        if ($result < 0) {
            return -1;
        } elseif ($result == 0 && !$forced) {
            $this->errors[] = $langs->trans("Banking4DolibarrErrorProcessNotStarted");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        if (isset($_SESSION['banking4dolibarr_process'][$process_key]['max_last_update_date'])) unset($_SESSION['banking4dolibarr_process'][$process_key]['max_last_update_date']);
        if (isset($_SESSION['banking4dolibarr_process'][$process_key]['date'])) unset($_SESSION['banking4dolibarr_process'][$process_key]['date']);
        if (isset($_SESSION['banking4dolibarr_process'][$process_key]['warning'])) unset($_SESSION['banking4dolibarr_process'][$process_key]['warning']);

        $result = $this->setProcessFlag($process_key, $state, false, $forced);
        if ($result < 0) return -1;

        return 1;
    }

    /**
     *  Refresh all banks from Budget Insight
     *
     * @param   User    $user       User who make the action
     * @return	int	                <0 if KO, >0 if OK
     */
    public function refreshBanks($user)
    {
    	global $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}", LOG_DEBUG);
		$langs->load("banking4dolibarr@banking4dolibarr");
		$this->error = '';
        $this->errors = array();

        $result = $this->loadBanks();
        if ($result < 0) return -1;

        $results = $this->_sendToApi(self::METHOD_GET, '/users/me/connections', [
			GuzzleHttp\RequestOptions::QUERY => [
				'expand' => 'connector',
			]
		]);
        if (!is_array($results)) {
            return -1;
        }

        if (is_array($results['connections'])) {
			$global_error = 0;
			$global_errors = array();
            $banks_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbanks');
            foreach ($results['connections'] as $connection) {
				if (!empty($connection['state'])) {
					$bank_link_connection =  dol_buildpath('/banking4dolibarr/admin/accounts.php', 1) . "?action=b4d_manage_bank_accounts";
					$global_errors[] = $langs->trans('Banking4DolibarrErrorApi_' . strtolower($connection['state']), $bank_link_connection) . "<br>";
					$global_error++;
				} elseif (!empty($connection['error']) || !empty($connection['error_message'])) {
					$bank_link_connection =  dol_buildpath('/banking4dolibarr/admin/accounts.php', 1) . "?action=b4d_manage_bank_accounts";
					if (!empty($connection['error'])) {
						$global_errors[] = $langs->trans('Banking4DolibarrErrorApi_' . strtolower($connection['error']), $bank_link_connection) . "<br>";
					}
					if (!empty($connection['error_message'])) {
						$global_errors[] = $connection['error_message'] . "<br>";
					}
					$global_error++;
				}

				if (!$global_error) {
					$line = isset(self::$banks_cached[$connection['id']]) ? self::$banks_cached[$connection['id']] : $banks_dictionary->getNewDictionaryLine();

					$error = 0;
					$line->db->begin();

					$fields = [
						'slug' => !empty($connection['connector']['slug']) ? $connection['connector']['slug'] : '',
						'label' => !empty($connection['connector']['name']) ? $connection['connector']['name'] : '',
						'code' => !empty($connection['connector']['code']) ? $connection['connector']['code'] : '',
						'last_update' => isset($connection['last_update']) ? strtotime($connection['last_update']) : dol_now(),
						'datas' => json_encode($connection),
					];

					if (!$error) {
						if ($line->id > 0) {
							$result = $line->update($fields, $user);
						} else {
							$line->id = $connection['id'];
							$result = $line->insert($fields, $user);
						}

						$active = $results['active'] ? 1 : 0;
						if ($result > 0 && $active != $line->active) $result = $line->active($active, $user);

						if ($result < 0) {
							$global_errors[] = $line->errorsToString();
							dol_syslog(__METHOD__ . " - Create/Update line. Data: " . json_encode($fields) . " Error: " . $this->errorsToString(), LOG_ERR);
							$error++;
						}
					}

					if ($error) {
						$line->db->rollback();
						$global_error++;
					} else {
						$line->db->commit();
					}
				}
				if ($global_error) {
					$this->errors[] = $langs->trans('Banking4DolibarrErrorBankConnection') .
						' - ' . (!empty($connection['connector']['code']) ? $connection['connector']['code'] : '') .
						' - ' . (!empty($connection['connector']['slug']) ? $connection['connector']['slug'] : '') .
						' - ' . (!empty($connection['connector']['name']) ? $connection['connector']['name'] : '') .
						' :<br>';
					$this->errors = array_merge($this->errors, $global_errors);
				}
            }
            if ($global_error) return -1;
        }

        $result = $this->loadBanks(true);
        if ($result < 0) return -1;

        return 1;
    }

    /**
     *  Refresh all account types from Budget Insight
     *
     * @param   User    $user       User who make the action
     * @return	int	                <0 if KO, >0 if OK
     */
    public function refreshBankAccountTypes($user)
    {
        global $conf;
        dol_syslog(__METHOD__ . " user_id={$user->id}", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        $result = $this->loadBankAccountTypes();
        if ($result < 0) return -1;

        $results = $this->_sendToApi(self::METHOD_GET, '/users/me/account_types');
        if (!is_array($results)) {
            return -1;
        }

        /*
			checking		Checking account
			savings			Savings account
			deposit			Deposit account
			loan			Loan
			market			Market account
			joint			Joint account
			card			Card
			lifeinsurance	Life insurance account
			pee				Plan Épargne Entreprise
			perco			Plan Épargne Retraite
			article83		Article 83
			rsp				Réserve spéciale de participation
			pea				Plan d'épargne en actions
			capitalisation	Contrat de capitalisation
			perp			Plan d'épargne retraite populaire
			madelin			Contrat retraite Madelin
			unknown			Unknown account type
        */
        $current_type_list = !empty($conf->global->BANKING4DOLIBARR_ACCOUNT_CURRENT_TYPE) ? array_filter(array_map('trim', explode(',', (string)$conf->global->BANKING4DOLIBARR_ACCOUNT_CURRENT_TYPE)), 'strlen') : array('checking', 'joint', 'card');

        if (is_array($results['accounttypes'])) {
            $bank_account_types_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankaccounttypes');
            foreach ($results['accounttypes'] as $account_type) {
                require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                $line = isset(self::$bank_account_types_cached[$account_type['id']]) ? self::$bank_account_types_cached[$account_type['id']] : $bank_account_types_dictionary->getNewDictionaryLine();

                $fields = [
                    'label' => $account_type['display_name'],
                    'type_of_bank_account' => in_array($account_type['name'], $current_type_list) ? Account::TYPE_CURRENT : Account::TYPE_SAVINGS,
                    'datas' => json_encode($account_type),
                ];

                if ($line->id > 0) {
                    $result = $line->update($fields, $user);
                } else {
                    $line->id = $account_type['id'];
                    $result = $line->insert($fields, $user);
                }

                if ($result < 0) {
                    $this->error = $line->error;
                    $this->errors = $line->errors;
                    dol_syslog(__METHOD__ . " - Create/Update line. Data: " . json_encode($fields) . " Error: " . $this->errorsToString(), LOG_ERR);
                    return -1;
                }
            }
        }

        $result = $this->loadBankAccountTypes(true);
        if ($result < 0) return -1;

        return 1;
    }

    /**
     *  Refresh all record categories from Budget Insight
     *
     * @param   User    $user       User who make the action
     * @return	int	                <0 if KO, >0 if OK
     */
    public function refreshBankRecordCategories($user)
    {
        dol_syslog(__METHOD__ . " user_id={$user->id}", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        $bank_record = new BudgetInsightBankRecord($this->db);
        $result = $bank_record->loadBankRecordCategories();
        if ($result < 0) return -1;

        $results = $this->_sendToApi(self::METHOD_GET, '/users/me/categories/full');
        if (!is_array($results)) {
            return -1;
        }

        if (is_array($results['categories'])) {
            $bank_record_categories_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankrecordcategories');
            foreach ($results['categories'] as $category) {
                $line = isset(BudgetInsightBankRecord::$bank_record_categories_cached[$category['id']]) ? BudgetInsightBankRecord::$bank_record_categories_cached[$category['id']] : $bank_record_categories_dictionary->getNewDictionaryLine();

                $fields = [
                    'label' => !empty($category['name_displayed']) ? $category['name_displayed'] : $category['name'],
                    'color' => $category['color'],
                    'datas' => json_encode($category),
                ];

                if ($line->id > 0) {
                    $result = $line->update($fields, $user);
                } else {
                    $line->id = $category['id'];
                    $result = $line->insert($fields, $user);
                }

                if ($result < 0) {
                    $this->error = $line->error;
                    $this->errors = $line->errors;
                    dol_syslog(__METHOD__ . " - Create/Update line. Data: " . json_encode($fields) . " Error: " . $this->errorsToString(), LOG_ERR);
                    return -1;
                }

                if (is_array($category['children'])) {
                    foreach ($category['children'] as $child_category) {
                        $child_line = isset(BudgetInsightBankRecord::$bank_record_categories_cached[$child_category['id']]) ? BudgetInsightBankRecord::$bank_record_categories_cached[$child_category['id']] : $bank_record_categories_dictionary->getNewDictionaryLine();

                        $child_fields = [
                            'label' => (!empty($child_category['name_displayed']) ? $child_category['name_displayed'] : $child_category['name']),
                            'id_parent_category' => $line->id,
                            'color' => $child_category['color'],
                            'datas' => json_encode($child_category),
                        ];

                        if ($child_line->id > 0) {
                            $result = $child_line->update($child_fields, $user);
                        } else {
                            $child_line->id = $child_category['id'];
                            $result = $child_line->insert($child_fields, $user);
                        }

                        if ($result < 0) {
                            $this->error = $child_line->error;
                            $this->errors = $child_line->errors;
                            dol_syslog(__METHOD__ . " - Create/Update line. Data: " . json_encode($child_fields) . " Error: " . $this->errorsToString(), LOG_ERR);
                            return -1;
                        }
                    }
                }
            }
        }

        $result = $bank_record->loadBankRecordCategories(true);
        if ($result < 0) return -1;

        return 1;
    }

    /**
     *  If has un-reconciled records for a bank
     *
     * @param   int     $bank_account_id    Id of the bank account
     * @return	int	                        <0 if KO, 0 if NO, 1 if Yes
     */
    public function hasUnReconliledRecords($bank_account_id)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        $sql = 'SELECT COUNT(*) AS nb FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record AS br' .
            ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_account as cb4dba ON cb4dba.rowid = br.id_account' .
            ' WHERE cb4dba.fk_bank_account = ' . $bank_account_id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $has_lines = 0;
        if ($obj = $this->db->fetch_object($resql)) {
            $has_lines = $obj->nb > 0 ? 1 : 0;
        }

        return $has_lines;
    }

    /**
     *  Auto link all bank records from Budget Insight to the bank records from dolibarr for a bank account
     *
     * @param   User            $user               User who make the action
     * @param   int             $bank_account_id    Id of the bank account
     * @param   string          $statement_number   Statement number of the bank reconciliation (date: YYYYMM ou YYYYMMDD)
     * @param   string          $state              UUID for test of unique process
     * @return	int|array	                        <0 if KO, else infos the status of the refresh of the records : array('offset', 'total')
     */
    public function autoLinkBankRecords($user, $bank_account_id, $statement_number, $state='')
    {
        global $conf, $langs, $hookmanager;
        dol_syslog(__METHOD__ . " user_id={$user->id}, bank_account_id=$bank_account_id, statement_number=$statement_number, state=$state", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");
        $this->error = '';
        $this->errors = array();

        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        $statement_number = trim($statement_number);
        if (empty($state)) $state = str_replace('.', '', uniqid('', true));

        // Check parameters
        if (empty($bank_account_id)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }
        // Check statement field
        if (!empty($conf->global->BANK_STATEMENT_REGEX_RULE) && !empty($statement_number) &&
            !preg_match('/' . preg_quote($conf->global->BANK_STATEMENT_REGEX_RULE, '/') . '/', $statement_number)
        ) {
            $this->errors[] = $langs->trans("ErrorBankStatementNameMustFollowRegex", $conf->global->BANK_STATEMENT_REGEX_RULE);
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $process_key = self::PROCESS_KEY_LINK_BANK_RECORDS . '_' . $bank_account_id;
        $session_process_key = 'banking4dolibarr_' . $process_key;

        if ($this->isProcessFlagSet($process_key) == 0) {
            $result = $this->purgePreLink($bank_account_id);
            if ($result < 0) {
                return -1;
            }
        }

        $result = $this->setProcessFlag($process_key, $state, true);
        if ($result < 0) {
            return -1;
        }

        $status = 0;
        $error = 0;
        $nb_processed = 0;
        $text = '';
        $require_confirmation = 0;

        // Auto link by hooks
        $hookmanager->initHooks(array('banking4dolibarrdao'));
        $parameters = array('user' => $user, 'session_process_key' => $session_process_key,
            'bank_account_id' => $bank_account_id, 'statement_number' => $statement_number, 'status' => &$status);
        $reshook = $hookmanager->executeHooks('autoLinkBankRecords', $parameters); // Note that $action and $object may have been
        if ($reshook < 0) {
            $this->error = $hookmanager->error;
            $this->errors = array_merge($this->errors, $hookmanager->errors);
            $error++;
        } elseif ($reshook == 0) {
            // Auto link all unique line who as same amount, payment type and near date operation/date value
            $status = $this->autoLinkBankRecordsForUniqueSameAmount($user, $session_process_key, $bank_account_id, $statement_number, 1, 1);
            if (is_numeric($status) && $status < 0) {
                $error++;
            }

            // Auto link all unique line who as same amount and near date operation/date value
            if (!$error && empty($status)) {
                $status = $this->autoLinkBankRecordsForUniqueSameAmount($user, $session_process_key, $bank_account_id, $statement_number, 1);
                if (is_numeric($status) && $status < 0) {
                    $error++;
                }
            }

            $has_unpaid_element = $this->hasUnpaidElement();
            if ($has_unpaid_element < 0) {
				$error++;
			} elseif ($has_unpaid_element > 0) {
				// Auto link and create payment for all unpaid element in credit near date operation/date value who the sum of downloaded bank record as same or inferior amount and ref found in label or comment
				if (!$error && empty($status)) {
					$status = $this->autoLinkAndCreatePaymentThroughUnpaidElement($user, $session_process_key, $bank_account_id, $statement_number, 0);
					if (is_numeric($status) && $status < 0) {
						$error++;
					}
				}

				// Auto link and create payment for all unique unpaid element in debit near date operation/date value who the sum of downloaded bank record as same or superior amount and ref found in label or comment
				if (!$error && empty($status)) {
					$status = $this->autoLinkAndCreatePaymentThroughUnpaidElement($user, $session_process_key, $bank_account_id, $statement_number, 1);
					if (is_numeric($status) && $status < 0) {
						$error++;
					}
				}

				// Auto link and create payment for all unique unpaid element near date operation/date value who as same amount and the company name found in label or comment
				if (!$error && empty($status)) {
					$status = $this->autoLinkAndCreatePaymentThroughUnpaidElement($user, $session_process_key, $bank_account_id, $statement_number, 2);
					if (is_numeric($status) && $status < 0) {
						$error++;
					}
				}

				// Auto link and create payment for all unique unpaid element near date operation/date value who as same amount
				if (!$error && empty($status)) {
					$status = $this->autoLinkAndCreatePaymentThroughUnpaidElement($user, $session_process_key, $bank_account_id, $statement_number, 3);
					if (is_numeric($status) && $status < 0) {
						$error++;
					}
				}
			}
        }

        if (!$error) {
            $nb_processed = $this->getAutoLinkProcessNbProcessed($session_process_key);
            $text = $this->getAutoLinkProcessNewStatusText($session_process_key, empty($status));
            $require_confirmation = $this->isAutoLinkProcessRequireConfirmation($session_process_key);
        }

        if ($error || (empty($status) && !$require_confirmation)) {
            $this->closeAutoLinkBankRecords($bank_account_id, $state);
        }

        return $error ? -1 : array('text' => $text, 'status' => $status, 'state' => $state, 'nb_processed' => $nb_processed, 'require_confirmation' => $require_confirmation);
    }

    /**
     *  Is auto link process started
     *
     * @param   int         $bank_account_id    Id of the bank account
     * @param   string      $state              UUID for test of unique process
     * @return	int	                            <0 if KO, =0 if No, > 0 if Yes
     */
    public function isAutoLinkBankRecordsStarted($bank_account_id, $state=null)
    {
        global $langs;
        dol_syslog(__METHOD__ . " bank_account_id=$bank_account_id, state=$state", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        if (isset($state)) $state = trim($state);

        // Check parameters
        if (empty($bank_account_id)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }
        $process_key = self::PROCESS_KEY_LINK_BANK_RECORDS . '_' . $bank_account_id;

        $result = $this->isProcessFlagSet($process_key, $state);

        return $result > 0 ? 1 : ($result < 0 ? -1 : 0);
    }

    /**
     *  Close auto link process
     *
     * @param   int         $bank_account_id    Id of the bank account
     * @param   string      $state              UUID for test of unique process
     * @param   bool        $forced             Force the update of the flag
     * @return	int	                            <0 if KO, > 0 if OK
     */
    public function closeAutoLinkBankRecords($bank_account_id, $state=null, $forced=false)
    {
        global $langs;
        dol_syslog(__METHOD__ . " bank_account_id=$bank_account_id, state=$state, forced=$forced", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        if (isset($state)) $state = trim($state);

        // Check parameters
        if (empty($bank_account_id) || (isset($state) && empty($state))) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }
        $process_key = self::PROCESS_KEY_LINK_BANK_RECORDS . '_' . $bank_account_id;
        $session_process_key = 'banking4dolibarr_' . $process_key;

        $result = $this->isAutoLinkBankRecordsStarted($bank_account_id, $state);
        if ($result < 0) {
            return -1;
        } elseif ($result == 0 && !$forced) {
            $this->errors[] = $langs->trans("Banking4DolibarrErrorProcessNotStarted");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        if (isset($_SESSION[$session_process_key])) unset($_SESSION[$session_process_key]);
        $result = $this->setProcessFlag($process_key, $state, false, $forced);
        if ($result < 0) return -1;

        return 1;
    }

    /**
     *  Auto link all bank records from Budget Insight to the bank records from dolibarr for a bank account
     *  and the unique line who as same amount
     *
     * @param   User        $user                   User who make the action
     * @param   string      $session_process_key    Session process key for saving infos of the process
     * @param   int         $bank_account_id        Id of the bank account
     * @param   string      $statement_number       Statement number of the bank reconciliation (date: YYYYMM ou YYYYMMDD)
     * @param   int         $same_dates             Check the same date operation and date value
     * @param   int         $same_payment_type      Check the same payment type
     * @return	int|array	                        <0 if KO, else count of bank records linked
     */
    public function autoLinkBankRecordsForUniqueSameAmount($user, $session_process_key, $bank_account_id, $statement_number, $same_dates=0, $same_payment_type=0)
    {
        global $conf, $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, session_process_key=$session_process_key, bank_account_id=$bank_account_id, statement_number=$statement_number", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        if ($same_dates && $same_payment_type) {
            // Same amount, date operation, date value and payment type
            $auto_link_process_key = 'b4d_p1';
            $auto_link_process_text = 'Banking4DolibarrTextStatusAutoLinkProcess1';
            $table_name = 'banking4dolibarr_reconcile_same_adp';
        } elseif ($same_dates) {
            // Same amount, date operation and date value
            $auto_link_process_key = 'b4d_p2';
            $auto_link_process_text = 'Banking4DolibarrTextStatusAutoLinkProcess2';
            $table_name = 'banking4dolibarr_reconcile_same_ad';
        }

        if ($this->isAutoLinkProcessFinished($session_process_key, $auto_link_process_key)) return 0;

        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        $statement_number = trim($statement_number);
        $limit = min(self::DEFAULT_AUTO_LINK_LIMIT, max(0, !empty($conf->global->BANKING4DOLIBARR_AUTO_LINK_LIMIT) ? $conf->global->BANKING4DOLIBARR_AUTO_LINK_LIMIT : self::DEFAULT_AUTO_LINK_LIMIT));

        // Get all unique line who as same date operation, date value and amount
        $sql = "SELECT t.fk_bank, t.fk_bank_record, t.id_category, t.record_date" .
            " FROM " . MAIN_DB_PREFIX . $table_name . ' AS t'.
            " LEFT JOIN (" .
            "   SELECT fk_bank, COUNT(*) AS nb" .
            "   FROM " . MAIN_DB_PREFIX . $table_name .
            "   WHERE fk_account = " . $bank_account_id .
            "   GROUP BY fk_bank" .
            " ) AS nbb ON nbb.fk_bank = t.fk_bank" .
            " LEFT JOIN (" .
            "   SELECT fk_bank_record, COUNT(*) AS nb" .
            "   FROM " . MAIN_DB_PREFIX . $table_name .
            "   WHERE fk_account = " . $bank_account_id .
            "   GROUP BY fk_bank_record" .
            " ) AS nbbr ON nbbr.fk_bank_record = t.fk_bank_record" .
            " WHERE t.fk_account = " . $bank_account_id .
            " AND nbb.nb = 1 AND nbbr.nb = 1" .
            " LIMIT " . $limit;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " Process: $auto_link_process_key; SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $error = 0;
        $num = $this->db->num_rows($resql);
        if ($num > 0) {
            $bank_record_update = new BudgetInsightBankRecord($this->db);
            while ($obj = $this->db->fetch_object($resql)) {
                $bank_record_update->id = $obj->fk_bank_record;
                $bank_record_update->id_category = $obj->id_category;
                $sn = $statement_number;
                if (empty($sn)) $sn = $this->getStatementNumberFromDate($this->db->jdate($obj->record_date));
                $result = $bank_record_update->reconcile($user, $sn, $obj->fk_bank, 0);
                if ($result < 0) {
                    $this->error = $bank_record_update->error;
                    $this->errors = array_merge($this->errors, $bank_record_update->errors);
                    $error++;
                    break;
                }
            }
        }

        $this->db->free($resql);

        if ($error) {
            return -1;
        } else {
            return $this->setAutoLinkProcessStatus($session_process_key, $auto_link_process_key, 'Banking4DolibarrTextStatusAutoLinkProcess', $auto_link_process_text, $num);
        }
    }

	/**
	 *  If has unpaid element to check
	 *
	 * @return int			<0 if KO, =0 if No, >0 if Yes
	 */
    public function hasUnpaidElement()
	{
		global $conf;

		$sql = "SELECT table_name FROM information_schema.tables" .
			" WHERE " . ($this->db->type == 'pgsql' ? "table_catalog" : "table_schema") . " = '" . $this->db->escape($conf->db->name) . "'" .
			" AND table_name = '" . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list'" .
			" LIMIT 1;";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		$has_unpaid_element = $this->db->num_rows($resql) > 0;
		$this->db->free($resql);

		return $has_unpaid_element ? 1 : 0;
	}

    /**
     * Auto link and create payment for all unpaid element
     *
     * @param   User        $user                       User who make the action
     * @param   string      $session_process_key        Session process key for saving infos of the process
     * @param   int         $bank_account_id            Id of the bank account
     * @param   string      $statement_number           Statement number of the bank reconciliation (date: YYYYMM ou YYYYMMDD)
     * @param   int         $mode                       0: Credit (invoice and invoice supplier) who the sum of downloaded bank record as same or inferior amount and ref found in label or comment
     *                                                  1: Debit (invoice and invoice supplier) who the sum of downloaded bank record as same or inferior amount and ref found in label or comment
     *                                                  2: Credit (invoice and invoice supplier) who as same amount
     *                                                  3: Debit (invoice and invoice supplier) who as same amount
     * @return	int|array	                            <0 if KO, else count of bank records linked
     */
    public function autoLinkAndCreatePaymentThroughUnpaidElement($user, $session_process_key, $bank_account_id, $statement_number, $mode=0)
    {
        global $conf, $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, session_process_key=$session_process_key, bank_account_id=$bank_account_id, statement_number=$statement_number", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        switch ($mode) {
            case 0: // Amount equal or inferior and ref found for credit
                $auto_link_process_key = 'b4d_p5';
                $auto_link_process_text = 'Banking4DolibarrTextStatusAutoLinkProcess5';
                break;
            case 1: // Amount equal or seperior and ref found for debit
                $auto_link_process_key = 'b4d_p6';
                $auto_link_process_text = 'Banking4DolibarrTextStatusAutoLinkProcess6';
                break;
            case 2: // Same amount and company name found
                $auto_link_process_key = 'b4d_p7';
                $auto_link_process_text = 'Banking4DolibarrTextStatusAutoLinkProcess7';
                break;
			case 3: // Same amount
				$auto_link_process_key = 'b4d_p8';
				$auto_link_process_text = 'Banking4DolibarrTextStatusAutoLinkProcess8';
				break;
            default:
                $langs->load("errors");
                $this->errors[] = $langs->trans("ErrorBadParameters");
                dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
        }

        if ($this->isAutoLinkProcessFinished($session_process_key, $auto_link_process_key)) return 0;

        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        $limit = min(self::DEFAULT_AUTO_LINK_LIMIT, max(0, !empty($conf->global->BANKING4DOLIBARR_AUTO_LINK_LIMIT) ? $conf->global->BANKING4DOLIBARR_AUTO_LINK_LIMIT : self::DEFAULT_AUTO_LINK_LIMIT));
		$debit_min_offset_date = $conf->global->BANKING4DOLIBARR_UNPAID_DEBIT_MIN_OFFSET_DATES > 0 ? $conf->global->BANKING4DOLIBARR_UNPAID_DEBIT_MIN_OFFSET_DATES : 60;
		$debit_max_offset_date = $conf->global->BANKING4DOLIBARR_UNPAID_DEBIT_MAX_OFFSET_DATES > 0 ? $conf->global->BANKING4DOLIBARR_UNPAID_DEBIT_MAX_OFFSET_DATES : 60;
		$credit_min_offset_date = $conf->global->BANKING4DOLIBARR_UNPAID_CREDIT_MIN_OFFSET_DATES > 0 ? $conf->global->BANKING4DOLIBARR_UNPAID_CREDIT_MIN_OFFSET_DATES : 60;
		$credit_max_offset_date = $conf->global->BANKING4DOLIBARR_UNPAID_CREDIT_MAX_OFFSET_DATES > 0 ? $conf->global->BANKING4DOLIBARR_UNPAID_CREDIT_MAX_OFFSET_DATES : 60;

        // Get all unpaid customer invoices
        switch ($mode) {
			case 0: // Amount equal or inferior and ref found for credit
				if ($this->db->type == 'pgsql') {
					$sql = "SELECT STRING_AGG(br.rowid::TEXT, '|') AS bank_records";
				} else {
					$sql = "SELECT GROUP_CONCAT(br.rowid SEPARATOR '|') AS bank_records";
				}
				$sql .= ", t.element_type, t.element_id" .
					" FROM " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list AS t" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS br ON (" .
					"   (t.ref IS NOT NULL AND t.ref != '' AND (REPLACE(br.label, ' ', '') LIKE CONCAT('%', REPLACE(t.ref, ' ', ''), '%') OR REPLACE(br.comment, ' ', '') LIKE CONCAT('%', REPLACE(t.ref, ' ', ''), '%')))" .
					"   OR (t.ref_ext IS NOT NULL AND t.ref_ext != '' AND (REPLACE(br.label, ' ', '') LIKE CONCAT('%', REPLACE(t.ref_ext, ' ', ''), '%') OR REPLACE(br.comment, ' ', '') LIKE CONCAT('%', REPLACE(t.ref_ext, ' ', ''), '%')))" .
					" ) AND br.amount > 0 AND br.amount <= t.amount" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account AS cb4dba ON cb4dba.rowid = br.id_account" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_pre_link AS pl ON pl.element_type = t.element_type AND pl.element_id = t.element_id" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON ba.rowid = cb4dba.fk_bank_account" .
					" WHERE br.rowid IS NOT NULL" .
					" AND br.status = " . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED .
					" AND br.deleted_date IS NULL" .
					" AND t.ref IS NOT NULL";
				if (!empty($conf->global->BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES)) {
					$sql .= " AND " . $this->db->ifsql("t.datee IS NULL",
							"t.dateb BETWEEN DATE_SUB(br.record_date, INTERVAL " . $credit_min_offset_date . " DAY) AND DATE_ADD(br.record_date, INTERVAL " . $credit_max_offset_date . " DAY)",
							"br.record_date BETWEEN DATE_SUB(t.dateb, INTERVAL " . $credit_min_offset_date . " DAY) AND DATE_ADD(t.datee, INTERVAL " . $credit_max_offset_date . " DAY)");
				}
				$sql .= " AND t.amount > 0" .
					" AND t.entity = ba.entity" .
					" AND ba.entity IN (" . getEntity('bank_account') . ")" .
					" AND pl.rowid IS NULL" .
					" AND cb4dba.fk_bank_account = '" . $bank_account_id . "'" .
					" GROUP BY t.element_type, t.element_id" .
					" HAVING SUM(br.amount) <= MAX(t.amount)" .
					" LIMIT " . $limit;
				break;
			case 1: // Amount equal or superior and ref found for debit
				if ($this->db->type == 'pgsql') {
					$sql = "SELECT STRING_AGG(br.rowid::TEXT, '|') AS bank_records";
				} else {
					$sql = "SELECT GROUP_CONCAT(br.rowid SEPARATOR '|') AS bank_records";
				}
				$sql .= ", t.element_type, t.element_id" .
					" FROM " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list AS t" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS br ON (" .
					"   (t.ref IS NOT NULL AND t.ref != '' AND (REPLACE(br.label, ' ', '') LIKE CONCAT('%', REPLACE(t.ref, ' ', ''), '%') OR REPLACE(br.comment, ' ', '') LIKE CONCAT('%', REPLACE(t.ref, ' ', ''), '%')))" .
					"   OR (t.ref_ext IS NOT NULL AND t.ref_ext != '' AND (REPLACE(br.label, ' ', '') LIKE CONCAT('%', REPLACE(t.ref_ext, ' ', ''), '%') OR REPLACE(br.comment, ' ', '') LIKE CONCAT('%', REPLACE(t.ref_ext, ' ', ''), '%')))" .
					" ) AND br.amount < 0 AND br.amount >= t.amount" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account AS cb4dba ON cb4dba.rowid = br.id_account" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_pre_link AS pl ON pl.element_type = t.element_type AND pl.element_id = t.element_id" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON ba.rowid = cb4dba.fk_bank_account" .
					" WHERE br.rowid IS NOT NULL" .
					" AND br.status = " . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED .
					" AND br.deleted_date IS NULL" .
					" AND t.ref IS NOT NULL" .
					" AND t.amount < 0";
				if (!empty($conf->global->BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES)) {
					$sql .= " AND " . $this->db->ifsql("t.datee IS NULL",
							"t.dateb BETWEEN DATE_SUB(br.record_date, INTERVAL " . $credit_min_offset_date . " DAY) AND DATE_ADD(br.record_date, INTERVAL " . $credit_max_offset_date . " DAY)",
							"br.record_date BETWEEN DATE_SUB(t.dateb, INTERVAL " . $credit_min_offset_date . " DAY) AND DATE_ADD(t.datee, INTERVAL " . $credit_max_offset_date . " DAY)");
				}
				$sql .= " AND t.entity = ba.entity" .
					" AND ba.entity IN (" . getEntity('bank_account') . ")" .
					" AND pl.rowid IS NULL" .
					" AND cb4dba.fk_bank_account = '" . $bank_account_id . "'" .
					" GROUP BY t.element_type, t.element_id" .
					" HAVING SUM(br.amount) >= MAX(t.amount)" .
					" LIMIT " . $limit;
				break;
			case 2: // Same amount and company name found
				$sql = "SELECT t.bank_records, t.element_type, t.element_id" .
					" FROM " . MAIN_DB_PREFIX . 'banking4dolibarr_unpaid_list_same_a AS t' .
					" LEFT JOIN (" .
					"   SELECT element_type, element_id, COUNT(*) AS nb" .
					"   FROM " . MAIN_DB_PREFIX . 'banking4dolibarr_unpaid_list_same_a' .
					"   WHERE fk_bank_account = '" . $bank_account_id . "'" .
					"   AND (" .
					"     (company_name IS NOT NULL AND company_name != '' AND (label LIKE CONCAT('%', company_name, '%') OR comment LIKE CONCAT('%', company_name, '%')))" .
					"     OR (company_alt_name IS NOT NULL AND company_alt_name != '' AND (label LIKE CONCAT('%', company_alt_name, '%') OR comment LIKE CONCAT('%', company_alt_name, '%')))" .
					"     OR (company_spe_name IS NOT NULL AND company_spe_name != '' AND (label LIKE CONCAT('%', company_spe_name, '%') OR comment LIKE CONCAT('%', company_spe_name, '%')))" .
					"   )" .
					"   AND entity IN (" . getEntity('bank_account') . ")";
				if (!empty($conf->global->BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES)) {
					$sql .= "   AND " . $this->db->ifsql("datee IS NULL",
							"dateb BETWEEN DATE_SUB(record_date, INTERVAL " . $this->db->ifsql("amount < 0", $debit_min_offset_date, $credit_min_offset_date) . " DAY) AND DATE_ADD(record_date, INTERVAL " . $this->db->ifsql("amount < 0", $debit_max_offset_date, $credit_max_offset_date) . " DAY)",
							"record_date BETWEEN DATE_SUB(dateb, INTERVAL " . $this->db->ifsql("amount < 0", $debit_min_offset_date, $credit_min_offset_date) . " DAY) AND DATE_ADD(datee, INTERVAL " . $this->db->ifsql("amount < 0", $debit_max_offset_date, $credit_max_offset_date) . " DAY)");
				}
				$sql .= "   GROUP BY element_type, element_id" .
					" ) AS nbb ON nbb.element_type = t.element_type AND nbb.element_id = t.element_id" .
					" LEFT JOIN (" .
					"   SELECT bank_records, COUNT(*) AS nb" .
					"   FROM " . MAIN_DB_PREFIX . 'banking4dolibarr_unpaid_list_same_a' .
					"   WHERE fk_bank_account = '" . $bank_account_id . "'" .
					"   AND (" .
					"     (company_name IS NOT NULL AND company_name != '' AND (label LIKE CONCAT('%', company_name, '%') OR comment LIKE CONCAT('%', company_name, '%')))" .
					"     OR (company_alt_name IS NOT NULL AND company_alt_name != '' AND (label LIKE CONCAT('%', company_alt_name, '%') OR comment LIKE CONCAT('%', company_alt_name, '%')))" .
					"     OR (company_spe_name IS NOT NULL AND company_spe_name != '' AND (label LIKE CONCAT('%', company_spe_name, '%') OR comment LIKE CONCAT('%', company_spe_name, '%')))" .
					"   )" .
					"   AND entity IN (" . getEntity('bank_account') . ")";
				if (!empty($conf->global->BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES)) {
					$sql .= "   AND " . $this->db->ifsql("datee IS NULL",
							"dateb BETWEEN DATE_SUB(record_date, INTERVAL " . $this->db->ifsql("amount < 0", $debit_min_offset_date, $credit_min_offset_date) . " DAY) AND DATE_ADD(record_date, INTERVAL " . $this->db->ifsql("amount < 0", $debit_max_offset_date, $credit_max_offset_date) . " DAY)",
							"record_date BETWEEN DATE_SUB(dateb, INTERVAL " . $this->db->ifsql("amount < 0", $debit_min_offset_date, $credit_min_offset_date) . " DAY) AND DATE_ADD(datee, INTERVAL " . $this->db->ifsql("amount < 0", $debit_max_offset_date, $credit_max_offset_date) . " DAY)");
				}
				$sql .= "   GROUP BY bank_records" .
					" ) AS nbbr ON nbbr.bank_records = t.bank_records" .
					" WHERE t.fk_bank_account = '" . $bank_account_id . "'" .
					" AND t.entity IN (" . getEntity('bank_account') . ")" .
					" AND nbb.nb = 1 AND nbbr.nb = 1" .
					" LIMIT " . $limit;
				break;
			case 3: // Same amount
				$sql = "SELECT t.bank_records, t.element_type, t.element_id" .
					" FROM " . MAIN_DB_PREFIX . 'banking4dolibarr_unpaid_list_same_a AS t' .
					" LEFT JOIN (" .
					"   SELECT element_type, element_id, COUNT(*) AS nb" .
					"   FROM " . MAIN_DB_PREFIX . 'banking4dolibarr_unpaid_list_same_a' .
					"   WHERE fk_bank_account = '" . $bank_account_id . "'" .
					"   AND entity IN (" . getEntity('bank_account') . ")";
				if (!empty($conf->global->BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES)) {
					$sql .= "   AND " . $this->db->ifsql("datee IS NULL",
							"dateb BETWEEN DATE_SUB(record_date, INTERVAL " . $this->db->ifsql("amount < 0", $debit_min_offset_date, $credit_min_offset_date) . " DAY) AND DATE_ADD(record_date, INTERVAL " . $this->db->ifsql("amount < 0", $debit_max_offset_date, $credit_max_offset_date) . " DAY)",
							"record_date BETWEEN DATE_SUB(dateb, INTERVAL " . $this->db->ifsql("amount < 0", $debit_min_offset_date, $credit_min_offset_date) . " DAY) AND DATE_ADD(datee, INTERVAL " . $this->db->ifsql("amount < 0", $debit_max_offset_date, $credit_max_offset_date) . " DAY)");
				}
				$sql .= "   GROUP BY element_type, element_id" .
					" ) AS nbb ON nbb.element_type = t.element_type AND nbb.element_id = t.element_id" .
					" LEFT JOIN (" .
					"   SELECT bank_records, COUNT(*) AS nb" .
					"   FROM " . MAIN_DB_PREFIX . 'banking4dolibarr_unpaid_list_same_a' .
					"   WHERE fk_bank_account = '" . $bank_account_id . "'" .
					"   AND entity IN (" . getEntity('bank_account') . ")";
				if (!empty($conf->global->BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES)) {
					$sql .= "   AND " . $this->db->ifsql("datee IS NULL",
							"dateb BETWEEN DATE_SUB(record_date, INTERVAL " . $this->db->ifsql("amount < 0", $debit_min_offset_date, $credit_min_offset_date) . " DAY) AND DATE_ADD(record_date, INTERVAL " . $this->db->ifsql("amount < 0", $debit_max_offset_date, $credit_max_offset_date) . " DAY)",
							"record_date BETWEEN DATE_SUB(dateb, INTERVAL " . $this->db->ifsql("amount < 0", $debit_min_offset_date, $credit_min_offset_date) . " DAY) AND DATE_ADD(datee, INTERVAL " . $this->db->ifsql("amount < 0", $debit_max_offset_date, $credit_max_offset_date) . " DAY)");
				}
				$sql .= "   GROUP BY bank_records" .
					" ) AS nbbr ON nbbr.bank_records = t.bank_records" .
					" WHERE t.fk_bank_account = '" . $bank_account_id . "'" .
					" AND t.entity IN (" . getEntity('bank_account') . ")" .
					" AND nbb.nb = 1 AND nbbr.nb = 1" .
					" LIMIT " . $limit;
				break;
		}

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $error = 0;
        $num = $this->db->num_rows($resql);
        while ($obj = $this->db->fetch_object($resql)) {
            $bank_records = explode('|', $obj->bank_records);
            foreach ($bank_records as $bank_record_id) {
                $result = $this->setPreLink($bank_account_id, $bank_record_id, $obj->element_type, $obj->element_id);
                if ($result < 0) {
                    $error++;
                    break;
                }
            }
        }

        $this->db->free($resql);

        if ($error) {
            return -1;
        } else {
            return $this->setAutoLinkProcessStatus($session_process_key, $auto_link_process_key, 'Banking4DolibarrTextStatusAutoLinkProcess', $auto_link_process_text, $num, 0, $num > 0 ? 1 : 0);
        }
    }

    /**
     *  Set pre-linking of the result in the auto link process who require confirm by user
     *
     * @param   int     $fk_bank_account        ID of the bank accound
     * @param   int     $fk_bank_record         ID of bank record downloaded
     * @param   int     $element_type           Type of the element unpaid
     * @param 	int		$element_id	            ID of the element unpaid
     * @param 	int		$fk_bank		        ID of bank line (Dolibarr)
     * @return	int	                            <0 if KO, >0 if OK
     */
    public function setPreLink($fk_bank_account, $fk_bank_record, $element_type='', $element_id=0, $fk_bank=0)
    {
        dol_syslog(__METHOD__ . " fk_bank_account=$fk_bank_account, fk_bank_record=$fk_bank_record, element_type=$element_type, element_id=$element_id, fk_bank=$fk_bank", LOG_DEBUG);

        // Check parameters
        $fk_bank_account = $fk_bank_account > 0 ? $fk_bank_account : 0;
        $fk_bank_record = $fk_bank_record > 0 ? $fk_bank_record : 0;
        $element_type = trim($element_type);
        $element_id = $element_id > 0 ? $element_id : 0;
        $fk_bank = $fk_bank > 0 ? $fk_bank : 0;

        $error = 0;
        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_pre_link(" .
            "fk_bank_account, fk_bank_record, element_type, element_id, fk_bank" .
            ") VALUES (" .
            $fk_bank_account .
            ", " . $fk_bank_record .
            ", " . (!empty($element_type) ? "'" . $this->db->escape($element_type) . "'" : "NULL") .
            ", " . ($element_id > 0 ? $element_id : "NULL") .
            ", " . ($fk_bank > 0 ? $fk_bank : "NULL") .
            ")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $error++;
        }

        $id = 0;
        if (!$error) {
            $id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_pre_link');
        }

        if (!$error) {
            $this->db->commit();
            return $id;
        } else {
            $this->db->rollback();
            return -1 * $error;
        }
    }

    /**
     *  Valid pre-linking of the result in the auto link process who require confirm by user
     *
     * @param   User        $user               User who make the action
     * @param   int         $bank_account_id    Id of the bank account
     * @param   string      $statement_number   Statement number of the bank reconciliation (date: YYYYMM ou YYYYMMDD)
     * @param   array       $ids	            List of ID of the pre-linking to valid
     * @param   array       $payment_modes	    List of payment mode specified when is not defined or unknown for each pre-link line
     * @return	int	                            <0 if KO, >0 if OK
     */
    public function validPreLinks($user, $bank_account_id, $statement_number, $ids, $payment_modes=array())
    {
        global $conf, $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, bank_account_id=$bank_account_id, statement_number=$statement_number, ids=".json_encode($ids) . ", payment_modes=".json_encode($payment_modes), LOG_DEBUG);
        $langs->load('banking4dolibarr@banking4dolibarr');
        $error = 0;

        // Clean parameters
        $ids = is_array($ids) ? $ids : (is_string($ids) ? implode(',', $ids) : array());
        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        $statement_number = trim($statement_number);

        // Check parameters
        if (empty($ids)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrPreLinkIdsToValid'));
            $error++;
        }
        if (empty($bank_account_id)) {
            $this->errors[] = $langs->trans("ErrorBadParameters");
            $error++;
        }
        if (!empty($conf->global->BANK_STATEMENT_REGEX_RULE) && !empty($statement_number) &&
            !preg_match('/' . preg_quote($conf->global->BANK_STATEMENT_REGEX_RULE, '/') . '/', $statement_number)
        ) {
            $this->errors[] = $langs->trans("ErrorBankStatementNameMustFollowRegex", $conf->global->BANK_STATEMENT_REGEX_RULE);
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "SELECT pl.rowid, pl.fk_bank_record, br.record_date, br.record_type, br.id_category, pl.fk_bank, pl.element_type, pl.element_id," .
			" " . $this->db->ifsql("ul.amount < 0", $this->db->ifsql("ul.amount > br.amount", "ul.amount", "br.amount"), $this->db->ifsql("ul.amount < br.amount", "ul.amount", "br.amount")) . " AS element_amount" .
            " FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_pre_link AS pl" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS br ON br.rowid = pl.fk_bank_record" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_record_category as cbrc ON cbrc.rowid = br.id_category" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON ba.rowid = pl.fk_bank_account" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list AS ul ON ul.element_type = pl.element_type AND ul.element_id = pl.element_id AND ul.entity = ba.entity" .
            " WHERE pl.rowid IN (" . implode(',', $ids) . ")" .
            " AND pl.fk_bank_account = " . $bank_account_id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $error++;
        } else {
            $bank_record_update = new BudgetInsightBankRecord($this->db);
            while ($obj = $this->db->fetch_object($resql)) {
                $record_date = $this->db->jdate($obj->record_date);
				$amount = 0;
                if (!empty($obj->element_type)) {
                    // Get payment mode ID in Dolibarr for the payment mode ID of the downloaded bank record
                    if (isset($payment_modes[$obj->rowid])) {
                        $payment_mode_id = $payment_modes[$obj->rowid];
                    } else {
                        $payment_mode_id = $bank_record_update->getDolibarrPaymentModeId($obj->record_type);
                        if ($payment_mode_id < 0) {
                            $this->error = $bank_record_update->error;
                            $this->errors = array_merge($this->errors, $bank_record_update->errors);
                            $error++;
                            break;
                        }
                    }

                    if ($obj->element_type == 'chequereceipt' && empty($conf->global->MAIN_DISABLEDRAFTSTATUS)) {
						require_once DOL_DOCUMENT_ROOT . '/compta/paiement/cheque/class/remisecheque.class.php';
						$checkdeposit = new RemiseCheque($this->db);
						$result = $checkdeposit->fetch($obj->element_id);
						if ($result > 0) $result = $checkdeposit->validate($user);
						if ($result < 0) {
							$this->error = $checkdeposit->error;
							$this->errors = array_merge($this->errors, $checkdeposit->errors);
							$error++;
							break;
						} elseif ($result > 0) {
							// Get nb bank line linked
							$sql2 = 'SELECT b.rowid' .
								" FROM " . MAIN_DB_PREFIX . "bank AS b" .
								" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS brl ON brl.fk_bank = b.rowid" .
								' WHERE b.fk_bordereau = ' . $obj->element_id .
								' AND brl.rowid IS NULL' .
								' GROUP BY b.rowid';

							$resql2 = $this->db->query($sql2);
							if (!$resql2) {
								$this->errors[] = 'Error ' . $this->db->lasterror();
								dol_syslog(__METHOD__ . " SQL: " . $sql2 . "; Error: " . $this->db->lasterror(), LOG_ERR);
								$error++;
								break;
							}

							$fk_bank_list = array();
							while ($obj2 = $this->db->fetch_object($resql2)) {
								$fk_bank_list[] = $obj2->rowid;
							}
							$this->db->free($resql2);
						} else {
							$langs->load("errors");
							$this->errors[] = $langs->trans("ErrorRecordNotFound");
							dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
							$error++;
							break;
						}
						if ($result > 0) {
							// Define output language
							$outputlangs = $langs;
							$newlang = '';
							if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id'])) $newlang = $_REQUEST['lang_id'];
							//if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
							if (!empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
							}
							$result = $checkdeposit->generatePdf($checkdeposit->modelpdf, $outputlangs);
							if ($result < 0) {
								$this->error = $checkdeposit->error;
								$this->errors = array_merge($this->errors, $checkdeposit->errors);
								$error++;
								break;
							}
						}
					} elseif ($obj->element_type == 'widthdraw') {
						require_once DOL_DOCUMENT_ROOT . '/compta/prelevement/class/bonprelevement.class.php';
						$bonprelevement = new BonPrelevement($this->db, "");
						$result = $bonprelevement->fetch($obj->element_id);
						if ($result == -1) {
							$langs->load("errors");
							$this->errors[] = $langs->trans("ErrorRecordNotFound");
							dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
							$error++;
							break;
						} elseif ($result == -2) {
							$this->errors[] = 'Error ' . $this->db->lasterror();
							dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
							$error++;
							break;
						} elseif ($result > 0) {
							if ($bonprelevement->statut == 1) {
								$old_prelevement_id_bankaccount = $conf->global->PRELEVEMENT_ID_BANKACCOUNT;
								$conf->global->PRELEVEMENT_ID_BANKACCOUNT = $bank_account_id;
								$result = $bonprelevement->set_infocredit($user, $record_date);
								if ($result < 0) {
									if ($result == -1025) $this->errors[] = "Open SQL transaction impossible";
									elseif ($result == -1026) $this->errors[] = "Already fetched";
									elseif ($result == -1027) $this->errors[] = "Date de credit < Date de trans";
									else $this->errors[] = "Error when set info credit: $result";
									$this->error = $bonprelevement->error;
									$this->errors = array_merge($this->errors, $bonprelevement->errors);
									$error++;
									break;
								}
								$conf->global->PRELEVEMENT_ID_BANKACCOUNT = $old_prelevement_id_bankaccount;
							} elseif ($bonprelevement->statut == 2 && $bonprelevement->date_credit != $record_date) {
								$sql3 = " UPDATE " . MAIN_DB_PREFIX . "prelevement_bons " .
									" SET date_credit = '" . $this->db->idate($record_date) . "'" .
									" WHERE rowid = " . $obj->element_id .
									" AND entity = " . $conf->entity;
								$resql3 = $this->db->query($sql3);
								if (!$resql3) {
									$this->errors[] = 'Error ' . $this->db->lasterror();
									dol_syslog(__METHOD__ . " SQL: " . $sql3 . "; Error: " . $this->db->lasterror(), LOG_ERR);
									$error++;
									break;
								}
							}
						}

						// Get nb bank line linked
						$sql2 = 'SELECT bu.fk_bank' .
							" FROM " . MAIN_DB_PREFIX . "bank_url AS bu" .
							" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS brl ON brl.fk_bank = bu.fk_bank" .
							" LEFT JOIN " . MAIN_DB_PREFIX . "bank AS b ON b.rowid = bu.fk_bank" .
							' WHERE bu.url_id = ' . $obj->element_id .
							" AND bu.type = 'withdraw'" .
							' AND brl.rowid IS NULL' .
							' GROUP BY bu.fk_bank';

						$resql2 = $this->db->query($sql2);
						if (!$resql2) {
							$this->errors[] = 'Error ' . $this->db->lasterror();
							dol_syslog(__METHOD__ . " SQL: " . $sql2 . "; Error: " . $this->db->lasterror(), LOG_ERR);
							$error++;
							break;
						}

						$fk_bank_list = array();
						while ($obj2 = $this->db->fetch_object($resql2)) {
							$fk_bank_list[] = $obj2->fk_bank;
							$amount += $obj->amount;
						}
						$this->db->free($resql2);
					} else {
						$fk_bank_list = $this->createPayment($user, $bank_account_id, $obj->element_type, $record_date, $payment_mode_id, array($obj->element_id => array('amount' => $obj->element_amount, 'multicurrency_amount' => 0)));
						if (!is_array($fk_bank_list)) {
							$error++;
							break;
						}
						if (!$error) {
							$payment_mode_code = dol_getIdFromCode($this->db, $payment_mode_id, 'c_paiement', 'id', 'code', 1);
							if ($payment_mode_code == 'CHQ' && $obj->element_amount > 0) {
								$result = $this->createCheckReceiptPayment($user, $bank_account_id, $record_date, $fk_bank_list);
								if ($result < 0) {
									$error++;
									break;
								}
							}
						}
					}
                } else {
					$fk_bank_list = array($obj->fk_bank);
                }

				$idx = 1;
				$nb_lines = count($fk_bank_list);
                $bank_record_update->id = $obj->fk_bank_record;
                $bank_record_update->id_category = $obj->id_category;
                $reconcile_last_line = $obj->element_type != 'widthdraw' || price2num($amount, 'MT') == price2num($obj->element_amount, 'MT');
                $sn = $statement_number;
                if (empty($sn)) $sn = $this->getStatementNumberFromDate($record_date);
                foreach ($fk_bank_list as $fk_bank) {
					if ($idx == $nb_lines && $reconcile_last_line) {
						$result = $bank_record_update->reconcile($user, $sn, $fk_bank, 0);
					} else {
						$result = $bank_record_update->link($user, $fk_bank);
					}
					if ($result < 0) {
						$this->error = $bank_record_update->error;
						$this->errors = array_merge($this->errors, $bank_record_update->errors);
						$error++;
						break;
					}
					$idx++;
				}

                $result = $this->purgePreLink($bank_account_id, $obj->rowid);
                if ($result < 0) {
                    $error++;
                    break;
                }
            }
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1 * $error;
        }
    }

    /**
     *  Purge pre-linking of the result in the auto link process who require confirm by user
     *
     * @param   int     $fk_bank_account        ID of the bank accound
     * @param   int     $line_id                ID of the pre-link line
     * @return	int	                            <0 if KO, >0 if OK
     */
    public function purgePreLink($fk_bank_account, $line_id=0)
    {
        dol_syslog(__METHOD__ . " fk_bank_account=$fk_bank_account, line_id=$line_id", LOG_DEBUG);

        // Check parameters
        $fk_bank_account = $fk_bank_account > 0 ? $fk_bank_account : 0;

        $error = 0;
        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_pre_link WHERE fk_bank_account = " . $fk_bank_account;
        if ($line_id > 0) $sql .= " AND rowid = " . $line_id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $error++;
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1 * $error;
        }
    }

    /**
     *  Get all manual reconciliation type
     *
     * @param   User            $user       User who make the action
     * @return	int|array	                <0 if KO or list of the manual reconciliation types
     */
    public function getManualReconciliationTypes($user)
    {
        global $conf, $langs, $hookmanager;
        $langs->loadLangs(array('banking4dolibarr@banking4dolibarr', 'banks'));

		$unpaid_element_list = $this->getManualReconciliationUnpaidTypes($user);
		if (!is_array($unpaid_element_list)) {
			return -1;
		}

		$objectTypes = [
            'bank_transaction' => array('label' => $langs->trans('BankTransactions'), 'payment_mode_require' => 0),
        ];
		if (!empty($unpaid_element_list)) {
            $objectTypes['unpaid_element'] = array('label' => $langs->trans('Banking4DolibarrUnpaidElements'), 'payment_mode_require' => 1);
        }
		if (empty($conf->global->BANK_DISABLE_CHECK_DEPOSIT) && !empty($conf->banque->enabled) && empty($conf->global->MAIN_DISABLEDRAFTSTATUS) && (!empty($conf->facture->enabled) || !empty($conf->global->MAIN_MENU_CHEQUE_DEPOSIT_ON))) {
			$langs->load('bills');
			$objectTypes['chequereceipt'] = array('label' => $langs->trans('ChequeDeposits'), 'payment_mode_require' => 0);;
		}
		if (!empty($conf->prelevement->enabled)) {
			$objectTypes['widthdraw'] = array('label' => $langs->trans('StandingOrders'), 'payment_mode_require' => 0);;
		}
        if ($user->rights->banque->transfer) {
            $objectTypes['bank_transfer'] = array('label' => $langs->trans('Banking4DolibarrNewBankTransfers'), 'payment_mode_require' => 0);
        }
        if (!empty($conf->fournisseur->enabled)) {
            $objectTypes['create_element_invoice_supplier'] = array('label' => $langs->trans('Banking4DolibarrNewInvoiceSupplier'), 'payment_mode_require' => 0);
        }
		if (!empty($conf->salaries->enabled)) {
			$objectTypes['salaries'] = array('label' => $langs->trans('Banking4DolibarrNewSalaries'), 'payment_mode_require' => 1);
		}
        if (!empty($conf->tax->enabled)) {
            $objectTypes['vat'] = array('label' => $langs->trans('Banking4DolibarrNewVAT'), 'payment_mode_require' => 1);
            $objectTypes['social_contribution'] = array('label' => $langs->trans('Banking4DolibarrNewContributions'), 'payment_mode_require' => 1);
        }
		$objectTypes['various_payment'] = array('label' => $langs->trans('Banking4DolibarrNewVariousPayment'), 'payment_mode_require' => 1);

        // Add manual reconciliation type by hooks
        $hookmanager->initHooks(array('banking4dolibarrdao'));
        $parameters = array('object_types' => &$objectTypes);
        $reshook = $hookmanager->executeHooks('manualReconciliationTypes', $parameters); // Note that $action and $object may have been
        if ($reshook < 0) {
            $this->error = $hookmanager->error;
            $this->errors = array_merge($this->errors, $hookmanager->errors);
            return -1;
        }

        return $objectTypes;
    }

    /**
     *  Get all manual reconciliation create element infos
     *
     * @param   User            $user       User who make the action
     * @return	int|array	                <0 if KO or list of the manual reconciliation types
     */
    public function getManualReconciliationCreateElementInfos($user)
    {
        global $langs, $hookmanager;
        $langs->loadLangs(array('banking4dolibarr@banking4dolibarr', 'banks'));

        $objectTypes = [
            'invoice_supplier' => array('card_path' => '/fourn/facture/card.php', 'id_parameter_names' => array('facid', 'search_fourninvoiceid')),
        ];

        // Add manual reconciliation create element infos by hooks
        $hookmanager->initHooks(array('banking4dolibarrdao'));
        $parameters = array('object_types' => &$objectTypes);
        $reshook = $hookmanager->executeHooks('manualReconciliationCreateElementInfos', $parameters); // Note that $action and $object may have been
        if ($reshook < 0) {
            $this->error = $hookmanager->error;
            $this->errors = array_merge($this->errors, $hookmanager->errors);
            return -1;
        }

        return $objectTypes;
    }

    /**
     *  Get all manual reconciliation unpaid type
     *
     * @param   User            $user       User who make the action
     * @return	int|array	                <0 if KO or list of the manual reconciliation unpaid types
     */
    public function getManualReconciliationUnpaidTypes($user)
    {
        global $conf, $langs, $hookmanager;
        $langs->load('bills');
        if (!empty($conf->don->enabled)) $langs->load('donations');
        if (!empty($conf->tax->enabled)) $langs->load('compta');
        if (!empty($conf->expensereport->enabled)) $langs->load('trips');
        if (!empty($conf->loan->enabled)) $langs->load('loan');

        $elementTypes = array();
        if (!empty($conf->facture->enabled)) $elementTypes['facture'] = $langs->trans('CustomersInvoices');
        if (!empty($conf->fournisseur->enabled)) $elementTypes['invoice_supplier'] = $langs->trans('SuppliersInvoices');
        if (!empty($conf->don->enabled)) $elementTypes['don'] = $langs->trans('Donations');
        if (!empty($conf->tax->enabled)) $elementTypes['chargesociales'] = $langs->trans('SocialContribution');
        if (!empty($conf->expensereport->enabled)) $elementTypes['expensereport'] = $langs->trans('ExpenseReport');
        if (!empty($conf->loan->enabled)) $elementTypes['loan'] = $langs->trans('Loan');

        // Add manual reconciliation type by hooks
        $hookmanager->initHooks(array('banking4dolibarrdao'));
        $parameters = array('user' => $user, 'element_types' => &$elementTypes);
        $reshook = $hookmanager->executeHooks('manualReconciliationUnpaidTypes', $parameters); // Note that $action and $object may have been
        if ($reshook < 0) {
            $this->error = $hookmanager->error;
            $this->errors = array_merge($this->errors, $hookmanager->errors);
            return -1;
        }

        return $elementTypes;
    }

    /**
     *  Create payment of a object
     *
     * @param   User    	$user                       User who make the action
     * @param   int  		$bank_account_id			Bank account ID
	 * @param   string  	$element_type             	Element type to create payment
	 * @param   int     	$payment_date     			Payment date
	 * @param   int     	$payment_mode_id     		Payment mode ID
	 * @param   array   	$amount_infos				Amounts	information => array(element_id => array('amount' => xxx, 'multicurrency_amount' => yyy), ...)
	 * @param   string     	$payment_number    			Payment number
	 * @param   string     	$payment_issuer    			Payment issuer
	 * @param   string     	$payment_bank_account_name	Payment bank account name
     * @return	int|array	                         	<0 if KO or the list of ID of the bank transaction
     */
    public function createPayment($user, $bank_account_id, $element_type, $payment_date, $payment_mode_id, $amount_infos, $payment_number='', $payment_issuer='', $payment_bank_account_name='')
    {
        global $langs, $hookmanager;
        dol_syslog(__METHOD__ . " user_id={$user->id}, bank_account_id=$bank_account_id, element_type=$element_type" .
            ", payment_date=$payment_date, payment_mode_id=$payment_mode_id, amount_infos=".json_encode($amount_infos).
            ", payment_number=$payment_number, payment_issuer=$payment_issuer, payment_bank_account_name=$payment_bank_account_name", LOG_DEBUG);
        $langs->load('banking4dolibarr@banking4dolibarr');
		$error = 0;

        // Clean parameters
        $bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
        $element_type = trim($element_type);
        $payment_mode_id = $payment_mode_id > 0 ? $payment_mode_id : 0;
        $payment_number = trim($payment_number);
        $payment_issuer = trim($payment_issuer);
        $payment_bank_account_name = trim($payment_bank_account_name);
		$amount_infos = is_array($amount_infos) ? $amount_infos : array();
		$amounts = array();
		$multicurrency_amounts = array();
        foreach ($amount_infos as $id => $infos) {
			$amount = price2num($infos['amount'],'MT');
			$multicurrency_amount = price2num($infos['multicurrency_amount'],'MT');
			if (!($id > 0) || (empty($amount) && empty($multicurrency_amount))) continue;
			$amounts[$id] = $amount;
			$multicurrency_amounts[$id] = $multicurrency_amount;
		}
		$amounts_per_third_party = array();

		// Check parameters
		if (empty($bank_account_id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrBankAccountID'));
			$error++;
		}
		if (empty($payment_mode_id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrPaymentModeId'));
			$error++;
		}
		if (empty($element_type)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrElementType'));
			$error++;
		}
		if (empty($amounts)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrAmounts'));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

        $this->db->begin();

		$bank_line_ids = array();
		if (!in_array($element_type, array('salaries', 'vat')) || $isV14p) {
			switch ($element_type) {
				case 'facture':
					require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
					require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
					require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';

					// Split amounts per customer
					foreach ($amounts as $id => $amounts_infos) {
						$fac = new Facture($this->db);
						$fac->fetch($id);

						if (!isset($amounts_per_third_party[$fac->socid]))
							$amounts_per_third_party[$fac->socid] = array('amounts' => array(), 'multicurrency_amounts' => array());
						$amounts_per_third_party[$fac->socid]['amounts'][$id] = $amounts_infos;
						$amounts_per_third_party[$fac->socid]['multicurrency_amounts'][$id] = $amounts_infos;
					}

					// Make one payment per customer
					foreach ($amounts_per_third_party as $third_party_id => $cursor_amounts) {
						// Creation of payment line
						$payment = new Paiement($this->db);

						$payment->datepaye = $payment_date;
						$payment->amounts = $cursor_amounts['amounts'];   // Array with all payments dispatching with invoice id
						$payment->multicurrency_amounts = $cursor_amounts['multicurrency_amounts'];   // Array with all payments dispatching
						$payment->paiementid = $payment_mode_id;
						$payment->num_paiement = $payment_number;
						$payment->note = $langs->trans('Banking4DolibarrAutoCreateByModule');

						$payment_id = $payment->create($user, 1);
						if ($payment_id < 0) {
							$this->error = $payment->error;
							$this->errors = array_merge($this->errors, $payment->errors);
							$error++;
						}

						$label = '(CustomerInvoicePayment)';
						if (!$error) {
							$sql = 'SELECT type, COUNT(type) as nb' .
								' FROM ' . MAIN_DB_PREFIX . 'facture' .
								' WHERE rowid IN (' . implode(',', array_keys($cursor_amounts['amounts'])) . ')' .
								' GROUP BY type';

							$resql = $this->db->query($sql);
							if ($resql) {
								$nb_credit_note = 0;
								$nb_other = 0;
								while ($obj = $this->db->fetch_object($resql)) {
									if ($obj->type == Facture::TYPE_CREDIT_NOTE) $nb_credit_note += $obj->nb;
									else $nb_other += $obj->nb;
								}
								$this->db->free($resql);

								if (!empty($nb_credit_note) && empty($nb_other)) $label = '(CustomerInvoicePaymentBack)';  // Refund of a credit note
							} else {
								$this->errors[] = 'Error ' . $this->db->lasterror();
								dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
								$error++;
							}
						}

						if (!$error) {
							$result = $payment->addPaymentToBank($user, 'payment', $label, $bank_account_id, $payment_issuer, $payment_bank_account_name);
							if ($result < 0) {
								$this->error = $payment->error;
								$this->errors = array_merge($this->errors, $payment->errors);
								$error++;
							}
						}

						if (!$error) {
							$payment->fetch($payment_id);
							if ($payment->bank_line > 0) $bank_line_ids[] = $payment->bank_line;
						}
					}
					break;
				case 'invoice_supplier':
					require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
					require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
					require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

					// Split amounts per customer
					foreach ($amounts as $id => $amounts_infos) {
						$fac = new FactureFournisseur($this->db);
						$fac->fetch($id);

						if (!isset($amounts_per_third_party[$fac->socid]))
							$amounts_per_third_party[$fac->socid] = array('amounts' => array(), 'multicurrency_amounts' => array());
						$amounts_per_third_party[$fac->socid]['amounts'][$id] = $amounts_infos;
						$amounts_per_third_party[$fac->socid]['multicurrency_amounts'][$id] = $amounts_infos;
					}

					// Make one payment per customer
					foreach ($amounts_per_third_party as $third_party_id => $cursor_amounts) {
						// Creation of payment line
						$payment = new PaiementFourn($this->db);

						// Warning : debit/credit inverted
						$amounts_tmp = array();
						$multicurrency_amounts_tmp = array();
						foreach ($cursor_amounts['amounts'] as $id => $amount) $amounts_tmp[$id] = -$amount;
						foreach ($cursor_amounts['multicurrency_amounts'] as $id => $amount) $multicurrency_amounts_tmp[$id] = -$amount;

						$payment->datepaye = $payment_date;
						$payment->amounts = $amounts_tmp;   // Array of amounts
						$payment->multicurrency_amounts = $multicurrency_amounts_tmp;
						$payment->paiementid = $payment_mode_id;
						$payment->num_paiement = $payment_number;
						$payment->note = $langs->trans('Banking4DolibarrAutoCreateByModule');

						$payment_id = $payment->create($user, 1);
						if ($payment_id < 0) {
							$this->error = $payment->error;
							$this->errors = array_merge($this->errors, $payment->errors);
							$error++;
						}

						if (!$error) {
							$result = $payment->addPaymentToBank($user, 'payment_supplier', '(SupplierInvoicePayment)', $bank_account_id, $payment_issuer, $payment_bank_account_name);
							if ($result < 0) {
								$this->error = $payment->error;
								$this->errors = array_merge($this->errors, $payment->errors);
								$error++;
							}
						}

						if (!$error) {
							$payment->fetch($payment_id);
							if ($payment->bank_line > 0) $bank_line_ids[] = $payment->bank_line;
						}
					}
					break;
				case 'don':
					foreach ($amounts as $element_id => $amount) {
						// Creation of payment line
						require_once DOL_DOCUMENT_ROOT . '/don/class/paymentdonation.class.php';
						$payment = new PaymentDonation($this->db);

						$payment->chid = $element_id;
						$payment->datepaid = $payment_date;
						$payment->amounts = array($element_id => $amount);   // Tableau de montant
						$payment->paymenttype = $payment_mode_id;
						$payment->num_payment = $payment_number;
						$payment->note = $langs->trans('Banking4DolibarrAutoCreateByModule');

						$payment_id = $payment->create($user);
						if ($payment_id < 0) {
							$this->error = $payment->error;
							$this->errors = array_merge($this->errors, $payment->errors);
							$error++;
						}

						if (!$error) {
							$result = $payment->addPaymentToBank($user, 'payment_donation', '(DonationPayment)', $bank_account_id, $payment_issuer, $payment_bank_account_name);
							if ($result < 0) {
								$this->error = $payment->error;
								$this->errors = array_merge($this->errors, $payment->errors);
								$error++;
							}
						}

						if (!$error) {
							$payment->fetch($payment_id);
							if ($payment->fk_bank > 0) $bank_line_ids[] = $payment->fk_bank;
						}
					}
					break;
				case 'chargesociales':
					foreach ($amounts as $element_id => $amount) {
						// Creation of payment line
						require_once DOL_DOCUMENT_ROOT . '/compta/sociales/class/paymentsocialcontribution.class.php';
						$payment = new PaymentSocialContribution($this->db);

						$payment->chid = $element_id;
						$payment->datepaye = $payment_date;
						// Warning : debit/credit inverted
						$payment->amounts = array($element_id => -$amount);   // Tableau de montant
						$payment->paiementtype = $payment_mode_id;
						$payment->num_paiement = $payment_number;
						$payment->note = $langs->trans('Banking4DolibarrAutoCreateByModule');

						$payment_id = $payment->create($user, 1);
						if ($payment_id < 0) {
							$this->error = $payment->error;
							$this->errors = array_merge($this->errors, $payment->errors);
							$error++;
						}

						if (!$error) {
							$result = $payment->addPaymentToBank($user, 'payment_sc', '(SocialContributionPayment)', $bank_account_id, $payment_issuer, $payment_bank_account_name);
							if ($result < 0) {
								$this->error = $payment->error;
								$this->errors = array_merge($this->errors, $payment->errors);
								$error++;
							}
						}

						if (!$error) {
							$payment->fetch($payment_id);
							if ($payment->fk_bank > 0) $bank_line_ids[] = $payment->fk_bank;
						}
					}
					break;
				case 'expensereport':
					foreach ($amounts as $element_id => $amount) {
						// Creation of payment line
						require_once DOL_DOCUMENT_ROOT . '/expensereport/class/paymentexpensereport.class.php';
						$payment = new PaymentExpenseReport($this->db);

						require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
						$expensereport = new ExpenseReport($this->db);
						$expensereport->fetch($element_id);

						$payment->chid = $expensereport->id;
						$payment->fk_expensereport = $expensereport->id;
						$payment->datepaid = $payment_date;
						// Warning : debit/credit inverted
						$payment->amounts = array($expensereport->fk_user_author => -$amount);   // Tableau de montant
						$payment->total = -$amount;
						$payment->fk_typepayment = $payment_mode_id;
						$payment->num_payment = $payment_number;
						$payment->note = $langs->trans('Banking4DolibarrAutoCreateByModule');

						$payment_id = $payment->create($user);
						if ($payment_id < 0) {
							$this->error = $payment->error;
							$this->errors = array_merge($this->errors, $payment->errors);
							$error++;
						}

						if (!$error) {
							$result = $payment->addPaymentToBank($user, 'payment_expensereport', '(ExpenseReportPayment)', $bank_account_id, $payment_issuer, $payment_bank_account_name);
							if ($result < 0) {
								$this->error = $payment->error;
								$this->errors = array_merge($this->errors, $payment->errors);
								$error++;
							}
						}

						if (!$error) {
							$payment->fetch($payment_id);
							if ($payment->fk_bank > 0) $bank_line_ids[] = $payment->fk_bank;
							if ($expensereport->total_ttc == $payment->amount) {
								$result = $expensereport->set_paid($expensereport->id, $user);
								if (!$result > 0) {
									$this->error = $expensereport->error;
									$this->errors = array_merge($this->errors, $expensereport->errors);
									$error++;
								}
							}
						}
					}
					break;
				case 'loan':
					foreach ($amounts as $element_id => $amount) {
						// Creation of payment line
						require_once DOL_DOCUMENT_ROOT . '/loan/class/paymentloan.class.php';
						$payment = new PaymentLoan($this->db);

						require_once DOL_DOCUMENT_ROOT . '/loan/class/loan.class.php';
						$loan = new Loan($this->db);
						$loan->fetch($element_id);

						$payment->chid = $loan->id;
						$payment->datepaid = $payment_date;
						$payment->label = $loan->label;
						$payment->amount_capital = $amount;
						$payment->amount_insurance = 0;
						$payment->amount_interest = 0;
						$payment->paymenttype = $payment_mode_id;
						$payment->num_payment = $payment_number;
						$payment->note_private = $langs->trans('Banking4DolibarrAutoCreateByModule');
						$payment->note_public = '';

						$payment_id = $payment->create($user);
						if ($payment_id < 0) {
							$this->error = $payment->error;
							$this->errors = array_merge($this->errors, $payment->errors);
							$error++;
						}

						if (!$error) {
							$result = $payment->addPaymentToBank($user, $element_id, 'payment_loan', '(LoanPayment)', $bank_account_id, $payment_issuer, $payment_bank_account_name);
							if ($result < 0) {
								$this->error = $payment->error;
								$this->errors = array_merge($this->errors, $payment->errors);
								$error++;
							}
						}

						if (!$error) {
							$payment->fetch($payment_id);
							if ($payment->fk_bank > 0) $bank_line_ids[] = $payment->fk_bank;
						}
					}
					break;
				case 'salaries':
					foreach ($amounts as $element_id => $amount) {
						// Creation of payment line
						require_once DOL_DOCUMENT_ROOT . '/salaries/class/paymentsalary.class.php';
						$payment = new PaymentSalary($this->db);

						$payment->chid = $element_id;
						$payment->datepaye = $payment_date;
						$payment->amounts = array($element_id => $amount); // Tableau de montant
						$payment->paiementtype = $payment_mode_id;
						$payment->num_payment = $payment_number;
						$payment->note = $langs->trans('Banking4DolibarrAutoCreateByModule');
						$payment->note_private = $payment->note;

						$payment_id = $payment->create($user, 1);
						if ($payment_id < 0) {
							$this->error = $payment->error;
							$this->errors = array_merge($this->errors, $payment->errors);
							$error++;
						}

						if (!$error) {
							$result = $payment->addPaymentToBank($user, 'payment_salary', '(SalaryPayment)', $bank_account_id, $payment_issuer, $payment_bank_account_name);
							if ($result < 0) {
								$this->error = $payment->error;
								$this->errors = array_merge($this->errors, $payment->errors);
								$error++;
							}
						}

						if (!$error) {
							$payment->fetch($payment_id);
							if ($payment->fk_bank > 0) $bank_line_ids[] = $payment->fk_bank;
						}
					}
					break;
				case 'vat':
					foreach ($amounts as $element_id => $amount) {
						// Creation of payment line
						require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/paymentvat.class.php';
						$payment = new PaymentVAT($this->db);

						$payment->chid         = $element_id;
						$payment->datepaye     = $payment_date;
						$payment->amounts      = array($element_id => $amount);   // Tableau de montant
						$payment->paiementtype = $payment_mode_id;
						$payment->num_payment  = $payment_number;
						$payment->note         = $langs->trans('Banking4DolibarrAutoCreateByModule');
						$payment->note_private = $payment->note;

						$payment_id = $payment->create($user, 1);
						if ($payment_id < 0) {
							$this->error = $payment->error;
							$this->errors = array_merge($this->errors, $payment->errors);
							$error++;
						}

						if (!$error) {
							$result = $payment->addPaymentToBank($user, 'payment_vat', '(VATPayment)', $bank_account_id, $payment_issuer, $payment_bank_account_name);
							if ($result < 0) {
								$this->error = $payment->error;
								$this->errors = array_merge($this->errors, $payment->errors);
								$error++;
							}
						}

						if (!$error) {
							$payment->fetch($payment_id);
							if ($payment->fk_bank > 0) $bank_line_ids[] = $payment->fk_bank;
						}
					}
					break;
				default:
					// Create payment by hooks
					$hookmanager->initHooks(array('banking4dolibarrdao'));
					$parameters = array();
					$reshook = $hookmanager->executeHooks('createPayment', $parameters); // Note that $action and $object may have been
					if ($reshook < 0) {
						$this->error = $hookmanager->error;
						$this->errors = array_merge($this->errors, $hookmanager->errors);
						$error++;
					} else {
						if (!empty($hookmanager->resArray)) $bank_line_ids = $hookmanager->resArray;
					}
					break;
			}
		}

        if (!$error && empty($bank_line_ids)) {
            $this->errors[] = $langs->trans("Banking4DolibarrErrorBankLineNotCreated");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            $error++;
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return $bank_line_ids;
        }
    }

	/**
	 *  Create check receipt payment of objects
	 *
	 * @param   User	$user					User who make the action
	 * @param   int		$bank_account_id		Bank account ID
	 * @param   array	$bank_line_ids			List of bank line ID (Dolibarr)
	 * @param   int		$date_receipt			Date of deposit the receipt to the bank
	 * @return	int								<0 if KO, >0 if OK
	 */
	public function createCheckReceiptPayment($user, $bank_account_id, $date_receipt, $bank_line_ids)
	{
		global $langs, $conf;
		dol_syslog(__METHOD__ . " user_id={$user->id}, bank_account_id=$bank_account_id, date_receipt=$date_receipt, bank_line_ids=" . json_encode($bank_line_ids), LOG_DEBUG);
		$langs->load('banking4dolibarr@banking4dolibarr');

		// Clean parameters
		$bank_account_id = $bank_account_id > 0 ? $bank_account_id : 0;
		$bank_line_ids = is_array($bank_line_ids) ? $bank_line_ids : array();

		// Check parameters
		if (empty($bank_account_id) || empty($bank_line_ids)) {
			$langs->load("errors");
			$this->errors[] = $langs->trans("ErrorBadParameters");
			dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$error = 0;
		$this->db->begin();

		require_once DOL_DOCUMENT_ROOT . '/compta/paiement/cheque/class/remisecheque.class.php';
		$remisecheque = new RemiseCheque($this->db);

		// Create check receipt payment
		$result = $remisecheque->create($user, $bank_account_id, 0, $bank_line_ids);
		if ($result > 0) $result = $remisecheque->validate($user);
		if ($result > 0) $result = $remisecheque->set_date($user, $date_receipt);
		if ($result > 0) {
			$remisecheque->fetch($remisecheque->id); // To force to reload all properties in correct property name
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id'])) $newlang = $_REQUEST['lang_id'];
			//if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$remisecheque->client->default_lang;
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$result = $remisecheque->generatePdf($remisecheque->modelpdf, $outputlangs);
		}

		if ($result < 0) {
			$this->error = $remisecheque->error;
			$this->errors = $remisecheque->errors;
			$error++;
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

    /**
     *  Is bank line already linked
     *
     * @param   int     $fk_bank        Bank line ID (Dolibarr)
     * @return	int	                    <0 if KO, >0 if Yes, =0 if No
     */
    public function isBankLineAlreadyLinked($fk_bank)
    {
        global $langs;
        dol_syslog(__METHOD__ . " fk_bank=$fk_bank", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $fk_bank = $fk_bank > 0 ? $fk_bank : 0;

        // Check parameters
        if (!($fk_bank > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Get nb bank line linked
        $sql = 'SELECT COUNT(DISTINCT brl.fk_bank_record) as nb' .
            ' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl'.
            ' WHERE brl.fk_bank = ' . $fk_bank .
            ' GROUP BY brl.fk_bank';

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $nb_bank_record_linked = 0;
        if ($obj = $this->db->fetch_object($resql)) {
            $nb_bank_record_linked = $obj->nb;
        }
        $this->db->free($resql);

        return $nb_bank_record_linked > 0 ? 1 : 0;
    }

    /**
     *  Is payment already linked
     *
     * @param   int         $payment_id         Payment ID
     * @param   string      $payment_type       Payment type (payment, payment_supplier, payment_donation, ...)
     * @return	int	                            <0 if KO, >0 if Yes, =0 if No
     */
    public function isPaymentAlreadyLinked($payment_id, $payment_type)
    {
        global $langs;
        dol_syslog(__METHOD__ . " payment_id=$payment_id, payment_type=$payment_type", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $payment_id = $payment_id > 0 ? $payment_id : 0;
        $payment_type = trim($payment_type);

        // Check parameters
        if (!($payment_id > 0) || empty($payment_type)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Get nb line linked
        $sql = "SELECT COUNT(DISTINCT brl.rowid) as nb" .
            " FROM " . MAIN_DB_PREFIX . "bank_url AS bu" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS brl ON brl.fk_bank = bu.fk_bank" .
            " WHERE brl.rowid IS NOT NULL" .
            " AND bu.url_id = " . $payment_id .
            " AND bu.type = '" . $this->db->escape($payment_type) . "'";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $nb_bank_record_linked = 0;
        if ($obj = $this->db->fetch_object($resql)) {
            $nb_bank_record_linked = $obj->nb;
        }
        $this->db->free($resql);

        return $nb_bank_record_linked > 0 ? 1 : 0;
    }

    /**
     *  Is bank line amount equal to the sum of all the bank record linked
     *
     * @param   int     $fk_bank        Bank line ID (Dolibarr)
     * @return	int	                    <0 if KO, >0 if Yes, =0 if No
     */
    public function isBankLineAmountReconciledWithSumBankRecordAmount($fk_bank)
    {
        global $langs;
        dol_syslog(__METHOD__ . " fk_bank=$fk_bank", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $fk_bank = $fk_bank > 0 ? $fk_bank : 0;

        // Check parameters
        if (!($fk_bank > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Get nb bank line linked
        $sql = 'SELECT ' . $this->db->ifsql("b.amount = SUM(br.amount)", "1", "0") . ' as equal_amount' .
            ' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl'.
            ' LEFT JOIN ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record as br ON br.rowid = brl.fk_bank_record'.
            ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON b.rowid = brl.fk_bank'.
            ' WHERE brl.fk_bank = ' . $fk_bank .
            ' GROUP BY b.rowid, b.amount';

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $equal_amount = 0;
        if ($obj = $this->db->fetch_object($resql)) {
            $equal_amount = $obj->equal_amount > 0 ? 1 : 0;
        }
        $this->db->free($resql);

        return $equal_amount;
    }

    /**
     *  Reconcile all bank record (Downloaded) linked to a bank line (Dolibarr)
     *
     * @param   User    $user                       User who make the action
     * @param   string  $statement_number           Statement number of the bank reconciliation (date: YYYYMM ou YYYYMMDD)
     * @param   int     $fk_bank                    Bank line ID (Dolibarr)
     * @param   int     $check_statement_number     Check the statement number
     * @param 	int		$notrigger		            0=Disable all triggers
     * @return	int	                                <0 if KO, >0 if OK, =0 if bypassed
     */
    public function reconcileAllBankRecordLinkedToBankLine(User $user, $statement_number, $fk_bank, $check_statement_number=1, $notrigger=0)
    {
        global $conf, $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, statement_number=$statement_number, fk_bank=$fk_bank, check_statement_number=$check_statement_number, notrigger=$notrigger", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $fk_bank = $fk_bank > 0 ? $fk_bank : 0;
        $statement_number = trim($statement_number);

        // Check parameters
        if (!($fk_bank > 0) || empty($statement_number)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Check statement number
        if ($check_statement_number) {
            if (!empty($conf->global->BANK_STATEMENT_REGEX_RULE) &&
                !preg_match('/' . preg_quote($conf->global->BANK_STATEMENT_REGEX_RULE, '/') . '/', $statement_number)
            ) {
                $this->errors[] = $langs->trans("ErrorBankStatementNameMustFollowRegex", $conf->global->BANK_STATEMENT_REGEX_RULE);
                dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        }

        // Get all bank record linked to the bank line
        $sql = 'SELECT brl.fk_bank_record' .
            ' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl'.
            ' WHERE brl.fk_bank = ' . $fk_bank;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $error = 0;
        $this->db->begin();

        while($obj = $this->db->fetch_object($resql)) {
            $budgetinsightbankrecord = new BudgetInsightBankRecord($this->db);
            $result = $budgetinsightbankrecord->fetch($obj->fk_bank_record);
            if ($result > 0) {
                $result = $budgetinsightbankrecord->reconcile($user, $statement_number, $obj->fk_bank_record, $check_statement_number, 1, $notrigger);
            } elseif ($result = 0) {
                $langs->load("errors");
                $this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ID: ' . $obj->fk_bank_record;
                $error++;
                break;
            }
            if ($result < 0) {
                $this->error = $budgetinsightbankrecord->error;
                $this->errors = array_merge($this->errors, $budgetinsightbankrecord->errors);
                $error++;
                break;
            }
        }
        $this->db->free($resql);

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Get the statement number from the date in function of the rule defined
     *
     * @param   int         $date       Date to parse
     * @return	string		            Statement number
     */
    public function getStatementNumberFromDate($date)
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);

        switch ($conf->global->BANKING4DOLIBARR_STATEMENT_NUMBER_RULES) {
            case self::STATEMENT_NUMBER_RULE_DAILY:
                $statement_number = dol_print_date($date, '%Y%m%d');
                break;
            case self::STATEMENT_NUMBER_RULE_WEEKLY:
                $statement_number = dol_print_date($date, '%Y%W');
                break;
            case self::STATEMENT_NUMBER_RULE_QUARTERLY:
                $month = dol_print_date($date, '%m');
                $statement_number = dol_print_date($date, '%Y') . (ceil($month / 3));
                break;
            case self::STATEMENT_NUMBER_RULE_FOUR_MONTHLY:
                $month = dol_print_date($date, '%m');
                $statement_number = dol_print_date($date, '%Y') . (ceil($month / 4));
                break;
            case self::STATEMENT_NUMBER_RULE_YEARLY:
                $statement_number = dol_print_date($date, '%Y');
                break;
            default: // self::STATEMENT_NUMBER_RULE_MONTHLY
                $statement_number = dol_print_date($date, '%Y%m');
                break;
        }

        return $statement_number;
    }

    /**
     *  Get the temporary code for Budget Insight
     *
     * @return	string|int		            <0 if KO, Temporary code for Budget Insight
     */
    protected function getTemporaryCode()
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);

        $storage = new DoliStorage($this->db, $conf);

        try {
            // Check if we have temporary code
            $token = $storage->retrieveAccessToken(self::SERVICE_NAME . '_' . $conf->entity);
        } catch (Exception $e) {
            if ('Token not found in db, are you sure you stored it?' != $e->getMessage()) {
                $this->errors[] = $e->getMessage();
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            } else {
                // Retrieve temporary code from Budget Insight
                $token = $this->retrieveTemporaryCode();
                if (!is_object($token)) {
                    return -1;
                }
            }
        }

        // Is token expired or will temporary code expire in the next 30 seconds
        $expire = ($token->getEndOfLife() !== -9002 && $token->getEndOfLife() !== -9001 && time() > ($token->getEndOfLife() - 30));

        // Token expired so we refresh it
        if ($expire) {
            // Retrieve temporary code from Budget Insight
            $token = $this->retrieveTemporaryCode();
            if (!is_object($token)) {
                return -1;
            }
        }

        return $token->getAccessToken();
    }

    /**
     *  Retrieve the temporary code from Budget Insight
     *
     * @return	string|int		            <0 if KO, Temporary code for Budget Insight
     */
    protected function retrieveTemporaryCode()
    {
        global $conf, $langs;
        dol_syslog(__METHOD__, LOG_DEBUG);

        if (empty($this->key)) {
            $langs->load('banking4dolibarr@banking4dolibarr');
            $this->errors[] = $langs->trans("Banking4DolibarrErrorModuleNotConfigured");
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $results = $this->_sendToApi(self::METHOD_GET, '/auth/token/code', [], $this->key);
        if (!is_array($results)) {
            return -1;
        }

        $storage = new DoliStorage($this->db, $conf);
        $token = new StdOAuth2Token();

        $token->setAccessToken($results['code']);
        $token->setLifetime($results['expires_in']);
        $token->setExtraParams($results);

        $storage->storeAccessToken(self::SERVICE_NAME . '_' . $conf->entity, $token);

        return $token;
    }

    /**
     * find Dolibarr bank accounts from the IBAN
     *
     * @param   string      $iban           IBAN to search
     * @return  int                         <0 if KO, =0 if not found, id of the bank account if found
     */
    public function findDolibarrBankAccount($iban)
    {
        dol_syslog(__METHOD__ . " iban=$iban", LOG_DEBUG);

        if (empty($iban) && empty($bic))
            return 0;

        $result = $this->loadDolibarrBankAccounts();
        if ($result < 0) return -1;

        $iban = str_replace(' ', '', $iban);

        if (is_array(self::$dolibarr_bank_accounts_cached)) {
            foreach (self::$dolibarr_bank_accounts_cached as $account_id => $account) {
                if ($iban == str_replace(' ', '', $account['iban'])) {
                    return $account_id;
                }
            }
        }

        return 0;
    }

	/**
	 * Has duplicate bank records
	 *
	 * @param 	int		$bank_account_id	Bank account id
	 * @return  int							<0 if KO, =0 if No, >0 if Yes
	 */
	public function hasDuplicateBankRecords($bank_account_id)
	{
		global $conf;
		dol_syslog(__METHOD__, LOG_DEBUG);

		$duplicate_test_on_fields = !empty($conf->global->BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS) ? explode(',', $conf->global->BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS) : array('label', 'record_date', 'vdate', 'amount');
		$duplicate_filters = array();
		foreach ($duplicate_test_on_fields as $field) {
			if ($field == "vdate") {
				$duplicate_filters[] = $this->db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate");
			} else {
				$duplicate_filters[] = 'br.' . $field;
			}
		}

		$sql = "SELECT COUNT(*) AS nb" . //"GROUP_CONCAT(br.id_record SEPARATOR ', ')" .
			" FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS br" .
			" LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account as cb4dba ON cb4dba.rowid = br.id_account" .
			" LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON ba.rowid = cb4dba.fk_bank_account" .
			" WHERE ba.entity IN (" . getEntity('bank_account') . ")" .
			" AND ba.rowid = " . $bank_account_id .
			" AND br.status != " . BudgetInsightBankRecord::BANK_RECORD_STATUS_DUPLICATE .
			" GROUP BY " . implode(', ', $duplicate_filters) .
			" HAVING COUNT(*) > 1" .
			" AND MIN(br.status) = " . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED;
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		$nb_result = 0;
		if ($obj = $this->db->fetch_object($resql)) $nb_result = $obj->nb;
		$this->db->free($resql);

		return $nb_result;
	}

	/**
	 * Fix duplicate bank records
	 *
	 * @param   User    $user               	User who make the action
	 * @param   bool    $record_ids       		Bank record IDs to fix
	 * @param   bool    $duplicate_as_one       All records provided is a one duplicate record
	 * @return  int								<0 if KO, >0 if Yes
	 */
	public function fixDuplicateRecords(User $user, $record_ids, $duplicate_as_one = false)
	{
		global $conf, $langs;
		dol_syslog(__METHOD__ . " user_id={$user->id}, record_ids=" . json_encode($record_ids) . ", duplicate_as_one=" . ($duplicate_as_one ? 1 : 0), LOG_DEBUG);
		$langs->load('banking4dolibarr@banking4dolibarr');

		// Clean parameters
		$record_ids = is_array($record_ids) ? $record_ids : array();
		$duplicate_as_one = !empty($duplicate_as_one);

		// Check parameters
		if (empty($record_ids)) {
			$langs->load("errors");
			$this->errors[] = $langs->trans("Banking4DolibarrErrorRecordsIdsToFixDuplicateRequired");
			dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$duplicate_test_on_fields = !empty($conf->global->BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS) ? explode(',', $conf->global->BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS) : array('label', 'record_date', 'vdate', 'amount');
		$duplicate_fields = array();
		$duplicate_sort_fields = array();
		$duplicate_sort_orders = array();
		foreach ($duplicate_test_on_fields as $field) {
			$duplicate_sort_fields[] = 'br.' . $field;
			$duplicate_sort_orders[] = 'ASC';
			if ($field == "vdate") {
				$duplicate_fields[] = $this->db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate") . " AS vdate";
			} else {
				$duplicate_fields[] = 'br.' . $field;
			}
		}
		$duplicate_sort_fields = array_merge($duplicate_sort_fields, array('br.status', 'br.datec'));
		$duplicate_sort_orders = array_merge($duplicate_sort_orders, array('DESC', 'ASC'));

		$sql = "SELECT br.rowid, br.id_record, br.id_account, br.reconcile_date, br.status, br.deleted_date, br.datec" .
			($duplicate_as_one ? '' : ', ' . implode(', ', $duplicate_fields)) .
			" FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS br" .
			" LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account as cb4dba ON cb4dba.rowid = br.id_account" .
			" LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON ba.rowid = cb4dba.fk_bank_account" .
			" WHERE ba.entity IN (" . getEntity('bank_account') . ")" .
			" AND br.rowid IN (" . implode(',', $record_ids) .")" .
			" AND br.status != " . BudgetInsightBankRecord::BANK_RECORD_STATUS_DUPLICATE;
		$sql_sort = $this->db->order(implode(',', $duplicate_sort_fields), implode(',', $duplicate_sort_orders));
		$sql .= str_replace('br.vdate', $this->db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate"), $sql_sort);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		$nb_fixed = 0;
		$budgetinsightbankrecord = new BudgetInsightBankRecord($this->db);
		$last_duplicate_field_values = array();
		$first_line = true;
		$duplicate_ids = array();
		$main_id = 0;
		$main_reconcile_date = null;
		$main_status = -1;
		$last_id_account = 0;
		$last_creation_date = null;
		while ($obj = $this->db->fetch_object($resql)) {
			$duplicate = true;
			if (!$duplicate_as_one) {
				foreach ($duplicate_test_on_fields as $field) {
					if (!$first_line && $last_duplicate_field_values[$field] != $obj->$field) $duplicate = false;
					$last_duplicate_field_values[$field] = $obj->$field;
				}
			}

			if (!$duplicate) {
				// Fix duplicate
				$result = $budgetinsightbankrecord->fixDuplicateRecords($user, $main_id, $main_reconcile_date, $main_status, $duplicate_ids);
				if ($result < 0) {
					$this->error = $budgetinsightbankrecord->error;
					$this->errors = $budgetinsightbankrecord->errors;
					return -1;
				}
				$nb_fixed++;
				$first_line = true;
				$duplicate_ids = array();
				$main_id = 0;
				$main_reconcile_date = null;
				$main_status = -1;
				$last_id_account = 0;
				$last_creation_date = null;
			}

			// Check coherence
			$reconcile_date = $this->db->jdate($obj->reconcile_date);
			$creation_date = $this->db->jdate($obj->datec);
			if (!$first_line && $obj->id_account != $last_id_account) {
				$this->errors[] = $langs->trans('Banking4DolibarrErrorFixDuplicateRecordsOnDifferentBankAccount', $obj->id_record);
				dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
				return -1;
			}
			if (!$first_line && $main_status == BudgetInsightBankRecord::BANK_RECORD_STATUS_RECONCILED && $obj->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_RECONCILED) {
				$this->errors[] = $langs->trans('Banking4DolibarrErrorFixDuplicateRecordsOnMultipleReconciled', $obj->id_record);
				dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
				return -1;
			}
			// Add to duplicate to fix
			if (empty($obj->deleted_date) && ($main_status != $obj->status || $last_creation_date < $creation_date)) {
				if ($main_id > 0) $duplicate_ids[] = $main_id;
				$main_id = $obj->rowid;
			} else {
				$duplicate_ids[] = $obj->rowid;
			}
			$last_id_account = $obj->id_account;
			$last_creation_date = $creation_date;
			if ($main_status != BudgetInsightBankRecord::BANK_RECORD_STATUS_RECONCILED) {
				$main_status = $obj->status;
				if ($obj->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_RECONCILED)
					$main_reconcile_date = $reconcile_date;
			}
			$first_line = false;
		}

		// Fix duplicate
		$result = $budgetinsightbankrecord->fixDuplicateRecords($user, $main_id, $main_reconcile_date, $main_status, $duplicate_ids);
		if ($result < 0) {
			$this->error = $budgetinsightbankrecord->error;
			$this->errors = $budgetinsightbankrecord->errors;
			return -1;
		}
		$nb_fixed++;

		return $nb_fixed;
	}

    /**
     * Load the cache for banks
     *
     * @param   bool    $force_reload       Force reload of the cache
     * @return  int                         <0 if KO, >0 if OK
     */
    public function loadDolibarrBankAccounts($force_reload=false)
    {
        if (!isset(self::$dolibarr_bank_accounts_cached) || $force_reload) {
            $sql = "SELECT rowid, iban_prefix FROM " . MAIN_DB_PREFIX . "bank_account".
                " WHERE entity IN (".getEntity('bank_account').")";
            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                return -1;
            }

            self::$dolibarr_bank_accounts_cached = array();
            while ($obj = $this->db->fetch_object($resql)) {
                self::$dolibarr_bank_accounts_cached[$obj->rowid] = array(
                    'iban' => $obj->iban_prefix,
                );
            }
        }

        return 1;
    }

    /**
     * Load the cache for banks
     *
     * @param   bool    $force_reload       Force reload of the cache
     * @return  int                         <0 if KO, >0 if OK
     */
    public function loadBanks($force_reload=false)
    {
        if (!isset(self::$banks_cached) || $force_reload) {
            $banks_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbanks');
            $res = $banks_dictionary->fetch_lines(-1);
            if ($res > 0) {
                self::$banks_cached = $banks_dictionary->lines;
            } else {
                $this->error = $banks_dictionary->error;
                $this->errors = array_merge($this->errors, $banks_dictionary->errors);
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        }

        return 1;
    }

    /**
     * Load the cache for bank accounts
     *
     * @param   bool    $force_reload       Force reload of the cache
     * @return  int                         <0 if KO, >0 if OK
     */
    public function loadBankAccounts($force_reload=false)
    {
        if (!isset(self::$bank_accounts_cached) || $force_reload) {
            $bank_accounts_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankaccounts');
            $res = $bank_accounts_dictionary->fetch_lines(-1);
            if ($res > 0) {
                self::$bank_accounts_cached = $bank_accounts_dictionary->lines;
            } else {
                $this->error = $bank_accounts_dictionary->error;
                $this->errors = array_merge($this->errors, $bank_accounts_dictionary->errors);
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        }

        return 1;
    }

    /**
     * Load the cache for bank account types
     *
     * @param   bool    $force_reload       Force reload of the cache
     * @return  int                         <0 if KO, >0 if OK
     */
    public function loadBankAccountTypes($force_reload=false)
    {
        if (!isset(self::$bank_account_types_cached) || $force_reload) {
            $bank_account_types_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankaccounttypes');
            $res = $bank_account_types_dictionary->fetch_lines(-1);
            if ($res > 0) {
                self::$bank_account_types_cached = $bank_account_types_dictionary->lines;
            } else {
                $this->error = $bank_account_types_dictionary->error;
                $this->errors = array_merge($this->errors, $bank_account_types_dictionary->errors);
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Get remote ID of the bank account linked
     *
     * @param   int    $bank_account_id     ID of the bank account in Dolibarr
     * @return	int	                        <0 if KO, 0 if none, >0 if remote ID of the bank account
     */
    public function getRemoteBankAccountID($bank_account_id)
    {
        dol_syslog(__METHOD__ . " bank_account_id=$bank_account_id", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        if (!($bank_account_id > 0))
            return 0;

        $result = $this->loadBankAccounts();
        if ($result < 0) return null;

        foreach(self::$bank_accounts_cached as $bank_account) {
            if($bank_account->fields['fk_bank_account'] == $bank_account_id && $bank_account->active)
                return $bank_account->id;
        }

        return 0;
    }

    /**
     *  Get last update date of the bank records of a bank account
     *
     * @param   int    $bank_account_id     ID of the bank account
     * @return	int	                        <0 if KO, 0 if none, >0 if date
     */
    public function getBankRecordsLastUpdateDate($bank_account_id)
    {
        dol_syslog(__METHOD__ . " bank_account_id=$bank_account_id", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        return $this->getProcessLastUpdateDate(self::PROCESS_KEY_REFRESH_BANK_RECORDS . '_' . $bank_account_id);
    }

    /**
     *  Set a unique flag for a type of process (only one person can launch the process a the same time)
     *
     * @param   string      $process_key        Key of process
     * @param   string      $state              UUID for test of unique process
     * @param   bool        $status             Status of the process flag to set
     * @param   bool        $forced             Force the update of the flag
     * @return	int	                            <0 if KO, >0 if OK
     */
    public function setProcessFlag($process_key, $state, $status, $forced=false)
    {
        global $langs, $conf;
        dol_syslog(__METHOD__ . " process_key=$process_key, status=$status", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $error = 0;
        if (empty($process_key)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrProcessKey'));
            $error++;
        }
        if (empty($state) && !$forced) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrProcessState'));
            $error++;
        }
        if (!isset($status)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrProcessStatus'));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $key = 'BANKING4DOLIBARR_PROCESS_FLAG_' . strtoupper($process_key);

        if (empty($conf->global->$key) || $conf->global->$key == $state || $forced) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

            if ($status) {
                $res = dolibarr_set_const($this->db, $key, $state, 'chaine', 0, '', $conf->entity);
            } else {
                $res = dolibarr_del_const($this->db, $key, $conf->entity);
            }

            if ($res < 0) {
                $this->errors[] = 'Error del const: ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
            return 1;
        }

        $this->errors[] = $langs->trans('Banking4DolibarrErrorOnlyOneProcessAtTheSameTime');

        return -1;
    }

    /**
     *  Is theunique flag for a type of process (only one person can launch the process a the same time) is defined
     *
     * @param   string      $process_key        Key of process
     * @param   string      $state              UUID for test of unique process
     * @return	int	                            <0 if KO, =0 if No, >0 if Yes
     */
    public function isProcessFlagSet($process_key, $state=null)
    {
        global $langs, $conf;
        dol_syslog(__METHOD__ . " process_key=$process_key, state=$state", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        if (isset($state)) $state = trim($state);

        $error = 0;
        if (empty($process_key)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrProcessKey'));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $key = 'BANKING4DOLIBARR_PROCESS_FLAG_' . strtoupper($process_key);

        return ((!isset($state) && !empty($conf->global->$key) || isset($state) && $conf->global->$key == $state)) ? 1 : 0;
    }

    /**
     *  Get the last update date for a type of process
     *
     * @param   string      $process_key        Key of process
     * @return	int	                            <0 if KO, >0 if date
     */
    public function getProcessLastUpdateDate($process_key)
    {
        global $langs, $conf;
        dol_syslog(__METHOD__ . " process_key=$process_key", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $error = 0;
        if (empty($process_key)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrProcessKey'));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $key = 'BANKING4DOLIBARR_PROCESS_LAST_UPDATE_DATE_' . strtoupper($process_key);

        return empty($conf->global->$key) ? 0 : $conf->global->$key;
    }

    /**
     *  Set the last update date for a type of process
     *
     * @param   string      $process_key        Key of process
     * @param   int         $date               Date to set
     * @return	int	                            <0 if KO, >0 if OK
     */
    public function setProcessLastUpdateDate($process_key, $date)
    {
        global $langs, $conf;
        dol_syslog(__METHOD__ . " process_key=$process_key, date=$date", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $error = 0;
        if (empty($process_key)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrProcessKey'));
            $error++;
        }
        if (!($date > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrProcessLastUpdateDate'));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $key = 'BANKING4DOLIBARR_PROCESS_LAST_UPDATE_DATE_' . strtoupper($process_key);

        $res = dolibarr_set_const($this->db, $key, $date, 'chaine', 0, '', $conf->entity);
        if ($res < 0) {
            $this->errors[] = 'Error set const: ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     *  Set the auto link process status
     *
     * @param   string      $session_process_key        Session process key for saving infos of the process
     * @param   string      $auto_link_process_key      Session process key for saving infos of the process
     * @param   string      $text_base                  Base text for the status text
     * @param   string      $text_status                Statut text for this process for the status text
     * @param   int         $nb_found                   Nb found for this batch
     * @param   int         $on_nb_parsed               On nb parsed for this batch
     * @param   int         $require_confirmation       Require confirmation by user
     * @return	int	                                    <0 if KO, >0 if OK
     */
    public function setAutoLinkProcessStatus($session_process_key, $auto_link_process_key, $text_base, $text_status, $nb_found, $on_nb_parsed=0, $require_confirmation=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " session_process_key=$session_process_key, auto_link_process_key=$auto_link_process_key, text_base=$text_base, text_status=$text_status, nb_found=$nb_found, on_nb_parsed=$on_nb_parsed, require_confirmation=$require_confirmation", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        if (!isset($_SESSION[$session_process_key]['saved_text']))
            $_SESSION[$session_process_key]['saved_text'] = $langs->trans($text_base);

        if (!isset($_SESSION[$session_process_key][$auto_link_process_key])) {
            $_SESSION[$session_process_key]['text'] = $_SESSION[$session_process_key]['saved_text'] . "<br>" . $langs->trans($text_status) . " {{status}}";
            $_SESSION[$session_process_key][$auto_link_process_key]['found'] = 0;
            $_SESSION[$session_process_key][$auto_link_process_key]['total'] = 0;
        }

        $_SESSION[$session_process_key][$auto_link_process_key]['found'] += $nb_found;
        $_SESSION[$session_process_key][$auto_link_process_key]['total'] += $on_nb_parsed;
        $status = $_SESSION[$session_process_key][$auto_link_process_key]['found'] . (!empty($_SESSION[$session_process_key][$auto_link_process_key]['total']) ? ' / ' . $_SESSION[$session_process_key][$auto_link_process_key]['total'] : '');

        if ($nb_found == 0) {
            $_SESSION[$session_process_key]['saved_text'] .= "<br>" . $langs->trans($text_status) . " " . $status;
            $_SESSION[$session_process_key][$auto_link_process_key]['finished'] = true;
            return 0;
        } elseif (!empty($require_confirmation)) {
            $_SESSION[$session_process_key]['require_confirmation'] = true;
        }

        if (!isset($_SESSION[$session_process_key]['nb_processed'])) $_SESSION[$session_process_key]['nb_processed'] = 0;
        $_SESSION[$session_process_key]['nb_processed'] = $_SESSION[$session_process_key]['nb_processed'] + $nb_found;

        return $status;
    }

    /**
     *  Check if the auto link process is finished or not
     *
     * @param   string      $session_process_key        Session process key for saving infos of the process
     * @param   string      $auto_link_process_key      Session process key for saving infos of the process
     * @return	int	                                    =0 if not, >0 if finished
     */
    public function isAutoLinkProcessFinished($session_process_key, $auto_link_process_key)
    {
        dol_syslog(__METHOD__ . " session_process_key=$session_process_key, auto_link_process_key=$auto_link_process_key", LOG_DEBUG);
        return empty($_SESSION[$session_process_key][$auto_link_process_key]['finished']) ? 0 : 1;
    }

    /**
     *  Get the offset of the next the auto link process
     *
     * @param   string      $session_process_key        Session process key for saving infos of the process
     * @param   string      $auto_link_process_key      Session process key for saving infos of the process
     * @return	int	                                    Offset
     */
    public function getAutoLinkProcessOffset($session_process_key, $auto_link_process_key)
    {
        dol_syslog(__METHOD__ . " session_process_key=$session_process_key, auto_link_process_key=$auto_link_process_key", LOG_DEBUG);
        return empty($_SESSION[$session_process_key][$auto_link_process_key]['total']) ? 0 : $_SESSION[$session_process_key][$auto_link_process_key]['total'];
    }

    /**
     *  Check if the process require confirmation by user
     *
     * @param   string      $session_process_key        Session process key for saving infos of the process
     * @return	int	                                    =0 if not, >0 if finished
     */
    public function isAutoLinkProcessRequireConfirmation($session_process_key)
    {
        dol_syslog(__METHOD__ . " session_process_key=$session_process_key", LOG_DEBUG);
        return empty($_SESSION[$session_process_key]['require_confirmation']) ? 0 : 1;
    }

    /**
     *  Get the new status text of the auto link process
     *
     * @param   string      $session_process_key        Session process key for saving infos of the process
     * @param   bool        $is_last_loop               Is last loop of the auto link process
     * @return	int	                                    New status text
     */
    public function getAutoLinkProcessNewStatusText($session_process_key, $is_last_loop=false)
    {
        dol_syslog(__METHOD__ . " session_process_key=$session_process_key", LOG_DEBUG);

        $text = '';
        if ($is_last_loop) {
            $text = $_SESSION[$session_process_key]['saved_text'];
        } elseif (!empty($_SESSION[$session_process_key]['text'])) {
            $text = $_SESSION[$session_process_key]['text'];
            $_SESSION[$session_process_key]['text'] = '';
        }

        return $text;
    }

    /**
     *  Get the number of processed lines of the auto link process
     *
     * @param   string      $session_process_key        Session process key for saving infos of the process
     * @return	int	                                    Number of processed lines
     */
    public function getAutoLinkProcessNbProcessed($session_process_key)
    {
        dol_syslog(__METHOD__ . " session_process_key=$session_process_key", LOG_DEBUG);
        return !empty($_SESSION[$session_process_key]['nb_processed']) ? $_SESSION[$session_process_key]['nb_processed'] : 0;
    }

	/**
	 *  Refresh all bank records from Budget Insight of a bank account (cron)
	 *
	 *  @return	int				0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function cronRefreshBankRecords()
	{
		global $conf, $user, $langs;

		if (!$user->rights->banking4dolibarr->bank_records->refresh) {
			$langs->load('errors');
			$this->error = $langs->trans('ErrorForbidden');
			dol_syslog(__METHOD__ . " Error: " . $this->error, LOG_ERR);
			return -1;
		}

		$sql = "SELECT ba.rowid, ba.ref, ba.label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "bank_account as ba";
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_account as cb4dba ON cb4dba.fk_bank_account = ba.rowid';
		$sql .= " WHERE ba.entity IN (" . getEntity('bank_account') . ")";
		$sql .= " AND ba.clos = 0";
		$sql .= " AND cb4dba.rowid IS NOT NULL";
		$sql .= " AND cb4dba.cron_refresh_bank_records = 1";
		$sql .= " ORDER BY ba.ref";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		$langs->load('banking4dolibarr@banking4dolibarr');
		$langs->load('banks');
		$error = 0;
		$output = '';

		while ($obj = $this->db->fetch_object($resql)) {
			$output .= $langs->trans('BankAccount') . ': ' . $obj->ref . ' - ' . $obj->label . "<br>";
			$last_update_date = $this->getBankRecordsLastUpdateDate($obj->rowid);
			if ($last_update_date < 0) {
				$output .= '<span style="color: red">' . $langs->trans('Error') . ': ' . $this->errorsToString() . '</span>' . "<br>";
				$error++;
			} elseif ($last_update_date == 0) {
				$output .= '<span style="color: red">' . $langs->trans('Error') . ': ' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrStartDate')) . '</span>' . "<br>";
				$error++;
			}

			if (!$error) {
                $langs->load('banking4dolibarr@banking4dolibarr');
				try {
					$result = $this->connection();
					if ($result < 0) {
						$output .= '<span style="color: red">' . $langs->trans('Error') . ': ' . $this->errorsToString() . '</span>' . "<br>";
						$error++;
						break;
					}

					if (!$error) {
						$offset = 0;
						$state = '';
						$text = '';

						$begin_date = dol_now();
						do {
							$result = $this->refreshBankRecords($user, $obj->rowid, $last_update_date, $offset, $state);
							if (!is_array($result)) {
								$output .= '<span style="color: red">' . $langs->trans('Error') . ': ' . $this->errorsToString() . '</span>' . "<br>";
								$error++;
								break;
							}

							$offset = $result['offset'];
							$finish = $result['finish'];
							$state = $result['state'];

							if (!empty($result['additional_text'])) {
								$text .= $result['additional_text'];
							}
						} while (!$finish);
						$end_date = dol_now();

						if ($offset > 0) {
							$url = dol_buildpath('/banking4dolibarr/bankrecords.php', 2) . '?id=' . $obj->rowid . '&search_tms=' . urlencode('>=' . dol_print_date($begin_date, 'standard') . ' & <=' . dol_print_date($end_date, 'standard'));
							$link = '<a href="' . dol_escape_js($url) . '">' . $offset . '</a>';
							$output .= '<span style="color: green">' . $langs->trans('Banking4DolibarrRefreshBankRecordsSuccess', $link) . '</span><br>';
						} else {
							$output .= '<span style="color: green">' . $langs->trans('Banking4DolibarrRefreshBankRecordsSuccessNoLinesUpdated') . '</span><br>';
						}
					}
				} catch (Exception $e) {
					$output .= '<span style="color: red;">' . $langs->trans('Error') . ': ' . $e->getMessage() . '</span>' . "<br>";
					$error++;
				}
			}

			$has_duplicate_bank_records = $this->hasDuplicateBankRecords($obj->rowid);
			if ($has_duplicate_bank_records < 0) {
				$output .= '<span style="color: red">' . $langs->trans('Error') . ': ' . $this->errorsToString() . '</span>' . "<br>";
				$error++;
			} elseif ($has_duplicate_bank_records) {
				$output .= '<span style="color: orangered">' . $langs->trans('Banking4DolibarrWarningDuplicateBankRecordFound') . '</span>' . "<br>";
			}

			$output .= '<br>';
		}
		$this->db->free($resql);

		if (!empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS) &&
			(!empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_USERS) || !empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_GROUPS))
		) {
			$output2 = $langs->trans('Banking4DolibarrNotifyEmailRefreshBankRecord') . ':</br>';

			$error2 = 0;
			$emails = array();
			$empty_emails = array();
			if (!empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_USERS)) {
				$users = explode(',', $conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_USERS);
				$user_static = new User($this->db);
				foreach ($users as $user_id) {
					$result = $user_static->fetch($user_id);
					if ($result < 0) {
						$output2 .= '<span style="color: red;">' . $langs->trans('Error') . ': ' . $user_static->errorsToString() . '</span>' . "<br>";
						$error2++;
						break;
					} elseif ($result > 0 && $user_static->statut == 1) {
						if (empty($user_static->email)) {
							$empty_emails[$user_static->id] = $this->_formatEmail($user_static->getFullName($langs), $user_static->email);
						} else {
							$emails[$user_static->email] = $this->_formatEmail($user_static->getFullName($langs), $user_static->email);
						}
					}
				}
			}

			if (!$error2 && !empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_GROUPS)) {
				$groups = explode(',', $conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_GROUPS);
				$group_static = new UserGroup($this->db);
				foreach ($groups as $group_id) {
					$result = $group_static->fetch($group_id);
					if ($result < 0) {
						$output2 .= '<span style="color: red;">' . $langs->trans('Error') . ': ' . $group_static->errorsToString() . '</span>' . "<br>";
						$error2++;
						break;
					} elseif ($result > 0 && !empty($group_static->members)) {
						foreach ($group_static->members as $user_f) {
							if ($user_f->statut == 1) {
								if (empty($user_f->email)) {
									$empty_emails[$user_f->id] = $this->_formatEmail($user_f->getFullName($langs), $user_f->email);
								} else {
									$emails[$user_f->email] = $this->_formatEmail($user_f->getFullName($langs), $user_f->email);
								}
							}
						}
					}
				}
			}

			if (!$error2 && !empty($emails)) {

				$subject = ($error ? 'Banking4DolibarrNotifyEmailRefreshBankRecordErrorSubject' : 'Banking4DolibarrNotifyEmailRefreshBankRecordSuccessSubject');
			    $subject = $langs->transnoentitiesnoconv($subject);
				$send_to = array_values($emails);
				$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
				$body = $output;
				$result = $this->_sendEmail($subject, $send_to, $from, $body);
				if (is_numeric($result) && $result < 0) {
					$output2 .= '<span style="color: red;">' . $langs->trans('Error') . ': ' . $this->errorsToString('<br>') . '</span>' . "<br>";
				} else {
					$output2 .= '<pre>' . $result . '</pre>';
				}
			}

			if (!$error2 && !empty($empty_emails)) {
				$output2 .= '<span style="color: orange;">' . $langs->trans('Banking4DolibarrWarningEmptyEmails') . ': ' . implode(', ', $empty_emails) . '</span>' . "<br>";
			}

			$output .= $output2;
		}

		if ($error) {
			$this->error = $output;
			return -1;
		}

		$this->error = "";
		$this->output = $output;
		$this->result = array("commandbackuplastdone" => "", "commandbackuptorun" => "");

		return 0;
	}

	/**
	 *  Get emails list of all the assigned to the request
	 *
	 * @param   string      $name       Name of the user
	 * @param   string      $name       Address email
	 * @return  string                  Formatted email (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 */
	private function _formatEmail($name, $email)
	{
		if (!preg_match('/<|>/i', $email) && !empty($name)) {
			$email = str_replace(array('<', '>'), '', $name) . ' <' . $email . '>';
		}

		return $email;
	}

	/**
	 *  Send notification to the assigned, requesters, watchers for a type of notification
	 *
	 * @param   string	        $subject             Topic/Subject of mail
	 * @param   array|string	$sendto              List of recipients emails  (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 * @param   string	        $from                Sender email               (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 * @param   string	        $body                Body message
	 * @param   array	        $filename_list       List of files to attach (full path of filename on file system)
	 * @param   array	        $mimetype_list       List of MIME type of attached files
	 * @param   array	        $mimefilename_list   List of attached file name in message
	 * @param   array|string	$sendtocc            Email cc
	 * @param   array|string	$sendtobcc           Email bcc (Note: This is autocompleted with MAIN_MAIL_AUTOCOPY_TO if defined)
	 * @param   int		        $deliveryreceipt     Ask a delivery receipt
	 * @param   int		        $msgishtml           1=String IS already html, 0=String IS NOT html, -1=Unknown make autodetection (with fast mode, not reliable)
	 * @param   string	        $errors_to      	 Email for errors-to
	 * @param   string	        $css                 Css option
	 * @param   string          $moreinheader        More in header. $moreinheader must contains the "\r\n" (TODO not supported for other MAIL_SEND_MODE different than 'phpmail' and 'smtps' for the moment)
	 * @param   string          $sendcontext      	 'standard', 'emailing', ...
	 * @return  int|string                           <0 if KO, result message if OK
	 */
	public function _sendEmail($subject, $sendto, $from, $body, $filename_list=array(), $mimetype_list=array(), $mimefilename_list=array(), $sendtocc="", $sendtobcc="", $deliveryreceipt=0, $msgishtml=1, $errors_to='', $css='', $moreinheader='', $sendcontext='standard')
	{
		global $langs, $dolibarr_main_url_root;
		dol_syslog(__METHOD__ . " subject=$subject, sendto=$sendto, from=$from, body=$body, filename_list=".json_encode($filename_list).", mimetype_list=".json_encode($mimetype_list).", mimefilename_list=".json_encode($mimefilename_list).", sendtocc=$sendtocc, sendtobcc=$sendtobcc, deliveryreceipt=$deliveryreceipt, msgishtml=$msgishtml, errors_to=$errors_to, css=$css, moreinheader=$moreinheader, sendcontext=$sendcontext", LOG_DEBUG);
		$this->errors = array();

		$langs->load('mails');

		// Check parameters
		$sendto = is_array($sendto) ? implode(',', $sendto) : $sendto;
		$sendtocc = is_array($sendtocc) ? implode(',', $sendtocc) : $sendtocc;
		$sendtobcc = is_array($sendtobcc) ? implode(',', $sendtobcc) : $sendtobcc;

		if (!empty($sendto)) {
			// Define $urlwithroot
			$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;   // This is to use external domain name found into config file
			//$urlwithroot=DOL_MAIN_URL_ROOT;                     // This is to use same domain name than current

			// Make a change into HTML code to allow to include images from medias directory with an external reabable URL.
			// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
			// become
			// <img alt="" src="'.$urlwithroot.'viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
			$body = preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1' . $urlwithroot . '/viewimage.php\2modulepart=medias\3file=\4\5', $body);

			// Send mail
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$mailfile = new CMailFile($subject, $sendto, $from, $body, $filename_list, $mimetype_list, $mimefilename_list, $sendtocc, $sendtobcc, $deliveryreceipt, $msgishtml, $errors_to, $css, '', $moreinheader, $sendcontext);
			if (!empty($mailfile->error)) {
				$this->errors[] = $mailfile->error;
			} else {
				$result = $mailfile->sendfile();
				if ($result) {
					return $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($sendto, 2));
				} else {
					$langs->load("other");
					$mesg = '<div class="error">';
					if ($mailfile->error) {
						$mesg .= $langs->trans('ErrorFailedToSendMail', $from, $sendto);
						$mesg .= '<br>' . $mailfile->error;
					} else {
						$mesg .= 'No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
					}
					$mesg .= '</div>';
					$this->errors[] = $mesg;
				}
			}
		} else {
			$langs->load("errors");
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo"));
		}

		dol_syslog(__METHOD__ . " Error: {$this->errorsToString()}", LOG_ERR);
		return -1;
	}

    /**
	 *  Send to the Api
	 *
	 * @param   string  $method     Method request
	 * @param   string  $url        Url request
	 * @param   array   $options    Options request
     * @param   string  $code       Specific code for the request
	 * @return	int                 <0 if KO, >0 if OK
	 */
    protected function _sendToApi($method, $url, $options = [], $code='')
    {
	    dol_syslog(__METHOD__ . " method=" . $method . " url=" . $url . " options=" . json_encode($options) . " code=" . $code, LOG_DEBUG);
	    global $conf, $langs;

	    try {
            if (!isset($this->client)) {
                $langs->load('banking4dolibarr@banking4dolibarr');
                $this->errors[] = $langs->trans("Banking4DolibarrErrorConnectionNotInitialized");
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }

            if (empty($code)) {
                $result = $this->fetchCode();
                if ($result < 0) {
                    return -1;
                }
                $code = $this->code;
            }
		    $options['headers']['Authorization'] = 'Bearer ' . $code;

		    switch ($method) {
                case self::METHOD_GET:
                    $response = $this->client->get($this->api_uri . $url, $options);
                    break;
                case self::METHOD_HEAD:
                    $response = $this->client->head($this->api_uri . $url, $options);
                    break;
                case self::METHOD_DELETE:
                    $response = $this->client->delete($this->api_uri . $url, $options);
                    break;
                case self::METHOD_PUT:
                    $response = $this->client->put($this->api_uri . $url, $options);
                    break;
                case self::METHOD_PATCH:
                    $response = $this->client->patch($this->api_uri . $url, $options);
                    break;
                case self::METHOD_POST:
                    $response = $this->client->post($this->api_uri . $url, $options);
                    break;
                case self::METHOD_OPTIONS:
                    $response = $this->client->options($this->api_uri . $url, $options);
                    break;
                default:
                    $this->errors[] = 'Bad REST Method';
                    dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
                    return -1;
            }

		    return json_decode($response->getBody()->getContents(), true);
	    } catch (RequestException $e) {
		    $request = $e->getRequest();
		    $response = $e->getResponse();

		    $errors_details = array();
		    if (isset($request)) $errors_details[] = $this->_requestToString($request);
		    if (isset($response)) $errors_details[] = $this->_responseToString($response);
		    else $errors_details[] = '<pre>' . dol_nl2br((string)$e) . '</pre>';

		    if (!empty($conf->global->BANKING4DOLIBARR_DEBUG)) {
			    $this->errors = array_merge($this->errors, $errors_details);
		    } else {
                if (isset($response)) {
                    $boby = $response->getBody();
                    $this->errors[] = '<b>' . $langs->trans('Banking4DolibarrResponseCode') . ': </b>' . $response->getStatusCode() . '<br>' .
                        '<b>' . $langs->trans('Banking4DolibarrResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() .
                        (!empty($boby) ? '<br>' . $boby : '');
                } else $this->errors[] = $e->getMessage();
            }

		    dol_syslog(__METHOD__ . " Error: " . dol_htmlentitiesbr_decode(implode(', ', $errors_details)), LOG_ERR);
		    return -1;
	    } catch (Exception $e) {
		    if (!empty($conf->global->BANKING4DOLIBARR_DEBUG)) {
			    $this->errors[] = (string)$e;
		    } else {
			    $this->errors[] = $e->getMessage();
		    }

		    dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
		    return -1;
	    }
    }

	/**
	 *  Add action
	 *
	 * @param   string      $type_code      Type code of the action
	 * @param   int         $socid          Company ID
	 * @param   int         $fk_element     Element linked ID
	 * @param   string      $elementtype    Element linked Type
	 * @param   string      $title          Title of the action
	 * @param   string      $msg            Message of the action
	 * @param   User        $user           User that modifies
	 * @return  int                         <0 if KO, >0 if OK
	 */
	protected function _addAction($type_code, $socid, $fk_element, $elementtype, $title, $msg, $user)
    {
	    dol_syslog(__METHOD__ . " type_code=" . $type_code . " socid=" . $socid . " fk_element=" . $fk_element . " title=" . $title . " msg=" . $msg . " user_id=" . $user->id, LOG_DEBUG);
	    $now = dol_now();
	    // Insertion action
	    require_once(DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
	    $actioncomm = new ActionComm($this->db);
	    $actioncomm->type_code = $type_code;
	    $actioncomm->label = $title;
	    $actioncomm->note = $msg;
	    $actioncomm->datep = $now;
	    $actioncomm->datef = $now;
	    $actioncomm->durationp = 0;
	    $actioncomm->punctual = 1;
	    $actioncomm->percentage = -1; // Not applicable
	    $actioncomm->contactid = 0;
	    $actioncomm->socid = $socid;
	    $actioncomm->author = $user; // User saving action
	    // $actioncomm->usertodo = $user; // User affected to action
	    $actioncomm->userdone = $user; // User doing action
	    $actioncomm->fk_element = $fk_element;
	    $actioncomm->elementtype = $elementtype;
	    $actioncomm->userownerid = $user->id;

	    $result = $actioncomm->create($user); // User qui saisit l'action
	    if ($result < 0) {
		    $this->error = $actioncomm->error;
		    $this->errors = $actioncomm->errors;
		    dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
	    }

	    return $result;
    }

	/**
	 *  Format the request to a string
	 *
	 * @param   RequestInterface    $request    Request handler
	 * @return	string		                    Formatted string of the request
	 */
    protected function _requestToString(RequestInterface $request)
    {
	    global $langs;

	    $out = '';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrRequestData') . ': </b><br><hr>';
	    $out .= '<div style="max-width: 1024px;">';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrRequestProtocolVersion') . ': </b>' . $request->getProtocolVersion() . '<br>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrRequestUri') . ': </b>' . $request->getUri() . '<br>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrRequestTarget') . ': </b>' . $request->getRequestTarget() . '<br>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrRequestMethod') . ': </b>' . $request->getMethod() . '<br>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrRequestHeaders') . ':</b><ul>';
	    foreach ($request->getHeaders() as $name => $values) {
		    $out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
	    }
	    $out .= '</ul>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrRequestBody') . ': </b>';
	    $out .= '<br><em>' . $request->getBody() . '</em><br>';
	    $out .= '</div>';
	    return $out;
    }

	/**
	 *  Format the response to a string
	 *
	 * @param   ResponseInterface   $response   Response handler
	 * @return	string		                    Formatted string of the response
	 */
    protected function _responseToString(ResponseInterface $response)
    {
	    global $langs;

	    $out = '';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrResponseData') . ': </b><br><hr>';
	    $out .= '<div style="max-width: 1024px;">';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrResponseProtocolVersion') . ': </b>' . $response->getProtocolVersion() . '<br>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrResponseCode') . ': </b>' . $response->getStatusCode() . '<br>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() . '<br>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrResponseHeaders') . ':</b><ul>';
	    foreach ($response->getHeaders() as $name => $values) {
		    $out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
	    }
	    $out .= '</ul>';
	    $out .= '<b>' . $langs->trans('Banking4DolibarrResponseBody') . ': </b>';
	    $body = json_decode($response->getBody(), true);
	    if (is_array($body)) {
		    $out .= '<ul>';
		    foreach ($body as $name => $values) {
			    $out .= '<li><b>' . $name . ': </b>' . (is_array($values) || is_object($values) ? json_encode($values) : $values) . '</li>';
		    }
		    $out .= '</ul>';
	    } else {
		    $out .= '<br><em>' . $response->getBody() . '</em><br>';
	    }
	    $out .= '</div>';
	    return $out;
    }

	/**
	 * Method to output saved errors
	 *
	 * @param   string      $separator      Separator between each error
	 * @return	string		                String with errors
	 */
	public function errorsToString($separator = ', ')
	{
		return $this->error . (is_array($this->errors) ? (!empty($this->error) ? $separator : '') . join($separator, $this->errors) : '');
	}
}


/**
 *	Class to manage downloaded bank record transaction
 */
class BudgetInsightBankRecord extends CommonObject
{
    public $element = 'banking4dolibarr_budgetinsightbankrecord';
    public $table_element = 'banking4dolibarr_bank_record';
    public $picto = 'generic';

    /**
     * @var int     The object identifier
     */
    public $id;
    /**
     * @var string  The object reference
     */
    public $ref;
    /**
     * Date created of the request
     * @var int
     */
    public $date_creation;
    /**
     * Date modified of the request
     * @var int
     */
    public $date_modification;
    /**
     * Id of the user who created the request
     * @var int
     */
    public $user_creation_id;
    /**
     * Id of the user who modified the request
     * @var int
     */
    public $user_modification_id;
    /**
     * @var int     Status of the transaction
     */
    public $status;

    /**
     * @var int     Remote ID (ID of the transaction - id)
     */
    public $id_record;
    /**
     * @var int     Remote bank account ID (ID of the related account - id_account)
     */
    public $id_account;
    /**
     * @var string  Label (Full label of the transaction - original_wording)
     */
    public $label;
    /**
     * @var string  Comment (User comment - comment)
     */
    public $comment;
	/**
	 * @var string  Note
	 */
	public $note;
    /**
     * @var int     Remote category ID (ID of the related category - id_category)
     */
    public $id_category;
    /**
     * @var int     Date of the debit (Debit date - date)
     */
    public $record_date;
    /**
     * @var int     Date realization of the transaction (Realization of the transaction - rdate)
     */
    public $rdate;
    /**
     * @var int     Date bank of the transaction (Date used by the bank for the transaction - bdate)
     */
    public $bdate;
    /**
     * @var int     Date value of the transaction (Value date of the transaction - vdate)
     */
    public $vdate;
    /**
     * @var int     Date scraped of the transaction (Date when the transaction has been seen - date_scraped)
     */
    public $date_scraped;
    /**
     * @var string  Type of the transaction (Type of transaction - type/nature)
     */
    public $record_type;
    /**
     * @var string  Original country of the transaction (Original country - country)
     */
    public $original_country;
    /**
     * @var double  Original amount of the transaction (Value in the original currency - original_value)
     */
    public $original_amount;
    /**
     * @var string  Original currency (Original currency - original_currency)
     */
    public $original_currency;
    /**
     * @var double  Commission of the transaction (Commission taken on the transaction - commission)
     */
    public $commission;
    /**
     * @var string  Commission currency of the transaction (Commission currency - commission_currency)
     */
    public $commission_currency;
    /**
     * @var double  Amount of the transaction (Value of the transaction - value)
     */
    public $amount;
    /**
     * @var double  Remaining amount to link of the transaction (Value of the transaction - value)
     */
    public $remaining_amount_to_link;
    /**
     * @var bool    Transaction hasn't been yet debited (If true, this transaction hasn't been yet debited - coming)
     */
    public $coming;
    /**
     * @var int     Date delete of the transaction (If set, this transaction has been removed from the bank - deleted)
     */
    public $deleted_date;
    /**
     * @var int     Date last update of the transaction (Last update of the transaction - last_update)
     */
    public $last_update_date;
    /**
     * @var int     Date of the reconciliation of the transaction
     */
    public $reconcile_date;
    /**
     * @var array   Save of the data downloaded
     */
    public $datas;

    /**
     * @var DictionaryLine[]    Cache of bank record categories list
     */
    static public $bank_record_categories_cached;
    /**
     * @var DictionaryLine[]    Cache of bank record types list
     */
    static public $bank_record_types_cached;
    /**
     * @var array    Cache of bank record types index list (code => rowid)
     */
    static public $bank_record_types_code_index_cached;
    /**
     * @var array    Cache of bank record types payment_mode_id list (code => payment_mode_id)
     */
    static public $bank_record_types_code_payment_mode_id_cached;

    /**
     * @var array   List of long language codes for bank record status
     */
    public $labelStatus;
    /**
     * @var array   List of short language codes for bank record status
     */
    public $labelStatusShort;

    const BANK_RECORD_STATUS_NOT_RECONCILED = 0;
    const BANK_RECORD_STATUS_RECONCILED = 1;
	const BANK_RECORD_STATUS_DISCARDED = 2;
	const BANK_RECORD_STATUS_DUPLICATE = 3;

    /**
     *  Constructor
     *
     * @param	DoliDB	$db		Database handler
     */
    function __construct(DoliDB $db)
    {
        $this->db = $db;

        // List of long language codes for bank record status
        $this->labelStatus = array(
            self::BANK_RECORD_STATUS_NOT_RECONCILED => 'Banking4DolibarrBankRecordStatusNotReconciled',
            self::BANK_RECORD_STATUS_RECONCILED => 'Banking4DolibarrBankRecordStatusReconciled',
            self::BANK_RECORD_STATUS_DISCARDED => 'Banking4DolibarrBankRecordStatusDiscarded',
			self::BANK_RECORD_STATUS_DUPLICATE => 'Banking4DolibarrBankRecordStatusDuplicate',
        );

        // List of short language codes for bank record status
        $this->labelStatusShort = array(
            self::BANK_RECORD_STATUS_NOT_RECONCILED => 'Banking4DolibarrBankRecordStatusShortNotReconciled',
            self::BANK_RECORD_STATUS_RECONCILED => 'Banking4DolibarrBankRecordStatusShortReconciled',
            self::BANK_RECORD_STATUS_DISCARDED => 'Banking4DolibarrBankRecordStatusShortDiscarded',
			self::BANK_RECORD_STATUS_DUPLICATE => 'Banking4DolibarrBankRecordStatusShortDuplicate',
        );
    }

    /**
     *  Load into memory content of a bank transaction line
     *
     * @param   int     $rowid              Id of bank transaction to load
     * @param   string  $ref                Ref of bank transaction to load
     * @param   int     $id_record          Id of the downloaded bank record
     * @param   int     $id_account         Id of the downloaded account
     * @param   int     $datas              Load also the datas downloaded
     * @param   int     $remaining_amount   Load also the remaing amount to link
     * @return  int                         <0 if KO, 0 if OK but not found, >0 if OK and found
     */
    function fetch($rowid=0, $ref='', $id_record=0, $id_account=0, $datas=0, $remaining_amount=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " rowid=$rowid, ref=$ref, id_record=$id_record, id_account=$id_account, datas=$datas, remaining_amount=$remaining_amount", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Check parameters
        if (!($rowid > 0 || !empty($ref) || ($id_record > 0 && $id_account > 0))) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $sql = "SELECT b4dbr.rowid, b4dbr.id_record, b4dbr.id_account, b4dbr.label, b4dbr.comment, b4dbr.note, b4dbr.id_category, b4dbr.record_date," .
            " b4dbr.rdate, b4dbr.bdate, b4dbr.vdate, b4dbr.date_scraped, b4dbr.record_type, b4dbr.original_country," .
            " b4dbr.original_amount, b4dbr.original_currency, b4dbr.commission, b4dbr.commission_currency, b4dbr.amount," .
            " b4dbr.coming, b4dbr.deleted_date, b4dbr.last_update_date, b4dbr.reconcile_date, b4dbr.status, b4dbr.datec, b4dbr.tms," .
            " b4dbr.fk_user_author, b4dbr.fk_user_modif, b4dbr.datas" .
            " FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record as b4dbr" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account AS cb4dba ON cb4dba.rowid = b4dbr.id_account" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "bank_account AS ba ON ba.rowid = cb4dba.fk_bank_account" .
            " WHERE ba.entity IN (" . getEntity('bank_account') . ")";
        if ($id_record > 0 && $id_account > 0) {
            $sql .= " AND b4dbr.id_record=" . $id_record . " AND b4dbr.id_account=" . $id_account;
        } elseif (!empty($ref)) $sql .= " AND b4dbr.rowid='" . $this->db->escape($ref) . "'";
        else $sql .= " AND b4dbr.rowid=" . $rowid;

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        if ($obj = $this->db->fetch_object($resql)) {
            $this->id = $obj->rowid;
            $this->ref = $obj->rowid;
            $this->id_record = $obj->id_record;
            $this->id_account = $obj->id_account;
            $this->label = $obj->label;
            $this->comment = $obj->comment;
			$this->note = $obj->note;
            $this->id_category = $obj->id_category;
            $this->record_date = $this->db->jdate($obj->record_date);
            $this->rdate = $this->db->jdate($obj->rdate);
            $this->bdate = $this->db->jdate($obj->bdate);
            $this->vdate = $this->db->jdate($obj->vdate);
            $this->date_scraped = $this->db->jdate($obj->date_scraped);
            $this->record_type = $obj->record_type;
            $this->original_country = $obj->original_country;
            $this->original_amount = $obj->original_amount;
            $this->original_currency = $obj->original_currency;
            $this->commission = $obj->commission;
            $this->commission_currency = $obj->commission_currency;
            $this->amount = $obj->amount;
            $this->remaining_amount_to_link = 0;
            $this->coming = $obj->coming ? 1 : 0;
            $this->deleted_date = $this->db->jdate($obj->deleted_date);
            $this->last_update_date = $this->db->jdate($obj->last_update_date);
            $this->reconcile_date = $this->db->jdate($obj->reconcile_date);
            $this->status = $obj->status;
            $this->date_creation = $this->db->jdate($obj->datec);
            $this->date_modification = $this->db->jdate($obj->tms);
            $this->user_creation_id = $obj->fk_user_author;
            $this->user_modification_id = $obj->fk_user_modif;
            if ($datas) $this->datas = json_decode($obj->datas);

            if ($remaining_amount) {
                $result = $this->fetchRemaingAmountToLink();
                if ($result < 0) {
                    return -1;
                }
            }

            return 1;
        }

        return 0;
    }

    /**
     *  Load remaing amount to link of a bank transaction line
     *
     * @return  int             <0 if KO, >0 if OK
     */
    function fetchRemaingAmountToLink()
    {
        global $langs;
        dol_syslog(__METHOD__ . " rowid={$this->id}", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Check parameters
        if (!($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $sql = "SELECT br.amount - SUM(" . $this->db->ifsql("b.amount IS NULL", "0", "b.amount") . ") AS remaining_amount_to_link" .
            " FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record as br" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account AS cb4dba ON cb4dba.rowid = br.id_account" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "bank_account AS ba ON ba.rowid = cb4dba.fk_bank_account" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link as brl ON brl.fk_bank_record = br.rowid" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "bank as b ON b.rowid = brl.fk_bank".
            " WHERE ba.entity IN (" . getEntity('bank_account') . ")" .
			" AND br.rowid = " . $this->id .
			" AND br.status = " . self::BANK_RECORD_STATUS_NOT_RECONCILED .
            " GROUP BY br.rowid, br.amount";

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $this->remaining_amount_to_link = 0;
        if ($obj = $this->db->fetch_object($resql)) {
            $this->remaining_amount_to_link = $obj->remaining_amount_to_link;

            return 1;
        }

        return 0;
    }

    /**
     *  Inserts a transaction to a bank account
     *
     * @param   User	$user		Object user making creation
     * @param   int     $notrigger  1=Disable triggers
     * @return  int                 <0 if KO, =0 if duplicated, rowid of the line if OK
     */
    public function insert(User $user, $notrigger=0)
    {
        dol_syslog(__METHOD__ . " user_id={$user->id}, notrigger=$notrigger", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Check parameters
        $this->date_creation = $this->date_creation > 0 ? $this->date_creation : dol_now();
        $this->date_modification = $this->date_modification > 0 ? $this->date_modification : dol_now();
        $this->datas = is_array($this->datas) || is_object($this->datas) ? json_encode($this->datas) : $this->datas;

        $error = 0;
        $duplicate = 0;
        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . "(" .
            "id_record, id_account, label, comment, id_category, record_date," .
            " rdate, bdate, vdate, date_scraped, record_type, original_country," .
            " original_amount, original_currency, commission, commission_currency, amount," .
            " coming, deleted_date, last_update_date, status, datec," .
            " fk_user_author, datas" .
            ") VALUES (" .
            $this->id_record .
            ", " . $this->id_account .
            ", '" . $this->db->escape($this->label) . "'" .
            ", " . (!empty($this->comment) ? "'" . $this->db->escape($this->comment) . "'" : "NULL") .
            ", " . ($this->id_category > 0 ? $this->id_category : "NULL") .
            ", " . ($this->record_date > 0 ? "'" . $this->db->idate($this->record_date) . "'" : "NULL") .
            ", " . ($this->rdate > 0 ? "'" . $this->db->idate($this->rdate) . "'" : "NULL") .
            ", " . ($this->bdate > 0 ? "'" . $this->db->idate($this->bdate) . "'" : "NULL") .
            ", " . ($this->vdate > 0 ? "'" . $this->db->idate($this->vdate) . "'" : "NULL") .
            ", " . ($this->date_scraped > 0 ? "'" . $this->db->idate($this->date_scraped) . "'" : "NULL") .
            ", " . (!empty($this->record_type) ? "'" . $this->db->escape($this->record_type) . "'" : "NULL") .
            ", " . (!empty($this->original_country) ? "'" . $this->db->escape($this->original_country) . "'" : "NULL") .
            ", " . (!empty($this->original_amount) ? $this->original_amount : "NULL") .
            ", " . (!empty($this->original_currency) ? "'" . $this->db->escape($this->original_currency) . "'" : "NULL") .
            ", " . (!empty($this->commission) ? $this->commission : "NULL") .
            ", " . (!empty($this->commission_currency) ? "'" . $this->db->escape($this->commission_currency) . "'" : "NULL") .
            ", " . (!empty($this->amount) ? $this->amount : "0") .
            ", " . (!empty($this->coming) ? 1 : "NULL") .
            ", " . ($this->deleted_date > 0 ? "'" . $this->db->idate($this->deleted_date) . "'" : "NULL") .
            ", '" . $this->db->idate($this->last_update_date) . "'" .
            ", " . self::BANK_RECORD_STATUS_NOT_RECONCILED .
            ", '" . $this->db->idate($this->date_creation) . "'" .
            ", '" . $user->id . "'" .
            ", '" . $this->db->escape($this->datas) . "'" .
            ")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                $duplicate++;
                $this->id = 0;
            } else {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $error++;
            }
        }

        if (!$error && !$duplicate) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
            $this->ref = $this->id;

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('BANKING4DOLIBARR_BANK_RECORD_CREATE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        if (!$error) {
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1 * $error;
        }
    }

    /**
     *  Update bank account record in database
     *
     * @param	User	$user			Object user making update
     * @param 	int		$notrigger		0=Disable all triggers
     * @return	int						<0 if KO, >0 if OK, =0 if not updated
     */
    function update(User $user, $notrigger = 0)
    {
        dol_syslog(__METHOD__ . " user_id={$user->id}, notrigger=$notrigger", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Check parameters
        $this->date_creation = $this->date_creation > 0 ? $this->date_creation : dol_now();
        $this->date_modification = $this->date_modification > 0 ? $this->date_modification : dol_now();
        $this->datas = is_array($this->datas) || is_object($this->datas) ? json_encode($this->datas) : $this->datas;

        $error = 0;
        $line_updated = 0;
        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET" .
            " label = '" . $this->db->escape($this->label) . "'" .
            ", comment = " . (!empty($this->comment) ? "'" . $this->db->escape($this->comment) . "'" : "NULL") .
			", note = " . (!empty($this->note) ? "'" . $this->db->escape($this->note) . "'" : "NULL") .
            ", id_category = " . ($this->id_category > 0 ? $this->id_category : "NULL") .
            ", record_date = " . ($this->record_date > 0 ? "'" . $this->db->idate($this->record_date) . "'" : "NULL") .
            ", rdate = " . ($this->rdate > 0 ? "'" . $this->db->idate($this->rdate) . "'" : "NULL") .
            ", bdate = " . ($this->bdate > 0 ? "'" . $this->db->idate($this->bdate) . "'" : "NULL") .
            ", vdate = " . ($this->vdate > 0 ? "'" . $this->db->idate($this->vdate) . "'" : "NULL") .
            ", date_scraped = " . ($this->date_scraped > 0 ? "'" . $this->db->idate($this->date_scraped) . "'" : "NULL") .
            ", record_type = " . (!empty($this->record_type) ? "'" . $this->db->escape($this->record_type) . "'" : "NULL") .
            ", original_country = " . (!empty($this->original_country) ? "'" . $this->db->escape($this->original_country) . "'" : "NULL") .
            ", original_amount = " . (!empty($this->original_amount) ? $this->original_amount : "NULL") .
            ", original_currency = " . (!empty($this->original_currency) ? "'" . $this->db->escape($this->original_currency) . "'" : "NULL") .
            ", commission = " . (!empty($this->commission) ? $this->commission : "NULL") .
            ", commission_currency = " . (!empty($this->commission_currency) ? "'" . $this->db->escape($this->commission_currency) . "'" : "NULL") .
            ", amount = " . (!empty($this->amount) ? $this->amount : "0") .
            ", coming = " . (!empty($this->coming) ? 1 : "NULL") .
            ", deleted_date = " . ($this->deleted_date > 0 ? "'" . $this->db->idate($this->deleted_date) . "'" : "NULL") .
            ", last_update_date = '" . $this->db->idate($this->last_update_date) . "'" .
            ", tms = '" . $this->db->idate($this->date_modification) . "'" .
            ", fk_user_modif = " . $user->id .
            ", datas = '" . $this->db->escape($this->datas) . "'";
        $sql .= " WHERE status = " . self::BANK_RECORD_STATUS_NOT_RECONCILED;
        if ($this->id > 0) {
            $sql .= " AND rowid = " . $this->id;
        } else {
            $sql .= " AND id_record = " . $this->id_record .
                " AND id_account = " . $this->id_account;
        }

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $error++;
        }

        if (!$error) {
            $line_updated = $this->db->affected_rows($resql);
        }

        if (!$error && $line_updated && !$notrigger) {
            // Call trigger
            $result = $this->call_trigger('BANKING4DOLIBARR_BANK_RECORD_UPDATE', $user);
            if ($result < 0) $error++;
            // End call triggers
        }

        if (!$error) {
            $this->db->commit();
            return $line_updated;
        } else {
            $this->db->rollback();
            return -1 * $error;
        }
    }

	/**
	 *  Delete bank account record in database
	 *
	 * @param   User    $user           User that deletes
	 * @param   bool    $notrigger      false=launch triggers after, true=disable triggers
	 * @return  int                     <0 if KO, >0 if OK, =0 if do nothing
	 */
	public function delete(User $user, $notrigger = false)
	{
		global $langs;
		$error = 0;
		$this->errors = array();

		dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id, LOG_DEBUG);

		if ($this->status != self::BANK_RECORD_STATUS_NOT_RECONCILED) {
			return 0;
		}

		$this->db->begin();

		// User is mandatory for trigger call
		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('BANKING4DOLIBARR_BANK_RECORD_DELETE', $user);
			if ($result < 0) {
				$error++;
				dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
			}
			// End call triggers
		}

		// Remove bank account record
		if (!$error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid = ' . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			}
		}

		if (!$error) {
			$this->db->commit();
			dol_syslog(__METHOD__ . " success", LOG_DEBUG);

			return 1;
		} else {
			$this->db->rollback();

			return -1;
		}
	}

    /**
     *  Get bank lines reconciled with this bank record
     *
     * @param   int             $mode       =0 if basic infos, =1 if preformatted for the ajax list, =2 if object
     * @return  int|array                   <0 if KO, else array of result
     */
    function getReconciledLines($mode=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " id={$this->id}, mode=$mode", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Check parameters
        if (!($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.num_chq, b.fk_type," .
            " ba.rowid as bankid, ba.ref as bankref," .
            " bu.url_id," .
            " brl.rowid AS line_id," .
            " s.nom, s.name_alias, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur," .
            " " . $this->db->ifsql("br.record_date != b.dateo", "1", "0") . " AS wrong_odate," .
			" " . $this->db->ifsql($this->db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate") . " != b.datev", "1", "0") . " AS wrong_vdate," .
			" " . $this->db->ifsql($this->db->ifsql("cb4dbrt.mode_reglement = ''", "b.fk_type", "cb4dbrt.mode_reglement") . " != b.fk_type", "1", "0") . " AS wrong_payment_type" .
            " FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link as brl" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record as br ON br.rowid = brl.fk_bank_record" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_record_type AS cb4dbrt ON cb4dbrt.code = br.record_type" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "bank AS b ON b.rowid = brl.fk_bank" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON ba.rowid = b.fk_account" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu ON bu.fk_bank = b.rowid AND type = 'company'" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON bu.url_id = s.rowid" .
            " WHERE brl.fk_bank_record = " . $this->id;

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
        require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
        require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
        require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
        if (version_compare(DOL_VERSION, "11.0.0") >= 0) {
            require_once DOL_DOCUMENT_ROOT . '/salaries/class/paymentsalary.class.php';
        } else {
            require_once DOL_DOCUMENT_ROOT . '/compta/salaries/class/paymentsalary.class.php';
        }
        require_once DOL_DOCUMENT_ROOT . '/expensereport/class/paymentexpensereport.class.php';

        $langs->loadLangs(array("banks", "bills", "categories", "companies", "margins", "salaries", "loan", "donations", "trips", "members", "compta", "accountancy"));

        $companystatic = new Societe($this->db);
        $bankaccountstatic = new Account($this->db);
        $paymentstatic = new Paiement($this->db);
        $paymentsupplierstatic = new PaiementFourn($this->db);
        $paymentvatstatic = new TVA($this->db);
        $paymentsalstatic = new PaymentSalary($this->db);
        $paymentexpensereportstatic = new PaymentExpenseReport($this->db);
        $bankstatic = new Account($this->db);
        $banklinestatic = new AccountLine($this->db);

        $warning_wrong_infos = img_warning($langs->trans('Banking4DolibarrWarningWrongInfosWithBankLine'));

        $lines = array();
        while ($obj = $this->db->fetch_object($resql)) {
            if ($mode == 1) {
                if (version_compare(DOL_VERSION, "11.0.0") >= 0) {
                    $linkToBankLine = '/compta/bank/line.php';
                } else {
                    $linkToBankLine = '/compta/bank/ligne.php';
                }

                $ref = '<a href="' . DOL_URL_ROOT . $linkToBankLine . '?rowid=' . $obj->rowid . '&save_lastsearch_values=1">' . img_object($langs->trans("ShowPayment") . ': ' . $obj->rowid, 'account', 'class="classfortooltip"') . ' ' . $obj->rowid . '</a>';

                preg_match('/\((.+)\)/i', $obj->label, $matches);    // Si texte entoure de parenthee on tente recherche de traduction
                if ($matches[1] && $langs->trans($matches[1]) != $matches[1]) $description = $langs->trans($matches[1]);
                else $description = dol_trunc($obj->label, 40);
                // Add links after description
                $links = $bankaccountstatic->get_url($obj->rowid);
                foreach ($links as $key => $val) {
                    if ($links[$key]['type'] == 'payment') {
                        $paymentstatic->id = $links[$key]['url_id'];
                        $paymentstatic->ref = $links[$key]['url_id'];
                        $description .= ' ' . $paymentstatic->getNomUrl(2);
                    } elseif ($links[$key]['type'] == 'payment_supplier') {
                        $paymentsupplierstatic->id = $links[$key]['url_id'];
                        $paymentsupplierstatic->ref = $links[$key]['url_id'];
                        $description .= ' ' . $paymentsupplierstatic->getNomUrl(2);
                    } elseif ($links[$key]['type'] == 'payment_sc') {
                        $description .= '<a href="' . DOL_URL_ROOT . '/compta/payment_sc/card.php?id=' . $links[$key]['url_id'] . '">';
                        $description .= ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
                        $description .= '</a>';
                    } elseif ($links[$key]['type'] == 'payment_vat') {
                        $paymentvatstatic->id = $links[$key]['url_id'];
                        $paymentvatstatic->ref = $links[$key]['url_id'];
                        $description .= ' ' . $paymentvatstatic->getNomUrl(2);
                    } elseif ($links[$key]['type'] == 'payment_salary') {
                        $paymentsalstatic->id = $links[$key]['url_id'];
                        $paymentsalstatic->ref = $links[$key]['url_id'];
                        $description .= ' ' . $paymentsalstatic->getNomUrl(2);
                    } elseif ($links[$key]['type'] == 'payment_loan') {
                        $description .= '<a href="' . DOL_URL_ROOT . '/loan/payment/card.php?id=' . $links[$key]['url_id'] . '">';
                        $description .= ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
                        $description .= '</a>';
                    } elseif ($links[$key]['type'] == 'payment_donation') {
                        $description .= '<a href="' . DOL_URL_ROOT . '/don/payment/card.php?id=' . $links[$key]['url_id'] . '">';
                        $description .= ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
                        $description .= '</a>';
                    } elseif ($links[$key]['type'] == 'payment_expensereport') {
                        $paymentexpensereportstatic->id = $links[$key]['url_id'];
                        $paymentexpensereportstatic->ref = $links[$key]['url_id'];
                        $description .= ' ' . $paymentexpensereportstatic->getNomUrl(2);
                    } elseif ($links[$key]['type'] == 'banktransfert') {
                        // Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
                        if ($obj->amount > 0) {
                            $banklinestatic->fetch($links[$key]['url_id']);
                            $bankstatic->id = $banklinestatic->fk_account;
                            $bankstatic->label = $banklinestatic->bank_account_ref;
                            $description .= ' (' . $langs->trans("TransferFrom") . ' ';
                            $description .= $bankstatic->getNomUrl(1, 'transactions');
                            $description .= ' ' . $langs->trans("toward") . ' ';
                            $bankstatic->id = $obj->bankid;
                            $bankstatic->label = $obj->bankref;
                            $description .= $bankstatic->getNomUrl(1, '');
                            $description .= ')';
                        } else {
                            $bankstatic->id = $obj->bankid;
                            $bankstatic->label = $obj->bankref;
                            $description .= ' (' . $langs->trans("TransferFrom") . ' ';
                            $description .= $bankstatic->getNomUrl(1, '');
                            $description .= ' ' . $langs->trans("toward") . ' ';
                            $banklinestatic->fetch($links[$key]['url_id']);
                            $bankstatic->id = $banklinestatic->fk_account;
                            $bankstatic->label = $banklinestatic->bank_account_ref;
                            $description .= $bankstatic->getNomUrl(1, 'transactions');
                            $description .= ')';
                        }
                    } elseif ($links[$key]['type'] == 'company') {
                    } elseif ($links[$key]['type'] == 'user') {
                    } elseif ($links[$key]['type'] == 'member') {
                    } elseif ($links[$key]['type'] == 'sc') {
                    } else {
                        // Show link with label $links[$key]['label']
                        if (!empty($obj->label) && !empty($links[$key]['label'])) $description .= ' - ';
                        $description .= '<a href="' . $links[$key]['url'] . $links[$key]['url_id'] . '">';
                        if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
                            // Label generique car entre parentheses. On l'affiche en le traduisant
                            if ($reg[1] == 'paiement') $reg[1] = 'Payment';
                            $description .= ' ' . $langs->trans($reg[1]);
                        } else {
                            $description .= ' ' . $links[$key]['label'];
                        }
                        $description .= '</a>';
                    }
                }

                $dateo = dol_print_date($this->db->jdate($obj->do), "day") . (!empty($obj->wrong_odate) ? ' <span id="wrong_odate_line_'.$this->id.'">' . $warning_wrong_infos . '</span>' : '');
                $datev = dol_print_date($this->db->jdate($obj->dv), "day") . (!empty($obj->wrong_vdate) ? ' <span id="wrong_vdate_line_'.$this->id.'">' . $warning_wrong_infos . '</span>' : '');

                $payment_type = ($langs->trans("PaymentTypeShort" . $obj->fk_type) != "PaymentTypeShort" . $obj->fk_type) ? $langs->trans("PaymentTypeShort" . $obj->fk_type) : $langs->getLabelFromKey($this->db, $obj->fk_type, 'c_paiement', 'code', 'libelle', '', 1);
                if ($payment_type == 'SOLD') $payment_type = '';
                $payment_type .= (!empty($obj->wrong_payment_type) ? ' <span id="wrong_payment_type_line_'.$this->id.'">' . $warning_wrong_infos . '</span>' : '');

                $num_chq = $obj->num_chq ? $obj->num_chq : '';

                $thirdparty = '';
                if ($obj->url_id) {
                    $companystatic->id = $obj->url_id;
                    $companystatic->name = $obj->nom;
                    $companystatic->name_alias = $obj->name_alias;
                    $companystatic->client = $obj->client;
                    $companystatic->fournisseur = $obj->fournisseur;
                    $companystatic->code_client = $obj->code_client;
                    $companystatic->code_fournisseur = $obj->code_fournisseur;
                    $companystatic->code_compta = $obj->code_compta;
                    $companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                    $thirdparty = $companystatic->getNomUrl(1);
                }

                $debit = $obj->amount < 0 ? price($obj->amount * -1) : '';
                $credit = $obj->amount > 0 ? price($obj->amount) : '';

                $lines[$obj->rowid] = array(
                    'link_line_id' => $obj->line_id,
                    'ref' => $ref,
                    'description' => $description,
                    'dateo' => $dateo,
                    'datev' => $datev,
                    'payment_type' => $payment_type,
                    'num_chq' => $num_chq,
                    'thirdparty' => $thirdparty,
                    'debit' => $debit,
                    'credit' => $credit,
                );
            } elseif ($mode == 2) {
                $bankline = new AccountLine($this->db);
                $bankline->fetch($obj->rowid);
                $bankline->link_line_id = $obj->line_id;
                $lines[$obj->rowid] = $bankline;
            } else {
                $lines[$obj->rowid] = array(
                    'link_line_id' => $obj->line_id,
                    'rowid' => $obj->rowid,
                    'dateo' => $this->db->jdate($obj->do),
                    'datev' => $this->db->jdate($obj->dv),
                    'amount' => $obj->amount,
                    'label' => $obj->label,
                    'num_chq' => $obj->num_chq,
                    'fk_type' => $obj->fk_type,
                    'wrong_odate' => $obj->wrong_odate ? 1 : 0,
                    'wrong_vdate' => $obj->wrong_vdate ? 1 : 0,
                    'wrong_payment_type' => $obj->wrong_payment_type ? 1 : 0,
                    'bankid' => $obj->bankid,
                    'bankref' => $obj->bankref,
                    'url_id' => $obj->url_id,
                    'soc_name' => $obj->nom,
                    'soc_name_alias' => $obj->name_alias,
                    'soc_client' => $obj->client,
                    'soc_fournisseur' => $obj->fournisseur,
                    'soc_code_client' => $obj->code_client,
                    'soc_code_fournisseur' => $obj->code_fournisseur,
                    'soc_code_compta' => $obj->code_compta,
                    'soc_code_compta_fournisseur' => $obj->code_compta_fournisseur,
                );
            }
        }

        return $lines;
    }

    /**
     *  Get bank lines linked to reconcile with this bank record
     *
     * @return  int|array           <0 if KO, else array of IDs
     */
    function getLinkedLinesToReconcile($mode=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " id={$this->id}, mode=$mode", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Check parameters
        if (!($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $sql = "SELECT DISTINCT brl.fk_bank" .
            " FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link as brl" .
            " WHERE brl.fk_bank_record = " . $this->id;

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $lines = array();
        while ($obj = $this->db->fetch_object($resql)) {
            $lines[$obj->fk_bank] = $obj->fk_bank;
        }

        return $lines;
    }

    /**
     *  Reconcile a bank record in Dolibarr with a downloaded
     *
     * @param   User    $user                       User who make the action
     * @param   string  $statement_number           Statement number of the bank reconciliation (date: YYYYMM ou YYYYMMDD)
     * @param   int     $fk_bank                    Bank record ID in Dolibarr
     * @param   int     $check_statement_number     Check the statement number
     * @param 	int		$nolink		                0=Disable the linking
     * @param 	int		$notrigger		            0=Disable all triggers
     * @return	int	                                <0 if KO, >0 if OK, =0 if bypassed
     */
    public function reconcile(User $user, $statement_number, $fk_bank, $check_statement_number=1, $nolink=0, $notrigger=0)
    {
        global $conf, $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, statement_number=$statement_number, fk_bank=$fk_bank, rowid={$this->id}" .
            ", status={$this->status}, id_category={$this->id_category}, check_statement_number=$check_statement_number, notrigger=$notrigger", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $fk_bank = $fk_bank > 0 ? $fk_bank : 0;
        $this->id = $this->id > 0 ? $this->id : 0;
        $statement_number = trim($statement_number);
        $this->id_category = $this->id_category > 0 ? $this->id_category : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        if ($this->status != self::BANK_RECORD_STATUS_NOT_RECONCILED)
            return 0;

        // Check parameters
        if (!($fk_bank > 0) || !($this->id > 0) || empty($statement_number)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Check statement number
        if ($check_statement_number) {
            if (!empty($conf->global->BANK_STATEMENT_REGEX_RULE) &&
                !preg_match('/' . preg_quote($conf->global->BANK_STATEMENT_REGEX_RULE, '/') . '/', $statement_number)
            ) {
                $this->errors[] = $langs->trans("ErrorBankStatementNameMustFollowRegex", $conf->global->BANK_STATEMENT_REGEX_RULE);
                dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        }

        // Get category ID in Dolibarr for the category ID of the downloaded bank record
        $bank_record_category_id = 0;
        if ($this->id_category > 0) {
            $bank_record_category_id = $this->getDolibarrRecordCategoryId($this->id_category);
            if ($bank_record_category_id < 0) {
                return -1;
            }
        }

        $now = dol_now();
        $error = 0;
        $this->db->begin();

		if (!$nolink) {
			// Link the two bank records
			$result = $this->link($user, $fk_bank);
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			$sql = "SELECT fk_bank FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link WHERE fk_bank_record = " . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
				$error++;
			} else {
				require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
				while ($obj = $this->db->fetch_object($resql)) {
					$account_line = new AccountLine($this->db);
					$account_line->num_releve = $statement_number;
					$account_line->id = $obj->fk_bank;

					// Reconcile bank record in Dolibarr
					$result = $account_line->update_conciliation($user, $bank_record_category_id);
					if ($result < 0) {
						$this->error = $account_line->error;
						$this->errors = array_merge($this->errors, $account_line->errors);
						$error++;
						break;
					}
				}
			}
		}

        if (!$error) {
            // Update status
            $sql = "UPDATE " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record SET" .
                " status = " . self::BANK_RECORD_STATUS_RECONCILED .
                ", reconcile_date = '" . $this->db->idate($now) . "'" .
                ", tms = '" . $this->db->idate($now) . "'" .
                ", fk_user_modif = " . $user->id .
                " WHERE rowid = " . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $error++;
            }
        }

        if (!$error && !$notrigger) {
            // Call trigger
            $this->context['bank_record_link'] = [
                'statement_number' => $statement_number,
                'fk_bank' => $fk_bank,
            ];
            $result = $this->call_trigger('BANKING4DOLIBARR_BANK_RECORD_LINK', $user);
            if ($result < 0) $error++;
            // End call triggers
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Un-reconcile a bank record in Dolibarr with a downloaded
     *
     * @param   User    $user               User who make the action
     * @param   int     $fk_bank            Bank record ID in Dolibarr
     * @param 	int		$notrigger		    0=Disable all triggers
     * @return	int	                        <0 if KO, >0 if OK
     */
    public function unreconcile(User $user, $fk_bank=0, $notrigger=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, rowid={$this->id}, status={$this->status}, fk_bank=$fk_bank, notrigger=$notrigger", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $fk_bank = $fk_bank > 0 ? $fk_bank : 0;
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        // Get the bank line of the bank record downloaded
        if (!($this->id > 0) && $fk_bank > 0) {
            $sql = "SELECT fk_bank_record FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link WHERE fk_bank = " . $fk_bank;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                return -1;
            }

            if ($obj = $this->db->fetch_object($resql)) {
                $this->fetch($obj->fk_bank_record);
            }
        }

        if ($this->status != self::BANK_RECORD_STATUS_RECONCILED)
            return 0;

        // Check parameters
        if (!($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $now = dol_now();
        $error = 0;
        $this->db->begin();

        if (!$notrigger) {
            $this->context['bank_record_unlink'] = [
                'fk_bank' => $fk_bank,
            ];
            // Call trigger
            $result = $this->call_trigger('BANKING4DOLIBARR_BANK_RECORD_UNLINK', $user);
            if ($result < 0) $error++;
            // End call triggers
        }

        if (!$error) {
            // Un-reconciled the bank line
            $sql = "UPDATE " . MAIN_DB_PREFIX . "bank AS b" .
                " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS b4dbrl ON b4dbrl.fk_bank = b.rowid" .
                " SET b.rappro = 0, b.num_releve = NULL, b.fk_user_rappro = NULL" .
                " WHERE b4dbrl.fk_bank_record = " . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $error++;
            }
        }

        if (!$error) {
            // Update un-reconciled status bank record downloaded
            $sql = "UPDATE " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record" .
                " SET status = " . self::BANK_RECORD_STATUS_NOT_RECONCILED .
                ", reconcile_date = NULL" .
                ", tms = '" . $this->db->idate($now) . "'" .
                ", fk_user_modif = " . $user->id .
                " WHERE rowid = " . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $error++;
            }
        }

        if (!$error) {
            // Delete all linked bank record downloaded of the bank line un-reconciled
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link WHERE fk_bank_record = " . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $error++;
            }
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Get the reconcile number of a bank record
     *
     * @return	int|string	        <0 if KO otherwise the reconsile number
     */
    public function get_statement_number()
    {
        global $langs;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        if ($this->status != self::BANK_RECORD_STATUS_RECONCILED)
            return '';

        // Check parameters
        if (!($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Get the reconcile number of the bank line
        $sql = "SELECT b.num_releve".
            " FROM " . MAIN_DB_PREFIX . "bank AS b" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS b4dbrl ON b4dbrl.fk_bank = b.rowid" .
            " WHERE b4dbrl.fk_bank_record = " . $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        if ($obj = $this->db->fetch_object($resql)) {
            return  $obj->num_releve;
        }

        return '';
    }

    /**
     *  Update the reconcile number of a bank record
     *
     * @param   User    $user               User who make the action
     * @param   string  $statement_number           Statement number of the bank reconciliation (date: YYYYMM ou YYYYMMDD)
     * @param   int     $check_statement_number     Check the statement number
     * @return	int	                        <0 if KO, >0 if OK
     */
    public function update_statement_number(User $user, $statement_number, $check_statement_number=1)
    {
        global $conf, $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, rowid={$this->id}, status={$this->status}, statement_number=$statement_number check_statement_number=$check_statement_number", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $statement_number = trim($statement_number);
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        if ($this->status != self::BANK_RECORD_STATUS_RECONCILED)
            return 0;

        // Check statement number
        if ($check_statement_number) {
            if (!empty($conf->global->BANK_STATEMENT_REGEX_RULE) &&
                !preg_match('/' . preg_quote($conf->global->BANK_STATEMENT_REGEX_RULE, '/') . '/', $statement_number)
            ) {
                $this->errors[] = $langs->trans("ErrorBankStatementNameMustFollowRegex", $conf->global->BANK_STATEMENT_REGEX_RULE);
                dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        }

        // Check parameters
        if (!($this->id > 0) || empty($statement_number)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Update the bank line
        $sql = "UPDATE " . MAIN_DB_PREFIX . "bank AS b" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS b4dbrl ON b4dbrl.fk_bank = b.rowid" .
            " SET b.num_releve = '" . $this->db->escape($statement_number). "', b.fk_user_rappro = " . $user->id .
            " WHERE b4dbrl.fk_bank_record = " . $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     *  Link a bank record in Dolibarr with a downloaded
     *
     * @param   User    $user                       User who make the action
     * @param   int     $fk_bank                    Bank record ID in Dolibarr
     * @return	int	                                <0 if KO, >0 if OK, =0 if bypassed
     */
    public function link(User $user, $fk_bank)
    {
        global $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, fk_bank=$fk_bank, rowid={$this->id}, status={$this->status}", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $fk_bank = $fk_bank > 0 ? $fk_bank : 0;
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        if ($this->status != self::BANK_RECORD_STATUS_NOT_RECONCILED)
            return 0;

        // Check parameters
        if (!($fk_bank > 0) || !($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $now = dol_now();

        // Link bank record in Dolibarr with the bank record downloaded
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link(fk_bank, fk_bank_record, tms, fk_user_to_link) VALUES (" .
            $fk_bank . ", " . $this->id . ", '" . $this->db->idate($now) . "', " . $user->id . ")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     *  Unlink a bank record in Dolibarr with a downloaded
     *
     * @param   User    $user               User who make the action
     * @param   int     $fk_bank            Bank record ID in Dolibarr
     * @return	int	                        <0 if KO, >0 if OK
     */
    public function unlink(User $user, $fk_bank=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, fk_bank=$fk_bank, rowid={$this->id}, status={$this->status}", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $fk_bank = $fk_bank > 0 ? $fk_bank : 0;
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        if ($this->status != self::BANK_RECORD_STATUS_NOT_RECONCILED)
            return 0;

        // Check parameters
        if (!($fk_bank > 0) || !($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Unlink bank record in Dolibarr with the bank record downloaded
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link WHERE fk_bank = " . $fk_bank . " AND fk_bank_record = " . $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     *  Discard a downloaded bank record
     *
     * @param   User    $user                       User who make the action
     * @param 	int		$notrigger		            0=Disable all triggers
     * @return	int	                                <0 if KO, >0 if OK
     */
    public function discard(User $user, $notrigger=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, rowid={$this->id}, status={$this->status}, notrigger=$notrigger", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        if ($this->status != self::BANK_RECORD_STATUS_NOT_RECONCILED)
            return 0;

        // Check parameters
        if (!($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $nb_bank_link = $this->nbBankLink();
        if ($nb_bank_link < 0) {
            return -1;
        } elseif ($nb_bank_link > 0)
            return 0;

        $now = dol_now();
        $error = 0;
        $this->db->begin();

        // Update status
        $sql = "UPDATE " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record SET" .
            " status = " . self::BANK_RECORD_STATUS_DISCARDED .
            ", tms = '" . $this->db->idate($now) . "'" .
            ", fk_user_modif = " . $user->id .
            " WHERE rowid = " . $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $error++;
        }

        if ($this->db->affected_rows($resql) == 1) {
            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('BANKING4DOLIBARR_BANK_RECORD_DISCARD', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Un-discard a downloaded bank record
     *
     * @param   User    $user               User who make the action
     * @param 	int		$notrigger		    0=Disable all triggers
     * @return	int	                        <0 if KO, >0 if OK
     */
    public function undiscard(User $user, $notrigger=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, rowid={$this->id}, status={$this->status}, notrigger=$notrigger", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        if ($this->status != self::BANK_RECORD_STATUS_DISCARDED)
            return 0;

        // Check parameters
        if (!($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $now = dol_now();
        $error = 0;
        $this->db->begin();

        // Update status
        $sql = "UPDATE " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record SET" .
            " status = " . self::BANK_RECORD_STATUS_NOT_RECONCILED .
            ", tms = '" . $this->db->idate($now) . "'" .
            ", fk_user_modif = " . $user->id .
            " WHERE rowid = " . $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $error++;
        }

        if ($this->db->affected_rows($resql) == 1) {
            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('BANKING4DOLIBARR_BANK_RECORD_UNDISCARD', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Fix bank line from downloaded bank record
     *
     * @param   User    $user               User who make the action
     * @param   int     $dates              Fix dates operation and value
     * @param   int     $payment_type       Fix payment type
     * @param 	int		$notrigger		    0=Disable all triggers
     * @return	int	                        <0 if KO, >0 if OK
     */
    public function fixBankLine(User $user, $dates=0, $payment_type=0, $notrigger=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " user_id={$user->id}, rowid={$this->id}, status={$this->status}, dates=$dates, payment_type=$payment_type, notrigger=$notrigger", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->status = $this->status > 0 ? $this->status : 0;

        if ($this->status != self::BANK_RECORD_STATUS_RECONCILED)
            return 0;

        // Check parameters
        if (!($this->id > 0) || (empty($dates) && empty($payment_type))) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $now = dol_now();
        $error = 0;
        $this->db->begin();

        if ($dates && $this->is_broken_down()) {
			$dates = 0;
		}

        // Update infos
        $sql = "UPDATE " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS b4dbr" .
            ($payment_type ? " LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_record_type AS cb4dbrt ON cb4dbrt.code = b4dbr.record_type" : "") .
            " LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS b4dbrl ON b4dbrl.fk_bank_record = b4dbr.rowid" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "bank AS b ON b.rowid = b4dbrl.fk_bank" .
            " SET b.tms = '" . $this->db->idate($now) . "'" .
            ($dates ? ", b.dateo = b4dbr.record_date, b.datev = " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") : '') .
            ($payment_type ? ", b.fk_type = " . $this->db->ifsql("cb4dbrt.mode_reglement = ''", "b.fk_type", "cb4dbrt.mode_reglement") : '') .
            " WHERE b4dbr.rowid = " . $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $error++;
        }

        if ($this->db->affected_rows($resql) == 1) {
            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('BANKING4DOLIBARR_BANK_RECORD_FIX_BANK_LINE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }
    }

	/**
	 *  Test if the bank record is broken down
	 *
	 * @return	int	          	<0 if KO, 0 if No, 1 if Yes
	 */
	public function is_broken_down()
	{
		global $langs;
		dol_syslog(__METHOD__ . " rowid={$this->id}", LOG_DEBUG);
		$this->error = '';
		$this->errors = array();

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (!($this->id > 0)) {
			$langs->load("errors");
			$this->errors[] = $langs->trans("ErrorBadParameters");
			dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		// Is bank record broken down
		$sql = 'SELECT COUNT(*) as nb' .
			' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl'.
			' LEFT JOIN ' . MAIN_DB_PREFIX . "accounting_bookkeeping AS abk ON abk.fk_docdet = brl.fk_bank" .
			' WHERE brl.fk_bank_record = ' . $this->id .
			" AND abk.doc_type = 'bank'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		$is_broken_down = 0;
		if ($obj = $this->db->fetch_object($resql)) {
			$is_broken_down = $obj->nb;
		}
		$this->db->free($resql);

		if (!$is_broken_down) {
			// Is bank record of the element broken down
			$sql = 'SELECT COUNT(*) as nb' .
				' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl'.
				" LEFT JOIN " . MAIN_DB_PREFIX . "bank_url AS bu ON bu.fk_bank = brl.fk_bank" .
				' LEFT JOIN ' . MAIN_DB_PREFIX . "accounting_bookkeeping AS abk ON abk.fk_doc = bu.url_id" .
				' WHERE brl.fk_bank_record = ' . $this->id .
				" AND ((abk.doc_type = 'customer_invoice' AND bu.type = 'payment')" .
				"   OR (abk.doc_type = 'supplier_invoice' AND bu.type = 'payment_supplier')" .
				"   OR (abk.doc_type = 'expense_report' AND bu.type = 'payment_expensereport'))";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
				return -1;
			}

			if ($obj = $this->db->fetch_object($resql)) {
				$is_broken_down = $obj->nb;
			}
			$this->db->free($resql);
		}

		return $is_broken_down;
	}

    /**
     *  Get nb bank line linked to this downloaded bank record
     *
     * @return	int	                    <0 if KO, else nb bank line linked
     */
    public function nbBankLink()
    {
        global $langs;
        dol_syslog(__METHOD__ . " rowid={$this->id}", LOG_DEBUG);
        $this->error = '';
        $this->errors = array();

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;

        // Check parameters
        if (!($this->id > 0)) {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorBadParameters");
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Get nb bank line linked
        $sql = 'SELECT COUNT(DISTINCT brl.fk_bank) as nb' .
            ' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl'.
            ' WHERE brl.fk_bank_record = ' . $this->id .
            ' GROUP BY brl.fk_bank_record';

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $nb_bank_linked = 0;
        if ($obj = $this->db->fetch_object($resql)) {
            $nb_bank_linked = $obj->nb;
        }
        $this->db->free($resql);

        return $nb_bank_linked;
    }

	/**
	 *  Get suggested third parties from this downloaded bank record
	 *
	 * @param 	string		$filter			Additional filter
	 * @return	int|array	                <0 if KO, else the list of the third parties suggested
	 */
	public function suggested_thirdparties($filter = '')
	{
		global $langs;
		dol_syslog(__METHOD__ . " rowid={$this->id}", LOG_DEBUG);
		$this->error = '';
		$this->errors = array();

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;
		$filter = trim($filter);

		// Check parameters
		if (!($this->id > 0)) {
			$langs->load("errors");
			$this->errors[] = $langs->trans("ErrorBadParameters");
			dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$filters = array();
		$words = array_merge(explode(' ', $this->label), explode(' ', $this->comment));
		foreach ($words as $word) {
			$word = trim($word);
			if (empty($word)) continue;
			$filters[$word] = "nom REGEXP '[[:<:]]" . $this->db->escape($word) . "[[:>:]]' OR name_alias REGEXP '[[:<:]]" . $this->db->escape($word) . "[[:>:]]'";
		}

		// Get suggested third parties
		$sql = 'SELECT rowid, nom, name_alias' .
			' FROM ' . MAIN_DB_PREFIX . 'societe' .
			' WHERE entity IN (' . getEntity('societe') . ')';
		if (!empty($filter)) $sql .= ' ' . $filter;
		if (!empty($filters)) $sql .= ' AND (' . implode(' OR ', $filters) . ')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		$thirdparties = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$thirdparties[$obj->rowid] = $obj->nom . (!empty($obj->name_alias) ? ' ( ' . $obj->name_alias . ' )' : '');
		}
		$this->db->free($resql);

		return $thirdparties;
	}

	/**
	 *  Get suggested employee from this downloaded bank record
	 *
	 * @return	int|array	                    <0 if KO, else the list of the employees suggested
	 */
	public function suggested_employees()
	{
		global $conf, $langs, $user;
		dol_syslog(__METHOD__ . " rowid={$this->id}", LOG_DEBUG);
		$this->error = '';
		$this->errors = array();

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (!($this->id > 0)) {
			$langs->load("errors");
			$this->errors[] = $langs->trans("ErrorBadParameters");
			dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$firstname_filters = array();
		$lastname_filters = array();
		$words = array_merge(explode(' ', $this->label), explode(' ', $this->comment));
		foreach ($words as $word) {
			$word = trim($word);
			if (empty($word)) continue;
			$firstname_filters[$word] = "firstname LIKE '" . $this->db->escape($word) . "'";
		}
		foreach ($words as $word) {
			$word = trim($word);
			if (empty($word)) continue;
			$lastname_filters[$word] = "lastname LIKE '" . $this->db->escape($word) . "'";
		}
		$filters = array();
		if (!empty($firstname_filters)) $filters[] = "firstname IS NULL OR firstname = '' OR " . implode(' OR ', $firstname_filters);
		if (!empty($lastname_filters)) $filters[] = "lastname IS NULL OR lastname = '' OR " . implode(' OR ', $lastname_filters);

		// Get suggested employees
		$sql = 'SELECT rowid, firstname, lastname FROM ' . MAIN_DB_PREFIX . 'user AS u';
		if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entity AS e ON e.rowid=u.entity";
			$sql .= " WHERE u.entity IS NOT NULL";
		} else {
			if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user AS ug ON ug.fk_user = u.rowid";
				$sql .= " WHERE ug.entity = " . $conf->entity;
			} else {
				$sql .= " WHERE u.entity IN (0," . $conf->entity . ")";
			}
		}
		$sql .= " AND u.employee = 1";
		if (!empty($filters)) $sql .= ' AND (' . implode(') AND (', $filters) . ')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
		$userstatic = new User($this->db);

		// $fullNameMode is 0=Lastname+Firstname (MAIN_FIRSTNAME_NAME_POSITION=1), 1=Firstname+Lastname (MAIN_FIRSTNAME_NAME_POSITION=0)
		$fullNameMode = 0;
		if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {
			$fullNameMode = 1; //Firstname+lastname
		}

		$employees = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$userstatic->id = $obj->rowid;
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;

			$employees[$obj->rowid] = $userstatic->getFullName($langs, $fullNameMode, -1);
		}
		$this->db->free($resql);

		return $employees;
	}

	/**
	 * Fix duplicate bank records
	 *
	 * @param   User    $user               		User who make the action
	 * @param   int		$main_id					Main record ID
	 * @param   int		$main_reconcile_date		Main reconcile date
	 * @param   int		$main_status				Main status
	 * @param   array	$duplicate_ids				List of duplicate records IDs
	 * @return  int									<0 if KO, >0 if Yes
	 */
	public function fixDuplicateRecords(User $user, $main_id, $main_reconcile_date, $main_status, $duplicate_ids)
	{
		global $langs;
		dol_syslog(__METHOD__ . " main_id=$main_id, main_reconcile_date=$main_reconcile_date, main_status=$main_status, duplicate_ids=" . json_encode($duplicate_ids), LOG_DEBUG);
		$langs->load('banking4dolibarr@banking4dolibarr');
		$error = 0;

		// Clean parameters
		$duplicate_ids = is_array($duplicate_ids) ? $duplicate_ids : array();
		$main_id = $main_id > 0 ? $main_id : 0;
		$main_reconcile_date = is_numeric($main_reconcile_date) ? $main_reconcile_date : null;
		$main_status = $main_status >= self::BANK_RECORD_STATUS_NOT_RECONCILED && $main_status < self::BANK_RECORD_STATUS_DUPLICATE ? $main_status : -1;

		// Check parameters
		if ($main_id == 0) {
			$this->errors[] = $langs->trans('Banking4DolibarrErrorFixDuplicateRecordsPrincipalRecordNotDefined', implode(', ', $duplicate_ids));
			$error++;
		} elseif (empty($duplicate_ids)) {
			$this->errors[] = $langs->trans("Banking4DolibarrErrorFixDuplicateRecordsOnOneRecord", $main_id);
			$error++;
		} elseif ($main_status == -1) {
			$this->errors[] = $langs->trans("Banking4DolibarrErrorFixDuplicateRecordsWrongPrincipalStatus", $main_id, implode(', ', $duplicate_ids));
			$error++;
		} elseif ($main_status == self::BANK_RECORD_STATUS_RECONCILED && !isset($main_reconcile_date)) {
			$this->errors[] = $langs->trans("Banking4DolibarrErrorFixDuplicateRecordsPrincipalReconcileDateNotDefined", $main_id, implode(', ', $duplicate_ids));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$now = dol_now();
		$this->db->begin();

		// Update duplicate records
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET" .
			"  fk_duplicate_of = " . $main_id .
			", reconcile_date = NULL" .
			", status = " . self::BANK_RECORD_STATUS_DUPLICATE .
			", tms = '" . $this->db->idate($now) . "'" .
			", fk_user_modif = " . $user->id;
		$sql .= " WHERE rowid IN (" . implode(',', $duplicate_ids) . ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			$error++;
		}

		// Update main records
		if (!$error) {
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET" .
				"  fk_duplicate_of = NULL" .
				", reconcile_date = " . (isset($main_reconcile_date) ? "'" . $this->db->idate($main_reconcile_date) . "'" : "NULL") .
				", status = " . $main_status .
				", tms = '" . $this->db->idate($now) . "'" .
				", fk_user_modif = " . $user->id;
			$sql .= " WHERE rowid = " . $main_id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
				$error++;
			}
		}

		// Update main records reconciled links
		if (!$error && $main_status == self::BANK_RECORD_STATUS_RECONCILED) {
			$sql = "UPDATE " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link SET" .
				"  fk_bank_record = " . $main_id .
				", tms = '" . $this->db->idate($now) . "'" .
				", fk_user_to_link = " . $user->id;
			$sql .= " WHERE fk_bank_record IN (" . implode(',', $duplicate_ids) . ")";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
				$error++;
			}
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

    /**
     *  Return label of bank record status
     *
     * @param   int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
     * @return  string              Libelle du statut
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->status,$mode);
    }

    /**
     *  Return label of bank record status provides
     *
     * @param   int     $statut     Id statut
     * @param   int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @return  string              Libelle du statut
     */
    public function LibStatut($statut, $mode=0)
	{
		global $langs;

		$langs->load("banking4dolibarr@banking4dolibarr");

		$isV10 = version_compare(DOL_VERSION, "10.0.0") >= 0;

		switch ($statut) {
			case self::BANK_RECORD_STATUS_NOT_RECONCILED:
				$icon = $isV10 ? 'status0' : 'statut0';
				$labelStatus = $langs->trans($this->labelStatus[$statut]);
				$labelStatusShort = $langs->trans($this->labelStatusShort[$statut]);
				break;
			case self::BANK_RECORD_STATUS_RECONCILED:
				$icon = $isV10 ? 'status4' : 'statut4';
				$labelStatus = $langs->trans($this->labelStatus[$statut]);
				$labelStatusShort = $langs->trans($this->labelStatusShort[$statut]);
				break;
			case self::BANK_RECORD_STATUS_DISCARDED:
			case self::BANK_RECORD_STATUS_DUPLICATE:
				$icon = $isV10 ? 'status6' : 'statut6';
				$labelStatus = $langs->trans($this->labelStatus[$statut]);
				$labelStatusShort = $langs->trans($this->labelStatusShort[$statut]);
				break;
			default:
				$icon = '';
				$labelStatus = $langs->trans('Unknown');
				$labelStatusShort = '';
				$mode = 0;
				break;
		}

		if ($isV10) {
			return dolGetStatus($labelStatus, $labelStatusShort, '', $icon, $mode);
		} else {
			switch ($mode) {
				case 1:
					return $labelStatusShort;
				case 2:
					return img_picto($labelStatus, $icon) . ' ' . $labelStatusShort;
				case 3:
					return img_picto($labelStatus, $icon);
				case 4:
					return img_picto($labelStatus, $icon) . ' ' . $labelStatus;
				case 5:
					return $labelStatusShort . ' ' . img_picto($labelStatus, $icon);
				case 6:
					return $labelStatus . ' ' . img_picto($labelStatus, $icon);
				default: // 0
					return $labelStatus;
			}
		}
	}

    /**
     *  Return label of bank record type
     *
     * @param   int		$mode       0=Long label, 1=Short label
     * @return  string              Libelle du type
     */
    function getLibType($mode=0)
    {
        return $this->LibType($this->record_type,$mode);
    }

    /**
     *  Return label of bank record type provides
     *
     * @param   int     $type       Type code
     * @param   int		$mode       0=Long label
     * @return  string              Libelle du type
     */
    public function LibType($type, $mode=0)
    {
        global $langs;

        $langs->load("banking4dolibarr@banking4dolibarr");
        $result = $this->loadBankRecordTypes();
        if ($result < 0) {
            return $this->errorsToString();
        }

        $id = isset(self::$bank_record_types_code_index_cached[$type]) ? self::$bank_record_types_code_index_cached[$type] : 0;
        switch ($mode) {
            default:
                return isset(self::$bank_record_types_cached[$id]) ? $langs->trans(self::$bank_record_types_cached[$id]->fields['label']) : $type;
        }
    }

    /**
     * Load the cache for bank record categories
     *
     * @param   bool    $force_reload       Force reload of the cache
     * @return  int                         <0 if KO, >0 if OK
     */
    public function loadBankRecordCategories($force_reload=false)
    {
        if (!isset(self::$bank_record_categories_cached) || $force_reload) {
            $bank_record_categories_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankrecordcategories');
            $res = $bank_record_categories_dictionary->fetch_lines(-1);
            if ($res > 0) {
                self::$bank_record_categories_cached = $bank_record_categories_dictionary->lines;
            } else {
                $this->error = $bank_record_categories_dictionary->error;
                $this->errors = array_merge($this->errors, $bank_record_categories_dictionary->errors);
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        }

        return 1;
    }

    /**
     * Load the cache for bank record types
     *
     * @param   bool    $force_reload       Force reload of the cache
     * @return  int                         <0 if KO, >0 if OK
     */
    public function loadBankRecordTypes($force_reload=false)
    {
        if (!isset(self::$bank_record_types_cached) || $force_reload) {
            $bank_record_types_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankrecordtypes');
            $res = $bank_record_types_dictionary->fetch_lines(-1);
            if ($res > 0) {
                self::$bank_record_types_cached = $bank_record_types_dictionary->lines;
                self::$bank_record_types_code_index_cached = array();
                foreach (self::$bank_record_types_cached as $line) {
                    self::$bank_record_types_code_index_cached[$line->fields['code']] = $line->id;
                    self::$bank_record_types_code_payment_mode_id_cached[$line->fields['code']] = dol_getIdFromCode($this->db, $line->fields['mode_reglement'], 'c_paiement', 'code', 'id', 1);
                }
            } else {
                $this->error = $bank_record_types_dictionary->error;
                $this->errors = array_merge($this->errors, $bank_record_types_dictionary->errors);
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Return category ID in Dolibarr for the category ID of the downloaded bank record
     *
     * @param   int     $bank_record_category_id    Category ID of the downloaded bank record
     * @return	int	                                <0 if KO, >0 if Category ID in Dolibarr
     */
    public function getDolibarrRecordCategoryId($bank_record_category_id)
    {
        global $langs;
        dol_syslog(__METHOD__ . " bank_record_category_id=$bank_record_category_id", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $result = $this->loadBankRecordCategories();
        if ($result < 0) {
            return -1;
        }

        if (empty(self::$bank_record_categories_cached[$bank_record_category_id]->fields['category'])) {
            dol_syslog(__METHOD__ . " - Warning: " . $langs->trans('Banking4DolibarrErrorBankRecordCategoryNotMatchedWithDolibarr', $bank_record_category_id), LOG_WARNING);
            return 0;
        }

        return self::$bank_record_categories_cached[$bank_record_category_id]->fields['category'];
    }

    /**
     *  Return payment mode ID in Dolibarr for the type of the downloaded bank record
     *
     * @param   int     $bank_record_type       Type of the downloaded bank record
     * @param   int     $mode                   Search mode (=0 if only on the $bank_record_type, =1 if search also on all bank record (Dolibarr) linked to this downloaded bank record)
     * @return	int	                            <0 if KO, >0 if payment mode ID in Dolibarr
     */
    public function getDolibarrPaymentModeId($bank_record_type, $mode=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " bank_record_type=$bank_record_type", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $result = $this->loadBankRecordTypes();
        if ($result < 0) {
            return -1;
        }

        if (empty(self::$bank_record_types_code_payment_mode_id_cached[$bank_record_type])) {
            $this->errors[] = $langs->trans('Banking4DolibarrErrorBankRecordTypeNotMatchedWithDolibarr', $bank_record_type);
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        return self::$bank_record_types_code_payment_mode_id_cached[$bank_record_type];
    }

    /**
     *  Return payment mode Code in Dolibarr for the type of the downloaded bank record
     *
     * @param   int     $bank_record_type       Type of the downloaded bank record
     * @param   int     $mode                   Search mode (=0 if only on the $bank_record_type, =1 if search also on all bank record (Dolibarr) linked to this downloaded bank record)
     * @return	int	                            <0 if KO, >0 if payment mode Code in Dolibarr
     */
    public function getDolibarrPaymentModeCode($bank_record_type, $mode=0)
    {
        global $langs;
        dol_syslog(__METHOD__ . " bank_record_type=$bank_record_type", LOG_DEBUG);
        $langs->load("banking4dolibarr@banking4dolibarr");

        $result = $this->loadBankRecordTypes();
        if ($result < 0) {
            return -1;
        }

        $id = isset(self::$bank_record_types_code_index_cached[$bank_record_type]) ? self::$bank_record_types_code_index_cached[$bank_record_type] : 0;
        if (!isset(self::$bank_record_types_cached[$id])) {
            $this->errors[] = $langs->trans('Banking4DolibarrErrorBankRecordTypeNotMatchedWithDolibarr', $bank_record_type);
            dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        return self::$bank_record_types_cached[$id]->fields['mode_reglement'];
    }

//    /**
//     *	Load miscellaneous information for tab "Info"
//     *
//     *	@param  int		$id		Id of object to load
//     *	@return	void
//     */
//    function info($id)
//    {
//        $sql = 'SELECT b.rowid, b.datec, b.tms as datem,';
//        $sql.= ' b.fk_user_author, b.fk_user_rappro';
//        $sql.= ' FROM '.MAIN_DB_PREFIX.'bank as b';
//        $sql.= ' WHERE b.rowid = '.$id;
//
//        $result=$this->db->query($sql);
//        if ($result)
//        {
//            if ($this->db->num_rows($result))
//            {
//                $obj = $this->db->fetch_object($result);
//                $this->id = $obj->rowid;
//
//                if ($obj->fk_user_author)
//                {
//                    $cuser = new User($this->db);
//                    $cuser->fetch($obj->fk_user_author);
//                    $this->user_creation     = $cuser;
//                }
//                if ($obj->fk_user_rappro)
//                {
//                    $ruser = new User($this->db);
//                    $ruser->fetch($obj->fk_user_rappro);
//                    $this->user_rappro = $ruser;
//                }
//
//                $this->date_creation     = $this->db->jdate($obj->datec);
//                $this->date_modification = $this->db->jdate($obj->datem);
//                //$this->date_rappro       = $obj->daterappro;    // Not yet managed
//            }
//            $this->db->free($result);
//        }
//        else
//        {
//            dol_print_error($this->db);
//        }
//    }
//
//    /**
//     *    	Return clicable name (with picto eventually)
//     *
//     *		@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
//     *		@param	int		$maxlen			Longueur max libelle
//     *		@param	string	$option			Option ('showall')
//     *		@return	string					Chaine avec URL
//     */
//    function getNomUrl($withpicto=0,$maxlen=0,$option='')
//    {
//        global $langs;
//
//        $result='';
//        $label=$langs->trans("ShowTransaction").': '.$this->rowid;
//        $linkstart = '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$this->rowid.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
//        $linkend='</a>';
//
//        $result .= $linkstart;
//        if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'account'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
//        if ($withpicto != 2) $result.=($this->ref?$this->ref:$this->rowid);
//        $result .= $linkend;
//
//        if ($option == 'showall' || $option == 'showconciliated') $result.=' (';
//        if ($option == 'showall')
//        {
//            $result.=$langs->trans("BankAccount").': ';
//            $accountstatic=new Account($this->db);
//            $accountstatic->id=$this->fk_account;
//            $accountstatic->ref=$this->bank_account_ref;
//            $accountstatic->label=$this->bank_account_label;
//            $result.=$accountstatic->getNomUrl(0).', ';
//        }
//        if ($option == 'showall' || $option == 'showconciliated')
//        {
//            $result.=$langs->trans("BankLineConciliated").': ';
//            $result.=yn($this->rappro);
//        }
//        if ($option == 'showall' || $option == 'showconciliated') $result.=')';
//
//        return $result;
//    }
}
