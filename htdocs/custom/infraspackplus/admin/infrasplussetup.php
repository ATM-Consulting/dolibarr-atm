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
	* 	\file		../infraspackplus/admin/infrasplussetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraS
	************************************************/
	function num_col(&$selectvalue, $listselect)
	{
		$nbCol	= is_array($listselect)	? count($listselect) + 1 : 12;
		$nums	= array('options' => '', 'err' => 0);
		for ($i = 1 ; $i < $nbCol ; $i++) {
			$afficher							= $i < 10 ? '0'.$i : $i;
			$nums['options']					.= '<option name = "'.$selectvalue['select'].'" value = "'.$i.'"';
			if ($selectvalue['value'] == $i)	$nums['options']	.= ' selected';
			$nums['options']					.= '>'.$afficher.'</option>';
		}
		foreach ($listselect as $selectvalues)	if ($selectvalues['select'] != $selectvalue['select'] && $selectvalues['value'] == $selectvalue['value'])	$nums['err']++;
		return $nums;
	}

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
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'companies', 'orders', 'sendings', 'contracts', 'bills', 'errors', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright					= !empty($user->admin) || !empty($user->rights->infraspackplus->paramBkpRest) ? 2 : (!empty($user->rights->infraspackplus->paramInfraSPlus) ? 1 : 0);
	if (empty($accessright))		accessforbidden();

	// Actions **************************************
	$form							= new Form($db);
	$formfile						= new FormFile($db);
	$formother						= new FormOther($db);
	$formcompany					= new FormCompany($db);
	$confirm_mesg					= '';
	$action							= GETPOST('action','alpha');
	$confirm						= GETPOST('confirm', 'alpha');
	$urlfile						= GETPOST('urlfile', 'alpha');
	$result							= '';
	$cgxdir							= !empty($conf->mycompany->multidir_output[$conf->entity])	? $conf->mycompany->multidir_output[$conf->entity]	: $conf->mycompany->dir_output;
	// Sauvegarde / Restauration
	if ($action == 'bkupParams')	$result	= infraspackplus_bkup_module ('infraspackplus');
	if ($action == 'restoreParams')	$result	= infraspackplus_restore_module ('infraspackplus');
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		if (preg_match('/INFRASPLUS_PDF_(FRM_E|FRM_R|TBL|SIGN)_LINE_DASH_(0|1|2|4)/', $confkey, $reg2)) {
			$listReg	= array(0, 1, 2, 4);
			foreach ($listReg as $key) {
				if ($reg2[2] == $key)	continue;
				$result					= dolibarr_set_const($db, 'INFRASPLUS_PDF_'.$reg2[1].'_LINE_DASH_'.$key,	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
			$inputFrmLineDashCleanValue		= ($reg2[1] == 'FRM_E' && $reg2[2] > 0) ? 1 : '';
			$inputFrmRLineDashCleanValue	= ($reg2[1] == 'FRM_R' && $reg2[2] > 0) ? 1 : '';
			$inputTblLineDashCleanValue		= ($reg2[1] == 'TBL' && $reg2[2] > 0) ? 1 : '';
			$inputSignLineDashCleanValue	= ($reg2[1] == 'SIGN' && $reg2[2] > 0) ? 1 : '';
		}
		// automatic switch between num and ref column
		if ($confkey == 'INFRASPLUS_PDF_WITH_NUM_COLUMN' && GETPOST('value') == 1)	$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_REF_COLUMN',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		if ($confkey == 'INFRASPLUS_PDF_WITH_REF_COLUMN' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'MAIN_GENERATE_DOCUMENTS_HIDE_REF',		1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_NUM_COLUMN',			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Gen'	=> array('INFRASPLUS_PDF_ROUNDED_REC',			'INFRASPLUS_PDF_FACTURE_PAID_WATERMARK'),
							'Head'	=> array('INFRASPLUS_PDF_TITLE_SIZE', 			'INFRASPLUS_PDF_FRM_E_LINE_WIDTH',		'INFRASPLUS_PDF_FRM_E_LINE_DASH',	'INFRASPLUS_PDF_FRM_E_OPACITY',
											'INFRASPLUS_PDF_FRM_R_LINE_WIDTH',		'INFRASPLUS_PDF_FRM_R_LINE_DASH',		'INFRASPLUS_PDF_FRM_R_OPACITY',		'INFRASPLUS_PDF_FOLD_MARK',
											'INFRASPLUS_PDF_SPACE_HEADERAFTER'),
							'Body'	=> array('INFRASPLUS_PDF_HEIGHT_TOP_TABLE',		'INFRASPLUS_PDF_TBL_LINE_WIDTH',		'INFRASPLUS_PDF_TBL_LINE_DASH',		'INFRASPLUS_PDF_LINESEP_HIGHT',
											'INFRASPLUS_PDF_EXF_PROD_POS',			'INFRASPLUS_PDF_HT_BC',					'INFRASPLUS_PDF_LARG_BC',			'INFRASPLUS_PDF_DIM_C2D',
											'INFRASPLUS_PDF_NUMCOL_REF',			'INFRASPLUS_PDF_NUMCOL_DESC',			'INFRASPLUS_PDF_NUMCOL_QTY',		'INFRASPLUS_PDF_NUMCOL_UNIT',
											'INFRASPLUS_PDF_NUMCOL_UP',				'INFRASPLUS_PDF_NUMCOL_TVA',			'INFRASPLUS_PDF_NUMCOL_DISC',		'INFRASPLUS_PDF_NUMCOL_UPD',
											'INFRASPLUS_PDF_NUMCOL_PROGRESS',		'INFRASPLUS_PDF_NUMCOL_TOTAL',			'INFRASPLUS_PDF_NUMCOL_TOTAL_TTC',
											'INFRASPLUS_PDF_LARGCOL_REF',			'INFRASPLUS_PDF_LARGCOL_QTY',			'INFRASPLUS_PDF_LARGCOL_UNIT',
											'INFRASPLUS_PDF_LARGCOL_UP',			'INFRASPLUS_PDF_LARGCOL_TVA',			'INFRASPLUS_PDF_LARGCOL_DISC',		'INFRASPLUS_PDF_LARGCOL_UPD',
											'INFRASPLUS_PDF_LARGCOL_PROGRESS',		'INFRASPLUS_PDF_LARGCOL_TOTAL',			'INFRASPLUS_PDF_LARGCOL_TOTAL_TTC',
											'INFRASPLUS_PDF_NUMCOLBL_REF',			'INFRASPLUS_PDF_NUMCOLBL_EFL',			'INFRASPLUS_PDF_NUMCOLBL_DESC',		'INFRASPLUS_PDF_NUMCOLBL_WV',
											'INFRASPLUS_PDF_NUMCOLBL_UNIT',			'INFRASPLUS_PDF_NUMCOLBL_ORDERED',		'INFRASPLUS_PDF_NUMCOLBL_REL',		'INFRASPLUS_PDF_NUMCOLBL_QTY',
											'INFRASPLUS_PDF_LARGCOLBL_REF',			'INFRASPLUS_PDF_LARGCOLBL_EFL',			'INFRASPLUS_PDF_LARGCOLBL_WV',		'INFRASPLUS_PDF_LARGCOLBL_UNIT',
											'INFRASPLUS_PDF_LARGCOLBL_ORDERED',		'INFRASPLUS_PDF_LARGCOLBL_REL',			'INFRASPLUS_PDF_LARGCOLBL_QTY',
											'INFRASPLUS_PDF_NUMCOLBR_REF',			'INFRASPLUS_PDF_NUMCOLBR_DESC',			'INFRASPLUS_PDF_NUMCOLBR_COMM',		'INFRASPLUS_PDF_NUMCOLBR_UNIT',
											'INFRASPLUS_PDF_NUMCOLBR_ORDERED',		'INFRASPLUS_PDF_NUMCOLBR_REL',			'INFRASPLUS_PDF_NUMCOLBR_QTY',
											'INFRASPLUS_PDF_LARGCOLBR_REF',			'INFRASPLUS_PDF_LARGCOLBR_COMM',		'INFRASPLUS_PDF_LARGCOLBL_UNIT',	'INFRASPLUS_PDF_LARGCOLBR_ORDERED',
											'INFRASPLUS_PDF_LARGCOLBR_REL',			'INFRASPLUS_PDF_LARGCOLBR_QTY',
								            'INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH',	'INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE',	'INFRASPLUS_PDF_TEXT_OUV_STYLE',	'INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF'),
							'Foot'	=> array('INFRASPLUS_PDF_SPACE_INFO',			'INFRASPLUS_PDF_SPACE_TOT',				'INFRASPLUS_PDF_PAY_INLINE',		'INFRASPLUS_PDF_PAY_SPEC',
											'INFRASPLUS_PDF_TVA_FORFAIT',			'INFRASPLUS_PDF_HT_SIGN_AREA',			'INFRASPLUS_PDF_SIGN_LINE_WIDTH',	'INFRASPLUS_PDF_SIGN_LINE_DASH'),
							'FootP'	=> array('INFRASPLUS_PDF_FOOTER_FREETEXT',		'INFRASPLUS_PDF_X_PAGE_NUM',			'INFRASPLUS_PDF_Y_PAGE_NUM'),
							'CGx'	=> array('INFRASPLUS_PDF_CGV_FROM_PRO_LABEL',	'INFRASPLUS_PDF_CGV',					'INFRASPLUS_PDF_CGI',				'INFRASPLUS_PDF_CGA')
							);
		$listcolor	= array('Gen'	=> array('INFRASPLUS_PDF_BODY_TEXT_COLOR'),
							'Head'	=> array('INFRASPLUS_PDF_HEADER_TEXT_COLOR',	'INFRASPLUS_PDF_FACT_DATEDUE_COLOR',	'INFRASPLUS_PDF_FRM_E_LINE_COLOR',	'INFRASPLUS_PDF_FRM_E_BG_COLOR',
											'INFRASPLUS_PDF_FRM_E_TEXT_COLOR',		'INFRASPLUS_PDF_FRM_R_LINE_COLOR',		'INFRASPLUS_PDF_FRM_R_BG_COLOR',	'INFRASPLUS_PDF_FRM_R_TEXT_COLOR'),
							'Body'	=> array('INFRASPLUS_PDF_BACKGROUND_COLOR',		'INFRASPLUS_PDF_TEXT_COLOR',
											'INFRASPLUS_PDF_TBL_LINE_COLOR',		'INFRASPLUS_PDF_HOR_LINE_COLOR',		'INFRASPLUS_PDF_VER_LINE_COLOR',
											'INFRASPLUS_PDF_BODY_SUBTI_COLOR',		'INFRASPLUS_PDF_TEXT_SUBTI_COLOR',		'INFRASPLUS_PDF_TEXT_SUBTO_COLOR',
											'INFRASPLUS_PDF_BODY_OUV_COLOR',		'INFRASPLUS_PDF_TEXT_OUV_COLOR',		'INFRASPLUS_PDF_DESC_FULL_LINE_COLOR', 'INFRASPLUS_PDF_DESC_PERIOD_COLOR'),
							'Foot'	=> array('INFRASPLUS_PDF_SIGN_LINE_COLOR', 		'INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR'),
							'FootP'	=> array(),
							'CGx'	=> array()
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= $constname == 'INFRASPLUS_PDF_ROUNDED_REC' ? (GETPOST($constname, 'alpha') == 0 ? 0.001 : GETPOST($constname, 'alpha')) : GETPOST($constname, 'none');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		foreach ($listcolor[$confkey] as $constname) {
			$constvalue	= implode(', ', colorStringToArray(GETPOST($constname, 'alpha')));
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'Gen')	$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_FONT', GETPOST('defaultfont'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Comportement général -> génération automatique, 1 fichier par modèle
	// Apparence générale -> police, couleur de texte, style des en-têtes et des cadres, fond, symbol monétaire
	if ($action == 'setfont') {
		$extension	= pathinfo($_FILES['fontfile']['name'], PATHINFO_EXTENSION);
		if ($extension == 'ttf' || $extension == 'TTF') {
			$pathfonts	= dol_buildpath('infraspackplus', 0).'/ttf/';
			$fontfile	= $_FILES['fontfile']['tmp_name'];
			$dest_file	= $_FILES['fontfile']['name'];
			$moved		= dol_move_uploaded_file($fontfile, $pathfonts.$dest_file, 1, 0, $_FILES['fontfile']['error']);
			if ($moved > 0) {
				$outpath					= dol_buildpath('infraspackplus', 0).'/tmp/';
				$fontname					= infraspackplus_Add_TCPDF_Font ('TrueTypeUnicode', '', 32, $outpath, 3, 1, true, false, $pathfonts.$dest_file);
				if ($fontname === false)	setEventMessages($langs->trans('InfraSPlusParamAddFontKo', $fontname), null, 'errors');
				else {
					dolCopyDir($outpath, dol_buildpath('infraspackplus', 0).'/fonts', 0, 1);
					dolCopyDir($outpath, TCPDF_PATH.'fonts', 0, 1);
					array_map('unlink', glob($outpath.'*'));
					setEventMessages($langs->trans('InfraSPlusParamAddFontOk', $fontname), null, 'mesgs');
				}
			}
		}
		else	setEventMessages($langs->trans('InfraSPlusParamAddTTFKo', $_FILES['fontfile']['name']), null, 'errors');
	}
	if ($action == 'testfont')	$resultTest	= infraspackplus_test_font();
	if ($resultTest == 1)		header('Location: '.DOL_URL_ROOT.'/document.php?modulepart=ecm&attachment=0&file=temp/TEST.pdf&entity='.$conf->entity, false);
	// Haut de page -> cadres, contenu des en-têtes, adresses, note, pliage, filigrame, dommées additionnelles (douanes)
	// Contenu, colonnage -> Colonnes additionnelles et masquées (référence, tva, remises), taille et position
	// Pied de document -> encours, total des remises, multi-devises, number-words, zones de signature, mentions complémentaires
	if (!empty($conf->global->INFRASPLUS_PDF_NUMBER_WORDS) && !in_array('numberwords', $conf->modules)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_NUMBER_WORDS', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		setEventMessages($langs->trans('InfraSPlusParamErrorNumWords'), null, 'errors');
	}
	if ($action == 'modifyPaySpec')	$result	= infraspackplus_modify_paiement_spec ();
	// Pied de page -> Lignes d'informations supplémentaires, n° de page, LCR
	if (preg_match('/set_INFRASPLUS_PDF_TYPE_FOOT_(.*)/', $action, $reg) || preg_match('/set_INFRASPLUS_PDF_HIDE_RECEP_FRAME/', $action, $reg2)) {
		$footAdress		= $conf->global->INFRASPLUS_PDF_HIDE_RECEP_FRAME ? '1' : dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_ADDRESS', $conf->entity);
		$footContacts	= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_CONTACTS', $conf->entity);
		$footManager	= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_MANAGER', $conf->entity);
		$footTypeSoc	= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_TYPESOC', $conf->entity);
		$footIds		= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_IDS', $conf->entity);
		$footAdress2	= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_ADDRESS2', $conf->entity);
		$typefoot1		= $footAdress	? ($footContacts	? '3' : '1') : ($footContacts	? '2' : '0');	// 1er digit = 0 (no address nor contact) / 1 (address only) / 2 (contact only) /3 (address and contact)
		$typefoot2		= $footManager	? '1' : '0';	// 2ème digit = 0(no manager) / 1 (manager)
		$typefoot3		= $footTypeSoc	? ($footIds			? '3' : '1') : ($footIds		? '2' : '0');	// 3ème digit = 0 (no type nor IDs) / 1 (type only) / 2 (Ids only) /3 (type and IDs)
		$typefoot4		= $footAdress2	? '1' : '0';	// 4ème digit = 0(1 line for address) / 1 (2 lines for address)
		$typefoot		= $typefoot1.$typefoot2.$typefoot3.$typefoot4;
		$result			= dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT',	$typefoot, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Conditions générales -> vente, interventions, achats
	if($action == 'addcgv') {
		$extension		= pathinfo($_FILES['CGVFile']['name'], PATHINFO_EXTENSION);
		$dest_file		= GETPOST('typeCG', 'alpha').'_'.GETPOST('CGVName', 'alpha').'.'.$extension;
		$moved			= dol_move_uploaded_file($_FILES['CGVFile']['tmp_name'], $cgxdir.'/'.$dest_file, 1, 0, $_FILES['CGVFile']['error']);
		if ($moved > 0)	setEventMessages($dest_file.' : '.$langs->trans('FileSaved'), null, 'mesgs');
		else if ($moved !== 1) {	// errors
			if ($moved < 0)	setEventMessages('UknownFileUploadError', null, 'errors');	// API documented error
			else			setEventMessages($moved, null, 'errors');	// We got an error string /o\
		}
	}
	if ($action == 'delete')	$confirm_mesg	= $form->formconfirm($_SERVER['PHP_SELF'].'?urlfile='.$urlfile, $langs->trans('InfraSPlusParamDeleteAFile'), $langs->trans('InfraSPlusParamConfirmDeleteAFile').' '.$urlfile.' ?', 'delete_ok', '', 1, (int) $conf->use_javascript_ajax);
	if ($action == 'delete_ok' && $confirm == 'yes') {
		$urlfile_dirname	= pathinfo($urlfile, PATHINFO_DIRNAME);
		$urlfile_filename	= pathinfo($urlfile, PATHINFO_FILENAME);
		$urlfile_ext		= pathinfo($urlfile, PATHINFO_EXTENSION);
		$a					= dol_delete_file($cgxdir.$urlfile, 1);
		if ($a)				setEventMessages($urlfile_filename.'.'.$urlfile_ext.' '.$langs->trans('Deleted'), null, 'mesgs');
		else				setEventMessages($langs->trans('ErrorFailToDeleteFile', $urlfile), null, 'errors');
	}
	// Retour => message Ok ou Ko
	if ($result == 1)	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	if ($result == -1)	setEventMessages($langs->trans('Error'), null, 'errors');

	// init variables *******************************
	// Sauvegarde / Restauration
	// Comportement général -> génération automatique, 1 fichier par modèle
	// Apparence générale -> police, couleur de texte, style des en-têtes et des cadres, fond
	$selected_font	= isset($conf->global->INFRASPLUS_PDF_FONT)	? $conf->global->INFRASPLUS_PDF_FONT : 'centurygothic';
	$dirfonts		= DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/fonts/';
	$listfonts		= dol_dir_list($dirfonts, 'files');
	$listfontuse	= array();
	foreach ($listfonts as $font) {
		$extension	= pathinfo($font['name'], PATHINFO_EXTENSION);
		if ($extension == 'php') {
			$fontname			= pathinfo($font['name'], PATHINFO_FILENAME);
			include_once ($font['fullname']);
			if ($name != '')	$listfontuse[]	= array('name' => $name, 'fontname' => $fontname);
			$name				= '';
		}
	}
	// Haut de page -> cadres, contenu des en-têtes, adresses, note, pliage, filigrame, dommées additionnelles (douanes)
	if (!empty($conf->global->INFRASPLUS_PDF_HEADER_AFTER_ADDR)) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_SMALL_HEAD_2', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_NUM_CLI_FRM', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (!empty($conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH_1))		$inputFrmLineDash	= 'size = "2" value = "'.($inputFrmLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W" pattern = "^(0?[1-9]|[1-2][0-9]|30)$"';
	elseif (!empty($conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH_2))	$inputFrmLineDash	= 'size = "5" value = "'.($inputFrmLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W,X" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	elseif (!empty($conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH_4))	$inputFrmLineDash	= 'size = "8" value = "'.($inputFrmLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W,X,Y,Z" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	else {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_FRM_E_LINE_DASH_0',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$inputFrmLineDash	= 'size = "1" value = "" readonly';
	}
	if (!empty($conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH_1))		$inputFrmRLineDash	= 'size = "2" value = "'.($inputFrmRLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W" pattern = "^(0?[1-9]|[1-2][0-9]|30)$"';
	elseif (!empty($conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH_2))	$inputFrmRLineDash	= 'size = "5" value = "'.($inputFrmRLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W,X" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	elseif (!empty($conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH_4))	$inputFrmRLineDash	= 'size = "8" value = "'.($inputFrmRLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W,X,Y,Z" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	else {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_FRM_R_LINE_DASH_0',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$inputFrmRLineDash	= 'size = "1" value = "" readonly';
	}
	$noCountryCode	= (empty($mysoc->country_code) ? true : false);
	if (! $noCountryCode) {
		$pid1				= $langs->transcountry("ProfId1", $mysoc->country_code);
		if ($pid1 == '-')	$pid1	= false;
		$pid2				= $langs->transcountry("ProfId2", $mysoc->country_code);
		if ($pid2 == '-')	$pid2	= false;
		$pid3				= $langs->transcountry("ProfId3", $mysoc->country_code);
		if ($pid3 == '-')	$pid3	= false;
		$pid4				= $langs->transcountry("ProfId4", $mysoc->country_code);
		if ($pid4 == '-')	$pid4	= false;
		$pid5				= $langs->transcountry("ProfId5", $mysoc->country_code);
		if ($pid5 == '-')	$pid5	= false;
	}
	else {
		$pid1	= img_warning().' <font class = "error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
		$pid2	= img_warning().' <font class = "error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
		$pid3	= img_warning().' <font class = "error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
		$pid4	= img_warning().' <font class = "error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
		$pid5	= img_warning().' <font class = "error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
	}
	$rowSpan3	= $conf->global->INFRASPLUS_PDF_HIDE_RECEP_FRAME														? 38	: 53;
	$rowSpan3	+= $conf->global->MAIN_MODULE_PRODUCT																	? 1		: 0;
	$rowSpan3	+= empty($conf->global->INFRASPLUS_PDF_HEADER_AFTER_ADDR)												? 2		: 0;
	$rowSpan3	+= !empty($conf->global->INFRASPLUS_PDF_DATES_BR)														? 1		: 0;
	$rowSpan3	+= $conf->global->INFRASPLUS_PDF_SHOW_REF_ORDER															? 1		: 0;
	$rowSpan3	+= !empty($conf->projet->enabled)																		? 1		: 0;
	$rowSpan3	+= $conf->global->INFRASPLUS_PDF_SHOW_REF_PROJECT														? 1		: 0;
	$rowSpan3	+= $conf->global->INFRASPLUS_PDF_SHOW_NUM_CLI && empty($conf->global->INFRASPLUS_PDF_HEADER_AFTER_ADDR)	? 1		: 0;
	$rowSpan3	+= $conf->global->INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT													? 1		: 0;
	// Contenu, colonnage -> Colonnes additionnelles et masquées (référence, tva, remises), taille et position
	if (!empty($conf->global->INFRASPLUS_PDF_TBL_LINE_DASH_1))		$inputTblLineDash	= 'size = "2" value = "'.($inputTblLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_TBL_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_TBL_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W" pattern = "^(0?[1-9]|[1-2][0-9]|30)$"';
	elseif (!empty($conf->global->INFRASPLUS_PDF_TBL_LINE_DASH_2))	$inputTblLineDash	= 'size = "5" value = "'.($inputTblLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_TBL_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_TBL_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W,X" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	elseif (!empty($conf->global->INFRASPLUS_PDF_TBL_LINE_DASH_4))	$inputTblLineDash	= 'size = "8" value = "'.($inputTblLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_TBL_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_TBL_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W,X,Y,Z" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	else {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_TBL_LINE_DASH_0',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$inputTblLineDash	= 'size = "1" value = "" readonly';
	}
	if ($conf->global->INFRASPLUS_PDF_HIDE_LABEL)				dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DESC", 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, "INFRASPLUS_PDF_DIM_C2D", ($conf->global->INFRASPLUS_PDF_DIM_C2D > $conf->global->INFRASPLUS_PDF_LARG_BC ? $conf->global->INFRASPLUS_PDF_LARG_BC : $conf->global->INFRASPLUS_PDF_DIM_C2D), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if (! $conf->global->MAIN_MODULE_MANAGEMENT)	dolibarr_set_const($db, "INFRASPLUS_PDF_SHOW_DATES_HOURS_FI", 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if (!empty($conf->global->INFRASPLUS_PDF_DISCOUNT_AUTO)) {
		$infoDiscountAuto	= ' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamDiscountAutoInfo');
		dolibarr_set_const($db, "INFRASPLUS_PDF_HIDE_UP",				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, "INFRASPLUS_PDF_HIDE_DISCOUNT",			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, "INFRASPLUS_PDF_SHOW_UP_DISCOUNTED",	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	else																$infoDiscountAuto	= '';
	$alignLeftRef														= !empty($conf->global->INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF) ? $conf->global->INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF : 'L';
	if (empty($conf->global->INFRASPLUS_PDF_DESC_FULL_LINE))			$result				= dolibarr_set_const($db, 'INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if (!empty($conf->global->INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN))	$result	= dolibarr_set_const($db, 'MAIN_GENERATE_DOCUMENTS_HIDE_REF', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	$result																= dolibarr_set_const($db, 'MAIN_GENERATE_DOCUMENTS_HIDE_DESC',	dolibarr_get_const($db, 'INFRASPLUS_PDF_SHOW_DESC_DEV', $conf->entity), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if (!empty($conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT)) {
		if (!empty($conf->global->INFRASPLUS_PDF_SHOW_UP_DISCOUNTED))	setEventMessages($langs->trans('InfraSPlusParamShowUPDiscountedKo1').' <FONT color = "red">'.$langs->trans('InfraSPlusParamShowUPDiscountedKo2').'</FONT> '.$langs->trans('InfraSPlusParamShowUPDiscountedKo3').' <FONT color = "red">'.$langs->trans('InfraSPlusParamShowUPDiscountedKo4').'</FONT> !', null, 'warnings');
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_SHOW_UP_DISCOUNTED',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (!empty($conf->global->INFRASPLUS_PDF_WITH_TTC_COLUMN)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC',			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT',				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USE_TVA_FORFAIT',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (!empty($conf->global->INFRASPLUS_PDF_WITHOUT_VAT_COLUMN)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC',			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT',				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (!empty($conf->global->INFRASPLUS_PDF_TTC_WITH_VAT_TOT)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_TTC_COLUMN',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITHOUT_VAT_COLUMN',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC',			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT',				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (!empty($conf->global->INFRASPLUS_PDF_ONLY_TTC)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_TTC_COLUMN',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITHOUT_VAT_COLUMN',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT',				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USE_TVA_FORFAIT',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HT_BY_VAT_P_OR_S',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (!empty($conf->global->INFRASPLUS_PDF_ONLY_HT)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_TTC_COLUMN',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITHOUT_VAT_COLUMN',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC',			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USE_TVA_FORFAIT',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HT_BY_VAT_P_OR_S',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (!empty($conf->global->INFRASPLUS_PDF_USE_TVA_FORFAIT)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_TTC_COLUMN',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITHOUT_VAT_COLUMN',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC',			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT',				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HT_BY_VAT_P_OR_S',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	$country		= !empty($mysoc->country_code)				? $mysoc->country_code	: substr($langs->defaultlang, -2);
	$TVAforfaitaire	= $country == 'CH' && !$mysoc->tva_assuj	? 1						: 0;
	$listselect		= array(array('select' => 'INFRASPLUS_PDF_NUMCOL_REF',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_REF)			? $conf->global->INFRASPLUS_PDF_NUMCOL_REF			: 1),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_DESC',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_DESC)			? $conf->global->INFRASPLUS_PDF_NUMCOL_DESC			: 2),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_QTY',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_QTY)			? $conf->global->INFRASPLUS_PDF_NUMCOL_QTY			: 3),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_UNIT',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_UNIT)			? $conf->global->INFRASPLUS_PDF_NUMCOL_UNIT			: 4),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_UP',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_UP)			? $conf->global->INFRASPLUS_PDF_NUMCOL_UP			: 5),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_TVA',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_TVA)			? $conf->global->INFRASPLUS_PDF_NUMCOL_TVA			: 6),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_DISC',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_DISC)			? $conf->global->INFRASPLUS_PDF_NUMCOL_DISC			: 7),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_UPD',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_UPD)			? $conf->global->INFRASPLUS_PDF_NUMCOL_UPD			: 8),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_PROGRESS',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_PROGRESS)		? $conf->global->INFRASPLUS_PDF_NUMCOL_PROGRESS		: 9),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_TOTAL',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_TOTAL)		? $conf->global->INFRASPLUS_PDF_NUMCOL_TOTAL		: 10),
							array('select' => 'INFRASPLUS_PDF_NUMCOL_TOTAL_TTC',	'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOL_TOTAL_TTC)	? $conf->global->INFRASPLUS_PDF_NUMCOL_TOTAL_TTC	: 11));
	$listcol		= array($langs->transnoentities(!empty($conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN) ? "PDFInfraSPlusNum" : "PDFInfraSPlusRef"),
							$langs->transnoentities("Designation"),
							$langs->transnoentities("Qty"),
							$langs->transnoentities("Unit"),
							$langs->transnoentities("PriceU"),
							$langs->transnoentities("VAT"),
							$langs->transnoentities("ReductionShort"),
							$langs->transnoentities("PDFInfraSPlusDiscountedPrice"),
							'('.$langs->transnoentities("Situation").')*',
							$langs->transnoentities(!empty($conf->global->INFRASPLUS_PDF_TTC_WITH_VAT_TOT) || !empty($conf->global->INFRASPLUS_PDF_ONLY_TTC) ? "TotalTTC" : "TotalHT"),
							$langs->transnoentities(!empty($conf->global->INFRASPLUS_PDF_WITH_TTC_COLUMN) ? "TotalTTC" : "-"));
	$listlarg		= array(array('key' => 'INFRASPLUS_PDF_LARGCOL_REF',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_REF)			? $conf->global->INFRASPLUS_PDF_LARGCOL_REF			: 28),
							array('key' => 'DESC',									'value' => 0),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_QTY',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_QTY)			? $conf->global->INFRASPLUS_PDF_LARGCOL_QTY			: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_UNIT',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_UNIT)		? $conf->global->INFRASPLUS_PDF_LARGCOL_UNIT		: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_UP',				'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_UP)			? $conf->global->INFRASPLUS_PDF_LARGCOL_UP			: 22),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_TVA',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_TVA)			? $conf->global->INFRASPLUS_PDF_LARGCOL_TVA			: 14),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_DISC',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_DISC)		? $conf->global->INFRASPLUS_PDF_LARGCOL_DISC		: 14),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_UPD',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_UPD)			? $conf->global->INFRASPLUS_PDF_LARGCOL_UPD			: 22),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_PROGRESS',		'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_PROGRESS)	? $conf->global->INFRASPLUS_PDF_LARGCOL_PROGRESS	: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_TOTAL',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_TOTAL)		? $conf->global->INFRASPLUS_PDF_LARGCOL_TOTAL		: 24),
							array('key' => 'INFRASPLUS_PDF_LARGCOL_TOTAL_TTC',		'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOL_TOTAL_TTC)	? $conf->global->INFRASPLUS_PDF_LARGCOL_TOTAL_TTC	: 24));
	$listselectBL	= array(array('select' => 'INFRASPLUS_PDF_NUMCOLBL_REF',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBL_REF)		? $conf->global->INFRASPLUS_PDF_NUMCOLBL_REF		: 1),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBL_EFL',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBL_EFL)		? $conf->global->INFRASPLUS_PDF_NUMCOLBL_EFL		: 2),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBL_DESC',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBL_DESC)		? $conf->global->INFRASPLUS_PDF_NUMCOLBL_DESC		: 3),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBL_WV',			'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBL_WV)			? $conf->global->INFRASPLUS_PDF_NUMCOLBL_WV			: 4),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBL_UNIT',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBL_UNIT)		? $conf->global->INFRASPLUS_PDF_NUMCOLBL_UNIT		: 5),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBL_ORDERED',	'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBL_ORDERED)	? $conf->global->INFRASPLUS_PDF_NUMCOLBL_ORDERED	: 6),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBL_REL',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBL_REL)		? $conf->global->INFRASPLUS_PDF_NUMCOLBL_REL		: 7),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBL_QTY',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBL_QTY)		? $conf->global->INFRASPLUS_PDF_NUMCOLBL_QTY		: 8));
	$listcolBL		= array($langs->transnoentities(!empty($conf->global->INFRASPLUS_PDF_BL_WITH_BC_COLUMN) ? 'PDFInfraSPlusCB' : 'PDFInfraSPlusRef'),
							$langs->transnoentities('InfraSPlusParamColEFLBL'),
							$langs->transnoentities('Designation'),
							$langs->transnoentities('WeightVolShort'),
							$langs->transnoentities('Unit'),
							$langs->transnoentities('PDFInfraSPlusExpeditionOrdered'),
							$langs->transnoentities('PDFInfraSPlusExpeditionbackorder'),
							$langs->transnoentities('PDFInfraSPlusExpeditionShipped'));
	$listlargBL		= array(array('key' => 'INFRASPLUS_PDF_LARGCOLBL_REF',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBL_REF)		? $conf->global->INFRASPLUS_PDF_LARGCOLBL_REF		: 28),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBL_EFL',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBL_EFL)		? $conf->global->INFRASPLUS_PDF_LARGCOLBL_EFL		: 14),
							array('key' => 'DESC',									'value' => 0),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBL_WV',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBL_WV)		? $conf->global->INFRASPLUS_PDF_LARGCOLBL_WV		: 22),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBL_UNIT',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBL_UNIT)		? $conf->global->INFRASPLUS_PDF_LARGCOLBL_UNIT		: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBL_ORDERED',		'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBL_ORDERED)	? $conf->global->INFRASPLUS_PDF_LARGCOLBL_ORDERED	: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBL_REL',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBL_REL)		? $conf->global->INFRASPLUS_PDF_LARGCOLBL_REL		: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBL_QTY',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBL_QTY)		? $conf->global->INFRASPLUS_PDF_LARGCOLBL_QTY		: 10));
	$listselectBR	= array(array('select' => 'INFRASPLUS_PDF_NUMCOLBR_REF',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_REF)		? $conf->global->INFRASPLUS_PDF_NUMCOLBR_REF		: 1),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBR_DESC',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_DESC)		? $conf->global->INFRASPLUS_PDF_NUMCOLBR_DESC		: 2),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBR_COMM',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_COMM)		? $conf->global->INFRASPLUS_PDF_NUMCOLBR_COMM		: 3),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBR_UNIT',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_UNIT)		? $conf->global->INFRASPLUS_PDF_NUMCOLBR_UNIT		: 4),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBR_ORDERED',	'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_ORDERED)	? $conf->global->INFRASPLUS_PDF_NUMCOLBR_ORDERED	: 5),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBR_REL',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_REL)		? $conf->global->INFRASPLUS_PDF_NUMCOLBR_REL		: 6),
							array('select' => 'INFRASPLUS_PDF_NUMCOLBR_QTY',		'value' => isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_QTY)		? $conf->global->INFRASPLUS_PDF_NUMCOLBR_QTY		: 7));
	$listcolBR		= array($langs->transnoentities(!empty($conf->global->INFRASPLUS_PDF_BL_WITH_BC_COLUMN) ? 'PDFInfraSPlusCB' : 'PDFInfraSPlusRef'),
							$langs->transnoentities('Designation'),
							$langs->transnoentities('PDFInfraSPlusReceiptComments'),
							$langs->transnoentities('Unit'),
							$langs->transnoentities('PDFInfraSPlusExpeditionOrdered'),
							$langs->transnoentities('PDFInfraSPlusExpeditionbackorder'),
							$langs->transnoentities('PDFInfraSPlusExpeditionShipped'));
	$listlargBR		= array(array('key' => 'INFRASPLUS_PDF_LARGCOLBR_REF',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_REF)		? $conf->global->INFRASPLUS_PDF_LARGCOLBR_REF		: 28),
							array('key' => 'DESC',									'value' => 0),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBR_COMM',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_COMM)		? $conf->global->INFRASPLUS_PDF_LARGCOLBR_COMM		: 50),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBR_UNIT',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_UNIT)		? $conf->global->INFRASPLUS_PDF_LARGCOLBR_UNIT		: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBR_ORDERED',		'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_ORDERED)	? $conf->global->INFRASPLUS_PDF_LARGCOLBR_ORDERED	: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBR_REL',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_REL)		? $conf->global->INFRASPLUS_PDF_LARGCOLBR_REL		: 10),
							array('key' => 'INFRASPLUS_PDF_LARGCOLBR_QTY',			'value' => isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_QTY)		? $conf->global->INFRASPLUS_PDF_LARGCOLBR_QTY		: 10));
	$marge_gauche	= isset($conf->global->MAIN_PDF_MARGIN_LEFT)	? $conf->global->MAIN_PDF_MARGIN_LEFT	: 10;
	$marge_droite	= isset($conf->global->MAIN_PDF_MARGIN_RIGHT)	? $conf->global->MAIN_PDF_MARGIN_RIGHT	: 10;
	$largutil		= $marge_gauche + $marge_droite;
	$formatarray	= pdf_getFormat();
	foreach ($listlarg as $largs)
		if ($largs['key'] != 'DESC')	$largutil	+= $largs['value'];
	$larg_desc_progress		= $formatarray['width'] - $largutil;
	$larg_desc				= $larg_desc_progress + $listlarg[8]['value'];
	$listlarg[1]['value']	= $larg_desc.' / ('.$larg_desc_progress.')*';
	$largutilBL				= $marge_gauche + $marge_droite;
	foreach ($listlargBL as $largsBL)
		if ($largsBL['key'] != 'DESC')	$largutilBL	+= $largsBL['value'];
	$listlargBL[2]['value']	= $formatarray['width'] - $largutilBL;
	$largutilBR				= $marge_gauche + $marge_droite;
	foreach ($listlargBR as $largsBR)
		if ($largsBR['key'] != 'DESC')	$largutilBR	+= $largsBR['value'];
	$listlargBR[1]['value']	= $formatarray['width'] - $largutilBR;
	$wvccopt												= 0;
	if (empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO))	$wvccopt++;
	if (empty($conf->global->PRODUCT_DISABLE_LENGTH ))		$wvccopt++;
	if (empty($conf->global->PRODUCT_DISABLE_SIZE ))		$wvccopt++;
	if (empty($conf->global->PRODUCT_DISABLE_SURFACE ))		$wvccopt++;
	if (empty($conf->global->PRODUCT_DISABLE_VOLUME ))		$wvccopt++;
	if (empty($conf->global->PRODUCT_DISABLE_WEIGHT ))		$wvccopt++;
	$rowSpan4												= $conf->global->INFRASPLUS_PDF_HIDE_LABEL												? 46														: 49;
	$rowSpan4												+= $conf->global->PRODUIT_CUSTOMER_PRICES												? 1															: 0;
	$rowSpan4												+= $conf->global->INVOICE_USE_SITUATION													? 1															: 0;
	$rowSpan4												+= $conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN && $conf->global->MAIN_MODULE_BARCODE	? 1															: 0;
	$rowSpan4												+= $conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN										? 2															: 0;
	$rowSpan4												+= $conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN										? 2															: 0;
	$rowSpan4												+= $conf->global->INFRASPLUS_PDF_DESC_FULL_LINE											? 3															: 0;
	$rowSpan4												+= empty($conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT)									? 1															: 0;
	$rowSpan4												+= $conf->global->MAIN_MODULE_BARCODE													? 4															: 0;
	$rowSpan4												+= $conf->global->INFRASPLUS_PDF_BL_WITH_POS_COLUMN										? 1															: 0;
	$rowSpan4												+= $wvccopt																				? 2															: 0;
	$rowSpan4												+= $wvccopt && $conf->global->INFRASPLUS_PDF_SHOW_WVCC									? 1															: 0;
	$rowSpan4												+= $TVAforfaitaire																		? 1															: 0;
	$rowSpan4												+= $TVAforfaitaire && $conf->global->INFRASPLUS_PDF_USE_TVA_FORFAIT						? 1															: 0;
	$rowSpan4												+= $conf->global->MAIN_MODULE_SUBTOTAL													? ($conf->global->INFRASPLUS_PDF_HIDE_BODY_SUBTO ? 6 : 5)	: 0;
	$rowSpan4												+= $conf->global->MAIN_MODULE_OUVRAGE													? 4															: 0;
	$rowSpan4												+= $conf->global->MAIN_MODULE_MILESTONE													? 2															: 0;
	$rowSpan4												+= $conf->global->MAIN_PDF_DASH_BETWEEN_LINES											? 0															: 1;
	// Pied de document -> encours, total des remises, multi-devises, number-words, zones de signature, mentions complémentaires
	if (!empty($conf->global->INFRASPLUS_PDF_HT_BY_VAT_P_OR_S)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT',			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USE_TVA_FORFAIT',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (! in_array('numberwords', $conf->modules))					dolibarr_set_const($db, 'INFRASPLUS_PDF_NUMBER_WORDS',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	if (!empty($conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH_1))		$inputSignLineDash	= 'size = "2" value = "'.($inputSignLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W" pattern = "^(0?[1-9]|[1-2][0-9]|30)$"';
	elseif (!empty($conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH_2))	$inputSignLineDash	= 'size = "5" value = "'.($inputSignLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W,X" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	elseif (!empty($conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH_4))	$inputSignLineDash	= 'size = "8" value = "'.($inputSignLineDashCleanValue ? '' : ($conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH != 0 ? $conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH : '')).'" title = "'.$langs->trans("InfraSPlusParamLineDashTitle").'" required = "required" placeholder = "W,X,Y,Z" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	else {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_SIGN_LINE_DASH_0',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$inputSignLineDash	= 'size = "1" value = "" readonly';
	}
	$rowSpan5	+= !empty($conf->global->INVOICE_USE_SITUATION)	? 29	: 30;
	if (!empty($TVAforfaitaire)) {
		$rowSpan5	+= 1;
		$rowSpan5	+= !empty($conf->global->INFRASPLUS_PDF_USE_TVA_FORFAIT) ? 1 : 0;
	}
	$rowSpan5	+= !empty($conf->global->INFRASPLUS_PDF_USE_PAY_SPEC) && !empty($conf->global->INFRASPLUS_PDF_PAY_SPEC)	? 1	: 0;
	$rowSpan5	+= $conf->global->INFRASPLUS_PDF_GET_CUSTOMER_SIGNING													? 1	: 0;
	$rowSpan5	+= $conf->global->MAIN_MODULE_CUSTOMLINK																? 1	: 0;
	$rowSpan5	= $conf->global->INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE_EMET										? 1	: 0;
	// Pied de page -> Lignes d'informations supplémentaires, n° de page, LCR
	$typefoot	= isset($conf->global->INFRASPLUS_PDF_TYPE_FOOT) ? $conf->global->INFRASPLUS_PDF_TYPE_FOOT : '0000';
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_ADDRESS',		(substr($typefoot, 0, 1) == 1 || substr($typefoot, 0, 1) == 3) ? 1 : 0,	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_CONTACTS',	(substr($typefoot, 0, 1) == 2 || substr($typefoot, 0, 1) == 3) ? 1 : 0,	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_MANAGER',		substr($typefoot, 1, 1) == 1 ? 1 : 0,									'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_TYPESOC',		(substr($typefoot, 2, 1) == 1 || substr($typefoot, 2, 1) == 3) ? 1 : 0,	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_IDS',			(substr($typefoot, 2, 1) == 2 || substr($typefoot, 2, 1) == 3) ? 1 : 0,	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_ADDRESS2',	substr($typefoot, 3, 1) == 1 ? 1 : 0,									'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	// Conditions générales -> vente, interventions, achats
	$selectCG	= array('CGV' => $langs->trans('InfraSPlusParamTypeCGV'), 'CGI' => $langs->trans('InfraSPlusParamTypeCGI'), 'CGA' => $langs->trans('InfraSPlusParamTypeCGA'));
	$CGVs		= infraspackplus_get_CGfiles ('CGV', $conf->entity);
	$CGIs		= infraspackplus_get_CGfiles ('CGI', $conf->entity);
	$CGAs		= infraspackplus_get_CGfiles ('CGA', $conf->entity);
	if ($conf->global->MAIN_MULTILANGS && $conf->global->INFRASPLUS_PDF_CGV_FROM_LANG) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_CGV', infraspackplus_get_CGfiles_lang ($CGVs, $conf->global->MAIN_LANG_DEFAULT), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_CGI', infraspackplus_get_CGfiles_lang ($CGIs, $conf->global->MAIN_LANG_DEFAULT), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_CGA', infraspackplus_get_CGfiles_lang ($CGAs, $conf->global->MAIN_LANG_DEFAULT), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	$selected_cgv	= $conf->global->INFRASPLUS_PDF_CGV;
	$selected_cgi	= $conf->global->INFRASPLUS_PDF_CGI;
	$selected_cga	= $conf->global->INFRASPLUS_PDF_CGA;
	$rowSpan7		= $conf->global->INFRASPLUS_PDF_CGV_FROM_PRO	? 7	: 6;

	// View *****************************************
	$page_name					= $langs->trans('infrasplussetup').' - '.$langs->trans('InfraSPlusParamsPDF');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	if (!empty($user->admin))	$linkback	= '<a href = "'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption				= img_picto($langs->trans('Setup'), 'setup');

	// Configuration header *************************
	$head	= infraspackplus_admin_prepare_head();
	$picto	= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'infrasplussetup', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script src = "'.dol_buildpath('/infraspackplus/includes/js/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					jQuery(document).ready(function() {
						var tblPSexp = "";
						$.isSet = function(testVar){ return typeof(testVar) !== "undefined" && testVar !== null && testVar !== ""; };
						if ($.cookie && $.isSet($.cookie("tblPSexp"))) { tblPSexp = $.cookie("tblPSexp"); }
						$(".toggle_bloc").hide();
						if (tblPSexp != "") { $("[name=" + tblPSexp + "]").toggle(); }
					});
					$(function () {
						$(".foldable .toggle_bloc_title").click(function() {
							if ($(this).siblings().is(":visible")) { $(".toggle_bloc").hide(); }
							else {
								$(".toggle_bloc").hide();
								$(this).siblings().show();
							}
							$.cookie("tblPSexp", "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) { $.cookie("tblPSexp", $(this).attr("name"), { expires: 1, path: "/" }); }
							});
						});
					});
				</script>';
	}
	print '	<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Sauvegarde / Restauration
	if ($accessright == 2)	infraspackplus_print_backup_restore();
	// Comportement général -> génération automatique, 1 fichier par modèle
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamTitleComp').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblCG" class = "noborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '156px');
		infraspackplus_print_colgroup($metas);
		$metas	= array(array(1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status'));
		infraspackplus_print_liste_titre($metas);
		$num	= infraspackplus_print_input('MAIN_DISABLE_PDF_AUTOUPDATE', 'on_off', $langs->trans('InfraSPlusParamAutoUpdate1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamAutoUpdate2'), '', array(), 1, 1, '', 1);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_MULTI_FILES', 'on_off', $langs->trans('InfraSPlusParamMultiFiles'), '', array(), 1, 1, '', $num);
		if (!empty($conf->global->INFRASPLUS_PDF_MULTI_FILES)) {
			print '		<tr><td colspan = "2" align="center">'.$langs->trans('InfraSPlusParamMultiFilesText').'</td></tr>';
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROJECT_TIMESTAMP', 'on_off', $langs->trans('InfraSPlusParamProjectTimeStamp'), '', array(), 1, 1, '', $num);
		}
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FILES_FROM_PROJECT', 'on_off', $langs->trans('InfraSPlusParamFilesFromProject'), '', array(), 1, 1, '', $num);
		print '		</table>';
		print '	</div>';
	}
	// Apparence générale -> police, couleur de texte, style des en-têtes et des cadres, fond
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamTitleGen').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblAG" class = "noborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '90px', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infraspackplus_print_liste_titre($metas);
		$metas	= array('type' => 'file', 'class' => 'flat centpercent', 'accept' => '.ttf', 'style' => 'padding: 0px; font-size: inherit; cursor: pointer;');
		$end	= '<td align = "center"><button class = "button" style = "width: 110px;" type = "submit" value = "setfont" name = "action">'.$langs->trans('Add').'</button></td>';
		$num	= infraspackplus_print_input('fontfile', 'input', $langs->trans('InfraSPlusParamAddFont'), '', $metas, 1, 2, $end, $num);
		infraspackplus_print_btn_action('Gen', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), 7, 4);
		for ($i = 0; $i < count($listfontuse); $i++)
			$selectOptions[$listfontuse[$i]['fontname']]	= $listfontuse[$i]['name'];
		$metas	= '<button class = "button" style = "width: 44px; padding: 0px;" type = "submit" value = "testfont" name = "action">'.$langs->trans('InfraSPlusParamTestFont').'</button>
					'.$form->selectarray('defaultfont', $selectOptions, $selected_font, 0, 0, 0, 'style = "width: calc(95% - 48px); padding: 0px; font-size: inherit; cursor: pointer;"');
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFont'), '', $metas, 1, 2, '', $num);
		$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamBodyTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR), '', $metas, 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_REFDATE_MERGE', 'on_off', $langs->trans('InfraSPlusParamRefDateMerge'), '', array(), 2, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '5', 'step' => '0.001');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ROUNDED_REC', 'input', $langs->trans('InfraSPlusParamRoundedRec'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_PAID_WATERMARK', 'input', $langs->trans('InfraSPlusParamInvoicePaidMark'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_CUR_SYMB', 'on_off', $langs->trans('InfraSPlusParamCurSymb'), '', array(), 2, 1, '', $num);
		print '		</table>';
		print '	</div>';
	}
	// Haut de page -> cadres, contenu des en-têtes, adresses, note, pliage, filigrame, dommées additionnelles (douanes)
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamTitleHeader').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblHP" class = "noborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '90px', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infraspackplus_print_liste_titre($metas);
		infraspackplus_print_btn_action('Head', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), $rowSpan3, 4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FIRST_PAGE_EMPTY', 'on_off', $langs->trans('InfraSPlusParamFirstPageEmpty'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SMALL_HEAD_2', 'on_off', $langs->trans('InfraSPlusParamSmallHead2').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').' '.$langs->trans('InfraSPlusParamSmallHead2Forced').'</FONT>', '', array(), 2, 1, '', $num);
		$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_HEADER_TEXT_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEADER_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamHeaderTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_HEADER_TEXT_COLOR), '', $metas, 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEADER_AFTER_ADDR', 'on_off', $langs->trans('InfraSPlusParamHeaderAfterAddr'), '', array(), 2, 1, '', $num);
		if (empty($conf->global->INFRASPLUS_PDF_HEADER_AFTER_ADDR)) {
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '3', 'step' => '0.1');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TITLE_SIZE', 'input', $langs->trans('InfraSPlusParamTitleSize').$langs->trans('InfraSPlusParamFontSize'), '', $metas, 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEADER_ALIGN_LEFT', 'on_off', $langs->trans('InfraSPlusParamHeaderAlignLeft'), '', array(), 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CREATOR_IN_HEADER', 'on_off', $langs->trans('InfraSPlusParamCreatorHeader'), '', array(), 2, 1, '', $num);
			$num++;
		}
		else {
			$num	+= 3;
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '10');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SPACE_HEADERAFTER', 'input', $langs->trans('InfraSPlusParamSpaceBeforeHeaderAfter'), '', $metas, 2, 1, '&nbsp;mm', $num);
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DATES_BR', 'on_off', $langs->trans('InfraSPlusParamDatesBR'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DATES_BOLD', 'on_off', $langs->trans('InfraSPlusParamDatesBold'), '', array(), 2, 1, '', $num);
		if (!empty($conf->global->INFRASPLUS_PDF_DATES_BR)) {
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_FACT_DATEDUE_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACT_DATEDUE_COLOR', 'color', $langs->trans('InfraSPlusParamFactDateDueColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_FACT_DATEDUE_COLOR), '', $metas, 2, 1, '', $num);
		}
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_CF_SHOW_CREATION_DATE', 'on_off', $langs->trans('InfraSPlusParamCFshowCreationDate'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_REFD_FROM_CUSTOMER', 'on_off', $langs->trans('InfraSPlusParamRefDFromCustomer'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_NO_DATE_LINKED', 'on_off', $langs->trans('InfraSPlusParamNoDateLinked'), '', array(), 2, 1, '', $num);
		if (!empty($conf->propal->enabled))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_PROPAL', 'on_off', $langs->trans('InfraSPlusParamShowRefPropal'), '', array(), 2, 1, '', $num);
		else	$num++;
		if (!empty($conf->commande->enabled)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_ORDER', 'on_off', $langs->trans('InfraSPlusParamShowRefOrder'), '', array(), 2, 1, '', $num);
			if (!empty($conf->global->INFRASPLUS_PDF_SHOW_REF_ORDER))
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_CUST_ON_ORDER', 'on_off', $langs->trans('InfraSPlusParamShowRefCustOnOrder'), '', array(), 2, 1, '', $num);
			else	$num++;
		}
		else	$num	+= 2;
		if (!empty($conf->expedition->enabled))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_SHIPPING', 'on_off', $langs->trans('InfraSPlusParamShowRefShipping'), '', array(), 2, 1, '', $num);
		else	$num++;
		if (!empty($conf->contrat->enabled))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_CONTRACT', 'on_off', $langs->trans('InfraSPlusParamShowRefContract'), '', array(), 2, 1, '', $num);
		else	$num++;
		if (!empty($conf->ficheinter->enabled))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_FICHINTER', 'on_off', $langs->trans('InfraSPlusParamShowRefFichinter'), '', array(), 2, 1, '', $num);
		else	$num++;
		if (!empty($conf->projet->enabled)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_PROJECT', 'on_off', $langs->trans('InfraSPlusParamShowRefProject'), '', array(), 2, 1, '', $num);
			if (!empty($conf->global->INFRASPLUS_PDF_SHOW_REF_PROJECT))
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DESC_PROJECT', 'on_off', $langs->trans('InfraSPlusParamShowDescProject'), '', array(), 2, 1, '', $num);
			else	$num++;
		}
		else	$num	+= 2;
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_LABELS_FRAMES', 'on_off', $langs->trans('InfraSPlusParamHideLabelsFrames', $langs->transnoentities('BillFrom'), $langs->transnoentities('BillTo')), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_RECEP_FRAME', 'on_off', $langs->trans('InfraSPlusParamHideRecepFrame'), '', array(), 2, 1, '', $num);
		if (empty($conf->global->INFRASPLUS_PDF_HIDE_RECEP_FRAME)) {
			infraspackplus_print_hr(4);
			$metas		= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '5', 'step' => '0.1');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamFrmELineW').$langs->trans('InfraSPlusParamLineW'), '', $metas, 2, 1, '&nbsp;mm', $num);
			$metas		= array();
			$metas[0]	= array($langs->trans('InfraSPlusParamLineDash0'), $langs->trans('InfraSPlusParamLineDash1'), $langs->trans('InfraSPlusParamLineDash2'), $langs->trans('InfraSPlusParamLineDash4'));
			$metas[1]	= array('INFRASPLUS_PDF_FRM_E_LINE_DASH_0' => '&nbsp;&nbsp;'.img_picto('Ligne continue',		'Dash0.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
								'INFRASPLUS_PDF_FRM_E_LINE_DASH_1' => '&nbsp;&nbsp;'.img_picto('Pointillés égaux',		'Dash1.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
								'INFRASPLUS_PDF_FRM_E_LINE_DASH_2' => '&nbsp;&nbsp;'.img_picto('Pointillés inégaux',	'Dash2.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
								'INFRASPLUS_PDF_FRM_E_LINE_DASH_4' => '&nbsp;&nbsp;'.img_picto('Ligne discontinue',		'Dash4.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'));
			$end		= '<input type = "text" class = "flat quatrevingtpercent right" style = "padding: 0px; font-size: inherit;" id = "INFRASPLUS_PDF_FRM_E_LINE_DASH" name = "INFRASPLUS_PDF_FRM_E_LINE_DASH" '.$inputFrmLineDash.'>';
			$num	= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamFrmELineDash'), $metas, 2, 200, $end, $num);
			$metas		= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_FRM_E_LINE_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamFrmELineColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_FRM_E_LINE_COLOR), '', $metas, 2, 1, '', $num);
			$metas		= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_FRM_E_BG_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_BG_COLOR', 'color', $langs->trans('InfraSPlusParamFrmEBgColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_FRM_E_BG_COLOR), '', $metas, 2, 1, '', $num);
			$metas		= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_OPACITY', 'input', $langs->trans('InfraSPlusParamFrmEOpacity'), '', $metas, 2, 1, '&nbsp;%', $num);
			$metas		= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_FRM_E_TEXT_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamFrmETextColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_FRM_E_TEXT_COLOR), '', $metas, 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_STATUS_WITH_SENDER_NAME', 'on_off', $langs->trans('InfraSPlusParamshowStatusWithSenderName'), '', array(), 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_EMET_DETAILS', 'on_off', $langs->trans('InfraSPlusParamshowEmetFDetails'), '', array(), 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TVAINTRA_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowTvaIntraInSourceAddress'), '', array(), 2, 1, '', $num);
			if ($pid1)	$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID1_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid1, '', array(), 2, 1, '', $num);
			else		$num++;
			if ($pid2)	$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID2_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid2, '', array(), 2, 1, '', $num);
			else		$num++;
			if ($pid3)	$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID3_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid3, '', array(), 2, 1, '', $num);
			else		$num++;
			if ($pid4)	$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID4_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid4, '', array(), 2, 1, '', $num);
			else		$num++;
			if ($pid5)	$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID5_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid5, '', array(), 2, 1, '', $num);
			else		$num++;
		}
		else	$num	+= 14;
		infraspackplus_print_hr(4);
		$metas		= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '5', 'step' => '0.1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamFrmRLineW').$langs->trans('InfraSPlusParamLineW'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas		= array();
		$metas[0]	= array($langs->trans('InfraSPlusParamLineDash0'), $langs->trans('InfraSPlusParamLineDash1'), $langs->trans('InfraSPlusParamLineDash2'), $langs->trans('InfraSPlusParamLineDash4'));
		$metas[1]	= array('INFRASPLUS_PDF_FRM_R_LINE_DASH_0' => '&nbsp;&nbsp;'.img_picto('Ligne continue',		'Dash0.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_FRM_R_LINE_DASH_1' => '&nbsp;&nbsp;'.img_picto('Pointillés égaux',		'Dash1.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_FRM_R_LINE_DASH_2' => '&nbsp;&nbsp;'.img_picto('Pointillés inégaux',	'Dash2.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_FRM_R_LINE_DASH_4' => '&nbsp;&nbsp;'.img_picto('Ligne discontinue',		'Dash4.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'));
		$end		= '<input type = "text" class = "flat quatrevingtpercent right" style = "padding: 0px; font-size: inherit;" id = "INFRASPLUS_PDF_FRM_R_LINE_DASH" name = "INFRASPLUS_PDF_FRM_R_LINE_DASH" '.$inputFrmRLineDash.'>';
		$num	= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamFrmRLineDash'), $metas, 2, 200, $end, $num);
		$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_FRM_R_LINE_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamFrmRLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_FRM_R_LINE_COLOR), '', $metas, 2, 1, '', $num);
		$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_FRM_R_BG_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_BG_COLOR', 'color', $langs->trans('InfraSPlusParamFrmRBgColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_FRM_R_BG_COLOR), '', $metas, 2, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_OPACITY', 'input', $langs->trans('InfraSPlusParamFrmROpacity'), '', $metas, 2, 1, '&nbsp;%', $num);
		$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_FRM_R_TEXT_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamFrmRTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_FRM_R_TEXT_COLOR), '', $metas, 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_STATUS_WITH_CLIENT_NAME', 'on_off', $langs->trans('InfraSPlusParamshowStatusWithClientName'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_RECEP_DETAILS', 'on_off', $langs->trans('InfraSPlusParamshowRecepFDetails'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		if ($conf->global->MAIN_MODULE_PRODUCT)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_ADR_PROD', 'on_off', $langs->trans('InfraSPlusParamshowAdrProd'), '', array(), 2, 1, '', $num);
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_NUM_CLI', 'on_off', $langs->trans('InfraSPlusParamshowNumCli'), '', array(), 2, 1, '', $num);
		if ($conf->global->INFRASPLUS_PDF_SHOW_NUM_CLI && empty($conf->global->INFRASPLUS_PDF_HEADER_AFTER_ADDR))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_NUM_CLI_FRM', 'on_off', $langs->trans('InfraSPlusParamNumCliFrm'), '', array(), 2, 1, '', $num);
		else	$num++;
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT', 'on_off', $langs->trans('InfraSPlusParamshowCodeCliComp'), '', array(), 2, 1, '', $num);
		if ($conf->global->INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CODE_CLI_COMPT_FRM', 'on_off', $langs->trans('InfraSPlusParamCodeCliCompFrm'), '', array(), 2, 1, '', $num);
		else	$num++;
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PRJ_DATEO_IN_NOTE', 'on_off', $langs->trans('InfraSPlusParamPrjDateoNote'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FIRST_SALES_REP_IN_NOTE', 'on_off', $langs->trans('InfraSPlusParam1SalesRepNote'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_LAST_NOTE_AS_TABLE', 'on_off', $langs->trans('InfraSPlusParam1LastNoteAsTable'), '', array(), 2, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '10');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FOLD_MARK', 'input', $langs->trans('InfraSPlusParamFoldMark'), '', $metas, 2, 1, '&nbsp;mm', $num);
		print '		</table>';
		print '	</div>';
	}
	// Contenu, colonnage -> Colonnes additionnelles et masquées (référence, tva, remises), taille et position
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamTitleCorps').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblCC" class = "noborder toggle_bloc" width = "100%">';
		$metas		= array('30px', '*', '90px', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		$metas		= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infraspackplus_print_liste_titre($metas);
		infraspackplus_print_btn_action('Body', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), $rowSpan4, 4);
		$metas		= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_BACKGROUND_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BACKGROUND_COLOR', 'color', $langs->trans('InfraSPlusParamBackgroundColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_BACKGROUND_COLOR), '', $metas, 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TITLE_BG', 'on_off', $langs->trans('InfraSPlusParamtTitleBackground'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_COLOR_AUTO', 'on_off', $langs->trans('InfraSPlusParamTextColorAuto'), '', array(), 2, 1, '', $num);
		$metas		= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_TEXT_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_TEXT_COLOR), '', $metas, 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$metas		= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '4', 'max' => '20', 'step' => '0.1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEIGHT_TOP_TABLE', 'input', $langs->trans('InfraSPlusParamHeightTopTable1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamHeightTopTable2'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_TOP_TABLE', 'on_off', $langs->trans('InfraSPlusParamhidetoptable'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$metas		= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '5', 'step' => '0.1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TBL_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamTblLineW').$langs->trans('InfraSPlusParamLineW'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas		= array();
		$metas[0]	= array($langs->trans('InfraSPlusParamLineDash0'), $langs->trans('InfraSPlusParamLineDash1'), $langs->trans('InfraSPlusParamLineDash2'), $langs->trans('InfraSPlusParamLineDash4'));
		$metas[1]	= array('INFRASPLUS_PDF_TBL_LINE_DASH_0' => '&nbsp;&nbsp;'.img_picto('Ligne continue',		'Dash0.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_TBL_LINE_DASH_1' => '&nbsp;&nbsp;'.img_picto('Pointillés égaux',	'Dash1.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_TBL_LINE_DASH_2' => '&nbsp;&nbsp;'.img_picto('Pointillés inégaux',	'Dash2.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_TBL_LINE_DASH_4' => '&nbsp;&nbsp;'.img_picto('Ligne discontinue',	'Dash4.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'));
		$end		= '<input type = "text" class = "flat quatrevingtpercent right" style = "padding: 0px; font-size: inherit;" id = "INFRASPLUS_PDF_TBL_LINE_DASH" name = "INFRASPLUS_PDF_TBL_LINE_DASH" '.$inputTblLineDash.'>';
		$num	= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamTblLineDash'), $metas, 2, 200, $end, $num);
		$metas		= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_TBL_LINE_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TBL_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamTblLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_TBL_LINE_COLOR), '', $metas, 2, 1, '', $num);
		$metas		= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_VER_LINE_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_VER_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamVerLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_VER_LINE_COLOR), '', $metas, 2, 1, '', $num);
		$metas		= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_HOR_LINE_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HOR_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamHorLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_HOR_LINE_COLOR), '', $metas, 2, 1, '', $num);
		if (! $conf->global->MAIN_PDF_DASH_BETWEEN_LINES) {
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '10');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LINESEP_HIGHT', 'input', $langs->trans('InfraSPlusParamLineSepHight'), '', $metas, 2, 1, '&nbsp;mm', $num);
		}
		else	$num++;
		if ($conf->global->MAIN_MODULE_SUBTOTAL) {
			infraspackplus_print_hr(4);
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_BODY_SUBTI_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_SUBTI_COLOR', 'color', $langs->trans('InfraSPlusParamBodySubTiColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_BODY_SUBTI_COLOR), '', $metas, 2, 1, '&nbsp;mm', $num);
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_TEXT_SUBTI_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', 'color', $langs->trans('InfraSPlusParamTextSubTiColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_TEXT_SUBTI_COLOR), '', $metas, 2, 1, '&nbsp;mm', $num);
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_TEXT_SUBTO_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', 'color', $langs->trans('InfraSPlusParamTextSubToColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_TEXT_SUBTO_COLOR), '', $metas, 2, 1, '&nbsp;mm', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_BODY_SUBTO', 'on_off', $langs->trans('InfraSPlusParamHideBodySubTo'), '', array(), 2, 1, '', $num);
			if (empty($conf->global->INFRASPLUS_PDF_HIDE_BODY_SUBTO))
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', 'on_off', $langs->trans('InfraSPlusParamBodySubToColorSubTi'), '', array(), 2, 1, '', $num);
			else	$num++;
		}
		else	$num	+= 5;
		if ($conf->global->MAIN_MODULE_MILESTONE) {
			infraspackplus_print_hr(4);
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_TEXT_SUBTI_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', 'color', $langs->trans('InfraSPlusParamTextJalonColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_TEXT_SUBTI_COLOR), '', $metas, 2, 1, '', $num);
		}
		else	$num++;
		if ($conf->global->MAIN_MODULE_OUVRAGE) {
			infraspackplus_print_hr(4);
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_BODY_OUV_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_OUV_COLOR', 'color', $langs->trans('InfraSPlusParamBodyOuvColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_BODY_OUV_COLOR), '', $metas, 2, 1, '', $num);
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_TEXT_OUV_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_OUV_COLOR', 'color', $langs->trans('InfraSPlusParamTextOuvColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_TEXT_OUV_COLOR), '', $metas, 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_OUV_STYLE', 'input', $langs->trans('InfraSPlusParamTextOuvStyle'), '', array(), 2, 1, '', $num);
		}
		else	$num	+= 3;
		infraspackplus_print_hr(4);
		if ($conf->global->MAIN_MODULE_BARCODE) {
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '5', 'max' => '20');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HT_BC', 'input', $langs->trans('InfraSPlusParamHtBC'), '', $metas, 2, 1, '&nbsp;mm', $num);
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '25', 'max' => '45', 'step' => '10');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LARG_BC', 'input', $langs->trans('InfraSPlusParamLargBC'), '', $metas, 2, 1, '&nbsp;mm', $num);
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '15', 'max' => '40');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DIM_C2D', 'input', $langs->trans('InfraSPlusParamDimC2D'), '', $metas, 2, 1, '&nbsp;mm', $num);
			infraspackplus_print_hr(4);
		}
		else	$num	+= 3;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_NUM_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowNumCol'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_REF_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowRefCol'), '', array(), 2, 1, '', $num);
		if (!empty($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN) && !empty($conf->global->MAIN_MODULE_BARCODE))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_REF_WITH_GENCODE', 'on_off', $langs->trans('InfraSPlusParamRefColumnWithGencode'), '', array(), 2, 1, '', $num);
		else	$num++;
		if (!empty($conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN) || !empty($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN)) {
			$metas	= $form->selectarray('INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF', array('L' => 'Left', 'C' => 'Center', 'R' => 'Right'), $alignLeftRef, 0, 0, 0, '', 1, 0, 0, '', 'quatrevingtpercent');
			$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamForceAlignLeftRefColumn'), '', $metas, 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BOLD_REF', 'on_off', $langs->trans('InfraSPlusParamBoldRefColumn'), '', array(), 2, 1, '', $num);
		}
		else	$num	+= 2;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowSupRefCol'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_FULL_LINE', 'on_off', $langs->trans('InfraSPlusParamDescriptionFullLine'), '', array(), 2, 1, '', $num);
		if (!empty($conf->global->INFRASPLUS_PDF_DESC_FULL_LINE)) {
			$metas = array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100', 'step' => '1');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamDescFullLineWidth'), '', $metas, 2, 1, '&nbsp;&percnt;', $num);
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_DESC_FULL_LINE_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_FULL_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamDescFullLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_DESC_FULL_LINE_COLOR), '', $metas, 2, 1, '', $num);
			infraspackplus_print_hr(4);
		}
		else	$num	+= 2;
		$metas = array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '20', 'step' => '1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE','input', $langs->trans('InfraSPlusParamDescPeriodFontSize'),'', $metas, 2, 1, '&nbsp;pt', $num);
		$metas = colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_DESC_PERIOD_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_PERIOD_COLOR','color',$langs->trans('InfraSPlusParamDescPeriodColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_DESC_PERIOD_COLOR),'', $metas, 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_CLEAN_FONT', 'on_off', $langs->trans('InfraSPlusParamDescriptionCleanFont'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_LABEL', 'on_off', $langs->trans('InfraSPlusParamHideLabel'), '', array(), 2, 1, '', $num);
		if (empty($conf->global->INFRASPLUS_PDF_HIDE_LABEL)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DESC_DEV', 'on_off', $langs->trans('InfraSPlusParamShowDescDev'), '', array(), 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_ONLY_ONE_DESC', 'on_off', $langs->trans('InfraSPlusParamOnlyOneDesc1').' <FONT color = "red">'.$langs->trans('InfraSPlusParamOnlyOneDesc2').'</FONT> '.$langs->trans('InfraSPlusParamOnlyOneDesc3'), '', array(), 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LABEL_BOLD', 'on_off', $langs->trans('InfraSPlusParamLabelBold1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamLabelBold2'), '', array(), 2, 1, '', $num);
		}
		else	$num	+= 3;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXTRADET_SECOND', 'on_off', $langs->trans('InfraSPlusParamExtraDetSecond'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_SERVICE_DATES', 'on_off', $langs->trans('InfraSPlusParamServiceDates'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_TIME_SPENT_FI', 'on_off', $langs->trans('InfraSPlusParamTimeSpentFI'), '', array(), 2, 1, '', $num);
		if ($conf->global->MAIN_MODULE_MANAGEMENT)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DATES_HOURS_FI', 'on_off', $langs->trans('InfraSPlusParamDatesHoursFI'), '', array(), 2, 1, '', $num);
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_QTY', 'on_off', $langs->trans('InfraSPlusParamHideQty'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_UP', 'on_off', $langs->trans('InfraSPlusParamHideUP').$infoDiscountAuto, '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_DISCOUNT', 'on_off', $langs->trans('InfraSPlusParamHideDiscount').' '.$langs->trans('GenModif').$infoDiscountAuto, '', array(), 2, 1, '', $num);
		if (empty($conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DISCOUNT_OPT', 'on_off', $langs->trans('InfraSPlusParamShowDiscountOpt'), '', array(), 2, 1, '', $num);
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_UP_DISCOUNTED', 'on_off', $langs->trans('InfraSPlusParamShowUPDiscounted').$infoDiscountAuto, '', array(), 2, 1, '', $num);
		if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DISCOUNT_AUTO', 'on_off', $langs->trans('InfraSPlusParamDiscountAuto'), '', array(), 2, 1, '', $num);
		else	$num++;
		if (!empty($conf->global->INVOICE_USE_SITUATION))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SITFAC_TOTLINE_AVT', 'on_off', $langs->trans('InfraSPlusParamSitFacTotLineAvt'), '', array(), 2, 1, '', $num);
		else	$num++;
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_TTC_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowTTCColumn'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', 'on_off', $langs->trans('InfraSPlusParamHideVATColumn1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamHideVATColumn2'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TTC_WITH_VAT_TOT', 'on_off', $langs->trans('InfraSPlusParamTTCWithVATTotal1').' <FONT color = "red">'.$langs->trans('InfraSPlusParamTTCWithVATTotal2').'</FONT> '.$langs->trans('InfraSPlusParamTTCWithVATTotal3'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ONLY_TTC', 'on_off', $langs->trans('InfraSPlusParamHideAnyVATInformation'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ONLY_HT', 'on_off', $langs->trans('InfraSPlusParamShowOlnyHT'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_COLS', 'on_off', $langs->trans('InfraSPlusParamHideCols'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_TOT_COL_DEVST', 'on_off', $langs->trans('InfraSPlusParamHideTotColDevSt'), '', array(), 2, 1, '', $num);
		if ($wvccopt > 0) {
			infraspackplus_print_hr(4);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_WVCC', 'on_off', $langs->trans('InfraSPlusParamShowWVCC'), '', array(), 2, 1, '', $num);
			if ($conf->global->INFRASPLUS_PDF_SHOW_WVCC) {
				$metas		= array();
				$metas[0]	= array('MAIN_MODULE_PROPALE'		=> $langs->trans('Proposals'),
									'MAIN_MODULE_COMMANDE'		=> $langs->trans('Orders'),
									'MAIN_MODULE_EXPEDITION'	=> $langs->trans('SendingCard'),
									'MAIN_MODULE_FACTURE'		=> $langs->trans('Invoices'));
				$metas[1]	= array('INFRASPLUS_PDF_WVCC_BY_DEF_FOR_PROPOSALS'	=> 'MAIN_MODULE_PROPALE',
									'INFRASPLUS_PDF_WVCC_BY_DEF_FOR_ORDERS'		=> 'MAIN_MODULE_COMMANDE',
									'INFRASPLUS_PDF_WVCC_BY_DEF_FOR_EXPEDITION'	=> 'MAIN_MODULE_EXPEDITION',
									'INFRASPLUS_PDF_WVCC_BY_DEF_FOR_INVOICES'	=> 'MAIN_MODULE_FACTURE');
				$num	= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamTypeDoc').'&nbsp;'.$langs->trans('InfraSPlusParamShowWVCCbyDef'), $metas, 3, 120, '', $num);
			}
			else	$num++;
		}
		else	$num	+= 2;
		infraspackplus_print_hr(4);
		print '			<tr class = "oddeven">
							<td class = "center" style = "color: #382453; font-weight: bold;">'.$num.'</td>
							<td colspan = "3">
								<table width = "100%" class = "noborderbottom">
									<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColNum').'
										</td>';
		foreach ($listselect as $selectvalues) {
			$numcol	= num_col($selectvalues, $listselect);
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">
											<select name = "'.$selectvalues['select'].'" class = "flat" style = "padding: 0px; font-size: inherit; border: none; cursor: pointer;';
			if ($numcol['err'] > 0)	print ' background-color: red;">';
			else					print '">';
			print								$numcol['options'].'
											</select>
										</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColName').'
										</td>';
		foreach ($listcol as $col)
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">'.$col.'</td>';
		print '						</tr>';
		print '						<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColLarg').'
										</td>';
		foreach ($listlarg as $largs) {
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">';
			if ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_PROGRESS')	print '(';
			if ($largs['key'] == 'DESC')	print $largs['value'];
			else {
				print '						<input type = "number" size = "2" style = "text-align: center; margin: 0; padding: 0;" dir = "rtl" id = "'.$largs['key'].'" name = "'.$largs['key'].'"';
				if ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_REF' && empty($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN) && empty($conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN) && empty($conf->global->INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_UNIT' && empty($conf->global->PRODUCT_USE_UNITS))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_QTY' && !empty($conf->global->INFRASPLUS_PDF_HIDE_QTY))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_UP' && !empty($conf->global->INFRASPLUS_PDF_HIDE_UP))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_TVA' && (!empty($conf->global->INFRASPLUS_PDF_WITHOUT_VAT_COLUMN) || !empty($conf->global->INFRASPLUS_PDF_TTC_WITH_VAT_TOT) || !empty($conf->global->INFRASPLUS_PDF_ONLY_TTC) || !empty($conf->global->INFRASPLUS_PDF_ONLY_HT)))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_DISC' && !empty($conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_UPD' && (empty($conf->global->INFRASPLUS_PDF_SHOW_UP_DISCOUNTED) || !empty($conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT)))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_TOTAL_TTC' && empty($conf->global->INFRASPLUS_PDF_WITH_TTC_COLUMN))
					print '					 min = "0" max = "0" value = "0" readonly>';
				else
					print '					 min = "10" max = "40" value = "'.($largs['value'] > 0 ? $largs['value'] : 10).'">';
			}
			if ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_PROGRESS')	print ')*';
			print '						</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td colspan = "12" align = "center">'.$langs->trans('InfraSPlusParamColProgress').'</td>
									</tr>';
		print '					</table>
							</td>
						</tr>';
		$num++;
		infraspackplus_print_hr(4);
		print '			<tr>
							<td colspan = "3" align = "center"><FONT color = "#382453" size = "2">'.$langs->trans('InfraSPlusParamShipping').'</FONT></td>
						</tr>';
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BL_WITH_BC_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBLBCCol'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BL_WITH_POS_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBLposCol'), '', array(), 2, 1, '', $num);
		if (!empty($conf->global->INFRASPLUS_PDF_BL_WITH_POS_COLUMN)) {
			$metas = array('size' => '6');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_PROD_POS', 'input', $langs->trans('InfraSPlusParamEXFprodPos'),'', $metas, 2, 1, '&nbsp;pt', $num);
		}
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_ORDERED', 'on_off', $langs->trans('InfraSPlusParamHideOrdered'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BL_WITH_REL_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBLrelCol'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		print '			<tr class = "oddeven">
							<td class = "center" style = "color: #382453; font-weight: bold;">'.$num.'</td>
							<td colspan = "3">
								<table width = "100%" class = "noborderbottom">';
		print '						<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColNum').'
										</td>';
		foreach ($listselectBL as $selectvaluesBL) {
			$numcol	= num_col($selectvaluesBL, $listselectBL);
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">
											<select name = "'.$selectvaluesBL['select'].'" class = "flat" style = "padding: 0px; font-size: inherit; border: none; cursor: pointer;';
			if ($numcol['err'] > 0)	print ' background-color: red;">';
			else					print '">';
			print								$numcol['options'].'
											</select>
										</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColName').'
										</td>';
		foreach ($listcolBL as $colBL)
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">'.$colBL.'</td>';
		print '						</tr>';
		print '						<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColLarg').'
										</td>';
		foreach ($listlargBL as $largsBL) {
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">';
			if ($largsBL['key'] == 'DESC')	print $largsBL['value'];
			else {
				print '						<input type = "number" size = "2" style = "text-align: center; margin: 0; padding: 0;" dir = "rtl" id = "'.$largsBL['key'].'" name = "'.$largsBL['key'].'"';
				if ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_REF' && empty($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN) && empty($conf->global->INFRASPLUS_PDF_BL_WITH_BC_COLUMN))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_EFL' && empty($conf->global->INFRASPLUS_PDF_BL_WITH_POS_COLUMN))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_WV' && !empty($conf->global->SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_UNIT' && empty($conf->global->PRODUCT_USE_UNITS))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_ORDERED' && (!empty($conf->global->INFRASPLUS_PDF_HIDE_ORDERED)))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_REL' && empty($conf->global->INFRASPLUS_PDF_BL_WITH_REL_COLUMN))
					print '					 min = "0" max = "0" value = "0" readonly>';
				else
					print '					 min = "10" max = "40" value = "'.($largsBL['value'] > 0 ? $largsBL['value'] : 10).'">';
			}
			print '						</td>';
		}
		print '						</tr>';
		print '					</table>
							</td>
						</tr>';
		$num++;
		infraspackplus_print_hr(4);
		print '			<tr>
							<td colspan = "3" align = "center"><FONT color = "#382453" size = "2">'.$langs->trans('InfraSPlusParamReceipt').'</FONT></td>
						</tr>';
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BR_WITH_BC_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBRBCCol'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BR_WITH_COMM_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBRcommCol'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_ORDERED', 'on_off', $langs->trans('InfraSPlusParamHideOrdered'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BR_WITH_REL_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBRrelCol'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		print '			<tr class = "oddeven">
							<td class = "center" style = "color: #382453; font-weight: bold;">'.$num.'</td>
							<td colspan = "3">
								<table width = "100%" class = "noborderbottom">';
		print '						<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColNum').'
										</td>';
		foreach ($listselectBR as $selectvaluesBR) {
			$numcol	= num_col($selectvaluesBR, $listselectBR);
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">
											<select name = "'.$selectvaluesBR['select'].'" class = "flat" style = "padding: 0px; font-size: inherit; border: none; cursor: pointer;';
			if ($numcol['err'] > 0)	print ' background-color: red;">';
			else					print '">';
			print								$numcol['options'].'
											</select>
										</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColName').'
										</td>';
		foreach ($listcolBR as $colBR)
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">'.$colBR.'</td>';
		print '						</tr>';
		print '						<tr>
										<td style = "margin: 0; padding: 0; border: none;">
											'.$langs->trans('InfraSPlusParamColLarg').'
										</td>';
		foreach ($listlargBR as $largsBR) {
			print '						<td style = "text-align: center; margin: 0; padding: 0; border: none;">';
			if ($largsBR['key'] == 'DESC')	print $largsBR['value'];
			else {
				print '						<input type = "number" size = "2" style = "text-align: center; margin: 0; padding: 0;" dir = "rtl" id = "'.$largsBR['key'].'" name = "'.$largsBR['key'].'"';
				if ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_REF' && empty($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN) && empty($conf->global->INFRASPLUS_PDF_BR_WITH_BC_COLUMN))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_COMM' && empty($conf->global->INFRASPLUS_PDF_BR_WITH_COMM_COLUMN))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_UNIT' && empty($conf->global->PRODUCT_USE_UNITS))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_ORDERED' && (!empty($conf->global->INFRASPLUS_PDF_HIDE_ORDERED)))
					print '					 min = "0" max = "0" value = "0" readonly>';
				elseif ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_REL' && empty($conf->global->INFRASPLUS_PDF_BR_WITH_REL_COLUMN))
					print '					 min = "0" max = "0" value = "0" readonly>';
				else
					print '					 min = "10" max = "100" value = "'.($largsBR['value'] > 0 ? $largsBR['value'] : 10).'">';
			}
			print '						</td>';
		}
		print '						</tr>';
		print '					</table>
							</td>
						</tr>';
		$num++;
		print '		</table>';
		print '	</div>';
	}
	// Pied de document -> encours, total des remises, multi-devises, number-words, zones de signature, mentions complémentaires
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamTitleFooter').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblPD" class = "noborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '90px', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infraspackplus_print_liste_titre($metas);
		infraspackplus_print_btn_action('Foot', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), $rowSpan5, 4);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '10');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SPACE_INFO', 'input', $langs->trans('InfraSPlusParamSpaceBeforeInfo'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SPACE_TOT', 'input', $langs->trans('InfraSPlusParamSpaceBeforeTot'), '', $metas, 2, 1, '&nbsp;mm', $num);
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_QTY_PROD_TOT', 'on_off', $langs->trans('InfraSPlusParamShowQtyProdTot'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_TOT_DISCOUNT', 'on_off', $langs->trans('InfraSPlusParamShowTotDisc').' '.$langs->trans('GenModif'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_OUTSTDBILL', 'on_off', $langs->trans('InfraSPlusParamShowOutStdBill'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HT_BY_VAT_P_OR_S', 'on_off', $langs->trans('InfraSPlusParamHTbyTvaPorS'), '', array(), 2, 1, '', $num);
		if (!empty($conf->global->INVOICE_USE_SITUATION))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_SITU_TOTAL_2', 'on_off', $langs->trans('InfraSPlusParamUseSituTotal2'), '', array(), 2, 1, '', $num);
		else	$num++;
		if (!empty($TVAforfaitaire)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_TVA_FORFAIT', 'on_off', $langs->trans('InfraSPlusParamUseTVAforfaitaire'), '', array(), 2, 1, '', $num);
			if (!empty($conf->global->INFRASPLUS_PDF_USE_TVA_FORFAIT)) {
				$metas = array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100', 'step' => '0.1');
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_TVA_FORFAIT', 'input', $langs->trans('InfraSPlusParamTVAforfaitaire'), '', $metas, 2, 1, '&nbsp;&percnt;', $num);
			}
			else	$num++;
		}
		else	$num	+= 2;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_TOTAL_LOCAL_CUR', 'on_off', $langs->trans('InfraSPlusParamShowTotLocCur'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_NUMBER_WORDS', 'on_off', $langs->trans('InfraSPlusParamNumWords1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamNumWords2').' <a href="'.$langs->trans('InfraSPlusParamNumWordsLink').'" target="_blank">'.$langs->trans('InfraSPlusParamNumWordsLinkText').'</a>', '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PAY_INLINE', 'select_types_paiements', $langs->trans('InfraSPlusParamPayInLine'), '', array('CRDT', 2, 1, 1, 20), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_PAY_SPEC', 'on_off', $langs->trans('InfraSPlusParamUsePaySpec'), '', array(), 2, 1, '', $num);
		if (!empty($conf->global->INFRASPLUS_PDF_USE_PAY_SPEC) && !empty($conf->global->INFRASPLUS_PDF_PAY_SPEC)) {
			print '		<tr class = "oddeven">
							<td class = "center" style = "color: #382453; font-weight: bold;">'.$num.'</td>
							<td colspan = "2">'.$langs->trans('InfraSPlusParamPaySpec').'</td>
							<td align="center">
								<button class = "button" style = "width: 110px; padding: 3px 0px;" type = "submit" value = "modifyPaySpec" name = "action">'.$langs->trans('Modify').'</button>
							</td>
						</tr>';
		}
		$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BANK_ONLY_NUMBER', 'on_off', $langs->trans('InfraSPlusParamBankOnlyNumber'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_IBAN_WITH_CB', 'on_off', $langs->trans('InfraSPlusParamIBANwithCB'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_IBAN_ALL', 'on_off', $langs->trans('InfraSPlusParamIBANAll'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$metas		= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '8', 'max' => '48');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HT_SIGN_AREA', 'input', $langs->trans('InfraSPlusParamHtSignArea'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas		= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '5', 'step' => '0.1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SIGN_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamSignLineW').$langs->trans('InfraSPlusParamLineW'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas		= array();
		$metas[0]	= array($langs->trans('InfraSPlusParamLineDash0'), $langs->trans('InfraSPlusParamLineDash1'), $langs->trans('InfraSPlusParamLineDash2'), $langs->trans('InfraSPlusParamLineDash4'));
		$metas[1]	= array('INFRASPLUS_PDF_SIGN_LINE_DASH_0' => '&nbsp;&nbsp;'.img_picto('Ligne continue',		'Dash0.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_SIGN_LINE_DASH_1' => '&nbsp;&nbsp;'.img_picto('Pointillés égaux',	'Dash1.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_SIGN_LINE_DASH_2' => '&nbsp;&nbsp;'.img_picto('Pointillés inégaux',	'Dash2.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'),
							'INFRASPLUS_PDF_SIGN_LINE_DASH_4' => '&nbsp;&nbsp;'.img_picto('Ligne discontinue',	'Dash4.png@infraspackplus', 'style = "vertical-align: bottom; height: 20pt;"'));
		$end	= '<input type = "text" class = "flat quatrevingtpercent right" style = "padding: 0px; font-size: inherit;" id = "INFRASPLUS_PDF_SIGN_LINE_DASH" name = "INFRASPLUS_PDF_SIGN_LINE_DASH" '.$inputSignLineDash.'>';
		$num	= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamSignLineDash'), $metas, 2, 200, $end, $num);
		$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_SIGN_LINE_COLOR));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SIGN_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamSignLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_SIGN_LINE_COLOR), '', $metas, 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_E_SIGNING', 'on_off', $langs->trans('InfraSPlusParamShowESigning'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_GET_CUSTOMER_SIGNING', 'on_off', $langs->trans('InfraSPlusParamGetCustomerSign'), '', array(), 2, 1, '', $num);
		if ($conf->global->INFRASPLUS_PDF_GET_CUSTOMER_SIGNING) {
			$metas	= colorArrayToHex(explode(',', $conf->global->INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR', 'color', $langs->trans('InfraSPlusParamCustomerSignColor').' '.$langs->trans('InfraSPlusParamActualRVB', $conf->global->INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR), '', $metas, 2, 1, '', $num);
		}
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignature'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROPAL_ST_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureSt'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_COMMANDE_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureCom'), '', array(), 2, 1, '', $num);
		if ($conf->global->MAIN_MODULE_CUSTOMLINK)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_COMMANDE_OF_SHOW_2_SIGNATURES', 'on_off', $langs->trans('InfraSPlusParamShow2SignaturesCom'), '', array(), 2, 1, '', $num);
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_CONTRACT_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureCtr'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXPEDITION_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureExp'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureFi'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE_EMET', 'on_off', $langs->trans('InfraSPlusParamShowSignatureFiEmet'), '', array(), 2, 1, '', $num);
		if (!empty($conf->global->INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE_EMET))
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_INTERVENTION_SIGNATURE_FULL', 'on_off', $langs->trans('InfraSPlusParamSignatureFiFull'), '', array(), 2, 1, '', $num);
		else	$num++;
		print '		</table>';
		print '	</div>';
	}
	// Pied de page -> Lignes d'informations supplémentaires, n° de page, LCR
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamTitleFooterPage').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblPP" class = "noborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '90px', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infraspackplus_print_liste_titre($metas);
		infraspackplus_print_btn_action('FootP', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), 16, 4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_ADDRESS', 'on_off', $langs->trans('InfraSPlusParamFooterAdress').(empty($conf->global->INFRASPLUS_PDF_HIDE_RECEP_FRAME) ? '' : ' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').' '.$langs->trans('InfraSPlusParamFooterAdressForced').'</FONT>'), '', array(), 2, 1, '', $num);
		if ($conf->global->INFRASPLUS_PDF_TYPE_FOOT_ADDRESS)
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_ADDRESS2', 'on_off', $langs->trans('InfraSPlusParamFooterAdress2'), '', array(), 2, 1, '', $num);
		else	$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_CONTACTS', 'on_off', $langs->trans('InfraSPlusParamFooterContacts'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_MANAGER', 'on_off', $langs->trans('InfraSPlusParamFooterManager'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_TYPESOC', 'on_off', $langs->trans('InfraSPlusParamFooterTypeSoc'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_IDS', 'on_off', $langs->trans('InfraSPlusParamFooterIds'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FOOTER_BOLD', 'on_off', $langs->trans('InfraSPlusParamFooterBold'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FOOTER_FREETEXT', 'textarea', $langs->trans('InfraSPlusParamFooterFreeText'), '', array(), 2, 1, '', $num);
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_PAGE_NUM', 'on_off', $langs->trans('InfraSPlusParamHidePageNum'), '', array(), 2, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '10', 'max' => '267', 'step' => '0.1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_X_PAGE_NUM', 'input', $langs->trans('InfraSPlusParamPosXPageNum'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '10', 'max' => '285', 'step' => '0.1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_Y_PAGE_NUM', 'input', $langs->trans('InfraSPlusParamPosYPageNum'), '', $metas, 2, 1, '&nbsp;mm', $num);
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_LCR', 'on_off', $langs->trans('InfraSPlusParamShowLCR'), '', array(), 2, 1, '', $num);
		print '		</table>';
		print '	</div>';
	}
	// Conditions générales -> vente, interventions, achats
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans("InfraSPlusParamCGVs").'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/Tools.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblCGx" class = "noborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '90px', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		print '			<tr class = "oddeven">
							<td colspan = "4">
								<table width = "100%">
								<tr>
										<td style = "border: none;">
											'.fieldLabel('InfraSPlusParamCGVFile', 'CGVFile').'
											<input type = "file" class = "flat" style = "padding: 0px; font-size: inherit; cursor: pointer;" id = "CGVFile" name = "CGVFile" accept=".pdf">
										</td>
										<td style = "border: none;">
											'.fieldLabel($langs->trans("InfraSPlusParamTypeCG"), 'typeCG').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
											'.$form->selectarray('typeCG', $selectCG, '', 0, 0, 0, 'style = "padding: 0px; font-size: inherit; cursor: pointer;"').'
										</td>
										<td class = "right" style = "border: none;">
											'.fieldLabel('InfraSPlusParamCGVName', 'CGVName').'
											<input type = "text" class = "flat" size = "30" style = "padding: 0px; font-size: inherit;" id = "CGVName" name = "CGVName">
										</td>
									</tr>
								</table>
							</td>
							<td align = "center"><button class = "button" style = "width: 110px;" type = "submit" value = "addcgv" name = "action">'.$langs->trans("Add").'</button></td>
						</tr>';
		print '			<tr>
							<td colspan = "5">';
		print load_fiche_titre('<FONT color = "#382453" size = "3">'.$langs->trans("InfraSPlusParamListCGV").'</FONT>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
		$CGV_files	= dol_dir_list($cgxdir, 'files', 0, '',  '', null, null, 1);
		$formfile->list_of_documents($CGV_files, null, 'mycompany', '', 1, '', 1, 0, '', 0, 'none');
		print '				</td>
						</tr>';
		if (count($CGVs) > 0 || count($CGIs) > 0 || count($CGAs) > 0) {
			$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
			infraspackplus_print_liste_titre($metas);
			infraspackplus_print_btn_action('CGx', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), $rowSpan7, 4);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CGV_FROM_PRO', 'on_off', $langs->trans('InfraSPlusParamCGVFromPro'), '', array(), 2, 1, '<td>&nbsp;</td>', $num);
			if (!empty($conf->global->INFRASPLUS_PDF_CGV_FROM_PRO)) {
				$sortparam	= (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT); // NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on label.
				$metas		= $form->selectarray('INFRASPLUS_PDF_CGV_FROM_PRO_LABEL', $formcompany->typent_array(1), $conf->global->INFRASPLUS_PDF_CGV_FROM_PRO_LABEL, 0, 0, 0, 'style = "padding: 0px; font-size: inherit; cursor: pointer;"', 0, 0, 0, $sortparam, '', 1).' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_CGV_FROM_PRO_LABEL', 'select', $langs->trans('InfraSPlusParamCGVFromProLabel1').' <FONT color = "red">'.$langs->trans('InfraSPlusParamCGVFromProLabel2').'</FONT> '.$langs->trans('InfraSPlusParamCGVFromProLabel3').' <FONT color = "red">'.$langs->trans('InfraSPlusParamCGVFromProLabel2').'</FONT> '.$langs->trans('InfraSPlusParamCGVFromLang6'), '', $metas, 2, 1, '<td>&nbsp;</td>', $num);
			}
			else	$num++;
			if ($conf->global->MAIN_MULTILANGS)
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_CGV_FROM_LANG', 'on_off', $langs->trans('InfraSPlusParamCGVFromLang1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCGVFromLang2').' <FONT color = "red">'.$langs->trans('InfraSPlusParamCGVFromLang3').'</FONT> '.$langs->trans('InfraSPlusParamCGVFromLang4').' <FONT color = "red">'.$langs->trans('InfraSPlusParamCGVFromLang5').'</FONT> '.$langs->trans('InfraSPlusParamCGVFromLang6'), '', array(), 2, 1, '<td>&nbsp;</td>', $num);
			else	$num++;
			$desc		= $form->selectarray('INFRASPLUS_PDF_CGV', $CGVs, $selected_cgv, $langs->trans('InfraSPlusParamNoCGV'), 0, 1, 'style = "padding: 0px; font-size: inherit; cursor: pointer;"');
			$desc		.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans('InfraSPlusParamTypeDoc');
			$metas		= array();
			$metas[0]	= array('MAIN_MODULE_PROPALE'	=> $langs->trans('Proposals'),
								'MAIN_MODULE_COMMANDE'	=> $langs->trans('Orders'),
								'MAIN_MODULE_CONTRAT'	=> $langs->trans('Contracts'),
								'MAIN_MODULE_FACTURE'	=> $langs->trans('Invoices'));
			$metas[1]	= array('INFRASPLUS_PDF_CGV_BY_DEF_FOR_PROPOSALS'	=> 'MAIN_MODULE_PROPALE',
								'INFRASPLUS_PDF_CGV_BY_DEF_FOR_ORDERS'		=> 'MAIN_MODULE_COMMANDE',
								'INFRASPLUS_PDF_CGV_BY_DEF_FOR_CONTRACTS'	=> 'MAIN_MODULE_CONTRAT',
								'INFRASPLUS_PDF_CGV_BY_DEF_FOR_INVOICES'	=> 'MAIN_MODULE_FACTURE');
			$num	= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamDefaultCGV').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$desc, $metas, 3, 120, '&nbsp;', $num);
			$desc		= $form->selectarray('INFRASPLUS_PDF_CGI', $CGIs, $selected_cgi, $langs->trans('InfraSPlusParamNoCGI'), 0, 1, 'style = "padding: 0px; font-size: inherit; cursor: pointer;"');
			$metas[0]	= array('INFRASPLUS_PDF_NO_VALUE'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE1'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE2'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE3'	=> '&nbsp;');
			$metas[1]	= array('&nbsp;' => 'INFRASPLUS_PDF_NO_VALUE',
								'&nbsp;' => 'INFRASPLUS_PDF_NO_VALUE1',
								'&nbsp;' => 'INFRASPLUS_PDF_NO_VALUE2',
								'&nbsp;' => 'INFRASPLUS_PDF_NO_VALUE3');
			$num	= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamDefaultCGI').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$desc, $metas, 3, 120, '&nbsp;', $num);
			$desc		= $form->selectarray('INFRASPLUS_PDF_CGA', $CGAs, $selected_cga, $langs->trans('InfraSPlusParamNoCGA'), 0, 1, 'style = "padding: 0px; font-size: inherit; cursor: pointer;"');
			$desc		.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans('InfraSPlusParamTypeDoc');
			$metas[0]	= array('MAIN_MODULE_PROPALE'		=> $langs->trans('Proposals'),
								'MAIN_MODULE_COMMANDE'		=> $langs->trans('Orders'),
								'INFRASPLUS_PDF_NO_VALUE'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE1'	=> '&nbsp;');
			$metas[1]	= array('INFRASPLUS_PDF_CGA_BY_DEF_FOR_PROPOSALS'	=> 'MAIN_MODULE_PROPALE',
								'INFRASPLUS_PDF_CGA_BY_DEF_FOR_ORDERS'		=> 'MAIN_MODULE_COMMANDE',
								'&nbsp;'									=> 'INFRASPLUS_PDF_NO_VALUE',
								'&nbsp;'									=> 'INFRASPLUS_PDF_NO_VALUE1');
			$num	= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamDefaultCGA').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$desc, $metas, 3, 120, '&nbsp;', $num);
		}
		print '		</table>';
	}
	print '		</form>';
	dol_fiche_end();
	llxFooter();
	$db->close();
?>