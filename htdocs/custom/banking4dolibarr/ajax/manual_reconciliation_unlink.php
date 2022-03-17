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

$id			= GETPOST('id', 'int');
$row_id		= GETPOST('row_id', 'int');
$line_id	= GETPOST('line_id', 'int');

/*
 * View
 */

//$outjson = array(
//    'result' => '',   // Result
//    'error' => '',   // Error message
//);

try {
    if ($id > 0 && $row_id > 0 && $line_id > 0) {
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
            } elseif ($budgetinsightbankrecord->status == BudgetInsightBankRecord::BANK_RECORD_STATUS_NOT_RECONCILED) {
                $result = $budgetinsightbankrecord->unlink($user, $line_id);
                if ($result < 0) {
                    $outjson = array(
                        'error' => $budgetinsightbankrecord->errorsToString(),
                    );
                } else {
                    $outjson = array(
                        'result' => 1,
                    );
                }
            } else {
                $outjson = array(
                    'error' => $langs->trans('Banking4DolibarrErrorLinkLineNotUnlinked', $object->id),
                );
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