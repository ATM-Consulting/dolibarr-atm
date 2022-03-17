<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
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
 */

/**
 * 	\defgroup	associe	Associe module
 * 	\brief		Associe module descriptor.
 * 	\file		core/modules/modAssocie.class.php
 * 	\ingroup	associe
 * 	\brief		Description and activation file for module Associe
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module Associe
 */
class modAssocie extends DolibarrModules
{

    /**
     * 	Constructor. Define names, constants, directories, boxes, permissions
     *
     * 	@param	DoliDB		$db	Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

		$this->editor_name = 'Geolane';
        $this->editor_url = 'http://geolane.fr';
        // Id for module (must be unique).
        // Use a free id here
        // (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 419419; // 104000 to 104999 for ATM CONSULTING
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'associe';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "Geolane";
        // Module label (no space allowed)
        // used if translation string 'ModuleXXXName' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description
        // used if translation string 'ModuleXXXDesc' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->description = "Commande associe à partir d'une commande client";
        // Possible values for version are: 'development', 'experimental' or version
        $this->version = '0.3.1';
        // Key used in llx_const table to save module status enabled/disabled
        // (where ASSOCIE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Where to store the module in setup page
        // (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png
        // use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png
        // use this->picto='pictovalue@module'
        $this->picto = 'associe.png@associe'; // mypicto@associe
        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /associe/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /associe/core/modules/barcode)
        // for specific css file (eg: /associe/css/associe.css.php)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory
            'triggers' => 1,
            // Set this to 1 if module has its own login method directory
            //'login' => 0,
            // Set this to 1 if module has its own substitution function file
            //'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory
            //'menus' => 0,
            // Set this to 1 if module has its own barcode directory
            //'barcode' => 0,
            // Set this to 1 if module has its own models directory
            //'models' => 1,
            // Set this to relative path of css if module has its own css file
            //'css' => '/associe/css/mycss.css.php',
            // Set here all hooks context managed by module
            'hooks' => array(
                'ordercard',
                'ordersuppliercard'
            ),
            // Set here all workflow context managed by module
            //'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE'))
            'moduleforexternal' => 0,
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/associe/temp");
        $this->dirs = array();

        // Config pages. Put here list of php pages
        // stored into associe/admin directory, used to setup module.
        $this->config_page_url = array("associe_setup.php@associe");

        // Dependencies
        // List of modules id that must be enabled if this module is enabled
        $this->depends = array();
        // List of modules id to disable if this one is disabled
        $this->requiredby = array();
        // Minimum version of PHP required by module
        $this->phpmin = array(5, 3);
        // Minimum version of Dolibarr required by module
        $this->need_dolibarr_version = array(3, 2);
        $this->langfiles = array("associe@associe"); // langfiles@associe
        // Constants
        // List of particular constants to add when module is enabled
        // (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example:
        $this->const = array();

        // Array to add new pages in new tabs
        // Example:
        $this->tabs = array();

        // Dictionnaries
        if (! isset($conf->orderassociefromorder->enabled)) {
            $conf->orderassociefromorder=new stdClass();
            $conf->orderassociefromorder->enabled = 0;
        }
        $this->dictionnaries = array();


        // Boxes
        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array(); // Boxes list
        $r = 0;
        // Example:

        /*
          $this->boxes[$r][1] = "myboxb.php";
          $r++;
         */

        // Permissions
        $this->rights = array(); // Permission array used by this module
        $r = 0;

        $this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
        $this->rights[$r][1] = 'Convertir les commandes clients en commandes associes';  // Permission label
        $this->rights[$r][3] = 0;                   // Permission by default for new user (0/1)
        $this->rights[$r][4] = 'read';              // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
    	$this->rights[$r][5] = '';		    // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $r++;

        // Main menu entries
        $this->menus = array(); // List of menus to add
        $r = 0;

		$this->menu[]=array(   'fk_menu'=>'fk_mainmenu=of',     // Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
            'type'=>'left',         // This is a Left menu entry
            'titre'=>'ProductsToOrder',
            'mainmenu'=>'replenishGPAO',
            'leftmenu'=>'replenishGPAO',
            'url'=>'/associe/ordercustomer.php',
            'langs'=>'associe@associe',
            'position'=>300,
            'target'=>'',
            'user'=>2);


        // Exports
        $r = 1;


    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus
     * (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * 	@param		string	$options	Options when enabling module ('', 'noboxes')
     * 	@return		int					1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf;

        $sql = array();

        $result = $this->loadTables();

        include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        // $result = $extrafields->addExtraField('bfaClient', 'bfaClient', 'int', 100 , 10, 'societe', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 1, '', 1, 'Pourcentage de BFA Client', '', $conf->entity, 'associe@associe', '1', 0, 2);
        // $result = $extrafields->addExtraField('bfaChoise', 'bfaChoise', 'int', 100 , 10, 'societe', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 1, '', 1, '', '', $conf->entity, 'associe@associe', '1', 0, 2);
        $result = $extrafields->addExtraField('bfacomm', 'bfacomm', 'separate', 450 , '', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, '', 1, '', '', $conf->entity, 'associe@associe', '1', 0, 2);	
        $result = $extrafields->addExtraField('bfaClient', 'bfaClient', 'int', 500 , 10, 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 1, '', 1, '', '', $conf->entity, 'associe@associe', '1', 0, 2);
        $result = $extrafields->addExtraField('comm', 'comm', 'int', 500 , 10, 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 1, '', 1, '', '', $conf->entity, 'associe@associe', '1', 0, 2);	
        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * 	@param		string	$options	Options when enabling module ('', 'noboxes')
     * 	@return		int					1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }

    /**
     * Create tables, keys and data required by module
     * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * and create data commands must be stored in directory /associe/sql/
     * This function is called by this->init
     *
     * 	@return		int		<=0 if KO, >0 if OK
     */
    private function loadTables()
    {
        return $this->_load_tables('/associe/sql/');
    }
}
