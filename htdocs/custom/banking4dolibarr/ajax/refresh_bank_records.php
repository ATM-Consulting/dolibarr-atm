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
 * \file    htdocs/banking4dolibarr/ajax/refresh_bank_records.php
 * \brief   File to return Ajax response on refresh bank records process
 */
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');

$id			= GETPOST('id', 'int');
$first_date	= GETPOST('first_date', 'int');
$start_date	= GETPOST('start_date', 'int');
$offset		= GETPOST('offset', 'int');
$state		= GETPOST('state', 'alpha');

/*
 * View
 */

//$outjson = array(
//    'page' => '',                 // Url of the AJAX page (if empty => Stop the process)
//    'text' => '',                 // Replace the current text of the box (can have {{status}} tag,)
//    'status' => '',               // Status text who the tag {{status}} is substituted
//    'data' => array(),            // Data send to the next AJAX request
//    'location' => '',             // Url redirection when the process if finished (if empty close the box)
//    'error' => '',                // Error message
//    'keep_window_open' => false,  // if true then keep the refresh box open
//);

try {
    if ($user->rights->banking4dolibarr->bank_records->refresh) {
        if ($id > 0 && ($start_date > 0 || $first_date > 0)) {
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
            } elseif ($account->clos == 0 && checkUserAccessToObject($user, array('banque'), $account->id, 'bank_account&bank_account', '', '', 'rowid')) {
                $langs->load('banking4dolibarr@banking4dolibarr');
                $budgetinsight = new BudgetInsight($db);

                $result = $budgetinsight->connection();
                if ($result > 0) $result = $budgetinsight->refreshBankRecords($user, $id, $start_date, $offset, $state, $first_date);
                if (!is_array($result)) {
                    $outjson = array(
                        'error' => $budgetinsight->errorsToString(),
                    );
                } else {
                    if ($result['finish']) {
                        $outjson = array(
                            'status' => "{$result['offset']}",
                            'page' => '',
                        );
                        if (!empty($result['additional_text'])) $outjson['keep_window_open'] = true;
                        if ($result['offset'] > 0) {
                            setEventMessage($langs->trans('Banking4DolibarrRefreshBankRecordsSuccess', $result['offset']));
                            $outjson['keep_window_open'] = true;
                        } else {
                            setEventMessage($langs->trans('Banking4DolibarrRefreshBankRecordsSuccessNoLinesUpdated'));
                        }
                    } else {
                        $outjson = array(
                            'status' => "{$result['offset']}",
                            'data' => array(
                                'id' => $id,
                                'first_date' => $first_date,
								'start_date' => $start_date,
                                'offset' => $result['offset'],
                                'state' => $result['state'],
                            ),
                        );
                    }
                    if (!empty($result['additional_text'])) {
                        $langs->load('banking4dolibarr@banking4dolibarr');
                        $outjson['text'] = $langs->trans("Banking4DolibarrProcessBoxText") . '<br><span style="color: orangered;">' . $result['additional_text'] . '</span>';
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
    } else {
        $langs->load('errors');
        $outjson = array(
            'error' => $langs->trans('ErrorForbidden'),
        );
    }
} catch (Exception $e) {
    $outjson = array(
        'error' => $e->getMessage(),
    );
}

echo json_encode($outjson);

$db->close();