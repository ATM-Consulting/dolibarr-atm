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
 *  \file		htdocs/banking4dolibarr/tpl/b4d_manual_reconciliation_vat.tpl.php
 *  \ingroup	banking4dolibarr
 *  \brief		Template to show manuel reconciliation content for the type 'vat'
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

$langs->loadLangs(array("compta","banks","bills","users","salaries","hrm"));

$dateep = dol_mktime(12, 0, 0, GETPOST("dateepmonth", 'int'), GETPOST("dateepday", 'int'), GETPOST("dateepyear", 'int'));

$label = GETPOST('label', 'alpha');
$type = GETPOST('type','int');
$amount = GETPOST('amount','int');
$num_payment = GETPOST('num_payment','alpha');
$note = GETPOST('note','restricthtml');

if ($action == 'update_manual_reconciliation_type' || (!$error && $action == 'save')) {
    $prelabel = $object->remaining_amount_to_link > 0 ? $langs->trans('VATRefund') : $langs->trans('VATPayment');
    $label = $prelabel . ' ' . $object->label . (!empty($object->comment) ? ' - ' . $object->comment : '');
	$type = $object->remaining_amount_to_link > 0 ? 1 : 0;
	$amount = abs($object->remaining_amount_to_link);
    $num_payment = '';
	$note = '';
}

$isV9p = version_compare(DOL_VERSION, "9.0.0") >= 0;
$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);

print load_fiche_titre($langs->trans("Banking4DolibarrNewVAT"));

dol_fiche_head('', '');

if ($user->rights->tax->charges->creer) {
	$year_current = strftime("%Y", $object->record_date);
	$pastmonth = strftime("%m", $object->record_date) - 1;
	$pastmonthyear = $year_current;
	if ($pastmonth == 0) {
		$pastmonth = 12;
		$pastmonthyear--;
	}

	$dateepmonth = GETPOST('dateepmonth', 'int');
	$dateepday = GETPOST('dateepday', 'int');
	$dateepyear = GETPOST('dateepyear', 'int');
	$dateep = dol_mktime(23, 59, 59, $dateepmonth, $dateepday, $dateepyear);

	if (empty($datesp) || empty($dateep)) { // We define date_end
		$dateep = dol_get_last_day($pastmonthyear, $pastmonth, false);
	}

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="titlefieldcreate">' . fieldLabel('Label', 'label', 1) . '</td>';
	print '<td>';
	print '<input name="label" id="label" class="minwidth300" value="' . dol_escape_htmltag($label) . '">';
	print "</td>";
	print "</tr>\n";

	// Date value
	print '<tr><td>';
	print $form->editfieldkey($form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo")), 'dateep', '', null, 0, 'string', '', 1) . '</td><td>';
	if ($isV9p) {
		print $form->selectDate($dateep, "dateep", '', '', '', 'add');
	} else {
		print $form->select_date($dateep, "dateep", '', '', '', 'add');
	}
	print '</td></tr>';

	print '<div id="selectmethod">';
	print '<div class="hideonsmartphone float">';
	print $langs->trans("Type") . ':&nbsp;&nbsp;&nbsp;';
	print '</div>';
	print '</div>';
	print "<br>\n";

	// Type
	print '<tr><td>' . $langs->trans("Type") . '</td><td>';
	print '<input type="radio" class="flat" id="type_payment" name="type" value="0"' . ($type ? '' : ' checked="checked"') . '>';
	print '<label for="type_payment">&nbsp;' . $langs->trans("Payment") . '</label>';
	print '&nbsp;&nbsp;&nbsp;';
	print '<input type="radio" class="flat" id="type_refund" name="type" value="1"' . ($type ? ' checked="checked"' : '') . '>';
	print '<label for="type_refund">&nbsp;' . $langs->trans("Refund") . '</label>';
	print '</td></tr>';

	// Amount
	print '<tr><td>';
	print $form->editfieldkey('Amount', 'amount', '', null, 0, 'string', '', 1) . '</td><td>';
	print '<input name="amount" id="amount" class="minwidth100" value="' . dol_escape_js(dol_escape_htmltag($amount)) . '">';
	print '</td></tr>';

	// Bank account
	print '<tr><td>' . $langs->trans("BankAccount") . '</td>';
	print '<td>';
	print $account->getNomUrl(1);
	print "</td>";
	print "</tr>\n";

	// Number
	print '<tr><td><label for="num_payment">' . $langs->trans('Numero');
	print ' <em>(' . $langs->trans("ChequeOrTransferNumber") . ')</em>';
	print '</label></td>';
	print '<td><input name="num_payment" id="num_payment" type="text" value="' . dol_escape_js(dol_escape_htmltag($num_payment)) . '"></td></tr>' . "\n";

	if ($isV14p) {
		// Comments
		print '<tr>';
		print '<td class="tdtop">' . $langs->trans("Note") . ' (' . $langs->trans("Payment") . ')</td>';
		print '<td class="tdtop"><textarea name="note" wrap="soft" cols="60" rows="' . ROWS_3 . '">' . $note . '</textarea></td>';
		print '</tr>';
	}

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit');
	}

	print "</table>";
} else {
    print '<br><span style="color: red;">' . $langs->trans('NotEnoughPermissions') . '</span>';
}

dol_fiche_end();
