<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
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
 *  \file       htdocs/prixmassecategorie/product.php
 *  \ingroup    prixmassecategorie
 *  \brief      Page to show product set
 */



$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

dol_include_once("/prixmassecategorie/class/prixmassecategorie.class.php");

$langs->load("prixmassecategorie@prixmassecategorie");

$langs->load("categories");
$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');
$langs->load("deliveries");
$langs->load('sendings');

$id = GETPOST('id', 'int');
$label = GETPOST('label', 'alpha');

$rowid = GETPOST('rowid', 'int');
$socid = GETPOST('socid', 'int');

$action = GETPOST('action','aZ09');
$actionATM = GETPOST('actionATM'); //restaurer supprimer  
$idVersion = GETPOST('idVersion');

$result=restrictedArea($user,'prixmassecategorie');

$object = new Categorie($db);
if ($id > 0 || ! empty($label)) $object->fetch($id, $label);


/*
    * Actions
    */

$form = new Form($db);

if ($action == 'confirm_addmassprice' && GETPOST('confirm') == 'yes')
{
    if (!GETPOST('mass_percent') && !GETPOST('price_ht'))
    {	
        setEventMessages($langs->trans('MassPercentIsEmpty'), null, 'errors');
    }
    else
    {
        $produits = $object->getObjectsInCateg(Categorie::TYPE_PRODUCT);

        $price_ht = GETPOST('price_ht') ? price2num(GETPOST('price_ht')) : 0;
        $mass_percent = GETPOST('mass_percent') ? price2num(GETPOST('mass_percent')) : 0;
        $level = GETPOST('level', 'int');

        $price_ht = price2num(price($price_ht), 'MT');

        $prixmassecategorie = new PrixMasseCategorie($db);
        $prixmassecategorie->fk_object = $object->id;
        $prixmassecategorie->percent = $mass_percent;
        $prixmassecategorie->price_ht = $price_ht;
        $prixmassecategorie->level = $level;

        if ($prixmassecategorie->create($user, $produits) > 0)
        {
            setEventMessages($langs->trans("MassPercentApplied"), null, 'mesgs');
        }
        else
        {
            setEventMessages($prixmassecategorie->error, $prixmassecategorie->errors, 'errors');
        }

    }
}

if ($action == 'confirm_delmod' && GETPOST('confirm') == 'yes' && $user->rights->prixmassecategorie->supprimer)
{
    $prixmassecategorie = new PrixMasseCategorie($db);
    $prixmassecategorie->fetch($rowid);

    $result = $prixmassecategorie->delete($user);

    if ($result < 0)
    {
        setEventMessages($prixmassecategorie->error, $prixmassecategorie->errors, 'errors');   
    } 
    else
    {
        setEventMessages($langs->trans("MassPercentDeleted"), null, 'mesgs');
    }
}

/*
*  View
*/

$title = $title=$langs->trans("ProductsCategoryShort");
$help_url = '';

llxHeader('', $langs->trans("Categories"), $help_url);

if ($action == 'delmod')
{
    $prixmassecategorie = new PrixMasseCategorie($db);
    $prixmassecategorie->fetch($rowid);

    print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id.'&rowid='.$rowid, $langs->trans('DeleteMassMod'), $langs->trans('ConfirmDeleteMassMod', dol_print_date($prixmassecategorie->date_creation, 'day'), $object->label), 'confirm_delmod', '', 'yes', 1); 
}


if ($action == 'addmassprice')
{
    $prixmassecategorie = new PrixMasseCategorie($db);
    $prixmassecategorie->fetch('', $object->id);

    $mods = $prixmassecategorie->mods;

    $levels = $prixmassecategorie->getLevels();

    $formquestion = array();

    if (sizeof($mods))
    {
        $mod = array_shift($mods);
        $formquestion['text'] = $langs->trans('OneModificationExists', dol_print_date($mod->date_creation, 'day'), $object->label);
    }

    // Create an array for form
    $formquestion[] = array(
        'type' => 'text',
        'name' => 'mass_percent',
        'label' => $langs->trans("MassPercent"),
        'value' => ''
    );

    $formquestion[] = array(
        'type' => 'text',
        'name' => 'price_ht',
        'label' => $langs->trans("TargetPriceHT"),
        'value' => ''
    );

    $formquestion[] = array(
        'type' => 'other',
        'name' => 'level',
        'label' => $langs->trans("ModLevel"),
        'value' => $form->selectarray('level', $levels, 0)
    );

    print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('AddMassPriceTitle'), $langs->trans('ConfirmAddMassPrice', $object->label), 'confirm_addmassprice', $formquestion, 'yes', 1, 280);			
}

if ($id > 0 || ! empty($label))
{
    $type = $object->type  == 0 ? Categorie::TYPE_PRODUCT : '';

    $prixmassecategorie = new PrixMasseCategorie($db);
    $prixmassecategorie->fetch('', $object->id);

    $levels = $prixmassecategorie->getLevels();

    /*
        * Affichage onglets
        */
    if (! empty($conf->notification->enabled)) $langs->load("mails");

    $head = categories_prepare_head($object, 'product');
    dol_fiche_head($head, 'prixmassecategorie', $title, -1, 'category');

    $linkback = '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
    $ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
    foreach ($ways as $way)
    {
        $morehtmlref.=$way."<br>\n";
    }
    $morehtmlref.='</div>';

    dol_banner_tab($object, 'label', $linkback, ($user->societe_id?0:1), 'label', 'label', $morehtmlref, '', 0, '', '', 1);

    dol_fiche_end();

    $mods = $prixmassecategorie->mods;

    $badge = count($prixmassecategorie->mods);

    print load_fiche_titre($langs->trans("MassModificationsHist"), '', '');

    print '<div class="div-table-responsive-no-min">';
    print '<table width="100%" id="tablelines" class="liste">'."\n";

    print '<tr class="liste_titre nodrag nodrop">';
    print_liste_field_titre('ModDate',$_SERVER["PHP_SELF"]);
    print_liste_field_titre('ModUser',$_SERVER["PHP_SELF"]);
    print_liste_field_titre('ModPercent',$_SERVER["PHP_SELF"]);
    print_liste_field_titre('ModLevel',$_SERVER["PHP_SELF"]);
    print_liste_field_titre('ModPriceHT',$_SERVER["PHP_SELF"]);
    print_liste_field_titre('ModAction',$_SERVER["PHP_SELF"]);
    print "</tr>\n";

    if (is_array($mods) && sizeof($mods))
    {
        foreach ($mods as $i => $m)
        {            
            print '<tr class="oddeven">';
            print '<td align="left">'.dol_print_date($m->date_creation, 'day').'</td>';
            print '<td align="left">'.$m->fk_user.'</td>';
            print '<td align="left">'.($m->percent != 0 ? price($m->percent, 0, $langs, 1, -1, 2).'%' : '').'</td>';
            print '<td align="left">'.(isset($levels[$m->level]) ? $levels[$m->level] : '').'</td>';

            print '<td align="left">'.($m->price_ht > 0 ? price($m->price_ht, 0, $langs, 1, -1, -1, $conf->currency) : '').'</td>';
            print '<td align="left">';

            if ($i == 0 && $user->rights->prixmassecategorie->supprimer)
            {
                print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delmod&amp;rowid=' . $m->id . '">';
                print img_delete();
                print '</a>';
            }
            else
            {
                print '&nbsp;';
            }

            print '</td>';  
            print '</tr>';            
        }
            
    }
    else
    {
        print '<tr class="oddeven">';
        print '<td align="left" colspan="6">';
        print $langs->trans('NoModFound');
        print '</td>';  
        print '</tr>'; 
    }
    

    print "</table>";
    print '</div>';

    /*
    * Buttons for actions
    */
    
    print '<div class="tabsAction">';

    if ($user->rights->prixmassecategorie->creer)
    {
        print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id.'&amp;action=addmassprice">' . $langs->trans('AddMassPrice') . '</a></div>';
    }

    print '</div>';
    

}

llxFooter();    

$db->close();

