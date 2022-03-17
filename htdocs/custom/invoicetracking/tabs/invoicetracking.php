<?php
/* Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2015-2017 Inovea Conseil	<info@inovea-conseil.com>
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
 *	\file       htdocs/margin/tabs/thirdpartyMargins.php
 *	\ingroup    product margins
 *	\brief      Page des marges des factures clients pour un tiers
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

$langs->load("companies");
$langs->load("facture");
$langs->load("invoicetracking@invoicetracking");

// Security check
$socid = GETPOST('id', 'int');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', '', '');


$mesg = '';

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if ($page == -1) { $page = 0; }

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.date_r";


/*
 * View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $langs->trans("ThirdParty").' - '.$langs->trans("Invoicetracking"), $help_url);

if ($socid > 0)
{
    $societe = new Societe($db, $socid);
    $societe->fetch($socid);

    /*
     * Affichage onglets
     */
	$version = (int) DOL_VERSION;
    $head = societe_prepare_head($societe);

    dol_fiche_head($head, 'invoicetracking', $langs->trans("ThirdParty"), 0, 'company');

    $sql = "SELECT";
    $sql.= " t.rowid,";
    $sql.= " t.fk_facture,";
    $sql.= " t.note,";
    $sql.= " t.date_r,";
    $sql.= " t.date_n,";
    $sql.= " t.stage,";
    $sql.= " t.fk_user_modif";
    $sql.= " FROM ".MAIN_DB_PREFIX."invoicetracking as t";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."facture as p ON p.rowid = t.fk_facture ";
    $sql .= "WHERE p.fk_soc=".$socid;
    $sql.= " ORDER BY rowid DESC";

    print '<table class="noborder">'."\n";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Id'), $_SERVER['PHP_SELF'], 't.rowid', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Company'));
    print_liste_field_titre($langs->trans('Facture'), $_SERVER['PHP_SELF'], 't.fk_facture', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Note'), $_SERVER['PHP_SELF'], 't.note', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Level'), $_SERVER['PHP_SELF'], 't.stage', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Date'), $_SERVER['PHP_SELF'], 't.date_r', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('DateNext'), $_SERVER['PHP_SELF'], 't.date_n', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('User'), $_SERVER['PHP_SELF'], 't.fk_user_modif', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('File'));
    print '</tr>';

    dol_syslog($script_file." sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
				$obj = $db->fetch_object($resql);
                if ($obj)
                {
                    $p = new Facture($db);
                    $p->fetch($obj->fk_facture);

                    $s = new Societe($db);
                    $s->fetch($p->socid);

                    $u = new User($db);
                    $u->fetch($obj->fk_user_modif);

                    switch ($obj->stage){
                        case 1:
                            $stagename = $langs->trans("ITSimplereminder");
                        break;
                        case 2:
                            $stagename = $langs->trans("ITStamplereminder");
                        break;
                        case 3:
                            $stagename = $langs->trans("ITLastreminder");
                        break;
                    }

                    // You can use here results
                    print '<tr><td>';
                    print '<a href="'. dol_buildpath("/invoicetracking/index.php?action=edit&id=$obj->rowid", 1).'">'.$obj->rowid.'</a>';
                    print '</td><td>';
                    if ($version >= 6) {
                        print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$p->socid.'">'.img_object($langs->trans("ShowCompany"), "company").$s->nom.'</a>';
                    }
                    else {
                        print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$p->socid.'">'.img_object($langs->trans("ShowCompany"), "company").$s->nom.'</a>';
                    }

                    print '</td><td>';
                    if ($version >= 6) {
                        print '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.$obj->fk_facture.'">'.img_object($langs->trans("ShowFacture"), "bill").$p->ref.'</a>';
                    }else {
                        print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->fk_facture.'">'.img_object($langs->trans("ShowFacture"), "bill").$p->ref.'</a>';
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
                    print img_object($langs->trans("ShowUser"), "user").$u->login;
                    print '</td><td>';
                    print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=facture&file='.preg_replace('#\/#', '_', $p->ref).'%2F'.preg_replace('#\/#', '_', $p->ref).'.pdf"><img src="'.DOL_URL_ROOT.'/theme/eldy/img/pdf2.png" /></a>';
                    print '</td></tr>';
                }

                $i++;
            }
        }
    }
    else {
        $error++;
        dol_print_error($db);
    }

    print '</table>'."\n";
    print '<div class="tabsAction">
        <div class="inline-block divButAction">
            <a href="'.dol_buildpath("/invoicetracking/index.php?action=add&idsoc=".GETPOST('id'), 1).'" class="butAction">'.$langs->trans('Add').'</a>
        </div>
        </div>';
}
else {
	dol_print_error();
}


llxFooter();
$db->close();
