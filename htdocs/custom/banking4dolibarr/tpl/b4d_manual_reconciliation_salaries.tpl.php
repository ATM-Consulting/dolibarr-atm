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
 *  \file		htdocs/banking4dolibarr/tpl/b4d_manual_reconciliation_salaries.tpl.php
 *  \ingroup	banking4dolibarr
 *  \brief		Template to show manuel reconciliation content for the type 'salaries'
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

$langs->loadLangs(array("compta","banks","bills","users","salaries","hrm"));

$fk_user = GETPOST("fk_user", 'int') > 0 ? GETPOST("fk_user", "int") : -1;

$datesp = dol_mktime(12, 0, 0, GETPOST("datespmonth", 'int'), GETPOST("datespday", 'int'), GETPOST("datespyear", 'int'));
$dateep = dol_mktime(12, 0, 0, GETPOST("dateepmonth", 'int'), GETPOST("dateepday", 'int'), GETPOST("dateepyear", 'int'));

$label = GETPOST('label', 'alpha');
$amount = GETPOST('amount','int');
$num_payment = GETPOST('num_payment','alpha');
$projectid = GETPOST('fk_project','int');
$note = GETPOST('note','restricthtml');

if ($action == 'update_manual_reconciliation_type' || (!$error && $action == 'save')) {
    $label = $object->label . (!empty($object->comment) ? ' - ' . $object->comment : '');
    $amount = abs($object->remaining_amount_to_link);
    $num_payment = '';
    $projectid = 0;
	$note = '';
}

$isV9p = version_compare(DOL_VERSION, "9.0.0") >= 0;
$isV11p = version_compare(DOL_VERSION, "11.0.0") >= 0;
$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);

print load_fiche_titre($langs->trans("Banking4DolibarrNewSalaries"), '', 'title_accountancy.png');

dol_fiche_head('', '');

if ($user->rights->salaries->write) {

	if ($conf->global->BANKING4DOLIBARR_RECONCILIATION_SALARIES_DEFAULT_PERIOD_RULES == 1) {
		$pastmonth = strftime("%m", $object->record_date);    // Current month
	} else {
		$pastmonth = strftime("%m", $object->record_date) - 1;  // Previous month
	}
	$year_current = strftime("%Y", $object->record_date);
	$pastmonthyear = $year_current;
	if ($pastmonth == 0) {
		$pastmonth = 12;
		$pastmonthyear--;
	}

	$datespmonth = GETPOST('datespmonth', 'int');
	$datespday = GETPOST('datespday', 'int');
	$datespyear = GETPOST('datespyear', 'int');
	$dateepmonth = GETPOST('dateepmonth', 'int');
	$dateepday = GETPOST('dateepday', 'int');
	$dateepyear = GETPOST('dateepyear', 'int');
	$datesp = dol_mktime(0, 0, 0, $datespmonth, $datespday, $datespyear);
	$dateep = dol_mktime(23, 59, 59, $dateepmonth, $dateepday, $dateepyear);

	if (empty($datesp) || empty($dateep)) // We define date_start and date_end
	{
		$datesp = dol_get_first_day($pastmonthyear, $pastmonth, false);
		$dateep = dol_get_last_day($pastmonthyear, $pastmonth, false);
	}

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td>';
	print $form->editfieldkey('Label', 'label', '', null, 0, 'string', '', 1) . '</td><td>';
	print '<input name="label" id="label" class="minwidth300" value="' . dol_escape_js(dol_escape_htmltag($label)) . '">';
	print '</td></tr>';

	// Date start period
	print '<tr><td>';
	print $form->editfieldkey('DateStartPeriod', 'datesp', '', null, 0, 'string', '', 1) . '</td><td>';
	if ($isV9p) {
		print $form->selectDate($datesp, "datesp", '', '', '', 'add');
	} else {
		print $form->select_date($datesp, "datesp", '', '', '', 'add');
	}
	print '</td></tr>';

	// Date end period
	print '<tr><td>';
	print $form->editfieldkey('DateEndPeriod', 'dateep', '', null, 0, 'string', '', 1) . '</td><td>';
	if ($isV9p) {
		print $form->selectDate($dateep, "dateep", '', '', '', 'add');
	} else {
		print $form->select_date($dateep, "dateep", '', '', '', 'add');
	}
	print '</td></tr>';

	// Employee
	print '<tr><td>';
	print $form->editfieldkey('Employee', 'fk_user', '', null, 0, 'string', '', 1) . '</td><td>';
	$noactive = 0; // We keep active and unactive users
	print $form->select_dolusers($fk_user, 'fk_user', 1, '', 0, '', '', 0, 0, 0, 'AND employee=1', 0, '', 'maxwidth300', $noactive);
	$suggested_employees = $object->suggested_employees();
	if (is_array($suggested_employees) && !empty($suggested_employees)) {
		$langs->load('banking4dolibarr@banking4dolibarr');
		print ' - ' . $langs->trans('Banking4DolibarrSuggestedEmployee') . ' : ';
		print $form->selectarray('suggested_employee', $suggested_employees, -1, $langs->trans('Banking4DolibarrSelectEmployee'), 0, 0, null, 0, 'minwidth300');
		print "<script type=\"text/javascript\">\n";
		print "$(document).ready(function(){\n";
		print "$('#suggested_employee').on('change', function () {\n";
		print "var _this = $(this);\n";
		print "$('#fk_user').val(_this.val());\n";
		print "$('#fk_user').trigger('change');\n";
		print "$('#search_fk_user').val($('#suggested_employee option:selected').text());\n";
		print "});\n";
		if (count($suggested_employees) == 1) {
			$val = array_keys($suggested_employees);
			print "$('#suggested_employee').val('{$val[0]}');\n";
			print "$('#suggested_employee').trigger('change');\n";
		}
		print "});\n";
		print "</script>\n";
	}
	print '</td></tr>';

	// Amount
	print '<tr><td>';
	print $form->editfieldkey('Amount', 'amount', '', null, 0, 'string', '', 1) . '</td><td>';
	print '<input name="amount" id="amount" class="minwidth100" value="' . dol_escape_js(dol_escape_htmltag($amount)) . '">';
	print '</td></tr>';

	// Project
	if (!empty($conf->projet->enabled) && $isV9p) {
		$langs->load("projects");

		require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
		$formproject = new FormProjets($db);

		print '<tr><td>' . $langs->trans("Project") . '</td><td>';
		$formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1);
		print '</td></tr>';
	}

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
	if (empty($reshook) && $isV11p) {
		print $object->showOptionals($extrafields, 'edit');
	}

	print "</table>";
} else {
    print '<br><span style="color: red;">' . $langs->trans('NotEnoughPermissions') . '</span>';
}

dol_fiche_end();
