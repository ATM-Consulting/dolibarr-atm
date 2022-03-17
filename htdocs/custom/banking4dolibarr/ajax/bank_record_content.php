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
$index_label	= GETPOST('index_label', 'int');
$nb_field		= GETPOST('nb_field', 'int');
$has_comment	= GETPOST('has_comment', 'int');

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
            $result = $budgetinsightbankrecord->fetch($row_id);
            if ($result < 0) {
                $outjson = array(
                    'error' => $budgetinsightbankrecord->errorsToString(),
                );
            } elseif ($result == 0) {
                $langs->load('errors');
                $outjson = array(
                    'error' => $langs->trans('ErrorRecordNotFound'),
                );
            } else {
                $reconciled_lines = $budgetinsightbankrecord->getReconciledLines(1);
                if (!is_array($reconciled_lines)) {
                    $outjson = array(
                        'error' => $budgetinsightbankrecord->errorsToString(),
                    );
                } else {
                    $has_comment = $has_comment > 0 ? 1 : 0;
                    $colspan_begin = $index_label > 2 ? $index_label - 1 : 1;
                    $colspan_padding_begin = $colspan_begin > 1 ? ' colspan="' . $colspan_begin . '"' : '';
                    $colspan_desc = $index_label > 1 ? ($has_comment ? 3 : 2) : ($has_comment ? 2 : 1);
                    $colspan_padding_desc = $colspan_desc > 1 ? ' colspan="' . $colspan_desc . '"' : '';
                    $colspan_padding_end = 'colspan="' . ($nb_field - $colspan_begin - $colspan_desc - 5) . '"';
                    $idx = 1;
                    $nb_lines = count($reconciled_lines);

                    $content = '';
                    $can_unlink = $budgetinsightbankrecord->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED && $account->clos == 0 && $user->rights->banque->consolidate;
                    foreach ($reconciled_lines as $line_id => $line) {
                        $content .= '<tr class="oddeven row_content_' . $row_id . ($can_unlink ? ' unlink_line_' . $row_id . '_' . $line_id : '') . '">';
                        $content .= '<td class="nowrap b4d_padding"' . $colspan_padding_begin . '>' . $line['ref'] . '</td>';
                        $content .= '<td class="nowrap"' . $colspan_padding_desc . '>' . $line['description'] . '</td>';
                        $content .= '<td class="nowrap" align="center">' . $line['dateo'] . '</td>';
                        $content .= '<td class="nowrap" align="center">' . $line['datev'] . '</td>';
                        $content .= '<td class="nowrap" align="center">' . $line['payment_type'] . '</td>';
                        $content .= '<td class="nowrap right">' . $line['debit'] . '</td>';
                        $content .= '<td class="nowrap right">' . $line['credit'] . '</td>';
                        $content .= '<td class="nowrap"' . $colspan_padding_end . '>' . $line['thirdparty'] . (!empty($line['num_chq']) ? ' - ' . $langs->trans("Numero") . ': ' . $line['num_chq'] : '') . '</td>';
                        $content .= '<td class="nowrap right">';
                        if ($can_unlink) {
                            $content .= '<a href="javascript:b4d_unlink_manual_reconciliation(' . $id . ', ' . $row_id . ', ' . $line_id . ');">' . img_delete($langs->trans("Banking4DolibarrUnlinkRecords")) . '</a>';
                        }
                        $content .= '</td>';
                        $content .= '</tr>';
                        $idx++;
                    }

                    $outjson = array(
                        'content' => $content,
                    );
                }
            }
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