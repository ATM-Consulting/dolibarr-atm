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
 *	    \file       htdocs/demat4dolibarr/admin/setup.php
 *		\ingroup    demat4dolibarr
 *		\brief      Page to setup demat4dolibarr module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/demat4dolibarr/lib/demat4dolibarr.lib.php');
dol_include_once('/demat4dolibarr/class/module_key/opendsimodulekeyd4d.class.php');
dol_include_once('/demat4dolibarr/class/ededoc.class.php');
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

$langs->load("admin");
$langs->load("demat4dolibarr@demat4dolibarr");
$langs->load("opendsi@demat4dolibarr");
$langs->load("oauth");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

/*
 *	Actions
 */

$errors = [];
$error = 0;
$db->begin();

if ($action == 'set_debug') {
    $value = GETPOST('value', 'int');

    if ($value) {
        $res = dolibarr_set_const($db, 'DEMAT4DOLIBARR_DEBUG', 1, 'chaine', 0, '', $conf->entity);
        if (!$res > 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    } else {
        $res = dolibarr_del_const($db, 'DEMAT4DOLIBARR_DEBUG', $conf->entity);
        if (!$res > 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    }

    if (!$error) {
        include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($db);

        $res = $extrafields->update('d4d_tech_separator', $langs->trans('Demat4DolibarrSeparatorChorusTech'), 'separate', '', 'facture', 0, 0, 1107, array('options' => array('2' => null)), 0, 0, $value ? 3 : 0, 0, 0, '', '', '', '1');
        if (!$res > 0) {
            $errors[] = $extrafields->error;
            $error++;
        }
    }
    if (!$error) {
        $result = $extrafields->update('d4d_job_id', $langs->trans('Demat4DolibarrJobId'), 'varchar', 36, 'facture', 0, 0, 1108, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
        if (!$res > 0) {
            $errors[] = $extrafields->error;
            $error++;
        }
    }
    if (!$error) {
        $result = $extrafields->update('d4d_job_owner', $langs->trans('Demat4DolibarrJobOwner'), 'varchar', 255, 'facture', 0, 0, 1109, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
        if (!$res > 0) {
            $errors[] = $extrafields->error;
            $error++;
        }
    }
    if (!$error) {
        $result = $extrafields->update('d4d_job_status', $langs->trans('Demat4DolibarrJobStatus'), 'sellist', '', 'facture', 0, 0, 1110, array('options' => array('c_demat4dolibarr_job_status:short_label:rowid::active=1' => null)), 1, '', $value ? 1 : 0, 0, '', '', '', '', '1');
        if (!$res > 0) {
            $errors[] = $extrafields->error;
            $error++;
        }
    }
    if (!$error) {
        $result = $extrafields->update('d4d_job_suspension_reason', $langs->trans('Demat4DolibarrJobSuspensionReason'), 'text', '', 'facture', 0, 0, 1111, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
        if (!$res > 0) {
            $errors[] = $extrafields->error;
            $error++;
        }
    }
    if (!$error) {
        $result = $extrafields->update('d4d_chorus_id', $langs->trans('Demat4DolibarrChorusId'), 'varchar', 36, 'facture', 0, 0, 1112, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
        if (!$res > 0) {
            $errors[] = $extrafields->error;
            $error++;
        }
    }
    if (!$error) {
        $result = $extrafields->update('d4d_chorus_invoice_id', $langs->trans('Demat4DolibarrChorusInvoiceId'), 'int', 10, 'facture', 0, 0, 1113, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
        if (!$res > 0) {
            $errors[] = $extrafields->error;
            $error++;
        }
    }
    if (!$error) {
        $result = $extrafields->update('d4d_chorus_submit_date', $langs->trans('Demat4DolibarrChorusSubmitDate'), 'datetime', '', 'facture', 0, 0, 1114, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
        if (!$res > 0) {
            $errors[] = $extrafields->error;
            $error++;
        }
    }
	if (!$error) {
		$result = $extrafields->update('d4d_chorus_status_error_message', $langs->trans('Demat4DolibarrChorusStatusErrorMessage'), 'text', '', 'facture', 0, 0, 1115, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
		if (!$res > 0) {
			$errors[] = $extrafields->error;
			$error++;
		}
	}
	if (!$error) {
		$result = $extrafields->update('d4d_invoice_id', $langs->trans('Demat4DolibarrInvoiceId'), 'varchar', 36, 'facture', 0, 0, 1116, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
		if (!$res > 0) {
			$errors[] = $extrafields->error;
			$error++;
		}
	}
	if (!$error) {
		$result = $extrafields->update('d4d_invoice_create_on', $langs->trans('Demat4DolibarrInvoiceCreateOn'), 'datetime', '', 'facture', 0, 0, 1117, null, 0, '', $value ? 1 : 0, 0, '', '', '', '', '1');
		if (!$res > 0) {
			$errors[] = $extrafields->error;
			$error++;
		}
	}
} elseif ($action == 'set_options') {
	require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
	$storage = new OAuth\Common\Storage\DoliStorage($db, $conf);
	$storage->clearToken(EdeDoc::SERVICE_NAME . '_' . $conf->entity);

	$value = GETPOST('DEMAT4DOLIBARR_PROVIDER_CODE', 'alpha');
	if (empty($value)) {
		$errors[] = $langs->trans('Demat4DolibarrErrorProviderCodeNotDefined');
		$error++;
	} elseif (strlen($value) > 50) {
		$errors[] = $langs->trans('Demat4DolibarrErrorProviderCodeTooLong', 50);
		$error++;
	}
	$res = dolibarr_set_const($db, 'DEMAT4DOLIBARR_PROVIDER_CODE', $value, 'chaine', 0, '', $conf->entity);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$resetAccessToken = false;
	$value = GETPOST('DEMAT4DOLIBARR_MODULE_KEY', 'alpha');
	$result = OpenDsiModuleKeyD4D::decode($value);
	if (!empty($result['error'])) {
		$errors[] = $result['error'];
		$error++;
	} elseif ($value != $conf->global->DEMAT4DOLIBARR_MODULE_KEY) {
		$module_key_infos = $result['key'];
		$resetAccessToken = true;
	}
	$res = dolibarr_set_const($db, 'DEMAT4DOLIBARR_MODULE_KEY', $value, 'chaine', 0, '', $conf->entity);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}
	if ($resetAccessToken) {
		$ededoc = new EdeDoc($db);

		$result = $ededoc->deleteAccessToken();
		if ($result < 0) {
			if (!empty($ededoc->error)) $errors[] = $ededoc->error;
			$errors = array_merge($ededoc->errors, $errors);
			$error++;
		}
	}

	$value = GETPOST('DEMAT4DOLIBARR_FILES_TYPE', 'alpha');
	$value = empty($value) ? '.pdf' : $value;
	$res = dolibarr_set_const($db, 'DEMAT4DOLIBARR_FILES_TYPE', $value, 'chaine', 0, '', $conf->entity);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('DEMAT4DOLIBARR_API_TIMEOUT', 'int');
	$value = $value > 5 ? $value : 5;
	$res = dolibarr_set_const($db, 'DEMAT4DOLIBARR_API_TIMEOUT', $value, 'chaine', 0, '', $conf->entity);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

    $value = GETPOST('DEMAT4DOLIBARR_DEFAULT_BILLING_MODE', 'int');
    $res = dolibarr_set_const($db, 'DEMAT4DOLIBARR_DEFAULT_BILLING_MODE', $value, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }
} elseif ($action == 'set_generate_file') {
    $value = GETPOST('value', 'int');

    if ($value == 1) {
        $res = dolibarr_set_const($db, 'DEMAT4DOLIBARR_INVOICE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS_IF_NO_FILES', 1, 'chaine', 0, '', $conf->entity);
        if (!$res > 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    }

    if ($value == 3) {
        $res = dolibarr_set_const($db, 'DEMAT4DOLIBARR_INVOICE_FORCE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS', 1, 'chaine', 0, '', $conf->entity);
        if (!$res > 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    }

    if ($value == 3 || $value == 0) {
        $res = dolibarr_del_const($db, 'DEMAT4DOLIBARR_INVOICE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS_IF_NO_FILES', $conf->entity);
        if ($res < 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    }

    if ($value == 1 || $value == 2) {
        $res = dolibarr_del_const($db, 'DEMAT4DOLIBARR_INVOICE_FORCE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS', $conf->entity);
        if ($res < 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    }
} elseif (preg_match('/set_(.*)/',$action,$reg)) {
    $code = $reg[1];
    $value = (GETPOST($code) ? GETPOST($code) : 1);
	$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
	if ($res < 0) {
        $errors[] = $db->lasterror();
        $error++;
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);
    if ($res < 0) {
        $errors[] = $db->lasterror();
        $error++;
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
        setEventMessages(/*$langs->trans("Error")*/'', $errors, 'errors');
    }
}


/*
 *	View
 */

$form = new Form($db);
$formdictionary = new FormDictionary($db);

$wikihelp='EN:Demat4Dolibarr_En|FR:Demat4Dolibarr_Fr|ES:Demat4Dolibarr_Es';
llxHeader('', $langs->trans("Demat4DolibarrSetup"), $wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("Demat4DolibarrSetup"),$linkback,'title_setup');
print "<br>\n";

$head=demat4dolibarr_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163028Name"), 0, 'opendsi@demat4dolibarr');

print '<br>';

/**
 * Settings.
 */

print '<div id="options"></div>';
print load_fiche_titre($langs->trans("Demat4DolibarrApi"),'','');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_options">';

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Parameters").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// DEMAT4DOLIBARR_DEBUG
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("Demat4DolibarrDebugName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrDebugDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (empty($conf->global->DEMAT4DOLIBARR_DEBUG)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_debug&value=1">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_debug&value=0">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// DEMAT4DOLIBARR_TEST
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("Demat4DolibarrTestName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrTestDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('DEMAT4DOLIBARR_TEST');
} else {
    if (empty($conf->global->DEMAT4DOLIBARR_TEST)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_DEMAT4DOLIBARR_TEST">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_DEMAT4DOLIBARR_TEST">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// DEMAT4DOLIBARR_PROVIDER_CODE
$var = !$var;
print '<tr ' . $bc[$var] . '>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrProviderCodeName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrProviderCodeDesc").'</td>'."\n";
print '<td align="right" class="nowrap">'."\n";
print '<input type="text" name="DEMAT4DOLIBARR_PROVIDER_CODE" size="100" value="'.dol_escape_htmltag($conf->global->DEMAT4DOLIBARR_PROVIDER_CODE).'" />'."\n";
print '</td></tr>'."\n";

// DEMAT4DOLIBARR_MODULE_KEY
$var = !$var;
print '<tr ' . $bc[$var] . '>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrModuleKeyName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrModuleKeyDesc").(empty($conf->global->DEMAT4DOLIBARR_MODULE_KEY) ? $langs->trans("Demat4DolibarrModuleKeyPurchaseDesc") : '').'</td>'."\n";
print '<td align="right" class="nowrap">'."\n";
print '<textarea name="DEMAT4DOLIBARR_MODULE_KEY" rows="10" cols="100">'.$conf->global->DEMAT4DOLIBARR_MODULE_KEY.'</textarea>'."\n";
print '</td></tr>'."\n";

// DEMAT4DOLIBARR_FILES_TYPE
$var = !$var;
print '<tr ' . $bc[$var] . '>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrFilesTypeName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrFilesTypeDesc").'</td>'."\n";
print '<td align="right" class="nowrap">'."\n";
print '<input type="text" name="DEMAT4DOLIBARR_FILES_TYPE" size="100" value="'.dol_escape_htmltag($conf->global->DEMAT4DOLIBARR_FILES_TYPE).'" />'."\n";
print '</td></tr>'."\n";

// DEMAT4DOLIBARR_API_TIMEOUT
$var = !$var;
print '<tr ' . $bc[$var] . '>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrApiTimeOutName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrApiTimeOutDesc").'</td>'."\n";
print '<td align="right" class="nowrap">'."\n";
print '<input type="number" name="DEMAT4DOLIBARR_API_TIMEOUT" size="100" value="'.dol_escape_htmltag($conf->global->DEMAT4DOLIBARR_API_TIMEOUT).'" />'."\n";
print '</td></tr>'."\n";

// DEMAT4DOLIBARR_DEFAULT_BILLING_MODE
$var = !$var;
print '<tr ' . $bc[$var] . '>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrDefaultBillingModeName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrDefaultBillingModeDesc").'</td>'."\n";
print '<td align="right" class="nowrap">'."\n";
print $formdictionary->select_dictionary('demat4dolibarr', 'demat4dolibarrbillingmode', $conf->global->DEMAT4DOLIBARR_DEFAULT_BILLING_MODE, 'DEMAT4DOLIBARR_DEFAULT_BILLING_MODE', 1, 'rowid', '{{code}} - {{label}}', array(), array('code' => 'ASC', 'label' => 'ASC'));
print '</td></tr>'."\n";

// DEMAT4DOLIBARR_BOX_SHOW_ONLY_STATUS_WHO_HAVE_INVOICE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("Demat4DolibarrBoxShowOnlyStatusWhoHaveInvoiceName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrBoxShowOnlyStatusWhoHaveInvoiceDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('DEMAT4DOLIBARR_BOX_SHOW_ONLY_STATUS_WHO_HAVE_INVOICE');
} else {
    if (empty($conf->global->DEMAT4DOLIBARR_BOX_SHOW_ONLY_STATUS_WHO_HAVE_INVOICE)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_DEMAT4DOLIBARR_BOX_SHOW_ONLY_STATUS_WHO_HAVE_INVOICE">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_DEMAT4DOLIBARR_BOX_SHOW_ONLY_STATUS_WHO_HAVE_INVOICE">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// DEMAT4DOLIBARR_INVOICE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS_IF_NO_FILES
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("Demat4DolibarrInvoiceGenerateFileBeforeSendToChorusIfNoFilesName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrInvoiceGenerateFileBeforeSendToChorusIfNoFilesDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (empty($conf->global->DEMAT4DOLIBARR_INVOICE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS_IF_NO_FILES)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_generate_file&value=1">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_generate_file&value=0">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// DEMAT4DOLIBARR_INVOICE_FORCE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("Demat4DolibarrInvoiceForceGenerateFileBeforeSendToChorusName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrInvoiceForceGenerateFileBeforeSendToChorusDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (empty($conf->global->DEMAT4DOLIBARR_INVOICE_FORCE_GENERATE_FILE_BEFORE_SEND_TO_CHORUS)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_generate_file&value=3">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_generate_file&value=2">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// DEMAT4DOLIBARR_INVOICE_DIRECT_SEND_TO_CHORUS_IF_ONLY_ONE_FILE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("Demat4DolibarrInvoiceDirectSendToChorusIfOnlyOneFileName").'</td>'."\n";
print '<td>'.$langs->trans("Demat4DolibarrInvoiceDirectSendToChorusIfOnlyOneFileDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('DEMAT4DOLIBARR_INVOICE_DIRECT_SEND_TO_CHORUS_IF_ONLY_ONE_FILE');
} else {
    if (empty($conf->global->DEMAT4DOLIBARR_INVOICE_DIRECT_SEND_TO_CHORUS_IF_ONLY_ONE_FILE)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_DEMAT4DOLIBARR_INVOICE_DIRECT_SEND_TO_CHORUS_IF_ONLY_ONE_FILE">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_DEMAT4DOLIBARR_INVOICE_DIRECT_SEND_TO_CHORUS_IF_ONLY_ONE_FILE">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

print '</table>'."\n";

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();

$db->close();
