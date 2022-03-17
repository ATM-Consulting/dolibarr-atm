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
	* 	\file		../infraspackplus/admin/mentions.php
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
	$langs->load('admin');
	$langs->load('bills');
	$langs->load('infraspackplus@infraspackplus');

	// Access control *******************************
	$accessright					= !empty($user->admin) || !empty($user->rights->infraspackplus->paramBkpRest) ? 2 : (!empty($user->rights->infraspackplus->paramMentions) ? 1 : 0);
	if (empty($accessright))		accessforbidden();

	// Actions **************************************
	$form							= new Form($db);
	$formfile						= new FormFile($db);
	$formother						= new FormOther($db);
	$confirm_mesg					= '';
	$errors							= array();
	$action							= GETPOST('action', 'alpha');
	$confirm						= GETPOST('confirm', 'alpha');
	$labelmention					= GETPOST('selmentions', 'alpha') ? GETPOST('selmentions', 'alpha') : 'BASE';
	$selmodule						= GETPOST('selmodules', 'alpha') ? GETPOST('selmodules', 'alpha') : 'PROPOSAL_FREE_TEXT';
	$variablename					= $labelmention == 'BASE' ? $selmodule : $selmodule.'_'.$labelmention;
	$result							= '';
	// Confirmations
	if ($action == 'confirm_TVAauto' && $confirm == 'yes') {
		$sql		= 'UPDATE '.MAIN_DB_PREFIX.'c_infraspackplus_mention AS im
						JOIN (SELECT "TVA_MICRO" AS code, '.(int) $conf->entity.' AS entity, 1 AS active
								UNION ALL SELECT "TVA_VSI", '.(int) $conf->entity.', 0
								UNION ALL SELECT "TVA_VSE", '.(int) $conf->entity.', 0
								UNION ALL SELECT "TVA_VPI", '.(int) $conf->entity.', 0
								UNION ALL SELECT "TVA_VPE", '.(int) $conf->entity.', 0
								UNION ALL SELECT "TVA_BTP", '.(int) $conf->entity.', 0
							) vals ON im.code = vals.code AND im.entity = vals.entity
						SET im.active = vals.active';
		$resql		= $db->query($sql);
		if ($resql)	$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_FREETEXT_TVA_AUTO', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		else {
			$errors[]	= $db->lasterror();
			$result		= -2;
		}
	}
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
		$list		= array('Notes'	=> array(),
							'Opt'	=> array('INFRASPLUS_PDF_FACTOR_PRE',
											'INFRASPLUS_PDF_FREETEXT_TVA_1',	'INFRASPLUS_PDF_FREETEXT_TVA_2',	'INFRASPLUS_PDF_FREETEXT_TVA_3',
											'INFRASPLUS_PDF_FREETEXT_TVA_4',	'INFRASPLUS_PDF_FREETEXT_TVA_5',	'INFRASPLUS_PDF_FREETEXT_TVA_6')
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'Notes')	$result	= dolibarr_set_const($db, $variablename, GETPOST($variablename, 'none'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}

	if ($result == 1)				setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	if ($result == -1)				setEventMessages($langs->trans('Error'), null, 'errors');
	if ($result == -2)				setEventMessages($langs->trans('Error'), $errors, 'errors');

	// init variables *******************************
	$listModules	= array (	'PROPOSAL_FREE_TEXT'			=> 'InfraSPlusParam_MAIN_MODULE_PROPALE',
								'ORDER_FREE_TEXT'				=> 'InfraSPlusParam_MAIN_MODULE_COMMANDE',
								'CONTRACT_FREE_TEXT'			=> 'InfraSPlusParam_MAIN_MODULE_CONTRAT',
								'SHIPPING_FREE_TEXT'			=> 'InfraSPlusParam_MAIN_MODULE_EXPEDITION',
								'DELIVERY_FREE_TEXT'			=> 'InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON',
								'FICHINTER_FREE_TEXT'			=> 'InfraSPlusParam_MAIN_MODULE_FICHEINTER',
								'INVOICE_FREE_TEXT'				=> 'InfraSPlusParam_MAIN_MODULE_FACTURE',
								'SUPPLIER_PROPOSAL_FREE_TEXT'	=> 'InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL',
								'SUPPLIER_ORDER_FREE_TEXT'		=> 'InfraSPlusParam_MAIN_MODULE_FOURNISSEUR',
								'PRODUCT_FREE_TEXT'				=> 'InfraSPlusParam_MAIN_MODULE_PRODUCT',
								'EXPENSEREPORT_FREE_TEXT'		=> 'InfraSPlusParam_MAIN_MODULE_EXPENSEREPORT'
							);
	$optionsSelect	= '';
	$country		= !empty($mysoc->country_code)						? $mysoc->country_code							: substr($langs->defaultlang, -2);
	$franchise		= $country == 'FR' && !$mysoc->tva_assuj			? 1												: 0;
	$confirm_mesg	= $franchise && empty($conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO) ? $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('InfraSPlusParamTVAauto'), $langs->trans('InfraSPlusParamConfirmSetTVAauto'), 'confirm_TVAauto', '', 'yes', 1) : '';
	$rowSpan2		= 7;
	foreach ($listModules as $module => $trans) {
		$constModuleName						= preg_replace('/InfraSPlusParam_/', '', $trans);
		if ($conf->global->$constModuleName)	$rowSpan2 ++;
	}
	$rowSpan2	+= $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO		? ($franchise ? 1 : 5)	: 0;
	$rowSpan2	+= $conf->global->INFRASPLUS_PDF_FREETEXT_FACTOR_AUTO	? 1						: 0;

	// View *****************************************
	$page_name					= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsMentions');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	if (! empty($user->admin))	$linkback	= '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head						= infraspackplus_admin_prepare_head();
	$picto						= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'mentions', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script type = "text/javascript" language = "javascript">
					function doReloadFreeT(){
						document.frm1.submit();
					}
				</script>';
	}
	print '		<form name="frm1" id="frm1" action="'.$_SERVER["PHP_SELF"].'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infraspackplus_print_backup_restore();
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamGestionMentions').'</FONT>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "noborder" width = "100%">';
	$metas	= array('*', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(3), 'InfraSPlusParamNewMention');
	infraspackplus_print_liste_titre($metas);
	if (! empty($accessright)) {
		infraspackplus_print_btn_action('Notes', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), 3, 2);
		print '			<tr class="oddeven">
							<td colspan = "2">';
		print $form->textwithpicto($langs->trans('InfraSPlusParamMention1'), $langs->trans('AddCRIfTooLong').'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'&nbsp;';
		print fieldLabel(''.$langs->trans('InfraSPlusParamMention2').'', 'selmodules').' '.
		$form->selectarray('selmodules', $listModules, $selmodule, 0, 0, 0, 'style = "padding: 0px; font-size: inherit; cursor: pointer;" onchange = "doReloadFreeT();"', 1, 0, 0, '', '');
		print select_infraspackplus_dict('c_infraspackplus_mention', $labelmention, 'selmentions', 0, 'doReloadFreeT()', 1);
		print info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'), 1, 1, 0);
		print '				</td>
						</tr>';
		print '			<tr>
							<td colspan = "2">';
		if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))	print '<textarea name="'.$variablename.'" class = "flat" cols = "120">'.$conf->global->$variablename.'</textarea>';
		else {
			$doleditor	= new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
			print $doleditor->Create();
		}
		print '				</td>
						</tr>';
		infraspackplus_print_final(2);
	}
	print '			</table>
				</form>
				<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamMentionsSetup').'</FONT>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
	print '			<table class = "noborder" width = "100%">';
	$metas	= array('30px', '*', '456px', '130px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (! empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Opt', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), $rowSpan2, 3);
		if ($conf->global->MAIN_MODULE_PROPALE)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PROPALE')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_COMMANDE)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_COM', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_COMMANDE')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_CONTRAT)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_CT', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_CONTRAT')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_EXPEDITION)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_EXP', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_EXPEDITION')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_SUBMODULE_LIVRAISON)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_REC', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_FICHEINTER)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FI', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FICHEINTER')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_FACTURE)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FAC', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FACTURE')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_SUPPLIERPROPOSAL)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV_FOU', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_FOURNISSEUR)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FOU', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FOURNISSEUR')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_PRODUCT)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_PROD', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PRODUCT')), '', array(), 1, 1, '', $num);
		else	$num++;
		if ($conf->global->MAIN_MODULE_EXPENSEREPORT)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_EXPR', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_EXPENSEREPORT')), '', array(), 1, 1, '', $num);
		else	$num++;
		infraspackplus_print_hr(3);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FREETEXTEND', 'on_off', $langs->trans('InfraSPlusParamFreeTextEnd'), '', array(), 1, 1, '', $num);
		infraspackplus_print_hr(3);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FREETEXT_FACTOR_AUTO', 'on_off', $langs->trans('InfraSPlusParamFreeTextFactorAuto'), '', array(), 1, 1, '', $num);
		if ($conf->global->INFRASPLUS_PDF_FREETEXT_FACTOR_AUTO)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTOR_PRE', 'input', $langs->trans('InfraSPlusParamFactorPrefix'), '', array(), 1, 1, '', $num);
		else	$num++;
		infraspackplus_print_hr(3);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FREETEXT_TVA_AUTO', 'on_off', $langs->trans('InfraSPlusParamFreeTextTVAauto'), ' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamFreeTextTVAautoHelp'), array(), 1, 1, '', $num);
		if ($conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO) {
			if ($franchise) {
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_1, 'INFRASPLUS_PDF_FREETEXT_TVA_1', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_1'), '', $metas, '1', '1');
				$num	+= 5;
			}
			else {
				$num++;
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_2, 'INFRASPLUS_PDF_FREETEXT_TVA_2', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_2'), '', $metas, 1, 1, '', $num);
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_3, 'INFRASPLUS_PDF_FREETEXT_TVA_3', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_3'), '', $metas, 1, 1, '', $num);
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_4, 'INFRASPLUS_PDF_FREETEXT_TVA_4', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_4'), '', $metas, 1, 1, '', $num);
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_5, 'INFRASPLUS_PDF_FREETEXT_TVA_5', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_5'), '', $metas, 1, 1, '', $num);
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_6, 'INFRASPLUS_PDF_FREETEXT_TVA_6', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_6'), '', $metas, 1, 1, '', $num);
			}
		}
		else	$num	+= 6;
	}
	print '			</table>
				</form>';
	dol_fiche_end();
	llxFooter();
?>