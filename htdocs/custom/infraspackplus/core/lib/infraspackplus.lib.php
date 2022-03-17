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

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	require_once TCPDF_PATH.'tcpdf.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Tests présence de champs supplémentaires dans les tables.
	*
	*	@param   string	$appliname	module name
	************************************************/
	function infraspackplus_test_new_fields($appliname)
	{
		dol_syslog('infraspackplus.Lib::infraspackplus_test_new_fields appliname = '.$appliname);
		infraspackplus_get_test_new_fields($appliname, 'societe_address', 'email');
		infraspackplus_get_test_new_fields($appliname, 'societe_address', 'url');
		infraspackplus_get_test_new_fields($appliname, 'societe', 'logo_emet');
	}

	/************************************************
	*	Tests présence d'un champ supplémentaire dans une table.
	*	Exécution du script de création si besoin.
	*
	*	@param   string	$appliname	module name
	*	@param   string	$table		simple table name without any prefix
	*	@param   string	$field		field name
	************************************************/
	function infraspackplus_get_test_new_fields($appliname, $table, $field)
	{
		global $conf, $db;

		$path			= dol_buildpath($appliname, 0).'/sql';
		$sql_column		= 'SHOW COLUMNS FROM '.MAIN_DB_PREFIX.$table.' LIKE "'.$field.'"';
		$result_columns	= $db->query($sql_column);
		dol_syslog('infraspackplus.Lib::infraspackplus_get_test_new_fields sql_column = '.$sql_column);
		if (! $db->num_rows($result_columns)) {
			$filetable	= $path.'/llx_'.$table.'-'.$field.'.sql';
			$result		= run_sql($filetable, 1, '', 1);
			dol_syslog('infraspackplus.Lib::infraspackplus_get_test_new_fields filetable = '.$filetable.' result = '.$result);
		}
		$db->free($result_columns);
	}

	/************************************************
	*	Modifie le fichier actions_milestone.class.php
	*	pour le rendre compatible avec le module
	*
	*	@param		string		$module_name	module name where is the file to change
	*	@param		string		$relFile		file name (with relative path from module name) to change
	*	@param		string		$tSearch		type of search 'F' => file 'S' => string
	*	@param		string		$search			string to search or name of the file containing this string
	*	@param		string		$replace		name of the file containing the string to use for replacement
	*	@param		string		$tReg			type of replacement 'F' => from file 'R' => from RegEx
	*	@param		string		$reg			string to replace or name of the file containing this string
	*	@return		void
	************************************************/
	function infraspackplus_test_module($module_name, $relFile, $tSearch, $search, $replace, $tReg, $reg)
	{
		global $conf, $langs;

		if (empty($conf->global->INFRASPACKPLUS_DISABLED_MODULE_CHANGE)) {
			$path			= dol_buildpath($module_name, 0);
			$mypath			= dol_buildpath('infraspackplus', 0);
			dol_syslog('infraspackplus.lib::infraspackplus_test_module path = '.$path.' PHP_OS = '.PHP_OS);
			$fileactions	= $path.$relFile;
			$i				= substr ($search, 0, 1) == substr ($reg, 0, 1) && preg_match('/^\d/', $search) ? '.'.substr ($search, 0, 1) : '';
			$search			= $tSearch == 'F' ? file_get_contents ($mypath.'/includes/'.$module_name.'/'.$search) : $search;
			$filereplace	= $mypath.'/includes/'.$module_name.'/'.$replace;
			$reg			= $tReg == 'F' ? file_get_contents ($mypath.'/includes/'.$module_name.'/'.$reg) : $reg;
			$actions		= strpos (file_get_contents ($fileactions), $search) === false ? file_get_contents ($fileactions) : false;
			if ($actions !== false && is_file($filereplace)) {
				$moved	= dol_copy($fileactions, $fileactions.'.old'.$i);
				dol_syslog('infraspackplus.lib::infraspackplus_test_module fileactions = '.$fileactions.' moved = '.$moved);
				if ($moved > 0) {
					if ($tReg == 'R')	$result	= file_put_contents ($fileactions, preg_replace ($reg, file_get_contents ($filereplace), $actions));
					if ($tReg == 'F')	$result	= file_put_contents ($fileactions, str_replace ($reg, file_get_contents ($filereplace), $actions));
				}
				else					$result	= false;
				if ($result	=== false)	setEventMessages('<FONT color = "red">'.$langs->trans('InfraSPlusCautionMess').'</FONT>'.$langs->trans('InfraSPlusModuleFileError', $module_name), null, 'errors');
			}
		}
	}

	/************************************************
	*	Check line type : title or subtotal from external module ?
	*
	*	@param		object		$line			line we work on
	*	@param		string		$element		line object element (for special case like shipping)
	*	@param		string		$searchName		module name we look for
	*	@return		integer						-1 if KO, 0 not found or module number if Ok
	************************************************/
	function infraspackplus_isSubTotalLine ($line, $element, $searchName)
	{
		global $db;

		if ($element == 'shipping' || $element == 'delivery') {
			$fk_origin_line	= $line->fk_origin_line;
			$line			= new OrderLine($db);
			$line->fetch($fk_origin_line);
		}
		if ($line->product_type == 9 && $line->special_code == infraspackplus_get_mod_number($searchName))	return true;
		else																								return false;
	}

	/************************************************
	*	Find module number
	*
	*	@param		string		$searchName		module name we look for
	*	@return		integer						-1 if KO, 0 not found or module number if Ok
	************************************************/
	function infraspackplus_get_mod_number ($modName)
	{
		global $db;

		if (class_exists($modName)) {
			$objMod	= new $modName($db);
			return $objMod->numero;
		}
		return 0;
	}

	/************************************************
	*	Find the title link to subtotal
	*
	*	@param		object		$object			Object we work on
	*	@param		object		$currentLine	Line we work on
	*	@param		integer		$modNumber		External module ID used
	*	@return		string						label or description of the title found
	************************************************/
	function infraspackplus_getTitle($object, $currentLine, $modNumber)
	{
		$res	= '';
		foreach ($object->lines as $line) {
			if ($line->id == $currentLine->id)																break;
			$qty_search																						= 100 - $currentLine->qty;
			if ($line->product_type == 9 && $line->special_code == $modNumber && $line->qty == $qty_search)	$res	= ($line->label) ? $line->label : (($line->description) ? $line->description : $line->desc);
		}
		return $res;
	}

	/************************************************
	*	Change directory name for Dolibarr 12
	*
	*	@param		string		$oldname	path with folder name to change
	*	@param		string		$newname	path with the new folder name
	*	@return		void
	************************************************/
	function infraspackplus_chg_dir_name ($oldname, $newname)
	{
		if (dol_is_dir($oldname)) {
			$resultCopy				= dolCopyDir($oldname, $newname, 0, 1);
			dol_syslog('infraspackplus.lib::infraspackplus_chg_dir_name oldname = '.$oldname.' newname = '.$newname.' resultCopy = '.$resultCopy);
			if ($resultCopy > 0)	dol_delete_dir_recursive($oldname);
		}
	}

	/************************************************
	*	Liste de fichier de conditions générales suivant le type recherché
	*
	*	@param	string	$type	type of file (CGV, CGI or CGA)
	*	@param	integer	$entity	current entity for multicompany
	*	@param	object	$object	object we work on
	*	@return	array			array of file name found for the type wanted
	************************************************/
	function infraspackplus_get_CGfiles ($type, $entity, $object = null)
	{
		global $conf, $db;

		$CGFromPro		= !empty($conf->global->INFRASPLUS_PDF_CGV_FROM_PRO)							? 1																			: 0;
		$labelFromPro	= !empty($CGFromPro) && isset($conf->global->INFRASPLUS_PDF_CGV_FROM_PRO_LABEL)	? $conf->global->INFRASPLUS_PDF_CGV_FROM_PRO_LABEL							: '';
		$myCompDir		= !empty($conf->mycompany->multidir_output[$entity])							? !empty($conf->mycompany->multidir_output[$entity])						: $conf->mycompany->dir_output;
		$CGs			= array();
		$labelToSearch	= !empty($labelFromPro) && is_object($object)									? ($object->thirdparty->typent_code == $labelFromPro ? $labelFromPro : '')	: '';
		if (glob($myCompDir.'/'.$type.'_*.pdf'))
			foreach (glob($myCompDir.'/'.$type.'_*'.$labelToSearch.'*.pdf') as $file)	$CGs[]	= dol_basename($file);
		if (!empty($CGFromPro) && empty($labelToSearch) && !empty($labelFromPro) && !empty($CGs)) {
			$exclude																	= array();
			foreach (glob($myCompDir.'/'.$type.'_*'.$labelFromPro.'*.pdf') as $file)	$exclude[]	= dol_basename($file);
			$CGs																		= array_diff($CGs, $exclude);
		}
		return $CGs;
	}

	/************************************************
	*	Recherche d'un fichier contenant un code langue dans son nom à partir d'une liste
	*
	*	@param	array	$CGs		list of file name to check
	*	@param	string	$searchLang	langage code search
	*	@return	string				file name found
	************************************************/
	function infraspackplus_get_CGfiles_lang ($CGs, $searchLang)
	{
		for ($i = 0; $i < count($CGs); $i++) {
			$langCG						= explode('.', $CGs[$i]);
			$langCG						= $langCG[count($langCG) - 2];
			if ($langCG == $searchLang)	return $CGs[$i];
		}
		return '';
	}

	/************************************************
	*	Conversion de fichier ttf en police TCPDF.
	*
	*	@param	String		$type			Font type. Leave empty for autodetect mode.
	*										Valid values are:
	*											TrueTypeUnicode
	*											TrueType
	*											Type1
	*											CID0JP	= CID-0	Japanese
	*											CID0KR	= CID-0	Korean
	*											CID0CS	= CID-0	Chinese Simplified
	*											CID0CT	= CID-0	Chinese Traditional
	*	@param  String		$enc     		Name of the encoding table to use.
	*										Leave empty for default mode. Omit this parameter for TrueType Unicode and symbolic font like Symbol or ZapfDingBats.
	*	@param  int	    	$flags   		Unsigned 32-bit integer containing flags specifying various characteristics of the font
	*										(PDF32000:2008 - 9.8.2 Font Descriptor Flags):
	*											+1 for fixed font;
	*											+4 for symbol;
	*											+32 for non-symbol;
	*											+64 for italic.
	*											Fixed and Italic mode are generally autodetected so you have to set it to 32 = non-symbolic font (default) or 4 = symbolic font.
	*	@param  String		$outpath		Output path for generated font files (must be writeable by the web server). Leave empty for default font folder.
	* 	@param	String		$platid			Platform ID for CMAP table to extract
	*										(when building a Unicode font for Windows this value should be 3, for Macintosh should be 1).
	* 	@param	Societe		$encid			Encoding ID for CMAP table to extract
	*										(when building a Unicode font for Windows this value should be 1, for Macintosh should be 0).
	*										When Platform ID is 3, legal values for Encoding ID are:
	*											0	= Symbol
	*											1	= Unicode
	*											2	= ShiftJIS
	*											3	= PRC
	*											4	= Big5
	*											5	= Wansung
	*											6	= Johab
	*											7	= Reserved
	*											8	= Reserved
	*											9	= Reserved
	*											10	= UCS-4
	* 	@param	Booleen		$addcbbox		Includes the character bounding box information on the php font file.
	* 	@param	Booleen		$link			Link to system font instead of copying the font data # (not transportable) - Note: do not work with Type1 font.
	* 	@param	String		$font			input font file.
	*	@return	String						Return Font name or false if error.
	************************************************/
	function infraspackplus_Add_TCPDF_Font($type = '', $enc = '', $flags = 32, $outpath, $platid = 3, $encid = 1, $addcbbox = false, $link = false, $font)
	{
		$options											= array();
		$typefont											= array('TrueTypeUnicode', 'TrueType', 'Type1', 'CID0JP', 'CID0KR', 'CID0CS', 'CID0CT');
		if (in_array($type, $typefont))	$options['type']	= $type;
		else												$options['type']	= '';
		$options['enc']										= $enc;
		$options['flags']									= intval($flags);
		$options['outpath']									= realpath($outpath);
		if (substr($options['outpath'], -1) != '/')			$options['outpath']	.= '/';
		$options['platid']									= min(max(1, intval($platid)), 3);
		$options['encid']									= min(max(0, intval($encid)), 10);
		$options['addcbbox']								= $addcbbox;
		$options['link']									= $link;
		$fontfile											= realpath($font);
		$fontname											= TCPDF_FONTS::addTTFfont($fontfile, $options['type'], $options['enc'], $options['flags'], $options['outpath'], $options['platid'], $options['encid'], $options['addcbbox'], $options['link']);
		return $fontname;
	}

	/************************************************
	* Function called to check Logo files associate to customer
	*
	* @param	string	$socid	societe Id to check
	* @return	string			logo file name
	************************************************/
	function infraspackplus_getLogoEmet($socid)
	{
		global $conf, $db;

		$logo_emet			= '';
		$sql_logo_emet		= 'SELECT s.logo_emet';
		$sql_logo_emet		.= ' FROM '.MAIN_DB_PREFIX.'societe AS s';
		$sql_logo_emet		.= ' WHERE s.rowid = '.$socid;
		$result_logo_emet	= $db->query($sql_logo_emet);
		if ($result_logo_emet) {
			$obj_logo_emet	= $db->fetch_object($result_logo_emet);
			$logo_emet		= $obj_logo_emet->logo_emet;
		}
		$db->free($result_logo_emet);
		return $logo_emet;
	}
	/************************************************
	* Function called to update Logo files associate to customer
	*
	* @param	string	$socid	societe Id to update
	* @param	string	$logo	File name to update
	* @return	string			0 if OK SQL error else
	************************************************/
	function infraspackplus_setLogoEmet($socid, $logo)
	{
		global $conf, $db;

		$sql_upt	= 'UPDATE '.MAIN_DB_PREFIX.'societe';
		$sql_upt	.= ' SET logo_emet = "'.$logo.'"';
		$sql_upt	.= ' WHERE rowid = '.$socid;
		$result_upt	= $db->query($sql_upt);
		if ($result_upt) {
			$db->free($result_upt);
			return 0;
		}
		else	return $db->error().' sql = '.$sql_upt;
	}

	/************************************************
	*	Return list of mention
	*
	*	@param	string	$dict			SQL table name
	*	@param	string	$selected		Preselected type
	*	@param  string	$htmlname		Name of field in html form
	* 	@param	int		$showempty		Add an empty field
	*	@param  string	$onChange		JavaScript for onchange event
	*	@param  int		$hasLabel		Show label before select
	*	@param  string	$filter			MySQL filter (example : 'code LIKE "TVA\_%"')
	*	@return	string					Select html tag with all mention labels found
	************************************************/
	function select_infraspackplus_dict($dict, $selected = '', $htmlname = 'fk_infraspackplus_dict', $showempty = 0, $onChange = '', $hasLabel = 1, $filter = '')
	{
		global $db, $conf, $langs;

		$typeDict	= ucfirst(explode('_', $dict)[2]);
		$result		= '';
		$sql		= 'SELECT rowid, code, libelle';
		$sql		.= ' FROM '.MAIN_DB_PREFIX.$dict;
		$sql		.= ' WHERE active = 1 AND entity = "'.$conf->entity.'"';
		$sql		.= !empty($filter) ? ' AND '.$filter : '';
		$sql		.= ' ORDER BY pos ASC';
		$resql		= $db->query($sql);
		dol_syslog('infraspackplus.Lib::select_infraspackplus_dict sql = '.$sql);
		if ($resql) {
			$num	= $db->num_rows($resql);
			$i		= 0;
			if ($num) {
				$result	.= $hasLabel ? '&nbsp;'.$langs->trans('InfraSPlusParam'.$typeDict.'3').'&nbsp;' : '';
				$result	.= '<select class = "flat" name="'.$htmlname.'" style = "max-width:270px;"'.($onChange ? 'onchange = "'.$onChange.';"' : '').'>';
				if ($showempty) {
					$result					.= '<option value = "-1"';
					if ($selected == -1)	$result	.= ' selected = "selected"';
					$result					.= '>&nbsp;</option>';
				}
				while ($i < $num) {
					$obj							= $db->fetch_object($resql);
					$libelle						= ($langs->trans('InfraSPlusDict'.$typeDict.'s'.$obj->code) != ('InfraSPlusDict'.$typeDict.'s'.$obj->code) ? $langs->trans('InfraSPlusDict'.$typeDict.'s'.$obj->code) : ($obj->libelle != '-' ? $obj->libelle : ''));
					$result							.= '<option value = "'.$obj->code.'"';
					if ($obj->code == $selected)	$result	.= ' selected';
					$result							.= '>'.dol_trunc($libelle, 32, 'middle').'</option>';
					$i++;
				}
				$result	.= '</select>';
			}
			else	$result	.= '<input type = "hidden" name = "'.$htmlname.' id = "'.$htmlname.'" value = -1>';	// si pas de liste, on positionne un hidden vide
		}
		else	$result	.= '<input type = "hidden" name = "'.$htmlname.' id = "'.$htmlname.'" value = -1>';	// si pas de liste, on positionne un hidden vide
		return	$result;
	}

	/************************************************
	*	Modify payment methode according to an old parameter.
	*
	*	@return	int				1 = Ok -1 = Ko
	************************************************/
	function infraspackplus_modify_paiement_spec()
	{
		global $db, $conf;

		$idPaySpec	= isset($conf->global->INFRASPLUS_PDF_PAY_SPEC) ? $conf->global->INFRASPLUS_PDF_PAY_SPEC : '';
		dol_syslog('infraspackplus.Lib::infraspackplus_modify_paiement_spec idPaySpec = '.$idPaySpec);
		if (!empty($idPaySpec)) {
			$sqldict	= 'UPDATE '.MAIN_DB_PREFIX.'c_paiement SET type = 3';
			$sqldict	.= ' WHERE id = '.$idPaySpec.' AND entity = "'.$conf->entity.'"';
			$resqldict	= $db->query($sqldict);
			if ($resqldict) {
				$result	= dolibarr_del_const($db, 'INFRASPLUS_PDF_PAY_SPEC', $conf->entity);
				return $result;
			}
		}
		return -1;
	}

	/************************************************
	*	Create a new PDF to show what the chosen font looks like
	*
	*	@return		string		1 = Ok or 0 = Ko
	************************************************/
	function infraspackplus_test_font()
	{
		global $db, $conf, $langs;
		$formatarray		= pdf_InfraSPlus_getFormat();
		$page_largeur		= $formatarray['width'];
		$page_hauteur		= $formatarray['height'];
		$format				= array($page_largeur, $page_hauteur);
		$main_umask			= isset($conf->global->MAIN_UMASK)			? $conf->global->MAIN_UMASK				: '0755';
		$font				= isset($conf->global->INFRASPLUS_PDF_FONT) ? $conf->global->INFRASPLUS_PDF_FONT	: 'Helvetica';
		$dir				= $conf->ecm->dir_output.'/temp/';
		$file				= $dir.'TEST.pdf';
		if (! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				setEventMessages($langs->trans('ErrorCanNotCreateDir', $dir), null, 'errors');
				return 0;
			}
		}
		if (file_exists($dir)) {
			// Create pdf instance
			$pdf				= pdf_getInstance($format, 'mm', 'L');
			$default_font_size	= pdf_getPDFFontSize($langs);																								// Must be after pdf_getInstance
			$pdf->SetAutoPageBreak(1, 0);
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont($font, '', 14);	// set font
			$tagvs						= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
			$pdf->setHtmlVSpace($tagvs);
			$pdf->Open();
			// set document information
			$pdf->SetTitle('InfraSPackPlus test font');
			$pdf->SetSubject('InfraSPackPlus');
			$pdf->SetCreator('Dolibarr '.DOL_VERSION);
			$pdf->SetAuthor('InfraS - Sylvain Legrand');
			$pdf->SetKeywords('InfraS, InfraSPack, InfraSPackPlus, PDF, example, test, guide');
			$pdf->SetMargins(10, 10, 10);   // Left, Top, Right
			$pdf->AddPage();	// add a page
			$txt						= "Font : ".$font."<br/>Test :<ul><li>Normal<ul><li>&nbsp;&nbsp;a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</li><li>&nbsp;&nbsp;0 1 2 3 4 5 6 7 8 9 + - * = ° ² é è à ù ç â ê î ô û ä ë ï ö ü , ; : ! ? . & § % µ @ $ £ € ¤ # | ( ) { } [ ] < > _ ~</li></ul></li><li><b>Gras</b><ul><li>&nbsp;&nbsp;<b>a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</b></li><li>&nbsp;&nbsp;<b>0 1 2 3 4 5 6 7 8 9 + - * = ° ² é è à ù ç â ê î ô û ä ë ï ö ü , ; : ! ? . & § % µ @ $ £ € ¤ # | ( ) { } [ ] < > _ ~</b></li></ul></li><li><em>Italique<em><ul><li>&nbsp;&nbsp;<em>a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</em></li><li>&nbsp;&nbsp;<em>0 1 2 3 4 5 6 7 8 9 + - * = ° ² é è à ù ç â ê î ô û ä ë ï ö ü , ; : ! ? . & § % µ @ $ £ € ¤ # | ( ) { } [ ] < > _ ~</em></li></ul></li><li><b>Gras Italique</b><ul><li>&nbsp;&nbsp;<em><b>a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</b></em></li><li>&nbsp;&nbsp;<em><b>0 1 2 3 4 5 6 7 8 9 + - * = ° ² é è à ù ç â ê î ô û ä ë ï ö ü , ; : ! ? . & § % µ @ $ £ € ¤ # | ( ) { } [ ] < > _ ~</b></em></li></ul></li></ul>";	// set some text to print
			$pdf->writeHTMLCell($page_hauteur - 20, $page_largeur - 20, 10, 10, dol_htmlentitiesbr($txt), 0, 1);
			$pdf->Close();
			$pdf->Output($file, 'F');
			if (! empty($main_umask))	@chmod($file, octdec($main_umask));
			return 1;   // Pas d'erreur
		}
		else {
			setEventMessages($langs->transnoentities('ErrorCanNotCreateDir', $dir), null, 'errors');
			return 0;
		}
	}

	/************************************************
	*	Check if the parent company sould be used
	*
	*	@param		Object		$object		Object we want to build document for
	*	@return		Object					Object address found
	************************************************/
	function infraspackplus_check_parent_addr_fact ($object)
	{
		global $db, $conf;

		$parent_adrfact	= isset($conf->global->INFRASPLUS_PDF_FACTURE_PARENT_ADDR_FACT)	? $conf->global->INFRASPLUS_PDF_FACTURE_PARENT_ADDR_FACT	: 0;
		if (! empty($parent_adrfact) && ! empty($object->thirdparty->parent)) {
			$parent	= new Societe($db);
			$parent->fetch($object->thirdparty->parent);
			return $parent;
		}
		else	return $object->thirdparty;
	}

	/************************************************
	*	Search an extrafield by name
	*
	*	@param		integer		$set		-1	= disable extrafields
											0	= check and update or enable
											1	= create extrafields
	*	@param		string		$tempName	temporary name of exterafield (used when the $const values are not loaded => init module process)
	*	@return		integer					> 0	= found (we return the number of extrafields found + the number created or the number updated)
											0	= not found
											-1	= no name
											-2 on error
	************************************************/
	function infraspackplus_search_extf ($set = 0, $tempName = '')
	{
		global $db, $langs, $conf;

		$name	= isset($conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF)	? $conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF	: $tempName;
		if (!empty($name)) {
			$listElem	= array('propal', 'commande', 'fichinter', 'expedition', 'facture', 'supplier_proposal', 'commande_fournisseur', 'facture_fourn');
			$sql		= 'SELECT elementtype FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "'.$name.'"';
			$resql		= $db->query($sql);
			if ($resql) {
				$num						= $db->num_rows($resql);
				if ($num == 0 && $set == 0)	return 0;	// there is no extrafield and we don't want to create them
				$extra						= new ExtraFields($db);
				if ($set == 1) {	// create
					foreach($listElem as $new)	$extra->addExtraField($name, 'InfraSPlusParamLabelExfFreeAddrLivr', 'html', 100, '2000', $new, 0, 0, '', '', 1, '', '3', '', '', $conf->entity, 'infraspackplus@infraspackplus', '1', 0, 2);
					return 1;
				}
				$arr										= array();
				while ($obj = $db->fetch_object($resql))	$arr[]	= $obj->elementtype;
				dol_syslog('infraspackplus_search_extf $arr = '.implode(',', $arr));
				if ($set == 0) {	// check and update or enable
					foreach($arr as $new)	$result	= $extra->update($name, 'InfraSPlusParamLabelExfFreeAddrLivr', 'html', '2000', $new, 0, 0, '100', '', 1, '', '3', '', '', '', $conf->entity, 'infraspackplus@infraspackplus', '1', 0, 2);
					dol_syslog('infraspackplus_search_extf $result = '.$result);
					$diff					= array_diff($listElem, $arr);
					if (!empty($diff)) {	// some extrafields are missing for some types; we need to update the missing elements
						foreach($diff as $new)	$extra->addExtraField($name, 'InfraSPlusParamLabelExfFreeAddrLivr', 'html', 100, '2000', $new, 0, 0, '', '', 1, '', '3', '', '', $conf->entity, 'infraspackplus@infraspackplus', '1', 0, 2);
						return $num + count($diff);
					}
					return $num;
				}
				if ($set == -1) {	// disable
					foreach($arr as $old)	$result	= $extra->update($name, 'InfraSPlusParamLabelExfFreeAddrLivr', 'html', '2000', $old, 0, 0, '100', '', 1, '', '0', '', '', '', $conf->entity, 'infraspackplus@infraspackplus', '0', 0, 0);
					return count($arr);
				}
			}
			else	return -2;
		}
		return -1;
	}

	/**
	*	Show html area for list of addresses
	*
	*	@param	Conf		$conf		Object conf
	*	@param	Translate	$langs		Object langs
	*	@param	DoliDB		$db			Database handler
	*	@param	Societe		$object		Third party object
	*	@param	string		$backtopage	Url to go once address is created
	*	@return	void
	*/
	function infraspackplus_show_addresses($conf, $langs, $db, $object, $backtopage = '')
	{
		global $user;

		dol_include_once('/infraspackplus/class/address.class.php');

		$langs->load('infraspackplus@infraspackplus');

		$addressstatic	= new Address($db);
		$num			= $addressstatic->fetch_lines($object->id);
		$newcardbutton	= '';
		if ($user->rights->societe->creer)
			$newcardbutton	= '	<a class = "butActionNew" href = "'.dol_buildpath('infraspackplus', 1).'/comm/address.php?socid='.$object->id.'&action=create&backtopage='.urlencode($backtopage).'"><span class = "valignmiddle">'.$langs->trans('AddAddress').'</span>
									<span class = "fa fa-plus-circle valignmiddle"></span>
								</a>';
		print load_fiche_titre($langs->trans('AddressesForCompany'), $newcardbutton, '');
		print '		<table class = "noborder" width = "100%">
						<tr class = "liste_titre">
							<td>'.$langs->trans('InfraSPlusParamAdressAlias').'</td>
							<td>'.$langs->trans('CompanyName').'</td>
							<td>'.$langs->trans('Town').'</td>
							<td>'.$langs->trans('Country').'</td>
							<td>'.$langs->trans('Phone').'</td>
							<td>'.$langs->trans('Fax').'</td>
							<td>'.$langs->trans('Email').'</td>
							<td>'.$langs->trans('url').'</td>
							<td>&nbsp;</td>
						</tr>';
		if ($num > 0) {
			foreach ($addressstatic->lines as $address) {
				$addressstatic->id		= $address->id;
				$addressstatic->label	= $address->label;
				$img					= picto_from_langcode($address->country_code);
				print '	<tr class = "oddeven">
							<td>'.$addressstatic->getNomUrl(1).'</td>
							<td>'.$address->name.'</td>
							<td>'.$address->town.'</td>
							<td>'.($img ? $img.' ' : '').$address->country.'</td>
							<td>';
				print dol_print_phone($address->phone, $address->country_code, $address->id, $object->id,'AC_TEL');	// Lien click to dial
				print '		</td>
							<td>';
				print dol_print_phone($address->fax, $address->country_code, $address->id, $object->id, 'AC_FAX');	// Lien click to dial
				print '		</td>
							<td>'.$address->email.'</td>
							<td>'.$address->url.'</td>';
				if ($user->rights->societe->creer) {
					print '	<td align = "right">
								<a href = "'.dol_buildpath('infraspackplus', 1).'/comm/address.php?action=edit&id='.$address->id.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">';
					print img_edit();
					print '		</a>
							</td>';
				}
				print '	</tr>';
			}
		}
		print '		</table>
					<br>';
		return $num;
	}
?>