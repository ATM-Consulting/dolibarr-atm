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
	* 	\file		../infraspackplus/core/lib/infraspackplusAdmin.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by InfraS module
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	/************************************************
	* Define head array for setup pages tabs
	*
	* @return	array			list of head
	************************************************/
	function infraspackplus_admin_prepare_head ()
	{
		global $langs, $conf, $user;

		$h				= 0;
		$head			= array();
		if (! empty($user->admin) || ! empty($user->rights->infraspackplus->paramDolibarr)) {
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/generalpdf.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsGeneralPDF');
			$head[$h][2]	= 'generalpdf';
		}
		if (! empty($user->admin) || ! empty($user->rights->infraspackplus->paramInfraSPlus)) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/infrasplussetup.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsPDF');
			$head[$h][2]	= 'infrasplussetup';
		}
		if (! empty($user->admin) || ! empty($user->rights->infraspackplus->paramImages)) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/images.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsImages');
			$head[$h][2]	= 'images';
		}
		if (! empty($user->admin) || ! empty($user->rights->infraspackplus->paramAdresses)) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/adresses.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsAdresses');
			$head[$h][2]	= 'adresses';
		}
		if (! empty($user->admin) || ! empty($user->rights->infraspackplus->paramExtraFields)) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/extrafields.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsExtraFields');
			$head[$h][2]	= 'extrafields';
		}
		if (! empty($user->admin) || ! empty($user->rights->infraspackplus->paramMentions)) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/mentions.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsMentions');
			$head[$h][2]	= 'mentions';
		}
		if (! empty($user->admin) || ! empty($user->rights->infraspackplus->paramNotes)) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/notes.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsNotes');
			$head[$h][2]	= 'notes';
		}
		$h++;
		$head[$h][0]	= dol_buildpath('/infraspackplus/admin/about.php', 1);
		$head[$h][1]	= $langs->trans('About');
		$head[$h][2]	= 'about';
		return $head;
	}

	/************************************************
	*	Test if the PHP extension 'XML' is loaded
	*
	************************************************/
	function infraspackplus_test_php_ext()
	{
		global $db, $conf, $langs;

		$langs->load('infraspackplus@infraspackplus');

		if (extension_loaded('xml'))	dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		else {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	-1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			setEventMessages('<FONT color = "red">'.$langs->trans('InfraSPlusCautionMess').'</FONT>'.$langs->trans('InfraSXMLextError'), null, 'warnings');
		}
	}

	/************************************************
	* Function called to check module name from local changelog
	* Control of the min version of Dolibarr needed and get versions list
	*
	* @param   string	$appliname	module name
	* @return	array				[0] current version from changelog
	*								[1] Dolibarr min version
	*								[2] flag for error (-1 = KO ; 0 = OK)
	*								[3] array => versions list or errors list
	************************************************/
	function infraspackplus_getLocalVersionMinDoli($appliname)
	{
		global $langs;

		$currentversion	= array();
		$sxe			= infraspackplus_getChangelogFile($appliname);
		if ($sxe === false) {
			$currentversion[0]	= '<font color = red><b>'.$langs->trans('InfraSPlusChangelogXMLError').'</b></font>';
			$currentversion[1]	= $langs->trans('InfraSPlusnoMinDolVersion');
			$currentversion[2]	= -1;
			$currentversion[3]	= $langs->trans('InfraSPlusChangelogXMLError');
			foreach (libxml_get_errors() as $error) {
				$currentversion[3]	.= $error->message;
				dol_syslog('infraspackplus.Lib::infraspackplus_getLocalVersionMinDoli error->message = '.$error->message);
			}
		}
		else {
			$currentversion[0]	= $sxe->Version[count($sxe->Version) - 1]->attributes()->Number;
			$currentversion[1]	= $sxe->Dolibarr->attributes()->minVersion;
			$currentversion[2]	= 0;
			$currentversion[3]	= $sxe->Version;
		}
		return $currentversion;
	}

	/************************************************
	 * Function called to check module name from local changelog
	 * Control of the min version of Dolibarr needed and get versions list
	 *
	 * @param   string			$appliname	module name
	 * @param   string			$from		sufixe name to separate inner changelog from download
	 * @return	string|boolean				changelog file contents or false
	************************************************/
	function infraspackplus_getChangelogFile($appliname, $from = '')
	{
		global $dolibarr_main_data_root;

		$file	= empty($from) ? dol_buildpath($appliname, 0).'/docs/changelog.xml' : $dolibarr_main_data_root.'/'.$appliname.'/changelogdwn.xml';
		if (is_file($file)) {
			libxml_use_internal_errors(true);
			$context	= stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
			$changelog	= @file_get_contents($file, false, $context);
			$sxe		= @simplexml_load_string(rtrim($changelog));
			dol_syslog('infraspackplus.Lib::infraspackplus_getChangelogFile appliname = '.$appliname.' from = '.$from.' context = '.$context.' changelog = '.($changelog ? 'Ok' : 'KO').' sxe = '.($sxe ? 'Ok' : 'KO'));
			return $sxe;
		}
		else return false;
	}

	/************************************************
	 * Function called to check the available version by downloading the last changelog file
	 * Check if the last changelog downloaded is less than 7 days if we do not do anything
	 *
	 * @return		string		current version with information about new ones on tooltip or error message
	************************************************/
	function infraspackplus_dwnChangelog($appliname)
	{
		global $langs, $conf, $dolibarr_main_data_root;

		$path												= $dolibarr_main_data_root.'/'.$appliname;
		if ($conf->global->INFRAS_PHP_EXT_XML == -1)		return -1;
		$sxelasthtmlversion									= infraspackplus_getChangelogFile($appliname, 'dwn');
		if ($sxelasthtmlversion === false)	$lasthtmldate	= '19000101';
		else								$lasthtmldate	= $sxelasthtmlversion->InfraS->attributes()->Downloaded;
		if ($lasthtmldate < date('Ymd', strtotime('-7 day'))) {
			$context						= stream_context_create(array('http' => array('header' => 'Accept: application/xml'), 'ssl' => array('verify_peer' => false, 'verify_peer_name' => false)));
			$newhtmlversion					= @file_get_contents('https://www.infras.fr/jdownloads/Technique/Modules%20Dolibarr/Changelogs/'.$appliname.'/changelog.xml', false, $context);
			if ($newhtmlversion === false)	return -1;	// not connected
			else {
				$newhtmlversion		= preg_replace('#Downloaded=\".+\"#', 'Downloaded="'.date('Ymd').'"', $newhtmlversion);
				file_put_contents($path.'/changelogdwn.xml', $newhtmlversion);
			}
		}
		return 1;
	}

	/************************************************
	*	Tests version du module et de Dolibarr.
	*	Avertissements si chgt trouvés.
	*
	************************************************/
	function infraspackplus_chgtVersions ($admin = 0)
	{
		global $db, $conf, $langs;

		$langs->load('infraspackplus@infraspackplus');

		$infrasnocheckversion	= isset($conf->global->INFRASPACKPLUS_DISABLED_CORE_CHANGE)	? $conf->global->INFRASPACKPLUS_DISABLED_CORE_CHANGE	: 0;
		$infrasdolversion		= isset($conf->global->INFRASPLUS_DOL_VERSION)				? $conf->global->INFRASPLUS_DOL_VERSION					: '';
		$lastversion			= isset($conf->global->INFRASPLUS_MAIN_VERSION)				? $conf->global->INFRASPLUS_MAIN_VERSION				: '';
		if (empty($infrasnocheckversion)) {
			$changelogversion												= infraspackplus_getLocalVersionMinDoli('infraspackplus');
			dol_syslog('infraspackplus.Lib::infraspackplus_chgtVersions infrasdolversion = '.$infrasdolversion.' lastversion = '.$lastversion.' changelogversion = '.$changelogversion[0]);
			if ($infrasdolversion && $infrasdolversion < DOL_VERSION)		setEventMessages('<FONT color = "red">'.$langs->trans('InfraSPlusCautionMess').'</FONT>'.$langs->trans(($admin ? 'InfraSPlusDolChg'		: 'PDFInfraSPlusWarningValidCoreChgt'), $infrasdolversion, DOL_VERSION), null, 'warnings');
			elseif ($lastversion && $lastversion < $changelogversion[0])	setEventMessages('<FONT color = "red">'.$langs->trans('InfraSPlusCautionMess').'</FONT>'.$langs->trans(($admin ? 'InfraSPlusVersionChg'	: 'PDFInfraSPlusWarningValidCoreChgt'), $lastversion, $changelogversion[0]), null, 'warnings');
		}
	}

	/************************************************
	*	Sauvegarde les paramètres du module
	*
	*	@param		string		$appliname	module name
	*	@return		string		1 = Ok or -1 = Ko or or 0 and error message
	************************************************/
	function infraspackplus_bkup_module ($appliname)
	{
		global $db, $conf, $langs, $errormsg, $dolibarr_main_data_root;

		// Set to UTF-8
		if (is_a($db, 'DoliDBMysqli'))	$db->db->set_charset('utf8');
		else {
			$db->query('SET NAMES utf8');
			$db->query('SET CHARACTER SET utf8');
		}
		// Control dir and file
		$path		= $dolibarr_main_data_root.'/'.$appliname.'/sql';
		$bkpfile	= $path.'/update.'.$conf->entity;
		if (! file_exists($path)) {
			if (dol_mkdir($path) < 0) {
				$errormsg	= $langs->transnoentities('ErrorCanNotCreateDir', $path);
				return 0;
			}
		}
		if (file_exists($path)) {
			$currentversion	= infraspackplus_getLocalVersionMinDoli('infraspackplus');
			$handle			= fopen($bkpfile, 'w+');
			if (fwrite($handle, '') === FALSE) {
				$langs->load('errors');
				$errormsg	= $langs->trans('ErrorFailedToWriteInDir');
				return -1;
			}
			// Print headers and global mysql config vars
			$sqlhead	= '-- '.$db::LABEL.' dump via php with Dolibarr '.DOL_VERSION.'
--
-- Host: '.$db->db->host_info.'    Database: '.$db->database_name.'
-- ------------------------------------------------------
-- Server version			'.$db->db->server_info.'
-- Dolibarr version			'.DOL_VERSION.'
-- InfraSPackPlus version	'.$currentversion[0].'

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = \'NO_AUTO_VALUE_ON_ZERO\';
';
			fwrite($handle, $sqlhead);
			$sql_model		= 'SELECT nom, entity, type, libelle';
			$sql_model		.= ' FROM '.MAIN_DB_PREFIX.'document_model';
			$sql_model		.= ' WHERE nom LIKE "INFRASPLUS\_%" AND entity = "'.$conf->entity.'"';
			$sql_model		.= ' ORDER BY nom';
			$listeCols		= array ('nom', 'entity', 'type', 'libelle');
			$duplicate		= array ('3', 'libelle', 'nom');
			fwrite($handle, infraspackplus_bkup_table ('document_model', $sql_model, $listeCols, $duplicate, 0, ''));
			$sql_const		= 'SELECT name, entity, value, type, visible, note';
			$sql_const		.= ' FROM '.MAIN_DB_PREFIX.'const';
			$sql_const		.= ' WHERE ((name LIKE "INFRASPLUS\_%" AND name NOT LIKE "INFRASPLUS\_PDF\_VALID\_CORE\_CHGT") OR (name LIKE "%\_ADDON\_PDF"  AND value LIKE "InfraSPlus\_%") OR name LIKE "%\_FREE\_TEXT%" OR name LIKE "%\_PUBLIC\_NOTE%")';
			$sql_const		.= ' AND entity = "'.$conf->entity.'"';
			$sql_const		.= ' ORDER BY name';
			$autoupdate		= isset($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)	? $conf->global->MAIN_DISABLE_PDF_AUTOUPDATE : 0;
			$onDuplicate	= $db->type == 'pgsql' ? ' ON CONFLICT (name) DO UPDATE SET ' : ' ON DUPLICATE KEY UPDATE ';
			$add			= 'INSERT INTO '.MAIN_DB_PREFIX.'const (name, entity, value, type, visible, note) VALUES (\'MAIN_DISABLE_PDF_AUTOUPDATE\', \'__ENTITY__\', \''.$autoupdate.'\', \'chaine\', \'0\', \'InfraSPackPlus module\')'.$onDuplicate.'value = \''.$autoupdate.'\';
';
			$listeCols		= array ('name', 'entity', 'value', 'type', 'visible', 'note');
			$duplicate		= array ('2', 'value', 'name');
			fwrite($handle, infraspackplus_bkup_table ('const', $sql_const, $listeCols, $duplicate, 0, $add));
			$sql_dict		= 'SELECT code, entity, pos, libelle, active';
			$sql_dict		.= ' FROM '.MAIN_DB_PREFIX.'c_infraspackplus_mention';
			$sql_dict		.= ' WHERE entity = "'.$conf->entity.'"';
			$sql_dict		.= ' ORDER BY pos';
			$listeCols		= array ('code', 'entity', 'pos', 'libelle', 'active');
			$duplicate		= array ('3', 'libelle', 'code');
			fwrite($handle, infraspackplus_bkup_table ('c_infraspackplus_mention', $sql_dict, $listeCols, $duplicate, 1, ''));
			$sql_dict		= 'SELECT code, entity, pos, libelle, active';
			$sql_dict		.= ' FROM '.MAIN_DB_PREFIX.'c_infraspackplus_note';
			$sql_dict		.= ' WHERE entity = "'.$conf->entity.'"';
			$sql_dict		.= ' ORDER BY pos';
			$listeCols		= array ('code', 'entity', 'pos', 'libelle', 'active');
			$duplicate		= array ('3', 'libelle', 'code');
			fwrite($handle, infraspackplus_bkup_table ('c_infraspackplus_note', $sql_dict, $listeCols, $duplicate, 1, ''));
			// Enabling back the keys/index checking
			$sqlfooter		= '
SET FOREIGN_KEY_CHECKS = 1;

-- Dump completed on '.date('Y-m-d G-i-s').'
';
			fwrite($handle, $sqlfooter);
			fclose($handle);
			if (file_exists($bkpfile))	$moved	= dol_copy($bkpfile, DOL_DATA_ROOT.($conf->entity != 1 ? '/'.$conf->entity : '').'/admin/'.$appliname.'_update'.date('Y-m-d-G-i-s').'.'.$conf->entity);
			return 1;
		}
	}

	/************************************************
	*	Recherche d'un fichier contenant un code langue dans son nom à partir d'une liste
	*
	*	@param	string	$table		table name to backup
	*	@param	string	$sql		sql query to prepare data  for backup
	*	@param	array	$listeCols	list of columns to backup on the table
	*	@param	array	$duplicate	values for 'ON DUPLICATE KEY UPDATE'
	*									[0] = column to update
	*									[1] = column name to update
	*									[2] = key value for conflict control (only postgreSQL)
	*	@param	boolean	$truncate	truncate the table before restore
	*	@param	string	$add		sql data to add on the beginning of the query
	*	@return	string				sql query to restore the datas
	************************************************/
	function infraspackplus_bkup_table ($table, $sql, $listeCols, $duplicate = array (), $truncate = 0, $add = '')
	{
		global $db, $conf, $langs, $errormsg;

		$sqlnewtable	= '';
		$result_sql		= $sql ? $db->query($sql) : '';
		dol_syslog('infraspackplus.Lib::infraspackplus_bkup_table sql = '.$sql);
		if ($result_sql) {
			$truncate		= $truncate ? 'TRUNCATE TABLE '.MAIN_DB_PREFIX.$table.';
' : '';
			$sqlnewtable	= '
-- Dumping data for table '.MAIN_DB_PREFIX.$table.'
'.$truncate.$add;
			while($row	= $db->fetch_row($result_sql)) {
				// For each row of data we print a line of INSERT
				$colsInsert						= '';
				foreach ($listeCols as $col)	$colsInsert	.= $col.', ';
				$sqlnewtable					.= 'INSERT INTO '.MAIN_DB_PREFIX.$table.' ('.substr($colsInsert, 0, -2).') VALUES (';
				$columns						= count($row);
				$duplicateValue					= '';
				for($j = 0; $j < $columns; $j++) {
					// Processing each columns of the row to ensure that we correctly save the value (eg: add quotes for string - in fact we add quotes for everything, it's easier)
					if ($row[$j] == null && !is_string($row[$j]))	$row[$j]	= 'NULL';	// IMPORTANT: if the field is NULL we set it NULL
					elseif(is_string($row[$j]) && $row[$j] == '')	$row[$j]	= '\'\'';	// if it's an empty string, we set it as an empty string
					else {																	// else for all other cases we escape the value and put quotes around
						$row[$j]	= addslashes($row[$j]);
						$row[$j]	= preg_replace('#\n#', '\\n', $row[$j]);
						$row[$j]	= '\''.$row[$j].'\'';
					}
					if ($j == 1)	$row[$j]	= '\'__ENTITY__\'';
					if (!empty($duplicate)) {
						$onDuplicate	= $db->type == 'pgsql' ? ' ON CONFLICT ('.$duplicate[2].') DO UPDATE SET ' : ' ON DUPLICATE KEY UPDATE ';
						$duplicateValue .= $j == $duplicate[0] ? $onDuplicate.$duplicate[1].' = '.$row[$j] : '';
					}
				}
				$sqlnewtable	.= implode(', ', $row).')'.$duplicateValue.';
';
			}
		}
		return $sqlnewtable;
	}

	/************************************************
	*	Restaure les paramètres du module
	*
	*	@param		string		$appliname	module name
	*	@return		string		1 = Ok or -1 = Ko
	************************************************/
	function infraspackplus_restore_module ($appliname)
	{
		global $conf, $db, $dolibarr_main_data_root;

		$pathsql	= $dolibarr_main_data_root.'/'.$appliname.'/sql';
		dol_syslog('infraspackplus.Lib::infraspackplus_restore_module $pathsql = '.$pathsql);
		$handle		= @opendir($pathsql);
		if (is_resource($handle)) {
			$filesql	= $pathsql.'/'.'update.'.$conf->entity;
			if (is_file($filesql)) {
				$templatenames	= array(array ('commande', 'C'),	array ('commande_bc', 'CBC'),	array ('commande_bl', 'CBL'),	array ('orderfab', 'OF'),	array ('proforma', 'CP'),			array ('contract', 'CT'),
										array ('etiquette', 'ET'),	array ('EX', 'BL'),				array ('expedition', 'BL'),		array ('invoice', 'F'),		array ('invoice_livraison', 'FL'),	array ('fichinter', 'FI'),
										array ('product', 'P'),		array ('BC_product', 'PBC'),	array ('devis', 'D'),			array ('devis-st', 'DST'),	array ('supplier_invoice', 'FF'),	array ('supplier_order', 'CF'),
										array ('supplier_proposal', 'DF')
										);	// Prepare test to migrate old files
				foreach ($templatenames as $templatename) {	// migrate datas from old update files
					$content		= file_get_contents($filesql);
					$content_chunks	= explode('InfraSPlus_'.$templatename[0], $content);
					$content		= implode('InfraSPlus_'.$templatename[1], $content_chunks);
					file_put_contents($filesql, $content);
				}
			}
			$moved	= dol_copy($filesql, $filesql.'.sql');
			if (is_file($filesql.'.sql')) {
				$result	= run_sql($filesql.'.sql', (empty($conf->global->MAIN_DISPLAY_SQL_INSTALL_LOG) ? 1 : 0), $conf->entity, 1);
				if ($result > 0) {
					$content	= file_get_contents($filesql.'.sql');
					if (preg_match_all('/INFRASPLUS_PDF_FREE_LIVR_EXF\', \'__ENTITY__\', \'.*/', $content, $reg)) {
						$tmp				= preg_quote(preg_replace('/\'/', '', $reg[0][0]), '/');
						$tmp				= preg_replace('/, chaine, 0, InfraSPackPlus module.*/', '', preg_replace('/INFRASPLUS_PDF_FREE_LIVR_EXF, __ENTITY__, /', '', $tmp));
						if (!empty($tmp))	infraspackplus_search_extf (0, $tmp);
					}
				}
			}
			$delete				= dol_delete_file($filesql.'.sql');
			dol_syslog('infraspackplus.Lib::infraspackplus_restore_module appliname = '.$appliname.' filesql = '.$filesql.' moved = '.$moved.' result = '.$result.' delete = '.$delete);
			if ($result > 0)	return 1;
		}
		return -1;
	}

	/************************************************
	*	Converts shorthand memory notation value to bytes
	*	From http://php.net/manual/en/function.ini-get.php
	*
	*	@param  string	$val	Memory size shorthand notation
	*	@return	string			value .
	************************************************/
	function infraspackplus_return_bytes($val)
	{
		$val	= trim($val);
		$last	= strtolower($val[strlen($val)-1]);
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	/************************************************
	*	Print HTML backup / restore section
	*
	*	@return		void
	************************************************/
	function infraspackplus_print_backup_restore()
	{
		global $conf, $langs;

		print '	<table width = "100%" style = "border-spacing: 0px;">';
		$metas	= array('*', '90px', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		print '		<tr>
						<td colspan = "2" align = "center" style = "font-size: 14px;">
							<a href = "'.DOL_URL_ROOT.'/document.php?modulepart=infraspackplus&file=sql/update.'.$conf->entity.'">'.$langs->trans('InfraSPlusParamAction1').' <b><FONT color = "#382453">'.$langs->trans('modcomnamePackPlus').'</FONT></b> <FONT size = "2">'.$langs->trans('InfraSPlusParamAction2').'</FONT></a>
						</td>
						<td align = "center"><button class = "butActionBackup" type = "submit" value = "bkupParams" name = "action">'.$langs->trans('InfraSPlusParamBkup').'</button></td>
						<td align = "center"><button class = "butActionBackup" type = "submit" value = "restoreParams" name = "action">'.$langs->trans('InfraSPlusParamRestore').'</button></td>
					</tr>';
		print '		<tr><td colspan = "4" align = "center" style = "padding: 0;"><hr></td></tr>';
		print '		<tr><td colspan = "4" style = "line-height: 1px;">&nbsp;</td></tr>';
		print '	</table>';
	}

	/************************************************
	*	Print HTML colgroup for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	************************************************/
	function infraspackplus_print_colgroup($metas = array())
	{
		print '	<tr>';
		foreach ($metas as $values)	print '<td class = "infrasFinal" style ="padding: 0px; height: 1px;'.($values == '*' ? '' : ' max-width: '.$values.'; min-width: '.$values.';').'">&nbsp;</td>';
		print '	</tr>';
	}

	/************************************************
	*	Print HTML title for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	************************************************/
	function infraspackplus_print_liste_titre($metas = array())
	{
		global $langs;

		print '	<tr class = "liste_titre">';
		for ($i = 1 ; $i < count($metas) ; $i++)
			print '	<td colspan = "'.$metas[0][$i - 1].'" class = "center">'.$langs->trans($metas[$i]).'</td>';
		print '	</tr>';
	}

	/************************************************
	*	Print HTML action button for admin page
	*
	*	@param		string		$action		action name (with prefix => 'update_')
	*	@param		string		$desc		Description of action (writes on the first line)
	*	@param		int			$rowspan	number of rows
	*	@return		void
	************************************************/
	function infraspackplus_print_btn_action($action, $desc = '', $rowspan = 0, $cs1 = 3)
	{
		global $langs;

		print '	<tr>
					<td colspan = "'.$cs1.'" class = "center">'.$desc.'</td>
					<td rowspan = "'.$rowspan.'" class = "center valigntop"><button class = "button" style = "width: 110px;" type = "submit" value = "update_'.$action.'" name = "action">'.$langs->trans('Modify').'</button></td>
				</tr>';
	}

	/************************************************
	*	Print HTML HR line
	*
	*	@param		string		$action		action name (with prefix => 'update_')
	*	@param		string		$desc		Description of action (writes on the first line)
	*	@param		int			$rowspan	number of rows
	*	@return		void
	************************************************/
	function infraspackplus_print_hr($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'"><hr class = "infrasHR"></td></tr>';
	}

	/************************************************
	*	Print HTML final line
	*
	*	@param		string		$action		action name (with prefix => 'update_')
	*	@param		string		$desc		Description of action (writes on the first line)
	*	@param		int			$rowspan	number of rows
	*	@return		void
	************************************************/
	function infraspackplus_print_final($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'" class = "infrasFinal">&nbsp;</td></tr>';
	}

	/************************************************
	*	Print HTML action button for admin page
	*
	*	@param		string		$confkey	action name (with prefix => 'update_')
	*	@param		string		$tag		input type (on/off button, input, textarea, color select, select, select_types_paiements, selectTypeContact)
	*	@param		string		$desc		Description of action
	*	@param		string		$help		Help description => active tooltip
	*	@param		array		$metas		list of HTML parameters and values (example : 'type'=>'text' and/or 'class'=>'flat center', etc...)
	*	@param		int			$cs1		first colspan
	*	@param		int			$cs2		second colspan => we add it with $cs1 in case off textarea
	*	@param		string		$end		if input element string to be added after or empty td to finish the line
	*	@param		int			$num		Add a numbering column first with this number
	*	@return		int						line number for next option
	************************************************/
	function infraspackplus_print_input($confkey, $tag = 'on_off', $desc = '', $help = '', $metas = array(), $cs1 = 2, $cs2 = 1, $end = '', $num = 0)
	{
		global $langs, $conf, $db;

		$form			= new Form($db);
		$formother		= new FormOther($db);
		$formcompany	= new FormCompany($db);
		$formactions	= new FormActions($db);
		print '	<tr class = "oddeven">';
		if (!empty($num)) {
			print '	<td class = "center" style = "color: #382453; font-weight: bold;">'.$num.'</td>';
			$num++;
		}
		if ($tag != 'textarea') {
			print '	<td colspan = "'.$cs1.'">';
			if (!empty($help))	print $form->textwithtooltip(($desc ? $desc : $langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));
			else				print $desc ? $desc : $langs->trans($confkey);
			print '	</td>
					<td colspan = "'.$cs2.'" class = "center">';
		}
		else {
			print '	<td colspan = "'.($cs1 + $cs2).'" class = "center">';
			if (!empty($desc))	print $desc.'<br/>';
		}
		if ($tag == 'on_off') {
			print '		<a href = "'.$_SERVER['PHP_SELF'].'?action=set_'.$confkey.'&token='.newToken().'&value='.(!empty($conf->global->$confkey) ? '0' : '1').'">';
			print ajax_constantonoff($confkey);
			print '		</a>';
		}
		if ($tag == 'on_off2')
			print '		<a href = "'.$_SERVER['PHP_SELF'].'?action=set_'.$confkey.'&token='.newToken().'&value='.(strpos($conf->global->$confkey, $metas) !== false ? '0' : '1').'">
							'.(strpos($conf->global->$confkey, $metas) !== false ? img_picto($langs->trans('Activated'), 'switch_on') : img_picto($langs->trans('Disabled'), 'switch_off')).'
						</a>';
		elseif ($tag == 'input') {
			$defaultMetas						= array('type' => 'text', 'class' => 'flat quatrevingtpercent', 'style' => 'padding: 0; font-size: inherit;', 'name' => $confkey, 'id' => $confkey, 'value' => $conf->global->$confkey);
			$metas								= array_merge ($defaultMetas, $metas);
			$metascompil						= '';
			foreach ($metas as $key => $value)	$metascompil	.= ' '.$key.($key == 'enabled' || $key == 'disabled' ? '' : ' = "'.$value.'"');
			print '	<'.$tag.' '.$metascompil.'>'.(!preg_match('/<td(.*)/', $end, $reg) ? $end : '');
		}
		elseif ($tag == 'textarea') {
			if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))	print '<textarea name = "'.$confkey.'" class = "flat" cols = "120">'.$conf->global->$confkey.'</textarea>';
			else {
				$doleditor	= new DolEditor($confkey, $conf->global->$confkey, '', 80, 'dolibarr_notes');
				print $doleditor->Create();
			}
		}
		elseif ($tag == 'color')					print $formother->selectColor($metas, $confkey);
		elseif ($tag == 'select')					print $metas;
		elseif ($tag == 'select_produits')			$form->select_produits($conf->global->$confkey, $confkey, $metas[0], $metas[1], $metas[2], $metas[3], $metas[4], $metas[5], $metas[6], $metas[7], $metas[8], $metas[9], $metas[10], $metas[11], $metas[12], $metas[13], $metas[14], $metas[15]);
		elseif ($tag == 'select_types_paiements')	$form->select_types_paiements($conf->global->$confkey, $confkey, $metas[0], $metas[1], $metas[2], $metas[3], $metas[4]);
		elseif ($tag == 'selectTypeContact')		print $formcompany->selectTypeContact($metas[0], $metas[1], $confkey, $metas[2], $metas[3], $metas[4], $metas[5]);
		elseif ($tag == 'select_type_actions')		$formactions->select_type_actions($conf->global->$confkey, $confkey, $metas[0], $metas[1], $metas[2]);
		print '		</td>';
		if (preg_match('/<td(.*)/', $end, $reg))	print $end;
		print '	</tr>';
		return $num;
	}

	/************************************************
	*	Print HTML action button for admin page
	*
	*	@param		string		$type		input type (empty or tests)
	*	@param		string		$desc		Description of action
	*	@param		array		$metas		list of columns with input keys and values to test
	*	@param		int			$cs1		first colspan
	*	@param		int			$w			width for input columns
	*	@param		string		$end		element string to be added on the last td of the line
	*	@param		int			$num		Add a numbering column first with this number
	*	@return		int						line number for next option
	************************************************/
	function infraspackplus_print_line_inputs($type = '', $desc = '', $metas = array(), $cs1 = 2, $w = 0, $end = '', $num = 0)
	{
		global $conf;

		print '	<tr class = "oddeven">';
		if (!empty($num)) {
			print '	<td class = "center" style = "color: #382453; font-weight: bold;">'.$num.'</td>';
			$num++;
		}
		print '		<td colspan = "'.$cs1.'">
						<table width = "100%">
							<tr>
								<td rowspan = "2" style = "border: none;">'.$desc.'</td>';
		foreach ($metas[0] as $confkey => $value)
			print '				<td width = "'.$w.'" style = "text-align: center; border: none;">'.($type == 'tests' ? ($conf->global->$confkey ? $value : '&nbsp;') : $value).'</td>';
		print '				</tr>
							<tr>';
		foreach ($metas[1] as $confkey => $value) {
			print '				<td style = "text-align: center; border: none;">';
			if ($type == 'tests' && empty($conf->global->$value))	print '&nbsp;';
			else {
				print '				<a href = "'.$_SERVER['PHP_SELF'].'?action=set_'.$confkey.'&token='.newToken().'&value='.(!empty($conf->global->$confkey) ? '0' : '1').'">';
				print ajax_constantonoff($confkey);
				print '				</a>'.($type == 'tests' ? '' : $value);
			}
			print '				</td>';
		}
		print '				</tr>
						</table>
					</td>';
		empty($end) ? print '' : print '<td align="center">'.$end.'</td>';
		print '	</tr>';
		return $num;
	}

	/************************************************
	 * Function called to get downloaded changelog and compare with the local one
	 * Presentation of results on a HTML table
	 *
	 * @param   string	$appliname		module name
	 * @param   string	$resVersion		flag for error (-1 = KO ; 0 = OK)
	 * @param   string	$tblversions	array => versions list or errors list
	 * @param	int		$dwn			flag to show download button (0 = hide it ; 1 = show it)
	 * @return	string					HTML presentation
	************************************************/
	function infraspackplus_getChangeLog($appliname, $resVersion, $tblversions, $dwn = 0)
	{
		global $langs;

		$headerPath				= dol_buildpath('/'.$appliname.'/img/InfraSheader.png', 1);
		$logoPath				= dol_buildpath('/'.$appliname.'/img/InfraS.gif', 1);
		$gplv3Path				= dol_buildpath('/'.$appliname.'/img/gplv3.png', 1);
		$listUpD				= dol_buildpath('/'.$appliname.'/img/list_updates.png', 1);
		$urlInfraS				= 'https://www.infras.fr';
		$urldownl				= '/index.php?option=com_content&view=featured&Itemid=161';
		$urldocs				= '/index.php?option=com_jdownloads&view=category&catid=11&Itemid=116';
		$urlDoli				= 'http://www.dolistore.com/search.php?search_query=InfraS';
		$style					= 'color: white; font-size: 16px; font-weight: bold; width: 30%;';
		$InputCarac				= 'class = "butActionChangelog" name = "readmore" type = "button"';
		$ret					= array();
		$ret					= '	<FONT color = "#382453" size = "3">'.$langs->trans('InfraSParamPresent').'</FONT>
									<br/>
									<table  width = "100%" cellspacing = "10" style = "background: url('.$headerPath.'); background-size: cover;">
										<tr>
											<td rowspan = "3" align = "left" valign = "bottom" style="'.$style.'">
												<a href = "'.$urlInfraS.'" target = "_blank"><img border = "0" width = "220" src = "'.$logoPath.'"></a>
												<br/>&nbsp;&nbsp;'.$langs->trans('InfraSParamSlogan').'
											</td>
											<td align = "center" valign = "middle" width = "30%">
												<a href = "'.$urlInfraS.$urldownl.'" target = "_blank"><input '.$InputCarac.' value = "'.$langs->trans('InfraSParamLienModules').'" /></a>
											</td>
											<td rowspan = "3" align = "center" valign = "middle" width = "30%">
												<a href = "'.$urlDoli.'" target = "_blank"><img border = "0" width = "180" src = "'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a>
												<br/>'.$langs->trans('InfraSParamMoreModulesLink').'
											</td>
										</tr>
										<tr>
											<td align = "center" valign = "middle">
												<a href = "'.$urlInfraS.$urldocs.'" target = "_blank"><input '.$InputCarac.' value = "'.$langs->trans('InfraSParamLienDocs').'" /></a>
											</td>
										</tr>
										<tr>
											<td align = "center" valign = "middle">
												<img border="0" width="120" src="'.$gplv3Path.'"/>
												<br/>'.$langs->trans('InfraSParamLicense').'
											</td>
										</tr>
									</table>';
		$ret					.= load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSParamHistoryUpdates').'</FONT>', '', $listUpD, 1);
		$sxe					= infraspackplus_getChangelogFile($appliname);
		$sxelast				= infraspackplus_getChangelogFile($appliname, 'dwn');
		if ($sxelast === false)	$tblversionslast	= array();
		else					$tblversionslast	= $sxelast->Version;
		if ($resVersion == -1) {
			foreach ($tblversions as $error)	$ret	.= $error->message;
			return $ret;
		}
		if ($conf->global->INFRAS_SKIP_CHECKVERSION == 1)	$dwnbutton	= $dwn ? $langs->trans('InfraSParamSkipCheck') : '';
		else												$dwnbutton	= $dwn ? '<button class = "button" style = "width: 190px; padding: 3px 0px;" type = "submit" value = "dwnChangelog" name = "action" title = "'.$langs->trans('InfraSParamCheckNewVersionTitle').'">'.$langs->trans('InfraSParamCheckNewVersion').'</button>' : '';
		$ret	.= '<table class = "noborder" >
						<tr class = "liste_titre">
							<th align = center width = 100px>'.$langs->trans('InfraSParamNumberVersion').'</th>
							<th align = center width = 100px>'.$langs->trans('InfraSParamMonthVersion').'</th>
							<th align = left >'.$langs->trans('InfraSParamChangesVersion').'</th>
							<th align = center width = "200px" >'.$dwnbutton.'</th>
						</tr>';
		if ($sxe !== false && count($tblversionslast) > count($tblversions)) {	// il y a du nouveau
			for ($i = count($tblversionslast)-1; $i >= 0; $i--) {
				$color					= '';
				$sxePath				= $sxe->xpath('//Version[@Number="'.$tblversionslast[$i]->attributes()->Number.'"]');
				dol_syslog('infraspackplus.Lib::infraspackplus_getChangeLog sxePath = '.$sxePath);
				if (empty($sxePath))	$color='bgcolor = orange';
				$lineversion			= $tblversionslast[$i]->change;
				$ret	.= '<tr class = "oddeven">
								<td align = center '.$color.' valign = top>'.$tblversionslast[$i]->attributes()->Number.'</td>
								<td align = center '.$color.' valign = top>'.$tblversionslast[$i]->attributes()->MonthVersion.'</td>
								<td align = left colspan = "2" '.$color.' valign = top style = "padding-top: 0; padding-bottom: 0;">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix')		$stylecolor	= ' color: red;';
					else if ($changeline->attributes()->type == 'add')	$stylecolor	= ' color: green;';
					else if ($changeline->attributes()->type == 'chg')	$stylecolor	= ' color: blue;';
					else												$stylecolor	= ' color: black;';
					$ret	.= '	<table>
										<tr>
											<td width = 50px style = "border: none; padding-top: 0; padding-bottom: 0;'.$stylecolor.'">'.$changeline->attributes()->type.'</td>
											<td style = "border: none; padding-top: 0; padding-bottom: 0;'.$stylecolor.'">'.$changeline.'</td>
										</tr>
									</table>';
				}
				$ret	.= '	</td>
							</tr>';
			}
		}
		elseif ($sxelast !== false && count($tblversionslast) < count($tblversions) && count($tblversionslast) > 0) {
			for ($i = count($tblversions)-1; $i >= 0; $i--) {
				$color						= '';
				$sxelastPath				= $sxelast->xpath('//Version[@Number="'.$tblversions[$i]->attributes()->Number.'"]');
				if (empty($sxelastPath))	$color	= 'bgcolor = lightgreen';
				$lineversion				= $tblversions[$i]->change;
				$ret	.= '<tr class = "oddeven">
								<td align = center '.$color.' valign = top>'.$tblversions[$i]->attributes()->Number.'</td>
								<td align = center '.$color.' valign = top>'.$tblversions[$i]->attributes()->MonthVersion.'</td>
								<td align = left colspan = "2" '.$color.' valign = top style = "padding-top: 0; padding-bottom: 0;">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix')		$stylecolor	= ' color: red;';
					else if ($changeline->attributes()->type == 'add')	$stylecolor	= ' color: green;';
					else if ($changeline->attributes()->type == 'chg')	$stylecolor	= ' color: blue;';
					else												$stylecolor	= ' color: black;';
					$ret	.= '	<table>
										<tr>
											<td width = 50px style = "border: none; padding-top: 0; padding-bottom: 0;'.$stylecolor.'">'.$changeline->attributes()->type.'</td>
											<td style = "border: none; padding-top: 0; padding-bottom: 0;'.$stylecolor.'">'.$changeline.'</td>
										</tr>
									</table>';
				}
				$ret	.= '	</td>
							</tr>';
			}
		}
		else {	//on est à jour des versions ou pas de connection internet
			for ($i = count($tblversions)-1; $i >= 0; $i--) {
				$lineversion	= $tblversions[$i]->change;
				$color			= '';
				$ret	.= '<tr class = "oddeven">
								<td align = center valign = top>'.$tblversions[$i]->attributes()->Number.'</td>
								<td align = center valign = top>'.$tblversions[$i]->attributes()->MonthVersion.'</td>
								<td align = left colspan = "2" '.$color.' valign = top style = "padding-top: 0; padding-bottom: 0;">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix')		$stylecolor	= ' color: red;';
					else if ($changeline->attributes()->type == 'add')	$stylecolor	= ' color: green;';
					else if ($changeline->attributes()->type == 'chg')	$stylecolor	= ' color: blue;';
					else												$stylecolor	= ' color: black;';
					$ret	.= '	<table>
										<tr>
											<td width = 50px style = "border: none; padding-top: 0; padding-bottom: 0;'.$stylecolor.'">'.$changeline->attributes()->type.'</td>
											<td style = "border: none; padding-top: 0; padding-bottom: 0;'.$stylecolor.'">'.$changeline.'</td>
										</tr>
									</table>';
				}
				$ret	.= '	</td>
							</tr>';
			}
		}
		$ret	.= '	</table>';
		return $ret;
	}

	/************************************************
	 * Function called to get support information
	 * Presentation of results on a HTML table
	 *
	 * @param   string	$currentversion	current version from changelog
	 * @return	string					HTML presentation
	************************************************/
	function infraspackplus_getSupportInformation($currentversion)
	{
		global $db, $langs;

		$ret	.= '<table class="noborder" >
						<tr class="liste_titre">
							<th align = center width=200px>'.$langs->trans('InfraSSupportInformation').'</th>
							<th align = center>'.$langs->trans('Value').'</th>
						</tr>
						<tr class="oddeven">
							<td width = 20opx style = "border: none; padding-top: 0; padding-bottom: 0;">'.$langs->trans('DolibarrVersion').'</td>
							<td style = "border: none; padding-top: 0; padding-bottom: 0;">'.DOL_VERSION.'</td>
						</tr>
						<tr class="oddeven">
							<td width = 20opx style = "border: none; padding-top: 0; padding-bottom: 0;">'.$langs->trans('ModuleVersion').'</td>
							<td style = "border: none; padding-top: 0; padding-bottom: 0;">'.$currentversion.'</td>
						</tr>
						<tr class="oddeven">
							<td width = 20opx style = "border: none; padding-top: 0; padding-bottom: 0;">'.$langs->trans('PHPVersion').'</td>
							<td style = "border: none; padding-top: 0; padding-bottom: 0;">'.version_php().'</td>
						</tr>
						<tr class="oddeven">
							<td width = 20opx style = "border: none; padding-top: 0; padding-bottom: 0;">'.$langs->trans('DatabaseVersion').'</td>
							<td style = "border: none; padding-top: 0; padding-bottom: 0;">'.$db::LABEL." ".$db->getVersion().'</td>
						</tr>
						<tr class="oddeven">
							<td width = 20opx style = "border: none; padding-top: 0; padding-bottom: 0;">'.$langs->trans('WebServerVersion').'</td>
							<td style = "border: none; padding-top: 0; padding-bottom: 0;">'.$_SERVER['SERVER_SOFTWARE'].'</td>
						</tr>
						<tr><td colspan = "3" style = "line-height: 1px;">&nbsp;</td></tr>
					</table>
					<br/>';
		return $ret;
	}
?>