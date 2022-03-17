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
	* 	\file		../infraspackplus/core/modules/expedition/doc/pdf_InfraSPlus_BLX.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF expedition
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF proposal InfraS
	************************************************/
	class pdf_InfraSPlus_BLX extends ModelePdfExpedition
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

			$this->name									= $langs->trans('PDFInfraSPlusExpeditionXName');
			$this->description							= $langs->trans('PDFInfraSPlusExpeditionXDescription');
			$this->emetteur								= $mysoc;
			if (empty($this->emetteur->country_code))	$this->emetteur->country_code									= substr($langs->defaultlang, -2);
			$this->type									= 'pdf';
			$this->defaulttemplate						= isset($conf->global->EXPEDITION_ADDON_PDF)					? $conf->global->EXPEDITION_ADDON_PDF : '';
			$this->multilangs							= isset($conf->global->MAIN_MULTILANGS)							? $conf->global->MAIN_MULTILANGS : 0;
			$this->use_fpdf								= isset($conf->global->MAIN_USE_FPDF)							? $conf->global->MAIN_USE_FPDF : 0;
			$this->main_umask							= isset($conf->global->MAIN_UMASK)								? $conf->global->MAIN_UMASK : '0755';
			$formatarray								= pdf_InfraSPlus_getFormat();
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
			$this->type_foot							= '0000';
			$this->hidepagenum							= 0;
			$this->wpicturefoot							= isset($conf->global->INFRASPLUS_PDF_PICTURE_FOOT_WIDTH)		? $conf->global->INFRASPLUS_PDF_PICTURE_FOOT_WIDTH : 188;
			$this->hpicturefoot							= isset($conf->global->INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT)		? $conf->global->INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT : 12;
			$this->maxsizeimgfoot						= array('largeur'=>$this->wpicturefoot, 'hauteur'=>$this->hpicturefoot);
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
			global $user, $langs, $conf, $db, $hookmanager, $nblignes;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_BLX') ? '' : '_BLX';
			$baseDir						= !empty($conf->expedition->multidir_output[$conf->entity]) ? $conf->expedition->multidir_output[$conf->entity] : $conf->expedition->dir_output;

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
	//				$this->logo			= $hookmanager->resArray['logo'];
	//				$this->adrlivr		= $hookmanager->resArray['adrlivr'];
					$this->pied			= $hookmanager->resArray['pied'];
					$nblignes			= count($object->lines);	// Set nblignes with the new facture lines content after hook
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
					$tagvs				= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities("Shipment"));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Shipment"));
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
					// Default PDF parameters
					$this->stdLineW		= 0.2; // épaisseur par défaut dans TCPDF = 0.2
					$this->stdLineDash	= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->stdLineCap	= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->stdLineColor	= array(128, 128, 128);
					$this->stdLineStyle	= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					// Define width and position of main table columns
					$this->heightline	= 4;
					// test template
					$template			= $dir.'/pdf_InfraSPlus_BLX.pdf';
					$isTemplate			= false;
					if (file_exists($template) && is_readable($template)) {
						$finfo	= finfo_open(FILEINFO_MIME_TYPE);
						if (finfo_file($finfo, $template) == 'application/pdf') {
							try
							{
								$isTemplate		= true;
								$nbPtemplate	= $pdf->setSourceFile($template);
								for ($i = 1; $i <= $nbPtemplate; $i ++) {
									$tplIdx	= $pdf->importPage($i);
									if ($tplIdx === false) {
										$isTemplate	= false;
										setEventMessages(null, array($outputlangs->trans("PDFInfraSPlusPdfFileError1", $template)), 'warnings');
									}
								}
							}
							catch (exception $e) {
								setEventMessages(null, array($outputlangs->trans("PDFInfraSPlusPdfFileError1", $template).$outputlangs->trans("PDFInfraSPlusPdfFileError2", $e->getMessage())), 'warnings');
							}
						}
					}
					// Template Ok we use it
					if ($isTemplate) {
						$sql_adrlivr		= 'SELECT DISTINCT sa.name, sa.address, sa.zip, sa.town, sa.fk_soc';
						$sql_adrlivr		.= ' FROM '.MAIN_DB_PREFIX.'societe_address AS sa';
						$sql_adrlivr		.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s';
						$sql_adrlivr		.= ' ON sa.fk_soc = s.rowid';
						$sql_adrlivr		.= ' WHERE s.nom = "'.$object->thirdparty->name.'"';
						$resultat_adrlivr	= $db->query($sql_adrlivr);
						if ($resultat_adrlivr) {
							// shipping address
							$obj_adrlivr	= array();
							$nbAdrLivr		= $db->num_rows($resultat_adrlivr);
							// linked orders and invoices
							$object->fetchObjectLinked();
							foreach($object->linkedObjects as $objecttype => $objects) {
								if ($objecttype == 'commande') {
									foreach($objects as $elementobject) {
										$dateOrderLinked	= dol_print_date($elementobject->date, 'day', '', $outputlangs);
										$elementobject->fetchObjectLinked();
										foreach($elementobject->linkedObjects as $elementobjecttype => $elementobjects) {
											if ($elementobjecttype == 'propal')
												foreach($elementobjects as $elementelementobject)	$datePropalLinked	= dol_print_date($elementelementobject->date, 'day', '', $outputlangs);
											if ($elementobjecttype == 'facture') {
												foreach($elementobjects as $elementelementobject) {
													$dateFactureLinked	= dol_print_date($elementelementobject->date, 'day', '', $outputlangs);
													$refFactureLinked	= $outputlangs->transnoentities($elementelementobject->ref);
												}
											}
										}
									}
								}
							}
							$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
													'E' => $object->getIdContact('external', 'SHIPPING')
													);
							if (is_array($arrayidcontact['E']) && count($arrayidcontact['E']) > 0) {
								$result			= $object->fetch_contact($arrayidcontact['E'][0]);
								$eContactLN		= $outputlangs->convToOutputCharset($object->contact->getFullName($outputlangs, 0, 4));
								$eContactFN		= $outputlangs->convToOutputCharset($object->contact->getFullName($outputlangs, 0, 2));
								$eContactJob	= $outputlangs->convToOutputCharset($object->contact->poste);
							}
							$this->nameCli							= $outputlangs->convToOutputCharset($object->thirdparty->name);
							$this->profIDcli						= $outputlangs->convToOutputCharset($object->thirdparty->idprof1);
							if (dol_strlen($this->profIDcli) == 9)	$this->profIDcli	= substr($this->profIDcli, 0, 3).' '.substr($this->profIDcli, 3, 3).' '.substr($this->profIDcli, 6, 3);
							$this->adrCli1							= str_replace('...', '', dolGetFirstLineOfText($object->thirdparty->address));
							$this->adrCli2							= trim(str_replace($this->adrCli1, '', dol_string_nohtmltag($object->thirdparty->address)));
							$this->phoneCli							= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($object->thirdparty->phone)));
							$this->emailCli							= $outputlangs->convToOutputCharset($object->thirdparty->email);
							if (is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0) {
								$result			= $object->fetch_user($arrayidcontact['I'][0]);
								$iContactLN		= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs, 0, 4));
								$iContactFN		= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs, 0, 2));
								$iContactJob	= $outputlangs->convToOutputCharset($object->user->job);
							}
							$this->myName	= $outputlangs->convToOutputCharset($this->emetteur->name);
							$this->myProfID	= $outputlangs->convToOutputCharset($this->emetteur->idprof2);
							$this->myAddr	= dol_string_nohtmltag($this->emetteur->address);
							$this->myPhone	= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($this->emetteur->phone)));
							$this->myEmail	= $outputlangs->convToOutputCharset($this->emetteur->email);
							for ($i = 0; $i < $nbAdrLivr; $i++) {
								$obj_adrlivr	= $db->fetch_object($resultat_adrlivr);
								$adrlivr1		= str_replace('...', '', dolGetFirstLineOfText($obj_adrlivr->address));
								$adrlivr2		= trim(str_replace($adrlivr1, '', dol_string_nohtmltag($obj_adrlivr->address)));
								// loop on line to add the first page for each product
								for ($j = 0 ; $j < $nblignes ; $j++) {
									$ref			= pdf_getlineref($object, $j, $outputlangs, $hidedetails);
									$pageposbefore	= $pdf->getPage();
									$qtyByAdr		= $object->lines[$j]->qty_shipped / $nbAdrLivr;
									$tplIdx			= $j == 0 ? $pdf->importPage(1) : $pdf->importPage(2);	// We get the first page of the template for the first product (head page) and the second one for the others
									if ($tplIdx !== false) {
										$templateSize	= $pdf->getTemplatesize($tplIdx);
										$pdf->AddPage($templateSize['h'] > $templateSize['w'] ? 'P' : 'L', array($templateSize['w'], $templateSize['h']));
										$pdf->useTemplate($tplIdx);
										// Default PDF parameters
										$pdf->MultiCell(0, 3, '');		// Set interline to 3
										$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
										$pdf->SetFont('', '', $default_font_size - 3);
										$pdf->SetDrawColor(0, 0, 0);
										if ($pdf->getPage() == ($pageposbefore + 1))	// first page of the template
										{
											$pdf->MultiCell(0, $this->heightline, $j + 1,				0, 'L', 0, 1, 11, 57,		true, 0, 0, false, 0, 'M', false);	// Line number
											$pdf->SetFont('', '', $default_font_size);
											$pdf->MultiCell(0, $this->heightline, $datePropalLinked,	0, 'L', 0, 1, 88, 68.5,		true, 0, 0, false, 0, 'M', false);	// propal date
											$pdf->MultiCell(0, $this->heightline, $dateFactureLinked,	0, 'L', 0, 1, 87, 75,		true, 0, 0, false, 0, 'M', false);	// invoice date
											$pdf->MultiCell(0, $this->heightline, $refFactureLinked,	0, 'L', 0, 1, 37, 81.5,		true, 0, 0, false, 0, 'M', false);	// invoice ref
											$pdf->MultiCell(0, $this->heightline, $obj_adrlivr->name,	0, 'L', 0, 1, 69, 88,		true, 0, 0, false, 0, 'M', false);	// site name
											$pdf->MultiCell(0, $this->heightline, $adrlivr1,			0, 'L', 0, 1, 36, 94.7,		true, 0, 0, false, 0, 'M', false);	// first address line
											$pdf->MultiCell(0, $this->heightline, $adrlivr2,			0, 'L', 0, 1, 37, 100.8,	true, 0, 0, false, 0, 'M', false);	// second address line
											$pdf->MultiCell(0, $this->heightline, $obj_adrlivr->zip,	0, 'L', 0, 1, 25, 107.5,	true, 0, 0, false, 0, 'M', false);	// zip
											$pdf->MultiCell(0, $this->heightline, $obj_adrlivr->town,	0, 'L', 0, 1, 19, 114,		true, 0, 0, false, 0, 'M', false);	// town
											$pdf->MultiCell(0, $this->heightline, $qtyByAdr,			0, 'L', 0, 1, 53, 140,		true, 0, 0, false, 0, 'M', false);	// product Qty
											pdf_InfraSPlus_writelinedesc($pdf, $object, $j, $outputlangs, $this->formatpage, '', 0, $this->heightline, 29, 159.3, 1, 1, 0, '');	// Product label
											$pdf->MultiCell(0, $this->heightline, $ref,					0, 'L', 0, 1, 24, 197.7,	true, 0, 0, false, 0, 'M', false);	// product Ref
										}
										$pdf->MultiCell(0, $this->heightline, $this->nameCli, 0, 'L', 0, 1, 8, 286.5, true, 0, 0, false, 0, 'M', false);	// Custommer Social name
										$this->_pagefoot($pdf, $object, $outputlangs, 0);
									}
									else	setEventMessages(null, array($outputlangs->trans("PDFInfraSPlusPdfFileError1", $template)), 'warnings');
								}
								// Now we add the other pages just once
								$ref			= pdf_getlineref($object, $j, $outputlangs, $hidedetails);
								$pageposbefore	= $pdf->getPage();
								$qtyByAdr		= $object->lines[$j]->qty_shipped / $nbAdrLivr;
								for ($k = 3; $k <= $nbPtemplate; $k ++)	// we start at the third page
								{
									$tplIdx	= $pdf->importPage($k);
									if ($tplIdx !== false) {
										$templateSize	= $pdf->getTemplatesize($tplIdx);
										$pdf->AddPage($templateSize['h'] > $templateSize['w'] ? 'P' : 'L', array($templateSize['w'], $templateSize['h']));
										$pdf->useTemplate($tplIdx);
										// Default PDF parameters
										$pdf->MultiCell(0, 3, '');		// Set interline to 3
										$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
										$pdf->SetFont('', '', $default_font_size);
										$pdf->SetDrawColor(0, 0, 0);
										if ($pdf->getPage() == ($pageposbefore + 1))	// third page of the template (just +1 because we start at the third page) {
											$pdf->MultiCell(0, $this->heightline, $eContactLN,					0, 'L', 0, 1, 34, 20,		true, 0, 0, false, 0, 'M', false);	// Custommer contact last name
											$pdf->MultiCell(0, $this->heightline, $eContactFN,					0, 'L', 0, 1, 119, 20,		true, 0, 0, false, 0, 'M', false);	// Custommer contact first name
											$pdf->MultiCell(0, $this->heightline, $this->nameCli,				0, 'L', 0, 1, 48, 34,		true, 0, 0, false, 0, 'M', false);	// Custommer Social name
											$pdf->MultiCell(0, $this->heightline, $this->profIDcli,				0, 'L', 0, 1, 49.5, 42,		true, 0, 0, false, 0, 'M', false);	// Custommer ID1
											$pdf->MultiCell(0, $this->heightline, $eContactJob,					0, 'L', 0, 1, 38.5, 60.9,	true, 0, 0, false, 0, 'M', false);	// Custommer contact job
											$pdf->MultiCell(0, $this->heightline, $this->adrCli1,				0, 'L', 0, 1, 23, 68.7,		true, 0, 0, false, 0, 'M', false);	// Custommer first address line
											$pdf->MultiCell(0, $this->heightline, $this->adrCli2,				0, 'L', 0, 1, 39, 76.4,		true, 0, 0, false, 0, 'M', false);	// Custommer second address line
											$pdf->MultiCell(0, $this->heightline, $object->thirdparty->zip,		0, 'L', 0, 1, 28, 84.2,		true, 0, 0, false, 0, 'M', false);	// Custommer zip
											$pdf->MultiCell(0, $this->heightline, $object->thirdparty->town,	0, 'L', 0, 1, 20, 91.9,		true, 0, 0, false, 0, 'M', false);	// Custommer town
											$pdf->MultiCell(0, $this->heightline, $this->phoneCli,				0, 'L', 0, 1, 25, 107.2,	true, 0, 0, false, 0, 'M', false);	// Custommer phone
											$pdf->MultiCell(0, $this->heightline, $this->emailCli,				0, 'L', 0, 1, 22, 122.7,	true, 0, 0, false, 0, 'M', false);	// Custommer email
											$pdf->MultiCell(0, $this->heightline, 'X',							0, 'L', 0, 1, 10, 133.2,	true, 0, 0, false, 0, 'M', false);	// first check box
											$pdf->MultiCell(0, $this->heightline, $object->thirdparty->town,	0, 'L', 0, 1, 18, 209,		true, 0, 0, false, 0, 'M', false);	// Custommer town on "fait à"
										}
										elseif ($pdf->getPage() == ($pageposbefore + 2))	// fourth page of the template (just +2 because we start at the third page) {
											$pdf->MultiCell(0, $this->heightline, $iContactLN,					0, 'L', 0, 1, 33, 21.2,		true, 0, 0, false, 0, 'M', false);	// My contact last name
											$pdf->MultiCell(0, $this->heightline, $iContactFN,					0, 'L', 0, 1, 110, 21.2,	true, 0, 0, false, 0, 'M', false);	// My contact first name
											$pdf->MultiCell(0, $this->heightline, $iContactJob,					0, 'L', 0, 1, 37, 29.2,		true, 0, 0, false, 0, 'M', false);	// My contact job
											$pdf->MultiCell(0, $this->heightline, $this->myName,				0, 'L', 0, 1, 29, 37.2,		true, 0, 0, false, 0, 'M', false);	// My Social name
											$pdf->MultiCell(0, $this->heightline, $this->myProfID,				0, 'L', 0, 1, 29, 44.8,		true, 0, 0, false, 0, 'M', false);	// My ID2
											$pdf->MultiCell(0, $this->heightline, $this->myAddr,				0, 'L', 0, 1, 21, 52.8,		true, 0, 0, false, 0, 'M', false);	// My address
											$pdf->MultiCell(0, $this->heightline, $this->emetteur->zip,			0, 'L', 0, 1, 24, 60.7,		true, 0, 0, false, 0, 'M', false);	// My zip
											$pdf->MultiCell(0, $this->heightline, $this->emetteur->town,		0, 'L', 0, 1, 17, 68.4,		true, 0, 0, false, 0, 'M', false);	// My town
											$pdf->MultiCell(0, $this->heightline, $this->myPhone,				0, 'L', 0, 1, 23, 76.6,		true, 0, 0, false, 0, 'M', false);	// My phone
											$pdf->MultiCell(0, $this->heightline, $this->myEmail,				0, 'L', 0, 1, 20, 91.9,		true, 0, 0, false, 0, 'M', false);	// My email
											$pdf->MultiCell(0, $this->heightline, 'X',							0, 'L', 0, 1, 8.4, 107.8,	true, 0, 0, false, 0, 'M', false);	// second check box
											$pdf->MultiCell(0, $this->heightline, $object->thirdparty->town,	0, 'L', 0, 1, 18, 148.7,	true, 0, 0, false, 0, 'M', false);	// Custommer town on "fait à"
										}
										$pdf->MultiCell(0, $this->heightline, $this->nameCli, 0, 'L', 0, 1, 8, 286.5, true, 0, 0, false, 0, 'M', false);	// Custommer Social name
										$this->_pagefoot($pdf, $object, $outputlangs, 0);
									}
									else	setEventMessages(null, array($outputlangs->trans("PDFInfraSPlusPdfFileError1", $template)), 'warnings');
								}
							}
						}
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters						= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
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
			if (! empty($this->pied))	$showdetails	.= 1;
			else						$showdetails	.= 0;
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}

	}
?>