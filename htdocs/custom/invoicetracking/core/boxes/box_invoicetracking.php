<?php

/* Copyright (C) 2015-2017 Inovea Conseil	<info@inovea-conseil.com>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * 	\file       htdocs/includes/boxes/box_combox.php
 * 	\ingroup    societes
 * 	\brief      Module to show box of bills, orders & facture of the current year
 * 	\version	$Id: box_combox.php,v 1.0 2015/05/05 Inovea Conseil
 */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";
include_once DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

class box_invoicetracking extends ModeleBoxes
{

    var $boxcode = "invoicetracking";
    var $boximg = "object_bill";
    var $boxlabel;
    var $depends = array("facture");
    var $db;
    var $param;
    var $info_box_head = array();
    var $info_box_contents = array();
    public $widgettype = 'graph';
    /**
     *      \brief      Constructeur de la classe
     */
    function box_invoicetracking()
    {
        global $langs;
        $langs->load("boxes");
        $langs->load("invoicetracking@invoicetracking");

        $this->boxlabel = $langs->trans("InvoiceTracking");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox()
    {
        global $conf, $user, $langs, $db;

        $version = (int) DOL_VERSION;

        $dir = '';  // We don't need a path because image file will not be saved into disk
        $prefix = '';
        $socid = 0;
        if ($user->societe_id)
            $socid = $user->societe_id;
        if (!$user->rights->societe->client->voir || $socid)
            $prefix .= 'private-' . $user->id . '-'; //

        include_once DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php";
        $facturestatic = new Facture($db);
        $refreshaction = 'refresh_' . $this->boxcode;

        $textHead = $langs->trans("Invoicetracking");
        $this->info_box_head = array(
            'text' => $textHead,
            'limit' => dol_strlen($textHead),
            'target' => 'none' // Set '' to get target="_blank"
        );
        if (!empty($conf->facture->enabled) && $user->rights->facture->lire) {
            if ((int) DOL_VERSION >= 10){
                $sql = "SELECT p.ref, s.nom, u.login, p.total, q.date_n, MAX(q.date_n) AS maxi ,s.rowid AS socid,p.rowid AS propid, q.rowid AS quotid FROM " . MAIN_DB_PREFIX . "facture AS p ";
            } else {
                $sql = "SELECT p.facnumber, s.nom, u.login, p.total, q.date_n, MAX(q.date_n) AS maxi ,s.rowid AS socid,p.rowid AS propid, q.rowid AS quotid FROM " . MAIN_DB_PREFIX . "facture AS p ";
            }
            $sql .= "INNER JOIN " . MAIN_DB_PREFIX . "user AS u ON fk_user_author=u.rowid ";
            $sql .= "INNER JOIN " . MAIN_DB_PREFIX . "societe AS s ON p.fk_soc=s.rowid ";
            $sql .= "INNER JOIN " . MAIN_DB_PREFIX . "invoicetracking AS q ON q.fk_facture=p.rowid ";
            $sql .= "WHERE p.fk_statut = '1'";

            if (!$user->rights->invoicetracking->seeall) {
                $sql .= " AND u.rowid = " . $user->id . " ";
            }

            if ((int) DOL_VERSION >= 10){
                $sql .= " GROUP BY p.ref ORDER BY maxi ASC";
            } else {
                $sql .= " GROUP BY p.facnumber ORDER BY maxi ASC";
            }
            $result = $db->query($sql);


            $sql = "SELECT COUNT(*) AS relance FROM " . MAIN_DB_PREFIX . "facture AS p ";
            $sql .= "INNER JOIN " . MAIN_DB_PREFIX . "user AS u ON p.fk_user_author=u.rowid ";
            if (!$user->rights->invoicetracking->seeall) {
                $sql .= "AND u.rowid = " . $user->id . " ";
            }

            $sql .= "WHERE  p.fk_statut = '1' AND p.rowid NOT IN (SELECT DISTINCT q.fk_facture FROM " . MAIN_DB_PREFIX . "invoicetracking AS q)";
            $count = $db->query($sql);
            if ($result) {
                $num = $db->num_rows($result);
                $i = 0;
                $tab = array();

                while ($i < $num) {
                    $objp = $db->fetch_object($result);
                    //echo "<pre>" . print_r($objp, 1) . "</pre>";
                    $this->info_box_contents[$i][0] = array('td' => 'align="right" width="16"', 'logo' => 'object_user');
                    $this->info_box_contents[$i][1] = array('td' => 'align="left"', 'text' => $objp->login);
                    if ((double) DOL_VERSION >= '3.7') {
                        $this->info_box_contents[$i][2] = array('td' => 'align="right"', 'logo' => 'object_company', 'url' => DOL_URL_ROOT . "/comm/card.php?socid=" . $objp->socid);
                        $this->info_box_contents[$i][3] = array('td' => 'align="left"', 'text' => $objp->nom, 'url' => DOL_URL_ROOT . "/comm/card.php?socid=" . $objp->socid);
                    } else {
                        $this->info_box_contents[$i][2] = array('td' => 'align="right"', 'logo' => 'object_company', 'url' => DOL_URL_ROOT . "/comm/fiche.php?socid=" . $objp->socid);
                        $this->info_box_contents[$i][3] = array('td' => 'align="left"', 'text' => $objp->nom, 'url' => DOL_URL_ROOT . "/comm/fiche.php?socid=" . $objp->socid);
                    }

                    if ($version >= 6) {
                        $this->info_box_contents[$i][4] = array('td' => 'align="right"', 'logo' => 'object_bill', 'url' => DOL_URL_ROOT . "/compta/facture/card.php?id=" . $objp->propid);
                        if ((int) DOL_VERSION >= 10){
                            $this->info_box_contents[$i][5] = array('td' => 'align="left"', 'text' => $objp->ref, 'url' => DOL_URL_ROOT . "/compta/facture/card.php?id=" . $objp->propid);
                        } else {
                            $this->info_box_contents[$i][5] = array('td' => 'align="left"', 'text' => $objp->facnumber, 'url' => DOL_URL_ROOT . "/compta/facture/card.php?id=" . $objp->propid);
                        }
                    }
                    else{
                        $this->info_box_contents[$i][4] = array('td' => 'align="right"', 'logo' => 'object_bill', 'url' => DOL_URL_ROOT . "/compta/facture.php?id=" . $objp->propid);
                        if ((int) DOL_VERSION >= 10){
                            $this->info_box_contents[$i][5] = array('td' => 'align="left"', 'text' => $objp->ref, 'url' => DOL_URL_ROOT . "/compta/facture.php?id=" . $objp->propid);
                        } else {
                            $this->info_box_contents[$i][5] = array('td' => 'align="left"', 'text' => $objp->facnumber, 'url' => DOL_URL_ROOT . "/compta/facture.php?id=" . $objp->propid);
                        }
                    }

                    $this->info_box_contents[$i][6] = array('td' => 'align="left"', 'text' => price($objp->total, null, null, null, null, null, $conf->currency) . " HT");
                    if ($objp->maxi < date('Y-m-d')) {
                        $this->info_box_contents[$i][7] = array('td' => 'align="left"', 'text' => dol_print_date($objp->maxi), 'url' => dol_buildpath('/invoicetracking/tabs/invoicetracking_facture.php?id=' . $objp->propid, 1), 'text2' => img_warning($langs->transnoentitiesnoconv("In late")));
                    } else {
                        $this->info_box_contents[$i][7] = array('td' => 'align="left"', 'text' => dol_print_date($objp->maxi), 'url' => dol_buildpath('/invoicetracking/tabs/invoicetracking_facture.php?id=' . $objp->propid, 1));
                    }

                    $i++;
                }

                $rest = $db->fetch_object($count)->relance;
                if ($rest > 0)
                    $this->info_box_contents[$i++][0] = array('td' => 'align="center" colspan="8"', 'text' => $langs->trans("InvoiceNotTract") . " " . $rest);
            }

            //echo "<pre>" . print_r($tab), 1) . "</pre>";

            if ($num == 0)
                $this->info_box_contents[$i][0] = array('td' => 'align="center"', 'text' => $langs->trans("NoRecorded"));
        }
        else {
            $this->info_box_contents[0][0] = array('td' => 'align="left"', 'text' => $langs->trans("ReadPermissionNotAllowed"));
        }
    }

    function showBox($head = null, $contents = null, $nooutput = 0)
    {
       return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
