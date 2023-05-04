<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Charles-Fr BENKE     <charles.fr@benke.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/product/accounting_category.php
 *      \ingroup    product
 *      \brief      Page ajout de categories comptabilité
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/accountingcategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array('accountancy', 'categories'));

$action = GETPOST('action', 'aZ09');
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$object = new AccountingCategory($db);

$categid 						= GETPOST('categid');
$label							= GETPOST("label");
$accountancy_code_sell			= GETPOST('accountancy_code_sell', 'alpha');
$accountancy_code_sell_intra	= GETPOST('accountancy_code_sell_intra', 'alpha');
$accountancy_code_sell_export	= GETPOST('accountancy_code_sell_export', 'alpha');
$accountancy_code_buy			= GETPOST('accountancy_code_buy', 'alpha');
$accountancy_code_buy_intra		= GETPOST('accountancy_code_buy_intra', 'alpha');
$accountancy_code_buy_export	= GETPOST('accountancy_code_buy_export', 'alpha');

// by default 'alphanohtml' (better security); hidden conf MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML allows basic html
$label_security_check = empty($conf->global->MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML) ? 'alphanohtml' : 'restricthtml';

if ((!$user->rights->produit->creer) || (!$user->rights->service->creer)) {
	accessforbidden();
}

/*
 * Actions
 */
$usercancreate = (($user->rights->produit->creer) || ($user->rights->service->creer));
$usercanupdateaccountancyinformation = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $usercancreate) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && (!empty($user->rights->produit->product_accountancy_advance->write) || !empty($user->rights->service->service_accountancy_advance->write))));

if (GETPOST('add') && $usercancreate) {
	$error = 0;

	if (!GETPOST('label', $label_security_check)) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), null, 'errors');
		$action = "create";
		$error++;
	}

	if (!$error) {
		$object->label					= GETPOST('label', $label_security_check);

		$accountancy_code_sell			= GETPOST('accountancy_code_sell', 'alpha');
		$accountancy_code_sell_intra	= GETPOST('accountancy_code_sell_intra', 'alpha');
		$accountancy_code_sell_export	= GETPOST('accountancy_code_sell_export', 'alpha');
		$accountancy_code_buy			= GETPOST('accountancy_code_buy', 'alpha');
		$accountancy_code_buy_intra		= GETPOST('accountancy_code_buy_intra', 'alpha');
		$accountancy_code_buy_export	= GETPOST('accountancy_code_buy_export', 'alpha');

		if (empty($accountancy_code_sell) || $accountancy_code_sell == '-1') {
			$object->accountancy_code_sell = '';
		} else {
			$object->accountancy_code_sell = $accountancy_code_sell;
		}
		if (empty($accountancy_code_sell_intra) || $accountancy_code_sell_intra == '-1') {
			$object->accountancy_code_sell_intra = '';
		} else {
			$object->accountancy_code_sell_intra = $accountancy_code_sell_intra;
		}
		if (empty($accountancy_code_sell_export) || $accountancy_code_sell_export == '-1') {
			$object->accountancy_code_sell_export = '';
		} else {
			$object->accountancy_code_sell_export = $accountancy_code_sell_export;
		}
		if (empty($accountancy_code_buy) || $accountancy_code_buy == '-1') {
			$object->accountancy_code_buy = '';
		} else {
			$object->accountancy_code_buy = $accountancy_code_buy;
		}
		if (empty($accountancy_code_buy_intra) || $accountancy_code_buy_intra == '-1') {
			$object->accountancy_code_buy_intra = '';
		} else {
			$object->accountancy_code_buy_intra = $accountancy_code_buy_intra;
		}
		if (empty($accountancy_code_buy_export) || $accountancy_code_buy_export == '-1') {
			$object->accountancy_code_buy_export = '';
		} else {
			$object->accountancy_code_buy_export = $accountancy_code_buy_export;
		}

		$object->create($user);
	}
}

if ($categid) {
	if ($object->fetch($categid) > 0) {
		// Update category
		if (GETPOST('update') && $label) {
			$object->label = $label;

			if (empty($accountancy_code_sell) || $accountancy_code_sell == '-1') {
				$object->accountancy_code_sell = '';
			} else {
				$object->accountancy_code_sell = $accountancy_code_sell;
			}
			if (empty($accountancy_code_sell_intra) || $accountancy_code_sell_intra == '-1') {
				$object->accountancy_code_sell_intra = '';
			} else {
				$object->accountancy_code_sell_intra = $accountancy_code_sell_intra;
			}
			if (empty($accountancy_code_sell_export) || $accountancy_code_sell_export == '-1') {
				$object->accountancy_code_sell_export = '';
			} else {
				$object->accountancy_code_sell_export = $accountancy_code_sell_export;
			}
			if (empty($accountancy_code_buy) || $accountancy_code_buy == '-1') {
				$object->accountancy_code_buy = '';
			} else {
				$object->accountancy_code_buy = $accountancy_code_buy;
			}
			if (empty($accountancy_code_buy_intra) || $accountancy_code_buy_intra == '-1') {
				$object->accountancy_code_buy_intra = '';
			} else {
				$object->accountancy_code_buy_intra = $accountancy_code_buy_intra;
			}
			if (empty($accountancy_code_buy_export) || $accountancy_code_buy_export == '-1') {
				$object->accountancy_code_buy_export = '';
			} else {
				$object->accountancy_code_buy_export = $accountancy_code_buy_export;
			}

			$object->update($user);
		}
		//Delete category
		if ($action == 'delete' && $usercancreate) {
			$object->delete($user);
		}
	}
}


/*
 * View
 */
$formaccounting = new FormAccounting($db);

$title = $langs->trans('RubriquesAccounting');
$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos|DE:Modul_Produkte';

llxHeader('', $title, $help_url);


print load_fiche_titre($langs->trans("RubriquesAccounting"), '', 'object_category');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
/*print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
*/

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Label").'</td>';
print '<td colspan="2">'.$langs->trans("Accountancy").'</td>';
print '<td></td>';
print "</tr>\n";

// Line to add category
if ($action != 'edit') {
	print '<tr class="oddeven">';
	print '<td>&nbsp;</td><td><input name="label" type="text" class="maxwidth100"></td>';

	// Accountancy_code_sell
	print '<td class="minwidth400 nowraponall">';
	print $langs->trans("ProductAccountancySellCode"). '<br>';
	if ($mysoc->isInEEC()) {
		print $langs->trans("ProductAccountancySellIntraCode") . '<br>';
	}
	print $langs->trans("ProductAccountancySellExportCode"). '<br>';
	print $langs->trans("ProductAccountancyBuyCode"). '<br>';
	if ($mysoc->isInEEC()) {
		print $langs->trans("ProductAccountancyBuyIntraCode") . '<br>';
	}
	print $langs->trans("ProductAccountancyBuyExportCode");
	print '<td>';
	print $formaccounting->select_account($accountancy_code_sell, 'accountancy_code_sell', 1, null, 1, 1, 'minwidth150 maxwidth300', 1). '<br>';
	if ($mysoc->isInEEC()) {
		print $formaccounting->select_account($accountancy_code_sell_intra, 'accountancy_code_sell_intra', 1, null, 1, 1, 'minwidth150 maxwidth300', 1) . '<br>';
	}
	print $formaccounting->select_account($accountancy_code_sell_export, 'accountancy_code_sell_export', 1, null, 1, 1, 'minwidth150 maxwidth300', 1). '<br>';
	print $formaccounting->select_account($accountancy_code_buy, 'accountancy_code_buy', 1, null, 1, 1, 'minwidth150 maxwidth300', 1). '<br>';
	if ($mysoc->isInEEC()) {
		print $formaccounting->select_account($accountancy_code_buy_intra, 'accountancy_code_buy_intra', 1, null, 1, 1, 'minwidth150 maxwidth300', 1) . '<br>';
	}
	print $formaccounting->select_account($accountancy_code_buy_export, 'accountancy_code_buy_export', 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
	print '</td>';

	print '<td class="center"><input type="submit" name="add" class="button" value="'.$langs->trans("Add").'"></td>';
	print '</tr>';
}


$sql = "SELECT rowid, label";
$sql .= " FROM ".MAIN_DB_PREFIX."product_accounting_category";
$sql .= " WHERE entity = ".$conf->entity;
$sql .= " ORDER BY rowid";

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	$total = 0;

	while ($i < $num) {
		$objp = $db->fetch_object($result);

		print '<tr class="oddeven">';
		print '<td>'.$objp->rowid.'</td>';
		if (GETPOST('action', 'aZ09') == 'edit' && GETPOST("categid") == $objp->rowid) {
			print '<td colspan="3">';
			print '<input type="hidden" name="categid" value="'.$objp->rowid.'">';
			print '<input name="label" type="text" size=45 value="'.$objp->label.'">';

			print '<td class="minwidth400 nowraponall">';
			print $langs->trans("ProductAccountancySellCode"). '<br>';
			if ($mysoc->isInEEC()) {
				print $langs->trans("ProductAccountancySellIntraCode") . '<br>';
			}
			print $langs->trans("ProductAccountancySellExportCode"). '<br>';
			print $langs->trans("ProductAccountancyBuyCode"). '<br>';
			if ($mysoc->isInEEC()) {
				print $langs->trans("ProductAccountancyBuyIntraCode") . '<br>';
			}
			print $langs->trans("ProductAccountancyBuyExportCode");
			print '<td>';
			print $formaccounting->select_account($accountancy_code_sell, 'accountancy_code_sell', 1, null, 1, 1, 'minwidth150 maxwidth300', 1). '<br>';
			if ($mysoc->isInEEC()) {
				print $formaccounting->select_account($accountancy_code_sell_intra, 'accountancy_code_sell_intra', 1, null, 1, 1, 'minwidth150 maxwidth300', 1) . '<br>';
			}
			print $formaccounting->select_account($accountancy_code_sell_export, 'accountancy_code_sell_export', 1, null, 1, 1, 'minwidth150 maxwidth300', 1). '<br>';
			print $formaccounting->select_account($accountancy_code_buy, 'accountancy_code_buy', 1, null, 1, 1, 'minwidth150 maxwidth300', 1). '<br>';
			if ($mysoc->isInEEC()) {
				print $formaccounting->select_account($accountancy_code_buy_intra, 'accountancy_code_buy_intra', 1, null, 1, 1, 'minwidth150 maxwidth300', 1) . '<br>';
			}
			print $formaccounting->select_account($accountancy_code_buy_export, 'accountancy_code_buy_export', 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
			print '</td>';

			print '<input type="submit" name="update" class="button" value="'.$langs->trans("Edit").'">';
			print "</td>";
		} else {
			print "<td>".$objp->label."</td>";
			print '<td class="minwidth400 nowraponall">';
			print $langs->trans("ProductAccountancySellCode"). '<br>';
			if ($mysoc->isInEEC()) {
				print $langs->trans("ProductAccountancySellIntraCode") . '<br>';
			}
			print $langs->trans("ProductAccountancySellExportCode"). '<br>';
			print $langs->trans("ProductAccountancyBuyCode"). '<br>';
			if ($mysoc->isInEEC()) {
				print $langs->trans("ProductAccountancyBuyIntraCode") . '<br>';
			}
			print $langs->trans("ProductAccountancyBuyExportCode");
			print '<td>';
			if (!empty($object->accountancy_code_sell)) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch('', $object->accountancy_code_sell, 1);

				print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
			}
			print '<br>';
			if ($mysoc->isInEEC()) {
				if (!empty($object->accountancy_code_sell_intra)) {
					$accountingaccount2 = new AccountingAccount($db);
					$accountingaccount2->fetch('', $object->accountancy_code_sell_intra, 1);

					print $accountingaccount2->getNomUrl(0, 1, 1, '', 1);
				}
				print '<br>';
			}
			if (!empty($object->accountancy_code_sell_export)) {
				$accountingaccount3 = new AccountingAccount($db);
				$accountingaccount3->fetch('', $object->accountancy_code_sell_export, 1);

				print $accountingaccount3->getNomUrl(0, 1, 1, '', 1);
			}
			print '<br>';
			if (!empty($object->accountancy_code_buy)) {
				$accountingaccount4 = new AccountingAccount($db);
				$accountingaccount4->fetch('', $object->accountancy_code_buy, 1);

				print $accountingaccount4->getNomUrl(0, 1, 1, '', 1);
			}
			print '<br>';
			if ($mysoc->isInEEC()) {
				if (!empty($object->accountancy_code_buy_intra)) {
					$accountingaccount5 = new AccountingAccount($db);
					$accountingaccount5->fetch('', $object->accountancy_code_buy_intra, 1);

					print $accountingaccount5->getNomUrl(0, 1, 1, '', 1);
				}
				print '<br>';
			}
			if (!empty($object->accountancy_code_buy_export)) {
				$accountingaccount6 = new AccountingAccount($db);
				$accountingaccount6->fetch('', $object->accountancy_code_buy_export, 1);

				print $accountingaccount6->getNomUrl(0, 1, 1, '', 1);
			}
			print '</td>';
			print '<td class="center">';
			print '<a class="editfielda reposition marginleftonly marginrightonly" href="'.$_SERVER["PHP_SELF"].'?categid='.$objp->rowid.'&amp;action=edit&amp;token='.newToken().'">'.img_edit().'</a>';
			print '<a class="marginleftonly" href="'.$_SERVER["PHP_SELF"].'?categid='.$objp->rowid.'&amp;action=delete&amp;token='.newToken().'">'.img_delete().'</a>';
			print '</td>';
		}
		print "</tr>";
		$i++;
	}
	$db->free($result);
}

print '</table>';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
