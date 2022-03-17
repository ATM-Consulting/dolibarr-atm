<?php

$res=@include("../../main.inc.php");						// For root directory
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

dol_include_once('/associe/lib/function.lib.php');
dol_include_once('/associe/class/cmdAss.class.php');

$langs->load("admin");
$langs->load('associe@associe');

global $db;

// Security check
if (! $user->admin) accessforbidden();

$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');

/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];

	$value = GETPOST($code, 'none');

	if($code == 'ASS_DEFAULT_PRODUCT_CATEGORY_FILTER') {

		if(is_array($value))
		{
			if(in_array(-1, $value) && count($value) > 1) {
				unset($value[array_search(-1, $value)]);
			}
			$TCategories = array_map('intval', $value);
		}
		elseif($value > 0)
		{
			$TCategories = array(intval($value));
		}
		else {
			$TCategories = array(-1);
		}

		$value = serialize($TCategories);
	}

	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{

		if($code=='ASS_USE_DELIVERY_TIME' && GETPOST($code, 'none') == 1) {

			dolibarr_set_const($db,'FOURN_PRODUCT_AVAILABILITY',1);
		}

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

llxHeader('',$langs->trans("Associe"));


// Configuration header
$head = associeAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module419419Name"),
    0,
    "associe@associe"
    );

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Associe"),$linkback,'associe@associe');


dol_fiche_end();

print '<br>';

$form=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";


// Add Eurochef's Marge Service Gestion
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("EuroMarge").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ASS_EURO_MARGE">';
print '<input class="flat" type="text" name="ASS_EURO_MARGE" value='.$conf->global->ASS_EURO_MARGE.'>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Add Eurochef's Marge Service Default
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("EuroMargeDefault").'</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ASS_EURO_MARGE_DEFAULT">';
$commEurochef = CMDGrandCompte::getCommEurochef('ASS_EURO_MARGE_DEFAULT',true);
if(strpos($commEurochef, '<option value="-1">') != null){
	$commECs = $langs->trans("NoServiceComm");
}else{
	$commECs = $commEurochef;
}
print '<td align="right" width="500">'.$commECs;
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">'.'</td>';
print '</form>';
print '</td></tr>';

// Add Eurochef's BFA Service Gestion
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("EuroBFA").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ASS_EURO_BFA">';
print '<input class="flat" type="text" name="ASS_EURO_BFA" value='.$conf->global->ASS_EURO_BFA.'>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Add shipment as titles in invoice
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CreateNewSupplierOrderAnyTime").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ASS_CREATE_NEW_SUPPLIER_ODER_ANY_TIME">';
print $form->selectyesno("ASS_CREATE_NEW_SUPPLIER_ODER_ANY_TIME",$conf->global->ASS_CREATE_NEW_SUPPLIER_ODER_ANY_TIME,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Create identical supplier order to order
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AddFreeLinesInSupplierOrder").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ASS_ADD_FREE_LINES">';
print $form->selectyesno("ASS_ADD_FREE_LINES",$conf->global->ASS_ADD_FREE_LINES,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ASS_DISPLAY_SERVICES").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ASS_DISPLAY_SERVICES">';
print $form->selectyesno("ASS_DISPLAY_SERVICES",$conf->global->ASS_DISPLAY_SERVICES,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Header to supplier order if only one associate
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ASS_HEADER_SUPPLIER_ORDER").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ASS_HEADER_SUPPLIER_ORDER">';
print $form->selectyesno("ASS_HEADER_SUPPLIER_ORDER",$conf->global->ASS_HEADER_SUPPLIER_ORDER,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

if (!empty($conf->global->ASS_ADD_FREE_LINES)) {
	//Use cost price as buying price for free lines
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("UseCostPriceAsBuyingPrice").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_ASS_COST_PRICE_AS_BUYING">';
	print $form->selectyesno("ASS_COST_PRICE_AS_BUYING",$conf->global->ASS_COST_PRICE_AS_BUYING,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';
}

print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
