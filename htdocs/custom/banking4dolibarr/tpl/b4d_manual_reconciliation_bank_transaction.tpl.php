<?php
/* Copyright (C) 2020      Open-DSI              <support@open-dsi.fr>
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
 *  \file		htdocs/banking4dolibarr/tpl/b4d_manual_reconciliation_bank_transaction.tpl.php
 *  \ingroup	banking4dolibarr
 *  \brief		Template to show manuel reconciliation content for the type 'bank_transaction'
 */


require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/bankcateg.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
if (version_compare(DOL_VERSION, "11.0.0") >= 0) {
    require_once DOL_DOCUMENT_ROOT . '/salaries/class/paymentsalary.class.php';
} else {
    require_once DOL_DOCUMENT_ROOT . '/compta/salaries/class/paymentsalary.class.php';
}
require_once DOL_DOCUMENT_ROOT . '/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';

$langs->loadLangs(array("banks", "bills", "categories", "companies", "margins", "salaries", "loan", "donations", "trips", "members", "compta", "accountancy"));

$dateop = dol_mktime(12, 0, 0, GETPOST("opmonth", 'int'), GETPOST("opday", 'int'), GETPOST("opyear", 'int'));
$debit = GETPOST("debit", 'alpha');
$credit = GETPOST("credit", 'alpha');
$search_type = GETPOST("search_type", 'alpha');
$search_accountancy_code = GETPOST('search_accountancy_code', 'alpha') ? GETPOST('search_accountancy_code', 'alpha') : GETPOST('accountancy_code', 'alpha');
$search_bid = GETPOST("search_bid", "int") ? GETPOST("search_bid", "int") : GETPOST("bid", "int");
$search_ref = GETPOST('search_ref', 'alpha');
$search_description = GETPOST("search_description", 'alpha');
$search_dt_start = dol_mktime(0, 0, 0, GETPOST('search_start_dtmonth', 'int'), GETPOST('search_start_dtday', 'int'), GETPOST('search_start_dtyear', 'int'));
$search_dt_end = dol_mktime(0, 0, 0, GETPOST('search_end_dtmonth', 'int'), GETPOST('search_end_dtday', 'int'), GETPOST('search_end_dtyear', 'int'));
$search_dv_start = dol_mktime(0, 0, 0, GETPOST('search_start_dvmonth', 'int'), GETPOST('search_start_dvday', 'int'), GETPOST('search_start_dvyear', 'int'));
$search_dv_end = dol_mktime(0, 0, 0, GETPOST('search_end_dvmonth', 'int'), GETPOST('search_end_dvday', 'int'), GETPOST('search_end_dvyear', 'int'));
$search_thirdparty = GETPOST("search_thirdparty", 'alpha') ? GETPOST("search_thirdparty", 'alpha') : GETPOST("thirdparty", 'alpha');
$search_req_nb = GETPOST("req_nb", 'alpha');
$search_num_releve = GETPOST("search_num_releve", 'alpha');
$search_conciliated = GETPOST("search_conciliated", 'int');
$num_releve = GETPOST("num_releve", "alpha");
$cat = GETPOST("cat");
if (empty($dateop)) $dateop = -1;

if ($action == 'update_manual_reconciliation_type') {
	$min_offset_dates = $object->remaining_amount_to_link < 0 ? $conf->global->BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES : $conf->global->BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES;
	$max_offset_dates = $object->remaining_amount_to_link < 0 ? $conf->global->BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES : $conf->global->BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES;
    $search_dt_start = $object->record_date - ((isset($min_offset_dates) && $min_offset_dates !== '' ? $min_offset_dates : 15) * 24 * 60 * 60);
    $search_dt_end = $object->record_date + ((isset($max_offset_dates) && $max_offset_dates !== '' ? $max_offset_dates : 15) * 24 * 60 * 60);
    $search_type = $object->getDolibarrPaymentModeCode($object->record_type, 1);
    $search_type = !is_numeric($search_type) ? $search_type : '';
    $debit = $object->remaining_amount_to_link < 0 ? price2num($object->remaining_amount_to_link) : '';
    $credit = $object->remaining_amount_to_link > 0 ? price2num($object->remaining_amount_to_link) : '';
    $search_conciliated = -1;
    $limit = $conf->liste_limit;
} else {
    $limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
    $sortfield = GETPOST('sortfield', 'alpha');
    $sortorder = GETPOST('sortorder', 'alpha');
    $page = GETPOST("page", 'int');
    $pageplusone = GETPOST("pageplusone", 'int');
}

if ($pageplusone) $page = $pageplusone - 1;
if (empty($page) || $page == -1) {
    $page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = 'ASC,ASC,ASC';
if (!$sortfield) $sortfield = 'b.datev,b.dateo,b.rowid';

// Initialize technical object to manage context to save list fields
$contextpage = 'b4dmanualreconciliationbanklist';

// fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label('banktransaction');
$search_array_options = $extrafields->getOptionalsFromPost($extralabels, '', 'search_');

$arrayfields = array(
    'b.rowid' => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'description' => array('label' => $langs->trans("Description"), 'checked' => 1),
    'b.dateo' => array('label' => $langs->trans("DateOperationShort"), 'checked' => 1),
    'b.datev' => array('label' => $langs->trans("DateValueShort"), 'checked' => 1),
    'type' => array('label' => $langs->trans("Type"), 'checked' => 1),
    'b.num_chq' => array('label' => $langs->trans("Numero"), 'checked' => 1),
    'bu.label' => array('label' => $langs->trans("ThirdParty"), 'checked' => 1, 'position' => 500),
    'b.debit' => array('label' => $langs->trans("Debit"), 'checked' => 1, 'position' => 600),
    'b.credit' => array('label' => $langs->trans("Credit"), 'checked' => 1, 'position' => 605),
    'b.num_releve' => array('label' => $langs->trans("AccountStatement"), 'checked' => 1, 'position' => 1010),
    'b.conciliated' => array('label' => $langs->trans("Conciliated"), 'checked' => 1, 'position' => 1020),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) {
        if (!empty($extrafields->attribute_list[$key])) $arrayfields["ef." . $key] = array('label' => $extrafields->attribute_label[$key], 'checked' => (($extrafields->attribute_list[$key] < 0) ? 0 : 1), 'position' => $extrafields->attribute_pos[$key], 'enabled' => (abs($extrafields->attribute_list[$key]) != 3 && $extrafields->attribute_perms[$key]));
    }
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $search_dt_start = '';
    $search_dt_end = '';
    $search_dv_start = '';
    $search_dv_end = '';
    $search_description = "";
    $search_type = "";
    $debit = "";
    $credit = "";
    $search_bid = "";
    $search_ref = "";
    $search_req_nb = '';
    $search_thirdparty = '';
    $search_num_releve = '';
    $search_conciliated = '';
    $toselect = array();
}
if ((empty($action))) $toselect = array();

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formaccounting = new FormAccounting($db);

$companystatic = new Societe($db);
$bankaccountstatic = new Account($db);

$societestatic = new Societe($db);
$userstatic = new User($db);
$chargestatic = new ChargeSociales($db);
$loanstatic = new Loan($db);
$memberstatic = new Adherent($db);
$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);
$paymentvatstatic = new TVA($db);
$paymentsalstatic = new PaymentSalary($db);
$donstatic = new Don($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);
$bankstatic = new Account($db);
$banklinestatic = new AccountLine($db);

$now = dol_now();


$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro as conciliated, b.num_releve, b.num_chq,";
$sql .= " b.fk_account, b.fk_type, " . $db->ifsql("brl.rowid IS NULL", "0", "1") . " as warning_already_linked,";
$sql .= " ba.rowid as bankid, ba.ref as bankref,";
$sql .= " bu.url_id,";
$sql .= " s.nom, s.name_alias, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef." . $key . ' as options_' . $key : '');
$sql .= " FROM " . MAIN_DB_PREFIX . "bank as b";
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON ba.rowid = b.fk_account';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl ON brl.fk_bank = b.rowid';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record as br ON br.rowid = brl.fk_bank_record';
$sql .= ' LEFT JOIN (';
$sql .= '   SELECT brl.fk_bank_record, count(DISTINCT brl.fk_bank) as nb';
$sql .= '   FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl';
$sql .= '   GROUP BY brl.fk_bank_record';
$sql .= ' ) as brlu ON brlu.fk_bank_record = brl.fk_bank_record';
$sql .= ' LEFT JOIN (';
$sql .= '   SELECT DISTINCT brl.fk_bank';
$sql .= '   FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record_link as brl';
$sql .= '   WHERE brl.fk_bank_record = ' . $object->id;
$sql .= ' ) as brlal ON brlal.fk_bank = b.rowid';
if ($search_bid > 0) $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_class as l ON b.rowid = l.lineid';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_extrafields as ef on (b.rowid = ef.fk_object)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu ON bu.fk_bank = b.rowid AND type = 'company'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON bu.url_id = s.rowid";
$sql .= " WHERE ba.entity IN (" . getEntity('bank_account') . ")";
$sql .= " AND b.fk_account = " . $id;
$sql .= " AND b.fk_type != 'SOLD'";
$sql .= " AND " . $db->ifsql("brl.fk_bank_record IS NULL", "0", "brl.fk_bank_record") . " != " . $object->id;
$sql .= " AND (brl.rowid IS NULL OR (br.status = " . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED . " AND " . $db->ifsql("brlu.nb IS NULL", "0", "brlu.nb") . " <= 1))";
$sql .= " AND brlal.fk_bank IS NULL";
// Search period criteria
if (dol_strlen($search_dt_start) > 0) $sql .= " AND b.dateo >= '" . $db->idate($search_dt_start) . "'";
if (dol_strlen($search_dt_end) > 0) $sql .= " AND b.dateo <= '" . $db->idate($search_dt_end) . "'";
// Search period criteria
if (dol_strlen($search_dv_start) > 0) $sql .= " AND b.datev >= '" . $db->idate($search_dv_start) . "'";
if (dol_strlen($search_dv_end) > 0) $sql .= " AND b.datev <= '" . $db->idate($search_dv_end) . "'";
if ($search_ref) $sql .= natural_search("b.rowid", $search_ref, 1);
if ($search_req_nb) $sql .= natural_search("b.num_chq", $search_req_nb);
if ($search_num_releve) $sql .= natural_search("b.num_releve", $search_num_releve);
if ($search_conciliated != '' && $search_conciliated != '-1') $sql .= " AND b.rappro = " . $search_conciliated;
if ($search_thirdparty) $sql .= natural_search("s.nom", $search_thirdparty);
//if ($search_description) $sql .= natural_search("b.label", $search_description);       // Warning some text are just translation keys, not translated strings
if ($search_bid > 0) $sql .= " AND l.fk_categ=" . $search_bid;
if (!empty($search_type)) $sql .= " AND b.fk_type = '" . $db->escape($search_type) . "' ";
// Search criteria amount
$debit = price2num(str_replace('-', '', $debit));
$credit = price2num(str_replace('-', '', $credit));
if ($debit) $sql .= natural_search('- b.amount', $debit, 1) . ' AND b.amount < 0';
if ($credit) $sql .= natural_search('b.amount', $credit, 1) . ' AND b.amount > 0';
// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
$sql .= " GROUP BY b.rowid, b.dateo, b.datev, b.amount, b.label, b.rappro, b.num_releve, b.num_chq,";
$sql .= " b.fk_account, b.fk_type, " . $db->ifsql("brl.rowid IS NULL", "0", "1") . ",";
$sql .= " ba.rowid, ba.ref,";
$sql .= " bu.url_id,";
$sql .= " s.nom, s.name_alias, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
$nbtotalofpages = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    $nbtotalofpages = ceil($nbtotalofrecords / $limit);
}

if (($id > 0 || !empty($ref)) && ((string)$page == '')) {
    // We open a list of transaction of a dedicated account and no page was set by defaut
    // We force on last page.
    $page = ($nbtotalofpages - 1);
    $offset = $limit * $page;
    if ($page < 0) $page = 0;
}
if ($page >= $nbtotalofpages) {
    // If we made a search and result has low page than the page number we were on
    $page = ($nbtotalofpages - 1);
    $offset = $limit * $page;
    if ($page < 0) $page = 0;
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('/banking4dolibarr/tpl/b4d_manual_reconciliation_bank_transaction.tpl.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);

    $arrayofselected = is_array($toselect) ? $toselect : array();

    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
    if (!empty($search_ref)) $param .= '&search_ref=' . urlencode($search_ref);
    if (!empty($search_description)) $param .= '&search_description=' . urlencode($search_description);
    if (!empty($search_type)) $param .= '&search_type=' . urlencode($search_type);
    if (!empty($search_thirdparty)) $param .= '&search_thirdparty=' . urlencode($search_thirdparty);
    if (!empty($debit)) $param .= '&debit=' . urlencode($debit);
    if (!empty($credit)) $param .= '&credit=' . urlencode($credit);
    if (!empty($search_num_releve)) $param .= '&search_num_releve=' . urlencode($search_num_releve);
    if ($search_conciliated != '' && $search_conciliated != '-1') $param .= '&search_conciliated=' . urlencode($search_conciliated);
    if ($search_bid > 0) $param .= '&search_bid=' . urlencode($search_bid);
    if (dol_strlen($search_dt_start) > 0) $param .= '&search_start_dtmonth=' . GETPOST('search_start_dtmonth', 'int') . '&search_start_dtday=' . GETPOST('search_start_dtday', 'int') . '&search_start_dtyear=' . GETPOST('search_start_dtyear', 'int');
    if (dol_strlen($search_dt_end) > 0) $param .= '&search_end_dtmonth=' . GETPOST('search_end_dtmonth', 'int') . '&search_end_dtday=' . GETPOST('search_end_dtday', 'int') . '&search_end_dtyear=' . GETPOST('search_end_dtyear', 'int');
    if (dol_strlen($search_dv_start) > 0) $param .= '&search_start_dvmonth=' . GETPOST('search_start_dvmonth', 'int') . '&search_start_dvday=' . GETPOST('search_start_dvday', 'int') . '&search_start_dvyear=' . GETPOST('search_start_dvyear', 'int');
    if (dol_strlen($search_dv_end) > 0) $param .= '&search_end_dvmonth=' . GETPOST('search_end_dvmonth', 'int') . '&search_end_dvday=' . GETPOST('search_end_dvday', 'int') . '&search_end_dvyear=' . GETPOST('search_end_dvyear', 'int');
    if ($search_req_nb) $param .= '&req_nb=' . urlencode($search_req_nb);
    if (GETPOST("search_thirdparty", 'int')) $param .= '&thirdparty=' . urlencode(GETPOST("search_thirdparty", 'int'));
    if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);
    // Add $param from extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

    // Lines of title fields
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="page" value="' . $page . '">';
    if (GETPOST('bid')) print '<input type="hidden" name="bid" value="' . GETPOST("bid") . '">';

    $i = 0;

    print_barre_liste($langs->trans("BankTransactions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '', '', $limit);

    // We can add page now to param
    if ($page != '') $param .= '&page=' . urlencode($page);

    $moreforfilter = '';

    if (!empty($conf->categorie->enabled)) {
        // Categories
        if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
            $langs->load('categories');

            // Bank line
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_BANK_LINE, $search_bid, 'parent', null, null, 1);
            if (!empty($cate_arbo)) {
				$moreforfilter .= '<div class="divsearchfield">';
				$moreforfilter .= $langs->trans('RubriquesTransactions') . ' : ';
				$moreforfilter .= $form->selectarray('search_bid', $cate_arbo, $search_bid, 1);
				$moreforfilter .= '</div>';
			}
        }
    }

    if ($moreforfilter) {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>' . "\n";
    }

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields =  $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields
    $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . ' tableBodyScroll">' . "\n";
    print '<thead>';

    print '<tr class="liste_titre_filter">';
    if (!empty($arrayfields['b.rowid']['checked'])) {
        print '<td class="liste_titre" width="10%">';
        print '<input type="text" class="flat" name="search_ref" size="2" value="' . dol_escape_htmltag($search_ref) . '">';
        print '</td>';
    }
    if (!empty($arrayfields['description']['checked'])) {
        print '<td class="liste_titre" width="40%">';
      //  print '<input type="text" class="flat" name="search_description" size="10" value="'.dol_escape_htmltag($search_description).'">';
        print '</td>';
    }
    if (!empty($arrayfields['b.dateo']['checked'])) {
        print '<td class="liste_titre center" width="20%">';
		print '<div class="nowrap">';
		print $langs->trans('From') . ' ';
		print $form->selectDate($search_dt_start?$search_dt_start:-1, 'search_start_dt', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to') . ' ';
		print $form->selectDate($search_dt_end?$search_dt_end:-1, 'search_end_dt', 0, 0, 1);
		print '</div>';
        print '</td>';
    }
    if (!empty($arrayfields['b.datev']['checked'])) {
		print '<td class="liste_titre center" width="20%">';
		print '<div class="nowrap">';
		print $langs->trans('From') . ' ';
		print $form->selectDate($search_dv_start?$search_dv_start:-1, 'search_start_dv', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to') . ' ';
		print $form->selectDate($search_dv_end?$search_dv_end:-1, 'search_end_dv', 0, 0, 1);
		print '</div>';
		print '</td>';
    }
    if (!empty($arrayfields['type']['checked'])) {
        print '<td class="liste_titre" align="center" width="25%">';
        $form->select_types_paiements(empty($search_type) ? '' : $search_type, 'search_type', '', 2, 1, 1, 0, 1, 'maxwidth100');
        print '</td>';
    }
    if (!empty($arrayfields['b.num_chq']['checked'])) {
        // Numero
        print '<td class="liste_titre" align="center" width="20%"><input type="text" class="flat" name="req_nb" value="' . dol_escape_htmltag($search_req_nb) . '" size="2"></td>';
    }
    if (!empty($arrayfields['bu.label']['checked'])) {
        print '<td class="liste_titre" width="25%"><input type="text" class="flat" name="search_thirdparty" value="' . dol_escape_htmltag($search_thirdparty) . '" size="10"></td>';
    }
    if (!empty($arrayfields['b.debit']['checked'])) {
        print '<td class="liste_titre right" width="15%">';
        print '<input type="text" class="flat" name="debit" size="4" value="' . dol_escape_htmltag($debit) . '">';
        print '</td>';
    }
    if (!empty($arrayfields['b.credit']['checked'])) {
        print '<td class="liste_titre right" width="15%">';
        print '<input type="text" class="flat" name="credit" size="4" value="' . dol_escape_htmltag($credit) . '">';
        print '</td>';
    }
    // Numero statement
    if (!empty($arrayfields['b.num_releve']['checked'])) {
        print '<td class="liste_titre" align="center" width="15%"><input type="text" class="flat" name="search_num_releve" value="' . dol_escape_htmltag($search_num_releve) . '" size="3"></td>';
    }
    // Conciliated
    if (!empty($arrayfields['b.conciliated']['checked'])) {
        print '<td class="liste_titre" align="center" width="15%">';
        print $form->selectyesno('search_conciliated', $search_conciliated, 1, False, 1);
        print '</td>';
    }
    // Extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';
    print '<td class="liste_titre" align="middle" width="64px">';
    $searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
    print $searchpicto;
    print '</td>';
    print "</tr>\n";

    // Fields title
	print '<tr class="liste_titre">';
    if (!empty($arrayfields['b.rowid']['checked'])) print_liste_field_titre($arrayfields['b.rowid']['label'], $_SERVER['PHP_SELF'], 'b.rowid', '', $param, 'width="10%"', $sortfield, $sortorder);
    if (!empty($arrayfields['description']['checked'])) print_liste_field_titre($arrayfields['description']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'width="40%"', $sortfield, $sortorder);
    if (!empty($arrayfields['b.dateo']['checked'])) print_liste_field_titre($arrayfields['b.dateo']['label'], $_SERVER['PHP_SELF'], 'b.dateo', '', $param, 'align="center" width="20%"', $sortfield, $sortorder);
    if (!empty($arrayfields['b.datev']['checked'])) print_liste_field_titre($arrayfields['b.datev']['label'], $_SERVER['PHP_SELF'], 'b.datev,b.dateo,b.rowid', '', $param, 'align="center" width="20%"', $sortfield, $sortorder);
    if (!empty($arrayfields['type']['checked'])) print_liste_field_titre($arrayfields['type']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'align="center" width="25%"', $sortfield, $sortorder);
    if (!empty($arrayfields['b.num_chq']['checked'])) print_liste_field_titre($arrayfields['b.num_chq']['label'], $_SERVER['PHP_SELF'], 'b.num_chq', '', $param, 'align="center" width="20%"', $sortfield, $sortorder);
    if (!empty($arrayfields['bu.label']['checked'])) print_liste_field_titre($arrayfields['bu.label']['label'], $_SERVER['PHP_SELF'], 'bu.label', '', $param, 'width="25%"', $sortfield, $sortorder);
    if (!empty($arrayfields['b.debit']['checked'])) print_liste_field_titre($arrayfields['b.debit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, 'align="right" width="15%"', $sortfield, $sortorder);
    if (!empty($arrayfields['b.credit']['checked'])) print_liste_field_titre($arrayfields['b.credit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, 'align="right" width="15%"', $sortfield, $sortorder);
    if (!empty($arrayfields['b.num_releve']['checked'])) print_liste_field_titre($arrayfields['b.num_releve']['label'], $_SERVER['PHP_SELF'], 'b.num_releve', '', $param, 'align="center" width="15%"', $sortfield, $sortorder);
    if (!empty($arrayfields['b.conciliated']['checked'])) print_liste_field_titre($arrayfields['b.conciliated']['label'], $_SERVER['PHP_SELF'], 'b.rappro', '', $param, 'align="center" width="15%"', $sortfield, $sortorder);
    // Extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center" width="64px"', $sortfield, $sortorder, 'maxwidthsearch ');
    print "</tr>\n";

    print '</thead>';
    print '<tbody id="result_lines">';

	if (version_compare(DOL_VERSION, "11.0.0") >= 0) {
		$linkToBankLine = '/compta/bank/line.php';
	} else {
		$linkToBankLine = '/compta/bank/ligne.php';
	}
	$warning_already_linked = img_warning($langs->trans('Banking4DolibarrWarningBankLineAlreadyLinked'));

    $totalarray = array();
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        if (empty($cachebankaccount[$obj->bankid])) {
            $bankaccounttmp = new Account($db);
            $bankaccounttmp->fetch($obj->bankid);
            $cachebankaccount[$obj->bankid] = $bankaccounttmp;
            $bankaccount = $bankaccounttmp;
        } else {
            $bankaccount = $cachebankaccount[$obj->bankid];
        }

		$backgroundcolor = '';
		if (!empty($conf->global->BANK_COLORIZE_MOVEMENT)) {
			$color_key = 'BANK_COLORIZE_MOVEMENT_COLOR' . ($obj->amount < 0 ? 1 : 2);
			$color = '#' . (!empty($conf->global->$color_key) ? $conf->global->$color_key : ($obj->amount < 0 ? 'fca955' : '7fdb86'));
			$backgroundcolor = ' style="background: ' . $color . ';"';
		}
        print '<tr class="oddeven b4d_target"'.$backgroundcolor.'>';

        // Ref
        if (!empty($arrayfields['b.rowid']['checked'])) {
            print '<td class="nowrap" width="10%">';
            print "<a href=\"" . DOL_URL_ROOT . $linkToBankLine . "?rowid=" . $obj->rowid . '&save_lastsearch_values=1">' . img_object($langs->trans("ShowPayment") . ': ' . $obj->rowid, 'account', 'class="classfortooltip"') . ' ' . $obj->rowid . "</a> &nbsp; ";
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
		}

        // Description
        if (!empty($arrayfields['description']['checked'])) {
            print '<td width="40%">';

            //print "<a href=\"" . DOL_URL_ROOT . "/compta/bank/ligne.php?rowid=".$obj->rowid."&amp;account=".$obj->fk_account."\">";
            $reg = array();
            preg_match('/\((.+)\)/i', $obj->label, $reg);    // Si texte entouré de parenthèses on tente une recherche de traduction
            if ($reg[1] && $langs->trans($reg[1]) != $reg[1]) print $langs->trans($reg[1]);
            else print dol_trunc($obj->label, 40);
            //print "</a>&nbsp;";

            // Add links after description
            $links = $bankaccountstatic->get_url($obj->rowid);
            $cachebankaccount = array();
            foreach ($links as $key => $val) {
                if ($links[$key]['type'] == 'payment') {
                    $paymentstatic->id = $links[$key]['url_id'];
                    $paymentstatic->ref = $links[$key]['url_id'];
                    print ' ' . $paymentstatic->getNomUrl(2);
                } elseif ($links[$key]['type'] == 'payment_supplier') {
                    $paymentsupplierstatic->id = $links[$key]['url_id'];
                    $paymentsupplierstatic->ref = $links[$key]['url_id'];
                    print ' ' . $paymentsupplierstatic->getNomUrl(2);
                } elseif ($links[$key]['type'] == 'payment_sc') {
                    print '<a href="' . DOL_URL_ROOT . '/compta/payment_sc/card.php?id=' . $links[$key]['url_id'] . '">';
                    print ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
                    //print $langs->trans("SocialContributionPayment");
                    print '</a>';
                } elseif ($links[$key]['type'] == 'payment_vat') {
                    $paymentvatstatic->id = $links[$key]['url_id'];
                    $paymentvatstatic->ref = $links[$key]['url_id'];
                    print ' ' . $paymentvatstatic->getNomUrl(2);
                } elseif ($links[$key]['type'] == 'payment_salary') {
                    $paymentsalstatic->id = $links[$key]['url_id'];
                    $paymentsalstatic->ref = $links[$key]['url_id'];
                    print ' ' . $paymentsalstatic->getNomUrl(2);
                } elseif ($links[$key]['type'] == 'payment_loan') {
                    print '<a href="' . DOL_URL_ROOT . '/loan/payment/card.php?id=' . $links[$key]['url_id'] . '">';
                    print ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
                    print '</a>';
                } elseif ($links[$key]['type'] == 'payment_donation') {
                    print '<a href="' . DOL_URL_ROOT . '/don/payment/card.php?id=' . $links[$key]['url_id'] . '">';
                    print ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
                    print '</a>';
                } elseif ($links[$key]['type'] == 'payment_expensereport') {
                    $paymentexpensereportstatic->id = $links[$key]['url_id'];
                    $paymentexpensereportstatic->ref = $links[$key]['url_id'];
                    print ' ' . $paymentexpensereportstatic->getNomUrl(2);
                } elseif ($links[$key]['type'] == 'banktransfert') {
                    // Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
                    if ($obj->amount > 0) {
                        $banklinestatic->fetch($links[$key]['url_id']);
                        $bankstatic->id = $banklinestatic->fk_account;
                        $bankstatic->label = $banklinestatic->bank_account_ref;
                        print ' (' . $langs->trans("TransferFrom") . ' ';
                        print $bankstatic->getNomUrl(1, 'transactions');
                        print ' ' . $langs->trans("toward") . ' ';
                        $bankstatic->id = $obj->bankid;
                        $bankstatic->label = $obj->bankref;
                        print $bankstatic->getNomUrl(1, '');
                        print ')';
                    } else {
                        $bankstatic->id = $obj->bankid;
                        $bankstatic->label = $obj->bankref;
                        print ' (' . $langs->trans("TransferFrom") . ' ';
                        print $bankstatic->getNomUrl(1, '');
                        print ' ' . $langs->trans("toward") . ' ';
                        $banklinestatic->fetch($links[$key]['url_id']);
                        $bankstatic->id = $banklinestatic->fk_account;
                        $bankstatic->label = $banklinestatic->bank_account_ref;
                        print $bankstatic->getNomUrl(1, 'transactions');
                        print ')';
                    }
                    //var_dump($links);
                } elseif ($links[$key]['type'] == 'company') {

                } elseif ($links[$key]['type'] == 'user') {

                } elseif ($links[$key]['type'] == 'member') {

                } elseif ($links[$key]['type'] == 'sc') {

                } else {
                    // Show link with label $links[$key]['label']
                    if (!empty($obj->label) && !empty($links[$key]['label'])) print ' - ';
                    print '<a href="' . $links[$key]['url'] . $links[$key]['url_id'] . '">';
                    if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
                        // Label generique car entre parentheses. On l'affiche en le traduisant
                        if ($reg[1] == 'paiement') $reg[1] = 'Payment';
                        print ' ' . $langs->trans($reg[1]);
                    } else {
                        print ' ' . $links[$key]['label'];
                    }
                    print '</a>';
                }
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
		}

        // Date ope
        if (!empty($arrayfields['b.dateo']['checked'])) {
            print '<td align="center" class="nowrap" width="20%">';
            print dol_print_date($db->jdate($obj->do), "day");
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
		}

        // Date value
        if (!empty($arrayfields['b.datev']['checked'])) {
            print '<td align="center" class="nowrap" width="20%">';
            print dol_print_date($db->jdate($obj->dv), "day");
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
		}

        // Payment type
        if (!empty($arrayfields['type']['checked'])) {
            print '<td align="center" class="nowrap" width="25%">';
            $labeltype = ($langs->trans("PaymentTypeShort" . $obj->fk_type) != "PaymentTypeShort" . $obj->fk_type) ? $langs->trans("PaymentTypeShort" . $obj->fk_type) : $langs->getLabelFromKey($db, $obj->fk_type, 'c_paiement', 'code', 'libelle', '', 1);
            if ($labeltype == 'SOLD') print '&nbsp;'; //$langs->trans("InitialBankBalance");
            else print $labeltype;
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
		}

        // Num cheque
        if (!empty($arrayfields['b.num_chq']['checked'])) {
            print '<td class="nowrap" align="center" width="20%">' . ($obj->num_chq ? $obj->num_chq : "") . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
		}

        // Third party
        if (!empty($arrayfields['bu.label']['checked'])) {
            print '<td width="25%">';
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
                print $companystatic->getNomUrl(1);
            } else {
                print '&nbsp;';
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
		}

        // Debit
        if (!empty($arrayfields['b.debit']['checked'])) {
            print '<td class="nowrap right" width="15%">';
            if ($obj->amount < 0) {
                print price($obj->amount * -1);
            }
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }

        // Credit
        if (!empty($arrayfields['b.credit']['checked'])) {
            print '<td class="nowrap right" width="15%">';
            if ($obj->amount > 0) {
                print price($obj->amount);
            }
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['b.num_releve']['checked'])) {
            print '<td class="nowrap" align="center" width="15%">';
                if ($obj->conciliated)
                {
                    print '<a href="' . DOL_URL_ROOT . '/compta/bank/releve.php?num=' . $obj->num_releve . '&amp;account=' . $obj->bankid . '">' . $obj->num_releve . '</a>';
                }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
		}

        if (!empty($arrayfields['b.conciliated']['checked'])) {
            print '<td class="nowrap" align="center" width="15%">';
            print $obj->conciliated ? $langs->trans("Yes") : $langs->trans("No");
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
		}

        // Action column
        print '<td class="nowrap right" width="64px">';
        $selected = 0;
        if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
        if ($obj->warning_already_linked) print $warning_already_linked . ' ';
        print '<input id="cb' . $obj->rowid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
		print '<input id="amount' . $obj->rowid . '" type="hidden" value="' . $obj->amount . '">';
        print '</td>';
        if (!$i) $totalarray['nbfield']++;

        print "</tr>";

        $i++;
    }

    print '</tbody>';
	print "</table>";
    print "</div>";

    // If no data to display after a search
    if ($num == 0) {
        print '<div id="result_foot" class="opacitymedium">' . $langs->trans("NoRecordFound") . '</div>';
    } else {
		print '<div><span id="result_foot" style="float: right;">' . $langs->trans('Total') . ' : <div id="total" style="min-width: 100px; text-align: right; display: inline-block;"></div></span></div>';

		$nbofdectoround = $conf->global->MAIN_MAX_DECIMALS_TOT > 0 ? $conf->global->MAIN_MAX_DECIMALS_TOT : 0;
		// Add symbol of currency if requested
		$currency_code = $conf->currency;
		$cursymbolbefore = $cursymbolafter = '';
		$listofcurrenciesbefore = array('USD', 'GBP', 'AUD', 'MXN', 'PEN', 'CNY');
		if (in_array($currency_code, $listofcurrenciesbefore)) $cursymbolbefore .= $langs->getCurrencySymbol($currency_code);
		else {
			$tmpcur = $langs->getCurrencySymbol($currency_code);
			$cursymbolafter .= ($tmpcur == $currency_code ? ' ' . $tmpcur : $tmpcur);
		}
		if ($cursymbolafter) $cursymbolafter = ' ' . $cursymbolafter;

		print <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function() {
        	b4d_update_total();

        	$('.checkforselect, #checkallactions').click(function() {
        	  	b4d_update_total();
        	});

        	function b4d_update_total() {
        		var total = 0;
        		$(".checkforselect:checked").map(function() {
        			var id = $(this).val();
        			var amount_input = $('#amount'+id);
        			var amount = amount_input.length ? price2numjs(amount_input.val()) : 0;
					if ($nbofdectoround > 0) amount = dolroundjs(amount, $nbofdectoround);
	  				total += amount;
				});
        		if ($nbofdectoround > 0) total = dolroundjs(total, $nbofdectoround);
				$('#total').text("$cursymbolbefore" + total + "$cursymbolafter");
        	}
        });
    </script>
SCRIPT;
	}

    $db->free($resql);
} else {
    dol_print_error($db);
}
