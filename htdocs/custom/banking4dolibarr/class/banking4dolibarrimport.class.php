<?php
/*  Copyright (C) 2020      Open-DSI             <support@open-dsi.fr>
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
 *	\file       banking4dolibarr/class/banking4dolibarrimport.class.php
 *  \ingroup    banking4dolibarr
 *	\brief      File of class with functions for the import for Banking4Dolibarr
 */

/**
 *	Class to manage functions for the import for Banking4Dolibarr
 */
class Banking4DolibarrImport
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var array	Record index cached
	 */
	public static $record_index_cached = null;

	/**
	 * @var array	Category by label cached
	 */
	public static $category_cached = null;

    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
    public function __construct($db)
    {
		$this->db = $db;
    }

	/**
	 * Manage the import of the default fields values
	 *
	 * @param   array   $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @return  int     <0 if KO, >0 if OK
	 */
	public function importSetValues(&$arrayrecord)
	{
		global $langs, $sourcelinenb;

		$langs->load('banking4dolibarr@banking4dolibarr');

		// Manage the import of the field amount/debit/credit
		$debit = $this->getRecordValue('debit', $arrayrecord);
		$credit = $this->getRecordValue('credit', $arrayrecord);
		$amount = $this->getRecordValue('br.amount', $arrayrecord);

		if (isset($amount) && $amount !== "") {
			$amount = price2num($amount);
		} elseif (isset($debit) && $debit !== "") {
			$amount = -1 * price2num($debit);
		} elseif (isset($credit) && $credit !== "") {
			$amount = price2num($credit);
		} else {
			setEventMessage($langs->trans('Banking4DolibarrErrorImportAmountNotDefined', $sourcelinenb), 'errors');
			dol_syslog(__METHOD__ . ' Error: amount not defined', LOG_ERR);
			return -1;
		}

		$this->setRecordValue('br.amount', $arrayrecord, $amount, true);
		$this->delRecordValue('debit', $arrayrecord);
		$this->delRecordValue('credit', $arrayrecord);

		// Manage the import of the field original_amount/original_debit/original_credit
		$original_debit = $this->getRecordValue('original_debit', $arrayrecord);
		$original_credit = $this->getRecordValue('original_credit', $arrayrecord);
		$original_amount = $this->getRecordValue('br.original_amount', $arrayrecord);

		if (isset($original_amount) && $original_amount !== "") {
			$original_amount = price2num($original_amount);
		} elseif (isset($original_debit) && $original_debit !== "") {
			$original_amount = -price2num($original_debit);
		} elseif (isset($original_credit) && $original_credit !== "") {
			$original_amount = price2num($original_credit);
		}

		$this->setRecordValue('br.original_amount', $arrayrecord, $original_amount, true);
		$this->delRecordValue('original_debit', $arrayrecord);
		$this->delRecordValue('original_credit', $arrayrecord);

		// Manage the import of the field commission/commission_debit/commission_credit
		$commission_debit = $this->getRecordValue('commission_debit', $arrayrecord);
		$commission_credit = $this->getRecordValue('commission_credit', $arrayrecord);
		$commission_amount = $this->getRecordValue('br.commission', $arrayrecord);

		if (isset($commission_amount) && $commission_amount !== "") {
			$commission_amount = price2num($commission_amount);
		} elseif (isset($commission_debit) && $commission_debit !== "") {
			$commission_amount = -price2num($commission_debit);
		} elseif (isset($commission_credit) && $commission_credit !== "") {
			$commission_amount = price2num($commission_credit);
		}

		$this->setRecordValue('br.commission', $arrayrecord, $commission_amount, true);
		$this->delRecordValue('commission_debit', $arrayrecord);
		$this->delRecordValue('commission_credit', $arrayrecord);

		// Manage the import of the field category_parent/category_child/id_category
		$id_category = $this->getRecordValue('br.id_category', $arrayrecord);
		$category_parent = $this->getRecordValue('category_parent', $arrayrecord);
		$category_child = $this->getRecordValue('category_child', $arrayrecord);

		if (empty($id_category) && (!empty($category_parent) || !empty($category_child))) {
			if (!isset(self::$category_cached)) {
				$categories_dictionary = Dictionary::getDictionary($this->db, 'banking4dolibarr', 'banking4dolibarrbankrecordcategories');
				$result = $categories_dictionary->fetch_lines();
				if ($result < 0) {
					setEventMessage($langs->trans('Banking4DolibarrErrorImportFetchCategory', $sourcelinenb, $categories_dictionary->errorsToString()), 'errors');
					dol_syslog(__METHOD__ . ' Errors: ' . $categories_dictionary->errorsToString(), LOG_ERR);
					return -1;
				}

				self::$category_cached = array();
				foreach ($categories_dictionary->lines as $line) {
					$parent_label = $line->fields['id_parent_category'] > 0 ? $categories_dictionary->lines[$line->fields['id_parent_category']]->fields['label'] : '';
					$label = $line->fields['label'];
					self::$category_cached[$parent_label][$label] = !isset(self::$category_cached[$parent_label][$label]) ? $line->id : -1;
				}
			}

			$id_category = self::$category_cached[$category_parent][$category_child];
			if (!($id_category > 0)) {
				setEventMessage($langs->trans('Banking4DolibarrErrorImportTooManyOrNotFoundCategory', $sourcelinenb), 'errors');
				dol_syslog(__METHOD__ . ' Error: not found or too many found results', LOG_ERR);
				return -1;
			}
		}

		if($id_category > 0) $this->setRecordValue('br.id_category', $arrayrecord, $id_category, true);
		$this->delRecordValue('category_parent', $arrayrecord);
		$this->delRecordValue('category_child', $arrayrecord);

		// Manage the import of the default fields values
		$sql = 'SELECT MIN(id_record) AS min_id_record FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record';
		$resql = $this->db->query($sql);
		if (!$resql) {
			setEventMessage($langs->trans('Banking4DolibarrErrorImportGetMinRecordID', $sourcelinenb, $this->db->lasterror()), 'errors');
			dol_syslog(__METHOD__ . ' SQL: ' . $sql . '; Errors: ' . $this->db->lasterror(), LOG_ERR);
			return -1;
		}
		$min_id_record = 0;
		if ($obj = $this->db->fetch_object($resql)) {
			$min_id_record = min(0, $obj->min_id_record);
		}
		$min_id_record -= 1;

		$now = dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S');

		$this->setRecordValue('br.id_record', $arrayrecord, $min_id_record, true);
		$this->setRecordValue('br.last_update_date', $arrayrecord, $now, true);
		$this->setRecordValue('br.datec', $arrayrecord, $now, true);

		return 1;
	}

	/**
	 *  Get the value of the records by the field name
	 *
	 * @param	string		$fields				Field name
	 * @param	array		$arrayrecord		Records array
	 * @return	mixed							Value
	 */
	public function getRecordValue($fields, $arrayrecord)
	{
		if (!isset(self::$record_index_cached)) {
			global $array_match_file_to_database;

			self::$record_index_cached = array_flip($array_match_file_to_database);
		}

		if (!isset(self::$record_index_cached[$fields]) || !isset($arrayrecord[self::$record_index_cached[$fields]])) {
			return null;
		}

		return $arrayrecord[self::$record_index_cached[$fields] - 1]['val'];
	}

	/**
	 *  Set the value of the records by the field name
	 *
	 * @param	string		$fields				Field name
	 * @param	array		$arrayrecord		Records array
	 * @param	mixed		$value				Value
	 * @param	bool		$force				Force insert of the value
	 */
	public function setRecordValue($fields, &$arrayrecord, $value, $force=false)
	{
		if (!isset(self::$record_index_cached)) {
			global $array_match_file_to_database;

			self::$record_index_cached = array_flip($array_match_file_to_database);
		}

		if (isset(self::$record_index_cached[$fields]) && ($force || isset($arrayrecord[self::$record_index_cached[$fields]]))) {
			$arrayrecord[self::$record_index_cached[$fields] - 1] = array(
				'val' => $value,
				'type' => isset($value) ? 1 : -1,
			);
		}
	}

	/**
	 *  Set the value of the records by the field name
	 *
	 * @param	string		$fields				Field name
	 * @param	array		$arrayrecord		Records array
	 */
	public function delRecordValue($fields, &$arrayrecord)
	{
		if (!isset(self::$record_index_cached)) {
			global $array_match_file_to_database;

			self::$record_index_cached = array_flip($array_match_file_to_database);
		}

		if (isset(self::$record_index_cached[$fields]) && isset($arrayrecord[self::$record_index_cached[$fields]])) {
			unset($arrayrecord[self::$record_index_cached[$fields] - 1]);
		}
	}
}
