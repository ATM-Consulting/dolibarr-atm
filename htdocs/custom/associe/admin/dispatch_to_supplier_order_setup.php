<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * 	\file		admin/associe.php
 * 	\ingroup	associe
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/function.lib.php';
dol_include_once('abricot/includes/lib/admin.lib.php');

// Translations
$langs->load("admin");
$langs->load('associe@associe');

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, GETPOST($code, 'none'), 'chaine', 0, '', $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if (preg_match('/del_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, 0) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

/*
 * View
 */
$page_name = "associeSetup";
llxHeader('', $langs->trans($page_name));

    // Configuration header
    $head = associeAdminPrepareHead();
    dol_fiche_head(
        $head,
        'nomenclature',
        $langs->trans("Module419419Name"),
        0,
        "associe@associe"
        );



    $linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
    print_fiche_titre($langs->trans("Associe"),$linkback,'associe@associe');


    dol_fiche_end();


    // Setup page goes here
    $form=new Form($db);
    $var=false;
    print '<table class="noborder" width="100%">';


    setup_print_title("Parameters");

    // USE Nomenclature tab
    setup_print_on_off('ASS_USE_NOMENCLATURE',false, '', 'ASS_USE_NOMENCLATURE_HELP');


    setup_print_title("ParametersNeedASS_USE_NOMENCLATURE");

    // Fill qty for nomenclature
    setup_print_on_off('ASS_FILL_QTY_NOMENCLATURE',false);

    // Disable product order if nomenclature
    setup_print_on_off('ASS_DISABLE_ORDER_POSIBILITY_TO_PRODUCT_WITH_NOMENCLATURE');


    // USE DELIVERY CONTACT
    setup_print_on_off('ASS_USE_DELIVERY_CONTACT',false, '', 'DeliveryHelp');


    // USE RESTRICTION CONTACT
    setup_print_on_off('ASS_USE_RESTRICTION_TO_CUSTOMER_ORDER');

    setup_print_on_off('ASS_ADD_QUANTITY_RATHER_THAN_CREATE_LINES');
    
    setup_print_on_off('ASS_DISPLAY_SERVICES');

    //EUROCHEF MARGE
    setup_print_input_form_part('ASS_EURO_MARGE', 'ASS_EURO_MARGE');
    
    //EUROCHEF MARGE DEFAULT
    setup_print_input_form_part('ASS_EURO_MARGE_DEFAULT', 'ASS_EURO_MARGE_DEFAULT');
    
    //EUROCHEF BFA
    setup_print_input_form_part('ASS_EURO_BFA', 'ASS_EURO_BFA');

    // Example with imput
    //setup_print_input_form_part('CONSTNAME', 'ParamLabel');

    // Example with color
   // setup_print_input_form_part('CONSTNAME', 'ParamLabel', 'ParamDesc', array('type'=>'color'),'input','ParamHelp');

    // Example with placeholder
    //setup_print_input_form_part('CONSTNAME','ParamLabel','ParamDesc',array('placeholder'=>'http://'),'input','ParamHelp');

    // Example with textarea
    //setup_print_input_form_part('CONSTNAME','ParamLabel','ParamDesc',array(),'textarea');


    print '</table>';

    llxFooter();

    $db->close();
