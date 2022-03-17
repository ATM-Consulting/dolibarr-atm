<?php
/* Copyright (C) 2021      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/eurochefintervention/admin/setup.php
 *		\ingroup    eurochefintervention
 *		\brief      Page to setup eurochefintervention module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
dol_include_once('/eurochefintervention/lib/eurochefintervention.lib.php');

$langs->load("admin");
$langs->load("eurochefintervention@eurochefintervention");
$langs->load("opendsi@eurochefintervention");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

/*
 *	Actions
 */

$errors = [];
$error = 0;
$db->begin();

if ($action == 'set_options') {
	$value = GETPOST('EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE', 'int');
	$res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE', 'int');
	$res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE', 'int');
	$res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}

    $value = GETPOST('EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE', 'int');
    $res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE', 'int');
    $res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE', 'int');
    $res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE', 'int');
    $res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE', 'int');
    $res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE', 'int');
    $res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE', 'int');
    $res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

	$value = GETPOST('EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_COUNT', 'int');
	$res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_COUNT', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_PATTERN', 'alphanohtml');
	$res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_PATTERN', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_QTY_PATTERN', 'alphanohtml');
	$res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_QTY_PATTERN', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}

    $value = GETPOST('EUROCHEFINTERVENTION_EXTRAFIELDS_THIRDPARTY_FIELD_TRAVELFEES', 'alphanohtml');
    $res = dolibarr_set_const($db, 'EUROCHEFINTERVENTION_EXTRAFIELDS_THIRDPARTY_FIELD_TRAVELFEES', $value, 'chaine', 1, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }
} elseif (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code) ? GETPOST($code) : 1);
	$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
	if ($res < 0) {
		$errors[] = $db->lasterror();
		$error++;
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);
	if ($res < 0) {
		$errors[] = $db->lasterror();
		$error++;
	}
}

if ($action != '') {
    if (!$error) {
	    $db->commit();
        setEventMessage($langs->trans("SetupSaved"));
	    header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
	    $db->rollback();
        setEventMessages('', $errors, 'errors');
    }
}


/*
 *	View
 */

$form = new Form($db);

$wikihelp = '';
llxHeader('', $langs->trans("EurochefInterventionSetup"), $wikihelp);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("EurochefInterventionSetup"), $linkback, 'title_setup');
print "<br>\n";

$head = eurochefintervention_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163058Name"), 0, 'opendsi@eurochefintervention');

/**
 * General settings.
 */

print '<div id="options"></div>';
print load_fiche_titre($langs->trans("Others"), '', '');
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '#options">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_options">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="width20p">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="right">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE, 'EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE', '1');
print '</td></tr>' . "\n";
// EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE, 'EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE', '1');
print '</td></tr>' . "\n";
// EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE, 'EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE', '1');
print '</td></tr>' . "\n";
// EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE, 'EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE', '1');
print '</td></tr>' . "\n";
// EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForLineDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE, 'EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE', '1');
print '</td></tr>' . "\n";

print '<td class="oddeven"><td colspan="3">' . "\n";
print '</td></tr>' . "\n";

// EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE, 'EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE', '1');
print '</td></tr>' . "\n";
// EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE, 'EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE', '1');
print '</td></tr>' . "\n";
// EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE, 'EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE', '1');
print '</td></tr>' . "\n";
// EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE, 'EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE', '1');
print '</td></tr>' . "\n";
// EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionServiceForInvoiceDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print $form->select_produits($conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE, 'EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE', '1');
print '</td></tr>' . "\n";

print '<td class="oddeven"><td colspan="3">' . "\n";
print '</td></tr>' . "\n";

// EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_COUNT
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionExtraFieldsProductCountName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionExtraFieldsProductCountDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print '<input type="number" name="EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_COUNT" size="50" value="' . dol_escape_htmltag($conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_COUNT) . '" />' . "\n";
print '</td></tr>' . "\n";

// EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_PATTERN
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionExtraFieldsProductPatternName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionExtraFieldsProductPatternDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print '<input type="text" name="EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_PATTERN" size="50" value="' . dol_escape_htmltag($conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_PATTERN) . '" />' . "\n";
print '</td></tr>' . "\n";

// EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_QTY_PATTERN
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("EurochefInterventionExtraFieldsProductQtyPatternName") . '</td>' . "\n";
print '<td>' . $langs->trans("EurochefInterventionExtraFieldsProductQtyPatternDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print '<input type="text" name="EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_QTY_PATTERN" size="50" value="' . dol_escape_htmltag($conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_QTY_PATTERN) . '" />' . "\n";
print '</td></tr>' . "\n";

// EUROCHEFINTERVENTION_EXTRAFIELDS_THIRDPARTY_FIELD_TRAVELFEES
$extrafields = new ExtraFields($db);
$company = new Societe($db);
$company_field_list = array();
$elementtype = 'societe';
$extralabels = $extrafields->fetch_name_optionals_label($company->element);
foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
    if (!empty($extrafields->attributes[$elementtype]['langfile'][$key])) {
        $langs->load($extrafields->attributes[$elementtype]['langfile'][$key]);
    }
    $company_field_list['options_' . $key] = $langs->trans($label);
}
print '<tr class="oddeven">';
print '<td>' . $langs->trans('EurochefInterventionThirdpartyFieldTravelFeesName') . '</td>';
print '<td>' . $langs->trans('EurochefInterventionThirdpartyFieldTravelFeesDesc') . '</td>';
print '<td class="nowrap right">' . "\n";
print Form::selectarray('EUROCHEFINTERVENTION_EXTRAFIELDS_THIRDPARTY_FIELD_TRAVELFEES', $company_field_list, $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_THIRDPARTY_FIELD_TRAVELFEES, 1, 0, 0, 'minwidth300');
print '</td></tr>';

print '</table>' . "\n";

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

dol_fiche_end();

llxFooter();

$db->close();
