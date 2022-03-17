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
	* 	\file		../infraspackplus/core/modules/commande/doc/pdf_InfraSPlus_CBL.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF command
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_CBL extends ModelePDFCommandes
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
			$this->name							= $langs->trans('PDFInfraSPlusOrderBLName');
			$this->description					= $langs->trans('PDFInfraSPlusOrderBLDescription');
			$this->titlekey						= 'PDFInfraSPlusExpeditionTitle';
			$this->defaulttemplate				= isset($conf->global->COMMANDE_ADDON_PDF)																	? $conf->global->COMMANDE_ADDON_PDF								: '';
			$this->draft_watermark				= isset($conf->global->COMMANDE_DRAFT_WATERMARK)															? $conf->global->COMMANDE_DRAFT_WATERMARK						: '';
			$this->doli_addr_livr_recep			= isset($conf->global->INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP) && !empty($this->use_doli_addr_livr)	? $conf->global->INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP	: 0;
			$this->show_sign_area				= isset($conf->global->INFRASPLUS_PDF_COMMANDE_SHOW_SIGNATURE)												? $conf->global->INFRASPLUS_PDF_COMMANDE_SHOW_SIGNATURE			: 0;
			$this->show_ExtraFieldsLines		= isset($conf->global->INFRASPLUS_PDF_EXFL_C)																? $conf->global->INFRASPLUS_PDF_EXFL_C							: 0;
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
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_CBL') ? '' : '_CBL';
			$baseDir						= !empty($conf->commande->multidir_output[$conf->entity]) ? $conf->commande->multidir_output[$conf->entity] : $conf->commande->dir_output;

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
					$this->hide_cols					= $hookmanager->resArray['hidecols'];
					$this->showwvccchk					= $hookmanager->resArray['showwvccchk'];
					$this->signvalue					= $hookmanager->resArray['signvalue'];
					$this->add_recap					= $hookmanager->resArray['subtotal_add_recap'];
					$nblignes							= count($object->lines);	// Set nblignes with the new facture lines content after hook
					if (!empty($this->show_ref_col))	$hideref = 1;	// Comme on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information
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
					$tagvs					= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('PDFInfraSPlusExpeditionTitle'));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('PDFInfraSPlusExpeditionTitle').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
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
					$listObjBib				= array();
					$listDescBib			= array();
					$objproduct				= new Product($db);
					$this->nbrProdTot		= 0;
					$this->nbrProdDif		= array();
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$isProd	= $objproduct->fetch($object->lines[$i]->fk_product);
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
					if (empty($this->show_ref_col) && empty($this->show_num_col))	$this->larg_ref		= 0;
					if (empty($this->product_use_unit))								$this->larg_unit		= 0;
					if (empty($this->hide_cols))			$this->larg_desc	= $this->larg_util_cadre - ($this->larg_qty + $this->larg_unit + $this->larg_ref); // Largeur variable suivant la place restante
					else							$this->larg_desc	= $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					$this->tableau					= array('ref'		=> array('col' => $this->num_ref,	'larg' => $this->larg_ref,	'posx' => 0),
															'desc'		=> array('col' => $this->num_desc,	'larg' => $this->larg_desc,	'posx' => 0),
															'qty'		=> array('col' => $this->num_qty,	'larg' => $this->larg_qty,	'posx' => 0),
															'unit'		=> array('col' => $this->num_unit,	'larg' => $this->larg_unit,	'posx' => 0),
															);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->largcol1		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 2)	$this->largcol2		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 3)	$this->largcol3		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 4)	$this->largcol4		= $ncol_array['larg'];
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1 + $this->largcol1;
					$this->posxcol3		= $this->posxcol2 + $this->largcol2;
					$this->posxcol4		= $this->posxcol3 + $this->largcol3;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->tableau[$ncol]['posx']	= $this->posxcol1;
						elseif ($ncol_array['col'] == 2)	$this->tableau[$ncol]['posx']	= $this->posxcol2;
						elseif ($ncol_array['col'] == 3)	$this->tableau[$ncol]['posx']	= $this->posxcol3;
						elseif ($ncol_array['col'] == 4)	$this->tableau[$ncol]['posx']	= $this->posxcol4;
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
					$ht_coltotal			= 0;
					$ht2_coltotal			= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, (!empty($this->number_words) ? 1 : 0), 1, $this->horLineStyle) : 0;
					if ($this->show_sign_area) {
						if ($ht2_coltotal > 3)	$ht_coltotal	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 1);
						else					$ht_coltotal	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
					}
					$ht_coltotal		+= $ht2_coltotal;
					$heightforinfotot	= $ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal;
					$heightforinfotot	+= $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
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
						$largC11	= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
						$txtC12		= $outputlangs->transnoentities('PDFInfraSPlusOrderDate').' : '.dol_print_date($object->date_commande, 'day', false, $outputlangs, true);
						$largC12	= $this->larg_util_txt - $largC11;
						$xC12		= $this->posx_G_txt + $this->larg_util_txt - $largC12;
						$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
						$pdf->MultiCell($largC12, $this->tab_hl, $txtC12, 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
						$nexY		= $tab_top + $this->tab_hl + 1;
						if ($object->ref_client) {
							$txtC21		= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_client);
							$largC21	= $pdf->GetStringWidth($txtC21, '', '', $default_font_size - 1) + 3;
							$pdf->MultiCell($largC21, $this->tab_hl, $txtC21, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						$txtC22	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt + $largC21, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if (!empty($txtC22)) {
							$largC22	= $this->larg_util_txt - $largC21;
							$xC22		= $object->ref_client ? $this->posx_G_txt + $this->larg_util_txt - $largC22 : $this->posx_G_txt;
							$pdf->MultiCell($largC22, $this->tab_hl, $txtC22, 0, ($object->ref_client ? 'R' : 'L'), 0, 0, $xC22, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						if ($object->ref_client || !empty($txtC22))	$nexY	+= $this->tab_hl + 1;
						$height_header_inf							= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf							+= $this->tab_hl;
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
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1)				$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							else							$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1)				$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							else							$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							// New page
							$pdf->AddPage();
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
							$pagenb++;
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							$nexY							= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
						}
						if ($this->add_recap)	$subtotalRecap	= pdf_InfraSPlus_subtotal_getrecap ($object, $i, $subtotalRecap);	// SubTotal module with recap option
					}
					$bottomlasttab		= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1)	$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					else				$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $pagenb);
					$posyinfo			= $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$posyfreetext		= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $bottomlasttab, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $bottomlasttab;
					if ($this->show_sign_area) {
						if ($ht2_coltotal > 3)	$posysignarea	= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 1);
						else					$posysignarea	= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 0);
					}
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
						$posytotrecap					= pdf_InfraSPlus_subtotal_recap($pdf, $object, $tab_top_newpage, $outputlangs, $subtotalRecap, $this, 0, $heightforfooter);
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
						$this->_pagefoot($pdf, $object, $outputlangs, 0);
					}
					if (method_exists($pdf, 'AliasNbPages'))	$pdf->AliasNbPages();
					// If merge CGV is active
					if (!empty($this->CGV))						pdf_InfraSPlus_CGV($pdf, $this->CGV, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					// if merge files is active
					if (!empty($this->files))					pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters									= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'fromInfraS' => 1);
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
					$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			}
			else {
				$this->error	= $outputlangs->transnoentities('ErrorConstantNotDefined', 'COMMANDE_OUTPUTDIR');
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
				if (!empty($date_livraison)) {
					$posy	+= $this->tab_hl;
					$txtdt	= $outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($date_livraison, 'day', false, $outputlangs, true);
					$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				$pdf->SetFont('', '', $default_font_size - 2);
				if ($object->ref_client) {
					$posy	+= $this->tab_hl - 0.5;
					$txtcc	= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_client);
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
										'E' => $object->getIdContact('external', (!empty($this->doli_addr_livr_recep) ? 'SHIPPING' : 'CUSTOMER')),
										'L' => (empty($this->doli_addr_livr_recep) ? $object->getIdContact('external', 'SHIPPING') : '')
										);
				$addresses		= array();
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 1);
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
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->page_largeur - $this->marge_droite))	$pdf->line($this->posxcol2, $tab_top, $this->posxcol2, $tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->page_largeur - $this->marge_droite))	$pdf->line($this->posxcol3, $tab_top, $this->posxcol3, $tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->page_largeur - $this->marge_droite))	$pdf->line($this->posxcol4, $tab_top, $this->posxcol4, $tab_top + $tab_height, $this->verLineStyle);
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
			// Show Qty of products and number of different product for the document
			if ($this->show_qty_prod_tot && $this->nbrProdTot > 0) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$nbrProd		= $outputlangs->transnoentities('PDFInfraSPlusQtyProd', $this->nbrProdTot, count($this->nbrProdDif));
				$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $nbrProd, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 2;
			}
			// Show shipping date
			$date_livraison	= version_compare(DOL_VERSION, '13.0.0', '>=') ? $object->delivery_date : $object->date_livraison;
			if (!empty($date_livraison)) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('DateDeliveryPlanned').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$dlp			= dol_print_date($date_livraison, 'daytext', false, $outputlangs, true);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $dlp, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			}
			elseif ($object->availability_code || $object->availability)    // Show availability conditions
			{
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre				= $outputlangs->transnoentities('AvailabilityPeriod').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$lib_availability	= $outputlangs->transnoentities('AvailabilityType'.$object->availability_code) != ('AvailabilityType'.$object->availability_code) ? $outputlangs->transnoentities('AvailabilityType'.$object->availability_code) : $outputlangs->convToOutputCharset(isset($object->availability) ? $object->availability : '');
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
			$signarea_hl			= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('PDFInfraSPlusExpeditionCustomerSignature'));
			$signarea_hl			= $signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl;
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$larg_signarea			= $this->larg_tabtotal;
			$posxsignarea			= $this->posxtabtotal;
			if ($freetext)			$pdf->Line($posxsignarea, $posy, $this->page_largeur - $this->marge_droite, $posy, $this->horLineStyle);
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusExpeditionCustomerSignature'), '', 'L', 0, 1, $posxsignarea + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
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