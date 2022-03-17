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
	* 	\file		../infraspackplus/core/modules/commande/doc/pdf_InfraSPlus_PJ.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF project
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	if (!empty($conf->propal->enabled))        require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	if (!empty($conf->facture->enabled))       require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	if (!empty($conf->facture->enabled))       require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
	if (!empty($conf->commande->enabled))      require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	if (!empty($conf->fournisseur->enabled))   require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	if (!empty($conf->fournisseur->enabled))   require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	if (!empty($conf->contrat->enabled))       require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	if (!empty($conf->ficheinter->enabled))    require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	if (!empty($conf->deplacement->enabled))   require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
	if (!empty($conf->expensereport->enabled)) require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
	if (!empty($conf->agenda->enabled))        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	if (!empty($conf->ndfp->enabled)) 			dol_include_once('/ndfp/class/ndfp.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_PJ extends ModelePDFProjects
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

			$langs->loadLangs(array('main', 'dict', 'bills', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'projects', 'trips', 'agenda', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->name							= $langs->trans('PDFInfraSPlusProjectName');
			$this->description					= $langs->trans('PDFInfraSPlusProjectDescription');
			$this->update_main_doc_field		= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->defaulttemplate				= isset($conf->global->PROJECT_ADDON_PDF)					? $conf->global->PROJECT_ADDON_PDF					: '';
			$this->deposit_are_payment			= isset($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)	? $conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS	: 0;
			$this->Prj_TimeStamp				= isset($conf->global->INFRASPLUS_PDF_PROJECT_TIMESTAMP)	? $conf->global->INFRASPLUS_PDF_PROJECT_TIMESTAMP	: 0;
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
		}

		/********************************************
		*	Function to build pdf onto disk
		*
		*	@param		Object		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@return     int             				1 = OK, <= 0 KO
		********************************************/
		public function write_file($object, $outputlangs) {
			global $user, $langs, $conf, $db, $hookmanager, $nblignes;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$timeStamp						= $this->Prj_TimeStamp ? '_'.dol_print_date(dol_now(), '%Y%m%d', false, $outputlangs, true) : '';
			$filesufixe						= $timeStamp.(! $this->multi_files || ($this->defaulttemplate && $this->defaulttemplate == 'InfraSPlus_PJ') ? '' : '_PJ');
			$baseDir						= !empty($conf->projet->multidir_output[$conf->entity]) ? $conf->projet->multidir_output[$conf->entity] : $conf->projet->dir_output;

			if ($baseDir) {
				$objectref	= dol_sanitizeFileName($object->ref);
				// Definition of $dir and $file
				if (preg_match('/specimen/i', $objectref)) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				}
				else {
					$dir	= $baseDir.'/'.$objectref;
					$file	= $dir.'/'.$objectref.$filesufixe.'.pdf';
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
					$parameters				= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook				= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
					$this->logo				= $hookmanager->resArray['logo'];
					$this->listnotep		= $hookmanager->resArray['listnotep'];
					$this->pied				= $hookmanager->resArray['pied'];
					$this->files			= $hookmanager->resArray['filesArray'];
					$task					= new Task($db);
					$tasksarray				= array();
					$tasksarray				= $task->getTasksArray(0, 0, $object->id);
					if (! $object->id > 0)	$tasksarray	= array_slice($tasksarray, 0, min(5, count($tasksarray)));	// Special case when used with object = specimen, we may return all lines
					$object->lines			= $tasksarray;
					$nblignes				= count($object->lines);
					// Create pdf instance
					$pdf					= pdf_getInstance($this->format);
					$default_font_size		= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
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
					$pdf->SetSubject($outputlangs->transnoentities("Project"));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Project"));
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
					$this->horLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Define width and position of notes frames
					$this->larg_util_txt	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt		= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					$this->num_ref			= 1;
					$this->num_date			= 2;
					$this->num_tiers		= 3;
					$this->num_totalht		= 4;
					$this->num_totalttc		= 5;
					$this->num_status		= 6;
					$this->larg_ref			= 30;
					$this->larg_date		= 18;
					$this->larg_totalht		= 24;
					$this->larg_totalttc	= 24;
					$this->larg_status		= 40;
					$this->larg_tiers		= $this->larg_util_cadre - ($this->larg_ref + $this->larg_date + $this->larg_totalht + $this->larg_totalttc + $this->larg_status); // Largeur variable suivant la place restante
					$this->tableau			= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
													'date'		=> array('col' => $this->num_date,		'larg' => $this->larg_date,		'posx' => 0),
													'tiers'		=> array('col' => $this->num_tiers,		'larg' => $this->larg_tiers,	'posx' => 0),
													'totalht'	=> array('col' => $this->num_totalht,	'larg' => $this->larg_totalht,	'posx' => 0),
													'totalttc'	=> array('col' => $this->num_totalttc,	'larg' => $this->larg_totalttc,	'posx' => 0),
													'status'	=> array('col' => $this->num_status,	'larg' => $this->larg_status,	'posx' => 0)
													);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->largcol1	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 2)	$this->largcol2	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 3)	$this->largcol3	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 4)	$this->largcol4	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 5)	$this->largcol5	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 6)	$this->largcol6	= $ncol_array['larg'];
					}
					$this->posxcol1	= $this->marge_gauche;
					$this->posxcol2	= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3	= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4	= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5	= $this->posxcol4	+ $this->largcol4;
					$this->posxcol6	= $this->posxcol5	+ $this->largcol5;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->tableau[$ncol]['posx']	= $this->posxcol1;
						elseif ($ncol_array['col'] == 2)	$this->tableau[$ncol]['posx']	= $this->posxcol2;
						elseif ($ncol_array['col'] == 3)	$this->tableau[$ncol]['posx']	= $this->posxcol3;
						elseif ($ncol_array['col'] == 4)	$this->tableau[$ncol]['posx']	= $this->posxcol4;
						elseif ($ncol_array['col'] == 5)	$this->tableau[$ncol]['posx']	= $this->posxcol5;
						elseif ($ncol_array['col'] == 6)	$this->tableau[$ncol]['posx']	= $this->posxcol6;
					}
					// Calculs de positions
					$this->tab_hl		= 4;
					$this->heightline	= $this->tab_hl;
					$this->decal_round	= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$entreTxtTable		= $this->tab_hl * 1.5 + $this->decal_round;
					$head				= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead		= $head["totalhead"];
					$tab_top_first		= $hauteurhead + 5;
					$tab_top_newpage	= (empty($this->small_head2) ? $hauteurhead : 17 + $this->tab_hl);
					$this->ht_top_table	= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1) + $this->heightline;
					$tab_top_first		+= 	pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top_first, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, $this->first_page_empty);
					$nexY				= $tab_top_first + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					$largCol			= ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 3;
					$oppAmount			= $object->opp_amount;
					$budgetAmount		= $object->budget_amount;
					$marge				= $oppAmount - $budgetAmount;
					// Rappel Opportunité / Budget / marge théorique
					$nexY							= $pdf->GetY() + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					$tab_height						= $this->ht_top_table + ($this->tab_hl * 2);
					$curY							= $nexY;
					if (!empty($this->title_bg))	$pdf->RoundedRect($this->marge_gauche, $curY, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
					else if ($this->showtblline)	$pdf->RoundedRect($this->marge_gauche, $curY, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
					if ($this->showtblline)			$pdf->RoundedRect($this->marge_gauche, $curY + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
					else							$pdf->line($this->marge_gauche, $curY + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $curY + $tab_height, $this->horLineStyle);
					if ($this->showtblline) {
						// Colonnes
						$pdf->line($this->marge_gauche + $largCol,			$curY, $this->marge_gauche + $largCol,			$curY + $tab_height, $this->tblLineStyle);
						$pdf->line($this->marge_gauche + ($largCol * 2),	$curY, $this->marge_gauche + ($largCol * 2),	$curY + $tab_height, $this->tblLineStyle);
					}
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->MultiCell($largCol, $this->ht_top_table, 'Montant opportunité', '', 'C', 0, 1, $this->marge_gauche, $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($largCol, $this->ht_top_table, 'Budget', '', 'C', 0, 1, $this->marge_gauche + $largCol, $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($largCol, $this->ht_top_table, 'Marge', '', 'C', 0, 1, $this->marge_gauche + ($largCol * 2), $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$curY	+= $this->ht_top_table;
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->MultiCell($largCol, $this->tab_hl * 2, pdf_InfraSPlus_price($object, $oppAmount, $outputlangs), '', 'C', 0, 1, $this->marge_gauche, $curY, true, 0, 0, true, $this->tab_hl * 2, 'M', false);
					$pdf->MultiCell($largCol, $this->tab_hl * 2, pdf_InfraSPlus_price($object, $budgetAmount, $outputlangs), '', 'C', 0, 1, $this->marge_gauche + $largCol, $curY, true, 0, 0, true, $this->tab_hl * 2, 'M', false);
					$pdf->MultiCell($largCol, $this->tab_hl * 2, pdf_InfraSPlus_price($object, $marge, $outputlangs), '', 'C', 0, 1, $this->marge_gauche + ($largCol * 2), $curY, true, 0, 0, true, $this->tab_hl * 2, 'M', false);
					$nexY	+= $tab_height + $this->tab_hl;
					// Loop on each tables
					$listofreferent		= array('propal'				=> array('name'				=> 'Proposals',
																				 'title'			=> 'ListProposalsAssociatedProject',
																				 'class'			=> 'Propal',
																				 'table'			=> 'propal',
																				 'datefieldname'	=> 'datep',
																				 'test'				=> $conf->propal->enabled && $user->rights->propale->lire,
																				 'list2'			=> 1,
																				 ),
												'order'					=> array('name'				=> 'CustomersOrders',
																				 'title'			=> 'ListOrdersAssociatedProject',
																				 'class'			=> 'Commande',
																				 'table'			=> 'commande',
																				 'datefieldname'	=> 'date_commande',
																				 'test'				=> $conf->commande->enabled && $user->rights->commande->lire,
																				 'list2'			=> 1,
																				 ),
												'invoice'				=> array('name'				=> 'CustomersInvoices',
																				 'title'			=> 'ListInvoicesAssociatedProject',
																				 'class'			=> 'Facture',
																				 'margin'			=> 'add',
																				 'table'			=> 'facture',
																				 'datefieldname'	=> 'datef',
																				 'test'				=> $conf->facture->enabled && $user->rights->facture->lire,
																				 'list1'			=> 1,
																				 'list2'			=> 1,
																				 ),
												'invoice_predefined'	=> array('name'				=> 'PredefinedInvoices',
																				 'title'			=> 'ListPredefinedInvoicesAssociatedProject',
																				 'class'			=> 'FactureRec',
																				 'table'			=> 'facture_rec',
																				 'datefieldname'	=> 'datec',
																				 'test'				=> $conf->facture->enabled && $user->rights->facture->lire,
																				 'list1'			=> 1,
																				 'list2'			=> 1,
																				 ),
												'order_supplier'		=> array('name'				=> 'SuppliersOrders',
																				 'title'			=> 'ListSupplierOrdersAssociatedProject',
																				 'class'			=> 'CommandeFournisseur',
																				 'table'			=> 'commande_fournisseur',
																				 'datefieldname'	=> 'date_commande',
																				 'test'				=> $conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire,
																				 'list2'			=> 1,
																				 ),
												'invoice_supplier'		=> array('name'				=> 'BillsSuppliers',
																				 'title'			=> 'ListSupplierInvoicesAssociatedProject',
																				 'class'			=> 'FactureFournisseur',
																				 'margin'			=> 'minus',
																				 'table'			=> 'facture_fourn',
																				 'datefieldname'	=> 'datef',
																				 'test'				=> $conf->fournisseur->enabled && $user->rights->fournisseur->facture->lire,
																				 'list1'			=> 1,
																				 'list2'			=> 1,
																				 ),
												'contract'				=> array('name'				=> 'Contracts',
																				 'title'			=> 'ListContractAssociatedProject',
																				 'class'			=> 'Contrat',
																				 'table'			=> 'contrat',
																				 'datefieldname'	=> 'date_contrat',
																				 'test'				=> $conf->contrat->enabled && $user->rights->contrat->lire,
																				 'list2'			=> 1,
																				 ),
												'intervention'			=> array('name'				=> 'Interventions',
																				 'title'			=> 'ListFichinterAssociatedProject',
																				 'class'			=> 'Fichinter',
																				 'table'			=> 'fichinter',
																				 'datefieldname'	=> 'date_valid',
																				 'disableamount'	=> 1,
																				 'test'				=> $conf->ficheinter->enabled && $user->rights->ficheinter->lire,
																				 'list2'			=> 1,
																				 ),
												'trip'					=> array('name'				=> 'TripsAndExpenses',
																				 'title'			=> 'ListExpenseReportsAssociatedProject',
																				 'class'			=> 'Deplacement',
																				 'table'			=> 'deplacement',
																				 'datefieldname'	=> 'dated',
																				 'margin'			=> 'minus',
																				 'disableamount'	=> 1,
																				 'test'				=> $conf->deplacement->enabled && $user->rights->deplacement->lire,
																				 'list2'			=> 1,
																				 ),
												'expensereport'			=> array('name'				=> 'TripsAndExpenses',
																				 'title'			=> 'ListExpenseReportsAssociatedProject',
																				 'class'			=> 'ExpenseReport',
																				 'table'			=> 'expensereport',
																				 'datefieldname'	=> 'date_valid',
																				 'margin'			=> 'minus',
																				 'test'				=> $conf->expensereport->enabled && $user->rights->expensereport->lire,
																				 'list1'			=> 1,
																				 'list2'			=> 1,
																				 ),
												'expensereportplus'		=> array('name'				=> 'TripsAndExpenses',
																				 'title'			=> 'ListExpenseReportsAssociatedProject',
																				 'class'			=> 'Ndfp',
																				 'table'			=> 'ndfp',
																				 'datefieldname'	=> 'date_valid',
																				 'margin'			=> 'minus',
																				 'fk_projet'		=> 'fk_project',
																				 'test'				=> $conf->ndfp->enabled && $user->rights->ndfp->myactions->read,
																				 'list1'			=> 1,
																				 'list2'			=> 1,
																				 ),
												'project_task'			=> array('name'				=> 'TaskTimeSpent',
																				 'title'			=> 'ListTaskTimeUserProject',
																				 'class'			=> 'Task',
																				 'table'			=> 'projet_task',
																				 'datefieldname'	=> 'task_date',
																				 'margin'			=> 'minus',
																				 'test'				=> $conf->projet->enabled && $user->rights->projet->lire,
																				 'list1'			=> 1,
																				 'list2'			=> 1,
																				 ),
												'agenda'				=> array('name'				=> 'Agenda',
																				 'title'			=> 'ListActionsAssociatedProject',
																				 'class'			=> 'ActionComm',
																				 'table'			=> 'actioncomm',
																				 'datefieldname'	=> 'datep',
																				 'disableamount'	=> 1,
																				 'test'				=> $conf->agenda->enabled && $user->rights->agenda->allactions->read,
																				 )
												);
					// first Loop on each tables to prepare calculs and variables
					$nbLines			= 0;
					$previdofelement	= 0;
					foreach ($listofreferent as $key => $referent) {
						if (! $referent['test'] || ! $referent['list1'])	continue;
						$listKeyOk[]										= $key;
						$element											= new $referent['class']($db);
						$elementarray										= $object->get_element_list($key, $referent['table'], $referent['datefieldname'], $dates, $datee, !empty($referent['fk_projet']) ? $referent['fk_projet'] : 'fk_projet');
						$num												= count($elementarray);
						if (is_array($elementarray) && $num > 0) {
							$nbLines ++;
							$total_ht[$key]		= 0;
							$total_ttc[$key]	= 0;
							$nbr[$key]			= 0;
							$sign				= $key == 'invoice' ? 1 : -1;
							for ($i = 0; $i < $num; $i ++) {
								$idofelement	= $elementarray[$i];
								if ($referent['class'] == 'ExpenseReport')	// We get id of expense report
								{
									$expensereportline						= new ExpenseReportLine($db);
									$expensereportline->fetch($idofelement);
									$idofelement							= $expensereportline->fk_expensereport;
									if ($idofelement == $previdofelement)	continue;
									$previdofelement						= $expensereportline->fk_expensereport;
								}
								$element->fetch($idofelement);
								$qualifiedfortotal	= true;
								if ($key == 'invoice') {
									if ($element->close_code == 'replaced')												$qualifiedfortotal	= false;	// Replacement invoice
									if (!empty($this->deposit_are_payment) && $element->type == Facture::TYPE_DEPOSIT) $qualifiedfortotal	= false;	// If hidden option to use deposits as payment deposits are not included
								}
								if ($key == 'propal')
									if ($element->statut == Propal::STATUS_NOTSIGNED)	$qualifiedfortotal	= false;	// Refused proposal must not be included in total
								// Define $total_ht_by_line
								if ($referent['table'] == 'fichinter')	$total_ht_by_line	= $element->getAmount();
								elseif ($referent['table'] == 'projet_task') {
									$tmp				= $element->getSumOfAmount('', $dates, $datee);
									$total_ht_by_line	= price2num($tmp['amount'], 'MT');
								}
								else	$total_ht_by_line						= $element->total_ht;
								// Define $total_ttc_by_line
								if ($referent['table'] == 'fichinter')			$total_ttc_by_line	= $element->getAmount();
								elseif ($referent['table'] == 'projet_task')	$total_ttc_by_line	= price2num($total_ht_by_line, 'MT');
								else	$total_ttc_by_line						= $element->total_ttc;
								if ($qualifiedfortotal) {
									$name[$key]			= $referent['name'];
									$total_ht[$key]		+= $sign * $total_ht_by_line;
									$total_ttc[$key]	+= $sign * $total_ttc_by_line;
									$nbr[$key] ++;
								}
							}
						}
					}
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->MultiCell($this->larg_util_txt, $this->tab_hl, 'Bénéfices', '', 'L', 0, 1, $this->posx_G_txt, $nexY, true, 0, 0, true, $this->tab_hl, 'M', false);
					$nexY 							+= $this->tab_hl;
					$tab_height						= $this->ht_top_table * ($nbLines + 2);
					$curY							= $nexY;
					if (!empty($this->title_bg))	$pdf->RoundedRect($this->marge_gauche, $curY, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
					else if ($this->showtblline)	$pdf->RoundedRect($this->marge_gauche, $curY, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
					if ($this->showtblline)			$pdf->RoundedRect($this->marge_gauche, $curY + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
					else							$pdf->line($this->marge_gauche, $curY + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $curY + $tab_height, $this->horLineStyle);
					if ($this->showtblline) {
						// Colonnes
						if ($this->posxcol4 > $this->posxcol3)	$pdf->line($this->posxcol4,	$curY, $this->posxcol4,	$curY + $tab_height, $this->tblLineStyle);
						if ($this->posxcol5 > $this->posxcol4)	$pdf->line($this->posxcol5,	$curY, $this->posxcol5,	$curY + $tab_height, $this->tblLineStyle);
						if ($this->posxcol6 > $this->posxcol5)	$pdf->line($this->posxcol6,	$curY, $this->posxcol6,	$curY + $tab_height, $this->tblLineStyle);
					}
					// En-tête tableau
					$totalLarg	= $this->tableau['ref']['larg'] + $this->tableau['date']['larg'] + $this->tableau['tiers']['larg'];
					$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->MultiCell($totalLarg, $this->ht_top_table, $outputlangs->transnoentities("element"), '', 'C', 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities("AmountHTShort"), '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, $outputlangs->transnoentities("AmountTTCShort"), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['status']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Nombre"), '', 'C', 0, 1, $this->tableau['status']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$totalHT	= 0;
					$totalTTC	= 0;
					foreach ($listKeyOk as $keyOk) {
						if ($name[$keyOk]) {
							$curY	+= $this->ht_top_table;
							$pdf->MultiCell($totalLarg, $this->ht_top_table, $outputlangs->transnoentities($name[$keyOk]), '', 'L', 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
							$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, pdf_InfraSPlus_price($object, $total_ht[$keyOk], $outputlangs), '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
							$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, pdf_InfraSPlus_price($object, $total_ttc[$keyOk], $outputlangs), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
							$pdf->MultiCell($this->tableau['status']['larg'], $this->ht_top_table, $nbr[$keyOk], '', 'C', 0, 1, $this->tableau['status']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
							$totalHT	+= $total_ht[$keyOk];
							$totalTTC	+= $total_ttc[$keyOk];
						}
					}
					$curY	+= $this->ht_top_table;
					$pdf->MultiCell($totalLarg, $this->ht_top_table, $outputlangs->transnoentities('Bénéfices'), '', 'R', 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, pdf_InfraSPlus_price($object, $totalHT, $outputlangs), '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, pdf_InfraSPlus_price($object, $totalTTC, $outputlangs), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['status']['larg'], $this->ht_top_table, '', '', 'C', 0, 1, $this->tableau['status']['posx'], $curY, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$nexY	+= $tab_height + $entreTxtTable + $this->tab_hl;
					// Loop on each tables
					$previdofelement	= 0;
					foreach ($listofreferent as $key => $referent) {
						if (! $referent['test'] || ! $referent['list2'])	continue;
						$element		= new $referent['class']($db);
						$elementarray	= $object->get_element_list($key, $referent['table'], $referent['datefieldname'], $dates, $datee, !empty($referent['fk_projet']) ? $referent['fk_projet'] : 'fk_projet');
						$num			= count($elementarray);
						if ($num >= 0) {
							$curY	= $nexY;
							if (is_array($elementarray) && count($elementarray) > 0) {
								$total_ht	= 0;
								$total_ttc	= 0;
								$nbExpRep	= 0;	// number of expense report => the loop is on expense report lines so we can find x lines for one expense report
								$tab_pagenb	= $pdf->getPage();	// page en début de tableau
								$tab_top	= $curY;	// Y en début de tableau
								$nexY		= $curY + $this->ht_top_table;	// 1ère ligne (sous l'en-tête du tableau)
								// Loop on each lines
								for ($i = 0; $i < $num; $i ++) {
									$curY								= $nexY;
									$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
									$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
									if (empty($this->hide_top_table))	$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
									else								$pdf->setTopMargin($tab_top_newpage);
									$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
									$pageposbefore						= $pdf->getPage();
									$showpricebeforepagebreak			= 1;
									$idofelement						= $elementarray[$i];
									if ($referent['class'] == 'ExpenseReport')	// We get id of expense report
									{
										$expensereportline						= new ExpenseReportLine($db);
										$expensereportline->fetch($idofelement);
										$idofelement							= $expensereportline->fk_expensereport;
										if ($idofelement == $previdofelement)	continue;
										$previdofelement						= $expensereportline->fk_expensereport;
										$nbExpRep++;
									}
									$element->fetch($idofelement);
									if (method_exists($element, 'fetch_thirdparty'))	$element->fetch_thirdparty();
									$qualifiedfortotal									= true;
									if ($key == 'invoice') {
										if ($element->close_code == 'replaced')												$qualifiedfortotal	= false;	// Replacement invoice
										if (!empty($this->deposit_are_payment) && $element->type == Facture::TYPE_DEPOSIT) $qualifiedfortotal	= false;	// If hidden option to use deposits as payment deposits are not included
									}
									if ($key == 'propal')
										if ($element->statut == Propal::STATUS_NOTSIGNED)	$qualifiedfortotal	= false;	// Refused proposal must not be included in total
									// label
									$pageposdesc	= $pdf->getPage();
									$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $element->ref, '', 'L', 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
									$pageposafter	= $pdf->getPage();
									$posyafter		= $pdf->GetY();
									if ($pageposafter > $pageposbefore)	// There is a pagebreak
									{
										if ($posyafter > ($this->page_hauteur - $heightforfooter))	// There is no space left for total + page foot
										{
											if ($i == ($num - 1))	// No more lines, and no space left to show total, so we create a new page
											{
												$pdf->AddPage('', '', true);
												$pdf->setPage($pageposafter + 1);
											}
										}
										else	$showpricebeforepagebreak	= 0;
									}
									elseif ($posyafter > ($this->page_hauteur - $heightforfooter))	// There is no space left for total + page foot
									{
										if ($i == ($num - 1))	// No more lines, and no space left to show total, so we create a new page
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
									// Date
									if ($referent['table'] == 'commande_fournisseur' || $referent['table'] == 'supplier_order')	$date = $element->date_commande;
									else {
										$date				= $element->date;
										if (empty($date))	$date	= $element->datep;
										if (empty($date))	$date	= $element->date_contrat;
										if (empty($date))	$date	= $element->datev; // Fiche inter
										if (empty($date))	$date	= $element->date_create; // Expense report
									}
									$date	= $referent['table'] == 'projet_task' ? convertSecondToTime($element->duration_effective, 'allhourmin') : dol_print_date($date, 'day');
									$pdf->MultiCell($this->tableau['date']['larg'], $this->heightline, $date, '', 'C', 0, 1, $this->tableau['date']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
									// Name
									if ($referent['class'] == 'ExpenseReport') {
										$fuser		= new User($db);
										$fuser->fetch($element->fk_user_author);
										$txtName	= $fuser->getFullName($outputlangs);
									}
									else	$txtName	= $referent['table'] == 'projet_task' ? $element->label : (is_object($element->thirdparty) ? $element->thirdparty->name : '');
									$pdf->MultiCell($this->tableau['tiers']['larg'], $this->heightline, $txtName, '', 'C', 0, 1, $this->tableau['tiers']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
									// Amount without tax
									if (empty($referent['disableamount'])) {
										// Define $total_ht_by_line
										if ($referent['table'] == 'fichinter')	$total_ht_by_line	= $element->getAmount();
										elseif ($referent['table'] == 'projet_task') {
											$tmp				= $element->getSumOfAmount('', $dates, $datee);
											$total_ht_by_line	= price2num($tmp['amount'], 'MT');
										}
										else	$total_ht_by_line						= $element->total_ht;
										// Define $total_ttc_by_line
										if ($referent['table'] == 'fichinter')			$total_ttc_by_line	= $element->getAmount();
										elseif ($referent['table'] == 'projet_task')	$total_ttc_by_line	= price2num($total_ht_by_line, 'MT');
										else	$total_ttc_by_line						= $element->total_ttc;
										$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, (isset($total_ht_by_line) ? price($total_ht_by_line) : ''), '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
										$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, (isset($total_ttc_by_line) ? price($total_ttc_by_line) : ''), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
									}
									else {
										if ($key == 'agenda') {
											$textforamount	= dol_trunc($element->label, 26);
											$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $textforamount, '', 'L', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
										}
										else	$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, '', '', 'L', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
									}
									// Status
									if ($element instanceof CommonInvoice)	$outputstatut	= $element->getLibStatut(1, $element->getSommePaiement());	// This applies for Facture and FactureFournisseur
									elseif ($element instanceof ndfp)		$outputstatut	= $element->get_lib_statut(1);
									else									$outputstatut	= $element->getLibStatut(1);
									$pdf->MultiCell($this->tableau['status']['larg'], $this->heightline, $outputstatut, '', 'R', 0, 1, $this->tableau['status']['posx'], $curY, true, 0, true, true, $this->heightline, 'M', false);
									if ($qualifiedfortotal) {
										$total_ht	+= $total_ht_by_line;
										$total_ttc	+= $total_ttc_by_line;
									}
									// Add dash or space between line
									if ($this->dash_between_line && $i < ($num - 1)) {
										$pdf->setPage($pageposafter);
										$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
										$nexY	+= 2;
									}
									else	$nexY	+= $this->lineSep_hight;
								}
								if (empty($referent['disableamount'])) {
									$curY		= $nexY;
									$totalLarg	= $this->tableau['ref']['larg'] + $this->tableau['date']['larg'] + $this->tableau['tiers']['larg'];
									$pdf->MultiCell($totalLarg, $this->heightline, $outputlangs->transnoentities("total"), '', 'R', 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
									$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, price($total_ht), '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
									$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, price($total_ttc), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
									$pdf->MultiCell($this->tableau['status']['larg'], $this->heightline, $outputlangs->transnoentities("Nb").' '.($referent['class'] == 'ExpenseReport' ? $nbExpRep : $num), '', 'R', 0, 1, $this->tableau['status']['posx'], $curY, true, 0, 0, true, $this->heightline, 'M', false);
								}
								$curY			= $nexY;
								$tab_end_pagenb	= $pdf->getPage();	// page en fin de tableau
								$tab_end		= $curY + (empty($referent['disableamount']) ? $this->heightline + 2 : 0);	// Y en fin de tableau
								//$test			= 'p_d : '.$tab_pagenb.' / p-f : '.$tab_end_pagenb.' ** Y_d : '.$tab_top.' / Y_f : '.$tab_end;
								if ($tab_pagenb == $tab_end_pagenb) {
									$tab_height	= $tab_end - $tab_top;
									$this->_tableau($pdf, $object, $tab_top, $tab_height, $outputlangs, $this->hide_top_table, 1, $tab_pagenb, $referent);
								}
								if ($tab_pagenb == ($tab_end_pagenb - 1)) {
									if (($this->page_hauteur - $heightforfooter) > ($tab_top + 	$this->tab_hl + $this->ht_top_table + $this->heightline)) {
										$tab_height	= $this->page_hauteur - $heightforfooter - $tab_top;
										$this->_tableau($pdf, $object, $tab_top, $tab_height, $outputlangs, $this->hide_top_table, 1, $tab_pagenb, $referent);
									}
									$tab_height	= $tab_end - $tab_top_newpage;
									$this->_tableau($pdf, $object, $tab_top_newpage, $tab_height, $outputlangs, $this->hide_top_table, 1, $tab_end_pagenb, $referent);
								}
								// Enregistrement de la position intermédiaire
								$pageposbefore	= $pdf->getPage();
								$nexY			+= $this->hide_top_table ? $entreTxtTable : ($entreTxtTable + $this->ht_top_table);
							}
							// Detect if some page were added automatically and output _pagefoot for past pages
							while ($pagenb < $pageposafter) {
								$pdf->setPage($pagenb);
								$this->_pagefoot($pdf, $object, $outputlangs, 0);
								$pagenb++;
								$pdf->setPage($pagenb);
								$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity);	// Show Watermarks
								if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
								else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							}
						}
					}
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
					$this->error=$outputlangs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}
			else {
				$this->error=$outputlangs->trans("ErrorConstantNotDefined","PROJECT_OUTPUTDIR");
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
		********************************************/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = 'Project') {
			global $conf, $hookmanager;

			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$w					= $this->header_align_left ? 92 - $this->decal_round : 100;
			$align				= $this->header_align_left ? 'L' : 'R';
			$posy				= $this->marge_haute;
			$posx				= $this->page_largeur - $this->marge_droite - $w;
			// Logo
			$heightLogo			= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $this->logo, $this->emetteur, $this->marge_gauche, $this->tab_hl, $this->headertxtcolor, $object->entity);
			$heightLogo			+= $posy + $this->tab_hl;
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$title				= $outputlangs->transnoentities($object->title);	// $titlekey);
			$pdf->MultiCell($w, $this->tab_hl * 2, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$posy				+= $this->tab_hl * 2;
			$txtref				= $outputlangs->transnoentities('Ref')." : ".$outputlangs->convToOutputCharset($object->ref);
			if ($object->statut == 0) {
				$pdf->SetTextColor(128, 0, 0);
				$txtref .= ' - '.$outputlangs->transnoentities("NotValidated");
			}
			$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
			$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
			$posy	+= $this->tab_hl;
			$txtdtS	= $outputlangs->transnoentities("DateStart").' : '.dol_print_date($object->date_start, "day", false, $outputlangs, true);
			$pdf->MultiCell($w, $this->tab_hl, $txtdtS, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl;
			$txtdtE	= $outputlangs->transnoentities("DateEnd").' : '.dol_print_date($object->date_end, "day", false, $outputlangs, true);
			$pdf->MultiCell($w, $this->tab_hl, $txtdtE, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 2);
			if (is_object($object->thirdparty)) {
				$posy	+= $this->tab_hl;
				$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities("ThirdParty").' : '.$object->thirdparty->getFullName($outputlangs), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			}
			$posy	+= $this->tab_hl;
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
	//		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 4, $object->title, '', 'C', 0, 1, $this->marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
			$posy			+= 0.5;
			$hauteurhead	= array('totalhead'		=> ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
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
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "Project") {
			global $conf, $hookmanager;

			$fromcompany	= $this->emetteur;
			$title			= $outputlangs->transnoentities($object->title);	// $titlekey);
			pdf_InfraSPlus_pagesmallhead($pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $this->formatpage, $this->decal_round, $this->logo, $this->headertxtcolor);
		}

		/********************************************
		*   Show table for lines
		*
		*	@param		PDF			$pdf     		Object PDF
		*	@param  	Object		$object     	Object to show
		*	@param		string		$tab_top		Top position of table
		*	@param		string		$tab_height		Height of table (rectangle)
		*	@param		Translate	$outputlangs	Langs object
		*	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int			$hidebottom		Hide bottom bar of array
		*	@return		void
		********************************************/
		protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb, $referent) {
			global $conf;

			// Force to disable hidetop and hidebottom
			$hidebottom			= 0;
			if ($hidetop)		$hidetop	= -1;
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->setPage($pagenb);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->larg_util_txt, $this->tab_hl, $outputlangs->transnoentities($referent['title']), '', 'L', 0, 1, $this->posx_G_txt, $tab_top - $this->tab_hl, true, 0, 0, true, $this->tab_hl, 'M', false);
				if (!empty($this->title_bg))	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				else if ($this->showtblline)	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				if ($this->showtblline)			$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				else							$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
			}
			else
				if ($this->showtblline)	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			if ($this->showtblline) {
				// Colonnes
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol2,		$tab_top, $this->posxcol2,	$tab_top + $tab_height, $this->tblLineStyle);
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol3,		$tab_top, $this->posxcol3,	$tab_top + $tab_height, $this->tblLineStyle);
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol4,		$tab_top, $this->posxcol4,	$tab_top + $tab_height, $this->tblLineStyle);
				if ($this->posxcol5 > $this->posxcol4 && $this->posxcol5 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol5,		$tab_top, $this->posxcol5,	$tab_top + $tab_height, $this->tblLineStyle);
				if ($this->posxcol6 > $this->posxcol5 && $this->posxcol6 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol6,		$tab_top, $this->posxcol6,	$tab_top + $tab_height, $this->tblLineStyle);
			}
			// En-tête tableau
			$this->title_bg ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['date']['larg'], $this->ht_top_table, $outputlangs->transnoentities(($referent['table'] == 'projet_task' ? 'Time' : 'Date')), '', 'C', 0, 1, $this->tableau['date']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['tiers']['larg'], $this->ht_top_table, $outputlangs->transnoentities(($referent['table'] == 'projet_task' ? 'label' : ($referent['class'] == 'ExpenseReport' ? 'User' : 'ThirdParty'))), '', 'C', 0, 1, $this->tableau['tiers']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (empty($referent['disableamount'])) {
					$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities("AmountHTShort"), '', 'R', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, $outputlangs->transnoentities("AmountTTCShort"), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
				else	$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, '', '', 'R', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['status']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Status"), '', 'C', 0, 1, $this->tableau['status']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
			}
		}

		/********************************************
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		PDF			$pdf     		The PDF factory
		*	@param		Object		$object			Object shown in PDF
		*	@param		Translate	$outputlangs	Object lang for output
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