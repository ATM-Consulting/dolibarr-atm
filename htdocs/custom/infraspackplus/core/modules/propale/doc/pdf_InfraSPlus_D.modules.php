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
	* 	\file		../infraspackplus/core/modules/propale/doc/pdf_InfraSPlus_D.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF commercial proposal
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/propalmergepdfproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF proposal InfraS
	************************************************/
	class pdf_InfraSPlus_D extends ModelePDFPropales
	{
		public $db;
		public $name;
		public $description;
		public $update_main_doc_field;	// Save the name of generated file as the main doc when generating a doc with this template
		public $type;
		public $phpmin	= array(5, 6);
		public $version	= 'dolibarr';
		public $page_largeur;
		public $page_hauteur;
		public $format;
		public $marge_gauche;
		public $marge_droite;
		public $marge_haute;
		public $marge_basse;
		public $emetteur;

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
			$this->name							= $langs->trans('PDFInfraSPlusProposalName');
			$this->description					= $langs->trans('PDFInfraSPlusProposalDescription');
			$this->titlekey						= 'PdfCommercialProposalTitle';
			$this->defaulttemplate				= isset($conf->global->PROPALE_ADDON_PDF)						? $conf->global->PROPALE_ADDON_PDF						: '';
			$this->draft_watermark				= isset($conf->global->PROPALE_DRAFT_WATERMARK)					? $conf->global->PROPALE_DRAFT_WATERMARK				: '';
			$this->show_sign_area				= isset($conf->global->INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE)	? $conf->global->INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE	: 0;
			$this->show_ExtraFieldsLines		= isset($conf->global->INFRASPLUS_PDF_EXFL_D)					? $conf->global->INFRASPLUS_PDF_EXFL_D					: 0;
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 1;	// Display payment mode
			$this->option_condreg				= 1;	// Display payment terms
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_escompte				= 0;	// Displays if there has been a discount
			$this->option_credit_note			= 0;	// Support credit notes
			$this->option_freetext				= 1;	// Support add of a personalised text
			$this->option_draft_watermark		= 1;	// Support add of a watermark on drafts
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
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			if ($this->show_desc)			$hidedesc						= 0;
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_D') ? '' : '_D';
			$baseDir						= !empty($conf->propal->multidir_output[$conf->entity]) ? $conf->propal->multidir_output[$conf->entity] : $conf->propal->dir_output;

			if ($baseDir) {
				$object->fetch_thirdparty();
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
						$this->error	= $outputlangs->transnoentities("ErrorCanNotCreateDir", $dir);
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
					$parameters							= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook							= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
					$this->logo							= $hookmanager->resArray['logo'];
					$this->adr							= $hookmanager->resArray['adr'];
					$this->adrlivr						= $hookmanager->resArray['adrlivr'];
					$this->listfreet					= $hookmanager->resArray['listfreet'];
					$this->listnotep					= $hookmanager->resArray['listnotep'];
					$this->pied							= $hookmanager->resArray['pied'];
					$this->CGV							= $hookmanager->resArray['cgv'];
					$this->files						= $hookmanager->resArray['filesArray'];
					$this->usentascover					= $hookmanager->resArray['usentascover'];
					$this->showntusedascover			= $hookmanager->resArray['showntusedascover'];
					$this->with_picture					= $hookmanager->resArray['hidepict'];
					$hidedesc							= $hookmanager->resArray['hidedesc'];
					$this->hide_discount				= $hookmanager->resArray['hidedisc'];
					$this->hide_cols					= $hookmanager->resArray['hidecols'];
					$this->showwvccchk					= $hookmanager->resArray['showwvccchk'];
					$this->show_tot_disc				= $hookmanager->resArray['showtotdisc'];
					$this->show_pay_spec				= $hookmanager->resArray['showpayspec'];
					$this->show_tva_btp					= $hookmanager->resArray['showtvabtp'];
					$this->signvalue					= $hookmanager->resArray['signvalue'];
					$this->add_recap					= $hookmanager->resArray['subtotal_add_recap'];
					$nblignes							= count($object->lines);	// Set nblignes with the new facture lines content after hook
					if (!empty($this->usentascover))	$this->first_page_empty	= 1;	// Comme on veut une page de garde on créé une page vide en premier puis on insère la note prévue sur celle-ci
					if (!empty($this->show_ref_col))	$hideref				= 1;	// Comme on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information
					// Create pdf instance
					$pdf								= pdf_getInstance($this->format);
					$default_font_size					= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
					$pdf->SetAutoPageBreak(1, 0);
					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}
					$pdf->SetFont($this->font);
					// reduce the top margin before ol / il tag
					$tagvs						= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('PdfCommercialProposalTitle'));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('PdfCommercialProposalTitle').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
					$pagenb						= 1;
					// Default PDF parameters
					$this->stdLineW				= 0.2; // épaisseur par défaut dans TCPDF = 0.2
					$this->stdLineDash			= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->stdLineCap			= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->stdLineColor			= array(128, 128, 128);
					$this->stdLineStyle			= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->bgLineW				= $this->tblLineW; // épaisseur par défaut dans TCPDF = 0.2
					$this->bgLineDash			= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->bgLineCap			= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->bgLineColor			= $this->bg_color;
					$this->bgLineStyle			= array('width'=>$this->bgLineW, 'dash'=>$this->bgLineDash, 'cap'=>$this->bgLineCap, 'color'=>$this->bgLineColor);
					$this->tblLineCap			= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->tblLineStyle			= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>(!empty($this->title_bg) && ! $this->showtblline ? $this->bg_color : $this->tblLineColor));
					$this->verLineStyle			= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->verLineColor);
					$this->horLineStyle			= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$this->signLineCap			= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->signLineStyle		= array('width'=>$this->signLineW, 'dash'=>$this->signLineDash, 'cap'=>$this->signLineCap, 'color'=>$this->signLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Use of multicurrency for this document
					$this->use_multicurrency	= (!empty($conf->multicurrency->enabled) && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? 1 : 0;
					// First loop on each lines to prepare calculs and variables
					$realpatharray				= array();
					$pricesObjProd				= array();
					$listObjBib					= array();
					$listDescBib				= array();
					$objproduct					= new Product($db);
					$this->TotRem				= 0;
					$this->hasService			= 0;
					$this->hasProduct			= 0;
					$this->nbrProdTot			= 0;
					$this->nbrProdDif			= array();
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$isProd				= $objproduct->fetch($object->lines[$i]->fk_product);
						$this->hasProduct	+= $object->lines[$i]->product_type == 0 ? 1 : 0;	// Products
						$this->hasService	+= $object->lines[$i]->product_type == 1 ? 1 : 0;	// Services
						// Positionne $this->atleastonediscount si on a au moins une remise
						if ($object->lines[$i]->remise_percent) {
							$this->atleastonediscount++;
							if ($this->show_tot_disc)	$this->TotRem	+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht);
						}
						if ($this->discount_auto && $isProd > 0 && $object->lines[$i]->subprice < $objproduct->price) {
							$this->atleastonediscount++;
							$pricesObjProd[$i]['pu_ht']		= $objproduct->price;
							$pricesObjProd[$i]['pu_ttc']	= $objproduct->price_ttc;
							$pricesObjProd[$i]['remise']	= (($objproduct->price - $object->lines[$i]->subprice) * 100) / $objproduct->price;
							if ($this->show_tot_disc)		$this->TotRem	+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht, $pricesObjProd[$i]);
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
						// detect if there is at least one image to show
						if (!empty($this->with_picture) && $isProd > 0) {
							if (!empty($this->old_path_photo)) {
								$pdir[0] = get_exdir($objproduct->id, 2, 0, 0, $objproduct, 'product').$objproduct->id .'/photos/';
								$pdir[1] = get_exdir(0, 0, 0, 0, $objproduct, 'product').dol_sanitizeFileName($objproduct->ref).'/';
							}
							else {
								$pdir[0] = get_exdir(0, 0, 0, 0, $objproduct, 'product').((float) DOL_VERSION >= 13.0 ? '' : dol_sanitizeFileName($objproduct->ref).'/');	// default
								$pdir[1] = get_exdir($objproduct->id, 2, 0, 0, $objproduct, 'product').$objproduct->id .'/photos/';		// alternative
							}
							$arephoto	= false;
							$onlyOne	= $this->only_one_picture ? (in_array($objproduct->id, $listObjBib) ? 1 : 0) : 0;
							foreach ($pdir as $midir) {
								if (!$arephoto && !$onlyOne) {
									$dir	= ($objproduct->entity != $conf->entity ? $conf->product->multidir_output[$objproduct->entity] : $conf->product->dir_output).'/'.$midir;
									foreach ($objproduct->liste_photos($dir, 1) as $key => $obj) {
										if (empty($this->cat_hq_image))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
										{
											if ($obj['photo_vignette'])	$filename	= $obj['photo_vignette'];
											else						$filename	= $obj['photo'];
										}
										else			$filename	= $obj['photo'];
										$realpath		= $dir.$filename;
										$listObjBib[]	= $objproduct->id;
										$arephoto		= true;
									}
								}
							}
							if ($realpath && $arephoto)	$realpatharray[$i]	= $realpath;
							elseif ($onlyOne)			$realpatharray[$i]	= 'done';
						}
						// ecoTaxes
						if ($isProd > 0 && $conf->global->MAIN_MODULE_OUVRAGE && is_array($this->listPrefixEcotax) && count($this->listPrefixEcotax) > 0) {
							foreach ($this->listPrefixEcotax as $PrefixEcotax) {
								if (! isset($this->ecoTaxes[$PrefixEcotax]['ht']))	$this->ecoTaxes[$PrefixEcotax]['ht']	= 0;
								if (! isset($this->ecoTaxes[$PrefixEcotax]['ttc']))	$this->ecoTaxes[$PrefixEcotax]['ttc']	= 0;
								$this->ecoTaxes[$PrefixEcotax]['ht']				+= preg_match('/'.$PrefixEcotax.'(.*)/', $objproduct->ref, $reg)								? $object->lines[$i]->total_ht									: 0;
								$this->ecoTaxes[$PrefixEcotax]['ttc']				+= preg_match('/'.$PrefixEcotax.'(.*)/', $objproduct->ref, $reg)								? $object->lines[$i]->total_ttc									: 0;
								$this->hasEcoTaxes									= !empty($this->ecoTaxes[$PrefixEcotax]['ht']) || !empty($this->ecoTaxes[$PrefixEcotax]['ttc'])	? $outputlangs->transnoentities('PDFInfraSPlusInclEcoTaxes')	: $this->hasEcoTaxes;
							}
						}
						// Calcul du nombre de produit du document si option
						if ($this->show_qty_prod_tot && $object->lines[$i]->product_type == 0) {
							$this->nbrProdTot													+= $object->lines[$i]->qty;
							if (!in_array($object->lines[$i]->product_ref, $this->nbrProdDif))	$this->nbrProdDif[]	= $object->lines[$i]->product_ref;
						}
					}
					// Define width and position of notes frames
					$this->larg_util_txt											= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre											= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt												= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->show_ref_col) && empty($this->show_num_col))	$this->larg_ref			= 0;
					if (empty($this->product_use_unit))								$this->larg_unit		= 0;
					if (!empty($this->hide_qty))									$this->larg_qty			= 0;
					if (!empty($this->hide_up))										$this->larg_up			= 0;
					if (!empty($this->hide_vat) || !empty($this->hide_vat_col))		$this->larg_tva			= 0;
					if (!empty($this->hide_discount))								$this->larg_discount	= 0;
					else if (empty($this->atleastonediscount))						$this->larg_discount	= 0;
					if (!empty($this->hide_discount))								$this->larg_updisc		= 0;
					else if (empty($this->show_up_discounted))						$this->larg_updisc		= 0;
					else if (empty($this->atleastonediscount))						$this->larg_updisc		= 0;
					if (empty($object->situation_cycle_ref))						$this->larg_progress	= 0;
					if (empty($this->show_ttc_col))									$this->larg_totalttc	= 0;
					if (empty($this->hide_cols))									$this->larg_desc		= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_unit +
																												$this->larg_up + $this->larg_tva + $this->larg_discount + $this->larg_updisc +
																												$this->larg_progress + $this->larg_totalht + $this->larg_totalttc); // Largeur variable suivant la place restante
					else {
						$this->num_desc		= 1;
						$this->larg_desc	= $this->larg_util_cadre;
					}
					$this->tableau	= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
											'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
											'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
											'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
											'up'		=> array('col' => $this->num_up,		'larg' => $this->larg_up,		'posx' => 0),
											'tva'		=> array('col' => $this->num_tva,		'larg' => $this->larg_tva,		'posx' => 0),
											'discount'	=> array('col' => $this->num_discount,	'larg' => $this->larg_discount,	'posx' => 0),
											'updisc'	=> array('col' => $this->num_updisc,	'larg' => $this->larg_updisc,	'posx' => 0),
											'progress'	=> array('col' => $this->num_progress,	'larg' => $this->larg_progress,	'posx' => 0),
											'totalht'	=> array('col' => $this->num_totalht,	'larg' => $this->larg_totalht,	'posx' => 0),
											'totalttc'	=> array('col' => $this->num_totalttc,	'larg' => $this->larg_totalttc,	'posx' => 0)
											);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->largcol1		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 2)	$this->largcol2		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 3)	$this->largcol3		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 4)	$this->largcol4		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 5)	$this->largcol5		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 6)	$this->largcol6		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 7)	$this->largcol7		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 8)	$this->largcol8		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 9)	$this->largcol9		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 10)	$this->largcol10	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 11)	$this->largcol11	= $ncol_array['larg'];
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3		= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4		= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5		= $this->posxcol4	+ $this->largcol4;
					$this->posxcol6		= $this->posxcol5	+ $this->largcol5;
					$this->posxcol7		= $this->posxcol6	+ $this->largcol6;
					$this->posxcol8		= $this->posxcol7	+ $this->largcol7;
					$this->posxcol9		= $this->posxcol8	+ $this->largcol8;
					$this->posxcol10	= $this->posxcol9	+ $this->largcol9;
					$this->posxcol11	= $this->posxcol10	+ $this->largcol10;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->tableau[$ncol]['posx']	= $this->posxcol1;
						elseif ($ncol_array['col'] == 2)	$this->tableau[$ncol]['posx']	= $this->posxcol2;
						elseif ($ncol_array['col'] == 3)	$this->tableau[$ncol]['posx']	= $this->posxcol3;
						elseif ($ncol_array['col'] == 4)	$this->tableau[$ncol]['posx']	= $this->posxcol4;
						elseif ($ncol_array['col'] == 5)	$this->tableau[$ncol]['posx']	= $this->posxcol5;
						elseif ($ncol_array['col'] == 6)	$this->tableau[$ncol]['posx']	= $this->posxcol6;
						elseif ($ncol_array['col'] == 7)	$this->tableau[$ncol]['posx']	= $this->posxcol7;
						elseif ($ncol_array['col'] == 8)	$this->tableau[$ncol]['posx']	= $this->posxcol8;
						elseif ($ncol_array['col'] == 9)	$this->tableau[$ncol]['posx']	= $this->posxcol9;
						elseif ($ncol_array['col'] == 10)	$this->tableau[$ncol]['posx']	= $this->posxcol10;
						elseif ($ncol_array['col'] == 11)	$this->tableau[$ncol]['posx']	= $this->posxcol11;
					}
					if ($conf->subtotal->enabled) {
						$pdf->outputlangs	= $outputlangs;
						$pdf->show_ttc_col	= $this->show_ttc_col;
						$pdf->heightline	= $this->heightline;
						$pdf->totalht_posx	= $this->tableau['totalht']['posx'];
						$pdf->totalht_larg	= $this->tableau['totalht']['larg'];
						$pdf->totalttc_posx	= $this->tableau['totalttc']['posx'];
						$pdf->totalttc_larg	= $this->tableau['totalttc']['larg'];
					}
					// Define width and position of secondary tables columns
					$this->larg_tabtotal	= 80;
					$this->larg_tabinfo		= $this->page_largeur - $this->marge_gauche - $this->marge_droite - $this->larg_tabtotal;
					$this->posxtabtotal		= $this->page_largeur - $this->marge_droite - $this->larg_tabtotal;
					// Calculs de positions
					$this->tab_hl			= 4;
					$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head					= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead			= $head['totalhead'];
					$hauteurcadre			= $head['hauteurcadre'];
					$tab_top				= $hauteurhead + 5;
					$tab_top_newpage		= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$ht_colinfo				= $this->_tableau_info($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht1_coltotal			= $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht2_coltotal			= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, (!empty($this->number_words) ? 1 : 0), 1, $this->horLineStyle) : 0;
					if ($this->show_sign_area) {
						if ($ht2_coltotal > 3)	$ht_coltotal	= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 1);
						else					$ht_coltotal	= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
					}
					$ht_coltotal		+= $ht1_coltotal + $ht2_coltotal;
					$heightforinfotot	= $ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal;
					$heightforinfotot	+= $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					// Insert a empty page or a cover page first
					if ($this->first_page_empty) {
						if (!empty($this->usentascover))	$height_cover	= pdf_InfraSPlus_Notes($pdf, $object, array($this->showntusedascover), $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, 0, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, -2, 0);
						$pdf->AddPage('', '', true);
						pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
						$pdf->setPage(2);
						$tab_top	= $tab_top_newpage;
					}
					// Incoterm
					$height_incoterms	= 0;
					if ($conf->incoterm->enabled) {
						$desc_incoterms	= $object->getIncotermsForPDF();
						if ($desc_incoterms) {
							$pdf->SetFont('', '', $default_font_size - 1);
							$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $tab_top, dol_htmlentitiesbr($desc_incoterms), 0, 1);
							$nexY												= $pdf->GetY();
							$height_incoterms									= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
							if ($this->showtblline && !$this->desc_full_line)	$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_incoterms + 1, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
							$height_incoterms									+= $this->tab_hl;
						}
					}
					$tab_top		+= $height_incoterms;
					// Livraison
					$height_livr	= 0;
					if ($head['livrshow']) {
						$pdf->SetFont('', 'B', $default_font_size + 2);
						$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusLivr')), 0, 1);
						$xlivr	= $pdf->GetX() + $pdf->GetStringWidth($outputlangs->transnoentities('PDFInfraSPlusLivr'), '', 'B', $default_font_size + 2) + 5;
						if ($this->adrlivr->name != '') {
							$pdf->SetFont('', 'B', $default_font_size);
							$pdf->writeHTMLCell($this->larg_util_txt - $xlivr - 3, $this->tab_hl, $xlivr, $tab_top + 0.6, dol_htmlentitiesbr($this->adrlivr->name), 0, 1);
							$nexY	= $pdf->GetY();
						}
						else												$nexY	= $tab_top + 0.6;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell($this->larg_util_txt - $xlivr - 3, $this->tab_hl, $xlivr, $nexY, dol_htmlentitiesbr($head['livrshow']), 0, 1);
						$nexY												= $pdf->GetY();
						$height_livr										= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						if ($this->showtblline && !$this->desc_full_line)	$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_livr + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_livr										+= $this->tab_hl;
					}
					$tab_top	+= $height_livr;
					// Header informations after Address blocks
					if (!empty($this->header_after_addr)) {
						$height_header_inf	= 0;
						$tab_top			+= $this->space_headerafter;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
						$refDoc				= !empty($this->ref_from_cust) ? $object->ref_client : $object->ref;
						$refCli				= !empty($this->ref_from_cust) ? '' : $object->ref_client;
						$txtC11				= $outputlangs->transnoentities($this->titlekey).' '.$outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($refDoc);
						if ($object->statut == 0) {
							$pdf->SetTextColor(128, 0, 0);
							$txtC11	.= ' - '.$outputlangs->transnoentities('NotValidated');
						}
						$largC11						= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
						$txtC12							= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
						if ($object->fin_validite)		$txtC12b	= $outputlangs->transnoentities('DateEndPropal').' : '.dol_print_date($object->fin_validite, 'day', false, $outputlangs, true);
						$largC12						= $this->larg_util_txt - $largC11;
						$xC12							= $this->posx_G_txt + $this->larg_util_txt - $largC12;
						$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
						$pdf->MultiCell($largC12, $this->tab_hl, $txtC12.(empty($this->dates_br) ? ' / '.$txtC12b : ''), 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
						if (!empty($this->dates_br))	$pdf->MultiCell($largC12, $this->tab_hl, $txtC12b, 0, 'R', 0, 0, $xC12, $tab_top + $this->tab_hl, true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
						$nexY							= $tab_top + ($this->tab_hl * (!empty($this->dates_br) ? 2 : 1)) + 1;
						if ($refCli) {
							$txtC21		= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($refCli);
							$largC21	= $pdf->GetStringWidth($txtC21, '', '', $default_font_size - 1) + 3;
							$pdf->MultiCell($largC21, $this->tab_hl, $txtC21, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						$txtC22	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt + $largC21, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if (!empty($txtC22)) {
							$largC22	= $this->larg_util_txt - $largC21;
							$xC22		= $refCli ? $this->posx_G_txt + $this->larg_util_txt - $largC22 : $this->posx_G_txt;
							$pdf->MultiCell($largC22, $this->tab_hl, $txtC22, 0, ($refCli ? 'R' : 'L'), 0, 0, $xC22, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						if ($refCli || !empty($txtC22))	$nexY	+= $this->tab_hl + 1;
						$height_header_inf						= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf						+= $this->tab_hl;
					}
					$tab_top		+= $height_header_inf;
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, $this->first_page_empty);
					$tab_top		+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY			= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Loop on each lines
					$subtotalRecap	= array();
					for ($i = 0 ; $i < $nblignes ; $i++) {
						if (!empty(pdf_InfraSPlus_escapeOuvrage($object, $i, 1)))	continue;	// COmposants d'ouvrage Inovea masqués
						$curY														= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table))							$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						else														$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore												= $pdf->getPage();
						$showpricebeforepagebreak									= 1;
						$colPicture													= $this->tableau['ref']['larg'] > 0 && $this->picture_in_ref ? 'ref' : 'desc';
						$imglinesize												= !empty($this->with_picture) ? pdf_InfraSPlus_getlineimgsize($this->tableau[$colPicture]['larg'], $realpatharray[$i]) : array();	// Define size of image if we need it
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && $this->linkpictureurl) {
							$lineurl	= pdf_InfraSPlus_getlineurl($object, $i);
							$txturl		= !empty($lineurl) ? '<a href = "'.$lineurl.'" target = "_blank">'.pdf_InfraSPlus_formatNotes($object, $outputlangs, $this->linkpictureurl).'</a>' : '&nbsp;';
							$ht_url		= $pdf->getStringHeight($this->tableau[$colPicture]['larg'], $txturl);
						}
						// Hauteur de la référence
						$this->heightline	= $this->tab_hl;
						if (! $this->hide_cols) {
							// Reference
							if (!empty($this->show_ref_col) || !empty($this->show_num_col)) {
								$pdf->startTransaction();
								$startline			= $pdf->GetY();
								$ref				= !empty($this->show_ref_col) ? pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails) : $i + 1;
								$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $startline, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
								$endline			= $pdf->GetY();
								$heightRef			= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
								$this->heightline	= !empty($this->picture_in_ref) && isset($imglinesize['width']) && isset($imglinesize['height']) ? $imglinesize['height'] + $ht_url + ($this->tab_hl / 2) : 0;
								$this->heightline	+= empty($this->picture_in_ref) ? $heightRef : (empty($this->picture_replace_ref) ? $heightRef + $this->tab_hl : 0);
								$pdf->rollbackTransaction(true);
							}
						}
						// Photo of product line first
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && empty($this->picture_after)) {
							if (($curY + ($this->picture_in_ref ? $this->heightline : $imglinesize['height']) + $ht_url) > ($this->page_hauteur - ($heightforfooter)))	// If photo too high, we moved completely on new page
							{
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								$curY						= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
								$showpricebeforepagebreak	= 0;
							}
							$PictureY	= $curY + ($this->picture_in_ref ? $heightRef + ($this->tab_hl / 2) : 0);
							$PictureY	= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau[$colPicture]['posx'], $PictureY, $this->tableau[$colPicture]['larg'], $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl);
							$curY		= ($this->picture_in_ref ? $curY : $PictureY) +  $this->picture_padding;
						}
						$extraDet	= '';
						if ($object->lines[$i]->product_type != 9) {
							$pdf->SetLineStyle($this->horLineStyle);
							// extrafieldsline
							$extrafieldslines							= '';
							if (!empty($this->show_ExtraFieldsLines))	$extrafieldslines	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor);
							$extraDet									.= empty($extrafieldslines) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$extrafieldslines.'<hr style = "width: 80%;">';
							// Custom values (weight, volume and code
							$WVCC										= '';
							if ($this->showwvccchk)						$WVCC	= pdf_InfraSPlus_getlinewvdcc($object, $i, $outputlangs);
							$extraDet									.= empty($WVCC) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$WVCC.'<hr style = "width: 80%;">';
						}
						// Description of product line
						$pageposdesc	= $pdf->getPage();
						$hide_desc		= !empty($hidedesc) ? $hidedesc : (!empty($object->lines[$i]->fk_product) && $this->only_one_desc ? (in_array($object->lines[$i]->fk_product, $listDescBib) ? 1 : 0) : 0);
						pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hide_desc, 0, $extraDet, null, $this->desc_full_line);
						$ret			= !empty($object->lines[$i]->fk_product) ? $listDescBib[] = $object->lines[$i]->fk_product : '';
						$pageposafter	= $pdf->getPage();
						$posyafter		= $pdf->GetY();
						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
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
						elseif ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes - 1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposafter + 1);
							}
						}
						$nexY	= $pdf->GetY();
						// Photo of product line after description
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && !empty($this->picture_after)) {
							$pageposimg	= $pdf->getPage();
							$nexY		+= $this->picture_padding;
							if (($nexY + $imglinesize['height'] + $ht_url) > ($this->page_hauteur - ($heightforfooter + ($i == ($nblignes - 1) ? $heightforinfotot : 0))))	// If photo too high, we moved completely on new page
							{
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposimg + 1);
								$nexY						= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
								$showpricebeforepagebreak	= 0;
							}
							$widthpicture	= $this->desc_full_line ? $this->larg_util_txt : $this->tableau['desc']['larg'];
							$nexY			= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau['desc']['posx'], $nexY, $widthpicture, $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl);
						}
						$pageposafter	= $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							if ($curY > ($this->page_hauteur - $heightforfooter - $this->tab_hl)) {
								$pdf->setPage($pageposafter);
								$curY	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
							}
							else	$pdf->setPage($pageposdesc);
						}
						$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut
						if (! $this->hide_cols) {
							// Reference
							if ((!empty($this->show_ref_col) || !empty($this->show_num_col)) && (empty($this->picture_in_ref) || (!empty($this->picture_in_ref) && empty($this->picture_replace_ref)))) {
								$pagepos	= $pdf->getPage();
								$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
								$pdf->setPage($pagepos);
							}
							// Quantity
							if (empty($this->hide_qty)) {
								$qty	= pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// Unit
							if (!empty($this->product_use_unit)) {
								$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
								$pdf->MultiCell($this->tableau['unit']['larg'], $this->heightline, $unit, '', 'L', 0, 1, $this->tableau['unit']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// Unit price
							if (empty($this->hide_up)) {
								if (empty($this->hide_discount)) {
									if (empty($this->hide_vat))	$up_line	= pdf_InfraSPlus_getlineupexcltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
									else						$up_line	= pdf_InfraSPlus_getlineupincltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
								}
								else {
									if (empty($this->hide_vat))	$up_line	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails);
									else						$up_line	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails);
								}
								$pdf->MultiCell($this->tableau['up']['larg'], $this->heightline, $up_line, '', 'R', 0, 1, $this->tableau['up']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// VAT Rate
							if (empty($this->hide_vat) && empty($this->hide_vat_col)) {
								$vat_rate	= pdf_InfraSPlus_getlinevatrate($object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['tva']['larg'], $this->heightline, $vat_rate, '', 'R', 0, 1, $this->tableau['tva']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// Discount on line
							if (($object->lines[$i]->remise_percent && empty($this->hide_discount)) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
								$remise_percent	= pdf_InfraSPlus_getlineremisepercent($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
								$pdf->MultiCell($this->tableau['discount']['larg'], $this->heightline, $remise_percent, '', 'R', 0, 1, $this->tableau['discount']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// Discounted price
							if (($object->lines[$i]->remise_percent && empty($this->hide_discount) && $this->show_up_discounted) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
								if (empty($this->hide_vat))	$up_disc	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails);
								else						$up_disc	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['updisc']['larg'], $this->heightline, $up_disc, '', 'R', 0, 1, $this->tableau['updisc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// Total line
							if (empty($this->hide_vat))	$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $i, $outputlangs, $hidedetails);
							else						$total_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails);
							$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							if($this->show_ttc_col) {
								$totalTTC_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, $totalTTC_line, '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
						}
						// Add dash or space between line
						$separate	= pdf_InfraSPlus_separateLine ($object, $i);
						if (!empty($separate)) {
							if ($this->dash_between_line && $i < ($nblignes - 1)) {
								$pdf->setPage($pageposafter);
								$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
								$nexY	+= 2;
							}
							else	$nexY	+= $this->lineSep_hight;
						}
						// Detect if some page were added automatically and output header, table and footer for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$heightforfooter										= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1 && ! $this->first_page_empty)			$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							elseif ($pagenb == 2 && $this->first_page_empty)		$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							elseif ($pagenb > ($this->first_page_empty ? 2 : 1))	$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
							if (empty($this->small_head2))							$this->_pagehead($pdf, $object, 0, $outputlangs);
							else													$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							$heightforfooter										= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1 && ! $this->first_page_empty)			$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							elseif ($pagenb == 2 && $this->first_page_empty)		$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							elseif ($pagenb > ($this->first_page_empty ? 2 : 1))	$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							// New page
							$pdf->AddPage();
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
							$pagenb++;
							if (empty($this->small_head2))							$this->_pagehead($pdf, $object, 0, $outputlangs);
							else													$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							$nexY													= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
						}
						if ($this->add_recap)	$subtotalRecap	= pdf_InfraSPlus_subtotal_getrecap ($object, $i, $subtotalRecap);	// SubTotal module with recap option
					}
					$bottomlasttab											= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1 && ! $this->first_page_empty)			$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					elseif ($pagenb == 2 && $this->first_page_empty)		$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					elseif ($pagenb > ($this->first_page_empty ? 2 : 1))	$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $pagenb);
					$posyinfo												= $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$posytot												= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$posyfreetext											= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, (!empty($this->number_words) ? 1 : 0), 0, $this->horLineStyle) : $posytot;
					if ($this->show_sign_area) {
						if ($ht2_coltotal > 3)	$posysignarea	= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 1);
						else					$posysignarea	= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 0);
					}
					else										$posysignarea	= $posyfreetext;
					$posy										= $posyinfo > $posysignarea ? $posyinfo : $posysignarea;
					$posy										= $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posy, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posy;
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if ($this->add_recap && count($subtotalRecap) > 0)	// SubTotal module with recap option
					{
						usort($subtotalRecap, pdf_InfraSPlus_compare('rang'));
						$pdf->AddPage();	// New page for review
						pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
						$pagenb++;
						if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
						else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						$posytotrecap					= pdf_InfraSPlus_subtotal_recap($pdf, $object, $tab_top_newpage, $outputlangs, $subtotalRecap, $this, $ht1_coltotal, $heightforfooter);
						$pageposafter					= $pdf->getPage();
						// Detect if some page were added automatically and output header, table and footer for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
						$bottomlasttab	= $this->page_hauteur - $ht1_coltotal - $heightforfooter - 1;
						$this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
						$this->_pagefoot($pdf, $object, $outputlangs, 0);
					}
					if (method_exists($pdf, 'AliasNbPages'))	$pdf->AliasNbPages();
					// If merge CGV is active
					if (!empty($this->CGV))						pdf_InfraSPlus_CGV($pdf, $this->CGV, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					// if merge files is active
					if (!empty($this->files))					pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					// If propal merge product PDF is active
					if (!empty($this->produit_pdf_merge)) {
						require_once DOL_DOCUMENT_ROOT.'/product/class/propalmergepdfproduct.class.php';
						$already_merged = array ();
						foreach ($object->lines as $line) {
							if (!empty($line->fk_product) && ! (in_array($line->fk_product, $already_merged))) {
								$filetomerge							= new Propalmergepdfproduct($db);	// Find the desire PDF
								if ($this->multilangs)					$filetomerge->fetch_by_product($line->fk_product, $outputlangs->defaultlang);
								else									$filetomerge->fetch_by_product($line->fk_product);
								$already_merged[]						= $line->fk_product;
								$product								= new Product($db);
								$product->fetch($line->fk_product);
								if ($product->entity != $conf->entity)	$entity_product_file	= $product->entity;
								else 									$entity_product_file	= $conf->entity;
								// If PDF is selected and file is not empty
								if (count($filetomerge->lines) > 0) {
									foreach ($filetomerge->lines as $linefile) {
										if (!empty($linefile->id) && !empty($linefile->file_name)) {
											if (!empty($this->old_path_photo)) {
												if (!empty($conf->product->enabled))		$filetomerge_dir	= $conf->product->multidir_output[$entity_product_file].'/'.get_exdir($product->id, 2, 0, 0, $product, 'product').$product->id."/photos";
												elseif (!empty($conf->service->enabled))	$filetomerge_dir	= $conf->service->multidir_output[$entity_product_file].'/'.get_exdir($product->id, 2, 0, 0, $product, 'product').$product->id."/photos";
											}
											else {
												if (!empty($conf->product->enabled))		$filetomerge_dir	= $conf->product->multidir_output[$entity_product_file].'/'.get_exdir(0, 0, 0, 0, $product, 'product');	//.dol_sanitizeFileName($product->ref);
												elseif (!empty($conf->service->enabled))	$filetomerge_dir	= $conf->service->multidir_output[$entity_product_file].'/'.get_exdir(0, 0, 0, 0, $product, 'product');	//.dol_sanitizeFileName($product->ref);
											}
											$infile	= $filetomerge_dir.'/'.$linefile->file_name;
											pdf_InfraSPlus_Merge($pdf, $infile, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
										}
									}
								}
							}
						}
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters	= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'fromInfraS' => 1);
					global $action;
					$reshook	= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
					if ($reshook < 0) {
						$this->error	= $hookmanager->error;
						$this->errors	= $hookmanager->errors;
					}
					if (!empty($this->main_umask))	@chmod($file, octdec($this->main_umask));
					$this->result					= array('fullpath' => $file);
					return 1;   // Pas d'erreur
				}
				else {
					$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			}
			else {
				$this->error	= $outputlangs->transnoentities('ErrorConstantNotDefined', 'PROP_OUTPUTDIR');
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
		*	@return		array		$hauteurhead	'totalhead'		= hight of header
		*											'hauteurcadre	= hight of frame
		********************************************/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
		{
			global $conf, $hookmanager;

			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$dimCadres			= array ('S' => 92, 'R' => 92);
			$w					= $this->header_align_left ? $dimCadres['R'] - $this->decal_round : 100;
			$align				= $this->header_align_left ? 'L' : 'R';
			$posy				= $this->marge_haute;
			$posx				= $this->page_largeur - $this->marge_droite - $w;
			// Logo
			$heightLogo			= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $this->logo, $this->emetteur, $this->marge_gauche, $this->tab_hl, $this->headertxtcolor, $object->entity);
			$heightLogo			+= $posy + $this->tab_hl;
			if (empty($this->header_after_addr)) {
				$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
				$title	= $outputlangs->transnoentities($this->titlekey);
				$pdf->MultiCell($w, $this->tab_hl * 2, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				$posy	+= $this->tab_hl * 2;
				$refDoc	= !empty($this->ref_from_cust) ? $object->ref_client : $object->ref;
				$refCli	= !empty($this->ref_from_cust) ? '' : $object->ref_client;
				$txtref	= $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($refDoc);
				if ($object->statut == 0) {
					$pdf->SetTextColor(128, 0, 0);
					$txtref .= ' - '.$outputlangs->transnoentities('NotValidated');
				}
				$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
				$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
				$posy	+= $this->tab_hl;
				$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
				if (empty($this->dates_br))
					if ($object->fin_validite)	$txtdt	.= ' / '.$outputlangs->transnoentities('DateEndPropal').' : '.dol_print_date($object->fin_validite, 'day', false, $outputlangs, true);
				$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				if (!empty($this->dates_br)) {
					if ($object->fin_validite) {
						$txtdt	= '';
						$posy	+= $this->tab_hl - 0.5;
						$txtdt	= $outputlangs->transnoentities('DateEndPropal').' : '.dol_print_date($object->fin_validite, 'day', false, $outputlangs, true);
						$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
					}
				}
				$pdf->SetFont('', '', $default_font_size - 2);
				if ($refCli) {
					$posy	+= $this->tab_hl - 0.5;
					$txtcc	= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($refCli);
					$pdf->MultiCell($w, $this->tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				if (!empty($this->show_num_cli) && !empty($this->num_cli_frm) && $object->thirdparty->code_client) {
					$txtNumCli	= $outputlangs->transnoentities('CustomerCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_client);
					$posy		+= $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $txtNumCli, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				if (!empty($this->show_code_cli_compt) && !empty($this->code_cli_compt_frm) && $object->thirdparty->code_compta) {
					$txtCodeCliCompt	= $outputlangs->transnoentities('CustomerAccountancyCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_compta);
					$posy				+= $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $txtCodeCliCompt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				if (!empty($this->add_creator_in_header)) {
					$usertmp	= pdf_InfraSPlus_creator($object, $outputlangs);
					if ($usertmp) {
						$posy	+= $this->tab_hl - 0.5;
						$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusRedac').' : '.$usertmp, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
					}
				}
				// Show list of linked objects
				$posy	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, $this->tab_hl, $align);
				$posy	+= 0.5;
			}
			$dimCadres['Y']	= ($this->use_iso_location && $posy <= 40 ? 40 : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			if ($showaddress) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'CUSTOMER'),
										'L' => $object->getIdContact('external', 'SHIPPING')
										);
				$addresses		= array();
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur);
				$hauteurcadre	= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect);
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
		*	@return		void
		********************************************/
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs)
		{
			global $conf, $hookmanager;

			$fromcompany	= $this->emetteur;
			$title			= $outputlangs->transnoentities($this->titlekey);
			pdf_InfraSPlus_pagesmallhead($pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $this->formatpage, $this->decal_round, $this->logo, $this->headertxtcolor);
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
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				if ($pagenb == 1) {
					$infocurrency	= $outputlangs->transnoentities('AmountInCurrency', $outputlangs->transnoentitiesnoconv('Currency'.$currency));
					$pdf->MultiCell($pdf->GetStringWidth($infocurrency) + 3, 2, $infocurrency, '', 'R', 0, 1, $this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($infocurrency) + 3) - $this->decal_round, $tab_top - $this->tab_hl, true, 0, 0, false, 0, 'M', false);
				}
				if (!empty($this->title_bg))							$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				else if ($this->showtblline && !$this->desc_full_line)	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				if ($this->showtblline && !$this->desc_full_line)		$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				else													$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
			}
			else
				if ($this->showtblline && !$this->desc_full_line)	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			if ($object->statut == 0 && (!empty($this->draft_watermark))) {
				if (empty($hidetop))	pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + $this->ht_top_table + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				else					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			}
			// Show Folder mark
			if (!empty($this->fold_mark)) {
				$pdf->Line(0, ($this->page_hauteur)/3, $this->fold_mark, ($this->page_hauteur)/3, $this->stdLineStyle);
				$pdf->Line($this->page_largeur - $this->fold_mark, ($this->page_hauteur)/3, $this->page_largeur, ($this->page_hauteur)/3, $this->stdLineStyle);
			}
			if (!$this->hide_cols && $this->showverline && !$this->desc_full_line) {
				// Colonnes
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol2,		$tab_top, $this->posxcol2,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol3,		$tab_top, $this->posxcol3,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol4,		$tab_top, $this->posxcol4,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol5 > $this->posxcol4 && $this->posxcol5 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol5,		$tab_top, $this->posxcol5,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol6 > $this->posxcol5 && $this->posxcol6 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol6,		$tab_top, $this->posxcol6,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol7 > $this->posxcol6 && $this->posxcol7 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol7,		$tab_top, $this->posxcol7,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol8 > $this->posxcol7 && $this->posxcol8 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol8,		$tab_top, $this->posxcol8,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol9 > $this->posxcol8 && $this->posxcol9 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol9,		$tab_top, $this->posxcol9,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol10 > $this->posxcol9 && $this->posxcol10 < ($this->marge_gauche + $this->larg_util_cadre))	$pdf->line($this->posxcol10,	$tab_top, $this->posxcol10,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol11 > $this->posxcol10 && $this->posxcol11 < ($this->marge_gauche + $this->larg_util_cadre))	$pdf->line($this->posxcol11,	$tab_top, $this->posxcol11,	$tab_top + $tab_height, $this->verLineStyle);
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (! $this->hide_cols) {
					if (!empty($this->show_ref_col))	$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if (!empty($this->show_num_col))	$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities("PDFInfraSPlusNum"), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if (empty($this->hide_qty))			$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Qty'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if ($this->product_use_unit)		$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Unit'), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if (empty($this->hide_up)) {
						if (empty($this->hide_vat))	$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PriceUHT'), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
						else						$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PriceUTTC'), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
					if (empty($this->hide_vat) && empty($this->hide_vat_col))	$pdf->MultiCell($this->tableau['tva']['larg'], $this->ht_top_table, $outputlangs->transnoentities('VAT'), '', 'C', 0, 1, $this->tableau['tva']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if ($this->atleastonediscount && empty($this->hide_discount)) {
						$pdf->MultiCell($this->tableau['discount']['larg'], $this->ht_top_table, $outputlangs->transnoentities("ReductionShort"), '', 'C', 0, 1, $this->tableau['discount']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
						if ($this->show_up_discounted)	$pdf->MultiCell($this->tableau['updisc']['larg'], $this->ht_top_table, $outputlangs->transnoentities("PDFInfraSPlusDiscountedPrice"), '', 'C', 0, 1, $this->tableau['updisc']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
					}
					if (empty($this->hide_vat))	$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalHTShort'), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					else						$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalTTCShort'), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if ($this->show_ttc_col)	$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalTTCShort'), '', 'C', 0, 1, $this->tableau['totalttc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
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
			$posytabinfo		= $posy + $this->ht_space_info;
			$tabinfo_hl			= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$larg_tabinfo		= $this->larg_tabinfo;
			$larg_col1info		= 46;
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
				$pdf->SetFont('', '', $default_font_size - 2);
				$nbrProd		= $outputlangs->transnoentities("PDFInfraSPlusQtyProd", $this->nbrProdTot, count($this->nbrProdDif));
				$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $nbrProd, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 2;
			}
			// Show total discount
			if ($this->show_tot_disc && $this->atleastonediscount) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities("PDFInfraSPlusTotRem").' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$total_ht		= $this->use_multicurrency ? $object->multicurrency_total_ht : $object->total_ht;
				$total_ttc		= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc;
				$TotRem			= pdf_InfraSPlus_price($object, $this->TotRem, $outputlangs).' '.$outputlangs->transnoentities(($this->only_ht ? "HT" : "TTC"));
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $TotRem, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 2;
			}
			// Show shipping date
			$date_livraison	= version_compare(DOL_VERSION, '13.0.0', '>=') ? $object->delivery_date : $object->date_livraison;
			if (!empty($date_livraison)) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities("DateDeliveryPlanned").' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$dlp			= dol_print_date($date_livraison, "daytext", false, $outputlangs, true);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $dlp, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			}
			elseif ($object->availability_code || $object->availability)    // Show availability conditions
			{
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre				= $outputlangs->transnoentities('AvailabilityPeriod').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$lib_availability	= $outputlangs->transnoentities("AvailabilityType".$object->availability_code) != ('AvailabilityType'.$object->availability_code) ? $outputlangs->transnoentities("AvailabilityType".$object->availability_code) : $outputlangs->convToOutputCharset(isset($object->availability) ? $object->availability : '');
				$lib_availability	= str_replace('\n', "\n", $lib_availability);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_availability, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo		= $pdf->GetY() + 1;
			}
			// Show shipping method
			if ($object->shipping_method_id > 0) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre					= $outputlangs->transnoentities('SendingMethod').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$shipping_code			= $outputlangs->getLabelFromKey($db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');	// Get code using getLabelFromKey
				$lib_shipping_method	= $outputlangs->trans("SendingMethod".strtoupper($shipping_code));
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_shipping_method, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo			= $pdf->GetY() + 1;
			}	//if ($object->shipping_method_id > 0)
			// Show payments conditions
			if (empty($this->hide_pay_term_cond) && ($object->cond_reglement_code || $object->cond_reglement)) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre					= $outputlangs->transnoentities('PaymentConditions').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$lib_condition_paiement	= $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) != ('PaymentCondition'.$object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc ? $object->cond_reglement_doc : $object->cond_reglement_label);
				$lib_condition_paiement	= str_replace('\n', "\n", $lib_condition_paiement);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_condition_paiement, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo			= $pdf->GetY() + 1;
			}
			if (empty($this->hide_pay_term_mode)) {
				// Show payment mode
				if ($object->mode_reglement_code && $object->mode_reglement_code != 'CHQ' && $object->mode_reglement_code != 'VIR') {
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$titre			= $outputlangs->transnoentities('PaymentMode').' : ';
					$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 2);
					$lib_mode_reg	= $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) != ('PaymentType'.$object->mode_reglement_code) ? $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
					$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_mode_reg, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					$posytabinfo	= $pdf->GetY() + 1;
				}
				// Show payment mode CHQ
				if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ') {
					// Si mode reglement non force ou si force a CHQ
					if (!empty($this->chq_num)) {
						$diffsizetitle	= (empty($this->diffsize_title) ? 3 : $this->diffsize_title);
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
				if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR' || ($object->mode_reglement_code == 'CB' && $this->IBAN_with_CB) || $this->IBAN_All) {
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
			$posytabtot						= $posy + $this->ht_space_tot;
			$tabtot_hl						= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			// Tableau total
			$larg_tabtotal					= $this->larg_tabtotal;
			$larg_col2total					= $this->larg_totalht;
			$larg_col1total					= $larg_tabtotal - $larg_col2total;
			$posxtabtotal					= $this->posxtabtotal;
			$posxcol2total					= $this->posxtabtotal + $larg_col1total;
			$index							= 0;
			// Total HT
			$this->atleastoneratenotnull	= 0;
			if (! $this->only_ttc) {
				if ($this->only_ht) {
					$pdf->RoundedRect($posxtabtotal, $posytabtot, $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
				}
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalHTShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ht	= $this->use_multicurrency ? $object->multicurrency_total_ht : $object->total_ht;
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ht + (!empty($object->remise) ? $object->remise : 0), $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			if ((empty($this->only_ht) && ! $this->only_ttc) || $this->show_ttc_vat_tot) {
				// Show VAT by rates and total
				$tvaisnull	= ((!empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
				if (!empty($this->hide_vat_ifnull) && $tvaisnull) {
					// Nothing to do
				}
				else {
					//Local tax 1 before VAT
					foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('1', '3', '5'))) continue;
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey		= str_replace('*', '', $tvakey);
									$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat	= $outputlangs->transcountrynoentities("TotalLT1", $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
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
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey		= str_replace('*', '', $tvakey);
									$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat	= $outputlangs->transcountrynoentities("TotalLT2", $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
						}
					}
					// VAT
					foreach ($this->tva as $tvakey => $tvaval) {
						if ($tvakey > 0)    // On affiche pas taux 0
						{
							$this->atleastoneratenotnull++;
							$index++;
							$pdf->SetAlpha($this->alpha);
							$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
							$pdf->SetAlpha(1);
							$tvacompl	= '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey		= str_replace('*', '', $tvakey);
								$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat	= $outputlangs->transcountrynoentities("TotalVAT", $this->emetteur->country_code).' ';
							$totalvat	.= vatrate($tvakey, 1).$tvacompl;
							$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						}
					}
					//Local tax 1 after VAT
					foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('2', '4', '6'))) continue;
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey		= str_replace('*', '', $tvakey);
									$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat	= $outputlangs->transcountrynoentities("TotalLT1", $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
						}
					}
					//Local tax 2 after VAT
					foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('2', '4', '6'))) continue;
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey		= str_replace('*', '', $tvakey);
									$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat	= $outputlangs->transcountrynoentities("TotalLT2", $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
						}
					}
				}
				$index++;
			}
			// Total TTC
			if (empty($this->only_ht)) {
				$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalTTCShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ttc	= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc;
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ttc, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if ($this->efPaySpec && $this->show_pay_spec) {
				$totalPaySpec	= 0;
				$listPaySpec	= pdf_InfraSPlus_SpecPayExtraField($object);
				foreach ($listPaySpec as $key => $paySpec) {
					if ($paySpec['value'] != 0) {
						$index++;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities($paySpec['label']), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $paySpec['value'], $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$totalPaySpec	+= $paySpec['value'];
					}
				}
				if ($totalPaySpec > 0) {
					$index++;
					$toBePaid	= price2num((!$this->only_ht ? $total_ttc : $total_ht + (!empty($object->remise) ? $object->remise : 0)) - $totalPaySpec, 'MT');
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusRemainExpense'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $toBePaid, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
				}
			}
			$pdf->SetFont('', '', $default_font_size - 1);
			if ($this->efDeposit) {
				$listDeposit	= pdf_InfraSPlus_SpecPayExtraField($object, 1);
				foreach ($listDeposit as $key => $deposit) {
					if ($deposit['value'] != 0) {
						$valDeposit	= price2num((!$this->only_ht ? $total_ttc : $total_ht + (!empty($object->remise) ? $object->remise : 0)) * $deposit['value'] / 100, 'MT');
						$index++;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities($deposit['label']).' ('.$deposit['value'].'%)', '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $valDeposit, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					}
				}
			}
			// ecoTaxes
			if (is_array($this->ecoTaxes) && count($this->ecoTaxes) > 0) {
				foreach ($this->ecoTaxes as $key => $ecoTaxe) {
					if (($this->only_ht && !empty($ecoTaxe['ht'])) || !empty($ecoTaxe['ttc'])) {
						$valEcoTaxe	= price2num($this->only_ht ? $ecoTaxe['ht'] : $ecoTaxe['ttc'], 'MT');
						$index++;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusTotalEcoTaxe', ($this->only_ht ? $outputlangs->transnoentities('HT') : $outputlangs->transnoentities('TTC'))).' '.$key, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $valEcoTaxe, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					}
				}
			}
			if ($this->use_multicurrency && $this->show_tot_local_cur) {
				$index++;
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities((empty($this->only_ht) ? 'TotalTTCShort' : 'TotalHTShort')).' ('.$conf->currency.')'.(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_loc_cur	= (empty($this->only_ht) ? $object->total_ttc : $object->total_ht);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_loc_cur, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			if (!empty($this->number_words)) {
				$index++;
				$savcurrency	= $conf->currency;
				$conf->currency	= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
				$total			= empty($this->only_ht) ? $total_ttc : $total_ht;
				$total_words	= $outputlangs->transnoentities("PDFInfraSPlusProposalArrete").' : '.$outputlangs->getLabelFromNumber($total, 1);
				$pdf->MultiCell($larg_tabtotal, $tabtot_hl, $total_words, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$conf->currency	= $savcurrency;
			}
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
		*	Show area for the customer to sign
		*
		*	@param		PDF			$pdf            Object PDF
		*	@param		Facture		$object         Object invoice
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@return		int							Position pour suite
		********************************************/
		protected function _signature_area(&$pdf, $object, $posy, $outputlangs, $calculseul = 0, $freetext = 0)
		{
			$pdf->startTransaction();
			$default_font_size		= pdf_getPDFFontSize($outputlangs);
			$signarea_top			= $posy + 1;
			$signarea_hl			= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ProposalCustomerSignature'));
			$signarea_hl			= $signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl;
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$larg_signarea			= $this->larg_tabtotal;
			$posxsignarea			= $this->posxtabtotal;
			if ($freetext)			$pdf->Line($posxsignarea, $posy, $this->page_largeur - $this->marge_droite, $posy, $this->horLineStyle);
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('ProposalCustomerSignature'), '', 'L', 0, 1, $posxsignarea + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
			$pdf->RoundedRect($posxsignarea, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			if ($this->signvalue)	pdf_InfraSPlus_Client_Sign($pdf, $this->signvalue, $larg_signarea, $this->ht_signarea, $posxsignarea, $signarea_top + $signarea_hl);
			if ($calculseul) {
				$heightforarea	= ($signarea_top + $signarea_hl + $this->ht_signarea + 1) - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			}
			else {
				if ($this->e_signing)	$pdf->addEmptySignatureAppearance($posxsignarea, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
				$pdf->commitTransaction();
				return $signarea_top + $signarea_hl + $this->ht_signarea + 1;
			}
		}

		/********************************************
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		PDF			$pdf     		The PDF factory
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		Societe		$fromcompany	Object company
		*	@param		int			$marge_basse	Margin bottom we use for the autobreak
		*	@param		int			$marge_gauche	Margin left
		*	@param		int			$page_hauteur	Page height
		*	@param		Object		$object			Object shown in PDF
		*	@param		int			$showdetails	Show company details into footer
		*	@param		int			$hidesupline	Completly hide the line up to footer (for some edition with only table)
		*	@param		int			$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int							Return height of bottom margin including footer text
		********************************************/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul)
		{
			global $conf;

			$showdetails				= $this->type_foot;
			if (!empty($this->pied))	$showdetails	.= 1;
			else						$showdetails	.= 0;
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
?>