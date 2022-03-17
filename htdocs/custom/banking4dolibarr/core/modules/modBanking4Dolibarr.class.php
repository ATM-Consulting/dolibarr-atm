<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   banking4dolibarr     Module Banking4Dolibarr
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/banking4dolibarr/core/modules directory.
 *  \file       htdocs/banking4dolibarr/core/modules/modBanking4Dolibarr.class.php
 *  \ingroup    banking4dolibarr
 *  \brief      Description and activation file for module Banking4Dolibarr
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');


/**
 *  Description and activation class for module Banking4Dolibarr
 */
class modBanking4Dolibarr extends DolibarrModules
{
    var $extrafields;

	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;
		$langs->load('opendsi@banking4dolibarr');

		$isV10p = version_compare(DOL_VERSION, "10.0.0") >= 0;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 163036;        // TODO Go on page http://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'banking4dolibarr';

		$family = (!empty($conf->global->EASYA_VERSION) ? 'easya' : 'opendsi');
		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = $family;
		// Module position in the family
		$this->module_position = 500;
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		$this->familyinfo = array($family => array('position' => '001', 'label' => $langs->trans($family . "Family")));
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Description of module Banking4Dolibarr";
		$this->descriptionlong = "";
		$this->editor_name = 'Open-DSI';
		$this->editor_url = 'http://www.open-dsi.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '7.0.58';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        if((float)DOL_VERSION <= 11.0) {
            $this->picto='opendsi@'.strtolower($this->name);
        } else {
            $this->picto='opendsi_big@'.strtolower($this->name);
        }

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /mymodule/core/modules/barcode)
		// for specific css file (eg: /mymodule/css/mymodule.css.php)
		//$this->module_parts = array(
		//                        	'triggers' => 0,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
		//							'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
		//							'substitutions' => 0,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
		//							'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
		//							'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
		//                        	'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
		//							'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
		//							'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
		//							'css' => array('/mymodule/css/mymodule.css.php'),	// Set this to relative path of css file if module has its own css file
		//							'js' => array('/mymodule/js/mymodule.js'),          // Set this to relative path of js file if module must load a js on all pages
		//							'hooks' => array('hookcontext1','hookcontext2',...) // Set here all hooks context managed by module. You can also set hook context 'all'
		//							'dir' => array('output' => 'othermodulename'),      // To force the default directories names
		//							'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@mymodule')) // Set here all workflow context managed by module
		//                        );
		$this->module_parts = array(
			'dictionaries' => 1,
			'hooks' => array('banktransactionlist', 'main'),
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		if(!empty($conf->global->BANKING4DOLIBARR_MODULE_KEY)) {
		    $link_admin = 'accounts.php@banking4dolibarr';
        } else {
            $link_admin = 'setup.php@banking4dolibarr';
        }
        $this->config_page_url = array($link_admin);

		// Dependencies
		$this->hidden = false;              // A condition to hide module
		$this->depends = array('modAdvanceDictionaries', 'modBanque');           // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();        // List of modules id to disable if this one is disabled
		$this->conflictwith = array();      // List of modules id this module is in conflict with
		$this->phpmin = array(5, 0);                    // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(7, 0);    // Minimum version of Dolibarr required by module
		$this->langfiles = array("banking4dolibarr@banking4dolibarr", "opendsi@banking4dolibarr");
		$langs->load('banking4dolibarr@banking4dolibarr');

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array(
			0 => array('BANKING4DOLIBARR_AUTO_LINK_BANK_ACCOUNT', 'chaine', '1', '', 0, 'current', 0),
			1 => array('BANKING4DOLIBARR_STATEMENT_NUMBER_RULES', 'chaine', BudgetInsight::STATEMENT_NUMBER_RULE_MONTHLY, '', 0, 'current', 0),
		);

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
		//                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		//                              'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in foundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view
		$this->tabs = array(
			'bank:+b4d_bank_records:Banking4DolibarrBankRecordsTab:banking4dolibarr@banking4dolibarr:$user->rights->banking4dolibarr->bank_records->lire:/banking4dolibarr/bankrecords.php?id=__ID__',
		);

		if (!isset($conf->banking4dolibarr) || !isset($conf->banking4dolibarr->enabled)) {
			$conf->banking4dolibarr = new stdClass();
			$conf->banking4dolibarr->enabled = 0;
		}

		// Dictionaries
		$this->dictionaries = array();
		/* Example:
		$this->dictionaries=array(
			'langs'=>'mylangfile@mymodule',
			'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
			'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
			'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
			'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
			'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
			'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
			'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
			'tabcond'=>array($conf->mymodule->enabled,$conf->mymodule->enabled,$conf->mymodule->enabled)												// Condition to show each dictionary
		);
		*/

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(
			0 => array('file' => 'box_b4d_bank_account@banking4dolibarr', 'note' => $langs->trans('Banking4DolibarrBoxBankAccount')),
		);            // List of boxes
		// Example:
		//$this->boxes=array(
		//    0=>array('file'=>'myboxa.php@mymodule','note'=>'','enabledbydefaulton'=>'Home'),
		//    1=>array('file'=>'myboxb.php@mymodule','note'=>''),
		//    2=>array('file'=>'myboxc.php@mymodule','note'=>'')
		//);

		// Cronjobs
		$this->cronjobs = array(
			0 => array('label' => $langs->trans('Banking4DolibarrCronRefreshBankRecords'), 'jobtype' => 'method', 'class' => '/custom/banking4dolibarr/class/budgetinsight.class.php', 'objectname' => 'BudgetInsight', 'method' => 'cronRefreshBankRecords', 'parameters' => '', 'comment' => '', 'frequency' => 1, 'unitfrequency' => 3600 * 24, 'test' => true),
		);
		// List of cron jobs entries to add
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'test'=>true)
		// );

		// Permissions
		$this->rights = array();        // Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = 163073;
		$this->rights[$r][1] = 'Lire les écritures bancaires liées à Budget Insight';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bank_records';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 163074;
		$this->rights[$r][1] = 'Rafraîchir les écritures bancaires liées à Budget Insight';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bank_records';
		$this->rights[$r][5] = 'refresh';
		$r++;

		$this->rights[$r][0] = 163075;
		$this->rights[$r][1] = 'Délier les écritures bancaires téléchargées rapprochées';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bank_records';
		$this->rights[$r][5] = 'unlink';
		$r++;

		$this->rights[$r][0] = 163083;
		$this->rights[$r][1] = 'Abandonner les écritures bancaires téléchargées';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bank_records';
		$this->rights[$r][5] = 'discard';
		$r++;

		$this->rights[$r][0] = 163084;
		$this->rights[$r][1] = 'Désabandonner les écritures bancaires téléchargées';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bank_records';
		$this->rights[$r][5] = 'undiscard';
		$r++;

		$this->rights[$r][0] = 163085;
		$this->rights[$r][1] = 'Fixer les écritures bancaires (Dolibarr) avec celles téléchargées';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bank_records';
		$this->rights[$r][5] = 'fix_lines';
		$r++;

		$this->rights[$r][0] = 163086;
		$this->rights[$r][1] = 'Supprimer les écritures bancaires téléchargées non rapprochées';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bank_records';
		$this->rights[$r][5] = 'supprimer';
		$r++;

		$this->rights[$r][0] = 163091;
		$this->rights[$r][1] = 'Fixer les écritures bancaires téléchargées en doubles';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bank_records';
		$this->rights[$r][5] = 'fix_duplicate';
		$r++;

        $this->rights[$r][0] = 163099;
        $this->rights[$r][1] = 'Accès déporté à la configuration du module dans l\'espace banque';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'param_menu';

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		// Main menu entries
		$this->menu = array();            // List of menus to add
		$r = 0;

        $this->menu[$r]	= array('fk_menu'	=> 'fk_mainmenu=bank',                                              // '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'		=> 'left',												            // This is a Left menu entry (top for top menu entry)
                                'titre'		=> 'Banking4DolibarrMenu',
                                'leftmenu'	=> 'banking4dolibarr',
                                'url'		=> '/compta/bank/list.php?mainmenu=bank&leftmenu=banking4dolibarr',
                                'langs'		=> 'banking4dolibarr@banking4dolibarr',					            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'	=> 120,
                                'enabled'	=> '$conf->banking4dolibarr->enabled',                              // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                                'perms'		=> '$user->rights->banking4dolibarr->param_menu',		            // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                                'target'	=> '',													            // '' to replace page or 'blank' to open on a new page
                                'user'		=> 0);													            // 0=Menu for internal users, 1=external users, 2=both
        $r++;
        $this->menu[$r]	= array('fk_menu' => 'fk_mainmenu=bank,fk_leftmenu=banking4dolibarr',                  // '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'		=> 'left',															// This is a Left menu entry (top for top menu entry)
                                'titre'		=> 'Banking4DolibarrMenuParameters',
                                'leftmenu'	=> '',
                                'url'		=> '/banking4dolibarr/admin/setup.php?leftmenu=banking4dolibarr',
                                'langs'		=> 'banking4dolibarr@banking4dolibarr', 			                // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'	=> 121,
                                'enabled'	=> '$conf->banking4dolibarr->enabled',								// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                                'perms'		=> '$user->rights->banking4dolibarr->param_menu',                   // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                                'target'	=> '',																// '' to replace page or 'blank' to open on a new page
                                'user'		=> 0);																// 0=Menu for internal users, 1=external users, 2=both
        $r++;
        $this->menu[$r]	= array('fk_menu' => 'fk_mainmenu=bank,fk_leftmenu=banking4dolibarr',                  // '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'		=> 'left',															// This is a Left menu entry (top for top menu entry)
                                'titre'		=> 'Banking4DolibarrMenuAccounts',
                                'leftmenu'	=> '',
                                'url'		=> '/banking4dolibarr/admin/accounts.php?leftmenu=banking4dolibarr',
                                'langs'		=> 'banking4dolibarr@banking4dolibarr',								// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'	=> 122,
                                'enabled'	=> '$conf->banking4dolibarr->enabled',                              // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                                'perms'		=> '$user->rights->banking4dolibarr->param_menu',                   // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                                'target'	=> '',																// '' to replace page or 'blank' to open on a new page
                                'user'		=> 0);																// 0=Menu for internal users, 1=external users, 2=both

		// Exports
		$r = 1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='MyModule';	// Translation key (used only if key ExportDataset_xxx_z not found)
		// $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_icon[$r]='generic:MyModule';					// Put here code of icon then string for translation key of module name
		// $this->export_permission[$r]=array(array("mymodule","level1","level2"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_TypeFields_array[$r]=array('t.date'=>'Date', 't.qte'=>'Numeric', 't.poids'=>'Numeric', 't.fad'=>'Numeric', 't.paq'=>'Numeric', 't.stockage'=>'Numeric', 't.fadparliv'=>'Numeric', 't.livau100'=>'Numeric', 't.forfait'=>'Numeric', 's.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','s.tva_intra'=>'Text','f.facnumber'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.date_lim_reglement'=>"Date",'f.total'=>"Numeric",'f.total_ttc'=>"Numeric",'f.tva'=>"Numeric",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_private'=>"Text",'f.note_public'=>"Text",'fd.description'=>"Text",'fd.subprice'=>"Numeric",'fd.tva_tx'=>"Numeric",'fd.qty'=>"Numeric",'fd.total_ht'=>"Numeric",'fd.total_tva'=>"Numeric",'fd.total_ttc'=>"Numeric",'fd.date_start'=>"Date",'fd.date_end'=>"Date",'fd.special_code'=>'Numeric','fd.product_type'=>"Numeric",'fd.fk_product'=>'List:product:label','p.ref'=>'Text','p.label'=>'Text','p.accountancy_code_sell'=>'Text');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $this->export_sql_order[$r] .=' ORDER BY s.nom';
		// $r++;

		// Import bank record downloaded
		$r++;
		$this->import_code[$r] = $this->rights_class . '_' . $r;
		$this->import_label[$r] = 'Banking4DolibarrImportBankRecord';
		$this->import_icon[$r] = 'bank';
		$this->import_tables_array[$r] = array(
			'br' => MAIN_DB_PREFIX . 'banking4dolibarr_bank_record'
		);    // List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r] = array(//field order as per structure of table llx_socpeople
			'br.rowid' => 'TechnicalID',
			'br.id_account' => 'Banking4DolibarrBankAccountID*',
			'br.label' => 'Description*',
			'br.record_date' => "DateOperation",
			'br.vdate' => "DateValue",
			'br.record_type' => "Type",
			'debit' => "Debit",
			'credit' => "Credit",
			'br.amount' => "Amount",
			'br.comment' => 'Comment',
			'br.note' => "Note",
			'category_parent' => "Banking4DolibarrCategoryParent",
			'category_child' => "Banking4DolibarrCategory",
			'br.id_category' => "Banking4DolibarrCategoryID",
			'br.rdate' => "Banking4DolibarrDateRealization",
			'br.bdate' => "Banking4DolibarrDateBank",
			'br.date_scraped' => "Banking4DolibarrDateScraped",
			'br.original_country' => "Country",
			'original_debit' => "Banking4DolibarrOriginDebit",
			'original_credit' => "Banking4DolibarrOriginCredit",
			'br.original_amount' => "Banking4DolibarrOriginAmount",
			'br.original_currency' => "Banking4DolibarrOriginCurrency",
			'commission_debit' => "Banking4DolibarrCommissionDebit",
			'commission_credit' => "Banking4DolibarrCommissionCredit",
			'br.commission' => "Banking4DolibarrCommissionAmount",
			'br.commission_currency' => "Banking4DolibarrCommissionCurrency",
			'br.coming' => "Banking4DolibarrComing",
			'br.deleted_date' => "Banking4DolibarrDeleteAt",
			'br.id_record' => "Banking4DolibarrRemoteIDNeeded",
			'br.last_update_date' => "Banking4DolibarrDateLastModificationNeeded",
			'br.datec' => "Banking4DolibarrDateCreationNeeded",
		);
		// End add extra fields
		$this->import_fieldshidden_array[$r] = array(
			'br.status' => 'const-0',
			'br.datas' => 'const-{}',
			'br.fk_user_author' => 'user->id',
		);    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_convertvalue_array[$r] = array(
			'br.label' => array(
				'rule' => 'compute',
				'file' => '/banking4dolibarr/class/banking4dolibarrimport.class.php',
				'class' => 'Banking4DolibarrImport',
				'method' => 'importSetValues'
			),
		);
		//$this->import_convertvalue_array[$r]=array('s.fk_soc'=>array('rule'=>'lastrowid',table='t');
		$this->import_regex_array[$r] = array(
			'br.id_account' => 'rowid@' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_account',
			'br.record_type' => 'code@' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_record_type' . (!$isV10p ? ' WHERE ' : ':') . 'entity=' . $conf->entity, // todo voir a mettre $dictionnary->getEntity()
			'br.original_currency' => 'code_iso@' . MAIN_DB_PREFIX . 'c_currencies',
			'br.commission_currency' => 'code_iso@' . MAIN_DB_PREFIX . 'c_currencies',
			'br.label' => '^.{0,255}$',
			'br.original_country' => '^.{0,255}$',
			'br.record_date' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
			'br.vdate' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
			'br.rdate' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
			'br.bdate' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
			'br.date_scraped' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
			'br.coming' => '^[0|1]$',
			'br.deleted_date' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]( [0-9][0-9]:[0-9][0-9]:[0-9][0-9])?$',
		);
		$this->import_examplevalues_array[$r] = array(
			'br.rowid' => 'Nothing, only used for the update',
			'br.id_account' => 'technical id of the bank account in the bank account list in the configuration of Banking4Dolibarr module',
			'br.label' => 'description',
			'br.record_date' => 'formatted as ' . dol_print_date(dol_now(), '%Y-%m-%d'),
			'br.vdate' => 'formatted as ' . dol_print_date(dol_now(), '%Y-%m-%d'),
			'br.record_type' => "code in the bank record type dictionary (c_banking4dolibarr_bank_record_type) in the configuration of Banking4Dolibarr module",
			'debit' => "debit value",
			'credit' => "credit value",
			'br.amount' => "amount value if the columns debit/credit not used (used for the management of the import)",
			'br.comment' => 'comment',
			'br.note' => "note",
			'category_parent' => "parent label in the categories dictionary (c_banking4dolibarr_bank_record_category) in the configuration of Banking4Dolibarr module",
			'category_child' => "label in the categories dictionary (c_banking4dolibarr_bank_record_category) in the configuration of Banking4Dolibarr module",
			'br.rdate' => 'formatted as ' . dol_print_date(dol_now(), '%Y-%m-%d'),
			'br.bdate' => 'formatted as ' . dol_print_date(dol_now(), '%Y-%m-%d'),
			'br.date_scraped' => 'formatted as ' . dol_print_date(dol_now(), '%Y-%m-%d'),
			'br.original_country' => "original country",
			'original_debit' => "original debit value",
			'original_credit' => "original credit value",
			'br.original_amount' => "original amount value if the columns original debit/original credit not used (if set then used for the management of the import)",
			'br.original_currency' => 'USD/EUR etc. matches field "code_iso" in table "' . MAIN_DB_PREFIX . 'c_currencies"',
			'commission_debit' => "commission debit value",
			'commission_credit' => "commission credit value",
			'br.commission' => "commission amount value if the columns commission debit/commission credit not used (if set then used for the management of the import)",
			'br.commission_currency' => 'USD/EUR etc. matches field "code_iso" in table "' . MAIN_DB_PREFIX . 'c_currencies"',
			'br.coming' => "coming [0 or 1]",
			'br.deleted_date' => 'formatted as ' . dol_print_date(dol_now(), '%Y-%m-%d %H:%i:%s'),
			'br.id_record' => "Nothing, only used for the management of the import",
			'br.last_update_date' => "Nothing, only used for the management of the import",
			'br.datec' => "Nothing, only used for the management of the import",
		);
		$this->import_updatekeys_array[$r] = array(
			'br.rowid' => 'TechnicalID'
		);
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
		global $conf, $langs, $hookmanager;
		$langs->load('banking4dolibarr@banking4dolibarr');

		// Create tables of all dictionaries
		if (dol_include_once('/advancedictionaries/class/dictionary.class.php')) {
			$dictionaries = Dictionary::fetchAllDictionaries($this->db, 'banking4dolibarr');
			foreach ($dictionaries as $dictionary) {
				if ($dictionary->createTables() < 0) {
					setEventMessage('Error create dictionary table: ' . $dictionary->errorsToString(), 'errors');
					return 0;
				}
			}
		} else {
			setEventMessage($langs->trans('Banking4DolibarrErrorModuleNotFound', "AdvanceDictionaries") . ' ' . $langs->trans('Banking4DolibarrErrorModuleNotFoundDownloadHere', 'https://github.com/OPEN-DSI/dolibarr_module_advancedictionaries'), 'errors');
			return 0;
		}

		$isV10p = version_compare(DOL_VERSION, "10.0.0") >= 0;
		$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

		$cq = $this->db->type == 'pgsql' ? '"' : '`';

		$sql = array(
			array('sql' => "INSERT INTO " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_record_type ({$cq}code{$cq}, {$cq}label{$cq}, {$cq}mode_reglement{$cq}) VALUES" .
				" ('transfer', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeTransfer')) . "', 'VIR')," .
				" ('order', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeOrder')) . "', 'PRE')," .
				" ('check', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeCheck')) . "', 'CHQ')," .
				" ('deposit', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeDeposit')) . "', 'LIQ')," .
				" ('payback', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypePayback')) . "', '')," .
				" ('withdrawal', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeWithdrawal')) . "', '')," .
				" ('loan_payment', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeLoanPayment')) . "', '')," .
				" ('bank', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeBank')) . "', 'PRE')," .
				" ('card', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeCard')) . "', 'CB')," .
				" ('deferred_card', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeDeferredCard')) . "', 'CB')," .
				" ('card_summary', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeCardSummary')) . "', 'CB');",
				'ignoreerror' => 1,
			),
			array('sql' => "INSERT INTO " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_record_type ({$cq}code{$cq}, {$cq}label{$cq}, {$cq}mode_reglement{$cq}) VALUES" .
				" ('summary_card', '" . $this->db->escape($langs->trans('Banking4DolibarrBankRecordTypeCardSummary')) . "', 'CB');",
				'ignoreerror' => 1,
			),
			array('sql' => "UPDATE " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_record_type SET {$cq}mode_reglement{$cq} = 'CHQ' WHERE {$cq}code{$cq} = 'deposit';",
				'ignoreerror' => 1,
			),
			array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_dates_options AS (" .
				"SELECT c.entity" .
				", MAX(" . $this->db->ifsql("c.name = 'BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES'", $this->db->ifsql("c.value != ''", "CAST(c.value AS SIGNED)", "NULL"), "NULL") . ") AS debit_min_offset_dates" .
				", MAX(" . $this->db->ifsql("c.name = 'BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES'", $this->db->ifsql("c.value != ''", "CAST(c.value AS SIGNED)", "NULL"), "NULL") . ") AS debit_max_offset_dates" .
				", MAX(" . $this->db->ifsql("c.name = 'BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES'", $this->db->ifsql("c.value != ''", "CAST(c.value AS SIGNED)", "NULL"), "NULL") . ") AS credit_min_offset_dates" .
				", MAX(" . $this->db->ifsql("c.name = 'BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES'", $this->db->ifsql("c.value != ''", "CAST(c.value AS SIGNED)", "NULL"), "NULL") . ") AS credit_max_offset_dates" .
				" FROM " . MAIN_DB_PREFIX . "const AS c" .
				" GROUP BY entity" .
				");",
			),
			array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_reconcile_same_adp AS (" .
				" SELECT b.fk_account, b.rowid AS fk_bank, b4dbr.rowid AS fk_bank_record, b4dbr.id_category" .
				", " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " AS record_date" .
				" FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS b4dbr" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account AS cb4dba ON cb4dba.rowid = b4dbr.id_account" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_record_type AS cb4dbrt ON cb4dbrt.code = b4dbr.record_type" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "bank AS b ON b.fk_account = cb4dba.fk_bank_account AND b.amount = b4dbr.amount" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS brl ON (brl.fk_bank = b.rowid OR brl.fk_bank_record = b4dbr.rowid)" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_dates_options AS b4ddo ON b4ddo.entity = cb4dba.entity" .
				" WHERE b.rowid IS NOT NULL" .
				" AND brl.rowid IS NULL" .
				" AND b4dbr.status = " . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED .
				" AND (" .
				"   (" .
				($this->db->type == 'pgsql' ?
					"     b.dateo BETWEEN b4dbr.record_date - (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.debit_min_offset_dates IS NULL", "15", "b4ddo.debit_min_offset_dates") . ") AND b4dbr.record_date + (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.debit_max_offset_dates IS NULL", "15", "b4ddo.debit_max_offset_dates") . ")" .
					"     AND b.datev BETWEEN " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " - (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.debit_min_offset_dates IS NULL", "15", "b4ddo.debit_min_offset_dates") . ") AND " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " + (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.debit_max_offset_dates IS NULL", "15", "b4ddo.debit_max_offset_dates") . ")"
				:
					"     b.dateo BETWEEN DATE_SUB(b4dbr.record_date, INTERVAL " . $this->db->ifsql("b4ddo.debit_min_offset_dates IS NULL", "15", "b4ddo.debit_min_offset_dates") . " DAY) AND DATE_ADD(b4dbr.record_date, INTERVAL " . $this->db->ifsql("b4ddo.debit_max_offset_dates IS NULL", "15", "b4ddo.debit_max_offset_dates") . " DAY)" .
					"     AND b.datev BETWEEN DATE_SUB(" . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . ", INTERVAL " . $this->db->ifsql("b4ddo.debit_min_offset_dates IS NULL", "15", "b4ddo.debit_min_offset_dates") . " DAY) AND DATE_ADD(" . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . ", INTERVAL " . $this->db->ifsql("b4ddo.debit_max_offset_dates IS NULL", "15", "b4ddo.debit_max_offset_dates") . " DAY)"
				) .
				"     AND b4dbr.amount < 0" .
				"   ) OR (" .
				($this->db->type == 'pgsql' ?
					"     b.dateo BETWEEN b4dbr.record_date - (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.credit_min_offset_dates IS NULL", "15", "b4ddo.credit_min_offset_dates") . ") AND b4dbr.record_date + (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.credit_max_offset_dates IS NULL", "15", "b4ddo.credit_max_offset_dates") . ")" .
					"     AND b.datev BETWEEN " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " - (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.credit_min_offset_dates IS NULL", "15", "b4ddo.credit_min_offset_dates") . ") AND " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " + (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.credit_max_offset_dates IS NULL", "15", "b4ddo.credit_max_offset_dates") . ")"
				:
					"     b.dateo BETWEEN DATE_SUB(b4dbr.record_date, INTERVAL " . $this->db->ifsql("b4ddo.credit_min_offset_dates IS NULL", "15", "b4ddo.credit_min_offset_dates") . " DAY) AND DATE_ADD(b4dbr.record_date, INTERVAL " . $this->db->ifsql("b4ddo.credit_max_offset_dates IS NULL", "15", "b4ddo.credit_max_offset_dates") . " DAY)" .
					"     AND b.datev BETWEEN DATE_SUB(" . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . ", INTERVAL " . $this->db->ifsql("b4ddo.credit_min_offset_dates IS NULL", "15", "b4ddo.credit_min_offset_dates") . " DAY) AND DATE_ADD(" . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . ", INTERVAL " . $this->db->ifsql("b4ddo.credit_max_offset_dates IS NULL", "15", "b4ddo.credit_max_offset_dates") . " DAY)"
				) .
				"     AND b4dbr.amount >= 0" .
				"   )" .
				" )" .
				" AND cb4dbrt.mode_reglement = b.fk_type" .
				" AND b.fk_type != 'SOLD'" .
				");",
			),
			array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_reconcile_same_ad AS (" .
				" SELECT b.fk_account, b.rowid AS fk_bank, b4dbr.rowid AS fk_bank_record, b4dbr.id_category" .
				", " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " AS record_date" .
				" FROM " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS b4dbr" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account AS cb4dba ON cb4dba.rowid = b4dbr.id_account" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "bank AS b ON b.fk_account = cb4dba.fk_bank_account AND b.amount = b4dbr.amount" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_link AS brl ON (brl.fk_bank = b.rowid OR brl.fk_bank_record = b4dbr.rowid)" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_dates_options AS b4ddo ON b4ddo.entity = cb4dba.entity" .
				" WHERE b.rowid IS NOT NULL" .
				" AND brl.rowid IS NULL" .
				" AND b4dbr.status = " . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED .
				" AND (" .
				"   (" .
				($this->db->type == 'pgsql' ?
					"     b.dateo BETWEEN b4dbr.record_date - (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.debit_min_offset_dates IS NULL", "15", "b4ddo.debit_min_offset_dates") . ") AND b4dbr.record_date + (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.debit_max_offset_dates IS NULL", "15", "b4ddo.debit_max_offset_dates") . ")" .
					"     AND b.datev BETWEEN " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " - (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.debit_min_offset_dates IS NULL", "15", "b4ddo.debit_min_offset_dates") . ") AND " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " + (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.debit_max_offset_dates IS NULL", "15", "b4ddo.debit_max_offset_dates") . ")"
				:
					"     b.dateo BETWEEN DATE_SUB(b4dbr.record_date, INTERVAL " . $this->db->ifsql("b4ddo.debit_min_offset_dates IS NULL", "15", "b4ddo.debit_min_offset_dates") . " DAY) AND DATE_ADD(b4dbr.record_date, INTERVAL " . $this->db->ifsql("b4ddo.debit_max_offset_dates IS NULL", "15", "b4ddo.debit_max_offset_dates") . " DAY)" .
					"     AND b.datev BETWEEN DATE_SUB(" . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . ", INTERVAL " . $this->db->ifsql("b4ddo.debit_min_offset_dates IS NULL", "15", "b4ddo.debit_min_offset_dates") . " DAY) AND DATE_ADD(" . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . ", INTERVAL " . $this->db->ifsql("b4ddo.debit_max_offset_dates IS NULL", "15", "b4ddo.debit_max_offset_dates") . " DAY)"
				) .
				"     AND b4dbr.amount < 0" .
				"   ) OR (" .
				($this->db->type == 'pgsql' ?
					"     b.dateo BETWEEN b4dbr.record_date - (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.credit_min_offset_dates IS NULL", "15", "b4ddo.credit_min_offset_dates") . ") AND b4dbr.record_date + (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.credit_max_offset_dates IS NULL", "15", "b4ddo.credit_max_offset_dates") . ")" .
					"     AND b.datev BETWEEN " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " - (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.credit_min_offset_dates IS NULL", "15", "b4ddo.credit_min_offset_dates") . ") AND " . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . " + (INTERVAL '1 day' * " . $this->db->ifsql("b4ddo.credit_max_offset_dates IS NULL", "15", "b4ddo.credit_max_offset_dates") . ")"
				:
					"     b.dateo BETWEEN DATE_SUB(b4dbr.record_date, INTERVAL " . $this->db->ifsql("b4ddo.credit_min_offset_dates IS NULL", "15", "b4ddo.credit_min_offset_dates") . " DAY) AND DATE_ADD(b4dbr.record_date, INTERVAL " . $this->db->ifsql("b4ddo.credit_max_offset_dates IS NULL", "15", "b4ddo.credit_max_offset_dates") . " DAY)" .
					"     AND b.datev BETWEEN DATE_SUB(" . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . ", INTERVAL " . $this->db->ifsql("b4ddo.credit_min_offset_dates IS NULL", "15", "b4ddo.credit_min_offset_dates") . " DAY) AND DATE_ADD(" . $this->db->ifsql("b4dbr.vdate IS NULL", "b4dbr.record_date", "b4dbr.vdate") . ", INTERVAL " . $this->db->ifsql("b4ddo.credit_max_offset_dates IS NULL", "15", "b4ddo.credit_max_offset_dates") . " DAY)"
				) .
				"     AND b4dbr.amount >= 0" .
				"   )" .
				" )" .
				" AND b.fk_type != 'SOLD'" .
				");",
			),
			array('sql' => "DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_reconcile_same_ap;",
			),
			array('sql' => "DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_reconcile_same_a;",
			)
		);

		// Unpaid list management
		//-------------------------------------
		$banking4dolibarr_unpaid_list = array();

		$hookmanager->initHooks(array('banking4dolibarrdao'));
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addCustomSqlUnpaidListIntoTheView', $parameters); // Note that $action and $object may have been
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			return 0;
		} elseif (is_array($hookmanager->resArray) && !empty($hookmanager->resArray)) {
			$banking4dolibarr_unpaid_list = $hookmanager->resArray;
		}

		// Invoices enabled
		if (!empty($conf->facture->enabled)) {
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_payment_invoice AS (" .
					" SELECT pf.fk_facture, sum(pf.amount) as amount" .
					" FROM " . MAIN_DB_PREFIX . "paiement_facture as pf" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = pf.fk_facture" .
					" WHERE f.paye = 0 AND f.fk_statut = 1" .
					" GROUP BY pf.fk_facture" .
					");",
				),
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_credit_note_invoice AS (" .
					" SELECT rc.fk_facture, sum(rc.amount_ttc) as amount" .
					" FROM " . MAIN_DB_PREFIX . "societe_remise_except as rc" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture as fs ON fs.rowid = rc.fk_facture_source" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = rc.fk_facture" .
					" WHERE (fs.type = 2 OR fs.type = 0)" .
					" AND f.paye = 0 AND f.fk_statut = 1" .
					" GROUP BY rc.fk_facture" .
					");",
				),
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_deposit_invoice AS (" .
					" SELECT rc.fk_facture, sum(rc.amount_ttc) as amount" .
					" FROM " . MAIN_DB_PREFIX . "societe_remise_except as rc" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture as fs ON fs.rowid = rc.fk_facture_source" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = rc.fk_facture" .
					" WHERE fs.type = 3" .
					" AND f.paye = 0 AND f.fk_statut = 1" .
					" GROUP BY rc.fk_facture" .
					");",
				),
			));
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'facture' AS element_type, f.rowid AS element_id, " . ($isV10p ? "f.ref" : "f.facnumber") . " AS ref, f.ref_client AS ref_ext" .
				", '' AS label, f.datef AS dateb, f.date_lim_reglement AS datee, (f.total_ttc - " . $this->db->ifsql("fp.amount IS NULL", "0", "fp.amount") . " - " . $this->db->ifsql("fcn.amount IS NULL", "0", "fcn.amount") . " - " . $this->db->ifsql("fd.amount IS NULL", "0", "fd.amount") . ") AS amount" .
				", f.fk_soc AS fk_soc, cn.company_name, cn.company_alt_name, cn.company_spe_name, f.fk_account, f.fk_mode_reglement AS fk_payment_mode, f.entity" .
				" FROM " . MAIN_DB_PREFIX . "facture as f" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_payment_invoice AS fp ON fp.fk_facture = f.rowid" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_credit_note_invoice AS fcn ON fcn.fk_facture = f.rowid" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_deposit_invoice AS fd ON fd.fk_facture = f.rowid" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_company_name AS cn ON cn.fk_soc = f.fk_soc" .
				" WHERE f.paye = 0 AND f.fk_statut = 1" .
				" AND (f.total_ttc - " . $this->db->ifsql("fp.amount IS NULL", "0", "fp.amount") . " - " . $this->db->ifsql("fcn.amount IS NULL", "0", "fcn.amount") . " - " . $this->db->ifsql("fd.amount IS NULL", "0", "fd.amount") . ") != 0";
		}
		// Supplier invoices enabled
		if (!empty($conf->fournisseur->enabled)) {
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_payment_invoice_supplier AS (" .
					" SELECT pff.fk_facturefourn, sum(pff.amount) as amount" .
					" FROM " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as pff" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ff ON ff.rowid = pff.fk_facturefourn" .
					" WHERE ff.paye = 0 AND ff.fk_statut = 1" .
					" GROUP BY pff.fk_facturefourn" .
					");",
				),
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_credit_note_invoice_supplier AS (" .
					" SELECT rc.fk_invoice_supplier, sum(rc.amount_ttc) as amount" .
					" FROM " . MAIN_DB_PREFIX . "societe_remise_except as rc" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ffs ON ffs.rowid = rc.fk_invoice_supplier_source" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ff ON ff.rowid = rc.fk_invoice_supplier" .
					" WHERE ffs.type = 2" .
					" AND ff.paye = 0 AND ff.fk_statut = 1" .
					" GROUP BY rc.fk_facture" .
					");",
				),
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_deposit_invoice_supplier AS (" .
					" SELECT rc.fk_invoice_supplier, sum(rc.amount_ttc) as amount" .
					" FROM " . MAIN_DB_PREFIX . "societe_remise_except as rc" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ffs ON ffs.rowid = rc.fk_invoice_supplier_source" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ff ON ff.rowid = rc.fk_invoice_supplier" .
					" WHERE ffs.type = 3" .
					" AND ff.paye = 0 AND ff.fk_statut = 1" .
					" GROUP BY rc.fk_facture" .
					");",
				),
			));
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'invoice_supplier' AS element_type, ff.rowid AS element_id, ff.ref, ff.ref_supplier AS ref_ext" .
				", '' AS label, ff.datef AS dateb, ff.date_lim_reglement AS datee, -(ff.total_ttc - " . $this->db->ifsql("ffp.amount IS NULL", "0", "ffp.amount") . " - " . $this->db->ifsql("ffcn.amount IS NULL", "0", "ffcn.amount") . " - " . $this->db->ifsql("ffd.amount IS NULL", "0", "ffd.amount") . ") AS amount" .
				", ff.fk_soc AS fk_soc, cn.company_name, cn.company_alt_name, cn.company_spe_name, ff.fk_account, ff.fk_mode_reglement AS fk_payment_mode, ff.entity" .
				" FROM " . MAIN_DB_PREFIX . "facture_fourn as ff" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_payment_invoice_supplier AS ffp ON ffp.fk_facturefourn = ff.rowid" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_credit_note_invoice_supplier AS ffcn ON ffcn.fk_invoice_supplier = ff.rowid" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_deposit_invoice_supplier AS ffd ON ffd.fk_invoice_supplier = ff.rowid" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_company_name AS cn ON cn.fk_soc = ff.fk_soc" .
				" WHERE ff.paye = 0 AND ff.fk_statut = 1" .
				" AND (ff.total_ttc - " . $this->db->ifsql("ffp.amount IS NULL", "0", "ffp.amount") . " - " . $this->db->ifsql("ffcn.amount IS NULL", "0", "ffcn.amount") . " - " . $this->db->ifsql("ffd.amount IS NULL", "0", "ffd.amount") . ") != 0";
		}
		// Invoices or supplier invoices enabled
		if (!empty($conf->facture->enabled) || !empty($conf->fournisseur->enabled)) {
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_company_name AS (" .
					" SELECT s.rowid AS fk_soc, s.nom AS company_name, s.name_alias AS company_alt_name, sef.b4d_spe_name AS company_spe_name" .
					" FROM " . MAIN_DB_PREFIX . "societe AS s" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields AS sef ON sef.fk_object = s.rowid" .
					");",
				),
			));
		}
		// Donations enabled
		if (!empty($conf->don->enabled)) {
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_payment_donation AS (" .
					" SELECT pd.fk_donation, sum(pd.amount) as amount" .
					" FROM " . MAIN_DB_PREFIX . "payment_donation as pd" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "don as d ON d.rowid = pd.fk_donation" .
					" WHERE d.paid = 0 AND d.fk_statut = 1" .
					" GROUP BY pd.fk_donation" .
					");",
				),
			));
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'don' AS element_type, d.rowid AS element_id, NULL AS ref, NULL AS ref_ext" .
				", d.rowid AS label, d.datedon AS dateb, NULL AS datee, (d.amount - " . $this->db->ifsql("dp.amount IS NULL", "0", "dp.amount") . ") AS amount" .
				", 0 AS fk_soc, NULL AS company_name, NULL AS company_alt_name, NULL AS company_spe_name, 0 AS fk_account, d.fk_payment AS fk_payment_mode, d.entity" .
				" FROM " . MAIN_DB_PREFIX . "don as d" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_payment_donation AS dp ON dp.fk_donation = d.rowid" .
				" WHERE d.paid = 0 AND d.fk_statut = 1" .
				" AND (d.amount - " . $this->db->ifsql("dp.amount IS NULL", "0", "dp.amount") . ") != 0";
		}
		// TVA enabled
		if (!empty($conf->tax->enabled)) {
			// Socials charges
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_payment_charge AS (" .
					" SELECT pc.fk_charge, sum(pc.amount) as amount" .
					" FROM " . MAIN_DB_PREFIX . "paiementcharge as pc" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "chargesociales as cs ON cs.rowid = pc.fk_charge" .
					" WHERE cs.paye = 0" .
					" GROUP BY pc.fk_charge" .
					");",
				),
			));
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'chargesociales' AS element_type, cs.rowid AS element_id, NULL AS ref, NULL AS ref_ext" .
				", CONCAT(cs.rowid, ' - ', cs.libelle) AS label, cs.periode AS dateb, cs.date_ech AS datee, -(cs.amount - " . $this->db->ifsql("csp.amount IS NULL", "0", "csp.amount") . ") AS amount" .
				", 0 AS fk_soc, NULL AS company_name, NULL AS company_alt_name, NULL AS company_spe_name, cs.fk_account AS fk_account, cs.fk_mode_reglement AS fk_payment_mode, cs.entity" .
				" FROM " . MAIN_DB_PREFIX . "chargesociales as cs" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_payment_charge AS csp ON csp.fk_charge = cs.rowid" .
				" WHERE cs.paye = 0" .
				" AND (cs.amount - " . $this->db->ifsql("csp.amount IS NULL", "0", "csp.amount") . ") != 0";

			// TVA
			if ($isV14p) {
				$sql = array_merge($sql, array(
					array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_payment_vat AS (" .
						" SELECT pv.fk_tva, sum(pv.amount) as amount" .
						" FROM " . MAIN_DB_PREFIX . "payment_vat as pv" .
						" LEFT JOIN " . MAIN_DB_PREFIX . "tva as v ON v.rowid = pv.fk_tva" .
						" WHERE v.paye = 0" .
						" GROUP BY pv.fk_tva" .
						");",
					),
				));
				$banking4dolibarr_unpaid_list[] =
					"SELECT 'vat' AS element_type, v.rowid AS element_id, NULL AS ref, NULL AS ref_ext" .
					", v.label AS label, v.datep AS dateb, v.datev AS datee, -(v.amount - " . $this->db->ifsql("vp.amount IS NULL", "0", "vp.amount") . ") AS amount" .
					", 0 AS fk_soc, NULL AS company_name, NULL AS company_alt_name, NULL AS company_spe_name, v.fk_account AS fk_account, v.fk_typepayment AS fk_payment_mode, v.entity" .
					" FROM " . MAIN_DB_PREFIX . "tva as v" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_payment_vat AS vp ON vp.fk_tva = v.rowid" .
					" WHERE v.paye = 0" .
					" AND (v.amount	- " . $this->db->ifsql("vp.amount IS NULL", "0", "vp.amount") . ") != 0";

			}
		}
		// Expenses reports enabled
		if (!empty($conf->expensereport->enabled)) {
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_payment_expensereport AS (" .
					" SELECT per.fk_expensereport, sum(per.amount) as amount" .
					" FROM " . MAIN_DB_PREFIX . "payment_expensereport as per" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = per.fk_expensereport" .
					" WHERE er.paid = 0 AND er.fk_statut = 5" .
					" GROUP BY per.fk_expensereport" .
					");",
				),
			));
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'expensereport' AS element_type, er.rowid AS element_id, er.ref, NULL AS ref_ext" .
				", '' AS label, er.date_approve AS dateb, NULL AS datee, -(er.total_ttc - " . $this->db->ifsql("erp.amount IS NULL", "0", "erp.amount") . ") AS amount" .
				", 0 AS fk_soc, NULL AS company_name, NULL AS company_alt_name, NULL AS company_spe_name, 0 AS fk_account, 0 AS fk_payment_mode, er.entity" .
				" FROM " . MAIN_DB_PREFIX . "expensereport as er" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_payment_expensereport AS erp ON erp.fk_expensereport = er.rowid" .
				" WHERE er.paid = 0 AND er.fk_statut = 5" .
				" AND (er.total_ttc - " . $this->db->ifsql("erp.amount IS NULL", "0", "erp.amount") . ") != 0";
		}
		// Loans enabled
		if (!empty($conf->loan->enabled)) {
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_payment_loan AS (" .
					" SELECT pl.fk_loan, sum(pl.amount_capital) as amount" .
					" FROM " . MAIN_DB_PREFIX . "payment_loan as pl" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "loan as l ON l.rowid = pl.fk_loan" .
					" WHERE l.paid = 0" .
					" GROUP BY pl.fk_loan" .
					");",
				),
			));
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'loan' AS element_type, l.rowid AS element_id, NULL AS ref, NULL AS ref_ext" .
				", CONCAT(l.rowid, ' - ', l.label) AS label, l.datestart AS dateb, l.dateend AS datee, (l.capital - " . $this->db->ifsql("lp.amount IS NULL", "0", "lp.amount") . ") AS amount" .
				", 0 AS fk_soc, NULL AS company_name, NULL AS company_alt_name, NULL AS company_spe_name, 0 AS fk_account, 0 AS fk_payment_mode, l.entity" .
				" FROM " . MAIN_DB_PREFIX . "loan as l" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_payment_loan AS lp ON lp.fk_loan = l.rowid" .
				" WHERE l.paid = 0" .
				" AND (l.capital - " . $this->db->ifsql("lp.amount IS NULL", "0", "lp.amount") . ") != 0";
		}
		// Salaries enabled
		if ($isV14p && !empty($conf->salaries->enabled)) {
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_payment_salary AS (" .
					" SELECT ps.fk_salary, sum(ps.amount) as amount" .
					" FROM " . MAIN_DB_PREFIX . "payment_salary as ps" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "salary as s ON s.rowid = ps.fk_salary" .
					" WHERE s.paye = 0" .
					" GROUP BY ps.fk_salary" .
					");",
				),
			));
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'salaries' AS element_type, s.rowid AS element_id, u.lastname AS ref, NULL AS ref_ext" .
				", s.label AS label, s.datesp AS dateb, s.dateep AS datee, -(s.amount - " . $this->db->ifsql("sp.amount IS NULL", "0", "sp.amount") . ") AS amount" .
				", 0 AS fk_soc, NULL AS company_name, NULL AS company_alt_name, NULL AS company_spe_name, s.fk_account AS fk_account, s.fk_typepayment AS fk_payment_mode, s.entity" .
				" FROM " . MAIN_DB_PREFIX . "salary as s" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_payment_salary AS sp ON sp.fk_salary = s.rowid" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "user AS u ON u.rowid = s.fk_user" .
				" WHERE s.paye = 0" .
				" AND (s.amount	- " . $this->db->ifsql("sp.amount IS NULL", "0", "sp.amount") . ") != 0";
		}
		// Check deposit (special case)
		if (empty($conf->global->BANK_DISABLE_CHECK_DEPOSIT) && !empty($conf->banque->enabled) && empty($conf->global->MAIN_DISABLEDRAFTSTATUS) && (!empty($conf->facture->enabled) || !empty($conf->global->MAIN_MENU_CHEQUE_DEPOSIT_ON))) {
			$payment_check_deposit_id = dol_getIdFromCode($this->db, 'CHQ', 'c_paiement', 'code', 'id', 1);
			if (!($payment_check_deposit_id > 0)) {
				setEventMessage('Error get check deposit payment type id :' . $this->db->lasterror(), 'errors');
				return 0;
			}
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'chequereceipt' AS element_type, bc.rowid AS element_id, bc.ref AS ref, NULL AS ref_ext" .
				", '' AS label, bc.date_bordereau AS dateb, NULL AS datee, bc.amount AS amount" .
				", 0 AS fk_soc, NULL AS company_name, NULL AS company_alt_name, NULL AS company_spe_name, bc.fk_bank_account AS fk_account, $payment_check_deposit_id AS fk_payment_mode, bc.entity" .
				" FROM " . MAIN_DB_PREFIX . "bordereau_cheque as bc" .
				" WHERE bc.statut = 0" .
				" AND bc.amount != 0";
		}
		// Standing order (special case)
		if (!empty($conf->prelevement->enabled)) {
			$sql = array_merge($sql, array(
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_widthdraw_rejected AS (" .
					" SELECT pl.fk_prelevement_bons, sum(pl.amount) as amount" .
					" FROM " . MAIN_DB_PREFIX . "prelevement_rejet as pr" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "prelevement_lignes as pl ON pl.rowid = pr.fk_prelevement_lignes	" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "prelevement_bons as pb ON pb.rowid = pl.fk_prelevement_bons	" .
					" WHERE pb.statut = 1" .
					" GROUP BY pl.fk_prelevement_bons" .
					");",
				),
			));
			$payment_standing_order_id = dol_getIdFromCode($this->db, 'PRE', 'c_paiement', 'code', 'id', 1);
			if (!($payment_standing_order_id > 0)) {
				setEventMessage('Error get standing order payment type id :' . $this->db->lasterror(), 'errors');
				return 0;
			}
			$banking4dolibarr_unpaid_list[] =
				"SELECT 'widthdraw' AS element_type, pb.rowid AS element_id, pb.ref AS ref, NULL AS ref_ext" .
				", '' AS label, pb.date_trans AS dateb, NULL AS datee, (pb.amount - " . $this->db->ifsql("bwr.amount IS NULL", "0", "bwr.amount") . ") AS amount" .
				", 0 AS fk_soc, NULL AS company_name, NULL AS company_alt_name, NULL AS company_spe_name, 0 AS fk_account, $payment_standing_order_id AS fk_payment_mode, pb.entity" .
				" FROM " . MAIN_DB_PREFIX . "prelevement_bons as pb" .
				" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_widthdraw_rejected AS bwr ON bwr.fk_prelevement_bons = pb.rowid" .
				" WHERE pb.statut = 1" .
				" AND (pb.amount - " . $this->db->ifsql("bwr.amount IS NULL", "0", "bwr.amount") . ") != 0";
		}
		if (!empty($banking4dolibarr_unpaid_list)) {
			$sql = array_merge($sql, array(
				array('sql' =>
					"CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list(element_type, element_id, ref, ref_ext" .
					", label, dateb, datee, amount, fk_soc, company_name, company_alt_name, company_spe_name, fk_account, fk_payment_mode, entity) AS " .
					implode(' UNION ', $banking4dolibarr_unpaid_list)
				),
				array('sql' => "CREATE OR REPLACE VIEW " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list_same_a AS (" .
					"SELECT br.rowid AS bank_records, t.element_type, t.element_id, cb4dba.fk_bank_account, br.record_date, t.dateb, t.datee, t.amount, br.label, br.comment, t.company_name, t.company_alt_name, t.company_spe_name, ba.entity" .
					" FROM " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list AS t" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record AS br ON br.amount = t.amount" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "c_banking4dolibarr_bank_account AS cb4dba ON cb4dba.rowid = br.id_account" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "banking4dolibarr_bank_record_pre_link AS pl ON pl.fk_bank_record = br.rowid" .
					" LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON ba.rowid = cb4dba.fk_bank_account" .
					" WHERE br.rowid IS NOT NULL" .
					" AND br.status = " . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED .
					" AND t.entity = ba.entity" .
					" AND pl.rowid IS NULL" .
					" AND br.deleted_date IS NULL" .
					");",
				),
			));
		}

		$this->_load_tables('/banking4dolibarr/sql/');

		// Create extrafields
		include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		// v7.0.6
		$result = $extrafields->addExtraField('b4d_account_balance', 'Banking4DolibarrBankAccountBalance', 'price', 100, '', 'bank_account', 0, 0, '', null, 0, '0', 1, 0, '', '', 'banking4dolibarr@banking4dolibarr', '1', 'false');
		$result = $extrafields->addExtraField('b4d_account_update_date', 'Banking4DolibarrBankAccountUpdateDate', 'datetime', 101, '', 'bank_account', 0, 0, '', null, 0, '0', 1, 0, '', '', 'banking4dolibarr@banking4dolibarr', '1', 'false');

		// v7.0.19
		// Invoices or supplier invoices enabled
		if (!empty($conf->facture->enabled) || !empty($conf->fournisseur->enabled)) {
			$result = $extrafields->addExtraField('b4d_spe_name', 'Banking4DolibarrSpecificName', 'varchar', 100, '255', 'societe', 0, 0, '', null, 1, '', 1, 0, '', '', 'banking4dolibarr@banking4dolibarr', '1');
		}

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array(
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_reconcile_same_adp",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_reconcile_same_ad",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list_same_a",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_unpaid_list",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_payment_invoice",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_credit_note_invoice",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_deposit_invoice",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_payment_invoice_supplier",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_credit_note_invoice_supplier",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_deposit_invoice_supplier",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_payment_donation",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_payment_charge",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_payment_expensereport",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_payment_loan",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_company_name",
			"DROP VIEW IF EXISTS " . MAIN_DB_PREFIX . "banking4dolibarr_dates_options",
		);

		return $this->_remove($sql, $options);
	}
}

