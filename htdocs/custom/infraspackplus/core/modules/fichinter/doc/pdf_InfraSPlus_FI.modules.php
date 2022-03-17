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
	* 	\file		../infraspackplus/core/modules/fichinter/doc/pdf_InfraSPlus_FI.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF fiche inter
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	 *	Class to generate PDF intervention card InfraS
	************************************************/
	class pdf_InfraSPlus_FI extends ModelePDFFicheinter
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
			$this->name							= $langs->trans('PDFInfraSPlusFicheInterName');
			$this->description					= $langs->trans('PDFInfraSPlusFicheInterDescription');
			$this->titlekey						= 'InterventionCard';
			$this->defaulttemplate				= isset($conf->global->FICHEINTER_ADDON_PDF)							? $conf->global->FICHEINTER_ADDON_PDF								: '';
			$this->product_use_unit				= 0;
			$this->duration_workday				= isset($conf->global->MAIN_DURATION_OF_WORKDAY)						? $conf->global->MAIN_DURATION_OF_WORKDAY							: 8;
			$this->draft_watermark				= isset($conf->global->FICHINTER_DRAFT_WATERMARK)						? $conf->global->FICHINTER_DRAFT_WATERMARK							: '';
			$this->lastNoteAsTable				= isset($conf->global->INFRASPLUS_PDF_LAST_NOTE_AS_TABLE)				? $conf->global->INFRASPLUS_PDF_LAST_NOTE_AS_TABLE					: 0;
			$this->hide_duration				= isset($conf->global->INFRASPLUS_PDF_HIDE_TIME_SPENT_FI)				? $conf->global->INFRASPLUS_PDF_HIDE_TIME_SPENT_FI					: 0;
			$this->show_dates_hours				= isset($conf->global->INFRASPLUS_PDF_SHOW_DATES_HOURS_FI)				? $conf->global->INFRASPLUS_PDF_SHOW_DATES_HOURS_FI					: 0;
			$this->show_sign_area_cli			= isset($conf->global->INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE)		? $conf->global->INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE			: 0;
			$this->show_sign_area_emet			= isset($conf->global->INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE_EMET)	? $conf->global->INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE_EMET	: 0;
			$this->show_sign_area				= $this->show_sign_area_cli || $this->show_sign_area_emet ? 1 : 0;
			$this->sign_area_full				= isset($conf->global->INFRASPLUS_PDF_INTERVENTION_SIGNATURE_FULL)		? $conf->global->INFRASPLUS_PDF_INTERVENTION_SIGNATURE_FULL			: 0;
			$this->show_ExtraFieldsLines		= isset($conf->global->INFRASPLUS_PDF_EXFL_FI)							? $conf->global->INFRASPLUS_PDF_EXF_FI								: 0;
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 0;	// Display payment mode
			$this->option_condreg				= 0;	// Display payment terms
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_escompte				= 1;	// Displays if there has been a discount
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
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_FI') ? '' : '_FI';
			$baseDir						= !empty($conf->ficheinter->multidir_output[$conf->entity]) ? $conf->ficheinter->multidir_output[$conf->entity] : $conf->ficheinter->dir_output;

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
					$this->CGI							= $hookmanager->resArray['cgi'];
					$this->files						= $hookmanager->resArray['filesArray'];
					$this->usentascover					= $hookmanager->resArray['usentascover'];
					$this->showntusedascover			= $hookmanager->resArray['showntusedascover'];
					$this->with_picture					= $hookmanager->resArray['hidepict'];
					$hidedesc							= $hookmanager->resArray['hidedesc'];
					$this->hide_discount				= $hookmanager->resArray['hidedisc'];
					$this->hide_cols					= $hookmanager->resArray['hidecols'];
					$this->showwvccchk					= $hookmanager->resArray['showwvccchk'];
					$this->showtot						= $hookmanager->resArray['showtot'];
					$this->signvalue					= $hookmanager->resArray['signvalue'];
					$nblignes							= count($object->lines);	// Set nblignes with the new facture lines content after hook
					if (!empty($this->show_ref_col))	$hideref = 1;	// Comme on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information
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
					$pdf->SetSubject($outputlangs->transnoentities('InterventionCard'));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('InterventionCard'));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
					$pagenb					= 1;
					// Default PDF parameters
					$this->stdLineW			= 0.2; // épaisseur par défaut dans TCPDF = 0.2
					$this->stdLineDash		= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->stdLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->stdLineColor		= array(128, 128, 128);
					$this->stdLineStyle		= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->bgLineW			= $this->tblLineW; // épaisseur par défaut dans TCPDF = 0.2
					$this->bgLineDash		= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->bgLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->bgLineColor		= $this->bg_color;
					$this->bgLineStyle		= array('width'=>$this->bgLineW, 'dash'=>$this->bgLineDash, 'cap'=>$this->bgLineCap, 'color'=>$this->bgLineColor);
					$this->tblLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->tblLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>(!empty($this->title_bg) && ! $this->showtblline ? $this->bg_color : $this->tblLineColor));
					$this->verLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->verLineColor);
					$this->horLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$this->signLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->signLineStyle	= array('width'=>$this->signLineW, 'dash'=>$this->signLineDash, 'cap'=>$this->signLineCap, 'color'=>$this->signLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// First loop on each lines to prepare calculs and variables
					$realpatharray			= array();
					$discount				= array();
					$isProd					= array();
					$pricesObjProd			= array();
					$listObjBib				= array();
					$listDescBib			= array();
					$objproduct				= new Product($db);
					$this->nbrProdTot		= 0;
					$this->nbrProdDif		= array();
					// Module management associé
					if ($conf->management->enabled) {
						$this->pricefichinter	= array();
						$this->pricefichinter	= pdf_infrasplus_getpricefichinter($object);
					}
					for ($i = 0 ; $i < $nblignes ; $i++) {
						// Module management associé
						if ($conf->management->enabled) {
							$this->prodfichinter[$i]	= array();
							$this->prodfichinter[$i]	= pdf_infrasplus_getlinefichinter($object, $i);
						}
						$discount[$i]		= $this->prodfichinter[$i] ? $this->prodfichinter[$i]['remise_percent']	: $object->lines[$i]->remise_percent;
						$fk_product			= $this->prodfichinter[$i] ? $this->prodfichinter[$i]['fk_product']		: $object->lines[$i]->fk_product;
						$qty				= pdf_InfraSPlus_getlineqty($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
						$product_type		= $this->prodfichinter[$i] ? $this->prodfichinter[$i]['product_type']	: $object->lines[$i]->product_type;
						$product_ref		= $this->prodfichinter[$i] ? $this->prodfichinter[$i]['ref']			: $object->lines[$i]->product_ref;
						$product_subprice	= $this->prodfichinter[$i] ? $this->prodfichinter[$i]['subprice']		: $object->lines[$i]->subprice;
						// Positionne $this->atleastoneproduct si on a au moins un produit / service
						if ($fk_product) {
							$this->atleastoneproduct++;
							$isProd[$i]	= $objproduct->fetch($fk_product);
						}
						// Positionne $this->atleastonediscount si on a au moins une remise
						if ($discount[$i])	$this->atleastonediscount++;
						if ($this->discount_auto && $isProd[$i] > 0 && $product_subprice != $objproduct->price) {
							$this->atleastonediscount++;
							$pricesObjProd[$i]['pu_ht']		= $objproduct->price;
							$pricesObjProd[$i]['pu_ttc']	= $objproduct->price_ttc;
							$pricesObjProd[$i]['remise']	= (($objproduct->price - $product_subprice) * 100) / $objproduct->price;
							if ($this->show_tot_disc)		$this->TotRem	+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht, $pricesObjProd[$i]);
						}
						// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
						$tvaligne			= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['total_tva']			: $object->lines[$i]->total_tva;
						$localtax1ligne		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['total_localtax1']	: $object->lines[$i]->total_localtax1;
						$localtax2ligne		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['total_localtax2']	: $object->lines[$i]->total_localtax2;
						$localtax1_rate		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['localtax1_tx']		: $object->lines[$i]->localtax1_tx;
						$localtax2_rate		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['localtax2_tx']		: $object->lines[$i]->localtax2_tx;
						$localtax1_type		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['localtax1_type']	: $object->lines[$i]->localtax1_type;
						$localtax2_type		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['localtax2_type']	: $object->lines[$i]->localtax2_type;
						$vatrate			= $this->prodfichinter[$i]	? (string) $this->prodfichinter[$i]['tva_tx']	: (string) $object->lines[$i]->tva_tx;
						$info_bits			= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['info_bits']			: $object->lines[$i]->info_bits;
						// Retrieve type from database for backward compatibility with old records
						if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
							&& (!empty($localtax1_rate) || !empty($localtax2_rate))) // and there is local tax
						{
							$localtaxtmp_array	= getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $this->emetteur);
							$localtax1_type		= isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
							$localtax2_type		= isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
						}
						// retrieve global local tax
						if ($localtax1_type && $localtax1ligne != 0)	$this->localtax1[$localtax1_type][$localtax1_rate]	+= $localtax1ligne;
						if ($localtax2_type && $localtax2ligne != 0)	$this->localtax2[$localtax2_type][$localtax2_rate]	+= $localtax2ligne;
						if (($info_bits & 0x01) == 0x01)				$vatrate											.= '*';
						if (! isset($this->tva[$vatrate]))				$this->tva[$vatrate]								= 0;
						$this->tva[$vatrate]							+= $object->lines[$i]->product_type != 9 && $object->lines[$i]->special_code != 501028 ? $tvaligne : 0;
						// detect if there is at least one image to show
						if (!empty($this->with_picture) && $isProd[$i] > 0) {
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
						if ($this->show_qty_prod_tot && $product_type == 0) {
							$this->nbrProdTot								+= $qty;
							if (!in_array($product_ref, $this->nbrProdDif))	$this->nbrProdDif[]	= $product_ref;
						}
					}
					// Define width and position of notes frames
					$this->larg_util_txt											= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre											= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt												= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->show_ref_col) && empty($this->show_num_col))	$this->larg_ref			= 0;
					if(! $this->product_use_unit)									$this->larg_unit		= 0;
					if (!empty($this->hide_qty))									$this->larg_qty			= 0;
					if (!empty($this->hide_up))										$this->larg_up			= 0;
					if (!empty($this->hide_vat) || !empty($this->hide_vat_col))	$this->larg_tva			= 0;
					if (!empty($this->hide_discount))								$this->larg_discount	= 0;
					else if (empty($this->atleastonediscount))						$this->larg_discount	= 0;
					if (!empty($this->hide_discount))								$this->larg_updisc		= 0;
					else if (empty($this->show_up_discounted))						$this->larg_updisc		= 0;
					else if (empty($this->atleastonediscount))						$this->larg_updisc		= 0;
					if (empty($this->show_ttc_col))									$this->larg_totalttc	= 0;
					if (! $this->hide_cols)											$this->larg_desc		= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_unit +
																												$this->larg_up + $this->larg_tva + $this->larg_discount + $this->larg_updisc +
																												$this->larg_totalht + $this->larg_totalttc); // Largeur variable suivant la place restante
					else {
						$this->num_desc		= $this->num_qty > $this->num_desc ? 1 : 2;
						$this->num_qty		= $this->num_desc == 2 ? 1 : 2;
						$this->larg_desc	= $this->larg_util_cadre - $this->larg_qty;
					}
					$this->tableau	= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
											'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
											'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
											'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
											'up'		=> array('col' => $this->num_up,		'larg' => $this->larg_up,		'posx' => 0),
											'tva'		=> array('col' => $this->num_tva,		'larg' => $this->larg_tva,		'posx' => 0),
											'discount'	=> array('col' => $this->num_discount,	'larg' => $this->larg_discount,	'posx' => 0),
											'updisc'	=> array('col' => $this->num_updisc,	'larg' => $this->larg_updisc,	'posx' => 0),
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
					}
					// Define width and position of secondary tables columns
					$this->larg_tabtotal		= 80;
					$this->larg_tabinfo			= $this->page_largeur - $this->marge_gauche - $this->marge_droite - $this->larg_tabtotal;
					$this->posxtabtotal			= $this->page_largeur - $this->marge_droite - $this->larg_tabtotal;
					// Calculs de positions
					$this->tab_hl				= 4;
					$this->decal_round			= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head						= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead				= $head["totalhead"];
					$hauteurcadre				= $head["hauteurcadre"];
					$tab_top					= $hauteurhead + 5;
					$tab_top_newpage			= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table			= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$ht_coltotal				= !empty($this->atleastoneproduct) && $this->showtot ? $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1) : 0;
					if ($this->show_sign_area)	$ht_coltotal	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
					$heightforinfotot			= $ht_coltotal;
					$heightforinfotot			+= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle);
					$heightforfooter			= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
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
						$txtC11				= $outputlangs->transnoentities($this->titlekey).' '.$outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
						if ($object->statut == 0) {
							$pdf->SetTextColor(128, 0, 0);
							$txtC11	.= ' - '.$outputlangs->transnoentities('NotValidated');
						}
						$largC11			= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
						$txtC12				= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->datec, 'day', false, $outputlangs, true);
						$largC12			= $this->larg_util_txt - $largC11;
						$xC12				= $this->posx_G_txt + $this->larg_util_txt - $largC12;
						$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
						$pdf->MultiCell($largC12, $this->tab_hl, $txtC12, 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
						$nexY				= $tab_top + $this->tab_hl + 1;
						$txtC22				= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if ($txtC22) {
							$pdf->MultiCell($this->larg_util_txt, $this->tab_hl, $txtC22, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
							$nexY				+= $this->tab_hl + 1;
						}
						$height_header_inf	= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf	+= $this->tab_hl;
					}
					$tab_top	+= $height_header_inf;
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 1, $this->first_page_empty);
					$tab_top		+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					// Affiche description
					$pdf->SetFont('', 'B', $default_font_size);
					$text		= $outputlangs->transnoentities("Description")." : ".$object->description;
					if ($object->duration > 0 && ! $this->hide_duration) {
						$totaltime	= convertSecondToTime($object->duration, 'all', $this->duration_workday);
						$text		.= ($text ? ' - ' : '').$outputlangs->trans("PDFInfraSPlusTemps")." : ".$totaltime;
					}
					if ($conf->global->MAIN_MODULE_MANAGEMENT && $this->show_dates_hours) {
						$dateo	= $object->dateo ? $outputlangs->trans("PDFInfraSPlusDateO")." : ".dol_print_date($object->dateo, "dayhour", false, $outputlangs, true) : '';
						$datee	= $object->datee ? $outputlangs->trans("PDFInfraSPlusDateE")." : ".dol_print_date($object->datee, "dayhour", false, $outputlangs, true) : '';
						$text	.= ($text ? ' - ' : '').$dateo.($dateo ? ' - ' : '').$datee;
					}
					// Add internal intervening if defined
					$arrayidcontact	= $object->getIdContact('internal','INTERVENING');
					if (count($arrayidcontact) > 0) {
						$object->fetch_user($arrayidcontact[0]);
						$text								.= ($text ? '<br />' : '' ).$outputlangs->transnoentities("PDFInfraSPlusFicheInterIntervening")." : ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
						if ($object->user->email) 			$text	.= ', '.$outputlangs->transnoentities("Email").' : '.$outputlangs->convToOutputCharset($object->user->email);
						if ($object->user->office_phone)	$text	.= ', '.$outputlangs->transnoentities("PhoneShort").' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($object->user->office_phone)));
					}
					$desc			= dol_htmlentitiesbr($text, 1);
					$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->marge_gauche + $this->decal_round, $tab_top, $outputlangs->convToOutputCharset($desc), 0, 1, 0);
					$nexY			= $pdf->GetY();
					$height_desc	= $nexY - $tab_top;
					$tab_top		+= $height_desc;
					$nexY			= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					$nbReport		= -1;
					// Loop on each lines for report
					for ($i = 0 ; $i < $nblignes ; $i++) {
						if ($isProd[$i])					continue;	// we keep only report line
						$nbReport++;	// On incrémente le nombre de ligne de rapport
						$curY								= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table))	$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						else								$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore						= $pdf->getPage();
						$showpricebeforepagebreak			= 1;
						// Description of report
						$valide								= empty($object->lines[$i]->id) ? 0 : $object->lines[$i]->fetch($object->lines[$i]->id);
						if ($valide > 0 || $object->specimen) {
							$extraDet														= '';
							$pdf->SetLineStyle($this->horLineStyle);
							// extrafieldsline
							if (!empty($this->show_ExtraFieldsLines))						$extraDet	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor);
							// Custom values (weight, volume and code
							$WVCC															= '';
							if ($this->showwvccchk)											$WVCC		= pdf_InfraSPlus_getlinewvdcc($object, $i, $outputlangs);
							$extraDet														.= empty($WVCC) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">'.$WVCC.'<hr style = "width: 80%;">' : $WVCC.'<hr style = "width: 80%;">');
							// Description of product line
							$txt															= $outputlangs->transnoentities("Date")." : ".dol_print_date($object->lines[$i]->date, 'dayhour', false, $outputlangs, true);
							if ($object->lines[$i]->duration > 0 && !$this->hide_duration)	$txt		.= " - ".$outputlangs->transnoentities("Duration")." : ".convertSecondToTime($object->lines[$i]->duration);
							$txt															= '<strong>'.dol_htmlentitiesbr($txt, 1, $outputlangs->charset_output).'</strong>';
							$desc															= dol_htmlentitiesbr($object->lines[$i]->desc, 1);
							$pageposdesc													= $pdf->getPage();
							if ($object->lines[$i]->date)									$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posxdesc, $curY, $txt.'<br style = "line-height: '.$default_font_size.'px;"/>'.$desc.(empty($extraDet) ? '' : '<br style = "line-height: '.$default_font_size.'px;"/>'.$extraDet), 0, 1, 0);
							else															pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->larg_util_txt, $this->tab_hl, $this->posxdesc, $curY, $hideref, 0, 0, $extraDet);
							$pageposafter													= $pdf->getPage();
							$posyafter														= $pdf->GetY();
							if ($pageposafter > $pageposbefore)	// There is a pagebreak
							{
								if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot)))	// There is no space left for total+free text
								{
									if ($i == ($nblignes - 1))	// No more lines, and no space left to show total, so we create a new page
									{
										$pdf->AddPage('','',true);
										$pdf->setPage($pageposafter + 1);
									}
								}
								else	$showpricebeforepagebreak	= 0; // we found a pagebreak
							}
							elseif ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot)))	// There is no space left for total+free text
							{
								if ($i == ($nblignes - 1))	// No more lines, and no space left to show total, so we create a new page
								{
									$pdf->AddPage('', '', true);
									pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
									$pdf->setPage($pageposafter + 1);
								}
							}
							$nexY			= $pdf->GetY();
							$pageposafter	= $pdf->getPage();
							$pdf->setPage($pageposbefore);
							$pdf->setTopMargin($this->marge_haute);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak))	$pdf->setPage($pageposdesc);
							$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut
							// Add dash or space between line
							if ($this->dash_between_line && $nbReport < ($nblignes - $this->atleastoneproduct - 1)) {
								$pdf->setPage($pageposafter);
								$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
								$nexY	+= 2;
							}
							else	$nexY	+= $this->lineSep_hight;
							// Detect if some page were added automatically and output _tableau for past pages
							while ($pagenb < $pageposafter) {
								$pdf->setPage($pagenb);
								$pagenb++;
								$pdf->setPage($pagenb);
								$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
								if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
								else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							}
							if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
								// New page
								$pdf->AddPage();
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
								$pagenb++;
								if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
								else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
								$nexY							= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
							}
						}
					}
					// Enregistrement de la position intermédiaire
					$this->endReport		= $nexY;
					$this->pageEndReport	= $pdf->getPage();
					$entreTable				= ($this->tab_hl * 1.5) + $this->decal_round;
					$this->startProd		= $this->endReport + $entreTable;
					$nexY					+= $this->hide_top_table ? $entreTable : ($entreTable + $this->ht_top_table);
					$nbProduct				= -1;
					// Loop on each lines for products / services
					for ($i = 0 ; $i < $nblignes ; $i++) {
						// Module management associé
						if (!$isProd[$i])					continue;	// we keep only product or service line
						$nbProduct++;	// On incrémente le nombre de ligne de produit / service
						$curY								= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table))	$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						else								$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore						= $pdf->getPage();
						$showpricebeforepagebreak			= 1;
						$valide								= empty($object->lines[$i]->id) ? 0 : $object->lines[$i]->fetch($object->lines[$i]->id);
						if ($valide > 0 || $object->specimen) {
							$colPicture			= $this->tableau['ref']['larg'] > 0 && $this->picture_in_ref ? 'ref' : 'desc';
							$imglinesize		= !empty($this->with_picture) ? pdf_InfraSPlus_getlineimgsize($this->tableau[$colPicture]['larg'], $realpatharray[$i]) : array();	// Define size of image if we need it
							if (isset($imglinesize['width']) && isset($imglinesize['height']) && $this->linkpictureurl) {
								$lineurl	= pdf_InfraSPlus_getlineurl($object, $i);
								$txturl		= !empty($lineurl) ? '<a href = "'.$lineurl.'" target = "_blank">'.pdf_InfraSPlus_formatNotes($object, $outputlangs, $this->linkpictureurl).'</a>' : '&nbsp;';
								$ht_url		= $pdf->getStringHeight($this->tableau[$colPicture]['larg'], $txturl);
							}
							// Hauteur de la référence
							$this->heightline	= $this->tab_hl;
							// Reference
							if ($isProduct && (!empty($this->show_ref_col) || !empty($this->show_num_col))) {
								$pdf->startTransaction();
								$startline			= $pdf->GetY();
								$ref				= !empty($this->show_ref_col) ? pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]) : $i + 1;
								$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $startline, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
								$endline			= $pdf->GetY();
								$heightRef			= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
								$this->heightline	= !empty($this->picture_in_ref) && isset($imglinesize['width']) && isset($imglinesize['height']) ? $imglinesize['height'] + $ht_url + ($this->tab_hl / 2) : 0;
								$this->heightline	+= empty($this->picture_in_ref) ? $heightRef : (empty($this->picture_replace_ref) ? $heightRef + $this->tab_hl : 0);
								$pdf->rollbackTransaction(true);
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
							if ($product_type != 9) {
								$pdf->SetLineStyle($this->horLineStyle);
								// Ajout du numéro de série, s'il existe...
								$serialEquip								= !empty($conf->equipement->enabled) ? pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, $i, 'intervention') : '';
								$extraDet									.= empty($serialEquip) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$serialEquip.'<hr style = "width: 80%;">';
								// extrafieldsline
								$extrafieldslines							= '';
								if (!empty($this->show_ExtraFieldsLines))	$extrafieldslines	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor);
								$extraDet									.= empty($extrafieldslines) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$extrafieldslines.'<hr style = "width: 80%;">';
								// Custom values (weight, volume and code
								$WVCC										= '';
								if ($this->showwvccchk)	$WVCC				= pdf_InfraSPlus_getlinewvdcc($object, $i, $outputlangs);
								$extraDet									.= empty($WVCC) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$WVCC.'<hr style = "width: 80%;">';
							}
							// Description of product line
							$txt									= $outputlangs->transnoentities("Date")." : ".dol_print_date($object->lines[$i]->datei, 'dayhour', false, $outputlangs, true);
							if ($object->lines[$i]->duration > 0)	$txt	.= " - ".$outputlangs->transnoentities("Duration")." : ".convertSecondToTime($object->lines[$i]->duration);
							$txt									= '<strong>'.dol_htmlentitiesbr($txt, 1, $outputlangs->charset_output).'</strong>';
							$desc									= dol_htmlentitiesbr($object->lines[$i]->desc, 1);
							$pageposdesc							= $pdf->getPage();
							$hide_desc								= !empty($hidedesc) ? $hidedesc : (!empty($object->lines[$i]->fk_product) && $this->only_one_desc ? (in_array($object->lines[$i]->fk_product, $listDescBib) ? 1 : 0) : 0);
							if ($object->lines[$i]->datei)			$pdf->writeHTMLCell($this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $txt.'<br>'.$desc.(empty($extraDet) ? '' : '<br/>'.$extraDet), 0, 1, 0);
							else									pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hide_desc, 0, $extraDet, $this->prodfichinter[$i], $this->desc_full_line);
							$ret									= !empty($object->lines[$i]->fk_product) ? $listDescBib[] = $object->lines[$i]->fk_product : '';
							$pageposafter							= $pdf->getPage();
							$posyafter								= $pdf->GetY();
							if ($pageposafter > $pageposbefore)	// There is a pagebreak
							{
								if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot)))	// There is no space left for total+free text
								{
									if ($i == ($nblignes - 1))	// No more lines, and no space left to show total, so we create a new page
									{
										$pdf->AddPage('','',true);
										$pdf->setPage($pageposafter + 1);
									}
								}
								else	$showpricebeforepagebreak	= 0; // we found a pagebreak
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
							// Quantity
							if (empty($this->hide_qty)) {
								$qty	= pdf_InfraSPlus_getlineqty($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
								$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							if (! $this->hide_cols) {
								// Reference
								if ((!empty($this->show_ref_col) || !empty($this->show_num_col)) && (empty($this->picture_in_ref) || (!empty($this->picture_in_ref) && empty($this->picture_replace_ref)))) {
									$pagepos	= $pdf->getPage();
									$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
									$pdf->setPage($pagepos);
								}
								// Unit price
								if (empty($this->hide_up)) {
									if (empty($this->hide_discount)) {
										if (empty($this->hide_vat))	$up_line	= pdf_InfraSPlus_getlineupexcltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i], $pricesObjProd[$i]);
										else						$up_line	= pdf_InfraSPlus_getlineupincltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i], $pricesObjProd[$i]);
									}
									else {
										if (empty($this->hide_vat))	$up_line	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
										else						$up_line	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
									}
									$pdf->MultiCell($this->tableau['up']['larg'], $this->heightline, $up_line, '', 'R', 0, 1, $this->tableau['up']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// VAT Rate
								if (empty($this->hide_vat) && empty($this->hide_vat_col)) {
									$vat_rate	= pdf_InfraSPlus_getlinevatrate($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
									$pdf->MultiCell($this->tableau['tva']['larg'], $this->heightline, $vat_rate, '', 'R', 0, 1, $this->tableau['tva']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// Discount on line
								if (($discount[$i] && empty($this->hide_discount)) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
									$remise_percent	= pdf_InfraSPlus_getlineremisepercent($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i], $pricesObjProd[$i]);
									$pdf->MultiCell($this->tableau['discount']['larg'], $this->heightline, $remise_percent, '', 'R', 0, 1, $this->tableau['discount']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// Discounted price
								if (($discount[$i] && empty($this->hide_discount) && $this->show_up_discounted) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
									if (empty($this->hide_vat))	$up_disc	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
									else						$up_disc	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
									$pdf->MultiCell($this->tableau['updisc']['larg'], $this->heightline, $up_disc, '', 'R', 0, 1, $this->tableau['updisc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// Total line
								if (empty($this->hide_vat))	$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
								else						$total_line = pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
								$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								if($this->show_ttc_col) {
									$totalTTC_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails);
									$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, $totalTTC_line, '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
							}
							// Add dash or space between line
							if ($this->dash_between_line && $nbProduct < ($this->atleastoneproduct - 1)) {
								$pdf->setPage($pageposafter);
								$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
								$nexY	+= 2;
							}
							else	$nexY	+= $this->lineSep_hight;
							// Detect if some page were added automatically and output _tableau for past pages
							while ($pagenb < $pageposafter) {
								$pdf->setPage($pagenb);
								$pagenb++;
								$pdf->setPage($pagenb);
								$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
								if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
								else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							}
							if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
								// New page
								$pdf->AddPage();
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
								$pagenb++;
								if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
								else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
								$nexY							= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
							}
						}
					}
					// Enregistrement de la position intermédiaire
					$this->endProd		= $nexY;
					$this->pageEndProd	= $pdf->getPage();
					$entreTable2		= ($this->tab_hl * 1.5) + $this->decal_round;
					$this->startNote	= $this->endProd + $entreTable2;
					$nexY				+= $this->hide_top_table ? $entreTable2 : ($entreTable2 + $this->ht_top_table);
					// Last note as table
					if (!empty($object->note_public) && $this->lastNoteAsTable) {
						$curY								= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table))	$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						else								$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore						= $pdf->getPage();
						$showpricebeforepagebreak			= 1;
						// Description of report
						$pdf->SetLineStyle($this->horLineStyle);
						$notetoshow							= pdf_InfraSPlus_formatNotes($object, $outputlangs, $object->note_public);
						$pdf->startTransaction();
						$pdf->writeHTMLCell($this->larg_desc, $this->tab_hl, $this->posxdesc, $curY, $notetoshow, 0, 1, 0);
						$pageposafter						= $pdf->getPage();
						$pageposdesc						= $pdf->getPage();
						$posyafter							= $pdf->GetY();
						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$pdf->rollbackTransaction(true);
							$pageposafter					= $pageposbefore;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
							$pageposdesc					= $pdf->getPage();
							$pdf->writeHTMLCell($this->larg_desc, $this->tab_hl, $this->posxdesc, $curY, $notetoshow, 0, 1, 0);
							$pageposafter					= $pdf->getPage();
							$posyafter						= $pdf->GetY();
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot)))	// There is no space left for total+free text
							{
								$pdf->AddPage('','',true);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
								$pdf->setPage($pageposafter + 1);
							}
							else	$showpricebeforepagebreak	= 0; // we found a pagebreak
						}
						elseif ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {
							$pdf->rollbackTransaction(true);
							$pageposafter					= $pageposbefore;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
							$pageposdesc					= $pdf->getPage();
							$pdf->writeHTMLCell($this->larg_desc, $this->tab_hl, $this->posxdesc, $curY, $notetoshow, 0, 1, 0);
							$pageposafter					= $pdf->getPage();
							$posyafter						= $pdf->GetY();
							$pdf->AddPage('','',true);
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
							$pdf->setPage($pageposafter + 1);
						}
						else																	$pdf->commitTransaction();	// No pagebreak
						$nexY																	= $pdf->GetY();
						$pageposafter															= $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak))	$pdf->setPage($pageposdesc);
						$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
					}
					for ($i = 1 ; $i < $pagenb ; $i++) {
						$pdf->setPage($i);
						$this->_pagefoot($pdf, $object, $outputlangs, 0);
						$bottom	= $this->page_hauteur - $heightforfooter;
						if (!empty($this->atleastoneproduct) && (!empty($object->note_public) && $this->lastNoteAsTable)) {
							if ($i == 1) {
								if ($i < $this->pageEndReport)	$this->_tableau1($pdf, $object, $tab_top, $bottom - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								elseif ($i == $this->pageEndReport && $i < $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startProd <= $bottom)	$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottom - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								}
								elseif ($i == $this->pageEndReport && $i == $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
									$this->_tableau($pdf, $object, $this->endReport + $entreTable, $this->endProd - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startNote <= $bottom)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
							}
							else {
								if ($i < $this->pageEndReport)	$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								elseif ($i == $this->pageEndReport && $i < $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startProd <= $bottom)	$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottom - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								}
								elseif ($i == $this->pageEndReport && $i == $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
									$this->_tableau($pdf, $object, $this->endReport + $entreTable, $this->endProd - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startNote <= $bottom)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
								elseif ($i > $this->pageEndReport && $i < $this->pageEndProd)	$this->_tableau($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
								elseif ($i > $this->pageEndReport && $i == $this->pageEndProd) {
									$this->_tableau($pdf, $object, $tab_top_newpage, $this->endProd - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startNote <= $bottom)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
								elseif ($i > $this->pageEndReport && $i > $this->pageEndProd)	$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
						}
						elseif (!empty($this->atleastoneproduct)) {
							if ($i == 1 && $i < $this->pageEndReport)	$this->_tableau1($pdf, $object, $tab_top, $bottom - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							elseif ($i < $this->pageEndReport)			$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
							elseif ($i == 1 && $i == $this->pageEndReport) {
								$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startProd <= $bottom)	$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottom - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
							}
							elseif ($i == $this->pageEndReport) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startProd <= $bottom)	$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottom - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
							}
							else	$this->_tableau($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
						}
						elseif (!empty($object->note_public) && $this->lastNoteAsTable) {
							if ($i == 1 && $i < $this->pageEndProd)	$this->_tableau1($pdf, $object, $tab_top, $bottom - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							elseif ($i < $this->pageEndProd)		$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i, 1);
							elseif ($i == 1 && $i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top, $this->endProd - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottom)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
							elseif ($i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endProd - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottom)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
							else	$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i, 1);
						}
						else {
							if ($i == 1)	$this->_tableau1($pdf, $object, $tab_top, $bottom - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							else			$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
						}
					}
					$pdf->setPage($pagenb);
					$bottomlasttab	= $this->page_hauteur - $heightforinfotot - $heightforfooter;
					if (!empty($this->atleastoneproduct) && (!empty($object->note_public) && $this->lastNoteAsTable)) {
						if ($i == 1) {
							if ($i < $this->pageEndReport)	$this->_tableau1($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							elseif ($i == $this->pageEndReport && $i < $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startProd <= $bottomlasttab)	$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottomlasttab - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
							}
							elseif ($i == $this->pageEndReport && $i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								$this->_tableau($pdf, $object, $this->endReport + $entreTable, $this->endProd - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottomlasttab)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
						}
						else {
							if ($i < $this->pageEndReport)	$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
							elseif ($i == $this->pageEndReport && $i < $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startProd <= $bottomlasttab)	$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottomlasttab - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
							}
							elseif ($i == $this->pageEndReport && $i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								$this->_tableau($pdf, $object, $this->endReport + $entreTable, $this->endProd - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottomlasttab)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
							elseif ($i > $this->pageEndReport && $i < $this->pageEndProd)	$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
							elseif ($i > $this->pageEndReport && $i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endProd - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottomlasttab)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
							elseif ($i > $this->pageEndReport && $i > $this->pageEndProd)	$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i, 1);
						}
					}
					elseif (!empty($this->atleastoneproduct)) {
						if ($i == 1 && $i < $this->pageEndReport)	$this->_tableau1($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
						elseif ($i < $this->pageEndReport)			$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
						elseif ($i == 1 && $i == $this->pageEndReport) {
							$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							if ($this->startProd <= $bottomlasttab)	$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottomlasttab - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
						}
						elseif ($i == $this->pageEndReport) {
							$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
							if ($this->startProd <= $bottomlasttab)	$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottomlasttab - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
						}
						else	$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
					}
					elseif (!empty($object->note_public) && $this->lastNoteAsTable) {
						if ($i == 1 && $i < $this->pageEndProd)	$this->_tableau1($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
						elseif ($i < $this->pageEndProd)		$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
						elseif ($i == 1 && $i == $this->pageEndProd) {
							$this->_tableau1($pdf, $object, $tab_top, $this->endProd - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							if ($this->startNote <= $bottomlasttab)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
						}
						elseif ($i == $this->pageEndProd) {
							$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endProd - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
							if ($this->startNote <= $bottomlasttab)	$this->_tableau1($pdf, $object, $this->endProd + $entreTable, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
						}
						else	$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i, 1);
					}
					else {
						if ($i == 1)	$this->_tableau1($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
						else			$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
					}
					$posytot									= !empty($this->atleastoneproduct) && $this->showtot ? $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0) : $bottomlasttab;
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$posysignarea								= $this->show_sign_area ? $this->_signature_area($pdf, $object, $posytot, $outputlangs, 0, 0) : $posytot;
					$posy										= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posysignarea, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages'))	$pdf->AliasNbPages();
					// If merge CGI is active
					if (!empty($this->CGI))						pdf_InfraSPlus_CGV($pdf, $this->CGI, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					// if merge files is active
					if (!empty($this->files))					pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters									= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook									= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
					if ($reshook < 0) {
						$this->error	= $hookmanager->error;
						$this->errors	= $hookmanager->errors;
					}
					if (!empty($this->main_umask))	@chmod($file, octdec($this->main_umask));
					$this->result					= array('fullpath' => $file);
					return 1;   // Pas d'erreur
				}
				else {
					$this->error=$outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			}
			else {
				$this->error=$outputlangs->transnoentities('ErrorConstantNotDefined', 'FICHEINTER_OUTPUTDIR');
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
				$txtref	= $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
				if ($object->statut == 0) {
					$pdf->SetTextColor(128, 0, 0);
					$txtref .= ' - '.$outputlangs->transnoentities('NotValidated');
				}
				$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
				$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
				$posy	+= $this->tab_hl;
				$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->datec, 'day', false, $outputlangs, true);
				$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
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
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'INTERREPFOLL'),
										'E' => $object->getIdContact('external', 'CUSTOMER')
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
		*   Show table with unique column for lines
		*
		*	@param		PDF			$pdf     		Object PDF
		*	@param  	Object		$object     	Object to show
		*	@param		string		$tab_top		Top position of table
		*	@param		string		$tab_height		Height of table (rectangle)
		*	@param		Translate	$outputlangs	Langs object
		*	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int			$hidebottom		Hide bottom bar of array
		*	@param		int			$pagenb			N° of page
		*	@param		int			$note			Table for note or for Report
		*	@return		void
		********************************************/
		protected function _tableau1(&$pdf, $object, $tab_top, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb, $note = '')
		{
			global $conf;

			// Force to disable hidetop and hidebottom
			$hidebottom			= 0;
			if ($hidetop)		$hidetop	= -1;
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				if (!empty($this->title_bg))	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				else if ($this->showtblline)	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				if ($this->showtblline)			$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				else							$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
			}
			else
				if ($this->showtblline)	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
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
			// Colonnes
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1)	$pdf->MultiCell($this->larg_util_txt, $this->ht_top_table, $outputlangs->transnoentities(($note ? "PDFInfraSPlusPiecesPrevision" : "PDFInfraSPlusRapport")), '', 'C', 0, 1, $this->posxdesc, $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
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
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusPieces'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (empty($this->hide_qty))	$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Qty"), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (! $this->hide_cols) {
					if (!empty($this->show_ref_col))	$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if (!empty($this->show_num_col))	$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusNum'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if (empty($this->hide_qty))			$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Qty'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if ($this->product_use_unit)		$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Unit'), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if (empty($this->hide_up)) {
						if (empty($this->hide_vat))	$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PriceUHT'), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
						else						$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PriceUTTC'), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
					if (empty($this->hide_vat) && empty($this->hide_vat_col))	$pdf->MultiCell($this->tableau['tva']['larg'], $this->ht_top_table, $outputlangs->transnoentities('VAT'), '', 'C', 0, 1, $this->tableau['tva']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if ($this->atleastonediscount && empty($this->hide_discount)) {
						$pdf->MultiCell($this->tableau['discount']['larg'], $this->ht_top_table, $outputlangs->transnoentities('ReductionShort'), '', 'C', 0, 1, $this->tableau['discount']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
						if ($this->show_up_discounted)	$pdf->MultiCell($this->tableau['updisc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusDiscountedPrice'), '', 'C', 0, 1, $this->tableau['updisc']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
					}
					if (empty($this->hide_vat))	$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalHTShort'), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					else						$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalTTCShort'), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if ($this->show_ttc_col)	$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalTTCShort'), '', 'C', 0, 1, $this->tableau['totalttc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
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
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalHTShort'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ht	= $this->pricefichinter['total_ht'];
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ht, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			if ((! $this->only_ht && ! $this->only_ttc) || $this->show_ttc_vat_tot) {
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
			if (! $this->only_ht) {
				$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities("TotalTTC"), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ttc	= $this->pricefichinter['total_ttc'];
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ttc, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			if (!empty($this->number_words)) {
				$index++;
				$savcurrency	= $conf->currency;
				$conf->currency	= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
				$total			= ! $this->only_ht ? $total_ttc : $total_ht;
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
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$signarea_top		= $posy + 1;
			$signarea_hl_emet	= $this->show_sign_area_emet ? $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('PDFInfraSPlusFicheInterSignTech')) : 0;
			$arrayidcontact		= array('I' => $object->getIdContact('internal', 'INTERVENING'),
										'E' => $object->getIdContact('external', 'CUSTOMER')
										);
			if ($this->show_sign_area_cli) {
				if (is_array($arrayidcontact['E']) && count($arrayidcontact['E']) > 0) {
					$object->fetch_contact($arrayidcontact['E'][0]);
					$textNameCli	= $outputlangs->convToOutputCharset($object->contact->getFullName($outputlangs, 1, 4));
				}
				$signarea_hl_cli	= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('PDFInfraSPlusFicheInterSignClient', $textNameCli));
			}
			if ($this->sign_area_full) {
				if (is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0) {
					$object->fetch_user($arrayidcontact['I'][0]);
					$textName			= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
					$signarea_hl_full	= $pdf->getStringHeight($larg_signarea, $textName);
				}
			}
			$signarea_hl	= $signarea_hl_emet < $signarea_hl_cli ? ($signarea_hl_cli < $signarea_hl_full ? $signarea_hl_full : $signarea_hl_cli) : ($signarea_hl_emet < $signarea_hl_full ? $signarea_hl_full : $signarea_hl_emet);
			$signarea_hl	= $signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl;
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$larg_signarea	= $this->larg_tabtotal;
			$posxsignarea1	= $this->marge_gauche;
			$posxsignarea2	= $this->posxtabtotal;
			if ($this->show_sign_area_emet) {
				$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusFicheInterSignTech'), '', 'L', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
				$pdf->RoundedRect($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			}
			if ($this->show_sign_area_cli) {
				$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusFicheInterSignClient', $textNameCli), '', 'L', 0, 1, $posxsignarea2 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
				$pdf->RoundedRect($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			}
			// Add internal intervening if defined
			if ($this->sign_area_full) {
				if (is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0) {
					$pdf->SetFont('', 'B', $default_font_size);
					$pdf->MultiCell($larg_signarea - ($this->decal_round * 2), $signarea_hl, $textName, '', 'C', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top + (($this->ht_signarea + $signarea_hl) / 2), true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 2);
				}
			}
			if ($this->signvalue)	pdf_InfraSPlus_Client_Sign($pdf, $this->signvalue, $larg_signarea, $this->ht_signarea, $posxsignarea2, $signarea_top + $signarea_hl);
			if ($calculseul) {
				$heightforarea	= $signarea_hl + $this->ht_signarea + 2;
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			}
			else {
				if ($this->e_signing)	$pdf->addEmptySignatureAppearance($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
				if ($this->e_signing)	$pdf->addEmptySignatureAppearance($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
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

			$showdetails	= $this->type_foot.(!empty($this->pied) ? 1 : 0);
			$noendline		= !empty($this->atleastoneproduct) && $this->showtot ? 0 : 1;
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle, $noendline);
		}
	}
?>