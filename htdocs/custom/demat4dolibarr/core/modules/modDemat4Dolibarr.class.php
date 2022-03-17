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
 * 	\defgroup   demat4dolibarr     Module Demat4Dolibarr
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/demat4dolibarr/core/modules directory.
 *  \file       htdocs/demat4dolibarr/core/modules/modDemat4Dolibarr.class.php
 *  \ingroup    demat4dolibarr
 *  \brief      Description and activation file for module Demat4Dolibarr
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Demat4Dolibarr
 */
class modDemat4Dolibarr extends DolibarrModules
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
        $langs->load('opendsi@demat4dolibarr');

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 163028;        // TODO Go on page http://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'demat4dolibarr';

        $family = (!empty($conf->global->EASYA_VERSION) ? 'easya' : 'opendsi');
        // Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
        // It is used to group modules by family in module setup page
        $this->family = $family;
        // Module position in the family
        $this->module_position = 1;
        // Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        $this->familyinfo = array($family => array('position' => '001', 'label' => $langs->trans($family."Family")));
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;

        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Description of module Demat4Dolibarr";
        $this->descriptionlong = "";
        $this->editor_name = 'Open-DSI';
        $this->editor_url = 'http://www.open-dsi.fr';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '7.0.38';
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
	        'triggers' => 1,
	        'hooks' => array('invoicecard', 'invoicelist', 'thirdpartylist', 'customerlist'),
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/mymodule/temp");
        $this->dirs = array();

        // Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@demat4dolibarr");

        // Dependencies
        $this->hidden = false;              // A condition to hide module
        $this->depends = array('modAdvanceDictionaries');           // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();        // List of modules id to disable if this one is disabled
        $this->conflictwith = array();      // List of modules id this module is in conflict with
        $this->phpmin = array(5, 0);                    // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(7, 0);    // Minimum version of Dolibarr required by module
        $this->langfiles = array("demat4dolibarr@demat4dolibarr", "opendsi@demat4dolibarr");
        $langs->load('demat4dolibarr@demat4dolibarr');

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
        //                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
        // );
        $this->const = array(
            0 => array('DEMAT4DOLIBARR_BOX_SHOW_ONLY_STATUS_WHO_HAVE_INVOICE', 'chaine', '1', '', 0, 'current', 0),
            1 => array('DEMAT4DOLIBARR_INVOICE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS_IF_NO_FILES', 'chaine', '1', '', 0, 'current', 0),
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
        // 'member'           to add a tab in fundation member view
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
        $this->tabs = array();

        if (!isset($conf->demat4dolibarr) || !isset($conf->demat4dolibarr->enabled)) {
            $conf->demat4dolibarr = new stdClass();
            $conf->demat4dolibarr->enabled = 0;
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
	        0 => array('file' => 'box_chorus_invoice@demat4dolibarr', 'note' => $langs->trans('Demat4DolibarrBoxInvoiceChorus')),
        );            // List of boxes
        // Example:
        //$this->boxes=array(
        //    0=>array('file'=>'myboxa.php@mymodule','note'=>'','enabledbydefaulton'=>'Home'),
        //    1=>array('file'=>'myboxb.php@mymodule','note'=>''),
        //    2=>array('file'=>'myboxc.php@mymodule','note'=>'')
        //);

        // Cronjobs
	    $this->cronjobs = array(
		    0 => array('label' => $langs->trans('Demat4DolibarrCronUpdateAllJobStatus'), 'jobtype' => 'method', 'class' => '/demat4dolibarr/class/ededoc.class.php', 'objectname' => 'EdeDoc', 'method' => 'cronRefreshAllJobStatus', 'parameters' => '', 'comment' => '', 'frequency' => 1, 'unitfrequency' => 3600*24, 'test' => true),
	    );
	    // List of cron jobs entries to add
        // Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'test'=>true),
        //                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'test'=>true)
        // );

        // Permissions
        $this->rights = array();        // Permission array used by this module
        $r = 0;

        $this->rights[$r][0] = 163059;
        $this->rights[$r][1] = 'Envoi chorus';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'chorus';
        $r++;

        $this->rights[$r][0] = 163068;
        $this->rights[$r][1] = 'Acces au widget "Factures Chorus"';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'widget_chorus_invoice';
        $r++;

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

        // Add here entries to declare new menus
        //
        // Example to declare a new Top Menu entry and its Left menu entry:
        // $this->menu[$r]=array(	'fk_menu'=>'',			                // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
        //							'type'=>'top',			                // This is a Top menu entry
        //							'titre'=>'MyModule top menu',
        //							'mainmenu'=>'mymodule',
        //							'leftmenu'=>'mymodule',
        //							'url'=>'/mymodule/pagetop.php',
        //							'langs'=>'mylangfile@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
        //							'position'=>100,
        //							'enabled'=>'$conf->mymodule->enabled',	// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
        //							'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
        //							'target'=>'',
        //							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
        // $r++;
        //
        // Example to declare a Left Menu entry into an existing Top menu entry:
        // $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=xxx',		    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
        //							'type'=>'left',			                // This is a Left menu entry
        //							'titre'=>'MyModule left menu',
        //							'mainmenu'=>'xxx',
        //							'leftmenu'=>'mymodule',
        //							'url'=>'/mymodule/pagelevel2.php',
        //							'langs'=>'mylangfile@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
        //							'position'=>100,
        //							'enabled'=>'$conf->mymodule->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
        //							'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
        //							'target'=>'',
        //							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
        // $r++;

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
		global $conf, $langs;
		$langs->load('demat4dolibarr@demat4dolibarr');

		$sql = array(
			array('sql' => "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "c_demat4dolibarr_billing_mode (`code`, `label`) VALUES" .
				" ('A1', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA1')) . "')," .
				" ('A2', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA2')) . "')," .
				" ('A3', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA3')) . "')," .
				" ('A4', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA4')) . "')," .
				" ('A5', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA5')) . "')," .
				" ('A6', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA6')) . "')," .
				" ('A7', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA7')) . "')," .
				" ('A8', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA8')) . "')," .
				" ('A9', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA9')) . "')," .
				" ('A10', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA10')) . "')," .
				" ('A12', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA12')) . "')," .
				" ('A13', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA13')) . "')," .
				" ('A14', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA14')) . "')," .
				" ('A15', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA15')) . "')," .
				" ('A16', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA16')) . "')," .
				" ('A17', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA17')) . "')," .
				" ('A18', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA18')) . "')," .
				" ('A19', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA19')) . "')," .
				" ('A20', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA20')) . "')," .
				" ('A21', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA21')) . "')," .
				" ('A22', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA22')) . "')," .
				" ('A23', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA23')) . "')," .
				" ('A24', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA24')) . "')," .
				" ('A25', '" . $this->db->escape($langs->trans('Demat4DolibarrBillingModeA25')) . "');",
			),
			array('sql' => "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "c_demat4dolibarr_job_status (`position`, `code`, `label`, `short_label`, `can_resend`) VALUES" .
				" (1, 'S', '" . $this->db->escape($langs->trans('Demat4DolibarrJobStatusS')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortJobStatusS')) . "', NULL)," .
				" (2, 'E', '" . $this->db->escape($langs->trans('Demat4DolibarrJobStatusE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortJobStatusE')) . "', NULL)," .
				" (3, 'I', '" . $this->db->escape($langs->trans('Demat4DolibarrJobStatusI')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortJobStatusI')) . "', NULL)," .
				" (4, 'C', '" . $this->db->escape($langs->trans('Demat4DolibarrJobStatusC')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortJobStatusC')) . "', NULL)," .
				" (5, 'X', '" . $this->db->escape($langs->trans('Demat4DolibarrJobStatusX')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortJobStatusX')) . "', 1)," .
				" (6, 'F', '" . $this->db->escape($langs->trans('Demat4DolibarrJobStatusF')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortJobStatusF')) . "', 1)," .
				" (7, 'A', '" . $this->db->escape($langs->trans('Demat4DolibarrJobStatusA')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortJobStatusA')) . "', 1);",
			),
			array('sql' => "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "c_demat4dolibarr_chorus_status (`position`, `code`, `label`, `short_label`, `can_resend`) VALUES" .
				" (1, 'Q', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusQ')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusQ')) . "', NULL)," .
				" (2, 'E', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusE')) . "', 1)," .
				" (3, 'IN_RECU', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_RECU')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_RECU')) . "', NULL)," .
				" (4, 'IN_TRAITE_SE_CPP', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_TRAITE_SE_CPP')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_TRAITE_SE_CPP')) . "', NULL)," .
				" (5, 'IN_EN_ATTENTE_TRAITEMENT_CPP', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_EN_ATTENTE_TRAITEMENT_CPP')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_EN_ATTENTE_TRAITEMENT_CPP')) . "', NULL)," .
				" (6, 'IN_EN_COURS_TRAITEMENT_CPP', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_EN_COURS_TRAITEMENT_CPP')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_EN_COURS_TRAITEMENT_CPP')) . "', NULL)," .
				" (7, 'IN_INCIDENTE', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_INCIDENTE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_INCIDENTE')) . "', 1)," .
				" (8, 'IN_REJETE', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_REJETE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_REJETE')) . "', 1)," .
				" (9, 'IN_EN_ATTENTE_RETRAITEMENT_CPP', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_EN_ATTENTE_RETRAITEMENT_CPP')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_EN_ATTENTE_RETRAITEMENT_CPP')) . "', NULL)," .
				" (10, 'IN_DEPOT_PORTAIL_EN_ATTENTE_TRAITEMENT_SE_CPP', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_DEPOT_PORTAIL_EN_ATTENTE_TRAITEMENT_SE_CPP')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_DEPOT_PORTAIL_EN_ATTENTE_TRAITEMENT_SE_CPP')) . "', NULL)," .
				" (11, 'IN_INTEGRE', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_INTEGRE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_INTEGRE')) . "', NULL)," .
				" (12, 'IN_INTEGRE_PARTIEL', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_INTEGRE_PARTIEL')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_INTEGRE_PARTIEL')) . "', NULL)," .
				" (13, 'IN_A_RELANCER', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_A_RELANCER')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_A_RELANCER')) . "', 1)," .
				" (14, 'IN_ERREUR_INTERNE', '" . $this->db->escape($langs->trans('Demat4DolibarrChorusStatusIN_ERREUR_INTERNE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortChorusStatusIN_ERREUR_INTERNE')) . "', 1);",
			),
            array('sql' => "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "c_demat4dolibarr_invoice_status (`position`, `code`, `label`, `short_label`, `can_resend`) VALUES" .
                " (1, 'DEPOSEE', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusDEPOSEE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusDEPOSEE')) . "', NULL)," .
                " (2, 'EN_COURS_ACHEMINEMENT', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusEN_COURS_ACHEMINEMENT')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusEN_COURS_ACHEMINEMENT')) . "', NULL)," .
                " (3, 'MISE_A_DISPOSITION', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusMISE_A_DISPOSITION')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusMISE_A_DISPOSITION')) . "', NULL)," .
                " (4, 'A_RECYCLER', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusA_RECYCLER')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusA_RECYCLER')) . "', 1)," .
                " (5, 'REJETEE', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusREJETEE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusREJETEE')) . "', 1)," .
                " (6, 'SUSPENDUE', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusSUSPENDUE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusSUSPENDUE')) . "', 1)," .
                " (7, 'COMPLETEE', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusCOMPLETEE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusCOMPLETEE')) . "', NULL)," .
                " (8, 'SERVICE_FAIT', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusSERVICE_FAIT')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusSERVICE_FAIT')) . "', NULL)," .
                " (9, 'MANDATEE', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusMANDATEE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusMANDATEE')) . "', NULL)," .
                " (10, 'MISE_A_DISPOSITION_COMPTABLE', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusMISE_A_DISPOSITION_COMPTABLE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusMISE_A_DISPOSITION_COMPTABLE')) . "', NULL)," .
                " (11, 'MISE_EN_PAIEMENT', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusMISE_EN_PAIEMENT')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusMISE_EN_PAIEMENT')) . "', NULL)," .
                " (12, 'COMPTABILISEE', '" . $this->db->escape($langs->trans('Demat4DolibarrInvoiceStatusCOMPTABILISEE')) . "', '" . $this->db->escape($langs->trans('Demat4DolibarrShortInvoiceStatusCOMPTABILISEE')) . "', NULL);",
            ),
		);

		// Create extrafields
		include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'facture', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'facture', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'facture', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_billing_mode', $langs->trans('Demat4DolibarrBillingMode'), 'sellist', 1103, '', 'facture', 0, 0, '', array('options' => array('c_demat4dolibarr_billing_mode:code|label:code::active=1' => null)), 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_job_id', $langs->trans('Demat4DolibarrJobId'), 'varchar', 1104, 36, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_job_id"]', '');
		$result = $extrafields->addExtraField('d4d_job_status', $langs->trans('Demat4DolibarrJobStatus'), 'varchar', 1105, 2000, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_job_status"]', '');
		$result = $extrafields->addExtraField('d4d_separator_service', $langs->trans('Demat4DolibarrSeparatorServiceChorus'), 'separate', 1100, '', 'socpeople', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_service_code', $langs->trans('Demat4DolibarrServiceCode'), 'varchar', 1101, 100, 'socpeople', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_separator_validator', $langs->trans('Demat4DolibarrSeparatorValidatorChorus'), 'separate', 1200, '', 'socpeople', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_validator_type_id', $langs->trans('Demat4DolibarrValidatorTypeId'), 'sellist', 1201, '', 'socpeople', 0, 0, '', array('options' => array('c_demat4dolibarr_validator_type_id:code|label:code::active=1' => null)), 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_validator_id', $langs->trans('Demat4DolibarrValidatorId'), 'varchar', 1202, 50, 'socpeople', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_validator_name', $langs->trans('Demat4DolibarrValidatorName'), 'varchar', 1203, 150, 'socpeople', 0, 0, '', null, 1, '', 1, 0, '', '');

		// v7.0.1
//		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'facture', 0, 0, 1100, null, 0, '', 3, 0, 0, '', '');
//		$result = $extrafields->update('d4d_job_status', $langs->trans('Demat4DolibarrJobStatus'), 'varchar', 64, 'facture', 0, 0, 1104, null, 0, '', 0, 1, '', '$object->array_options["options_d4d_job_status"]', '');
		$result = $extrafields->addExtraField('d4d_job_status_label', $langs->trans('Demat4DolibarrJobStatusLabel'), 'varchar', 1104, 2000, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_job_status_label"]', '');
		$result = $extrafields->addExtraField('d4d_chorus_status', $langs->trans('Demat4DolibarrChorusStatus'), 'varchar', 1105, 64, 'facture', 0, 0, '', '', 0, '', 0, 1, '$object->array_options["options_d4d_chorus_status"]', '');
		$result = $extrafields->addExtraField('d4d_chorus_status_label', $langs->trans('Demat4DolibarrChorusStatusLabel'), 'varchar', 1105, 2000, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_chorus_status_label"]', '');
		$result = $extrafields->addExtraField('d4d_tech_separator', $langs->trans('Demat4DolibarrSeparatorChorusTech'), 'separate', 1106, '', 'facture', 0, 0, '',  array('options' => array('2' => null)), 0, '', 3, 0, '', '');
//		$result = $extrafields->update('d4d_job_id', $langs->trans('Demat4DolibarrJobId'), 'varchar', 36, 'facture', 0, 0, 1107, null, 0, '', 1, 0, '', '$object->array_options["options_d4d_job_id"]', '');
		$result = $extrafields->addExtraField('d4d_job_workflow_name', $langs->trans('Demat4DolibarrJobWorkflowName'), 'varchar', 1108, 255, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_job_workflow_name"]', '');
		$result = $extrafields->addExtraField('d4d_job_owner', $langs->trans('Demat4DolibarrJobOwner'), 'varchar', 1109, 255, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_job_owner"]', '');
		$result = $extrafields->addExtraField('d4d_job_create_on', $langs->trans('Demat4DolibarrJobCreatedOn'), 'varchar', 1110, 32, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_job_create_on"]', '');
		$result = $extrafields->addExtraField('d4d_job_suspension_reason', $langs->trans('Demat4DolibarrJobSuspensionReason'), 'varchar', 1111, 2000, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_job_suspension_reason"]', '');
		$result = $extrafields->addExtraField('d4d_chorus_id', $langs->trans('Demat4DolibarrChorusId'), 'varchar', 1112, 36, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_chorus_id"]', '');
		$result = $extrafields->addExtraField('d4d_chorus_submit_date', $langs->trans('Demat4DolibarrChorusSubmitDate'), 'varchar', 1113, 32, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_chorus_submit_date"]', '');
		$result = $extrafields->addExtraField('d4d_chorus_status_error_message', $langs->trans('Demat4DolibarrChorusStatusErrorMessage'), 'varchar', 1114, 2000, 'facture', 0, 0, '', '', 0, '', 1, 0, '$object->array_options["options_d4d_chorus_status_error_message"]', '');

		// v7.0.2
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'facture', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
//		$result = $extrafields->update('d4d_job_status', $langs->trans('Demat4DolibarrJobStatus'), 'varchar', 64, 'facture', 0, 0, 1104, null, 0, '', 0, 1, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_job_status_label', $langs->trans('Demat4DolibarrJobStatusLabel'), 'text', '', 'facture', 0, 0, 1104, null, 0, 0, 1, 0, 0, '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_chorus_status', $langs->trans('Demat4DolibarrChorusStatus'), 'varchar', 64, 'facture', 0, 0, 1105, null, 0, '', 0, 1, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_chorus_status_label', $langs->trans('Demat4DolibarrChorusStatusLabel'), 'text', '', 'facture', 0, 0, 1105, null, 0, '', 1, 0, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_tech_separator', $langs->trans('Demat4DolibarrSeparatorChorusTech'), 'separate', '', 'facture', 0, 0, 1106, array('options' => array('2' => null)), 0, 0, 3, 0, 0, '', '');
//		$result = $extrafields->update('d4d_job_id', $langs->trans('Demat4DolibarrJobId'), 'varchar', 36, 'facture', 0, 0, 1107, null, 0, '', 1, 0, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_job_workflow_name', $langs->trans('Demat4DolibarrJobWorkflowName'), 'varchar', 255, 'facture', 0, 0, 1108, null, 0, '', 1, 0, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_job_owner', $langs->trans('Demat4DolibarrJobOwner'), 'varchar', 255, 'facture', 0, 0, 1109, null, 0, '', 1, 0, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_job_create_on', $langs->trans('Demat4DolibarrJobCreatedOn'), 'datetime', '', 'facture', 0, 0, 1110, null, 0, '', 1, 0, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_job_suspension_reason', $langs->trans('Demat4DolibarrJobSuspensionReason'), 'text', '', 'facture', 0, 0, 1111, null, 0, '', 1, 0, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_chorus_id', $langs->trans('Demat4DolibarrChorusId'), 'varchar', 36, 'facture', 0, 0, 1112, null, 0, '', 1, 0, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_chorus_submit_date', $langs->trans('Demat4DolibarrChorusSubmitDate'), 'datetime', '', 'facture', 0, 0, 1113, null, 0, '', 1, 0, '', '', '', '', '1', '0');
//		$result = $extrafields->update('d4d_chorus_status_error_message', $langs->trans('Demat4DolibarrChorusStatusErrorMessage'), 'text', '', 'facture', 0, 0, 1114, null, 0, '', 1, 0, '', '', '', '', '1', '0');

		// v7.0.3
//		$result = $extrafields->update('d4d_job_status', $langs->trans('Demat4DolibarrJobStatus'), 'sellist', '', 'facture', 0, 0, 1104, array('options' => array('c_demat4dolibarr_job_status:label:code::active=1' => null)), 0, '', 1, 0, '', '', '', '', '1', '0');
		$result = $extrafields->delete('d4d_job_status_label', 'facture');
//		$result = $extrafields->update('d4d_chorus_status', $langs->trans('Demat4DolibarrChorusStatus'), 'sellist', '', 'facture', 0, 0, 1105, array('options' => array('c_demat4dolibarr_chorus_status:label:code::active=1' => null)), 0, '', 1, 0, '', '', '', '', '1', '0');
		$result = $extrafields->delete('d4d_chorus_status_label', 'facture');

		// v7.0.4
//		$result = $extrafields->update('d4d_job_status', $langs->trans('Demat4DolibarrJobStatus'), 'sellist', '', 'facture', 0, 0, 1104, array('options' => array('c_demat4dolibarr_job_status:label:code::active=1' => null)), 0, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_chorus_status', $langs->trans('Demat4DolibarrChorusStatus'), 'sellist', '', 'facture', 0, 0, 1105, array('options' => array('c_demat4dolibarr_chorus_status:label:code::active=1' => null)), 0, '', 1, 0, '', '', '', '', '1');
		$result = $extrafields->addExtraField('d4d_invoice_status', $langs->trans('Demat4DolibarrInvoiceStatus'), 'sellist', 1106, '', 'facture', 0, 0, '', array('options' => array('c_demat4dolibarr_invoice_status:label:code::active=1' => null)), 0, '', 1, 0, '', '', '', '1');
//		$result = $extrafields->update('d4d_tech_separator', $langs->trans('Demat4DolibarrSeparatorChorusTech'), 'separate', '', 'facture', 0, 0, 1107, array('options' => array('2' => null)), 0, 0, 3, 0, 0, '', '', '', '1');
//		$result = $extrafields->update('d4d_job_id', $langs->trans('Demat4DolibarrJobId'), 'varchar', 36, 'facture', 0, 0, 1108, null, 0, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_job_workflow_name', $langs->trans('Demat4DolibarrJobWorkflowName'), 'varchar', 255, 'facture', 0, 0, 1109, null, 0, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_job_owner', $langs->trans('Demat4DolibarrJobOwner'), 'varchar', 255, 'facture', 0, 0, 1110, null, 0, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_job_create_on', $langs->trans('Demat4DolibarrJobCreatedOn'), 'datetime', '', 'facture', 0, 0, 1111, null, 0, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_job_suspension_reason', $langs->trans('Demat4DolibarrJobSuspensionReason'), 'text', '', 'facture', 0, 0, 1112, null, 0, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_chorus_id', $langs->trans('Demat4DolibarrChorusId'), 'varchar', 36, 'facture', 0, 0, 1113, null, 0, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_chorus_submit_date', $langs->trans('Demat4DolibarrChorusSubmitDate'), 'datetime', '', 'facture', 0, 0, 1114, null, 0, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_chorus_status_error_message', $langs->trans('Demat4DolibarrChorusStatusErrorMessage'), 'text', '', 'facture', 0, 0, 1115, null, 0, '', 1, 0, '', '', '', '', '1');

		// v7.0.5
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'commande', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'commande', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'commande', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'contrat', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'contrat', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'contrat', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'expedition', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'expedition', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'expedition', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'facture_rec', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'facture_rec', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'facture_rec', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'fichinter', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'fichinter', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'fichinter', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'projet', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'projet', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'projet', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'propal', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'propal', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'propal', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', 1100, '', 'requestmanager', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_promise_code', $langs->trans('Demat4DolibarrPromiseCode'), 'varchar', 1101, 50, 'requestmanager', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->addExtraField('d4d_contract_number', $langs->trans('Demat4DolibarrContractNumber'), 'varchar', 1102, 50, 'requestmanager', 0, 0, '', null, 1, '', 1, 0, '', '');
		$result = $extrafields->update('d4d_billing_mode', $langs->trans('Demat4DolibarrBillingMode'), 'sellist', '', 'facture', 0, 0, 1103, array('options' => array('c_demat4dolibarr_billing_mode:code|label:rowid::active=1' => null)), 1, '', 1, 0, '', '', '');
//		$result = $extrafields->update('d4d_job_status', $langs->trans('Demat4DolibarrJobStatus'), 'sellist', '', 'facture', 0, 0, 1104, array('options' => array('c_demat4dolibarr_job_status:label:rowid::active=1' => null)), 1, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_chorus_status', $langs->trans('Demat4DolibarrChorusStatus'), 'sellist', '', 'facture', 0, 0, 1105, array('options' => array('c_demat4dolibarr_chorus_status:label:rowid::active=1' => null)), 1, '', 1, 0, '', '', '', '', '1');
//		$result = $extrafields->update('d4d_invoice_status', $langs->trans('Demat4DolibarrInvoiceStatus'), 'sellist', '', 'facture', 0, 0, 1106, array('options' => array('c_demat4dolibarr_invoice_status:label:rowid::active=1' => null)), 1, '', 1, 0, '', '', '', '', '1');
		$result = $extrafields->update('d4d_validator_type_id', $langs->trans('Demat4DolibarrValidatorTypeId'), 'sellist', '', 'socpeople', 0, 0, 1201, array('options' => array('c_demat4dolibarr_validator_type_id:code|label:rowid::active=1' => null)), 1, '', 1, 0, '', '', '');

        // v7.0.10
        $hidden_tech_block = empty($conf->global->DEMAT4DOLIBARR_DEBUG);
//        $result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'commande', 0, 0, 1100, null, 0, '', 3, 0, '', '', '');
//        $result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'contrat', 0, 0, 1100, null, 0, '', 3, 0, '', '', '');
//        $result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'expedition', 0, 0, 1100, null, 0, '', 3, 0, '', '', '');
//        $result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'facture_rec', 0, 0, 1100, null, 0, '', 3, 0, '', '', '');
//        $result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'fichinter', 0, 0, 1100, null, 0, '', 3, 0, '', '', '');
//        $result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'projet', 0, 0, 1100, null, 0, '', 3, 0, '', '', '');
//        $result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'propal', 0, 0, 1100, null, 0, '', 3, 0, '', '', '');
//        $result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'requestmanager', 0, 0, 1100, null, 0, '', 3, 0, '', '', '');
        $result = $extrafields->update('d4d_job_create_on', $langs->trans('Demat4DolibarrJobCreatedOn'), 'datetime', '', 'facture', 0, 0, 1104, null, 0, '', 1, 0, '', '', '', '', '1');
        $result = $extrafields->update('d4d_chorus_status', $langs->trans('Demat4DolibarrChorusStatus'), 'sellist', '', 'facture', 0, 0, 1105, array('options' => array('c_demat4dolibarr_chorus_status:short_label:rowid::active=1' => null)), 1, '', 1, 0, '', '', '', '', '1');
        $result = $extrafields->update('d4d_invoice_status', $langs->trans('Demat4DolibarrInvoiceStatus'), 'sellist', '', 'facture', 0, 0, 1106, array('options' => array('c_demat4dolibarr_invoice_status:short_label:rowid::active=1' => null)), 1, '', 1, 0, '', '', '', '', '1');
        $result = $extrafields->update('d4d_tech_separator', $langs->trans('Demat4DolibarrSeparatorChorusTech'), 'separate', '', 'facture', 0, 0, 1107, array('options' => array('2' => null)), 0, 0, $hidden_tech_block ? 0 : 3, 0, 0, '', '', '', '1');
        $result = $extrafields->update('d4d_job_id', $langs->trans('Demat4DolibarrJobId'), 'varchar', 36, 'facture', 0, 0, 1108, null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '', '1');
        $result = $extrafields->delete('d4d_job_workflow_name', 'facture');
        $result = $extrafields->update('d4d_job_owner', $langs->trans('Demat4DolibarrJobOwner'), 'varchar', 255, 'facture', 0, 0, 1109, null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '', '1');
        $result = $extrafields->update('d4d_job_status', $langs->trans('Demat4DolibarrJobStatus'), 'sellist', '', 'facture', 0, 0, 1110, array('options' => array('c_demat4dolibarr_job_status:short_label:rowid::active=1' => null)), 1, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '', '1');
        $result = $extrafields->update('d4d_job_suspension_reason', $langs->trans('Demat4DolibarrJobSuspensionReason'), 'text', '', 'facture', 0, 0, 1111, null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '', '1');
        $result = $extrafields->update('d4d_chorus_id', $langs->trans('Demat4DolibarrChorusId'), 'varchar', 36, 'facture', 0, 0, 1112, null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '', '1');
        $result = $extrafields->addExtraField('d4d_chorus_invoice_id', $langs->trans('Demat4DolibarrChorusInvoiceId'), 'int', 1113, 10, 'facture', 0, 0, '', null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '1');
        $result = $extrafields->update('d4d_chorus_submit_date', $langs->trans('Demat4DolibarrChorusSubmitDate'), 'datetime', '', 'facture', 0, 0, 1114, null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '', '1');
        $result = $extrafields->update('d4d_chorus_status_error_message', $langs->trans('Demat4DolibarrChorusStatusErrorMessage'), 'text', '', 'facture', 0, 0, 1115, null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '', '1');
        $result = $extrafields->addExtraField('d4d_invoice_send_to_chorus', $langs->trans('Demat4DolibarrInvoicesSendToChorus'), 'boolean', 1000, '', 'societe', 0, 0, '', null, 1, '', 1, 0, '', '', '', '1');

        // v7.0.15
        $result = $extrafields->update('d4d_separator_service', $langs->trans('Demat4DolibarrSeparatorServiceChorus'), 'separate', '', 'socpeople', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
        $result = $extrafields->update('d4d_separator_validator', $langs->trans('Demat4DolibarrSeparatorValidatorChorus'), 'separate', '', 'socpeople', 0, 0, 1200, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');

		// v7.0.30
		$result = $extrafields->addExtraField('d4d_invoice_id', $langs->trans('Demat4DolibarrInvoiceId'), 'varchar', 1116, 36, 'facture', 0, 0, '', null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '1');
		$result = $extrafields->addExtraField('d4d_invoice_create_on', $langs->trans('Demat4DolibarrInvoiceCreateOn'), 'datetime', 1117, '', 'facture', 0, 0, '', null, 0, '', $hidden_tech_block ? 0 : 1, 0, '', '', '', '1');

		// v7.0.32
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'commande', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'contrat', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'expedition', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'facture_rec', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'fichinter', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'projet', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'propal', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');
		$result = $extrafields->update('d4d_separator', $langs->trans('Demat4DolibarrSeparatorChorus'), 'separate', '', 'requestmanager', 0, 0, 1100, array('options' => array('2' => null)), 0, '', 3, 0, '', '', '');

		// Create tables of all dictionaries
		if (dol_include_once('/advancedictionaries/class/dictionary.class.php')) {
			$dictionaries = Dictionary::fetchAllDictionaries($this->db, 'demat4dolibarr');
			foreach ($dictionaries as $dictionary) {
				if ($dictionary->createTables() < 0) {
					setEventMessage('Error create dictionary table: ' . $dictionary->errorsToString(), 'errors');
					return 0;
				}
			}
		} else {
		    setEventMessage($langs->trans('Demat4DolibarrErrorModuleNotFound', "AdvanceDictionaries").' ' .$langs->trans('Demat4DolibarrErrorModuleNotFoundDownloadHere', 'https://github.com/OPEN-DSI/dolibarr_module_advancedictionaries'), 'errors');
		    return 0;
        }

		$this->_load_tables('/demat4dolibarr/sql/');

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
		$sql = array();

        return $this->_remove($sql, $options);
	}
}

