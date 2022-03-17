<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 SuperAdmin <francis.appels@z-application.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    blockoutstanding/admin/setup.php
 * \ingroup blockoutstanding
 * \brief   BlockOutstanding setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/blockoutstanding.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "blockoutstanding@blockoutstanding"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters = array(
	'BLOCKOUTSTANDING_LEVEL'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_ADDLINE'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_CREATE'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_VALIDATE'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_PROPOSAL'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_ORDER'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_INVOICE'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_SUPPLIER_ORDER'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_SUPPLIER_INVOICE'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_TICKET'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_FICHINTER'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'yesno'),
	'BLOCKOUTSTANDING_DEFAULT_AMOUNT'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'text')
);



/*
 * Actions
 */

if ($action == 'setYesNo') {
	$db->begin();

	// Process common param fields
	if (is_array($_GET)) {
		foreach ($_GET as $key => $val) {
			if (preg_match('/^param(\w*)$/', $key, $reg)) {
				$param=GETPOST("param".$reg[1], 'alpha');
				$value=GETPOST("value".$reg[1], 'int');
				if ($param) {
					$res = dolibarr_set_const($db, $param, $value, 'yesno', 0, '', $conf->entity);
					if (! $res > 0) $error++;
				}
			}
		}
	}

	if (! $error) {
		$db->commit();
	} else {
		$db->rollback();
		if (empty($nomessageinsetmoduleoptions)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
	}
} elseif ($action == 'update') {
	$db->begin();

	$error=0;
	foreach ($arrayofparameters as $key => $val) {
		if ($arrayofparameters[$key]['type'] == 'text') {
			$result=dolibarr_set_const($db, $key, GETPOST($key, 'alpha'), 'chaine', 0, '', $conf->entity);
			if ($result < 0) {
				$error++;
				break;
			}
		}
	}

	if (! $error) {
		$db->commit();
		if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
	}
}


/*
 * View
 */

$page_name = "BlockOutstandingSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_blockoutstanding@blockoutstanding');

// Configuration header
$head = blockoutstandingAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', '', -1, "blockoutstanding@blockoutstanding");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("BlockOutstandingSetupPage").'</span><br><br>';


if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if (function_exists('newToken')) print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		print '<tr class="oddeven"><td>';
		$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
		print $form->textwithpicto($langs->trans($key), $tooltiphelp);
		if ($val['type'] == 'yesno') {
			if ($conf->global->$key == "1") {
				print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&token='.newToken().'&value'.$key.'=0">';
				print img_picto($langs->trans("Activated"), 'switch_on');
				print '</td></tr>';
			} else {
				print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&token='.newToken().'&value'.$key.'=1">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
				print '</a></td></tr>';
			}
		} else {
			print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.$conf->global->$key.'">';
		}
		print '</td></tr>';
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	if (!empty($arrayofparameters)) {
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach ($arrayofparameters as $key => $val) {
			print '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
			print $form->textwithpicto($langs->trans($key), $tooltiphelp);
			if ($val['type'] == 'yesno') {
				if ($conf->global->$key == "1") {
					print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&token='.newToken().'&value'.$key.'=0">';
					print img_picto($langs->trans("Activated"), 'switch_on');
					print '</td></tr>';
				} else {
					print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&token='.newToken().'&value'.$key.'=1">';
					print img_picto($langs->trans("Disabled"), 'switch_off');
					print '</a></td></tr>';
				}
			} else {
				print '</td><td>'.$conf->global->$key;
			}
			print '</td></tr>';
		}

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
		print '</div>';
	} else {
		print '<br>'.$langs->trans("NothingToSetup");
	}
}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
