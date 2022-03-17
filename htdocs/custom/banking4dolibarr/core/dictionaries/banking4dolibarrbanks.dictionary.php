<?php
/* Copyright (C) 2019  Open-Dsi <support@open-dsi.fr>
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
 * \file        core/dictionaries/banking4dolibarrbanks.dictionary.php
 * \ingroup     banking4dolibarr
 * \brief       Class of the dictionary Banks
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for Banking4DolibarrBanksDictionary
 */
class Banking4DolibarrBanksDictionary extends Dictionary
{
    /**
     * @var int         Version of this dictionary
     */
    public $version = 3;

    /**
     * @var array       List of languages to load
     */
    public $langs = array('banking4dolibarr@banking4dolibarr', 'banks');

    /**
     * @var string      Family name of which this dictionary belongs
     */
    public $family = 'banking4dolibarr';

    /**
     * @var string      Family label for show in the list, translated if key found
     */
    public $familyLabel = 'Module163036Name';

    /**
     * @var int         Position of the dictionary into the family
     */
    public $familyPosition = 1;

    /**
     * @var string      Module name of which this dictionary belongs
     */
    public $module = 'banking4dolibarr';

    /**
     * @var string      Module name for show in the list, translated if key found
     */
    public $moduleLabel = 'Module163036Name';

    /**
     * @var string      Name of this dictionary for show in the list, translated if key found
     */
    public $nameLabel = 'Banking4DolibarrBanksDictionaryLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_banking4dolibarr_bank';

    /**
     * @var array  Fields of the dictionary table
     * 'name' => array(
     *   'name'       => string,         // Name of the field
     *   'label'      => string,         // Label of the field, translated if key found
     *   'type'       => string,         // Type of the field (varchar, text, int, double, date, datetime, boolean, price, phone, mail, url,
     *                                                         password, select, sellist, radio, checkbox, chkbxlst, link, custom)
     *   'database' => array(            // Description of the field in the database always rewrite default value if set
     *     'type'      => string,        // Data type
     *     'length'    => string,        // Length of the data type (require)
     *     'default'   => string,        // Default value in the database
     *   ),
     *   'is_require' => bool,           // Set at true if this field is required
     *   'options'    => array()|string, // Parameters same as extrafields (ex: 'table:label:rowid::active=1' or array(1=>'value1', 2=>'value2') )
     *                                      string: sellist, chkbxlst, link | array: select, radio, checkbox
     *                                      The key of the value must be not contains the character ',' and for chkbxlst it's a rowid
     *   'is_not_show'       => bool,    // Set at true if this field is not show must be set at true if you want to search or edit
     *   'td_title'          => array (
     *      'moreClasses'    => string,  // Add more classes in the title balise td
     *      'moreAttributes' => string,  // Add more attributes in the title balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'td_output'         => array (
     *      'moreClasses'    => string,  // Add more classes in the output balise td
     *      'moreAttributes' => string,  // Add more attributes in the output balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_output'       => array (
     *      'moreAttributes' => string,  // Add more attributes in when show output field
     *   ),
     *   'is_not_searchable' => bool,    // Set at true if this field is not searchable
     *   'td_search'         => array (
     *      'moreClasses'    => string,  // Add more classes in the search input balise td
     *      'moreAttributes' => string,  // Add more attributes in the search input balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_search_input' => array (
     *      'size'           => int,     // Size attribute of the search input field (input text)
     *      'moreClasses'    => string,  // Add more classes in the search input field
     *      'moreAttributes' => string,  // Add more attributes in the search input field
     *   ),
     *   'is_not_addable'    => bool,    // Set at true if this field is not addable
     *   'is_not_editable'   => bool,    // Set at true if this field is not editable
     *   'td_input'         => array (
     *      'moreClasses'    => string,  // Add more classes in the input balise td
     *      'moreAttributes' => string,  // Add more attributes in the input balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_input'        => array (
     *      'moreClasses'    => string,  // Add more classes in the input field
     *      'moreAttributes' => string,  // Add more attributes in the input field
     *   ),
     *   'help' => '',                   // Help text for this field or url, translated if key found
     *   'is_not_sortable'   => bool,    // Set at true if this field is not sortable
     *   'min'               => int,     // Value minimum (include) if type is int, double or price
     *   'max'               => int,     // Value maximum (include) if type is int, double or price
     * )
     */
    public $fields = array(
        'slug' => array(
            'name'       => 'slug',
            'label'      => 'Code',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 25,
            ),
        ),
        'label' => array(
            'name'       => 'label',
            'label'      => 'Label',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 255,
            ),
        ),
        'code' => array(
            'name'       => 'code',
            'label'      => 'Banking4DolibarrBankCode',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 128,
            ),
        ),
        'last_update' => array(
            'name'                      => 'last_update',
            'label'                     => 'DateLastModification',
            'type'                      => 'datetime',
            'show_column_by_default'    => false,
            'is_not_editable'           => true,
        ),
        'datas' => array(
            'name'                      => 'datas',
            'label'                     => 'Banking4DolibarrData',
            'type'                      => 'text',
            'show_column_by_default'    => false,
            'is_not_editable'           => true,
        ),
    );

    /**
     * @var array  List of index for the database
     * array(
     *   'fields'    => array( ... ), // List of field name who constitute this index
     *   'is_unique' => bool,         // Set at true if this index is unique
     * )
     */
    public $indexes = array(
    );

    /**
     * @var array  List of fields/indexes added, updated or deleted for a version
     * array(
     *   'version' => array(
     *     'fields' => array('field_name'=>'a', 'field_name'=>'u', ...), // List of field name who is added(a) or updated(u) for a version
     *     'deleted_fields' => array('field_name'=> array('name', 'type', other_custom_data_required_for_delete), ...), // List of field name who is deleted for a version
     *     'indexes' => array('idx_number'=>'u', 'idx_number'=>'d', ...), // List of indexes number who is updated(u) or deleted(d) for a version
	 *     'primary_key' => 'a' or 'u' or 'd', // The primary key is added(a) or updated(u) or deleted(d) for a version
     *   ),
     * )
     */
    public $updates = array(
    	1 => array(
			'primary_key' => 'u',
		),
		3 => array(
			'indexes' => array(
				0 => 'd',
			),
		),
    );

    /**
     * @var bool    Is rowid auto increment (false : rowid = defined by the option $is_rowid_defined_by_code)
     */
    public $is_rowid_auto_increment = false;

    /**
     * @var bool    Is rowid defined by code (true: rowid = $this->id of the DictionaryLine; false: rowid = 'last rowid in the table' + 1)
     */
    public $is_rowid_defined_by_code = true;

	/**
	 * @var array  List of fields composing the primary key
	 */
	public $primary_key = array('rowid', 'entity');

    /**
     * @var bool    Is multi entity (false = partaged, true = by entity)
     */
    public $is_multi_entity = true;

    /**
     * @var bool    Determine if lines can be added or not (must be defined in the function initialize())
     */
    public $lineCanBeAdded = false;

    /**
     * @var bool    Determine if lines can be updated or not (must be defined in the function initialize())
     */
    public $lineCanBeUpdated = false;

    /**
     * @var bool    Determine if lines can be deleted or not (must be defined in the function initialize())
     */
    public $lineCanBeDeleted = false;

    /**
     * @var bool    Determine if the rowid must be show in the list
     */
    public $showTechnicalID = true;

    /**
     * Determine if lines can be disabled or not
     *
     * @param  DictionaryLine   $dictionaryLine     Line instance
     * @return mixed                                =null: Show "Always active" text
     *                                              =true: Show button
     *                                              =string: Show the text returned, translated if key found
     *                                              other: Show disabled button
     */
    function isLineCanBeDisabled(&$dictionaryLine)
    {
        return false;
    }
}

/**
 * Class for Banking4DolibarrBanksDictionaryLine
 */
class Banking4DolibarrBanksDictionaryLine extends DictionaryLine
{
}