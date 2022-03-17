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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

$langs->loadLangs(array('compta', 'bills', 'banks'));

$actioncode	= GETPOST('actioncode', 'alpha');
$label		= GETPOST('label', 'alpha');
$dateech	= dol_mktime(GETPOST('echhour'), GETPOST('echmin'), GETPOST('echsec'), GETPOST('echmonth'), GETPOST('echday'), GETPOST('echyear'));
$dateperiod	= dol_mktime(GETPOST('periodhour'), GETPOST('periodmin'), GETPOST('periodsec'), GETPOST('periodmonth'), GETPOST('periodday'), GETPOST('periodyear'));
$amount		= GETPOST('amount', 'int');
$project_id	= GETPOST('fk_project', 'int');

if ($action == 'update_manual_reconciliation_type' || (!$error && $action == 'save')) {
    $label = $object->label . (!empty($object->comment) ? ' - ' . $object->comment : '');
    $amount = abs($object->remaining_amount_to_link);
	$dateech = $object->record_date;
    $projectid = 0;
}

$isV9p = version_compare(DOL_VERSION, "9.0.0") >= 0;
$isV11p = version_compare(DOL_VERSION, "11.0.0") >= 0;

/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);
$formsocialcontrib = new FormSocialContrib($db);

print load_fiche_titre($langs->trans("Banking4DolibarrNewContributions"), '', 'title_accountancy.png');

dol_fiche_head('', '');

if ($user->rights->tax->charges->creer) {

    $year_current = strftime("%Y", $object->record_date);
    $pastmonth = strftime("%m", $object->record_date) - 1;
    $pastmonthyear = $year_current;
    if ($pastmonth == 0)
    {
        $pastmonth = 12;
        $pastmonthyear--;
    }

    $dateperiodmonth = GETPOST('dateperiodmonth', 'int');
    $dateperiodday = GETPOST('dateepday', 'int');
    $dateperiodyear = GETPOST('dateperiodyear', 'int');
    $dateperiod = dol_mktime(23, 59, 59, $dateperiodmonth, $dateperiodday, $dateperiodyear);

    if (empty($dateperiod)) // We define dateperiod
    {
        $dateperiod = dol_get_last_day($pastmonthyear, $pastmonth, false);
    }

	$label = dol_trunc($label, 80, 'right', 'UTF-8', 1);

	print '<table class="border centpercent">';

	// Label
	print '<tr><td>';
	print $form->editfieldkey('Label', 'label', '', null, 0, 'string', '', 1) . '</td><td>';
	print '<input name="label" id="label" class="minwidth300" maxlength="80" value="' . dol_escape_js(dol_escape_htmltag($label)) . '">';
	print '</td></tr>';

	// Type
	print '<tr><td>';
	print $form->editfieldkey('Type', 'actioncode', '', null, 0, 'string', '', 1) . '</td><td>';
	$formsocialcontrib->select_type_socialcontrib($actioncode, 'actioncode', 1);
	print '</td></tr>';

	// Date end period
	print '<tr><td>';
	print $form->editfieldkey($form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo")), 'period', '', null, 0, 'string', '', 1) . '</td><td>';
	if (version_compare(DOL_VERSION, "9.0.0") >= 0) {
		print $form->selectDate(! empty($dateperiod)?$dateperiod:'-1', 'period', 0, 0, 0, 'charge', 1);
	} else {
		print $form->select_date(! empty($dateperiod)?$dateperiod:'-1', 'period', 0, 0, 0, 'charge', 1);
	}
	print '</td></tr>';

	// Date due
	print '<tr><td>';
	print $form->editfieldkey('DateDue', 'ech', '', null, 0, 'string', '', 1) . '</td><td>';
	if (version_compare(DOL_VERSION, "9.0.0") >= 0) {
		print $form->selectDate(! empty($dateech)?$dateech:'-1', 'ech', 0, 0, 0, 'charge', 1);
	} else {
		print $form->select_date(! empty($dateech)?$dateech:'-1', 'ech', 0, 0, 0, 'charge', 1);
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
//
//	// Other attributes
//	$parameters = array();
//	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
//	print $hookmanager->resPrint;
//	if (empty($reshook) && $isV11p) {
//		print $object->showOptionals($extrafields, 'edit');
//	}

	print "</table>";
} else {
    print '<br><span style="color: red;">' . $langs->trans('NotEnoughPermissions') . '</span>';
}

dol_fiche_end();
