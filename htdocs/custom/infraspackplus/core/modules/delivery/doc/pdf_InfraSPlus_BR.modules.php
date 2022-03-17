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
	* 	\file		../infraspackplus/core/modules/livraison/doc/pdf_InfraSPlus_BR.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF receipt
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/livraison/modules_livraison.php';
	require_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';

	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF proposal InfraS
	************************************************/
	class pdf_InfraSPlus_BR extends ModelePDFDeliveryOrder
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

			$langs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->name									= $langs->trans('PDFInfraSPlusReceiptName');
			$this->description							= $langs->trans('PDFInfraSPlusReceiptDescription');
			$this->titlekey								= 'DeliveryOrder';
			$this->defaulttemplate						= isset($conf->global->EXPEDITION_ADDON_PDF)							? $conf->global->EXPEDITION_ADDON_PDF							: '';
			$this->draft_watermark						= isset($conf->global->SHIPPING_DRAFT_WATERMARK)						? $conf->global->SHIPPING_DRAFT_WATERMARK						: '';
			$this->show_comm_col						= isset($conf->global->INFRASPLUS_PDF_BR_WITH_COMM_COLUMN)				? $conf->global->INFRASPLUS_PDF_BR_WITH_COMM_COLUMN				: 0;
			$this->desc_full_line						= $this->show_comm_col	? 0 : (isset($conf->global->INFRASPLUS_PDF_DESC_FULL_LINE) ? $conf->global->INFRASPLUS_PDF_DESC_FULL_LINE : 0);
			$this->show_bc_col							= isset($conf->global->INFRASPLUS_PDF_BL_WITH_BC_COLUMN)				? $conf->global->INFRASPLUS_PDF_BL_WITH_BC_COLUMN				: 0;
			$this->show_rel_col							= isset($conf->global->INFRASPLUS_PDF_BL_WITH_REL_COLUMN)				? $conf->global->INFRASPLUS_PDF_BL_WITH_REL_COLUMN				: 0;
			$this->exf_prod_pos							= isset($conf->global->INFRASPLUS_PDF_EXF_PROD_POS)						? $conf->global->INFRASPLUS_PDF_EXF_PROD_POS					: '';
			$this->hide_ordered							= isset($conf->global->INFRASPLUS_PDF_HIDE_ORDERED)						? $conf->global->INFRASPLUS_PDF_HIDE_ORDERED					: 0;
			$this->larg_ref								= isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_REF)					? $conf->global->INFRASPLUS_PDF_LARGCOLBR_REF					: 28;
			$this->larg_comm							= isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_COMM)					? $conf->global->INFRASPLUS_PDF_LARGCOLBR_COMM					: 50;
			$this->larg_unit							= isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_UNIT)					? $conf->global->INFRASPLUS_PDF_LARGCOLBR_UNIT					: 10;
			$this->larg_ordered							= isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_ORDERED)				? $conf->global->INFRASPLUS_PDF_LARGCOLBR_ORDERED				: 10;
			$this->larg_rel								= isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_REL)					? $conf->global->INFRASPLUS_PDF_LARGCOLBR_REL					: 10;
			$this->larg_qty								= isset($conf->global->INFRASPLUS_PDF_LARGCOLBR_QTY)					? $conf->global->INFRASPLUS_PDF_LARGCOLBR_QTY					: 10;
			$this->num_ref								= isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_REF)						? $conf->global->INFRASPLUS_PDF_NUMCOLBR_REF					: 1;
			$this->num_desc								= isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_DESC)					? $conf->global->INFRASPLUS_PDF_NUMCOLBR_DESC					: 2;
			$this->num_comm								= isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_COMM)					? $conf->global->INFRASPLUS_PDF_NUMCOLBR_COMM					: 3;
			$this->num_unit								= isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_UNIT)					? $conf->global->INFRASPLUS_PDF_NUMCOLBR_UNIT					: 4;
			$this->num_ordered							= isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_ORDERED)					? $conf->global->INFRASPLUS_PDF_NUMCOLBR_ORDERED				: 5;
			$this->num_rel								= isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_REL)						? $conf->global->INFRASPLUS_PDF_NUMCOLBR_REL					: 6;
			$this->num_qty								= isset($conf->global->INFRASPLUS_PDF_NUMCOLBR_QTY)						? $conf->global->INFRASPLUS_PDF_NUMCOLBR_QTY					: 7;
			$this->show_sign_area						= isset($conf->global->INFRASPLUS_PDF_EXPEDITION_SHOW_SIGNATURE)		? $conf->global->INFRASPLUS_PDF_EXPEDITION_SHOW_SIGNATURE		: 0;
			$this->show_ExtraFieldsLines				= isset($conf->global->INFRASPLUS_PDF_EXFL_E)							? $conf->global->INFRASPLUS_PDF_EXFL_E							: 0;
			$this->option_logo							= 1;	// Display logo
			$this->option_tva							= 0;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg						= 0;	// Display payment mode
			$this->option_condreg						= 0;	// Display payment terms
			$this->option_codeproduitservice			= 1;	// Display product-service code
			$this->option_multilang						= 1;	// Available in several languages
			$this->option_escompte						= 0;	// Displays if there has been a discount
			$this->option_credit_note					= 0;	// Support credit notes
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
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_BR') ? '' : '_BR';
			$baseDir						= !empty($conf->expedition->multidir_output[$conf->entity]) ? $conf->expedition->multidir_output[$conf->entity] : $conf->expedition->dir_output;

			if ($baseDir) {
				if (!empty($this->show_ExtraFieldsLines)) {
					$extrafieldsline	= new ExtraFields($db);
					$extralabelsline	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
				}
				// Definition of $dir and $file
				if ($object->specimen) {
					$this->show_ExtraFieldsLines	= '';
					$dir							= $baseDir.'/receipt';
					$file							= $dir.'/SPECIMEN.pdf';
				}
				else {
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/receipt/'.$objectref;
					$file		= $dir.'/'.$objectref.$filesufixe.'.pdf';
				}
				if (! file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error=$outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
						return 0;
					}
				}
				if (file_exists($dir)) {
					if (! is_object($hookmanager))	// Add pdfgeneration hook
					{
						include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
						$hookmanager					= new HookManager($db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters							= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook							= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
					$this->logo							= $hookmanager->resArray['logo'];
					$this->adr							= $hookmanager->resArray['adr'];
					$this->adrlivr						= $hookmanager->resArray['adrlivr'];
					$this->adrSst						= $hookmanager->resArray['adrSst'];
					$this->listfreet					= $hookmanager->resArray['listfreet'];
					$this->listnotep					= $hookmanager->resArray['listnotep'];
					$this->pied							= $hookmanager->resArray['pied'];
					$this->files						= $hookmanager->resArray['filesArray'];
					$this->usentascover					= $hookmanager->resArray['usentascover'];
					$this->showntusedascover			= $hookmanager->resArray['showntusedascover'];
					$this->with_picture					= $hookmanager->resArray['hidepict'];
					$hidedesc							= $hookmanager->resArray['hidedesc'];
					$this->showwvccchk					= $hookmanager->resArray['showwvccchk'];
					$this->signvalue					= $hookmanager->resArray['signvalue'];
					$nblignes							= count($object->lines);	// Set nblignes with the new facture lines content after hook
					if (!empty($this->show_bc_col))	$this->show_ref_col	= 0;	// Comme on affiche une colonne 'Code barre' on désactive la colonne 'Référence' qu'elle remplace
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
					$pdf->SetSubject($outputlangs->transnoentities('Shipment'));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Shipment'));
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
					if (!empty($this->show_rel_col)) {
						// We get the shipment that is the origin of delivery receipt
						$expedition								= new Expedition($db);
						$result									= $expedition->fetch($object->origin_id);
						$commande								= new Commande($db);	// Now we get the order that is origin of shipment
						if ($expedition->origin == 'commande')	$commande->fetch($expedition->origin_id);
						$object->commande						= $commande; // We set order of shipment onto delivery.
						$object->commande->loadExpeditions();
					}
					$this->totaux	= array();
					$qty_rel		= array();
					$realpatharray	= array();
					$prod_pos		= array();
					$listObjBib		= array();
					$objproduct		= new Product($db);
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$this->totaux['asked']		+= $object->lines[$i]->qty_asked;
						$this->totaux['shipped']	+= $object->lines[$i]->qty_shipped;
						$qty_rel[$i]				= $object->lines[$i]->qty_asked - $object->commande->expeditions[$object->lines[$i]->fk_origin_line];
						$this->totaux['rel']		+= $qty_rel[$i];
						$isProd						= $objproduct->fetch($object->lines[$i]->fk_product);
						// detect if there is at least one image to show
						if (!empty($this->with_picture) && $isProd > 0) {
							if (!empty($this->old_path_photo)) {
								$pdir[0] = get_exdir($objproduct->id, 2, 0, 0, $objproduct, 'product').$objproduct->id ."/photos/";
								$pdir[1] = get_exdir(0, 0, 0, 0, $objproduct, 'product').dol_sanitizeFileName($objproduct->ref).'/';
							}
							else {
								$pdir[0] = get_exdir(0, 0, 0, 0, $objproduct, 'product').((float) DOL_VERSION >= 13.0 ? '' : dol_sanitizeFileName($objproduct->ref).'/');	// default
								$pdir[1] = get_exdir($objproduct->id, 2, 0, 0, $objproduct, 'product').$objproduct->id ."/photos/";		// alternative
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
					}
					// Define width and position of notes frames
					$this->larg_util_txt			= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre			= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt				= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					$this->larg_ref					= !empty($this->show_bc_col) ? $this->wBC : (empty($this->show_ref_col) ? 0 : $this->larg_ref);
					if(! $this->show_comm_col)		$this->larg_comm	= 0;
					if(! $this->product_use_unit)	$this->larg_unit	= 0;
					if($this->hide_ordered)			$this->larg_ordered	= 0;
					if(! $this->show_rel_col)		$this->larg_rel		= 0;
					$this->larg_desc				= $this->larg_util_cadre - ($this->larg_ref + $this->larg_comm + $this->larg_unit + $this->larg_ordered + $this->larg_rel + $this->larg_qty); // Largeur variable suivant la place restante
					$this->tableau	= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
											'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
											'comm'		=> array('col' => $this->num_comm,		'larg' => $this->larg_comm,		'posx' => 0),
											'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
											'ordered'	=> array('col' => $this->num_ordered,	'larg' => $this->larg_ordered,	'posx' => 0),
											'rel'		=> array('col' => $this->num_rel,		'larg' => $this->larg_rel,		'posx' => 0),
											'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0)
											);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->largcol1		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 2)	$this->largcol2		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 3)	$this->largcol3		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 4)	$this->largcol4		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 5)	$this->largcol5		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 6)	$this->largcol6		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 7)	$this->largcol7		= $ncol_array['larg'];
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3		= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4		= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5		= $this->posxcol4	+ $this->largcol4;
					$this->posxcol6		= $this->posxcol5	+ $this->largcol5;
					$this->posxcol7		= $this->posxcol6	+ $this->largcol6;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->tableau[$ncol]['posx']	= $this->posxcol1;
						elseif ($ncol_array['col'] == 2)	$this->tableau[$ncol]['posx']	= $this->posxcol2;
						elseif ($ncol_array['col'] == 3)	$this->tableau[$ncol]['posx']	= $this->posxcol3;
						elseif ($ncol_array['col'] == 4)	$this->tableau[$ncol]['posx']	= $this->posxcol4;
						elseif ($ncol_array['col'] == 5)	$this->tableau[$ncol]['posx']	= $this->posxcol5;
						elseif ($ncol_array['col'] == 6)	$this->tableau[$ncol]['posx']	= $this->posxcol6;
						elseif ($ncol_array['col'] == 7)	$this->tableau[$ncol]['posx']	= $this->posxcol7;
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
					$ht_coltotal			= $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht2_coltotal			= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 1, 1, $this->horLineStyle) : 0;
					$ht_coltotal			+= $ht2_coltotal;
					$ht_signareas			= $this->show_2sign_area ? ($ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal) : $ht_coltotal;
					if ($this->show_sign_area) {

						if ($ht2_coltotal > 3)	$ht_signareas	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 1);
						else					$ht_signareas	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
					}
					$heightforinfotot	= $this->show_2sign_area ? $ht_signareas : ($ht_colinfo > $ht_signareas ? $ht_colinfo : $ht_signareas);
					$heightforinfotot	+= $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
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
					$tab_top	+= $height_incoterms;
					// Livraison
					$height_livr	= 0;
					$larg_livrshow	= $head['livrshow'] && $head['SsTshow'] ? ($this->larg_util_txt / 2) - 2 : $this->larg_util_txt;
					$larg_SsTshow	= $head['livrshow'] && $head['SsTshow'] ? ($this->larg_util_txt / 2) - 2 : $this->larg_util_txt;
					$posx_SsTshow	= $head['livrshow'] && $head['SsTshow'] ? $this->posx_G_txt + $larg_livrshow + 4 : $this->posx_G_txt;
					if ($head['livrshow']) {
						$pdf->SetFont('', 'B', $default_font_size + 2);
						$pdf->writeHTMLCell($larg_livrshow, $this->tab_hl, $this->posx_G_txt, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusLivr')), 0, 1);
						$xlivr	= $this->posx_G_txt + $pdf->GetStringWidth($outputlangs->transnoentities('PDFInfraSPlusLivr'), '', 'B', $default_font_size + 2) + 5;
						if ($this->adrlivr->name != '') {
							$pdf->SetFont('', 'B', $default_font_size);
							$pdf->writeHTMLCell($larg_livrshow - $xlivr - 3, $this->tab_hl, $xlivr, $tab_top + 0.6, dol_htmlentitiesbr($this->adrlivr->name), 0, 1);
							$nexY_livrshow	= $pdf->GetY();
						}
						else					$nexY_livrshow	= $tab_top + 0.6;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell($larg_livrshow - $xlivr - 3, $this->tab_hl, $xlivr, $nexY_livrshow, dol_htmlentitiesbr($head['livrshow']), 0, 1);
						$nexY_livrshow			= $pdf->GetY();
						$height_livr			= $this->Rounded_rect * 2 > $nexY_livrshow - $tab_top ? $this->Rounded_rect * 2 : $nexY_livrshow - $tab_top;
					}
					if ($head['SsTshow']) {
						$pdf->SetFont('', 'B', $default_font_size + 2);
						$pdf->writeHTMLCell($larg_SsTshow, $this->tab_hl, $posx_SsTshow, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusSsT')), 0, 1);
						$xSsT	= $posx_SsTshow + $pdf->GetStringWidth($outputlangs->transnoentities('PDFInfraSPlusSsT'), '', 'B', $default_font_size + 2) + 5;
						if ($this->adrSst->name != '') {
							$pdf->SetFont('', 'B', $default_font_size);
							$pdf->writeHTMLCell($larg_SsTshow - $xSsT - 3, $this->tab_hl, $xSsT, $tab_top + 0.6, dol_htmlentitiesbr($this->adrSst->name), 0, 1);
							$nexY_SsTshow	= $pdf->GetY();
						}
						else					$nexY_SsTshow	= $tab_top + 0.6;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell($larg_SsTshow - $xSsT - 3, $this->tab_hl, $xSsT, $nexY_SsTshow, dol_htmlentitiesbr($head['SsTshow']), 0, 1);
						$nexY_SsTshow			= $pdf->GetY();
						$height_SsT				= $this->Rounded_rect * 2 > $nexY_SsTshow - $tab_top ? $this->Rounded_rect * 2 : $nexY_SsTshow - $tab_top;
					}
					$height_adrs	= $height_livr > $height_SsT ? $height_livr : $height_SsT;
					if ($height_adrs) {
						if ($this->showtblline)	$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_adrs + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_adrs			+= $this->tab_hl;
					}
					$tab_top		+= $height_adrs;
					// Header informations after Address blocks
					if (!empty($this->header_after_addr)) {
						$height_header_inf	= 0;
						$tab_top			+= $this->space_headerafter;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
						$txtC11				= $outputlangs->transnoentities($this->titlekey).' '.$outputlangs->transnoentities('RefSending').' : '.$outputlangs->convToOutputCharset($object->ref);
						if ($object->statut == 0) {
							$pdf->SetTextColor(128, 0, 0);
							$txtC11	.= ' - '.$outputlangs->transnoentities('NotValidated');
						}
						$largC11	= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
						if ($object->date_delivery) {
							$txtC12		= $outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($object->date_delivery, 'dayhour', false, $outputlangs, true);
							$largC12	= $this->larg_util_txt - $largC11;
							$xC12		= $this->posx_G_txt + $this->larg_util_txt - $largC12;
							$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
							$pdf->MultiCell($largC12, $this->tab_hl, $txtC12, 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
							$pdf->SetFont('', '', $default_font_size - 1);
						}
						$nexY	= $tab_top + $this->tab_hl + 1;
						if ($object->ref_customer) {
							$txtC21		= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_customer);
							$largC21	= $pdf->GetStringWidth($txtC21, '', '', $default_font_size - 1) + 3;
							$pdf->MultiCell($largC21, $this->tab_hl, $txtC21, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						$txtC22	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt + $largC21, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if (!empty($txtC22)) {
							$largC22	= $this->larg_util_txt - $largC21;
							$xC22		= $object->ref_customer ? $this->posx_G_txt + $this->larg_util_txt - $largC22 : $this->posx_G_txt;
							$pdf->MultiCell($largC22, $this->tab_hl, $txtC22, 0, ($object->ref_customer ? 'R' : 'L'), 0, 0, $xC22, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						if ($object->ref_customer || !empty($txtC22))	$nexY	+= $this->tab_hl + 1;
						$height_header_inf							= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf							+= $this->tab_hl;
					}
					$tab_top	+= $height_header_inf;
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, -1);
					$tab_top		+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY			= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Loop on each lines
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$curY								= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table))	$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						else								$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore						= $pdf->getPage();
						$showpricebeforepagebreak			= 1;
						$imglinesize						= !empty($this->with_picture) ? pdf_InfraSPlus_getlineimgsize($this->tableau['desc']['larg'], $realpatharray[$i]) : array();	// Define size of image if we need it
						// Photo of product line first
						if (!empty($this->with_picture) && empty($this->picture_after)) {
							if (($curY + (isset($imglinesize['width']) && isset($imglinesize['height']) ? $imglinesize['height'] : $this->tab_hl)) > ($this->page_hauteur - ($heightforfooter)))	// If photo too high, we moved completely on new page
							{
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								$curY						= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
								$showpricebeforepagebreak	= 0;
							}
							$curY	= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau['desc']['posx'], $curY, $this->tableau['desc']['larg'], $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl);
						}
						// Hauteur de la référence
						$this->heightline	= $this->tab_hl;
						// Hauteur du code barre
						if (!empty($this->show_bc_col)) {
							$pdf->startTransaction();
							$BC					= pdf_InfraSPlus_writelineBC($pdf, $object, $i, $this->bodytxtcolor, $this->tableau['ref']['posx'], $curY, $this->wBC, $this->hBC);
							$this->heightline	= $BC < 1 ? $this->tab_hl : ($BC == 2 ? $this->dimC2D : $this->hBC);
							$pdf->rollbackTransaction(true);
						}
						// Hauteur de la Reference
						if (!empty($this->show_ref_col)) {
							$pdf->startTransaction();
							$startline			= $pdf->GetY();
							$ref				= pdf_getlineref($object, $i, $outputlangs, $hidedetails);
							$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $ref, '', 'L', 0, 1, $this->tableau['ref']['posx'], $startline, true, 0, 0, false, 0, 'M', false);
							$endline			= $pdf->GetY();
							$this->heightline	= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
							$pdf->rollbackTransaction(true);
						}
						// Extra fields & custom informations
						$pdf->SetLineStyle($this->horLineStyle);
						$extraDet									= '';
						// Ajout du numéro de série, s'il existe...
						$serialEquip								= !empty($conf->equipement->enabled) ? pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, $i, 'expedition') : '';
						$extraDet									.= empty($serialEquip) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$serialEquip.'<hr style = "width: 80%;">';
						// extrafieldsline
						$extrafieldslines							= '';
						if (!empty($this->show_ExtraFieldsLines))	$extrafieldslines	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor);
						$extraDet									.= empty($extrafieldslines) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$extrafieldslines.'<hr style = "width: 80%;">';
						// Custom values (weight, volume and code
						$WVCC										= '';
						if ($this->showwvccchk)	$WVCC				= pdf_InfraSPlus_getlinewvdcc($object, $i, $outputlangs);
						$extraDet									.= empty($WVCC) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$WVCC.'<hr style = "width: 80%;">';
						// Description of product line
						$pageposdesc	= $pdf->getPage();
						pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hidedesc, 0, $extraDet, null, $this->desc_full_line);
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
						if (!empty($this->with_picture) && !empty($this->picture_after)) {
							$pageposimg	= $pdf->getPage();
							if (($nexY + (isset($imglinesize['width']) && isset($imglinesize['height']) ? $imglinesize['height'] : $this->tab_hl)) > ($this->page_hauteur - ($heightforfooter + ($i == ($nblignes - 1) ? $heightforinfotot : 0))))	// If photo too high, we moved completely on new page
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
						// Bar code or ref
						if (!empty($this->show_bc_col))	pdf_InfraSPlus_writelineBC($pdf, $object, $i, $this->bodytxtcolor, $this->tableau['ref']['posx'], $curY, $this->wBC, $this->hBC);
						if (!empty($this->show_ref_col)) {
							$ref	= pdf_getlineref($object, $i, $outputlangs, $hidedetails);
							$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $ref, '', 'L', 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						}
						// Unit
						if ($this->product_use_unit) {
							$originLine	= new OrderLine($db);
							$originLine->fetch($object->lines[$i]->fk_origin_line);
							$unit		= $outputlangs->trans($originLine->getLabelOfUnit());
							$pdf->MultiCell($this->tableau['unit']['larg'], $this->heightline, $unit, '', 'L', 0, 1, $this->tableau['unit']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						}
						// Qty ordered
						if (empty($this->hide_ordered))		$pdf->MultiCell($this->tableau['ordered']['larg'], $this->heightline, $object->lines[$i]->qty_asked, '', 'C', 0, 1, $this->tableau['ordered']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Qty backorder
						if (!empty($this->show_rel_col))	$pdf->MultiCell($this->tableau['rel']['larg'], $this->heightline, $qty_rel[$i], '', 'C', 0, 1, $this->tableau['rel']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Qty to ship
						$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $object->lines[$i]->qty_shipped, '', 'C', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Add dash or space between line
						if ($this->dash_between_line && $i < ($nblignes - 1)) {
							$pdf->setPage($pageposafter);
							$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
							$nexY	+= 2;
						}
						else	$nexY	+= $this->lineSep_hight;
						// Detect if some page were added automatically and output _tableau for past pages
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
					}
					$bottomlasttab		= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1)	$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					else				$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $pagenb);
					$posyinfo			= $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$posytot			= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$posyfreetext		= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, 1, 0, $this->horLineStyle) : $posytot;
					$posysignarea		= $this->show_2sign_area ? ($posyinfo > $posyfreetext ? $posyinfo : $posyfreetext) : $posyfreetext;
					if ($this->show_sign_area) {
						if ($ht2_coltotal > 3)	$posyendsignarea	= $this->_signature_area($pdf, $object, $posysignarea, $outputlangs, 0, 1);
						else					$posyendsignarea	= $this->_signature_area($pdf, $object, $posysignarea, $outputlangs, 0, 0);
						$posy					= $this->show_2sign_area ? $posyendsignarea : ($posyinfo > $posyendsignarea ? $posyinfo : $posyendsignarea);
					}
					else										$posy	= $posysignarea;
					$posy										= $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posy, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posy;
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages'))	$pdf->AliasNbPages();
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
				$this->error=$outputlangs->transnoentities('ErrorConstantNotDefined', 'EXP_OUTPUTDIR');
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
				$txtref	= $outputlangs->transnoentities('RefSending').' : '.$outputlangs->convToOutputCharset($object->ref);
				if ($object->statut == 0) {
					$pdf->SetTextColor(128, 0, 0);
					$txtref .= ' - '.$outputlangs->transnoentities('NotValidated');
				}
				$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
				$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
				if ($object->date_delivery) {
					$posy	+= $this->tab_hl;
					$txtdt	= $outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($object->date_delivery, 'dayhour', false, $outputlangs, true);
					$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				$pdf->SetFont('', '', $default_font_size - 2);
				if ($object->ref_customer) {
					$posy	+= $this->tab_hl - 0.5;
					$txtcc	= '';
					$txtcc	.= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_customer);
					$pdf->MultiCell($w, $this->tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				if (!empty($this->show_num_cli) && !empty($this->num_cli_frm) && $object->thirdparty->code_client) {
					$txtNumCli	= $outputlangs->transnoentities('CustomerCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_client);
					$posy		+= $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $txtNumCli, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				if (!empty($this->show_code_cli_compt) && !empty($this->code_cli_compt_frm) && $object->thirdparty->code_compta) {
					$txtCodeCliCompt	= $outputlangs->transnoentities('CustomerAccountancyCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_compta);
					$posy		        += $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $txtCodeCliCompt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				// Show list of linked objects
				$posy	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, $this->tab_hl, $align);
				$posy	+= 0.5;
			}
			$dimCadres['Y']	= ($this->use_iso_location && $posy <= 40 ? 40 : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			if ($showaddress) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'SHIPPING')
										);
				$addresses		= array();
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', null, 0, $this->adrSst);
				$hauteurcadre	= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect);
			}
			$hauteurhead	= array('totalhead'		=> $dimCadres['Y'] + $hauteurcadre,
									'hauteurcadre'	=> $hauteurcadre,
									'livrshow'		=> $addresses['livrshow'],
									'SsTshow'		=> $addresses['SsTshow']
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
			if ($this->showverline && !$this->desc_full_line) {
				// Colonnes
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol2,		$tab_top, $this->posxcol2,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol3,		$tab_top, $this->posxcol3,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol4,		$tab_top, $this->posxcol4,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol5 > $this->posxcol4 && $this->posxcol5 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol5,		$tab_top, $this->posxcol5,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol6 > $this->posxcol5 && $this->posxcol6 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol6,		$tab_top, $this->posxcol6,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol7 > $this->posxcol6 && $this->posxcol7 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol7,		$tab_top, $this->posxcol7,	$tab_top + $tab_height, $this->verLineStyle);
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				if (!empty($this->show_bc_col))		$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusCB'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				if (!empty($this->show_ref_col))	$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->show_comm_col))	$pdf->MultiCell($this->tableau['comm']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Comments'), '', 'C', 0, 1, $this->tableau['comm']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				if ($this->product_use_unit)		$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Unit'), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				if (empty($this->hide_ordered))		$pdf->MultiCell($this->tableau['ordered']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ordered'), '', 'C', 0, 1, $this->tableau['ordered']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				if (!empty($this->show_rel_col))	$pdf->MultiCell($this->tableau['rel']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusExpeditionbackorder'), '', 'C', 0, 1, $this->tableau['rel']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('QtyShipped'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
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
			$larg_col1info		= 51;
			$larg_col2info		= $larg_tabinfo - $larg_col1info;
			$posxtabinfo		= $this->marge_gauche;
			$posxcol2info		= $posxtabinfo + $larg_col1info;
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$labelShipped		= $outputlangs->transnoentities('PDFInfraSPlusExpeditionTotalShipped').' : ';
			$pdf->MultiCell($larg_col1info, $tabinfo_hl, $labelShipped, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->MultiCell($larg_col2info, $tabinfo_hl, $this->totaux['shipped'], '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
			$posytabinfo		= $pdf->GetY() + 1;
			$pdf->SetFont('', 'B', $default_font_size - 2);
			if (empty($this->hide_ordered)) {
				$labelShipped		= $outputlangs->transnoentities('PDFInfraSPlusExpeditionTotalAsked').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $labelShipped, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $this->totaux['asked'], '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo		= $pdf->GetY() + 1;
			}
			if ($object->shipping_method_id > 0) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('SendingMethod').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$label			= '';
				$code			= $outputlangs->getLabelFromKey($db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');	// Get code using getLabelFromKey
				$label			.= $outputlangs->trans('SendingMethod'.strtoupper($code));
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $label, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			}	//if ($object->shipping_method_id > 0)
			if (!empty($object->tracking_number)) {
				$object->GetUrlTrackingStatus($object->tracking_number);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('TrackingNumber').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $object->tracking_number, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
				if (!empty($object->tracking_url) && $object->tracking_url != $object->tracking_number) {
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$titre			= $outputlangs->transnoentities('LinkToTrackYourPackage').' : ';
					$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 2);
					$pdf->writeHTMLCell($larg_col2info, $tabinfo_hl, $posxcol2info, $posytabinfo, $object->tracking_url, 0, 1, false, true, 'L');
					$posytabinfo	= $pdf->GetY() + 1;
				}
			}
			if ($calculseul) {
				$heightforinfo							= $posytabinfo - $posy;
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
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$posytabtot			= $posy + $this->ht_space_tot;
			$tabtot_hl			= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			// Tableau total
			$larg_tabtotal		= $this->larg_tabtotal;
			$larg_col2total		= $this->tableau['comm']['larg'];
			$larg_col1total		= $larg_tabtotal - $larg_col2total;
			$posxtabtotal		= $this->posxtabtotal;
			$posxcol2total		= $this->tableau['comm']['posx'];
			$index				= 0;
			// Totaux
			$pdf->MultiCell($larg_col1total, $tabtot_hl, ' ', '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
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
			$signarea_hl			= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ContactNameAndSignature', $object->thirdparty->name));
			$signarea_hl2			= $this->show_2sign_area ? $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ContactNameAndSignature', $this->adrSst->name)) : 0;
			$signarea_hl			= $signarea_hl < $signarea_hl2 ? ($signarea_hl2 < $this->tab_hl ? $this->tab_hl : $signarea_hl2) : ($signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$larg_signarea			= $this->larg_tabtotal;
			$posxsignarea1			= $this->marge_gauche;
			$posxsignarea2			= $this->posxtabtotal;
			if ($freetext)			$pdf->Line(($this->show_2sign_area ? $posxsignarea1 : $posxsignarea2), $posy, $this->page_largeur - $this->marge_droite, $posy, $this->horLineStyle);
			if ($this->show_2sign_area) {
				$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('ContactNameAndSignature', $this->adrSst->name), '', 'L', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
				$pdf->RoundedRect($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			}
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('ContactNameAndSignature', $object->thirdparty->name), '', 'L', 0, 1, $posxsignarea2 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
			$pdf->RoundedRect($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			if ($this->signvalue)	pdf_InfraSPlus_Client_Sign($pdf, $this->signvalue, $larg_signarea, $this->ht_signarea, $posxsignarea2, $signarea_top + $signarea_hl);
			if ($calculseul) {
				$heightforarea	= ($signarea_top + $signarea_hl + $this->ht_signarea + 1) - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			}
			else {
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
