<?php
	/************************************************
	* Copyright (C) 2016-2022	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	************************************************/

	/************************************************
	* 	\file		../infraspackplus/admin/extrafields.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup extrafields for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->load('admin');
	$langs->load('infraspackplus@infraspackplus');

	// Access control *******************************
	$accessright					= !empty($user->admin) || !empty($user->rights->infraspackplus->paramBkpRest) ? 2 : (!empty($user->rights->infraspackplus->paramExtraFields) ? 1 : 0);
	if (empty($accessright))		accessforbidden();

	// Actions **************************************
	$form							= new Form($db);
	$formfile						= new FormFile($db);
	$formother						= new FormOther($db);
	$action							= GETPOST('action', 'alpha');
	$result							= '';
	//Sauvegarde / Restauration
	if ($action == 'bkupParams')	$result	= infraspackplus_bkup_module ('infraspackplus');
	if ($action == 'restoreParams')	$result	= infraspackplus_restore_module ('infraspackplus');
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('EXF'	=> array('INFRASPLUS_PDF_EXF_PAY_SPEC', 'INFRASPLUS_PDF_EXF_DEPOSIT', 'INFRASPLUS_PDF_EXF_PRE'));
		$listcolor	= array('EXF'	=> array('INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR'),
							'EXFL'	=> array('INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR'));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {

			$constvalue	= GETPOST($constname, 'alpha');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		foreach ($listcolor[$confkey] as $constname) {
			$constvalue	= implode(', ', colorStringToArray(GETPOST($constname, 'alpha')));
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}

	if ($result == 1)	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	if ($result == -1)	setEventMessages($langs->trans('Error'), null, 'errors');

	// View *****************************************
	$page_name					= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsExtraFields');
	llxHeader('', $page_name);
	if (! empty($user->admin))	$linkback	= '<a href = "'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head						= infraspackplus_admin_prepare_head();
	$picto						= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'extrafields', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	print '		<form action = "'.$_SERVER['PHP_SELF'].'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infraspackplus_print_backup_restore();
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamExtraFieldsSetup').'</FONT>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "noborder width = "100%">';
	$metas	= array('30px', '*', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (! empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('EXF', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), 13, 3);
		$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamEXFValueTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR), '', $metas, 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_PAY_SPEC', 'input', $langs->trans('InfraSPlusParamEXFpaySpec'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_DEPOSIT', 'input', $langs->trans('InfraSPlusParamEXFdeposit'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_D', 'on_off', $langs->trans('InfraSPlusParamEXFD'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_C', 'on_off', $langs->trans('InfraSPlusParamEXFC'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_CT', 'on_off', $langs->trans('InfraSPlusParamEXFCT'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_FI', 'on_off', $langs->trans('InfraSPlusParamEXFFI'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_E', 'on_off', $langs->trans('InfraSPlusParamEXFE'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_F', 'on_off', $langs->trans('InfraSPlusParamEXFF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_DF', 'on_off', $langs->trans('InfraSPlusParamEXFDF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_CF', 'on_off', $langs->trans('InfraSPlusParamEXFCF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_FF', 'on_off', $langs->trans('InfraSPlusParamEXFFF'), '', array(), 1, 1, '', $num);
		print '	</form>';
		infraspackplus_print_hr(4);
		print '	<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype="multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
		infraspackplus_print_btn_action('EXFL', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), 13, 3);
		$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamEXFLValueTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR), '', $metas, 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_D', 'on_off', $langs->trans('InfraSPlusParamEXFLD'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_C', 'on_off', $langs->trans('InfraSPlusParamEXFLC'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_CT', 'on_off', $langs->trans('InfraSPlusParamEXFLCT'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_FI', 'on_off', $langs->trans('InfraSPlusParamEXFLFI'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_E', 'on_off', $langs->trans('InfraSPlusParamEXFLE'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_F', 'on_off', $langs->trans('InfraSPlusParamEXFLF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_DF', 'on_off', $langs->trans('InfraSPlusParamEXFLDF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_CF', 'on_off', $langs->trans('InfraSPlusParamEXFLCF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_FF', 'on_off', $langs->trans('InfraSPlusParamEXFLFF'), '', array(), 1, 1, '', $num);
	}
	print '			</table>
				</form>';
	dol_fiche_end();
	llxFooter();
?>