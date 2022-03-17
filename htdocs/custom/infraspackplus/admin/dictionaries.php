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
	* 	\file		../infraspackplus/admin/dictionaries.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup Additional informations for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once dol_buildpath('/advancedictionaries/core/actions_dictionaries.inc.php');
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->load("admin");
	$langs->load('infraspackplus@infraspackplus');

	// Access control *******************************
	$accessright					= !empty($user->admin) || !empty($user->rights->infraspackplus->paramBkpRest) ? 2 : (!empty($user->rights->infraspackplus->paramDict) ? 1 : 0);
	if (empty($accessright))		accessforbidden();

	// Actions **************************************
	$form							= new Form($db);
	$formother						= new FormOther($db);
	$action							= GETPOST('action', 'alpha');
	$labelnote						= GETPOST('selnotes', 'alpha')		? GETPOST('selnotes', 'alpha')		: 'BASE';
	$selmodule						= GETPOST('selmodules', 'alpha')	? GETPOST('selmodules', 'alpha')	: 'PROPOSAL_PUBLIC_NOTE';
	$variablename					= $labelnote == 'BASE' ? $selmodule : $selmodule.'_'.$labelnote;
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
	if ($action == 'update_Notes')	$result	= dolibarr_set_const($db, $variablename, GETPOST($variablename, 'none'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);

	if ($result == 1)				setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	if ($result == -1)				setEventMessages($langs->trans("Error"), null, 'errors');

	// init variables *******************************
	$listModules	= array (	'PROPOSAL_PUBLIC_NOTE'			=> 'InfraSPlusParam_MAIN_MODULE_PROPALE',
								'ORDER_PUBLIC_NOTE'				=> 'InfraSPlusParam_MAIN_MODULE_COMMANDE',
								'CONTRACT_PUBLIC_NOTE'			=> 'InfraSPlusParam_MAIN_MODULE_CONTRAT',
								'SHIPPING_PUBLIC_NOTE'			=> 'InfraSPlusParam_MAIN_MODULE_EXPEDITION',
								'DELIVERY_PUBLIC_NOTE'			=> 'InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON',
								'FICHINTER_PUBLIC_NOTE'			=> 'InfraSPlusParam_MAIN_MODULE_FICHEINTER',
								'INVOICE_PUBLIC_NOTE'			=> 'InfraSPlusParam_MAIN_MODULE_FACTURE',
								'SUPPLIER_PROPOSAL_PUBLIC_NOTE'	=> 'InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL',
								'SUPPLIER_ORDER_PUBLIC_NOTE'	=> 'InfraSPlusParam_MAIN_MODULE_FOURNISSEUR',
								'PRODUCT_PUBLIC_NOTE'			=> 'InfraSPlusParam_MAIN_MODULE_PRODUCT',
								'PROJECT_PUBLIC_NOTE'			=> 'InfraSPlusParam_MAIN_MODULE_PROJET',
								'EXPENSEREPORT_PUBLIC_NOTE'		=> 'InfraSPlusParam_MAIN_MODULE_EXPENSEREPORT'
							);

	// View *****************************************
	$page_name					= $langs->trans("infrasplussetup") ." - ". $langs->trans("InfraSPlusParamsDict");
	llxHeader('', $page_name);
	if (! empty($user->admin))	$linkback	= '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head						= infraspackplus_admin_prepare_head();
	$picto						= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'dictionaries', $langs->trans("modcomnamePackPlus"), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script type = "text/javascript" language = "javascript">
					function doReloadNoteP(){
						document.frm1.submit();
					}
				</script>';
	}
	print '		<form name="frm1" id="frm1" action="'.$_SERVER["PHP_SELF"].'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infraspackplus_print_backup_restore();
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamGestionNotes').'</FONT>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "noborder" width = "100%">';
	$metas	= array('*', '130px', '120px');
	infraspackplus_print_colgroup($metas);
	infraspackplus_print_liste_titre(3, 'InfraSPlusParamNewNote');
	if (! empty($accessright)) {
		infraspackplus_print_btn_action('Notes', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), 3, 2);
		print '			<tr class="oddeven">
							<td colspan = "2">';
		print $form->textwithpicto($langs->trans("InfraSPlusParamNote1"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'&nbsp;';
		print fieldLabel(''.$langs->trans("InfraSPlusParamNote2").'', 'selmodules').' '.
		$form->selectarray('selmodules', $listModules, $selmodule, 0, 0, 0, 'style = "padding: 0px; font-size: inherit; cursor: pointer;" onchange = "doReloadNoteP();"', 1, 0, 0, '', '');
		print select_infraspackplus_dict('c_infraspackplus_note', $labelnote, 'selnotes', 0, 'doReloadNoteP()');
		print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1, 1, 0);
		print '				</td>
						</tr>';
		print '			<tr>
							<td colspan = "2">';
		$doleditor	= new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
		print $doleditor->Create();
		print '				</td>
						</tr>';
		infraspackplus_print_final();
	}
	print '			</table>
				</form>
				<form action="'.$_SERVER["PHP_SELF"].'" method = "post" enctype="multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamNotesSetup').'</FONT>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
	print '			<table class = "noborder" width = "100%">';
	$metas	= array('*', '130px');
	infraspackplus_print_colgroup($metas);
	infraspackplus_print_liste_titre(1, 'Description', false);
	if (! empty($accessright)) {
		if ($conf->global->MAIN_MODULE_PROPALE)				infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV',		'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PROPALE')),				'', array(), '1', '1');
		if ($conf->global->MAIN_MODULE_COMMANDE)			infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_COM',		'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_COMMANDE')),				'', array(), '1', '1');
		if ($conf->global->MAIN_MODULE_CONTRAT)				infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_CT', 		'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_CONTRAT')),				'', array(), '1', '1');
		if ($conf->global->MAIN_MODULE_EXPEDITION)			infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_EXP',		'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_EXPEDITION')),			'', array(), '1', '1');
		if ($conf->global->MAIN_SUBMODULE_LIVRAISON)		infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_REC',		'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON')),			'', array(), '1', '1');
		if ($conf->global->MAIN_MODULE_FICHEINTER)			infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FI',		'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FICHEINTER')),			'', array(), '1', '1');
		if ($conf->global->MAIN_MODULE_FACTURE)				infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FAC',		'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FACTURE')),				'', array(), '1', '1');
		if ($conf->global->MAIN_MODULE_SUPPLIERPROPOSAL)	infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV_FOU',	'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL')),		'', array(), '1', '1');
		if ($conf->global->MAIN_MODULE_FOURNISSEUR)			infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FOU',		'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FOURNISSEUR')),			'', array(), '1', '1');
	}
		infraspackplus_print_final(2);
	print '			</table>
				</form>';
	dol_fiche_end();
	llxFooter();
?>