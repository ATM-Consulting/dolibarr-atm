<?php

/*
 * Copyright (C) 2015-2018 Inovea Conseil	<info@inovea-conseil.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *    \file        admin/admin.php
 *    \ingroup    chantier
 *    \brief        This file is an example module setup page
 *                Put some comments here
 */
// Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php"))
    $res = @include '../main.inc.php';     // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php"))
    $res = @include '../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include '../../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (!$res)
    die("Include of main fails");
// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';

global $db, $conf;
$langs->load("invoicetracking@invoicetracking");
$langs->load("admin");
$formadmin = new FormAdmin($db);
$action = GETPOST('action');
$name = GETPOST('name');
// Access control
if (!$user->admin) {
    accessforbidden();
}

$block = 1;
$authorizedCountry = ["Suisse"];
$country = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);

if (in_array($country[2], $authorizedCountry)) {
    $block = 0;
}

if (GETPOST('l')) {
    $l = GETPOST('l');
} else {
    $l = $langs->defaultlang;
}

if (!$user->admin)
    accessforbidden();

//echo "<pre>".print_r($conf->global,1)."</pre>";
if ($action == 'set') {
    dolibarr_set_const($db, $name, 1, 'chaine', 0, '', $conf->entity);
} else if ($action == 'del') {
    dolibarr_del_const($db, $name, $conf->entity);
}

if ($action == 'setvalue' && $user->admin) {
    $db->begin();
    $subject = "ITSUBJECT_COMMERCIAL_" . $l;
    $result = dolibarr_set_const($db, $subject, GETPOST($subject, 'alpha'), 'chaine', 0, '', $conf->entity);
    if (!$result > 0)
        $error++;
    $content = "ITCONTENT_COMMERCIAL_" . $l;
    $result = dolibarr_set_const($db, $content, GETPOST($content, 'alpha'), 'chaine', 0, '', $conf->entity);
    if (!$result > 0)
        $error++;
    for ($i = 0; $i < 4; $i++) {
        $reminder = "REMINDER" . $i . "_" . $l;
        $result = dolibarr_set_const($db, $reminder, GETPOST($reminder, 'none'), 'chaine', 0, '', $conf->entity);
        if (!$result > 0)
            $error++;
        $subject = "ITSUBJECT" . $i . "_" . $l;
        $result = dolibarr_set_const($db, $subject, GETPOST($subject, 'none'), 'chaine', 0, '', $conf->entity);
        if (!$result > 0)
            $error++;
        $content = "ITCONTENT" . $i . "_" . $l;
        $result = dolibarr_set_const($db, $content, GETPOST($content, 'none'), 'chaine', 0, '', $conf->entity);
        if (!$result > 0)
            $error++;
        $tracking = "ITREMINDDATE" . $i;
        $result = dolibarr_set_const($db, $tracking, GETPOST($tracking, 'none'), 'chaine', 0, '', $conf->entity);
        if (!$result > 0)
            $error++;
        $adding = "ADDING" . $i;
        $padding = preg_replace('#,#', '.', GETPOST($adding));
        $result = dolibarr_set_const($db, $adding, $padding, 'int', 0, '', $conf->entity);
        if (!$result > 0)
            $error++;
        $addings = "select_ADDING" . $i;
        $result = dolibarr_set_const($db, $addings, GETPOST($addings), 'int', 0, '', $conf->entity);
        if (!$result > 0)
            $error++;
        if (!$block && empty($conf->blockedlog->enabled)) {
            if (!$result > 0)
                $error++;
            $pa = "prod_ADDING" . $i;
            $result = dolibarr_set_const($db, $pa, GETPOST($pa), 'int', 0, '', $conf->entity);
            if (!$result > 0)
                $error++;
        }
    }


    $result = dolibarr_set_const($db, "LIMITSELECT", GETPOST('LIMITSELECT'), 'int', 0, '', $conf->entity);
    $result = dolibarr_set_const($db, "ITREMINDDATE", GETPOST('ITREMINDDATE'), 'chaine', 0, '', $conf->entity);
    $result = dolibarr_set_const($db, "FEESSUP", GETPOST('FEESSUP'), 'chaine', 0, '', $conf->entity);
    $result = dolibarr_set_const($db, "INVOICETEST", GETPOST('INVOICETEST'), 'chaine', 0, '', $conf->entity);
    $result = dolibarr_set_const($db, "addingprod", GETPOST('addingprod'), 'chaine', 0, '', $conf->entity);
    $result = dolibarr_set_const($db, "ITSENDERMAIL", GETPOST('ITSENDERMAIL'), 'chaine', 0, '', $conf->entity);

    if (!$result > 0)
        $error++;


    if (!$error) {
        $db->commit();
        setEventMessage($langs->trans("SetupSaved"));
    } else {
        $db->rollback();
        dol_print_error($db);
    }
}


/*
 * 	View
 */

$form = new Form($db);

llxHeader('', $langs->trans("InvoiceTrackingSetup"));


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre(' - ' . $langs->trans("ModuleSetup"), $linkback);
print '<br>';

print "<script type='text/javascript'>
        $(document).ready(function(){
         $('#default_lang').change(function(){
         lang=$('#default_lang').val();
                    window.location.replace('" . $_SERVER['PHP_SELF'] . "?l='+lang);    
                    });
        });
</script>";

if ($conf->global->MAIN_MULTILANGS) {
    print '<br /><strong>' . $langs->trans("INVOICETRACKING_LANGUAGE") . '</strong></td><td colspan="3" class="maxwidthonsmartphone">' . "\n";
    print $formadmin->select_language((GETPOST('l') ? GETPOST('l') : $langs->defaultlang), 'default_lang', 0, 0, 1, 0, 0, 'maxwidth200onsmartphone');
    print '<br /><br /><br />';
}

print $langs->trans("invoicetrackingdesc") . "<br>\n";

print '<br>';
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?l=' . $l . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvalue">';


print '<table class="noborder" width="100%">';

$var = true;
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("AccountParameter") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print "</tr>\n";

for ($i = 0; $i < 4; $i++) {
    $var = !$var;
    $auto = "AUTOTRACKING" . $i;
    showConf($auto, $langs->trans($auto), '', $bc[$var]);
}

$var = !$var;
showConf("savereminder", $langs->trans("savereminder"), '', $bc[$var]);

showConf("INVOICETRACKING_COMMERCIAL_CC", $langs->trans("INVOICETRACKING_COMMERCIAL_CC"), '', $bc[$var]);

for ($i = 1; $i < 4; $i++) {
    $adding = "ADDING" . $i;
    $addings = "select_ADDING" . $i;
    $addinginv = "prod_ADDING" . $i;
    print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
    print $langs->trans($adding) . '</td><td>';
    print '<input type="text" name="' . $adding . '" value="' . $conf->global->$adding . '" style="margin-right:3%"/>';
    if ($conf->global->$addings == '1') {
        $select1 = "selected";
        $select2 = "";
    } else {
        $select1 = "";
        $select2 = "selected";
    }

    if ($conf->global->$addinginv == '1') {
        $select3 = "selected";
        $select4 = "";
    } else {
        $select3 = "";
        $select4 = "selected";
    }

    print '<select name="' . $addings . '" /><option value="1" ' . $select1 . '>' . $langs->trans("PERCENTAGE") . '</option><option value="2" ' . $select2 . '>' . $langs->trans("NUMERIC") . '</option></select>';
    if (!$block && empty($conf->blockedlog->enabled)) {
        print '&nbsp;&nbsp;' . $langs->trans("AddFeesOnInvoice") . '<select name="' . $addinginv . '" /><option value="1" ' . $select3 . '>' . $langs->trans("yes") . '</option><option value="2" ' . $select4 . '>' . $langs->trans("no") . '</option></select>';
    }

    print '</td></tr>';
}

if (!$block && empty($conf->blockedlog->enabled)) {
    print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
    print $langs->trans('productToAddForFees') . '</td><td>';
    print '<input type="text" name="addingprod" value="' . $conf->global->addingprod . '" size="10" />';
    print '</td></tr>';
}

for ($i = 0; $i < 4; $i++) {
    $remindert = "REMINDER" . $i;
    $reminder = "REMINDER" . $i . "_" . $l;
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
    print $langs->trans($remindert) . '</td><td>';
    $doleditor = new DolEditor($reminder, $conf->global->$reminder, '', 250, 'Full', '', false, true, 1, 20, 130);
    $doleditor->Create();
    print '</td></tr>';

    $subjectt = "ITSUBJECT" . $i;
    $subject = "ITSUBJECT" . $i . "_" . $l;
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
    print $langs->trans($subjectt) . '</td><td>';
    print '<input type="text" name="' . $subject . '" value="' . $conf->global->$subject . '" size="150" />';
    print '</td></tr>';

    $contentt = "ITCONTENT" . $i;
    $content = "ITCONTENT" . $i . "_" . $l;
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
    print $langs->trans($contentt) . '</td><td>';
    $doleditor = new DolEditor($content, $conf->global->$content, '', 250, 'Full', '', false, true, 1, 20, 130);
    $doleditor->Create();
    print '</td></tr>';

    $trackingt = "ITREMINDDATE" . $i;
    $tracking = "ITREMINDDATE" . $i;
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
    print $langs->trans($trackingt) . '</td><td><input type="text" name="' . $tracking . '" value="' . $conf->global->$tracking . '" />' . $langs->trans('Exampledays') . '';
    print '</td></tr>';
}

$var = !$var;
print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
print $langs->trans("ITREMINDDATE") . '</td><td><input type="text" name="ITREMINDDATE" value="' . $conf->global->ITREMINDDATE . '" />';
print '</td></tr>';

$var = !$var;
print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
print $langs->trans("LIMITSELECT") . '</td><td><input type="text" name="LIMITSELECT" value="' . $conf->global->LIMITSELECT . '" />';
print '</td></tr>';

/* $var = !$var;
  print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
  print $langs->trans("FEESSUP") . '</td><td><input type="text" name="FEESSUP" value="' . $conf->global->FEESSUP . '" />';
  print '</td></tr>'; */

$var = !$var;
print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
print $langs->trans("INVOICETEST") . '</td><td><input type="text" name="INVOICETEST" value="' . $conf->global->INVOICETEST . '" />' . $langs->trans("INVOICETESTURL", dol_buildpath('/invoicetracking/auto.php?test=1&idf=' . $conf->global->INVOICETEST, 1));
print '</td></tr>';

$var = !$var;
print '<tr ' . $bc[$var] . '><td class="fieldrequired">';
print $langs->trans("ITSENDERMAIL") . '</td><td><input type="text" name="ITSENDERMAIL" value="' . $conf->global->ITSENDERMAIL . '" />';
print '</td></tr>';

print '</table>';

print '<br><center><input type="submit" class="button" value="' . $langs->trans("Modify") . '"></center>';

print '</form>';

dol_fiche_end();

print '<br><br>';

llxFooter();
$db->close();

function showConf($const, $texte_nom, $texte_descr, $var)
{
    global $conf, $langs;

    print '<tr ' . $var . '>' . "\n";
    print '<td>' . $texte_nom . '</td>' . "\n";

    if ($conf->global->$const == '1') {
        echo '<td align="center">' . "\n";
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del&name=' . $const . '">';
        echo img_picto($langs->trans("Activated"), 'switch_on');
        echo "</td>\n";
    } else {
        $disabled = false;

        print '<td align="center">';
        if (!$disabled)
            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set&name=' . $const . '">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        if (!$disabled)
            print '</a>';
        print '</td>';
    }
}
