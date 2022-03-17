<?php

/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016-2018      Nicolas ZABOURI      <info@inovea-conseil.com>
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
 *
 * Note: Page can be call with param mode=sendremind to bring feature to send
 * remind by emails.
 */

/**
 *        \file       htdocs/compta/facture/mergepdftool.php
 *        \ingroup    facture
 *        \brief      Page to list and build doc of selected invoices
 */
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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

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

// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
if (!empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
dol_include_once('/invoicetracking/class/invoicetracking.class.php');
global $conf, $langs;
$langs->load("mails");
$langs->load("bills");

$id = (GETPOST('facid', 'int') ? GETPOST('facid', 'int') : GETPOST('id', 'int'));
$action = GETPOST('action', 'alpha');
$option = GETPOST('option');
$mode = "sendmassremind";
$builddoc_generatebutton = GETPOST('builddoc_generatebutton');
$month = GETPOST("month", "int");
$year = GETPOST("year", "int");
$filter = GETPOST("filtre");
if (GETPOST('button_search')) {
    $filter = GETPOST('filtre', 2);
    //if ($filter != 'payed:0') $option='';
}

if ($option == 'late')
    $filter = 'f.date_lim_reglement < NOW()';
if ($option == 'unpaidall')
    $filter = 'paye:0';
if ($mode == 'sendmassremind' && $filter == '')
    $filter = 'paye:0';
if ($filter == '')
    $filter = 'paye:0';

$search_user = GETPOST('search_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_date_lim_reglement = GETPOST('search_date_lim_reglement');
if (!empty($search_date_lim_reglement))
    $search_date_lim_reglement3 = mktime(0, 0, 0, GETPOST('search_date_lim_reglementmonth'), GETPOST('search_date_lim_reglementday'), GETPOST('search_date_lim_reglementyear'));
// Security check
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'facture', $id, '');

//$diroutputpdf = $conf->facture->dir_output . '/unpaid/temp';
$diroutputpdf = $conf->facture->dir_output . '/temp/massgeneration/' . $user->id . '/';

$resultmasssend = '';

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {  // Both test must be present to be compatible with all browsers
    $search_ref = "";
    $search_date_lim_reglement = "";
    $search_ref_supplier = "";
    $search_user = "";
    $search_sale = "";
    $search_label = "";
    $search_company = "";
    $search_amount_no_tax = "";
    $search_amount_all_tax = "";
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
    $search_proj_ref = '';
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
    $year = "";
    $month = "";
    $filter = "";
    $option = "";
}


/*
 * Action
 */
$fileok = array();
if ($action == 'presend' && GETPOST('sendmail')) {
    $remind = new Invoicetracking($db);
    $remind->itsendmassemail();
    $fileok = $remind->itsendmassmail();
}


// Remove file
if ($action == 'remove_file') {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    $langs->load("other");
    $upload_dir = $diroutputpdf;
    $file = $upload_dir . '/' . GETPOST('file');
    $ret = dol_delete_file($file);
    if ($ret)
        setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
    else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
    $action = '';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);

$title = $langs->trans("Globalreminder");

llxHeader('', $title);
?>
    <script type="text/javascript">
        $(document).ready(function () {
            $("#checkall").click(function () {
                $(".checkformerge").prop('checked', true);
            });
            $("#checknone").click(function () {
                $(".checkformerge").prop('checked', false);
            });
            $("#checkallemail").click(function () {
                $(".checkformergeemail").prop('checked', true);
            });
            $("#checknoneemail").click(function () {
                $(".checkformergeemail").prop('checked', false);
            });
            $("#checkallsend").click(function () {
                $(".checkforsend").prop('checked', true);
            });
            $("#checknonesend").click(function () {
                $(".checkforsend").prop('checked', false);
            });
            $("#checkallsendemail").click(function () {
                $(".checkforsendemail").prop('checked', true);
            });
            $("#checknonesendemail").click(function () {
                $(".checkforsendemail").prop('checked', false);
            });
        });
    </script>
<?php

$now = dol_now();

$search_ref = GETPOST("search_ref");
$search_refcustomer = GETPOST('search_refcustomer');
$search_societe = GETPOST("search_societe");
//$search_paymentmode = GETPOST("search_paymentmode");
//$search_montant_ht = GETPOST("search_montant_ht");
$search_montant_ttc = GETPOST("search_montant_ttc");
$search_status = GETPOST("search_status");
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
$search_proj_ref = GETPOST('search_proj_ref');
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
//Add filter to show all bill's wich date has passed --
$search_date_passed = GETPOST('search_date_passed');
//
$late = "paye:0";

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) { // Both test are required to be compatible with all browsers
    $search_ref = '';
    $search_refcustomer = '';
    $search_societe = '';
    $search_paymentmode = '';
    $search_montant_ht = '';
    $search_montant_ttc = '';
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
    $search_proj_ref = '';
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
    // $search_status='';
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');

if ($page <= 0) {
    $page = 0;
}

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield)
    if (!empty($conf->global->LIMITSELECT))
        $sortfield = "date_r";
else $sortfield = "f.date_lim_reglement";
if (!$sortorder)
    $sortorder = "DESC";
if ($search_date_lim_reglement) {
    $sortfield = "f.date_lim_reglement";
    $sortorder = "ASC";
}

$limit = $conf->liste_limit;
/*
  $sql = "SELECT s.nom as name, s.rowid as socid, s.email";
  $sql.= ", f.rowid as facid, f.facnumber, f.ref_client, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.localtax1, f.localtax2, f.revenuestamp";
  $sql.= ", f.datef as df, f.date_lim_reglement as datelimite";
  $sql.= ", f.paye as paye, f.fk_statut, f.type, f.fk_mode_reglement";
  $sql.= ", avg(pf.amount) as am";
  $sql.= ", it.note,it.date_n as relance,it.stage as stage,it.date_r as date_r, it.rowid";
  $sql.= " FROM " . MAIN_DB_PREFIX . "facture as f";
  $sql.= " INNER JOIN " . MAIN_DB_PREFIX . "societe as s ON (f.fk_soc=s.rowid)";
  $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "element_contact as ec ON (f.rowid=ec.element_id)";
  $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "paiement_facture as pf ON f.rowid=pf.fk_facture ";
  //$sql.= " LEFT JOIN ". MAIN_DB_PREFIX . "c_type_contact as tc ON tc.rowid=ec.fk_c_type_contact";
  //$sql.= " LEFT JOIN ". MAIN_DB_PREFIX . "socpeople as sp ON ec.fk_socpeople=sp.rowid";
  $sql.= " LEFT JOIN (SELECT * FROM " . MAIN_DB_PREFIX . "invoicetracking  ORDER BY rowid DESC) as it ON f.rowid=it.fk_facture";
  $sql.= " WHERE f.paye = 0 AND f.date_lim_reglement < '" . $db->idate(dol_now()) . "'AND f.type IN (0,1,3,5) AND f.entity = " . $conf->entity;
  //$sql.= " AND tc.element='facture' AND tc.source='external' AND ec.element_id = f.rowid";
  $sql .= " GROUP BY f.rowid";
  $sql .= " ORDER BY ";
  $listfield = explode(',', $sortfield);
  foreach ($listfield as $key => $value)
  $sql.=$listfield[$key] . " " . $sortorder . ",";
  $sql.= " date_r DESC ";
  if(!empty($conf->global->LIMITSELECT)){
  $sql .= "LIMIT ".$conf->global->LIMITSELECT;
  }
 */

$sql = "SELECT s.nom as name, s.rowid as socid, s.email";
if ((int) DOL_VERSION >= 10) {
    if((int) DOL_VERSION >=14){
        $sql .= ", f.rowid as facid, f.ref, f.ref_client, f.increment, f.total_ht as total_ht, f.total_tva as total_tva, f.total_ttc, f.localtax1, f.localtax2, f.revenuestamp";
    }else {
        $sql .= ", f.rowid as facid, f.ref, f.ref_client, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.localtax1, f.localtax2, f.revenuestamp";
    }
    } else {
    $sql .= ", f.rowid as facid, f.facnumber, f.ref_client, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.localtax1, f.localtax2, f.revenuestamp";
}
$sql .= ", f.datef as df, f.date_lim_reglement as datelimite";
$sql .= ", f.paye as paye, f.fk_statut, f.type, f.fk_mode_reglement";
$sql .= ", avg(pf.amount) as am";
$sql .= ", it.rowid,it.note,it.date_n as relance,it.stage as stage,it.date_r as date_r";
if ($search_sale > 0 && $user->rights->societe->client->voir && !$socid)
    $sql .= ", sc.fk_soc, sc.fk_user ";
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
if (!empty($conf->projet->enabled)) $sql .= ", proj.rowid as proj_rowid, proj.ref as proj_ref";
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
if ($search_sale > 0 && $user->rights->societe->client->voir && !$socid)
    $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
$sql .= "," . MAIN_DB_PREFIX . "facture as f";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "paiement_facture as pf ON f.rowid=pf.fk_facture ";
$sql .= " LEFT JOIN (SELECT * FROM " . MAIN_DB_PREFIX . "invoicetracking  ORDER BY rowid DESC) as it ON f.rowid=it.fk_facture";
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
if (!empty($conf->projet->enabled)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as proj ON proj.rowid = f.fk_projet";
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End

// We'll need this table joined to the select in order to filter by sale

if ($search_user > 0) {
    $sql .= ", " . MAIN_DB_PREFIX . "element_contact as ec";
    $sql .= ", " . MAIN_DB_PREFIX . "c_type_contact as tc";
}

$sql .= " WHERE f.fk_soc = s.rowid";
$sql .= " AND f.entity = " . $conf->entity;
$sql .= " AND f.type IN (0,1,3,5)";
if ($filter == 'paye:0')
    $sql .= " AND f.fk_statut = 1";
//$sql.= " AND f.paye = 0";
if(!empty($search_date_passed)){
    $sql.= " AND  f.date_lim_reglement < CURRENT_DATE()";
}
if ($option == 'late')
    $sql .= " AND f.date_lim_reglement < '" . $db->idate(dol_now() - $conf->facture->client->warning_delay) . "'";
if ($search_sale > 0 && $user->rights->societe->client->voir && !$socid)
    $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
if (!empty($socid))
    $sql .= " AND s.rowid = " . $socid;
if ($filter && $filter != -1) {  // GETPOST('filtre') may be a string
    $filtrearr = explode(",", $filter);
    foreach ($filtrearr as $fil) {
        $filt = explode(":", $fil);
        $sql .= " AND " . $filt[0] . " = " . $filt[1];
    }
}


if ($search_ref)
if ((int) DOL_VERSION >= 10) {
	$sql .= " AND f.ref LIKE '%" . $db->escape($search_ref) . "%'";
} else {
	$sql .= " AND f.facnumber LIKE '%" . $db->escape($search_ref) . "%'";
}
if ($search_refcustomer)
    $sql .= " AND f.ref_client LIKE '%" . $db->escape($search_refcustomer) . "%'";
if ($search_societe)
    $sql .= " AND s.nom LIKE '%" . $db->escape($search_societe) . "%'";
if ($search_paymentmode)
    $sql .= " AND f.fk_mode_reglement = " . $search_paymentmode . "";
if ($search_montant_ht)
    $sql .= " AND f.total = '" . $db->escape($search_montant_ht) . "'";
if ($search_montant_ttc)
    $sql .= " AND f.total_ttc = '" . $db->escape($search_montant_ttc) . "'";
if ($search_date_lim_reglement)
    $sql .= " AND f.date_lim_reglement >= '" . $db->idate($search_date_lim_reglement3) . "'";

if (GETPOST('sf_ref')) {
    if ((int) DOL_VERSION >= 10) {
        $sql .= " AND f.ref LIKE '%" . $db->escape(GETPOST('sf_ref')) . "%'";
    } else {
        $sql .= " AND f.facnumber LIKE '%" . $db->escape(GETPOST('sf_ref')) . "%'";
    }
}
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
if ($search_proj_ref)
    $sql .= " AND proj.ref LIKE '%" . $db->escape($search_proj_ref) . "%'";
// Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End

if ($search_status)
    $sql .= " AND f.fk_statut = " . $search_status;
if ($month > 0) {
    if ($year > 0)
        $sql .= " AND f.datef BETWEEN '" . $db->idate(dol_get_first_day($year, $month, false)) . "' AND '" . $db->idate(dol_get_last_day($year, $month, false)) . "'";
    else $sql .= " AND date_format(f.datef, '%m') = '$month'";
} else if ($year > 0) {
    $sql .= " AND f.datef BETWEEN '" . $db->idate(dol_get_first_day($year, 1, false)) . "' AND '" . $db->idate(dol_get_last_day($year, 12, false)) . "'";
}

if ($search_sale > 0 && $user->rights->societe->client->voir && !$socid)
    $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $search_sale;
if ($search_user > 0) {
    $sql .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = " . $search_user;
}
//$sql.= " GROUP BY s.nom, s.rowid, s.email, f.rowid, f.facnumber, f.ref_client, f.increment, f.total, f.tva, f.total_ttc, f.localtax1, f.localtax2, f
//if (.revenuestamp,";
//$sql.= " f.datef, f.date_lim_reglement, f.paye, f.fk_statut, f.type, fk_mode_reglement";
$sql .= " GROUP BY f.rowid";
$sql .= " ORDER BY ";

$listfield = explode(',', $sortfield);
foreach ($listfield as $key => $value)
    $sql .= $listfield[$key] . " " . $sortorder . " ";
if (!empty($conf->global->LIMITSELECT)) {
    $sql .= "LIMIT " . $conf->global->LIMITSELECT;
}

print '<br />';

//print '<pre>' . print_r($sql, 1) . '</pre>'; //prent
//$sql .= $db->plimit($limit+1,$offset);

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);

    $sql2 = "SELECT * FROM ". MAIN_DB_PREFIX ."invoicetracking GROUP BY fk_facture, stage ORDER BY date_r, stage DESC";
    $resql2 = $db->query($sql2);

    $arrayInvoicetracking = array();
    while (($row = $db->fetch_object($resql2)) != null) {
        $arrayInvoicetracking[$row->fk_facture] = array("note" => $row->note, "date_r" => $row->date_r, "date_n" => $row->date_n , "stage" => $row->stage);
    }

    if (!empty($socid)) {
        $soc = new Societe($db);
        $soc->fetch($socid);
    }

    $param = "";
    $param .= (!empty($socid) ? "&amp;socid=" . $socid : "");
    if ($search_ref)
        $param .= '&amp;search_ref=' . urlencode($search_ref);
    if ($search_refcustomer)
        $param .= '&amp;search_ref=' . urlencode($search_refcustomer);
    if ($search_societe)
        $param .= '&amp;search_societe=' . urlencode($search_societe);
    //if ($search_societe)     $param.='&amp;search_paymentmode='.urlencode($search_paymentmode);
    if ($search_montant_ht)
        $param .= '&amp;search_montant_ht=' . urlencode($search_montant_ht);
    //if ($search_montant_ttc) $param.='&amp;search_montant_ttc='.urlencode($search_montant_ttc);
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
    if ($search_proj_ref)
        $param .= '&amp;search_proj_ref=' . urlencode($search_proj_ref);
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
    if ($search_status)
        $param .= '&amp;search_status=' . urlencode($search_status);
    if ($late)
        $param .= '&amp;late=' . urlencode($late);
    if ($mode)
        $param .= '&amp;mode=' . urlencode($mode);
    $urlsource = $_SERVER['PHP_SELF'] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder;
    $urlsource .= str_replace('&amp;', '&', $param);

    //$titre=(! empty($socid)?$langs->trans("BillsCustomersUnpaidForCompany",$soc->name):$langs->trans("BillsCustomersUnpaid"));
    $titre = (!empty($socid) ? $langs->trans("BillsCustomersForCompany", $soc->name) : $langs->trans("BillsCustomers"));
    if ($option == 'late')
        $titre .= ' (' . $langs->trans("Late") . ')';
    //else $titre.=' ('.$langs->trans("All").')';

    $link = '';
    //if (empty($option) || $option == 'sendmassremind') $link.=($link?' - ':'').'<a href="'.$_SERVER["PHP_SELF"].'?option=sendmassremind'.$param.'">'.$langs->trans("ShowUnpaidAll").'</a>';
    //if (empty($option) || $option == 'unpaidall') $link.=($link?' - ':'').'<a href="'.$_SERVER["PHP_SELF"].'?option=late'.$param.'">'.$langs->trans("ShowUnpaidLateOnly").'</a>';

    $param .= (!empty($option) ? "&amp;option=" . $option : "");

    print_fiche_titre($titre, $link);
    //print_barre_liste($titre,$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',0);   // We don't want pagination on this page

    print '<form id="form_unpaid" method="POST" action="' . $_SERVER["PHP_SELF"] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

    if (GETPOST('modelselected')) {
        $action = 'presend';
    }


    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="mode" value="' . $mode . '">';
    if ($late)
        print '<input type="hidden" name="late" value="' . dol_escape_htmltag($late) . '">';

    if ($resultmasssend) {
        print '<br><strong>' . $langs->trans("ResultOfMassSending") . ':</strong><br>' . "";
        print $langs->trans("Selected") . ': ' . $countToSend . "<br>";
        print $langs->trans("Ignored") . ': ' . $nbignored . "<br>";
        print $langs->trans("Sent") . ': ' . $nbsent . "<br>";
        //print $resultmasssend;
        print '<br>';
    }

    $i = 0;
    print '<table class="liste" width="100%">';

    // If the user can view prospects other than his'
    $moreforfilter = '';
    if ($user->rights->societe->client->voir || $socid) {
        $langs->load("commercial");
        $moreforfilter .= $langs->trans('ThirdPartiesOfSaleRepresentative') . ': ';
        $moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user);
        $moreforfilter .= ' &nbsp; &nbsp; &nbsp; ';
    }

    // If the user can view prospects other than his'
    if ($user->rights->societe->client->voir || $socid) {
        $moreforfilter .= $langs->trans('LinkedToSpecificUsers') . ': ';
        $moreforfilter .= $form->select_dolusers($search_user, 'search_user', 1);
    }

    if ($moreforfilter) {
        print '<tr class="liste_titre">';
        $colspan = 12;
        // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
        if (!empty($conf->projet->enabled)) $colspan++;
        // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
        print '<td class="liste_titre" colspan="' . $colspan . '">';
        print $moreforfilter;
        print '</td></tr>';
    }

    print '<tr class="liste_titre">';
    if ((int) DOL_VERSION >= 10) {
        print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "f.ref", "", $param, "", $sortfield, $sortorder);
    } else {
        print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "f.facnumber", "", $param, "", $sortfield, $sortorder);
    }
    print_liste_field_titre($langs->trans("ThirdParty"), $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
    if (!empty($conf->projet->enabled)) print_liste_field_titre($langs->trans('Project'), $_SERVER['PHP_SELF'], 'proj.ref', '', $param, '', $sortfield, $sortorder);
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
    //print_liste_field_titre($langs->trans('RefCustomer'),$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","",$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateDue"), $_SERVER["PHP_SELF"], "f.date_lim_reglement", "", $param, 'align="center"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("DateLastReminder"), $_SERVER["PHP_SELF"], "it.date_r", "", $param, 'align="center"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("LastLevel"), $_SERVER["PHP_SELF"], "it.stage", "", $param, 'align="center"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("Note"), $_SERVER["PHP_SELF"], "it.note", "", $param, 'align="center"', $sortfield, $sortorder);
    //print_liste_field_titre($langs->trans("PaymentMode"),$_SERVER["PHP_SELF"],"f.fk_reglement_mode","",$param,"",$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total","",$param,'align="right"',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("Taxes"),$_SERVER["PHP_SELF"],"f.tva","",$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AmountTTC"), $_SERVER["PHP_SELF"], "f.total_ttc", "", $param, 'align="right"', $sortfield, $sortorder);
    //print_liste_field_titre($langs->trans("Received"),$_SERVER["PHP_SELF"],"am","",$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Rest"), $_SERVER["PHP_SELF"], "", "", $param, 'align="right"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("DateNextReminder"), $_SERVER["PHP_SELF"], "it.date_n", "", $param, 'align="center"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("Levelsend"), $_SERVER["PHP_SELF"], "", "", $param, 'align="center"', "", "");
    print_liste_field_titre($langs->trans(""), $_SERVER["PHP_SELF"], "", "", $param, 'align="center"', "", "");

    $searchpitco = '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
    $searchpitco .= '<input type="image" class="liste_titre" name="button_removefilter" src="' . img_picto($langs->trans("Reset"), 'searchclear.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
    if (empty($mode)) {
        print_liste_field_titre($searchpitco, $_SERVER["PHP_SELF"], "", "", $param, 'align="center"', $sortfield, $sortorder);
    } else {
        print_liste_field_titre($searchpitco, $_SERVER["PHP_SELF"], "", "", $param, 'align="center"', $sortfield, $sortorder);
    }

    print "</tr>";

    // Lignes des champs de filtre
    print '<tr class="liste_titre">';
    // Ref
    print '<td class="liste_titre">';

    print '<input class="flat" size="10" type="text" name="search_ref" value="' . $search_ref . '"></td>';


    print '<td class="liste_titre" align="left"><input class="flat" type="text" size="10" name="search_societe" value="' . dol_escape_htmltag($search_societe) . '"></td>';
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
    if (!empty($conf->projet->enabled)) {
        print '<td class="liste_titre" align="center">';
        print '<input class="flat" size="10" type="text" name="search_proj_ref" value="' . $search_proj_ref . '">';
        print '</td>';
    }
    // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End
    print '<td class="liste_titre" align="center">';
    // Add an alert filter that allows to show all the bills which expiration date has passed --
    print '<input type="checkbox" name="search_date_passed"';
    empty($search_date_passed) ? print '' : print 'checked';
    print '>'.$langs->trans("DatePassed");
    // --
    print '</td>';
    print '<td class="liste_titre" align="center">';
    print '</td>';
    print '<td class="liste_titre">';
    print '</td>';
    print '<td class="liste_titre">';
    print '</td>';
    print '<td class="liste_titre" align="right"><input class="flat" type="text" size="8" name="search_montant_ttc" value="' . dol_escape_htmltag($search_montant_ttc) . '"></td>';
    print '<td class="liste_titre">';
    print '</td>';
    print '<td class="liste_titre" align="center">';
    print '</td>';
    print '<td class="liste_titre">';
    print '</td>';

    print '<td class="liste_titre" align="center">';
    if (empty($mode)) {
        if ($conf->use_javascript_ajax)
            print '<a href="#" id="checkall">' . $langs->trans("AllMail") . '</a> / <a href="#" id="checknone">' . $langs->trans("None") . '</a>';
    } else {
        if ($conf->use_javascript_ajax)
            print '<a href="#" id="checkallsend">' . $langs->trans("AllMail") . '</a> / <a href="#" id="checknonesend">' . $langs->trans("None") . '</a>';
    }

    print '<td class="liste_titre" align="center">';
    if (empty($mode)) {
        if ($conf->use_javascript_ajax)
            print '<a href="#" id="checkallemail">' . $langs->trans("AllEmail") . '</a> / <a href="#" id="checknoneemail">' . $langs->trans("None") . '</a>';
    } else {
        if ($conf->use_javascript_ajax)
            print '<a href="#" id="checkallsendemail">' . $langs->trans("AllEmail") . '</a> / <a href="#" id="checknonesendemail">' . $langs->trans("None") . '</a>';
    }

    print '</td>';
    print "</tr>";

    if ($num > 0) {
        $var = true;
        $total_ht = 0;
        $total_tva = 0;
        $total_ttc = 0;
        $total_paid = 0;

        $facturestatic = new Facture($db);
        // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
        if (!empty($conf->projet->enabled)) $projectStatic = new Project($db);
        // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End

        while ($i < $num) {
            $objp = $db->fetch_object($resql);
            $date_limit = $db->jdate($objp->relance);

            $var = !$var;

            print "<tr " . $bc[$var] . ">";
            $classname = "impayee";

            print '<td class="nowrap">';

            $facturestatic->id = $objp->facid;
            if ((int) DOL_VERSION >= 10) {
                $facturestatic->ref = $objp->ref;
            } else {
                $facturestatic->ref = $objp->facnumber;
            }
            $facturestatic->type = $objp->type;

            print '<table class="nobordernopadding"><tr class="nocellnopadd">';

            // Ref
            print '<td class="nobordernopadding nowrap">';
            print $facturestatic->getNomUrl(1);
            print '</td>';

            // PDF Picto
            print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
            if ((int) DOL_VERSION >= 10) {
                $filename = dol_sanitizeFileName($objp->ref);
                $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->ref);
            } else {
                $filename = dol_sanitizeFileName($objp->facnumber);
                $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
            }
            print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
            print '</td>';

            print '</tr></table>';

            print "</td>";

            print '<td>';
            $thirdparty = new Societe($db);
            $thirdparty->id = $objp->socid;
            $thirdparty->name = $objp->name;
            $thirdparty->client = $objp->client;
            $thirdparty->code_client = $objp->code_client;
            print $thirdparty->getNomUrl(1, 'customer');
            print '</td>';

            // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- Begin
            if (!empty($conf->projet->enabled)) {
                $projectStatic->id = $objp->proj_id;
                $projectStatic->ref = $objp->proj_ref;
                if ($projectStatic->id != null || $projectStatic->ref != null) {
                    print '<td>';
                    print $projectStatic->getNomUrl(1);
                    print '</td>';
                } else {
                    print '<td>-';
                    print '</td>';
                }
            }
            // Open-DSI -- Add order by activity - Ticket TS1909-1719 -- End

            print '<td class="nowrap" align="center">'. dol_print_date($db->jdate($objp->datelimite), 'day');
            if(strtotime($objp->datelimite) < dol_now()){ //show a warning sign if the bill's expiration date has passed
                print img_warning($langs->trans("Late"));
            }
            print '</td>' . "";
            print '<td class="nowrap" align="center">' . dol_print_date($db->jdate($arrayInvoicetracking[$objp->facid]["date_r"]), 'day');
            if ($date_limit < ($now - $conf->facture->client->warning_delay) && !$objp->paye && $objp->fk_statut == 1 && strtotime($objp->datelimite) < dol_now()) { //debug : Shows the warning sign only if the due date of the invoice has passed
                print img_warning($langs->trans("Late"));
            }
            print '</td>';
            $level = Invoicetracking::selectReminder($arrayInvoicetracking[$objp->facid]["stage"]);

            print '<td class="nowrap" align="center">' . $langs->trans($level) . '</td>' . "";
            print '<td class="nowrap" align="center">' . $arrayInvoicetracking[$objp->facid]["note"] . '</td>' . "";


            print '<td class="amount nowraponall" align="right">' . price($objp->total_ttc) . '</td>';
            $cn = $facturestatic->getSumCreditNotesUsed();
            $dep = $facturestatic->getSumDepositsUsed();

            // Remain to receive
            print '<td class="amount nowraponall" align="right">' . (((!empty($objp->total_ttc) || !empty($objp->am) || !empty($cn) || !empty($dep)) && ($objp->total_ttc - $objp->am - $cn - $dep)) ? price($objp->total_ttc - $objp->am - $cn - $dep) : '&nbsp;') . '</td>';
            print '<td class="nowrap" align="center">' . dol_print_date($db->jdate($arrayInvoicetracking[$objp->facid]["date_n"]), 'day');

            // Status of invoice
            print '<td align="right" class="nowrap">';
            //print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$objp->am);


            switch ($arrayInvoicetracking[$objp->facid]["stage"]) {
                case '':
                    $l0 = "selected";
                    $l1 = "";
                    $l2 = "";
                    $l3 = "";
                    break;
                case 0:
                    $l0 = "";
                    $l1 = "selected";
                    $l2 = "";
                    $l3 = "";
                    break;
                case 1:
                    $l0 = "";
                    $l1 = "";
                    $l2 = "selected";
                    $l3 = "";
                    break;
                case 2:
                    $l0 = "";
                    $l1 = "";
                    $l2 = "";
                    $l3 = "selected";
                    break;
                case 3:
                case 4:
                    $l0 = "";
                    $l1 = "";
                    $l2 = "";
                    $l3 = "selected";
                    break;
            }
            print '<select name="stage_' . $objp->facid . '">';
            if (($date_limit > ($now - $conf->facture->client->warning_delay)) || empty($date_limit) && !$objp->paye && $objp->fk_statut == 1)
                print '<option value="0" ' . $l0 . '>' . $langs->trans("ITBeforereminder") . '</option>';
            print '<option value="1" ' . $l1 . '>' . $langs->trans("ITSimplereminder") . '</option>';
            print '<option value="2" ' . $l2 . '>' . $langs->trans("ITStamplereminder") . '</option>';
            print '<option value="3" ' . $l3 . '>' . $langs->trans("ITLastreminder") . '</option>';
            print '</td>';

            if (empty($mode)) {
                // Checkbox to merge
                print '<td align="center">';
                if (!empty($formfile->infofiles['extensions']['pdf']))
				if ((int) DOL_VERSION >= 10) {
					print '<input id="cb' . $objp->facid . '" class="flat checkformerge" type="checkbox" name="toGenerate[]" value="' . $objp->ref . '">';
				} else {
					print '<input id="cb' . $objp->facid . '" class="flat checkformerge" type="checkbox" name="toGenerate[]" value="' . $objp->facnumber . '">';
				}
                print '</td>';
            } else {
                // Checkbox to send remind
                print '<td class="nowrap" align="center">';
                print '<input class="flat checkforsend" type="checkbox" name="toSendmail[]" value="' . $objp->facid . '">';

                print '</td>';
                // Checkbox to send remind
                print '<td class="nowrap" align="center">';
                $sendto = "";
                $f = new Facture($db);
                $f->fetch($objp->facid);
                $contactr = $f->liste_contact(-1, 'external', 0, 'REMINDER');
                if (!empty($contactr)) {
                    $sendto = $contactr[0]['email'];
                }

                if (empty($sendto)) {
                    $contactf = $f->liste_contact(-1, 'external', 0, 'BILLING');
                    if (!empty($contactf)) {
                        $sendto = $contactf[0]['email'];
                    }
                }

                if ($objp->email || !empty($sendto))
                    print '<input class="flat checkforsendemail" type="checkbox" name="toSendemail[]" value="' . $objp->facid . '">';
                else print img_picto($langs->trans("NoEMail"), 'warning.png');
                print '</td>';
            }

            print "</tr>";
            $total_ttc += $objp->total_ttc;
            $total_paid += $objp->am + $cn + $dep;

            $i++;
        }

        print '<tr class="liste_total">';
        print '<td colspan="6" align="left">' . $langs->trans("Total") . '</td>';

        print '<td align="right"><b>' . price($total_ttc) . '</b></td>';
        print '<td align="right"><b>' . price($total_ttc - $total_paid) . '</b></td>';
        print "</tr>";
    }

    print "</table>";

    print '<div class="tabsAction">';
    //print '<a href="' . $_SERVER["PHP_SELF"] . '?action=presend&mode=sendremind" class="butAction" name="buttonsendremind" value="' . dol_escape_htmltag($langs->trans("SendRemind")) . '">' . $langs->trans("SendRemind") . '</a>';
    print '<input class="button" type="submit" id="sendmail" name="sendmail" value="' . $langs->trans("Globalreminder") . '">';
    //print '<input type="hidden" name="sendmail" value="1">';
    print '<input type="hidden"  name="action" value="presend">';
    print '<input type="hidden"  name="mode" value="sendremind">';
    print '</div>';
    print '<br>';

    /*
     * Show list of available documents
     */
    $filedir = $diroutputpdf;
    $delallowed = $user->rights->facture->lire;

    print '<br>';
    // We disable multilang because we concat already existing pdf.
    $formfile->show_documents('massfilesarea_invoices', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $langs->trans("Invoicetracking"), $langs->trans("Invoicetracking"));

    print '</form>';

    $db->free($resql);
} else dol_print_error($db, '');


llxFooter();
$db->close();
