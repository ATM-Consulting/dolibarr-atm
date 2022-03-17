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
 * \file        core/dictionaries/banking4dolibarrbankrecordcategories.dictionary.php
 * \ingroup     banking4dolibarr
 * \brief       Class of the dictionary Bank Record Categories
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for Banking4DolibarrBankRecordCategoriesDictionary
 */
class Banking4DolibarrBankRecordCategoriesDictionary extends Dictionary
{
    /**
     * @var int         Version of this dictionary
     */
    public $version = 4;

    /**
     * @var array       List of languages to load
     */
    public $langs = array('banking4dolibarr@banking4dolibarr', 'banks', 'accountancy');

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
    public $familyPosition = 3;

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
    public $nameLabel = 'Banking4DolibarrBankRecordCategoriesDictionaryLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_banking4dolibarr_bank_record_category';

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
        'id_parent_category' => array(),
        'label' => array(
            'name'       => 'label',
            'label'      => 'Label',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 255,
            ),
            'show_input' => array(
                'moreAttributes' => ' style="width:40%;"',
            ),
            'is_not_editable' => true,
        ),
        'color' => array(
            'name'       => 'color',
            'label'      => 'Color',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 6,
            ),
            'show_input' => array(
                'moreAttributes' => ' style="width:20%;"',
            ),
            'is_not_editable' => true,
        ),
        'category' => array(),
		'accountancy_code' => array(
			'name'       => 'accountancy_code',
			'label'      => 'AccountAccounting',
			'type'       => 'varchar',
			'database'   => array(
				'length'   => 32,
			),
			'show_input' => array(
				'moreAttributes' => ' style="width:20%;"',
			),
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
            'fields' => array(
                'id_parent_category' => 'a',
            ),
        ),
		2 => array(
			'fields' => array(
				'id_parent_category' => 'u',
				'category' => 'u',
			),
		),
		3 => array(
			'fields' => array(
				'accountancy_code' => 'a',
			),
		),
		4 => array(
			'primary_key' => 'u',
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

    /**
     * Initialize the dictionary
     *
     * @return  void
     */
    protected function initialize()
	{
		$this->fields['id_parent_category'] = array(
			'name' => 'id_parent_category',
			'label' => 'Parent',
			'type' => 'sellist',
			'database' => array(
				'type' => 'integer',
				'length' => '',
			),
			'options' => 'c_banking4dolibarr_bank_record_category:label:rowid::entity IN (' . $this->getEntity() . ')',
			'show_input' => array(
				'moreAttributes' => ' style="width:40%;"',
			),
			'is_not_editable' => true,
		);

		// Load bank groups
		require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/bankcateg.class.php';
		$bankcateg = new BankCateg($this->db);

		$categories = array();
		foreach ($bankcateg->fetchAll() as $bankcategory) {
			$categories[$bankcategory->id] = $bankcategory->label;
		}

		$this->fields['category'] = array(
			'name' => 'category',
			'label' => 'Category',
			'type' => 'select',
			'database' => array(
				'type' => 'integer',
				'length' => '',
			),
			'options' => $categories,
		);
	}
}

/**
 * Class for Banking4DolibarrBankRecordCategoriesDictionaryLine
 */
class Banking4DolibarrBankRecordCategoriesDictionaryLine extends DictionaryLine
{
	/**
	 * @var string[]		Accounting account label cached
	 */
	protected static $accounting_account_label_cached = array();

	/**
	 * Return HTML string to put an output field into a page
	 *
	 * @param   string	$fieldName      Name of the field
	 * @param   string	$value          Value to show
	 * @return	string					Formatted value
	 */
	public function showOutputFieldAD($fieldName, $value = null)
	{
		global $conf;

		if ($fieldName == 'accountancy_code' && !empty($conf->accounting->enabled) && !empty($this->dictionary->fields[$fieldName])) {
			if ($value === null) $value = $this->fields[$fieldName];

			if (!isset(self::$accounting_account_label_cached[$value])) {
				require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
				$accountingaccount = new AccountingAccount($this->db);
				$result = $accountingaccount->fetch('', $value, 1);
				if ($result > 0) {
					self::$accounting_account_label_cached[$value] = $accountingaccount->getNomUrl(0, 1, 1, '', 1);
				} else {
					if ($result < 0) {
						setEventMessages($accountingaccount->error, $accountingaccount->errors, 'errors');
					}
					self::$accounting_account_label_cached[$value] = $value;
				}
			}

			return self::$accounting_account_label_cached[$value];
		}

		return parent::showOutputFieldAD($fieldName, $value);
	}

	/**
	 * Return HTML string to put an input field into a page
	 *
	 * @param  string  $fieldName      		Name of the field
	 * @param  string  $value          		Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $keyprefix     	 	Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keysuffix      		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  int     $objectid       		Current object id
	 * @param  int     $options_only   		1: Return only the html output of the options of the select input
	 * @return string
	 */
	public function showInputFieldAD($fieldName, $value=null, $keyprefix='', $keysuffix='', $objectid=0, $options_only=0)
	{
		global $conf;

		if ($fieldName == 'accountancy_code' && !empty($conf->accounting->enabled)) {
			$field = $this->dictionary->fields[$fieldName];

			if ($value === null) $value = $this->fields[$fieldName];

			$required = !empty($field['is_require']);

			$fieldHtmlName = $keyprefix . $fieldName . $keysuffix;

			$moreClasses = trim($field['show_input']['moreClasses']);
			if (empty($moreClasses)) {
				$moreClasses = ' minwidth300 maxwidth300 maxwidthonsmartphone';
			} else {
				$moreClasses = ' ' . $moreClasses;
			}

			if (!empty($hidden)) {
				$out = '<input type="hidden" value="' . $value . '" id="' . $fieldHtmlName . '" name="' . $fieldHtmlName . '"/>';
			} else {
				global $formaccounting;
				if (!is_object($formaccounting)) {
					require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
					$formaccounting = New FormAccounting($this->db);
				}
				$out = $formaccounting->select_account($value, $fieldHtmlName, $required ? 0 : 1, null, 1, 1, $moreClasses);
			}

			return $out;
		}

		return parent::showInputFieldAD($fieldName, $value, $keyprefix, $keysuffix, $objectid, $options_only);
	}
}
