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
	* 	\file		../infraspackplus/core/modules/modinfraspackplus.class.php
	* 	\ingroup	InfraS
	* 	\brief		Description and activation file for module InfraSPackPlus
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Description and activation class *************
	class modinfraspackplus extends DolibarrModules
	{
		/************************************************
		 * Constructor. Define names, constants, directories, boxes, permissions
		 * @param DoliDB $db Database handler
		************************************************/
		function __construct($db)
		{
			global $langs, $conf;

			$langs->load('infraspackplus@infraspackplus');

			infraspackplus_test_php_ext();

			$this->db				= $db;
			$this->numero			= 550000;											// Unique Id for module
			$this->name				= preg_replace('/^mod/i', '', get_class($this));	// Module label (no space allowed)
			$this->editor_name		= '<b>InfraS - sylvain Legrand</b>';
			$this->editor_web		= 'https://www.infras.fr/';
			$this->rights_class		= $this->name;										// Key text used to identify module (for permissions, menus, etc...)
			$family					= !empty($conf->global->EASYA_VERSION) ? 'easya' : 'Modules '.$langs->trans('basenamePackPlus');
			$this->family			= $family;											// used to group modules in module setup page
			$this->familyinfo		= array($family => array('position' => '001', 'label' => $langs->trans($family)));
			$this->description		= $langs->trans('Module550000Desc');				// Module description
			$this->version			= $this->getLocalVersion();							// Version : 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
			$this->const_name		= 'MAIN_MODULE_'.strtoupper($this->name);			// llx_const table to save module status enabled/disabled
			$this->special			= 0;												// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->picto			= $this->name.'@'.$this->name;						// Name of image file used for this module. If in theme => 'pictovalue' ; if in module => 'pictovalue@module' under name object_pictovalue.png
			$this->module_parts		= array('models'	=> 1,							// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
											'hooks'		=> array('login',
																 'formfile',
																 'pdfgeneration',
																 'thirdpartycard',
																 ),
											'triggers'	=> 1,
											'css'		=> '/'.$this->name.'/css/'.$this->name.'.css'
											);
			$this->dirs				= array('/mycompany/logos/thumbs',
											'/infraspackplus/sql');						// Data directories to create when module is enabled. Example: this->dirs = array("/mymodule/temp");
			$this->config_page_url	= array('infrasplussetup.php@'.$this->name);		// List of php page, stored into mymodule/admin directory, to use to setup module.
			// Dependencies
			$this->hidden			= false;											// A condition to hide module
			$this->depends			= array();											// List of modules id that must be enabled if this module is enabled
			$this->requiredby		= array();											// List of modules id to disable if this one is disabled
			$this->conflictwith		= array();											// List of modules id this module is in conflict with
			$this->phpmin			= array(7, 0);										// Minimum version of PHP required by module
			$this->langfiles		= array($this->name.'@'.$this->name);
			$this->const			= array();											// List of particular constants to add when module is enabled
			$this->tabs				= array();
			if (! isset($conf->infraspackplus->enabled)) {
				$conf->infraspackplus			= new stdClass();
				$conf->infraspackplus->enabled	= 0;
			}
			$this->dictionaries		= array('langs'				=> $this->name.'@'.$this->name,
											'tabname'			=> array(	MAIN_DB_PREFIX.'c_infraspackplus_mention',
																			MAIN_DB_PREFIX.'c_infraspackplus_note'
																		),
											'tablib'			=> array(	'InfraSPlusDictMentions',
																			'InfraSPlusDictNotes'
																		),
											'tabsql'			=> array(	'SELECT rowid, code, entity, pos, libelle, active FROM '.MAIN_DB_PREFIX.'c_infraspackplus_mention WHERE entity = '.getEntity(MAIN_DB_PREFIX.'c_infraspackplus_mention'),
																			'SELECT rowid, code, entity, pos, libelle, active FROM '.MAIN_DB_PREFIX.'c_infraspackplus_note WHERE entity = '.getEntity(MAIN_DB_PREFIX.'c_infraspackplus_note')
																		),
											'tabsqlsort'		=> array(	'pos ASC',
																			'pos ASC'
																		),
											'tabfield'			=> array(	'code,pos,libelle,entity',
																			'code,pos,libelle,entity'
																		),
											'tabfieldvalue'		=> array(	'code,pos,libelle',
																			'code,pos,libelle'
																		),
											'tabfieldinsert'	=> array(	'code,pos,libelle,entity',
																			'code,pos,libelle,entity'
																		),
											'tabrowid'			=> array(	'rowid',
																			'rowid'
																		),
											'tabcond'			=> array(	$conf->infraspackplus->enabled,
																			$conf->infraspackplus->enabled
																		),
											'tabhelp'			=> array(	array('code'	=> $langs->trans('InfraSPlusDictEnterCodeMention1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').' '.$langs->trans('InfraSPlusDictEnterCodeMention2').'</FONT>',
																				  'pos'		=> $langs->trans('PositionIntoComboList')),
																			array('code'	=> $langs->trans('EnterAnyCode'),
																				  'pos'		=> $langs->trans('PositionIntoComboList'))
																		)
											);	// Dictionaries
			$this->boxes			= array();	// List of boxes
			$this->cronjobs			= array();	// List of cron jobs entries to add
			$this->rights			= array();	// Permission array used by this module
			$r						= 0;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermMenu');		// libelle de la permission
			$this->rights[$r][3]	= 1;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramMenu';								// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermPDFDol');	// libelle de la permission
			$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramDolibarr';							// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermSpecif');	// libelle de la permission
			$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramInfraSPlus';						// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermImg');		// libelle de la permission
			$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramImages';							// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermAdr');		// libelle de la permission
			$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramAdresses';							// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermExtF');		// libelle de la permission
			$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramExtraFields';						// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermMent');		// libelle de la permission
			$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramMentions';							// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermNotes');		// libelle de la permission
			$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramNotes';								// action for php test if ($user->rights->permkey->level1->level2)
		//	$r++;
		//	$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
		//	$this->rights[$r][1]	= $langs->trans('InfraSPlusPermDict');		// libelle de la permission
		//	$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
		//	$this->rights[$r][4]	= 'paramDict';								// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermBkpRest');	// libelle de la permission
			$this->rights[$r][3]	= 0;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramBkpRest';							// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermLastOpt');	// libelle de la permission
			$this->rights[$r][3]	= 1;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramLastOpt';							// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermCGV');		// libelle de la permission
			$this->rights[$r][3]	= 1;										// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramCGV';								// action for php test if ($user->rights->permkey->level1->level2)
			$this->menu				= array();									// List of menus to add
			$r						= 0;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home',																					// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> 'InfraSPack Plus v7',
											'mainmenu'	=> 'home',
											'leftmenu'	=> $this->name,
											'url'		=> '/index.php?mainmenu=home&leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 120,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu',															// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home,fk_leftmenu='.$this->name,															// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSPlusParamsGeneralPDF'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/generalpdf.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 121,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu && $user->rights->'.$this->name.'->paramDolibarr',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home,fk_leftmenu='.$this->name,															// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSPlusParamsPDF'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/infrasplussetup.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 122,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu && $user->rights->'.$this->name.'->paramInfraSPlus',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home,fk_leftmenu='.$this->name,															// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSPlusParamsImages'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/images.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 123,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu && $user->rights->'.$this->name.'->paramImages',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home,fk_leftmenu='.$this->name,															// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSPlusParamsAdresses'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/adresses.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 124,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu && $user->rights->'.$this->name.'->paramAdresses',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home,fk_leftmenu='.$this->name,															// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSPlusParamsExtraFields'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/extrafields.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 125,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu && $user->rights->'.$this->name.'->paramExtraFields',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home,fk_leftmenu='.$this->name,															// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSPlusParamsMentions'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/mentions.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 126,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu && $user->rights->'.$this->name.'->paramMentions',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home,fk_leftmenu='.$this->name,															// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSPlusParamsNotes'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/notes.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 127,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu && $user->rights->'.$this->name.'->paramNotes',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=home,fk_leftmenu='.$this->name,															// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																								// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSPlusParamsDict'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/dictionaries.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 128,
											'enabled'	=> '$conf->'.$this->name.'->enabled && empty($user->admin)',											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->rights->'.$this->name.'->paramMenu && $user->rights->'.$this->name.'->paramDict',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																									// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																									// 0=Menu for internal users, 1=external users, 2=both
		}

		/************************************************
		 *		Function called when module is enabled.
		 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
		 *		It also creates data directories
		 *      @param		string		$options		Options when enabling module ('', 'noboxes')
		 *      @return		int							1 if OK, 0 if KO
		************************************************/
		function init($options = '')
		{
			global $langs, $conf, $db, $dolibarr_main_data_root;

			$sql		= array();
			$path		= dol_buildpath($this->name, 0);
			$pathsql	= $path.'/sql/';
			dol_syslog('modinfraspackplus.class::init pathsql = '.$pathsql.' PHP_OS = '.PHP_OS);
			$resultCopy	= dolCopyDir($path.'/fonts', DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/fonts', 0, 1);
			dol_syslog('modinfraspackplus.class::init path/fonts = '.$path.'/fonts'.' resultCopy = '.$resultCopy);
			if (empty($conf->global->INFRASPACKPLUS_DISABLED_CORE_CHANGE)) {
				$dolibranch		= explode('.', DOL_VERSION);
				$pathCoreChange	= $path.'/corechange-dlb'.$dolibranch[0].'0x';
				$resultCopy		= dolCopyDir($pathCoreChange, DOL_DOCUMENT_ROOT, 0, 1);
				dol_syslog('modinfraspackplus.class::init pathCoreChange = '.$pathCoreChange.' resultCopy = '.$resultCopy);
			}
			// Check if there is any old files for param update and change its name
			$filesqlold	= array ($pathsql.'update.sql', $pathsql.'update.'.$conf->entity.'.sql', $pathsql.'llx_societe.sql', $pathsql.'llx_societe_address.sql');
			foreach ($filesqlold as $oldsqlfile) {
				if (is_file($oldsqlfile))	$moved	= dol_move($oldsqlfile, $oldsqlfile.'.old');
				dol_syslog('modinfraspackplus.class::init oldsqlfile = '.$oldsqlfile.' moved = '.$moved);
			}
			if (is_file($pathsql.'update.'.$conf->entity)) {	// Moved update file to new folder
				$moved	= dol_move($pathsql.'update.'.$conf->entity, $dolibarr_main_data_root.'/'.$this->name.'/sql/update.'.$conf->entity);
				dol_syslog('modinfraspackplus.class::init moved update file to main_data_root = '.$moved);
			}
			$this->_load_tables('/'.$this->name.'/sql/');
			infraspackplus_restore_module ($this->name);
			if (empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT))	dolibarr_set_const($db, 'SOCIETE_ADDRESSES_MANAGEMENT', $conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			dolibarr_set_const($db, 'INFRASPLUS_DOL_VERSION',		DOL_VERSION,	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			dolibarr_set_const($db, 'INFRASPLUS_MAIN_VERSION',		$this->version,	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			return $this->_init($sql, $options);
		}

		/************************************************
		 * Function called when module is disabled.
		 * Remove from database constants, boxes and permissions from Dolibarr database.
		 * Data directories are not deleted
		 * @param		string		$options		Options when enabling module ('', 'noboxes')
		 * @return		int							1 if OK, 0 if KO
		************************************************/
		function remove($options = '')
		{
			global $langs, $conf;

			if (empty($conf->global->INFRASPACKPLUS_DISABLED_CORE_CHANGE)) {
				$dolibranch	= explode('.', DOL_VERSION);
				$path		= dol_buildpath('infraspackplus', 0);
				$pathCore	= $path.'/core-dlb'.$dolibranch[0].'0x';
				$resultCopy	= dolCopyDir($pathCore, DOL_DOCUMENT_ROOT, 0, 1);
				dol_syslog('modinfraspackplus.class::remove pathCore = '.$pathCore.' resultCopy = '.$resultCopy);
				if (is_file(DOL_DOCUMENT_ROOT.'/comm/address.php'))					$delete	= dol_delete_file(DOL_DOCUMENT_ROOT.'/comm/address.php');	// Clean old version
				dol_syslog('modinfraspackplus.class::remove delete (address.php) = '.$delete);
				if (is_file(DOL_DOCUMENT_ROOT.'/societe/class/address.class.php'))	$delete	= dol_delete_file(DOL_DOCUMENT_ROOT.'/societe/class/address.class.php');	// Clean old version
				dol_syslog('modinfraspackplus.class::remove delete (address.class.php) = '.$delete);
			}
			infraspackplus_bkup_module ($this->name);
			$sql		= array('DELETE FROM '.MAIN_DB_PREFIX.'const WHERE name like "INFRASPLUS\_%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.MAIN_DB_PREFIX.'const WHERE name like "%\_ADDON\_PDF" AND value like "InfraSPlus_%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.MAIN_DB_PREFIX.'const WHERE name like "%\_FREE\_TEXT\_%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.MAIN_DB_PREFIX.'const WHERE name like "%\_PUBLIC\_NOTE%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.MAIN_DB_PREFIX.'document_model WHERE nom like "InfraSPlus\_%" AND entity = "'.$conf->entity.'"',
								'DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'c_infraspackplus_mention',
								'DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'c_infraspackplus_note');
			infraspackplus_search_extf (-1);
			return $this->_remove($sql);
		}

		/************************************************
		 * Function called to check module name from changelog
		 * @param		int			$translated
		 * @return		string		current version or error message
		************************************************/
		function getVersion($translated = 1)
		{
			global $langs;

			$currentversion						= $this->version;
			$sxelasthtmlversion					= infraspackplus_getChangelogFile($this->name, 'dwn');
			if ($sxelasthtmlversion === false)	return $currentversion;
			else								$tblversionslast	= $sxelasthtmlversion->Version;
			$lastversion						= $tblversionslast[count($tblversionslast) - 1]->attributes()->Number;
			if ($lastversion != (string) $currentversion) {
				$mode							= GETPOSTISSET('mode') ? GETPOST('mode', 'alpha') : (empty($conf->global->MAIN_MODULE_SETUP_ON_LIST_BY_DEFAULT) ? 'commonkanban' : 'common');
				if (empty($mode))				$mode			= 'common';
				if ($mode == 'commonkanban')	$currentversion	= '<font color = '.($lastversion > (string) $currentversion ? '#FF6600)' : 'red').'><b>'.$currentversion.'</b></font>';
				else {
					if ($lastversion > (string) $currentversion) {
						$newversion		= $langs->trans('NewVersionAvailable').' : '.$lastversion;
						$currentversion	= '<font title = "'.$newversion.'" color = #FF6600><b>'.$currentversion.'</b></font>';
					}
					else	$currentversion	= '<font title = "'.$langs->trans('PiloteVersion').'" color = red><b>'.$currentversion.'</b></font>';
				}
			}
			return $currentversion;
		}

		/************************************************
		 * Function called to check module name from local changelog
		 * Control of the min version of Dolibarr needed
		 * If dolibarr version does'nt match the min version the module is disabled
		 * @return		string		current version or error message
		************************************************/
		function getLocalVersion()
		{
			global $conf, $langs;

			if ($conf->global->INFRAS_PHP_EXT_XML == -1)	return $langs->trans('InfraSPlusChangelogXMLError');
			$currentversion									= array();
			$currentversion									= infraspackplus_getLocalVersionMinDoli($this->name);
			$this->need_dolibarr_version					= $currentversion[1];
			if (version_compare($this->need_dolibarr_version, DOL_VERSION, '>'))	$this->disabled	= true;
			return $currentversion[0];
		}

		/************************************************
		 * Function called to view changelog on help tab
		 * @return		string		html view
		************************************************/
		function getChangeLog()
		{
			$currentversion	= infraspackplus_getLocalVersionMinDoli($this->name);
			$ChangeLog		= infraspackplus_getChangeLog($this->name, $currentversion[2], $currentversion[3], 0);
			return $ChangeLog;
		}
	}
?>
