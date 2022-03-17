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


class ActionsBanking4Dolibarr
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
	 * @var array 	Embed pages urls
	 */
	protected static $embed_pages_cached = array();

	/**
	 * @var bool 	Is embed page managed
	 */
	protected static $is_embed_page_managed = false;

    /**
     * Constructor
     *
     * @param        DoliDB $_db Database handler
     */
    public function __construct($_db)
    {
        global $db;
        $this->db = is_object($_db) ? $_db : $db;
    }

    /**
     * Overloading the updateSession function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function updateSession($parameters, &$object, &$action, $hookmanager)
    {
        $this->_set_embed_page();
        return $this->_disable_bank_line_functions($parameters, $object, $action, $hookmanager);
    }

    /**
     * Overloading the afterLogin function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function afterLogin($parameters, &$object, &$action, $hookmanager)
    {
        $this->_set_embed_page();
        return $this->_disable_bank_line_functions($parameters, $object, $action, $hookmanager);
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
        global $langs;

        $context = explode(':', $parameters['context']);

		$this->_set_embed_page();

		if (in_array('banktransactionlist', $context)) {
			$langs->load('banking4dolibarr@banking4dolibarr');
			dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
			$budgetinsight = new BudgetInsight($this->db);
			$remote_bank_account_id = $budgetinsight->getRemoteBankAccountID($object->id);
			if ($remote_bank_account_id < 0) {
				setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
			} elseif ($remote_bank_account_id > 0) {
				if (GETPOST('confirm_reconcile') || GETPOST('confirm_savestatement', 'alpha')) {
					$_GET['confirm_reconcile'] = '';
					$_POST['confirm_reconcile'] = '';
					$_GET['confirm_savestatement'] = '';
					$_POST['confirm_savestatement'] = '';
					setEventMessage($langs->trans('Banking4DolibarrErrorFunctionDisabledBecauseIsReconciled'), 'errors');
				}
			}
		}

        return 0;
    }

	/**
	 * Overloading the printTopRightMenu function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function printTopRightMenu($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		if (!$this->_is_embed_page()) $this->_manage_embed_page();

		return 0;
	}

	/**
	 * Overloading the completeTabsHead function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		$obj = $parameters['object'];
		if (empty($obj->context['b4d_embed_page_hook'])) $this->_manage_embed_page($obj, 1);

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
		$this->_manage_embed_page($object);

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
        $context = explode(':', $parameters['context']);

        if (in_array('banktransactionlist', $context)) {
            print <<<SCRIPT
<script type="text/javascript">
    $(document).ready(function(){
        $('span[id^="dateoperation_"]').next('span').remove();
        $('span[id^="datevalue_"]').next('span').remove();
    });
</script>
SCRIPT;
        }

        return 0;
    }

	/**
	 * Overloading the addHtmlHeader function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addHtmlHeader($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		if (!empty($conf->revolutionpro->enabled) && $this->_is_embed_page()) {
			print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/banking4dolibarr/css/revolution_fix.css.php', 1).'">'."\n";
		}

		return 0;
	}

	/**
	 * Manage the disabling of the bank functions
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	protected function _disable_bank_line_functions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

        if (version_compare(DOL_VERSION, "11.0.0") >= 0) {
            $linkToBankLine = '/compta/bank/line.php';
        } else {
            $linkToBankLine = '/compta/bank/ligne.php';
        }

		if (preg_match('/' . preg_quote($linkToBankLine, '/') . '/i', $_SERVER["PHP_SELF"])) {
			$rowid = GETPOST('rowid', 'int');

			require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
			$account_line = new AccountLine($this->db);
			$result = $account_line->fetch($rowid);
			if ($result < 0) {
				setEventMessages($account_line->error, $account_line->errors, 'errors');
				return -1;
			} elseif ($result > 0) {
				$langs->load('banking4dolibarr@banking4dolibarr');
				dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
				$budgetinsight = new BudgetInsight($this->db);
				$remote_bank_account_id = $budgetinsight->getRemoteBankAccountID($account_line->fk_account);
				if ($remote_bank_account_id < 0) {
					setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
					return -1;
				} elseif ($remote_bank_account_id > 0) {
					$action = GETPOST('action', 'aZ09');

					if ($action == 'num_releve' || $action == 'setreconcile') {
						$langs->load('banking4dolibarr@banking4dolibarr');
						$_GET['action'] = '';
						$_POST['action'] = '';
						setEventMessage($langs->trans('Banking4DolibarrErrorFunctionDisabledBecauseIsReconciled'), 'errors');
					}
				}
			}
		}

		return 0;
	}

	/**
	 * Get embed page urls
	 *
	 * @param   string 		$element_type		Element type
	 * @return  array							List of embed page urls
	 */
	public function get_embed_page_urls($element_type)
	{
		if ($element_type == 'invoice_supplier' && !isset(self::$embed_pages_cached[$element_type])) {
			require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
			$object = new FactureFournisseur($this->db);
			$object->id = 0;
			$object->context['b4d_embed_page_hook'] = 1;
			require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
			ob_start();
			$head = facturefourn_prepare_head($object);
			ob_end_clean();
			$page_urls = array();
			foreach ($head as $item) {
				$page_urls[] = substr($item[0], 0, strpos($item[0], '?'));
			}
			self::$embed_pages_cached[$element_type] = $page_urls;
		}

		return self::$embed_pages_cached[$element_type];
	}

	/**
	 * Is a manual reconciliation create element page
	 *
	 * @return  bool
	 */
	protected function _is_embed_page()
	{
		return GETPOST('b4d_embed_page_id', 'int') > 0;
	}

	/**
	 * Set embed page conf (hide menus, ...)
	 *
	 * @return  void
	 */
	protected function _set_embed_page()
	{
		global $conf;

		if ((empty($conf->dol_hide_topmenu) || empty($conf->dol_hide_leftmenu)) && $this->_is_embed_page()) {
			$conf->dol_hide_topmenu = true;
			$conf->dol_hide_leftmenu = true;
			if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
		}
	}

	/**
	 * Manage embed page (hide buttons/links, add url/input parameters, ...)
	 *
	 * @param   CommonObject 	$object 	Object handler
	 * @param   int 			$object 	Object handler
	 * @return  void
	 */
	protected function _manage_embed_page($object=null, $amount_warn=0)
	{
		global $conf, $langs;

		if (!self::$is_embed_page_managed) {
			if ($this->_is_embed_page()) {
				$b4d_embed_page_id = GETPOST('b4d_embed_page_id', 'int');
				$action_card = GETPOST('action', 'alpha');

				dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
				$budgetinsightbankrecord = new BudgetInsightBankRecord($this->db);
				$budgetinsightbankrecord->fetch($b4d_embed_page_id, '', 0, 0, 0, 1);

				$remaining_amount = price2num($budgetinsightbankrecord->remaining_amount_to_link, 'MT');
				if ($object->element == 'invoice_supplier') $remaining_amount *= -1;
				$element_amount = price2num($object->total_ttc, 'MT');

				if ($amount_warn) {
					$billing_amount = price(min($remaining_amount, $element_amount), 1, $langs, 0, -1, -1, $conf->currency);
					$langs->load('banking4dolibarr@banking4dolibarr');
					print '<div class="info center">' . $langs->trans('Banking4DolibarrInfoAmountBilled') . ' ' . $billing_amount . '</div>';
				}

				print "<!-- Manual reconciliation embed page - Begin -->\n";
				print "<script type=\"text/javascript\">\n";
				print "$(document).ready(function() {\n";

				if ($action_card == 'create' || $action_card == 'add') {
					require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
					$form = new Form($this->db);

					$langs->load('bills');

					$html_name_payment_mode = 'mode_reglement_id';
					$html_name_bank_account = 'fk_account';
					$html_name_thirdparty = 'socid';

					// Third party
					$filter = '';
					if ($object->element == 'invoice_supplier') $filter = 'AND fournisseur = 1';
					// disabled reload page to retrieve supplier informations
					if (!empty($conf->global->RELOAD_PAGE_ON_SUPPLIER_CHANGE)) {
						print "$('#$html_name_thirdparty').unbind('change');";
					}
					$suggested_thirdparties = $budgetinsightbankrecord->suggested_thirdparties($filter);
					if (is_array($suggested_thirdparties) && !empty($suggested_thirdparties)) {
						$langs->load('banking4dolibarr@banking4dolibarr');
						$thirdparty_text = ' - ' . $langs->trans('Banking4DolibarrSuggestedThirdParty') . ' : ';
						$thirdparty_text .= $form->selectarray('suggested_thirdparty', $suggested_thirdparties, -1, $langs->trans('SelectThirdParty'), 0, 0, null, 0, 'minwidth300');
						$thirdparty_text = dol_escape_js($thirdparty_text, 1);
						print "$('#$html_name_thirdparty').closest('td').append('$thirdparty_text');\n";
						print "$('#suggested_thirdparty').on('change', function () {\n";
						print "var _this = $(this);\n";
						print "$('#$html_name_thirdparty').val(_this.val());\n";
						print "$('#$html_name_thirdparty').trigger('change');\n";
						print "$('#search_$html_name_thirdparty').val($('#suggested_thirdparty option:selected').text());\n";
						print "});\n";
						if (count($suggested_thirdparties) == 1) {
							$val = array_keys($suggested_thirdparties);
							print "$('#suggested_thirdparty').val('{$val[0]}');\n";
							print "$('#suggested_thirdparty').trigger('change');\n";
						}
					}
					// Payment mode
					$payment_mode = GETPOST('b4d_payment_mode', 'int');
					if ($payment_mode > 0) {
						$fk_type = $langs->getLabelFromKey($this->db, $payment_mode, 'c_paiement', 'id', 'code', '', 1);
						$labeltype = ($langs->trans("PaymentTypeShort" . $fk_type) != "PaymentTypeShort" . $fk_type) ? $langs->trans("PaymentTypeShort" . $fk_type) : $langs->getLabelFromKey($this->db, $fk_type, 'c_paiement', 'code', 'libelle', '', 1);
						if ($labeltype == 'SOLD') $labeltype = '&nbsp;'; //$langs->trans("InitialBankBalance");
						$labeltype .= '<input type="hidden" name="' . $html_name_payment_mode . '" value="' . $payment_mode . '">';
						$labeltype = dol_escape_js($labeltype, 1);
						print "$('#select$html_name_payment_mode').closest('td').html('$labeltype');\n";
					} else {
						$_POST[$html_name_payment_mode] = $payment_mode;
						print "$('#select$html_name_payment_mode').closest('tr').first('td').addClass('fieldrequired');\n";
					}

					// Bank account
					$bank_account = GETPOST('b4d_bank_account', 'int');
					require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
					$account = new Account($this->db);
					$account->fetch($bank_account);
					$bank_account_text = $account->getNomUrl(1);
					$bank_account_text .= '<input type="hidden" name="' . $html_name_bank_account . '" value="' . $bank_account . '">';
					$bank_account_text = dol_escape_js($bank_account_text, 1);
					print "$('#select$html_name_bank_account').closest('td').html('$bank_account_text');\n";

					// Manual reconciliation flag
					print "$('form').map(function() {
						$(this).append('<input type=\"hidden\" name=\"b4d_payment_mode\" value=\"$payment_mode\"><input type=\"hidden\" name=\"b4d_bank_account\" value=\"$bank_account\">');
					});\n";

					// cancel button
					print "$('input[type=\"button\"][value=\"" . $langs->trans("Cancel") . "\"]').remove();\n";
				} else {
					if ($element_amount < $remaining_amount) {
						print "$('#b4d_save_and_create', window.parent.document).show()\n";
					} else {
						print "$('#b4d_save_and_create', window.parent.document).hide()\n";
					}

					print "$('div.pagination.paginationref').remove();\n";
					print "$('a[href*=\"?action=editmode&\"]').remove();\n";
					print "$('a[href*=\"?action=editbankaccount&\"]').remove();\n";
					print "$('div.divButAction :contains(\"" . dol_escape_js($langs->transnoentitiesnoconv('ReOpen')) . "\")').remove();\n";
					print "$('div.divButAction :contains(\"" . dol_escape_js($langs->transnoentitiesnoconv('DoPayment')) . "\")').remove();\n";
					print "$('div.divButAction :contains(\"" . dol_escape_js($langs->transnoentitiesnoconv('ClassifyPaid')) . "\")').remove();\n";
					print "$('div.divButAction :contains(\"" . dol_escape_js($langs->transnoentitiesnoconv('DoPaymentBack')) . "\")').remove();\n";
					print "$('div.divButAction :contains(\"" . dol_escape_js($langs->transnoentitiesnoconv('ConvertToReduc')) . "\")').remove();\n";
					print "$('div.divButAction :contains(\"" . dol_escape_js($langs->transnoentitiesnoconv('ToClone')) . "\")').remove();\n";
					print "$('div.divButAction :contains(\"" . dol_escape_js($langs->transnoentitiesnoconv('CreateCreditNote')) . "\")').remove();\n";
				}

				$page_urls = $this->get_embed_page_urls($object->element);
				print "var b4d_embed_urls = $.makeArray(" . json_encode($page_urls) . ");

				$('a').not('.cke a').map(function() {
					var _this = $(this);
					var _href = _this.attr('href');
					var is_embed_url = false;

					if (typeof _href === 'string' && _href.indexOf('javascript:') == -1) {
						$.each(b4d_embed_urls, function(idx, url) {
							if (_href.indexOf(url) >= 0) {
							 	is_embed_url = true;
							 	return false;
							}
						});

						if (is_embed_url) {
							var token = (_href.indexOf('?') >= 0 ? '&' : '?') + 'b4d_embed_page_id=$b4d_embed_page_id';
							var tag_pos = _href.indexOf('#');
							var new_href = tag_pos >= 0 ? _href.substr(0, tag_pos) + token + _href.substr(tag_pos) : _href + token;
							_this.attr('href', new_href);
						} else {
							_this.attr('target', '_blank');
						}
					}
				});

				$('form').map(function() {
					$(this).append('<input type=\"hidden\" name=\"b4d_embed_page_id\" value=\"$b4d_embed_page_id\">');
				});\n";

				print "});\n";
				print "</script>\n";
				print "<!-- Manual reconciliation embed page - End -->\n";
			} else {
				print <<<SCRIPT
	<!-- Embed page management - Begin -->
	<script type="text/javascript">
		if (typeof window.parent !== 'undefined' && typeof window.parent.window.b4d_embed_page_id !== 'undefined' && window.parent.window.b4d_embed_page_id) {
			window.location.href = window.location.href + (window.location.href.indexOf('?') >= 0 ? '&' : '?') + 'b4d_embed_page_id=' + window.parent.window.b4d_embed_page_id;
		}
	</script>
	<!-- Embed page management - End -->
SCRIPT;
			}
			self::$is_embed_page_managed = true;
		}
	}
}