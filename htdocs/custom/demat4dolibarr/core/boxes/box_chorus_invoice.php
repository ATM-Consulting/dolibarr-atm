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
 *  \file       htdocs/core/boxes/box_chorus_invoice.php
 *  \ingroup    demat4dolibarr
 *  \brief      Module to show list sum invoice price for each chorus status
 */
include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");

/**
 * Class to manage the box to show list sum invoice price for each chorus status
 */
class box_chorus_invoice extends ModeleBoxes
{
	var $boxcode = "d4d_chorus_invoice";
	var $boximg = "opendsi@demat4dolibarr";
	var $boxlabel = "Demat4DolibarrBoxInvoiceChorus";
	var $depends = array("facture");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


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
		$langs->load("demat4dolibarr@demat4dolibarr");

		$this->db = $db;

		$this->hidden = !($user->rights->facture->lire) || !($user->rights->demat4dolibarr->widget_chorus_invoice);
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

		$textHead = $langs->trans("Demat4DolibarrBoxInvoiceChorus");
		$this->info_box_head = array('text' => $textHead, 'limit' => dol_strlen($textHead));

        if((float)DOL_VERSION < 14.0) {
            $ftotalht = 'f.total';
            $ftotaltva = 'f.tva';
            $ftotalttc = 'f.total_ttc';
        } else {
            $ftotalht = 'f.total_ht';
            $ftotaltva = 'f.total_tva';
            $ftotalttc = 'f.total_ttc';
        }

		if ($user->rights->facture->lire) {
            $sql = "SELECT IF(IFNULL(fef.d4d_invoice_status, '') != '', CONCAT('I_', fef.d4d_invoice_status), IF(IFNULL(fef.d4d_chorus_status, '') != '', CONCAT('C_', fef.d4d_chorus_status), IF(IFNULL(fef.d4d_job_status, '') != '', CONCAT('J_', fef.d4d_job_status), ''))) AS key_status";
            $sql .= ", sum(IFNULL(".$ftotalht.", 0)) AS total_ht";
			$sql .= ", sum(IFNULL(".$ftotaltva.", 0)) AS total_tva";
			$sql .= ", sum(IFNULL(".$ftotalttc.", 0)) AS total_ttc";
			$sql .= ", COUNT(DISTINCT f.rowid) AS number";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture_extrafields as fef";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = fef.fk_object";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
			$sql .= " WHERE f.entity IN (" . getEntity('facture') . ")";
            $sql .= " AND (fef.d4d_job_id IS NOT NULL or fef.d4d_invoice_id IS NOT NULL)";
            $sql .= " AND f.paye = 0";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
			if ($user->socid) $sql .= " AND s.rowid = " . $user->socid;
			$sql .= " GROUP BY IF(IFNULL(fef.d4d_invoice_status, '') != '', CONCAT('I_', fef.d4d_invoice_status), IF(IFNULL(fef.d4d_chorus_status, '') != '', CONCAT('C_', fef.d4d_chorus_status), IF(IFNULL(fef.d4d_job_status, '') != '', CONCAT('J_', fef.d4d_job_status), '')))";

			$resql = $this->db->query($sql);
			if ($resql) {
				$lines = array();
				$total_ht = 0;
				$total_tva = 0;
				$total_ttc = 0;
				$total_number = 0;

				while ($obj = $this->db->fetch_object($resql)) {
					$code = !empty($obj->key_status) ? $obj->key_status : '';
					$lines[$code] = array('total_ht' => $obj->total_ht, 'total_tva' => $obj->total_tva, 'total_ttc' => $obj->total_ttc, 'number' => $obj->number);
					$total_ht += $obj->total_ht;
					$total_tva += $obj->total_tva;
					$total_ttc += $obj->total_ttc;
					$total_number += $obj->number;
				}

				$this->db->free($resql);

				$langs->trans("NoRecordedInvoices");

                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $dictionary_job = Dictionary::getDictionary($this->db, 'demat4dolibarr', 'demat4dolibarrjobstatus');
                $dictionary_job->fetch_lines(1, array(), array('position' => 'ASC'));

                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $dictionary_chorus = Dictionary::getDictionary($this->db, 'demat4dolibarr', 'demat4dolibarrchorusstatus');
                $dictionary_chorus->fetch_lines(1, array(), array('position' => 'ASC'));

                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $dictionary_invoice = Dictionary::getDictionary($this->db, 'demat4dolibarr', 'demat4dolibarrinvoicestatus');
                $dictionary_invoice->fetch_lines(1, array(), array('position' => 'ASC'));

                $num_line = 0;
                $this->add_header($langs->trans('Status'), $num_line);

				if (!empty($conf->global->DEMAT4DOLIBARR_BOX_SHOW_ONLY_STATUS_WHO_HAVE_INVOICE) && count($lines) == 0) {
					$this->info_box_contents[$num_line][] = array(
						'td' => 'align="center" colspan="6"',
						'text' => $langs->trans('NoRecordedInvoices'),
					);
				} else {
				    $list_url = dol_buildpath('/compta/facture/list.php', 1) . '?search_status=1';
                    $filter_url = $list_url . '&search_options_d4d_job_status=';
                    foreach ($dictionary_job->lines as $line) {
                        $code = 'J_'.$line->id;
                        $this->add_lines($langs->trans('Demat4DolibarrJobStatus'), $lines, $code, $line, $filter_url, $num_line);
                    }

                    $filter_url = $list_url . '&search_options_d4d_chorus_status=';
                    foreach ($dictionary_chorus->lines as $line) {
                        $code = 'C_'.$line->id;
                        $this->add_lines($langs->trans('Demat4DolibarrChorusStatus'), $lines, $code, $line, $filter_url, $num_line);
                    }

                    $filter_url = $list_url . '&search_options_d4d_invoice_status=';
                    foreach ($dictionary_invoice->lines as $line) {
                        $code = 'I_'.$line->id;
                        $this->add_lines($langs->trans('Demat4DolibarrInvoiceStatus'), $lines, $code, $line, $filter_url, $num_line);
                    }

					$this->info_box_contents[$num_line][] = array(
						'tr' => 'class="liste_total"',
						'td' => 'class="liste_total" colspan="2"',
						'text' => $langs->trans('Total'),
					);
					$this->info_box_contents[$num_line][] = array(
						'td' => 'align="right" class="liste_total"',
						'text' => price($total_ht, 0, $langs, 0, -1, -1, $conf->currency),
					);
					$this->info_box_contents[$num_line][] = array(
						'td' => 'align="right" class="liste_total"',
						'text' => price($total_tva, 0, $langs, 0, -1, -1, $conf->currency),
					);
					$this->info_box_contents[$num_line][] = array(
						'td' => 'align="right" class="liste_total"',
						'text' => price($total_ttc, 0, $langs, 0, -1, -1, $conf->currency),
					);
					$this->info_box_contents[$num_line][] = array(
						'td' => 'align="right" class="liste_total"',
						'text' => $total_number,
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
				'td' => 'align="left" class="nohover opacitymedium"',
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
     * @param $lines
     * @param $code
     * @param $line
     * @param $filter_url
     * @param $num_line
     */
    function add_lines($type_label, $lines, $code, $line, $filter_url, &$num_line)
    {
        global $conf, $langs, $form;

        if (empty($conf->global->DEMAT4DOLIBARR_BOX_SHOW_ONLY_STATUS_WHO_HAVE_INVOICE) || isset($lines[$code])) {
            if (!isset($lines[$code])) $lines[$code] = array('total_ht' => 0, 'total_tva' => 0, 'total_ttc' => 0, 'number' => 0);

            $this->info_box_contents[$num_line][] = array(
                'td' => '',
                'text' => $type_label,
            );
            $this->info_box_contents[$num_line][] = array(
                'td' => '',
                'text' => $form->textwithtooltip($line->fields['short_label'], $line->fields['label']),
            );
            $this->info_box_contents[$num_line][] = array(
                'td' => 'align="right"',
                'text' => price($lines[$code]['total_ht'], 0, $langs, 0, -1, -1, $conf->currency),
            );
            $this->info_box_contents[$num_line][] = array(
                'td' => 'align="right"',
                'text' => price($lines[$code]['total_tva'], 0, $langs, 0, -1, -1, $conf->currency),
            );
            $this->info_box_contents[$num_line][] = array(
                'td' => 'align="right"',
                'text' => price($lines[$code]['total_ttc'], 0, $langs, 0, -1, -1, $conf->currency),
            );
            $this->info_box_contents[$num_line][] = array(
                'td' => 'align="right"',
                'url' => $filter_url . $line->id,
                'target' => '_blank',
                'text' => $lines[$code]['number'],
            );

            $num_line++;
        }
    }

    /**
     * @param $header_label
     * @param $num_line
     */
    function add_header($header_label, &$num_line)
    {
        global $langs;

        $this->info_box_contents[$num_line][] = array(
            'tr' => 'class="liste_titre"',
            'td' => 'class="liste_titre"',
            'text' => $header_label,
        );
        $this->info_box_contents[$num_line][] = array(
            'td' => 'class="liste_titre"',
            'text' => '',
        );
        $this->info_box_contents[$num_line][] = array(
            'td' => 'align="right" class="liste_titre"',
            'text' => $langs->trans('TotalHT'),
        );
        $this->info_box_contents[$num_line][] = array(
            'td' => 'align="right" class="liste_titre"',
            'text' => $langs->trans('TotalVAT'),
        );
        $this->info_box_contents[$num_line][] = array(
            'td' => 'align="right" class="liste_titre"',
            'text' => $langs->trans('TotalTTC'),
        );
        $this->info_box_contents[$num_line][] = array(
            'td' => 'align="right" class="liste_titre"',
            'text' => $langs->trans('Quantity'),
        );
        $num_line++;
    }
}

