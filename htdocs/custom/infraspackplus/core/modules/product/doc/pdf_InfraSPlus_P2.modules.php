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
	* 	\file		../infraspackplus/core/modules/product/doc/pdf_InfraSPlus_P2.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF product card
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF product card InfraS
	************************************************/
	class pdf_InfraSPlus_P2 extends ModelePDFProduct
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

			$langs->loadLangs(array('main', 'dict', 'products', 'companies', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->name							= $langs->trans('PDFInfraSPlusProductName2');
			$this->description					= $langs->trans('PDFInfraSPlusProductDescription');
			$this->update_main_doc_field		= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->defaulttemplate				= isset($conf->global->PRODUCT_ADDON_PDF)				? $conf->global->PRODUCT_ADDON_PDF					: '';
			$this->frmeLineW					= isset($conf->global->INFRASPLUS_PDF_FRM_E_LINE_WIDTH)	? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_WIDTH	: 0.2;
			$this->frmeLineDash					= isset($conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH)	? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_DASH		: '0';
			$this->frmeLineColor				= isset($conf->global->INFRASPLUS_PDF_FRM_E_LINE_COLOR)	? $conf->global->INFRASPLUS_PDF_FRM_E_LINE_COLOR	: '';
			$this->frmeLineColor				= explode(',', $this->frmeLineColor);
			$this->frmeBgColor					= isset($conf->global->INFRASPLUS_PDF_FRM_E_BG_COLOR)	? $conf->global->INFRASPLUS_PDF_FRM_E_BG_COLOR		: '';
			$this->frmeBgColor					= explode(',', $this->frmeBgColor);
			$this->show_adr						= isset($conf->global->INFRASPLUS_PDF_SHOW_ADR_PROD)	? $conf->global->INFRASPLUS_PDF_SHOW_ADR_PROD		: 0;
			$this->hidepagenum					= 1;
			$this->wpicture						= isset($conf->global->INFRASPLUS_PDF_PICTURE_WIDTH)	? $conf->global->INFRASPLUS_PDF_PICTURE_WIDTH		: 20;
			$this->hpicture						= isset($conf->global->INFRASPLUS_PDF_PICTURE_HEIGHT)	? $conf->global->INFRASPLUS_PDF_PICTURE_HEIGHT		: 32;
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 0;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 0;	// Display payment mode
			$this->option_condreg				= 0;	// Display payment terms
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_escompte				= 0;	// Displays if there has been a discount
			$this->option_credit_note			= 0;	// Support credit notes
			$this->option_freetext				= 1;	// Support add of a personalised text
			$this->option_draft_watermark		= 0;	// Support add of a watermark on drafts
		}

		/********************************************
		*	Function to build pdf onto disk
		*
		*	@param		Product		$object				Object source to build document
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
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_P2') ? '' : '-P2';
			$baseDir						= !empty($conf->product->multidir_output[$conf->entity]) ? $conf->product->multidir_output[$conf->entity] : $conf->product->dir_output;

			if ($baseDir) {
				// Definition of $dir and $file
				if ($object->specimen) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				}
				else {
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/'.$objectref;
					$file		= $dir.'/'.$objectref.$filesufixe.'.pdf';
				}
				$productFournisseur		= new ProductFournisseur($db);
				$supplierprices			= $productFournisseur->list_product_fournisseur_price($object->id);
				$object->supplierprices	= $supplierprices;
				if (! file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error	= $outputlangs->transnoentities("ErrorCanNotCreateDir", $dir);
						return -1;
					}
				}
				if (file_exists($dir)) {
					if (! is_object($hookmanager))	// Add pdfgeneration hook
					{
						include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
						$hookmanager	= new HookManager($db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters			= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook			= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
					$this->logo			= $hookmanager->resArray['logo'];
					$this->listfreet	= $hookmanager->resArray['listfreet'];
					$this->listnotep	= $hookmanager->resArray['listnotep'];
					$this->pied			= $hookmanager->resArray['pied'];
					// Create pdf instance
					$pdf				= pdf_getInstance($this->format);
					$default_font_size	= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
					$pdf->SetAutoPageBreak(1, 0);
					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}
					$pdf->SetFont($this->font);
					// reduce the top margin before ol / il tag
					$tagvs								= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities("Product"));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Product")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
					$pagenb								= 1;
					// Default PDF parameters
					$this->stdLineW						= 0.2; // épaisseur par défaut dans TCPDF = 0.2
					$this->stdLineDash					= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->stdLineCap					= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->stdLineColor					= array(128, 128, 128);
					$this->stdLineStyle					= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->bgLineW						= $this->tblLineW; // épaisseur par défaut dans TCPDF = 0.2
					$this->bgLineDash					= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->bgLineCap					= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->bgLineColor					= $this->bg_color;
					$this->bgLineStyle					= array('width'=>$this->bgLineW, 'dash'=>$this->bgLineDash, 'cap'=>$this->bgLineCap, 'color'=>$this->bgLineColor);
					$this->frmeLineCap					= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->frmeLineStyle				= array('width'=>$this->frmeLineW, 'dash'=>$this->frmeLineDash, 'cap'=>$this->frmeLineCap, 'color'=>$this->frmeLineColor);
					$this->tblLineCap					= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->tblLineStyle					= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>(! empty($this->title_bg) && ! $this->showtblline ? $this->bg_color : $this->tblLineColor));
					$this->horLineStyle					= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Define width and position of notes frames
					$this->larg_util_txt				= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + 2);
					$this->larg_util_cadre				= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt					= $this->marge_gauche + 1;
					// Calculs de positions
					$this->tab_hl						= 4;
					$tab_top							= 0;
					$tab_top_newpage					= (empty($this->small_head2) ? $this->hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table					= $this->height_top_table + $this->tab_hl * 0.5;
					$heightforinfotot					= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle);
					$heightforfooter					= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					$bottomlasttab						= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					$sortfield							= 'position_name';
					$sortorder							= 'asc';
					$posxpicture						= $this->posx_G_txt;
					$posypicture						= $curY + 0.5;
					if (! empty($this->old_path_photo))	$pdir = get_exdir($this->id,2,0,0,$this,'product') . $this->id ."/photos/";
					else								$pdir = get_exdir(0, 0, 0, 0, $object, 'product').dol_sanitizeFileName($object->ref).'/';
					$dir								= $baseDir.'/'.$pdir;
					// Defined relative dir to DOL_DATA_ROOT
					$relativedir						= '';
					if ($dir) {
						$relativedir	= preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $dir);
						$relativedir	= preg_replace('/^[\\/]/','',$relativedir);
						$relativedir	= preg_replace('/[\\/]$/','',$relativedir);
					}
					$filearray				= dol_dir_list($dir, 'files', 0, '(.jpg|.jpeg|.png)$', null, $sortfield, (strtolower($sortorder)=='desc' ? SORT_DESC : SORT_ASC), 1);
					$filearrayindatabase	= dol_dir_list_in_database($relativedir, '', null, 'name', SORT_ASC);
					// Complete filearray with properties found into $filearrayindatabase
					foreach ($filearray as $key => $val) {
						// Search if it exists into $filearrayindatabase
						foreach ($filearrayindatabase as $key2 => $val2) {
							if ($filearrayindatabase[$key2]['name'] == $filearray[$key]['name']) {
								$filearray[$key]['position_name']	= ($filearrayindatabase[$key2]['position'] ? $filearrayindatabase[$key2]['position'] : '0').'_'.$filearrayindatabase[$key2]['name'];
								$filearray[$key]['position']		= $filearrayindatabase[$key2]['position'];
								$filearray[$key]['cover']			= $filearrayindatabase[$key2]['cover'];
								$filearray[$key]['acl']				= $filearrayindatabase[$key2]['acl'];
								$filearray[$key]['rowid']			= $filearrayindatabase[$key2]['rowid'];
								$filearray[$key]['label']			= $filearrayindatabase[$key2]['label'];
								break;
							}
						}
					}
					if (count($filearray)) {
						if ($sortfield && $sortorder)	$filearray	= dol_sort_array($filearray, $sortfield, $sortorder);
						$this->wpicture					= ($this->larg_util_txt - ((count($filearray) - 1) * 5)) / count($filearray);	// corrige la largeur maximal des images pour être au plus égale à la largeur disponible / nombre de vignette à afficher
						$imglinesize					= array();
						$nbimg							= 0;
						foreach ($filearray as $key => $val) {
							$photo	= $val['name'];
							if (image_format_supported($photo) >= 0) {
								if (empty($this->cat_hq_image))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
								{
									$vignette					= $dir.'thumbs/'.basename(getImageFileNameForSize($dir.$photo, '_small'));
									if (dol_is_file($vignette)) $realpath		= $vignette;
									else						$realpath		= $dir.$photo;
								}
								else				$realpath		= $dir.$photo;
								if ($nbimg == 1)	// there is a second picture
								{
									$hasimg2	= 1;
									break;
								}
								if ($realpath)		$imglinesize	= pdf_InfraSPlus_getSizeForImage($realpath, $this->page_largeur, $this->page_hauteur / 4, 1);
								if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
									$posxpicture		= ($this->page_largeur - $imglinesize['width']) / 2;	// centre l'image
									$pdf->Image($realpath, $posxpicture, 0, $imglinesize['width'], $imglinesize['height']);	// Use 300 dpi
									$logodir			= !empty($conf->mycompany->multidir_output[$objEntity]) ? $conf->mycompany->multidir_output[$objEntity] : $conf->mycompany->dir_output;
									if ($this->logo)	$logo	= $logodir.'/logos/'.$this->logo;
									else				$logo	= $logodir.'/logos/'.$this->emetteur->logo;
									if ($logo) {
										if (is_file($logo) && is_readable($logo)) {
											$logosize	= array();
											$logosize	= pdf_InfraSPlus_getSizeForImage($logo, $imglinesize['width'] / 4, $imglinesize['height'] / 4, 1);
											$pdf->Image($logo, $posxpicture + $imglinesize['width'] - $logosize['width'], $imglinesize['height'] - $logosize['height'], $logosize['width'], $logosize['height']);
										}
									}
									$nbimg ++;
								}
							}
						}
						$tab_top	+= $imglinesize['height'] + $this->tab_hl;	// $pdf->Image does not increase value return by getY, so we save it manually
					}
					$curY	= $tab_top + $this->ht_top_table + $this->bgLineW + ($this->tab_hl * 0.5);
					// Label and Ref.
					$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
					$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
					$title			= $outputlangs->transnoentities($object->label);
					$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, dol_htmlentitiesbr($title), 0, 1, 0);
					$curY			= $pdf->GetY() + $this->tab_hl;
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$txtref			= $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
					$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, dol_htmlentitiesbr($txtref), 0, 1, 0);
					$curY			= $pdf->GetY();
					for ($i = 0 ; $i < 2 ; $i++)	// 2 turns : first for measuring purpose, second to write
					{
						$pdf->startTransaction();
						if ($i == 0)	$posybefore	= $curY;	// first pass => recording
						else			$curY	= $posybefore + (($bottomlasttab - $posybefore - $height_desc) / 2);	// first pass => adjusting blank space
						// Affiche notes
						$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $curY, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $heightforfooter, $this->page_hauteur, 0, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, -1);
						$curY			+= $height_note;
						// Description
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (!empty($hasimg2)) {
							if ($realpath)			$imglinesize	= pdf_InfraSPlus_getSizeForImage($realpath, $this->larg_util_txt / 3, $this->page_hauteur / 6, 1);
							if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
								$posxpicture	= $this->posx_G_txt + $this->larg_util_txt - $imglinesize['width'];	// centre l'image
								$pdf->Image($realpath, $posxpicture, $curY, $imglinesize['width'], $imglinesize['height']);	// Use 300 dpi
								$larg_util_txt	= $this->larg_util_txt - $imglinesize['width'] - 5;
								$hasimg			= 1;
							}
						}
						else	$larg_util_txt	= $this->larg_util_txt;
						$txtDesc	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $object->description);
						$txtNotes2	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $object->note);
						$pdf->writeHTMLCell($larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, dol_htmlentitiesbr($txtDesc.(!empty($txtNotes2) ? '<br />'.$txtNotes2 : '')), 0, 1, 0);
						$curY		= ($hasimg && ($curY + $imglinesize['height'] > $pdf->GetY()) ? $curY + $imglinesize['height'] : $pdf->GetY()) + $this->tab_hl;
						if ($object->url) {
							$txturl		= '<a href = "'.$object->url.'" target = "_blank">'.$object->url.'</a>';
							$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, $outputlangs->trans("PublicUrl").' : '.$object->url, 0, 1);
							$curY		= $pdf->GetY() + $this->tab_hl;
						}
						$txtDim	=	pdf_InfraSPlus_getlinewvdcc($object, 'P', $outputlangs);
						$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, $txtDim, 0, 1);
						$curY	= $pdf->GetY();
						if ($i == 0) {
							$height_desc	= $curY - $posybefore;
							$pdf->rollbackTransaction(true);
						}
						else	$pdf->commitTransaction();
					}
					$this->_tableau($pdf, $object, $tab_top, $height_note, $bottomlasttab - $tab_top, $outputlangs, 0, 0, $pagenb);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $bottomlasttab, $outputlangs, $this->emetteur, $this->listfreet, 0, 0);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages'))	$pdf->AliasNbPages();
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters		= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook		= $hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);
					if ($reshook < 0) {
						$this->error	= $hookmanager->error;
						$this->errors	= $hookmanager->errors;
					}
					if (! empty($this->main_umask))	@chmod($file, octdec($this->main_umask));
					$this->result					= array('fullpath' => $file);
					return 1;   // Pas d'erreur
				}
				else {
					$this->error=$outputlangs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}
			else {
				$this->error=$outputlangs->trans("ErrorConstantNotDefined","PRODUCT_OUTPUTDIR");
				return 0;
			}
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
		protected function _tableau(&$pdf, $object, $tab_top, $height_note, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb) {
			global $conf;

			// Force to disable hidetop and hidebottom
			$hidebottom			= 0;
			if ($hidetop)		$hidetop	= -1;
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (! empty($this->title_bg))	$pdf->RoundedRect(0, $tab_top, $this->page_largeur, $this->ht_top_table / 2, 0, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
			// Show Folder mark
			if (! empty($this->fold_mark)) {
				$pdf->Line(0, ($this->page_hauteur)/3, $this->fold_mark, ($this->page_hauteur)/3, $this->stdLineStyle);
				$pdf->Line($this->page_largeur - $this->fold_mark, ($this->page_hauteur)/3, $this->page_largeur, ($this->page_hauteur)/3, $this->stdLineStyle);
			}
		}

		/********************************************
		*	Show footer of page. Need this->emetteur object
		*,
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
			if (! empty($this->pied))	$showdetails	.= 1;
			else						$showdetails	.= 0;
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 1, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
?>
