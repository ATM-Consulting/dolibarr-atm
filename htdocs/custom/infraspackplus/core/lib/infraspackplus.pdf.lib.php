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
	* 	\file		../infraspackplus/core/lib/infraspackplus.pdf.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Set of functions used for InfraS PDF generation
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbank.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/barcode.lib.php';
	if ($conf->global->MAIN_MODULE_OUVRAGE)		dol_include_once('/ouvrage/class/ouvrage.class.php');
	if ($conf->global->MAIN_MODULE_OUVRAGE)		dol_include_once('/ouvrage/core/modules/modouvrage.class.php');
	if ($conf->global->MAIN_MODULE_SUBTOTAL)	dol_include_once('/subtotal/core/modules/modSubtotal.class.php');

	/************************************************
	*	Return array with format properties
	*
	*	@param	object		$template	Object we work on
	*	@return	void
	************************************************/
	function pdf_InfraSPlus_getValues(&$template)
	{
		global $conf, $langs, $mysoc;

		$template->update_main_doc_field				= 1;	// Save the name of generated file as the main doc when generating a doc with this template
		$template->emetteur								= $mysoc;
		if (empty($template->emetteur->country_code))	$template->emetteur->country_code										= substr($langs->defaultlang, -2);
		$template->atleastonediscount					= 0;
		$template->tva									= array();
		$template->localtax1							= array();
		$template->localtax2							= array();
		$template->credit_note							= isset($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)					? $conf->global->INVOICE_POSITIVE_CREDIT_NOTE					: 0;
		$template->atleastoneratenotnull				= 0;
		$template->situationinvoice						= False;
		$template->type									= 'pdf';
		$template->multilangs							= isset($conf->global->MAIN_MULTILANGS)									? $conf->global->MAIN_MULTILANGS								: 0;
		$template->use_fpdf								= isset($conf->global->MAIN_USE_FPDF)									? $conf->global->MAIN_USE_FPDF									: 0;
		$template->main_umask							= isset($conf->global->MAIN_UMASK)										? $conf->global->MAIN_UMASK										: '0755';
		$formatarray									= pdf_InfraSPlus_getFormat();
		$template->page_largeur							= $formatarray['width'];
		$template->page_hauteur							= $formatarray['height'];
		$template->format								= array($template->page_largeur, $template->page_hauteur);
		$template->marge_gauche							= $conf->global->MAIN_PDF_MARGIN_LEFT >= 4								? $conf->global->MAIN_PDF_MARGIN_LEFT							: 10;
		$template->marge_haute							= $conf->global->MAIN_PDF_MARGIN_TOP >= 4								? $conf->global->MAIN_PDF_MARGIN_TOP							: 10;
		$template->marge_droite							= $conf->global->MAIN_PDF_MARGIN_RIGHT >= 4								? $conf->global->MAIN_PDF_MARGIN_RIGHT							: 10;
		$template->marge_basse							= $conf->global->MAIN_PDF_MARGIN_BOTTOM >= 4							? $conf->global->MAIN_PDF_MARGIN_BOTTOM							: 10;
		$template->formatpage							= array('largeur'	=> $template->page_largeur,	'hauteur'	=> $template->page_hauteur,	'mgauche'	=> $template->marge_gauche,
																'mdroite'	=> $template->marge_droite,	'mhaute'	=> $template->marge_haute,	'mbasse'	=> $template->marge_basse);
		$template->use_iso_location						= isset($conf->global->MAIN_PDF_USE_ISO_LOCATION)						? $conf->global->MAIN_PDF_USE_ISO_LOCATION						: 0;
		$template->dash_between_line					= isset($conf->global->MAIN_PDF_DASH_BETWEEN_LINES)						? $conf->global->MAIN_PDF_DASH_BETWEEN_LINES					: 1;
		$template->product_use_unit						= isset($conf->global->PRODUCT_USE_UNITS)								? $conf->global->PRODUCT_USE_UNITS								: 0;
		$template->hide_vat_ifnull						= isset($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL)		? $conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL		: 0;
		$template->no_payment_details					= isset($conf->global->INVOICE_NO_PAYMENT_DETAILS)						? $conf->global->INVOICE_NO_PAYMENT_DETAILS						: 0;
		$template->produit_pdf_merge					= isset($conf->global->PRODUIT_PDF_MERGE_PROPAL)						? $conf->global->PRODUIT_PDF_MERGE_PROPAL						: 0;
		$template->hide_pay_term_cond					= isset($conf->global->PROPALE_PDF_HIDE_PAYMENTTERMCOND)				? $conf->global->PROPALE_PDF_HIDE_PAYMENTTERMCOND				: 0;
		$template->hide_pay_term_mode					= isset($conf->global->PROPALE_PDF_HIDE_PAYMENTTERMMOD)					? $conf->global->PROPALE_PDF_HIDE_PAYMENTTERMMOD				: 0;
		$template->chq_num								= isset($conf->global->FACTURE_CHQ_NUMBER)								? $conf->global->FACTURE_CHQ_NUMBER								: 0;
		$template->diffsize_title						= isset($conf->global->PDF_DIFFSIZE_TITLE)								? $conf->global->PDF_DIFFSIZE_TITLE								: 0;
		$template->hidechq_address						= isset($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)						? $conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS						: 0;
		$template->rib_num								= isset($conf->global->FACTURE_RIB_NUMBER)								? $conf->global->FACTURE_RIB_NUMBER								: 0;
		$template->use_doli_addr_livr					= isset($conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON)		? $conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON		: 0;
		$template->text_TVA_auto						= isset($conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO)				? $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO				: 0;
		$template->multi_files							= isset($conf->global->INFRASPLUS_PDF_MULTI_FILES)						? $conf->global->INFRASPLUS_PDF_MULTI_FILES						: 0;
		$template->font									= isset($conf->global->INFRASPLUS_PDF_FONT)								? $conf->global->INFRASPLUS_PDF_FONT							: 'centurygothic';
		$template->headertxtcolor						= isset($conf->global->INFRASPLUS_PDF_HEADER_TEXT_COLOR)				? $conf->global->INFRASPLUS_PDF_HEADER_TEXT_COLOR				: 0;
		$template->headertxtcolor						= explode(',', $template->headertxtcolor);
		$template->bodytxtcolor							= isset($conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR)					? $conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR					: 0;
		$template->bodytxtcolor							= explode(',', $template->bodytxtcolor);
		$template->datesbold							= isset($conf->global->INFRASPLUS_PDF_DATES_BOLD)						? $conf->global->INFRASPLUS_PDF_DATES_BOLD						: 0;
		$template->ref_from_cust						= isset($conf->global->INFRASPLUS_PDF_REFD_FROM_CUSTOMER)				? $conf->global->INFRASPLUS_PDF_REFD_FROM_CUSTOMER				: 0;
		$template->first_page_empty						= isset($conf->global->INFRASPLUS_PDF_FIRST_PAGE_EMPTY)					? $conf->global->INFRASPLUS_PDF_FIRST_PAGE_EMPTY				: 0;
		$template->small_head2							= isset($conf->global->INFRASPLUS_PDF_SMALL_HEAD_2)						? $conf->global->INFRASPLUS_PDF_SMALL_HEAD_2					: 0;
		$template->title_size							= isset($conf->global->INFRASPLUS_PDF_TITLE_SIZE)						? $conf->global->INFRASPLUS_PDF_TITLE_SIZE						: 2;
		$template->height_top_table						= isset($conf->global->INFRASPLUS_PDF_HEIGHT_TOP_TABLE)					? $conf->global->INFRASPLUS_PDF_HEIGHT_TOP_TABLE				: 4;
		$template->hide_top_table						= isset($conf->global->INFRASPLUS_PDF_HIDE_TOP_TABLE)					? $conf->global->INFRASPLUS_PDF_HIDE_TOP_TABLE					: 0;
		$template->Rounded_rect							= isset($conf->global->INFRASPLUS_PDF_ROUNDED_REC)						? $conf->global->INFRASPLUS_PDF_ROUNDED_REC						: 0;
		$template->bg_color								= isset($conf->global->INFRASPLUS_PDF_BACKGROUND_COLOR)					? $conf->global->INFRASPLUS_PDF_BACKGROUND_COLOR				: '';
		$template->txtcolor								= explode(',', pdf_InfraSPlus_txt_color($template->bg_color));
		$template->bg_color								= explode(',', $template->bg_color);
		$template->title_bg								= isset($conf->global->INFRASPLUS_PDF_TITLE_BG)							? $conf->global->INFRASPLUS_PDF_TITLE_BG						: 0;
		$template->header_after_addr					= isset($conf->global->INFRASPLUS_PDF_HEADER_AFTER_ADDR)				? $conf->global->INFRASPLUS_PDF_HEADER_AFTER_ADDR				: 0;
		$template->space_headerafter					= isset($conf->global->INFRASPLUS_PDF_SPACE_HEADERAFTER)				? $conf->global->INFRASPLUS_PDF_SPACE_HEADERAFTER				: 0;
		$template->header_align_left					= isset($conf->global->INFRASPLUS_PDF_HEADER_ALIGN_LEFT)				? $conf->global->INFRASPLUS_PDF_HEADER_ALIGN_LEFT				: 0;
		$template->dates_br								= isset($conf->global->INFRASPLUS_PDF_DATES_BR)							? $conf->global->INFRASPLUS_PDF_DATES_BR						: 0;
		$template->show_emet_details					= isset($conf->global->INFRASPLUS_PDF_SHOW_EMET_DETAILS)				? $conf->global->INFRASPLUS_PDF_SHOW_EMET_DETAILS				: 0;
		$template->show_recep_details					= isset($conf->global->INFRASPLUS_PDF_SHOW_RECEP_DETAILS)				? $conf->global->INFRASPLUS_PDF_SHOW_RECEP_DETAILS				: 0;
		$template->show_num_cli							= isset($conf->global->INFRASPLUS_PDF_SHOW_NUM_CLI)						? $conf->global->INFRASPLUS_PDF_SHOW_NUM_CLI					: 0;
		$template->num_cli_frm							= isset($conf->global->INFRASPLUS_PDF_NUM_CLI_FRM)						? $conf->global->INFRASPLUS_PDF_NUM_CLI_FRM						: 0;
		$template->show_code_cli_compt					= isset($conf->global->INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT)				? $conf->global->INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT 			: 0;
		$template->code_cli_compt_frm					= isset($conf->global->INFRASPLUS_PDF_CODE_CLI_COMPT_FRM)				? $conf->global->INFRASPLUS_PDF_CODE_CLI_COMPT_FRM				: 0;
		$template->add_creator_in_header				= isset($conf->global->INFRASPLUS_PDF_CREATOR_IN_HEADER)				? $conf->global->INFRASPLUS_PDF_CREATOR_IN_HEADER				: 0;
		$template->fold_mark							= isset($conf->global->INFRASPLUS_PDF_FOLD_MARK)						? $conf->global->INFRASPLUS_PDF_FOLD_MARK						: 0;
		$template->paid_watermark						= isset($conf->global->INFRASPLUS_PDF_FACTURE_PAID_WATERMARK)			? $conf->global->INFRASPLUS_PDF_FACTURE_PAID_WATERMARK			: '';
		$template->tblLineW								= isset($conf->global->INFRASPLUS_PDF_TBL_LINE_WIDTH)					? $conf->global->INFRASPLUS_PDF_TBL_LINE_WIDTH					: 0.2;
		$template->tblLineDash							= isset($conf->global->INFRASPLUS_PDF_TBL_LINE_DASH)					? $conf->global->INFRASPLUS_PDF_TBL_LINE_DASH					: '0';
		$template->tblLineColor							= isset($conf->global->INFRASPLUS_PDF_TBL_LINE_COLOR)					? $conf->global->INFRASPLUS_PDF_TBL_LINE_COLOR					: '';
		$template->showtblline							= $template->tblLineColor == '255, 255, 255' ? 0 : 1;
		$template->tblLineColor							= explode(',', $template->tblLineColor);
		$template->verLineColor							= isset($conf->global->INFRASPLUS_PDF_VER_LINE_COLOR)					? $conf->global->INFRASPLUS_PDF_VER_LINE_COLOR					: '';
		$template->showverline							= $template->verLineColor == '255, 255, 255' ? 0 : 1;
		$template->verLineColor							= explode(',', $template->verLineColor);
		$template->horLineColor							= isset($conf->global->INFRASPLUS_PDF_HOR_LINE_COLOR)					? $conf->global->INFRASPLUS_PDF_HOR_LINE_COLOR					: '';
		$template->horLineColor							= explode(',', $template->horLineColor);
		$template->hBC									= isset($conf->global->INFRASPLUS_PDF_HT_BC)							? $conf->global->INFRASPLUS_PDF_HT_BC							: 12;
		$template->wBC									= isset($conf->global->INFRASPLUS_PDF_LARG_BC)							? $conf->global->INFRASPLUS_PDF_LARG_BC							: 35;
		$template->dimC2D								= isset($conf->global->INFRASPLUS_PDF_DIM_C2D)							? $conf->global->INFRASPLUS_PDF_DIM_C2D							: 15;
		$template->lineSep_hight						= isset($conf->global->INFRASPLUS_PDF_LINESEP_HIGHT)					? $conf->global->INFRASPLUS_PDF_LINESEP_HIGHT					: 4;
		$template->show_ref_col							= isset($conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN)					? $conf->global->INFRASPLUS_PDF_WITH_REF_COLUMN					: 0;
		$template->show_num_col							= isset($conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN)					? $conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN					: 0;
		$template->force_align_left_ref					= isset($conf->global->INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF)				? $conf->global->INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF			: 'L';
		$template->picture_in_ref						= isset($conf->global->INFRASPLUS_PDF_PICTURE_IN_REF)					? $conf->global->INFRASPLUS_PDF_PICTURE_IN_REF					: 0;
		$template->picture_replace_ref					= isset($conf->global->INFRASPLUS_PDF_PICTURE_REPLACE_REF)				? $conf->global->INFRASPLUS_PDF_PICTURE_REPLACE_REF				: 0;
		$template->desc_full_line						= isset($conf->global->INFRASPLUS_PDF_DESC_FULL_LINE)					? $conf->global->INFRASPLUS_PDF_DESC_FULL_LINE					: 0;
		$template->show_desc							= isset($conf->global->INFRASPLUS_PDF_SHOW_DESC_DEV)					? $conf->global->INFRASPLUS_PDF_SHOW_DESC_DEV					: 0;
		$template->only_one_desc						= isset($conf->global->INFRASPLUS_PDF_ONLY_ONE_DESC)					? $conf->global->INFRASPLUS_PDF_ONLY_ONE_DESC					: 0;
		$template->hide_qty								= isset($conf->global->INFRASPLUS_PDF_HIDE_QTY)							? $conf->global->INFRASPLUS_PDF_HIDE_QTY						: 0;
		$template->hide_up								= isset($conf->global->INFRASPLUS_PDF_HIDE_UP)							? $conf->global->INFRASPLUS_PDF_HIDE_UP							: 0;
		$template->hide_discount						= isset($conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT)					? $conf->global->INFRASPLUS_PDF_HIDE_DISCOUNT					: 0;
		$template->show_up_discounted					= isset($conf->global->INFRASPLUS_PDF_SHOW_UP_DISCOUNTED)				? $conf->global->INFRASPLUS_PDF_SHOW_UP_DISCOUNTED				: 0;
		$template->discount_auto						= isset($conf->global->INFRASPLUS_PDF_DISCOUNT_AUTO)					? $conf->global->INFRASPLUS_PDF_DISCOUNT_AUTO					: 0;
		$template->show_ttc_col							= isset($conf->global->INFRASPLUS_PDF_WITH_TTC_COLUMN)					? $conf->global->INFRASPLUS_PDF_WITH_TTC_COLUMN					: 0;
		$template->hide_vat_col							= isset($conf->global->INFRASPLUS_PDF_WITHOUT_VAT_COLUMN)				? $conf->global->INFRASPLUS_PDF_WITHOUT_VAT_COLUMN				: 0;
		$template->show_ttc_vat_tot						= isset($conf->global->INFRASPLUS_PDF_TTC_WITH_VAT_TOT)					? $conf->global->INFRASPLUS_PDF_TTC_WITH_VAT_TOT				: 0;
		if ($template->show_ttc_vat_tot)				$template->hide_vat														= 1;
		$template->only_ttc								= isset($conf->global->INFRASPLUS_PDF_ONLY_TTC)							? $conf->global->INFRASPLUS_PDF_ONLY_TTC						: 0;
		if ($template->only_ttc) {
			$template->hide_vat_col	= 1;
			$template->hide_vat		= 1;
		}
		$template->only_ht								= isset($conf->global->INFRASPLUS_PDF_ONLY_HT)							? $conf->global->INFRASPLUS_PDF_ONLY_HT							: 0;
		if ($template->only_ht) {
			$template->hide_vat_col	= 1;
			$template->hide_vat		= 0;
		}
		$template->hide_totcol							= isset($conf->global->INFRASPLUS_PDF_HIDE_TOT_COL_DEVST)				? $conf->global->INFRASPLUS_PDF_HIDE_TOT_COL_DEVST				: 0;
		$template->larg_ref								= isset($conf->global->INFRASPLUS_PDF_LARGCOL_REF)						? $conf->global->INFRASPLUS_PDF_LARGCOL_REF						: 28;
		$template->larg_qty								= isset($conf->global->INFRASPLUS_PDF_LARGCOL_QTY)						? $conf->global->INFRASPLUS_PDF_LARGCOL_QTY						: 10;
		$template->larg_unit							= isset($conf->global->INFRASPLUS_PDF_LARGCOL_UNIT)						? $conf->global->INFRASPLUS_PDF_LARGCOL_UNIT					: 10;
		$template->larg_up								= isset($conf->global->INFRASPLUS_PDF_LARGCOL_UP)						? $conf->global->INFRASPLUS_PDF_LARGCOL_UP						: 22;
		$template->larg_tva								= isset($conf->global->INFRASPLUS_PDF_LARGCOL_TVA)						? $conf->global->INFRASPLUS_PDF_LARGCOL_TVA						: 14;
		$template->larg_discount						= isset($conf->global->INFRASPLUS_PDF_LARGCOL_DISC)						? $conf->global->INFRASPLUS_PDF_LARGCOL_DISC					: 14;
		$template->larg_updisc							= isset($conf->global->INFRASPLUS_PDF_LARGCOL_UPD)						? $conf->global->INFRASPLUS_PDF_LARGCOL_UPD						: 22;
		$template->larg_progress						= isset($conf->global->INFRASPLUS_PDF_LARGCOL_PROGRESS)					? $conf->global->INFRASPLUS_PDF_LARGCOL_PROGRESS				: 10;
		$template->larg_totalht							= isset($conf->global->INFRASPLUS_PDF_LARGCOL_TOTAL)					? $conf->global->INFRASPLUS_PDF_LARGCOL_TOTAL					: 24;
		$template->larg_totalttc						= isset($conf->global->INFRASPLUS_PDF_LARGCOL_TOTAL_TTC)				? $conf->global->INFRASPLUS_PDF_LARGCOL_TOTAL_TTC				: 24;
		$template->num_ref								= isset($conf->global->INFRASPLUS_PDF_NUMCOL_REF)						? $conf->global->INFRASPLUS_PDF_NUMCOL_REF						: 1;
		$template->num_desc								= isset($conf->global->INFRASPLUS_PDF_NUMCOL_DESC)						? $conf->global->INFRASPLUS_PDF_NUMCOL_DESC						: 2;
		$template->num_qty								= isset($conf->global->INFRASPLUS_PDF_NUMCOL_QTY)						? $conf->global->INFRASPLUS_PDF_NUMCOL_QTY						: 3;
		$template->num_unit								= isset($conf->global->INFRASPLUS_PDF_NUMCOL_UNIT)						? $conf->global->INFRASPLUS_PDF_NUMCOL_UNIT						: 4;
		$template->num_up								= isset($conf->global->INFRASPLUS_PDF_NUMCOL_UP)						? $conf->global->INFRASPLUS_PDF_NUMCOL_UP						: 5;
		$template->num_tva								= isset($conf->global->INFRASPLUS_PDF_NUMCOL_TVA)						? $conf->global->INFRASPLUS_PDF_NUMCOL_TVA						: 6;
		$template->num_discount							= isset($conf->global->INFRASPLUS_PDF_NUMCOL_DISC)						? $conf->global->INFRASPLUS_PDF_NUMCOL_DISC						: 7;
		$template->num_updisc							= isset($conf->global->INFRASPLUS_PDF_NUMCOL_UPD)						? $conf->global->INFRASPLUS_PDF_NUMCOL_UPD						: 8;
		$template->num_progress							= isset($conf->global->INFRASPLUS_PDF_NUMCOL_PROGRESS)					? $conf->global->INFRASPLUS_PDF_NUMCOL_PROGRESS					: 9;
		$template->num_totalht							= isset($conf->global->INFRASPLUS_PDF_NUMCOL_TOTAL)						? $conf->global->INFRASPLUS_PDF_NUMCOL_TOTAL					: 10;
		$template->num_totalttc							= isset($conf->global->INFRASPLUS_PDF_NUMCOL_TOTAL_TTC)					? $conf->global->INFRASPLUS_PDF_NUMCOL_TOTAL_TTC				: 11;
		$template->ht_space_info						= isset($conf->global->INFRASPLUS_PDF_SPACE_INFO)						? $conf->global->INFRASPLUS_PDF_SPACE_INFO						: 5;
		$template->ht_space_tot							= isset($conf->global->INFRASPLUS_PDF_SPACE_TOT)						? $conf->global->INFRASPLUS_PDF_SPACE_TOT						: 1;
		$template->show_qty_prod_tot					= isset($conf->global->INFRASPLUS_PDF_SHOW_QTY_PROD_TOT)				? $conf->global->INFRASPLUS_PDF_SHOW_QTY_PROD_TOT				: 0;
		$template->efPaySpec							= isset($conf->global->INFRASPLUS_PDF_EXF_PAY_SPEC)						? $conf->global->INFRASPLUS_PDF_EXF_PAY_SPEC					: '';
		$template->efDeposit							= isset($conf->global->INFRASPLUS_PDF_EXF_DEPOSIT)						? $conf->global->INFRASPLUS_PDF_EXF_DEPOSIT						: '';
		$template->IBAN_with_CB							= isset($conf->global->INFRASPLUS_PDF_IBAN_WITH_CB)						? $conf->global->INFRASPLUS_PDF_IBAN_WITH_CB					: 0;
		$template->IBAN_All								= isset($conf->global->INFRASPLUS_PDF_IBAN_ALL)							? $conf->global->INFRASPLUS_PDF_IBAN_ALL						: 0;
		$template->bank_only_number						= !empty($conf->global->INFRASPLUS_PDF_BANK_ONLY_NUMBER)				? $conf->global->INFRASPLUS_PDF_BANK_ONLY_NUMBER			    : 0;
		$template->show_outstandings					= isset($conf->global->INFRASPLUS_PDF_SHOW_OUTSTDBILL)					? $conf->global->INFRASPLUS_PDF_SHOW_OUTSTDBILL					: 0;
		$template->show_tot_local_cur					= isset($conf->global->INFRASPLUS_PDF_SHOW_TOTAL_LOCAL_CUR)				? $conf->global->INFRASPLUS_PDF_SHOW_TOTAL_LOCAL_CUR			: 0;
		$template->number_words							= isset($conf->global->INFRASPLUS_PDF_NUMBER_WORDS)						? $conf->global->INFRASPLUS_PDF_NUMBER_WORDS					: 0;
		$template->listPrefixEcotax						= isset($conf->global->OUVRAGE_LIST_PREFIX_ECOTAX)						? $conf->global->OUVRAGE_LIST_PREFIX_ECOTAX						: '';
		$template->listPrefixEcotax						= explode(',', $template->listPrefixEcotax);
		$template->ht_signarea							= isset($conf->global->INFRASPLUS_PDF_HT_SIGN_AREA)						? $conf->global->INFRASPLUS_PDF_HT_SIGN_AREA					: 24;
		$template->signLineW							= isset($conf->global->INFRASPLUS_PDF_SIGN_LINE_WIDTH)					? $conf->global->INFRASPLUS_PDF_SIGN_LINE_WIDTH					: 0.2;
		$template->signLineDash							= isset($conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH)					? $conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH					: '0';
		$template->signLineColor						= isset($conf->global->INFRASPLUS_PDF_SIGN_LINE_COLOR)					? $conf->global->INFRASPLUS_PDF_SIGN_LINE_COLOR					: '';
		$template->signLineColor						= explode(',', $template->signLineColor);
		if ($conf->global->MAIN_MODULE_CUSTOMLINK)
			$template->show_2sign_area					= isset($conf->global->INFRASPLUS_PDF_COMMANDE_OF_SHOW_2_SIGNATURES)	? $conf->global->INFRASPLUS_PDF_COMMANDE_OF_SHOW_2_SIGNATURES	: 0;
		else											$template->show_2sign_area	= 0;
		$template->e_signing							= isset($conf->global->INFRASPLUS_PDF_SHOW_E_SIGNING)					? $conf->global->INFRASPLUS_PDF_SHOW_E_SIGNING					: 0;
		$template->free_text_end						= isset($conf->global->INFRASPLUS_PDF_FREETEXTEND)						? $conf->global->INFRASPLUS_PDF_FREETEXTEND						: 0;
		$template->type_foot							= isset($conf->global->INFRASPLUS_PDF_TYPE_FOOT)						? $conf->global->INFRASPLUS_PDF_TYPE_FOOT						: '0000';
		$template->hidepagenum							= isset($conf->global->INFRASPLUS_PDF_HIDE_PAGE_NUM)					? $conf->global->INFRASPLUS_PDF_HIDE_PAGE_NUM					: 0;
		$template->wpicturefoot							= isset($conf->global->INFRASPLUS_PDF_PICTURE_FOOT_WIDTH)				? $conf->global->INFRASPLUS_PDF_PICTURE_FOOT_WIDTH				: 188;
		$template->hpicturefoot							= isset($conf->global->INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT)				? $conf->global->INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT				: 12;
		$template->maxsizeimgfoot						= array('largeur'=>$template->wpicturefoot, 'hauteur'=>$template->hpicturefoot);
		$template->only_one_picture						= isset($conf->global->INFRASPLUS_PDF_ONLY_ONE_PICTURE)					? $conf->global->INFRASPLUS_PDF_ONLY_ONE_PICTURE				: 0;
		$template->picture_after						= isset($conf->global->INFRASPLUS_PDF_PICTURE_AFTER)					? $conf->global->INFRASPLUS_PDF_PICTURE_AFTER					: 0;
		$template->picture_after						= empty($template->picture_in_ref)										? $template->picture_after										: 0;
		$template->picture_padding						= isset($conf->global->INFRASPLUS_PDF_PICTURE_PADDING)					? $conf->global->INFRASPLUS_PDF_PICTURE_PADDING					: 0;
		$template->linkpictureurl						= isset($conf->global->INFRASPLUS_PDF_LINK_PICTURE_URL)					? $conf->global->INFRASPLUS_PDF_LINK_PICTURE_URL				: '';
		$template->old_path_photo						= isset($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)					? $conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO					: 0;
		$template->cat_hq_image							= isset($conf->global->CAT_HIGH_QUALITY_IMAGES)							? $conf->global->CAT_HIGH_QUALITY_IMAGES						: 0;
		$template->alpha								= 0.2;
		$template->exftxtcolor							= isset($conf->global->INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR)				? $conf->global->INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR			: 0;
		$template->exftxtcolor							= explode(',', $template->exftxtcolor);
		$template->exfltxtcolor							= isset($conf->global->INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR)			? $conf->global->INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR			: 0;
		$template->exfltxtcolor							= explode(',', $template->exfltxtcolor);
	}

	/************************************************
	*	Return array with format properties
	*
	*	@param	string		$format			specific format to use
	*	@param	Translate	$outputlangs	Output lang to use to autodetect output format if setup not done
	*	@param	string		$mode			'setup' = Use setup, 'auto' = Force autodetection whatever is setup (this onkly if local $format is not used)
	*	@return	array						Array('width'=>w,'height'=>h,'unit'=>u);
	************************************************/
	function pdf_InfraSPlus_getFormat($format = '', $outputlangs = null, $mode = 'setup')
	{
		global $conf, $db, $langs;

		dol_syslog('pdf_InfraSPlus_getFormat Get paper format with mode = '.$mode.' MAIN_PDF_FORMAT = '.(empty($conf->global->MAIN_PDF_FORMAT) ? 'null' : $conf->global->MAIN_PDF_FORMAT).' outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null').' and langs->defaultlang = '.(is_object($langs) ? $langs->defaultlang : 'null'));
		// Default value if setup was not done and/or entry into c_paper_format not defined
		$width					= 210;
		$height					= 297;
		$unit					= 'mm';
		if (!empty($format))	$pdfformat	= $format;
		elseif ($mode == 'auto' || empty($conf->global->MAIN_PDF_FORMAT) || $conf->global->MAIN_PDF_FORMAT == 'auto') {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$pdfformat	= dol_getDefaultFormat($outputlangs);
		}
		else	$pdfformat	= $conf->global->MAIN_PDF_FORMAT;
		$sql	= 'SELECT code, label, width, height, unit FROM '.MAIN_DB_PREFIX.'c_paper_format';
		$sql	.= ' WHERE code = "'.$db->escape($pdfformat).'"';
		$resql	= $db->query($sql);
		if ($resql) {
			$obj	= $db->fetch_object($resql);
			if ($obj) {
				$width	= (int) $obj->width;
				$height	= (int) $obj->height;
				$unit	= $obj->unit;
			}
		}
		$db->free($resql);
		return array('width' => $width, 'height' => $height, 'unit' => $unit);
	}

	/************************************************
	*	Select text color from background values
	*
	*	@param	string		$bgcolor     		RGB value for background color
	*	@return	int								'255' or '0' for white (255, 255 ,255) or black (0, 0, 0)
	************************************************/
	function pdf_InfraSPlus_txt_color(&$bgcolor)
	{
		global $conf;

		$tmppart			= explode(',', $bgcolor);
		$tmpvalr			= (!empty($tmppart[0]) ? $tmppart[0] : 0) * 0.3;
		$tmpvalg			= (!empty($tmppart[1]) ? $tmppart[1] : 0) * 0.59;
		$tmpvalb			= (!empty($tmppart[2]) ? $tmppart[2] : 0) * 0.11;
		$tmpval				= $tmpvalr + $tmpvalg + $tmpvalb;
		if ($tmpval <= 128)	$colorauto	= '255, 255, 255';
		else				$colorauto	= '0, 0, 0';
		$textcolorauto		= isset($conf->global->INFRASPLUS_PDF_TEXT_COLOR_AUTO)	? $conf->global->INFRASPLUS_PDF_TEXT_COLOR_AUTO : 1;
		$colorman			= isset($conf->global->INFRASPLUS_PDF_TEXT_COLOR)		? $conf->global->INFRASPLUS_PDF_TEXT_COLOR : $colorauto;
		if ($textcolorauto)	return $colorauto;
		else				return $colorman;
	}

	/************************************************
	*	Display a background as a watermark
	*
	*	@param	TCPDF		$pdf            The PDF factory
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	* 	@param	int			$objEntity		Object entity
	* 	@return	void
	************************************************/
	function pdf_InfraSPlus_bg_watermark($pdf, $formatpage, $objEntity)
	{
		global $conf;

		$image_watermark		= isset($conf->global->INFRASPLUS_PDF_IMAGE_WATERMARK)		? $conf->global->INFRASPLUS_PDF_IMAGE_WATERMARK		: '';
		$watermark_i_opacity	= isset($conf->global->INFRASPLUS_PDF_I_WATERMARK_OPACITY)	? $conf->global->INFRASPLUS_PDF_I_WATERMARK_OPACITY	: 1;
		$logodir				= !empty($conf->mycompany->multidir_output[$objEntity])		? $conf->mycompany->multidir_output[$objEntity]		: $conf->mycompany->dir_output;
		$filigrane				= $logodir.'/logos/'.$image_watermark;
		if ($filigrane && is_readable($filigrane)) {
			$imgsize	= array();
			$imgsize	= pdf_InfraSPlus_getSizeForImage($filigrane, $formatpage['largeur'], $formatpage['hauteur']);
			if (isset($imgsize['width']) && isset($imgsize['height'])) {
				$pdf->SetAlpha($watermark_i_opacity / 100);
				$bMargin			= $pdf->getBreakMargin();	// get the current page break margin
				$auto_page_break	= $pdf->getAutoPageBreak();	// get current auto-page-break mode
				$pdf->SetAutoPageBreak(false, 0);	// disable auto-page-break
				$posxpicture		= ($formatpage['largeur'] - $imgsize['width']) / 2;	// centre l'image dans la page
				$posypicture		= ($formatpage['hauteur'] - $imgsize['height']) / 2;	// centre l'image dans la page
				$pdf->Image($filigrane, $posxpicture, $posypicture, $imgsize['width'], $imgsize['height'], '', '', '', false, 300, '', false, false, 0);	// set bacground image
				$pdf->SetAutoPageBreak($auto_page_break, $bMargin);	// restore auto-page-break status
				$pdf->setPageMark();	// set the starting point for the page content
				$pdf->SetAlpha(1);
			}
		}
	}

	/********************************************
	*	Calcul for total discount
	*
	*	@param		Object		$object			Object shown in PDF
	*	@param		int			$i				Row number
	*	@param		boolean		$only_ht		don't include taxes
	*	@param		array		$pricesObjProd	price datas from product (need if we use customer prices for product and automatic discount)
	*	@return		String						Return the difference between standart price and discounted one
	********************************************/
	function pdf_InfraSPlus_getTotRem($object, $i, $only_ht = 0, $pricesObjProd = array())
	{
		global $conf;

		$isSitFac		= !empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0 ? $object->lines[$i]->situation_percent / 100 : 1;	// use this to find the real unit price on situation invoice
		$TotBrutLine	= (!empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice) * $object->lines[$i]->qty * $isSitFac;
		$TotBrutLine	= !empty($pricesObjProd['pu_ht']) ? $pricesObjProd['pu_ht'] * $object->lines[$i]->qty : $TotBrutLine;
		if (!$only_ht) {
			$tvalignebrut		= $TotBrutLine * $object->lines[$i]->tva_tx / 100;
			$localtax1lignebrut	= $TotBrutLine * $object->lines[$i]->localtax1_tx / 100;
			$localtax2lignebrut	= $TotBrutLine * $object->lines[$i]->localtax2_tx / 100;
			return ($TotBrutLine + $tvalignebrut + $localtax1lignebrut + $localtax2lignebrut) - $object->lines[$i]->total_ttc;
		}
		else	return $TotBrutLine - $object->lines[$i]->total_ht;
	}

	/************************************************
	*	Show top small header of page.
	*
	*	@param	TCPDF		$pdf				The PDF factory
	*	@param  Translate	$outputlangs		Object lang for output
	*	@param	float		$posy				Position depart (hauteur)
	*	@param	float		$w					Largeur 1ère colonne
	* 	@param	boolean		$logo				Name of logo file
	* 	@param	object		$emetteur
	* 	@param	int			$marge_gauche
	* 	@param	int			$tab_hl
	* 	@param	array		$headertxtcolor		Text color
	* 	@param	int			$objEntity			Object entity
	* 	@return	float							Return height of logo
	************************************************/
	function pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $logo, $emetteur, $marge_gauche, $tab_hl, $headertxtcolor, $objEntity)
	{
		global $conf;

		$cat_hq_image			= isset($conf->global->CAT_HIGH_QUALITY_IMAGES)			? $conf->global->CAT_HIGH_QUALITY_IMAGES			: 0;
		$noMyLogo				= isset($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)		? $conf->global->PDF_DISABLE_MYCOMPANY_LOGO			: 0;
		$useLargeLogo			= isset($conf->global->MAIN_PDF_USE_LARGE_LOGO)			? $conf->global->MAIN_PDF_USE_LARGE_LOGO			: 0;
		$heightLogo				= 0;
		if (!empty($noMyLogo))	return $heightLogo;
		$logodir				= !empty($conf->mycompany->multidir_output[$objEntity])	? $conf->mycompany->multidir_output[$objEntity]		: $conf->mycompany->dir_output;
		if ($logo)				$logo	= $logodir.'/logos/'.$logo;
		else					$logo	= empty($useLargeLogo) && empty($cat_hq_image)	? $logodir.'/logos/thumbs/'.$emetteur->logo_small	: $logodir.'/logos/'.$emetteur->logo;
		if ($logo) {
			if (is_file($logo) && is_readable($logo)) {
				$heightLogo	= pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $marge_gauche, $posy, 0, $heightLogo);	// width=0 (auto)
			}
			else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($w, $tab_hl, $outputlangs->transnoentities("PDFInfraSPlusLogoFileNotFound", $logo), '', 'L', 0, 1, $marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($w, $tab_hl, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), '', 'L', 0, 1, $marge_gauche, $pdf->getY() + 1, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor($headertxtcolor[0], $headertxtcolor[1], $headertxtcolor[2]);
				$heightLogo	= $pdf->getY() + 1;
			}
		}
		else {
			$text		= $emetteur->name;
			$pdf->MultiCell($w, $tab_hl, $outputlangs->convToOutputCharset($text), '', 'L', 0, 1, $marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
			$heightLogo = $tab_hl;
		}
		return $heightLogo;
	}

	/************************************************
	*	Search Creator name
	*
	*	@param	Object		$object				Object
	*	@return	string							name found or empty
	************************************************/
	function pdf_InfraSPlus_creator($object, $outputlangs)
	{
		global $db;

		if (is_object($object->user_creation)) {
			if ($object->user_creation->id)	return $object->user_creation->getFullName($outputlangs);
			else							return '';
		}
		else {
			$userstatic				= new User($db);
			if ($object->user_creation_id > 0 || $object->user_creation > 0) {
				$userstatic->fetch($object->user_creation_id ? $object->user_creation_id : $object->user_creation);
			}
			if ($userstatic->id)	return $userstatic->getFullName($outputlangs);
			else {
				if ($object->user_author_id > 0 || $object->user_author > 0) {
					$userstatic->fetch($object->user_author_id ? $object->user_author_id : $object->user_author);
				}
				if ($userstatic->id)	return $userstatic->getFullName($outputlangs);
				else					return '';
			}
		}
	}

	/************************************************
	*	Show linked objects for PDF generation
	*
	*	@param	TCPDF			$pdf            	The PDF factory
	*	@param  Object			$object				Object shown in PDF
	*	@param	Translate		$outputlangs		Output langs object
	*	@param  int				$posx				Pos x
	*	@param  int				$posy				Pos y
	*	@param  int				$w					Width of cells. If 0, they extend up to the right margin of the page
	*	@param  int				$tab_hl				Cell minimum height. The cell extends automatically if needed.
	*	@param	int				$align				Align
	*	@param	int				$header_after_addr	Where it's displayed
	*	@return	float | string						The Y PDF position or the string to print
	************************************************/
	function pdf_InfraSPlus_writeLinkedObjects(&$pdf, $object, $outputlangs, $posx, $posy, $w, $tab_hl, $align, $header_after_addr = 0)
	{
		global $conf;

		$nodatelinked	= isset($conf->global->INFRASPLUS_PDF_NO_DATE_LINKED)	? $conf->global->INFRASPLUS_PDF_NO_DATE_LINKED : 0;
		$linkedobjects	= pdf_InfraSPlus_getLinkedObjects($object, $outputlangs);
		if (!empty($linkedobjects)) {
			$refstoshow	= '';
			foreach($linkedobjects as $linkedobject) {
				$reftoshow															= $linkedobject['ref_title'].' : '.$linkedobject['ref_value'];
				if (empty($nodatelinked) && !empty($linkedobject['date_value']))	$reftoshow	.= ' / '.$linkedobject['date_value'];
				if (empty($header_after_addr)) {
					$posy																+= $tab_hl - 0.5;
					$pdf->MultiCell($w, $tab_hl, dol_trunc($reftoshow, 60), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				else $refstoshow	.= ($refstoshow ? ' / ' : '').$reftoshow;
			}
		}
		return (empty($header_after_addr) ? $pdf->getY() : $refstoshow);
	}

	/************************************************
	*	Return linked objects to use for document generation.
	*	Warning: To save space, it returns only one link per link type (all links are concated on same record string).
	*	It is used by pdf_InfraSPlus_writeLinkedObjects
	*
	*	@param  Object		$object			Object shown in PDF
	*	@param	Translate	$outputlangs	Output langs object
	* 	@return	array                       Linked objects
	************************************************/
	function pdf_InfraSPlus_getLinkedObjects($object, $outputlangs)
	{
		global $conf, $db, $hookmanager;

		$propallinked		= isset($conf->global->INFRASPLUS_PDF_SHOW_REF_PROPAL)			? $conf->global->INFRASPLUS_PDF_SHOW_REF_PROPAL			: 0;
		$orderlinked		= isset($conf->global->INFRASPLUS_PDF_SHOW_REF_ORDER)			? $conf->global->INFRASPLUS_PDF_SHOW_REF_ORDER			: 0;
		$refcustonorder		= isset($conf->global->INFRASPLUS_PDF_SHOW_REF_CUST_ON_ORDER)	? $conf->global->INFRASPLUS_PDF_SHOW_REF_CUST_ON_ORDER	: 0;
		$shippinglinked		= isset($conf->global->INFRASPLUS_PDF_SHOW_REF_SHIPPING)		? $conf->global->INFRASPLUS_PDF_SHOW_REF_SHIPPING		: 0;
		$contractlinked		= isset($conf->global->INFRASPLUS_PDF_SHOW_REF_CONTRACT)		? $conf->global->INFRASPLUS_PDF_SHOW_REF_CONTRACT		: 0;
		$fichinterlinked	= isset($conf->global->INFRASPLUS_PDF_SHOW_REF_FICHINTER)		? $conf->global->INFRASPLUS_PDF_SHOW_REF_FICHINTER		: 0;
		$projectlinked		= isset($conf->global->INFRASPLUS_PDF_SHOW_REF_PROJECT)			? $conf->global->INFRASPLUS_PDF_SHOW_REF_PROJECT		: 0;
		$projectdesc		= isset($conf->global->INFRASPLUS_PDF_SHOW_DESC_PROJECT)		? $conf->global->INFRASPLUS_PDF_SHOW_DESC_PROJECT		: 0;
		$linkedobjects		= array();
		$object->fetchObjectLinked();
		foreach($object->linkedObjects as $objecttype => $objects) {
			if ($objecttype == 'facture') {
			   // For invoice, we don't want to have a reference line on document. Image we are using recuring invoice, we will have a line longer than document width.
			}
			elseif (($objecttype == 'propal' && $propallinked) || ($objecttype == 'supplier_proposal' && $propallinked)) {
				$outputlangs->load('propal');
				foreach($objects as $elementobject) {
					$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('RefProposal');
					$linkedobjects[$objecttype]['ref_value']	= $outputlangs->transnoentities($elementobject->ref);
					$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('DatePropal');
					$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->date, 'day', '', $outputlangs);
				}
			}
			elseif (($objecttype == 'commande' && $orderlinked) || ($objecttype == 'order_supplier' && $orderlinked)) {
				$outputlangs->load('orders');
				foreach($objects as $elementobject) {
					$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('RefOrder');
					$linkedobjects[$objecttype]['ref_value']	= $outputlangs->transnoentities($elementobject->ref).($elementobject->ref_client && $refcustonorder ? ' ('.$elementobject->ref_client.')' : '').($elementobject->ref_supplier ? ' ('.$elementobject->ref_supplier.')' : '');
					$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('OrderDate');
					$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->date, 'day', '', $outputlangs);
				}
			}
			elseif ($objecttype == 'contrat' && $contractlinked) {
				$outputlangs->load('contracts');
				foreach($objects as $elementobject) {
					$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('RefContract');
					$linkedobjects[$objecttype]['ref_value']	= $outputlangs->transnoentities($elementobject->ref);
					$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('DateContract');
					$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->date_contrat, 'day', '', $outputlangs);
				}
			}
			elseif ($objecttype == 'fichinter' && $fichinterlinked) {
				$outputlangs->load('interventions');
				foreach($objects as $elementobject) {
					$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('PDFInfraSPlusRefInter');
					$linkedobjects[$objecttype]['ref_value']	= $outputlangs->transnoentities($elementobject->ref);
					$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('Date');
					$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->datec, 'day', '', $outputlangs);
				}
			}
			elseif ($objecttype == 'shipping' && $shippinglinked) {
				foreach($objects as $x => $elementobject) {
					$order	= null;
					if (empty($object->linkedObjects['commande']) && $object->element != 'commande') {	// There is not already a link to order and object is not the order, so we show also info with order
						$elementobject->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
						if (!empty($elementobject->linkedObjectsIds['commande'])) {
							include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
							$order			= new Commande($db);
							$ret			= $order->fetch(reset($elementobject->linkedObjectsIds['commande']));
							if ($ret < 1)	$order	= null;
						}
					}
					if (! is_object($order)) {
						$linkedobjects[$objecttype]['ref_title']				= $outputlangs->transnoentities('RefSending');
						if (!empty($linkedobjects[$objecttype]['ref_value']))	$linkedobjects[$objecttype]['ref_value']	.=' / ';
						$linkedobjects[$objecttype]['ref_value']				.= $outputlangs->transnoentities($elementobject->ref);
						$linkedobjects[$objecttype]['date_title']				= $outputlangs->transnoentities('DateDeliveryPlanned');
						$linkedobjects[$objecttype]['date_value']				= dol_print_date($elementobject->date_delivery, 'day', '', $outputlangs);
					}
					else {
						$linkedobjects[$objecttype]['ref_title']				= $outputlangs->transnoentities('RefOrder').' / '.$outputlangs->transnoentities('RefSending');
						if (empty($linkedobjects[$objecttype]['ref_value']))	$linkedobjects[$objecttype]['ref_value']	= $outputlangs->convToOutputCharset($order->ref) . ($order->ref_client ? ' ('.$order->ref_client.')' : '');
						$linkedobjects[$objecttype]['ref_value']				.= ' / '.$outputlangs->transnoentities($elementobject->ref);
						$linkedobjects[$objecttype]['date_title']				= $outputlangs->transnoentities('DateDeliveryPlanned');
						$linkedobjects[$objecttype]['date_value']				= dol_print_date($elementobject->date_delivery, 'day', '', $outputlangs);
					}
				}
			}
		}
		if ($projectlinked && !empty($conf->projet->enabled) && !empty($object->fk_project)) {
			$proj		= new Project($db);
			$proj->fetch($object->fk_project);
			$linkedobjects['project']['ref_title']	= $outputlangs->transnoentities('RefProject');
			$linkedobjects['project']['ref_value']	= $proj->ref.($projectdesc ? ' - '.$proj->title : ''); // simplification => only the reference remains
		}
		// For add external linked objects
		if (is_object($hookmanager)) {
			$parameters							= array('linkedobjects' => $linkedobjects, 'outputlangs' => $outputlangs);
			$action								= '';
			$hookmanager->executeHooks('pdf_getLinkedObjects', $parameters, $object, $action);
			if (!empty($hookmanager->resArray))	$linkedobjects	= $hookmanager->resArray;
		}
		return $linkedobjects;
	}

	/************************************************
	*	Return linked shipping objects to use for document generation.
	*
	*	@param  Object		$object			Object shown in PDF
	*	@param	Translate	$outputlangs	Output langs object
	* 	@return	array                       Linked shippings
	************************************************/
	function pdf_InfraSPlus_getLinkedshippings($object, $outputlangs)
	{
		global $conf, $db;

		$linkedshippings	= array();
		$sql				 = 'SELECT *';
		$sql				.= ' FROM '.MAIN_DB_PREFIX.'element_element AS ee';
		$sql				.= ' INNER JOIN '.MAIN_DB_PREFIX.'expedition AS e';
		$sql				.= ' ON ee.fk_source = e.rowid';
		$sql				.= ' WHERE ee.sourcetype = "shipping"';
		$sql				.= ' AND ee.fk_target = "'.$object->id.'"';
		$resql				= $db->query($sql);
		if ($resql) {
			$num	= $db->num_rows($resql);
			for ($i = 0; $i < $num; $i++) {
				$obj		= $db->fetch_object($resql);
				$linkedshippings['ref']					= $obj->ref;
				$linkedshippings['date_delivery']		= $obj->date_delivery;
				$linkedshippings['fk_shipping_method']	= $obj->fk_shipping_method;
				$linkedshippings['tracking_number']		= $obj->tracking_number;
			}
		}
		$db->free($resql);
		return $linkedshippings;
	}

	/************************************************
	*	Get all addresses needed
	*
	*	@param  object		$object			Object shown in PDF
	*	@param  Translate	$outputlangs	Object lang for output
	*	@param	array		$arrayidcontact	List of contact ID
	*	@param	string		$adr			Alternative address for sender
	* 	@param	string		$adrlivr		Shipping address
	* 	@param	object		$emetteur		Object company
	* 	@param	boolean		$invLivr		Inversion shipping address and recepient one
	* 	@param	string		$typeadr		for supplier documents if we use both internal and external adrresses (shipping to customer)
	*	@param	object		$adrfact		Object address
	*	@param	int			$ticket			set to 1 for special format (small page)
	*	@param	object		$adrSst			Object address
	* 	@return	array		Return all addresses found
	************************************************/
	function pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $adr, $adrlivr, $emetteur, $invLivr = 0, $typeadr = '', $adrfact = null, $ticket = 0, $adrSst = null)
	{
		global $db, $conf;

		$show_emet_details	= isset($conf->global->INFRASPLUS_PDF_SHOW_EMET_DETAILS) && empty($ticket)	? $conf->global->INFRASPLUS_PDF_SHOW_EMET_DETAILS		: 0;
		$useCompNameContact	= isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)					? $conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT		: 0;
		$includealias		= isset($conf->global->PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME)				? $conf->global->PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME	: 0;
		$show_sender_alias	= isset($conf->global->INFRASPLUS_PDF_SHOW_SENDER_ALIAS)					? $conf->global->INFRASPLUS_PDF_SHOW_SENDER_ALIAS		: 0;
		$show_recep_details	= isset($conf->global->INFRASPLUS_PDF_SHOW_RECEP_DETAILS) && empty($ticket)	? $conf->global->INFRASPLUS_PDF_SHOW_RECEP_DETAILS		: 0;
		$showadrlivr		= isset($conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION)				? $conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION	: 0;
		$free_addr_livr		= isset($conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF)						? $conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF			: '';
		if (!empty($free_addr_livr)) {
			$extrafields	= new ExtraFields($db);
			$extralabels	= $extrafields->fetch_name_optionals_label($object->table_element);
			$printable		= intval($extrafields->attributes[$object->table_element]['printable'][$free_addr_livr]);
			$value			= pdf_InfraSPlus_formatNotes($object, $outputlangs, $extrafields->showOutputField($free_addr_livr, $object->array_options['options_'.$free_addr_livr]));
			$free_addr_livr	= $printable == 1 || (!empty($value) && $printable == 2) ? $value : '';	// check if something is writting for this extrafield according to the extrafield management
		}
		$use_doli_addr_livr		= isset($conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON)									? $conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON		: 0;
		$doli_addr_livr_recep	= isset($conf->global->INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP) && !empty($use_doli_addr_livr)	? $conf->global->INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP	: 0;
		$showadrSsT				= isset($conf->global->INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT)										? $conf->global->INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT			: 0;
		// Sender properties
		$sender_Alias			= '';
		$carac_emetteur			= '';
		// Add internal contact if defined
		if (is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0) {
			$object->fetch_user($arrayidcontact['I'][0]);
			$carac_emetteur	.= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		}
		if ($adrlivr && $typeadr == 'supplierInvoice')	$carac_emetteur .= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $object->client, '', 0, ($show_emet_details ? 'source' : 'sourcewithnodetails'), $object, 1, false, $ticket);
		elseif ($adr) {
			$sender_Alias	= $show_sender_alias ? $adr->name : '';
			$carac_emetteur .= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $adr, $object->thirdparty, '', 0, ($show_emet_details ? 'source' : 'sourcewithnodetails'), $object, 1, false, $ticket);
		}
		else	$carac_emetteur .= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $object->thirdparty, '', 0, ($show_emet_details ? 'source' : 'sourcewithnodetails'), $object, 1, false, $ticket);
		if (is_object ($arrayidcontact['U'])) {
			$carac_client_name	= $outputlangs->transnoentities("AUTHOR").' : '.$arrayidcontact['U']->getFullName($outputlangs);
			$carac_client		= $outputlangs->transnoentities("DateCreation").' : '.dol_print_date($object->date_create, 'day', false, $outputlangs)."\n";
			$det_client			= $outputlangs->convToOutputCharset(dol_format_address($arrayidcontact['U']->address, 0, "\n", $outputlangs))."\n";
			$det_client			.= $arrayidcontact['U']->email ? $outputlangs->transnoentities("Email").' : '.$outputlangs->convToOutputCharset($arrayidcontact['U']->email)."\n" : '';
			$det_client			.= $arrayidcontact['B']->iban ? $outputlangs->transnoentities("IBAN").' : '.$outputlangs->convToOutputCharset($arrayidcontact['B']->iban)."\n" : '';
			$carac_client		.= $det_client ? $det_client."\n" : '';
			if ($object->fk_statut == 99 && $object->fk_user_refuse > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_refuse);
				$carac_client	.= $outputlangs->transnoentities("REFUSEUR").' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities("MOTIF_REFUS").' : '.$outputlangs->convToOutputCharset($object->detail_refuse)."\n";
				$carac_client	.= $outputlangs->transnoentities("DATE_REFUS").' : '.dol_print_date($object->date_refuse, 'day', false, $outputlangs);
			}
			elseif ($object->fk_statut == 4 && $object->fk_user_cancel > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_cancel);
				$carac_client	.= $outputlangs->transnoentities("CANCEL_USER").' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities("MOTIF_CANCEL").' : '.$outputlangs->convToOutputCharset($object->detail_cancel)."\n";
				$carac_client	.= $outputlangs->transnoentities("DATE_CANCEL").' : '.dol_print_date($object->date_cancel, 'day', false, $outputlangs);
			}
			elseif ($object->fk_user_approve > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_approve);
				$carac_client	.= $outputlangs->transnoentities("VALIDOR").' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities("DateApprove").' : '.dol_print_date($object->date_approve, 'day', false, $outputlangs);
			}
			if ($object->fk_statut == 6 && $object->fk_user_paid > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_paid);
				$carac_client	.= $outputlangs->transnoentities("AUTHORPAIEMENT").' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities("DATE_PAIEMENT").' : '.dol_print_date($object->date_paiement, 'day', false, $outputlangs);
			}
		}
		else {
			// Recipient properties - If contact defined, we use it
			if ($adrfact) {
				$carac_client		= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $adrfact, '', 0, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 2, false, $ticket);
				if ($carac_client)	$carac_client_name	= $outputlangs->convToOutputCharset($adrfact->name);
			}
			else {
				$usecontact		= false;
				$norepeatname	= false;
				// $listElementsCli	= array('propal', 'commande', 'facture');
				if (!empty($doli_addr_livr_recep) && is_array($arrayidcontact['L']) && count($arrayidcontact['L']) > 0 && $object->element == 'commande') {
					$usecontact	= true;
					$result		= $object->fetch_contact($arrayidcontact['L'][0]);
				}
				elseif (is_array($arrayidcontact['E']) && count($arrayidcontact['E']) > 0) {
					$usecontact	= true;
					$result		= $object->fetch_contact($arrayidcontact['E'][0]);
				}
				//Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($useCompNameContact) && !empty($object->contact)) {
					$norepeatname	= true;
					$thirdparty		= $object->contact;
				}
				else				$thirdparty	= $typeadr == 'supplierInvoice' ? $adrlivr : infraspackplus_check_parent_addr_fact ($object);
				$carac_client_name	= pdf_InfraSPlus_Build_Third_party_Name($thirdparty, $outputlangs, $includealias, $object->contact);
				$carac_client		= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $thirdparty, ($usecontact ? $object->contact : ''), $usecontact, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 1, $norepeatname, $ticket);
			}
			// Shipping address
			if (!empty($use_doli_addr_livr) && is_array($arrayidcontact['L']) && count($arrayidcontact['L']) > 0 && $object->element == 'facture') {
				$result		= $object->fetch_contact($arrayidcontact['L'][0]);
				$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $object->contact, $object->contact, 1, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 1, false, $ticket);
			}
			elseif ($showadrlivr && $adrlivr && empty($free_addr_livr)) {
				if ($adrlivr == 'Default')	$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $object->thirdparty, '', 0, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 0, false, $ticket);
				else						$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $adrlivr, '', 0, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 0, false, $ticket);
			}
			// Subcontractor address
			if ($showadrSsT && $adrSst)	$SsTshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $adrSst, '', 0, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 0, false, $ticket);
		}
		return	array(	'sender_Alias'		=> $sender_Alias,
						'carac_emetteur'	=> $carac_emetteur,
						'carac_client_name'	=> $invLivr && $livrshow	? $adrlivr->name	: $carac_client_name,
						'carac_client'		=> $invLivr && $livrshow	? $livrshow			: $carac_client,
						'livrshow'			=> empty($free_addr_livr)	? $livrshow			: $free_addr_livr,
						'SsTshow'			=> $SsTshow
					 );
	}

	/************************************************
	*	Return a string with full address formated for output on documents
	*
	*	@param	Translate			$outputlangs		Output langs object
	*	@param  Societe				$sourcecompany		Source company object
	*	@param  Address				$sourceaddress		Source address object
	*	@param  Societe|string|null	$targetcompany		Target company object
	*	@param  Contact|string|null	$targetcontact		Target contact object
	*	@param	int					$usecontact			Use contact instead of company
	*	@param	string				$mode				Address type ('source', 'sourcewithnodetails', 'target', 'targetwithnodetails', 'targetwithdetails',
	*																	'targetwithdetails_xxx': target but include also phone/fax/email/url)
	*	@param  Object				$object				Object we want to build document for
	*	@param  Bolean				$profids			Display profesionnal IDs
	*	@param  Bolean				$norepeatname		Display name of contact
	*	@return	string									String with full address
	************************************************/
	function pdf_InfraSPlus_build_address($outputlangs, $sourcecompany, $sourceaddress = '', $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null, $profids = 0, $norepeatname = false, $ticket = 0)
	{
		global $conf, $hookmanager;

		if (strpos($mode, 'source') === 0 && ! is_object($sourcecompany))		return -1;
		if (strpos($mode, 'source') === 0 && ! is_object($sourceaddress))		return -1;
		if (strpos($mode, 'target') === 0 && ! is_object($targetcompany))		return -1;
		if (!empty($sourceaddress->state_id) && empty($sourceaddress->state))	$sourceaddress->state	= getState($sourceaddress->state_id);
		if (!empty($targetcompany->state_id) && empty($targetcompany->state))	$targetcompany->state	= getState($targetcompany->state_id);
		$targetcompanyIDs														= $profids == 2 ? infraspackplus_check_parent_addr_fact ($object) : $targetcompany;
		$reshook																= 0;
		$stringaddress															= '';
		$disableSourceDet	= isset($conf->global->MAIN_PDF_DISABLESOURCEDETAILS)				? $conf->global->MAIN_PDF_DISABLESOURCEDETAILS				: 0;
		$forceWithCountry	= isset($conf->global->INFRASPLUS_PDF_WITH_COUNTRY)					? $conf->global->INFRASPLUS_PDF_WITH_COUNTRY				: 0;
		$tvaInSourceAddr	= isset($conf->global->INFRASPLUS_PDF_TVAINTRA_IN_SOURCE_ADDRESS)	? $conf->global->INFRASPLUS_PDF_TVAINTRA_IN_SOURCE_ADDRESS	: 0;
		$id1InSourceAddr	= isset($conf->global->INFRASPLUS_PDF_PROFID1_IN_SOURCE_ADDRESS)	? $conf->global->INFRASPLUS_PDF_PROFID1_IN_SOURCE_ADDRESS	: 0;
		$id2InSourceAddr	= isset($conf->global->INFRASPLUS_PDF_PROFID2_IN_SOURCE_ADDRESS)	? $conf->global->INFRASPLUS_PDF_PROFID2_IN_SOURCE_ADDRESS	: 0;
		$id3InSourceAddr	= isset($conf->global->INFRASPLUS_PDF_PROFID3_IN_SOURCE_ADDRESS)	? $conf->global->INFRASPLUS_PDF_PROFID3_IN_SOURCE_ADDRESS	: 0;
		$id4InSourceAddr	= isset($conf->global->INFRASPLUS_PDF_PROFID4_IN_SOURCE_ADDRESS)	? $conf->global->INFRASPLUS_PDF_PROFID4_IN_SOURCE_ADDRESS	: 0;
		$id5InSourceAddr	= isset($conf->global->INFRASPLUS_PDF_PROFID5_IN_SOURCE_ADDRESS)	? $conf->global->INFRASPLUS_PDF_PROFID5_IN_SOURCE_ADDRESS	: 0;
		$id6InSourceAddr	= isset($conf->global->INFRASPLUS_PDF_PROFID6_IN_SOURCE_ADDRESS)	? $conf->global->INFRASPLUS_PDF_PROFID6_IN_SOURCE_ADDRESS	: 0;
		$moreInSourceAddr	= isset($conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS)			? $conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS			: 0;
		$targetDet			= isset($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS)				? $conf->global->MAIN_PDF_ADDALSOTARGETDETAILS				: 0;
		$showNumCli			= isset($conf->global->INFRASPLUS_PDF_SHOW_NUM_CLI)					? $conf->global->INFRASPLUS_PDF_SHOW_NUM_CLI				: 0;
		$numCliFrm			= isset($conf->global->INFRASPLUS_PDF_NUM_CLI_FRM)					? $conf->global->INFRASPLUS_PDF_NUM_CLI_FRM					: 0;
		$showCodeCliCompt   = isset($conf->global->INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT)			? $conf->global->INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT 		: 0;
		$codeCliComptFrm	= isset($conf->global->INFRASPLUS_PDF_CODE_CLI_COMPT_FRM)			? $conf->global->INFRASPLUS_PDF_CODE_CLI_COMPT_FRM			: 0;
		$noTvaAddr			= isset($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS)				? $conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS				: 0;
		$id1InAddr			= isset($conf->global->MAIN_PROFID1_IN_ADDRESS)						? $conf->global->MAIN_PROFID1_IN_ADDRESS					: 0;
		$id2InAddr			= isset($conf->global->MAIN_PROFID2_IN_ADDRESS)						? $conf->global->MAIN_PROFID2_IN_ADDRESS					: 0;
		$id3InAddr			= isset($conf->global->MAIN_PROFID3_IN_ADDRESS)						? $conf->global->MAIN_PROFID3_IN_ADDRESS					: 0;
		$id4InAddr			= isset($conf->global->MAIN_PROFID4_IN_ADDRESS)						? $conf->global->MAIN_PROFID4_IN_ADDRESS					: 0;
		$id5InAddr			= isset($conf->global->MAIN_PROFID5_IN_ADDRESS)						? $conf->global->MAIN_PROFID5_IN_ADDRESS					: 0;
		$id6InAddr			= isset($conf->global->MAIN_PROFID6_IN_ADDRESS)						? $conf->global->MAIN_PROFID6_IN_ADDRESS					: 0;
		$noteInAddr			= isset($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS)					? $conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS				: 0;
		if (is_object($hookmanager)) {
			$parameters		= array('sourcecompany'=>&$sourcecompany, 'targetcompany'=>&$targetcompany, 'targetcontact'=>&$targetcontact, 'outputlangs'=>$outputlangs, 'mode'=>$mode, 'usecontact'=>$usecontact);
			$action			= '';
			$reshook		= $hookmanager->executeHooks('pdf_build_address', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			$stringaddress	.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			$withCountry	= ((!empty($sourceaddress->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourceaddress->country_code)) || $forceWithCountry) ? 1 : 0;	// Country
			if ($mode == 'source' || $mode == 'sourcewithnodetails') {
				$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($sourceaddress, $withCountry, "\n", $outputlangs)).($ticket ? '' : "\n");
				if ( $mode != 'sourcewithnodetails') {
					if (empty($disableSourceDet)) {
						// Phone
						if ($sourceaddress->phone)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('PhoneShort').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($sourceaddress->phone)));
						// Fax
						if ($sourceaddress->fax)	$stringaddress	.= ($stringaddress ? ($sourceaddress->phone ? ' - ' : "\n") : '' ).$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($sourceaddress->fax)));
						// EMail
						if ($sourceaddress->email)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($sourceaddress->email);
						// Web
						if ($sourceaddress->url)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Web').' : '.$outputlangs->convToOutputCharset($sourceaddress->url);
					}
				}
				if ($profids) {
					$reg	= array();
					if ((!empty($tvaInSourceAddr) || !empty($ticket)) && !empty($sourcecompany->tva_intra)) {
						$tmpID			= pdf_InfraSPlus_build_IDs('TVA', $outputlangs->convToOutputCharset($sourcecompany->tva_intra), $sourcecompany->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('VATIntraShort').' : '.$tmpID;
					}
					if ((!empty($id1InSourceAddr) || !empty($ticket)) && !empty($sourcecompany->idprof1)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId1', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID1', $outputlangs->convToOutputCharset($sourcecompany->idprof1), $sourcecompany->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id2InSourceAddr) && !empty($sourcecompany->idprof2)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId2', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID2', $outputlangs->convToOutputCharset($sourcecompany->idprof2), $sourcecompany->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id3InSourceAddr) && !empty($sourcecompany->idprof3)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId3', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID3', $outputlangs->convToOutputCharset($sourcecompany->idprof3), $sourcecompany->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id4InSourceAddr) && !empty($sourcecompany->idprof4)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId4', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID4', $outputlangs->convToOutputCharset($sourcecompany->idprof4), $sourcecompany->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id5InSourceAddr) && !empty($sourcecompany->idprof5)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId5', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID5', $outputlangs->convToOutputCharset($sourcecompany->idprof5), $sourcecompany->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id6InSourceAddr) && !empty($sourcecompany->idprof6)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId6', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID6', $outputlangs->convToOutputCharset($sourcecompany->idprof6), $sourcecompany->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
				}
	    		if (!empty($moreInSourceAddr))	$stringaddress	.= ($stringaddress ? "\n" : '').$moreInSourceAddr;
			}
			if ($mode == 'target' || $mode == 'targetwithnodetails' || preg_match('/targetwithdetails/',$mode)) {
				if ($usecontact) {
					if (!$norepeatname)						$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1, -1));
					if (!empty($targetcontact->address))	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcontact, $withCountry, "\n", $outputlangs))."\n";
					else {
						$companytouseforaddress	= $targetcompany;
						if ($targetcontact->socid > 0 && $targetcontact->socid != $targetcompany->id) {	// Contact on a thirdparty that is a different thirdparty than the thirdparty of object
							$targetcontact->fetch_thirdparty();
							$companytouseforaddress	= $targetcontact->thirdparty;
						}
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress, $withCountry, "\n", $outputlangs))."\n";
					}
					if (!empty($targetDet) || preg_match('/targetwithdetails/', $mode)) {
						// Phone
						if (!empty($targetDet) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
							if (!empty($targetcontact->phone_pro) || !empty($targetcontact->phone_mobile))	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('PhoneShort').' : ';
							if (!empty($targetcontact->phone_pro))											$stringaddress	.= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcontact->phone_pro)));
							if (!empty($targetcontact->phone_pro) && !empty($targetcontact->phone_mobile))	$stringaddress	.= ' / ';
							if (!empty($targetcontact->phone_mobile))										$stringaddress	.= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcontact->phone_mobile)));
						}
						// Fax
						if (!empty($targetDet) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode))
							if ($targetcontact->fax)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcontact->fax)));
						// EMail
						if (!empty($targetDet) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode))
							if ($targetcontact->email)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($targetcontact->email);
						// Web
						if (!empty($targetDet) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode))
							if ($targetcontact->url)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Web').' : '.$outputlangs->convToOutputCharset($targetcontact->url);
					}
				}
				else {
					$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany, $withCountry, "\n", $outputlangs)).($ticket ? '' : "\n");
					if (!empty($targetDet) || preg_match('/targetwithdetails/', $mode)) {
						// Phone
						if (!empty($targetDet) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
							if (!empty($targetcompany->phone) || !empty($targetcompany->phone_mobile))	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('PhoneShort').' : ';
							if (!empty($targetcompany->phone))											$stringaddress	.= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcompany->phone)));
							if (!empty($targetcompany->phone) && !empty($targetcompany->phone_mobile))	$stringaddress	.= " / ";
							if (!empty($targetcompany->phone_mobile))									$stringaddress	.= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcompany->phone_mobile)));
						}
						// Fax
						if (!empty($targetDet) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode))
							if ($targetcompany->fax)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcompany->fax)));
						// EMail
						if (!empty($targetDet) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode))
							if ($targetcompany->email)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($targetcompany->email);
						// Web
						if (!empty($targetDet) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode))
							if ($targetcompany->url)	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Web').' : '.$outputlangs->convToOutputCharset($targetcompany->url);
					}
				}
				$listElementsCli	= array('propal', 'commande', 'facture', 'contrat', 'shipping', 'fichinter');
				$showNumCli			= !empty($showNumCli) && empty($numCliFrm) ? 1 : 0;
				$showCodeCliCompt	= !empty($showCodeCliCompt) && empty($codeCliComptFrm) ? 1 : 0;
				if (in_array($object->element, $listElementsCli) && $showNumCli && $mode != 'targetwithnodetails') {
					if ($object->thirdparty->code_client) {
						$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities('CustomerCode') . ' : ' . $outputlangs->convToOutputCharset($object->thirdparty->code_client);
					}
				}
				if (in_array($object->element, $listElementsCli) && $showCodeCliCompt && $mode != 'targetwithnodetails') {
					if ($object->thirdparty->code_compta) {
						$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities('CustomerAccountancyCode') . ' : ' . $outputlangs->convToOutputCharset($object->thirdparty->code_compta);
					}
				}
				// Intra VAT
				if (empty($noTvaAddr) && $mode != 'targetwithnodetails') {
					if ($targetcompanyIDs->tva_intra) {
						$tmpID			= pdf_InfraSPlus_build_IDs('TVA', $outputlangs->convToOutputCharset($targetcompanyIDs->tva_intra), $targetcompanyIDs->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('VATIntraShort').' : '.$tmpID;
					}
				}
				// Professionnal Ids
				if ($profids) {
					if (!empty($id1InAddr) && !empty($targetcompanyIDs->idprof1)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId1', $targetcompanyIDs->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID1', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof1), $targetcompanyIDs->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id2InAddr) && !empty($targetcompanyIDs->idprof2)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId2', $targetcompanyIDs->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID2', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof2), $targetcompanyIDs->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id3InAddr) && !empty($targetcompanyIDs->idprof3)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId3', $targetcompanyIDs->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID3', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof3), $targetcompanyIDs->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id4InAddr) && !empty($targetcompanyIDs->idprof4)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId4', $targetcompanyIDs->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID4', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof4), $targetcompanyIDs->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id5InAddr) && !empty($targetcompanyIDs->idprof5)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId5', $targetcompanyIDs->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID5', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof5), $targetcompanyIDs->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id6InAddr) && !empty($targetcompanyIDs->idprof6)) {
						$tmp										= $outputlangs->transcountrynoentities('ProfId6', $targetcompanyIDs->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg))	$tmp	= $reg[1];
						$tmpID										= pdf_InfraSPlus_build_IDs('ID6', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof6), $targetcompanyIDs->country_code);
						$stringaddress								.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
				}
				// Public note
				if (!empty($noteInAddr)) {
					if ($mode == 'source' && !empty($sourcecompany->note_public))
						$stringaddress	.= ($stringaddress ? "\n" : '' ).dol_string_nohtmltag($sourcecompany->note_public);
					if (($mode == 'target' || preg_match('/targetwithdetails/',$mode)) && !empty($targetcompany->note_public))
						$stringaddress	.= ($stringaddress ? "\n" : '' ).dol_string_nohtmltag($targetcompany->note_public);
				}
			}
		}
		return $stringaddress;
	}

	/************************************************
	*	Format IDs
	*
	*	@param	string		$typeID			Type of IDs (ID1, ID2, ..., TVA)
	*	@param	string		$profID			Original ID
	*	@param	string		$country_code	Country code of company
	*	@return	string						String with formated ID
	************************************************/
	function pdf_InfraSPlus_build_IDs($typeID, $profID, $country_code)
	{
		if (strtoupper($country_code) == 'FR') {
			if ($typeID == 'ID1' && dol_strlen($profID) == 9)	$profID	= substr($profID, 0, 3).' '.substr($profID, 3, 3).' '.substr($profID, 6, 3);
			if ($typeID == 'ID2' && dol_strlen($profID) == 14)	$profID	= substr($profID, 0, 3).' '.substr($profID, 3, 3).' '.substr($profID, 6, 3).' '.substr($profID, 9, 5);
			if ($typeID == 'TVA' && dol_strlen($profID) == 13)	$profID	= substr($profID, 0, 2).' '.substr($profID, 2, 2).' '.substr($profID, 4, 3).' '.substr($profID, 7, 3).' '.substr($profID, 10, 3);
		}
		return $profID;
	}

	/************************************************
	*	Returns the name of the thirdparty
	*
	*	@param	Societe|Contact     $thirdparty     Contact or thirdparty
	*	@param	Translate           $outputlangs    Output language
	*	@param	int                 $includealias   1 = Include alias name before name
	*	@return	string				String with name of thirdparty (+ alias if requested)
	************************************************/
	function pdf_InfraSPlus_Build_Third_party_Name($thirdparty, $outputlangs, $includealias = 0, $contact)
	{
		global $conf;

		$useCompNameContact		= isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) && !empty($contact)	? $conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT													: 0;
		$useCompNamePlusContact	= isset($conf->global->INFRASPLUS_PDF_COMPANY_NAME_PLUS_CONTACT)				? $conf->global->INFRASPLUS_PDF_COMPANY_NAME_PLUS_CONTACT											: 0;
		$useCompNamePlusContact	= !empty($useCompNameContact) && !empty($useCompNamePlusContact)				? 1																									: 0;
		$statusWithName			= isset($conf->global->INFRASPLUS_PDF_SHOW_STATUS_WITH_CLIENT_NAME)				? $conf->global->INFRASPLUS_PDF_SHOW_STATUS_WITH_CLIENT_NAME										: 0;
		$statusWithName			= !empty($statusWithName) && $thirdparty->forme_juridique_code					? ' '.$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($thirdparty->forme_juridique_code))	: '';
		$socname				= '';
		if ($thirdparty instanceof Societe && empty($useCompNameContact))			$socname	= $thirdparty->name.$statusWithName.($includealias && !empty($thirdparty->name_alias) ? ' - '.$thirdparty->name_alias : '');
		elseif ($thirdparty instanceof Contact && !empty($useCompNamePlusContact))	$socname	= $outputlangs->convToOutputCharset($thirdparty->getFullName($outputlangs, 1, -1))."\n".$thirdparty->socname;
		elseif ($thirdparty instanceof Contact)										$socname	= $outputlangs->convToOutputCharset($thirdparty->getFullName($outputlangs, 1, -1));
		return $outputlangs->convToOutputCharset($socname);
	}

	/************************************************
	*	Show top small header of page.
	*
	*	@param	TCPDF		$pdf			The PDF factory
	*	@param  Object		$object			Object shown in PDF
	*	@param  Translate	$outputlangs	Object lang for output
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	* 	@param	array		$dimCadres		Frame dimensions => 'R', 'S', 'Y'
	* 	@param	int			$tab_hl			Line height
	* 	@param	object		$emetteur		Object company
	* 	@param	array		$addresses		All addresses found
	* 	@param	int			$Rounded_rect	Radius corner value
	* 	@param	boolean		$ndf			Frames title for expense report
	* 	@return	float		return frame height
	************************************************/
	function pdf_InfraSPlus_writeAddresses(&$pdf, $object, $outputlangs, $formatpage, $dimCadres, $tab_hl, $emetteur, $addresses, $Rounded_rect, $ndf = false)
	{
		global $conf;

		$invert_sender_recipient			= isset($conf->global->MAIN_INVERT_SENDER_RECIPIENT)		? $conf->global->MAIN_INVERT_SENDER_RECIPIENT		: 0;
		$hide_labels_frames					= isset($conf->global->INFRASPLUS_PDF_HIDE_LABELS_FRAMES)	? $conf->global->INFRASPLUS_PDF_HIDE_LABELS_FRAMES	: 0;
		$hide_recep_frame					= isset($conf->global->INFRASPLUS_PDF_HIDE_RECEP_FRAME)		? $conf->global->INFRASPLUS_PDF_HIDE_RECEP_FRAME	: 0;
		$frmeLineW							= isset($conf->global->INFRASPLUS_PDF_FRM_E_LINE_WIDTH)		? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_WIDTH	: 0.2;
		$frmeLineDash						= isset($conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH)		? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH		: '0';
		$frmeLineColor						= isset($conf->global->INFRASPLUS_PDF_FRM_E_LINE_COLOR)		? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_COLOR	: '';
		$frmeLineColor						= explode(',', $frmeLineColor);
		$frmeBgColor						= isset($conf->global->INFRASPLUS_PDF_FRM_E_BG_COLOR)		? $conf->global->INFRASPLUS_PDF_FRM_E_BG_COLOR		: '';
		$frmeBgColor						= explode(',', $frmeBgColor);
		$frmeAlpha							= isset($conf->global->INFRASPLUS_PDF_FRM_E_OPACITY)		? $conf->global->INFRASPLUS_PDF_FRM_E_OPACITY		: 30;
		$frmrLineW							= isset($conf->global->INFRASPLUS_PDF_FRM_R_LINE_WIDTH)		? $conf->global->INFRASPLUS_PDF_FRM_R_LINE_WIDTH	: 0.2;
		$frmrLineDash						= isset($conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH)		? $conf->global->INFRASPLUS_PDF_FRM_R_LINE_DASH		: '0';
		$frmrLineColor						= isset($conf->global->INFRASPLUS_PDF_FRM_R_LINE_COLOR)		? $conf->global->INFRASPLUS_PDF_FRM_R_LINE_COLOR	: '';
		$frmrLineColor						= explode(',', $frmrLineColor);
		$frmrBgColor						= isset($conf->global->INFRASPLUS_PDF_FRM_R_BG_COLOR)		? $conf->global->INFRASPLUS_PDF_FRM_R_BG_COLOR		: '';
		$frmrBgColor						= explode(',', $frmrBgColor);
		$frmrAlpha							= isset($conf->global->INFRASPLUS_PDF_FRM_R_OPACITY)		? $conf->global->INFRASPLUS_PDF_FRM_R_OPACITY		: 30;
		$frmeLineCap						= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		$frmeLineStyle						= array('width'=>$frmeLineW, 'dash'=>$frmeLineDash, 'cap'=>$frmeLineCap, 'color'=>$frmeLineColor);
		$frmrLineCap						= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		$frmrLineStyle						= array('width'=>$frmrLineW, 'dash'=>$frmrLineDash, 'cap'=>$frmrLineCap, 'color'=>$frmrLineColor);
		$default_font_size					= pdf_getPDFFontSize($outputlangs);
		$decal_round						= $Rounded_rect > 0 ? $Rounded_rect : 0;
		if ($formatpage['largeur'] < 210)	$dimCadres['R']	= 84;	// To work with US executive format
		if (!empty($invert_sender_recipient)) {
			$dimCadres['xS']	= empty($dimCadres['xS']) ? $formatpage['largeur'] - $formatpage['mdroite'] - $dimCadres['S'] : $dimCadres['xS'];
			$dimCadres['xR']	= empty($dimCadres['xR']) ? $formatpage['mgauche'] : $dimCadres['xR'];
		}
		else {
			$dimCadres['xS']	= empty($dimCadres['xS']) ? $formatpage['mgauche'] : $dimCadres['xS'];
			$dimCadres['xR']	= empty($dimCadres['xR']) ? $formatpage['largeur'] - $formatpage['mdroite'] - $dimCadres['R'] : $dimCadres['xR'];
		}
		$pdf->startTransaction();
		$hauteurcadre	= pdf_InfraSPlus_writeFrame($pdf, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, 0, $hide_recep_frame);
		$pdf->rollbackTransaction(true);
		if (! $hide_labels_frames) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->MultiCell($dimCadres['S'], $tab_hl + 1, $outputlangs->transnoentities($ndf ? 'TripSociete' : 'BillFrom').' : ', '', 'L', 0, 1, $dimCadres['xS'] + $decal_round, $dimCadres['Y'] - 4, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($dimCadres['R'], $tab_hl + 1, $outputlangs->transnoentities($ndf ? 'TripNDF' : 'BillTo').' : ', '', 'L', 0, 1, $dimCadres['xR'] + $decal_round, $dimCadres['Y'] - 4, true, 0, 0, false, 0, 'M', false);
		}
		// Show sender frame
		if (! $hide_recep_frame) {
			$pdf->SetAlpha($frmeAlpha / 100);
			$frme	= (implode(',', $frmeLineColor) == '255, 255, 255' ? '' : 'D').( implode(',', $frmeBgColor) == '255, 255, 255' ? '' : 'F');
			$pdf->RoundedRect($dimCadres['xS'], $dimCadres['Y'], $dimCadres['S'], $hauteurcadre, $Rounded_rect, '1111', $frme, $frmeLineStyle, $frmeBgColor);
		}
		// Show recipient frame
		$pdf->SetAlpha($frmrAlpha / 100);
		$frmr	= (implode(',', $frmrLineColor) == '255, 255, 255' ? '' : 'D').( implode(',', $frmrBgColor) == '255, 255, 255' ? '' : 'F');
		$pdf->RoundedRect($dimCadres['xR'], $dimCadres['Y'], $dimCadres['R'], $hauteurcadre, $Rounded_rect, '1111', $frmr, $frmrLineStyle, $frmrBgColor);
		$pdf->SetAlpha(1);
		pdf_InfraSPlus_writeFrame($pdf, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, 0, $hide_recep_frame);
		return $hauteurcadre;
	}

	/************************************************
	*	Show top small header of page.
	*
	*	@param	TCPDF		$pdf				The PDF factory
	*	@param  Translate	$outputlangs		Object lang for output
	*	@param	int			$default_font_size	Font size
	* 	@param	int			$tab_hl				Line height
	* 	@param	array		$dimCadres			Frame dimensions => 'R', 'S', 'Y', 'xS', 'xR'
	* 	@param	object		$emetteur			Object company
	* 	@param	array		$addresses			All addresses found
	* 	@param	boolean		$ticket				Ticket format
	* 	@param	boolean		$hide_recep_frame	No sender frame
	* 	@param	float		return frame height
	************************************************/
	function pdf_InfraSPlus_writeFrame(&$pdf, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, $ticket = 0, $hide_recep_frame = 0)
	{
		global $conf;

		$frmeTxtColor	= isset($conf->global->INFRASPLUS_PDF_FRM_E_TEXT_COLOR)	? $conf->global->INFRASPLUS_PDF_FRM_E_TEXT_COLOR	: '';
		$frmeTxtColor	= explode(',', $frmeTxtColor);
		$frmrTxtColor	= isset($conf->global->INFRASPLUS_PDF_FRM_R_TEXT_COLOR)	? $conf->global->INFRASPLUS_PDF_FRM_R_TEXT_COLOR	: '';
		$frmrTxtColor	= explode(',', $frmrTxtColor);
		$statusWithName	= !empty($conf->global->INFRASPLUS_PDF_SHOW_STATUS_WITH_SENDER_NAME) && $emetteur->forme_juridique_code ? ' '.$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($emetteur->forme_juridique_code)) : '';
		if (! $hide_recep_frame) {
			// Show sender
			$posy				= $dimCadres['Y'];
			if (empty($ticket))	$pdf->SetTextColor($frmeTxtColor[0], $frmeTxtColor[1], $frmeTxtColor[2]);
			// Show sender name
			$pdf->SetFont('', 'B', $default_font_size - ($ticket ? 2 : 0));
			$emetteurName		= ($ticket ? $outputlangs->transnoentities('BillFrom').' : ' : '').($addresses['sender_Alias'] ? $addresses['sender_Alias'] : $emetteur->name);
			$pdf->MultiCell($dimCadres['S'] - 4, $tab_hl, $outputlangs->convToOutputCharset($emetteurName).$statusWithName, '', 'L', 0, 1, $dimCadres['xS'] + 2, $posy + 1, true, 0, 0, false, 0, 'M', false);
			$posy				= $pdf->getY();
			// Show sender information
			$pdf->SetFont('', '', $default_font_size - ($ticket ? 3 : 1));
			$pdf->MultiCell($dimCadres['S'] - 4, $tab_hl, $addresses['carac_emetteur'], '', 'L', 0, 1, $dimCadres['xS'] + 2, $posy, true, 0, 0, false, 0, 'M', false);
			$posyendsender		= $pdf->getY();
		}
		//Show Recipient
		if (empty($ticket))						$posy	= $dimCadres['Y'];
		else									$posy	= $posyendsender;
		if (empty($ticket))						$pdf->SetTextColor($frmrTxtColor[0], $frmrTxtColor[1], $frmrTxtColor[2]);
		// Show recipient name
		$pdf->SetFont('', 'B', $default_font_size - ($ticket ? 2 : 0));
		$pdf->MultiCell($dimCadres['R'] - 4, $tab_hl, ($ticket ? $outputlangs->transnoentities('BillTo').' : ' : '').$addresses['carac_client_name'], '', 'L', 0, 1, $dimCadres['xR'] + 2, $posy + 1, true, 0, 0, false, 0, 'M', false);
		$posy									= $pdf->getY();
		// Show recipient information
		$pdf->SetFont('', '', $default_font_size - ($ticket ? 3 : 1));
		$pdf->MultiCell($dimCadres['R'] - 4, $tab_hl, $addresses['carac_client'], '', 'L', 0, 1, $dimCadres['xR'] + 2, $posy, true, 0, 0, false, 0, 'M', false);
		$posyendrecipient						= $pdf->getY();
		if ($posyendsender > $posyendrecipient)	return ($posyendsender - $dimCadres['Y']) + 1;
		else									return ($posyendrecipient - $dimCadres['Y']) + 1;
	}

	/************************************************
	*	Show top small header of page.
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param  Object		$object     	Object shown in PDF
	*	@param  int	    	$showaddress    0=no, 1=yes
	*	@param  Translate	$outputlangs	Object lang for output
	* 	@param	string		$title			string title in connection with objet type
	* 	@param	Societe		$fromcompany	Object company
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	* 	@param	int			$decal_round	décalage en fonction du rayon des angles du tableau
	* 	@param	string		$logo			Objet logo to show
	* 	@param	array		$txtcolor		Text color
	************************************************/
	function pdf_InfraSPlus_pagesmallhead(&$pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $formatpage, $decal_round, $logo, $txtcolor = array(0, 0, 0))
	{
		global $conf;

		$default_font_size	= pdf_getPDFFontSize($outputlangs);
		$pdf->SetTextColor($txtcolor[0], $txtcolor[1], $txtcolor[2]);
		$pdf->SetFont('','', $default_font_size - 2);
		$posy				= $formatpage['mhaute'];
		$posx				= $formatpage['largeur'] - $formatpage['mdroite'] - 100 - $decal_round;
		// Logo
		$logodir			= !empty($conf->mycompany->multidir_output[$object->entity]) ? $conf->mycompany->multidir_output[$object->entity] : $conf->mycompany->dir_output;
		if ($logo)			$logo	= $logodir.'/logos/'.$logo;
		else				$logo	= $logodir.'/logos/'.$fromcompany->logo;
		if ($logo) {
			if (is_file($logo) && is_readable($logo))	$pdf->Image($logo, $formatpage['mgauche'], $posy, 0, 6);	// width=0 (auto)
			else {
				$pdf->SetTextColor(200, 0 ,0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 4, $outputlangs->transnoentities("PDFInfraSPlusLogoFileNotFound", $logo), '', 'L', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell(100, 4, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), '', 'L', 0, 1, $formatpage['mgauche'], $posy + 8, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor($txtcolor[0], $txtcolor[1], $txtcolor[2]);
			}
		}
		else {
			$text	= $fromcompany->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), '', 'L', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
		}
		$posy				+= 3;
		pdf_InfraSPlus_pagesrefdate($pdf, $object, $outputlangs, $title, $posy, $posx);
	}

	/************************************************
	*	Function whitch returns vat statement (according to the seller, the buyer and the products present in the document)
	*	If the seller is in france and not subject to VAT => statement n° 1 => End of rule.
	*	If the seller is not subject to VAT => End of rule.
	*	If the seller and the buyer are from the same country => End of rule.
	*	If the seller and the buyer are from different countries from the EEC and there are services on the document => statement n ° 2 => End of rule.
	*	If the seller is from the EEC but not the buyer and there are services on the document => statement n ° 3 => End of rule.
	*	If the seller and the buyer are from different countries from the EEC and there are products on the document => statement n ° 4 => End of rule.
	*	If the seller is from the EEC but not the buyer and there are products on the document => statement n ° 5 => End of rule.
	*
	*	@param  Object		$object         Object shown in PDF
	*	@param	Societe		$seller			Object seller
	*	@param  Societe		$buyer			Object buyer
	*	@param  boolean		$hasService		there are services on the document
	*	@param	boolean		$hasProduct		there are products on the document
	*	@param	boolean		$show_tva_btp	we show the BTP mention
	*	@return array						0 = no mention or array of mention (keys are 'F' => franchise, 'S' => services, 'P' => products)
	************************************************/
	function pdf_InfraSPlus_VAT_auto($object, Societe $seller, Societe $buyer, $hasService = 0, $hasProduct = 0, $show_tva_btp = 0)
	{
		global $conf;

		$result									= array();
		$franchise								= ((is_numeric($seller->tva_assuj) && !$seller->tva_assuj) || (!is_numeric($seller->tva_assuj) && $seller->tva_assuj == 'franchise')) ? 1 : 0;
		$sellerCC								= $seller->country_code;
		$sellerInEEC							= isInEEC($seller);
		$buyerCC								= $buyer->country_code;
		$buyerInEEC								= isInEEC($buyer);
		// ($franchise && $sellerCC != 'FR') || $sellerCC == $buyerCC => nothing to do
		if ($sellerCC == 'FR' && $franchise)	$result['F']	= pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_1');
		elseif ($sellerInEEC && $buyerInEEC && $sellerCC != $buyerCC) {
			$result['S']	= $hasService ? pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_2') : '';
			$result['P']	= $hasProduct ? pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_4') : '';
		}
		elseif ($sellerInEEC && !$buyerInEEC) {
			$result['S']	= $hasService ? pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_3') : '';
			$result['P']	= $hasProduct ? pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_5') : '';
		}
		if ($show_tva_btp)	$result['B']	= pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_6');
		return (is_array($result) && count($result) > 0 ? $result : 0);
	}

	/************************************************
	*	Search label on dictionary from constant key that contained the code
	*
	*	@param  Object		$object         Object shown in PDF
	*	@param	string		$keyTVA		mention code to search on dictionary
	*	@return string					0 if Ko else label found on dictionary
	************************************************/
	function pdf_InfraSPlus_get_VAT_mention($object, $keyTVA)
	{
		global $conf;

		if ($object->element == 'propal')					$prefix	= 'PROPOSAL';
		elseif ($object->element == 'commande')				$prefix	= 'ORDER';
		elseif ($object->element == 'contrat')				$prefix	= 'CONTRACT';
		elseif ($object->element == 'shipping')				$prefix	= 'SHIPPING';
		elseif ($object->element == 'fichinter')			$prefix	= 'FICHINTER';
		elseif ($object->element == 'facture')				$prefix	= 'INVOICE';
		elseif ($object->element == 'supplier_proposal')	$prefix	= 'SUPPLIER_PROPOSAL';
		elseif ($object->element == 'order_supplier')		$prefix	= 'SUPPLIER_ORDER';
		$freeTTVA											= $prefix.'_FREE_TEXT_'.(isset($conf->global->$keyTVA) ? $conf->global->$keyTVA : '');
		return (isset($conf->global->$freeTTVA) ? $conf->global->$freeTTVA : '');
	}

	/************************************************
	*	Search label on dictionary from constant key that contained the code
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param  Object		$object         Object shown in PDF
	*	@param	Translate	$outputlangs	Objet langs
	*	@param	string		$txtTVA			mention code to search on dictionary
	*	@param	int			$w				width
	*	@param	int			$hl				line height
	*	@param	int			$x				position x
	*	@param	int			$y				position y
	*	@return string						next y position
	************************************************/
	function pdf_InfraSPlus_write_VAT_mention(&$pdf, $object, $outputlangs, $txtTVA, $w, $hl, $x, $y)
	{
		$txtTVA2	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $txtTVA);
		$pdf->writeHTMLCell($w, $h1, $x, $y, $txtTVA2, 0, 1, false, true, '', true);
		return ($pdf->GetY() + 2);
	}

	/************************************************
	*	Show free text
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param  Object		$object         Object shown in PDF
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	int			$posx			Position depart (largeur)
	*	@param	int			$posy			Position depart (hauteur)
	*	@param	Translate	$outputlangs	Objet langs
	* 	@param	Societe		$fromcompany	Object company
	* 	@param	array		$listfreetext	Root of constant names of free text
	*	@param	int			$withupline		Trace une ligne au-dessus du texte sur la largeur
	*	@param	int			$calculseul		Arrête la fonction au calcul de hauteur nécessaire
	*	@param	array		$LineStyle		PDF Line style
	* 	@return	int							Return height of free text
	************************************************/
	function pdf_InfraSPlus_free_text(&$pdf, $object, $formatpage, $posx, $posy, $outputlangs, $fromcompany, $listfreetext, $withupline = 0, $calculseul = 0, $LineStyle = null)
	{
		global $db, $conf;

		$pdf->startTransaction();
		$posy0			= $posy;
		$line			= '';
		if ($listfreetext && $listfreetext != 'None') {
			foreach ($listfreetext as $freeT) {
				$freetext	= isset($conf->global->$freeT) ? $conf->global->$freeT : '';
				// Line of free text
				if (!empty($freetext)) {
					$newfreetext	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $freetext);
					$line			.= $line ? '<br />'.$outputlangs->convToOutputCharset($newfreetext) : $outputlangs->convToOutputCharset($newfreetext);
				}
			}
		}
		if ($line) {	// Free text
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetFont('', '', $default_font_size - 2);
			if ($withupline) {
				$posy	+= 0.5;
				$pdf->Line($posx, $posy, $formatpage['largeur'] - $formatpage['mdroite'], $posy, $LineStyle);
			}
			$posy	+= 0.5;
			$pdf->writeHTMLCell(0, 3, $posx, $posy, dol_htmlentitiesbr($line), 0, 1);
			$posy	= $pdf->GetY() + 1;
		}
		if ($calculseul) {
			$heightforfreetext	= ($posy - $posy0);
			$pdf->rollbackTransaction(true);
			return $heightforfreetext;
		}
		else {
			$pdf->commitTransaction();
			return $posy;
		}
	}

	/********************************************
	*	Show notes
	*
	*	@param		TCPDF		$pdf				The PDF factory
	*	@param		Object		$object				Object shown in PDF
	* 	@param		array		$listnotep			Root of constant names of standard public notes
	*	@param		Translate	$outputlangs		Object lang for output
	*	@param		array		$exftxtcolor		text color values (RGB)
	*	@param		int			$default_font_size	font size value
	*	@param		int			$tab_top			height for top page (header)
	*	@param		int			$larg_util_txt		note width
	*	@param		int			$tab_hl				line height
	*	@param		int			$posx_G_txt			x position for the notes
	*	@param		array		$horLineStyle		params for horizontale line style
	*	@param		int			$usedSpace			used space height
	*	@param		int			$page_hauteur		page width
	*	@param		int			$Rounded_rect		radius value for rounded corner
	*	@param		boolean		$showtblline		Show note frame
	*	@param		int			$marge_gauche		left margin
	*	@param		int			$larg_util_cadre	table width
	*	@param		array		$tblLineStyle		params for table line style
	*	@param		int			$typeNotes			type of notes to show :	-2 => Cover page
	*																		-1 => short (public notes + extrafields)
	*																		0 => standard (sales representative + public notes + extrafields)
	*																		1 => extended (sales representative + public notes + extrafields + serial number for equipement)
	*	@param		boolean		$firstpageempty		If we insert an empty page first we need to change the condition to check the pitch of the notes
	*	@return		int								Return height of notes
	********************************************/
	function pdf_InfraSPlus_Notes(&$pdf, $object, $listnotep, $outputlangs, $exftxtcolor, $default_font_size, $tab_top, $larg_util_txt, $tab_hl, $posx_G_txt, $horLineStyle, $usedSpace, $page_hauteur, $Rounded_rect, $showtblline, $marge_gauche, $larg_util_cadre, $tblLineStyle, $typeNotes = 0, $firstpageempty = 0)
	{
		global $conf, $db;

		$show_sales_rep_in_notes	= isset($conf->global->INFRASPLUS_PDF_FIRST_SALES_REP_IN_NOTE)	? $conf->global->INFRASPLUS_PDF_FIRST_SALES_REP_IN_NOTE	: 0;
		switch ($object->element) {
			case 'propal':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_D)	? $conf->global->INFRASPLUS_PDF_EXF_D	: 0;
			break;
			case 'commande':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_C)				? $conf->global->INFRASPLUS_PDF_EXF_C				: 0;
				$add_prj_dateo_in_notes		= isset($conf->global->INFRASPLUS_PDF_PRJ_DATEO_IN_NOTE)	? $conf->global->INFRASPLUS_PDF_PRJ_DATEO_IN_NOTE	: 0;
				if (($object->modelpdf == 'InfraSPlus_OF' || $object->modelpdf == 'InfraSPlus_OM') && $add_prj_dateo_in_notes) {
					$txtDateoPrj	= $outputlangs->transnoentities('PDFInfraSPlusDateoPrj').' : ';
					if (!empty($conf->projet->enabled) && !empty($object->fk_project)) {
						$proj			= new Project($db);
						$proj->fetch($object->fk_project);
						$txtDateoPrj	.= dol_print_date($proj->date_start, 'day', false, $outputlangs, true);
					}
				}
			break;
			case 'contrat':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_CT)	? $conf->global->INFRASPLUS_PDF_EXF_CT	: 0;
			break;
			case 'fichinter':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_FI)				? $conf->global->INFRASPLUS_PDF_EXF_FI				: 0;
				$lastNoteAsTable			= isset($conf->global->INFRASPLUS_PDF_LAST_NOTE_AS_TABLE)	? $conf->global->INFRASPLUS_PDF_LAST_NOTE_AS_TABLE	: 0;
			break;
			case 'shipping':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_E)	? $conf->global->INFRASPLUS_PDF_EXF_E	: 0;
			break;
			case 'facture':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_F)	? $conf->global->INFRASPLUS_PDF_EXF_F	: 0;
			break;
			case 'supplier_proposal':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_DF)	? $conf->global->INFRASPLUS_PDF_EXF_DF	: 0;
			break;
			case 'order_supplier':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_CF)	? $conf->global->INFRASPLUS_PDF_EXF_CF	: 0;
			break;
			case 'invoice_supplier':
				$show_ExtraFields_in_notes	= isset($conf->global->INFRASPLUS_PDF_EXF_FF)	? $conf->global->INFRASPLUS_PDF_EXF_FF	: 0;
			break;
			default:
				$show_ExtraFields_in_notes	= 0;
			break;
		}
		$height_note	= 0;
		$salesrep		= !empty($show_sales_rep_in_notes) && $typeNotes > -1 ? pdf_InfraSPlus_SalesRepInNotes($object, $outputlangs) : '';
		if ($listnotep && $listnotep != 'None') {
			$notesptoshow	= array();
			foreach ($listnotep as $noteP) {
				$notePub				= isset($conf->global->$noteP) ? $conf->global->$noteP : '';
				if (!empty($notePub))	$notesptoshow[]	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $notePub);
			}
		}
		$notetoshow		= !empty($object->note_public) && empty($lastNoteAsTable) && $typeNotes > -2 ? pdf_InfraSPlus_formatNotes($object, $outputlangs, $object->note_public) : '';
		$extraDet		= !empty($show_ExtraFields_in_notes) && $typeNotes > -2 ? pdf_InfraSPlus_ExtraFieldsInNotes($object, $exftxtcolor, $outputlangs) : '';
		$serialEquip	= $typeNotes > 0 ? pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, 0, 'intervention') : '';
		if ($txtDateoPrj || $salesrep || !empty($notesptoshow) || $notetoshow || $extraDet || $serialEquip) {
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->startTransaction();
			$nexY				= $tab_top;
			if ($firstpageempty) {
				$pdf->line($posx_G_txt, $nexY + 1, $posx_G_txt + $larg_util_txt, $nexY + 1, $horLineStyle);
				$nexY	+= 2;
			}
			if ($txtDateoPrj)	$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $txtDateoPrj, $horLineStyle, ($salesrep || !empty($notesptoshow) || $notetoshow || $extraDet || $serialEquip ? 1 : 0));
			if ($salesrep)		$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $salesrep, $horLineStyle, (!empty($notesptoshow) || $notetoshow || $extraDet || $serialEquip ? 1 : 0));
			if (!empty($notesptoshow))
				foreach ($notesptoshow as $noteptoshow)	$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $noteptoshow, $horLineStyle, (next($notesptoshow) !== FALSE || $notetoshow || $extraDet || $serialEquip ? 1 : 0));
			if ($notetoshow)	$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $notetoshow, $horLineStyle, ($extraDet || $serialEquip ? 1 : 0));
			if ($extraDet)		$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $extraDet, $horLineStyle, ($serialEquip ? 1 : 0));
			if ($serialEquip)	$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $serialEquip, $horLineStyle, 0);
			if ($pdf->getPage() > ($firstpageempty ? 2 : 1) || $pdf->GetY() > ($page_hauteur - (($tab_hl * 4) + $usedSpace))) {	// Notes need pagebreak or There is no space left for footer
				$pdf->rollbackTransaction(true);
				$pdf->writeHTMLCell($larg_util_txt, $tab_hl, $posx_G_txt, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities("PDFInfraSPlusNoteTooLong")), 0, 1);
			}
			else				$pdf->commitTransaction();
			$nexY				= $pdf->GetY();
			$height_note		= $Rounded_rect * 2 > $nexY - $tab_top ? $Rounded_rect * 2 : $nexY - $tab_top;
			if ($showtblline)	$pdf->RoundedRect($marge_gauche, $tab_top - 1, $larg_util_cadre, $height_note + 2, $Rounded_rect, '1111', null, $tblLineStyle);
			$height_note		+= $tab_hl * 1.5;
		}
		return $height_note;
	}

	/********************************************
	*	Write notes
	*
	*	@param		TCPDF		$pdf				The PDF factory
	*	@param		int			$larg_util_txt		note width
	*	@param		int			$tab_hl				line height
	*	@param		int			$posx_G_txt			x position for the notes
	*	@param		int			$nexY				y position to start
	*	@param		string		$notes				notes to write
	*	@param		array		$horLineStyle		params for horizontale line style
	*	@param		boolean		$hasNextVal			Some more notes exist
	*	@return		int								Return Y value for the next position
	********************************************/
	function pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $notes, $horLineStyle, $hasNextVal = 0)
	{
		$pdf->writeHTMLCell($larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $notes, 0, 1);
		$nexY	= $pdf->GetY();
		if ($hasNextVal) {
			$pdf->line($posx_G_txt + 30, $nexY + 2, $posx_G_txt + $larg_util_txt - 30 , $nexY + 2, $horLineStyle);
			$nexY	= $pdf->GetY() + 4;
		}
		return $nexY;
	}

	/********************************************
	*	Get sales representative
	*
	*	@param		Object		$object			Object shown in PDF
	*	@param		Translate	$outputlangs	Object lang for output
	*	@return		String						Return sales representative with details if found
	********************************************/
	function pdf_InfraSPlus_SalesRepInNotes($object, $outputlangs)
	{
		global $db;

		$salesrep		= '';
		$arrayidcontact	= $object->getIdContact('internal', 'SALESREPFOLL');
		if (count($arrayidcontact) > 0) {
			$tmpuser					= new User($db);
			$tmpuser->fetch($arrayidcontact[0]);
			$salesrep					.= $outputlangs->transnoentities('CaseFollowedBy').' <b>'.$tmpuser->getFullName($outputlangs).'</b>';
			if ($tmpuser->email) 		$salesrep	.= ', '.$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($tmpuser->email);
			if ($tmpuser->office_phone)	$salesrep	.= ', '.$outputlangs->transnoentities('PhoneShort').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($tmpuser->office_phone)));
		}
		return $salesrep;
	}

	/********************************************
	*	Format notes with substitutions and right path for pictures
	*
	*	@param		Object		$object			Object shown in PDF
	*	@param		Translate	$outputlangs	Object lang for output
	*	@param		String		$notes			html string from data base
	*	@return		String						Return html string ready to print
	********************************************/
	function pdf_InfraSPlus_formatNotes($object, $outputlangs, $notes)
	{
		global $dolibarr_main_url_root;

		$substitutionarray						= pdf_getSubstitutionArray($outputlangs, null, $object);
		// More substitution keys
		$substitutionarray['__FROM_NAME__']		= $fromcompany->name;
		$substitutionarray['__FROM_EMAIL__']	= $fromcompany->email;
		complete_substitutions_array($substitutionarray, $outputlangs, $object);
		$html									= make_substitutions($notes, $substitutionarray, $outputlangs);
		// the code below came from a Dolibarr v10 native function (convertBackOfficeMediasLinksToPublicLinks()) on functions2.lib.php
		$urlwithouturlroot						= preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));	// Define $urlwithroot
		$urlwithroot							= $urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
		$html									= preg_replace('/src="[a-zA-Z0-9_\/\-\.]*(viewimage\.php\?modulepart=medias[^"]*)"/', 'src="'.$urlwithroot.'/\1"', preg_replace('#amp;#', '', $html));
		return $html;
	}

	/********************************************
	*	Get document extrafields
	*
	*	@param		Object		$object			Object shown in PDF
	*	@param		array		$exftxtcolor	array with rgb color code
	*	@param		Translate	$outputlangs	Object lang for output
	*	@return		String						Return extrafields found
	********************************************/
	function pdf_InfraSPlus_ExtraFieldsInNotes($object, $exftxtcolor, $outputlangs)
	{
		global $db, $conf;

		$efPaySpec		= isset($conf->global->INFRASPLUS_PDF_EXF_PAY_SPEC)		? $conf->global->INFRASPLUS_PDF_EXF_PAY_SPEC	: '';
		$efPaySpec		= explode(',', preg_replace('/\s+/', '', $efPaySpec));	// string without any space to array
		$efDeposit		= isset($conf->global->INFRASPLUS_PDF_EXF_DEPOSIT)		? $conf->global->INFRASPLUS_PDF_EXF_DEPOSIT		: '';
		$efDeposit		= explode(',', preg_replace('/\s+/', '', $efDeposit));	// string without any space to array
		$ef				= array_merge($efPaySpec, $efDeposit);
		$free_addr_livr	= isset($conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF)	? $conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF	: '';
		$listEF			= array();
		$extraDet		= '';
		$extrafields	= new ExtraFields($db);
		$extralabels	= $extrafields->fetch_name_optionals_label($object->table_element);
		$object->fetch_optionals();
		foreach ($extralabels as $key => $label) {
			$printable												= intval($extrafields->attributes[$object->table_element]['printable'][$key]);
			if (empty($printable))									continue;	// check extrafield atribute printable (0 = no ; 1 = always ; 2 = if not empty)
			if (in_array($key, $ef))								continue;	// check key to avoid printing extra field for special payment
			if (!empty($free_addr_livr) && $key == $free_addr_livr)	continue;	// check key to avoid printing extra field for delivery address
			$options_key											= $object->array_options['options_'.$key];
			$value													= pdf_InfraSPlus_formatNotes($object, $outputlangs, $extrafields->showOutputField($key, $options_key));
			if ($printable == 1 || (!empty($value) && $printable == 2)) {	// check if something is writting for this extrafield according to the extrafield management
				$EF			= new stdClass();
				$EF->rank	= intval($extrafields->attributes[$object->table_element]['pos'][$key]);
				$EF->label	= $outputlangs->trans($label);
				$EF->value	= $value;
				$EF->type	= $extrafields->attributes[$object->table_element]['type'][$key];
				$listEF[]	= $EF;
			}
		}
		if (!empty($listEF)) {
			uasort($listEF, function ($a, $b) { return  ($a->rank > $b->rank) ? 1 : -1; });
			foreach ($listEF as $EF) {
				$value		= '<span style = "color: rgb('.$exftxtcolor[0].', '.$exftxtcolor[1].', '.$exftxtcolor[2].')">'.$EF->value.'</span>';
				$extraDet	.= (empty($extraDet) ? '<ul><li>' : '<li>').$outputlangs->trans($EF->label).' : <b>'.$value.'</b></li>';
			}
		}
		$extraDet	.= empty($extraDet) ? '' : '</ul>';
		return $extraDet;
	}

	/********************************************
	*	Get serial Number for equipement
	*
	*	@param		Object		$object			Object shown in PDF
	*	@param		Translate	$outputlangs	Object lang for output
	*	@param		int			$i				Row number.
	*	@param		String		$typedoc		type of document asking.
	*	@return		String						Return equipement serial number
	********************************************/
	function pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, $i, $typedoc = '')
	{
		global $db;

		$idprod	= (!empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
		$space	= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$retStr	= '';
		if ($idprod || $typedoc == 'intervention')
		{
			if ($typedoc == 'expedition') {
				$sql	= 'SELECT eq.ref';
				$sql	.= ' FROM '.MAIN_DB_PREFIX.'equipement AS eq,'.MAIN_DB_PREFIX.'equipementevt AS eqv';
				$sql	.= ' WHERE eq.rowid = eqv.fk_equipement';
				$sql	.= ' AND eqv.fk_expedition = "'.$object->id.'"';
				$sql	.= ' AND eq.fk_product = "'.$idprod.'"';
			}
			elseif ($typedoc == 'facture') {
				$sql	= 'SELECT eq.ref';
				$sql	.= ' FROM '.MAIN_DB_PREFIX.'equipement AS eq';
				$sql	.= ' WHERE eq.fk_facture = "'.$object->id.'"';
				$sql	.= ' AND eq.fk_product = "'.$idprod.'"';
			}
			elseif ($typedoc == 'intervention') {
				$sql	= 'SELECT eq.ref, p.ref as refproduct';
				$sql	.= ' FROM '.MAIN_DB_PREFIX.'equipement AS eq,'.MAIN_DB_PREFIX.'equipementevt AS eqv,'.MAIN_DB_PREFIX.'product AS p';
				$sql	.= ' WHERE eq.rowid = eqv.fk_equipement';
				$sql	.= ' AND p.rowid = eq.fk_product';
				$sql	.= ' AND eqv.fk_fichinter = "'.$object->id.'"';
				$sql	.= ' ORDER BY eq.fk_product';
			}
			else return	$retStr;
			$result	= $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				if ($num>0) {
					$retStr	= $outputlangs->trans("PDFInfraSPlusSerialRef").' = '.($num > 1 ? '<br/>' : '');
					for ($i = 0; $i < $num; $i++) {
						$objp							= $db->fetch_object($result);
						if ($typedoc == 'intervention')	$retStr	.= ($num > 1 ? ($i == 0 ? $space : '<br/>'.$space) : '&nbsp;').$outputlangs->trans("PDFInfraSPlusFicheInterSerialNum", $objp->refproduct, $objp->ref);
						else							$retStr	.= ($num > 1 ? ($i == 0 ? $space : '<br/>'.$space) : '&nbsp;').$objp->ref;
					}
				}
			}
			else	$retStr	= '';
		}
		return	$retStr;
	}

	/********************************************
	*	Return line product ref for Intervention card
	*
	*	@param	Object	$object		Object
	*	@param	int		$i			Current line number
	* 	@return	array
	 ********************************************/
	function pdf_infrasplus_getlinefichinter($object, $i)
	{
		global $db;

		$prodfichinter	= array();
		$sql	= 'SELECT fid.total_ht, fid.subprice, fid.fk_product, fid.tva_tx, fid.localtax1_tx, fid.localtax1_type, fid.localtax2_tx, fid.localtax2_type, fid.qty,';
		$sql	.= ' fid.remise_percent, fid.remise, fid.fk_remise_except, fid.price, fid.total_tva, fid.total_localtax1, fid.total_localtax2, fid.total_ttc,';
		$sql	.= ' fid.product_type, fid.info_bits, fid.buy_price_ht, fid.fk_product_fournisseur_price, p.ref, p.label';
		$sql	.= ' FROM '.MAIN_DB_PREFIX.'fichinterdet AS fid';
		$sql	.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product AS p ON fid.fk_product = p.rowid';
		$sql	.= ' WHERE fid.fk_fichinter = '.$object->id.' AND fid.rowid = '.$object->lines[$i]->id;
		$resql	= $db->query($sql);
		if ($resql) {
			$num	= $db->num_rows($resql);
			$j		= 0;
			while ($j < $num) {
				$objp											= $db->fetch_object($resql);
				$prodfichinter['total_ht']						= $objp->total_ht;
				$prodfichinter['subprice']						= $objp->subprice;
				$prodfichinter['fk_product']					= $objp->fk_product;
				$prodfichinter['tva_tx']						= $objp->tva_tx;
				$prodfichinter['localtax1_tx']					= $objp->localtax1_tx;
				$prodfichinter['localtax1_type']				= $objp->localtax1_type;
				$prodfichinter['localtax2_tx']					= $objp->localtax2_tx;
				$prodfichinter['localtax2_type']				= $objp->localtax2_type;
				$prodfichinter['qty']							= $objp->qty;
				$prodfichinter['remise_percent']				= $objp->remise_percent;
				$prodfichinter['remise']						= $objp->remise;
				$prodfichinter['fk_remise_except']				= $objp->fk_remise_except;
				$prodfichinter['price']							= $objp->price;
				$prodfichinter['total_tva']						= $objp->total_tva;
				$prodfichinter['total_localtax1']				= $objp->total_localtax1;
				$prodfichinter['total_localtax2']				= $objp->total_localtax2;
				$prodfichinter['total_ttc']						= $objp->total_ttc;
				$prodfichinter['product_type']					= $objp->product_type;
				$prodfichinter['info_bits']						= $objp->info_bits;
				$prodfichinter['buy_price_ht']					= $objp->buy_price_ht;
				$prodfichinter['fk_product_fournisseur_price']	= $objp->fk_product_fournisseur_price;
				$prodfichinter['ref']							= $objp->ref;
				$prodfichinter['label']							= $objp->label;
				$j++;
			}
			$db->free($resql);
		}
		return $prodfichinter;
	}

	/********************************************
	*	Return dimensions to use for images onto PDF,
	*	checking that width and height are not higher than maximum (20x32 by default).
	*
	*	@param	string		$realpath			Full path to photo file to use
	*	@param	int			$maxwidth			Maximum width to use
	*	@param	int			$maxheight			Maximum height to use
	*	@param	boolean		$wFirst				Adjust to the withd first
	*	@return	array							Height and width to use to output image (in pdf user unit, so mm)
	 ********************************************/
	function pdf_InfraSPlus_getSizeForImage($realpath, $maxwidth, $maxheight, $wFirst = 0)
	{
		global $conf;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
		$imglinesize	= dol_getImageSize($realpath);
		if ($imglinesize['height'] && $imglinesize['width']) {
			if (empty($wFirst)) {
				$width	= (int) round($maxheight * $imglinesize['width'] / $imglinesize['height']);	// I try to use maxheight
				if ($width > $maxwidth) {	// Pb with maxwidth, so i use maxheight
					$width	= $maxwidth;
					$height	= (int) round($maxwidth * $imglinesize['height'] / $imglinesize['width']);
				}
				else	$height	= $maxheight;	// No pb with maxwidth
			}
			else {
				$height	= (int) round($maxwidth * $imglinesize['height'] / $imglinesize['width']);	// I try to use maxwidth
				if ($height > $maxheight) {	// Pb with maxheight, so i use maxwidth
					$height	= $maxheight;
					$width	= (int) round($maxheight * $imglinesize['width'] / $imglinesize['height']);
				}
				else	$width	= $maxwidth;	// No pb with maxheight
			}
		}
		return array('width' => $width, 'height' => $height);
	}

	/********************************************
	*  Return line url
	*
	*  @param  Object		$object			Object shown in PDF
	*  @param  int			$i				Current line number (0 = first line, 1 = second line, ...)
	*  @return string						url found in product/service data
	 ********************************************/
	function pdf_InfraSPlus_getlineurl(&$object, $i)
	{
		global $db;

		$idprod		= (!empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
		$prodser	= new Product($db);
		$prodser->fetch($idprod);

		return $prodser->url;
	}

	/********************************************
	*	Return line product ref
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param  Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	* 	@return	string
	 ********************************************/
	function pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null)
	{
		global $db, $conf, $hookmanager;

		$reshook		= 0;
		$result			= '';
		$refprod		= $prodfichinter										? $prodfichinter['ref']								: $object->lines[$i]->product_ref;
		$bold_num_col	= isset($conf->global->INFRASPLUS_PDF_BOLD_REF)			? $conf->global->INFRASPLUS_PDF_BOLD_REF			: 0;
		$with_gencode	= isset($conf->global->INFRASPLUS_PDF_REF_WITH_GENCODE)	? $conf->global->INFRASPLUS_PDF_REF_WITH_GENCODE	: 0;
		if (!empty($with_gencode) && $i > -1) {
			$idprod			= !empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false;
			$prodser		= new Product($db);
			if ($idprod)	$prodser->fetch($idprod);
		}
		else	$prodser	= $object;
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineref', $parameters,$object, $action);    // Note that $action and $object may have been modified by some hooks
			$result											.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if (!empty($bold_num_col))						$refprod	= '<b>'.$refprod.'</b>';
			if (empty($hidedetails) || $hidedetails > 1)	$result		.= dol_htmlentitiesbr($refprod.($prodser->barcode ? '<br/>'.$prodser->barcode : ''));
		}
		return $result;
	}

	/************************************************
	*	Return line ref_supplier
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param  Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0 = no, 1 = yes, 2 = just special lines)
	*	@return	string
	************************************************/
	function pdf_InfraSPlus_getlineref_supplier($object, $i, $outputlangs, $hidedetails = 0)
	{
		global $db, $conf, $hookmanager;

		$prodRefSupp	= isset($conf->global->PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES) ? $conf->global->PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES : 1;
		$idprod			= !empty($object->lines[$i]->fk_product)	? $object->lines[$i]->fk_product	: false;
		$ref_supplier	= (!empty($object->lines[$i]->ref_supplier) ? $object->lines[$i]->ref_supplier : (!empty($object->lines[$i]->ref_fourn) ? $object->lines[$i]->ref_fourn : ''));
		if (empty($ref_supplier)) {
			$ref	= $object->lines[$i]->ref;
			$sql	= 'SELECT pfp.ref_fourn ';
			$sql	.= 'FROM '.MAIN_DB_PREFIX.'product AS p ';
			$sql	.= 'LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price AS pfp ON p.rowid = pfp.fk_product ';
			$sql	.= 'WHERE p.ref = "'.$ref.'" AND pfp.fk_soc = "'.$object->thirdparty->id.'"';
			$resql	= $db->query($sql);
			if ($resql) {
				$obj								= $db->fetch_object($resql);
				if ($obj)							$ref_supplier	= $obj->ref_fourn;
			}
			$db->free($resql);
		}
		$prodser					= new ProductFournisseur($db);
		if ($idprod)				$prodser->fetch($idprod);
		if ($prodRefSupp == 1)		$ref_prodserv	= $ref_supplier;
		elseif ($prodRefSupp == 2)	$ref_prodserv	= (!empty($ref_supplier) ? $ref_supplier.' ' : '').($prodser->ref ? '('.$prodser->ref.')' : '');
		else {	// Common case
			$ref_prodserv		= $prodser->ref; // Show local ref
			if ($ref_supplier)	$ref_prodserv	.= ($prodser->ref ? ' ' : '').'('.$ref_supplier.')';
		}
		$reshook	= 0;
		$result		= '';
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineref_supplier', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			$result											.= $hookmanager->resPrint;
		}
		if (empty($reshook))	$result	.= dol_htmlentitiesbr($ref_prodserv);
		return $result;
	}

	/********************************************
	*	Output line product bar code
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param	Object		$object			Object
	*	@param	int			$i				Current line number or -1 if we want to print the object bar code instead of line object bar code
	*	@param  Array		$bodytxtcolor	current text color
	*	@param	float		$posx			x position
	*	@param	float		$posy			y position
	*	@param	float		$width			bar code width
	*	@param	float		$height			bar code height (for 2D code such as Qr Code width = height and we change the x position to be on the middle of the width)
	* 	@return	Boolean						1 -> Ok ; <1 -> Ko
	 ********************************************/
	 function pdf_InfraSPlus_writelineBC(&$pdf, $object, $i, $bodytxtcolor, $posx, $posy, $width, $height)
	 {
		global $db;

		$pos	= $i > -2 ? 'C' : '';
		$decal	= 0;
		if ($i > -1) {
			$idprod			= !empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false;
			$prodser		= new Product($db);
			if ($idprod)	$prodser->fetch($idprod);
			$pos			= '';
			$decal			= ($width - $height) / 2;
		}
		else	$prodser	= $object;
		if ($prodser->barcode) {
			// Complete object if not complete
			if (empty($prodser->barcode_type_code) || empty($prodser->barcode_type_coder)) {
				$result	= $prodser->fetch_barcode();
				if ($result < 1)	$BC	= '-- ErrorFetchBarcode -- '.$result;	//Check if fetch_barcode() failed
			}
			if (! $BC) {
				if ($prodser->barcode_type <= 6) {
					if ($prodser->barcode_type == 2 && strlen($prodser->barcode) > 12) {
						$ean	= substr($prodser->barcode, 0, 12);
						$eansum	= barcode_gen_ean_sum($ean);
						if (substr($prodser->barcode, -1) != $eansum) {
							$txtErr	= '<b><FONT size="7">'.$ean.'<FONT color="red">'.substr($prodser->barcode, -1).'</FONT><FONT color="green">('.$eansum.')</FONT></FONT></b>';
							$pdf->writeHTMLCell($width, $height, $posx, $posy, $txtErr, 0, 0, false, true, 'C');
							return 1;
						}
					}
					switch ($width) {
						case 25:
							$xres	= 0.2;
							break;
						case 35:
							$xres	= 0.3;
							break;
						case 45:
							$xres	= 0.4;
							break;
						default:
							$xres	= 0.2;
					}
					$styleBC	= array('position'		=> $pos,
										'align'			=> '',
										'stretch'		=> false,
										'fitwidth'		=> true,
										'cellfitalign'	=> 'C',
										'border'		=> false,
										'hpadding'		=> '0',
										'vpadding'		=> '0',
										'fgcolor'		=> array($bodytxtcolor[0], $bodytxtcolor[1], $bodytxtcolor[2]),
										'bgcolor'		=> false,
										'text'			=> true,
										'label'			=> $prodser->barcode,
										'font'			=> $pdf->getFontFamily(),
										'fontsize'		=> 6,
										'stretchtext'	=> 4
										);
					$pdf->write1DBarcode($prodser->barcode, $prodser->barcode_type_code, $posx, $posy, $width, $height, $xres, $styleBC, 'B');
					return 1;
				}
				if ($prodser->barcode_type > 6) {
					$posx			+= $decal;
					$styleBC		= array('position'		=> $pos,
											'border'		=> false,
											'hpadding'		=> '0',
											'vpadding'		=> '0',
											'fgcolor'		=> array($bodytxtcolor[0], $bodytxtcolor[1], $bodytxtcolor[2]),
											'bgcolor'		=> false,
											'module_width'	=> 1,
											'module_height'	=> 1
											);
					$pdf->write2DBarcode($prodser->barcode, $prodser->barcode_type_code, $posx, $posy, $height, $height, $styleBC, 'B');
					return 2;
				}
			}
			else $pdf->writeHTMLCell(0, 0, $posx, $posy, $BC, 0, 1);
		}
		return 0;
	 }

	/********************************************
	*	Get lines extrafields
	*
	*	@param		Object		$line				Line shown in PDF
	*	@param		Object		$extrafieldsline	Extra field line in document
	*	@param		Object		$extralabelsline	Extra label line in document
	*	@param		array		$exfltxtcolor		array with rgb color code
	*	@return		String							Return extrafields found
	********************************************/
	function pdf_InfraSPlus_ExtraFieldsLines($line, $extrafieldsline, $extralabelsline, $exfltxtcolor)
	{
		global $db, $conf;

		$extraDet		= '';
		$line->fetch_optionals($line->rowid);
		foreach ($extralabelsline as $key => $label) {
			$printable										= intval($extrafieldsline->attributes[$line->table_element]['printable'][$key]);
			if (empty($printable))							continue;	// check extrafield atribute printable (0 = no ; 1 or 3 = always ; 4 = if not empty)
			$options_key									= $line->array_options['options_'.$key];
			$value											= $extrafieldsline->showOutputField($key, $options_key);
			if (preg_match('#<img.*src=.*\/>#', $value))	$value	= preg_replace('#src=\"\/viewimage.*modulepart=#', 'src="'.DOL_DATA_ROOT.'/', preg_replace('#&amp;entity=[0-9]*&amp;file=#', '/', $value));
			if (in_array($printable, array(1, 3)) || (!empty($value) && $printable == 4)) {	// check if something is writting for this extrafield according to the extrafield management
				$value		= '<span style = "color: rgb('.$exfltxtcolor[0].', '.$exfltxtcolor[1].', '.$exfltxtcolor[2].')">'.$value.'</span>';
				$extraDet	.= (empty($extraDet) ? '' : '<br/>').$label.' : <b>'.$value.'</b>';
			}
		}
		return $extraDet;
	}

	/********************************************
	*  Return line weight volume dimensions and Customs code into array
	*
	*  @param  Object		$object			Object shown in PDF
	*  @param  int			$i				Current line number (0 = first line, 1 = second line, ...)
	*  @param  Translate	$outputlangs	Object langs for output
	*  @return array						Array with elements found
	 ********************************************/
	function pdf_InfraSPlus_getlinewvdcc(&$object, $i, $outputlangs)
	{
		global $db, $conf, $langs;

		$outputlangs->load('other');

		if ($i === 'P') {
			$idprod	= $object->id;
			$type	= $object->fk_product_type;
		}
		else {
			$idprod	= (!empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
			$type	= $object->lines[$i]->product_type;
		}
		$prodser	= new Product($db);
		$dimtxt		= '';
		$weighttxt	= '';
		$voltxt		= '';
		$surftxt	= '';
		$ccodetxt	= '';
		$countrytxt	= '';
		if ($idprod && $type == 0) {
			$prodser->fetch($idprod);
			if ($prodser->length || $prodser->width || $prodser->height) {
				$txtDim	= '';
				$txtDim	.= ($prodser->length ? $outputlangs->trans('Length') : '');
				$txtDim	.= ($prodser->length && ($prodser->width || $prodser->height) ? ' x ' : '');
				$txtDim	.= ($prodser->width ? $outputlangs->trans('Width') : '');
				$txtDim	.= (($prodser->width && $prodser->height) ? ' x ' : '');
				$txtDim	.= ($prodser->height ? $outputlangs->trans('Height') : '');
				$dimtxt	= $txtDim.' : ';
				$txtDim	= ($prodser->length ? $prodser->length : '');
				$txtDim	.= ($prodser->length && ($prodser->width || $prodser->height) ? ' x ' : '');
				$txtDim	.= ($prodser->width ? $prodser->width : '');
				$txtDim	.= (($prodser->width && $prodser->height) ? ' x ' : '');
				$txtDim	.= ($prodser->height ? $prodser->height : '');
				$txtDim	.= ' '.(version_compare(DOL_VERSION, '10.0.0', '<') ? measuring_units_string($prodser->length_units, 'size') : measuringUnitString(7, 'size', $prodser->length_units));
				$dimtxt	.= $txtDim;
			}
			if (version_compare(DOL_VERSION, '10.0.0', '<')) {
				if ($prodser->weight)		$weighttxt	= $outputlangs->trans('Weight').' : '.$prodser->weight.' '.measuring_units_string($prodser->weight_units, 'weight');
				if ($prodser->volume)		$voltxt		= $outputlangs->trans('Volume').' : '.$prodser->volume.' '.measuring_units_string($prodser->volume_units, 'volume');
				if ($prodser->surface)		$surftxt	= $outputlangs->trans('Surface').' : '.$prodser->surface.' '.measuring_units_string($prodser->surface_units, 'surface');
			}
			else {
				if ($prodser->weight)		$weighttxt	= $outputlangs->trans('Weight').' : '.$prodser->weight.' '.measuringUnitString(2, 'weight', $prodser->weight_units);
				if ($prodser->volume)		$voltxt		= $outputlangs->trans('Volume').' : '.$prodser->volume.' '.measuringUnitString(19, 'volume', $prodser->volume_units);
				if ($prodser->surface)		$surftxt	= $outputlangs->trans('Surface').' : '.$prodser->surface.' '.measuringUnitString(13, 'surface', $prodser->surface_units);
			}
			if ($prodser->customcode)	$ccodetxt	= $outputlangs->trans('CustomCode').' : '.$prodser->customcode;
			if ($prodser->country_id)	$countrytxt	= $outputlangs->trans('CountryOrigin').' : '.getCountry($prodser->country_id, 0, $db);
		}
		$linewvdcc	= $dimtxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($weighttxt)	? '<br/>' : '').$weighttxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($voltxt)		? '<br/>' : '').$voltxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($surftxt)		? '<br/>' : '').$surftxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($ccodetxt)		? '<br/>' : '').$ccodetxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($countrytxt)	? '<br/>' : '').$countrytxt;
		return $linewvdcc;
	}

	/********************************************
	*	Output line description into PDF
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param	Object		$object			Object shown in PDF
	*	@param	int			$i				Current line number
	*	@param  Translate	$outputlangs	Object lang for output
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	* 	@param	array		$LineStyle		params for table line style
	*	@param  int			$w				Width
	*	@param  int			$h				Height
	*	@param  int			$posx			Pos x
	*	@param  int			$posy			Pos y
	*	@param  int			$hideref       	Hide reference
	*	@param  int			$hidedesc		Hide description
	*	@param	int			$issupplierline	Is it a line for a supplier object ?
	*	@param	string		$extraDet		HTML extra fields content
	*	@param	array		$prodfichinter	Product information when object is an intervention card
	*	@param  int			$desc_full_line	description width full line
	*	@return	string
	 ********************************************/
	function pdf_InfraSPlus_writelinedesc(&$pdf, $object, $i, $outputlangs, $formatpage, $LineStyle, $w, $h, $posx, $posy, $hideref = 0, $hidedesc = 0, $issupplierline = 0, $extraDet = '', $prodfichinter = null, $desc_full_line = 0, $isRecap = 0)
	{
		global $db, $conf, $hookmanager;

		$picture_in_ref										= isset($conf->global->INFRASPLUS_PDF_PICTURE_IN_REF)		? $conf->global->INFRASPLUS_PDF_PICTURE_IN_REF			: 0;
		$cleanFont											= isset($conf->global->INFRASPLUS_PDF_DESC_CLEAN_FONT)		? $conf->global->INFRASPLUS_PDF_DESC_CLEAN_FONT			: 0;
		$descFullLineWitdh									= isset($conf->global->INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH)	? $conf->global->INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH	: 0;
		$descFullLineColor									= isset($conf->global->INFRASPLUS_PDF_DESC_FULL_LINE_COLOR)	? $conf->global->INFRASPLUS_PDF_DESC_FULL_LINE_COLOR	: 0;
		if (!empty($descFullLineColor))	$LineStyle['color']	= explode(',', $descFullLineColor);
		$reshook											= 0;
		$result												= '';
		$isSubTotalLine										= infraspackplus_isSubTotalLine($object->lines[$i], $object->element, 'modSubtotal');
		$isATMLine											= $conf->global->MAIN_MODULE_SUBTOTAL && $isSubTotalLine ? true : false;
		$isSubTitle											= $isATMLine && $object->lines[$i]->qty  < 10 ? 1 : 0;	// Sous-titre ATM
		$isSubTotal											= $isATMLine && $object->lines[$i]->qty  > 90 ? infraspackplus_get_mod_number('modSubtotal') : 0;	// Sous-total ATM
		$isSubFreeT											= $isATMLine && $object->lines[$i]->qty  == 50 ? 1 : 0;	// Ligne libre ATM
		$isOuvrage											= pdf_InfraSPlus_escapeOuvrage ($object, $i, 2);	// Ouvrage Inovea
		if ($object->lines[$i]->product_type == 9 && $conf->global->MAIN_MODULE_MILESTONE) {	// ligne de sous-titre ou de sous-total
			$bodytxtcolor		= isset($conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR)	? $conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR		: 0;
			$bodytxtcolor		= explode(',', $bodytxtcolor);
			$bodytxtsubticolor	= isset($conf->global->INFRASPLUS_PDF_TEXT_SUBTI_COLOR)	? $conf->global->INFRASPLUS_PDF_TEXT_SUBTI_COLOR	: 0;
			$bodytxtsubticolor	= explode(',', $bodytxtsubticolor);
			$bodybgsubticolor	= isset($conf->global->MILESTONE_BACKGROUND_COLOR)		? $conf->global->MILESTONE_BACKGROUND_COLOR			: 'e6e6e6';
			$bodybgsubticolor	= colorStringToArray($bodybgsubticolor);
			$pdf->SetTextColor($bodytxtsubticolor[0], $bodytxtsubticolor[1], $bodytxtsubticolor[2]);	// Sous-titre Milestone/Jalon
			$frm				= implode(',', $bodybgsubticolor) == '255, 255, 255' ? '' : 'F';
			$frmstyle			= array('width'=>'0.2', 'dash'=>'0', 'cap'=>'butt', 'color'=>'255, 255, 255');
			$pdf->RoundedRect($formatpage['mgauche'], $posy, $formatpage['largeur'] - $formatpage['mdroite'] - $formatpage['mgauche'], $h--, 0.001, '1111', $frm, $frmstyle, $bodybgsubticolor);
		}
		if (is_object($hookmanager) && !$isATMLine) {
			$special_code									= empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('pdf'=>$pdf, 'i'=>$i, 'outputlangs'=>$outputlangs, 'w'=>$w, 'h'=>$h, 'posx'=>$posx, 'posy'=>$posy, 'hideref'=>$hideref, 'hidedesc'=>$hidedesc, 'issupplierline'=>$issupplierline, 'special_code'=>$special_code);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_writelinedesc', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint))				$result	.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			$fulllabel				= pdf_InfraSPlus_getlinedesc($object, $i, $outputlangs, $hideref, $hidedesc, $issupplierline, $extraDet, $prodfichinter, $isSubTotal, $isSubTitle, $isSubFreeT, $isOuvrage);
			$labelproductservice	= $fulllabel['main'];
			$labelproductservice	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $labelproductservice);	// enable the use of an image in description
			// Fix bug of some HTML editors that replace links <img src="http://localhostgit/viewimage.php?modulepart=medias&file=image/efd.png" into <img src="http://localhostgit/viewimage.php?modulepart=medias&amp;file=image/efd.png"
			// We make the reverse, so PDF generation has the real URL.
			$labelproductservice	= preg_replace('/(<img[^>]*src=")([^"]*)(&amp;)([^"]*")/', '\1\2&\4', $labelproductservice, -1, $nbrep);
			if (!empty($cleanFont))	$labelproductservice	= dol_string_neverthesehtmltags($labelproductservice, $disallowed_tags = array('span'));
			// Description
			// Open-DSI -- NEW full line description -- Begin
			if (!empty($desc_full_line) && !$isSubTotal && !$isSubTitle && $isOuvrage < 2) {
				$xPos					= $picture_in_ref ? $posx : $formatpage['mgauche'];
				$wd						= $formatpage['largeur'] - $xPos - $formatpage['mdroite'];
				$decal                  = $wd * ((1 - ($descFullLineWitdh / 100)) / 2);
				$labelproductservice	= ($isSubTotalLine && $object->lines[$i]->qty == 50 ? '<br />' : '').$labelproductservice;	// Free text of SubTotal
				if (dol_textishtml($labelproductservice)) {
					$retchararray	= array('<br>', '<br/>', '<br />', '</p>');
					$pos			= false;
					$isbr			= false;
					$retcharlen		= 0;
					foreach ($retchararray as $retchar) {	// Get first position of a html return
						$posfound	= strpos($labelproductservice, $retchar);
						if ($pos === false || ($posfound !== false && $posfound < $pos)) {
							$pos		= $posfound;
							$isbr		= $retchar != '</p>';
							$retcharlen	= strlen($retchar);
						}
					}
					if ($pos !== false) {
						if ($isbr) {	// Fix html to <br> <p> </p> if it's the case <p> <br> </p>
							$posfound	= strpos($labelproductservice, '<p>');
							if ($posfound !== false && $posfound < $pos) {
								$labelproductservice	= substr_replace($labelproductservice, '<p>', $pos + $retcharlen, 0);
								$labelproductservice	= substr_replace($labelproductservice, '', $posfound, strlen('<p>'));
								$pos					-= strlen('<p>');
							}
						}
						// Fix the real positions
						$pos		= $isbr ? $pos					: $pos + $retcharlen;
						$startdesc	= $isbr ? $pos + $retcharlen	: $pos;
					}
				}
				else {
					$pos		= strpos($labelproductservice, "\n");
					$startdesc	= $pos + strlen("\n");
				}
				if ($pos !== false) {
					$heightline										= $pdf->getStringHeight($w - $fulllabel['decal'], $outputlangs->convToOutputCharset(substr($labelproductservice, 0, $pos)));
					$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy, $outputlangs->convToOutputCharset(substr($labelproductservice, 0, $pos)), 0, 1, false, true, 'J', true);
					$posy											= $picture_in_ref ? $posy + $heightline : $pdf->GetY();
					if (!$hidedesc && !empty($descFullLineWitdh))	$pdf->line($xPos + $decal, $posy + 1, $formatpage['largeur'] - $formatpage['mdroite'] - $decal, $posy + 1, $LineStyle);
					$pdf->writeHTMLCell($wd - $fulllabel['decal'], $h, $xPos + $fulllabel['decal'], $posy + 2, $outputlangs->convToOutputCharset(substr($labelproductservice, $startdesc)), 0, 1, false, true, 'J', true);
				}
				else	$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'J', true);
			}
			elseif ($isSubTotal || $isSubTitle) {	// ligne de sous-titre ou de sous-total ATM
				$bodytxtcolor		= isset($conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR)	? $conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR		: 0;
				$bodytxtcolor		= explode(',', $bodytxtcolor);
				$bodysubticolor		= isset($conf->global->INFRASPLUS_PDF_BODY_SUBTI_COLOR)	? $conf->global->INFRASPLUS_PDF_BODY_SUBTI_COLOR	: '220, 220, 220';
				$bodysubticolor		= explode(',', $bodysubticolor);
				$bodytxtsubticolor	= isset($conf->global->INFRASPLUS_PDF_TEXT_SUBTI_COLOR)	? $conf->global->INFRASPLUS_PDF_TEXT_SUBTI_COLOR	: 0;
				$bodytxtsubticolor	= explode(',', $bodytxtsubticolor);
				$bodytxtsubtocolor	= isset($conf->global->INFRASPLUS_PDF_TEXT_SUBTO_COLOR)	? $conf->global->INFRASPLUS_PDF_TEXT_SUBTO_COLOR	: 0;
				$bodytxtsubtocolor	= explode(',', $bodytxtsubtocolor);
				$h					= $pdf->getStringHeight($w, $labelproductservice);
				$frmstyle			= array('width'=>'0.2', 'dash'=>'0', 'cap'=>'butt', 'color'=>'255, 255, 255');
				if($object->lines[$i]->info_bits > 0) {
					$pdf->addPage();
					$posy	= $pdf->GetY();
				}
				if ($isSubTitle) {	// Sous-titre ATM
					$style								= isset($conf->global->SUBTOTAL_TITLE_STYLE) ? $conf->global->SUBTOTAL_TITLE_STYLE : ($object->lines[$i]->qty == 1 ? 'BU' : 'BUI');
					$bodybgsubcolor						= colorStringToArray($bodysubticolor);
					$frm								= implode(',', $bodybgsubcolor) == '255, 255, 255' ? '' : 'F';
					$pdf->SetTextColor($bodytxtsubticolor[0], $bodytxtsubticolor[1], $bodytxtsubticolor[2]);
					$pdf->SetFont('', $style);
					$tmpAlpha							= ($object->lines[$i]->qty - 1) * 0.25;
					$pdf->SetAlpha(1 - ($tmpAlpha >= 0 ? $tmpAlpha : 1));
					if ($frm == 'F')					$pdf->RoundedRect($formatpage['mgauche'], $posy, $formatpage['largeur'] - $formatpage['mdroite'] - $formatpage['mgauche'], $h, 1, '1111', $frm, $frmstyle, $bodybgsubcolor);
					$pdf->SetAlpha(1);
					$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'L', true);
					$pdf->SetFont('', '', pdf_getPDFFontSize($outputlangs) - 1);   // On repositionne la police par defaut
					if (!empty($fulllabel['subdesc']))	$pdf->writeHTMLCell($w, $h, $posx, $posy + $h, $outputlangs->convToOutputCharset($fulllabel['subdesc']), 0, 1, false, true, 'L', true);
				}
				elseif ($isSubTotal) {	// Sous-total ATM
					$hideBg								= isset($conf->global->INFRASPLUS_PDF_HIDE_BODY_SUBTO)			? $conf->global->INFRASPLUS_PDF_HIDE_BODY_SUBTO			: 0;
					$bgSubToColorSubTi					= isset($conf->global->INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI)	? $conf->global->INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI	: 0;
					$style								= isset($conf->global->SUBTOTAL_SUBTOTAL_STYLE)					? $conf->global->SUBTOTAL_SUBTOTAL_STYLE				: 'B';
					$txt								= $outputlangs->convToOutputCharset($labelproductservice);
					$txt								= $isRecap ? substr($txt, 0, strlen($txt) - 13) : $txt;
					$pdf->SetTextColor($bodytxtsubtocolor[0], $bodytxtsubtocolor[1], $bodytxtsubtocolor[2]);
					$pdf->SetFont('', $style);
					if (empty($hideBg)) {
						$bodybgsubcolor		= colorStringToArray((!empty($bgSubToColorSubTi) ? $bodysubticolor : ($object->lines[$i]->qty == 99 ? '220, 220, 220' : ($object->lines[$i]->qty == 98 ? '230, 230, 230' : '240, 240, 240'))));
						$frm				= implode(',', $bodybgsubcolor) == '255, 255, 255' ? '' : 'F';
						$tmpAlpha			= (100 - $object->lines[$i]->qty - 1) * 0.25;
						$pdf->SetAlpha(!empty($bgSubToColorSubTi) ? 1 - ($tmpAlpha >= 0 ? $tmpAlpha : 1) : 1);
						if ($frm == 'F')	$pdf->RoundedRect($formatpage['mgauche'], $posy, $formatpage['largeur'] - $formatpage['mdroite'] - $formatpage['mgauche'], $h, 1, '1111', $frm, $frmstyle, $bodybgsubcolor);
						$pdf->SetAlpha(1);
					}
					$pdf->writeHTMLCell($w, $h, $posx, $posy, $txt, 0, 1, false, true, ($isRecap ? 'L' : 'R'), true);
					$pdf->SetFont('', '', pdf_getPDFFontSize($outputlangs) - 1);   // On repositionne la police par defaut
				}
			}
			elseif ($isOuvrage > 1) {	// ligne d'ouvrage Inovea
				$bodyouvcolor						= isset($conf->global->INFRASPLUS_PDF_BODY_OUV_COLOR)	? $conf->global->INFRASPLUS_PDF_BODY_OUV_COLOR	: '220, 220, 220';
				$bodyouvcolor						= explode(',', $bodyouvcolor);
				$txtouvcolor						= isset($conf->global->INFRASPLUS_PDF_TEXT_OUV_COLOR)	? $conf->global->INFRASPLUS_PDF_TEXT_OUV_COLOR	: 0;
				$txtouvcolor						= explode(',', $txtouvcolor);
				$txtouvstyle						= isset($conf->global->INFRASPLUS_PDF_TEXT_OUV_STYLE)	? $conf->global->INFRASPLUS_PDF_TEXT_OUV_STYLE	: 'B';
				$frmstyle							= array('width'=>'0.2', 'dash'=>'0', 'cap'=>'butt', 'color'=>'255, 255, 255');
				$bodyouvcolor						= colorStringToArray($bodyouvcolor);
				$frm								= implode(',', $bodyouvcolor) == '255, 255, 255' ? '' : 'F';
				$pdf->SetTextColor($txtouvcolor[0], $txtouvcolor[1], $txtouvcolor[2]);
				$pdf->SetFont('', $txtouvstyle);
				if ($frm == 'F')					$pdf->RoundedRect($formatpage['mgauche'], $posy, $formatpage['largeur'] - $formatpage['mdroite'] - $formatpage['mgauche'], $h, 1, '1111', $frm, $frmstyle, $bodyouvcolor);
				$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'L', true);
				$pdf->SetFont('', '', pdf_getPDFFontSize($outputlangs) - 1);   // On repositionne la police par defaut
				if (!empty($fulllabel['subdesc']))	$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy + $h, $outputlangs->convToOutputCharset($fulllabel['subdesc']), 0, 1, false, true, 'L', true);
			}
			else	$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'J', true);
			$result	.= $labelproductservice;
		}
		if ($object->lines[$i]->product_type == 9)	$pdf->SetTextColor($bodytxtcolor[0], $bodytxtcolor[1], $bodytxtcolor[2]); // retour à la normal
		return $result;
	}

	/********************************************
	*	Return line description translated in outputlangs and encoded into htmlentities and with <br>
	*
	*	@param  Object		$object			Object shown in PDF
	*	@param  int			$i				Current line number (0 = first line, 1 = second line, ...)
	*	@param  Translate	$outputlangs	Object langs for output
	*	@param  int			$hideref		Hide reference
	*	@param  int			$hidedesc		Hide description
	*	@param  int			$issupplierline	Is it a line for a supplier object ?
	*	@param	string		$extraDet		HTML extra fields content
	*	@param	array		$prodfichinter	Product information when object is an intervention card
	*	@param	int			$isSubTotal		external module number if line is title or subtotal from external module like subTotal
	*	@return string						String with line
	 ********************************************/
	function pdf_InfraSPlus_getlinedesc(&$object, $i, $outputlangs, $hideref = 0, $hidedesc = 0, $issupplierline = 0, $extraDet = '', $prodfichinter = null, $isSubTotal = 0, $isSubTitle = 0, $isSubFreeT = 0, $isOuvrage = 0)
	{
		global $db, $conf, $langs;

		$idprod			= $prodfichinter ? $prodfichinter['fk_product'] : (!empty($object->lines[$i]->fk_product)	? $object->lines[$i]->fk_product	: false);
		$label			= $prodfichinter ? $prodfichinter['label'] 		: (!empty($object->lines[$i]->label)		? $object->lines[$i]->label			: (!empty($object->lines[$i]->product_label) ? $object->lines[$i]->product_label : ''));
		$desc			= (!empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : ''));
		$note			= (!empty($object->lines[$i]->note) ? $object->lines[$i]->note : '');
		$dbatch			= (!empty($object->lines[$i]->detail_batch) ? $object->lines[$i]->detail_batch : false);
		$isMultilangs	= isset($conf->global->MAIN_MULTILANGS)								? $conf->global->MAIN_MULTILANGS							: 0;
		$forceTranslate	= isset($conf->global->MAIN_MULTILANG_TRANSLATE_EVEN_IF_MODIFIED)	? $conf->global->MAIN_MULTILANG_TRANSLATE_EVEN_IF_MODIFIED	: 0;
		$hidelabel		= isset($conf->global->INFRASPLUS_PDF_HIDE_LABEL)					? $conf->global->INFRASPLUS_PDF_HIDE_LABEL					: 0;
		$labelbold		= isset($conf->global->INFRASPLUS_PDF_LABEL_BOLD)					? $conf->global->INFRASPLUS_PDF_LABEL_BOLD					: 0;
		$subref			= isset($conf->global->SHOW_SUBPRODUCT_REF_IN_PDF)					? $conf->global->SHOW_SUBPRODUCT_REF_IN_PDF					: 0;
		$extraDetPos2	= isset($conf->global->INFRASPLUS_PDF_EXTRADET_SECOND)				? $conf->global->INFRASPLUS_PDF_EXTRADET_SECOND				: 0;
		$depositDate	= isset($conf->global->INVOICE_ADD_DEPOSIT_DATE)					? $conf->global->INVOICE_ADD_DEPOSIT_DATE					: 0;
		$descFirst		= isset($conf->global->MAIN_DOCUMENTS_DESCRIPTION_FIRST)			? $conf->global->MAIN_DOCUMENTS_DESCRIPTION_FIRST			: 0;
		$hidelblvariant	= isset($conf->global->HIDE_LABEL_VARIANT_PDF)						? $conf->global->HIDE_LABEL_VARIANT_PDF						: 0;
		$prodAddType	= isset($conf->global->PRODUCT_ADD_TYPE_IN_DOCUMENTS)				? $conf->global->PRODUCT_ADD_TYPE_IN_DOCUMENTS				: 0;
		$prodRefSupp	= isset($conf->global->PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES)		? $conf->global->PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES		: 1;
		$HTMLinDesc		= isset($conf->global->PDF_BOLD_PRODUCT_REF_AND_PERIOD)				? $conf->global->PDF_BOLD_PRODUCT_REF_AND_PERIOD			: (isset($conf->global->ADD_HTML_FORMATING_INTO_DESC_DOC) ? $conf->global->ADD_HTML_FORMATING_INTO_DESC_DOC : 0);
		$CatInDesc		= isset($conf->global->CATEGORY_ADD_DESC_INTO_DOC)					? $conf->global->CATEGORY_ADD_DESC_INTO_DOC					: 0;
		$hideServDate	= isset($conf->global->INFRASPLUS_PDF_HIDE_SERVICE_DATES)			? $conf->global->INFRASPLUS_PDF_HIDE_SERVICE_DATES			: 0;
		if (!empty($isSubTotal) || !empty($isSubTitle) || !empty($isSubFreeT) || $isOuvrage > 1 ) {
			if ($object->element == 'delivery' && !empty($object->commande->expeditions[$object->lines[$i]->fk_origin_line]))
				unset($object->commande->expeditions[$object->lines[$i]->fk_origin_line]);
			if (empty($label)) {
				$label	= $desc;
				$desc	= '';
			}
			if ($conf->global->SUBTOTAL_USE_NEW_FORMAT && !empty($isSubTotal))	$libelleproduitservice	= (infraspackplus_getTitle($object, $object->lines[$i], $isSubTotal) != '' ? infraspackplus_getTitle($object, $object->lines[$i], $isSubTotal).' : ' : '').$label;
			else																$libelleproduitservice	= $label;
			$decal																= empty($object->lines[$i]->fk_parent_line) ? 0 : 3;
			return array('main' => $libelleproduitservice, 'subdesc' => ((!empty($isSubTitle) || $isOuvrage > 1) && !empty($desc) ? $desc : ''), 'decal' => $decal);
		}
		if ($issupplierline) {
			$ref_supplier	= (!empty($object->lines[$i]->ref_supplier) ? $object->lines[$i]->ref_supplier : (!empty($object->lines[$i]->ref_fourn) ? $object->lines[$i]->ref_fourn : ''));
			if (empty($ref_supplier)) {
				$ref	= $object->lines[$i]->ref;
				$sql	= 'SELECT pfp.ref_fourn ';
				$sql	.= 'FROM '.MAIN_DB_PREFIX.'product AS p ';
				$sql	.= 'LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price AS pfp ON p.rowid = pfp.fk_product ';
				$sql	.= 'WHERE p.ref = "'.$ref.'" AND pfp.fk_soc = "'.$object->thirdparty->id.'"';
				$resql	= $object->db->query($sql);
				if ($resql) {
					$obj		= $db->fetch_object($resql);
					if ($obj)	$ref_supplier	= $obj->ref_fourn;
				}
				$db->free($resql);
			}
			$prodser	= new ProductFournisseur($db);
		}
		else	$prodser	= new Product($db);
		if ($idprod) {
			$prodser->fetch($idprod);
			// If a predefined product and multilang and on other lang, we renamed label with label translated
			if ($isMultilangs && ($outputlangs->defaultlang != $langs->defaultlang)) {
				$translatealsoifmodified	= (!empty($forceTranslate));	// By default if value was modified manually, we keep it (no translation because we don't have it)

				// TODO Instead of making a compare to see if param was modified, check that content contains reference translation. If yes, add the added part to the new translation
				// ($textwasmodified is replaced with $textwasmodifiedorcompleted and we add completion).

				// Set label
				// If we want another language, and if label is same than default language (we did force it to a specific value), we can use translation.
				//var_dump($outputlangs->defaultlang.' - '.$langs->defaultlang.' - '.$label.' - '.$prodser->label);exit;
				$textwasmodified			= ($label == $prodser->label);
				if (!empty($prodser->multilangs[$outputlangs->defaultlang]['label']) && ($textwasmodified || $translatealsoifmodified))			$label				= $prodser->multilangs[$outputlangs->defaultlang]['label'];

				// Set desc
				// Manage HTML entities description test because $prodser->description is store with htmlentities but $desc no
				$textwasmodified																												= false;
				if (!empty($desc) && dol_textishtml($desc) && !empty($prodser->description) && dol_textishtml($prodser->description))			$textwasmodified	= (strpos(dol_html_entity_decode($desc, ENT_QUOTES | ENT_HTML5), dol_html_entity_decode($prodser->description, ENT_QUOTES | ENT_HTML5)) !== false);
				else																															$textwasmodified	= ($desc == $prodser->description);
				if (!empty($prodser->multilangs[$outputlangs->defaultlang]['description']) && ($textwasmodified || $translatealsoifmodified))	$desc				= $prodser->multilangs[$outputlangs->defaultlang]['description'];
				// Set note
				$textwasmodified																												= ($note == $prodser->note);
				if (!empty($prodser->multilangs[$outputlangs->defaultlang]['note']) && ($textwasmodified || $translatealsoifmodified))			$note				= $prodser->multilangs[$outputlangs->defaultlang]['note'];
			}
		}
		elseif (($object->element == 'facture' || $object->element == 'facturefourn') && preg_match('/^\(DEPOSIT\).+/', $desc))	$desc	= str_replace('(DEPOSIT)', $outputlangs->trans('Deposit'), $desc);
		$libelleproduitservice																									= '';
		// Description short of product line
		if (empty($hidelabel)) {
			if ($labelbold && $label)	$libelleproduitservice	.= '<b>'.$label.'</b>';
			else						$libelleproduitservice	.= $label;
			// Add ref of subproducts
			if (!empty($subref)) {
				$prodser->get_sousproduits_arbo();
				if (!empty($prodser->sousprods) && is_array($prodser->sousprods) && count($prodser->sousprods)) {
					$tmparrayofsubproducts							= reset($prodser->sousprods);
					foreach ($tmparrayofsubproducts as $subprodval)	$libelleproduitservice .= '__N__ * '.$subprodval[5].(($subprodval[5] && $subprodval[3]) ? ' - ' : '').$subprodval[3].' ('.$subprodval[1].')';
				}
			}
		}
		// Extra-details of product line
		if ($extraDet)	$libelleproduitservice	.= empty($extraDetPos2) ? $extraDet : '';
		// Description long of product line
		if (!empty($desc) && ($desc != $label || !empty($hidelabel))) {
			if ($libelleproduitservice && empty($hidedesc))	$libelleproduitservice	.= '__N__';
			if ($desc == '(CREDIT_NOTE)' && $object->lines[$i]->fk_remise_except) {
				$discount				= new DiscountAbsolute($db);
				$discount->fetch($object->lines[$i]->fk_remise_except);
				$sourceref				= !empty($discount->discount_type) ? $discount->ref_invoive_supplier_source : $discount->ref_facture_source;
				$libelleproduitservice	= $outputlangs->transnoentitiesnoconv('DiscountFromCreditNote', $sourceref);
			}
			elseif ($desc == '(DEPOSIT)' && $object->lines[$i]->fk_remise_except) {
				$discount				= new DiscountAbsolute($db);
				$discount->fetch($object->lines[$i]->fk_remise_except);
				$sourceref				= !empty($discount->discount_type) ? $discount->ref_invoive_supplier_source : $discount->ref_facture_source;
				$libelleproduitservice	= $outputlangs->transnoentitiesnoconv('DiscountFromDeposit', $sourceref);
				// Add date of deposit
				if (!empty($depositDate))	echo ' ('.dol_print_date($discount->datec, 'day', '', $outputlangs).')';
			}
			elseif ($desc == '(EXCESS RECEIVED)' && $object->lines[$i]->fk_remise_except) {
				$discount				= new DiscountAbsolute($db);
				$discount->fetch($object->lines[$i]->fk_remise_except);
				$libelleproduitservice	= $outputlangs->transnoentitiesnoconv('DiscountFromExcessReceived', $discount->ref_facture_source);
			}
			elseif ($desc == '(EXCESS PAID)' && $object->lines[$i]->fk_remise_except) {
				$discount				= new DiscountAbsolute($db);
				$discount->fetch($object->lines[$i]->fk_remise_except);
				$libelleproduitservice	= $outputlangs->transnoentitiesnoconv('DiscountFromExcessPaid', $discount->ref_invoice_supplier_source);
			}
			else {
				if ($idprod) {
					// Check if description must be output for this kind of document
					if (!empty($object->element)) {
						$tmpkey								= 'MAIN_DOCUMENTS_HIDE_DESCRIPTION_FOR_'.strtoupper($object->element);
						if (!empty($conf->global->$tmpkey))	$hidedesc	= 1;
					}
					if (empty($hidedesc)) {
						if (!empty($descFirst))	$libelleproduitservice	= $desc.'__N__'.$libelleproduitservice;
						else {
							if (!empty($hidelblvariant) && $prodser->isVariant())	$libelleproduitservice	= $desc;
							else													$libelleproduitservice	.= $desc;
						}
					}
				}
				else	$libelleproduitservice	.= $desc;
			}
		}
		if (!empty($extraDetPos2))	$libelleproduitservice	.= !empty($extraDet) ? $extraDet : '';
		// We add ref of product (and supplier ref if defined)
		$prefix_prodserv	= '';
		$ref_prodserv		= '';
		if (!empty($prodAddType)) {	// In standard mode, we do not show this
			if ($prodser->isService())	$prefix_prodserv	= $outputlangs->transnoentitiesnoconv('Service').' ';
			else						$prefix_prodserv	= $outputlangs->transnoentitiesnoconv('Product').' ';
		}
		if (empty($hideref)) {
			if ($issupplierline) {
				if (empty($prodRefSupp)) {	// Common case
					$ref_prodserv		= $prodser->ref; // Show local ref
					if ($ref_supplier)	$ref_prodserv	.= ($prodser->ref ? ' (' : '').$outputlangs->transnoentitiesnoconv('SupplierRef').' '.$ref_supplier.($prodser->ref ? ')' : '');
				}
				elseif ($prodRefSupp == 1)	$ref_prodserv	= $ref_supplier;
				elseif ($prodRefSupp == 2)	$ref_prodserv	= $ref_supplier.' ('.$outputlangs->transnoentitiesnoconv('InternalRef').' '.$prodser->ref.')';
			}
			else															$ref_prodserv	= $prodser->ref; // Show local ref only
			if (!empty($libelleproduitservice) && !empty($ref_prodserv))	$ref_prodserv	.= ' - ';
		}
		if (!empty($ref_prodserv) && !empty($HTMLinDesc))	$ref_prodserv	= '<b>'.$ref_prodserv.'</b>';
		$libelleproduitservice								= $prefix_prodserv.$ref_prodserv.$libelleproduitservice;
		// Add an additional description for the category products
		if (!empty($CatInDesc) && $idprod && !empty($conf->categorie->enabled)) {
			include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			$categstatic	= new Categorie($db);
			$tblcateg		= $categstatic->containing($idprod, Categorie::TYPE_PRODUCT);	// recovering the list of all the categories linked to product
			foreach($tblcateg as $cate) {
				$desccateg		= $cate->add_description;	// Adding the descriptions if they are filled
				if ($desccateg)	$libelleproduitservice	.= '__N__'.$desccateg;
			}
		}
		if (empty($hideServDate)) {
			$period		= '';
			$period2	= '';
			// Show duration if exists
			if ($object->element == 'contrat') {
				if ($object->lines[$i]->date_start)		$period		.= $outputlangs->transnoentitiesnoconv('DateStartPlanned').' : '.dol_print_date($object->lines[$i]->date_start, 'day', false, $outputlangs);
				if ($object->lines[$i]->date_end)		$period		.= ($period ? ' - ' : '').$outputlangs->transnoentitiesnoconv('DateEndPlanned').' : '.dol_print_date($object->lines[$i]->date_end, 'day', false, $outputlangs);
				if ($object->lines[$i]->date_ouverture)	$period2	.= $outputlangs->transnoentitiesnoconv('DateStartReal').' : '.dol_print_date($object->lines[$i]->date_ouverture, 'day', false, $outputlangs);
				if ($object->lines[$i]->date_cloture)	$period2	.= ($period ? ' - ' : '').$outputlangs->transnoentitiesnoconv('DateEndReal').' : '.dol_print_date($object->lines[$i]->date_cloture, 'day', false, $outputlangs);
				$period									.= ($period && $period2 ? '__N__'.$period2 : $period2);
			}
			else {
				if ($object->lines[$i]->date_start && $object->lines[$i]->date_end)		$period	= '('.$outputlangs->transnoentitiesnoconv('DateFromTo', dol_print_date($object->lines[$i]->date_start, 'day', false, $outputlangs), dol_print_date($object->lines[$i]->date_end, 'day', false, $outputlangs)).')';
				if ($object->lines[$i]->date_start && ! $object->lines[$i]->date_end)	$period	= '('.$outputlangs->transnoentitiesnoconv('DateFrom', dol_print_date($object->lines[$i]->date_start, 'day', false, $outputlangs)).')';
				if (! $object->lines[$i]->date_start && $object->lines[$i]->date_end)	$period = '('.$outputlangs->transnoentitiesnoconv('DateUntil', dol_print_date($object->lines[$i]->date_end, 'day', false, $outputlangs)).')';
			}
			if (!empty($period)) {
				$period_html = $period;
				if (!empty($conf->global->INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE) || !empty($conf->global->INFRASPLUS_PDF_DESC_PERIOD_COLOR)) {
					$period_style = '';
					if (!empty($conf->global->INFRASPLUS_PDF_DESC_PERIOD_COLOR)) {
						$period_style .= ' color: rgb(' . $conf->global->INFRASPLUS_PDF_DESC_PERIOD_COLOR . ');';
					}
					if (!empty($conf->global->INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE)) {
						$period_style .= ' font-size: ' . $conf->global->INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE . ';';
					}
					$period_html = '<span' . (!empty($period_style) ? ' style="' . $period_style . '"' : '') . '>' . $period_html . '</span>';
				}
				$libelleproduitservice .= "__N__" . $period_html;
			}
		}
		if ($dbatch) {
			foreach($dbatch as $detail) {
				$dte=array();
				if ($detail->eatby)		$dte[]	= $outputlangs->transnoentitiesnoconv('printEatby', dol_print_date($detail->eatby, 'day', false, $outputlangs));
				if ($detail->sellby)	$dte[]	= $outputlangs->transnoentitiesnoconv('printSellby', dol_print_date($detail->sellby, 'day', false, $outputlangs));
				if ($detail->batch)		$dte[]	= $outputlangs->transnoentitiesnoconv('printBatch', $detail->batch);
				$dte[]					= $outputlangs->transnoentitiesnoconv('printQty', $detail->qty);
				$libelleproduitservice	.= '__N__  '.implode(' - ', $dte);
			}
		}
		// Now we convert \n into br
		if (dol_textishtml($libelleproduitservice))	$libelleproduitservice	= preg_replace('/__N__/', '<br>', $libelleproduitservice);
		else										$libelleproduitservice	= preg_replace('/__N__/', "\n", $libelleproduitservice);
		$libelleproduitservice						= dol_htmlentitiesbr($libelleproduitservice, 1);
		$decal										= empty($object->lines[$i]->fk_parent_line) ? 0 : 6;
		return array('main' => $libelleproduitservice, 'subdesc' => '', 'decal' => $decal);
	}

	/********************************************
	*	Return dimensions to use for images onto PDF with a width limit (to fit column widt for example)
	*
	*	@param  int			$w				Width
	*	@param  array		$realpatharray	List of image path classed by line row ID ($i)
	*	@return	array						Height and width to use to output image (in pdf user unit, so mm)
	 ********************************************/
	function pdf_InfraSPlus_getlineimgsize($w, $realpatharray)
	{
		global $conf;

		$wpicture					= isset($conf->global->INFRASPLUS_PDF_PICTURE_WIDTH)	? $conf->global->INFRASPLUS_PDF_PICTURE_WIDTH	: 20;
		$hpicture					= isset($conf->global->INFRASPLUS_PDF_PICTURE_HEIGHT)	? $conf->global->INFRASPLUS_PDF_PICTURE_HEIGHT	: 32;
		if ($w - 2 < $wpicture)		$wpicture		= $w - 2;	// corrige la largeur maximal de l'image pour être au plus égale à la largeur colonne
		$imglinesize				= array();
		if (!empty($realpatharray))	$imglinesize	= pdf_InfraSPlus_getSizeForImage($realpatharray, $wpicture, $hpicture);
		return $imglinesize;
	}

	/********************************************
	*	Output product / service image into PDF
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param	Object		$object			Object shown in PDF
	*	@param	int			$i				Current line number
	*	@param  Translate	$outputlangs	Object lang for output
	*	@param  int			$posx			Pos x
	*	@param  int			$posy			Pos y
	*	@param  int			$w				Width
	*	@param  array		$realpatharray	List of image path classed by line row ID ($i)
	*	@param  array		$imglinesize	Image size => 'width', 'height'
	*	@param  string		$linkpictureurl	Public URL to show
	*	@param  int			$tab_hl			Line height
	*	@return	void
	 ********************************************/
	function pdf_InfraSPlus_writelineimg(&$pdf, $object, $i, $outputlangs, $posx, $posy, $w, $realpatharray, $imglinesize, $linkpictureurl, $tab_hl = 4)
	{
		$lineurl	= pdf_InfraSPlus_getlineurl($object, $i);
		$txturl		= !empty($lineurl) && !empty($linkpictureurl) ? '<a href = "'.$lineurl.'" target = "_blank">'.pdf_InfraSPlus_formatNotes($object, $outputlangs, $linkpictureurl).'</a>' : '&nbsp;';
		if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
			$posxpicture					= $posx + (($w - $imglinesize['width']) / 2);	// centre l'image dans la colonne
			$pdf->Image($realpatharray[$i], $posxpicture, $posy, $imglinesize['width'], $imglinesize['height']);	// Use 300 dpi
			$pdf->writeHTMLCell($w, $tab_hl, $posxpicture, $posy + $imglinesize['height'] - ($txturl == '&nbsp;' ? $tab_hl : 0), dol_htmlentitiesbr($txturl), 0, 1);
			return $pdf->GetY();
		}
		elseif	($txturl != '&nbsp;' && $realpatharray[$i] != 'done') {
			$pdf->writeHTMLCell($w, $tab_hl, $posx, $posy, dol_htmlentitiesbr($txturl), 0, 1);
			return $pdf->GetY();
		}
		else	return $posy;
	}

	/********************************************
	*	Return line quantity
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param  Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@return	string
	 ********************************************/
	function pdf_InfraSPlus_getlineqty($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null)
	{
		global $hookmanager;

		$result		= '';
		$reshook	= 0;
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineqty', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint))				$result			= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
		   if ($object->lines[$i]->special_code == 3)	return '';
		   if (empty($hidedetails) || $hidedetails > 1)	$result	.= $prodfichinter ? $prodfichinter['qty'] : $object->lines[$i]->qty;
		}
		return $result;
	}

	/********************************************
	*	Return line vat rate
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param  Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$prodfichinter		intervention Line
	*	@param	boolean		$calcul				0 = return TVA with sign (to print) ; 1 = return value for calculation
	* 	@return	string
	********************************************/
	function pdf_InfraSPlus_getlinevatrate($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $calcul = 0)
	{
		global $conf, $hookmanager, $mysoc;

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$result		= '';
		$reshook	= 0;
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlinevatrate',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint))				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				$tva_tx		= $prodfichinter ? $prodfichinter['tva_tx'] : $object->lines[$i]->tva_tx;
				$info_bits	= $prodfichinter ? $prodfichinter['info_bits'] : $object->lines[$i]->info_bits;
				$tmpresult	= '';
				$tmpresult	.= vatrate($tva_tx, 0, $info_bits, -1);
				if (empty($conf->global->MAIN_PDF_MAIN_HIDE_SECOND_TAX)) {
					$total_localtax1	= $prodfichinter ? $prodfichinter['total_localtax1'] : $object->lines[$i]->total_localtax1;
					if ($total_localtax1 != 0) {
						$localtax1_tx								= $prodfichinter ? $prodfichinter['localtax1_tx'] : $object->lines[$i]->localtax1_tx;
						if (preg_replace('/[\s0%]/','',$tmpresult))	$tmpresult	.= '/';
						else										$tmpresult	= '';
						$tmpresult									.= vatrate(abs($localtax1_tx), 0);
					}
				}
				if (empty($conf->global->MAIN_PDF_MAIN_HIDE_THIRD_TAX)) {
					$total_localtax2	= $prodfichinter ? $prodfichinter['total_localtax2'] : $object->lines[$i]->total_localtax2;
					if ($total_localtax2 != 0) {
						$localtax2_tx								= $prodfichinter ? $prodfichinter['localtax2_tx'] : $object->lines[$i]->localtax2_tx;
						if (preg_replace('/[\s0%]/','',$tmpresult))	$tmpresult	.= '/';
						else										$tmpresult	= '';
						$tmpresult									.= vatrate(abs($localtax2_tx), 0);
					}
				}
				$tmpresult	.= $calcul ? '' : '%';
				$result		.= $tmpresult;
			}
		}
		return $result;
	}

	/********************************************
	*	Return line remise percent
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param  Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$prodfichinter		intervention Line
	*	@param	array		$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	* 	@return	string
	********************************************/
	function pdf_InfraSPlus_getlineremisepercent($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $conf, $hookmanager;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$showDiscOpt											= isset($conf->global->INFRASPLUS_PDF_SHOW_DISCOUNT_OPT) ? $conf->global->INFRASPLUS_PDF_SHOW_DISCOUNT_OPT : 0;
		$rounding												= min($conf->global->MAIN_MAX_DECIMALS_UNIT, $conf->global->MAIN_MAX_DECIMALS_TOT);
		$reshook												= 0;
		$result													= '';
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineremisepercent', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint))				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			$remise_percent									= $prodfichinter												? $prodfichinter['remise_percent']	: $object->lines[$i]->remise_percent;
			$remise_percent									= empty($remise_percent) && !empty($pricesObjProd['remise'])	? $pricesObjProd['remise']			: $remise_percent;
			if (empty($hidedetails) || $hidedetails > 1)	$result	.= dol_print_reduction(round($remise_percent, $rounding), $outputlangs);
		}
		return $result;
	}

	/********************************************
	*	Return formated price
	*
	*	@param	Object		$object				Object
	*	@param	float		$price				price to format
	*	@param  Translate	$outputlangs		Object langs for output
	* 	@return	price
	 ********************************************/
	function pdf_InfraSPlus_price($object, $price, $outputlangs, $forceSymb = 0)
	{
		global $conf;

		$rounding		= min($conf->global->MAIN_MAX_DECIMALS_UNIT, $conf->global->MAIN_MAX_DECIMALS_TOT);
		$currency		= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
		$showCurSymb	= $forceSymb ? 1 : (isset($conf->global->INFRASPLUS_PDF_SHOW_CUR_SYMB) ? $conf->global->INFRASPLUS_PDF_SHOW_CUR_SYMB : 0);
		return price($price, 0, $outputlangs, 1, $rounding, -1, ($showCurSymb ? $currency : ''));
	}
	/********************************************
	*	Return line unit price excluding tax
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param  Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	* 	@return	string							Line unit price excluding tax
	 ********************************************/
	function pdf_InfraSPlus_getlineupexcltax($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $conf, $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$sign													= isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE) ? -1 : 1;
		$reshook												= 0;
		$result													= '';
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i,'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineupexcltax', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint)) 				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$subprice	= !empty($conf->multicurrency->enabled) && $object->lines[$i]->multicurrency_subprice != 0 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
					break;
					case 'fichinter':
						$subprice	= $prodfichinter ? $prodfichinter['subprice'] : 0;
					break;
					default:
						$subprice	= !empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
					break;
				}
				$subprice	= !empty($pricesObjProd['pu_ht']) ? $pricesObjProd['pu_ht'] : $subprice;
				$result		.= pdf_InfraSPlus_price($object, $sign * $subprice, $outputlangs);
			}
		}
		return $result;
	}

	/********************************************
	*	Return line unit price including tax
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param  Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	* 	@return	string							Line unit price including tax
	 ********************************************/
	function pdf_InfraSPlus_getlineupincltax($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $conf, $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$sign													= isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE) ? -1 : 1;
		$reshook												= 0;
		$result													= '';
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i,'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineupwithtax', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint)) 				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$subprice	= !empty($conf->multicurrency->enabled) && $object->lines[$i]->multicurrency_subprice != 0 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
					break;
					case 'fichinter':
						$subprice	= $prodfichinter ? $prodfichinter['subprice'] : 0;
					break;
					default:
						$subprice	= !empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
					break;
				}
				$tva_tx		= $prodfichinter ? $prodfichinter['tva_tx'] : $object->lines[$i]->tva_tx;
				$ttcPrice	= !empty($pricesObjProd['pu_ttc']) ? $pricesObjProd['pu_ttc'] : ($subprice + ($subprice * $tva_tx / 100));
				$result		.= pdf_InfraSPlus_price($object, $sign * $ttcPrice, $outputlangs);
			}
		}
		return $result;
	}

	/********************************************
	*	Return line unit price with discount and excluding tax
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param  Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	* 	@return	string							Line unit price with discount and excluding tax
	 ********************************************/
	function pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null)
	{
		global $conf, $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$sign													= isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE) ? -1 : 1;
		$reshook												= 0;
		$result													= '';
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i,'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineupexcltax', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint)) 				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$total_ht	= !empty($conf->multicurrency->enabled) && $object->lines[$i]->multicurrency_total_ht != 0 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
					break;
					case 'fichinter':
						$total_ht	= $prodfichinter ? $prodfichinter['total_ht'] : 0;
					break;
					default:
						$total_ht	= !empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
					break;
				}
				$isSitFac	= !empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0 ? $object->lines[$i]->situation_percent / 100 : 1;	// use this to find the real unit price on situation invoice
				$qty		= $prodfichinter ? $prodfichinter['qty'] : $object->lines[$i]->qty;
				$qty		= $qty == 0 ? 1 : $qty;
				$result		.= pdf_InfraSPlus_price($object, $sign * ($total_ht / $qty / $isSitFac), $outputlangs);
			}
		}
		return $result;
	}

	/********************************************
	*	Return line unit price with discount and including tax
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param 	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
	*	@return	string							Line unit price with discount and including tax
	 ********************************************/
	function pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null)
	{
		global $conf, $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$sign													= isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE) ? -1 : 1;
		$reshook												= 0;
		$result													= '';
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineupwithtax', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint)) 				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$total_ttc	= !empty($conf->multicurrency->enabled) && $object->lines[$i]->multicurrency_total_ttc != 0 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc;
					break;
					case 'fichinter':
						$total_ttc	= $prodfichinter ? $prodfichinter['total_ttc'] : 0;
					break;
					default:
						$total_ttc	= !empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc;
					break;
				}
				$isSitFac	= !empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0 ? $object->lines[$i]->situation_percent / 100 : 1;	// use this to find the real unit price on situation invoice
				$qty		= $prodfichinter ? $prodfichinter['qty'] : $object->lines[$i]->qty;
				$qty		= $qty == 0 ? 1 : $qty;
				$result		.= pdf_InfraSPlus_price($object, $sign * ($total_ttc / $qty / $isSitFac), $outputlangs);
			}
		}
		return $result;
	}

	/********************************************
	*	Return line percent
	*
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param 	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
	*	@param	Object		$hookmanager		Hook manager instance
	*	@return	string							Rounded percentage of line progress
	 ********************************************/
	function pdf_InfraSPlus_getlineprogress($object, $i, $outputlangs, $hidedetails = 0, $hookmanager = null)
	{
		if (empty($hookmanager)) global $hookmanager;
		global $conf;

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$reshook												= 0;
		$result													= '';
		$rounding												= min($conf->global->MAIN_MAX_DECIMALS_UNIT, $conf->global->MAIN_MAX_DECIMALS_TOT);
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineprogress', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint))				return $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if ($object->lines[$i]->special_code == 3)	return '';
			if (empty($hidedetails) || $hidedetails > 1) {
				if ($conf->global->SITUATION_DISPLAY_DIFF_ON_PDF) {
					$prev_progress										= 0;
					if (method_exists($object, 'get_prev_progress'))	$prev_progress	= $object->lines[$i]->get_prev_progress($object->id);
					$result												= round(($object->lines[$i]->situation_percent - $prev_progress), $rounding).'%';
				}
				else	$result	= round($object->lines[$i]->situation_percent, $rounding).'%';
			}
		}
		return $result;
	}

	/********************************************
	*	Return total of line excluding tax
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param 	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
	*	@return	string							Total of line excluding tax
	 ********************************************/
	function pdf_InfraSPlus_getlinetotalexcltax(&$pdf, $object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null)
	{
		global $conf, $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$sitFacTotLineAvt										= isset($conf->global->INFRASPLUS_PDF_SITFAC_TOTLINE_AVT) ? $conf->global->INFRASPLUS_PDF_SITFAC_TOTLINE_AVT : 0;
		$sign													= isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE) ? -1 : 1;
		$reshook												= 0;
		$result													= '';
		$isSubTotalLine											= infraspackplus_isSubTotalLine($object->lines[$i], $object->element, 'modSubtotal');
		$isATMLine												= $conf->global->MAIN_MODULE_SUBTOTAL && $isSubTotalLine ? true : false;
		$isSubTotal												= $isATMLine && $object->lines[$i]->qty  > 90 ? infraspackplus_get_mod_number('modSubtotal') : 0;	// Sous-total ATM
		if ($isSubTotal) {	// Sous-total ATM
			$bodytxtsubtocolor	= isset($conf->global->INFRASPLUS_PDF_TEXT_SUBTO_COLOR)	? $conf->global->INFRASPLUS_PDF_TEXT_SUBTO_COLOR	: 0;
			$bodytxtsubtocolor	= explode(',', $bodytxtsubtocolor);
			$pdf->SetTextColor($bodytxtsubtocolor[0], $bodytxtsubtocolor[1], $bodytxtsubtocolor[2]);
			$pdf->SetFont('', (!empty($conf->global->SUBTOTAL_SUBTOTAL_STYLE) ? $conf->global->SUBTOTAL_SUBTOTAL_STYLE : 'B'));
		}
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlinetotalexcltax', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint)) 				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if ($object->lines[$i]->special_code == 3)	return $outputlangs->transnoentities('Option');
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$total_ht	= !empty($conf->multicurrency->enabled) && $object->lines[$i]->multicurrency_total_ht != 0 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
					break;
					case 'fichinter':
						$total_ht	= $prodfichinter ? $prodfichinter['total_ht'] : 0;
					break;
					default:
						$total_ht	= !empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
					break;
				}
				if (!empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0 && empty($sitFacTotLineAvt)) {
					$prev_progress	= 0;
					$progress		= 1;
					if (method_exists($object->lines[$i], 'get_prev_progress')) {
						$prev_progress	= $object->lines[$i]->get_prev_progress($object->id);
						$progress		= ($object->lines[$i]->situation_percent - $prev_progress) / 100;
					}
					$result	.= pdf_InfraSPlus_price($object, $sign * ($total_ht / ($object->lines[$i]->situation_percent / 100)) * $progress, $outputlangs);
				}
				else	$result	.= pdf_InfraSPlus_price($object, $sign * $total_ht, $outputlangs);
			}
		}
		return $result;
	}

	/********************************************
	*	Return total of line including tax
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param	Object		$object				Object
	*	@param	int			$i					Current line number
	*	@param 	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
	*	@return	string							Total of line including tax
	 ********************************************/
	function pdf_InfraSPlus_getlinetotalincltax(&$pdf, $object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null)
	{
		global $conf, $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i)))	return '';
		$sitFacTotLineAvt										= isset($conf->global->INFRASPLUS_PDF_SITFAC_TOTLINE_AVT) ? $conf->global->INFRASPLUS_PDF_SITFAC_TOTLINE_AVT : 0;
		$sign													= isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE) ? -1 : 1;
		$reshook												= 0;
		$result													= '';
		$isSubTotalLine											= infraspackplus_isSubTotalLine($object->lines[$i], $object->element, 'modSubtotal');
		$isATMLine												= $conf->global->MAIN_MODULE_SUBTOTAL && $isSubTotalLine ? true : false;
		$isSubTotal												= $isATMLine && $object->lines[$i]->qty  > 90 ? infraspackplus_get_mod_number('modSubtotal') : 0;	// Sous-total ATM
		if ($isSubTotal) {	// Sous-total ATM
			$bodytxtsubtocolor	= isset($conf->global->INFRASPLUS_PDF_TEXT_SUBTO_COLOR)	? $conf->global->INFRASPLUS_PDF_TEXT_SUBTO_COLOR	: 0;
			$bodytxtsubtocolor	= explode(',', $bodytxtsubtocolor);
			$pdf->SetTextColor($bodytxtsubtocolor[0], $bodytxtsubtocolor[1], $bodytxtsubtocolor[2]);
			$pdf->SetFont('', (!empty($conf->global->SUBTOTAL_SUBTOTAL_STYLE) ? $conf->global->SUBTOTAL_SUBTOTAL_STYLE : 'B'));
		}
		if (is_object($hookmanager)) {
			$special_code									= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlinetotalwithtax', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint))				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if ($object->lines[$i]->special_code == 3)	return $outputlangs->transnoentities('Option');
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$total_ttc	= !empty($conf->multicurrency->enabled) && $object->lines[$i]->multicurrency_total_ttc != 0 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc;
					break;
					case 'fichinter':
						$total_ttc	= $prodfichinter ? $prodfichinter['total_ttc'] : 0;
					break;
					default:
						$total_ttc	= !empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc;
					break;
				}
				if ($object->lines[$i]->situation_percent > 0 && empty($sitFacTotLineAvt)) {
					$prev_progress	= 0;
					$progress		= 1;
					if (method_exists($object->lines[$i], 'get_prev_progress')) {
						$prev_progress	= $object->lines[$i]->get_prev_progress($object->id);
						$progress		= ($object->lines[$i]->situation_percent - $prev_progress) / 100;
					}
					$result	.= pdf_InfraSPlus_price($object, $sign * ($total_ttc / ($object->lines[$i]->situation_percent / 100)) * $progress, $outputlangs);
				}
				else	$result	.= pdf_InfraSPlus_price($object, $sign * $total_ttc, $outputlangs);
			}
		}
		return $result;
	}

	/********************************************
	*	Return line product ref for Intervention card
	*
	*	@param	Object	$object		Object
	* 	@return	array
	 ********************************************/
	function pdf_infrasplus_getpricefichinter($object)
	{
		global $db;

		$pricefichinter	= array();
		$sql	= 'SELECT fi.total_ht, fi.total_ttc, fi.total_tva, fi.total_localtax1, fi.total_localtax2';
		$sql	.= ' FROM '.MAIN_DB_PREFIX.'fichinter AS fi';
		$sql	.= ' WHERE fi.rowid = '.$object->id;
		$resql	= $db->query($sql);
		if ($resql) {
			$num	= $db->num_rows($resql);
			for ($j = 0; $j < $num; $j++) {
				$objp								= $db->fetch_object($resql);
				$pricefichinter['total_ht']			= $objp->total_ht;
				$pricefichinter['total_ttc']		= $objp->total_ttc;
				$prodfichinter['total_tva']			= $objp->total_tva;
				$prodfichinter['total_localtax1']	= $objp->total_localtax1;
				$prodfichinter['total_localtax2']	= $objp->total_localtax2;
			}
			$db->free($resql);
		}
		return $pricefichinter;
	}

	/********************************************
	*	Show bank informations for PDF generation
	*
	*	@param	TCPDF		$pdf           	 	The PDF factory
	*	@param  Translate	$outputlangs		Object lang for output
	*	@param  int			$posx				X position
	*	@param  int			$posy				Y position
	*	@param  int			$larg				Block Width
	*	@param  int			$hl					Line height
	*	@param  Object		$account			Bank account object
	*	@param  int			$onlynumber			Output only number (bank+desk+key+number according to country, but without name of bank and domiciliation)
	*	@param  int			$default_font_size	Default font size
	* 	@return	float							The Y PDF position
	 ********************************************/
	function pdf_infrasplus_bank(&$pdf, $outputlangs, $posx, $posy, $larg = 100, $hl = 4, $account, $onlynumber = 0, $default_font_size = 10)
	{
		global $conf;

		$outputlangs->load('banks');

		$diffsizetitle		= isset($conf->global->PDF_DIFFSIZE_TITLE)						? $conf->global->PDF_DIFFSIZE_TITLE						: 3;
		$diffsizecontent	= isset($conf->global->PDF_DIFFSIZE_CONTENT)					? $conf->global->PDF_DIFFSIZE_CONTENT					: 4;
		$only_BIC_IBAN		= isset($conf->global->PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN)	? $conf->global->PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN	: 0;
		if (empty($onlynumber)) {
			$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
			$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').' : ', 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $hl;
		}
		$bickey				= $account->getCountryCode() == 'EN' ? 'SWIFT' : 'BICNumber';	// Use correct name of bank id according to country
		$usedetailedbban	= $account->useDetailedBBAN();	// Get format of bank account according to its country
		if ($usedetailedbban) {
			$savposx	= $posx;
			if (empty($onlynumber)) {
				$pdf->SetFont('', '', $default_font_size - $diffsizecontent);
				$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities('Bank').' : '.$outputlangs->convToOutputCharset($account->bank), 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
				$posy	+= $hl;
			}
			if (empty($only_BIC_IBAN)) {	// Note that some countries still need bank number, BIC/IBAN not enougth for them
				// Note:
				// bank = code_banque (FR), sort code (GB, IR. Example: 12-34-56)
				// desk = code guichet (FR), used only when $usedetailedbban = 1
				// number = account number
				// key = check control key used only when $usedetailedbban = 1
				if (empty($onlynumber)) $pdf->line($posx, $posy, $posx, $posy + (($hl - 1) * 2));
				foreach ($account->getFieldsToShow() as $val) {
					if ($val == 'BankCode') {
						$tmplength	= $larg / 6;
						$content	= $account->code_banque;
					}
					elseif ($val == 'DeskCode') {
						$tmplength	= $larg / 6;
						$content	= $account->code_guichet;
					}
					elseif ($val == 'BankAccountNumber') {
						$tmplength	= $larg / 4;
						$content	= $account->number;
					}
					elseif ($val == 'BankAccountNumberKey') {
						$tmplength	= $larg / 12;
						$content	= $account->cle_rib;
					}
					elseif ($val == 'IBAN' || $val == 'BIC') {
						$tmplength	= 0;
						$content	= '';
					}
					else {
						dol_print_error($account->db, 'Unexpected value for getFieldsToShow: '.$val);
						break;
					}
					$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
					$pdf->MultiCell($tmplength, $hl - 1, $outputlangs->transnoentities($val), 0, 'C', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - $diffsizecontent);
					$pdf->MultiCell($tmplength, $hl - 1, $outputlangs->convToOutputCharset($content), 0, 'C', false, 1, $posx, $posy + $hl - 1, true, 0, false, false, 0, 'M', false);
					// Open-DSI -- FIX vertical align in PDF models -- End
					$posx	+= $tmplength;
					if (empty($onlynumber))	$pdf->line($posx, $posy, $posx, $posy + (($hl - 1) * 2));
				}
				$posx	= $savposx;
				$posy	+= (($hl - 1) * 2) + 1;
			}
		}
		else {
			$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
			$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities('Bank').' : '.$outputlangs->convToOutputCharset($account->bank), 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy						+= $hl;
			$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
			$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities('BankAccountNumber').' : '.$outputlangs->convToOutputCharset($account->number), 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy						+= $hl;
			if ($diffsizecontent <= 2)	$posy += 1;
		}
		$pdf->SetFont('', '', $default_font_size - $diffsizecontent);
		if (empty($onlynumber) && !empty($account->domiciliation)) {
			$val	= $outputlangs->transnoentities('Residence').' : '.$outputlangs->convToOutputCharset($account->domiciliation);
			$pdf->MultiCell($larg, $hl, $val, 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $pdf->getStringHeight($larg, $val);
		}
		if (empty($onlynumber) && !empty($account->proprio)) {
			$val	= $outputlangs->transnoentities("BankAccountOwner").' : '.$outputlangs->convToOutputCharset($account->proprio);
			$pdf->MultiCell($larg, $hl, $val, 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $pdf->getStringHeight($larg, $val);
		}
		elseif (!$usedetailedbban)	$posy	+= 1;
		$ibankey					= FormBank::getIBANLabel($account);	// Use correct name of bank id according to country
		if (!empty($account->iban)) {
			//Remove whitespaces to ensure we are dealing with the format we expect
			$ibanDisplay_temp	= str_replace(' ', '', $outputlangs->convToOutputCharset($account->iban));
			$ibanDisplay		= '';
			$nbIbanDisplay_temp	= dol_strlen($ibanDisplay_temp);
			for ($i = 0; $i < $nbIbanDisplay_temp; $i++) {
				$ibanDisplay				.= $ibanDisplay_temp[$i];
				if ($i % 4 == 3 && $i > 0)	$ibanDisplay	.= ' ';
			}
			$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
			$val	= $outputlangs->transnoentities($ibankey).' : '.$ibanDisplay;
			$pdf->MultiCell($larg, $hl, $val, 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $pdf->getStringHeight($larg, $val);
		}
		if (!empty($account->bic)) {
			$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
			$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities($bickey).' : '.$outputlangs->convToOutputCharset($account->bic), 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
		}
		return $pdf->getY();
	}

	/********************************************
	*	Get spacial payment extrafield
	*
	*	@param		Object		$object			Object shown in PDF
	*	@param		int			$deposit		0 for special payments || 1 for deposit
	*	@return		array						Return extrafield found ['label'] => label ['value'] => value
	********************************************/
	function pdf_InfraSPlus_SpecPayExtraField($object, $deposit = 0)
	{
		global $db, $conf;

		$list			= array();
		$efPaySpec		= isset($conf->global->INFRASPLUS_PDF_EXF_PAY_SPEC)	? $conf->global->INFRASPLUS_PDF_EXF_PAY_SPEC	: '';
		$efDeposit		= isset($conf->global->INFRASPLUS_PDF_EXF_DEPOSIT)	? $conf->global->INFRASPLUS_PDF_EXF_DEPOSIT		: '';
		$ef				= explode(',', preg_replace('/\s+/', '', ($deposit ? $efDeposit : $efPaySpec)));	// string without any space to array
		$extrafields	= new ExtraFields($db);
		$extralabels	= $extrafields->fetch_name_optionals_label($object->table_element);
		$object->fetch_optionals();
		foreach ($extralabels as $key => $label) {
			if (in_array($key, $ef)) {
				$options_key	= $object->array_options['options_' .$key];
				$value			= price2num($extrafields->showOutputField($key, $options_key), 'MT');
				if (!empty($value)) { // check if something is writting for this extrafield
					$list[$key]['label']	= $label;
					$list[$key]['value']	= $value;
				}
			}
		}
		return $list;
	}

	/************************************************
	*	Show CGV for PDF generation
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param  string		$cgv			PDF file name
	*	@param	int			$hidepagenum	Hide page num (x/y)
	*	@param  Object		$object     	Object shown in PDF
	*	@param  Translate	$outputlangs	Object lang for output
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	************************************************/
	function pdf_InfraSPlus_CGV(&$pdf, $cgv, $hidepagenum = 0, $object, $outputlangs, $formatpage)
	{
		global $conf;

		$path		= ($conf->entity > 1 ? "/".$conf->entity : '');
		$cgv_pdf	= DOL_DATA_ROOT.$path.'/mycompany/'.$cgv;
		pdf_InfraSPlus_Merge($pdf, $cgv_pdf, $hidepagenum, $object, $outputlangs, $formatpage);
	}

	/************************************************
	*	Show files for PDF generation
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param  string		$files			list of rowid for PDF file from llx_ecm_files
	*	@param	int			$hidepagenum	Hide page num (x/y)
	*	@param  Object		$object     	Object shown in PDF
	*	@param  Translate	$outputlangs	Object lang for output
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	************************************************/
	function pdf_InfraSPlus_files(&$pdf, $files, $hidepagenum = 0, $object, $outputlangs, $formatpage)
	{
		global $conf, $db;

		if ($files && $files != 'None') {
			foreach ($files as $fileID) {
				$sql	= ' SELECT filename, filepath';
				$sql	.= ' FROM '.MAIN_DB_PREFIX.'ecm_files';
				$sql	.= ' WHERE rowid = '.$fileID;
				$sql	.= ' AND entity = '.$conf->entity;
				$resql	= $db->query($sql);
				if ($resql) {
					$objFile	= $db->fetch_object($resql);
					if ($objFile) {
						$filename	= $objFile->filename;
						$filepath	= $objFile->filepath;
						$file		= DOL_DATA_ROOT.'/'.$filepath.'/'.$filename;
						pdf_InfraSPlus_Merge($pdf, $file, $hidepagenum, $object, $outputlangs, $formatpage);
					}
				}
			}
		}
	}

	/************************************************
	*	Show CGV for PDF generation
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param  string		$infile			PDF file full name (with path) to merge
	*	@param	int			$hidepagenum	Hide page num (x/y)
	*	@param  Object		$object     	Object shown in PDF
	*	@param  Translate	$outputlangs	Object lang for output
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	************************************************/
	function pdf_InfraSPlus_Merge(&$pdf, $infile, $hidepagenum = 0, $object, $outputlangs, $formatpage)
	{
		global $conf;

		if (file_exists($infile) && is_readable($infile)) {
			$finfo	= finfo_open(FILEINFO_MIME_TYPE);
			if (finfo_file($finfo, $infile) == 'application/pdf') {
				try {
					$pdf->SetAutoPageBreak(0, 0);
					$pagecount	= $pdf->setSourceFile($infile);
					for ($i = 1; $i <= $pagecount; $i ++) {
						$tplIdx	= $pdf->importPage($i);
						if ($tplIdx !== false) {
							$s	= $pdf->getTemplatesize($tplIdx);
							$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L', array($s['w'], $s['h']));
							$pdf->useTemplate($tplIdx);
							if (! $hidepagenum) {
								$prevFont									= $pdf->getFontFamily();
								$pdf->SetFont('Helvetica');
								if (empty($conf->global->MAIN_USE_FPDF))	$pdf->MultiCell(20, 2, $pdf->PageNo().' / '.$pdf->getAliasNbPages(), 0, 'R', 0, 1, $conf->global->INFRASPLUS_PDF_X_PAGE_NUM, $conf->global->INFRASPLUS_PDF_Y_PAGE_NUM, true, 0, 0, false, 0, 'M', false);
								else										$pdf->MultiCell(20, 2, $pdf->PageNo().' / {nb}', 0, 'R', 0, 1, $conf->global->INFRASPLUS_PDF_X_PAGE_NUM, $conf->global->INFRASPLUS_PDF_Y_PAGE_NUM, true, 0, 0, false, 0, 'M', false);
								$pdf->SetFont($prevFont);
							}
							if (!empty($conf->global->INFRASPLUS_PDF_REFDATE_MERGE))	pdf_InfraSPlus_pagesrefdate($pdf, $object, $outputlangs, '', $formatpage['mhaute'], $formatpage['largeur'] - $formatpage['mdroite'] - 100);
						}
						else	setEventMessages(null, array($outputlangs->trans("PDFInfraSPlusPdfFileError1", $infile)), 'warnings');
					}
					$pdf->SetAutoPageBreak(1, 0);
				}
				catch (exception $e) {
					setEventMessages(null, array($outputlangs->trans("PDFInfraSPlusPdfFileError1", $infile).$outputlangs->trans("PDFInfraSPlusPdfFileError2", $e->getMessage())), 'warnings');
				}
			}
		}
	}

	/************************************************
	*	Show reference and date of document.
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param  Object		$object     	Object shown in PDF
	*	@param  Translate	$outputlangs	Object lang for output
	* 	@param	string		$title			string title in connection with objet type
	*	@param	int			$posx			Position depart (largeur)
	*	@param	int			$posy			Position depart (hauteur)
	************************************************/
	function pdf_InfraSPlus_pagesrefdate(&$pdf, $object, $outputlangs, $title, $posy, $posx, $ticket = 0)
	{
		global $conf;

		$ref_from_cust		= isset($conf->global->INFRASPLUS_PDF_REFD_FROM_CUSTOMER) ? $conf->global->INFRASPLUS_PDF_REFD_FROM_CUSTOMER : 0;
		$ref				= $outputlangs->transnoentities('Ref');
		$reference			= $outputlangs->convToOutputCharset($object->element == 'propal' && !empty($ref_from_cust) ? $object->ref_client : $object->ref);
		$date				= dol_print_date($object->date, 'day', false, $outputlangs, true);
		if (empty($date))	$date	= dol_print_date($object->date_commande, 'day', false, $outputlangs, true);
		if (empty($date))	$date	= dol_print_date($object->date_contrat, 'day', false, $outputlangs, true);
		if (empty($date))	$date	= dol_print_date($object->date_delivery, 'day', false, $outputlangs, true);
		if (empty($date))	$date	= dol_print_date($object->datec, 'day', false, $outputlangs, true);
		if (!empty($date))	$pdf->MultiCell(100, 4, ($title ? $title.' ' : '').$ref.' '.$reference.' '.$outputlangs->transnoentities('Of').' '.$date, '', $ticket ? 'L' : 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
		else				$pdf->MultiCell(100, 4, ($title ? $title.' ' : '').$ref.' '.$reference, '', $ticket ? 'L' : 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
	}

	/********************************************
	*	Add a draft watermark on PDF files
	*
	*	@param	TCPDF		$pdf            The PDF factory
	*	@param  Translate	$outputlangs	Object lang
	*	@param  string		$text           Text to show
	*	@param  int		    $center_y       Y center of rotation
	*	@param  int		    $w		        Width of table
	*	@param  int		    $hp		        Height of page
	*	@param  string	    $unit           Unit of height (mm, pt, ...)
	*	@return	void
	 ********************************************/
	function pdf_InfraSPlus_watermark(&$pdf, $outputlangs, $text, $center_y, $w, $hp, $unit)
	{
		global $conf;

		$watermark_t_opacity	= isset($conf->global->INFRASPLUS_PDF_T_WATERMARK_OPACITY)		? $conf->global->INFRASPLUS_PDF_T_WATERMARK_OPACITY : 10;
		// Print Draft Watermark
		if ($unit=='pt')		$k = 1;
		elseif ($unit=='mm')	$k = 72/25.4;
		elseif ($unit=='cm')	$k = 72/2.54;
		elseif ($unit=='in')	$k = 72;
		$savx					= $pdf->getX();
		$savy					= $pdf->getY();
		$watermark_angle		= 20 / 180 * pi();	// angle de rotation 20° en radian
		$center_x				= $w / 2;			// x centre
		$pdf->SetFont('', 'B', 40);
		$pdf->SetTextColor(255, 0, 0);
		$pdf->SetAlpha($watermark_t_opacity / 100);
		//rotate
		$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', cos($watermark_angle), sin($watermark_angle),
					-sin($watermark_angle), cos($watermark_angle), $center_x * $k, ($hp - $center_y) * $k, -$center_x * $k, -($hp - $center_y) * $k));
		//print watermark
		$pdf->SetXY(10, $center_y - 10);
		$pdf->Cell($w, 20, $outputlangs->convToOutputCharset($text), "", 2, "C", 0);
		//antirotate
		$pdf->_out('Q');
		$pdf->SetXY($savx, $savy);
		$pdf->SetAlpha(1);
	}

	/************************************************
	*	Show customer signature
	*
	*	@param	TCPDF		$pdf     		The PDF factory
	*	@param	str			$signvalue		base64 string for png image
	*	@param  int		    $larg_signarea	Width of area
	*	@param  int		    $ht_signarea	Height of area
	*	@param  int		    $posxsignarea	X position for the up left corner of area
	*	@param  int		    $posysignarea	Y position for the up left corner of area
	* 	@return	void
	************************************************/
	function pdf_InfraSPlus_Client_Sign($pdf, $signvalue, $larg_signarea, $ht_signarea, $posxsignarea, $posysignarea)
	{
		$imgSign64	= preg_replace('#^data:image/[^;]+;base64,#', '', $signvalue);
		$fileSign	= dol_buildpath('infraspackplus', 0).'/tmp/tmp.png';
		file_put_contents($fileSign, base64_decode($imgSign64));
		if ($fileSign && is_readable($fileSign)) {
			$imgsize	= array();
			$imgsize	= pdf_InfraSPlus_getSizeForImage($fileSign, $larg_signarea, $ht_signarea);
			if (isset($imgsize['width']) && isset($imgsize['height'])) {
				$posxSign	= ($larg_signarea - $imgsize['width']) / 2;	// centre l'image dans la zone
				$posySign	= ($ht_signarea - $imgsize['height']) / 2;	// centre l'image dans la zone
				$pdf->Image($fileSign, $posxsignarea + $posxSign, $posysignarea + $posySign, $imgsize['width'], $imgsize['height'], '', '', '', false, 300, '', false, false, 0);	// set sgnature image
			}
		}
		dol_delete_file($fileSign);
	}

	/************************************************
	*	Show footer of page for PDF generation
	*
	*	@param	TCPDF		$pdf     		The PDF factory
	*	@param  Object		$object         Object shown in PDF
	*	@param  Translate	$outputlangs	Object lang for output
	* 	@param	Societe		$fromcompany	Object company
	* 	@param	array		$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	* 	@param	int			$showdetails	Show company details into footer. This param seems to not be used by standard version.
											00000 = vide
											1xx0x = address								=> line 1
											1xx1x = address need 2 lines				=> line 1 + 1bis
											2xxxx = contacts (phone, fax, url, mail)	=> line 2
											3xxxx = 1xxx + 2xxx
											x1xxx = manager								=> line 3
											xx1xx = type soc. + capital					=> line 3
											xx2xx = prof. ids							=> line 4
											xx3xx = xx1x + xx2x
											xxxx1 = footer image						=> line 5
	*	@param	int			$hidesupline	Completly hide the line up to footer (for some edition with only table)
	*	@param	int			$calculseul		Arrête la fonction au calcul de hauteur nécessaire
	* 	@param	int			$objEntity		Object entity
	*	@param	str			$image_foot		File Name of the image to show
	* 	@param	array		$maxsizeimgfoot	Maximum size for image foot => 'largeur', 'hauteur'
	*	@param	int			$hidepagenum	Hide page num (x/y)
	* 	@param	array		$txtcolor		Text color
	*	@param	array		$LineStyle		PDF Line style
	*	@param	int			$noendline		1 to hide the end line (before footer)
	* 	@return	int							Return height of bottom margin including footer text
	************************************************/
	function pdf_InfraSPlus_pagefoot(&$pdf, $object, $outputlangs, $fromcompany, $formatpage, $showdetails, $hidesupline, $calculseul, $objEntity, $image_foot = '', $maxsizeimgfoot, $hidepagenum = 0, $txtcolor = array(0, 0, 0), $LineStyle = null, $noendline = 0)
	{
		global $conf, $user;

		$pdf->SetTextColor($txtcolor[0], $txtcolor[1], $txtcolor[2]);
		$footer_bold	= isset($conf->global->INFRASPLUS_PDF_FOOTER_BOLD) ? $conf->global->INFRASPLUS_PDF_FOOTER_BOLD : 0;
		$pdf->SetFont('', $footer_bold ? 'B' : '', 7);
		$alignL1		= 'C';
		// First line of company infos
		if (!empty($conf->global->INFRASPLUS_PDF_FOOTER_FREETEXT)) {
			$footer_freeText	= $conf->global->INFRASPLUS_PDF_FOOTER_FREETEXT;
			$line1				= pdf_InfraSPlus_formatNotes($object, $outputlangs, $footer_freeText);
			$htLine1			= $pdf->getStringHeight($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), dol_htmlentitiesbr($line1), true, false, 0, 0);
			$alignL1			= '';
		}
		else {
			$line1 = ''; $htLine1 = 3; $line2 = ''; $line3 = ''; $line4 = ''; $line5 = 0;
			if (substr($showdetails, 0, 1) == 1 || substr($showdetails, 0, 1) == 3) {
				if ($fromcompany->name)			$line1	.= ($line1 ? ' - ' : '').$outputlangs->transnoentities('RegisteredOffice').' : '.$fromcompany->name; // Company name
				if ($fromcompany->address)		$line1	.= ($line1 ? ' - ' : '').str_replace(CHR(13).CHR(10),' - ',$fromcompany->address); // Address
				if ($fromcompany->zip)			$line1	.= ($line1 ? ' - ' : '').$fromcompany->zip; // Zip code
				if ($fromcompany->town)			$line1	.= ($line1 ? ' ' : '').$fromcompany->town; // Town
				if ($fromcompany->country_code)	$line1	.= ($line1 ? ' - ' : '').$outputlangs->transnoentitiesnoconv('Country'.$fromcompany->country_code); // Country
			}
			if (substr($showdetails, 0, 1) == 2 || substr($showdetails, 0, 1) == 3) {
				if ($fromcompany->phone)	$line2	.= ($line2 ? ' - ' : '').$outputlangs->transnoentities('PhoneShort').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($fromcompany->phone))); // Phone
				if ($fromcompany->fax)		$line2	.= ($line2 ? ' - ' : '').$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($fromcompany->fax))); // Fax
				if ($fromcompany->url)		$line2	.= ($line2 ? ' - ' : '').$fromcompany->url; // URL
				if ($fromcompany->email)	$line2	.= ($line2 ? ' - ' : '').$fromcompany->email; // Email
			}
			if (substr($showdetails, 1, 1) == 1 || ($fromcompany->country_code == 'DE'))
				if ($fromcompany->managers)	$line3 .= ($line3 ? ' - ' : '').$outputlangs->transnoentities('PDFInfraSPlusManagement').' : '.$fromcompany->managers; // Managers
			if (substr($showdetails, 2, 1) == 1 || substr($showdetails, 2, 1) == 3) {
				if ($fromcompany->forme_juridique_code)	$line3 .= ($line3 ? ' - ' : '').$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code)); // Juridical status
				if ($fromcompany->capital) { // Capital
					$tmpamounttoshow											= price2num($fromcompany->capital); // This field is a free string or a float
					if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0)	$line3	.= ($line3 ? ' - ' : '').$outputlangs->transnoentities('CapitalOf', price($tmpamounttoshow, 0, $outputlangs, 0, 0, 0, $conf->currency));
					elseif (!empty($fromcompany->capital))						$line3	.= ($line3 ? ' - ' : '').$outputlangs->transnoentities('CapitalOf', $tmpamounttoshow, $outputlangs);
				}
			}
			if (substr($showdetails, 2, 1) == 2 || substr($showdetails, 2, 1) == 3) {
				if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || ! $fromcompany->idprof2)) { // Prof Id 1
					$field											= $outputlangs->transcountrynoentities('ProfId1', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg))	$field	= $reg[1];
					$tmpID											= pdf_InfraSPlus_build_IDs('ID1', $outputlangs->convToOutputCharset($fromcompany->idprof1), $fromcompany->country_code);
					$line4											.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if ($fromcompany->idprof2) { // Prof Id 2
					$field											= $outputlangs->transcountrynoentities('ProfId2', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg))	$field	= $reg[1];
					$tmpID											= pdf_InfraSPlus_build_IDs('ID2', $outputlangs->convToOutputCharset($fromcompany->idprof2), $fromcompany->country_code);
					$line4											.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if ($fromcompany->idprof3) { // Prof Id 3
					$field											= $outputlangs->transcountrynoentities('ProfId3', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg))	$field	= $reg[1];
					$tmpID											= pdf_InfraSPlus_build_IDs('ID3', $outputlangs->convToOutputCharset($fromcompany->idprof3), $fromcompany->country_code);
					$line4											.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if ($fromcompany->idprof4) { // Prof Id 4
					$field											= $outputlangs->transcountrynoentities('ProfId4', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg))	$field	= $reg[1];
					$tmpID											= pdf_InfraSPlus_build_IDs('ID4', $outputlangs->convToOutputCharset($fromcompany->idprof4), $fromcompany->country_code);
					$line4											.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if ($fromcompany->idprof5) { // Prof Id 5
					$field											= $outputlangs->transcountrynoentities('ProfId5', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg))	$field	= $reg[1];
					$tmpID											= pdf_InfraSPlus_build_IDs('ID5', $outputlangs->convToOutputCharset($fromcompany->idprof5), $fromcompany->country_code);
					$line4											.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if ($fromcompany->idprof6) { // Prof Id 6
					$field											= $outputlangs->transcountrynoentities('ProfId6', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg))	$field	= $reg[1];
					$tmpID											= pdf_InfraSPlus_build_IDs('ID6', $outputlangs->convToOutputCharset($fromcompany->idprof6), $fromcompany->country_code);
					$line4											.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if ($fromcompany->tva_intra != '') {	// IntraCommunautary VAT
					$tmpID	= pdf_InfraSPlus_build_IDs('TVA', $outputlangs->convToOutputCharset($fromcompany->tva_intra), $fromcompany->country_code);
					$line4	.= ($line4 ? ' - ' : '').$outputlangs->transnoentities('VATIntraShort').' : '.$tmpID;
				}
			}
		}
		if (substr($showdetails, 4, 1) == 1) {
			$logodir	= !empty($conf->mycompany->multidir_output[$objEntity]) ? $conf->mycompany->multidir_output[$objEntity] : $conf->mycompany->dir_output;
			$logospied	= $logodir.'/logos/'.$image_foot;	// Logos partenaires en ligne 5
			if (is_readable($logospied)) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
				$imglinesize	= pdf_InfraSPlus_getSizeForImage($logospied, $maxsizeimgfoot['largeur'], $maxsizeimgfoot['hauteur']);
				if ($imglinesize['height'])	$line5	= $imglinesize['height'];
			}
		}
		// The start of the bottom of this page footer is positioned according to # of lines
		$nopage					= $pdf->PageNo();
		$nbpage					= $pdf->getNumPages();
		$marginwithfooter		= (($nopage == $nbpage) && empty($hidesupline) ? 1 : 0) + (!empty($line1) ? $htLine1 : 0) + (!empty($line2) ? 3 : 0) + (!empty($line3) ? 3 : 0) + (!empty($line4) ? 3 : 0) + $line5 + $formatpage['mbasse'];
		if ($calculseul == 1)	return $marginwithfooter;
		$posy					= $formatpage['hauteur'] - $marginwithfooter;
		$pdf->SetY($posy);
		if (empty($noendline) && $nopage == $nbpage && empty($hidesupline)) {
			$pdf->line($formatpage['mgauche'], $posy, $formatpage['largeur']-$formatpage['mdroite'], $posy, $LineStyle);
			$posy++;
		}
		if (!empty($line1)) {
			$pdf->writeHTMLCell($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), $htLine1, $formatpage['mgauche'], $posy, dol_htmlentitiesbr($line1), 0, 1, false, true, $alignL1, true);
			$posy	+= $htLine1 == 3 ? (substr($showdetails, 3, 1) == 1 ? 6 : 3) : $htLine1;
		}
		if (!empty($line2)) {
			$pdf->MultiCell($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), 2, $line2, 0, 'C', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= 3;
		}
		if (!empty($line3)) {
			$pdf->MultiCell($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), 2, $line3, 0, 'C', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= 3;
		}
		if (!empty($line4))	$pdf->MultiCell($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), 2, $line4, 0, 'C', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
		if (is_readable($logospied)) {
			$posy			+= $htLine1 == 3 ? 3 : 0;
			$posxpicture	= $formatpage['mgauche'] + (($formatpage['largeur'] - $formatpage['mgauche'] - $formatpage['mdroite'] - $imglinesize['width']) / 2);	// centre l'image dans la colonne
			$pdf->Image($logospied, $posxpicture, $posy, $imglinesize['width'], $line5);	// width=0 or height=0 (auto)
		}
		$pdf->SetFont('', '', 7);
		if (! $hidepagenum) { // Show page nb only on iso languages (so default Helvetica font)
			$prevFont																				= $pdf->getFontFamily();
			$pdf->SetFont('Helvetica');
			if (version_compare(DOL_VERSION, '8.0', '>=') || empty($conf->global->MAIN_USE_FPDF))	$pdf->MultiCell(26, 2, $pdf->PageNo().' / '.$pdf->getAliasNbPages(), 0, 'R', 0, 1, $formatpage['largeur'] - ($formatpage['mdroite'] + 20), $formatpage['hauteur'] - $formatpage['mbasse'], true, 0, 0, false, 0, 'M', false);
			else																					$pdf->MultiCell(26, 2, $pdf->PageNo().' / {nb}', 0, 'R', 0, 1, $formatpage['largeur'] - ($formatpage['mdroite'] + 20), $formatpage['hauteur'] - $formatpage['mbasse'], true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont($prevFont);
		}
		return $marginwithfooter;
	}

	/************************************************
	*	Convert RGBA color to RGB value
	*
	*	@param	string		$bgcolor     		RGB value for background color
	*	@param	string		$color	     		RGB color to convert
	*	@param	float		$alpha	     		alpha value => 0.0 to 1
	*	@return	string							new RGB color
	************************************************/
	function pdf_InfraSPlus_rgba_to_rgb(&$color, $bgcolor = '255, 255, 255', $alpha = 1)
	{
		global $conf;

		$tmpcol				= explode(',', $color);
		$tmpcol[0]			= (!empty($tmpcol[0]) ? $tmpcol[0] : 0);
		$tmpcol[1]			= (!empty($tmpcol[1]) ? $tmpcol[1] : 0);
		$tmpcol[2]			= (!empty($tmpcol[2]) ? $tmpcol[2] : 0);
		$tmpbg				= explode(',', $bgcolor);
		$tmpbg[0]			= (!empty($tmpbg[0]) ? $tmpbg[0] : 0);
		$tmpbg[1]			= (!empty($tmpbg[1]) ? $tmpbg[1] : 0);
		$tmpbg[2]			= (!empty($tmpbg[2]) ? $tmpbg[2] : 0);
		$alpha				= (!empty($alpha) && 0 < $alpha && $alpha < 1 ? $alpha : 1);
		$tmpvalr			= ((1 - $alpha) * $tmpbg[0]) + ($alpha * $tmpcol[0]);
		$tmpvalg			= ((1 - $alpha) * $tmpbg[1]) + ($alpha * $tmpcol[1]);
		$tmpvalb			= ((1 - $alpha) * $tmpbg[2]) + ($alpha * $tmpcol[2]);
		$tmpval				= $tmpvalr.', '.$tmpvalg.', '.$tmpvalb;
		return $tmpval;
	}

	/************************************************
	*	Get subtotal lines for summary
	*
	*	@param		Object		$object			Object shown in PDF
	*	@param		integer		$i				Line number we work on
	*	@param		array		$subtotalRecap	list of subtotal lines
	*	@return		array						array of subtotal lines updated
	************************************************/
	function pdf_InfraSPlus_subtotal_getrecap ($object, $i, $subtotalRecap)
	{
		$isSubTotalLine		= infraspackplus_isSubTotalLine($object->lines[$i], $object->element, 'modSubtotal');
		$isSubTitle			= $isSubTotalLine && $object->lines[$i]->qty  < 10 ? 1 : 0;	// Sous-titre ATM
		$isSubTotal			= $isSubTotalLine && $object->lines[$i]->qty  > 90 ? infraspackplus_get_mod_number('modSubtotal') : 0;	// Sous-total ATM
		if ($isSubTotal) {
			foreach ($object->lines as $line) {
				if ($line->id == $object->lines[$i]->id)		break;
				$qty_search										= 100 - $object->lines[$i]->qty;
				$isSubTotalLine									= infraspackplus_isSubTotalLine($line, $object->element, 'modSubtotal');
				$isSubTitle										= $isSubTotalLine && $line->qty  < 10 ? 1 : 0;	// Sous-titre ATM
				if ($isSubTitle && $line->qty == $qty_search)	$titleRang	= $line->rang;
			}
			$subtotalRecap[]	= array('line' => $i, 'type' => 'subtotal', 'rang' => $titleRang, 'level' => $qty_search);
		}
		return $subtotalRecap;
	}

	/************************************************
	*	Sort an array by values using a closure (for multi-dimensional array)
	*
	*	@param		string		$key			key to use
	*	@return		array						sorted array
	************************************************/
	function pdf_InfraSPlus_compare ($key)
	{
		return function ($a, $b) use ($key)	{ return strnatcmp($a[$key], $b[$key]); };
	}

	/************************************************
	*	Print subtotal Summary
	*
	*	@param		PDF			$pdf     			The PDF factory
	*	@param		Object		$object				Object shown in PDF
	*	@param		float		$tab_top			Top position of table
	*	@param		Translate	$outputlangs		Object lang for output
	*	@param		array		$subtotalRecap		array of lines to print
	*	@param		Object		$template			object template we work on
    *	@param		integer		$heightforinfotot	height reserved for info table
	*	@param		integer		$heightforfooter	height reserved for footer
	*	@return		integer							next Y position
	************************************************/
	function pdf_InfraSPlus_subtotal_recap (&$pdf, $object, $tab_top, $outputlangs, $subtotalRecap, &$template, $ht_coltotal, $heightforfooter)
	{
		global $conf, $db;

		$default_font_size	= pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->MultiCell($template->formatpage['largeur'] - $template->formatpage['mgauche'] - $template->formatpage['mdroite'], $template->heightline * 2, $outputlangs->transnoentities('PDFInfraSPlusRecap'), '', 'C', 0, 1, $template->formatpage['mgauche'], $tab_top + 10, true, 0, 0, false, 0, 'M', false);
		$pdf->SetFont('', '', $default_font_size - 1);
		$posy				= $tab_top + 30;
		$nblignes			= count($subtotalRecap);
		for ($i = 0 ; $i < $nblignes ; $i++) {
			$pageposbefore				= $pdf->getPage();
			$posx						= $template->tableau['desc']['posx'] + ($subtotalRecap[$i]['level'] > 1 ? $subtotalRecap[$i]['level'] * 4 : 0);
			pdf_InfraSPlus_writelinedesc($pdf, $object, $subtotalRecap[$i]['line'], $outputlangs, $template->formatpage, $template->horLineStyle, $template->tableau['desc']['larg'], $template->heightline, $posx, $posy, 0, 0, 0, '', null, 0, 1);
			// Total line
			if (empty($template->hide_vat))		$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $subtotalRecap[$i]['line'], $outputlangs);
			else						$total_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $subtotalRecap[$i]['line'], $outputlangs);
			$pdf->MultiCell($template->tableau['totalht']['larg'], $template->heightline, $total_line, '', 'R', 0, 1, $template->tableau['totalht']['posx'], $posy, true, 0, 0, false, 0, 'M', false);
			if($template->show_ttc_col) {
				$totalTTC_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $subtotalRecap[$i]['line'], $outputlangs);
				$pdf->MultiCell($template->tableau['totalttc']['larg'], $template->heightline, $totalTTC_line, '', 'R', 0, 1, $template->tableau['totalttc']['posx'], $posy, true, 0, 0, false, 0, 'M', false);
			}
			$pageposafter	= $pdf->getPage();
			$posyafter		= $pdf->GetY();
			if ($pageposafter > $pageposbefore) {	// There is a pagebreak
				if ($posyafter > ($template->formatpage['hauteur'] - ($heightforfooter + $ht_coltotal))) {	// There is no space left for total+free text
					$pdf->AddPage('', '', true);
					$pdf->setPage($pageposafter + 1);
					$posy	= $tab_top + ($template->hide_top_table ? $template->decal_round : $template->ht_top_table + $template->decal_round);
				}
			}
			elseif ($posyafter > ($template->formatpage['hauteur'] - ($heightforfooter + $ht_coltotal))) {	// There is no space left for total+free text
				$pdf->AddPage('', '', true);
				$pdf->setPage($pageposafter + 1);
				$posy	= $tab_top + ($template->hide_top_table ? $template->decal_round : $template->ht_top_table + $template->decal_round);
			}
			$posy	+= $template->heightline * 2;
		}
		return $pdf->GetY();
	}

	/********************************************
	*	Check whether we need to show or hide the values of works and / or sub-elements of work
	*
	*	@param	Object		$object		Object
	*	@param	int			$i			Current line number
	*	@param	int			$mode		function mode	=> 0 = std (return 1 to hide numeric values || 0 to show them)
	*													=> 1 = all line (return 1 to hide the entire line || 0 to show it)
	*													=> 2 = check for description and label
	* 	@return	int						1 if if we need to escape (return empty string) 0 if we can continue
	********************************************/
	function pdf_InfraSPlus_escapeOuvrage ($object, $i, $mode = 0)
	{
		global $conf;

		if ($conf->global->MAIN_MODULE_OUVRAGE) {
			$isOuvrage													= 0;
			$ouvHideMnt													= GETPOST('OUVRAGE_HIDE_MONTANT', 'int');	// Cacher le montant des ouvrages/forfaits
			$ouvHideDet													= GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL', 'int');	// Afficher uniquement l'ouvrage/forfait
			$ouvHideDesc												= GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION', 'int');	// Cacher les détails tarifaires des produits/services
			if (Ouvrage::isOuvrage($object->lines[$i]))					$isOuvrage	= 2;	// ligne d'ouvrage Inovea
			if ($isOuvrage == 2 && !empty($ouvHideMnt) && empty($mode))	return 1;	// ligne d'ouvrage + mode 1 => on cache le montant
			if (isset($object->lines[$i]->fk_parent_line)) {
				$inOuvrage = false;
				foreach ($object->lines as $key => $value)
					if ($object->lines[$i]->fk_parent_line == $object->lines[$key]->rowid && Ouvrage::isOuvrage($object->lines[$key]))	$inOuvrage = true;
				if ($inOuvrage) {	// Composant d'ouvrage
					switch ($mode) {
						case '2':
							return $isOuvrage + 1;	// mode 2 et ligne incluse dans un ouvrage Inovea
						break;
						case '1':
							if (!empty($ouvHideDet) && !Ouvrage::isOuvrage($object->lines[$i]))	return 1;	// mode 1 et Afficher uniquement l'ouvrage et la ligne n'est pas un ouvrage
						break;
						case '0':
							if ((!empty($ouvHideDet) || !empty($ouvHideDesc)) && !empty($ouvHideMnt))							return 1;	// (Afficher uniquement l'ouvrage ou Cacher les détails tarifaires) et  Cacher le montant
							elseif ((!empty($ouvHideDet) || !empty($ouvHideDesc)) && !Ouvrage::isOuvrage($object->lines[$i]))	return 1;	// (Afficher uniquement l'ouvrage ou Cacher les détails tarifaires) et  la ligne n'est pas un ouvrage
							elseif (Ouvrage::isOuvrage($object->lines[$i]) && !empty($ouvHideMnt))								return 1;	// la ligne est un ouvrage et Cacher le montant
						break;
					}
				}
			}
		}
		return $mode == 2 ? $isOuvrage : 0;
	}

	/********************************************
	*	Check whether we need to show or hide the lines between elements
	*
	*	@param	Object		$object		Object
	*	@param	int			$i			Current line number
	* 	@return	int						1 if if we can use the standard type of separation between elements 0 if not
	********************************************/
	function pdf_InfraSPlus_separateLine ($object, $i)
	{
		global $conf;

		if ($conf->global->MAIN_MODULE_OUVRAGE) {
			$ouvHideDet										= GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL', 'int');	// Afficher uniquement l'ouvrage/forfait
			if (!empty($ouvHideDet))						return 1;	// each line can be processed as usual because we hide all the details of the works
			elseif (Ouvrage::isOuvrage($object->lines[$i]))	return 0;	// we never want to show a separation between a work and its sub-elements
			else {
				if (isset($object->lines[$i]->fk_parent_line)) {
					$inOuvrage = false;
					foreach ($object->lines as $key => $value)
						if ($object->lines[$i]->fk_parent_line == $object->lines[$key]->rowid && Ouvrage::isOuvrage($object->lines[$key]))	$inOuvrage = true;
					if ($inOuvrage) {	// Composant d'ouvrage
						if ($object->lines[$i]->fk_parent_line == $object->lines[$i + 1]->fk_parent_line)	return 0;
						else																				return 1;
					}
				}
			}
		}
		return 1;
	}
?>
