<?php
/* Copyright (C) 2019 Open-DSI            <support@open-dsi.fr>
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
 * \file    htdocs/banking4dolibarr/ajax/bank_record_content.php
 * \brief   File to return details content of a bank record
 */
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');

$id				= GETPOST('id', 'int');
$row_id			= GETPOST('row_id', 'int');
$arrayfields	= GETPOST('array_fields', 'array');

/*
 * View
 */

//$outjson = array(
//    'content' => '',  // HTML content of the response
//    'error' => '',    // Error message
//);

try {
    if ($id > 0 && $row_id > 0) {
        $langs->load('banks');
        $account = new Account($db);
        $result = $account->fetch($id);
        if ($result < 0) {
            $outjson = array(
                'error' => $account->errorsToString(),
            );
        } elseif ($result == 0) {
            $langs->load('errors');
            $outjson = array(
                'error' => $langs->trans('ErrorRecordNotFound'),
            );
        } elseif (checkUserAccessToObject($user, array('banque'), $account->id, 'bank_account&bank_account', '', '', 'rowid')) {
			$langs->load('banking4dolibarr@banking4dolibarr');
			$budgetinsightbankrecord = new BudgetInsightBankRecord($db);
			$content = '';

			// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
			$hookmanager->initHooks(array('b4dbankrecordslist'));

			// List
			//--------------------------------------------------------
			$sql = 'SELECT ';
			$sql .= ' br.rowid, br.id_record, br.id_account,';
			$sql .= ' br.label, br.comment, CONCAT(' . $db->ifsql("cb4dbrsc.rowid IS NULL", "''", "CONCAT(cb4dbrsc.label, ' - ')") . ', cb4dbrc.label) AS category_label,';
			$sql .= ' br.record_type, br.original_country, br.original_amount, br.original_currency,';
			$sql .= ' br.commission, br.commission_currency, br.amount, br.coming, br.deleted_date, br.record_date, br.rdate, br.bdate,';
			$sql .= ' br.vdate, br.date_scraped, br.last_update_date, br.reconcile_date, br.datas, br.tms, br.status';
			// Add fields from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'banking4dolibarr_bank_record as br';
			$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_account as cb4dba ON cb4dba.rowid = br.id_account';
			$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON ba.rowid = cb4dba.fk_bank_account';
			$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_record_category as cb4dbrc ON cb4dbrc.rowid = br.id_category';
			$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_banking4dolibarr_bank_record_category as cb4dbrsc ON cb4dbrsc.rowid = cb4dbrc.id_parent_category';
			// Add join from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
			$sql .= ' WHERE ba.entity IN (' . getEntity('bank_account') . ')';
			$sql .= ' AND ba.rowid = ' . $id;
			$sql .= ' AND br.fk_duplicate_of = ' . $row_id;
			// Add where from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;

			$sql .= ' GROUP BY br.rowid, br.id_record, br.id_account,';
			$sql .= ' br.label, br.comment, CONCAT(' . $db->ifsql("cb4dbrsc.rowid IS NULL", "''", "CONCAT(cb4dbrsc.label, ' - ')") . ', cb4dbrc.label),';
			$sql .= ' br.record_type, br.original_country, br.original_amount, br.original_currency,';
			$sql .= ' br.commission, br.commission_currency, br.amount, br.coming, br.deleted_date, br.record_date, br.rdate, br.bdate,';
			$sql .= ' br.vdate, br.date_scraped, br.last_update_date, br.reconcile_date, br.datas, br.tms, br.status';

			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);

				$i = 0;
				while ($obj = $db->fetch_object($resql)) {
					$padding_added = false;

					$content .= '<tr class="oddeven row_content_' . $row_id . '">';
					if (!empty($arrayfields['br.rowid']['checked'])) {
						$content .= '<td';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= $obj->rowid;
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.id_record']['checked'])) {
						$content .= '<td';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= $obj->id_record;
						$content .= "</td>\n";
					}
					// if (!empty($arrayfields['br.label']['checked'])) {
					$content .= '<td colspan="2"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					$content .= $obj->label;
					$content .= "</td>\n";
					if (!empty($arrayfields['br.comment']['checked'])) {
						$content .= '<td';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= $obj->comment;
						$content .= "</td>\n";
					}
					// }
					// if (!empty($arrayfields['br.record_date']['checked'])) {
					$content .= '<td align="center"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					if (!empty($obj->record_date)) $content .= dol_print_date($db->jdate($obj->record_date), 'day');
					$content .= "</td>\n";
					// }
					// if (!empty($arrayfields['br.vdate']['checked'])) {
					$content .= '<td align="center"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					if (!empty($obj->vdate)) $content .= dol_print_date($db->jdate($obj->vdate), 'day');
					elseif (!empty($obj->record_date)) $content .= dol_print_date($db->jdate($obj->record_date), 'day');
					$content .= "</td>\n";
					// }
					// if (!empty($arrayfields['br.record_type']['checked'])) {
					$content .= '<td align="center"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					$content .= $budgetinsightbankrecord->LibType($obj->record_type);
					$content .= "</td>\n";
					// }
					// if (!empty($arrayfields['debit']['checked'])) {
					$content .= '<td class="nowrap right"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					if ($obj->amount < 0) {
						$content .= price($obj->amount * -1);
					}
					$content .= "</td>\n";
					// }
					// if (!empty($arrayfields['credit']['checked'])) {
					$content .= '<td class="nowrap right"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					if ($obj->amount > 0) {
						$content .= price($obj->amount);
					}
					$content .= "</td>\n";
					// }
					if (!empty($arrayfields['bank_list']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= '</td>';
					}
					// if (!empty($arrayfields['b.num_releve']['checked'])) {
					$content .= '<td align="center"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					$content .= '</td>';
					// }
					if (!empty($arrayfields['br.reconcile_date']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= '</td>';
					}
					if (!empty($arrayfields['br.id_category']['checked'])) {
						$content .= '<td';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= $obj->category_label;
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.rdate']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						if (!empty($obj->rdate)) $content .= dol_print_date($db->jdate($obj->rdate), 'day');
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.bdate']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						if (!empty($obj->bdate)) $content .= dol_print_date($db->jdate($obj->bdate), 'day');
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.date_scraped']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						if (!empty($obj->date_scraped)) $content .= dol_print_date($db->jdate($obj->date_scraped), 'dayhour');
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.original_country']['checked'])) {
						$content .= '<td';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= $obj->original_country;
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.original_amount']['checked'])) {
						$content .= '<td class="right"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						if ($obj->original_amount > 0) $content .= price($obj->original_amount);
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.original_currency']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						if (!empty($obj->original_currency)) $content .= $langs->trans('Currency' . $obj->original_currency);
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.commission']['checked'])) {
						$content .= '<td class="right"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						if ($obj->commission > 0) $content .= price($obj->commission);
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.commission_currency']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						if (!empty($obj->commission_currency)) $content .= $langs->trans('Currency' . $obj->commission_currency);
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.coming']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= yn($obj->coming);
						$content .= "</td>\n";
					}
					if (!empty($arrayfields['br.deleted_date']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						if (!empty($obj->deleted_date)) $content .= dol_print_date($db->jdate($obj->deleted_date), 'dayhour');
						$content .= "</td>\n";
					}
					// Fields from hook
					$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
					$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
					$content .= $hookmanager->resPrint;
					if (!empty($arrayfields['br.last_update_date']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= dol_print_date($db->jdate($obj->last_update_date), 'dayhour', 'tzuser');
						$content .= '</td>';
					}
					if (!empty($arrayfields['br.tms']['checked'])) {
						$content .= '<td align="center"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
						$content .= '</td>';
					}
					if (!empty($arrayfields['br.datas']['checked'])) {
						$content .= '<td class="maxwidthonsmartphone"';
						if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
						$content .= '>';
						$content .= $obj->datas;
						$content .= '</td>';
					}
					// if (!empty($arrayfields['br.status']['checked'])) {
					$content .= '<td class="nowrap right"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					$content .= $budgetinsightbankrecord->LibStatut($obj->status, 5);
					$content .= '</td>';
					// }
					// Action column
					$content .= '<td class="right nowrap"';
					if (!$padding_added) { $content .= ' class="b4d_padding"'; $padding_added = true; }
					$content .= '>';
					$content .= '</td>';

					$content .= "</tr>\n";

					$i++;
				}

				$db->free($resql);
			}

			$outjson = array(
				'content' => $content,
			);
		} else {
            $langs->load('errors');
            $outjson = array(
                'error' => $langs->trans('ErrorForbidden'),
            );
        }
    } else {
        $langs->load('errors');
        $outjson = array(
            'error' => $langs->trans('ErrorBadParameters'),
        );
    }
} catch (Exception $e) {
    $outjson = array(
        'error' => $e->getMessage(),
    );
}

echo json_encode($outjson);

$db->close();