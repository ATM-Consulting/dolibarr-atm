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
 *  \file		htdocs/banking4dolibarr/tpl/b4d_manual_reconciliation_widthdraw.tpl.php
 *  \ingroup	banking4dolibarr
 *  \brief		Template to show manuel reconciliation content for the type 'widthdraw'
 */


require_once DOL_DOCUMENT_ROOT . '/compta/prelevement/class/bonprelevement.class.php';

$langs->loadLangs(array("banks", "bills", "withdrawals"));

$debit = GETPOST("debit", 'alpha');
$credit = GETPOST("credit", 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_date_start = dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'));
$search_date_end = dol_mktime(0, 0, 0, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'));
$search_status = GETPOST("search_status", 'alpha');

if ($action == 'update_manual_reconciliation_type') {
	$min_offset_dates = $object->remaining_amount_to_link < 0 ? $conf->global->BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES : $conf->global->BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES;
	$max_offset_dates = $object->remaining_amount_to_link < 0 ? $conf->global->BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES : $conf->global->BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES;
	$search_date_start = $object->record_date - ((isset($min_offset_dates) && $min_offset_dates !== '' ? $min_offset_dates : 15) * 24 * 60 * 60);
	$search_date_end = $object->record_date + ((isset($max_offset_dates) && $max_offset_dates !== '' ? $max_offset_dates : 15) * 24 * 60 * 60);
	$search_status = '';
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
if (!$sortorder) $sortorder = 'ASC';
if (!$sortfield) $sortfield = 'pb.date_trans';

// Initialize technical object to manage context to save list fields
$contextpage = 'b4dmanualreconciliationwidthdrawlist';

$arrayfields = array(
    'pb.rowid' => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'pb.date_trans' => array('label' => $langs->trans("TransData"), 'checked' => 1),
	'pb.ref' => array('label' => $langs->trans("BankAccount"), 'checked' => 1, 'position' => 500),
    'pb.debit' => array('label' => $langs->trans("Debit"), 'checked' => 1, 'position' => 600),
    'pb.credit' => array('label' => $langs->trans("Credit"), 'checked' => 1, 'position' => 605),
	'pb.statut' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $search_ref = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_status = '';
	$debit = "";
    $credit = "";
	$standing_order_id = 0;
}
if ((empty($action))) $standing_order_id = 0;
if ($search_status === '') $search_status = '-1';

/*
 * View
 */

$form = new Form($db);

$bonprelevementstatic = new BonPrelevement($db);

$now = dol_now();

$sql = "WITH banking4dolibarr_widthdraw_rejected AS (" .
	" SELECT pl.fk_prelevement_bons, sum(pl.amount) as amount" .
	" FROM " . MAIN_DB_PREFIX . "prelevement_rejet as pr" .
	" LEFT JOIN " . MAIN_DB_PREFIX . "prelevement_lignes as pl ON pl.rowid = pr.fk_prelevement_lignes	" .
	" GROUP BY pl.fk_prelevement_bons" .
	")";
$sql .= "SELECT pb.rowid, pb.ref, (pb.amount - " . $db->ifsql("bwr.amount IS NULL", "0", "bwr.amount") . ") AS amount, pb.date_trans, pb.statut";
$sql .= " FROM " . MAIN_DB_PREFIX . "prelevement_bons as pb";
$sql .= " LEFT JOIN banking4dolibarr_widthdraw_rejected AS bwr ON bwr.fk_prelevement_bons = pb.rowid";
$sql .= " WHERE pb.entity IN (" . getEntity('facture') . ")";
// Search period criteria
if (dol_strlen($search_date_start) > 0) $sql .= " AND pb.date_trans >= '" . $db->idate($search_date_start) . "'";
if (dol_strlen($search_date_end) > 0) $sql .= " AND pb.date_trans <= '" . $db->idate($search_date_end) . "'";
if ($search_ref) $sql .= natural_search("pb.ref", $search_ref);
if ($search_status != -1) $sql .= " AND pb.statut = " . $search_status;
// Search criteria amount
$debit = price2num(str_replace('-', '', $debit));
$credit = price2num(str_replace('-', '', $credit));
if ($debit) $sql .= natural_search("(" . $db->ifsql("bwr.amount IS NULL", "0", "bwr.amount") . " - pb.amount)", $debit, 1) . " AND (pb.amount - " . $db->ifsql("bwr.amount IS NULL", "0", "bwr.amount") . ") < 0";
if ($credit) $sql .= natural_search("(pb.amount - " . $db->ifsql("bwr.amount IS NULL", "0", "bwr.amount") . ")", $credit, 1) . " AND (pb.amount - " . $db->ifsql("bwr.amount IS NULL", "0", "bwr.amount") . ") > 0";

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

dol_syslog('/banking4dolibarr/tpl/b4d_manual_reconciliation_widthdraw.tpl.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);

    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
    if (!empty($search_ref)) $param .= '&search_ref=' . urlencode($search_ref);
	if (dol_strlen($search_date_start) > 0) $param .= '&search_date_startmonth=' . GETPOST('search_date_startmonth', 'int') . '&search_date_startday=' . GETPOST('search_date_startday', 'int') . '&search_date_startyear=' . GETPOST('search_date_startyear', 'int');
	if (dol_strlen($search_date_end) > 0) $param .= '&search_date_endmonth=' . GETPOST('search_date_endmonth', 'int') . '&search_date_endday=' . GETPOST('search_date_endday', 'int') . '&search_date_endyear=' . GETPOST('search_date_endyear', 'int');
	if (!empty($search_status)) $param .= '&search_status=' . urlencode($search_status);
    if (!empty($debit)) $param .= '&debit=' . urlencode($debit);
    if (!empty($credit)) $param .= '&credit=' . urlencode($credit);
    if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);

    // Lines of title fields
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="page" value="' . $page . '">';

    $i = 0;

    print_barre_liste($langs->trans("StandingOrders"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '', '', $limit);

    // We can add page now to param
    if ($page != '') $param .= '&page=' . urlencode($page);

    $moreforfilter = '';

    if ($moreforfilter) {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>' . "\n";
    }

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields =  $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . ' tableBodyScroll">' . "\n";
    print '<thead>';

    print '<tr class="liste_titre_filter">';
    if (!empty($arrayfields['pb.rowid']['checked'])) {
        print '<td class="liste_titre" width="35%">';
        print '<input type="text" class="flat" name="search_ref" size="8" value="' . dol_escape_htmltag($search_ref) . '">';
        print '</td>';
    }
    if (!empty($arrayfields['pb.date_trans']['checked'])) {
        print '<td class="liste_titre center" width="15%">';
		print '<div class="nowrap">';
		print $langs->trans('From') . ' ';
		print $form->selectDate($search_date_start?$search_date_start:-1, 'search_date_start', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to') . ' ';
		print $form->selectDate($search_date_end?$search_date_end:-1, 'search_date_end', 0, 0, 1);
		print '</div>';
        print '</td>';
    }
    if (!empty($arrayfields['pb.debit']['checked'])) {
        print '<td class="liste_titre right" width="15%">';
        print '<input type="text" class="flat" name="debit" size="4" value="' . dol_escape_htmltag($debit) . '">';
        print '</td>';
    }
	if (!empty($arrayfields['pb.credit']['checked'])) {
		print '<td class="liste_titre right" width="15%">';
		print '<input type="text" class="flat" name="credit" size="4" value="' . dol_escape_htmltag($credit) . '">';
		print '</td>';
	}
	if (!empty($arrayfields['pb.statut']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone right" width="20%">';
		$liststatus = array('0' => $langs->trans("StatusWaiting"), '1' => $langs->trans("StatusTrans"), '2' => $langs->trans("StatusCredited"));
		print $form->selectarray('search_status', $liststatus, $search_status, 1);
		print '</td>';
	}
    print '<td class="liste_titre" align="middle" width="64px">';
    $searchpicto = $form->showFilterAndCheckAddButtons(0, 'checkforselect', 1);
    print $searchpicto;
    print '</td>';
    print "</tr>\n";

    // Fields title
    print '<tr class="liste_titre">';
    if (!empty($arrayfields['pb.rowid']['checked'])) print_liste_field_titre($arrayfields['pb.rowid']['label'], $_SERVER['PHP_SELF'], 'pb.ref', '', $param, 'width="35%"', $sortfield, $sortorder);
    if (!empty($arrayfields['pb.date_trans']['checked'])) print_liste_field_titre($arrayfields['pb.date_trans']['label'], $_SERVER['PHP_SELF'], 'pb.date_trans', '', $param, 'align="center" width="15%"', $sortfield, $sortorder);
    if (!empty($arrayfields['pb.debit']['checked'])) print_liste_field_titre($arrayfields['pb.debit']['label'], $_SERVER['PHP_SELF'], 'pb.amount', '', $param, 'align="right" width="15%"', $sortfield, $sortorder);
	if (!empty($arrayfields['pb.credit']['checked'])) print_liste_field_titre($arrayfields['pb.credit']['label'], $_SERVER['PHP_SELF'], 'pb.amount', '', $param, 'align="right" width="15%"', $sortfield, $sortorder);
	if (!empty($arrayfields['pb.statut']['checked'])) print_liste_field_titre($arrayfields['pb.statut']['label'], $_SERVER['PHP_SELF'], 'pb.statut', '', $param, 'align="right" width="20%"', $sortfield, $sortorder);
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center" width="64px"', $sortfield, $sortorder, 'maxwidthsearch ');
    print "</tr>\n";

    print '</thead>';
    print '<tbody id="result_lines">';

    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        $backgroundcolor = '';
        if (!empty($conf->global->BANK_COLORIZE_MOVEMENT)) {
            $color_key = 'BANK_COLORIZE_MOVEMENT_COLOR' . ($obj->amount < 0 ? 1 : 2);
            $color = '#' . (!empty($conf->global->$color_key) ? $conf->global->$color_key : ($obj->amount < 0 ? 'fca955' : '7fdb86'));
			$backgroundcolor = ' style="background: ' . $color . ';"';
        }
        print '<tr class="oddeven b4d_target"'.$backgroundcolor.'>';

        // Ref
        if (!empty($arrayfields['pb.rowid']['checked'])) {
			print '<td class="nowrap" width="35%">';
			$bonprelevementstatic->id = $obj->rowid;
			$bonprelevementstatic->ref = $obj->ref;
			print $bonprelevementstatic->getNomUrl(1);
			print '</td>';
		}
        // Date
        if (!empty($arrayfields['pb.date_trans']['checked'])) {
            print '<td align="center" class="nowrap" width="15%">';
            print dol_print_date($db->jdate($obj->date_trans), "day");
            print "</td>\n";
        }
        // Debit
        if (!empty($arrayfields['pb.debit']['checked'])) {
            print '<td class="right" width="15%">';
            if ($obj->amount < 0) {
                print price($obj->amount * -1);
            }
            print "</td>\n";
        }
        // Credit
        if (!empty($arrayfields['pb.credit']['checked'])) {
            print '<td class="right" width="15%">';
            if ($obj->amount > 0) {
                print price($obj->amount);
            }
            print "</td>\n";
        }
		// Status
		if (! empty($arrayfields['pb.statut']['checked'])) {
			print '<td class="nowrap right" width="20%">';
			print $bonprelevementstatic->LibStatut($obj->statut, 5);
			print "</td>";
		}
        // Action column
        print '<td class="nowrap" align="center" width="64px">';
        if (empty($standing_order_id)) $standing_order_id = $obj->rowid;
        print '<input class="flat" type="radio" name="standing_order_id" value="' . $obj->rowid . '"' . ($standing_order_id == $obj->rowid ? ' checked="checked"' : '') . '>';
        print '</td>';

        print "</tr>";

        $i++;
    }

    print '</tbody>';
    print "</table>";
    print "</div>";

    // If no data to display after a search
    if ($num == 0) {
        print '<div class="opacitymedium">' . $langs->trans("NoRecordFound") . '</div>';
    }

    $db->free($resql);
} else {
    dol_print_error($db);
}
