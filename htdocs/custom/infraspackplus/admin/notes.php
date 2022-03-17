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
	* 	\file		../infraspackplus/admin/notes.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup Additional informations for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->load("admin");
	$langs->load('infraspackplus@infraspackplus');

	// Access control *******************************
	$accessright					= !empty($user->admin) || !empty($user->rights->infraspackplus->paramBkpRest) ? 2 : (!empty($user->rights->infraspackplus->paramNotes) ? 1 : 0);
	if (empty($accessright))		accessforbidden();

	// Actions **************************************
	$form							= new Form($db);
	$formfile						= new FormFile($db);
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
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('NoteStd'	=> array('INFRASPLUS_PDF_NT_USED_AS_COVER'));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'none');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'Notes')	$result	= dolibarr_set_const($db, $variablename, GETPOST($variablename, 'none'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if ($result == 1)	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	if ($result == -1)	setEventMessages($langs->trans('Error'), null, 'errors');

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
	$ntUsedAsCover	= !empty($conf->global->INFRASPLUS_PDF_NT_USED_AS_COVER)	? $conf->global->INFRASPLUS_PDF_NT_USED_AS_COVER	: '';
	$rowSpan1		= 2;
	$rowSpan1		+= $conf->global->MAIN_MODULE_PROPALE						? 1													: 0;
	$rowSpan1		+= $conf->global->MAIN_MODULE_COMMANDE						? 1													: 0;
	$rowSpan1		+= $conf->global->MAIN_MODULE_CONTRAT						? 1													: 0;
	$rowSpan1		+= $conf->global->MAIN_MODULE_EXPEDITION					? 1													: 0;
	$rowSpan1		+= $conf->global->MAIN_SUBMODULE_LIVRAISON					? 1													: 0;
	$rowSpan1		+= $conf->global->MAIN_MODULE_FICHEINTER					? 1													: 0;
	$rowSpan1		+= $conf->global->MAIN_MODULE_FACTURE						? 1													: 0;
	$rowSpan1		+= $conf->global->MAIN_MODULE_SUPPLIERPROPOSAL				? 1													: 0;
	$rowSpan1		+= $conf->global->MAIN_MODULE_FOURNISSEUR					? 1													: 0;

	// View *****************************************
	$page_name					= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsNotes');
	llxHeader('', $page_name);
	if (! empty($user->admin))	$linkback	= '<a href = "'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head						= infraspackplus_admin_prepare_head();
	$picto						= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'notes', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script type = "text/javascript" language = "javascript">
					function doReloadNoteP(){
						document.frm1.submit();
					}
				</script>';
	}
	print '		<form name = "frm1" id = "frm1" action = "'.$_SERVER['PHP_SELF'].'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infraspackplus_print_backup_restore();
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamGestionNotes').'</FONT>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "noborder centpercent">';
	$metas	= array('*', '130px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(3), 'InfraSPlusParamNewNote');
	infraspackplus_print_liste_titre($metas);
	if (! empty($accessright)) {
		infraspackplus_print_btn_action('Notes', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), 3, 2);
		print '			<tr class="oddeven">
							<td colspan = "2">';
		print $form->textwithpicto($langs->trans('InfraSPlusParamNote1'), $langs->trans('AddCRIfTooLong').'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'&nbsp;';
		print fieldLabel(''.$langs->trans('InfraSPlusParamNote2').'', 'selmodules').' '.
		$form->selectarray('selmodules', $listModules, $selmodule, 0, 0, 0, 'style = "padding: 0px; font-size: inherit; cursor: pointer;" onchange = "doReloadNoteP();"', 1, 0, 0, '', '');
		print select_infraspackplus_dict('c_infraspackplus_note', $labelnote, 'selnotes', 0, 'doReloadNoteP()');
		print info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'), 1, 1, 0);
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
				<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	if (! empty($accessright)) {
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamNotesSetup').'</FONT>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
		print '			<table class = "noborder centpercent">';
		$metas		= array('30px', '*', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		$metas		= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infraspackplus_print_liste_titre($metas);
		infraspackplus_print_btn_action('NoteStd', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), $rowSpan1, 3);
		$num	= 1;
		$metas	= select_infraspackplus_dict('c_infraspackplus_note', $ntUsedAsCover, 'INFRASPLUS_PDF_NT_USED_AS_COVER', 1, '', 0);
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamNTUsedAsCover'), '', $metas, 1, 1, '', $num);
		if ($conf->global->MAIN_MODULE_PROPALE)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PROPALE')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_COMMANDE)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_COM', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_COMMANDE')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_CONTRAT)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_CT', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_CONTRAT')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_EXPEDITION)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_EXP', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_EXPEDITION')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_SUBMODULE_LIVRAISON)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_REC', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_FICHEINTER)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FI', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FICHEINTER')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_FACTURE)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FAC', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FACTURE')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_SUPPLIERPROPOSAL)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV_FOU', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_FOURNISSEUR)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FOU', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FOURNISSEUR')), '', array(), 1, 1, '', $num);
		else	$num++;
		print '		</table>';
	}
	print '		</form>';
	dol_fiche_end();
	llxFooter();
?>