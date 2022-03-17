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
 * \file        core/dictionaries/banking4dolibarrbankaccounts.dictionary.php
 * \ingroup     banking4dolibarr
 * \brief       Class of the dictionary Bank Accounts
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for Banking4DolibarrBankAccountsDictionary
 */
class Banking4DolibarrBankAccountsDictionary extends Dictionary
{
    /**
     * @var int         Version of this dictionary
     */
    public $version = 3;

    /**
     * @var array       List of languages to load
     */
    public $langs = array('banking4dolibarr@banking4dolibarr', 'banks', 'bills');

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
    public $familyPosition = 0;

    /**
     * @var bool        Hide this dictionary in the list
     */
    public $hidden = true;

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
    public $nameLabel = 'Banking4DolibarrBankAccountsDictionaryLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_banking4dolibarr_bank_account';

    /**
     * @var string      Custom name for the title in the values list screen
     */
    public $customTitle = 'Banking4DolibarrBankAccountsDictionaryTitle';

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
        'label' => array(
            'name'       => 'label',
            'label'      => 'Label',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 255,
            ),
			'is_not_addable'	=> true,
            'is_not_editable'	=> true,
        ),
        'type_id' => array(),
        'currency_code' => array(
            'name'       => 'currency_code',
            'label'      => 'Currency',
            'type'       => 'sellist',
            'options'    => 'c_currencies:code_iso:code_iso::',
            'translate_prefix' => 'Currency',
			'is_not_addable'	=> true,
            'is_not_editable'	=> true,
        ),
        'bank_id' => array(),
        'bic' => array(
            'name'       => 'bic',
            'label'      => 'BICNumber',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 11,
            ),
			'is_not_addable' => true,
            'is_not_editable' => true,
        ),
        'iban' => array(
            'name'       => 'iban',
            'label'      => 'IBANNumber',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 34,   // full iban. 34 according to ISO 13616
            ),
			'is_not_addable'	=> true,
            'is_not_editable'	=> true,
        ),
        'fk_bank_account' => array(),
		'cron_refresh_bank_records' => array(
			'name'       => 'cron_refresh_bank_records',
			'label'      => 'Banking4DolibarrAutoRefreshBankRecords',
			'help'       => 'Banking4DolibarrAutoRefreshBankRecordsHelp',
			'type'       => 'boolean',
			'database' 	 => array(
			  'type'      => 'boolean',
			  'default'   => '1',
			),
		),
        'last_update' => array(
            'name'                      => 'last_update',
            'label'                     => 'DateLastModification',
            'type'                      => 'datetime',
            'show_column_by_default'    => false,
			'is_not_addable'			=> true,
            'is_not_editable'           => true,
        ),
        'datas' => array(
            'name'                      => 'datas',
            'label'                     => 'Banking4DolibarrData',
            'type'                      => 'text',
            'show_column_by_default'    => false,
			'is_not_addable'			=> true,
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
        0 => array(
            'fields'    => array('fk_bank_account'),
        ),
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
				'type_id' => 'u',
				'bank_id' => 'u',
				'fk_bank_account' => 'u',
			),
		),
		2 => array(
			'primary_key' => 'u',
		),
		3 => array(
			'fields' => array(
				'cron_refresh_bank_records' => 'a',
			),
		),
    );

    /**
     * @var bool    Is rowid auto increment (false : rowid = defined by the option $is_rowid_defined_by_code)
     */
    public $is_rowid_auto_increment = false;

	/**
	 * @var array  List of fields composing the primary key
	 */
	public $primary_key = array('rowid', 'entity');

    /**
     * @var bool    Is multi entity (false = partaged, true = by entity)
     */
    public $is_multi_entity = true;

	/**
	 * @var bool    Show the management of the entity of the dictionary lines (show column entity and the mass action for change the entity of the lines) (false = show, true = hide)
	 */
	public $show_entity_management = false;

    /**
     * @var bool    Determine if the rowid must be show in the list
     */
    public $showTechnicalID = true;

    /**
     * Initialize the dictionary
     *
     * @return  void
     */
    protected function initialize()
	{
		global $conf, $langs;

		$this->fields['type_id'] = array(
			'name' => 'type_id',
			'label' => 'AccountType',
			'type' => 'sellist',
			'database' => array(
				'type' => 'integer',
				'length' => '',
			),
			'options' => 'c_banking4dolibarr_bank_account_type:label:rowid::entity IN (' . $this->getEntity() . ')',
			'is_not_addable' => true,
			'is_not_editable' => true,
		);
		$this->fields['bank_id'] = array(
			'name' => 'bank_id',
			'label' => 'BankName',
			'type' => 'sellist',
			'database' => array(
				'type' => 'integer',
				'length' => '',
			),
			'options' => 'c_banking4dolibarr_bank:rowid|label:rowid::entity IN (' . $this->getEntity() . ')',
			'label_separator' => '.',
			'is_not_addable' => true,
			'is_not_editable' => true,
		);
		$this->fields['fk_bank_account'] = array(
			'name' => 'fk_bank_account',
			'label' => 'BankAccount',
			'type' => 'sellist',
			'database' => array(
				'type' => 'integer',
				'length' => '',
			),
			'options' => 'bank_account:label:rowid::clos=0 AND entity IN (' . getEntity('bank_account') . '):Account:/compta/bank/class/account.class.php',
		);

		if (!empty($conf->global->BANKING4DOLIBARR_MODULE_KEY)) {
			$this->customBackLink = '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=b4d_refresh_bank_accounts" >' . $langs->trans('Banking4DolibarrRefreshBankAccounts') . '</a>' .
				'<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=b4d_manage_bank_accounts" >' . $langs->trans('Banking4DolibarrManageBankAccounts') . '</a>';
		} else {
			$title_button = $langs->trans('Banking4DolibarrDisabledBecauseYouDontHaveKey');
			$this->customBackLink = '<a class="butActionRefused classfortooltip"  href="#" title="' . dol_escape_htmltag($title_button) . '">' . $langs->trans('Banking4DolibarrRefreshBankAccounts') . '</a>' .
				'<a class="butActionRefused classfortooltip"  href="#" title="' . dol_escape_htmltag($title_button) . '">' . $langs->trans('Banking4DolibarrManageBankAccounts') . '</a>';
		}
	}

    /**
     * Overwrite default actions of the dictionary template page (After the hook "doActions")
     *
     * @return  int                 <0 if KO, =0 if do default actions, >0 if don't do default actions
     */
    public function doActions()
	{
		global $conf, $action, $user;

		if (!empty($conf->global->BANKING4DOLIBARR_MODULE_KEY)) {
			if ($action == 'b4d_refresh_bank_accounts') {
				dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
				$budgetinsight = new BudgetInsight($this->db);

				$result = $budgetinsight->connection();
				if ($result > 0) $result = $budgetinsight->refreshBankAccounts($user);
				if ($result < 0) {
					setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
				} elseif (!empty($budgetinsight->errors) || !empty($budgetinsight->error)) {
					setEventMessages($budgetinsight->error, $budgetinsight->errors, 'warnings');
				}

				// Go to the webview page of Budget Insight
				header("Location: " . $_SERVER["PHP_SELF"]);
				exit;
			} elseif ($action == 'b4d_manage_bank_accounts') {
				// Go to the webview page of Budget Insight
				header("Location: " . dol_buildpath('/banking4dolibarr/core/modules/oauth/webview_callback.php', 1) . "?action=manage");
				exit;
			}
		}

		return 0;
	}

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
		global $conf;
		return $dictionaryLine->id < 0 || empty($conf->global->BANKING4DOLIBARR_MODULE_KEY);
	}

	/**
	 * Determine if lines can be updated or not
	 *
	 * @param  DictionaryLine   $dictionaryLine     Line instance
	 * @return bool
	 */
	public function isLineCanBeUpdated(&$dictionaryLine)
	{
		global $conf;
		return $dictionaryLine->id < 0 || ($dictionaryLine->id > 0 && !empty($conf->global->BANKING4DOLIBARR_MODULE_KEY));
	}

	/**
	 * Determine if lines can be deleted or not
	 *
	 * @param  DictionaryLine   $dictionaryLine     Line instance
	 * @return bool
	 */
	public function isLineCanBeDeleted(&$dictionaryLine)
	{
		return !$this->_hasBankRecords($dictionaryLine->id);
	}

	/**
	 *  Get last row ID of the dictionary
	 *
	 * @return  int              Last row ID
	 */
	public function getNextRowID()
	{
		$last_rowid = 0;
		$sql = 'SELECT MIN(' . $this->rowid_field . ') AS last_rowid FROM ' . MAIN_DB_PREFIX . $this->table_name;
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($obj = $this->db->fetch_object($resql)) {
				$last_rowid = $obj->last_rowid;
			}
		}

		return min(0, $last_rowid) - 1;
	}

	/**
	 *  Get last row ID of the dictionary
	 *
	 * @param 	int		$account_id		Account ID
	 * @return  bool
	 */
	protected function _hasBankRecords($account_id)
	{
		$nb = 0;

		$sql = 'SELECT COUNT(*) AS nb FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record WHERE id_account = ' . $account_id;
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($obj = $this->db->fetch_object($resql)) {
				$nb = $obj->nb;
			}
		}

		return $nb > 0;
	}
}

/**
 * Class for Banking4DolibarrBankAccountsDictionaryLine
 */
class Banking4DolibarrBankAccountsDictionaryLine extends DictionaryLine
{
}