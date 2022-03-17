<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2014      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2016      Juan José Menent       <jmenent@2byte.es>
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
 *  \file       htdocs/banking4dolibarr/core/boxes/box_b4d_bank_account.php
 *  \ingroup    banking4dolibarr
 *  \brief      Module to show list of bank account with infos
 */
include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");

/**
 * Class to manage the box to show list of bank account with infos
 */
class box_b4d_bank_account extends ModeleBoxes
{
	var $boxcode = "b4d_bank_account";
	var $boximg = "opendsi@banking4dolibarr";
	var $boxlabel = "Banking4DolibarrBoxBankAccount";
	var $depends = array("banque");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

    var $num_line;

	/**
	 *  Constructor
	 *
	 * @param  DoliDB   $db         Database handler
	 * @param  string   $param      More parameters
	 */
	function __construct($db, $param = '')
	{
		global $user, $langs;
		$langs->load("boxes");
		$langs->load("banking4dolibarr@banking4dolibarr");

		$this->db = $db;

		$this->hidden = !($user->rights->banque->lire) || !($user->rights->banking4dolibarr->bank_records->lire);
	}

	/**
	 *  Load data for box to show them later
	 *
	 * @param   int     $max    Maximum number of records to load
	 * @return  void
	 */
	function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		$this->max = $max;

		$textHead = $langs->trans("Banking4DolibarrBoxBankAccount");
		$this->info_box_head = array('text' => $textHead, 'limit' => dol_strlen($textHead));

		if ($user->rights->banque->lire && $user->rights->banking4dolibarr->bank_records->lire) {
		    dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
            $sql = "SELECT ba.rowid, ba.ref, ba.label, ba.number, ba.currency_code, ba.account_number, aj.code as accountancy_journal";
            $sql .= ", baef.b4d_account_update_date, baef.b4d_account_balance";
            $sql .= ", bal.balance AS balance_dolibarr";
            $sql .= ", nrr.not_recorded_debit_amount, nrr.not_recorded_credit_amount, " . $this->db->ifsql("nrr.not_recorded_count IS NULL", "0", "nrr.not_recorded_count") . " AS not_recorded_count";
            $sql .= " FROM " . MAIN_DB_PREFIX . "bank_account as ba";
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_account as cb4dba ON cb4dba.fk_bank_account = ba.rowid';
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_account_extrafields AS baef ON baef.fk_object = ba.rowid";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_journal AS aj ON aj.rowid = ba.fk_accountancy_journal";
            $sql .= " LEFT JOIN (";
            $sql .= "   SELECT b.fk_account, SUM(b.amount) AS balance";
            $sql .= "   FROM " . MAIN_DB_PREFIX . "bank as b";
            $sql .= "   LEFT JOIN " . MAIN_DB_PREFIX . "bank_account AS ba ON ba.rowid = b.fk_account";
            $sql .= "   WHERE ba.entity IN (" . getEntity('bank_account') . ")";
            $sql .= "   GROUP BY b.fk_account";
            $sql .= " ) AS bal ON bal.fk_account = ba.rowid";
            $sql .= " LEFT JOIN (";
            $sql .= "   SELECT ba.rowid AS fk_account, SUM(" . $this->db->ifsql("br.amount < 0", "br.amount", "0") . ") AS not_recorded_debit_amount, SUM(" . $this->db->ifsql("br.amount > 0", "br.amount", "0") . ") AS not_recorded_credit_amount, COUNT(*) AS not_recorded_count";
            $sql .= '   FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record as br';
            $sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_account as cb4dba ON cb4dba.rowid = br.id_account';
            $sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON ba.rowid = cb4dba.fk_bank_account';
            $sql .= "   WHERE ba.entity IN (" . getEntity('bank_account') . ")";
            $sql .= "   AND br.status = " . BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED;
            $sql .= "   GROUP BY ba.rowid";
            $sql .= " ) AS nrr ON nrr.fk_account = ba.rowid";
            $sql .= " WHERE ba.entity IN (" . getEntity('bank_account') . ")";
            $sql .= " AND ba.clos = 0";
            $sql .= " AND cb4dba.rowid IS NOT NULL";
            $sql .= " ORDER BY ba.ref";
            $sql .= $this->db->plimit($max, 0);

            $resql = $this->db->query($sql);
            if ($resql) {
                $num = $this->db->num_rows($resql);
                $this->num_line = 0;

                // Set header
                $this->add_header();

                if ($num > 0) {
                    $total_account_balance = 0;
                    $total_account_balance_dolibarr = 0;
                    $total_not_reconciled_debit_amount = 0;
                    $total_not_reconciled_credit_amount = 0;
                    $total_not_reconciled_records_count = 0;

                    require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                    $account_static = new Account($this->db);

                    $url = dol_buildpath('/banking4dolibarr/bankrecords.php', 1) . '?viewstatut=0&id=';
                    while ($obj = $this->db->fetch_object($resql)) {
                        $account_static->id = $obj->rowid;
                        $account_static->ref = $obj->ref;
                        $account_static->label = $obj->label;
                        $account_static->number = $obj->number;
                        $account_static->currency_code = $obj->currency_code;
                        $account_static->account_number = $obj->account_number;
                        $account_static->accountancy_journal = $obj->accountancy_journal;

                        $total_account_balance += $obj->b4d_account_balance;
                        $total_account_balance_dolibarr += $obj->balance_dolibarr;
                        $total_not_reconciled_debit_amount += $obj->not_recorded_debit_amount;
                        $total_not_reconciled_credit_amount += $obj->not_recorded_credit_amount;
                        $total_not_reconciled_records_count += $obj->not_recorded_count;

                        $this->add_line($account_static->getNomUrl(1), $this->db->jdate($obj->b4d_account_update_date),
                            $obj->b4d_account_balance, $obj->balance_dolibarr,
                            $obj->not_recorded_debit_amount, $obj->not_recorded_credit_amount,
                            $obj->currency_code,
                            $obj->not_recorded_count, $url . $obj->rowid);
                    }

                    $this->add_total($total_account_balance, $total_account_balance_dolibarr,
                        $total_not_reconciled_debit_amount, $total_not_reconciled_credit_amount,
                        $obj->currency_code, $total_not_reconciled_records_count);

                    $this->db->free($resql);
                } else {
					$this->info_box_contents[$this->num_line][] = array(
						'td' => 'align="center" colspan="' . (!empty($conf->global->BANKING4DOLIBARR_WIDGET_SHOW_BALANCE) ? 7 : 6) . '"',
						'text' => $langs->trans('Banking4DolibarrNoRecordedBankAccounts'),
					);
				}
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength' => 500,
                    'text' => ($this->db->lasterror() . ' sql=' . $sql),
                );
            }
        } else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover opacitymedium"',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}

    /**
     *  Method to show box
     *
     * @param   array   $head       Array with properties of box title
     * @param   array   $contents   Array with properties of box lines
     * @param   int     $nooutput   No print, only return string
     * @return  string
     */
    function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }

    /**
     *  Add line
     * @param   string  $account_label                      Account label
     * @param   int     $date_update_account                Date of the last synchronization of the bank account
     * @param   double  $account_balance                    Account balance of the downloaded bank records
     * @param   double  $account_balance_dolibarr           Account balance in Dolibarr
     * @param   double  $not_reconciled_debit_amount        Debit amount of the downloaded bank records not reconciled
     * @param   double  $not_reconciled_credit_amount       Credit amount of the downloaded bank records not reconciled
     * @param   double  $account_currency                   Account currency
     * @param   int     $not_reconciled_records_count       Count of the downloaded bank records not reconciled
     * @param   string  $not_reconciled_records_count_url   Url to the downloaded bank records not reconciled
     * @return  void
     */
    function add_line($account_label, $date_update_account, $account_balance, $account_balance_dolibarr, $not_reconciled_debit_amount, $not_reconciled_credit_amount, $account_currency, $not_reconciled_records_count, $not_reconciled_records_count_url)
	{
		global $conf, $langs;

		$this->info_box_contents[$this->num_line][] = array(
			'td' => '',
			'text' => $account_label,
			'asis' => 1,
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'align="center"',
			'text' => dol_print_date($date_update_account, 'dayhour'),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="nowrap right"',
			'text' => price($account_balance, 0, $langs, 0, -1, -1, $account_currency),
		);
		if (!empty($conf->global->BANKING4DOLIBARR_WIDGET_SHOW_BALANCE)) {
			$this->info_box_contents[$this->num_line][] = array(
				'td' => 'class="nowrap right"',
				'text' => price($account_balance_dolibarr, 0, $langs, 0, -1, -1, $account_currency),
			);
		}
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="nowrap right"',
			'text' => price($not_reconciled_debit_amount, 0, $langs, 0, -1, -1, $account_currency),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="nowrap right"',
			'text' => price($not_reconciled_credit_amount, 0, $langs, 0, -1, -1, $account_currency),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="nowrap right"',
			'url' => $not_reconciled_records_count_url,
			'target' => '_blank',
			'text' => $not_reconciled_records_count,
		);
		$this->num_line++;
	}

    /**
     *  Add header line
     * @return void
     */
    function add_header()
	{
		global $conf, $langs;

		$this->info_box_contents[$this->num_line][] = array(
			'tr' => 'class="liste_titre"',
			'td' => 'class="liste_titre"',
			'text' => $langs->trans('Banking4DolibarrBankAccount'),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="center liste_titre"',
			'text' => $langs->trans('Banking4DolibarrDateUpdateAccount'),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="right liste_titre"',
			'text' => $langs->trans('Banking4DolibarrAccountBalance'),
		);
		if (!empty($conf->global->BANKING4DOLIBARR_WIDGET_SHOW_BALANCE)) {
			$this->info_box_contents[$this->num_line][] = array(
				'td' => 'class="right liste_titre"',
				'text' => $langs->trans('Banking4DolibarrAccountBalanceDolibarr'),
			);
		}
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="right liste_titre"',
			'text' => $langs->trans('Banking4DolibarrNotReconciledDebitAmount'),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="right liste_titre"',
			'text' => $langs->trans('Banking4DolibarrNotReconciledCreditAmount'),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="right liste_titre"',
			'text' => $langs->trans('Banking4DolibarrNotReconciledRecordsCount'),
		);
		$this->num_line++;
	}

    /**
     *  Add total line
     * @param   double  $account_balance                    Account balance of the downloaded bank records
     * @param   double  $account_balance_dolibarr           Account balance in Dolibarr
     * @param   double  $not_reconciled_debit_amount        Debit amount of the downloaded bank records not reconciled
     * @param   double  $not_reconciled_credit_amount       Credit amount of the downloaded bank records not reconciled
     * @param   double  $account_currency                   Account currency
     * @param   int     $not_reconciled_records_count       Count of the downloaded bank records not reconciled
     * @return  void
     */
    function add_total($account_balance, $account_balance_dolibarr, $not_reconciled_debit_amount, $not_reconciled_credit_amount, $account_currency, $not_reconciled_records_count)
	{
		global $conf, $langs;

		$this->info_box_contents[$this->num_line][] = array(
			'tr' => 'class="liste_total"',
			'td' => 'class="liste_total" colspan="2"',
			'text' => $langs->trans('Total'),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="nowrap right liste_total"',
			'text' => price($account_balance, 0, $langs, 0, -1, -1, $account_currency),
		);
		if (!empty($conf->global->BANKING4DOLIBARR_WIDGET_SHOW_BALANCE)) {
			$this->info_box_contents[$this->num_line][] = array(
				'td' => 'class="nowrap right liste_total"',
				'text' => price($account_balance_dolibarr, 0, $langs, 0, -1, -1, $account_currency),
			);
		}
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="nowrap right liste_total"',
			'text' => price($not_reconciled_debit_amount, 0, $langs, 0, -1, -1, $account_currency),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="nowrap right liste_total"',
			'text' => price($not_reconciled_credit_amount, 0, $langs, 0, -1, -1, $account_currency),
		);
		$this->info_box_contents[$this->num_line][] = array(
			'td' => 'class="nowrap right liste_total"',
			'text' => $not_reconciled_records_count,
		);
		$this->num_line++;
	}
}

