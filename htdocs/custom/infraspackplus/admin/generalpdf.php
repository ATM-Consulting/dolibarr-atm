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
	* 	\file		../infraspackplus/admin/generalpdf.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->load("admin");
	$langs->load("companies");
	$langs->load('infraspackplus@infraspackplus');

	// Access control *******************************
	$accessright				= !empty($user->admin) || !empty($user->rights->infraspackplus->paramDolibarr) ? 1 : 0;
	if (empty($accessright))	accessforbidden();

	// Actions **************************************
	$formadmin					= new FormAdmin($db);
	$form						= new Form($db);
	$formfile					= new FormFile($db);
	$formother					= new FormOther($db);
	$action						= GETPOST('action','alpha');
	$result						= '';
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		if ($confkey == 'MAIN_GENERATE_DOCUMENTS_HIDE_REF' && empty($conf->global->$confkey)) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_REF_COLUMN',				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array ('Gen'	=> array('MAIN_PDF_MARGIN_LEFT',	'MAIN_PDF_MARGIN_TOP',	'MAIN_PDF_MARGIN_RIGHT',	'MAIN_PDF_MARGIN_BOTTOM',	'MAIN_PDF_FORMAT',
											'MAIN_PDF_FORCE_FONT_SIZE',	'PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES'));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= $constname == 'INFRASPLUS_PDF_ROUNDED_REC' ? (GETPOST($constname, 'alpha') == 0 ? 0.001 : GETPOST($constname, 'alpha')) : GETPOST($constname, 'alpha');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	// Retour => message Ok ou Ko
	if ($result == 1)	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	if ($result == -1)	setEventMessages($langs->trans('Error'), null, 'errors');

	// init variables *******************************
	if ($conf->global->MAIN_PDF_MARGIN_TOP < 4)		dolibarr_set_const($db, 'MAIN_PDF_MARGIN_TOP',		4, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if ($conf->global->MAIN_PDF_MARGIN_LEFT < 4)	dolibarr_set_const($db, 'MAIN_PDF_MARGIN_LEFT',		4, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if ($conf->global->MAIN_PDF_MARGIN_RIGHT < 4)	dolibarr_set_const($db, 'MAIN_PDF_MARGIN_RIGHT',	4, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if ($conf->global->MAIN_PDF_MARGIN_BOTTOM < 4)	dolibarr_set_const($db, 'MAIN_PDF_MARGIN_BOTTOM',	4, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	$selected			= isset($conf->global->MAIN_PDF_FORMAT)	? $conf->global->MAIN_PDF_FORMAT : dol_getDefaultFormat();
	$default_font_size	= 10;
	$noCountryCode		= (empty($mysoc->country_code) ? true : false);
	if (! $noCountryCode) {
		$pid1				= $langs->transcountry('ProfId1',$mysoc->country_code);
		if ($pid1 == '-')	$pid1	= false;
		$pid2				= $langs->transcountry('ProfId2',$mysoc->country_code);
		if ($pid2 == '-')	$pid2	= false;
		$pid3				= $langs->transcountry('ProfId3',$mysoc->country_code);
		if ($pid3 == '-')	$pid3	= false;
		$pid4				= $langs->transcountry('ProfId4',$mysoc->country_code);
		if ($pid4 == '-')	$pid4	= false;
		$pid5				= $langs->transcountry('ProfId5',$mysoc->country_code);
		if ($pid5 == '-')	$pid5	= false;
	}
	else {
		$pid1	= img_warning().' <font class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</font>';
		$pid2	= img_warning().' <font class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</font>';
		$pid3	= img_warning().' <font class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</font>';
		$pid4	= img_warning().' <font class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</font>';
		$pid5	= img_warning().' <font class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</font>';
	}
	$rowSpan	= $conf->global->INFRASPLUS_PDF_HIDE_LABEL	? 34	: 35;

	// View *****************************************
	$page_name					= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsGeneralPDF');
	llxHeader('', $page_name);
	if (!empty($user->admin))	$linkback	= '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head	= infraspackplus_admin_prepare_head();
	$picto	= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'generalpdf', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	print '		<form action="'.$_SERVER["PHP_SELF"].'" method = "post" enctype="multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	print load_fiche_titre(''.$langs->trans('PDFParamGeneralDol').'</FONT>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "noborder" width = "100%">';
	$metas	= array('30px', '*', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	infraspackplus_print_btn_action('Gen', $langs->trans('InfraSPlusParamCautionSave'), $rowSpan, 3);
	if (!empty($accessright)) {
		$num	= 1;
		print '			<tr class = "oddeven">
							<td class = "center" style = "color: #382453; font-weight: bold;">'.$num.'</td>
							<td colspan = "2">
								<table width = "100%">
									<tr>
										<td width = "500px" style = "margin: 0; padding: 0; border: none;">'.$langs->trans('PDFParamMargin').'</td>
										<td style = "margin: 0; padding: 0; border: none;">
											<table>
												<tr>
													<td align = "center" style = "margin: 0; padding: 0; border: none;">
														'.$langs->trans('PDFParamMarginTop').'<br><input type = "number" size = "10" style = "text-align: center; margin: 0; padding: 0; border: none;" dir="rtl" id = "MAIN_PDF_MARGIN_TOP" name = "MAIN_PDF_MARGIN_TOP" min = "4" max = "20" value = "'.$conf->global->MAIN_PDF_MARGIN_TOP.'">
													</td>
												</tr>
												<tr>
													<td align = "center" style = "margin: 0; padding: 0; border: none;">
														'.$langs->trans('PDFParamMarginLeft').'&nbsp;<input type = "number" size = "10" style = "text-align: left; margin: 0; padding: 0; border: none;" id = "MAIN_PDF_MARGIN_LEFT" name = "MAIN_PDF_MARGIN_LEFT" min = "4" max = "20" value = "'.$conf->global->MAIN_PDF_MARGIN_LEFT.'">
														&nbsp;&nbsp;&nbsp;<input type = "number" size = "10" style = "text-align: right; margin: 0; padding: 0; border: none;" dir="rtl" id = "MAIN_PDF_MARGIN_RIGHT" name = "MAIN_PDF_MARGIN_RIGHT" min = "4" max = "20" value = "'.$conf->global->MAIN_PDF_MARGIN_RIGHT.'">&nbsp;'.$langs->trans('PDFParamMarginRight').'
													</td>
												</tr>
												<tr>
													<td align = "center" style = "margin: 0; padding: 0; border: none;">
														<input type = "number" size = "10" style = "text-align: center; margin: 0; padding: 0; border: none;" dir="rtl" id = "MAIN_PDF_MARGIN_BOTTOM" name = "MAIN_PDF_MARGIN_BOTTOM" min = "4" max = "20" value = "'.$conf->global->MAIN_PDF_MARGIN_BOTTOM.'"><br>'.$langs->trans('PDFParamMarginBottom').'
													</td>
												</tr>
											</table>
										</td>
										<td align = "right" style = "margin: 0; padding: 0; border: none;">'.$langs->trans('DictionaryPaperFormat').' : '.$formadmin->select_paper_format($selected,'MAIN_PDF_FORMAT').'</td>
									</tr>
								</table>
							</td>
						</tr>';
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '2', 'max' => '30');
		$num	= infraspackplus_print_input('MAIN_PDF_FORCE_FONT_SIZE', 'input', $langs->trans('PDFParamForceFontSize', $default_font_size), '', $metas, 1, 1, '', $num);
		infraspackplus_print_hr();
		$num	= infraspackplus_print_input('PDF_DISABLE_MYCOMPANY_LOGO', 'on_off', $langs->trans('PDFParamNoMyLogo'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_PDF_USE_LARGE_LOGO', 'on_off', $langs->trans('PDFParamLargeLogo1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('PDFParamLargeLogo2'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_INVERT_SENDER_RECIPIENT', 'on_off', $langs->trans('PDFParamInvertSenderRecipient'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME', 'on_off', $langs->trans('PDFParamAliasIn3rdName'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_USE_COMPANY_NAME_OF_CONTACT', 'on_off', $langs->trans('PDFParamSocNameContact'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_PDF_USE_ISO_LOCATION', 'on_off', $langs->trans('PlaceCustomerAddressToIsoLocation'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_TVAINTRA_NOT_IN_ADDRESS', 'on_off', $langs->trans('PDFParamHideVATIntraInAddress'), '', array(), 1, 1, '', $num);
		if ($pid1)	$num	= infraspackplus_print_input('MAIN_PROFID1_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid1, '', array(), 1, 1, '', $num);
		else	$num++;
		if ($pid2)	$num	= infraspackplus_print_input('MAIN_PROFID2_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid2, '', array(), 1, 1, '', $num);
		else	$num++;
		if ($pid3)	$num	= infraspackplus_print_input('MAIN_PROFID3_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid3, '', array(), 1, 1, '', $num);
		else	$num++;
		if ($pid4)	$num	= infraspackplus_print_input('MAIN_PROFID4_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid4, '', array(), 1, 1, '', $num);
		else	$num++;
		if ($pid5)	$num	= infraspackplus_print_input('MAIN_PROFID5_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid5, '', array(), 1, 1, '', $num);
		else	$num++;
		infraspackplus_print_hr();
		$num	= infraspackplus_print_input('MAIN_PDF_DASH_BETWEEN_LINES', 'on_off', $langs->trans('PDFParamShowDashOnPDF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('FCKEDITOR_ENABLE_DETAILS_FULL', 'on_off', $langs->trans('PDFParamFullDetWYSIWYG'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('ADD_HTML_FORMATING_INTO_DESC_DOC', 'on_off', $langs->trans('PDFParamHTMLformatDesc'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_GENERATE_DOCUMENTS_HIDE_REF', 'on_off', $langs->trans('HideRefOnPDF'), '', array(), 1, 1, '', $num);
		if (empty($conf->global->INFRASPLUS_PDF_HIDE_LABEL))
			$num	= infraspackplus_print_input('MAIN_GENERATE_DOCUMENTS_HIDE_DESC', 'on_off', $langs->trans('HideDescOnPDF').' '.$langs->trans('GenModif'), '', array(), 1, 1, '', $num);
		else	$num++;
		$num	= infraspackplus_print_input('MAIN_DOCUMENTS_DESCRIPTION_FIRST', 'on_off', $langs->trans('PDFParamDescFirst'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME', 'on_off', $langs->trans('PDFParamHideWaightAndVolumeOnPDF').' '.$langs->trans('GenModif'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('PRODUCT_USE_UNITS', 'on_off', $langs->trans('PDFParamProdUseUnit'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('PRODUIT_PDF_MERGE_PROPAL', 'on_off', $langs->trans('PDFParamMergeProductPDF1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('PDFParamMergeProductPDF2'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('CATEGORY_ADD_DESC_INTO_DOC', 'on_off', $langs->trans('PDFParamAddDescIntoDoc'), '', array(), 1, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '2');
		$num	= infraspackplus_print_input('PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES', 'input', $langs->trans('PDFParamRefInSupplierLine'), '', $metas, 1, 1, '', $num);
		infraspackplus_print_hr();
		$num	= infraspackplus_print_input('INVOICE_USE_SITUATION', 'on_off', $langs->trans('PDFParamUseSitFac'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_PDF_HIDE_CHQ_ADDRESS', 'on_off', $langs->trans('PDFParamHideChqAddr'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN', 'on_off', $langs->trans('PDFParamOnlyBICIBAN'), '', array(), 1, 1, '', $num);
		if (!empty($conf->paypal->enabled) || !empty($conf->stripe->enabled) || !empty($conf->paybox->enabled))
			$num	= infraspackplus_print_input('PDF_SHOW_LINK_TO_ONLINE_PAYMENT', 'on_off', $langs->trans('PDFParamShowLinkOnlinePay'), '', array(), 1, 1, '', $num);
		else	$num++;
		$num	= infraspackplus_print_input('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS', 'on_off', $langs->trans('PDFParamFactureDepositAsPayments'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('PROPALE_PDF_HIDE_PAYMENTTERMCOND', 'on_off', $langs->trans('PDFParamHidePayCond'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('PROPALE_PDF_HIDE_PAYMENTTERMMOD', 'on_off', $langs->trans('PDFParamHidePayMode'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INVOICE_NO_PAYMENT_DETAILS', 'on_off', $langs->trans('PDFParamNoPayDetInv'), '', array(), 1, 1, '', $num);
	}
	print '			</table>';
	if (!empty($user->admin)) {
		print '		<table width = "100%">
						<tr>
							<td align = "center"><a href="'.DOL_URL_ROOT.'/admin/pdf.php">'.$langs->trans('PDFParamBackToPDFConf').'</a></td>
						</tr>';
	}
	print '			</table>
					<br>';
	dol_fiche_end();
	llxFooter();
	$db->close();
?>