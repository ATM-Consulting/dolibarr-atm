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
	* 	\file		../infraspackplus/core/modules/expensereport/doc/pdf_InfraSPlus_NDF.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF expense report
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/userbankaccount.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_NDF extends ModeleExpenseReport
	{
		public $db;
		public $name;
		public $description;
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

			$langs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'trips', 'banks', 'projects', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->name							= $langs->trans('PDFInfraSPlusExpenseName');
			$this->description					= $langs->trans('PDFInfraSPlusExpenseDescription');
			$this->update_main_doc_field		= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->franchise					= !$this->emetteur->tva_assuj;
			$this->defaulttemplate				= isset($conf->global->EXPENSEREPORT_ADDON_PDF)					? $conf->global->EXPENSEREPORT_ADDON_PDF				: '';
			$this->no_payment_details			= isset($conf->global->PDF_EXPENSEREPORT_NO_PAYMENT_DETAILS)	? $conf->global->PDF_EXPENSEREPORT_NO_PAYMENT_DETAILS	: 0;
			$this->draft_watermark				= isset($conf->global->EXPENSEREPORT_DRAFT_WATERMARK)			? $conf->global->EXPENSEREPORT_DRAFT_WATERMARK			: '';
			$this->InfraSExpense				= isset($conf->global->MAIN_MODULE_INFRASEXPENSE)				? 1														: 0;
			$this->extraDetPos2					= isset($conf->global->INFRASPLUS_PDF_EXTRADET_SECOND)			? $conf->global->INFRASPLUS_PDF_EXTRADET_SECOND			: 0;
			$this->show_num_col					= 1;//isset($conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN)		? $conf->global->INFRASPLUS_PDF_WITH_NUM_COLUMN			: 0;
			$this->show_ttc_col					= 1;
			$this->larg_ref						= 10;
			$this->larg_pro						= 10;
			$this->larg_qty						= 10;
			$this->larg_up						= 22;
			$this->larg_tva						= 16;
			$this->larg_totalttc				= 24;
			$this->num_ref						= 1;
			$this->num_desc						= 2;
			$this->num_pro						= 3;
			$this->num_qty						= 4;
			$this->num_up						= 5;
			$this->num_tva						= 6;
			$this->num_totalttc					= 7;
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
			$filesufixe						= ! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_NDF') ? '' : '_NDF';
			$baseDir						= !empty($conf->expensereport->multidir_output[$conf->entity]) ? $conf->expensereport->multidir_output[$conf->entity] : $conf->expensereport->dir_output;

			if ($baseDir) {
				$this->paid	= $object->getSumPayments();
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
						$this->error=$outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
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
					$this->listfreet					= $hookmanager->resArray['listfreet'];
					$this->listnotep					= $hookmanager->resArray['listnotep'];
					$this->pied							= $hookmanager->resArray['pied'];
					$this->files						= $hookmanager->resArray['filesArray'];
					$nblignes							= count($object->lines);	// Set nblignes with the new facture lines content after hook
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
					$tagvs						= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('Trips'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Trips'));
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
					for ($i = 0 ; $i < $nblignes ; $i++) {
						// Collecte des totaux par valeur de tva dans $this->tva['taux']=total_tva
						$tvaligne						= doubleval($object->lines[$i]->total_tva);
						$localtax1ligne					= $object->lines[$i]->total_localtax1;
						$localtax2ligne					= $object->lines[$i]->total_localtax2;
						$localtax1_rate					= $object->lines[$i]->localtax1_tx;
						$localtax2_rate					= $object->lines[$i]->localtax2_tx;
						$localtax1_type					= $object->lines[$i]->localtax1_type;
						$localtax2_type					= $object->lines[$i]->localtax2_type;
						$vatrate						= (string) $object->lines[$i]->tva_tx;
						// Retrieve type from database for backward compatibility with old records
						if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
							&& (!empty($localtax1_rate) || !empty($localtax2_rate))) // and there is local tax
						{
							$localtaxtmp_array	= getLocalTaxesFromRate($vatrate, 0, $this->emetteur, $this->emetteur);
							$localtax1_type		= isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
							$localtax2_type		= isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
						}
						// retrieve global local tax
						if ($localtax1_type && $localtax1ligne != 0)		$this->localtax1[$localtax1_type][$localtax1_rate]					+= $localtax1ligne;
						if ($localtax2_type && $localtax2ligne != 0)		$this->localtax2[$localtax2_type][$localtax2_rate]					+= $localtax2ligne;
						if (($object->lines[$i]->info_bits & 0x01) == 0x01)	$vatrate															.= '*';
						if (! isset($this->tva[$vatrate])) 					$this->tva[$vatrate]												= 0;
						if (!empty($object->lines[$i]->TTotal_tva))			foreach ($object->lines[$i]->TTotal_tva as $vatrate => $tvaligne)	$this->tva[$vatrate]	+= $tvaligne;
						else
							if(!empty($tvaligne))	$this->tva[$vatrate]	+= $tvaligne;
					}
					// Define width and position of notes frames
					$this->larg_util_txt				= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre				= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt					= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->show_num_col))		$this->larg_ref			= 0;
					if (!empty($this->hide_vat_col))	$this->larg_tva			= 0;
					if (empty($this->InfraSExpense))	$this->larg_pro			= 0;
					$this->larg_desc					= $this->larg_util_cadre - ($this->larg_ref + $this->larg_pro + $this->larg_qty + $this->larg_up + $this->larg_tva + $this->larg_totalttc); // Largeur variable suivant la place restante
					$this->tableau	= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
											'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
											'pro'		=> array('col' => $this->num_pro,		'larg' => $this->larg_pro,		'posx' => 0),
											'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
											'up'		=> array('col' => $this->num_up,		'larg' => $this->larg_up,		'posx' => 0),
											'tva'		=> array('col' => $this->num_tva,		'larg' => $this->larg_tva,		'posx' => 0),
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
					$ht_coltotal			= $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1);
					if ($this->paid && empty($this->no_payment_details)) {
						$ht_coltotal	+= $this->_tableau_versements($pdf, $object, $this->marge_haute, $outputlangs, 1);
						$ht2_coltotal	= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					}
					else					$ht2_coltotal	= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$heightforinfotot		= $ht_coltotal + $ht2_coltotal;
					$heightforinfotot		+= $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$heightforfooter		= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$tab_top	+= 	pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, $this->first_page_empty);
					$nexY		= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
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
						// Hauteur de la référence
						$this->heightline	= $this->tab_hl;
						// Description of product line
						$pageposdesc	= $pdf->getPage();
						$this->_writelinedesc($pdf, $object, $i, $outputlangs, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hidedesc, '');
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
						$nexY			= $pdf->GetY();
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
						// Reference
						if (!empty($this->show_num_col)) {
							$ref	= $i + 1;
							$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $ref, '', 'R', 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						}
						// Pro card with InfraSExpense module
						if (!empty($this->InfraSExpense))	$pdf->MultiCell($this->tableau['pro']['larg'], $this->heightline, ($object->lines[$i]->special_code ? 'Pro' : ''), '', 'R', 0, 1, $this->tableau['pro']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Quantity
						$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $object->lines[$i]->qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Unit price
						$vat		= pdf_InfraSPlus_getlinevatrate($object, $i, $outputlangs, 0, null, 1);
						$value_ht	= $this->InfraSExpense ? $object->lines[$i]->value_unit - ($object->lines[$i]->total_tva / $object->lines[$i]->qty) : $object->lines[$i]->value_unit / (1 + ($vat / 100));
						$pdf->MultiCell($this->tableau['up']['larg'], $this->heightline, pdf_InfraSPlus_price($object, $value_ht, $outputlangs), '', 'R', 0, 1, $this->tableau['up']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// VAT Rate
						if (empty($this->hide_vat_col)) {
							$vat_rate	= $this->InfraSExpense ? pdf_InfraSPlus_price($object, ($object->lines[$i]->total_tva / $object->lines[$i]->qty), $outputlangs) : pdf_InfraSPlus_getlinevatrate($object, $i, $outputlangs, $hidedetails);
							$pdf->MultiCell($this->tableau['tva']['larg'], $this->heightline, $vat_rate, '', 'R', 0, 1, $this->tableau['tva']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						}
						// Total line
						$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, price($object->lines[$i]->total_ttc), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
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
					}
					$bottomlasttab		= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1)	$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					else				$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $pagenb);
					$posytot			= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					if ($this->paid && empty($this->no_payment_details)) {
						$posytot		= $this->_tableau_versements($pdf, $object, $posytot, $outputlangs, 0);
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						$posyfreetext	= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posytot;
					}
					else {
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						$posyfreetext	= ! $this->free_text_end ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posytot;
					}
					$posy										= $posyfreetext;
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
				$this->error=$outputlangs->transnoentities('ErrorConstantNotDefined', 'EXPENSEREPORT_OUTPUTDIR');
				return 0;
			}
		}

		/********************************************
		*	Output line description into PDF
		*
		*	@param	TCPDF		$pdf            The PDF factory
		*	@param	Object		$object			Object shown in PDF
		*	@param	int			$i				Current line number
		*	@param  Translate	$outputlangs	Object lang for output
		*	@param  int			$w				Width
		*	@param  int			$h				Height
		*	@param  int			$posx			Pos x
		*	@param  int			$posy			Pos y
		*	@param  int			$hideref       	Hide reference
		*	@param  int			$hidedesc		Hide description
		*	@param	int			$extraDet		Module equipement compatibility
		*	@return	string
		 ********************************************/
		protected function _writelinedesc(&$pdf, $object, $i, $outputlangs, $w, $h, $posx, $posy, $hideref = 0, $hidedesc = 0, $extraDet = '') {
			global $db, $conf, $hookmanager;

			$reshook	= 0;
			$result		= '';
			if (is_object($hookmanager)) {
				$special_code										= $object->lines[$i]->special_code;
				if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
				$parameters											= array('pdf'=>$pdf, 'i'=>$i, 'outputlangs'=>$outputlangs, 'w'=>$w, 'h'=>$h, 'posx'=>$posx, 'posy'=>$posy, 'hideref'=>$hideref, 'hidedesc'=>$hidedesc, 'issupplierline'=>$issupplierline, 'special_code'=>$special_code);
				$action												= '';
				$reshook											= $hookmanager->executeHooks('pdf_writelinedesc', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
				if (!empty($hookmanager->resPrint))					$result	.= $hookmanager->resPrint;
			}
			if (empty($reshook)) {
				$expensereporttypecode										= $object->lines[$i]->type_fees_code;
				$expensereporttypecodetoshow								= ($outputlangs->trans(($expensereporttypecode)) == $expensereporttypecode ? $object->lines[$i]->type_fees_libelle : $outputlangs->trans($expensereporttypecode));
				if ($expensereporttypecodetoshow == $expensereporttypecode)	$expensereporttypecodetoshow	= preg_replace('/^(EX_|TF_)/', '', $expensereporttypecodetoshow);
				$comments													= $outputlangs->trans('Date').' : '. dol_print_date($object->lines[$i]->date, 'day', false, $outputlangs).' ';
				$comments													.= $outputlangs->trans('Type').' : '. $expensereporttypecodetoshow.'<br>';
				if (!empty($object->lines[$i]->projet_ref))				$comments						.= $outputlangs->trans('Project').' : '. $object->lines[$i]->projet_ref.'<br>';
				$comments													.= $object->lines[$i]->comments;
				if ($extraDet)												$comments						= empty($this->extraDetPos2) ? $extraDet.$comments : $comments.$extraDet;
				$comments													= preg_replace('/(<img[^>]*src=")([^"]*)(&amp;)([^"]*")/', '\1\2&\4', $comments, -1, $nbrep);
				// Description
				$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($comments), 0, 1, false, true, 'J', true);
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
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = 'ExpenseReport') {
			global $db, $conf, $hookmanager;

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
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$title				= $outputlangs->transnoentities($titlekey);
			$pdf->MultiCell($w, $this->tab_hl * 2, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$posy				+= $this->tab_hl * 2;
			$txtref				= $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
			$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
			$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
			$posy	+= $this->tab_hl;
			$txtdtS	= $outputlangs->transnoentities('DateStart').' : '.($object->date_debut > 0 ? dol_print_date($object->date_debut, 'day', false, $outputlangs, true) : '');
			$pdf->MultiCell($w, $this->tab_hl, $txtdtS, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl;
			$txtdtE	= $outputlangs->transnoentities('DateEnd').' : '.($object->date_fin > 0 ? dol_print_date($object->date_fin, 'day', false, $outputlangs, true) : '');
			$pdf->MultiCell($w, $this->tab_hl, $txtdtE, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 2);
			$posy	+= $this->tab_hl;
			$pdf->MultiCell($w, $this->tab_hl, $object->getLibStatut(0), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy			+= 0.5;
			$dimCadres['Y']	= ($this->use_iso_location && $posy <= 40 ? 40 : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			if ($showaddress) {
				$receiver			= new User($db);
				$receiver->fetch($object->fk_user_author);
				$receiver_account	= new UserBankAccount($db);
				$receiver_account->fetch(0, '', $object->fk_user_author);
				$arrayidcontact		= array('U' => $receiver,
											'B'	=> $receiver_account
											);
				$addresses			= array();
				$addresses			= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, NULL, $this->emetteur);
				$hauteurcadre		= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect, true);
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
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = 'ExpenseReport') {
			global $conf, $hookmanager;

			$fromcompany	= $this->emetteur;
			$title			= $outputlangs->transnoentities($titlekey);
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
			if ($this->showverline) {
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
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->show_num_col))	$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusNum'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->InfraSExpense))	$pdf->MultiCell($this->tableau['pro']['larg'], $this->ht_top_table, '', '', 'C', 0, 1, $this->tableau['pro']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Qty'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PriceUHT'), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (empty($this->hide_vat_col))		$pdf->MultiCell($this->tableau['tva']['larg'], $this->ht_top_table, $outputlangs->transnoentities('VAT'), '', 'C', 0, 1, $this->tableau['tva']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalTTC'), '', 'C', 0, 1, $this->tableau['totalttc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
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
			$larg_col2total					= $this->larg_totalttc;
			$larg_col1total					= $larg_tabtotal - $larg_col2total;
			$posxtabtotal					= $this->posxtabtotal;
			$posxcol2total					= $this->posxtabtotal + $larg_col1total;
			$index							= 0;
			// Total HT
			$this->atleastoneratenotnull	= 0;
			$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalHT'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $object->total_ht, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			// Totaux taxes
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
									$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT1', $this->emetteur->country_code).' ';
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
									$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT2', $this->emetteur->country_code).' ';
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
								$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
							}
							$totalvat	= $outputlangs->transcountrynoentities('TotalVAT', $this->emetteur->country_code).' ';
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
									$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT1', $this->emetteur->country_code).' ';
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
									$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT2', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
						}
					}
				}
				$index++;
			// Total TTC
			$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalTTC'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $object->total_ttc, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			if (!empty($this->InfraSExpense)) {
				$totalpro			= $object->getSumProCard();
				$amountexpected 	= price2num($object->total_ttc - $totalpro);
				if ($amountexpected != 0) {
					$index++;
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('AmountExpected'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $amountexpected, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				}
				$this->resteapayer	= price2num($amountexpected - $this->totalpaid);
				if ($this->resteapayer != 0) {
					$index++;
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('RemainderToPay'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->resteapayer, $outputlangs), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
				}
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
			$pdf->SetFont('', '', $default_font_size - 3);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			// Tableau total
			$larg_tabver											= $this->larg_tabtotal;
			$larg_col1ver											= ($larg_tabver / 4) - 5;
			$larg_col2ver											= $larg_tabver / 4;
			$larg_col3ver											= ($larg_tabver / 4) + 5;
			$larg_col4ver											= $larg_tabver / 4;
			$posxtabver												= $this->posxtabtotal;
			$posxcol2ver											= $posxtabver + $larg_col1ver;
			$posxcol3ver											= $posxcol2ver + $larg_col2ver;
			$posxcol4ver											= $posxcol3ver + $larg_col3ver;
			$index													= 0;
			$title													= $outputlangs->transnoentities('PaymentsAlreadyDone');
			$pdf->MultiCell($larg_tabver, $tabver_hl, $title, '', 'L', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$index++;
			$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
			$pdf->SetFont('', '', $default_font_size - 4);
			$pdf->MultiCell($larg_col1ver, $tabver_hl, $outputlangs->transnoentities('Date'), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col2ver, $tabver_hl, $outputlangs->transnoentities('Amount'), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col3ver, $tabver_hl, $outputlangs->transnoentities('Type'), '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col4ver, $tabver_hl, $outputlangs->transnoentities('Num'), '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$index++;
			$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
			$pdf->SetFont('', '', $default_font_size - 4);
			// Loop on each payment already done (from payment on this expensereport)
			$sql	= 'SELECT p.datep as date, p.num_payment as num, p.amount as amount, cp.code';
			$sql	.= ' FROM '.MAIN_DB_PREFIX.'expensereport as e, '.MAIN_DB_PREFIX.'payment_expensereport as p';
			$sql	.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as cp ON p.fk_typepayment = cp.id';
			$sql	.= ' WHERE e.rowid = "'.$object->id.'" AND p.fk_expensereport = e.rowid AND e.entity IN ('.getEntity('expensereport').')';
			$sql	.= ' ORDER BY p.datep';
			$resql	= $db->query($sql);
			if ($resql) {
				$num				= $db->num_rows($resql);
				$i					= 0;
				$this->totalpaid	= 0;
				while ($i < $num) {
					$row				= $db->fetch_object($resql);
					$MntLine			= $row->amount;
					$this->totalpaid	+= $MntLine;
					$oper				= $outputlangs->transnoentitiesnoconv('PaymentTypeShort'.$row->code);
					$pdf->MultiCell($larg_col1ver, $tabver_hl, dol_print_date($db->jdate($row->date),'day', false, $outputlangs, true), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2ver, $tabver_hl, pdf_InfraSPlus_price($object, $MntLine, $outputlangs), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
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