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
	* 	\file		../infraspackplus/core/modules/product/doc/pdf_InfraSPlus_PBC.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF Barcode card for product
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF product card InfraS
	************************************************/
	class pdf_InfraSPlus_PBC extends ModelePDFProduct
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

			$this->name									= $langs->trans('PDFInfraSPlusBCProductName');
			$this->description							= $langs->trans('PDFInfraSPlusBCProductDescription');
			$this->emetteur								= $mysoc;
			if (empty($this->emetteur->country_code))	$this->emetteur->country_code									= substr($langs->defaultlang, -2);
			$this->type									= 'pdf';
			$this->defaulttemplate						= isset($conf->global->PRODUCT_ADDON_PDF)						? $conf->global->PRODUCT_ADDON_PDF : '';
			$this->use_fpdf								= isset($conf->global->MAIN_USE_FPDF)							? $conf->global->MAIN_USE_FPDF : 0;
			$this->main_umask							= isset($conf->global->MAIN_UMASK)								? $conf->global->MAIN_UMASK : '0755';
			$formatarray								= pdf_InfraSPlus_getFormat();
			$this->page_largeur							= $formatarray['width'];
			$this->page_hauteur							= $formatarray['height'];
			$this->format								= array($this->page_largeur, $this->page_hauteur);
			$this->marge_gauche							= 8;//isset($conf->global->MAIN_PDF_MARGIN_LEFT)					? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
			$this->marge_haute							= 14;//isset($conf->global->MAIN_PDF_MARGIN_TOP)						? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
			$this->marge_droite							= 6;//isset($conf->global->MAIN_PDF_MARGIN_RIGHT)					? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
			$this->marge_basse							= 12;//isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)					? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;
			$this->formatpage							= array('largeur'=>$this->page_largeur, 'hauteur'=>$this->page_hauteur, 'mgauche'=>$this->marge_gauche,
																'mdroite'=>$this->marge_droite, 'mhaute'=>$this->marge_haute, 'mbasse'=>$this->marge_basse);
			$this->multi_files							= isset($conf->global->INFRASPLUS_PDF_MULTI_FILES)				? $conf->global->INFRASPLUS_PDF_MULTI_FILES : 0;
			$this->font									= isset($conf->global->INFRASPLUS_PDF_FONT)						? $conf->global->INFRASPLUS_PDF_FONT : 'Helvetica';
			$this->bodytxtcolor							= isset($conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR)			? $conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR : 0;
			$this->bodytxtcolor							= explode(',', $this->bodytxtcolor);
			$this->option_logo							= 0;	// Display logo
			$this->option_tva							= 0;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg						= 0;	// Display payment mode
			$this->option_condreg						= 0;	// Display payment terms
			$this->option_codeproduitservice			= 1;	// Display product-service code
			$this->option_multilang						= 1;	// Available in several languages
			$this->option_escompte						= 0;	// Displays if there has been a discount
			$this->option_credit_note					= 0;	// Support credit notes
			$this->option_freetext						= 0;	// Support add of a personalised text
			$this->option_draft_watermark				= 0;	// Support add of a watermark on drafts
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
			global $user, $langs, $conf, $db, $hookmanager, $nblignes, $filebarcode;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_PBC') ? '' : '_PBC';
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
					$tagvs	= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
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
					$pagenb					= 1;
					// Default PDF parameters
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Define width
					$nbCol		= 3;
					$nbLine		= 8;
					$interline	= 0.5;
					$intercol	= 5;
					$largBC		= 63;//($this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($intercol * ($nbCol -1)))) / $nbCol;
					$htBC		= 34;//($this->page_hauteur - ($this->marge_haute + $this->marge_basse + ($interline * ($nbLine -1)))) / $nbLine;
					$curY		= $this->marge_haute;
					if ($object->barcode) {
						for ($i = 0 ; $i < $nbLine ; $i++) {
							$curX	= $this->marge_gauche;
							for ($ii = 0 ; $ii < $nbCol ; $ii++) {
								$pdf->writeHTMLCell($largBC, 4, $curX, $curY, dol_htmlentitiesbr($object->label), 0, 1, 0, 1, 'L');
								$pdf->writeHTMLCell($largBC, 4, $curX, $curY + 5, price($object->price, 1, $outputlangs, 1, 2, 2, $conf->currency), 0, 1, 0, 1, 'L');
								pdf_InfraSPlus_writelineBC($pdf, $object, -2, $this->bodytxtcolor, $curX, $curY + 10, 45, 0);
								$curX	+= $largBC + $intercol;
							}
							$curY	+= $htBC + $interline;
						}
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters	= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook	= $hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);
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
	}
?>
