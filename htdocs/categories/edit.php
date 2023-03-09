<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Patrick Raguin	  	<patrick.raguin@gmail.com>
 * Copyright (C) 2020      Frédéric France		<frederic.france@netlogic.fr>
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
 *      \file       htdocs/categories/edit.php
 *      \ingroup    category
 *      \brief      Page d'edition de categorie produit
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

/* ************************* SPÉ VET COMPANY { *********************** */
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
/* ************************* SPÉ VET COMPANY } *********************** */

// Load translation files required by the page
$langs->load("categories");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alphanohtml');
$type = GETPOST('type', 'aZ09');		// Can be int or string
$action = (GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'edit');
$confirm = GETPOST('confirm');
$cancel = GETPOST('cancel', 'alpha');

$socid = (int) GETPOST('socid', 'int');
$label = (string) GETPOST('label', 'alphanohtml');
$description = (string) GETPOST('description', 'restricthtml');
$color = preg_replace('/[^0-9a-f#]/i', '', (string) GETPOST('color', 'alphanohtml'));
$visible = (int) GETPOST('visible', 'int');
$parent = (int) GETPOST('parent', 'int');

if ($id == "") {
	dol_print_error('', 'Missing parameter id');
	exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
if ($id > 0) {
	$result = $object->fetch($id);
}

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard'));

$error = 0;


/*
 * Actions
 */

if ($cancel) {
	header('Location: '.DOL_URL_ROOT.'/categories/viewcat.php?id='.$object->id.'&type='.$type);
	exit;
}

// Action mise a jour d'une categorie
if ($action == 'update' && $user->rights->categorie->creer) {
	$object->oldcopy = dol_clone($object);
	$object->label          = $label;
	$object->description    = dol_htmlcleanlastbr($description);
	$object->color          = $color;
	$object->socid          = ($socid > 0 ? $socid : 0);
	$object->visible        = $visible;
	$object->fk_parent = $parent != -1 ? $parent : 0;


/* ************************* SPÉ VET COMPANY { *********************** */
	$accountancy_code_sell 			 = GETPOST('accountancy_code_sell','alpha');
	$accountancy_code_sell_intra	 = GETPOST('accountancy_code_sell_intra','alpha');
	$accountancy_code_sell_export	 = GETPOST('accountancy_code_sell_export','alpha');
	$accountancy_code_buy 			 = GETPOST('accountancy_code_buy','alpha');
	if ($accountancy_code_sell <= 0) {
		$object->accountancy_code_sell = '';
	} else {
		$object->accountancy_code_sell = $accountancy_code_sell;
	}
	if ($accountancy_code_sell_intra <= 0) {
		$object->accountancy_code_sell_intra = '';
	} else {
		$object->accountancy_code_sell_intra = $accountancy_code_sell_intra;
	}
	if ($accountancy_code_sell_export <= 0) {
		$object->accountancy_code_sell_export = '';
	} else {
		$object->accountancy_code_sell_export = $accountancy_code_sell_export;
	}
	if ($accountancy_code_buy <= 0) {
		$object->accountancy_code_buy = '';
	} else {
		$object->accountancy_code_buy = $accountancy_code_buy;
	}
/* ************************* SPÉ VET COMPANY } *********************** */

	if (empty($object->label)) {
		$error++;
		$action = 'edit';
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	if (!$error && empty($object->error)) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) $error++;

		if (!$error && $object->update($user) > 0) {
			header('Location: '.DOL_URL_ROOT.'/categories/viewcat.php?id='.$object->id.'&type='.$type);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

/* ************************* SPÉ VET COMPANY { *********************** */
if (! empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);
/* ************************* SPÉ VET COMPANY } *********************** */

llxHeader("", "", $langs->trans("Categories"));

print load_fiche_titre($langs->trans("ModifCat"));

$object->fetch($id);


print "\n";
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
print '<input type="hidden" name="type" value="'.$type.'">';

print dol_get_fiche_head('');

print '<table class="border centpercent">';

// Ref
print '<tr><td class="titlefieldcreate fieldrequired">';
print $langs->trans("Ref").'</td>';
print '<td><input type="text" size="25" id="label" name ="label" value="'.$object->label.'" />';
print '</tr>';

// Description
print '<tr>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor = new DolEditor('description', $object->description, '', 200, 'dolibarr_notes', '', false, true, $conf->fckeditor->enabled, ROWS_6, '90%');
$doleditor->Create();
print '</td></tr>';

// Color
print '<tr>';
print '<td>'.$langs->trans("Color").'</td>';
print '<td>';
print $formother->selectColor($object->color, 'color');
print '</td></tr>';

// Parent category
print '<tr><td>'.$langs->trans("In").'</td><td>';
print $form->select_all_categories($type, $object->fk_parent, 'parent', 64, $object->id);
print '</td></tr>';


/* ************************* SPÉ VET COMPANY { *********************** */

// Accountancy codes
if (! empty($conf->accounting->enabled) && !empty($conf->global->CATEGORIE_USE_ACCOUNTANCY_CODES) && $type == 0)
{
    // Accountancy_code_sell
    print '<tr><td class="titlefieldcreate">'.$langs->trans("CategorieAccountancySellCode").'</td>';
    print '<td>';
    print $formaccounting->select_account($object->accountancy_code_sell, 'accountancy_code_sell', 1, null, 1, 1, '');
    print '</td></tr>';

    if ($conf->global->MAIN_FEATURES_LEVEL)
    {
        // Accountancy_code_sell_intra
        if ($mysoc->isInEEC())
        {
            print '<tr><td class="titlefieldcreate">'.$langs->trans("CategorieAccountancySellIntraCode").'</td>';
            print '<td>';
            print $formaccounting->select_account($object->accountancy_code_sell_intra, 'accountancy_code_sell_intra', 1, null, 1, 1, '');
            print '</td></tr>';
        }

        // Accountancy_code_sell_export
        print '<tr><td class="titlefieldcreate">'.$langs->trans("CategorieAccountancySellExportCode").'</td>';
        print '<td>';
        print $formaccounting->select_account($object->accountancy_code_sell_export, 'accountancy_code_sell_export', 1, null, 1, 1, '');
        print '</td></tr>';
    }

    // Accountancy_code_buy
    print '<tr><td>'.$langs->trans("CategorieAccountancyBuyCode").'</td>';
    print '<td>';
    print $formaccounting->select_account($object->accountancy_code_buy, 'accountancy_code_buy', 1, null, 1, 1, '');
    print '</td></tr>';
}
/* ************************* SPÉ VET COMPANY } *********************** */

$parameters = array();
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (empty($reshook)) {
	print $object->showOptionals($extrafields, 'edit', $parameters);
}

print '</table>';


print dol_get_fiche_end();


print '<div class="center"><input type="submit" class="button" name"submit" value="'.$langs->trans("Modify").'"> &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
