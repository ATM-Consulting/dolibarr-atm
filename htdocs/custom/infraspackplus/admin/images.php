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
	* 	\file		../infraspackplus/admin/images.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup pictures for the module InfraS
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
	require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->load('admin');
	$langs->load('companies');
	$langs->load('errors');
	$langs->load('infraspackplus@infraspackplus');

	// Access control *******************************
	$accessright					= !empty($user->admin) || !empty($user->rights->infraspackplus->paramBkpRest) ? 2 : (!empty($user->rights->infraspackplus->paramImages) ? 1 : 0);
	if (empty($accessright))		accessforbidden();

	// Actions **************************************
	$form							= new Form($db);
	$formfile						= new FormFile($db);
	$formother						= new FormOther($db);
	$confirm_mesg					= '';
	$action							= GETPOST('action', 'alpha');
	$confirm						= GETPOST('confirm', 'alpha');
	$urlfile						= GETPOST('urlfile', 'alpha');
	$result							= '';
	$logodir						= !empty($conf->mycompany->multidir_output[$conf->entity])	? $conf->mycompany->multidir_output[$conf->entity]	: $conf->mycompany->dir_output;
	$use_iso_location				= isset($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? $conf->global->MAIN_PDF_USE_ISO_LOCATION : 0;
	$hlogo							= isset($conf->global->MAIN_DOCUMENTS_LOGO_HEIGHT) ? $conf->global->MAIN_DOCUMENTS_LOGO_HEIGHT : 10;
	$maxhlogo						= ($use_iso_location ? 28 : 50);
	$hlogo							= ($hlogo > $maxhlogo ? $maxhlogo : $hlogo);
	dolibarr_set_const($db, "MAIN_DOCUMENTS_LOGO_HEIGHT", $hlogo, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	//Sauvegarde / Restauration
	if ($action == 'bkupParams')	$result	= infraspackplus_bkup_module ('infraspackplus');
	if ($action == 'restoreParams')	$result	= infraspackplus_restore_module ('infraspackplus');
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		if ($confkey == 'INFRASPLUS_PDF_PICTURE_IN_REF' && !empty($conf->global->INFRASPLUS_PDF_PICTURE_IN_REF))	$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_AFTER',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		if ($confkey == 'INFRASPLUS_PDF_PICTURE_AFTER' && !empty($conf->global->INFRASPLUS_PDF_PICTURE_AFTER))		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_IN_REF',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Img'	=> array('MAIN_DOCUMENTS_LOGO_HEIGHT',		'INFRASPLUS_PDF_PICTURE_FOOT_WIDTH',	'INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT',	'INFRASPLUS_PDF_LINK_PICTURE_URL',
											'INFRASPLUS_PDF_PICTURE_PADDING',	'INFRASPLUS_PDF_PICTURE_WIDTH',			'INFRASPLUS_PDF_PICTURE_HEIGHT',		'INFRASPLUS_PDF_T_WATERMARK_OPACITY',
											'INFRASPLUS_PDF_I_WATERMARK_OPACITY'));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}

	if($action == 'add') {
		$extension	= pathinfo($_FILES['InfraSPlusParamLogoFile']['name'], PATHINFO_EXTENSION);
		$dest_file	= GETPOST('InfraSPlusParamLogoName', 'alpha').'.'.mb_strtolower($extension);
		$dest_path	= $logodir.'/logos/';
		$moved		= dol_move_uploaded_file($_FILES['InfraSPlusParamLogoFile']['tmp_name'], $dest_path.$dest_file, 1, 0, $_FILES['InfraSPlusParamLogoFile']['error']);
		if ($moved > 0) {
			if ($isimage = image_format_supported($dest_file) === 1) {
				$imgThumbSmall	= vignette($dest_path.$dest_file, $maxwidthsmall, $maxheightsmall, '_mini', $quality);
				$imgThumbSmall	= vignette($dest_path.$dest_file, $maxwidthsmall, $maxheightsmall, '_small', $quality);
			}
			setEventMessages($dest_file.' : '.$langs->trans('FileSaved'), null, 'mesgs');
		}
		else if ($moved !== 1) {	// errors
			if ($moved < 0)	setEventMessages('UknownFileUploadError', null, 'errors');	// API documented error
			else			setEventMessages($moved, null, 'errors');	// We got an error string /o\
		}
	}
	if ($action == 'defaultP')	$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_IMAGE_FOOT',		GETPOST('defaultpied'),			'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if ($action == 'defaultW')	$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_IMAGE_WATERMARK',	GETPOST('defaultwatermark'),	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if ($action == 'delete')	$confirm_mesg	= $form->formconfirm($_SERVER["PHP_SELF"].'?urlfile='.$urlfile, $langs->trans('InfraSPlusParamDeleteAFile'), $langs->trans('InfraSPlusParamConfirmDeleteAFile').' '.$urlfile.' ?', 'delete_ok', '', 1, (int) $conf->use_javascript_ajax);
	if ($action == 'delete_ok' && $confirm == 'yes') {
		$urlfile_dirname	= pathinfo($urlfile, PATHINFO_DIRNAME);
		$urlfile_filename	= pathinfo($urlfile, PATHINFO_FILENAME);
		$urlfile_ext		= pathinfo($urlfile, PATHINFO_EXTENSION);
		$urlfile_small		= $urlfile_dirname.$urlfile_filename.'_small.'.$urlfile_ext;
		$urlfile_mini		= $urlfile_dirname.$urlfile_filename.'_mini.'.$urlfile_ext;
		$a					= dol_delete_file($logodir.'/logos'.$urlfile, 1);
		$b					= dol_delete_file($logodir.'/logos/thumbs'.$urlfile_small, 1);
		$c					= dol_delete_file($logodir.'/logos/thumbs'.$urlfile_mini, 1);
		if ($a && $b && $c)	setEventMessages($urlfile_filename.'.'.$urlfile_ext.' '.$langs->trans('Deleted'), null, 'mesgs');
		else				setEventMessages($langs->trans('ErrorFailToDeleteFile', $urlfile), null, 'errors');
	}

	if ($result == 1)			setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	if ($result == -1)			setEventMessages($langs->trans('Error'), null, 'errors');

	// init variables *******************************
	$logos																				= array();
	foreach (glob($logodir.'/logos/{*.jpg,*.jpeg,*.gif,*.png}', GLOB_BRACE) as $file)	$logos[]	= dol_basename($file);
	$selected_logo																		= $conf->global->INFRASPLUS_PDF_IMAGE_FOOT;
	$selected_watermark																	= $conf->global->INFRASPLUS_PDF_IMAGE_WATERMARK;
	$noMyLogo																			= isset($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)	? $conf->global->PDF_DISABLE_MYCOMPANY_LOGO : 0;
	$disabledLogoHeight																	= empty($noMyLogo) ? 'enabled' : 'disabled';
	if (!empty($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN) && !empty($conf->global->INFRASPLUS_PDF_PICTURE_IN_REF)) {
		$picture_width	= isset($conf->global->INFRASPLUS_PDF_LARGCOL_REF) ? $conf->global->INFRASPLUS_PDF_LARGCOL_REF : 28;
		dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_WIDTH', $picture_width, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$modifWidth		= ' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamPictureInRef2');
	}
	$rowInnerSpan	= !empty($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN)	? 5						: 4;
	$rowSpan		= $conf->global->INFRASPLUS_PDF_WITH_PICTURE			? 17 + $rowInnerSpan	: 13;

	// View *****************************************
	$page_name					= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsImages');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	if (!empty($user->admin))	$linkback	= '<a href = "'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption				= img_picto($langs->trans('Setup'), 'setup');

	// Configuration header *************************
	$head						= infraspackplus_admin_prepare_head();
	$picto						= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'images', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script src = "'.dol_buildpath('/infraspackplus/includes/js/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					jQuery(document).ready(function() {
						var tblIexp = "";
						$.isSet = function(testVar){ return typeof(testVar) !== "undefined" && testVar !== null && testVar !== ""; };
						if ($.cookie && $.isSet($.cookie("tblIexp"))) { tblIexp = $.cookie("tblIexp"); }
						$(".toggle_bloc").hide();
						if (tblIexp != "") { $("[name=" + tblIexp + "]").toggle(); }
					});
					$(function () {
						$(".foldable .toggle_bloc_title").click(function() {
							if ($(this).siblings().is(":visible")) { $(".toggle_bloc").hide(); }
							else {
								$(".toggle_bloc").hide();
								$(this).siblings().show();
							}
							$.cookie("tblIexp", "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) { $.cookie("tblIexp", $(this).attr("name"), { expires: 1, path: "/" }); }
							});
						});
					});
				</script>';
	}
	print '		<form action="'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infraspackplus_print_backup_restore();
	print '			<div class = "foldable">';
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans("InfraSPlusParamGestionLogos").'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/Tools.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '				<table name = "tblGF" class = "noborder toggle_bloc" width = "100%">';
	$metas	= array('30px', '*', '350px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 3), 'NumberingShort', 'InfraSPlusParamNewLogo');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		print '				<tr class = "oddeven">
								<td class = "center" style = "color: #382453; font-weight: bold;">'.$num.'</td>
								<td>
									'.fieldLabel('InfraSPlusParamLogoFile', 'InfraSPlusParamLogoFile').'
									<input type = "file" class = "flat" id = "InfraSPlusParamLogoFile" name = "InfraSPlusParamLogoFile" accept="image/*" style = "padding: 0px; font-size: inherit;">
								</td>
								<td align = "right">
									'.fieldLabel('InfraSPlusParamLogoName', 'InfraSPlusParamLogoName').'
									<input type = "text" class = "flat" id = "InfraSPlusParamLogoName" name = "InfraSPlusParamLogoName" style = "padding: 0px; font-size: inherit;">
								</td>
								<td align = "center"><button class = "button" style = "width: 110px;" type = "submit" value = "add" name = "action">'.$langs->trans('Add').'</button></td>
							</tr>';
		$num++;
		$metas	= $form->selectarray('defaultpied', $logos, $selected_logo, $langs->trans('InfraSPlusParamNoPied'), 0, 1, 'style = "padding: 0px; font-size: inherit; cursor: pointer;"', 0, 0, 0, '', 'centpercent');
		$end	= '<td align = "center"><button class = "button" style = "width: 110px;" type = "submit" value = "defaultP" name = "action">'.$langs->trans('Validate').'</button></td>';
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamDefaultImageFooter'), '', $metas, 1, 1, $end, $num);
		$metas	= $form->selectarray('defaultwatermark', $logos, $selected_watermark, $langs->trans('InfraSPlusParamNoWatermarkImage'), 0, 1, 'style = "padding: 0px; font-size: inherit; cursor: pointer;"', 0, 0, 0, '', 'centpercent');
		$end	= '<td align = "center"><button class = "button" style = "width: 110px;" type = "submit" value = "defaultW" name = "action">'.$langs->trans('Validate').'</button></td>';
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamDefaultWatermarkImage'), '', $metas, 1, 1, $end, $num);
		print '				<tr><td colspan = "3" style = "line-height: 1px;">&nbsp;</td></tr>';
	}
	print '				</table>
					</div>
				</form>';
	$logo_files	= dol_dir_list($logodir.'/logos/', 'files', 0, '',  '', null, null, 1);
	print '		<div class = "foldable">';
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamListLogos').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/list.png', 1), 1, '', 'NoBCollapse toggle_bloc_title cursorpointer');
	print '			<div name = "tblLL" class = "toggle_bloc">';
	$formfile->list_of_documents($logo_files, null, 'companylogo', '', 1, '', 1, 0, $langs->trans('NoLogo'), 0, 'none');
	print '			</div>
				</div>';
	print '		<form action = "'.$_SERVER['PHP_SELF'].'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">
					<div class = "foldable">';
	print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamImagesSetup').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '				<table name = "tblOPT" class="noborder toggle_bloc" width="100%">';
	$metas	= array('30px', '*', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Img', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave').'<br/><FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamAvertissementCalculImage'), $rowSpan, 3);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '5', 'max' => $maxhlogo, $disabledLogoHeight => 'true');
		$num	= infraspackplus_print_input('MAIN_DOCUMENTS_LOGO_HEIGHT', 'input', $langs->trans('InfraSPlusParamLogoHeight', $maxhlogo), '', $metas, 1, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '20', 'max' => '190');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_FOOT_WIDTH', 'input', $langs->trans('InfraSPlusParamPictureFootWidth'), '', $metas, 1, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '4', 'max' => '30');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT', 'input', $langs->trans('InfraSPlusParamPictureFootHeight'), '', $metas, 1, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SET_LOGO_EMET_TIERS', 'on_off', $langs->trans('InfraSPlusParamSetLogoEmetTiers'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_PICTURE', 'on_off', $langs->trans('InfraSPlusParamWithPicture').' '.$langs->trans('GenModif'), '', array(), 1, 1, '', $num);
		if (!empty($conf->global->INFRASPLUS_PDF_WITH_PICTURE)) {
			if (!empty($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN) || !empty($conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN)) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_IN_REF', 'on_off', $langs->trans('InfraSPlusParamPictureInRef'), '', array(), 1, 1, '', $num);
				if (!empty($conf->global->INFRASPLUS_PDF_PICTURE_IN_REF))
					$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_REPLACE_REF', 'on_off', $langs->trans('InfraSPlusParamPictureReplaceRef'), '', array(), 1, 1, '', $num);
				else	$num++;
			}
			else	$num	+= 2;
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_ONLY_ONE_PICTURE', 'on_off', $langs->trans('InfraSPlusParamOnlyOnePicture1').' <FONT color = "red">'.$langs->trans('InfraSPlusParamOnlyOnePicture2').'</FONT> '.$langs->trans('InfraSPlusParamOnlyOnePicture3'), '', array(), 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_AFTER', 'on_off', $langs->trans('InfraSPlusParamPictureAfter1').' <FONT color = "red">'.$langs->trans('InfraSPlusParamPictureAfter2').'</FONT> '.$langs->trans('InfraSPlusParamPictureAfter3'), '', array(), 1, 1, '', $num);
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '15');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_PADDING', 'input', $langs->trans('InfraSPlusParamPicturePadding'), '', $metas, 1, 1, '&nbsp;mm', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LINK_PICTURE_URL', 'input', $langs->trans('InfraSPlusParamLinkPictureUrl'), '', array(), 1, 1, '', $num);
		}
		else	$num	+= 6;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SUPPLIER_ORDER_WITH_PICTURE', 'on_off', $langs->trans('InfraSPlusParamSupplierOrderWithPicture').' '.$langs->trans('GenModif'), '', array(), 1, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '16', 'max' => '210');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_WIDTH', 'input', $langs->trans('InfraSPlusParamPictureWidth').$modifWidth, '', $metas, 1, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '16', 'max' => '210');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_HEIGHT', 'input', $langs->trans('InfraSPlusParamPictureHeight'), '', $metas, 1, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('PRODUCT_USE_OLD_PATH_FOR_PHOTO', 'on_off', $langs->trans('InfraSPlusParamOldPathPhoto'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('CAT_HIGH_QUALITY_IMAGES', 'on_off', $langs->trans('InfraSPlusParamHQPicture'), '', array(), 1, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '100');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_T_WATERMARK_OPACITY', 'input', $langs->trans('InfraSPlusParamWatermarkTOpacity'), '', $metas, 1, 1, '&nbsp;%', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '100');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_I_WATERMARK_OPACITY', 'input', $langs->trans('InfraSPlusParamWatermarkIOpacity'), '', $metas, 1, 1, '&nbsp;%', $num);
		print '				<tr><td colspan = "3" style = "line-height: 1px;">&nbsp;</td></tr>';
	}
	print '				</table>
					</div>
				</form>';
	dol_fiche_end();
	llxFooter();
	$db->close();
?>