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
	* 	\file		../infraspackplus/core/modules/expedition/doc/pdf_InfraSPlus_ET.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF expedition
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF proposal InfraS
	************************************************/
	class pdf_InfraSPlus_ET extends ModelePdfExpedition
	{
		var $db;
		var $name;
		var $description;
		var $type;
		var $phpmin		= array(5, 5);
		var $version	= 'dolibarr';
		var $page_largeur;
		var $page_hauteur;
		var $format;
		var $marge_gauche;
		var	$marge_droite;
		var	$marge_haute;
		var	$marge_basse;
		var $emetteur;

		/********************************************
		*	Constructor
		*
		*	@param		DoliDB		$db      Database handler
		********************************************/
		public function __construct($db)
		{
			global $conf, $langs, $mysoc;

			$langs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'infraspackplus@infraspackplus'));

			$this->name									= $langs->trans('PDFInfraSPlusEtiquetteName');
			$this->description							= $langs->trans('PDFInfraSPlusEtiquetteDescription');
			$this->emetteur								= $mysoc;
			if (empty($this->emetteur->country_code))	$this->emetteur->country_code									= substr($langs->defaultlang, -2);
			$this->type									= 'pdf';
			$this->defaulttemplate						= isset($conf->global->EXPEDITION_ADDON_PDF)					? $conf->global->EXPEDITION_ADDON_PDF : '';
			$this->multilangs							= isset($conf->global->MAIN_MULTILANGS)							? $conf->global->MAIN_MULTILANGS : 0;
			$this->use_fpdf								= isset($conf->global->MAIN_USE_FPDF)							? $conf->global->MAIN_USE_FPDF : 0;
			$this->main_umask							= isset($conf->global->MAIN_UMASK)								? $conf->global->MAIN_UMASK : '0755';
			$formatarray								= pdf_InfraSPlus_getFormat('ET-EXP');
			$this->page_largeur							= $formatarray['width'];
			$this->page_hauteur							= $formatarray['height'];
			$this->format								= array($this->page_largeur, $this->page_hauteur);
			$this->marge_gauche							= isset($conf->global->MAIN_PDF_MARGIN_LEFT)					? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
			$this->marge_haute							= isset($conf->global->MAIN_PDF_MARGIN_TOP)						? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
			$this->marge_droite							= isset($conf->global->MAIN_PDF_MARGIN_RIGHT)					? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
			$this->marge_basse							= isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)					? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;
			$this->formatpage							= array('largeur'=>$this->page_largeur, 'hauteur'=>$this->page_hauteur, 'mgauche'=>$this->marge_gauche,
																'mdroite'=>$this->marge_droite, 'mhaute'=>$this->marge_haute, 'mbasse'=>$this->marge_basse);
			$this->includealias							= isset($conf->global->PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME)	? $conf->global->PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME : 0;
			$this->multi_files							= isset($conf->global->INFRASPLUS_PDF_MULTI_FILES)				? $conf->global->INFRASPLUS_PDF_MULTI_FILES : 0;
			$this->font									= isset($conf->global->INFRASPLUS_PDF_FONT)						? $conf->global->INFRASPLUS_PDF_FONT : 'Helvetica';
			$this->headertxtcolor						= isset($conf->global->INFRASPLUS_PDF_HEADER_TEXT_COLOR)		? $conf->global->INFRASPLUS_PDF_HEADER_TEXT_COLOR : 0;
			$this->headertxtcolor						= explode(',', $this->headertxtcolor);
			$this->bodytxtcolor							= isset($conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR)			? $conf->global->INFRASPLUS_PDF_BODY_TEXT_COLOR : 0;
			$this->bodytxtcolor							= explode(',', $this->bodytxtcolor);
			$this->showadrlivr							= isset($conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION)	? $conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION : 0;
			$this->option_logo							= 0;	// Affiche logo
			$this->option_freetext						= 0;	// Support add of a personalised text
			$this->option_draft_watermark				= 0;	// Support add of a watermark on drafts
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
			global $user, $langs, $conf, $db, $hookmanager;

			if (! is_object($outputlangs)) $outputlangs	= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (! empty($this->use_fpdf)) $outputlangs->charset_output	= 'ISO-8859-1';
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_ET') ? '' : '_ET';
			$baseDir						= !empty($conf->expedition->multidir_output[$conf->entity]) ? $conf->expedition->multidir_output[$conf->entity] : $conf->expedition->dir_output;

			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'infraspackplus@infraspackplus'));

			if ($baseDir) {
				// Definition of $dir and $file
				if ($object->specimen) {
					$dir	= $baseDir.'/sending';
					$file	= $dir.'/SPECIMEN.pdf';
				}
				else {
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/sending/'.$objectref;
					$file		= $dir.'/'.$objectref.$filesufixe.'.pdf';
				}
				if (! file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
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
					$parameters			= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook			= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
					$this->logo			= $hookmanager->resArray['logo'];
					$this->adrlivr		= $hookmanager->resArray['adrlivr'];
					// Create pdf instance
					$pdf				= pdf_getInstance($this->format, 'mm', 'P');
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
					$pdf->SetSubject($outputlangs->transnoentities("Shipment"));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Shipment"));
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
					// New page
					$pdf->AddPage();
					$pagenb					= 1;
					// Default PDF parameters
					$this->stdLineW			= 0.2; // épaisseur par défaut dans TCPDF = 0.2
					$this->stdLineDash		= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->stdLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->stdLineColor		= array(128, 128, 128);
					$this->stdLineStyle		= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size);
					$pdf->SetDrawColor(0, 0, 0);
					// Define width and position of notes frames
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					// Calculs de positions
					$this->tab_hl			= 6;
					$this->hBC				= 20;
					if ($this->logo)	$logo	= $conf->mycompany->dir_output.'/logos/'.$this->logo;
					else				$logo	= $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
					if ($logo) {
						if (is_file($logo) && is_readable($logo)) {
							$heightLogo	= pdf_getHeightForLogo($logo);
							$pdf->Image($logo, $this->marge_gauche, $this->marge_haute, 0, $heightLogo, '', '', '', false, '', 'C');	// width=0 (auto)
						}
						else {
							$pdf->SetTextColor(200, 0, 0);
							$pdf->SetFont('', 'B', $default_font_size - 2);
							$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $outputlangs->transnoentities("ErrorInfraSPlusParamLogoFileNotFound", $logo), '', 'C', 0, 1, $this->marge_gauche, $this->marge_haute, true, 0, 0, false, 0, 'M', false);
							$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), '', 'C', 0, 1, $this->marge_gauche, $pdf->getY() + 1, true, 0, 0, false, 0, 'M', false);
							$pdf->SetTextColor($headertxtcolor[0], $headertxtcolor[1], $headertxtcolor[2]);
							$heightLogo	= $pdf->getY() + 1;
						}
					}
					else {
						$text		= $emetteur->name;
						$pdf->MultiCell($w, $this->tab_hl, $outputlangs->convToOutputCharset($text), '', 'C', 0, 1, $this->marge_gauche, $this->marge_haute, true, 0, 0, false, 0, 'M', false);
						$heightLogo = $this->tab_hl;
					}
					$posy		= $this->marge_haute + $heightLogo + $this->tab_hl;
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->Line($this->marge_gauche, $posy, $this->page_largeur - $this->marge_droite, $posy, $this->stdLineStyle);
					$posy	+= 2;
					$refcom		= '';
					$datec		= '';
					$nbrProdTot	= 0;
					if (!empty($object->origin) && $object->origin_id > 0) {
						$typeobject	= $object->origin;
						$origin		= $object->origin;
						$origin_id	= $object->origin_id;
						$object->fetch_origin();         // Load property $object->commande, $object->propal, ...
						if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled)) {
							$objectsrc								= new Commande($db);
							$objectsrc->fetch($object->$typeobject->id);
							$nblignes								= count($objectsrc->lines);
							for ($i = 0 ; $i < $nblignes ; $i++)	$nbrProdTot	+= $objectsrc->lines[$i]->product_type == 0 ? $objectsrc->lines[$i]->qty : 0;
							$txtref									= $outputlangs->transnoentities("RefOrder").' : '.$objectsrc->ref.' / '.$objectsrc->ref_client;
							$txtdt									= $outputlangs->transnoentities("PDFInfraSPlusOrderDate")." : ".dol_print_date($objectsrc->date_commande, "day", false, $outputlangs, true);
							$txtnbprod								= $outputlangs->transnoentities("PDFInfraSPlusEtiquetteNbrArt").' : '.$nbrProdTot;
						}
					}
					$carac_emetteur	= dol_string_nohtmltag(dol_format_address($this->emetteur, 0, ' ', $outputlangs));
					$carac_client	= '';
					if ($this->showadrlivr && $this->adrlivr) {
						if ($this->adrlivr == 'Default')	$carac_client		= pdf_InfraSPlus_build_address($outputlangs, $this->emetteur, $this->emetteur, $object->thirdparty, '', 0, 'targetwithnodetails', $object, 0);
						else								$carac_client		= pdf_InfraSPlus_build_address($outputlangs, $this->emetteur, $this->emetteur, $this->adrlivr, '', 0, 'targetwithnodetails', $object, 0);
						if ($carac_client)					$carac_client_name	= dol_htmlentitiesbr($this->adrlivr->name);
					}
					if (!$this->showadrlivr || !$this->adrlivr) {
						// Recipient properties
						$carac_client_name	= pdf_InfraSPlus_Build_Third_party_Name($object->thirdparty, $outputlangs, $this->includealias);
						$carac_client		= pdf_InfraSPlus_build_address($outputlangs, $this->emetteur, $this->emetteur, $object->thirdparty, '', false, 'targetwithnodetails', $object, 1, false);
					}
					$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $txtref, '', 'C', 0, 1, $this->marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
					$posy	= $pdf->getY() + 1;
					$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $txtdt, '', 'C', 0, 1, $this->marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
					$posy	= $pdf->getY() + 1;
					$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $txtnbprod, '', 'C', 0, 1, $this->marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);

					$posy		= $pdf->getY() + $this->tab_hl;

					$protocol	= ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
					$link		= $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
					$txtlink	= str_replace($_SERVER['QUERY_STRING'], 'id='.$object->origin_id, str_replace('expedition', 'commande', $link));
					$styleBC	= array('position'		=> $posy,
										'border'		=> false,
										'hpadding'		=> '0',
										'vpadding'		=> '0',
										'fgcolor'		=> array($bodytxtcolor[0], $bodytxtcolor[1], $bodytxtcolor[2]),
										'bgcolor'		=> false,
										'module_width'	=> 1,
										'module_height'	=> 1
										);
					$pdf->write2DBarcode($txtlink, 'QRCODE', $this->larg_util_cadre / 2, $posy, $this->hBC, $this->hBC, $styleBC, 'B');
					$pdf->SetFont('', 'B', $default_font_size + 2);
					$posy		= ($this->page_hauteur / 2) + $this->marge_haute + $this->tab_hl;
					$pdf->Line($this->marge_gauche, $posy, $this->page_largeur - $this->marge_droite, $posy, $this->stdLineStyle);
					$posy		+= 2;
					$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $outputlangs->transnoentities("PDFInfraSPlusEtiquetteDest").' : ', '', 'L', 0, 1, $this->marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
					$posy		= $pdf->getY() + 3;
					$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $carac_client_name, '', 'L', 0, 1, $this->marge_gauche, $posy + 1, true, 0, 0, false, 0, 'M', false);
					$posy		= $pdf->getY() + 1;
					$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $carac_client, '', 'L', 0, 1, $this->marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
					$posy		= $this->page_hauteur - $this->marge_basse - ($this->tab_hl * 2);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->MultiCell($this->larg_util_cadre, $this->tab_hl, $outputlangs->transnoentities("PDFInfraSPlusEtiquetteRet").' : '.$carac_emetteur, '', 'L', 0, 1, $this->marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters	= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook	= $hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
					if ($reshook < 0) {
						$this->error	= $hookmanager->error;
						$this->errors	= $hookmanager->errors;
					}
					if (! empty($this->main_umask))	@chmod($file, octdec($this->main_umask));
					$this->result					= array('fullpath' => $file);
					return 1;   // Pas d'erreur
				}
				else {
					$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}
			else {
				$this->error=$langs->trans("ErrorConstantNotDefined","EXP_OUTPUTDIR");
				return 0;
			}
		}

	}
?>