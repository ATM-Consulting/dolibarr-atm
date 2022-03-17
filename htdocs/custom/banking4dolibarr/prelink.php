<?php
/* Copyright (C) 2019      Open-DSI              <support@open-dsi.fr>
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
 *	\file       	htdocs/packinglist/packinglist.php
 *	\ingroup    	packinglist
 *	\brief      	Page of PackingList list
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
dol_include_once('/banking4dolibarr/lib/opendsi_common.lib.php');
dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
dol_include_once('/banking4dolibarr/class/html.formbanking4dolibarr.class.php');
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

$langs->loadLangs(array('banking4dolibarr@banking4dolibarr', 'banks', 'companies', 'other'));

$id	            = GETPOST('id', 'int');
$ref		    = GETPOST('ref','alpha');
$action         = GETPOST('action','alpha');
$confirm        = GETPOST('confirm','alpha');
$toselect       = GETPOST('toselect', 'array');
$state          = GETPOST('state','alpha');

// Security check
if ($user->societe_id > 0 || !$user->rights->banking4dolibarr->bank_records->lire || empty($state)) accessforbidden();
$result = restrictedArea($user, 'banque', !empty($ref) ? $ref : $id, 'bank_account&bank_account', '', '', !empty($ref) ? 'ref' : 'rowid');

// Load object
$object = new Account($db);
if ($id > 0 || !empty($ref)) {
    $ret = $object->fetch($id, $ref);
    if ($ret > 0) {
        $ret = $object->fetch_thirdparty();
        $id = $object->id;
        if ($object->clos != 0 || !$user->rights->banque->consolidate) accessforbidden();
    } elseif ($ret < 0) {
        dol_print_error('', $object->error, $object->errors);
    } elseif ($ret == 0) {
        $langs->load("errors");
        accessforbidden($langs->trans('ErrorRecordNotFound'));
    }
}

$budgetinsight = new BudgetInsight($db);
$has_unpaid_element = $budgetinsight->hasUnpaidElement();
if ($has_unpaid_element < 0) {
	setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
}
if ($has_unpaid_element <= 0) {
	accessforbidden($langs->trans());
}
if (!($budgetinsight->isAutoLinkBankRecordsStarted($id, $state) > 0)) accessforbidden();
$last_update_date = $budgetinsight->getBankRecordsLastUpdateDate($id);
if ($last_update_date < 0) {
    setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
}
$remote_bank_account_id = $budgetinsight->getRemoteBankAccountID($id);
if (!isset($remote_bank_account_id)) {
	setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
}

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
$search_last_update_date_start	= dol_mktime(0, 0, 0, GETPOST('search_last_update_date_startmonth', 'int'), GETPOST('search_last_update_date_startday', 'int'), GETPOST('search_last_update_date_startyear', 'int'));
$search_last_update_date_end	= dol_mktime(23, 59, 59, GETPOST('search_last_update_date_endmonth', 'int'), GETPOST('search_last_update_date_endday', 'int'), GETPOST('search_last_update_date_endyear', 'int'));
$search_tms_start				= dol_mktime(0, 0, 0, GETPOST('search_tms_startmonth', 'int'), GETPOST('search_tms_startday', 'int'), GETPOST('search_tms_startyear', 'int'));
$search_tms_end					= dol_mktime(23, 59, 59, GETPOST('search_tms_endmonth', 'int'), GETPOST('search_tms_endday', 'int'), GETPOST('search_tms_endyear', 'int'));
$view_mode                      = GETPOST('view_mode', 'int');

$search_btn         = GETPOST('button_search','alpha');
$search_remove_btn  = GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha'); // All tests are required to be compatible with all browsers
$cancel_btn         = GETPOST('button_cancel_x','alpha') || GETPOST('button_cancel.x','alpha') || GETPOST('button_cancel','alpha'); // All tests are required to be compatible with all browsers
$valid_links_btn    = GETPOST('button_valid_links_x','alpha') || GETPOST('button_valid_links.x','alpha') || GETPOST('button_valid_links','alpha'); // All tests are required to be compatible with all browsers

if (!empty($cancel_btn)) $action = 'cancel';
if (!empty($valid_links_btn)) $action = 'b4d_valid_links';

$optioncss      = GETPOST('optioncss','alpha');

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
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'b4dbankrecordsprelinklist';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('b4dbankrecordsprelinklist'));

$arrayfields = array(
    'br.rowid' => array('label'=> $langs->trans("TechnicalID"), 'checked'=> (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID) ? 1 : 0)),
    'br.id_record' => array('label'=> $langs->trans("Banking4DolibarrRemoteID"), 'checked'=> 1),
    'br.comment' => array('label' => $langs->trans("Comment"), 'checked' => 1),
    'br.record_date' => array('label' => $langs->trans("DateOperationShort"), 'checked' => 1),
    'br.vdate' => array('label' => $langs->trans("DateValueShort"), 'checked' => 1),
    'br.rdate' => array('label' => $langs->trans("Banking4DolibarrDateRealization"), 'checked' => 0),
    'br.bdate' => array('label' => $langs->trans("Banking4DolibarrDateBank"), 'checked' => 0),
    'br.date_scraped' => array('label' => $langs->trans("Banking4DolibarrDateScraped"), 'checked' => 0),
    'br.record_type' => array('label' => $langs->trans("Type"), 'checked' => 1),
    'br.id_category' => array('label' => $langs->trans("Banking4DolibarrCategory"), 'checked' => 1),
    'br.original_country' => array('label' => $langs->trans("Country"), 'checked' => 0),
    'br.original_amount' => array('label' => $langs->trans("Banking4DolibarrOriginAmount"), 'checked' => 0),
    'br.original_currency' => array('label' => $langs->trans("Banking4DolibarrOriginCurrency"), 'checked' => 0),
    'br.commission' => array('label' => $langs->trans("Banking4DolibarrCommissionAmount"), 'checked' => 0),
    'br.commission_currency' => array('label' => $langs->trans("Banking4DolibarrCommissionCurrency"), 'checked' => 0),
    'br.last_update_date' => array('label' => $langs->trans("DateLastModification"), 'checked' => 0, 'position' => 500),
    'br.tms' => array('label' => $langs->trans("DateModification"). " (D)", 'checked' => 0, 'position' => 500),
);


/*
 * Actions
 */

if ($remote_bank_account_id != 0) {
    // Do we click on purge search criteria ?
    if ($search_remove_btn) {
		$search_id_record = '';
		$search_label = '';
		$search_comment = '';
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
		$search_category = '';
		$search_original_country = '';
		$search_original_amount = '';
		$search_original_currency = '';
		$search_commission = '';
		$search_commission_currency = '';
		$search_last_update_date_start = '';
		$search_last_update_date_end = '';
		$search_tms_start = '';
		$search_tms_end = '';
		$search_debit = '';
		$search_credit = '';
		$view_mode = 1;
		$toselect = '';
	}
    if ($search_record_type == -1) $search_record_type = "";
    if ($view_mode != 1 && $view_mode != 2) $view_mode = 2;
    $search_debit = price2num(str_replace('-', '', $search_debit));
    $search_credit = price2num(str_replace('-', '', $search_credit));

    $param = '&id=' . urlencode($id) . '&state=' . urlencode($state) . '&view_mode=' . urlencode($view_mode);
    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
    if ($search_id_record) $param .= '&search_id_record=' . urlencode($search_id_record);
    if ($search_label) $param .= '&search_label=' . urlencode($search_label);
    if ($search_comment) $param .= '&search_comment=' . urlencode($search_comment);
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
    if ($search_record_type) $param .= '&search_record_type=' . urlencode($search_record_type);
    if ($search_category) $param .= '&search_category=' . urlencode($search_category);
    if ($search_original_country) $param .= '&search_original_country=' . urlencode($search_original_country);
    if ($search_original_amount) $param .= '&search_original_amount=' . urlencode($search_original_amount);
    if ($search_original_currency) $param .= '&search_original_currency=' . urlencode($search_original_currency);
    if ($search_commission) $param .= '&search_commission=' . urlencode($search_commission);
    if ($search_commission_currency) $param .= '&search_commission_currency=' . urlencode($search_commission_currency);
	if (dol_strlen($search_last_update_date_start) > 0) $param .= '&search_last_update_date_startmonth=' . GETPOST('search_last_update_date_startmonth', 'int') . '&search_last_update_date_startday=' . GETPOST('search_last_update_date_startday', 'int') . '&search_last_update_date_startyear=' . GETPOST('search_last_update_date_startyear', 'int');
	if (dol_strlen($search_last_update_date_end) > 0) $param .= '&search_last_update_date_endmonth=' . GETPOST('search_last_update_date_endmonth', 'int') . '&search_last_update_date_endday=' . GETPOST('search_last_update_date_endday', 'int') . '&search_last_update_date_endyear=' . GETPOST('search_last_update_date_endyear', 'int');
	if (dol_strlen($search_tms_start) > 0) $param .= '&search_tms_startmonth=' . GETPOST('search_tms_startmonth', 'int') . '&search_tms_startday=' . GETPOST('search_tms_startday', 'int') . '&search_tms_startyear=' . GETPOST('search_tms_startyear', 'int');
	if (dol_strlen($search_tms_end) > 0) $param .= '&search_tms_endmonth=' . GETPOST('search_tms_endmonth', 'int') . '&search_tms_endday=' . GETPOST('search_tms_endday', 'int') . '&search_tms_endyear=' . GETPOST('search_tms_endyear', 'int');
    if ($search_debit) $param .= '&search_debit=' . urlencode($search_debit);
    if ($search_credit) $param .= '&search_credit=' . urlencode($search_credit);
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

    if (empty($reshook)) {
        if ($action == 'confirm_b4d_valid_links') {
            if (empty($toselect) && is_array($_SESSION['b4dbankrecordsprelinklist_toselect_' . $id])) {
                $toselect = $_SESSION['b4dbankrecordsprelinklist_toselect_' . $id];
                unset($_SESSION['b4dbankrecordsprelinklist_toselect_' . $id]);
            }
            $payment_modes = $_SESSION['b4dbankrecordsprelinklist_payment_modes_' . $id];
            unset($_SESSION['b4dbankrecordsprelinklist_payment_modes_' . $id]);
        }

        $error = 0;
        // Mass actions. Controls on number of lines checked.
        $maxformassaction = (empty($conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS) ? 1000 : $conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
        if (in_array($action, array('b4d_valid_links', 'confirm_b4d_valid_links')) && count($toselect) < 1) {
            $error++;
            setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
        }
        if (!$error && is_array($toselect) && count($toselect) > $maxformassaction) {
            setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
            $error++;
        }

        if (!$error && $action == 'confirm_b4d_valid_links' && !empty($toselect)) {
            $nbtotalofrecords = GETPOST('nbtotalofrecords', 'int');

            $budgetinsight->db->begin();
            $result = $budgetinsight->validPreLinks($user, $id, '', $toselect, $payment_modes);
            if ($result < 0) {
                setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
                $error++;
            }

            if (!$error && $nbtotalofrecords == count($toselect)) {
                $result = $budgetinsight->purgePreLink($id);
                if ($result < 0) {
                    setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
                    $error++;
                }
            }

            if ($error) {
                $budgetinsight->db->rollback();
            } else {
                $budgetinsight->db->commit();
                setEventMessage($langs->trans("Banking4DolibarrValidPreLinkSuccess"));
                if ($nbtotalofrecords == count($toselect)) {
                    $budgetinsight->closeAutoLinkBankRecords($id, $state);

                    header("Location: " . dol_buildpath('/banking4dolibarr/bankrecords.php', 1) . '?restore_lastsearch_values=1&id=' . $id);
                    exit();
                }
                $action = '';
            }
        } elseif (!$error && $action == 'cancel') {
            $budgetinsight->closeAutoLinkBankRecords($id, $state);

            header("Location: " . dol_buildpath('/banking4dolibarr/bankrecords.php', 1) . '?restore_lastsearch_values=1&id=' . $id);
            exit();
        }
    }
}
if (empty($action) || $action == 'list') $toselect = array();


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formdictionary = new FormDictionary($db);
$budgetinsightbankrecord = new BudgetInsightBankRecord($db);

$help_url='EN:Banking4Dolibarr_EN|FR:Banking4Dolibarr_FR|ES:Banking4Dolibarr_ES';
llxHeader('', $langs->trans('FinancialAccount') . ' - ' . $langs->trans('Banking4DolibarrBankRecords'), $help_url, '', 0, 0, '', array(
    '/banking4dolibarr/css/banking4dolibarr.css.php',
));

// Bank card
//--------------------------------------------------------
$head=bank_prepare_head($object);
dol_fiche_head($head,'b4d_bank_records', $langs->trans("FinancialAccount"), 0, 'account');

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

dol_fiche_end();

if ($remote_bank_account_id != 0) {
    // Form Confirm
    //--------------------------------------------------------
    $formconfirm = '';

    // Confirm refresh
    if ($action == 'b4d_valid_links' && count($toselect) > 0) {
        $_SESSION['b4dbankrecordsprelinklist_toselect_' . $id] = $toselect;
        $payment_modes = array();
        foreach ($_POST as $k => $v) {
            if (preg_match('/^payment_mode_(\d+)$/', $k, $matches) && $v > 0) {
                $payment_modes[$matches[1]] = $v;
            }
        }
        $_SESSION['b4dbankrecordsprelinklist_payment_modes_' . $id] = $payment_modes;

        $nbtotalofrecords = GETPOST('nbtotalofrecords', 'int');
        $formconfirm = $form->formconfirm($base_url . '&nbtotalofrecords=' . $nbtotalofrecords, $langs->trans('Banking4DolibarrValidLinks'), $langs->trans("Banking4DolibarrConfirmValidLinks"), 'confirm_b4d_valid_links', '', 0, 1);
    }

    $parameters = array('base_url' => $base_url);
    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
    elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;
    print $formconfirm;

    // List
    //--------------------------------------------------------
    $sql = 'SELECT DISTINCT ';
    $sql .= ' br.rowid, br.id_record, br.id_account,';
    $sql .= ' br.label, br.comment, CONCAT(' . $db->ifsql("cb4dbrsc.rowid IS NULL", "''", "CONCAT(cb4dbrsc.label, ' - ')") . ', cb4dbrc.label) AS category_label, br.record_type, br.original_country, br.original_amount, br.original_currency,';
    $sql .= ' br.commission, br.commission_currency, br.amount, br.coming, br.deleted_date, br.record_date, br.rdate, br.bdate,';
    $sql .= ' br.vdate, br.date_scraped, br.last_update_date, br.tms,';
    $sql .= ' brpl.rowid AS pre_link_id, brpl.element_type, brpl.element_id, brpl.fk_bank,';
	$sql .= ' ul.amount AS unpaid_amount, ul.dateb AS unpaid_dateb, ul.datee AS unpaid_datee';
    // Add fields from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_pre_link as brpl';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record as br ON br.rowid = brpl.fk_bank_record';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON ba.rowid = brpl.fk_bank_account';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'banking4dolibarr_unpaid_list as ul ON ul.element_type = brpl.element_type AND ul.element_id = brpl.element_id AND ul.entity = ba.entity';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_record_category as cb4dbrc ON cb4dbrc.rowid = br.id_category';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_record_category as cb4dbrsc ON cb4dbrsc.rowid = cb4dbrc.id_parent_category';
    // Add join from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= ' WHERE ba.entity IN (' . getEntity('bank_account') . ')';
    $sql .= ' AND brpl.fk_bank_account = ' . $id;
    if ($search_id_record) $sql .= natural_search('br.id_record', $search_id_record);
    if ($search_label) $sql .= natural_search('br.label', $search_label);
    if ($search_comment) $sql .= natural_search('br.comment', $search_comment);
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
    if ($search_record_type) $sql .= natural_search("br.record_type", $search_record_type);
    if ($search_category) $sql .= natural_search("cb4dbrc.label", $search_category);
    if ($search_original_country) $sql .= natural_search("br.original_country", $search_original_country);
    if ($search_original_amount) $sql .= natural_search("br.original_amount", $search_original_amount, 1);
    if ($search_original_currency) $sql .= natural_search("br.original_currency", $search_original_currency);
    if ($search_commission) $sql .= natural_search("br.commission", $search_commission, 1);
    if ($search_commission_currency) $sql .= natural_search("br.commission_currency", $search_commission_currency);
	if (dol_strlen($search_last_update_date_start) > 0) $sql .= " AND br.last_update_date >= '" . $db->idate($search_last_update_date_start) . "'";
	if (dol_strlen($search_last_update_date_end) > 0) $sql .= " AND br.last_update_date <= '" . $db->idate($search_last_update_date_end) . "'";
	if (dol_strlen($search_tms_start) > 0) $sql .= " AND br.tms >= '" . $db->idate($search_tms_start) . "'";
	if (dol_strlen($search_tms_end) > 0) $sql .= " AND br.tms <= '" . $db->idate($search_tms_end) . "'";
    if ($search_debit) $sql .= natural_search("- br.amount", $search_debit, 1) . ' AND br.amount < 0';
    if ($search_credit) $sql .= natural_search("br.amount", $search_credit, 1) . ' AND br.amount > 0';
    // Add where from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

    if ($sortfield == 'br.vdate') $sql .= " ORDER BY " . $db->ifsql("br.vdate IS NULL", "br.record_date", "br.vdate") . " " . $sortorder . ($view_mode == 1 ? ',br.rowid ASC' : ',brpl.element_type ASC,brpl.element_id ASC,brpl.fk_bank ASC');
    else $sql .= $db->order($sortfield . ($view_mode == 1 ? ',br.rowid' : ',brpl.element_type,brpl.element_id,brpl.fk_bank'), $sortorder . ($view_mode == 1 ? ',ASC' : ',ASC,ASC,ASC'));

    // Count total nb of records
    $nbtotalofrecords = '';
    $resql = $db->query('SELECT COUNT(*) As nb FROM (' . $sql . ') As count_table');
    if ($resql && ($obj = $db->fetch_object($resql))) {
        $nbtotalofrecords = $obj->nb;
    } else {
        setEventMessage($db->lasterror(), 'errors');
    }

    $sql .= $db->plimit($limit + 1, $offset);

    $resql = $db->query($sql);
    if ($resql) {
        $title = $langs->trans('Banking4DolibarrPreLinkListOfBankRecords');

        $num = $db->num_rows($resql);

        $arrayofselected = is_array($toselect) ? $toselect : array();

        // Lignes des champs de filtre
        print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&state=' . $state . '">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="page" value="' . $page . '">';
        print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
        print '<input type="hidden" name="nbtotalofrecords" value="' . $nbtotalofrecords . '">';

        $morhtmlright = '<input type="submit" name="button_cancel" class="button" value="' . $langs->trans("Banking4DolibarrFinish") . '">';
        $morhtmlright .= '&nbsp;&nbsp;';
        $morhtmlright .= '<input type="submit" name="button_valid_links" class="button" value="' . $langs->trans("Banking4DolibarrValidLinks") . '">';

        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, $morhtmlright, '', $limit);

        $i = 0;

        $moreforfilter = '';

        // View mode
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('Banking4DolibarrPreLinkViewListMode') . ': ';
        $moreforfilter .= $form->selectarray('view_mode', array(
            1 => $langs->trans("Banking4DolibarrPreLinkViewListModeBankRecord"),
            2 => $langs->trans("Banking4DolibarrPreLinkViewListModeObject"),
        ), $view_mode, 0, 0, 0, '', 0, 0, 0, '', ' minwidth200');
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
        $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

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
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_label" value="' . dol_escape_htmltag($search_label) . '">';
        print '</td>';
        if (!empty($arrayfields['br.comment']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_comment" value="' . dol_escape_htmltag($search_comment) . '">';
            print '</td>';
        }
        if (!empty($arrayfields['br.record_date']['checked'])) {
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
        }
        if (!empty($arrayfields['br.vdate']['checked'])) {
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
        if (!empty($arrayfields['br.record_type']['checked'])) {
            print '<td align="center" class="liste_titre">';
            print $formdictionary->select_dictionary('banking4dolibarr', 'banking4dolibarrbankrecordtypes', $search_record_type, 'search_record_type', 1);
            print '</td>';
        }
        if (!empty($arrayfields['br.id_category']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_category" value="' . dol_escape_htmltag($search_category) . '">';
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
        print '<td class="liste_titre right">';
        print '<input class="flat" size="6" type="text" name="search_debit" value="' . dol_escape_htmltag($search_debit) . '">';
        print '</td>';
        print '<td class="liste_titre right">';
        print '<input class="flat" size="6" type="text" name="search_credit" value="' . dol_escape_htmltag($search_credit) . '">';
        print '</td>';
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
        print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"], 'br.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['br.comment']['checked'])) print_liste_field_titre($arrayfields['br.comment']['label'], $_SERVER["PHP_SELF"], 'br.comment', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['br.record_date']['checked'])) print_liste_field_titre($arrayfields['br.record_date']['label'].$filter_date_help, $_SERVER["PHP_SELF"], 'br.record_date', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.vdate']['checked'])) print_liste_field_titre($arrayfields['br.vdate']['label'].$filter_date_help, $_SERVER["PHP_SELF"], 'br.vdate', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.rdate']['checked'])) print_liste_field_titre($arrayfields['br.rdate']['label'].$filter_date_help, $_SERVER["PHP_SELF"], 'br.rdate', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.bdate']['checked'])) print_liste_field_titre($arrayfields['br.bdate']['label'].$filter_date_help, $_SERVER["PHP_SELF"], 'br.bdate', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.date_scraped']['checked'])) print_liste_field_titre($arrayfields['br.date_scraped']['label'].$filter_date_help, $_SERVER["PHP_SELF"], "br.date_scraped", "", $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.record_type']['checked'])) print_liste_field_titre($langs->trans("Type"), $_SERVER["PHP_SELF"], "br.record_type", "", $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.id_category']['checked'])) print_liste_field_titre($arrayfields['br.id_category']['label'], $_SERVER["PHP_SELF"], 'cb4dbrc.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['br.original_country']['checked'])) print_liste_field_titre($arrayfields['br.original_country']['label'], $_SERVER["PHP_SELF"], 'br.original_country', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['br.original_amount']['checked'])) print_liste_field_titre($arrayfields['br.original_amount']['label'], $_SERVER["PHP_SELF"], 'br.original_amount', '', $param, 'align="right"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.original_currency']['checked'])) print_liste_field_titre($arrayfields['br.original_currency']['label'], $_SERVER["PHP_SELF"], 'br.original_currency', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.commission']['checked'])) print_liste_field_titre($arrayfields['br.commission']['label'], $_SERVER["PHP_SELF"], 'br.commission', '', $param, 'align="right"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.commission_currency']['checked'])) print_liste_field_titre($arrayfields['br.commission_currency']['label'], $_SERVER["PHP_SELF"], 'br.commission_currency', '', $param, 'align="center"', $sortfield, $sortorder);
        // Hook fields
        $parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
        $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (!empty($arrayfields['br.last_update_date']['checked'])) print_liste_field_titre($arrayfields['br.last_update_date']['label'].$filter_date_help, $_SERVER["PHP_SELF"], "br.last_update_date", "", $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['br.tms']['checked'])) print_liste_field_titre($arrayfields['br.tms']['label'].$filter_date_help, $_SERVER["PHP_SELF"], "br.tms", "", $param, 'align="center"', $sortfield, $sortorder);
        print_liste_field_titre($langs->trans("Debit"), $_SERVER["PHP_SELF"], 'br.amount', '', $param, 'align="right"', $sortfield, $sortorder);
        print_liste_field_titre($langs->trans("Credit"), $_SERVER["PHP_SELF"], 'br.amount', '', $param, 'align="right"', $sortfield, $sortorder);
        print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
        print '</tr>' . "\n";

        $i = 0;
        $totalarray = array();
        $print_lines = array();
        $object_cached = array();
        $payment_type_cached = array();
        $last_key = '';
        while ($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);

            $object_key = $obj->element_type . '_' . $obj->element_id . '_' . $obj->fk_bank;
            $key = $view_mode == 1 ? $obj->rowid : $object_key;

            if (!isset($object_cached[$object_key])) {
                if (!empty($obj->element_type)) {
                    $srcobject = opendsi_get_object($db, $obj->element_type, $obj->element_id);
                } else {
                    $srcobject = new AccountLine($db);
                    $srcobject->fetch($obj->fk_bank);
                }
                $srcobject->fetch_thirdparty();

                $object_cached[$object_key] = $srcobject;
            }

            $object_temp = $object_cached[$object_key];
            $amount = $object_temp->element == 'bank' ? $object_temp->amount : $obj->unpaid_amount;

            //-----------------------------------------------------
            // Bank record - Begin
            //-----------------------------------------------------
            ob_start();

            $padding_class = $view_mode != 1 ? ' class="b4d_padding"' : '';
            if (!empty($arrayfields['br.rowid']['checked'])) {
                print '<td' . $padding_class . '>';
                print $obj->rowid;
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.id_record']['checked'])) {
                print '<td' . (empty($arrayfields['br.rowid']['checked']) ? $padding_class : '') . '>';
                print $obj->id_record;
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            print '<td' . (empty($arrayfields['br.rowid']['checked']) || empty($arrayfields['br.id_record']['checked']) ? $padding_class : '') . '>';
            print $obj->label;
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!empty($arrayfields['br.comment']['checked'])) {
                print '<td>';
                print $obj->comment;
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!$i) $totalarray['nbfield_pre_record_date'] = $totalarray['nbfield'];
            if (!empty($arrayfields['br.record_date']['checked'])) {
                print '<td align="center">';
                if (!empty($obj->record_date)) print dol_print_date($db->jdate($obj->record_date), 'day');
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.vdate']['checked'])) {
                print '<td align="center">';
                if (!empty($obj->vdate)) print dol_print_date($db->jdate($obj->vdate), 'day');
                elseif (!empty($obj->record_date)) print dol_print_date($db->jdate($obj->record_date), 'day');
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.rdate']['checked'])) {
                print '<td align="center">';
                if (!empty($obj->rdate)) print dol_print_date($db->jdate($obj->rdate), 'day');
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.bdate']['checked'])) {
                print '<td align="center">';
                if (!empty($obj->bdate)) print dol_print_date($db->jdate($obj->bdate), 'day');
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.date_scraped']['checked'])) {
                print '<td align="center">';
                if (!empty($obj->date_scraped)) print dol_print_date($db->jdate($obj->date_scraped), 'dayhour');
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!$i) $totalarray['nbfield_pre_record_type'] = $totalarray['nbfield'];
            if (!empty($arrayfields['br.record_type']['checked'])) {
                print '<td align="center">';
                print $budgetinsightbankrecord->LibType($obj->record_type);
                if ($budgetinsightbankrecord->getDolibarrPaymentModeId($obj->record_type) < 0) {
                    print ' : ';
                    $form->select_types_paiements(isset($payment_modes[$obj->pre_link_id]) ? $payment_modes[$obj->pre_link_id] : GETPOST('payment_mode_' . $obj->pre_link_id, 'alpha'),
                        'payment_mode_' . $obj->pre_link_id, $obj->amount < 0 ? 'DBIT' : 'CRDT', 0, 1);
                }
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.id_category']['checked'])) {
                print '<td>';
                print $obj->category_label;
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.original_country']['checked'])) {
                print '<td>';
                print $obj->original_country;
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.original_amount']['checked'])) {
                print '<td class="right">';
                if ($obj->original_amount > 0) print price($obj->original_amount);
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.original_currency']['checked'])) {
                print '<td align="center">';
                if (!empty($obj->original_currency)) print $langs->trans('Currency' . $obj->original_currency);
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.commission']['checked'])) {
                print '<td class="right">';
                if ($obj->commission > 0) print price($obj->commission);
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.commission_currency']['checked'])) {
                print '<td align="center">';
                if (!empty($obj->commission_currency)) print $langs->trans('Currency' . $obj->commission_currency);
                print "</td>\n";
                if (!$i) $totalarray['nbfield']++;
            }
            // Fields from hook
            $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
            $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (!empty($arrayfields['br.last_update_date']['checked'])) {
                print '<td align="center">';
                print dol_print_date($db->jdate($obj->last_update_date), 'dayhour', 'tzuser');
                print '</td>';
                if (!$i) $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['br.tms']['checked'])) {
                print '<td align="center">';
                print dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
                print '</td>';
                if (!$i) $totalarray['nbfield']++;
            }
            print '<td class="right">';
            if ($obj->amount < 0) {
                print price($obj->amount * -1);
            }
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            print '<td class="right">';
            if ($obj->amount > 0) {
                print price($obj->amount);
            }
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            // Action column
            print '<td class="right">';
            if ($view_mode != 1)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            {
                $selected = 0;
                if (in_array($obj->pre_link_id, $arrayofselected)) $selected = 1;
                print ' <input id="cb_' . $obj->pre_link_id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->pre_link_id . '"' . ($selected ? ' checked="checked"' : '') . '>';
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;

            $bank_record_line = ob_get_contents();
            ob_end_clean();
            //-----------------------------------------------------
            // Bank record - End
            //-----------------------------------------------------

            //-----------------------------------------------------
            // Object - Begin
            //-----------------------------------------------------
            ob_start();

            $colspan = $totalarray['nbfield_pre_record_date'];
            print '<td colspan="' . $colspan . '"' . ($view_mode == 1 ? ' class="b4d_padding"' : '') . '>';
            if (isset($object_temp)) print $object_temp->getNomUrl(1);
            $infos_to_print = array();
			if (!empty($object_temp->ref_client)) $infos_to_print[] = $langs->trans('RefCustomer') . ' : ' . $object_temp->ref_client;
			if (!empty($object_temp->ref_supplier)) $infos_to_print[] = $langs->trans('RefSupplier') . ' : ' . $object_temp->ref_supplier;
			if (is_object($object_temp->thirdparty)) {
				$langs->load('companies');
				$infos_to_print[] = $langs->trans('ThirdParty') . ' : ' . $object_temp->thirdparty->getNomUrl(1);
			}
			if (!empty($infos_to_print)) print ' ( ' . implode('; ', $infos_to_print) . ' )';
            print "</td>\n";
            if (!empty($arrayfields['br.record_date']['checked'])) {
                print '<td align="center">';
				if (isset($object_temp) && $object_temp->element == 'bank') print dol_print_date($db->jdate($object_temp->dateo), 'day');
				else {
					print dol_print_date($db->jdate($obj->unpaid_dateb), 'day');
					if (!empty($obj->unpaid_datee)) print ' ' . $langs->trans('to') . ' ' . dol_print_date($db->jdate($obj->unpaid_datee), 'day');
				}
                print "</td>\n";
                $colspan++;
            }
            if (!empty($arrayfields['br.vdate']['checked'])) {
                print '<td align="center">';
                if (isset($object_temp) && $object_temp->element == 'bank') print dol_print_date($db->jdate($object_temp->datev), 'day');
                print "</td>\n";
                $colspan++;
            }
            $colspan2 = $colspan < $totalarray['nbfield_pre_record_type'] ? $totalarray['nbfield_pre_record_type'] - $colspan : 0;
            if ($colspan2 > 0) print '<td colspan="' . $colspan2 . '">' . "</td>\n";
            if (!empty($arrayfields['br.record_type']['checked'])) {
                print '<td align="center">';
                if (isset($object_temp) && $object_temp->element == 'bank') {
                    if (!isset($payment_type_cached[$object_temp->type])) {
                        $payment_type = ($langs->trans("PaymentTypeShort" . $object_temp->type) != "PaymentTypeShort" . $object_temp->type) ? $langs->trans("PaymentTypeShort" . $object_temp->type) : $langs->getLabelFromKey($db, $object_temp->type, 'c_paiement', 'code', 'libelle', '', 1);
                        if ($payment_type == 'SOLD') $payment_type = '';
                        $payment_type_cached[$object_temp->type] = $payment_type;
                    }
                    print $payment_type_cached[$object_temp->type];
                }
                print "</td>\n";
                $colspan2++;
            }
            $colspan3 = $colspan + $colspan2 + 3 < $totalarray['nbfield'] ? $totalarray['nbfield'] - ($colspan + $colspan2 + 3) : 0;
            if ($colspan3 > 0) print '<td colspan="' . $colspan3 . '">' . "</td>\n";
            print '<td class="nowrap right">';
            if ($amount < 0) {
                print price($amount * -1);
            }
            print "</td>\n";
            print '<td class="nowrap right">';
            if ($amount > 0) {
                print price($amount);
            }
            print "</td>\n";
            // Action column
            print '<td class="right">';
            if ($view_mode == 1)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            {
                $selected = 0;
                if (in_array($obj->pre_link_id, $arrayofselected)) $selected = 1;
                print ' <input id="cb_' . $obj->pre_link_id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->pre_link_id . '"' . ($selected ? ' checked="checked"' : '') . '>';
            }
            print '</td>';

            $object_line = ob_get_contents();
            ob_end_clean();
            //-----------------------------------------------------
            // Object - End
            //-----------------------------------------------------

            if ($key != $last_key) {
                if (!empty($print_lines)) {
                    print '<tr class="oddeven" id="' . $last_key . '">' . $print_lines['header'] . '</tr>' . "\n";
                    $nb_lines = count($print_lines['lines']);

                    for ($idx = 0; $idx < $nb_lines; $idx++) {
                        $class = ' b4d_middle_line';
                        if ($idx == 0) $class .= ' b4d_first_line';
                        if ($idx == $nb_lines - 1) $class .= ' b4d_last_line';
                        print '<tr class="oddeven' . $class . '">' . $print_lines['lines'][$idx] . '</tr>' . "\n";
                    }
                }

                $print_lines = [
                    'header' => $view_mode == 1 ? $bank_record_line : $object_line,
                    'lines' => array(),
                ];
            }
            $print_lines['lines'][] = $view_mode == 1 ? $object_line : $bank_record_line;

            $last_key = $key;

            $i++;
        }

        // Print lines
        print '<tr class="oddeven" id="' . $last_key . '">' . $print_lines['header'] . '</tr>' . "\n";
        $nb_lines = count($print_lines['lines']);
        for ($idx = 0; $idx < $nb_lines; $idx++) {
            print '<tr class="oddeven">' . $print_lines['lines'][$idx] . '</tr>' . "\n";
        }

        $db->free($resql);

        $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        print '</table>' . "\n";

        print '<div class="right">';
        print '<input type="submit" name="button_cancel" class="button" value="' . $langs->trans("Banking4DolibarrFinish") . '">';
        print '&nbsp;&nbsp;';
        print '<input type="submit" name="button_valid_links" class="button" value="' . $langs->trans("Banking4DolibarrValidLinks") . '">';
        print '</div>';

        print '</div>' . "\n";

        print '</form>' . "\n";
    } else {
        dol_print_error($db);
    }
} else {
    print $langs->trans("None");
}

// End of page
llxFooter();
$db->close();
