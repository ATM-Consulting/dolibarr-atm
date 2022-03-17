<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/banking4dolibarr/admin/setup.php
 *		\ingroup    banking4dolibarr
 *		\brief      Page to setup banking4dolibarr module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
dol_include_once('/banking4dolibarr/lib/banking4dolibarr.lib.php');
dol_include_once('/banking4dolibarr/class/module_key/opendsimodulekeyb4d.class.php');
dol_include_once('/banking4dolibarr/class/budgetinsight.class.php');
dol_include_once('/banking4dolibarr/class/html.formbanking4dolibarr.class.php');

$langs->load("admin");
$langs->load("banking4dolibarr@banking4dolibarr");
$langs->load("opendsi@banking4dolibarr");
$langs->load("oauth");

if (!$user->admin || empty($user->rights->banking4dolibarr->param_menu)) accessforbidden();

$action = GETPOST('action', 'aZ09');

/*
 *	Actions
 */

$errors = [];
$error = 0;
$db->begin();

if ($action == 'set_api_options') {
	$value = GETPOST('BANKING4DOLIBARR_MODULE_KEY', 'alpha');
	if (!empty($value)) {
		$result = OpenDsiModuleKeyB4D::decode($value);
		if (!empty($result['error'])) {
			$errors[] = $result['error'];
			$error++;
		} else {
			$module_key_infos = $result['key'];
			$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_MODULE_KEY', $value, 'chaine', 0, '', $conf->entity);
			if (!($res > 0)) {
				$errors[] = $db->lasterror();
				$error++;
			}
		}
	} else {
		$res = dolibarr_del_const($db, 'BANKING4DOLIBARR_MODULE_KEY', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}
	}
}
$has_key = !empty($conf->global->BANKING4DOLIBARR_MODULE_KEY);

if (!$error) {
	if ($action == 'set_api_options' && $has_key) {
		if (!$error) {
			$value = GETPOST('BANKING4DOLIBARR_API_TIMEOUT', 'int');
			$value = $value > 0 ? $value : 10;
			$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_API_TIMEOUT', $value, 'chaine', 0, '', $conf->entity);
			if (!($res > 0)) {
				$errors[] = $db->lasterror();
				$error++;
			}

			$value = GETPOST('BANKING4DOLIBARR_REQUEST_LIMIT', 'int');
			$value = min(BudgetInsight::MAX_REQUEST_LIMIT, $value > 0 ? $value : BudgetInsight::DEFAULT_REQUEST_LIMIT);
			$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_REQUEST_LIMIT', $value, 'chaine', 0, '', $conf->entity);
			if (!($res > 0)) {
				$errors[] = $db->lasterror();
				$error++;
			}
		}
	} elseif ($action == 'set_auto_create_link_bank_account' && $has_key) {
		$value = GETPOST('value', 'int');

		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_AUTO_LINK_BANK_ACCOUNT', $value == 1 || $value == 3 ? '1' : ($value == 0 ? '0' : $conf->global->BANKING4DOLIBARR_AUTO_LINK_BANK_ACCOUNT), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_AUTO_CREATE_BANK_ACCOUNT', $value == 3 ? '1' : ($value == 0 || $value == 2 ? '0' : $conf->global->BANKING4DOLIBARR_AUTO_CREATE_BANK_ACCOUNT), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}
	} elseif ($action == 'set_bank_account_options') {
		$value = GETPOST('BANKING4DOLIBARR_STATEMENT_NUMBER_RULES', 'int');
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_STATEMENT_NUMBER_RULES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_REFRESH_BANK_RECORDS_RULES', 'int');
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_REFRESH_BANK_RECORDS_RULES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES', 'int');
		$value = $value >= 0 ? $value : '';
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES', 'int');
		$value = $value >= 0 ? $value : '';
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES', 'int');
		$value = $value >= 0 ? $value : '';
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES', 'int');
		$value = $value >= 0 ? $value : '';
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_UNPAID_DEBIT_MIN_OFFSET_DATES', 'int');
		$value = $value >= 0 ? $value : '';
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_UNPAID_DEBIT_MIN_OFFSET_DATES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_UNPAID_DEBIT_MAX_OFFSET_DATES', 'int');
		$value = $value >= 0 ? $value : '';
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_UNPAID_DEBIT_MAX_OFFSET_DATES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_UNPAID_CREDIT_MIN_OFFSET_DATES', 'int');
		$value = $value >= 0 ? $value : '';
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_UNPAID_CREDIT_MIN_OFFSET_DATES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_UNPAID_CREDIT_MAX_OFFSET_DATES', 'int');
		$value = $value >= 0 ? $value : '';
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_UNPAID_CREDIT_MAX_OFFSET_DATES', $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS', 'array');
		$value = !empty($value) ? $value : array('label', 'record_date', 'vdate', 'amount');
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS', implode(',', $value), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}
    } elseif ($action == 'set_reconciliation_options') {
        $value = GETPOST('BANKING4DOLIBARR_RECONCILIATION_SALARIES_DEFAULT_PERIOD_RULES', 'int');
        $res = dolibarr_set_const($db, 'BANKING4DOLIBARR_RECONCILIATION_SALARIES_DEFAULT_PERIOD_RULES', $value, 'chaine', 0, '', $conf->entity);
        if (!($res > 0)) {
            $errors[] = $db->lasterror();
            $error++;
        }
	} elseif ($action == 'set_notify_options' && $has_key) {
		$value = GETPOST('BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_USERS', 'array');
		$value = !empty($value) ? $value : array();
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_USERS', implode(',', $value), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}

		$value = GETPOST('BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_GROUPS', 'array');
		$value = !empty($value) ? $value : array();
		$res = dolibarr_set_const($db, 'BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_GROUPS', implode(',', $value), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$errors[] = $db->lasterror();
			$error++;
		}
	} elseif ($action == 'set_bank_colorize_movement_options') {
		// Save colors
		for ($i = 1; $i <= 2; $i++) {
			$color_key = 'BANK_COLORIZE_MOVEMENT_COLOR' . $i;
			$color = trim(GETPOST($color_key, 'alpha'));
			if ($color == '-1') $color = '';

			$res = dolibarr_set_const($db, $color_key, $color, 'chaine', 0, '', $conf->entity);
			if (!($res > 0)) {
				$errors[] = $db->lasterror();
				$error++;
			}
		}
	} elseif (preg_match('/set_(.*)/', $action, $reg)) {
		$code = $reg[1];
		$value = (GETPOST($code) ? GETPOST($code) : 1);
		$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
		if ($res < 0) {
			$errors[] = $db->lasterror();
			$error++;
		}
	} elseif (preg_match('/del_(.*)/', $action, $reg)) {
		$code = $reg[1];
		$res = dolibarr_del_const($db, $code, $conf->entity);
		if ($res < 0) {
			$errors[] = $db->lasterror();
			$error++;
		}
	}
}

if ($action != '') {
    if (!$error) {
	    $db->commit();
        setEventMessage($langs->trans("SetupSaved"));
	    header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
	    $db->rollback();
        setEventMessages('', $errors, 'errors');
    }
}


/*
 *	View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formbanking4dolibarr = new FormBanking4Dolibarr($db);

$wikihelp = 'EN:Banking4Dolibarr_En|FR:Banking4Dolibarr_Fr|ES:Banking4Dolibarr_Es';
llxHeader('', $langs->trans("Banking4DolibarrSetup"), $wikihelp);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("Banking4DolibarrSetup"), $linkback, 'title_setup');
print "<br>\n";

$head = banking4dolibarr_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163036Name"), 0, 'opendsi@banking4dolibarr');
$disabled_message = $form->textwithpicto($langs->trans('Banking4DolibarrFunctionalityDisabled'), $langs->trans('Banking4DolibarrDisabledBecauseYouDontHaveKey'));

/**
 * API settings.
 */

print '<div id="api_options"></div>';
print load_fiche_titre($langs->trans("Banking4DolibarrApiParameters"), '', '');
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '#api_options">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_api_options">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="right">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// Show redirect URI
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrRedirectUriName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrRedirectUriDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
$redirect_uri = banking4dolibarr_url_host($_SERVER) . dol_buildpath('/banking4dolibarr/core/modules/oauth/webview_callback.php', 1);
print '<input type="text" readonly="readonly" size="100" value="' . dol_escape_htmltag($redirect_uri) . '" />' . "\n";
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_MODULE_KEY
print '<tr class="oddeven">' . "\n";
print '<td style="vertical-align: top;">' . $langs->trans("Banking4DolibarrModuleKeyName") . '</td>' . "\n";
print '<td style="vertical-align: top;">' . $langs->trans("Banking4DolibarrModuleKeyDesc") . (empty($conf->global->BANKING4DOLIBARR_MODULE_KEY) ? $langs->trans("Banking4DolibarrModuleKeyPurchaseDesc") : '') . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print '<textarea name="BANKING4DOLIBARR_MODULE_KEY" rows="10" cols="100">' . $conf->global->BANKING4DOLIBARR_MODULE_KEY . '</textarea>' . "\n";
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_DEBUG
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrDebugName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrDebugDesc") . '</td>' . "\n";
print '<td class="right">' . "\n";
if ($has_key) {
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('BANKING4DOLIBARR_DEBUG');
	} else {
		if (empty($conf->global->BANKING4DOLIBARR_DEBUG)) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_BANKING4DOLIBARR_DEBUG#api_options">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
		} else {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_BANKING4DOLIBARR_DEBUG#api_options">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		}
	}
} else {
	print $disabled_message;
}
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_API_TIMEOUT
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrApiTimeOutName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrApiTimeOutDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
if ($has_key) {
	print '<input type="number" name="BANKING4DOLIBARR_API_TIMEOUT" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_API_TIMEOUT) . '" />' . "\n";
} else {
	print $disabled_message;
}
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_REQUEST_LIMIT
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrRequestLimitName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrRequestLimitDesc", BudgetInsight::DEFAULT_REQUEST_LIMIT, BudgetInsight::MAX_REQUEST_LIMIT) . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
if ($has_key) {
	print '<input type="number" name="BANKING4DOLIBARR_REQUEST_LIMIT" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_REQUEST_LIMIT) . '" />' . "\n";
} else {
	print $disabled_message;
}
print '</td></tr>' . "\n";


print '</table>' . "\n";

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

/**
 * Bank account settings.
 */

print '<br>';
print '<div id="bank_account_options"></div>';
print load_fiche_titre($langs->trans("Banking4DolibarrBankAccountParameters"), '', '');
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '#bank_account_options">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_bank_account_options">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="right">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// BANKING4DOLIBARR_AUTO_LINK_BANK_ACCOUNT
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrAutoLinkBankAccountName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrAutoLinkBankAccountDesc") . '</td>' . "\n";
print '<td class="right">' . "\n";
if ($has_key) {
	if (empty($conf->global->BANKING4DOLIBARR_AUTO_LINK_BANK_ACCOUNT)) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_auto_create_link_bank_account&value=1#bank_account_options">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_auto_create_link_bank_account&value=0#bank_account_options">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
} else {
	print $disabled_message;
}
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_AUTO_CREATE_BANK_ACCOUNT
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrAutoCreateBankAccountName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrAutoCreateBankAccountDesc") . '</td>' . "\n";
print '<td class="right">' . "\n";
if ($has_key) {
	if (empty($conf->global->BANKING4DOLIBARR_AUTO_CREATE_BANK_ACCOUNT)) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_auto_create_link_bank_account&value=3#bank_account_options">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_auto_create_link_bank_account&value=2#bank_account_options">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
} else {
	print $disabled_message;
}
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_STATEMENT_NUMBER_RULES
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrStatementNumberRulesName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrStatementNumberRulesDesc") . '</td>' . "\n";
print '<td class="right">' . "\n";
$statement_number_rules_list = array(
	BudgetInsight::STATEMENT_NUMBER_RULE_DAILY => $langs->trans('Banking4DolibarrStatementNumberRuleDaily'),
	BudgetInsight::STATEMENT_NUMBER_RULE_WEEKLY => $langs->trans('Banking4DolibarrStatementNumberRuleWeekly'),
	BudgetInsight::STATEMENT_NUMBER_RULE_MONTHLY => $langs->trans('Banking4DolibarrStatementNumberRuleMonthly'),
	BudgetInsight::STATEMENT_NUMBER_RULE_QUARTERLY => $langs->trans('Banking4DolibarrStatementNumberRuleQuarterly'),
	BudgetInsight::STATEMENT_NUMBER_RULE_FOUR_MONTHLY => $langs->trans('Banking4DolibarrStatementNumberRuleFourMonthly'),
	BudgetInsight::STATEMENT_NUMBER_RULE_YEARLY => $langs->trans('Banking4DolibarrStatementNumberRuleYearly'),
);
print $form->selectarray('BANKING4DOLIBARR_STATEMENT_NUMBER_RULES', $statement_number_rules_list, $conf->global->BANKING4DOLIBARR_STATEMENT_NUMBER_RULES);
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_REFRESH_BANK_RECORDS_RULES
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrRefreshBankRecordsRulesName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrRefreshBankRecordsRulesDesc") . '</td>' . "\n";
print '<td class="right">' . "\n";
$refresh_rules_list = array(
	BudgetInsight::REFRESH_BANK_RECORDS_RULE_DEBIT_CREDIT => $langs->trans('Debit') . ' / ' . $langs->trans('Credit'),
	BudgetInsight::REFRESH_BANK_RECORDS_RULE_DEBIT => $langs->trans('Debit'),
	BudgetInsight::REFRESH_BANK_RECORDS_RULE_CREDIT => $langs->trans('Credit'),
);
print $form->selectarray('BANKING4DOLIBARR_REFRESH_BANK_RECORDS_RULES', $refresh_rules_list, $conf->global->BANKING4DOLIBARR_REFRESH_BANK_RECORDS_RULES);
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrDebitMinOffsetDatesName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrDebitMinOffsetDatesDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print '<input type="number" name="BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_DEBIT_MIN_OFFSET_DATES) . '" />' . "\n";
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrDebitMaxOffsetDatesName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrDebitMaxOffsetDatesDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print '<input type="number" name="BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_DEBIT_MAX_OFFSET_DATES) . '" />' . "\n";
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrCreditMinOffsetDatesName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrCreditMinOffsetDatesDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print '<input type="number" name="BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_CREDIT_MIN_OFFSET_DATES) . '" />' . "\n";
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrCreditMaxOffsetDatesName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrCreditMaxOffsetDatesDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
print '<input type="number" name="BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_CREDIT_MAX_OFFSET_DATES) . '" />' . "\n";
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES
print '<tr class="oddeven">' . "\n";
print '<td><div id="enabled_unpaid_offset_dates"></div>' . $langs->trans("Banking4DolibarrEnabledUnpaidOffsetDatesName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrEnabledUnpaidOffsetDatesDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
if (empty($conf->global->BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES)) {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES#enabled_unpaid_offset_dates">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES#enabled_unpaid_offset_dates">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

if (!empty($conf->global->BANKING4DOLIBARR_ENABLED_UNPAID_OFFSET_DATES)) {
	// BANKING4DOLIBARR_UNPAID_DEBIT_MIN_OFFSET_DATES
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("Banking4DolibarrUnpaidDebitMinOffsetDatesName") . '</td>' . "\n";
	print '<td>' . $langs->trans("Banking4DolibarrUnpaidDebitMinOffsetDatesDesc") . '</td>' . "\n";
	print '<td class="nowrap right">' . "\n";
	print '<input type="number" name="BANKING4DOLIBARR_UNPAID_DEBIT_MIN_OFFSET_DATES" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_UNPAID_DEBIT_MIN_OFFSET_DATES) . '" />' . "\n";
	print '</td></tr>' . "\n";

	// BANKING4DOLIBARR_UNPAID_DEBIT_MAX_OFFSET_DATES
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("Banking4DolibarrUnpaidDebitMaxOffsetDatesName") . '</td>' . "\n";
	print '<td>' . $langs->trans("Banking4DolibarrUnpaidDebitMaxOffsetDatesDesc") . '</td>' . "\n";
	print '<td class="nowrap right">' . "\n";
	print '<input type="number" name="BANKING4DOLIBARR_UNPAID_DEBIT_MAX_OFFSET_DATES" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_UNPAID_DEBIT_MAX_OFFSET_DATES) . '" />' . "\n";
	print '</td></tr>' . "\n";

	// BANKING4DOLIBARR_UNPAID_CREDIT_MIN_OFFSET_DATES
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("Banking4DolibarrUnpaidCreditMinOffsetDatesName") . '</td>' . "\n";
	print '<td>' . $langs->trans("Banking4DolibarrUnpaidCreditMinOffsetDatesDesc") . '</td>' . "\n";
	print '<td class="nowrap right">' . "\n";
	print '<input type="number" name="BANKING4DOLIBARR_UNPAID_CREDIT_MIN_OFFSET_DATES" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_UNPAID_CREDIT_MIN_OFFSET_DATES) . '" />' . "\n";
	print '</td></tr>' . "\n";

	// BANKING4DOLIBARR_UNPAID_CREDIT_MAX_OFFSET_DATES
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("Banking4DolibarrUnpaidCreditMaxOffsetDatesName") . '</td>' . "\n";
	print '<td>' . $langs->trans("Banking4DolibarrUnpaidCreditMaxOffsetDatesDesc") . '</td>' . "\n";
	print '<td class="nowrap right">' . "\n";
	print '<input type="number" name="BANKING4DOLIBARR_UNPAID_CREDIT_MAX_OFFSET_DATES" size="100" value="' . dol_escape_htmltag($conf->global->BANKING4DOLIBARR_UNPAID_CREDIT_MAX_OFFSET_DATES) . '" />' . "\n";
	print '</td></tr>' . "\n";
}

// BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrDuplicateTestOnFieldsName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrDuplicateTestOnFieldsDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
$duplicate_test_on_fields_list = array(
	'label' => $langs->trans("Description"),
	'comment' => $langs->trans("Comment"),
	'record_date' => $langs->trans("DateOperation"),
	'vdate' => $langs->trans("DateValue"),
	'rdate' => $langs->trans("Banking4DolibarrDateRealization"),
	'bdate' => $langs->trans("Banking4DolibarrDateBank"),
	'amount' => $langs->trans("Amount"),
	'record_type' => $langs->trans("Type"),
	'id_category' => $langs->trans("Banking4DolibarrCategory"),
	'original_country' => $langs->trans("Country"),
	'original_amount' => $langs->trans("Banking4DolibarrOriginAmount"),
	'original_currency' => $langs->trans("Banking4DolibarrOriginCurrency"),
	'commission' => $langs->trans("Banking4DolibarrCommissionAmount"),
	'commission_currency' => $langs->trans("Banking4DolibarrCommissionCurrency"),
);
print $form->multiselectarray('BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS', $duplicate_test_on_fields_list, explode(',', $conf->global->BANKING4DOLIBARR_DUPLICATE_TEST_ON_FIELDS), 0, 0, ' minwidth300');
print '</td></tr>' . "\n";

print '</table>' . "\n";

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

/**
 * Reconciliation settings.
 */

print '<br>';
print '<div id="reconciliation_options"></div>';
print load_fiche_titre($langs->trans("Banking4DolibarrReconciliationParameters"), '', '');
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '#reconciliation_options">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_reconciliation_options">';

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">\n";
print '<td colspan="4">' . $langs->trans("Name") . '</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

print '<tr class="oddeven"><td colspan="4" width="100">';
print $langs->trans('Banking4DolibarrReconciliationSalariesDefaultPeriodDesc');
print '</td><td class="right">' . "\n";
$salaries_reconciliation_rules_list = array(0=>$langs->trans("PreviousMonth"), 1=>$langs->trans("CurrentMonth"));
print $form->selectarray('BANKING4DOLIBARR_RECONCILIATION_SALARIES_DEFAULT_PERIOD_RULES', $salaries_reconciliation_rules_list, (isset($conf->global->BANKING4DOLIBARR_RECONCILIATION_SALARIES_DEFAULT_PERIOD_RULES) ? $conf->global->BANKING4DOLIBARR_RECONCILIATION_SALARIES_DEFAULT_PERIOD_RULES : 0));
print '</td></tr>' . "\n";
print '</table>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

/**
 * Notify settings.
 */
print '<br>';
print '<div id="notify_options"></div>';
print load_fiche_titre($langs->trans("Banking4DolibarrNotifyParameters"), '', '');
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '#notify_options">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_notify_options">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="right">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrNotifyEmailRefreshBankRecordsName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrNotifyEmailRefreshBankRecordsDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
if ($has_key) {
	if (empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS)) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS#notify_options">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS#notify_options">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
} else {
	print $disabled_message;
}
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_USERS
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrNotifyEmailRefreshBankRecordsUsersName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrNotifyEmailRefreshBankRecordsUsersDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
if ($has_key) {
	print $formbanking4dolibarr->multiselect_dolusers(explode(',', $conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_USERS), 'BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_USERS', '', empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS), '', array(), 0, 0, 0, 'AND fk_soc IS NULL', 0, '', '', 1);
} else {
	print $disabled_message;
}
print '</td></tr>' . "\n";

// BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_GROUPS
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrNotifyEmailRefreshBankRecordsGroupsName") . '</td>' . "\n";
print '<td>' . $langs->trans("Banking4DolibarrNotifyEmailRefreshBankRecordsGroupsDesc") . '</td>' . "\n";
print '<td class="nowrap right">' . "\n";
if ($has_key) {
	print $formbanking4dolibarr->multiselect_dolgroups(explode(',', $conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_GROUPS), 'BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS_GROUPS', '', empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS), '', 1);
} else {
	print $disabled_message;
}
print '</td></tr>' . "\n";

print '</table>' . "\n";

if ($has_key && !empty($conf->global->BANKING4DOLIBARR_NOTIFY_EMAIL_REFRESH_BANK_RECORDS)) {
	print '<br>';
	print '<div align="center">';
	print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
	print '</div>';
}

print '</form>';

/**
 * Bank colorize movement settings.
 */

print '<br>';
print '<div id="bank_colorize_movement_options"></div>';
print load_fiche_titre($langs->trans("BankColorizeMovement"), '', '');
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '#bank_colorize_movement_options">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_bank_colorize_movement_options">';

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">\n";
print '<td colspan="4">' . $langs->trans("Name") . '</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

print '<tr class="oddeven"><td colspan="4" width="100">';
print $langs->trans('BankColorizeMovementDesc');
print '</td><td class="right">' . "\n";
if (empty($conf->global->BANK_COLORIZE_MOVEMENT)) {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_BANK_COLORIZE_MOVEMENT#bank_colorize_movement_options">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_BANK_COLORIZE_MOVEMENT#bank_colorize_movement_options">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

if (!empty($conf->global->BANK_COLORIZE_MOVEMENT)) {
	for ($i = 1; $i <= 2; $i++) {
		$color_key = 'BANK_COLORIZE_MOVEMENT_COLOR' . $i;

		print '<tr class="oddeven">';
		// Label
		print '<td colspan="4" width="180" class="nowrap">' . $langs->trans("BankColorizeMovementName" . $i) . "</td>";
		// Color
		print '<td class="nowrap right">';
		print $formother->selectColor((GETPOST($color_key) ? GETPOST($color_key) : $conf->global->$color_key), $color_key, 'bankmovementcolorconfig', 1, '', 'right hideifnotset');
		print '</td>';
		print "</tr>";
	}
}
print '</table>';

if (!empty($conf->global->BANK_COLORIZE_MOVEMENT)) {
	print '<br>';
	print '<div align="center">';
	print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
	print '</div>';
}

print '</form>';

dol_fiche_end();

llxFooter();

$db->close();
