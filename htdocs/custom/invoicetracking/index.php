<?php

/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016 Nicolas ZABOURI <info@inovea-conseil.com>
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
 *    \file       dev/Crms/Crm_page.php
 *        \ingroup    mymodule othermodule1 othermodule2
 *        \brief      This file is an example of a php page
 *                    Initialy built by build_class_from_table on 2015-03-25 15:28
 */
//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)
//ini_set("display_errors", 1);
// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include '../main.inc.php';                    // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php")) $res = @include '../../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res = @include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res = @include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (!$res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
dol_include_once('/invoicetracking/class/invoicetracking.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

if (empty($user->rights->invoicetracking->seeall)) {
    if (function_exists("llxHeader")) llxHeader('');
    elseif (function_exists("llxHeaderVierge")) llxHeaderVierge('');
    print '<div class="error">';
    print $langs->trans("ErrorForbidden");
    print '</div>';
    print '<br>';
    if (function_exists("llxFooter")) llxFooter();
    exit();
}

$langs->load('facture');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("invoicetracking@invoicetracking");
// Get parameters
$id = GETPOST('id', 'int');
$action = "list";
if (GETPOST("action")) {
    $action = GETPOST('action', 'alpha');
    if ($action == "builddoc") {
        $action = "list";
    }
}

$myparam = GETPOST('myparam', 'alpha');
$search_ref = GETPOST('search_ref') ? GETPOST('search_ref', 'int') : GETPOST('search_ref', 'int');
//$search_customer=GETPOST('search_customer','alpha');
//
$search_reffact = GETPOST('search_reffact', 'alpha');
$search_note = GETPOST('search_note', 'alpha');
$search_statut = GETPOST('search_statut', 'alpha');
//$search_date=GETPOST('search_date','int');
//$search_dater=GETPOST('search_dater','int');
//$search_user=GETPOST('search_user','int');

$page = GETPOST("page", 'int');
if ($page <= 0) {
    $page = 0;
}     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
/*
 if (! $sortorder) $sortorder='DESC';
  if (! $sortfield) $sortfield='t.rowid';
*/

$limit = $conf->liste_limit;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!empty($conf->global->LIMITSELECT)) {
    $limitselect = $conf->global->LIMITSELECT;
} else {
    $limitselect = 50;
}


// Protection if external user
if ($user->societe_id > 0) {
    //accessforbidden();
}

// Remove file
if ($action == 'remove_file') {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    $langs->load("other");
    $upload_dir = $conf->facture->dir_output . '/unpaid/temp';
    $file = $upload_dir . '/' . GETPOST('file');
    $ret = dol_delete_file($file);
    if ($ret)
        setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
    else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
    $action = 'list';
    //header('Location: '.DOL_URL_ROOT.'/invoicetracking/index.php?action=list');
}

/* * *****************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 * ****************************************************************** */
if ($action == 'add') {
    if ($_POST['addconfirm']) {
        $object = new invoicetracking($db);
        $object->fk_facture = GETPOST("fk_facture");
        if (GETPOST("toSendemail"))
            $object->note = $langs->trans("sendbyemail") . ":" . GETPOST("note");
        else $object->note = GETPOST("note");
        $object->stage = GETPOST("stage");
        $object->date_n = dol_mktime(12, 0, 0, GETPOST('date_nmonth'), GETPOST('date_nday'), GETPOST('date_nyear'));
        $r = $object->verify();
        if ($r == 0) {
            $object->fk_user_modif = $user->id;
            $result = $object->create($user);
            if (!GETPOST('toSendemail')) {
                $_POST['toSendmail'] = array('0' => GETPOST("fk_facture"));
                $_POST['stage_' . $_POST['toSendmail'][0]] = GETPOST("stage");
                $object->itsendmassmail(1);
            }

            if (GETPOST("toSendemail")) {
                $_POST['toSendemail'] = array('0' => GETPOST("fk_facture"));
                $_POST['stage_' . $_POST['toSendemail'][0]] = GETPOST("stage");
                $object->itsendmassemail(1);
            }

            if ($result > 0) {
                if ((int) DOL_VERSION >= 7) {
                    header('Location: ' . dol_buildpath('/invoicetracking/index.php?action=list&mainmenu=billing', 1));
                } else {
                    header('Location: ' . dol_buildpath('/invoicetracking/index.php?action=list&mainmenu=accountancy', 1));
                }
            }
            {
                // Creation KO
                $mesg = $object->error;
            }
        } else {
            $mesg = "ErrorFieldRequired";
        }
    }
}

if ($action == 'edit') {
    if ($_POST['editconfirm']) {
        $object = new invoicetracking($db);
        $object->fk_facture = GETPOST("fk_facture");
        $object->note = GETPOST("note");
        $object->stage = GETPOST("stage");
        $object->date_n = dol_mktime(12, 0, 0, GETPOST('date_nmonth'), GETPOST('date_nday'), GETPOST('date_nyear'));
        $r = $object->verify();
        if ($r == 0) {
            $object->id = GETPOST('id');
            $object->fk_user_modif = $user->id;
            //echo "<pre>".print_r($object,1)."</pre>";
            $result = $object->update($user);
            if ($result > 0) {
                if ((int) DOL_VERSION >= 7) {
                    header('Location: ' . dol_buildpath('/invoicetracking/index.php?action=list&mainmenu=billing', 1));
                } else {
                    header('Location: ' . dol_buildpath('/invoicetracking/index.php?action=list&mainmenu=accountancy', 1));
                }
            }
            {
                // Creation KO
                $mesg = $object->error;
            }
        }
    }
}


/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */

llxHeader('', $langs->trans('Invoicetracking'), '');


$form = new Form($db);


// Put here content of your page
// Example 1 : Adding jquery code
print '<script type="text/javascript" language="javascript">
$( document ).ready(function() {
        $("#fk_facture").change(function(){
        var idfact = $("#fk_facture").val();
        $.get("ajax/check_mail.php", {
                                    id: idfact,
                                }).done(function(data){
                                   $("#mailcheck").html(data);
                                });
        });
        $(".buttongen").hide();
    });
</script>';


// Example 2 : Adding links to objects
// The class must extends CommonObject class to have this method available
//$somethingshown=$object->showLinkedObjectBlock();
// Example 3 : List of data
if ($action == 'list') {
    $sql = "SELECT";
    $sql .= " t.rowid,";
    $sql .= " t.fk_facture,";
    $sql .= " t.note,";
    $sql .= " t.date_r,";
    $sql .= " t.date_n,";
    $sql .= " t.stage,";
    $sql .= " t.fk_user_modif";
    $sql .= " FROM " . MAIN_DB_PREFIX . "invoicetracking as t";
    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture as p ON p.rowid = t.fk_facture ";
    if (!$user->rights->societe->client->voir)
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=p.fk_soc";
    $sql .= " WHERE (p.fk_statut=1 OR p.fk_statut=0) ";

    if (!$user->rights->societe->client->voir)
        $sql .= " AND sc.fk_user = " . $user->id;
    if ($search_ref)
        $sql .= natural_search('t.rowid', $search_ref);
    if ($search_customer)
        $sql .= natural_search('t.nom', $search_customer);
    //if ($search_reffact) $sql.= natural_search('t.fk_facture', $search_reffact);
    if ($search_note)
        $sql .= natural_search('t.note', '%' . $search_note . '%');
    //if ($search_date) $sql.= natural_search('t.date_r', $search_date);
    //if ($search_dater) $sql.= natural_search('t.date_n', $search_dater);
    //if ($search_user) $sql.= natural_search('t.fk_user_modif', $search_user);

    if ($search_statut != '' && $search_statut == 0) {
        $sql .= " AND (t.stage LIKE '%0%')";
    } else if ($search_statut > 0) {
        $sql .= natural_search('t.stage', $search_statut);
    }

    if (GETPOST('sortfield') && GETPOST('sortorder')) {
        $sql .= " ORDER BY " . GETPOST('sortfield') . " " . GETPOST('sortorder');
    } else {
        $sql .= " ORDER BY rowid DESC ";
    }

    dol_syslog($script_file . " sql=" . $sql, LOG_DEBUG);
    $resql = $db->query($sql);
    //echo $sql;

    if ($resql) {
        $num2 = $db->num_rows($resql);
    }

    $search = array();
    $search['ref'] = $search_ref;
    $search['reffact'] = $search_reffact;
    $search['note'] = $search_note;
    $search['statut'] = $search_statut;

    foreach ($search as $key => $val) {
        $param .= '&search_' . $key . '=' . urlencode($search[$key]);
    }

    print_barre_liste($langs->trans('InvoiceTracking'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $limit + 1, $num2);


    print '</div>';
    $sql = "SELECT";
    $sql .= " t.rowid,";
    $sql .= " t.fk_facture,";
    $sql .= " t.note,";
    $sql .= " t.date_r,";
    $sql .= " t.date_n,";
    $sql .= " t.stage,";
    $sql .= " t.fk_user_modif";
    $sql .= " FROM " . MAIN_DB_PREFIX . "invoicetracking as t";
    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture as p ON p.rowid = t.fk_facture ";
    if (!$user->rights->societe->client->voir)
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=p.fk_soc";
    $sql .= " WHERE (p.fk_statut=1 OR p.fk_statut=0) AND p.type != 2 ";
    if (!$user->rights->societe->client->voir)
        $sql .= " AND sc.fk_user = " . $user->id;
    if ($search_ref)
        $sql .= natural_search('t.rowid', $search_ref);
    if ($search_customer)
        $sql .= natural_search('t.nom', $search_customer);
    if ($search_reffact) $sql .= natural_search('t.fk_facture', $search_reffact);
    if ($search_note)
        $sql .= natural_search('t.note', '%' . $search_note . '%');
    //if ($search_date) $sql.= natural_search('t.date_r', $search_date);
    //if ($search_dater) $sql.= natural_search('t.date_n', $search_dater);
    //if ($search_user) $sql.= natural_search('t.fk_user_modif', $search_user);

    if ($search_statut != '' && $search_statut == 0) {
        $sql .= " AND (t.stage LIKE '%0%')";
    } else if ($search_statut > 0) {
        $sql .= natural_search('t.stage', $search_statut);
    }

    if (GETPOST('sortfield') && GETPOST('sortorder')) {
        $sql .= " ORDER BY " . GETPOST('sortfield') . " " . GETPOST('sortorder');
    } else {
        $sql .= " ORDER BY rowid DESC ";
    }

    $sql .= " LIMIT " . $limit . " ";
    $sql .= " OFFSET " . $offset;

    $version = (int) DOL_VERSION;

    dol_syslog($script_file . " sql=" . $sql, LOG_DEBUG);
    $resql = $db->query($sql);

    print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '?action=list">' . "\n";
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<table class="noborder">' . "\n";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Id'), $_SERVER['PHP_SELF'], 't.rowid', '', "action=list", '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Thirdparty'));
    print_liste_field_titre($langs->trans('Invoice'), $_SERVER['PHP_SELF'], 't.fk_facture', '', "action=list", '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Note'), $_SERVER['PHP_SELF'], 't.note', '', "action=list", '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Level'), $_SERVER['PHP_SELF'], 't.stage', '', "action=list", '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Date'), $_SERVER['PHP_SELF'], 't.date_r', '', "action=list", '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('DateNext'), $_SERVER['PHP_SELF'], 't.date_n', '', "action=list", '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('User'), $_SERVER['PHP_SELF'], 't.fk_user_modif', '', "action=list", '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('File'));
    print '</tr>';

    // Filters lines
    print '<tr class="liste_titre">';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" size="6" type="text" name="search_ref" value="' . $search_ref . '">';
    print '</td>';
    print '<td class="liste_titre">';
    //print '<input class="flat" size="10" type="text" name="search_customer" value="'.$search_customer.'">';
    print '</td>';
    print '<td class="liste_titre">';
    //print '<input class="flat" size="12" type="text" name="search_reffact" value="'.$search_reffact.'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_note" value="' . $search_note . '">';
    print '</td>';
    print '<td class="liste_titre">';
    $liststatut = array('0' => $langs->trans("ITBeforereminder"), '1' => $langs->trans("ITSimplereminder"), '2' => $langs->trans("ITStamplereminder"), '3' => $langs->trans("ITLastreminder"));
    print $form->selectarray('search_statut', $liststatut, $search_statut, 1);
    print '</td>';
    //print '<td class="liste_titre">';
    //print '<input class="flat" size="6" type="text" name="search_statut" value="' . $search_statut . '">';
    //print '</td>';
    print '<td class="liste_titre">';
    //print '<input class="flat" size="6" type="text" name="search_date" value="'.$search_date.'">';
    print '</td>';
    print '<td class="liste_titre">';
    //print '<input class="flat" size="6" type="text" name="search_dater" value="'.$search_dater.'">';
    print '</td>';
    print '<td class="liste_titre">';
    //print '<input class="flat" size="6" type="text" name="search_user" value="'.$search_user.'">';
    print '</td>';
    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
    print "</td></tr>\n";
    print "</form>\n";

    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    $p = new Facture($db);
                    $p->fetch($obj->fk_facture);
                    //echo "<pre>".print_r($p,1)."</pre>";
                    $s = new Societe($db);
                    $s->fetch($p->socid);

                    $u = new User($db);
                    $u->fetch($obj->fk_user_modif);
                    $stagename = $langs->trans(Invoicetracking::selectReminder($obj->stage));

                    // You can use here results
                    print '<tr><td>';

                    print '<a href="' . dol_buildpath('/invoicetracking/index.php?action=edit&id=' . $obj->rowid, 1) . '">' . $obj->rowid . '</a>';
                    print '</td><td>';
                    if ($version >= 6) {
                        print '<a href="' . DOL_URL_ROOT . '/societe/card.php?socid=' . $p->socid . '">' . img_object($langs->trans("ShowCompany"), "company") . $s->nom . '</a>';
                    } else {
                        print '<a href="' . DOL_URL_ROOT . '/societe/soc.php?socid=' . $p->socid . '">' . img_object($langs->trans("ShowCompany"), "company") . $s->nom . '</a>';
                    }

                    print '</td><td>';
                    if ($version >= 6) {
                        print '<a href="' . DOL_URL_ROOT . '/compta/facture/card.php?id=' . $obj->fk_facture . '">' . img_object($langs->trans("ShowInvoice"), "bill") . $p->ref . '</a>';
                    } else {
                        print '<a href="' . DOL_URL_ROOT . '/compta/facture.php?facid=' . $obj->fk_facture . '">' . img_object($langs->trans("ShowInvoice"), "bill") . $p->ref . '</a>';
                    }

                    print '</td><td>';
                    print $obj->note;
                    print '</td><td>';
                    print $stagename;
                    print '</td><td>';
                    print dol_print_date($db->jdate($obj->date_r), 'day');
                    print '</td><td>';
                    print dol_print_date($db->jdate($obj->date_n), 'day');
                    print '</td><td>';
                    print img_object($langs->trans("ShowUser"), "user") . $u->login;
                    print '</td><td>';
                    if (preg_match('/(email|e-mail)/i', $obj->note)) {
                        print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=invoice&file=/' . preg_replace('#\/#', '_', $p->ref) . '/' . preg_replace('#\/#', '_', $p->ref) . '.pdf"><img src="' . DOL_URL_ROOT . '/theme/eldy/img/pdf2.png" /></a>';
                    } else {
                        print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=invoice&file=/temp/massgeneration/' . preg_replace('#\/#', '_', $p->ref) . '.pdf"><img src="' . DOL_URL_ROOT . '/theme/eldy/img/pdf2.png" /></a>';
                    }
                    print '</td></tr>';
                }

                $i++;
            }
        }
    } else {
        $error++;
        dol_print_error($db);
    }

    print '</table>' . "\n";
    print '<div class="tabsAction">
        <div class="inline-block divButAction">
            <a href="' . dol_buildpath('/invoicetracking/index.php?action=add&mainmenu=billing', 1) . '" class="butAction">' . $langs->trans('Add') . '</a>
                
        </div>
        </div>';
}


if ($action == "add") {
    $var = true;
    $crm = new invoicetracking($db);
    if (GETPOST('idsoc'))
        $soc = GETPOST('idsoc');
    else $soc = 0;
    $otherprop = $crm->liste_array_facture(2, 1, 0, $soc, $limitselect);
    //echo "<pre>".print_r($otherprop,1)."</pre>";
    $stages = array(0 => $langs->trans("ITBeforereminder"), 1 => $langs->trans("ITSimplereminder"), 2 => $langs->trans("ITStamplereminder"), 3 => $langs->trans("ITLastreminder"));
    if (is_array($otherprop) && count($otherprop)) {
        $var = !$var;
        $html = '<form action="' . $_SERVER["PHP_SELF"] . '" name="formsoc" method="post">';
        $html .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        $html .= '<input type="hidden" name="action" value="add">';
        $html .= '<table class="border" width="100%">';
        $html .= '<tr><td style="width: 200px;"><span class="fieldrequired">';
        $html .= $langs->trans("Invoice") . '</span></td><td colspan="2">';
        $html .= $form->selectarray("fk_facture", $otherprop, GETPOST("facid"), 1);
        $html .= '</td></tr><tr><td style="width: 200px;">';
        $html .= $langs->trans("Note") . '</td><td>';
        $html .= '<textarea name="note" cols="100" rows="10">' . GETPOST("note") . '</textarea>';
        $html .= '</td></tr><tr><td style="width: 200px;"><span class="fieldrequired">';
        $html .= $langs->trans("Level") . '</span></td><td colspan="2">';
        $html .= $form->selectarray("stage", $stages, GETPOST("stage"), 1);
        $html .= '</td></tr><tr><td style="width: 200px;"><span class="fieldrequired">';
        $html .= $langs->trans("DateNext") . '</span></td><td>';
        $html .= $form->select_date(date('Y-m-d', strtotime($conf->global->ITREMINDDATE)), 'date_n', '', '', '', 1, 1, 0, 1);
        $html .= '</td></tr><tr><td style="width: 200px;"><span>';
        $html .= $langs->trans("Sendemail") . '</span></td><td id="mailcheck">';
        $html .= '<input type="checkbox" name="toSendemail" value="1" ></input>';
        $html .= '</td></tr></table>';
        $html .= '<div class="center"><input type="hidden" value="1" name="addconfirm">';
        $html .= '<input type="submit" class="button" value="' . $langs->trans("Add") . '"></div>';
    } else {
        $html .= $langs->trans("NoUnpaidInvoice");
    }

    // Label
    print $html;
}

if ($action == "edit") {
    $var = true;
    $crm = new invoicetracking($db);
    $crm->fetch(GETPOST('id'));
    $otherprop = $crm->liste_array_facture(2, 1, 0, 0, $limitselect);
    $stages = array(0 => $langs->trans("ITBeforereminder"), 1 => $langs->trans("ITSimplereminder"), 2 => $langs->trans("ITStamplereminder"), 3 => $langs->trans("ITLastreminder"));

    if (is_array($otherprop) && count($otherprop)) {
        $var = !$var;
        $html = '<form action="' . $_SERVER["PHP_SELF"] . '" name="formsoc" method="post">';
        $html .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        $html .= '<input type="hidden" name="action" value="edit">';
        $html .= '<input type="hidden" name="id" value="' . GETPOST('id') . '">';
        $html .= '<table class="border" width="100%">';
        $html .= '<tr><td style="width: 200px;"><span class="fieldrequired">';
        $html .= $langs->trans("Invoice") . '</span></td><td colspan="2">';
        $html .= $form->selectarray("fk_facture", $otherprop, $crm->fk_facture, 1);
        $html .= '</td></tr><tr><td style="width: 200px;"><span class="fieldrequired">';
        $html .= $langs->trans("Note") . '</span></td><td>';
        $html .= '<textarea name="note" cols="100" rows="10">' . $crm->note . '</textarea>';
        $html .= '</td></tr><tr><td style="width: 200px;"><span class="fieldrequired">';
        $html .= $langs->trans("Level") . '</span></td><td colspan="2">';
        $html .= $form->selectarray("stage", $stages, $crm->stage, 1);
        $html .= '</td></tr><tr><td style="width: 200px;"><span class="fieldrequired">';
        $html .= $langs->trans("DateNext") . '</span></td><td>';
        $html .= $form->select_date($crm->date_n, 'date_n', '', '', '', 1, 1, 0, 1);
        $html .= '</td></tr><tr><td style="width: 200px;"><span>';
        $html .= $langs->trans("Send email") . '</span></td><td id="mailcheck">';
        $html .= '<input type="checkbox" name="toSendemail" value="1"></input>';
        $html .= '</td></tr></table>';
        $html .= '<div class="center"><input type="hidden" value="1" name="editconfirm">';
        $html .= '<input type="submit" class="button" value="' . $langs->trans("Edit") . '"></div>';
    } else {
        $html .= $langs->trans("NoUnpaidInvoice");
    }

    // Label
    print $html;
}//echo "<pre>".print_r($crm,1)."</pre>";
dol_htmloutput_errors($object->errors[0], $errors);

// End of page
llxFooter();
$db->close();
