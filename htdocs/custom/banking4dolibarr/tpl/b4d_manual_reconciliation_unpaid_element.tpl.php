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
 *  \file		htdocs/banking4dolibarr/tpl/b4d_manual_reconciliation_unpaid_element.tpl.php
 *  \ingroup	banking4dolibarr
 *  \brief		Template to show manuel reconciliation content for the type 'unpaid_element'
 */

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
dol_include_once('/banking4dolibarr/lib/opendsi_common.lib.php');

$langs->loadLangs(array("banks", "bills", "categories", "companies", "margins", "salaries", "loan", "donations", "trips", "members", "compta", "accountancy"));

$search_type = GETPOST("search_type", 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_ref_ext = GETPOST('search_ref_ext', 'alpha');
$search_dateb_start = dol_mktime(0, 0, 0, GETPOST('search_dateb_startmonth', 'int'), GETPOST('search_dateb_startday', 'int'), GETPOST('search_dateb_startyear', 'int'));
$search_dateb_end = dol_mktime(0, 0, 0, GETPOST('search_dateb_endmonth', 'int'), GETPOST('search_dateb_endday', 'int'), GETPOST('search_dateb_endyear', 'int'));
$search_datee_start = dol_mktime(0, 0, 0, GETPOST('search_datee_startmonth', 'int'), GETPOST('search_datee_startday', 'int'), GETPOST('search_datee_startyear', 'int'));
$search_datee_end = dol_mktime(0, 0, 0, GETPOST('search_datee_endmonth', 'int'), GETPOST('search_datee_endday', 'int'), GETPOST('search_datee_endyear', 'int'));
$search_thirdparty = GETPOST("search_thirdparty", 'alpha');
$search_account = GETPOST("search_account", 'int');
$search_payment_mode = GETPOST("search_payment_mode", 'int');
$debit = GETPOST("debit", 'alpha');
$credit = GETPOST("credit", 'alpha');
if ($search_type == -1) $search_type = "";

if ($action == 'update_manual_reconciliation_type') {
	$min_offset_dates = $object->remaining_amount_to_link < 0 ? $conf->global->BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES : $conf->global->BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES;
	$max_offset_dates = $object->remaining_amount_to_link < 0 ? $conf->global->BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES : $conf->global->BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES;
	$search_dateb_start = $object->record_date - ((isset($min_offset_dates) && $min_offset_dates !== '' ? $min_offset_dates : 15) * 24 * 60 * 60);
	$search_dateb_end = $object->record_date + ((isset($max_offset_dates) && $max_offset_dates !== '' ? $max_offset_dates : 15) * 24 * 60 * 60);
	//$search_account = $account->id;
	//$search_payment_mode = $payment_mode_id > 0 ? $payment_mode_id : $payment_mode;
	$debit = $object->remaining_amount_to_link < 0 ? price2num($object->remaining_amount_to_link) : '';
	$credit = $object->remaining_amount_to_link > 0 ? price2num($object->remaining_amount_to_link) : '';
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
if (!$sortorder) $sortorder = 'ASC,ASC';
if (!$sortfield) $sortfield = 'ul.element_type,ul.dateb';

// Initialize technical object to manage context to save list fields
$contextpage = 'b4dmanualreconciliationunpaidelementlist';

// fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label('banktransaction');
$search_array_options = $extrafields->getOptionalsFromPost($extralabels, '', 'search_');

$arrayfields = array(
    'ul.element_type' => array('label' => $langs->trans("Type"), 'checked' => 1),
	'ul.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
	'ul.ref_ext' => array('label' => $langs->trans("ExternalRef"), 'checked' => 1),
    'ul.dateb' => array('label' => $langs->trans("Date") . " / " . $langs->trans("DateStart"), 'checked' => 1),
    'ul.datee' => array('label' => $langs->trans("DateEnd"), 'checked' => 1),
    's.nom' => array('label' => $langs->trans("ThirdParty"), 'checked' => 1, 'position' => 500),
    'ba.ref' => array('label' => $langs->trans("BankAccount"), 'checked' => 1, 'position' => 500),
    'ul.fk_payment_mode' => array('label' => $langs->trans("PaymentMode"), 'checked' => 1, 'position' => 500),
    'debit' => array('label' => $langs->trans("Debit"), 'checked' => 1, 'position' => 600),
    'credit' => array('label' => $langs->trans("Credit"), 'checked' => 1, 'position' => 605),
);


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $search_type = "";
	$search_ref = "";
	$search_ref_ext = "";
	$search_dateb_start = '';
	$search_dateb_end = '';
	$search_datee_start = '';
	$search_datee_end = '';
    $search_thirdparty = '';
    $search_account = '';
    $search_payment_mode = '';
    $debit = "";
    $credit = "";
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();

// Must be before button action
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
if (!empty($search_type)) $param .= '&search_type=' . urlencode($search_type);
if (!empty($search_ref)) $param .= '&search_ref=' . urlencode($search_ref);
if (!empty($search_ref_ext)) $param .= '&search_ref_ext=' . urlencode($search_ref_ext);
if (dol_strlen($search_dateb_start) > 0) $param .= '&search_dateb_startmonth=' . GETPOST('search_dateb_startmonth', 'int') . '&search_dateb_startday=' . GETPOST('search_dateb_startday', 'int') . '&search_dateb_startyear=' . GETPOST('search_dateb_startyear', 'int');
if (dol_strlen($search_dateb_end) > 0) $param .= '&search_dateb_endmonth=' . GETPOST('search_dateb_endmonth', 'int') . '&search_dateb_endday=' . GETPOST('search_dateb_endday', 'int') . '&search_dateb_endyear=' . GETPOST('search_dateb_endyear', 'int');
if (dol_strlen($search_datee_start) > 0) $param .= '&search_datee_startmonth=' . GETPOST('search_datee_startmonth', 'int') . '&search_datee_startday=' . GETPOST('search_datee_startday', 'int') . '&search_datee_startyear=' . GETPOST('search_datee_startyear', 'int');
if (dol_strlen($search_datee_end) > 0) $param .= '&search_datee_endmonth=' . GETPOST('search_datee_endmonth', 'int') . '&search_datee_endday=' . GETPOST('search_datee_endday', 'int') . '&search_datee_endyear=' . GETPOST('search_datee_endyear', 'int');
if (!empty($search_thirdparty)) $param .= '&search_thirdparty=' . urlencode($search_thirdparty);
if ($search_account > 0) $param .= '&search_account=' . urlencode($search_account);
if ($search_payment_mode > 0) $param .= '&search_payment_mode=' . urlencode($search_payment_mode);
if (!empty($debit)) $param .= '&debit=' . urlencode($debit);
if (!empty($credit)) $param .= '&credit=' . urlencode($credit);
if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);

$options = array();

$sql = "SELECT ul.element_type, ul.element_id, ul.ref, ul.ref_ext, ul.label, ul.dateb, ul.datee, ul.amount, ul.fk_account, ul.fk_payment_mode";
$sql .= ", ul.fk_soc, s.nom, s.name_alias, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
$sql .= ", ba.ref as bank_ref, ba.label as bank_label";
$sql .= " FROM " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list as ul";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = ul.fk_soc";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON ba.rowid = ul.fk_account";
$sql .= " WHERE ul.entity IN (" . $conf->entity . ")";
if ($search_type) $sql .= " AND ul.element_type = '" . $db->escape($search_type) . "' ";
if ($search_ref) $sql .= natural_search(array("ul.ref", "ul.label"), $search_ref);
if ($search_ref_ext) $sql .= natural_search("ul.ref_ext", $search_ref_ext);
if (dol_strlen($search_dateb_start) > 0) $sql .= " AND ul.dateb >= '" . $db->idate($search_dateb_start) . "'";
if (dol_strlen($search_dateb_end) > 0) $sql .= " AND ul.dateb <= '" . $db->idate($search_dateb_end) . "'";
if (dol_strlen($search_datee_start) > 0) $sql .= " AND ul.datee >= '" . $db->idate($search_datee_start) . "'";
if (dol_strlen($search_datee_end) > 0) $sql .= " AND ul.datee <= '" . $db->idate($search_datee_end) . "'";
if ($search_thirdparty) $sql .= natural_search(array("s.nom", "s.name_alias", "s.code_client", "s.code_fournisseur"), $search_thirdparty);
if ($search_account > 0) $sql .= " AND ul.fk_account = " . $search_account;
if ($search_payment_mode > 0) $sql .= " AND ul.fk_payment_mode = " . $search_payment_mode;
$sql .= " AND ul.element_type IN ('" . implode("', '", array_keys($unpaid_types)) . "')";
// Search criteria amount
$debit = price2num(str_replace('-', '', $debit));
$credit = price2num(str_replace('-', '', $credit));
if ($debit) $sql .= natural_search('- ul.amount', $debit, 1) . ' AND ul.amount < 0';
if ($credit) $sql .= natural_search('ul.amount', $credit, 1) . ' AND ul.amount > 0';

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

dol_syslog('/banking4dolibarr/tpl/b4d_manual_reconciliation_unpaid_element.tpl.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);

    // Lines of title fields
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="page" value="' . $page . '">';

    $i = 0;

    print_barre_liste($langs->trans("Banking4DolibarrUnpaidElements"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '', '', $limit);

    // We can add page now to param
    if ($page != '') $param .= '&page=' . urlencode($page);

    $moreforfilter = '';

    if ($moreforfilter) {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>' . "\n";
    }

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . ' tableBodyScroll">' . "\n";
    print '<thead>';

    print '<tr class="liste_titre_filter">';
    if (!empty($arrayfields['ul.element_type']['checked'])) {
        print '<td class="liste_titre" width="25%">';
        print $form->selectarray('search_type', $unpaid_types, $search_type, 1);
        print '</td>';
    }
    if (!empty($arrayfields['ul.ref']['checked'])) {
        print '<td class="liste_titre" width="15%"><input type="text" class="flat" name="search_ref" size="8" value="' . dol_escape_htmltag($search_ref) . '"></td>';
    }
	if (!empty($arrayfields['ul.ref_ext']['checked'])) {
		print '<td class="liste_titre" width="15%"><input type="text" class="flat" name="search_ref_ext" size="8" value="' . dol_escape_htmltag($search_ref_ext) . '"></td>';
	}
    if (!empty($arrayfields['ul.dateb']['checked'])) {
        print '<td class="liste_titre center" width="15%">';
		print '<div class="nowrap">';
		print $langs->trans('From') . ' ';
		print $form->selectDate($search_dateb_start?$search_dateb_start:-1, 'search_dateb_start', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to') . ' ';
		print $form->selectDate($search_dateb_end?$search_dateb_end:-1, 'search_dateb_end', 0, 0, 1);
		print '</div>';
		print '</td>';
    }
    if (!empty($arrayfields['ul.datee']['checked'])) {
		print '<td class="liste_titre center" width="15%">';
		print '<div class="nowrap">';
		print $langs->trans('From') . ' ';
		print $form->selectDate($search_datee_start?$search_datee_start:-1, 'search_datee_start', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to') . ' ';
		print $form->selectDate($search_datee_end?$search_datee_end:-1, 'search_datee_end', 0, 0, 1);
		print '</div>';
		print '</td>';
    }
    if (!empty($arrayfields['s.nom']['checked'])) {
        print '<td class="liste_titre" width="20%"><input type="text" class="flat" name="search_thirdparty" value="' . dol_escape_htmltag($search_thirdparty) . '" size="10"></td>';
    }
    if (!empty($arrayfields['ba.ref']['checked'])) {
        print '<td class="liste_titre" width="25%">';
        $form->select_comptes($search_account, 'search_account', 0, '', 1);
        print '</td>';
    }
    if (!empty($arrayfields['ul.fk_payment_mode']['checked'])) {
        print '<td class="liste_titre" width="15%">';
        $form->select_types_paiements($search_payment_mode, 'search_payment_mode', '', 0, 1, 1);
        print '</td>';
    }
    if (!empty($arrayfields['debit']['checked'])) {
        print '<td class="liste_titre right" width="10%"><input type="text" class="flat" name="debit" size="4" value="' . dol_escape_htmltag($debit) . '"></td>';
    }
    if (!empty($arrayfields['credit']['checked'])) {
        print '<td class="liste_titre right" width="10%"><input type="text" class="flat" name="credit" size="4" value="' . dol_escape_htmltag($credit) . '"></td>';
    }
    print '<td class="liste_titre right" width="150px">';
    $searchpicto = $form->showFilterAndCheckAddButtons(0, 'checkforselect', 1);
    print $searchpicto;
    print '</td>';
    print "</tr>\n";

    // Fields title
	$filter_date_help = ' ' . $form->textwithpicto('', $langs->transnoentitiesnoconv('Banking4DolibarrFilterDateHelp'));
    print '<tr class="liste_titre">';
    if (!empty($arrayfields['ul.element_type']['checked'])) print_liste_field_titre($arrayfields['ul.element_type']['label'], $_SERVER['PHP_SELF'], 'ul.element_type', '', $param, 'width="25%"', $sortfield, $sortorder);
    if (!empty($arrayfields['ul.ref']['checked'])) print_liste_field_titre($arrayfields['ul.ref']['label'], $_SERVER['PHP_SELF'], 'ul.ref', '', $param, 'width="15%"', $sortfield, $sortorder);
	if (!empty($arrayfields['ul.ref_ext']['checked'])) print_liste_field_titre($arrayfields['ul.ref_ext']['label'], $_SERVER['PHP_SELF'], 'ul.ref_ext', '', $param, 'width="15%"', $sortfield, $sortorder);
    if (!empty($arrayfields['ul.dateb']['checked'])) print_liste_field_titre($arrayfields['ul.dateb']['label'].$filter_date_help, $_SERVER['PHP_SELF'], 'ul.dateb', '', $param, 'align="center" width="15%"', $sortfield, $sortorder);
    if (!empty($arrayfields['ul.datee']['checked'])) print_liste_field_titre($arrayfields['ul.datee']['label'].$filter_date_help, $_SERVER['PHP_SELF'], 'ul.datee', '', $param, 'align="center" width="15%"', $sortfield, $sortorder);
    if (!empty($arrayfields['s.nom']['checked'])) print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER['PHP_SELF'], 's.nom', '', $param, 'width="20%"', $sortfield, $sortorder);
    if (!empty($arrayfields['ba.ref']['checked'])) print_liste_field_titre($arrayfields['ba.ref']['label'], $_SERVER['PHP_SELF'], 'ba.ref', '', $param, 'width="25%"', $sortfield, $sortorder);
    if (!empty($arrayfields['ul.fk_payment_mode']['checked'])) print_liste_field_titre($arrayfields['ul.fk_payment_mode']['label'], $_SERVER['PHP_SELF'], 'ul.fk_payment_mode', '', $param, 'width="15%"', $sortfield, $sortorder);
    if (!empty($arrayfields['debit']['checked'])) print_liste_field_titre($arrayfields['debit']['label'], $_SERVER['PHP_SELF'], 'ul.amount', '', $param, 'align="right" width="10%"', $sortfield, $sortorder);
    if (!empty($arrayfields['credit']['checked'])) print_liste_field_titre($arrayfields['credit']['label'], $_SERVER['PHP_SELF'], 'ul.amount', '', $param, 'align="right" width="10%"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Banking4DolibarrReconciledAmount'), '', "", '', '', 'width="150px"', '', '', "right ");
    print "</tr>\n";

    print '</thead>';
    print '<tbody id="result_lines">';

    $companystatic = new Societe($db);
    $accountstatic = new Account($db);

    $totalarray = array();
    $element_cached = array();
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        $key = $obj->element_type . '_' . $obj->element_id;
        if (!isset($element_cached[$key])) {
            $element_cached[$key] = opendsi_get_object($db, $obj->element_type, $obj->element_id);
        }
        $element_object = $element_cached[$key];

        $companystatic->id = $obj->fk_soc;
        $companystatic->name = $obj->nom;
        $companystatic->name_alias = $obj->name_alias;
        $companystatic->client = $obj->client;
        $companystatic->fournisseur = $obj->fournisseur;
        $companystatic->code_client = $obj->code_client;
        $companystatic->code_fournisseur = $obj->code_fournisseur;
        $companystatic->code_compta = $obj->code_compta;
        $companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

        $accountstatic->id = $obj->fk_account;
		$accountstatic->ref = $obj->bank_ref;
        $accountstatic->label = $obj->bank_label;

        $backgroundcolor = '';
        if (!empty($conf->global->BANK_COLORIZE_MOVEMENT)) {
            $color_key = 'BANK_COLORIZE_MOVEMENT_COLOR' . ($obj->amount < 0 ? 1 : 2);
            $color = '#' . (!empty($conf->global->$color_key) ? $conf->global->$color_key : ($obj->amount < 0 ? 'fca955' : '7fdb86'));
			$backgroundcolor = ' style="background: ' . $color . ';"';
        }
        print '<tr class="oddeven b4d_target"'.$backgroundcolor.'>';

        // Type
        if (!empty($arrayfields['ul.element_type']['checked'])) {
            print '<td width="25%">';
            print isset($unpaid_types[$obj->element_type]) ? $unpaid_types[$obj->element_type] : ($langs->trans('Unknown') . ' : ' . $obj->element_type);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Ref
        if (!empty($arrayfields['ul.ref']['checked'])) {
            print '<td width="15%">';
            print isset($element_object) && method_exists($element_object, 'getNomUrl') ? $element_object->getNomUrl(1) : $obj->ref;
            if (!empty($obj->label)) print ' - ' . $obj->label;
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
		// Ref external
		if (!empty($arrayfields['ul.ref_ext']['checked'])) {
			print '<td width="15%">';
			print $obj->ref_ext;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
        // Date / Date begin
        if (!empty($arrayfields['ul.dateb']['checked'])) {
            print '<td align="center" class="nowrap" width="15%">';
            print dol_print_date($db->jdate($obj->dateb), "day");
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }
        // Date end
        if (!empty($arrayfields['ul.datee']['checked'])) {
            print '<td align="center" class="nowrap" width="15%">';
            print dol_print_date($db->jdate($obj->datee), "day");
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }
        // Third party
        if (!empty($arrayfields['s.nom']['checked'])) {
            print '<td width="20%">';
            if ($obj->fk_soc > 0) print $companystatic->getNomUrl(1);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Bank Account
        if (!empty($arrayfields['ba.ref']['checked'])) {
            print '<td width="25%">';
            if ($obj->fk_account > 0) print $accountstatic->getNomUrl(1);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Payment mode
        if (!empty($arrayfields['ul.fk_payment_mode']['checked'])) {
            print '<td width="15%">';
            $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_payment_mode, 'none', '', -1);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Debit
        if (!empty($arrayfields['debit']['checked'])) {
            print '<td class="nowrap right" width="10%">';
            if ($obj->amount < 0) {
                print price($obj->amount * -1);
            }
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }
        // Credit
        if (!empty($arrayfields['credit']['checked'])) {
            print '<td class="nowrap right" width="10%">';
            if ($obj->amount > 0) {
                print price($obj->amount);
            }
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }
        // Action column
        print '<td class="nowrap right" width="150px">';
        print img_picto($langs->trans("Banking4DolibarrAutoFill"), 'rightarrow', 'class="b4d_auto_fill_qty" data-target="reconciled_amount_' . $key . '" data-amount="' . $obj->amount . '"');
        print '<input class="flat right b4d_amount maxwidth100" type="number" step="any" id="reconciled_amount_' . $key . '" name="reconciled_amount_' . $key . '" value="' .
            dol_escape_htmltag(GETPOST('reconciled_amount_' . $key, 'int')) . '"' . ($obj->amount > 0 ? ' min="0" max="' . $obj->amount . '"' : ' min="' . $obj->amount . '" max="0"') . '>';
        print '</td>';
        if (!$i) $totalarray['nbfield']++;

        print "</tr>";

        $i++;
    }

    print '</tbody>';
    print "</table>";
    print "</div>";
    print <<<SCRIPT
<script type="text/javascript">
$(document).ready(function(){
    $('.b4d_auto_fill_qty').on('click', function() {
        var _this = $(this);
        var target = _this.attr('data-target');
        var amount = _this.attr('data-amount');
        $('#'+target).val(amount);
    });
});
</script>
SCRIPT;

    // If no data to display after a search
    if ($num == 0) {
        print '<div class="opacitymedium">' . $langs->trans("NoRecordFound") . '</div>';
    } else {
		print '<div><span id="result_foot" style="float: right;">';
		$payment_mode_code = dol_getIdFromCode($db, $payment_mode_id, 'c_paiement', 'id', 'code', 1);
		$total_amount = price2num($object->remaining_amount_to_link, 'MT');
		if ($payment_mode_code == 'CHQ' && $total_amount > 0) {
			print $langs->trans('Banking4DolibarrDepositReceiptCheckDate') . ' : ';
			$deposit_receipt_date = dol_mktime(0, 0, 0, GETPOST("deposit_receipt_datemonth", 'int'), GETPOST("deposit_receipt_dateday", 'int'), GETPOST("deposit_receipt_dateyear", 'int'));
			$form->select_date($deposit_receipt_date, 'deposit_receipt_date');
			print ' ';
		}
		print $langs->trans('Total') . ' : <div id="total" style="min-width: 100px; text-align: right; display: inline-block;"></div></span></div>';

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

        	$('.b4d_amount').on('change keyup', function() {
        	  	b4d_update_total();
        	});

        	$('.b4d_auto_fill_qty').click(function() {
        	  	b4d_update_total();
        	});

        	function b4d_update_total() {
        		var total = 0;
        		$(".b4d_amount").each(function() {
        			var value = $(this).val();
        			if (value == '' || value == '0') return 1;
        			var amount = price2numjs(value);
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
