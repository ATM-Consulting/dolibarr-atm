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
	* 	\file		../infraspackplus/core/modules/facture/doc/pdf_InfraSPlus_FT.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF invoice
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	 *	Class to generate PDF invoice InfraS
	************************************************/
	class pdf_InfraSPlus_FT extends ModelePDFFactures
	{
		public $db;
		public $name;
		public $description;
		public $update_main_doc_field;	// Save the name of generated file as the main doc when generating a doc with this template
		public $type;
		public $phpmin	= array(5, 5);
		public $version	= 'dolibarr';
		public $page_largeur;
		public $page_hauteur;
		public $format;
		public $marge_gauche;
		public $marge_droite;
		public $marge_haute;
		public $marge_basse;
		public $emetteur;
		public $situationinvoice;		// @var bool Situation invoice type

        public $bank_only_number = 0;

		/********************************************
		*	Constructor
		*
		*	@param		DoliDB		$db      Database handler
		********************************************/
		public function __construct($db)
		{
			global $conf, $langs, $mysoc;

			$langs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'infraspackplus@infraspackplus'));

            pdf_InfraSPlus_getValues($this);
			$this->name									= $langs->trans('PDFInfraSPlusInvoiceTicketName');
			$this->description							= $langs->trans('PDFInfraSPlusInvoiceTicketDescription');
			$this->update_main_doc_field				= 1;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->emetteur								= $mysoc;
			if (empty($this->emetteur->country_code))	$this->emetteur->country_code										= substr($langs->defaultlang, -2);
			$this->atleastonediscount					= 0;
			$this->credit_note							= isset($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)				? $conf->global->INVOICE_POSITIVE_CREDIT_NOTE				: 0;
			$this->tva									= array();
			$this->localtax1							= array();
			$this->localtax2							= array();
			$this->tvaProd								= array();
			$this->localtax1Prod						= array();
			$this->localtax2Prod						= array();
			$this->htProd								= array();
			$this->tvaServ								= array();
			$this->localtax1Serv						= array();
			$this->localtax2Serv						= array();
			$this->htServ								= array();
			$this->atleastoneratenotnull				= 0;
			$this->situationinvoice						= False;
			$this->type									= 'pdf';
			$this->defaulttemplate						= isset($conf->global->FACTURE_ADDON_PDF)							? $conf->global->FACTURE_ADDON_PDF							: '';
			$this->multilangs							= isset($conf->global->MAIN_MULTILANGS)								? $conf->global->MAIN_MULTILANGS							: 0;
			$this->use_fpdf								= isset($conf->global->MAIN_USE_FPDF)								? $conf->global->MAIN_USE_FPDF								: 0;
			$this->main_umask							= isset($conf->global->MAIN_UMASK)									? $conf->global->MAIN_UMASK									: '0755';
			$formatarray								= array('width'=>100, 'height'=>141, 'unit'=>'mm');	// pdf_InfraSPlus_getFormat();
			$this->page_largeur							= $formatarray['width'];
			$this->page_hauteur							= $formatarray['height'];
			$this->format								= array($this->page_largeur, $this->page_hauteur);
			$this->marge_gauche							= 5;	// isset($conf->global->MAIN_PDF_MARGIN_LEFT)						? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
			$this->marge_haute							= 5;	// isset($conf->global->MAIN_PDF_MARGIN_TOP)							? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
			$this->marge_droite							= 5;	// isset($conf->global->MAIN_PDF_MARGIN_RIGHT)						? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
			$this->marge_basse							= 5;	// isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)						? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;
			$this->formatpage							= array('largeur'=>$this->page_largeur, 'hauteur'=>$this->page_hauteur, 'mgauche'=>$this->marge_gauche,
																'mdroite'=>$this->marge_droite, 'mhaute'=>$this->marge_haute, 'mbasse'=>$this->marge_basse);
			$this->hide_vat_ifnull						= isset($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL)	? $conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL	: 0;
			$this->show_pointoftax_date					= isset($conf->global->INVOICE_POINTOFTAX_DATE)						? $conf->global->INVOICE_POINTOFTAX_DATE					: 0;
			$this->chq_num								= isset($conf->global->FACTURE_CHQ_NUMBER)							? $conf->global->FACTURE_CHQ_NUMBER							: 0;
			$this->hidechq_address						= isset($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)					? $conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS					: 0;
			$this->rib_num								= isset($conf->global->FACTURE_RIB_NUMBER)							? $conf->global->FACTURE_RIB_NUMBER							: 0;
			$this->draft_watermark						= isset($conf->global->FACTURE_DRAFT_WATERMARK)						? $conf->global->FACTURE_DRAFT_WATERMARK					: '';
			$this->text_TVA_auto						= isset($conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO)			? $conf->global->INFRASPLUS_PDF_FREETEXT_TVA_AUTO			: 0;
			$this->multi_files							= isset($conf->global->INFRASPLUS_PDF_MULTI_FILES)					? $conf->global->INFRASPLUS_PDF_MULTI_FILES					: 0;
			$this->font									= 'centurygothic';	// isset($conf->global->INFRASPLUS_PDF_FONT)							? $conf->global->INFRASPLUS_PDF_FONT : 'Helvetica';
			$this->headertxtcolor						= isset($conf->global->INFRASPLUS_PDF_HEADER_TEXT_COLOR)			? $conf->global->INFRASPLUS_PDF_HEADER_TEXT_COLOR			: 0;
			$this->headertxtcolor						= explode(',', $this->headertxtcolor);
			$this->bodytxtcolor							= isset($conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR)				? $conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR				: 0;
			$this->bodytxtcolor							= explode(',', $this->bodytxtcolor);
			$this->small_head2							= 1;	// isset($conf->global->INFRASPLUS_PDF_SMALL_HEAD_2)					? $conf->global->INFRASPLUS_PDF_SMALL_HEAD_2 : 0;
			$this->title_size							= isset($conf->global->INFRASPLUS_PDF_TITLE_SIZE)					? $conf->global->INFRASPLUS_PDF_TITLE_SIZE					: 2;
			$this->height_top_table						= isset($conf->global->INFRASPLUS_PDF_HEIGHT_TOP_TABLE)				? $conf->global->INFRASPLUS_PDF_HEIGHT_TOP_TABLE			: 4;
			$this->hide_top_table						= 1;	// isset($conf->global->INFRASPLUS_PDF_HIDE_TOP_TABLE)				? $conf->global->INFRASPLUS_PDF_HIDE_TOP_TABLE : 0;
			$this->bg_color								= isset($conf->global->INFRASPLUS_PDF_BACKGROUND_COLOR)				? $conf->global->INFRASPLUS_PDF_BACKGROUND_COLOR			: '';
			$this->txtcolor								= explode(',', pdf_InfraSPlus_txt_color($this->bg_color));
			$this->bg_color								= explode(',', $this->bg_color);
			$this->title_bg								= isset($conf->global->INFRASPLUS_PDF_TITLE_BG)						? $conf->global->INFRASPLUS_PDF_TITLE_BG					: 0;
			$this->dates_br								= 1;	// isset($conf->global->INFRASPLUS_PDF_DATES_BR)						? $conf->global->INFRASPLUS_PDF_DATES_BR : 0;
			$this->add_creator_in_header				= 0;	// isset($conf->global->INFRASPLUS_PDF_CREATOR_IN_HEADER)			? $conf->global->INFRASPLUS_PDF_CREATOR_IN_HEADER : 0;
			$this->tblLineW								= isset($conf->global->INFRASPLUS_PDF_TBL_LINE_WIDTH)				? $conf->global->INFRASPLUS_PDF_TBL_LINE_WIDTH				: 0.2;
			$this->tblLineDash							= isset($conf->global->INFRASPLUS_PDF_TBL_LINE_DASH)				? $conf->global->INFRASPLUS_PDF_TBL_LINE_DASH				: '0';
			$this->tblLineColor							= isset($conf->global->INFRASPLUS_PDF_TBL_LINE_COLOR)				? $conf->global->INFRASPLUS_PDF_TBL_LINE_COLOR				: '';
			$this->showtblline							= 0;	// $this->tblLineColor == '255, 255, 255' ? 0 : 1;
			$this->tblLineColor							= explode(',', $this->tblLineColor);
			$this->horLineColor							= isset($conf->global->INFRASPLUS_PDF_HOR_LINE_COLOR)				? $conf->global->INFRASPLUS_PDF_HOR_LINE_COLOR				: '';
			$this->horLineColor							= explode(',', $this->horLineColor);
			$this->desc_full_line						= isset($conf->global->INFRASPLUS_PDF_DESC_FULL_LINE)				? $conf->global->INFRASPLUS_PDF_DESC_FULL_LINE				: 0;
			$this->larg_ref								= 5;
			$this->larg_qty								= 7;
			$this->larg_unit							= 8;
			$this->larg_updisc							= 12;
			$this->larg_totalht							= 14;
			$this->force_align_left_ref					= isset($conf->global->INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF)			? $conf->global->INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF		: 0;
			$this->num_ref								= 1;
			$this->num_desc								= 2;
			$this->num_updisc							= 3;
			$this->num_qty								= 4;
			$this->num_unit								= 5;
			$this->num_totalht							= 6;
			$this->show_qty_prod_tot					= isset($conf->global->INFRASPLUS_PDF_SHOW_QTY_PROD_TOT)			? $conf->global->INFRASPLUS_PDF_SHOW_QTY_PROD_TOT			: 0;
			$this->show_outstandings					= isset($conf->global->INFRASPLUS_PDF_SHOW_OUTSTDBILL)				? $conf->global->INFRASPLUS_PDF_SHOW_OUTSTDBILL				: 0;
			$this->signLineW							= isset($conf->global->INFRASPLUS_PDF_SIGN_LINE_WIDTH)				? $conf->global->INFRASPLUS_PDF_SIGN_LINE_WIDTH				: 0.2;
			$this->signLineDash							= isset($conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH)				? $conf->global->INFRASPLUS_PDF_SIGN_LINE_DASH				: '0';
			$this->signLineColor						= isset($conf->global->INFRASPLUS_PDF_SIGN_LINE_COLOR)				? $conf->global->INFRASPLUS_PDF_SIGN_LINE_COLOR				: '';
			$this->signLineColor						= explode(',', $this->signLineColor);
			$this->type_foot							= isset($conf->global->INFRASPLUS_PDF_TYPE_FOOT)					? $conf->global->INFRASPLUS_PDF_TYPE_FOOT					: '0000';
			$this->hidepagenum							= 1;
			$this->wpicturefoot							= isset($conf->global->INFRASPLUS_PDF_PICTURE_FOOT_WIDTH)			? $conf->global->INFRASPLUS_PDF_PICTURE_FOOT_WIDTH			: 188;
			$this->hpicturefoot							= isset($conf->global->INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT)			? $conf->global->INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT			: 12;
			$this->maxsizeimgfoot						= array('largeur'=>$this->wpicturefoot, 'hauteur'=>$this->hpicturefoot);
			$this->linkpictureurl						= isset($conf->global->INFRASPLUS_PDF_LINK_PICTURE_URL)				? $conf->global->INFRASPLUS_PDF_LINK_PICTURE_URL			: '';
			$this->alpha								= 0.2;
			$this->exftxtcolor							= isset($conf->global->INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR)			? $conf->global->INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR		: 0;
			$this->exftxtcolor							= explode(',', $this->exftxtcolor);
			$this->exfltxtcolor							= isset($conf->global->INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR)		? $conf->global->INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR		: 0;
			$this->exfltxtcolor							= explode(',', $this->exfltxtcolor);
			$this->option_logo							= 0;	// Display logo
			$this->option_tva							= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg						= 1;	// Display payment mode
			$this->option_condreg						= 1;	// Display payment terms
			$this->option_codeproduitservice			= 1;	// Display product-service code
			$this->option_multilang						= 1;	// Available in several languages
			$this->option_escompte						= 1;	// Displays if there has been a discount
			$this->option_credit_note					= 1;	// Support credit notes
			$this->option_freetext						= 1;	// Support add of a personalised text
			$this->option_draft_watermark				= 1;	// Support add of a watermark on drafts
		}

		/********************************************
		*	Function to build pdf onto disk
		*
		*	@param		Object		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int			$hidedetails		Do not show line details (inutilisée ! laissé pour la compatibilité)
		*	@param		int			$hidedesc			Do not show desc
		*	@param		int			$hideref			Do not show ref
		*	@return     int             				1=OK, 0=KO
		********************************************/
		public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
		{
			global $user, $langs, $conf, $db, $hookmanager, $nblignes;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs))							$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))							$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$this->sign												= 1;
			if ($object->type == 2 && !empty($this->credit_note))	$this->sign						= -1;
			$filesufixe												= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_FT') ? '' : '_FT';
			$baseDir												= !empty($conf->facture->multidir_output[$conf->entity]) ? $conf->facture->multidir_output[$conf->entity] : $conf->facture->dir_output;

			if ($baseDir) {
				$object->fetch_thirdparty();
				// Use of multicurrency for this document
				$this->use_multicurrency	= (!empty($conf->multicurrency->enabled) && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? 1 : 0;
				$this->paid					= $object->getSommePaiement($this->use_multicurrency ? 1 : 0);
				$this->credit_notes			= $object->getSumCreditNotesUsed($this->use_multicurrency ? 1 : 0);	// Warning, this also include excess received
				$this->deposits				= $object->getSumDepositsUsed($this->use_multicurrency ? 1 : 0);
				if (!empty($this->show_ExtraFieldsLines)) {
					$extrafieldsline	= new ExtraFields($db);
					$extralabelsline	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
				}
				// Definition of $dir and $file
				if ($object->specimen) {
					$this->show_ExtraFieldsLines	= '';
					$dir							= $baseDir;
					$file							= $dir.'/SPECIMEN.pdf';
				}
				else {
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/'.$objectref;
					$file		= $dir.'/'.$objectref.$filesufixe.'.pdf';
				}
				if (! file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir", $dir);
						return 0;
					}
				}
				if (file_exists($dir)) {
					if (! is_object($hookmanager))	// Add pdfgeneration hook
					{
						include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
						$hookmanager	= new HookManager($db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters							= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook							= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
					$this->logo							= $hookmanager->resArray['logo'];
					$this->adr							= $hookmanager->resArray['adr'];
					$this->adrlivr						= $hookmanager->resArray['adrlivr'];
					$this->listfreet					= $hookmanager->resArray['listfreet'];
					$this->listnotep					= $hookmanager->resArray['listnotep'];
					$this->pied							= $hookmanager->resArray['pied'];
					$this->CGV							= '';	// $hookmanager->resArray['cgv'];
					$this->with_picture					= 0;	// $hookmanager->resArray['hidepict'];
					$hidedesc							= 1;	// $hookmanager->resArray['hidedesc'];
					$this->adrfact						= $hookmanager->resArray['adrfact'];
					$this->showwvccchk					= $hookmanager->resArray['showwvccchk'];
					$this->show_tot_disc				= $hookmanager->resArray['showtotdisc'];
					$this->show_tva_btp					= $hookmanager->resArray['showtvabtp'];
					$nblignes							= count($object->lines);	// Set nblignes with the new facture lines content after hook
					if (!empty($this->show_ref_col))	$hideref = 1;	// Comme on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information
					$nbpayments 						= count($object->getListOfPayments());
					// Create pdf instance
					$pdf								= pdf_getInstance($this->format);
					$default_font_size					= pdf_getPDFFontSize($outputlangs);																								// Must be after pdf_getInstance
					$pdf->SetAutoPageBreak(1, 0);
					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}
					$pdf->SetFont($this->font);
					// reduce the top margin before ol / il tag
					$tagvs					= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities("PdfInvoiceTitle"));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfInvoiceTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
					// New page
					$pdf->AddPage();
					$pagenb					= 1;
					// Default PDF parameters
					$this->stdLineW			= 0.2; // épaisseur par défaut dans TCPDF = 0.2
					$this->stdLineDash		= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->stdLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->stdLineColor		= array(0, 0, 0);
					$this->stdLineStyle		= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->bgLineStyle		= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->tblLineStyle		= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->horLineStyle		= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->signLineStyle	= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor(0, 0, 0);
					$pdf->SetFont('', '', $default_font_size - 3);
					// First loop on each lines to prepare calculs and variables
					$this->TotRem		= 0;
					$this->hasService	= 0;
					$this->hasProduct	= 0;
					$this->nbrProdTot	= 0;
					$this->nbrProdDif	= array();
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$this->hasProduct	+= $object->lines[$i]->product_type == 0 ? 1 : 0;	// Products
						$this->hasService	+= $object->lines[$i]->product_type == 1 ? 1 : 0;	// Services
						// Positionne $this->atleastonediscount si on a au moins une remise
						if ($object->lines[$i]->remise_percent) {
							$this->atleastonediscount++;
							if ($this->show_tot_disc)	$this->TotRem	+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht);
						}
						// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
						if ($this->use_multicurrency)	$tvaligne		= doubleval($object->lines[$i]->multicurrency_total_tva);
						else							$tvaligne		= doubleval($object->lines[$i]->total_tva);
						$localtax1ligne					= $object->lines[$i]->total_localtax1;
						$localtax2ligne					= $object->lines[$i]->total_localtax2;
						$localtax1_rate					= $object->lines[$i]->localtax1_tx;
						$localtax2_rate					= $object->lines[$i]->localtax2_tx;
						$localtax1_type					= $object->lines[$i]->localtax1_type;
						$localtax2_type					= $object->lines[$i]->localtax2_type;
						if ($object->remise_percent)	$tvaligne		-= ($tvaligne * $object->remise_percent) / 100;
						if ($object->remise_percent)	$localtax1ligne	-= ($localtax1ligne * $object->remise_percent) / 100;
						if ($object->remise_percent)	$localtax2ligne	-= ($localtax2ligne * $object->remise_percent) / 100;
						$vatrate						= (string) $object->lines[$i]->tva_tx;
						// Retrieve type from database for backward compatibility with old records
						if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
							&& (!empty($localtax1_rate) || !empty($localtax2_rate))) // and there is local tax
						{
							$localtaxtmp_array	= getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $this->emetteur);
							$localtax1_type		= isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
							$localtax2_type		= isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
						}
						// retrieve global local tax
						if ($localtax1_type && $localtax1ligne != 0)		$this->localtax1[$localtax1_type][$localtax1_rate]	+= $localtax1ligne;
						if ($localtax2_type && $localtax2ligne != 0)		$this->localtax2[$localtax2_type][$localtax2_rate]	+= $localtax2ligne;
						if (($object->lines[$i]->info_bits & 0x01) == 0x01)	$vatrate											.= '*';
						if (! isset($this->tva[$vatrate])) 					$this->tva[$vatrate]								= 0;
						if ($object->lines[$i]->product_type != 9 && $object->lines[$i]->special_code != 501028) {
							if ($this->use_multicurrency && !empty($object->lines[$i]->TTotal_tva_multicurrency))
								foreach ($object->lines[$i]->TTotal_tva_multicurrency as $vatrate => $tvaligne)	$this->tva[$vatrate]	+= $tvaligne;
							elseif (!empty($object->lines[$i]->TTotal_tva))
								foreach ($object->lines[$i]->TTotal_tva as $vatrate => $tvaligne)				$this->tva[$vatrate]	+= $tvaligne;
							else
								if(!empty($tvaligne))															$this->tva[$vatrate]	+= $tvaligne;
						}
						if ($this->show_qty_prod_tot && $object->lines[$i]->product_type == 0) {
							$this->nbrProdTot													+= $object->lines[$i]->qty;
							if (!in_array($object->lines[$i]->product_ref, $this->nbrProdDif))	$this->nbrProdDif[]	= $object->lines[$i]->product_ref;
						}
					}
					// Define width and position of notes frames
					$this->larg_util_txt	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + 2);
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt		= $this->marge_gauche + 1;
					// Define width and position of main table columns
					$this->larg_desc												= $this->larg_util_cadre - ($this->larg_qty + $this->larg_totalht +
																						$this->larg_unit + $this->larg_updisc + $this->larg_ref); // Largeur variable suivant la place restante
					$this->tableau													= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
																							'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
																							'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
																							'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
																							'updisc'	=> array('col' => $this->num_updisc,	'larg' => $this->larg_updisc,	'posx' => 0),
																							'totalht'	=> array('col' => $this->num_totalht,	'larg' => $this->larg_totalht,	'posx' => 0),
																							);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)	$this->largcol1			= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 2)	$this->largcol2		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 3)	$this->largcol3		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 4)	$this->largcol4		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 5)	$this->largcol5		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 6)	$this->largcol6		= $ncol_array['larg'];
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1 + $this->largcol1;
					$this->posxcol3		= $this->posxcol2 + $this->largcol2;
					$this->posxcol4		= $this->posxcol3 + $this->largcol3;
					$this->posxcol5		= $this->posxcol4 + $this->largcol4;
					$this->posxcol6		= $this->posxcol5 + $this->largcol5;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->tableau[$ncol]['posx']	= $this->posxcol1;
						elseif ($ncol_array['col'] == 2)	$this->tableau[$ncol]['posx']	= $this->posxcol2;
						elseif ($ncol_array['col'] == 3)	$this->tableau[$ncol]['posx']	= $this->posxcol3;
						elseif ($ncol_array['col'] == 4)	$this->tableau[$ncol]['posx']	= $this->posxcol4;
						elseif ($ncol_array['col'] == 5)	$this->tableau[$ncol]['posx']	= $this->posxcol5;
						elseif ($ncol_array['col'] == 6)	$this->tableau[$ncol]['posx']	= $this->posxcol6;
					}
					// Define width and position of secondary tables columns
					$this->larg_tabtotal										= $this->larg_updisc + $this->larg_qty + $this->larg_unit + $this->larg_totalht;
					$this->larg_tabinfo											= $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					$this->posxtabtotal											= $this->marge_gauche;
					// Calculs de positions
					$this->tab_hl												= 4;
					$head														= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead												= $head["totalhead"];
					$hauteurcadre												= $head["hauteurcadre"];
					$tab_top													= $hauteurhead + 5;
					$tab_top_newpage											= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table											= $this->height_top_table + $this->tab_hl * 0.5;
					$ht_colinfo													= $this->_tableau_info($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht_coltotal												= $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1);
					if ($this->paid || $this->credit_notes || $this->deposits)	$ht_colpay	+= $this->_tableau_versements($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$heightforinfotot											= $ht_colinfo + $ht_coltotal + $ht_colpay;
					$heightforinfotot											+= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 1, 1, $this->horLineStyle);
					$heightforfooter											= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note												= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, $this->first_page_empty);
					$tab_top													+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY														= $tab_top + $this->ht_top_table + ($this->tab_hl * 0.5);
					// Loop on each lines
					for ($i = 0 ; $i < $nblignes ; $i++) {
						if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i, 1)))	continue;	// COmposants d'ouvrage Inovea masqués
						$curY														= $nexY;
						$pdf->SetFont('', '', $default_font_size - 4);   // Into loop to work with multipage
						$pdf->SetTextColor(0, 0, 0);
						if (empty($this->hide_top_table))							$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table);
						else														$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore												= $pdf->getPage();
						$showpricebeforepagebreak									= 1;
						// Hauteur de la référence
						$this->heightline	= $this->tab_hl;
						$extraDet	= '';
						// Description of product line
						$pdf->startTransaction();
						pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hidedesc, 0, $extraDet);
						$pageposafter	= $pdf->getPage();
						$pageposdesc	= $pdf->getPage();
						$posyafter		= $pdf->GetY();
						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$pdf->rollbackTransaction(true);
							$pageposafter	= $pageposbefore;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
							$pageposdesc	= $pdf->getPage();
							pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hidedesc, 0, $extraDet);
							$pageposafter	= $pdf->getPage();
							$posyafter		= $pdf->GetY();
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot)))	// There is no space left for total+free text
							{
								if ($i == ($nblignes - 1))	// No more lines, and no space left to show total, so we create a new page
								{
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposafter + 1);
								}
							}
							else	$showpricebeforepagebreak	= 0;
						}
						elseif ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {
							$pdf->rollbackTransaction(true);
							$pageposafter	= $pageposbefore;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
							$pageposdesc	= $pdf->getPage();
							pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hidedesc, 0, $extraDet);
							$pageposafter	= $pdf->getPage();
							$posyafter		= $pdf->GetY();
							if ($i == ($nblignes - 1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposafter + 1);
							}
						}
						else			$pdf->commitTransaction();	// No pagebreak
						$nexY			= $pdf->GetY();
						$pageposafter	= $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							if ($curY > ($this->page_hauteur - $heightforfooter - $this->tab_hl)) {
								$pdf->setPage($pageposafter);
								$curY	= $tab_top_newpage + ($this->hide_top_table ? 0 : $this->ht_top_table);
							}
							else	$pdf->setPage($pageposdesc);
						}
						$pdf->SetFont('', '', $default_font_size - 4);   // On repositionne la police par defaut
						// N°
						$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $i + 1, '', 'R', 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Quantity
						$qty	= pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Unit
						$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->MultiCell($this->tableau['unit']['larg'], $this->heightline, $unit, '', 'L', 0, 1, $this->tableau['unit']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Discounted price
						$up_disc	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->tableau['updisc']['larg'], $this->heightline, $up_disc, '', 'R', 0, 1, $this->tableau['updisc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Total line
						$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Add dash or space between line
						if ($i < ($nblignes - 1)) {
							$pdf->setPage($pageposafter);
							$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
							$nexY	+= 2;
						}
						// Detect if some page were added automatically and output _tableau for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1)				$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							else							$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1)				$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							else							$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							// New page
							$pdf->AddPage();
							$pagenb++;
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							$nexY							= $tab_top_newpage + ($this->hide_top_table ? 0 : $this->ht_top_table);
						}
					}
					$bottomlasttab		= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1)	$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					else				$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $pagenb);
					$posyinfo			= $this->_tableau_info($pdf, $object, $bottomlasttab + $ht_coltotal, $outputlangs, 0);
					$posytot			= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$posypay			= ($this->paid || $this->credit_notes || $this->deposits) ?	$this->_tableau_versements($pdf, $object, $posyinfo, $outputlangs, 0) : $posyinfo;
					$posy				= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posypay, $outputlangs, $this->emetteur, $this->listfreet, 1, 0, $this->horLineStyle);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters			= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook			= $hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
					if ($reshook < 0) {
						$this->error	= $hookmanager->error;
						$this->errors	= $hookmanager->errors;
					}
					if (!empty($this->main_umask))	@chmod($file, octdec($this->main_umask));
					$this->result					= array('fullpath' => $file);
					return 1;   // Pas d'erreur
				}
				else {
					$this->error=$outputlangs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}
			else {
				$this->error=$outputlangs->trans("ErrorConstantNotDefined","FAC_OUTPUTDIR");
				return 0;
			}
		}

		/********************************************
		*	Show top header of page.
		*
		*	@param		PDF			$pdf     		Object PDF
		*	@param		Object		$object     	Object to show
		*	@param		int	    	$showaddress    0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		string		$titlekey		Translation key to show as title of document
		*	@return		array		$hauteurhead	'totalhead'		= hight of header
		*											'hauteurcadre	= hight of frame
		********************************************/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "PdfInvoiceTitle") {
			global $conf, $db, $hookmanager;

			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$dimCadres			= array ('S' => $this->larg_util_cadre, 'R' => $this->larg_util_cadre);
			$w					= $this->larg_util_cadre;
			$align				= 'L';
			$posy				= $this->marge_haute;
			$posx				= $this->marge_gauche;
			$title				= $outputlangs->transnoentities($titlekey);
			$pdf->MultiCell($w, $this->tab_hl * 2, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size - 3);
			$posy				+= $this->tab_hl;
			$styleBC			= array('position'		=> '',
										'align'			=> 'C',
										'stretch'		=> false,
										'fitwidth'		=> true,
										'cellfitalign'	=> 'R',
										'border'		=> false,
										'hpadding'		=> 'auto',
										'vpadding'		=> 'auto',
										'fgcolor'		=> array(0, 0, 0),
										'bgcolor'		=> false,
										'text'			=> false,
										'font'			=> $this->font,
										'fontsize'		=> 8,
										'stretchtext'	=> 4
										);
			$pdf->write1DBarcode($object->id, 'C128', $this->marge_gauche + ($w / 2), $posy, '', '', 0.4, $styleBC, 'B');
			$posyBC				= $pdf->GetY();
			$txtref				= $outputlangs->transnoentities('Ref')." : ".$outputlangs->convToOutputCharset($object->ref);
			if ($object->statut == Facture::STATUS_DRAFT) {
				$pdf->SetTextColor(0, 0, 0);
				$txtref	.=' - '.$outputlangs->transnoentities("NotValidated");
			}
			$objidnext	= $object->getIdReplacingInvoice('validated');
			if ($object->type == 0 && $objidnext) {
				$orep	= new Facture($db);
				$orep->fetch($objidnext);
				$txtref	.= ' / '.$outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($orep->ref);
			}
			if ($object->type == 1) {
				$orep	= new Facture($db);
				$orep->fetch($object->fk_facture_source);
				$txtref	.= ' / '.$outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($orep->ref);
			}
			if ($object->type == 2 && !empty($object->fk_facture_source)) {
				$orep	= new Facture($db);
				$orep->fetch($object->fk_facture_source);
				$txtref	.= ' / '.$outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($orep->ref);
			}
			$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 4);
			$posy	+= $this->tab_hl;
			$txtdt	= $outputlangs->transnoentities("DateInvoice")." : ".dol_print_date($object->date, "day", false, $outputlangs, true);
			if (empty($this->dates_br)) {
				if (!empty($this->show_pointoftax_date))	$txtdt	.= ' / '.$outputlangs->transnoentities("DatePointOfTax")." : ".dol_print_date($object->date_pointoftax, "day", false, $outputlangs, true);
				if ($object->type != 2)						$txtdt	.= ' / '.$outputlangs->transnoentities("DateDue")." : ".dol_print_date($object->date_lim_reglement, "day", false, $outputlangs, true);
			}
			$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			if (!empty($this->dates_br)) {
				if (!empty($this->show_pointoftax_date)) {
					$txtdt	= '';
					$posy	+= $this->tab_hl - 0.5;
					$txtdt	= $outputlangs->transnoentities("DatePointOfTax")." : ".dol_print_date($object->date_pointoftax, "day", false, $outputlangs, true);
					$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				if ($object->type != 2) {
					$txtdt	= '';
					$posy	+= $this->tab_hl - 0.5;
					$txtdt	= $outputlangs->transnoentities("DateDue")." : ".dol_print_date($object->date_lim_reglement, "day", false, $outputlangs, true);
					$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
			}
			$pdf->SetFont('', '', $default_font_size - 4);
			if ($object->ref_client) {
				$posy	+= $this->tab_hl - 0.5;
				$txtcc	= $outputlangs->transnoentities("RefCustomer")." : ".$outputlangs->convToOutputCharset($object->ref_client);
				$pdf->MultiCell($w, $this->tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			}
			if (!empty($this->add_creator_in_header)) {
				$usertmp	= pdf_InfraSPlus_creator($object, $outputlangs);
				if ($usertmp) {
					$posy		+= $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities("PDFInfraSPlusRedac")." : ".$usertmp, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
			}
			// Show list of linked objects
			$dimCadres['Y']	= ($posy + $this->tab_hl) > $posyBC ? $posy + $this->tab_hl : $posyBC;
			if ($showaddress) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'BILLING'),
										'L' => $object->getIdContact('external', 'SHIPPING')
										);
				$addresses		= array();
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', $this->adrfact, 1);
				$dimCadres['xS']	= $this->formatpage['mgauche'];
				$dimCadres['xR']	= $this->formatpage['mgauche'];
				$hauteurcadre		= pdf_InfraSPlus_writeFrame($pdf, $outputlangs, $default_font_size, $this->tab_hl, $dimCadres, $this->emetteur, $addresses, 1);
			}
			$hauteurhead	= array('totalhead'		=> $dimCadres['Y'] + $hauteurcadre,
									'hauteurcadre'	=> $hauteurcadre,
									'livrshow'		=> $addresses['livrshow']
									);
			return $hauteurhead;
		}

		/********************************************
		*	Show top small header of page.
		*
		*	@param		PDF			$pdf     		Object PDF
		*	@param		Object		$object     	Object to show
		*	@param		int	    	$showaddress    0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		string		$titlekey		Translation key to show as title of document
		*	@return		void
		********************************************/
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "PdfInvoiceTitle") {
			global $conf, $hookmanager;

			$title							= $this->emetteur->name.' '.$outputlangs->transnoentities($titlekey);
			pdf_InfraSPlus_pagesrefdate($pdf, $object, $outputlangs, $title, $this->marge_haute, $this->marge_gauche, 1);
		}

		/********************************************
		*   Show table for lines
		*
		*	@param		PDF			$pdf     		Object PDF
		*	@param  	Object		$object     	Object to show
		*	@param		float		$tab_top		Top position of table
		*	@param		float		$tab_height		Height of table (rectangle)
		*	@param		Translate	$outputlangs	Langs object
		*	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int			$hidebottom		Hide bottom bar of array
		*	@return		void
		********************************************/
		protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb)
		{
			global $conf;

			// Force to disable hidetop and hidebottom
			$hidebottom			= 0;
			if ($hidetop)		$hidetop	= -1;
			$currency			= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 4);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				if ($pagenb == 1) {
					$infocurrency	= $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
					$pdf->MultiCell($pdf->GetStringWidth($infocurrency) + 3, 2, $infocurrency, '', 'R', 0, 1, $this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($infocurrency) + 3), $tab_top - $this->tab_hl, true, 0, 0, false, 0, 'M', false);
				}
			}
			if ($object->statut == Facture::STATUS_DRAFT && (!empty($this->draft_watermark))) {
				if (empty($hidetop))	pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + $this->ht_top_table + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				else					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				$pdf->SetTextColor(0, 0, 0);
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 3);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->line($this->marge_gauche, $tab_top, $this->marge_gauche + $this->larg_util_cadre, $tab_top, $this->horLineStyle);
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Designation"), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, 'N°', '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Qty"), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Unit"), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['updisc']['larg'], $this->ht_top_table, $outputlangs->transnoentities("PDFInfraSPlusDiscountedPrice"), '', 'C', 0, 1, $this->tableau['updisc']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities("TotalHT"), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->line($this->marge_gauche, $tab_top + $this->ht_top_table, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $this->ht_top_table, $this->horLineStyle);
			}
		}

		/********************************************
		*	Show miscellaneous information (payment mode, payment term, ...)
		*
		*	@param		PDF			$pdf     		Object PDF
		*	@param		Object		$object			Object to show
		*	@param		int			$posy			Y
		*	@param		Translate	$outputlangs	Langs object
		*	@return		int			$posy			Position pour suite
		********************************************/
		protected function _tableau_info(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			global $conf, $db;

			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$posytabinfo		= $posy + 1;
			$tabinfo_hl			= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 4);
			$pdf->SetTextColor(0, 0, 0);
			$larg_tabinfo		= $this->larg_tabinfo;
			$larg_col1info		= 40;
			$larg_col2info		= $larg_tabinfo - $larg_col1info;
			$posxtabinfo		= $this->marge_gauche;
			$posxcol2info		= $posxtabinfo + $larg_col1info;
			// VAT statements
			if ($this->text_TVA_auto) {
				$statements	= pdf_InfraSPlus_VAT_auto($object, $this->emetteur, $object->thirdparty, $this->hasService, $this->hasProduct, $this->show_tva_btp);
				if (is_array($statements)) {
					$pdf->SetFont('', '', $default_font_size - 2);
					if (!empty($statements['F']))	$posytabinfo	= pdf_InfraSPlus_write_VAT_mention($pdf, $object, $outputlangs, $statements['F'], $larg_tabinfo, $tabinfo_hl, $posxtabinfo, $posytabinfo);
					if (!empty($statements['S']))	$posytabinfo	= pdf_InfraSPlus_write_VAT_mention($pdf, $object, $outputlangs, $statements['S'], $larg_tabinfo, $tabinfo_hl, $posxtabinfo, $posytabinfo);
					if (!empty($statements['P']))	$posytabinfo	= pdf_InfraSPlus_write_VAT_mention($pdf, $object, $outputlangs, $statements['P'], $larg_tabinfo, $tabinfo_hl, $posxtabinfo, $posytabinfo);
					if (!empty($statements['B']))	$posytabinfo	= pdf_InfraSPlus_write_VAT_mention($pdf, $object, $outputlangs, $statements['B'], $larg_tabinfo, $tabinfo_hl, $posxtabinfo, $posytabinfo);
				}
			}
			// Show Qty of products and number of different product for the document
			if ($this->show_qty_prod_tot && $this->nbrProdTot > 0) {
				$nbrProd		= $outputlangs->transnoentities("PDFInfraSPlusQtyProd", $this->nbrProdTot, count($this->nbrProdDif));
				$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $nbrProd, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY();
			}
			// Show Outstandings
			if (!empty($this->show_outstandings)) {
				include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
				$titre				= $outputlangs->transnoentities("CurrentOutstandingBill").' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$outstandingBills	= $object->thirdparty->getOutstandingBills();
				$outstandingAmount	= pdf_InfraSPlus_price($object, $outstandingBills['opened'], $outputlangs);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $outstandingAmount, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo		= $pdf->GetY();
			}
			// Show total discount
			if ($this->show_tot_disc && $this->atleastonediscount) {
				$titre			= $outputlangs->transnoentities("PDFInfraSPlusTotRem").' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$total_ht		= $this->use_multicurrency ? $object->multicurrency_total_ht : $object->total_ht;
				$total_ttc		= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc;
				$TotRem			= pdf_InfraSPlus_price($object, $this->TotRem, $outputlangs, 1).' '.$outputlangs->transnoentities(($this->only_ht ? "HT" : "TTC"));
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $TotRem, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY();
			}
			// Show payments conditions
			if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement)) {
				$titre			= $outputlangs->transnoentities("PaymentConditions").' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$lib_condition_paiement	= $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) != ('PaymentCondition'.$object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc ? $object->cond_reglement_doc : $object->cond_reglement_label);
				$lib_condition_paiement	= str_replace('\n', "\n", $lib_condition_paiement);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_condition_paiement, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY();
			}
			if ($object->type != 2) {
				// Check a payment mode is defined
				if (empty($object->mode_reglement_code) && empty($this->chq_num) && empty($this->rib_num))	$this->error = $outputlangs->transnoentities("ErrorNoPaiementModeConfigured");
				// Avoid having any valid PDF with setup that is not complete
				elseif (($object->mode_reglement_code == 'CHQ' && empty($this->chq_num) && empty($object->fk_account) && empty($object->fk_bank))
					|| ($object->mode_reglement_code == 'VIR' && empty($this->rib_num) && empty($object->fk_account) && empty($object->fk_bank))) {
					$pdf->SetTextColor(0, 0, 0);
					$this->error = $outputlangs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup", $object->mode_reglement_code);
					$pdf->MultiCell($larg_col1info, $tabinfo_hl, $this->error, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					$posy=$pdf->GetY();
				}
				// Show payment mode
				if ($object->mode_reglement_code && $object->mode_reglement_code != 'CHQ' && $object->mode_reglement_code != 'VIR') {
					$titre			= $outputlangs->transnoentities("PaymentMode").' : ';
					$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					$lib_mode_reg	= $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) != ('PaymentType'.$object->mode_reglement_code) ? $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
					$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_mode_reg, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					$posytabinfo	= $pdf->GetY();
				}
				// Show payment mode CHQ
				if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ') {
					// Si mode reglement non force ou si force a CHQ
					if (!empty($this->chq_num)) {
						if ($this->chq_num > 0) {
							$account							= new Account($db);
							$account->fetch($this->chq_num);
							$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
							if (empty($this->hidechq_address))	$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $account->proprio), '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
							else								$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$account->proprio, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
							$posytabinfo						= $pdf->GetY() + 1;
							if (empty($this->hidechq_address)) {
								$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
								$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->convToOutputCharset($account->owner_address), '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
								$posytabinfo	= $pdf->GetY() + 1;
							}
						}
						if ($this->chq_num == -1) {
							$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
							if (empty($this->hidechq_address))	$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $this->emetteur->name), '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
							else								$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$this->emetteur->name, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
							$posytabinfo						= $pdf->GetY() + 1;
							if (empty($this->hidechq_address)) {
								$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
								$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
								$posytabinfo	= $pdf->GetY() + 1;
							}
						}
					}
				}
				// If payment mode not forced or forced to VIR, show payment with BAN
				if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR') {
					if ($object->fk_account > 0 || $object->fk_bank > 0 || !empty($this->rib_num)) {
						$bankid						= (empty($object->fk_account) ? $this->rib_num : $object->fk_account);
						if ($object->fk_bank > 0)	$bankid	= $object->fk_bank;   // For backward compatibility when object->fk_account is forced with object->fk_bank
						$account					= new Account($db);
						$account->fetch($bankid);
						$pdf->SetLineStyle($this->stdLineStyle);
						$posytabinfo				= pdf_infrasplus_bank($pdf, $outputlangs, $posxtabinfo, $posytabinfo, $larg_tabinfo, $tabinfo_hl, $account, $this->bank_only_number, $default_font_size);
						$posytabinfo				+= 1;
					}
				}
			}
			if ($calculseul) {
				$heightforinfo	= $posytabinfo - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforinfo;
			}
			else {
				$pdf->commitTransaction();
				return $posytabinfo;
			}
		}

		/********************************************
		*	Show total to pay
		*
		*	@param		PDF			$pdf            Object PDF
		*	@param		Facture		$object         Object invoice
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@return		int							Position pour suite
		********************************************/
		protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			global $conf;

			$pdf->startTransaction();
			$default_font_size				= pdf_getPDFFontSize($outputlangs);
			$posytabtot						= $posy + 1;
			$tabtot_hl						= $this->tab_hl - 1;
			$pdf->SetFont('', '', $default_font_size - 4);
			$pdf->SetTextColor(0, 0, 0);
			// Tableau total
			$larg_tabtotal					= $this->larg_tabtotal;
			$larg_col2total					= $this->larg_totalht;
			$larg_col1total					= $larg_tabtotal - $larg_col2total;
			$posxtabtotal					= $this->page_largeur - $this->marge_droite - $this->larg_tabtotal;
			$posxcol2total					= $posxtabtotal + $larg_col1total;
			$index							= 0;
			// Total HT
			$this->atleastoneratenotnull	= 0;
			$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index) - 0.7, $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index) - 0.7, $this->horLineStyle);
			$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
			$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalHT'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
			$total_ht	= $this->use_multicurrency ? $object->multicurrency_total_ht : $object->total_ht;
			$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ht + (!empty($object->remise) ? $object->remise : 0), $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
			// Show VAT by rates and total
			$tvaisnull	= ((!empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (!empty($this->hide_vat_ifnull) && $tvaisnull) {
				// Nothing to do
			}
			//Local tax 1 before VAT
			foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
				if (in_array((string) $localtax_type, array('1', '3', '5'))) continue;
				foreach ($localtax_rate as $tvakey => $tvaval) {
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						$index++;
						$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
						$tvacompl	= '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey		= str_replace('*', '', $tvakey);
							$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat	= $outputlangs->transcountrynoentities("TotalLT1", $this->emetteur->country_code).' ';
						$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					}
				}
			}
			//Local tax 2 before VAT
			foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
				if (in_array((string) $localtax_type, array('1', '3', '5'))) continue;
				foreach ($localtax_rate as $tvakey => $tvaval) {
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						$index++;
						$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
						$tvacompl	= '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey		= str_replace('*', '', $tvakey);
							$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat	= $outputlangs->transcountrynoentities("TotalLT2", $this->emetteur->country_code).' ';
						$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					}
				}
			}
			// VAT
			foreach ($this->tva as $tvakey => $tvaval) {
				if ($tvakey > 0)    // On affiche pas taux 0
				{
					$this->atleastoneratenotnull++;
					$index++;
					$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
					$tvacompl	= '';
					if (preg_match('/\*/', $tvakey)) {
						$tvakey	= str_replace('*', '', $tvakey);
						$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
					}
					$totalvat	= $outputlangs->transcountrynoentities("TotalVAT", $this->emetteur->country_code).' ';
					$totalvat	.= vatrate($tvakey, 1).$tvacompl;
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				}
			}
			//Local tax 1 after VAT
			foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
				if (in_array((string) $localtax_type, array('2', '4', '6'))) continue;
				foreach ($localtax_rate as $tvakey => $tvaval) {
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						$index++;
						$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
						$tvacompl	= '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey		= str_replace('*', '', $tvakey);
							$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat	= $outputlangs->transcountrynoentities("TotalLT1", $this->emetteur->country_code).' ';
						$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					}
				}
			}
			//Local tax 2 after VAT
			foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
				if (in_array((string) $localtax_type, array('2', '4', '6'))) continue;
				foreach ($localtax_rate as $tvakey => $tvaval) {
					$index++;
					$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
					$tvacompl	= '';
					if (preg_match('/\*/', $tvakey)) {
						$tvakey		= str_replace('*', '', $tvakey);
						$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
					}
					$totalvat	= $outputlangs->transcountrynoentities("TotalLT2", $this->emetteur->country_code).' ';
					$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				}
			}
			// Revenue stamp
			if (price2num($object->revenuestamp) != 0) {
				$index++;
				$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities("RevenueStamp"), '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->sign * $object->revenuestamp, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
			}
			$index++;
			// Total TTC
			$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
			$pdf->SetFont('', 'B', $default_font_size - 4);
			$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities("TotalTTC"), '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
			$total_ttc	= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc;
			$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ttc, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 4);
			$this->resteapayer	= price2num((! $this->only_ht ? $actual_ttc : $actual_ht) - $this->paid - $this->credit_notes - $this->deposits, 'MT');
			if ($object->paye)	$this->resteapayer	= 0;
			if ($this->paid > 0 || $this->credit_notes > 0 || $this->deposits > 0) {
				// Already paid + Deposits
				$index++;
				$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities("Paid"), '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->paid + $this->deposits, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				// Credit note
				if ($this->credit_notes) {
					$index++;
					$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
					$labeltouse	= ($outputlangs->transnoentities("CreditNotesOrExcessReceived") != "CreditNotesOrExcessReceived") ? $outputlangs->transnoentities("CreditNotesOrExcessReceived") : $outputlangs->transnoentities("CreditNotes");
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $labeltouse, '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->credit_notes, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				}
				// Escompte
				if ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
					$index++;
					$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities("EscompteOfferedShort"), '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ttc - $this->paid - $this->credit_notes - $this->deposits, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					$this->resteapayer	= 0;
				}
				$index++;
				$pdf->line($posxtabtotal, $posytabtot + ($tabtot_hl * $index), $posxtabtotal + $larg_tabtotal, $posytabtot + ($tabtot_hl * $index), $this->horLineStyle);
				$pdf->SetFont('', 'B', $default_font_size - 4);
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities("RemainderToPay"), '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->resteapayer, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 4);
			}
			if ($this->use_multicurrency && $this->show_tot_local_cur) {
				$index++;
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities((! $this->only_ht ? "TotalTTC" : "TotalHT")).' ('.$conf->currency.')', '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				$total_loc_cur	= $this->resteapayer;
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_loc_cur, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetFont('', '', $default_font_size - 4);
			$posytabtot	= $pdf->GetY() + 1;
			if ($calculseul) {
				$heightfortot	= $posytabtot - $posy;
				$pdf->rollbackTransaction(true);
				return $heightfortot;
			}
			else {
				$pdf->commitTransaction();
				return $posytabtot;
			}
		}

		/********************************************
		*	Show payments table
		*
		*	@param		PDF			$pdf           Object PDF
		*	@param		Object		$object         Object invoice
		*	@param		int			$posy           Position y in PDF
		*	@param		Translate	$outputlangs    Object langs for output
		*	@return		int             			Position pour suite
		********************************************/
		protected function _tableau_versements(&$pdf, $object, $posy, $outputlangs, $calculseul = 0) {
			global $conf, $db;

			$pdf->startTransaction();
			$default_font_size										= pdf_getPDFFontSize($outputlangs);
			$posytabver												= $posy + 1;
			$tabver_hl												= $this->tab_hl - 1;
			$pdf->SetFont('', '', $default_font_size - 4);
			$pdf->SetTextColor(0, 0, 0);
			// Tableau total
			$larg_tabver											= $this->larg_tabinfo;
			$larg_col1ver											= ($larg_tabver / 4) - 5;
			$larg_col2ver											= $larg_tabver / 4;
			$larg_col3ver											= ($larg_tabver / 4) + 5;
			$larg_col4ver											= $larg_tabver / 4;
			$posxtabver												= $this->posxtabtotal;
			$posxcol2ver											= $posxtabver + $larg_col1ver;
			$posxcol3ver											= $posxcol2ver + $larg_col2ver;
			$posxcol4ver											= $posxcol3ver + $larg_col3ver;
			$index													= 0;
			$title													= $outputlangs->transnoentities("PaymentsAlreadyDone");
			if ($object->type == 2)									$title		= $outputlangs->transnoentities("PaymentsBackAlreadyDone");
			$pdf->MultiCell($larg_tabver, $tabver_hl, $title, '', 'L', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$index++;
			$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
			$pdf->MultiCell($larg_col1ver, $tabver_hl, $outputlangs->transnoentities("Date"), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col2ver, $tabver_hl, $outputlangs->transnoentities("Amount"), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col3ver, $tabver_hl, $outputlangs->transnoentities("Type"), '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col4ver, $tabver_hl, $outputlangs->transnoentities("Num"), '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$index++;
			$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
			// Loop on each discount available (deposits, credit notes and excess of payment included)
			$sql	= 'SELECT re.rowid, re.amount_ht, re.multicurrency_amount_ht, re.amount_tva, re.multicurrency_amount_tva,';
			$sql	.= ' re.amount_ttc, re.multicurrency_amount_ttc, re.description, re.fk_facture_source, f.type, f.datef';
			$sql	.= ' FROM '.MAIN_DB_PREFIX .'societe_remise_except as re, '.MAIN_DB_PREFIX .'facture as f';
			$sql	.= ' WHERE re.fk_facture_source = f.rowid AND re.fk_facture = '.$object->id;
			$resql	= $db->query($sql);
			if ($resql) {
				$num		= $db->num_rows($resql);
				$i			= 0;
				$invoice	= new Facture($db);
				while ($i < $num) {
					$obj						= $db->fetch_object($resql);
					$invoice->fetch($obj->fk_facture_source);
					if (! $this->only_ht)		$MntLine	= $this->use_multicurrency ? $obj->multicurrency_amount_ttc : $obj->amount_ttc;
					else						$MntLine	= $this->use_multicurrency ? $obj->multicurrency_amount_ht : $obj->amount_ht;
					if ($obj->type == 0)		$text		= $outputlangs->transnoentities("ExcessReceived");
					elseif ($obj->type == 2)	$text		= $outputlangs->transnoentities("CreditNote");
					elseif ($obj->type == 3)	$text		= $outputlangs->transnoentities("Deposit");
					else						$text		= $outputlangs->transnoentities("UnknownType");
					$pdf->MultiCell($larg_col1ver, $tabver_hl, dol_print_date($db->jdate($obj->datef), 'day', false, $outputlangs, true), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2ver, $tabver_hl, pdf_InfraSPlus_price($object, $MntLine, $outputlangs), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col3ver, $tabver_hl, $text, '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col4ver, $tabver_hl, $invoice->ref, '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
					$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
					$i++;
				}
			}
			else {
				$this->error	= $db->lasterror();
				$posytabver		= $pdf->GetY() + 1;
				if ($calculseul) {
					$heightforver	= $posytabver - $posy;
					$pdf->rollbackTransaction(true);
					return $heightforver;
				}
				else {
					$pdf->commitTransaction();
					return $posytabver;
				}
			}
			// Loop on each payment
			$sql	= 'SELECT p.datep as date, p.fk_paiement as type, p.num_paiement as num, pf.amount as amount,';
			$sql	.= ' pf.multicurrency_amount, cp.code';
			$sql	.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p';
			$sql	.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as cp ON p.fk_paiement = cp.id AND cp.entity IN ('.getEntity("c_paiement").')';
			$sql	.= ' WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = '.$object->id;
			$sql	.= ' ORDER BY p.datep';
			$resql	= $db->query($sql);
			if ($resql) {
				$num	= $db->num_rows($resql);
				$i		= 0;
				while ($i < $num) {
					$row		= $db->fetch_object($resql);
					$MntLine	= $this->use_multicurrency ? $row->multicurrency_amount : $row->amount;
					$oper		= $outputlangs->transnoentitiesnoconv("PaymentTypeShort".$row->code);
					$pdf->MultiCell($larg_col1ver, $tabver_hl, dol_print_date($db->jdate($row->date),'day', false, $outputlangs, true), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2ver, $tabver_hl, pdf_InfraSPlus_price($object, $this->sign * $MntLine, $outputlangs), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col3ver, $tabver_hl, $oper, '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col4ver, $tabver_hl, $row->num, '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
					$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
					$i++;
				}
			}
			else		$this->error	= $db->lasterror();
			$posytabver	= $pdf->GetY() + 1;
			if ($calculseul) {
				$heightforver	= $posytabver - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforver;
			}
			else {
				$pdf->commitTransaction();
				return $posytabver;
			}
		}

		/********************************************
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		PDF			$pdf     		The PDF factory
		*	@param		Object		$object			Object shown in PDF
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		int			$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		********************************************/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul)
		{

		}
	}
?>