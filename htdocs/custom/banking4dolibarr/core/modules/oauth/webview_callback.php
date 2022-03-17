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
 *      \file       htdocs/banking4dolibarr/core/modules/oauth/webview_callback.php
 *      \ingroup    banking4dolibarr
 *      \brief      Page to get webview callback
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../../../main.inc.php")) $res=@include '../../../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../../../main.inc.php")) $res=@include '../../../../../main.inc.php';	// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

$action     = GETPOST('action', 'alpha');
$backtourl  = dol_buildpath('/banking4dolibarr/admin/accounts.php', 1);

if ($action == 'manage') {
    // Get code
    dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
    $budgetinsight = new BudgetInsight($db);

    $result = $budgetinsight->connection();
    if ($result > 0) $result = $budgetinsight->fetchCode();
    if ($result < 0) {
        setEventMessages($budgetinsight->error, $budgetinsight->errors, 'errors');
    }

    if (!empty($budgetinsight->code)) {
		dol_include_once('/banking4dolibarr/lib/banking4dolibarr.lib.php');
		$_SESSION["b4d_state"] = $state = $budgetinsight->customer_id;

		// Go to the webview page of Budget Insight
		header("Location: " . $budgetinsight->api_uri . "/auth/webview/" . $budgetinsight->webview_language . "/manage?connector_capabilities=bank&client_id=" . urlencode($budgetinsight->client_id) . "&code=" . urlencode($budgetinsight->code) .
			"&redirect_uri=" . urlencode(rtrim($budgetinsight->bridge_url, "/") . '/callbacks/banking4dolibarr.php') . (!empty($state) ? "&state=" . urlencode($state) : ''));
		// account_types
		exit;
	}
} else {
    $state              = GETPOST('state', 'alpha');
    $error              = GETPOST('error', 'alpha');
    $error_description  = GETPOST('error_description', 'alpha');
    if ($state != $_SESSION["b4d_state"]) {
        setEventMessage($langs->trans('Banking4DolibarrErrorBadState'), 'errors');
    } elseif (!empty($error) || !empty($error_description)) {
        setEventMessage("Error: $error<br>Error description: $error_description", 'errors');
    } else {
        unset($_SESSION["b4d_state"]);
        $backtourl .= '?action=b4d_refresh_bank_accounts';
    }
}

$db->close();

// Go to the bank accounts manage page
header("Location: " . $backtourl);
exit;
