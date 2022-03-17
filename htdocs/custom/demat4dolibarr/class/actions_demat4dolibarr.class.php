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


class ActionsDemat4Dolibarr
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
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function doActions($parameters, &$object, &$action, $hookmanager)
    {
	    global $conf, $langs, $user;

	    $context = explode(':', $parameters['context']);

	    if (in_array('invoicecard', $context)) {
		    $confirm = GETPOST('confirm', 'alpha');
            if ($action == 'd4d_send_chrorus' && $user->rights->demat4dolibarr->chorus && !$object->paye &&
                (!empty($conf->global->DEMAT4DOLIBARR_INVOICE_FORCE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS) ||
                    !empty($conf->global->DEMAT4DOLIBARR_INVOICE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS_IF_NO_FILES) ||
                    !empty($conf->global->DEMAT4DOLIBARR_INVOICE_DIRECT_SEND_TO_CHORUS_IF_ONLY_ONE_FILE)) &&
                (($object->type == Facture::TYPE_STANDARD && $object->statut == Facture::STATUS_VALIDATED) ||
                    ($object->type == Facture::TYPE_DEPOSIT && $object->statut == Facture::STATUS_VALIDATED) ||
                    ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == Facture::STATUS_VALIDATED))
            ) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                $langs->load('demat4dolibarr@demat4dolibarr');
                $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
                $file_list = dol_dir_list($filedir, 'files', 0, '(' . preg_quote($conf->global->DEMAT4DOLIBARR_FILES_TYPE) . ')$', '', 'date', SORT_DESC);
                if (!empty($conf->global->DEMAT4DOLIBARR_INVOICE_FORCE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS) ||
                    (is_array($file_list) && count($file_list) == 0 && !empty($conf->global->DEMAT4DOLIBARR_INVOICE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS_IF_NO_FILES))) {
                    $hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
                    $hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
                    $hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

                    $outputlangs = $langs;
                    $newlang = '';
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                        $outputlangs->load('products');
                    }
                    $model = $object->modelpdf;
                    $ret = $object->fetch($object->id); // Reload to get new records

                    $result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
                    if ($result > 0) {
                        if (!empty($conf->global->DEMAT4DOLIBARR_INVOICE_DIRECT_SEND_TO_CHORUS_IF_ONLY_ONE_FILE)) {
                            $file_list = dol_dir_list($filedir, 'files', 0, '(' . preg_quote($conf->global->DEMAT4DOLIBARR_FILES_TYPE) . ')$', '', 'date', SORT_DESC);
                        }
                    } else {
                        setEventMessages($object->error, $object->errors, 'errors');
                        return -1;
                    }
                }
                if (is_array($file_list) && count($file_list) == 1 && !empty($conf->global->DEMAT4DOLIBARR_INVOICE_DIRECT_SEND_TO_CHORUS_IF_ONLY_ONE_FILE)) {
                    $file = array_values($file_list)[0];
                    $_GET['pdf_file'] = $file["name"];
                    $confirm = "yes";
                    $action = "confirm_d4d_send_chrorus";
                }
            }
            if ($action == 'confirm_d4d_send_chrorus' && $confirm == 'yes' && $user->rights->demat4dolibarr->chorus &&
			    !$object->paye && $this->_canSend($object) &&
			    (($object->type == Facture::TYPE_STANDARD && $object->statut == Facture::STATUS_VALIDATED) ||
                 ($object->type == Facture::TYPE_DEPOSIT && $object->statut == Facture::STATUS_VALIDATED) ||
                 ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == Facture::STATUS_VALIDATED))
		    ) {
			    $langs->load('demat4dolibarr@demat4dolibarr');

			    $file = GETPOST('pdf_file', 'alpha');
			    if (empty($file)) {
				    setEventMessage($langs->trans('Demat4DolibarrErrorNoneFileSelected'), 'errors');
				    return -1;
			    }

			    dol_include_once('/demat4dolibarr/class/ededoc.class.php');
			    $ededoc = new EdeDoc($this->db);

                $currentNumRequest = $ededoc->get_number_request_sent();
                if ($currentNumRequest < 0) {
                    setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                    return -1;
                }

                if ($ededoc->max_request * 2 <= $currentNumRequest) {
                    setEventMessage($langs->trans('Demat4DolibarrDontSendExceedsDoubleMaxRequest', $ededoc->max_request, $currentNumRequest));
                    return -1;
                } else {
                    // Connection to EDEDOC
                    $result = $ededoc->connection();
                    if ($result < 0) {
                        setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                        return -1;
                    }

                    // Send invoice to EDEDOC
                    $filepath = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref) . '/' . $file;
                    $result = $ededoc->sendInvoiceToChorus($object, $filepath);
                    if ($result < 0) {
                        setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                        return -1;
                    } elseif ($result == 2) {
                        setEventMessage($langs->trans('Demat4DolibarrErrorWhenCreateActionSendInvoiceToChorus'), 'warnings');
                        setEventMessages($ededoc->error, $ededoc->errors, 'warnings');
                    }

                    setEventMessage($langs->trans('Demat4DolibarrSendChorusSuccess'));

                    header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id);
                    exit();
                }
		    } elseif ($action == 'd4d_update_status_chrorus' && (!empty($object->array_options['options_d4d_job_id']) || !empty($object->array_options['options_d4d_invoice_id'])) &&
                $user->rights->demat4dolibarr->chorus && (($object->type == Facture::TYPE_STANDARD && $object->statut == Facture::STATUS_VALIDATED) ||
                    ($object->type == Facture::TYPE_DEPOSIT && $object->statut == Facture::STATUS_VALIDATED) ||
                    ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == Facture::STATUS_VALIDATED))
            ) {
                $langs->load('demat4dolibarr@demat4dolibarr');

                dol_include_once('/demat4dolibarr/class/ededoc.class.php');
                $ededoc = new EdeDoc($this->db);

                // Connection to EDEDOC
                $result = $ededoc->connection();
                if ($result < 0) {
                    setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                    return -1;
                }

                // Get chorus status from EDEDOC
                $result = $ededoc->getJobStatus($object);
                if ($result < 0) {
                    setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                    return -1;
                } elseif ($result > 0) {
                    setEventMessage($langs->trans('Demat4DolibarrGetChorusStatusSuccess'));
                }

                header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id);
                exit();
            } elseif ($action == 'd4d_activate_invoices_send_to_chorus') {
                $result = $object->fetch_thirdparty();
                if ($result < 1) {
                    setEventMessages($object->thirdparty->error, $object->thirdparty->errors, 'errors');
                } else {
                    $object->thirdparty->array_options['options_d4d_invoice_send_to_chorus'] = 1;
                    $result = $object->thirdparty->insertExtraFields();
                    if ($result < 1) {
                        setEventMessages($object->thirdparty->error, $object->thirdparty->errors, 'errors');
                    }
                }

                header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id);
                exit();
            }
	    }

	    return 0;
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf;

        $context = explode(':', $parameters['context']);

        if (in_array('invoicecard', $context)) {
            if ($action == 'create') {
                if (!($object->array_options['options_d4d_billing_mode'] > 0) && $conf->global->DEMAT4DOLIBARR_DEFAULT_BILLING_MODE > 0) {
                    $object->array_options['options_d4d_billing_mode'] = $conf->global->DEMAT4DOLIBARR_DEFAULT_BILLING_MODE;
                }
            }
        }

        return 0;
    }

	/**
	* Overloading the formObjectOptions function : replacing the parent's function with the one below
	*
	* @param   array() $parameters Hook metadatas (context, etc...)
	* @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	* @param   string &$action Current action (if set). Generally create or edit or null
	* @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	* @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	*/
	function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $form, $formfile, $user;

		$context = explode(':', $parameters['context']);

		if (in_array('invoicecard', $context)) {
			if ($action == 'd4d_send_chrorus' && $user->rights->demat4dolibarr->chorus && !$object->paye &&
				(($object->type == Facture::TYPE_STANDARD && $object->statut == Facture::STATUS_VALIDATED) ||
			     ($object->type == Facture::TYPE_DEPOSIT && $object->statut == Facture::STATUS_VALIDATED) ||
			     ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == Facture::STATUS_VALIDATED))
			) {
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
				$langs->load('demat4dolibarr@demat4dolibarr');
				if (!is_object($form)) {
					require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
					$form = new Form($this->db);
				}
				if (!is_object($formfile)) {
					require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
					$formfile = new FormFile($this->db);
				}

				// Get list of files
				$modulepart = 'facture';
				$modulesubdir = dol_sanitizeFileName($object->ref);
				$filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
				$files_output = '<table id="pdf_files_table" class="liste noborder" width="100%">';
				$file_list = dol_dir_list($filedir, 'files', 0, '(' . preg_quote($conf->global->DEMAT4DOLIBARR_FILES_TYPE) . ')$', '', 'date', SORT_DESC);
				// Loop on each file found
				if (is_array($file_list) && count($file_list) > 0) {
					$idx = 0;
					foreach ($file_list as $file) {
						// Define relative path for download link (depends on module)
						$relativepath = $file["name"];                                              // Cas general
						if ($modulesubdir) $relativepath = $modulesubdir . "/" . $file["name"];     // Cas propal, facture...
						if ($modulepart == 'export') $relativepath = $file["name"];                 // Other case

						$files_output .= '<tr class="oddeven">';

						$documenturl = DOL_URL_ROOT . '/document.php';
						if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP;    // To use another wrapper

						$files_output .= '<td width="20px" class="nowrap"><input type="radio" id="pdf_file" name="pdf_file[]" value="' . htmlspecialchars($file["name"]) . '"' . ($idx == 0 ? ' checked' : '') . '></td>';

						// Show file name with link to download
						$files_output .= '<td class="minwidth200">';
						$files_output .= '<a class="documentdownload paddingright" href="' . $documenturl . '?modulepart=' . $modulepart . '&amp;file=' . urlencode($relativepath) . '"';
						$files_output .= ' target="_blank">';
						$files_output .= img_mime($file["name"], $langs->trans("File") . ': ' . $file["name"]);
						$files_output .= dol_trunc($file["name"], 150);
						$files_output .= '</a>' . "\n";
						$files_output .= $formfile->showPreview($file, $modulepart, $relativepath, 0, '');
						$files_output .= '</td>';

						// Show file size
						$size = (!empty($file['size']) ? $file['size'] : dol_filesize($filedir . "/" . $file["name"]));
						$files_output .= '<td align="right" class="nowrap">' . dol_print_size($size) . '</td>';

						// Show file date
						$date = (!empty($file['date']) ? $file['date'] : dol_filemtime($filedir . "/" . $file["name"]));
						$files_output .= '<td align="right" class="nowrap">' . dol_print_date($date, 'dayhour', 'tzuser') . '</td>';

						$files_output .= '</tr>';
						$idx++;
					}
				} else {
					$files_output .= '<tr class="oddeven"><td colspan="4" class="opacitymedium">' . $langs->trans("None") . '</td></tr>' . "\n";
				}
				$files_output .= "</table>\n";
				$files_output .= <<<SCRIPT
	<script type="text/javascript" language="javascript">
		$(document).ready(function () {
			$('table#pdf_files_table tr').click(function(event) {
				if (event.target.type !== 'radio') {
					$(':radio', this).trigger('click');
				}
			});
		});
	</script>
SCRIPT;

				dol_include_once('/demat4dolibarr/class/ededoc.class.php');
				$ededoc = new EdeDoc($this->db);
				$currentNumRequest = $ededoc->get_number_request_sent();
				if ($currentNumRequest < 0) {
				    setEventMessages($ededoc->error, $ededoc->errors, 'errors');
				    return -1;
                }

                if ($ededoc->max_request * 2 <= $currentNumRequest) {
                    $formquestion = array();
                    $formquestion[] = array('type' => 'onecolumn', 'value' => '<span style="color: red;">' . $langs->trans('Demat4DolibarrDontSendExceedsDoubleMaxRequest', $ededoc->max_request, $currentNumRequest) . '</span>');

                    print $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('Demat4DolibarrSendChorus'), $langs->trans('Demat4DolibarrConfirmSendChorus'), 'confirm_d4d_dontsend_chrorus', $formquestion, 'yes', 1, 400, 800);
                } else {
                    $formquestion = array();
                    $break = false;
                    if (!empty($object->array_options['options_d4d_job_id']) || !empty($object->array_options['options_d4d_invoice_id'])) {
                        $formquestion[] = array('type' => 'onecolumn', 'value' => '<span style="color: red;">' . $langs->trans('Demat4DolibarrWarningAlreadySendToChorus') . '</span>');
                        $break = true;
                    }
                    if ($ededoc->max_request <= $currentNumRequest) {
                        $formquestion[] = array('type' => 'onecolumn', 'value' => '<span style="color: red;">' . $langs->trans('Demat4DolibarrExceedsMaxRequest', $currentNumRequest, $ededoc->max_request, $ededoc->max_request * 2 - $currentNumRequest) . '</span>');
                        $break = true;
                    }
                    if ($break) {
                        $formquestion[] = array('type' => 'onecolumn', 'value' => '&nbsp;');
                    }

                    $formquestion[] = array('type' => 'onecolumn', 'value' => $langs->trans('Demat4DolibarrChoicePDFFileToSendChorus'));
                    $formquestion[] = array('type' => 'onecolumn', 'name' => 'pdf_file', 'value' => $files_output);

                    print $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('Demat4DolibarrSendChorus'), $langs->trans('Demat4DolibarrConfirmSendChorus'), 'confirm_d4d_send_chrorus', $formquestion, 'yes', 1, 400, 800);
                }
			}
		}

		return 0;
	}

	/**
	* Overloading the printFieldPreListTitle function : replacing the parent's function with the one below
	*
	* @param   array() $parameters Hook metadatas (context, etc...)
	* @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	* @param   string &$action Current action (if set). Generally create or edit or null
	* @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	* @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	*/
	function printFieldPreListTitle($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $form, $formfile, $user;

		$context = explode(':', $parameters['context']);

		if (in_array('invoicelist', $context)) {
			$massaction = GETPOST('massaction', 'alpha');
			if ($massaction == 'd4d_send_chrorus' && $user->rights->demat4dolibarr->chorus) {
				$toselect = GETPOST('toselect', 'array');
				$langs->load('demat4dolibarr@demat4dolibarr');
				if (!is_object($form)) {
					require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
					$form = new Form($this->db);
				}

				dol_include_once('/demat4dolibarr/class/ededoc.class.php');
				$ededoc = new EdeDoc($this->db);
                $currentNumRequest = $ededoc->get_number_request_sent();
                if ($currentNumRequest < 0) {
                    setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                    return -1;
                }

                if ($ededoc->max_request * 2 < $currentNumRequest + count($toselect)) {
                    $formquestion = array();
                    $formquestion[] = array('type' => 'onecolumn', 'value' => '<span style="color: red;">' . $langs->trans('Demat4DolibarrDontSendExceedsDoubleMaxRequestTo', $ededoc->max_request, $currentNumRequest + count($toselect) - $ededoc->max_request, $currentNumRequest) . '</span>');

                    print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('Demat4DolibarrSendChorus'), $langs->trans('Demat4DolibarrConfirmSendChorus'), 'confirm_d4d_dontsend_chrorus', $formquestion, 'yes', 0, 200, 500, 1);
                } else {
                    $formquestion = array(array('type' => 'onecolumn', 'value' => $langs->trans('Demat4DolibarrOnlyInvoiceWithOneFileSendToChorus')));
                    if ($ededoc->max_request < $currentNumRequest + count($toselect)) {
                        $formquestion[] = array('type' => 'onecolumn', 'value' => '<span style="color: red;">' . $langs->trans('Demat4DolibarrExceedsMaxRequestTo', $ededoc->max_request, $currentNumRequest + count($toselect) - $ededoc->max_request, $currentNumRequest) . '</span>');
                    }

                    print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('Demat4DolibarrSendChorus'), $langs->trans('Demat4DolibarrConfirmSendChorus'), 'confirm_d4d_send_chrorus', $formquestion, 'yes', 0, 200, 500, 1);
                }
			}
		}

		return 0;
	}

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
	    global $conf, $langs, $user;

	    $context = explode(':', $parameters['context']);

	    if (in_array('invoicecard', $context)) {
		    // Send to chorus
		    if ($user->rights->demat4dolibarr->chorus && (($object->type == Facture::TYPE_STANDARD && $object->statut == Facture::STATUS_VALIDATED) ||
				    ($object->type == Facture::TYPE_DEPOSIT && $object->statut == Facture::STATUS_VALIDATED) ||
				    ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == Facture::STATUS_VALIDATED))
		    ) {
			    $langs->load('demat4dolibarr@demat4dolibarr');

			    if (!is_object($object->thirdparty)) {
                    $object->fetch_thirdparty();
                }

			    if (!empty($object->thirdparty->array_options['options_d4d_invoice_send_to_chorus'])) {
                    if (!$object->paye) {
                        if ($this->_canSend($object)) {
                            if (!empty($conf->global->DEMAT4DOLIBARR_PROVIDER_CODE) && !empty($conf->global->DEMAT4DOLIBARR_MODULE_KEY)) {
                                print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&action=d4d_send_chrorus" >' .
                                    $langs->trans('Demat4DolibarrSendChorus') . '</a></div>';
                            } else {
                                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("Demat4DolibarrErrorModuleNotConfigured") . '" >' .
                                    $langs->trans('Demat4DolibarrSendChorus') . '</a></div>';
                            }
                        } else {
                            print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("Demat4DolibarrErrorAlreadySendToChorus") . '" >' .
                                $langs->trans('Demat4DolibarrSendChorus') . '</a></div>';
                        }
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("Demat4DolibarrErrorAlreadyPayed") . '" >' .
                            $langs->trans('Demat4DolibarrSendChorus') . '</a></div>';
                    }
                    if (!empty($object->array_options['options_d4d_job_id']) || !empty($object->array_options['options_d4d_invoice_id'])) {
                        if (!empty($conf->global->DEMAT4DOLIBARR_MODULE_KEY)) {
                            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&action=d4d_update_status_chrorus" >' .
                                $langs->trans('Demat4DolibarrGetChorusStatus') . '</a></div>';
                        } else {
                            print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("Demat4DolibarrErrorModuleNotConfigured") . '" >' .
                                $langs->trans('Demat4DolibarrGetChorusStatus') . '</a></div>';
                        }
                    }
                } else {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&action=d4d_activate_invoices_send_to_chorus" >' .
                        $langs->trans('Demat4DolibarrActivateSendToChorus') . '</a></div>';
                }
		    }
	    }

	    return 0;
    }

	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $context = explode(':', $parameters['context']);

        if (in_array('invoicelist', $context)) {
            $massaction = GETPOST('massaction', 'alpha');
            $confirm = GETPOST('confirm', 'alpha');
            if ($user->rights->demat4dolibarr->chorus && $massaction == 'd4d_update_status_chrorus') {
                $langs->load('demat4dolibarr@demat4dolibarr');

                dol_include_once('/demat4dolibarr/class/ededoc.class.php');
                $ededoc = new EdeDoc($this->db);

                // Connection to EDEDOC
                $result = $ededoc->connection();
                if ($result < 0) {
                    setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                    return -1;
                }

                // Get chorus status from EDEDOC
                $error = 0;
                $invoice_success = array();
                require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                foreach ($parameters['toselect'] as $objectid) {
                    $invoice = new Facture($this->db);
                    $invoice->fetch($objectid);

                    if ((!empty($invoice->array_options['options_d4d_job_id']) || !empty($invoice->array_options['options_d4d_invoice_id'])) &&
                        ($invoice->type == Facture::TYPE_STANDARD && $invoice->statut == Facture::STATUS_VALIDATED) ||
                        ($invoice->type == Facture::TYPE_DEPOSIT && $invoice->statut == Facture::STATUS_VALIDATED) ||
                        ($invoice->type == Facture::TYPE_CREDIT_NOTE && $invoice->statut == Facture::STATUS_VALIDATED)
                    ) {
                        $result = $ededoc->getJobStatus($invoice);
                        if ($result < 0) {
                            setEventMessage($langs->trans('Demat4DolibarrErrorInvoice', $invoice->ref, $objectid), 'errors');
                            setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                            $error++;
                        } elseif ($result > 0) {
                            $invoice_success[] = $invoice->ref;
                        }
                    }
                }

                if (!$error || count($invoice_success) > 0) {
                    setEventMessage($langs->trans('Demat4DolibarrGetChorusStatusSuccess'));
                    if (count($invoice_success) > 0) setEventMessage(implode(', ', $invoice_success));
                } else {
                    return -1;
                }
            } elseif ($user->rights->demat4dolibarr->chorus && $action == 'confirm_d4d_send_chrorus' && $confirm == 'yes') {
                $langs->load('demat4dolibarr@demat4dolibarr');

                dol_include_once('/demat4dolibarr/class/ededoc.class.php');
                $ededoc = new EdeDoc($this->db);

                $currentNumRequest = $ededoc->get_number_request_sent();
                if ($currentNumRequest < 0) {
                    setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                    return -1;
                }

                if ($ededoc->max_request * 2 < $currentNumRequest + count($parameters['toselect'])) {
                    setEventMessage($langs->trans('Demat4DolibarrDontSendExceedsDoubleMaxRequest', $ededoc->max_request, $currentNumRequest));
                    return -1;
                } else {
                    // Connection to EDEDOC
                    $result = $ededoc->connection();
                    if ($result < 0) {
                        setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                        return -1;
                    }

                    // Send invoice to chorus by EDEDOC
                    $error = 0;
                    $invoice_success = array();
                    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                    foreach ($parameters['toselect'] as $objectid) {
                        $invoice = new Facture($this->db);
                        $invoice->fetch($objectid);

                        if (($invoice->type == Facture::TYPE_STANDARD && $invoice->statut == Facture::STATUS_VALIDATED) ||
                            ($invoice->type == Facture::TYPE_DEPOSIT && $invoice->statut == Facture::STATUS_VALIDATED) ||
                            ($invoice->type == Facture::TYPE_CREDIT_NOTE && $invoice->statut == Facture::STATUS_VALIDATED)
                        ) {
                            $invoice->fetch_thirdparty();
                            if (!empty($invoice->thirdparty->array_options['options_d4d_invoice_send_to_chorus']) && !$invoice->paye && $this->_canSend($invoice)) {
                                // Get list of files
                                $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($invoice->ref);
                                $file_list = dol_dir_list($filedir, 'files', 0, '(' . preg_quote($conf->global->DEMAT4DOLIBARR_FILES_TYPE) . ')$', '', 'date', SORT_DESC);
                                // Loop on each file found
                                if (is_array($file_list)) {
                                    $nbFiles = count($file_list);
                                    if ($nbFiles > 1) {
                                        setEventMessage($langs->trans('Demat4DolibarrErrorInvoiceTooManyFiles', $invoice->ref), 'errors');
                                        $error++;
                                    } elseif ($nbFiles == 0) {
                                        setEventMessage($langs->trans('Demat4DolibarrErrorInvoiceDontHaveFile', $invoice->ref), 'errors');
                                        $error++;
                                    } else {
                                        $files = array_values($file_list);
                                        $document_filepath = $conf->facture->dir_output . '/' . dol_sanitizeFileName($invoice->ref) . '/' . $files[0]["name"];

                                        $result = $ededoc->sendInvoiceToChorus($invoice, $document_filepath);
                                        if ($result < 0) {
                                            setEventMessage($langs->trans('Demat4DolibarrErrorInvoice', $invoice->ref, $objectid), 'errors');
                                            setEventMessages($ededoc->error, $ededoc->errors, 'errors');
                                            $error++;
                                        } elseif ($result > 0) {
                                            $invoice_success[] = $invoice->ref;
                                        }
                                    }
                                } else {
                                    setEventMessage($langs->trans('Demat4DolibarrErrorInvoiceGetFiles', $invoice->ref), 'errors');
                                    $error++;
                                }
                            }
                        }
                    }

                    if (!$error || count($invoice_success) > 0) {
                        setEventMessage($langs->trans('Demat4DolibarrSendInvoicesChorusSuccess'));
                        if (count($invoice_success) > 0) setEventMessage(implode(', ', $invoice_success));
                    } else {
                        return -1;
                    }
                }
            }
        } elseif (in_array('thirdpartylist', $context) || in_array('customerlist', $context)) {
            $massaction = GETPOST('massaction', 'alpha');

            if ($user->rights->demat4dolibarr->chorus && $massaction == 'd4d_activate_invoices_send_to_chrorus') {
                $error = 0;

                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                foreach ($parameters['toselect'] as $objectid) {
                    $company = new Societe($this->db);
                    $company->fetch($objectid);

                    $company->array_options['options_d4d_invoice_send_to_chorus'] = 1;
                    $result = $company->insertExtraFields();
                    if ($result < 1) {
                        setEventMessage($company->getFullName($langs), 'errors');
                        setEventMessages($company->error, $company->errors, 'errors');
                        $error++;
                    }
                }

                if (!$error) {
                    setEventMessage($langs->trans('Demat4DolibarrActivatedInvoicesSendToChorusSuccess'));
                } else {
                    return -1;
                }
            }
        }

        return 0;
    }

	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user;

        $context = explode(':', $parameters['context']);

        if (in_array('invoicelist', $context)) {
            $langs->load('demat4dolibarr@demat4dolibarr');
            $disabled = !$user->rights->demat4dolibarr->chorus;
            $this->resprints = '<option value="d4d_send_chrorus"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans("Demat4DolibarrSendChorus") . '</option>' .
                '<option value="d4d_update_status_chrorus"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans("Demat4DolibarrGetChorusStatus") . '</option>';
        } elseif (in_array('thirdpartylist', $context) || in_array('customerlist', $context)) {
            $langs->load('demat4dolibarr@demat4dolibarr');
            $disabled = !$user->rights->demat4dolibarr->chorus;
            $this->resprints = '<option value="d4d_activate_invoices_send_to_chrorus"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans("Demat4DolibarrActivateInvoicesSendToChorus") . '</option>';
        }

        return 0;
    }

	/**
	 * If job and chorus status permit to send to EDEDOC
	 *
	 * @param   CommonObject    &$object    The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @return  bool
	 */
    protected function _canSend(&$object)
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');

    	if ($object->array_options['options_d4d_job_status'] > 0) {
            $dictionaryLine = Dictionary::getDictionaryLine($this->db, 'demat4dolibarr', 'demat4dolibarrjobstatus');
            $res = $dictionaryLine->fetch($object->array_options['options_d4d_job_status']);
            if ($res <= 0 || empty($dictionaryLine->fields['can_resend']) || empty($dictionaryLine->active)) {
                if ($res <= 0) setEventMessages($dictionaryLine->error, $dictionaryLine->errors, 'errors');
                return false;
            }
	    }

        if ($object->array_options['options_d4d_chorus_status'] > 0) {
            $dictionaryLine = Dictionary::getDictionaryLine($this->db, 'demat4dolibarr', 'demat4dolibarrchorusstatus');
            $res = $dictionaryLine->fetch($object->array_options['options_d4d_chorus_status']);
            if ($res <= 0 || empty($dictionaryLine->fields['can_resend']) || empty($dictionaryLine->active)) {
                if ($res <= 0) setEventMessages($dictionaryLine->error, $dictionaryLine->errors, 'errors');
                return false;
            }
        }

        if ($object->array_options['options_d4d_invoice_status'] > 0) {
            $dictionaryLine = Dictionary::getDictionaryLine($this->db, 'demat4dolibarr', 'demat4dolibarrinvoicestatus');
            $res = $dictionaryLine->fetch($object->array_options['options_d4d_invoice_status']);
            if ($res <= 0 || empty($dictionaryLine->fields['can_resend']) || empty($dictionaryLine->active)) {
                if ($res <= 0) setEventMessages($dictionaryLine->error, $dictionaryLine->errors, 'errors');
                return false;
            }
        }

        return true;
    }
}