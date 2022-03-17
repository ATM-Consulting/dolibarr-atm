<?php
/* Copyright (C) 2019-2021  Open-DSI              <support@open-dsi.fr>
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
 *	\file       	htdocs/banking4dolibarr/bankrecords.php
 *	\ingroup    	Banking4dolibarr
 *	\brief      	Page of bank records list
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
dol_include_once('/banking4dolibarr/lib/opendsi_common.lib.php');
dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
dol_include_once('/banking4dolibarr/class/html.formbanking4dolibarr.class.php');
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

$langs->loadLangs(array('banking4dolibarr@banking4dolibarr', 'banks', 'companies', 'bills', 'other'));

$id	            = GETPOST('id', 'int');
$ref		    = GETPOST('ref','alpha');
$line_id	    = GETPOST('line_id', 'int');
$action         = GETPOST('action','alpha');
$confirm        = GETPOST('confirm','alpha');
$massaction     = GETPOST('massaction', 'alpha');
//$show_files     = GETPOST('show_files','int');
$toselect       = GETPOST('toselect', 'array');

// Security check
if ($user->societe_id > 0 || !$user->rights->banking4dolibarr->bank_records->lire) accessforbidden();
$result = restrictedArea($user, 'banque', !empty($ref) ? $ref : $id, 'bank_account&bank_account', '', '', !empty($ref) ? 'ref' : 'rowid');

// Load object
$object = new Account($db);
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$object->fetch_thirdparty();
		$id = $object->id;
	} elseif ($ret < 0) {
		dol_print_error('', $object->error, $object->errors);
	} elseif ($ret == 0) {
		$langs->load("errors");
		accessforbidden($langs->trans('ErrorRecordNotFound'));
	}
}

// Load bank record line
$budgetinsight_bank_line = new BudgetInsightBankRecord($db);
if ($line_id > 0) {
	$ret = $budgetinsight_bank_line->fetch($line_id);
	if ($ret < 0) {
		dol_print_error('', $budgetinsight_bank_line->error, $budgetinsight_bank_line->errors);
	} elseif ($ret == 0) {
		$langs->load("errors");
		accessforbidden($langs->trans('ErrorRecordNotFound'));
	}
}

// fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

$budgetinsight = new BudgetInsight($db);
$last_update_date = $budgetinsight->getBankRecordsLastUpdateDate($id);
if ($last_update_date < 0) {
    setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
}
$remote_bank_account_id = $budgetinsight->getRemoteBankAccountID($id);
if (!isset($remote_bank_account_id)) {
    setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
}

if (empty($toselect) && is_array($_SESSION['b4dbankrecordslist_toselect_' . $id])) $toselect = $_SESSION['b4dbankrecordslist_toselect_' . $id];

$search_id_record               = GETPOST('search_id_record', 'alpha');
$search_label                   = GETPOST('search_label', 'alpha');
$search_comment                 = GETPOST('search_comment', 'alpha');
$search_category                = GETPOST('search_category', 'alpha');
$search_record_date_start		= dol_mktime(0, 0, 0, GETPOST('search_record_date_startmonth', 'int'), GETPOST('search_record_date_startday', 'int'), GETPOST('search_record_date_startyear', 'int'));
$search_record_date_end			= dol_mktime(23, 59, 59, GETPOST('search_record_date_endmonth', 'int'), GETPOST('search_record_date_endday', 'int'), GETPOST('search_record_date_endyear', 'int'));
$search_rdate_start				= dol_mktime(0, 0, 0, GETPOST('search_rdate_startmonth', 'int'), GETPOST('search_rdate_startday', 'int'), GETPOST('search_rdate_startyear', 'int'));
$search_rdate_end				= dol_mktime(23, 59, 59, GETPOST('search_rdate_endmonth', 'int'), GETPOST('search_rdate_endday', 'int'), GETPOST('search_rdate_endyear', 'int'));
$search_bdate_start				= dol_mktime(0, 0, 0, GETPOST('search_bdate_startmonth', 'int'), GETPOST('search_bdate_startday', 'int'), GETPOST('search_bdate_startyear', 'int'));
$search_bdate_end				= dol_mktime(23, 59, 59, GETPOST('search_bdate_endmonth', 'int'), GETPOST('search_bdate_endday', 'int'), GETPOST('search_bdate_endyear', 'int'));
$search_vdate_start				= dol_mktime(0, 0, 0, GETPOST('search_vdate_startmonth', 'int'), GETPOST('search_vdate_startday', 'int'), GETPOST('search_vdate_startyear', 'int'));
$search_vdate_end				= dol_mktime(23, 59, 59, GETPOST('search_vdate_endmonth', 'int'), GETPOST('search_vdate_endday', 'int'), GETPOST('search_vdate_endyear', 'int'));
$search_date_scraped_start		= dol_mktime(0, 0, 0, GETPOST('search_date_scraped_startmonth', 'int'), GETPOST('search_date_scraped_startday', 'int'), GETPOST('search_date_scraped_startyear', 'int'));
$search_date_scraped_end		= dol_mktime(23, 59, 59, GETPOST('search_date_scraped_endmonth', 'int'), GETPOST('search_date_scraped_endday', 'int'), GETPOST('search_date_scraped_endyear', 'int'));
$search_record_type             = GETPOST('search_record_type', 'alpha');
$search_original_country        = GETPOST('search_original_country', 'alpha');
$search_original_amount         = GETPOST('search_original_amount', 'alpha');
$search_original_currency       = GETPOST('search_original_currency', 'alpha');
$search_commission              = GETPOST('search_commission', 'alpha');
$search_commission_currency     = GETPOST('search_commission_currency', 'alpha');
$search_debit                   = GETPOST('search_debit', 'alpha');
$search_credit                  = GETPOST('search_credit', 'alpha');
$search_coming                  = GETPOST('search_coming', 'int');
$search_deleted_date_start		= dol_mktime(0, 0, 0, GETPOST('search_deleted_date_startmonth', 'int'), GETPOST('search_deleted_date_startday', 'int'), GETPOST('search_deleted_date_startyear', 'int'));
$search_deleted_date_end		= dol_mktime(23, 59, 59, GETPOST('search_deleted_date_endmonth', 'int'), GETPOST('search_deleted_date_endday', 'int'), GETPOST('search_deleted_date_endyear', 'int'));
$search_last_update_date_start	= dol_mktime(0, 0, 0, GETPOST('search_last_update_date_startmonth', 'int'), GETPOST('search_last_update_date_startday', 'int'), GETPOST('search_last_update_date_startyear', 'int'));
$search_last_update_date_end	= dol_mktime(23, 59, 59, GETPOST('search_last_update_date_endmonth', 'int'), GETPOST('search_last_update_date_endday', 'int'), GETPOST('search_last_update_date_endyear', 'int'));
$search_reconcile_date_start	= dol_mktime(0, 0, 0, GETPOST('search_reconcile_date_startmonth', 'int'), GETPOST('search_reconcile_date_startday', 'int'), GETPOST('search_reconcile_date_startyear', 'int'));
$search_reconcile_date_end		= dol_mktime(23, 59, 59, GETPOST('search_reconcile_date_endmonth', 'int'), GETPOST('search_reconcile_date_endday', 'int'), GETPOST('search_reconcile_date_endyear', 'int'));
$search_tms_start				= dol_mktime(0, 0, 0, GETPOST('search_tms_startmonth', 'int'), GETPOST('search_tms_startday', 'int'), GETPOST('search_tms_startyear', 'int'));
$search_tms_end					= dol_mktime(23, 59, 59, GETPOST('search_tms_endmonth', 'int'), GETPOST('search_tms_endday', 'int'), GETPOST('search_tms_endyear', 'int'));
$search_bank_list               = GETPOST('search_bank_list', 'alpha');
$search_num_releve              = GETPOST('search_num_releve', 'alpha');
$search_datas                   = GETPOST('search_datas', 'alpha');
$search_import_key              = GETPOST('search_import_key', 'alpha');
$search_not_valid_only          = GETPOST('search_not_valid_only', 'array');
$search_duplicate_only          = GETPOST('search_duplicate_only', 'int');

$search_btn         = GETPOST('button_search','alpha');
$search_remove_btn  = GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha'); // All tests are required to be compatible with all browsers

$viewstatut     = GETPOST('viewstatut','alpha');
$optioncss      = GETPOST('optioncss','alpha');
$search_statut  = GETPOST('search_statut','alpha');

$mesg = (GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));

$limit      = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield  = GETPOST('sortfield','alpha');
$sortorder  = GETPOST('sortorder', 'alpha');
$page       = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='br.record_date';
if (! $sortorder) $sortorder='DESC';

// Initialize technical object to manage context to save list fields
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'b4dbankrecordslist';

//$diroutputmassaction=$conf->banking4dolibarr->dir_output . '/temp/massgeneration/'.$user->id;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('b4dbankrecordslist'));

$use_various_payment_card = !empty($conf->global->EASYA_VERSION) ||
	(version_compare(DOL_VERSION, "9.0.0") < 0 && $conf->global->MAIN_FEATURES_LEVEL >= 1) ||
	(version_compare(DOL_VERSION, "9.0.0") >= 0 && empty($conf->global->BANK_USE_OLD_VARIOUS_PAYMENT));

$class_fonts_awesome = !empty($conf->global->EASYA_VERSION) && version_compare(DOL_VERSION, "10.0.0") >= 0 ? 'fal' : 'fa';
$class_fonts_awesome2 = !empty($conf->global->EASYA_VERSION) && version_compare(DOL_VERSION, "10.0.0") >= 0 ? 'far' : 'fa';

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
);

$arrayfields = array(
    'br.rowid' => array('label'=> $langs->trans("TechnicalID"), 'checked'=> (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID) ? 1 : 0)),
    'br.id_record' => array('label'=> $langs->trans("Banking4DolibarrRemoteID"), 'checked'=> 1),
//    'br.label' => array('label' => $langs->trans("Description"), 'checked' => 1),
//    'br.record_date' => array('label' => $langs->trans("DateOperation"), 'checked' => 1),
//    'br.vdate' => array('label' => $langs->trans("DateValue"), 'checked' => 1),
//    'br.record_type' => array('label' => $langs->trans("Type"), 'checked' => 1),
//    'debit' => array('label' => $langs->trans("Debit"), 'checked' => 1),
//    'credit' => array('label' => $langs->trans("Credit"), 'checked' => 1),
    'bank_list' => array('label' => $langs->trans("BankTransactions"). " (D)", 'checked' => 0),
//    'b.num_releve' => array('label' => $langs->trans("AccountStatementShort"), 'checked' => 1, 'position' => 500),
    'br.reconcile_date' => array('label' => $langs->trans("Banking4DolibarrReconcileAt"), 'checked' => 0),
    'br.comment' => array('label' => $langs->trans("Comment"), 'checked' => 0),
    'br.id_category' => array('label' => $langs->trans("Banking4DolibarrCategory"), 'checked' => 0),
    'br.rdate' => array('label' => $langs->trans("Banking4DolibarrDateRealization"), 'checked' => 0),
    'br.bdate' => array('label' => $langs->trans("Banking4DolibarrDateBank"), 'checked' => 0),
    'br.date_scraped' => array('label' => $langs->trans("Banking4DolibarrDateScraped"), 'checked' => 0),
    'br.original_country' => array('label' => $langs->trans("Country"), 'checked' => 0),
    'br.original_amount' => array('label' => $langs->trans("Banking4DolibarrOriginAmount"), 'checked' => 0),
    'br.original_currency' => array('label' => $langs->trans("Banking4DolibarrOriginCurrency"), 'checked' => 0),
    'br.commission' => array('label' => $langs->trans("Banking4DolibarrCommissionAmount"), 'checked' => 0),
    'br.commission_currency' => array('label' => $langs->trans("Banking4DolibarrCommissionCurrency"), 'checked' => 0),
    'br.coming' => array('label' => $langs->trans("Banking4DolibarrComing"), 'checked' => 1),
    'br.deleted_date' => array('label' => $langs->trans("Banking4DolibarrDeleteAt"), 'checked' => 1),
    'br.last_update_date' => array('label' => $langs->trans("DateLastModification"), 'checked' => 0, 'position' => 500),
    'br.tms' => array('label' => $langs->trans("DateModification"). " (D)", 'checked' => 0, 'position' => 500),
	'br.datas' => array('label' => $langs->trans("Banking4DolibarrData"), 'checked' => 0, 'position' => 1000),
	'br.import_key' => array('label' => $langs->trans("ImportKey"), 'checked' => 0, 'position' => 1000),
//    'br.status' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);


/*
 * Actions
 */

if ($remote_bank_account_id != 0) {
	if (GETPOST('cancel', 'aZ09')) {
		$action = 'list';
		$massaction = '';
	}
	if (!GETPOST('confirmmassaction', 'alpha') && !in_array($massaction, array('preinpreparation', 'prereadytoship', 'preordercarrier', 'preeditordercarrier', 'pretobetakenoverbycarrier', 'predelete'))) {
		$massaction = '';
	}

	// Do we click on purge search criteria ?
	if ($search_remove_btn) {
		$search_id_record = '';
		$search_label = '';
		$search_comment = '';
		$search_category = '';
		$search_record_date_start = '';
		$search_record_date_end = '';
		$search_rdate_start = '';
		$search_rdate_end = '';
		$search_bdate_start = '';
		$search_bdate_end = '';
		$search_vdate_start = '';
		$search_vdate_end = '';
		$search_date_scraped_start = '';
		$search_date_scraped_end = '';
		$search_record_type = -1;
		$search_original_country = '';
		$search_original_amount = '';
		$search_original_currency = '';
		$search_commission = '';
		$search_commission_currency = '';
		$search_debit = '';
		$search_credit = '';
		$search_coming = '';
		$search_deleted_date_start = '';
		$search_deleted_date_end = '';
		$search_last_update_date_start = '';
		$search_last_update_date_end = '';
		$search_reconcile_date_start = '';
		$search_reconcile_date_end = '';
		$search_tms_start = '';
		$search_tms_end = '';
		$search_bank_list = '';
		$search_num_releve = '';
		$search_datas = '';
		$search_import_key = '';
		$search_not_valid_only = array();
		$search_duplicate_only = 0;
		$viewstatut = -1;
		$search_statut = -1;
		$toselect = '';
	}
	if ($search_duplicate_only) $search_statut = -1;
	if ($search_record_type == -1) $search_record_type = "";
	if ($search_coming === '') $search_coming = -1;
	if ($search_statut !== '') $viewstatut = $search_statut;
	if ($viewstatut === '') $viewstatut = '0';
	$search_debit = price2num(str_replace('-', '', $search_debit));
	$search_credit = price2num(str_replace('-', '', $search_credit));
	if (in_array("1", $search_not_valid_only)) {
		$arrayfields['br.record_date']['checked'] = 1;
	}
	if (in_array("2", $search_not_valid_only)) {
		$arrayfields['br.vdate']['checked'] = 1;
	}
	if (in_array("3", $search_not_valid_only)) {
		$arrayfields['br.record_type']['checked'] = 1;
	}

	$param = '&id=' . urlencode($id) . ($viewstatut != -1 ? '&viewstatut=' . urlencode($viewstatut) : '');
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
	if ($search_id_record) $param .= '&search_id_record=' . urlencode($search_id_record);
	if ($search_label) $param .= '&search_label=' . urlencode($search_label);
	if ($search_comment) $param .= '&search_comment=' . urlencode($search_comment);
	if ($search_category) $param .= '&search_category=' . urlencode($search_category);
	if ($search_record_type) $param .= '&search_record_type=' . urlencode($search_record_type);
	if ($search_original_country) $param .= '&search_original_country=' . urlencode($search_original_country);
	if ($search_original_amount) $param .= '&search_original_amount=' . urlencode($search_original_amount);
	if ($search_original_currency) $param .= '&search_original_currency=' . urlencode($search_original_currency);
	if ($search_commission) $param .= '&search_commission=' . urlencode($search_commission);
	if ($search_commission_currency) $param .= '&search_commission_currency=' . urlencode($search_commission_currency);
	if ($search_debit) $param .= '&search_debit=' . urlencode($search_debit);
	if ($search_credit) $param .= '&search_credit=' . urlencode($search_credit);
	if ($search_coming != -1) $param .= '&search_coming=' . urlencode($search_coming);
	if (dol_strlen($search_record_date_start) > 0) $param .= '&search_record_date_startmonth=' . GETPOST('search_record_date_startmonth', 'int') . '&search_record_date_startday=' . GETPOST('search_record_date_startday', 'int') . '&search_record_date_startyear=' . GETPOST('search_record_date_startyear', 'int');
	if (dol_strlen($search_record_date_end) > 0) $param .= '&search_record_date_endmonth=' . GETPOST('search_record_date_endmonth', 'int') . '&search_record_date_endday=' . GETPOST('search_record_date_endday', 'int') . '&search_record_date_endyear=' . GETPOST('search_record_date_endyear', 'int');
	if (dol_strlen($search_rdate_start) > 0) $param .= '&search_rdate_startmonth=' . GETPOST('search_rdate_startmonth', 'int') . '&search_rdate_startday=' . GETPOST('search_rdate_startday', 'int') . '&search_rdate_startyear=' . GETPOST('search_rdate_startyear', 'int');
	if (dol_strlen($search_rdate_end) > 0) $param .= '&search_rdate_endmonth=' . GETPOST('search_rdate_endmonth', 'int') . '&search_rdate_endday=' . GETPOST('search_rdate_endday', 'int') . '&search_rdate_endyear=' . GETPOST('search_rdate_endyear', 'int');
	if (dol_strlen($search_bdate_start) > 0) $param .= '&search_bdate_startmonth=' . GETPOST('search_bdate_startmonth', 'int') . '&search_bdate_startday=' . GETPOST('search_bdate_startday', 'int') . '&search_bdate_startyear=' . GETPOST('search_bdate_startyear', 'int');
	if (dol_strlen($search_bdate_end) > 0) $param .= '&search_bdate_endmonth=' . GETPOST('search_bdate_endmonth', 'int') . '&search_bdate_endday=' . GETPOST('search_bdate_endday', 'int') . '&search_bdate_endyear=' . GETPOST('search_bdate_endyear', 'int');
	if (dol_strlen($search_vdate_start) > 0) $param .= '&search_vdate_startmonth=' . GETPOST('search_vdate_startmonth', 'int') . '&search_vdate_startday=' . GETPOST('search_vdate_startday', 'int') . '&search_vdate_startyear=' . GETPOST('search_vdate_startyear', 'int');
	if (dol_strlen($search_vdate_end) > 0) $param .= '&search_vdate_endmonth=' . GETPOST('search_vdate_endmonth', 'int') . '&search_vdate_endday=' . GETPOST('search_vdate_endday', 'int') . '&search_vdate_endyear=' . GETPOST('search_vdate_endyear', 'int');
	if (dol_strlen($search_date_scraped_start) > 0) $param .= '&search_date_scraped_startmonth=' . GETPOST('search_date_scraped_startmonth', 'int') . '&search_date_scraped_startday=' . GETPOST('search_date_scraped_startday', 'int') . '&search_date_scraped_startyear=' . GETPOST('search_date_scraped_startyear', 'int');
	if (dol_strlen($search_date_scraped_end) > 0) $param .= '&search_date_scraped_endmonth=' . GETPOST('search_date_scraped_endmonth', 'int') . '&search_date_scraped_endday=' . GETPOST('search_date_scraped_endday', 'int') . '&search_date_scraped_endyear=' . GETPOST('search_date_scraped_endyear', 'int');
	if (dol_strlen($search_deleted_date_start) > 0) $param .= '&search_deleted_date_startmonth=' . GETPOST('search_deleted_date_startmonth', 'int') . '&search_deleted_date_startday=' . GETPOST('search_deleted_date_startday', 'int') . '&search_deleted_date_startyear=' . GETPOST('search_deleted_date_startyear', 'int');
	if (dol_strlen($search_deleted_date_end) > 0) $param .= '&search_deleted_date_endmonth=' . GETPOST('search_deleted_date_endmonth', 'int') . '&search_deleted_date_endday=' . GETPOST('search_deleted_date_endday', 'int') . '&search_deleted_date_endyear=' . GETPOST('search_deleted_date_endyear', 'int');
	if (dol_strlen($search_last_update_date_start) > 0) $param .= '&search_last_update_date_startmonth=' . GETPOST('search_last_update_date_startmonth', 'int') . '&search_last_update_date_startday=' . GETPOST('search_last_update_date_startday', 'int') . '&search_last_update_date_startyear=' . GETPOST('search_last_update_date_startyear', 'int');
	if (dol_strlen($search_last_update_date_end) > 0) $param .= '&search_last_update_date_endmonth=' . GETPOST('search_last_update_date_endmonth', 'int') . '&search_last_update_date_endday=' . GETPOST('search_last_update_date_endday', 'int') . '&search_last_update_date_endyear=' . GETPOST('search_last_update_date_endyear', 'int');
	if (dol_strlen($search_reconcile_date_start) > 0) $param .= '&search_reconcile_date_startmonth=' . GETPOST('search_reconcile_date_startmonth', 'int') . '&search_reconcile_date_startday=' . GETPOST('search_reconcile_date_startday', 'int') . '&search_reconcile_date_startyear=' . GETPOST('search_reconcile_date_startyear', 'int');
	if (dol_strlen($search_reconcile_date_end) > 0) $param .= '&search_reconcile_date_endmonth=' . GETPOST('search_reconcile_date_endmonth', 'int') . '&search_reconcile_date_endday=' . GETPOST('search_reconcile_date_endday', 'int') . '&search_reconcile_date_endyear=' . GETPOST('search_reconcile_date_endyear', 'int');
	if (dol_strlen($search_tms_start) > 0) $param .= '&search_tms_startmonth=' . GETPOST('search_tms_startmonth', 'int') . '&search_tms_startday=' . GETPOST('search_tms_startday', 'int') . '&search_tms_startyear=' . GETPOST('search_tms_startyear', 'int');
	if (dol_strlen($search_tms_end) > 0) $param .= '&search_tms_endmonth=' . GETPOST('search_tms_endmonth', 'int') . '&search_tms_endday=' . GETPOST('search_tms_endday', 'int') . '&search_tms_endyear=' . GETPOST('search_tms_endyear', 'int');
	if ($search_bank_list) $param .= "&search_bank_list=" . urlencode($search_bank_list);
	if ($search_num_releve) $param .= "&search_num_releve=" . urlencode($search_num_releve);
	if ($search_datas) $param .= "&search_datas=" . urlencode($search_datas);
	if ($search_import_key) $param .= "&search_import_key=" . urlencode($search_import_key);
	foreach ($search_not_valid_only as $v) {
		$param .= "&search_not_valid_only[]=" . urlencode($v);
	}
	if ($search_duplicate_only) $param .= "&search_duplicate_only=" . urlencode($search_duplicate_only);
	if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);

	// Url de base for form confirm add button, ...
	$base_url = $_SERVER['PHP_SELF'] . '?restore_lastsearch_values=1' . $param;
	if ($sortfield) $base_url .= '&sortfield=' . urlencode($sortfield);
	if ($sortorder) $base_url .= '&sortorder=' . urlencode($sortorder);
	if ($page > 1) $base_url .= '&page=' . urlencode($page);

	$parameters = array();
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	$refresh_infos = array();

	if (empty($reshook)) {
		$error = 0;
		// Mass actions. Controls on number of lines checked.
		$maxformassaction = (empty($conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS) ? 1000 : $conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
		if (!empty($massaction) && count($toselect) < 1) {
			$error++;
			setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
		}
		if (!$error && is_array($toselect) && count($toselect) > $maxformassaction) {
			setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
			$error++;
		}

		if (!$error) {
			if ($action == 'confirm_b4d_refresh_bank_records' && $confirm == "yes" && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->refresh) {
				$date = $last_update_date;
				$first_date = null;
				if ($date <= 0) {
					$date = 0;
					$first_date = dol_mktime(0, 0, 0, GETPOST('last_updatemonth', 'int'), GETPOST('last_updateday', 'int'), GETPOST('last_updateyear', 'int'));
				}

				if (!empty($date) || isset($first_date)) {
					$result = $budgetinsight->closeRefreshBankRecords($id, null, true);
					if ($result > 0) {
						$process_box_infos = array(
							'title' => $langs->trans('Banking4DolibarrRefreshBankRecords'),
							'url' => dol_buildpath('/banking4dolibarr/ajax/refresh_bank_records.php', 1),
							'finished_url' => $base_url,
							'data' => array(
								'id' => $id,
								'first_date' => $first_date,
								'start_date' => $date,
							),
							'height' => 350,
						);
					} else {
						setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
					}
				} else {
					setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Banking4DolibarrStartDate')), 'errors');
					if ($last_update_date <= 0) $action = 'b4d_refresh_bank_records';
				}
			} elseif ($action == 'confirm_b4d_auto_reconcile_bank_records' && $confirm == "yes" && $object->clos == 0 && $user->rights->banque->consolidate) {
				$result = $budgetinsight->closeAutoLinkBankRecords($id, null, true);
				if ($result > 0) {
					$process_box_infos = array(
						'title' => $langs->trans('Banking4DolibarrAutoReconcileBankRecords'),
						'url' => dol_buildpath('/banking4dolibarr/ajax/auto_link_bank_records.php', 1),
						'finished_url' => $base_url,
						'data' => array(
							'id' => $id,
							'start_date' => $date,
						),
						'width' => 800,
						'height' => 300,
					);
				} else {
					setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
				}
			} elseif ($action == 'confirm_b4d_edit_line' && $object->clos == 0 && $confirm == "yes" && $line_id > 0 && $budgetinsight_bank_line->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED && $user->rights->banque->consolidate) {
				$budgetinsight_bank_line->oldcopy = clone $budgetinsight_bank_line;
				$budgetinsight_bank_line->note = GETPOST('note');
				$has_different_currency = !empty($budgetinsight_bank_line->original_currency) && $budgetinsight_bank_line->original_currency != $object->currency_code;
				if ($has_different_currency) $budgetinsight_bank_line->amount = GETPOST('amount', 'int');
				$result = $budgetinsight_bank_line->update($user);
				if ($result < 0) {
					setEventMessages($budgetinsight_bank_line->error, $budgetinsight_bank_line->errors, 'errors');
				} else {
					setEventMessage($langs->trans("Banking4DolibarrEditNoteSuccess", $nbok));
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_unlink' && $object->clos == 0 && $confirm == "yes" && $user->rights->banking4dolibarr->bank_records->unlink) {
				$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
				$nbok = 0;
				foreach ($toselect as $toselectid) {
					$result = $budgetinsightbankrecord->fetch($toselectid);
					if ($result > 0) {
						$result = $budgetinsightbankrecord->unreconcile($user);
						if ($result > 0) {
							$nbok++;
						}
					}
					if ($result < 0) {
						setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
						$error++;
						break;
					}
				}

				if (!$error) {
					setEventMessage($langs->trans("Banking4DolibarrUnlinkRecordsSuccess", $nbok));
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_delete' && $object->clos == 0 && $confirm == "yes" && $user->rights->banking4dolibarr->bank_records->supprimer) {
				$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
				$nbok = 0;
				foreach ($toselect as $toselectid) {
					$result = $budgetinsightbankrecord->fetch($toselectid);
					if ($result > 0) {
						$result = $budgetinsightbankrecord->delete($user);
						if ($result > 0) {
							$nbok++;
						}
					}
					if ($result < 0) {
						setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
						$error++;
						break;
					}
				}

				if (!$error) {
					setEventMessage($langs->trans("Banking4DolibarrDeleteRecordsSuccess", $nbok));
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_discard' && $object->clos == 0 && $confirm == "yes" && $user->rights->banking4dolibarr->bank_records->discard) {
				$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
				$nbok = 0;
				foreach ($toselect as $toselectid) {
					$result = $budgetinsightbankrecord->fetch($toselectid);
					if ($result > 0) {
						$result = $budgetinsightbankrecord->discard($user);
						if ($result > 0) {
							$nbok++;
						}
					}
					if ($result < 0) {
						setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
						$error++;
						break;
					}
				}

				if (!$error) {
					setEventMessage($langs->trans("Banking4DolibarrDiscardRecordsSuccess", $nbok));
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_undiscard' && $object->clos == 0 && $confirm == "yes" && $user->rights->banking4dolibarr->bank_records->undiscard) {
				$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
				$nbok = 0;
				foreach ($toselect as $toselectid) {
					$result = $budgetinsightbankrecord->fetch($toselectid);
					if ($result > 0) {
						$result = $budgetinsightbankrecord->undiscard($user);
						if ($result > 0) {
							$nbok++;
						}
					}
					if ($result < 0) {
						setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
						$error++;
						break;
					}
				}

				if (!$error) {
					setEventMessage($langs->trans("Banking4DolibarrUndiscardRecordsSuccess", $nbok));
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_fix_dates' && $object->clos == 0 && $confirm == "yes" && $user->rights->banking4dolibarr->bank_records->fix_lines) {
				$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
				$nbok = 0;
				foreach ($toselect as $toselectid) {
					$result = $budgetinsightbankrecord->fetch($toselectid);
					if ($result > 0) {
						$result = $budgetinsightbankrecord->is_broken_down();
						if ($result == 1) {
							setEventMessage($langs->trans("Banking4DolibarrWarningCannotCorrectDatesOnBankRecordBrokenDown", $budgetinsightbankrecord->id_record), 'warnings');
						} elseif ($result == 0) {
							$result = $budgetinsightbankrecord->fixBankLine($user, 1);
						}
						if ($result > 0) {
							$nbok++;
						}
					}
					if ($result < 0) {
						setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
						$error++;
						break;
					}
				}

				if (!$error) {
					setEventMessage($langs->trans("Banking4DolibarrFixDatesRecordsSuccess", $nbok));
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_fix_payment_types' && $object->clos == 0 && $confirm == "yes" && $user->rights->banking4dolibarr->bank_records->fix_lines) {
				$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
				$nbok = 0;
				foreach ($toselect as $toselectid) {
					$result = $budgetinsightbankrecord->fetch($toselectid);
					if ($result > 0) {
						$result = $budgetinsightbankrecord->fixBankLine($user, 0, 1);
						if ($result > 0) {
							$nbok++;
						}
					}
					if ($result < 0) {
						setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
						$error++;
						break;
					}
				}

				if (!$error) {
					setEventMessage($langs->trans("Banking4DolibarrFixPaymentTypesRecordsSuccess", $nbok));
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_fix_dates_payment_types' && $object->clos == 0 && $confirm == "yes" && $user->rights->banking4dolibarr->bank_records->fix_lines) {
				$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
				$nbok = 0;
				foreach ($toselect as $toselectid) {
					$result = $budgetinsightbankrecord->fetch($toselectid);
					if ($result > 0) {
						$dates = 1;
						$result = $budgetinsightbankrecord->is_broken_down();
						if ($result == 1) {
							setEventMessage($langs->trans("Banking4DolibarrWarningCannotCorrectDatesOnBankRecordBrokenDown", $budgetinsightbankrecord->id_record), 'warnings');
							$dates = 0;
						}
						if ($result > 0) $result = $budgetinsightbankrecord->fixBankLine($user, $dates, 1);
						if ($result > 0) {
							$nbok++;
						}
					}
					if ($result < 0) {
						setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
						$error++;
						break;
					}
				}

				if (!$error) {
					setEventMessage($langs->trans("Banking4DolibarrFixDatesAndPaymentTypesRecordsSuccess", $nbok));
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_edit_statement_number' && $object->clos == 0 && $confirm == "yes" && $user->rights->banque->consolidate) {
				$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
				$statement_number = GETPOST('statement_number', 'int');

				if ($line_id > 0) {
					$result = $budgetinsightbankrecord->fetch($line_id);
					if ($result > 0) $result = $budgetinsightbankrecord->update_statement_number($user, $statement_number);
					if ($result < 0) {
						setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
						$error++;
					} elseif ($result > 0) {
						setEventMessage($langs->trans("Banking4DolibarrEditStatementNumberSuccess"));
					}
				} else {
					$nbok = 0;
					foreach ($toselect as $toselectid) {
						$result = $budgetinsightbankrecord->fetch($toselectid);
						if ($result > 0) $result = $budgetinsightbankrecord->update_statement_number($user, $statement_number);
						if ($result > 0) {
							$nbok++;
						} elseif ($result < 0) {
							setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
							$error++;
							break;
						}
					}

					if (!$error) {
						setEventMessage($langs->trans("Banking4DolibarrEditStatementNumberSuccess", $nbok));
					}
				}
				if ($error) {
					$action = 'b4d_edit_statement_number';
				} else {
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_create_various_payments_reconciled' && $object->clos == 0 && $confirm == "yes" && $user->rights->banque->consolidate) {
				// Load bank record categories from the dictionary
				$bank_record_categories_dictionary = Dictionary::getDictionary($db, 'banking4dolibarr', 'banking4dolibarrbankrecordcategories');
				$result = $bank_record_categories_dictionary->fetch_lines();
				if ($result < 0) {
					setEventMessages($bank_record_categories_dictionary->error, $bank_record_categories_dictionary->errors, 'errors');
					$error++;
				}

				if (!$error) {
					$label = GETPOST('label', 'alpha');
					$selected_payment_mode = GETPOST('selectpayment_mode', 'int');
					$payment_mode_overwrite = GETPOST('payment_mode_overwrite', 'int');
					$num_payment = GETPOST('num_payment', 'alpha');
					$projectid = GETPOST('projectid', 'int');
					$selected_category_transaction = GETPOST('category_transaction', 'alpha');
					$category_transaction_overwrite = GETPOST('category_transaction_overwrite', 'int');
					$selected_accountancy_code = GETPOST('accountancy_code', 'alpha');
					$accountancy_code_overwrite = GETPOST('accountancy_code_overwrite', 'int');
					$subledger_account = GETPOST('subledger_account', 'alpha');

					$label = trim($label);
					$selected_payment_mode = $selected_payment_mode > 0 ? $selected_payment_mode : 0;
					$num_payment = trim($num_payment);
					$projectid = $projectid > 0 ? $projectid : 0;
					$selected_category_transaction = $selected_category_transaction > 0 ? $selected_category_transaction : 0;
					$selected_accountancy_code = $selected_accountancy_code != -1 ? $selected_accountancy_code : "";
					$subledger_account = $subledger_account != -1 ? $subledger_account : "";

					$nbok = 0;
					$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
					foreach ($toselect as $toselectid) {
						$result = $budgetinsightbankrecord->fetch($toselectid, '', 0, 0, 0, 1);
						if ($result > 0 && $budgetinsightbankrecord->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED) {
							$record_payment_mode = $budgetinsightbankrecord->getDolibarrPaymentModeId($budgetinsightbankrecord->record_type, 1);
							$record_category_transaction = 0;
							$record_accountancy_code = "";
							if ($budgetinsightbankrecord->id_category > 0) {
								$category_info = $bank_record_categories_dictionary->lines[$budgetinsightbankrecord->id_category]->fields;
								$record_category_transaction = !empty($category_info['category']) ? $category_info['category'] : 0;
								$record_accountancy_code = !empty($category_info['accountancy_code']) ? $category_info['accountancy_code'] : "";
							}

							$label = !empty($label) ? $label : ($budgetinsightbankrecord->label . (!empty($budgetinsightbankrecord->comment) ? ' - ' . $budgetinsightbankrecord->comment : ''));
							$datep = $budgetinsightbankrecord->record_date;
							$datev = empty($budgetinsightbankrecord->vdate) ? $budgetinsightbankrecord->record_date : $budgetinsightbankrecord->vdate;
							$amount = $budgetinsightbankrecord->remaining_amount_to_link;
							$operation = $record_payment_mode > 0 && empty($payment_mode_overwrite) ? $record_payment_mode : $selected_payment_mode;
							$category_transaction = $record_category_transaction > 0 && empty($category_transaction_overwrite) ? $record_category_transaction : $selected_category_transaction;
							$accountancy_code = !empty($record_accountancy_code) && empty($accountancy_code_overwrite) ? $record_accountancy_code : $selected_accountancy_code;

							if (empty($label)) {
								setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), 'errors');
								$error++;
							}
							if (empty($datep) || empty($datev)) {
								setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), 'errors');
								$error++;
							}
							if (empty($operation)) {
								setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), 'errors');
								$error++;
							}
							if (empty($amount)) {
								setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), 'errors');
								$error++;
							}
							if (!empty($conf->accounting->enabled) && empty($accountancy_code)) {
								$langs->load('accountancy');
								setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountAccounting")), 'errors');
								$error++;
							}

							if (!$error) {
								$db->begin();

								if ($use_various_payment_card) {
									require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/paymentvarious.class.php';
									$paymentvarious = new PaymentVarious($db);
									$paymentvarious->ref = '';
									$paymentvarious->accountid = $object->id;
									$paymentvarious->datep = $datep;
									$paymentvarious->datev = $datev;
									$paymentvarious->amount = abs($amount);
									$paymentvarious->label = $label;
									//$paymentvarious->note=GETPOST("note",'none');
									$paymentvarious->type_payment = $operation;
									$paymentvarious->num_payment = $num_payment;
									$paymentvarious->fk_user_author = $user->id;
									$paymentvarious->sens = $amount >= 0 ? 1 : 0;
									$paymentvarious->fk_project = $projectid;
									$paymentvarious->category_transaction = $category_transaction;
									$paymentvarious->accountancy_code = $accountancy_code;
									$paymentvarious->subledger_account = $subledger_account;

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
									$bank_id = $object->addline($datep, $operation, $label, $amount, $num_payment, $category_transaction, $user, '', '', $accountancy_code);
									if ($bank_id < 0) {
										setEventMessages($object->error, $object->errors, 'errors');
										$error++;
									}
								}

								if (!$error) {
									$statement_number = $budgetinsight->getStatementNumberFromDate($budgetinsightbankrecord->record_date);
									$result = $budgetinsightbankrecord->reconcile($user, $statement_number, $bank_id, 1);
									if ($result < 0) {
										setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
										$error++;
									}
								}

								if (!$error) {
									$nbok++;
									$db->commit();
								} else {
									$db->rollback();
									break;
								}
							}
						} elseif ($result < 0) {
							setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
							$error++;
							break;
						}
					}
				}

				if (!$error) {
					setEventMessage($langs->trans("Banking4DolibarrCreateReconciledVariousPaymentsSuccess", $nbok));
				} elseif ($budgetinsightbankrecord->id > 0) {
					setEventMessage($langs->trans("Banking4DolibarrErrorWhenCreateReconciledVariousPayments", $budgetinsightbankrecord->id_record), 'errors');
				}

				if ($error) {
					$action = 'b4d_create_various_payments_reconciled';
				} else {
					$action = '';
				}
			} elseif ($action == 'confirm_b4d_fix_duplicate' && $object->clos == 0 && $confirm == "yes" && $user->rights->banking4dolibarr->bank_records->fix_duplicate) {
				$duplicate_as_one = GETPOST('duplicate_as_one', 'alpha');

				$result = $budgetinsight->fixDuplicateRecords($user, $toselect, !empty($duplicate_as_one));
				if ($result < 0) {
					setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
					$action = 'b4d_fix_duplicate';
				} else {
					setEventMessage($langs->trans("Banking4DolibarrFixDuplicateRecordsSuccess", $result));
				}
			}
		}
	}
}
if ((empty($action) && empty($massaction))) $toselect = array();


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formbankink4dolibarr = new FormBanking4Dolibarr($db);
$formdictionary = new FormDictionary($db);
$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
$formaccounting = new FormAccounting($db);

$help_url='EN:Banking4Dolibarr_EN|FR:Banking4Dolibarr_FR|ES:Banking4Dolibarr_ES';
llxHeader('', $langs->trans('FinancialAccount') . ' - ' . $langs->trans('Banking4DolibarrBankRecords'), $help_url, '', 0, 0, array(
    '/banking4dolibarr/js/banking4dolibarr.js.php',
), array(
    '/banking4dolibarr/css/banking4dolibarr.css.php',
));

// Bank card
//--------------------------------------------------------
$head=bank_prepare_head($object);
dol_fiche_head($head,'b4d_bank_records', $langs->trans("FinancialAccount"), 0, 'account');

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
$morehtmlref = '';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border" width="100%">';

print '<tr><td class="titlefield">' . $langs->trans("Banking4DolibarrBankAccountBalance") . '</td><td>' . $extrafields->showOutputField('b4d_account_balance', $object->array_options['options_b4d_account_balance']) . '</td></tr>';
print '<tr><td>' . $langs->trans("Banking4DolibarrBankAccountUpdateDate") . '</td><td>' . $extrafields->showOutputField('b4d_account_update_date', $object->array_options['options_b4d_account_update_date']) . '</td></tr>';

print "</table>\n";

print '</div>';

dol_fiche_end();

if ($remote_bank_account_id != 0) {
	// Process box
	//--------------------------------------------------------
	if (!empty($process_box_infos)) {
		$process_box = $formbankink4dolibarr->processBox($process_box_infos['url'], $process_box_infos['title'], $process_box_infos['data'], $process_box_infos['finished_url'],
			$process_box_infos['text'], $process_box_infos['initial_status_text'], $process_box_infos['height'], $process_box_infos['width']);

		$parameters = array('base_url' => $base_url);
		$reshook = $hookmanager->executeHooks('processBox', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $process_box .= $hookmanager->resPrint;
		elseif ($reshook > 0) $process_box = $hookmanager->resPrint;
		print $process_box;
	}

	// Form Confirm
	//--------------------------------------------------------
	$formconfirm = '';
	if (!empty($toselect)) $_SESSION['b4dbankrecordslist_toselect_' . $id] = $toselect;

	// Confirm refresh
	if ($action == 'b4d_refresh_bank_records' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->refresh && !empty($conf->global->BANKING4DOLIBARR_MODULE_KEY) && $remote_bank_account_id > 0) {
		$height = 200;
		$width = 500;
		$formquestion = array();
		if (!($last_update_date > 0)) {
			$width = 550;
			$date = dol_mktime(0, 0, 0, GETPOST('last_updatemonth', 'int'), GETPOST('last_updateday', 'int'), GETPOST('last_updateyear', 'int'));
			$formquestion[] = array('type' => 'date', 'name' => 'last_update', 'label' => $langs->trans("Banking4DolibarrStartDate"), 'value' => $date > 0 ? $date : '');
		}
		if ($budgetinsight->isRefreshBankRecordsStarted($id) > 0) {
			$height = 250 + (!($last_update_date > 0) ? 30 : 0);
			$width = 550;
			$formquestion['text'] = '<span style="color: red;">' . $langs->trans('Banking4DolibarrWarningRefreshBankRecordsAlreadyStarted') . '</span>';
		}
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrRefreshBankRecords'), $langs->trans("Banking4DolibarrConfirmRefreshBankRecords"), 'confirm_b4d_refresh_bank_records', $formquestion, 0, 1, $height, $width);
	} elseif ($action == 'b4d_auto_reconcile_bank_records' && $object->clos == 0 && $user->rights->banque->consolidate) {
		$height = 200;
		$width = 500;
		$formquestion = '';
		if ($budgetinsight->isAutoLinkBankRecordsStarted($id) > 0) {
			$height = 250;
			$width = 600;
			$formquestion = array('text' => '<span style="color: red;">' . $langs->trans('Banking4DolibarrWarningAutoReconcileBankRecordsAlreadyStarted') . '</span>');
		}
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrAutoReconcileBankRecords'), $langs->trans("Banking4DolibarrConfirmAutoReconcileBankRecords"), 'confirm_b4d_auto_reconcile_bank_records', $formquestion, 0, 1, $height, $width);
	} elseif ($action == 'b4d_edit_line' && $object->clos == 0 && $budgetinsight_bank_line->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED && $user->rights->banque->consolidate) {
		$height = 410;

		require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
		$doleditor = new DolEditor('note', (!empty($budgetinsight_bank_line->note) ? $budgetinsight_bank_line->note : ''), '', 180, 'dolibarr_notes', 'In', false, true, $conf->fckeditor->enabled, ROWS_5, '90%');
		$formquestion = array(array('type' => 'onecolumn', 'name' => 'note', 'value' => $doleditor->Create(1, !empty($conf->fckeditor->enabled) ? ".on('change', function(e) { $('textarea#note').val(this.getData()); });" : '')));

		$has_different_currency = !empty($budgetinsight_bank_line->original_currency) && $budgetinsight_bank_line->original_currency != $object->currency_code;
		if ($has_different_currency) {
			$height = 450;
			$formquestion = array(
				array('type' => 'text', 'name' => 'amount', 'label' => $langs->trans('Amount'), 'value' => $budgetinsight_bank_line->amount),
				array('type' => 'onecolumn', 'name' => 'note', 'value' => $langs->trans('Note') . ' :<br>' . $doleditor->Create(1, !empty($conf->fckeditor->enabled) ? ".on('change', function(e) { $('textarea#note').val(this.getData()); });" : ''))
			);
		} else {
			$formquestion = array(array('type' => 'onecolumn', 'name' => 'note', 'value' => $doleditor->Create(1, !empty($conf->fckeditor->enabled) ? ".on('change', function(e) { $('textarea#note').val(this.getData()); });" : '')));
		}

		$formconfirm = $form->formconfirm($base_url . '&line_id=' . $line_id, $langs->trans($has_different_currency ? 'Banking4DolibarrEditLine' : 'Banking4DolibarrEditNote'), $langs->trans($has_different_currency ? "Banking4DolibarrConfirmEditLine" : "Banking4DolibarrConfirmEditNote"), 'confirm_b4d_edit_line', $formquestion, 0, 1, $height, 800);
	} elseif ($massaction == 'b4d_delete' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->supprimer) {
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrDeleteRecords'), $langs->trans("Banking4DolibarrConfirmDeleteRecords"), 'confirm_b4d_delete', '', 0, 1);
	} elseif ($massaction == 'b4d_unlink' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->unlink) {
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrUnlinkRecords'), $langs->trans("Banking4DolibarrConfirmUnlinkRecords"), 'confirm_b4d_unlink', '', 0, 1);
	} elseif ($massaction == 'b4d_discard' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->discard) {
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrDiscardRecords'), $langs->trans("Banking4DolibarrConfirmDiscardRecords"), 'confirm_b4d_discard', '', 0, 1);
	} elseif ($massaction == 'b4d_undiscard' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->undiscard) {
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrUndiscardRecords'), $langs->trans("Banking4DolibarrConfirmUndiscardRecords"), 'confirm_b4d_undiscard', '', 0, 1);
	} elseif ($massaction == 'b4d_fix_dates' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->fix_lines) {
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrFixDatesRecords'), $langs->trans("Banking4DolibarrConfirmFixDatesRecords"), 'confirm_b4d_fix_dates', '', 0, 1);
	} elseif ($massaction == 'b4d_fix_payment_types' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->fix_lines) {
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrFixPaymentTypesRecords'), $langs->trans("Banking4DolibarrConfirmFixPaymentTypesRecords"), 'confirm_b4d_fix_payment_types', '', 0, 1);
	} elseif ($massaction == 'b4d_fix_dates_payment_types' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->fix_lines) {
		$formconfirm = $form->formconfirm($base_url, $langs->trans('Banking4DolibarrFixDatesAndPaymentTypesRecords'), $langs->trans("Banking4DolibarrConfirmFixDatesAndPaymentTypesRecords"), 'confirm_b4d_fix_dates_payment_types', '', 0, 1);
	} elseif (($action == 'b4d_edit_statement_number' || $massaction == 'b4d_edit_statement_number') && $object->clos == 0 && $user->rights->banque->consolidate) {
		$statement_number = GETPOST('statement_number', 'int');

		$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
		if ($line_id > 0) {
			$result = $budgetinsightbankrecord->fetch($line_id);
			if ($result < 0) {
				setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
			} elseif ($result > 0) {
				$statement_number = $budgetinsightbankrecord->get_statement_number();
				if ($statement_number < 0) {
					setEventMessages($budgetinsightbankrecord->error, $budgetinsightbankrecord->errors, 'errors');
					$statement_number = '';
				}
			}
		}

		$formquestion = array(
			array('type' => 'text', 'name' => 'statement_number', 'label' => $langs->trans("AccountStatement"), 'value' => $statement_number),
		);
		$formconfirm = $form->formconfirm($base_url . '&line_id=' . $line_id, $langs->trans('Banking4DolibarrEditStatementNumber'), $langs->trans($line_id > 0 ? "Banking4DolibarrConfirmEditStatementNumber" : "Banking4DolibarrConfirmEditStatementNumbers"), 'confirm_b4d_edit_statement_number', $formquestion, 0, 1);
	} elseif ($massaction == 'b4d_create_various_payments_reconciled' && $object->clos == 0 && $user->rights->banque->consolidate) {
		$label = GETPOST('label', 'alpha');
		$payment_mode = GETPOST('selectpayment_mode', 'int');
		$payment_mode_overwrite = GETPOST('payment_mode_overwrite', 'int');
		$num_payment = GETPOST('num_payment', 'alpha');
		$projectid = GETPOST('projectid','int');
		$category_transaction = GETPOST('category_transaction', 'alpha');
		$category_transaction_overwrite = GETPOST('category_transaction_overwrite', 'int');
		$accountancy_code = GETPOST('accountancy_code', 'alpha');
		$accountancy_code_overwrite = GETPOST('accountancy_code_overwrite', 'int');
		$subledger_account = GETPOST('subledger_account','alpha');

		$default_value_help = $form->textwithpicto('', $langs->trans('Banking4DolibarrDefaultValueHelp'));
		$set_value_help = $form->textwithpicto('', $langs->trans('Banking4DolibarrSetValueHelp'));

		if (!empty($conf->accounting->enabled)) {
			$input_accountancy_code = $formaccounting->select_account($accountancy_code, 'accountancy_code', 1, null, 1, 1, ' minwidth400 maxwidth400 maxwidthonsmartphone');
		} else { // For external software
			$input_accountancy_code = '<input class="minwidth100" id="accountancy_code" name="accountancy_code" value="' . dol_escape_htmltag($accountancy_code) . '">';
		}

		ob_start();
		$form->select_types_paiements($payment_mode, 'payment_mode', '', 0, 1);
		$input_payment_mode = ob_get_contents();
		ob_end_clean();

		// Load bank groups
		require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/bankcateg.class.php';
		$bankcateg = new BankCateg($db);
		$options = array();
		foreach ($bankcateg->fetchAll() as $bankcategory) {
			$options[$bankcategory->id] = $bankcategory->label;
		}

		$formquestion = array(
			array('type' => 'text', 'name' => 'label', 'label' => $langs->trans('Label') . ' ' . $set_value_help, 'value' => $label),
			array('type' => 'other', 'name' => 'selectpayment_mode', 'label' => '<span class="nowrap">' . $langs->trans('Type') . ' ' . $default_value_help .
				'<div style="float: right; position: relative;"><input type="checkbox" id="payment_mode_overwrite" name="payment_mode_overwrite" value="1" ' . ($payment_mode_overwrite ? ' selected' : '') . '></div></span>',
				'value' => $input_payment_mode),
			array('name' => 'payment_mode_overwrite'),
			array('type' => 'text', 'name' => 'num_payment', 'label' => $langs->trans('Numero') . ' <em>(' . $langs->trans("ChequeOrTransferNumber") . ')</em> ' . $set_value_help, 'value' => $num_payment),
		);
		// Project
		if (!empty($conf->projet->enabled) && $use_various_payment_card) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
			$formproject = new FormProjets($db);
			$langs->load("projects");
			ob_start();
			$numproject = $formproject->select_projects(-1, $projectid, 'projectid', 0, 0, 1, 1);
			$input_project = ob_get_contents();
			ob_end_clean();
			$formquestion = array_merge($formquestion, array(
				array('type' => 'other', 'name' => 'projectid', 'label' => $langs->trans("Project") . ' ' . $set_value_help, 'value' =>$input_project),
			));
		}
		// Category
		if (is_array($options) && count($options) && $conf->categorie->enabled) {
			$input_category_transaction = $form->selectarray('category_transaction', $options, $category_transaction, 1);
			$formquestion = array_merge($formquestion, array(
				array('type' => 'other', 'name' => 'category_transaction', 'label' => '<span class="nowrap">' . $langs->trans("RubriquesTransactions") . ' ' . $default_value_help .
					'<div style="float: right; position: relative;"><input type="checkbox" id="category_transaction_overwrite" name="category_transaction_overwrite" value="1" ' . ($category_transaction_overwrite ? ' selected' : '') . '></div></span>',
					'value' =>$input_category_transaction),
				array('name' => 'category_transaction_overwrite'),
			));
		}
		$formquestion = array_merge($formquestion, array(
			array('type' => 'other', 'name' => 'accountancy_code', 'label' => '<span class="nowrap">' . $langs->trans('AccountAccounting') . ' ' . $default_value_help .
				'<div style="float: right; position: relative;"><input type="checkbox" id="accountancy_code_overwrite" name="accountancy_code_overwrite" value="1" ' . ($accountancy_code_overwrite ? ' selected' : '') . '></div></span>',
				'value' => $input_accountancy_code),
			array('name' => 'accountancy_code_overwrite'),
		));
		// Subledger account
		if ($use_various_payment_card && version_compare(DOL_VERSION, "10.0.0") >= 0) {
			if (!empty($conf->accounting->enabled)) {
				if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX)) {
					$input_subledger_account = $formaccounting->select_auxaccount($subledger_account, 'subledger_account', 1, 'minwidth400 maxwidth400 maxwidthonsmartphone');
				} else {
					$input_subledger_account = '<input type="text" class="maxwidth200" name="subledger_account" value="' . dol_escape_htmltag($subledger_account) . '">';
				}
			} else { // For external software
				$input_subledger_account = '<input class="minwidth100" name="subledger_account" value="' . dol_escape_htmltag($subledger_account) . '">';
			}
			$formquestion = array_merge($formquestion, array(
				array('type' => 'other', 'name' => 'subledger_account', 'label' => $langs->trans("SubledgerAccount") . ' ' . $set_value_help, 'value' =>$input_subledger_account),
			));
		}
		$formconfirm = $form->formconfirm($base_url . '&line_id=' . $line_id, $langs->trans('Banking4DolibarrCreateReconciledVariousPayments'), $langs->trans("Banking4DolibarrConfirmCreateReconciledVariousPayments"), 'confirm_b4d_create_various_payments_reconciled', $formquestion, 0, 1, 350, 800);
	} elseif ($massaction == 'b4d_fix_duplicate' && $object->clos == 0 && $user->rights->banking4dolibarr->bank_records->fix_duplicate) {
		$duplicate_as_one = GETPOST('duplicate_as_one', 'alpha');
		$formquestion = array(array('type' => 'checkbox', 'name' => 'duplicate_as_one', 'label' => $langs->trans('Banking4DolibarrFixDuplicateRecordsAsOne'), 'value' => !empty($duplicate_as_one)));
		$formconfirm = $form->formconfirm($base_url . '&line_id=' . $line_id, $langs->trans('Banking4DolibarrFixDuplicateRecords'), $langs->trans("Banking4DolibarrConfirmFixDuplicateRecords"), 'confirm_b4d_fix_duplicate', $formquestion, 0, 1, 200, 600);
	}

	$parameters = array('base_url' => $base_url);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;
	print $formconfirm;

	// Actions buttons
	//--------------------------------------------------------
	print '<div class="tabsAction">';
	$parameters = array('base_url' => $base_url);
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		if ($object->clos == 0 && $user->rights->banking4dolibarr->bank_records->refresh) {
			if (!empty($conf->global->BANKING4DOLIBARR_MODULE_KEY)) {
				if ($remote_bank_account_id > 0) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $base_url . '&action=b4d_refresh_bank_records" >' .
						'<span class="' . $class_fonts_awesome . ' fa-download paddingleftonly">&nbsp;</span>' .
						$langs->trans('Banking4DolibarrRefreshBankRecords') . '</a></div>';
				} else {
					$title_button = $langs->trans('Banking4DolibarrDisabledBecauseIsNotLinkToBudgetInsight');
					print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip"  href="#" title="' . dol_escape_htmltag($title_button) . '">' .
						'<span class="' . $class_fonts_awesome . ' fa-download paddingleftonly">&nbsp;</span>' .
						$langs->trans('Banking4DolibarrRefreshBankRecords') . '</a></div>';
				}
			} else {
				$title_button = $langs->trans('Banking4DolibarrDisabledBecauseYouDontHaveKey');
				print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip"  href="#" title="' . dol_escape_htmltag($title_button) . '">' .
					'<span class="' . $class_fonts_awesome . ' fa-download paddingleftonly">&nbsp;</span>' .
					$langs->trans('Banking4DolibarrRefreshBankRecords') . '</a></div>';
			}
		}
		if ($object->clos == 0 && $user->rights->banque->consolidate && $budgetinsight->hasUnReconliledRecords($id)) {
            $icon_autoreconcile = (!empty($conf->global->EASYA_VERSION) || version_compare(DOL_VERSION, "11.0.0") >= 0) ? 'fa-magic' : 'fa-bolt';
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $base_url . '&action=b4d_auto_reconcile_bank_records" >' .
				'<span class="' . $class_fonts_awesome . ' ' . $icon_autoreconcile . ' paddingleftonly">&nbsp;</span>' .
				$langs->trans('Banking4DolibarrAutoReconcileBankRecords') . '</a></div>';
		}
	}
	print '</div>';

	// List
	//--------------------------------------------------------
	$sql = "SELECT ";
	$sql .= " br.rowid, br.id_record, br.id_account, " . $db->ifsql("brlu.nb IS NULL", "0", "brlu.nb") . " as nb_bank_linked,";
	$sql .= " br.label, br.comment, br.note, CONCAT(" . $db->ifsql("cb4dbrsc.rowid IS NULL", "''", "CONCAT(cb4dbrsc.label, ' - ')") . ", cb4dbrc.label) AS category_label, br.record_type, br.original_country, br.original_amount, br.original_currency,";
	$sql .= " br.commission, br.commission_currency, br.amount, br.coming, br.deleted_date, br.record_date, br.rdate, br.bdate,";
	$sql .= " br.vdate, br.date_scraped, br.last_update_date, br.reconcile_date, br.datas, br.tms, br.status, br.import_key,";
	$sql .= " SUM(" . $db->ifsql("br.record_date != b.dateo", "1", "0") . ") AS wrong_odate,";
	$sql .= " SUM(" . $db->ifsql($db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate") . " != b.datev", "1", "0") . ") AS wrong_vdate,";
	$sql .= " SUM(" . $db->ifsql($db->ifsql("cb4dbrt.mode_reglement = ''", "b.fk_type", "cb4dbrt.mode_reglement") . " != b.fk_type", "1", "0") . ") AS wrong_payment_type,";
	$sql .= " bl.bank_list, b.num_releve,";
	$sql .= " " . $db->ifsql("brds.fk_duplicate_of IS NULL", "0", "1") . " AS duplicated_line";
	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record as br';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_account as cb4dba ON cb4dba.rowid = br.id_account';
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_record_type AS cb4dbrt ON cb4dbrt.code = br.record_type";
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON ba.rowid = cb4dba.fk_bank_account';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_record_category as cb4dbrc ON cb4dbrc.rowid = br.id_category';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_record_category as cb4dbrsc ON cb4dbrsc.rowid = cb4dbrc.id_parent_category';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl ON brl.fk_bank_record = br.rowid';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON b.rowid = brl.fk_bank';
	$sql .= " LEFT JOIN (";
	if ($db->type == 'pgsql') {
		$sql .= "   SELECT fk_bank_record, STRING_AGG(fk_bank::TEXT, '|') AS bank_list";
	} else {
		$sql .= "   SELECT fk_bank_record, GROUP_CONCAT(fk_bank ORDER BY fk_bank SEPARATOR '|') AS bank_list";
	}
	$sql .= "   FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link";
	$sql .= "   GROUP BY fk_bank_record";
	$sql .= " ) bl ON bl.fk_bank_record = br.rowid";
	$sql .= ' LEFT JOIN (';
	$sql .= '   SELECT brl.fk_bank, count(DISTINCT brl.fk_bank_record) as nb';
	$sql .= '   FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl';
	$sql .= '   GROUP BY brl.fk_bank';
	$sql .= ' ) as brlu ON brlu.fk_bank = brl.fk_bank';
	$sql .= ' LEFT JOIN (';
	$sql .= '   SELECT fk_duplicate_of';
	$sql .= '   FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record';
	$sql .= '   WHERE fk_duplicate_of IS NOT NULL';
	$sql .= '   GROUP BY fk_duplicate_of';
	$sql .= ' ) as brds ON brds.fk_duplicate_of = br.rowid';
	if ($search_duplicate_only) {
		$duplicate_test_on_fields = !empty($conf->global->BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS) ? explode(',', $conf->global->BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS) : array('label', 'record_date', 'vdate', 'amount');
		$duplicate_fields = array();
		$duplicate_filters = array();
		$duplicate_on_filters = array();
		foreach ($duplicate_test_on_fields as $field) {
			if ($field == "vdate") {
				$duplicate_fields[] = $db->ifsql("br0.vdate IS NULL", "br0.record_date", "br0.vdate") . " AS vdate";
				$duplicate_filters[] = $db->ifsql("br0.vdate IS NULL", "br0.record_date", "br0.vdate");
				$duplicate_on_filters[] = "brd.vdate = " . $db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate");
			} else {
				$duplicate_fields[] = 'br0.' . $field;
				$duplicate_filters[] = 'br0.' . $field;
				$duplicate_on_filters[] = 'brd.' . $field . ' = br.' . $field;
			}
		}

		$sql .= ' LEFT JOIN (';
		$sql .= '   SELECT COUNT(*) AS nb, ' . implode(', ', $duplicate_fields);
		$sql .= '   FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record as br0';
		$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_account as cb4dba ON cb4dba.rowid = br0.id_account';
		$sql .= '   WHERE br0.status != ' . BudgetInsightBankRecord::BANK_RECORD_STATUS_DUPLICATE;
		$sql .= '   AND cb4dba.fk_bank_account = ' . $id;
		$sql .= '   GROUP BY ' . implode(', ', $duplicate_filters);
		$sql .= '   HAVING COUNT(*) > 1';
		$sql .= '   AND MIN(br0.status) = ' . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED;
		$sql .= ' ) as brd ON ' . implode(' AND ', $duplicate_on_filters);
	}
	// Add join from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= ' WHERE ba.entity IN (' . getEntity('bank_account') . ')';
	$sql .= ' AND ba.rowid = ' . $id;
	$sql .= ' AND br.fk_duplicate_of IS NULL';
	if ($viewstatut == BudgetInsightBankRecord::BANK_RECORD_STATUS_DUPLICATE) $sql .= ' AND brds.fk_duplicate_of IS NOT NULL';
	elseif ($viewstatut != '-1') $sql .= ' AND br.status = ' . $viewstatut;
	if ($search_id_record) $sql .= natural_search('br.id_record', $search_id_record);
	if ($search_label) $sql .= natural_search(array('br.label', 'br.note'), $search_label);
	if ($search_comment) $sql .= natural_search('br.comment', $search_comment);
	if ($search_category) $sql .= natural_search("cb4dbrc.label", $search_category);
	if (dol_strlen($search_record_date_start) > 0) $sql .= " AND br.record_date >= '" . $db->idate($search_record_date_start) . "'";
	if (dol_strlen($search_record_date_end) > 0) $sql .= " AND br.record_date <= '" . $db->idate($search_record_date_end) . "'";
	if (dol_strlen($search_rdate_start) > 0) $sql .= " AND br.rdate >= '" . $db->idate($search_rdate_start) . "'";
	if (dol_strlen($search_rdate_end) > 0) $sql .= " AND br.rdate <= '" . $db->idate($search_rdate_end) . "'";
	if (dol_strlen($search_bdate_start) > 0) $sql .= " AND br.bdate >= '" . $db->idate($search_bdate_start) . "'";
	if (dol_strlen($search_bdate_end) > 0) $sql .= " AND br.bdate <= '" . $db->idate($search_bdate_end) . "'";
	if (dol_strlen($search_vdate_start) > 0) $sql .= " AND br.vdate >= '" . $db->idate($search_vdate_start) . "'";
	if (dol_strlen($search_vdate_end) > 0) $sql .= " AND br.vdate <= '" . $db->idate($search_vdate_end) . "'";
	if (dol_strlen($search_date_scraped_start) > 0) $sql .= " AND br.date_scraped >= '" . $db->idate($search_date_scraped_start) . "'";
	if (dol_strlen($search_date_scraped_end) > 0) $sql .= " AND br.date_scraped <= '" . $db->idate($search_date_scraped_end) . "'";
	if ($search_record_type) $sql .= " AND br.record_type = '" . $db->escape($search_record_type) . "'";
	if ($search_original_country) $sql .= natural_search("br.original_country", $search_original_country);
	if ($search_original_amount) $sql .= natural_search("br.original_amount", $search_original_amount, 1);
	if ($search_original_currency) $sql .= natural_search("br.original_currency", $search_original_currency);
	if ($search_commission) $sql .= natural_search("br.commission", $search_commission, 1);
	if ($search_commission_currency) $sql .= natural_search("br.commission_currency", $search_commission_currency);
	if ($search_debit) $sql .= natural_search("- br.amount", $search_debit, 1) . ' AND br.amount < 0';
	if ($search_credit) $sql .= natural_search("br.amount", $search_credit, 1) . ' AND br.amount > 0';
	if ($search_coming != -1) $sql .= ' AND br.coming ' . (empty($search_coming) ? "IS NULL" : "= " . $search_coming);
	if (dol_strlen($search_deleted_date_start) > 0) $sql .= " AND br.deleted_date_date >= '" . $db->idate($search_deleted_date_start) . "'";
	if (dol_strlen($search_deleted_date_end) > 0) $sql .= " AND br.deleted_date_date <= '" . $db->idate($search_deleted_date_end) . "'";
	if (dol_strlen($search_last_update_date_start) > 0) $sql .= " AND br.last_update_date >= '" . $db->idate($search_last_update_date_start) . "'";
	if (dol_strlen($search_last_update_date_end) > 0) $sql .= " AND br.last_update_date <= '" . $db->idate($search_last_update_date_end) . "'";
	if (dol_strlen($search_reconcile_date_start) > 0) $sql .= " AND br.reconcile_date >= '" . $db->idate($search_reconcile_date_start) . "'";
	if (dol_strlen($search_reconcile_date_end) > 0) $sql .= " AND br.reconcile_date <= '" . $db->idate($search_reconcile_date_end) . "'";
	if (dol_strlen($search_tms_start) > 0) $sql .= " AND br.tms >= '" . $db->idate($search_tms_start) . "'";
	if (dol_strlen($search_tms_end) > 0) $sql .= " AND br.tms <= '" . $db->idate($search_tms_end) . "'";
	if ($search_bank_list) $sql .= opendsi_natural_search("b.rowid", $search_bank_list, 1);
	if ($search_num_releve) $sql .= opendsi_natural_search("b.num_releve", $search_num_releve);
	if ($search_datas) $sql .= natural_search("br.datas", $search_datas);
	if ($search_import_key) $sql .= natural_search("br.import_key", $search_import_key);
	$to_where = array();
	if (in_array("1", $search_not_valid_only)) {
		$to_where[] = "br.record_date != b.dateo";
	}
	if (in_array("2", $search_not_valid_only)) {
		$to_where[] = $db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate") . " != b.datev";
	}
	if (in_array("3", $search_not_valid_only)) {
		$to_where[] = $db->ifsql("cb4dbrt.mode_reglement = ''", "b.fk_type", "cb4dbrt.mode_reglement") . " != b.fk_type";
	}
	if (!empty($to_where)) {
		$sql .= " AND (" . implode(' OR ', $to_where) . ")";
	}
	if ($search_duplicate_only) $sql .= " AND brd.nb IS NOT NULL";
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY br.rowid, br.id_record, br.id_account, " . $db->ifsql("brlu.nb IS NULL", "0", "brlu.nb") . ",";
	$sql .= " br.label, br.comment, br.note, CONCAT(" . $db->ifsql("cb4dbrsc.rowid IS NULL", "''", "CONCAT(cb4dbrsc.label, ' - ')") . ", cb4dbrc.label), br.record_type, br.original_country, br.original_amount, br.original_currency,";
	$sql .= ' br.commission, br.commission_currency, br.amount, br.coming, br.deleted_date, br.record_date, br.rdate, br.bdate,';
	$sql .= ' br.vdate, br.date_scraped, br.last_update_date, br.reconcile_date, br.datas, br.tms, br.status, br.import_key,';
	$sql .= " bl.bank_list, b.num_releve,";
	$sql .= " " . $db->ifsql("brds.fk_duplicate_of IS NULL", "0", "1") . "";
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters);    // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sort_fields = explode(',', $sortfield);
	$sort_orders = explode(',', $sortorder);
	if ($search_duplicate_only) {
		foreach ($duplicate_test_on_fields as $field) {
			if (!in_array('br.' . $field, $sort_fields)) {
				$sort_fields = array_merge(array('br.' . $field), $sort_fields);
				$sort_orders = array_merge(array('ASC'), $sort_orders);;
			}
		}
	}
	$sql_sort = $db->order(implode(',', $sort_fields), implode(',', $sort_orders));
	$sql .= str_replace('br.vdate', $db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate"), $sql_sort);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
		$resql = $db->query('SELECT COUNT(*) As nb FROM (' . $sql . ') As count_table');
		if ($resql && ($obj = $db->fetch_object($resql))) {
			$nbtotalofrecords = $obj->nb;
		} else {
			setEventMessage($db->lasterror(), 'errors');
		}
	}

	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if ($resql) {
		$title = $langs->trans('Banking4DolibarrListOfBankRecords');

		$num = $db->num_rows($resql);

		$arrayofselected = is_array($toselect) ? $toselect : array();

		// List of mass actions available
		$arrayofmassactions = array();

		if ($object->clos == 0 && $user->rights->banque->consolidate) {
			$arrayofmassactions['b4d_create_various_payments_reconciled'] = $langs->trans("Banking4DolibarrCreateReconciledVariousPayments");
			$arrayofmassactions['b4d_edit_statement_number'] = $langs->trans("Banking4DolibarrEditStatementNumber");
		}
		if ($object->clos == 0 && $user->rights->banking4dolibarr->bank_records->unlink)
			$arrayofmassactions['b4d_unlink'] = $langs->trans("Banking4DolibarrUnlinkRecords");
		if ($object->clos == 0 && $user->rights->banking4dolibarr->bank_records->discard)
			$arrayofmassactions['b4d_discard'] = $langs->trans("Banking4DolibarrDiscardRecords");
		if ($object->clos == 0 && $user->rights->banking4dolibarr->bank_records->undiscard)
			$arrayofmassactions['b4d_undiscard'] = $langs->trans("Banking4DolibarrUndiscardRecords");
		if ($object->clos == 0 && $user->rights->banking4dolibarr->bank_records->fix_lines) {
			$arrayofmassactions['b4d_fix_dates'] = $langs->trans("Banking4DolibarrFixDatesRecords");
			$arrayofmassactions['b4d_fix_payment_types'] = $langs->trans("Banking4DolibarrFixPaymentTypesRecords");
			$arrayofmassactions['b4d_fix_dates_payment_types'] = $langs->trans("Banking4DolibarrFixDatesAndPaymentTypesRecords");
		}
		if ($object->clos == 0 && $user->rights->banking4dolibarr->bank_records->fix_duplicate)
			$arrayofmassactions['b4d_fix_duplicate'] = $langs->trans("Banking4DolibarrFixDuplicateRecords");
        if ($object->clos == 0 && $user->rights->banking4dolibarr->bank_records->supprimer)
            $arrayofmassactions['b4d_delete'] = $langs->trans("Banking4DolibarrDeleteRecords");

		// if (in_array($massaction, array(''))) $arrayofmassactions = array();
		$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

		// Lignes des champs de filtre
		print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
		print '<input type="hidden" name="page" value="' . $page . '">';
		print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

		print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit);

		$i = 0;

		$moreforfilter = '';

		// Show not valid only
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('Banking4DolibarrSearchNotValidOnly') . ': ';
		$moreforfilter .= $form->multiselectarray('search_not_valid_only', array(
			1 => $langs->trans("DateOperation"),
			2 => $langs->trans("DateValue"),
			3 => $langs->trans("Type"),
		), $search_not_valid_only, 0, 0, ' minwidth200');
		$moreforfilter .= '</div>';

		// Show duplicate lines only
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('Banking4DolibarrSearchDuplicateOnly');
		$has_duplicate_bank_records = $budgetinsight->hasDuplicateBankRecords($id);
		if ($has_duplicate_bank_records < 0) {
			setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
		} elseif ($has_duplicate_bank_records) {
			$moreforfilter .= ' ' . $form->textwithpicto('', $langs->trans('Banking4DolibarrWarningDuplicateBankRecordFound'), 1, 'warning');
		}
		$moreforfilter .= ' : ';
		$moreforfilter .= $form->selectyesno('search_duplicate_only', $search_duplicate_only, 1);
		$moreforfilter .= '</div>';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
		else $moreforfilter = $hookmanager->resPrint;

		if (!empty($moreforfilter)) {
			print '<div class="liste_titre liste_titre_bydiv centpercent">';
			print $moreforfilter;
			print '</div>';
		}

		$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
		$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields
		if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

		print '<tr class="liste_titre_filter">';
		if (!empty($arrayfields['br.rowid']['checked'])) {
			print '<td class="liste_titre"></td>';
		}
		if (!empty($arrayfields['br.id_record']['checked'])) {
			print '<td class="liste_titre">';
			print '<input class="flat" size="6" type="text" name="search_id_record" value="' . dol_escape_htmltag($search_id_record) . '">';
			print '</td>';
		}
//        if (!empty($arrayfields['br.label']['checked'])) {
		print '<td class="liste_titre" colspan="2">';
		print '<input class="flat" size="6" type="text" name="search_label" value="' . dol_escape_htmltag($search_label) . '">';
		print '</td>';
		if (!empty($arrayfields['br.comment']['checked'])) {
			print '<td class="liste_titre">';
			print '<input class="flat" size="6" type="text" name="search_comment" value="' . dol_escape_htmltag($search_comment) . '">';
			print '</td>';
		}
//        }
//        if (!empty($arrayfields['br.record_date']['checked'])) {
		print '<td align="center" class="liste_titre nowrap">';
		print '<div class="nowrap">';
		print $langs->trans('From') . ' ';
		print $form->selectDate($search_record_date_start?$search_record_date_start:-1, 'search_record_date_start', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to') . ' ';
		print $form->selectDate($search_record_date_end?$search_record_date_end:-1, 'search_record_date_end', 0, 0, 1);
		print '</div>';
		print '</td>';
//        }
//        if (!empty($arrayfields['br.vdate']['checked'])) {
		print '<td align="center" class="liste_titre nowrap">';
		print '<div class="nowrap">';
		print $langs->trans('From') . ' ';
		print $form->selectDate($search_vdate_start?$search_vdate_start:-1, 'search_vdate_start', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to') . ' ';
		print $form->selectDate($search_vdate_end?$search_vdate_end:-1, 'search_vdate_end', 0, 0, 1);
		print '</div>';
		print '</td>';
//        }
//        if (!empty($arrayfields['br.record_type']['checked'])) {
		print '<td align="center" class="liste_titre">';
		print $formdictionary->select_dictionary('banking4dolibarr', 'banking4dolibarrbankrecordtypes', $search_record_type, 'search_record_type', 1, 'code');
		print '</td>';
//        }
//        if (!empty($arrayfields['debit']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" size="6" type="text" name="search_debit" value="' . dol_escape_htmltag($search_debit) . '">';
		print '</td>';
//        }
//        if (!empty($arrayfields['credit']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" size="6" type="text" name="search_credit" value="' . dol_escape_htmltag($search_credit) . '">';
		print '</td>';
//        }
		if (!empty($arrayfields['bank_list']['checked'])) {
			print '<td align="center" class="liste_titre nowrap">';
			print '<input class="flat" size="6" type="text" name="search_bank_list" value="' . dol_escape_htmltag($search_bank_list) . '">';
			print '</td>';
		}
//        if (!empty($arrayfields['b.num_releve']['checked'])) {
		print '<td align="center" class="liste_titre nowrap">';
		print '<input class="flat" size="6" type="text" name="search_num_releve" value="' . dol_escape_htmltag($search_num_releve) . '">';
		print '</td>';
//        }
		if (!empty($arrayfields['br.reconcile_date']['checked'])) {
			print '<td align="center" class="liste_titre nowrap">';
			print '<div class="nowrap">';
			print $langs->trans('From') . ' ';
			print $form->selectDate($search_reconcile_date_start?$search_reconcile_date_start:-1, 'search_reconcile_date_start', 0, 0, 1);
			print '</div>';
			print '<div class="nowrap">';
			print $langs->trans('to') . ' ';
			print $form->selectDate($search_reconcile_date_end?$search_reconcile_date_end:-1, 'search_reconcile_date_end', 0, 0, 1);
			print '</div>';
			print '</td>';
		}
		if (!empty($arrayfields['br.id_category']['checked'])) {
			print '<td class="liste_titre">';
			print '<input class="flat" size="6" type="text" name="search_category" value="' . dol_escape_htmltag($search_category) . '">';
			print '</td>';
		}
		if (!empty($arrayfields['br.rdate']['checked'])) {
			print '<td align="center" class="liste_titre nowrap">';
			print '<div class="nowrap">';
			print $langs->trans('From') . ' ';
			print $form->selectDate($search_rdate_start?$search_rdate_start:-1, 'search_rdate_start', 0, 0, 1);
			print '</div>';
			print '<div class="nowrap">';
			print $langs->trans('to') . ' ';
			print $form->selectDate($search_rdate_end?$search_rdate_end:-1, 'search_rdate_end', 0, 0, 1);
			print '</div>';
			print '</td>';
		}
		if (!empty($arrayfields['br.bdate']['checked'])) {
			print '<td align="center" class="liste_titre nowrap">';
			print '<div class="nowrap">';
			print $langs->trans('From') . ' ';
			print $form->selectDate($search_bdate_start?$search_bdate_start:-1, 'search_bdate_start', 0, 0, 1);
			print '</div>';
			print '<div class="nowrap">';
			print $langs->trans('to') . ' ';
			print $form->selectDate($search_bdate_end?$search_bdate_end:-1, 'search_bdate_end', 0, 0, 1);
			print '</div>';
			print '</td>';
		}
		if (!empty($arrayfields['br.date_scraped']['checked'])) {
			print '<td align="center" class="liste_titre nowrap">';
			print '<div class="nowrap">';
			print $langs->trans('From') . ' ';
			print $form->selectDate($search_date_scraped_start?$search_date_scraped_start:-1, 'search_date_scraped_start', 0, 0, 1);
			print '</div>';
			print '<div class="nowrap">';
			print $langs->trans('to') . ' ';
			print $form->selectDate($search_date_scraped_end?$search_date_scraped_end:-1, 'search_date_scraped_end', 0, 0, 1);
			print '</div>';
			print '</td>';
		}
		if (!empty($arrayfields['br.original_country']['checked'])) {
			print '<td class="liste_titre">';
			print '<input class="flat" size="6" type="text" name="search_original_country" value="' . dol_escape_htmltag($search_original_country) . '">';
			print '</td>';
		}
		if (!empty($arrayfields['br.original_amount']['checked'])) {
			print '<td class="liste_titre right">';
			print '<input class="flat" size="6" type="text" name="search_original_amount" value="' . dol_escape_htmltag($search_original_amount) . '">';
			print '</td>';
		}
		if (!empty($arrayfields['br.original_currency']['checked'])) {
			print '<td align="center" class="liste_titre">';
			print $form->selectCurrency($search_original_currency, 'search_original_currency');
			// Add empty option
			print <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function () {
            $("#search_original_currency").prepend('<option value=""' + ("$search_original_currency" == "" ? ' selected' : '') + '>&nbsp;</option>')
        });
    </script>
SCRIPT;
			print '</td>';
		}
		if (!empty($arrayfields['br.commission']['checked'])) {
			print '<td class="liste_titre right">';
			print '<input class="flat" size="6" type="text" name="search_commission" value="' . dol_escape_htmltag($search_commission) . '">';
			print '</td>';
		}
		if (!empty($arrayfields['br.commission_currency']['checked'])) {
			print '<td align="center" class="liste_titre">';
			print $form->selectCurrency($search_commission_currency, 'search_commission_currency');
			// Add empty option
			print <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function () {
            $("#search_commission_currency").prepend('<option value=""' + ("$search_commission_currency" == "" ? ' selected' : '') + '>&nbsp;</option>')
        });
    </script>
SCRIPT;
			print '</td>';
		}
		if (!empty($arrayfields['br.coming']['checked'])) {
			print '<td align="center" class="liste_titre">';
			print $form->selectyesno('search_coming', $search_coming, 1, false, 1);
			print '</td>';
		}
		if (!empty($arrayfields['br.deleted_date']['checked'])) {
			print '<td align="center" class="liste_titre">';
			print '<div class="nowrap">';
			print $langs->trans('From') . ' ';
			print $form->selectDate($search_deleted_date_start?$search_deleted_date_start:-1, 'search_deleted_date_start', 0, 0, 1);
			print '</div>';
			print '<div class="nowrap">';
			print $langs->trans('to') . ' ';
			print $form->selectDate($search_deleted_date_end?$search_deleted_date_end:-1, 'search_deleted_date_end', 0, 0, 1);
			print '</div>';
			print '</td>';
		}
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields);
		$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (!empty($arrayfields['br.last_update_date']['checked'])) {
			print '<td align="center" class="liste_titre nowrap">';
			print '<div class="nowrap">';
			print $langs->trans('From') . ' ';
			print $form->selectDate($search_last_update_date_start?$search_last_update_date_start:-1, 'search_last_update_date_start', 0, 0, 1);
			print '</div>';
			print '<div class="nowrap">';
			print $langs->trans('to') . ' ';
			print $form->selectDate($search_last_update_date_end?$search_last_update_date_end:-1, 'search_last_update_date_end', 0, 0, 1);
			print '</div>';
			print '</td>';
		}
		if (!empty($arrayfields['br.tms']['checked'])) {
			print '<td align="center" class="liste_titre nowrap">';
			print '<div class="nowrap">';
			print $langs->trans('From') . ' ';
			print $form->selectDate($search_tms_start?$search_tms_start:-1, 'search_tms_start', 0, 0, 1);
			print '</div>';
			print '<div class="nowrap">';
			print $langs->trans('to') . ' ';
			print $form->selectDate($search_tms_end?$search_tms_end:-1, 'search_tms_end', 0, 0, 1);
			print '</div>';
			print '</td>';
		}
		if (!empty($arrayfields['br.datas']['checked'])) {
			print '<td class="liste_titre maxwidthonsmartphone">';
			print '<input class="flat" size="6" type="text" name="search_datas" value="' . dol_escape_htmltag($search_datas) . '">';
			print '</td>';
		}
		if (!empty($arrayfields['br.import_key']['checked'])) {
			print '<td class="liste_titre maxwidthonsmartphone">';
			print '<input class="flat" size="6" type="text" name="search_import_key" value="' . dol_escape_htmltag($search_import_key) . '">';
			print '</td>';
		}
//        if (!empty($arrayfields['br.status']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone right">';
		print $form->selectarray('search_statut', $budgetinsightbankrecord->labelStatusShort, $viewstatut, 1, 0, 0, '', 1);
		print '</td>';
//        }
		// Action column
		print '<td class="liste_titre" align="middle">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
		print "</tr>\n";

		// Fields title
		$filter_date_help = ' ' . $form->textwithpicto('', $langs->transnoentitiesnoconv('Banking4DolibarrFilterDateHelp'));
		print '<tr class="liste_titre">';
		if (!empty($arrayfields['br.rowid']['checked'])) print_liste_field_titre($arrayfields['br.rowid']['label'], $_SERVER["PHP_SELF"], 'br.rowid', '', $param, '', $sortfield, $sortorder);
		if (!empty($arrayfields['br.id_record']['checked'])) print_liste_field_titre($arrayfields['br.id_record']['label'], $_SERVER["PHP_SELF"], 'br.id_record', '', $param, '', $sortfield, $sortorder);
		/*if (!empty($arrayfields['br.label']['checked']))*/
		print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"], 'br.label', '', $param, ' colspan="2"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.comment']['checked'])) print_liste_field_titre($arrayfields['br.comment']['label'], $_SERVER["PHP_SELF"], 'br.comment', '', $param, '', $sortfield, $sortorder);
		/*if (!empty($arrayfields['br.record_date']['checked']))*/
		print_liste_field_titre($langs->trans("DateOperationShort") . $filter_date_help, $_SERVER["PHP_SELF"], 'br.record_date', '', $param, 'align="center"', $sortfield, $sortorder);
		/*if (!empty($arrayfields['br.vdate']['checked']))*/
		print_liste_field_titre($langs->trans("DateValueShort") . $filter_date_help, $_SERVER["PHP_SELF"], 'br.vdate', '', $param, 'align="center"', $sortfield, $sortorder);
		/*if (!empty($arrayfields['br.record_type']['checked']))*/
		print_liste_field_titre($langs->trans("Type"), $_SERVER["PHP_SELF"], "br.record_type", "", $param, 'align="center"', $sortfield, $sortorder);
		/*if (!empty($arrayfields['debit']['checked']))*/
		print_liste_field_titre($langs->trans("Debit"), $_SERVER["PHP_SELF"], 'br.amount', '', $param, 'align="right"', $sortfield, $sortorder);
		/*if (!empty($arrayfields['credit']['checked']))*/
		print_liste_field_titre($langs->trans("Credit"), $_SERVER["PHP_SELF"], 'br.amount', '', $param, 'align="right"', $sortfield, $sortorder);
		if (!empty($arrayfields['bank_list']['checked'])) print_liste_field_titre($langs->trans("BankTransactions") . " (D)", $_SERVER["PHP_SELF"], "", "", $param, 'align="center"', $sortfield, $sortorder);
		/*if (!empty($arrayfields['b.num_releve']['checked']))*/
		print_liste_field_titre($langs->trans("AccountStatementShort"), $_SERVER["PHP_SELF"], "b.num_releve", "", $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.reconcile_date']['checked'])) print_liste_field_titre($arrayfields['br.reconcile_date']['label'] . $filter_date_help, $_SERVER["PHP_SELF"], "br.reconcile_date", "", $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.id_category']['checked'])) print_liste_field_titre($arrayfields['br.id_category']['label'], $_SERVER["PHP_SELF"], 'cb4dbrc.label', '', $param, '', $sortfield, $sortorder);
		if (!empty($arrayfields['br.rdate']['checked'])) print_liste_field_titre($arrayfields['br.rdate']['label'] . $filter_date_help, $_SERVER["PHP_SELF"], 'br.rdate', '', $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.bdate']['checked'])) print_liste_field_titre($arrayfields['br.bdate']['label'] . $filter_date_help, $_SERVER["PHP_SELF"], 'br.bdate', '', $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.date_scraped']['checked'])) print_liste_field_titre($arrayfields['br.date_scraped']['label'] . $filter_date_help, $_SERVER["PHP_SELF"], "br.date_scraped", "", $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.original_country']['checked'])) print_liste_field_titre($arrayfields['br.original_country']['label'], $_SERVER["PHP_SELF"], 'br.original_country', '', $param, '', $sortfield, $sortorder);
		if (!empty($arrayfields['br.original_amount']['checked'])) print_liste_field_titre($arrayfields['br.original_amount']['label'], $_SERVER["PHP_SELF"], 'br.original_amount', '', $param, 'align="right"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.original_currency']['checked'])) print_liste_field_titre($arrayfields['br.original_currency']['label'], $_SERVER["PHP_SELF"], 'br.original_currency', '', $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.commission']['checked'])) print_liste_field_titre($arrayfields['br.commission']['label'], $_SERVER["PHP_SELF"], 'br.commission', '', $param, 'align="right"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.commission_currency']['checked'])) print_liste_field_titre($arrayfields['br.commission_currency']['label'], $_SERVER["PHP_SELF"], 'br.commission_currency', '', $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.coming']['checked'])) print_liste_field_titre($arrayfields['br.coming']['label'], $_SERVER["PHP_SELF"], 'br.coming', '', $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.deleted_date']['checked'])) print_liste_field_titre($arrayfields['br.deleted_date']['label'] . $filter_date_help, $_SERVER["PHP_SELF"], 'br.deleted_date', '', $param, 'align="center"', $sortfield, $sortorder);
		// Hook fields
		$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
		$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (!empty($arrayfields['br.last_update_date']['checked'])) print_liste_field_titre($arrayfields['br.last_update_date']['label'] . $filter_date_help, $_SERVER["PHP_SELF"], "br.last_update_date", "", $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.tms']['checked'])) print_liste_field_titre($arrayfields['br.tms']['label'] . $filter_date_help, $_SERVER["PHP_SELF"], "br.tms", "", $param, 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['br.datas']['checked'])) print_liste_field_titre($arrayfields['br.datas']['label'], $_SERVER["PHP_SELF"], 'br.datas', '', $param, '', $sortfield, $sortorder);
		if (!empty($arrayfields['br.import_key']['checked'])) print_liste_field_titre($arrayfields['br.import_key']['label'], $_SERVER["PHP_SELF"], 'br.import_key', '', $param, '', $sortfield, $sortorder);
		/*if (!empty($arrayfields['br.status']['checked']))*/
		print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "br.status", "", $param, '', $sortfield, $sortorder, "right ");
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
		print '</tr>' . "\n";

		$warning_wrong_infos = img_warning($langs->trans('Banking4DolibarrWarningWrongInfosWithBankLine'));
		$details_tooltip = dol_escape_js($langs->trans('Banking4DolibarrReconciledBankLinesDetails'));
		$duplicate_tooltip = dol_escape_js($langs->trans('Banking4DolibarrDuplicateBankRecordsDetails'));

		$i = 0;
		$totalarray = array();
		while ($i < min($num, $limit)) {
			$obj = $db->fetch_object($resql);

			if (empty($obj->bank_list))
				$expand_button = '<span class="row_details ' . $class_fonts_awesome . ' fa-plus-square disabled" id="row_details_' . $obj->rowid . '"></span>';
			else
				$expand_button = '<span class="row_details cursorpointer ' . $class_fonts_awesome . ' fa-plus-square" id="row_details_' . $obj->rowid . '" data-row-id="' . $obj->rowid . '" title="' . $details_tooltip . '"></span>';
			if ($obj->duplicated_line) {
				$expand_button = '<span class="nowrap">' . $expand_button . '<span class="duplicate_details cursorpointer ' . $class_fonts_awesome2 . ' fa-clone" id="duplicate_details_' . $obj->rowid . '" data-row-id="' . $obj->rowid . '" title="' . $duplicate_tooltip . '"></span></span>';
			}
			$expand_button_added = false;
			$can_unlink = $obj->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED && $object->clos == 0 && $user->rights->banque->consolidate;

			$backgroundcolor = '';
			if (!empty($conf->global->BANK_COLORIZE_MOVEMENT)) {
				$color_key = 'BANK_COLORIZE_MOVEMENT_COLOR' . ($obj->amount < 0 ? 1 : 2);
				$color = '#' . (!empty($conf->global->$color_key) ? $conf->global->$color_key : ($obj->amount < 0 ? 'fca955' : '7fdb86'));
				$backgroundcolor = ' style="background: ' . $color . ';"';
			}
			print '<tr class="oddeven" id="row_id_' . $obj->rowid . '"' . $backgroundcolor . '>';
			if (!empty($arrayfields['br.rowid']['checked'])) {
				print '<td>';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print $obj->rowid;
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.id_record']['checked'])) {
				print '<td>';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print $obj->id_record;
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
//            if (!empty($arrayfields['br.label']['checked'])) {
			print '<td colspan="2">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			print $obj->label;
			if (!empty($obj->note)) print ' ' . $form->textwithtooltip('', $obj->note, 2, 1, '<i class="' . $class_fonts_awesome . ' fa-sticky-note" />');
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['totallabelfield'] = $totalarray['nbfield'];
			if (!empty($arrayfields['br.comment']['checked'])) {
				print '<td>';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print $obj->comment;
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
//            }
//            if (!empty($arrayfields['br.record_date']['checked'])) {
			print '<td align="center">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			if (!empty($obj->record_date)) print dol_print_date($db->jdate($obj->record_date), 'day');
			if (!empty($obj->wrong_odate)) print '<span id="wrong_odate_' . $obj->rowid . '"> ' . $warning_wrong_infos . '</span>';
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
//            }
//            if (!empty($arrayfields['br.vdate']['checked'])) {
			print '<td align="center">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			if (!empty($obj->vdate)) print dol_print_date($db->jdate($obj->vdate), 'day');
			elseif (!empty($obj->record_date)) print dol_print_date($db->jdate($obj->record_date), 'day');
			if (!empty($obj->wrong_vdate)) print '<span id="wrong_vdate_' . $obj->rowid . '"> ' . $warning_wrong_infos . '</span>';
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
//            }
//            if (!empty($arrayfields['br.record_type']['checked'])) {
			print '<td align="center">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			print $budgetinsightbankrecord->LibType($obj->record_type);
			if (!empty($obj->wrong_payment_type)) print '<span id="wrong_payment_type_' . $obj->rowid . '"> ' . $warning_wrong_infos . '</span>';
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
//            }
//            if (!empty($arrayfields['debit']['checked'])) {
			print '<td class="nowrap right">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			if ($obj->amount < 0) {
				print price($obj->amount * -1);
				$totalarray['totaldeb'] += $obj->amount;
			}
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['totaldebfield'] = $totalarray['nbfield'];
//            }
//            if (!empty($arrayfields['credit']['checked'])) {
			print '<td class="nowrap right">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			if ($obj->amount > 0) {
				print price($obj->amount);
				$totalarray['totalcred'] += $obj->amount;
			}
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['totalcredfield'] = $totalarray['nbfield'];
//            }
			if (!empty($arrayfields['bank_list']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if (!empty($obj->bank_list)) {
					$bank_list = explode('|', $obj->bank_list);
					$to_print = array();

					if (version_compare(DOL_VERSION, "11.0.0") >= 0) {
						$linkToBankLine = '/compta/bank/line.php';
					} else {
						$linkToBankLine = '/compta/bank/ligne.php';
					}

					foreach ($bank_list as $bank_id) {
						$to_print[] = '<span' . ($can_unlink ? ' class="unlink_line_' . $obj->rowid . '_' . $bank_id . '"' : '') .
							'><a href="' . DOL_URL_ROOT . $linkToBankLine . '?rowid=' . $bank_id . '&save_lastsearch_values=1">' .
							img_object($langs->trans("ShowPayment") . ': ' . $bank_id, 'account', 'class="classfortooltip"') . ' ' . $bank_id . "</a> &nbsp; </span>";
					}
					print implode('', $to_print);
				}
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
//            if (!empty($arrayfields['b.num_releve']['checked'])) {
			print '<td align="center">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			if (!empty($obj->num_releve)) print '<a href="' . DOL_URL_ROOT . '/compta/bank/releve.php?num=' . $obj->num_releve . '&amp;account=' . $id . '">' . $obj->num_releve . '</a>';
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
//            }
			if (!empty($arrayfields['br.reconcile_date']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print dol_print_date($db->jdate($obj->reconcile_date), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.id_category']['checked'])) {
				print '<td>';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print $obj->category_label;
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.rdate']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if (!empty($obj->rdate)) print dol_print_date($db->jdate($obj->rdate), 'day');
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.bdate']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if (!empty($obj->bdate)) print dol_print_date($db->jdate($obj->bdate), 'day');
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.date_scraped']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if (!empty($obj->date_scraped)) print dol_print_date($db->jdate($obj->date_scraped), 'dayhour');
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.original_country']['checked'])) {
				print '<td>';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print $obj->original_country;
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.original_amount']['checked'])) {
				print '<td class="right">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if ($obj->original_amount > 0) print price($obj->original_amount);
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.original_currency']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if (!empty($obj->original_currency)) print $langs->trans('Currency' . $obj->original_currency);
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.commission']['checked'])) {
				print '<td class="right">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if ($obj->commission > 0) print price($obj->commission);
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.commission_currency']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if (!empty($obj->commission_currency)) print $langs->trans('Currency' . $obj->commission_currency);
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.coming']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print yn($obj->coming);
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.deleted_date']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				if (!empty($obj->deleted_date)) print dol_print_date($db->jdate($obj->deleted_date), 'dayhour');
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			if (!empty($arrayfields['br.last_update_date']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print dol_print_date($db->jdate($obj->last_update_date), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.tms']['checked'])) {
				print '<td align="center">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.datas']['checked'])) {
				print '<td class="maxwidthonsmartphone">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print $obj->datas;
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($arrayfields['br.import_key']['checked'])) {
				print '<td class="maxwidthonsmartphone">';
				if (!$expand_button_added) {
					print $expand_button;
					$expand_button_added = true;
				}
				print $obj->import_key;
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
//            if (!empty($arrayfields['br.status']['checked'])) {
			print '<td class="nowrap right">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			print $budgetinsightbankrecord->LibStatut($obj->status, 5);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
//            }
			// Action column
			print '<td class="right nowrap">';
			if (!$expand_button_added) {
				print $expand_button;
				$expand_button_added = true;
			}
			if ($obj->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED && $object->clos == 0 && $user->rights->banque->consolidate && !isset($obj->deleted_date)) {
				$has_different_currency = !empty($obj->original_currency) && $obj->original_currency != $object->currency_code;
                $icon_manualreconcile = (!empty($conf->global->EASYA_VERSION) || version_compare(DOL_VERSION, "11.0.0") >= 0) ? 'fa-link' : 'fa-link';
				print '<a href="' . $base_url . '&line_id=' . $obj->rowid . '&action=b4d_edit_line">' . img_edit($langs->trans($has_different_currency ? "Banking4DolibarrEditLine" : "Banking4DolibarrEditNote")) . '</a>';
				print '&nbsp;&nbsp;&nbsp;';
				print '<a href="javascript:b4d_open_manual_reconciliation_box(' . $id . ', ' . $obj->rowid . ');"' . ($obj->nb_bank_linked > 1 ? ' class="reconcile_button_' . $obj->rowid . '" style="display:none;"' : '') . '><span class="' . $class_fonts_awesome . ' ' . $icon_manualreconcile . ' fa-lg" title="' . dol_escape_js($langs->trans('Banking4DolibarrManualReconcileBankRecords')) . '"></span></a>';
			} elseif ($obj->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_RECONCILED && $object->clos == 0 && $user->rights->banque->consolidate) {
				print '<a href="' . $base_url . '&line_id=' . $obj->rowid . '&action=b4d_edit_statement_number">' . img_edit($langs->trans("Banking4DolibarrEditStatementNumber")) . '</a>';
			}
			if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			{
				print '&nbsp;&nbsp;&nbsp;';
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
				print '<input id="cb' . $obj->rowid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;

			print "</tr>\n";

			$i++;
		}

		// Show total line
		if (isset($totalarray['totaldebfield']) || isset($totalarray['totalcredfield'])) {
			print '<tr class="liste_total">';
			if ($num < $limit && empty($offset)) print '<td>' . $langs->trans("Total") . '</td>';
			else print '<td>' . $langs->trans("Totalforthispage") . '</td>';
			$i = 0;
			while ($i < $totalarray['nbfield']) {
				$i++;
				if ($totalarray['totaldebfield'] == $i) print '<td class="right">' . price(-1 * $totalarray['totaldeb']) . '</td>';
				elseif ($totalarray['totalcredfield'] == $i) print '<td class="right">' . price($totalarray['totalcred']) . '</td>';
				else print '<td></td>';
			}
			print '</tr>';
		}

		$db->free($resql);

		$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
		$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print '</table>' . "\n";
		print '</div>' . "\n";

		print '</form>' . "\n";

		if ($num > 0) {
			$index_label = $totalarray['totallabelfield'];
			$nb_field = $totalarray['nbfield'];
			$nb_column = $nb_field + 1;
			$has_comment = !empty($arrayfields['br.comment']['checked']) ? 1 : 0;
			$array_fields = json_encode($arrayfields);
			print <<<SCRIPT
<script type="text/javascript">
    $(document).ready(function(){
        $(".row_details:not(.disabled)").click(function() {
            var _this = $(this);
            if (_this.not('.disabled')) {
                var row_id = _this.attr("data-row-id");
    
                if (_this.hasClass("fa-plus-square")) {
                    b4d_open_content_line($id, row_id, $index_label, $nb_field, $nb_column, $has_comment);
                } else {
                    b4d_close_content_line(row_id);
                }
            }
        });
        $(".duplicate_details").click(function() {
            var _this = $(this);
			var row_id = _this.attr("data-row-id");

			if (_this.hasClass("far")) {
				b4d_open_duplicate_line($id, row_id, $nb_column, $array_fields);
			} else {
				b4d_close_content_line(row_id);
			}
        });
    });
</script>
SCRIPT;
		}

		if ($object->clos == 0 && $user->rights->banque->consolidate && $budgetinsight->hasUnReconliledRecords($id)) {
			print $formbankink4dolibarr->manualReconciliationBox(
				dol_buildpath('/banking4dolibarr/manualreconciliation.php', 1),
				$langs->trans('Banking4DolibarrManualReconcileBankRecords'),
				"90%", "90%", "searchFormList"
			);
		}

//    if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
//        /*
//         * Show list of available documents
//         */
//        $urlsource = $_SERVER['PHP_SELF'] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder;
//        $urlsource .= str_replace('&amp;', '&', $param);
//
//        $filedir = $diroutputmassaction;
//        $genallowed = $user->rights->propal->lire;
//        $delallowed = $user->rights->propal->creer;
//
//        print $formfile->showdocuments('massfilesarea_proposals', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, '', '');
//    } else {
//        print '<br><a name="show_files"></a><a href="' . $_SERVER["PHP_SELF"] . '?show_files=1' . $param . '#show_files">' . $langs->trans("ShowTempMassFilesArea") . '</a>';
//    }
	} else {
		dol_print_error($db);
	}
} else {
    print $langs->trans('Banking4DolibarrBankAccountNotLinked');
}

// End of page
llxFooter();
$db->close();
