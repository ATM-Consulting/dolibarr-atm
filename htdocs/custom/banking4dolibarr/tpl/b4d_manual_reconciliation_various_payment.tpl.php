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
 *  \file		htdocs/banking4dolibarr/tpl/b4d_manual_reconciliation_bank_transfer.tpl.php
 *  \ingroup	banking4dolibarr
 *  \brief		Template to show manuel reconciliation content for the type 'bank_transfer'
 */

$langs->loadLangs(array("banks", 'accountancy'));

$label = GETPOST('label', 'alpha');
$sens = GETPOST('sens','int');
$amount = GETPOST('amount','int');
$num_payment = GETPOST('num_payment','alpha');
$projectid = GETPOST('projectid','int');
$category_transaction = GETPOST('category_transaction','int');
$accountancy_code = GETPOST('accountancy_code','alpha');
$subledger_account = GETPOST('subledger_account','alpha');

if ($action == 'update_manual_reconciliation_type' || (!$error && $action == 'save')) {
    $label = $object->label . (!empty($object->comment) ? ' - ' . $object->comment : '');
    $sens = $object->remaining_amount_to_link < 0 ? 0 : 1;
    $amount = abs($object->remaining_amount_to_link);
    $num_payment = '';
    $projectid = 0;
    $category_transaction = '';
    $accountancy_code = '';
	$subledger_account = '';

	if ($object->id_category > 0 && !empty($category_infos)) {
		$accountancy_code = $category_infos["accountancy_code"];
		$category_transaction = $category_infos["category"];
	}
}

/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);
if (!empty($conf->accounting->enabled)) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
    $formaccounting = New FormAccounting($db);
}

print_barre_liste($langs->trans("Banking4DolibarrNewVariousPayment"), '', '', '', '', '', '', 0, '', '');

dol_fiche_head('', '');

if ($user->rights->banque->modifier) {
	$use_various_payment_card = !empty($conf->global->EASYA_VERSION) ||
		(version_compare(DOL_VERSION, "9.0.0") < 0 && $conf->global->MAIN_FEATURES_LEVEL >= 1) ||
		(version_compare(DOL_VERSION, "9.0.0") >= 0 && empty($conf->global->BANK_USE_OLD_VARIOUS_PAYMENT));

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="titlefieldcreate">' . fieldLabel('Label', 'label', 1) . '</td>';
	print '<td>';
	print '<input name="label" id="label" class="minwidth300" value="' . dol_escape_htmltag($label) . '">';
	print "</td>";
	print "</tr>\n";

	// Sens
	print '<tr><td>' . fieldLabel('Sens', 'sens', 1) . '</td>';
	print '<td>';
	$sensarray = array('0' => $langs->trans("Debit"), '1' => $langs->trans("Credit"));
	print $form->selectarray('sens', $sensarray, $sens);
	print "</td>";
	print "</tr>\n";

	// Amount
	print '<tr><td>' . fieldLabel('Amount', 'sens', 1) . '</td>';
	print '<td>';
	print '<input name="amount" id="amount" class="minwidth100" value="' . dol_escape_htmltag($amount) . '">';
	print "</td>";
	print "</tr>\n";

	// BankAccount
	print '<tr><td>' . $langs->trans("BankAccount") . '</td>';
	print '<td>';
	print $account->getNomUrl(1);
	print "</td>";
	print "</tr>\n";

	// Number
	print '<tr><td><label for="num_payment">' . $langs->trans('Numero') . ' <em>(' . $langs->trans("ChequeOrTransferNumber") . ')</em></label></td>';
	print '<td>';
	print '<input name="num_payment" id="num_payment" type="text" value="' . dol_escape_htmltag($num_payment) . '">';
	print "</td>";
	print "</tr>\n";

	// Project
	if (!empty($conf->projet->enabled) && $use_various_payment_card) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
		$formproject = new FormProjets($db);
		$langs->load("projects");
		print '<tr><td>' . $langs->trans("Project") . '</td><td>';
		$numproject = $formproject->select_projects(-1, $projectid, 'projectid', 0, 0, 1, 1);
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Load bank groups
	require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/bankcateg.class.php';
	$bankcateg = new BankCateg($db);
	$options = array();
	foreach ($bankcateg->fetchAll() as $bankcategory) {
		$options[$bankcategory->id] = $bankcategory->label;
	}

	// Category
	if (is_array($options) && count($options) && $conf->categorie->enabled) {
		print '<tr><td>' . $langs->trans("RubriquesTransactions") . '</td><td>';
		print $form->selectarray('category_transaction', $options, $category_transaction, 1);
		print '</td></tr>';
	}

	// Accountancy account
	print '<tr><td class="titlefieldcreate">' . fieldLabel('AccountAccounting', 'accountancy_code', 1) . '</td>';
	print '<td class="maxwidthonsmartphone">';
	if (!empty($conf->accounting->enabled)) {
		// TODO Remove the fieldrequired and allow instead to edit a various payment to enter accounting code
		print $formaccounting->select_account($accountancy_code, 'accountancy_code', 1, null, 1, 1);
	} else { // For external software
		print '<input class="minwidth100" id="accountancy_code" name="accountancy_code" value="' . dol_escape_htmltag($accountancy_code) . '">';
	}
	print "</td>";
	print "</tr>\n";

	if ($use_various_payment_card && version_compare(DOL_VERSION, "10.0.0") >= 0) {
		// Subledger account
		print '<tr><td>' . $langs->trans("SubledgerAccount") . '</td>';
		if (!empty($conf->accounting->enabled)) {
			print '<td>';
			if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX)) {
				print $formaccounting->select_auxaccount($subledger_account, 'subledger_account', 1, '');
			} else {
				print '<input type="text" class="maxwidth200" name="subledger_account" value="' . dol_escape_htmltag($subledger_account) . '">';
			}
			print '</td>';
		} else { // For external software
			print '<td class="maxwidthonsmartphone"><input class="minwidth100" name="subledger_account" value="' . dol_escape_htmltag($subledger_account) . '"></td>';
		}
		print '</tr>';
	}

	print "</table>";
} else {
    print '<br><span style="color: red;">' . $langs->trans('NotEnoughPermissions') . '</span>';
}

dol_fiche_end();
