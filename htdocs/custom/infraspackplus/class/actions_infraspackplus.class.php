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
	* 	\file		../infraspackplus/class/actions_infraspackplus.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraS
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	/************************************************
	* Class infraspackplus
	************************************************/
	class Actionsinfraspackplus
	{
		public $results = array();	// @var array Hook results. Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $errors = array();	// @var array Errors

		/************************************************
		* Constructor
		*
		* @param   DATABASE		$db     db object
		* @return						void
		************************************************/
		public function __construct($db)
		{
			$this->db	= $db;
		}

		/************************************************
		* When login (../main.inc.php)
		*
		* @param   array()         $parameters     Hook metadatas (context, etc...)
		* @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param   string          &$action        Current action (if set). Generally create or edit or null
		* @return  int                             < 0 on error
		************************************************/
		public function afterLogin($parameters, &$object, &$action)
		{
			global $user;

			if ($user->admin)	infraspackplus_chgtVersions (1);	// tests upgrade of Dolibarr or InfraSPackPlus for core change
			infraspackplus_test_new_fields('infraspackplus');	// Check the database configuration
			return 0;
		}

		/************************************************
		* Table build to generate new document and to show linked objects (../core/class/html.formfile.class.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @return	void + string					$this->resprints HTML code to show
		************************************************/
		public function formBuilddocOptions($parameters, &$object, &$action)
		{
			global $conf, $db, $langs, $user;

			// Rigths control *******************************
			$InfraSPermLastOpt													= 0;
			if (!empty($user->admin))											$InfraSPermLastOpt	= 1;
			if (!empty($user->rights->infraspackplus->paramLastOpt))			$InfraSPermLastOpt	= 1;
			$discountAuto														= isset($conf->global->INFRASPLUS_PDF_DISCOUNT_AUTO) ? $conf->global->INFRASPLUS_PDF_DISCOUNT_AUTO : 0;;
			infraspackplus_chgtVersions (0);
			if ($object != null && method_exists($object, 'fetch_thirdparty'))	$object->fetch_thirdparty();
			if ($conf->milestone->enabled) {
				$reg	= '/else(.{1,6})\{(.{1,7})\$tab_top_newpage = \(empty\(\$conf->global->MAIN_PDF_DONOTREPEAT_HEAD/s';
				infraspackplus_test_module('milestone', '/class/actions_milestone.class.php', 'S', 'InfraSPackPlus_model', 'new.txt', 'R', $reg);
			}
			$path		= dol_buildpath('infraspackplus', 0);
			$urlpath	= dol_buildpath('infraspackplus', 1);
            // Colspan
            if ((float) DOL_VERSION < 14) {
                $colspan = 4;
                $colspanshort = 2;
            } else {
                $colspan = 5;
                $colspanshort = 3;
            }
			// Présentation générale des options, Récupération des paramètres sauvegardés
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'project', 'expensereport'))) {
				dolibarr_set_const($db, 'INFRASPLUS_PDF_DIRECT_PRINT',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);	// la demande d'impression est manuelle
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				$langs->load('infraspackplus@infraspackplus');
				infraspackplus_test_new_fields('infraspackplus');
				if($object -> element == 'facture')	$idvar	= 'facid';
				else								$idvar	= 'id';
				$urlTo								= '?'.$idvar.'='.$object->id;
				$ht_signarea						= (isset($conf->global->INFRASPLUS_PDF_HT_SIGN_AREA)			? $conf->global->INFRASPLUS_PDF_HT_SIGN_AREA			: 24) * 7.5;
				$signColor							= (isset($conf->global->INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR)	? $conf->global->INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR	: 0);
				$signColor							= '#'.colorArrayToHex(explode(',', $signColor));
				// Page JS to toggle some parameters
				$permHide							= $InfraSPermLastOpt ? '.infrasfoldable' : '.InfraSPermLastOpt';
				$permFoldFunction					= $InfraSPermLastOpt ? '$(".infrasfoldable").toggle();' : '';
				$js									= <<< EOJS
				function funcListSsT(id, val)
				{
					var listSsT = '?id=' + id + '&idSst=' + val;
					window.location.href = window.location.protocol + '//' + window.location.host + '/' + window.location.pathname + listSsT;
				}
				$(document).ready(function(){
					$('{$permHide}').hide();
				});
				$(function ()
				{
					$('.infrasfold').click(function ()
					{
						{$permFoldFunction}
					});
					$('a[rel=getClientSign]').click(function ()
					{
						$('#dialog-promptInfraSPlusSign').remove();
						var dialog_html = '	<div id = "dialog-promptInfraSPlusSign" style = "animation: drop-in 1s; animation-fill-mode: forwards;">';
						dialog_html += '		<div id = "signature"></div>';
						dialog_html += '	</div>';
						$('body').append(dialog_html);
						$("#signature").jSignature({'UndoButton':true
													, 'width': 600
													, 'height': {$ht_signarea}
													, 'decor-color': 'transparent'
													, 'color': '{$signColor}'
						})
						$( "#dialog-promptInfraSPlusSign" ).dialog({
							resizable: false,
							height: 'auto',
							width: 'auto',
							modal: true,
							title: "{$langs->trans("InfraSPlusSSignTitle")}",
	                        buttons: {
	                            "{$langs->trans('InfraSPlusSclearBtn')}": function() {
	                                $("#signature").jSignature("reset");
	                            },
	                            "{$langs->trans('Validate')}": function() {
									$("#signvalue").val($("#signature").jSignature("getData"));
                                    $(this).dialog("close");
	                            },
	                            "{$langs->trans('Cancel')}": function() {
	                                $(this).dialog("close");
	                            }
	                        }
						});
						$('.ui-widget-header').removeClass().addClass('liste_titre_bydiv infrasModal');
						$('.ui-dialog-title').addClass('infrasModalTitle');
						var buttons = $('.ui-dialog-buttonset').children('button');
						$(buttons[0]).removeClass().addClass('butActionDelete');
						$(buttons[1]).removeClass().addClass('button');
						$(buttons[2]).removeClass().addClass('butActionDelete');
					});
				});
EOJS;
				$userParamsKey	= 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_USER_'.$user->id;
				$objParamsKey	= 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_DOC_'.$object->id;
				$ParamLogoEmet	= isset($conf->global->INFRASPLUS_PDF_SET_LOGO_EMET_TIERS)	? $conf->global->INFRASPLUS_PDF_SET_LOGO_EMET_TIERS	: 0;
				$txtUserParams	= isset($conf->global->$userParamsKey)						? $conf->global->$userParamsKey						: '';
				$txtObjParams	= isset($conf->global->$objParamsKey)						? $conf->global->$objParamsKey						: '';
				if ($txtUserParams) {
					$userParams	= explode ('&', $txtUserParams);
					foreach ($userParams as $userParam) {
						$paramUser				= array ();
						parse_str($userParam,	$paramUser);
						$_POST[key($paramUser)]	= $paramUser[key($paramUser)];
					}	//foreach ($userParams as $userParam)
				}
				if ($txtObjParams) {
					$objParams	= explode ('&', $txtObjParams);
					foreach ($objParams as $objParam) {
						$paramObj				= array ();
						parse_str($objParam,	$paramObj);
						$_POST[key($paramObj)]	= $paramObj[key($paramObj)];
					}	//foreach ($objParams as $objParam)
				}
				// Titre des options InfraSPackPlus
				$titleOptions		= $langs->trans('PDFInfraSPlusOptions').'&nbsp;&nbsp;&nbsp;'.img_picto($langs->trans('Setup'), 'setup', 'style="vertical-align: bottom; height: 20px;"');
				$titleStyle			= 'background: transparent !important; background-color: rgba(148, 148, 148, .065) !important; cursor: pointer;';
				$this->resprints	= '	<!--[if lt IE 9]>
											<script type = "text/javascript" src = "'.$urlpath.'/includes/jsignature/flashcanvas.js"></script>
										<![endif]-->
										<script src = "'.$urlpath.'/includes/jsignature/jSignature.min.js"></script>
										<script src = "'.$urlpath.'/includes/jsignature/jSignature.UndoButton.js"></script>
										<script type = "text/javascript">'.$js.'</script>
										<tr class = "infrasfold" style = "'.$titleStyle.'"><td colspan = "'.$colspan.'" align = "center" style = "font-size: 120%; color: rgb(109,70,140);">'.$titleOptions.'</td></tr>';
			}
			// Logo et Adresse expéditeur, Mentions complémentaires + Image en pied de document
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'project', 'expensereport')))
			{
				$countryAddr		= isset($conf->global->INFRASPLUS_PDF_USE_CUSTOM_COUNTRY_ADDR)		? $conf->global->INFRASPLUS_PDF_USE_CUSTOM_COUNTRY_ADDR	: 0;
				$defaultimagefoot	= isset($conf->global->INFRASPLUS_PDF_IMAGE_FOOT)					? $conf->global->INFRASPLUS_PDF_IMAGE_FOOT				: '';
				$noMyLogo			= isset($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)					? $conf->global->PDF_DISABLE_MYCOMPANY_LOGO				: 0;
				$tvaAuto			= isset($conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO)			? $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO		: 0;
				$factor				= isset($conf->global->INFRASPLUS_PDF_FACTOR_PRE)					? $conf->global->INFRASPLUS_PDF_FACTOR_PRE				: '';
				$ntUsedAsCover		= isset($conf->global->INFRASPLUS_PDF_NT_USED_AS_COVER)				? $conf->global->INFRASPLUS_PDF_NT_USED_AS_COVER		: -1;
				$logos				= array();
				$logodir			= !empty($conf->mycompany->multidir_output[$object->entity])		? $conf->mycompany->multidir_output[$object->entity]	: $conf->mycompany->dir_output;
				foreach (glob($logodir.'/logos/{*.jpg,*.jpeg,*.gif,*.png}', GLOB_BRACE) as $file)		$logos[]												= dol_basename($file);
				$logoPost			= GETPOST('logo', 'alpha') == 'None'								? ''													: GETPOST('logo',		'alpha');
				$adrPost			= GETPOST('adrlb', 'alpha') == 'None'								? ''													: GETPOST('adrlb',		'alpha');
				$freeTPost			= GETPOST('listfreet', 'alpha') == 'None'							? ''													: GETPOST('listfreet',	'alpha');
				$notePPost			= GETPOST('listnotep', 'alpha') == 'None'							? ''													: GETPOST('listnotep',	'alpha');
				$piedPost			= GETPOST('pied', 'alpha') == 'None'								? ''													: GETPOST('pied',		'alpha');
				$filesPost			= GETPOST('filesArray', 'alpha') == 'None'							? ''													: GETPOST('filesArray',	'alpha');
				$disableLogo		= '';
				$optionNoLogo		= '';
				$selected_logo		= $ParamLogoEmet													? infraspackplus_getLogoEmet($object->thirdparty->id)	: $logoPost;
				$selected_adr		= !empty($countryAddr) && !empty($object->thirdparty->country_code)	? $object->thirdparty->country_code						: $adrPost;
				$selected_freeT		= explode ('-', $freeTPost);
				$selected_noteP		= explode ('-', $notePPost);
				$selected_pied		= $piedPost;
				$selected_Files		= explode ('-', $filesPost);
				// logo
				if (!empty($noMyLogo)) {
					$disableLogo	= 'disabled';
					$optionNoLogo	= '<option name = "logo" value = "" selected>&nbsp;</option>';
					$selected_logo	= '';
				}
				$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "logo">'.$langs->trans('PDFInfraSPlusLogo').'</label>&nbsp;
												<select class = "flat" name = "logo" style = "width: 200px; cursor: pointer;" '.$disableLogo.'>
													<option name = "logo" value = "">'.$langs->trans('PDFInfraSPlusDefaultLogo').'</option>';
				$this->resprints	.= $optionNoLogo;
				for ($i = 0; $i < count($logos); $i++) {
					$this->resprints					.= '<option name = "logo" value = "'.$logos[$i].'"';
					if ($selected_logo === $logos[$i])	$this->resprints	.= ' selected';
					$this->resprints					.= '>'.$logos[$i].'</option>';
				}
				$this->resprints	.=			'</select>
											</td>
										</tr>';
				unset($i);
				// adresse expéditeur
				if (!in_array($object->element, array('product', 'project'))) {
					$sql_adr		= 'SELECT DISTINCT label, fk_soc';
					$sql_adr		.= ' FROM '.MAIN_DB_PREFIX.'societe_address';
					$sql_adr		.= ' WHERE fk_soc = "0"';
					$sql_adr		.= ' AND entity = '.$conf->entity;
					$resultat_adr	= $db->query($sql_adr);
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_adr = '.$sql_adr);
					$this->resprints	.=	'<tr class="oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "adr">'.$langs->trans('PDFInfraSPlusAddress').'</label>&nbsp;
													<select class = "flat" name = "adr" style = "width: 200px; cursor: pointer;">
														<option name = "adr" value = "">'.$langs->trans('PDFInfraSPlusDefaultAddress').'</option>';
					if ($resultat_adr) {
						$obj_adr	= array();
						$num		= $db->num_rows($resultat_adr);
						for ($i = 0; $i < $num; $i++)
						{
							$obj_adr			= $db->fetch_array($resultat_adr);
							$this->resprints	.= '	<option name = "adrlivr" value = "'.$obj_adr['label'].'" '.($selected_adr === $obj_adr['label'] ? ' selected' : '').'>'.$obj_adr['label'].'</option>';
						}
					}
					else	dol_print_error($db);
					$this->resprints	.=			'</select>
												</td>
											</tr>';
					$db->free($resultat_adr);
					unset($i);
				}
				// Mentions complémentaires
                $listModules	= array (	array ('propal',			'PROPOSAL_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV'),
											array ('commande',			'ORDER_FREE_TEXT',				'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_COM'),
											array ('contrat',			'CONTRACT_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_CT'),
											array ('shipping',			'SHIPPING_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_EXP'),
											array ('delivery',			'DELIVERY_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_REC'),
											array ('fichinter',			'FICHINTER_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FI'),
											array ('facture',			'INVOICE_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FAC'),
											array ('supplier_proposal',	'SUPPLIER_PROPOSAL_FREE_TEXT',	'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV_FOU'),
											array ('order_supplier',	'SUPPLIER_ORDER_FREE_TEXT',		'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FOU'),
											array ('product',			'PRODUCT_FREE_TEXT',			''),
											array ('project',			'PROJECT_FREE_NOTE',			''),
											array ('expensereport',		'EXPENSEREPORT_FREE_TEXT',		'')
										);
				$rootfreetext	= '';
				$showsysmcbase	= '';
				foreach ($listModules as $module) {
					if ($object->element == $module[0]) {
						$rootfreetext	= $module[1];
						$showsysmcbase	= $module[2];
						$showsysmcbase	= isset($conf->global->$showsysmcbase) ? $conf->global->$showsysmcbase : '';
						break;
					}
				}
				if ($rootfreetext) {
					$sql_freeT	= 'SELECT c.name, c.value, d.libelle, d.pos';
					$sql_freeT	.= ' FROM '.MAIN_DB_PREFIX.'const AS c';
					$sql_freeT	.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_infraspackplus_mention AS d ON d.code = REPLACE(c.name, "'.$rootfreetext.'_", "")';
					$sql_freeT	.= ' WHERE c.name LIKE "'.$rootfreetext.'%"';
					$sql_freeT	.= $rootfreetext == 'INVOICE_FREE_TEXT' && $tvaAuto ? ' AND d.code NOT LIKE "TVA\_%"' : '';
					$sql_freeT	.= $rootfreetext == 'INVOICE_FREE_TEXT' && $factor ? ' AND d.code NOT LIKE "'.$factor.'%"' : '';
					if ($showsysmcbase)
					{
						$sql_freeT			.= ' AND c.name NOT LIKE "'.$rootfreetext.'"';
						$this->resprints	.= '<input type = "hidden" name = "showsysmcbase" value = "'.$rootfreetext.'">';
					}
					$sql_freeT		.= ' AND c.entity = "'.$conf->entity.'"';
                    $sql_freeT		.= ' ORDER BY d.pos ASC';
					$result_freeT	= $db->query($sql_freeT);
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_freeT = '.$sql_freeT);
				}
				if ($result_freeT) {
					$num	= $db->num_rows($result_freeT);
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions num_rows($result_freeT) = '.$num);
					for ($i = 0; $i < $num; $i++) {
						$objFreeT		= $db->fetch_object($result_freeT);
						if ($objFreeT)	$listFreeT[$objFreeT->name] = array('id' => $objFreeT->name, 'fulllabel' => ($objFreeT->libelle ? $objFreeT->libelle : $langs->trans('PDFInfraSPlusMentionsBase')));
					}
					$db->free($result_freeT);
					unset($i);
				}
				else	dol_print_error($db);
				$arrayFreeT	= array ();
				if (is_array($listFreeT) && count($listFreeT)) {
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions count($listFreeT) = '.count($listFreeT));
					foreach($listFreeT as $key => $value)	$arrayFreeT[$listFreeT[$key]['id']] = $listFreeT[$key]['fulllabel'];
					$form									= new Form($db);
					$this->resprints						.=	'<tr class="oddeven infrasfoldable InfraSPermLastOpt">
																	<td colspan = "'.$colspan.'" align = "right">
																		<label for = "listfreet">'.$langs->trans('PDFInfraSPlusMentions').'</label>&nbsp;';
					$this->resprints						.= $form->multiselectarray('listfreet', $arrayFreeT, $selected_freeT, '', 0, '', 0, '400px');
					$this->resprints						.=		'</td>
																</tr>';
				}
				// Notes publiques standards
				$listModules	= array (	array ('propal',			'PROPOSAL_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV'),
											array ('commande',			'ORDER_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_COM'),
											array ('contrat',			'CONTRACT_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_CT'),
											array ('shipping',			'SHIPPING_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_EXP'),
											array ('delivery',			'DELIVERY_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_REC'),
											array ('fichinter',			'FICHINTER_PUBLIC_NOTE',			'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FI'),
											array ('facture',			'INVOICE_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FAC'),
											array ('supplier_proposal',	'SUPPLIER_PROPOSAL_PUBLIC_NOTE',	'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV_FOU'),
											array ('order_supplier',	'SUPPLIER_ORDER_PUBLIC_NOTE',		'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FOU'),
											array ('product',			'PRODUCT_PUBLIC_NOTE',				''),
											array ('project',			'PROJECT_PUBLIC_NOTE',				''),
											array ('expensereport',		'EXPENSEREPORT_PUBLIC_NOTE',		'')
										);
				$rootnotepub	= '';
				$showsysntbase	= '';
				foreach ($listModules as $module) {
					if ($object->element == $module[0]) {
						$rootnotepub	= $module[1];
						$showsysntbase	= $module[2];
						$showsysntbase	= isset($conf->global->$showsysntbase) ? $conf->global->$showsysntbase : '';
						break;
					}
				}
				if ($rootnotepub) {
					$sql_noteP	= 'SELECT c.name, c.value, d.libelle, d.pos';
					$sql_noteP	.= ' FROM '.MAIN_DB_PREFIX.'const AS c';
					$sql_noteP	.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_infraspackplus_note AS d ON d.code = REPLACE(c.name, "'.$rootnotepub.'_", "")';
					$sql_noteP	.= ' WHERE c.name LIKE "'.$rootnotepub.'%"';
					if ($showsysntbase) {
						$sql_noteP			.= ' AND c.name NOT LIKE "'.$rootnotepub.'"';
						$this->resprints	.= '<input type = "hidden" name = "showsysntbase" value = "'.$rootnotepub.'">';
					}
					if ($ntUsedAsCover != -1) {
						$sql_noteP			.= ' AND c.name NOT LIKE "'.$rootnotepub.'_'.$ntUsedAsCover.'"';
						$this->resprints	.= '<input type = "hidden" name = "showntusedascover" value = "'.$rootnotepub.'_'.$ntUsedAsCover.'">';
					}
					$sql_noteP		.= ' AND c.entity = "'.$conf->entity.'"';
					$sql_noteP		.= ' ORDER BY d.pos ASC';
					$result_noteP	= $db->query($sql_noteP);
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_noteP = '.$sql_noteP);
				}
				if ($result_noteP) {
					$num	= $db->num_rows($result_noteP);
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions num_rows($result_noteP) = '.$num);
					for ($i = 0; $i < $num; $i++)
					{
						$objNoteP		= $this->db->fetch_object($result_noteP);
						if ($objNoteP)	$listNoteP[$objNoteP->name] = array('id'=>$objNoteP->name, 'fulllabel'=>($objNoteP->libelle ? $objNoteP->libelle : $langs->trans('PDFInfraSPlusNotesBase')));
					}
					$db->free($result_noteP);
					unset($i);
				}
				else	dol_print_error($db);
				$arrayNoteP	= array ();
				if (is_array($listNoteP) && count($listNoteP)) {
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions count($listNoteP) = '.count($listNoteP));
					foreach($listNoteP as $key => $value)	$arrayNoteP[$listNoteP[$key]['id']] = $listNoteP[$key]['fulllabel'];
					$form									= new Form($db);
					$this->resprints						.=	'<tr class="oddeven infrasfoldable InfraSPermLastOpt">
																	<td colspan = "'.$colspan.'" align = "right">
																		<label for = "listnotep">'.$langs->trans('PDFInfraSPlusNotes').'</label>&nbsp;';
					$this->resprints						.= $form->multiselectarray('listnotep', $arrayNoteP, $selected_noteP, '', 0, '', 0, '400px');
					$this->resprints						.=		'</td>
																</tr>';
				}
				// Image en pied de document
				$this->resprints	.=	'<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "pied">'.$langs->trans('PDFInfraSPlusPied').'</label>&nbsp;
												<select class = "flat" name = "pied" style = "width: 200px; cursor: pointer;">
													<option name = "pied" value = "'.$defaultimagefoot.'">'.$langs->trans('PDFInfraSPlusDefaultPied').'</option>';
				for ($i = 0; $i < count($logos); $i++) {
					if ($logos[$i] === 'thumbs' || $logos[$i][0] === '.')	continue;	// Choose to ignore field which isn't image
					$this->resprints					.= '<option name = "pied" value = "'.$logos[$i].'"';
					if ($selected_pied === $logos[$i])	$this->resprints	.= ' selected';
					$this->resprints					.= '>'.$logos[$i].'</option>';
				}
				$this->resprints	.=			'</select>
											</td>
										</tr>';
				unset($i);
			}
			// Adresse de livraison (client)
			if (in_array($object->element, array('propal', 'commande', 'facture', 'fichinter', 'shipping', 'delivery'))) {
				$def_adrfact	= isset($conf->global->INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT)		? $conf->global->INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT		: 'Vide';
				$addrLivrSiFact	= isset($conf->global->INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SI_FACT)	? $conf->global->INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SI_FACT	: 0;
				$useDoliAddr	= isset($conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON)	? $conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON	: 0;
				$showadrlivr	= isset($conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION)		? $conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION		: 0;
				$freeadrlivr	= isset($conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF)				? $conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF				: '';
				if (!empty($useDoliAddr) || !empty($object->array_options['options_'.$freeadrlivr])) {
					$showadrlivr			= 0;
					$_POST['labeladrlivr']	= '';
				}
				in_array($object->element, array('fichinter')) ? $typeadr = $langs->trans('PDFInfraSPlusAdrInter') : $typeadr = $langs->trans('PDFInfraSPlusAdrLivr');
				$sel_adrliv		= GETPOST('labeladrlivr', 'alpha') && GETPOST('labeladrlivr', 'alpha') != 'None' ? GETPOST('labeladrlivr', 'alpha') : ($addrLivrSiFact && $def_adrfact != 'Vide' ? 'Default' : 'None');
				if ($showadrlivr) {
					$sql_adrlivr		= 'SELECT DISTINCT label, fk_soc';
					$sql_adrlivr		.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
					$sql_adrlivr		.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON sa.fk_soc = s.rowid';
					$sql_adrlivr		.= ' WHERE s.rowid = '.$object->thirdparty->id;
					$sql_adrlivr		.= ' AND sa.entity = '.$conf->entity;
					$resultat_adrlivr	= $db->query($sql_adrlivr);
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_adrlivr = '.$sql_adrlivr);
					$this->resprints	.= '<tr class="oddeven InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "adrlivr">'.$typeadr.'</label>&nbsp;
													<select class = "flat" name = "adrlivr" style = "width: 200px; cursor: pointer;">
														<option name = "adrlivr" value = "None"'.($sel_adrliv === 'None' ? ' selected' : '').'>&nbsp;</option>
														<option name = "adrlivr" value = "Default"'.($sel_adrliv === 'Default' ? ' selected' : '').'>'.$langs->trans('PDFInfraSPlusBaseAddress').'</option>';
					if ($resultat_adrlivr) {
						$obj_adrlivr	= array();
						$num			= $db->num_rows($resultat_adrlivr);
						for ($i = 0; $i < $num; $i++) {
							$obj_adrlivr		= $db->fetch_array($resultat_adrlivr);
							$this->resprints	.= '	<option name = "adrlivr" value = "'.$obj_adrlivr['label'].'" '.($sel_adrliv === $obj_adrlivr['label'] ? ' selected' : '').'>'.$obj_adrlivr['label'].($def_adrlivr === $obj_adrlivr['label'] ? $this->resprints	.= ' '.$langs->trans('PDFInfraSPlusDefaultAddressLivr') : '').'</option>';
						}
					}
					else	dol_print_error($db);
					$this->resprints	.=			'</select>
												</td>
											</tr>';
					$db->free($resultat_adrlivr);
					unset($i);
				}
			}
			// Sous-Traitant (lié au client via "CustomLink")
			if ($conf->global->MAIN_MODULE_CUSTOMLINK && in_array($object->element, array('commande', 'shipping', 'delivery'))) {
				$showadrSsT	= isset($conf->global->INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT)	? $conf->global->INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT	: 0;
				$typeCtSsT	= isset($conf->global->INFRASPLUS_PDF_TYPE_SOUS_TRAITANT)		? $conf->global->INFRASPLUS_PDF_TYPE_SOUS_TRAITANT		: '';
				$doc_id		= GETPOST('id', 'int');
				$sel_SsT	= GETPOST('idSst', 'alpha')		? GETPOST('idSst', 'alpha')		: 'None';
				$sel_adrSst	= GETPOST('idadrSst', 'alpha')	? GETPOST('idadrSst', 'alpha')	: 'None';
				if ($showadrSsT && $typeCtSsT) {
					$sql_listSsT		= 'SELECT DISTINCT s.rowid, s.nom';
					$sql_listSsT		.= ' FROM '.MAIN_DB_PREFIX.'socpeople AS sp';
					$sql_listSsT		.= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact AS ec ON sp.rowid = ec.fk_socpeople';
					$sql_listSsT		.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = sp.fk_soc';
					$sql_listSsT		.= ' WHERE ec.element_id = '.$object->thirdparty->id.' AND ec.fk_c_type_contact = '.$typeCtSsT;
					$resultat_listSsT	= $db->query($sql_listSsT);
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_listSsT = '.$sql_listSsT);
					if ($resultat_listSsT && $db->num_rows($resultat_listSsT) > 0) {
						$ar_listSsT			= array();
						$num_SsT			= $db->num_rows($resultat_listSsT);
						$this->resprints	.= '<tr class="oddeven InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "idSst">'.$langs->trans('PDFInfraSPlusListSsT').'</label>&nbsp;
														<select class = "flat" name = "idSst" style = "width: 200px; cursor: pointer;" onchange="funcListSsT('.$doc_id.', this.value)">';
						if ($num_SsT > 1)	$this->resprints	.= '<option name = "idSst" value = "None"'.($sel_SsT === 'None' ? ' selected' : '').'>&nbsp;</option>';
						for ($i = 0; $i < $num_SsT; $i++) {
							$ar_listSsT		= $db->fetch_array($resultat_listSsT);
							$this->resprints	.= '		<option name = "idSst" value = "'.$ar_listSsT['rowid'].'"'.($sel_SsT === $ar_listSsT['rowid'] ? ' selected' : '').'>'.$ar_listSsT['nom'].'</option>';
						}
						$this->resprints	.=			'</select>
													</td>
												</tr>';
						if ($sel_SsT !== 'None' || $num_SsT == 1) {
							$idCustomer			= $num_SsT > 1 ? $sel_SsT : $ar_listSsT['rowid'];
							$sql_adrSsT			= 'SELECT DISTINCT sa.rowid, sa.label, sa.fk_soc';
							$sql_adrSsT			.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
							$sql_adrSsT			.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON sa.fk_soc = s.rowid';
							$sql_adrSsT			.= ' WHERE s.rowid = '.$idCustomer;
							$sql_adrSsT			.= ' AND sa.entity = '.$conf->entity;
							$resultat_adrSsT	= $db->query($sql_adrSsT);
							dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_adrSsT = '.$sql_adrSsT);
							if ($resultat_adrSsT) {
								$ar_adrSsT			= array();
								$num				= $db->num_rows($resultat_adrSsT);
								$this->resprints	.= '<tr class="oddeven InfraSPermLastOpt">
															<td colspan = "'.$colspan.'" align = "right">
																<label for = "idadrSst">'.$langs->trans('PDFInfraSPlusAdrSsT').'</label>&nbsp;
																<select class = "flat" name = "idadrSst" style = "width: 200px; cursor: pointer;">
																	<option name = "idadrSst" value = "None"'.($sel_adrSst === 'None' ? ' selected' : '').'>&nbsp;</option>
																	<option name = "idadrSst" value = "Default,'.$idCustomer.'" '.($sel_adrSst === 'Default' ? ' selected' : '').'>'.$langs->trans('PDFInfraSPlusBaseAddress').'</option>';
								for ($j = 0; $j < $num; $j++) {
									$ar_adrSsT			= $db->fetch_array($resultat_adrSsT);
									$this->resprints	.= '		<option name = "idadrSst" value = "'.$ar_adrSsT['rowid'].','.$ar_adrSsT['fk_soc'].'" '.($sel_adrSst === $ar_adrSsT['rowid'] ? ' selected' : '').'>'.$ar_adrSsT['label'].'</option>';
								}
								$this->resprints	.=			'</select>
															</td>
														</tr>';
							}
							else	dol_print_error($db);
							$db->free($resultat_adrSsT);
							unset($j);
						}
					}
					else	dol_print_error($db);
					$db->free($resultat_listSsT);
					unset($i);
				}
			}
			// Adresse de livraison spéciale fournisseur (interne ou interne + client)
			if (in_array($object->element, array('supplier_proposal', 'order_supplier'))) {
				$useDoliAddr	= isset($conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON)	? $conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON	: 0;
				$showadrlivr	= isset($conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON)		? $conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON		: 0;
				$def_adrlivr	= isset($conf->global->INFRASPLUS_PDF_DEFAULT_ADDR_DELIV)			? $conf->global->INFRASPLUS_PDF_DEFAULT_ADDR_DELIV			: '';
				$adrlivrcli		= isset($conf->global->INFRASPLUS_PDF_ADRESSE_LIVRAISON_MIXTE)		? $conf->global->INFRASPLUS_PDF_ADRESSE_LIVRAISON_MIXTE		: 0;
				$sel_adrliv		= GETPOST('adrlivr', 'alpha') ? GETPOST('adrlivr', 'alpha') : $def_adrlivr;
				$freeadrlivr	= isset($conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF)				? $conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF				: '';
				if (!empty($useDoliAddr) || !empty($object->array_options['options_'.$freeadrlivr])) {
					$showadrlivr		= 0;
					$_POST['adrlivr']	= '';
				}
				if ($showadrlivr) {
					if (! $adrlivrcli) {
						$sql_adrlivr		= 'SELECT DISTINCT label, fk_soc';
						$sql_adrlivr		.= ' FROM '.MAIN_DB_PREFIX.'societe_address';
						$sql_adrlivr		.= ' WHERE fk_soc = "0"';
						$sql_adrlivr		.= ' AND entity IN ('.getEntity('societe_address').')';
						$resultat_adrlivr	= $db->query($sql_adrlivr);
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions::supplier sql_adrlivr = '.$sql_adrlivr);
                        $this->resprints	.= '<tr class="oddeven InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" class = "right">
														<label for = "adrlivr">'.$langs->trans('PDFInfraSPlusAdrLivr').'</label>&nbsp;
														<select class = "flat" name = "adrlivr" style = "width: 200px; cursor: pointer;">
															<option name = "adrlivr" value = "Vide"'.($sel_adrliv === 'Vide' ? ' selected' : '').'>&nbsp;</option>
															<option name = "adrlivr" value = "Default"'.($sel_adrliv === 'Default' ? ' selected' : '').'>'.$langs->trans('PDFInfraSPlusBaseAddress').'</option>';
						if ($resultat_adrlivr) {
							$obj_adrlivr	= array();
							$num			= $db->num_rows($resultat_adrlivr);
							for ($i = 0; $i < $num; $i++) {
								$obj_adrlivr		= $db->fetch_array($resultat_adrlivr);
								$this->resprints	.= '	<option name = "adrlivr" value = "'.$obj_adrlivr['label'].($sel_adrliv === $obj_adrlivr['label'] ? '" selected' : '"').'>'.($def_adrlivr === $obj_adrlivr['label'] ? $obj_adrlivr['label'].' '.$langs->trans('PDFInfraSPlusDefaultAddressLivr') : $obj_adrlivr['label']).'</option>';
							}
						}
						else	dol_print_error($db);
						$this->resprints	.=			'</select>
													</td>
												</tr>';
						$db->free($resultat_adrlivr);
						unset($i);
					}
					else {
						$sql_adrlivr		.= 'SELECT * FROM';
						$sql_adrlivr		.= ' (';
						$sql_adrlivr		.= '	(SELECT sa.label, sa.name, s.nom, sa.town';
						$sql_adrlivr		.= '	 FROM '.MAIN_DB_PREFIX.'societe AS s';
						$sql_adrlivr		.= '	 LEFT JOIN '.MAIN_DB_PREFIX.'societe_address AS sa ON s.rowid = sa.fk_soc AND sa.fk_soc IS NULL AND sa.entity IN ('.getEntity('societe_address').')';
						$sql_adrlivr		.= '	 WHERE s.client <> "0"';
						$sql_adrlivr		.= '	 AND s.entity IN ('.getEntity('societe').')';
						$sql_adrlivr		.= ' )'; // Clients - adresse principale
						$sql_adrlivr		.= ' 	UNION';
						$sql_adrlivr		.= '	(SELECT sa.label, sa.name, s.nom, sa.town';
						$sql_adrlivr		.= '	 FROM '.MAIN_DB_PREFIX.'societe AS s';
						$sql_adrlivr		.= '	 RIGHT JOIN '.MAIN_DB_PREFIX.'societe_address AS sa ON s.rowid = sa.fk_soc AND sa.entity IN ('.getEntity('societe_address').')';
                        $sql_adrlivr		.= '	 WHERE s.client <> "0"';
                        $sql_adrlivr		.= '	 AND s.entity IN ('.getEntity('societe').')';
                        $sql_adrlivr		.= ' )'; // Clients - adresses secondaires
						$sql_adrlivr		.= '	UNION';
						$sql_adrlivr		.= '	(SELECT sa.label, sa.name, s.nom, sa.town';
						$sql_adrlivr		.= '	 FROM '.MAIN_DB_PREFIX.'societe AS s';
						$sql_adrlivr		.= '	 RIGHT JOIN '.MAIN_DB_PREFIX.'societe_address AS sa ON s.rowid = sa.fk_soc AND sa.entity IN ('.getEntity('societe_address').')';
						$sql_adrlivr		.= '	 WHERE sa.fk_soc = "0")'; // Interne - adresses secondaires
						$sql_adrlivr		.= ' ) AS dt';
						$sql_adrlivr		.= ' ORDER BY dt.nom';
						$resultat_adrlivr	= $db->query($sql_adrlivr);
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions::supplier sql_adrlivr = '.$sql_adrlivr);
						$this->resprints	.= '<tr>
													<td colspan = "2" class = "right">'.$langs->trans('PDFInfraSPlusAdrLivr').'</td>
													<td colspan = "'.$colspanshort.'" class = "right">
														<link rel = "stylesheet" href = "'.$path.'/includes/awesomplete/awesomplete.css" />
														<script type = "text/javascript" src = "'.$path.'/includes/awesomplete/awesomplete.js" async></script>
														<input class = "flat awesomplete" style = "width: 270px !important" name = "adrlivr" list = "listadrlivr"/>
														<datalist id = "listadrlivr">
															<option>Vide</option>
															<option>Default</option>';
						if ($resultat_adrlivr) {
							$obj_adrlivr	= array();
							$num			= $db->num_rows($resultat_adrlivr);
							for ($i = 0; $i < $num; $i++) {
								$obj_adrlivr		= $db->fetch_array($resultat_adrlivr);
								$errors[]			= preg_match('( \_ | \/ )', $obj_adrlivr['nom'].$obj_adrlivr['label'].$obj_adrlivr['name']) ? $obj_adrlivr['nom'] : '' ;
								$adrlivr			= ! $obj_adrlivr['nom'] ? 'Interne _ '.$obj_adrlivr['label'].' / '.$obj_adrlivr['name'].' / '.$obj_adrlivr['town'] : 'Client _ '.$obj_adrlivr['nom'].($obj_adrlivr['label'] ? ' / '.$obj_adrlivr['label'].' / '.$obj_adrlivr['name'].' / '.$obj_adrlivr['town'] : '');
								$this->resprints	.= '	<option>'.$adrlivr.'</option>';
							}
							foreach($errors as $error)	$error ? setEventMessages($langs->trans('PDFInfraSPlusErrName', $error), null, 'warnings') : '';
						}
						else	dol_print_error($db);
						$this->resprints	.=			'</datalist>
													</td>
												</tr>';
						$db->free($resultat_adrlivr);
						unset($i);
					}
				}
			}
			// Conditions générales
			if (!empty($user->rights->infraspackplus->paramCGV)) {
				$CGbyLang	= $conf->global->MAIN_MULTILANGS && $conf->global->INFRASPLUS_PDF_CGV_FROM_LANG && $object->thirdparty->default_lang	? 1 : 0;
				// CGV
				if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat'))) {
					if (! GETPOST('cgv', 'alpha')) {
						$cgvbydef	= 0;
						$cgvbydef	+= (in_array($object->element, array('propal')) && $conf->global->INFRASPLUS_PDF_CGV_BY_DEF_FOR_PROPOSALS)	? 1 : 0;
						$cgvbydef	+= (in_array($object->element, array('commande')) && $conf->global->INFRASPLUS_PDF_CGV_BY_DEF_FOR_ORDERS)	? 1 : 0;
						$cgvbydef	+= (in_array($object->element, array('facture')) && $conf->global->INFRASPLUS_PDF_CGV_BY_DEF_FOR_INVOICES)	? 1 : 0;
						$cgvbydef	+= (in_array($object->element, array('contrat')) && $conf->global->INFRASPLUS_PDF_CGV_BY_DEF_FOR_CONTRACTS)	? 1 : 0;
						$cgvbydef	= $cgvbydef > 0 ? (isset($conf->global->INFRASPLUS_PDF_CGV) ? $conf->global->INFRASPLUS_PDF_CGV : '') : '';
					}
					else	$cgvbydef	= GETPOST('cgv', 'alpha') == 'None'	? 0 : GETPOST('cgv', 'alpha');
					$CGVs	= infraspackplus_get_CGfiles ('CGV', $object->entity, $object);
					if (count($CGVs) > 0) {
						$cgvbydef			= $cgvbydef && $CGbyLang ? infraspackplus_get_CGfiles_lang ($CGVs, $object->thirdparty->default_lang) : $cgvbydef;
						$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "cgv">'.$langs->trans('PDFInfraSPlusCGVchk').'</label>&nbsp;
														<select class = "flat" name = "cgv" style = "width: 200px; cursor: pointer;">
															<option name = "cgv" value = "">'.$langs->trans('InfraSPlusParamNoCGV').'</option>';
						for ($i = 0; $i < count($CGVs); $i++)
							$this->resprints	.=	'		<option name = "cgv" value = "'.$CGVs[$i].($cgvbydef === $CGVs[$i] ? '" selected' : '"').'>'.$CGVs[$i].'</option>';
						$this->resprints	.=			'</select>
													</td>
												</tr>';
						unset($i);
					}
				}
				// CGI
				if (in_array($object->element, array('fichinter'))) {
					if (! GETPOST('cgi', 'alpha'))	$cgibydef	= isset($conf->global->INFRASPLUS_PDF_CGI) ? $conf->global->INFRASPLUS_PDF_CGI : '';
					else							$cgibydef	= GETPOST('cgi', 'alpha') == 'None'	? 0 : GETPOST('cgi', 'alpha');
					$CGIs							= infraspackplus_get_CGfiles ('CGI', $object->entity, $object);
					if (count($CGIs) > 0) {
						$cgibydef			= $cgibydef && $CGbyLang ? infraspackplus_get_CGfiles_lang ($CGIs, $object->thirdparty->default_lang) : $cgibydef;
						$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "cgi">'.$langs->trans('PDFInfraSPlusCGIchk').'</label>&nbsp;
														<select class = "flat" name = "cgi" style = "width: 200px; cursor: pointer;">
															<option name = "cgi" value = "">'.$langs->trans('InfraSPlusParamNoCGI').'</option>';
						for ($i = 0; $i < count($CGIs); $i++)
							$this->resprints	.=	'		<option name = "cgi" value = "'.$CGIs[$i].($cgibydef === $CGIs[$i] ? '" selected' : '"').'>'.$CGIs[$i].'</option>';
						$this->resprints	.=			'</select>
													</td>
												</tr>';
						unset($i);
					}
				}
				// CGA
				if (in_array($object->element, array('supplier_proposal', 'order_supplier'))) {
					if (! GETPOST('cga', 'alpha')) {
						$cgabydef	= 0;
						$cgabydef	+= (in_array($object->element, array('supplier_proposal')) && $conf->global->INFRASPLUS_PDF_CGA_BY_DEF_FOR_PROPOSALS) ? 1 : 0;
						$cgabydef	+= (in_array($object->element, array('order_supplier')) && $conf->global->INFRASPLUS_PDF_CGA_BY_DEF_FOR_ORDERS) ? 1 : 0;
						$cgabydef	= $cgabydef > 0 ? (isset($conf->global->INFRASPLUS_PDF_CGA) ? $conf->global->INFRASPLUS_PDF_CGA : '') : '';
					}
					else	$cgabydef	= GETPOST('cga', 'alpha') == 'None'	? 0 : GETPOST('cga', 'alpha');
					$CGAs	= infraspackplus_get_CGfiles ('CGA', $object->entity, $object);
					if (count($CGAs) > 0) {
						$cgabydef			= $cgabydef && $CGbyLang ? infraspackplus_get_CGfiles_lang ($CGAs, $object->thirdparty->default_lang) : $cgabydef;
						$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
													<td "'.$colspan.'" align = "right">
														<label for = "cga">'.$langs->trans('PDFInfraSPlusCGAchk').'</label>&nbsp;
														<select class = "flat" name = "cga" style = "width: 200px; cursor: pointer;">
															<option name = "cga" value = "">'.$langs->trans('InfraSPlusParamNoCGA').'</option>';
						for ($i = 0; $i < count($CGAs); $i++)
							$this->resprints	.=	'		<option name = "cga" value = "'.$CGAs[$i].($cgabydef === $CGAs[$i] ? '" selected' : '"').'>'.$CGAs[$i].'</option>';
						$this->resprints	.=			'</select>
													</td>
												</tr>';
						unset($i);
					}
				}
			}
			// Fichiers joints à fusionner
			if (! in_array($object->element, array('product'))) {
				$entity_dir											= $conf->entity > 1 ? $conf->entity.'/' : '';
				if ($object->element == 'propal')					$upload_dir	= $entity_dir.'propale/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'commande')				$upload_dir	= $entity_dir.'commande/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'facture')				$upload_dir	= $entity_dir.'facture/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'contrat')				$upload_dir	= $entity_dir.'contract/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'shipping')				$upload_dir	= $entity_dir.'expedition/sending/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'fichinter')			$upload_dir	= $entity_dir.'ficheinter/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'order_supplier')		$upload_dir	= $entity_dir.'fournisseur/commande/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'supplier_proposal')	$upload_dir	= $entity_dir.'supplier_proposal/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'project')				$upload_dir	= $entity_dir.'projet/'.dol_sanitizeFileName($object->ref);
				elseif ($object->element == 'expensereport')		$upload_dir	= $entity_dir.'expensereport/'.dol_sanitizeFileName($object->ref);
				$filesArray											= dol_dir_list_in_database($upload_dir, '(\.pdf)$', '');
				$filesFromProject									= isset($conf->global->INFRASPLUS_PDF_FILES_FROM_PROJECT)	? $conf->global->INFRASPLUS_PDF_FILES_FROM_PROJECT	: 0;
				if ($filesFromProject && $object->element != 'project') {
					require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
					$projectstatic	= new Project($db);
					$projectstatic->fetch($object->fk_project);
					$upload_dir2	= 'projet/'.dol_sanitizeFileName($projectstatic->ref);
					$filesArray2	= dol_dir_list_in_database($upload_dir2, '(\.pdf)$', '');
					$filesArray		= array_merge($filesArray, $filesArray2);
				}
				$arrayFiles	= array();
				if (is_array($filesArray) && count($filesArray)) {
					dol_syslog('actions_infraspackplus.class::formBuilddocOptions count($filesArray) = '.count($filesArray));
					foreach($filesArray as $file)	$arrayFiles[$file['rowid']] = $file['name'];
					$form							= new Form($db);
					$this->resprints				.=	'<tr class="oddeven infrasfoldable InfraSPermLastOpt">
															<td colspan = "'.$colspan.'" align = "right">
																<label for = "filesArray">'.$langs->trans('PDFInfraSPlusFiles').'</label>&nbsp;';
					$this->resprints				.= $form->multiselectarray('filesArray', $arrayFiles, $selected_Files, 0, 0, '', 0, '400px');
					$this->resprints				.=		'</td>
														</tr>';
				}
			}
			// Page de garde
			if ($ntUsedAsCover != -1) {
				$usentascover		= GETPOST('usentascover', 'alpha') == '' ? 0 : 1;
				$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "usentascover">'.$langs->trans('PDFInfraSPlusUseNtAsCover').'</label>&nbsp;
												<input type = "checkbox" name = "usentascover" value = "usentascover" '.($usentascover ? 'checked' : '').' style = "cursor: pointer;">
											</td>
										</tr>';
			}
			// Infos Douanières (Poids, volume, dimensions et code SH
			if (in_array($object->element, array('propal', 'commande', 'facture', 'shipping', 'delivery'))) {
				$wvccopt												= 0;
				if (empty($conf->global->PRODUCT_DISABLE_SIZE))			$wvccopt++;
				if (empty($conf->global->PRODUCT_DISABLE_LENGTH ))		$wvccopt++;
				if (empty($conf->global->PRODUCT_DISABLE_SURFACE ))		$wvccopt++;
				if (empty($conf->global->PRODUCT_DISABLE_VOLUME ))		$wvccopt++;
				if (empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO ))	$wvccopt++;
				if ($wvccopt > 0) {
					$showwvcc		= isset($conf->global->INFRASPLUS_PDF_SHOW_WVCC)					? $conf->global->INFRASPLUS_PDF_SHOW_WVCC					: 0;
					$wvccproposals	= isset($conf->global->INFRASPLUS_PDF_WVCC_BY_DEF_FOR_PROPOSALS)	? $conf->global->INFRASPLUS_PDF_WVCC_BY_DEF_FOR_PROPOSALS	: 0;
					$wvccorders		= isset($conf->global->INFRASPLUS_PDF_WVCC_BY_DEF_FOR_ORDERS)		? $conf->global->INFRASPLUS_PDF_WVCC_BY_DEF_FOR_ORDERS		: 0;
					$wvccexped		= isset($conf->global->INFRASPLUS_PDF_WVCC_BY_DEF_FOR_EXPEDITION)	? $conf->global->INFRASPLUS_PDF_WVCC_BY_DEF_FOR_EXPEDITION	: 0;
					$wvccinvoices	= isset($conf->global->INFRASPLUS_PDF_WVCC_BY_DEF_FOR_INVOICES)		? $conf->global->INFRASPLUS_PDF_WVCC_BY_DEF_FOR_INVOICES	: 0;
					if (! GETPOST('showwvccchk', 'alpha')) {
						if (in_array($object->element, array('propal')) && $wvccproposals)	$wvccbydef	= 1;
						if (in_array($object->element, array('commande')) && $wvccorders)	$wvccbydef	= 1;
						if (in_array($object->element, array('shipping')) && $wvccexped)	$wvccbydef	= 1;
						if (in_array($object->element, array('facture')) && $wvccinvoices)	$wvccbydef	= 1;
					}
					else	$wvccbydef	= GETPOST('showwvccchk', 'alpha') == 'None'	? 0 : 1;
					if ($showwvcc) {
						$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "showwvccchk">'.$langs->trans('PDFInfraSPlusShowWVCCchk').'</label>&nbsp;
														<input type = "checkbox" name = "showwvccchk" value = "showwvccchk" '.($wvccbydef ? 'checked' : '').' style = "cursor: pointer;">
													</td>
												</tr>';
					}
				}
			}
			// Image des produits / services dans les documents client
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'delivery'))) {
				$hidepict			= ! GETPOST('hidepict', 'alpha') ? (isset($conf->global->INFRASPLUS_PDF_WITH_PICTURE) ? $conf->global->INFRASPLUS_PDF_WITH_PICTURE : 0) : (GETPOST('hidepict', 'alpha') == 'None' ? 0 : 1);
				$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "hidepict">'.$langs->trans('PDFInfraSPlusHidePictchk').'</label>&nbsp;
												<input type = "checkbox" name = "hidepict" value = "hidepict" '.($hidepict ? 'checked' : '').' style = "cursor: pointer;">
											</td>
										</tr>';
			}
			// Image des produits / services dans les commandes fournisseur
			if (in_array($object->element, array('order_supplier'))) {
				$hidepict			= ! GETPOST('hidepict', 'alpha') ? (isset($conf->global->INFRASPLUS_PDF_SUPPLIER_ORDER_WITH_PICTURE) ? $conf->global->INFRASPLUS_PDF_SUPPLIER_ORDER_WITH_PICTURE : 0) : (GETPOST('hidepict', 'alpha') == 'None' ? 0 : 1);
				$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "hidepict">'.$langs->trans('PDFInfraSPlusHidePictchk').'</label>&nbsp;
												<input type = "checkbox" name = "hidepict" value = "hidepict" '.($hidepict ? 'checked' : '').' style = "cursor: pointer;">
											</td>
										</tr>';
			}
			// Description longue des produits / services
			if (in_array($object->element, array('propal', 'commande', 'facture', 'fichinter', 'shipping', 'supplier_proposal', 'order_supplier'))) {
				if (empty($conf->global->INFRASPLUS_PDF_HIDE_LABEL)) {
					if (! GETPOST('hidedesc', 'alpha')) {
						$hidedesc															= isset($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC)	? $conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC	: 0;
						$showdescdev														= isset($conf->global->INFRASPLUS_PDF_SHOW_DESC_DEV)		? $conf->global->INFRASPLUS_PDF_SHOW_DESC_DEV		: 0;
						if (in_array($object->element, array('propal')) && $showdescdev)	$hidedesc	= 0;
					}
					else	$hidedesc	= GETPOST('hidedesc', 'alpha') == 'None'	? 0 : 1;
					$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "hidedesc">'.$langs->trans('PDFInfraSPlusHideDescchk').'</label>&nbsp;
													<input type = "checkbox" name = "hidedesc" value = "hidedesc" '.($hidedesc ? 'checked' : '').' style = "cursor: pointer;">
												</td>
											</tr>';
				}
			}
			// Remise et description seule
			if (in_array($object->element, array('propal', 'commande', 'contrat', 'fichinter')) && empty($discountAuto)) {
				if (! GETPOST('hidedisc', 'alpha'))	$hidedisc	= isset($conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT)	? $conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT : 0;
				else								$hidedisc	= GETPOST('hidedisc', 'alpha') == 'None'				? 0 : 1;
				if (! GETPOST('hidecols', 'alpha'))	$hidecols	= isset($conf->global->INFRASPLUS_PDF_HIDE_COLS)		? $conf->global->INFRASPLUS_PDF_HIDE_COLS : 0;
				else								$hidecols	= GETPOST('hidecols', 'alpha') == 'None'				? 0 : 1;
				$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "hidedisc">'.$langs->trans('PDFInfraSPlusHideDiscchk').'</label>&nbsp;
												<input type = "checkbox" name = "hidedisc" value = "hidedisc" '.($hidedisc ? 'checked' : '').' style = "cursor: pointer;">
											</td>
										</tr>
										<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "hidecols">'.$langs->trans('PDFInfraSPlusHideColschk').'</label>&nbsp;
												<input type = "checkbox" name = "hidecols" value = "hidecols" '.($hidecols ? 'checked' : '').' style = "cursor: pointer;">
											</td>
										</tr>';
			}
			// Information concernant l'adresse de facturation automatique
			if (in_array($object->element, array('facture'))) {
				$def_adrfact		= isset($conf->global->INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT)	? $conf->global->INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT		: 'Vide';
				$client				= infraspackplus_check_parent_addr_fact($object);
				$sql_adrfact		= 'SELECT DISTINCT sa.label, sa.fk_soc, sa.name AS fact';
				$sql_adrfact		.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
				$sql_adrfact		.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON sa.fk_soc = s.rowid';
				$sql_adrfact		.= ' WHERE s.rowid = "'.$client->id.'"';
				$sql_adrfact		.= ' AND sa.label = "'.$def_adrfact.'"';
				$sql_adrfact		.= ' AND sa.entity = '.$conf->entity;
				$resultat_adrfact	= $db->query($sql_adrfact);
				dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_adrfact = '.$sql_adrfact);
				if ($resultat_adrfact) {
					$obj_adrfact	= array();
					$num			= $db->num_rows($resultat_adrfact);
					for ($i = 0; $i < $num; $i++) {
						$obj_adrfact		= $db->fetch_array($resultat_adrfact);
						$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "adrfact">'.$langs->trans('PDFInfraSPlusAdrFact').' '.$obj_adrfact['fact'].' ('.$obj_adrfact['label'].')'.'</label>&nbsp;
														<input type = "checkbox" id = "adrfact" name = "adrfact" value = "'.$obj_adrfact['label'].'" checked style = "cursor: pointer;">
													</td>
												</tr>';
					}
				}
				else {
					$this->resprints	.=	'	<tr class="oddeven infrasfoldable InfraSPermLastOpt"><td colspan = "'.$colspan.'" align = "right">&nbsp;</td></tr>';
					dol_print_error($db);
				}
				$db->free($resultat_adrfact);
				unset($i);
			}
			// Affichage du total des remises
			if (in_array($object->element, array('propal', 'commande', 'facture'))) {
				if (! GETPOST('showtotdisc', 'alpha'))	$showtotdisc	= isset($conf->global->INFRASPLUS_PDF_SHOW_TOT_DISCOUNT)	? $conf->global->INFRASPLUS_PDF_SHOW_TOT_DISCOUNT : 0;
				else									$showtotdisc	= GETPOST('showtotdisc', 'alpha') == 'None'					? 0 : 1;
				$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "showtotdisc">'.$langs->trans('InfraSPlusShowTotDiscChk').'</label>&nbsp;
												<input type = "checkbox" name = "showtotdisc" value = "showtotdisc" '.($showtotdisc ? 'checked' : '').' style = "cursor: pointer;">
											</td>
										</tr>';
			}
			// Affichage de la mention d'autoliquidation BTP
			if (in_array($object->element, array('propal', 'commande', 'facture', 'order_supplier'))) {
				$hastxttvabtp	= isset($conf->global->INFRASPLUS_PDF_FREETEXT_TVA_6)	? $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_6	: 0;
				if ($hastxttvabtp)
				{
					$showtvabtp	= GETPOST('showtvabtp', 'alpha') == 'None'	? 0	: 1;
					$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "showtvabtp">'.$langs->trans('InfraSPlusShowTVAtxtBTPChk').'</label>&nbsp;
													<input type = "checkbox" name = "showtvabtp" value = "showtvabtp" '.($showtvabtp ? 'checked' : '').' style = "cursor: pointer;">
												</td>
											</tr>';
				}
			}
			// Affichage des totaux en pied de document sur les fichiches d'intervention
			if (in_array($object->element, array('fichinter'))) {
				$showtot			= GETPOST('showtot', 'alpha') == 'None' ? 0 : 1;
				$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "showtot">'.$langs->trans('InfraSPlusShowTotChk').'</label>&nbsp;
												<input type = "checkbox" name = "showtot" value = "showtot" '.($showtot ? 'checked' : '').' style = "cursor: pointer;">
											</td>
										</tr>';
			}
			// Désactivation des paiements spéciaux
			if (in_array($object->element, array('propal'))) {
				$showpayspec		= GETPOST('showpayspec', 'alpha') == 'None' ? 0 : 1;
				$this->resprints	.= '<tr class="oddeven infrasfoldable InfraSPermLastOpt">
											<td colspan = "'.$colspan.'" align = "right">
												<label for = "showpayspec">'.$langs->trans('InfraSPlusShowPaySpecChk').'</label>&nbsp;
												<input type = "checkbox" name = "showpayspec" value = "showpayspec" '.($showpayspec ? 'checked' : '').' style = "cursor: pointer;">
											</td>
										</tr>';
			}
			// Saisie de la signature client
			if (in_array($object->element, array('propal', 'commande', 'contrat', 'fichinter', 'shipping'))) {
				$getSign		= isset($conf->global->INFRASPLUS_PDF_GET_CUSTOMER_SIGNING)	? $conf->global->INFRASPLUS_PDF_GET_CUSTOMER_SIGNING : 0;
				if ($getSign)	$this->resprints	.= '<tr class="oddeven">
															<td colspan = "'.$colspan.'" align = "right">
																<label for = "getClientSign">'.$langs->trans('InfraSPlusShowClientSignPopup').'</label>&nbsp;
																<a id="getClientSign" rel="getClientSign" href="javascript:;" class="butAction">'.$langs->trans("InfraSPlusSSign").'</a>
																<input type = "hidden" id = "signvalue" name = "signvalue" value = "">
															</td>
														</tr>';
			}
			// ligne de séparation fin des options InfraSPackPlus
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'project', 'expensereport'))) {
				$this->resprints	.= '<tr style = "background: transparent !important;"><td colspan = "'.$colspan.'" align = "center" style = "padding: 0;"><hr style = "width: 80%;"></td></tr>';
			}
			return 0;
		}

		/************************************************
		* When we ask to generate a PDF document (../modules/type of element/doc/pdf_ModelName.modules.php)
		*
		* @param   array()         $parameters     Hook metadatas (context, etc...)
		* @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param   string          &$action        Current action (if set). Generally create or edit or null
		* @return  int                             < 0 on error
		************************************************/
		public function beforePDFCreation($parameters, &$object, &$action)
		{
			global $conf, $db, $mysoc, $user;

			$_SESSION['InfraSPackPlus_model']	= true;	// Write a session variable to indicate that we are using an InfraSPackPlus template
			$manualPrint						= GETPOST('action', 'alpha') == 'builddoc' ? 1 : 0;
			// Récupération des paramètres sauvegardés
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'project', 'expensereport'))) {
				if (empty($manualPrint)) {
					$userParamsKey	= 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_USER_'.$user->id;
					$objParamsKey	= 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_DOC_'.$object->id;
					$ParamLogoEmet	= isset($conf->global->INFRASPLUS_PDF_SET_LOGO_EMET_TIERS)	? $conf->global->INFRASPLUS_PDF_SET_LOGO_EMET_TIERS	: 0;
					$txtUserParams	= isset($conf->global->$userParamsKey)						? $conf->global->$userParamsKey						: '';
					$txtObjParams	= isset($conf->global->$objParamsKey)						? $conf->global->$objParamsKey						: '';
					if ($txtUserParams) {
						$userParams	= explode ('&', $txtUserParams);
						foreach ($userParams as $userParam)
						{
							$paramUser				= array ();
							parse_str($userParam,	$paramUser);
							$_POST[key($paramUser)]	= $paramUser[key($paramUser)] != 'None' ? $paramUser[key($paramUser)] : '';
							dol_syslog('actions_infraspackplus.class::beforePDFCreation $paramUser = '.$paramUser.' $paramUser[key($paramUser)] = '.$paramUser[key($paramUser)]);
						}	//foreach ($userParams as $userParam)
					}
					if ($txtObjParams) {
						$objParams	= explode ('&', $txtObjParams);
						foreach ($objParams as $objParam) {
							$paramObj				= array ();
							parse_str($objParam,	$paramObj);
							$_POST[key($paramObj)]	= $paramObj[key($paramObj)] != 'None' ? $paramObj[key($paramObj)] : '';
							dol_syslog('actions_infraspackplus.class::beforePDFCreation $paramObj = '.$paramObj.' $paramObj[key($paramObj)] = '.$paramObj[key($paramObj)]);
						}	//foreach ($objParams as $objParam)
					}
				}
			}
			// Logo et Adresse expéditeur, Mentions complémentaires + Image en pied de document
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'project', 'expensereport'))) {
				if (empty($manualPrint)) {
					$selected_logo			= GETPOST('logo', 'alpha') == 'None'			? ''						: GETPOST('logo',		'alpha');
					$freeTPost				= GETPOST('listfreet', 'alpha') == 'None'		? ''						: GETPOST('listfreet',	'alpha');
					$notePPost				= GETPOST('listnotep', 'alpha') == 'None'		? ''						: GETPOST('listnotep',	'alpha');
					$filesPost				= GETPOST('filesArray', 'alpha') == 'None'		? ''						: GETPOST('filesArray',	'alpha');
					$_POST['listfreet']		= !empty($freeTPost) && $freeTPost != "Array"	? explode ('-', $freeTPost)	: array();
					$_POST['listnotep']		= explode ('-', $notePPost);
					$_POST['filesArray']	= explode ('-', $filesPost);
				}
				else						$selected_logo			= GETPOST('logo', 'alpha');
				$selected_adr				= GETPOST('adr', 'alpha');
				$selected_pied				= GETPOST('pied', 'alpha');
				if (!empty($selected_logo))	$this->results['logo'] = $selected_logo;
				else						$this->results['logo'] = '';
				if (!empty($selected_adr) && !in_array($object->element, array('product'))) {
					$sql_address	= 'SELECT a.label, a.name, a.address, a.zip, a.town, a.phone, a.fax, a.email, a.url, c.code';
					$sql_address	.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS a';
					$sql_address	.= ' JOIN '.MAIN_DB_PREFIX.'c_country AS c ON c.rowid = a.fk_pays';
					$sql_address	.= ' WHERE a.label = "'.$selected_adr.'"';
					$result_address	= $db->query($sql_address);
					dol_syslog('actions_infraspackplus.class::beforePDFCreation sql_address = '.$sql_address);
					if ($result_address) {
						$obj_address			= $db->fetch_object($result_address);
						$this->results['adr']	= $obj_address;
						$this->results['adrlb']	= $obj_address->label;
					}
					else {
						$this->results['adr']	= '';
						$this->results['adrlb']	= '';
						dol_print_error($db);
					}
					$db->free($result_address);
				}
				else {
					$this->results['adr']	= '';
					$this->results['adrlb']	= '';
				}
				//Mentions complémentaires
				$this->results['listfreet']																= array();
				if (GETPOST('listfreet', 'alpha') == '' && GETPOST('showsysmcbase', 'alpha') == '')		$this->results['listfreet']		= 'None';
				if (GETPOST('showsysmcbase', 'alpha') != '')											$this->results['listfreet'][]	= GETPOST('showsysmcbase', 'alpha');
				if (is_array($this->results['listfreet']) && is_array (GETPOST('listfreet', 'array')))	$this->results['listfreet']		= array_merge($this->results['listfreet'], GETPOST('listfreet', 'array'));
				else if (GETPOST('listfreet', 'alpha') != '')											$this->results['listfreet'][]	= GETPOST('listfreet', 'alpha');
				$listModules	= array (	array ('propal',			'PROPOSAL_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV'),
											array ('commande',			'ORDER_FREE_TEXT',				'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_COM'),
											array ('contrat',			'CONTRACT_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_CT'),
											array ('shipping',			'SHIPPING_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_EXP'),
											array ('delivery',			'DELIVERY_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_REC'),
											array ('fichinter',			'FICHINTER_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FI'),
											array ('facture',			'INVOICE_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FAC'),
											array ('supplier_proposal',	'SUPPLIER_PROPOSAL_FREE_TEXT',	'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV_FOU'),
											array ('order_supplier',	'SUPPLIER_ORDER_FREE_TEXT',		'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FOU'),
											array ('product',			'PRODUCT_FREE_TEXT',			''),
											array ('project',			'PROJECT_FREE_NOTE',			''),
											array ('expensereport',		'EXPENSEREPORT_FREE_TEXT',		'')
										);
				$rootfreetext	= '';
				$showsysmcbase	= '';
				foreach ($listModules as $module) {
					if ($object->element == $module[0])
					{
						$rootfreetext	= $module[1];
						$showsysmcbase	= $module[2];
						$showsysmcbase	= isset($conf->global->$showsysmcbase) ? $conf->global->$showsysmcbase : '';
						break;
					}
				}
				if (!empty($rootfreetext) && $showsysmcbase && empty($this->results['listfreet']))		$this->results['listfreet']	= array($rootfreetext);
				if (is_array($this->results['listfreet']) && !empty($this->results['listfreet']))		$this->results['listfreet']	= array_flip(array_flip($this->results['listfreet']));
				//Notes publiques standards
				$this->results['listnotep']																= array();
				if (GETPOST('listnotep', 'alpha') == '' && GETPOST('showsysntbase', 'alpha') == '')		$this->results['listnotep']		= 'None';
				if (GETPOST('showsysntbase', 'alpha') != '')											$this->results['listnotep'][]	= GETPOST('showsysntbase', 'alpha');
				if (is_array($this->results['listnotep']) && is_array (GETPOST('listnotep', 'array')))	$this->results['listnotep']		= array_merge($this->results['listnotep'], GETPOST('listnotep', 'array'));
				else if (GETPOST('listnotep', 'alpha') != '')											$this->results['listnotep'][]	= GETPOST('listnotep', 'alpha');
				if (!empty($selected_pied))		$this->results['pied']	= $selected_pied;	// Replace default footer
				else							$this->results['pied']	= '';
			}
			// Adresse de livraison (client)
			if (in_array($object->element, array('propal', 'commande', 'facture', 'fichinter', 'shipping', 'delivery'))) {
				$showadrlivr	= isset($conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION) ? $conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION : 0;
				if ($showadrlivr) {
					$sel_adrliv	= GETPOST('adrlivr', 'alpha');
					if (!empty($sel_adrliv) && $sel_adrliv != 'Default' && $sel_adrliv != 'None') {	// Replace default address
						$sql_adrlivr	= 'SELECT sa.name, sa.address, sa.zip, sa.town, sa.phone, sa.fax, sa.email, sa.url, c.code';
						$sql_adrlivr	.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
						$sql_adrlivr	.= ' JOIN '.MAIN_DB_PREFIX.'c_country AS c ON c.rowid = sa.fk_pays';
						$sql_adrlivr	.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON sa.fk_soc = s.rowid';
						$sql_adrlivr	.= ' WHERE s.rowid = '.$object->thirdparty->id;
						$sql_adrlivr	.= ' AND sa.label = "'.$sel_adrliv.'"';
						$result_adrlivr	= $db->query($sql_adrlivr);
						dol_syslog('actions_infraspackplus.class::beforePDFCreation sql_adrlivr = '.$sql_adrlivr);
						if ($result_adrlivr) {
							$obj_adrlivr				= $db->fetch_object($result_adrlivr);
							$this->results['adrlivr']	= $obj_adrlivr;
						}
						else	dol_print_error($db);
						$db->free($result_adrlivr);
					}
					else if ($sel_adrliv == 'Default')	$this->results['adrlivr']	= $sel_adrliv;
					else if ($sel_adrliv == 'None')		$this->results['adrlivr']	= '';
					$this->results['labeladrlivr']		= $sel_adrliv;
				}
			}
			// Sous-Traitant (lié au client via "CustomLink")
			if ($conf->global->MAIN_MODULE_CUSTOMLINK && in_array($object->element, array('commande', 'shipping', 'delivery'))) {
				$showadrSsT	= isset($conf->global->INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT)	? $conf->global->INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT	: 0;
				if ($showadrSsT) {
					$sel_adrSst	= explode(',', GETPOST('idadrSst', 'alpha'));
					if (!empty($sel_adrSst[0]) && $sel_adrSst[0] != 'Default' && $sel_adrSst[0] != 'None') {	// Replace default address
						$sql_adrSsT		= 'SELECT sa.name, sa.address, sa.zip, sa.town, sa.phone, sa.fax, sa.email, sa.url, c.code';
						$sql_adrSsT		.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
						$sql_adrSsT		.= ' JOIN '.MAIN_DB_PREFIX.'c_country AS c ON c.rowid = sa.fk_pays';
						$sql_adrSsT		.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON sa.fk_soc = s.rowid';
						$sql_adrSsT		.= ' WHERE s.rowid = '.$sel_adrSst[1];
						$sql_adrSsT		.= ' AND sa.rowid = "'.$sel_adrSst[0].'"';
						$result_adrSsT	= $db->query($sql_adrSsT);
						dol_syslog('actions_infraspackplus.class::beforePDFCreation sql_adrSsT = '.$sql_adrSsT);
						if ($result_adrSsT) {
							$obj_adrSsT					= $db->fetch_object($result_adrSsT);
							$this->results['adrSst']	= $obj_adrSsT;
						}
						else	dol_print_error($db);
						$db->free($result_adrSsT);
					}
					elseif ($sel_adrSst[0] == 'None')	$this->results['adrSst']	= '';
					elseif ($sel_adrSst[0] == 'Default') {
						$socStatic					= new Societe($db);
						$socStatic->fetch($sel_adrSst[1]);
						$this->results['adrSst']	= $socStatic;
					}
					$this->results['idSst']		= $sel_adrSst[1];
					$this->results['idadrSst']	= $sel_adrSst[0];
				}
			}
			// Adresse de livraison spéciale fournisseur (interne ou interne + client)
			if (in_array($object->element, array('supplier_proposal', 'order_supplier'))) {
				$showadrlivr	= isset($conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON)	? $conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON	: 0;
				$adrlivrcli		= isset($conf->global->INFRASPLUS_PDF_ADRESSE_LIVRAISON_MIXTE)	? $conf->global->INFRASPLUS_PDF_ADRESSE_LIVRAISON_MIXTE	: 0;
				if ($showadrlivr) {
					$sel_adrliv	= GETPOST('adrlivr', 'alpha');
					if (! $adrlivrcli) {
						if (!empty($sel_adrliv) && $sel_adrliv != 'Default' && $sel_adrliv != 'Vide') {	// Replace default address
							$sql_adrlivr	= 'SELECT sa.name, sa.address, sa.zip, sa.town, sa.phone, sa.fax, sa.email, sa.url, c.code';
							$sql_adrlivr	.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
							$sql_adrlivr	.= ' JOIN '.MAIN_DB_PREFIX.'c_country AS c ON c.rowid = sa.fk_pays';
							$sql_adrlivr	.= ' WHERE sa.fk_soc = "0"';
							$sql_adrlivr	.= ' AND sa.label = "'.$sel_adrliv.'"';
							$result_adrlivr	= $db->query($sql_adrlivr);
							dol_syslog('actions_infraspackplus.class::beforePDFCreation::supplier sql_adrlivr = '.$sql_adrlivr);
							if ($result_adrlivr) {
								$obj_adrlivr				= $db->fetch_object($result_adrlivr);
								$this->results['adrlivr']	= $obj_adrlivr;
							}
							else	dol_print_error($db);
							$db->free($result_adrlivr);
						}
						else if ($sel_adrliv == 'Default')	$this->results['adrlivr']	= $sel_adrliv;
						else if ($sel_adrliv == 'Vide')		$this->results['adrlivr']	= '';
					}
					else {
						if (!empty($sel_adrliv) && $sel_adrliv != 'Default' && $sel_adrliv != 'Vide') {	// Replace default address
							$typeadr					= explode (' _ ', $sel_adrliv);
							$labeladr					= explode (' / ', $typeadr[1]);
							$this->results['typeadr']	= $typeadr[0];
							if ($typeadr[0] == 'Interne') {
								$sql_adrlivr	= 'SELECT sa.name, sa.address, sa.zip, sa.town, sa.phone, sa.fax, sa.email, sa.url, c.code';
								$sql_adrlivr	.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
								$sql_adrlivr	.= ' JOIN '.MAIN_DB_PREFIX.'c_country AS c ON c.rowid = sa.fk_pays';
								$sql_adrlivr	.= ' WHERE sa.fk_soc = "0"';
								$sql_adrlivr	.= ' AND sa.label = "'.$labeladr[0].'"';
							}
							elseif ($typeadr[0] == 'Client' && !$labeladr[1]) {
								$sql_adrlivr	= 'SELECT s.nom, s.address, s.zip, s.town, s.phone, s.fax, s.email, s.url, c.code';
								$sql_adrlivr	.= ' FROM '.MAIN_DB_PREFIX.'societe AS s';
								$sql_adrlivr	.= ' JOIN '.MAIN_DB_PREFIX.'c_country AS c ON c.rowid = s.fk_pays';
								$sql_adrlivr	.= ' WHERE s.nom = "'.$labeladr[0].'"';
							}
							elseif ($typeadr[0] == 'Client' && $labeladr[1]) {
								$sql_adrlivr	= 'SELECT s.nom, sa.name, sa.address, sa.zip, sa.town, sa.phone, sa.fax, sa.email, sa.url, c.code';
								$sql_adrlivr	.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
								$sql_adrlivr	.= ' JOIN '.MAIN_DB_PREFIX.'c_country AS c ON c.rowid = sa.fk_pays';
								$sql_adrlivr	.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON sa.fk_soc = s.rowid';
								$sql_adrlivr	.= ' WHERE s.nom = "'.$labeladr[0].'"';
								$sql_adrlivr	.= ' AND sa.label = "'.$labeladr[1].'"';
							}
							$result_adrlivr	= $db->query($sql_adrlivr);
							dol_syslog('actions_infraspackplus.class::beforePDFCreation::supplier sql_adrlivr = '.$sql_adrlivr);
							if ($result_adrlivr) {
								$obj_adrlivr				= $db->fetch_object($result_adrlivr);
								$this->results['adrlivr']	= $obj_adrlivr;
							}
							else	dol_print_error($db);
							$db->free($result_adrlivr);
						}
						else if ($sel_adrliv == 'Default') {
							$this->results['adrlivr']	= $sel_adrliv;
							$this->results['typeadr']	= 'Interne';
						}
						else if ($sel_adrliv == 'Vide')	$this->results['adrlivr']	= '';
					}
				}
			}
			// CGV
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat'))) {
				$this->results['cgv']	= !empty($user->rights->infraspackplus->paramCGV) ? GETPOST('cgv', 'alpha') : (isset($conf->global->INFRASPLUS_PDF_CGV) ? $conf->global->INFRASPLUS_PDF_CGV : '');
			}
			// CGI
			if (in_array($object->element, array('fichinter'))) {
				$this->results['cgi']	= !empty($user->rights->infraspackplus->paramCGV) ? GETPOST('cgi', 'alpha') : (isset($conf->global->INFRASPLUS_PDF_CGI) ? $conf->global->INFRASPLUS_PDF_CGI : '');
			}
			// CGA
			if (in_array($object->element, array('supplier_proposal', 'order_supplier'))) {
				$this->results['cga']	= !empty($user->rights->infraspackplus->paramCGV) ? GETPOST('cga', 'alpha') : (isset($conf->global->INFRASPLUS_PDF_CGA) ? $conf->global->INFRASPLUS_PDF_CGA : '');
			}
			// Fichiers joints à fusionner
			if (!in_array($object->element, array('product'))) {
				if (GETPOST('filesArray', 'alpha') == '')		$this->results['filesArray']	= 'None';
				else if (GETPOST('filesArray', 'alpha') != '')	$this->results['filesArray']	= GETPOST('filesArray', 'alpha');
			}
			// Page de garde
			if (GETPOST('showntusedascover', 'alpha') != '' && GETPOST('usentascover', 'alpha') != '') {
				$this->results['usentascover']		= GETPOST('usentascover', 'alpha');
				$this->results['showntusedascover']	= GETPOST('showntusedascover', 'alpha');
			}
			// Infos Douanières (Poids, volume, dimensions et code SH
			if (in_array($object->element, array('propal', 'commande', 'facture', 'shipping', 'delivery'))) {
				$wvccopt												= 0;
				if (empty($conf->global->PRODUCT_DISABLE_SIZE))			$wvccopt++;
				if (empty($conf->global->PRODUCT_DISABLE_LENGTH ))		$wvccopt++;
				if (empty($conf->global->PRODUCT_DISABLE_SURFACE ))		$wvccopt++;
				if (empty($conf->global->PRODUCT_DISABLE_VOLUME ))		$wvccopt++;
				if (empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO ))	$wvccopt++;
				if ($wvccopt > 0)										$this->results['showwvccchk']	= GETPOST('showwvccchk', 'alpha');
				else													$this->results['showwvccchk']	= '';
			}
			// Image des produits / services dans les documents client
			// Image des produits / services dans les commandes fournisseur
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'delivery', 'order_supplier'))) {
				$this->results['hidepict']		= GETPOST('hidepict', 'alpha');
			}
			// Description longue des produits / services
			if (in_array($object->element, array('propal', 'commande', 'facture', 'fichinter', 'shipping', 'supplier_proposal', 'order_supplier'))) {
				if (empty($conf->global->INFRASPLUS_PDF_HIDE_LABEL))	$this->results['hidedesc']	= GETPOST('hidedesc', 'alpha');
			}
			// Remise et description seule
			if (in_array($object->element, array('propal', 'commande', 'contrat', 'fichinter'))) {
				$discountAuto					= isset($conf->global->INFRASPLUS_PDF_DISCOUNT_AUTO) ? $conf->global->INFRASPLUS_PDF_DISCOUNT_AUTO : 0;
				$this->results['hidedisc']		= empty($discountAuto) ? GETPOST('hidedisc', 'alpha') : 0;
				$this->results['hidecols']		= GETPOST('hidecols', 'alpha');
			}
			// Information concernant l'adresse de facturation automatique
			if (in_array($object->element, array('facture'))) {
				$client			= infraspackplus_check_parent_addr_fact($object);
				$sel_adrfact	= GETPOST('adrfact', 'alpha');
				if (!empty($sel_adrfact) && $sel_adrfact != 'Default' && $sel_adrfact != 'Vide') {	// Replace default address
					$sql_adrfact	= 'SELECT sa.name, sa.address, sa.zip, sa.town, sa.phone, sa.fax, sa.email, sa.url, c.code as country_code, c.label as country';
					$sql_adrfact	.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
					$sql_adrfact	.= ' JOIN '.MAIN_DB_PREFIX.'c_country AS c ON c.rowid = sa.fk_pays';
					$sql_adrfact	.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON sa.fk_soc = s.rowid';
					$sql_adrfact	.= ' WHERE s.rowid = "'.$client->id.'"';
					$sql_adrfact	.= ' AND sa.label = "'.$sel_adrfact.'"';
					$result_adrfact	= $db->query($sql_adrfact);
					dol_syslog('actions_infraspackplus.class::beforePDFCreation sql_adrfact = '.$sql_adrfact);
					if ($result_adrfact) {
						$obj_adrfact				= $db->fetch_object($result_adrfact);
						$this->results['adrfact']	= $obj_adrfact;
					}
					else {
						$this->results['adrfact']	= '';
						dol_print_error($db);
					}
					$db->free($result_adrfact);
				}
				else $this->results['adrfact']	= '';
			}
			// Affichage du total des remises
			if (in_array($object->element, array('propal', 'commande', 'facture'))) {
				$this->results['showtotdisc']	= GETPOST('showtotdisc', 'alpha');
			}
			// Affichage de la mention d'autoliquidation BTP
			if (in_array($object->element, array('propal', 'commande', 'facture', 'order_supplier'))) {
				$this->results['showtvabtp']	= GETPOST('showtvabtp', 'alpha');
			}
			// Affichage des totaux en pied de document sur les fichiches d'intervention
			if (in_array($object->element, array('fichinter'))) {
				$this->results['showtot']	= GETPOST('showtot', 'alpha');
			}
			// Désactivation des paiements spéciaux
			if (in_array($object->element, array('propal'))) {
				$this->results['showpayspec']	= GETPOST('showpayspec', 'alpha');
			}
			// Saisie de la signature client
			if (in_array($object->element, array('propal', 'commande', 'contrat', 'fichinter', 'shipping'))) {
				$this->results['signvalue']	= GETPOST('signvalue', 'alpha');
			}
			if ($conf->global->MAIN_MODULE_SUBTOTAL && ((!empty($conf->global->SUBTOTAL_PROPAL_ADD_RECAP) && in_array($object->element, array('propal')))
													|| (!empty($conf->global->SUBTOTAL_COMMANDE_ADD_RECAP) && in_array($object->element, array('commande')))
													|| (!empty($conf->global->SUBTOTAL_INVOICE_ADD_RECAP) && in_array($object->element, array('facture'))))) {
				$this->results['subtotal_add_recap']	= GETPOST('subtotal_add_recap');
			}
			// enregistrement des choix utilisateur
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'project', 'expensereport'))) {
				// préparation des paramètres
				$paramsResults						= array_combine(array_keys($this->results), $this->results);	// récupération	// array_replace([], $this->results);
				$listNoParams						= array ('adr', 'adrlivr', 'adrSst', 'typeadr', 'signvalue');	// liste à enlever
				foreach ($listNoParams as $NoParam)	unset ($paramsResults[$NoParam]);	// nettoyage des clés
				// paramètres objet (liés au document)
				$listParamsObjID							= array ('labeladrlivr', 'idSst', 'idadrSst', 'filesArray', 'listfreet', 'listnotep');	// liste à garder
				$listArrayParamsObjID						= array ('filesArray', 'listfreet', 'listnotep');	// liste des arrays à traiter
				foreach ($listParamsObjID as $ParamObjID)	$paramsResultsObjID[$ParamObjID]	= $paramsResults[$ParamObjID];	// récupération
				foreach ($paramsResultsObjID as $paramResultObjID) {	// nettoyage des valeurs
					$tmpkey				= array_search('',$paramsResultsObjID);
					if ($tmpkey == '0')	unset ($paramsResultsObjID[$tmpkey]);
					else				$paramsResultsObjID[$tmpkey]	= 'None';
				}
				foreach ($listArrayParamsObjID as $ArrayParamObjID)	$paramsResultsObjID[$ArrayParamObjID]	= $paramsResultsObjID[$ArrayParamObjID] == 'None' ? '' : implode ('-', $paramsResultsObjID[$ArrayParamObjID]);	// traitement des arrays
				$txtResultsParamsObjID								= http_build_query ($paramsResultsObjID, '');	// écriture de la chaine
				dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_DOC_'.$object->id,	$txtResultsParamsObjID, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);	// enregistrement de la chaine
				dol_syslog('actions_infraspackplus.class::beforePDFCreation::dolibarr_set_const name = '.'INFRASPLUS_PDF_PARAMS_'.$object->element.'_DOC_'.$object->id.' entity = '.$conf->entity.' txtResultsParamsObjID = '.$txtResultsParamsObjID);
				// paramètres standards (liés à l'utilisateur)
				foreach ($listParamsObjID as $ParamObjID)			unset ($paramsResults[$ParamObjID]);	// on enlève les paramètres objet (liés au document)
				$listArrayParams									= array ();	// liste des arrays à traiter
				foreach ($paramsResults as $paramResult) {	// nettoyage des valeurs
					$tmpkey				= array_search('',$paramsResults);
					if ($tmpkey == '0')	unset ($paramsResults[$tmpkey]);
					else				$paramsResults[$tmpkey]	= 'None';
				}
				foreach ($listArrayParams as $ArrayParam)	$paramsResults[$ArrayParam]		= $paramsResults[$ArrayParam] == 'None' ? '' : implode ('-', $paramsResults[$ArrayParam]);	// traitement des arrays
				$txtResultsParams							= http_build_query ($paramsResults, '');	// écriture de la chaine
				dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_USER_'.$user->id,	$txtResultsParams, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);	// enregistrement de la chaine
				dol_syslog('actions_infraspackplus.class::beforePDFCreation::dolibarr_set_const name = '.'INFRASPLUS_PDF_PARAMS_'.$object->element.'_USER_'.$user->id.' entity = '.$conf->entity.' txtResultsParams = '.$txtResultsParams);
			}
			return 0;
		}

		/************************************************
		* When we finish to generate a PDF document (../modules/type of element/doc/pdf_ModelName.modules.php)
		*
		* @param   array()         $parameters     Hook metadatas (context, etc...)
		* @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param   string          &$action        Current action (if set). Generally create or edit or null
		* @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
		* @return  int                             < 0 on error
		************************************************/
	    public function afterPDFCreation($parameters, &$object, &$action)
		{
			unset($_SESSION['InfraSPackPlus_model']);	// Destroys the session variable that indicates that we are using an InfraSPackPlus template
			return 0;
		}

		/************************************************
		* When we show or edit object extrafields on main card (../core/tpl/extrafields_add+_edit+_view.tpl.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @return	int + string					< 0 on error, 0 on success, 1 to replace standard code
		*											$this->resprints HTML code to show
		************************************************/
		public function formObjectOptions($parameters, &$object, &$action)
		{
			global $conf, $db, $langs;
			$langs->load('infraspackplus@infraspackplus');

			$TContext		= explode(':', $parameters['context']);
			$ParamLogoEmet	= isset($conf->global->INFRASPLUS_PDF_SET_LOGO_EMET_TIERS)	? $conf->global->INFRASPLUS_PDF_SET_LOGO_EMET_TIERS : 0;
			if (in_array('thirdpartycard', $TContext) && $ParamLogoEmet) {
				$selected_logo_emet	= infraspackplus_getLogoEmet($object->id);
				if ($action == 'create' || $action == 'edit') {
					$listlogos																	= array();
					$logodir																	= !empty($conf->mycompany->multidir_output[$object->entity])	? $conf->mycompany->multidir_output[$object->entity]	: $conf->mycompany->dir_output;
					foreach (glob($logodir.'/logos/{*.jpg,*.gif,*.png}', GLOB_BRACE) as $file)	$listlogos[]													= dol_basename($file);
					$this->resprints	.= '<tr>
												<td>'.$langs->trans('PDFInfraSPlusLogo').'</td>
												<td colspan = "'.$colspanshort++.'" class = "maxwidthonsmartphone">
													<select class = "flat" name = "logosChoice" style = "cursor: pointer;">
														<option name = "logosChoice" value = "">'.$langs->trans('PDFInfraSPlusDefaultLogo').'</option>';
					for ($i = 0; $i < count($listlogos); $i++)
						$this->resprints	.= '		<option name = "logosChoice" value = "'.$listlogos[$i].($selected_logo_emet === $listlogos[$i] ? '" selected' : '"').'>'.$listlogos[$i].'</option>';
					$this->resprints	.=			'</select>
												</td>
											</tr>';
					unset($i);
				}
				else {
					$this->resprints	.= '<tr>
												<td>'.$langs->trans('PDFInfraSPlusLogo').'</td>
												<td colspan = "'.$colspanshort++.'" class = "maxwidthonsmartphone">'.($selected_logo_emet ? $selected_logo_emet : $langs->trans('PDFInfraSPlusDefaultLogo')).'</td>
											</tr>';
				}
			}
		}

		/************************************************
		* When we ask for an action (../element/card.php)
		*
		* @param   array()         $parameters     Hook metadatas (context, etc...)
		* @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param   string          &$action        Current action (if set). Generally create or edit or null
		* @return  int                             < 0 on error, 0 on success, 1 to replace standard code
		************************************************/
		public function doActions($parameters, &$object, $action)
		{
			global $db, $conf, $langs, $user;

			$TContext	= explode(':', $parameters['context']);
			$ParamLogoEmet	= isset($conf->global->INFRASPLUS_PDF_SET_LOGO_EMET_TIERS)	? $conf->global->INFRASPLUS_PDF_SET_LOGO_EMET_TIERS : 0;
			if (in_array('thirdpartycard', $TContext) && $ParamLogoEmet)
				if ($action == 'update' && $user->rights->societe->creer)	$this->errors	= infraspackplus_setLogoEmet($parameters['id'], GETPOST('logosChoice', 'alpha'));
		}
	}
?>