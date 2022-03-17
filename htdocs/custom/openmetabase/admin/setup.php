<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/openmetabase/admin/setup.php
 *		\ingroup    openmetabase
 *		\brief      Page to setup openmetabase module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists('../../main.inc.php'))     $res = @include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists('../../../main.inc.php'))  $res = @include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (!$res) die('Include of main fails');

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/openmetabase/lib/openmetabase.lib.php');

global $langs,$user,$db, $conf;

$langs->loadLangs(array('admin', 'openmetabase@openmetabase', 'opendsi@openmetabase'));

if (!$user->admin)  accessforbidden();

$action = GETPOST('action', 'alpha');

$hidden_password = '******';

/**
 * Action
 */
$error = 0;
$errors = array();

if ($action == 'set') {
    if (!$error) {
        $res = dolibarr_set_const($db, 'OPENMETABASE_API_TIMEOUT', GETPOST('OPENMETABASE_API_TIMEOUT', 'int'), 'chaine', 0, '', 0);
        if ($res < 0) {
            $error++;
            $errors[] = $db->lasterror();
        }
    }
    if (!$error) {
        $res = dolibarr_set_const($db, 'OPENMETABASE_API_URI_BASE', rtrim(GETPOST('OPENMETABASE_API_URI_BASE', 'alpha'), '/'), 'chaine', 0, '', 0);
        if ($res < 0) {
            $error++;
            $errors[] = $db->lasterror();
        }
    }
    if (!$error) {
        $value = GETPOST('OPENMETABASE_API_TOKEN', 'alpha');
        if ($value != $hidden_password) {
            $res = dolibarr_set_const($db, 'OPENMETABASE_API_TOKEN', $value, 'chaine', 0, '', 0);
            if ($res < 0) {
                $error++;
                $errors[] = $db->lasterror();
            }
        }
    }
	if (!$error) {
		$value = trim(GETPOST('OPENMETABASE_IGNORE_TABLE', 'alpha'));
//		if (empty($value)) $value = "llx_blockedlog;llx_blockedlog_authority;llx_actioncomm;llx_actioncomm_extrafields;llx_actioncomm_reminder;llx_actioncomm_resources";
		$res = dolibarr_set_const($db, 'OPENMETABASE_IGNORE_TABLE', GETPOST('OPENMETABASE_IGNORE_TABLE', 'alpha'), 'chaine', 0, '', 0);
		if ($res < 0) {
			$error++;
			$errors[] = $db->lasterror();
		}
	}
} elseif (preg_match('/set_(.*)/',$action,$reg)) {
    $code=$reg[1];
    $value=(GETPOST($code) ? GETPOST($code) : 1);

    $res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
        $errors[] = $db->lasterror();
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code = $reg[1];

    $res = dolibarr_del_const($db, $code, $conf->entity);
    if (!$res > 0) {
        $error++;
        $errors[] = $db->lasterror();
    }
}

if ($action != '') {
    if ($error) {
        setEventMessages('', $errors, 'errors');
    } else {
        setEventMessage($langs->trans('SetupSaved'));
        header('Location: ' . $_SERVER["PHP_SELF"]);
        exit();
    }
}


/**
 * View
 */
$help_url = 'EN:OpenMetabase_En|FR:OpenMetabase_Fr|ES:OpenMetabase_Es';
llxHeader('', $langs->trans('OpenMetabaseSetup'), $help_url);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($langs->trans('OpenMetabaseSetup'), $linkback, 'title_setup');
print '<br>';

$head = openmetabase_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans('Parameters'), -1, 'opendsi@openmetabase');

print '<br>';
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
print '<input type="hidden" name="action" value="set" />';

$var = true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="right">' . $langs->trans('Value') . '</td>';
print '</tr>';

// OPENMETABASE_DEBUG
print '<tr class="oddeven">';
print '<td>'.$langs->trans("OpenMetabaseDebugName").'</td>'."\n";
print '<td>'.$langs->trans("OpenMetabaseDebugDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('OPENMETABASE_DEBUG');
} else {
    if (empty($conf->global->OPENMETABASE_DEBUG)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_OPENMETABASE_DEBUG">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_OPENMETABASE_DEBUG">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// OPENMETABASE_API_TIMEOUT
print '<tr class="oddeven">';
print '<td>'.$langs->trans("OpenMetabaseApiTimeOutName").'</td>'."\n";
print '<td>'.$langs->trans("OpenMetabaseApiTimeOutDesc").'</td>'."\n";
print '<td align="right" class="nowrap">'."\n";
print '<input type="number" name="OPENMETABASE_API_TIMEOUT" size="150" placeholder="60" value="'.dol_escape_htmltag($conf->global->OPENMETABASE_API_TIMEOUT).'" />'."\n";
print '</td></tr>'."\n";

// OPENMETABASE_API_URI_BASE
print '<tr class="oddeven">';
print '<td>' . $langs->trans('OpenMetabaseAPIUriBaseName') . '</td>';
print '<td>' . $langs->trans('OpenMetabaseAPIUriBaseDesc') . '</td>';
print '<td class="nowrap right">';
print '<input type="text" name="OPENMETABASE_API_URI_BASE" size="150" value="' . dol_escape_htmltag($conf->global->OPENMETABASE_API_URI_BASE) . '" />';
print '</td></tr>';

// OPENMETABASE_API_TOKEN
print '<tr class="oddeven">';
print '<td>' . $langs->trans('OpenMetabaseAPITokenName') . '</td>';
print '<td>' . $langs->trans('OpenMetabaseAPITokenDesc') . '</td>';
print '<td class="nowrap right">';
print '<input type="password" name="OPENMETABASE_API_TOKEN" size="150" value="' . (!empty($conf->global->OPENMETABASE_API_TOKEN) ? $hidden_password : '') . '" />';
print '</td></tr>';

// OPENMETABASE_IGNORE_TABLE
print '<tr class="oddeven">';
print '<td>' . $langs->trans('OpenMetabaseIgnoreTableName') . '</td>';
print '<td>' . $langs->trans('OpenMetabaseIgnoreTableDesc') . '</td>';
print '<td class="nowrap right">';
print '<input type="text" name="OPENMETABASE_IGNORE_TABLE" size="150" value="' . dol_escape_htmltag($conf->global->OPENMETABASE_IGNORE_TABLE) . '"placeholder="llx_blockedlog;llx_blockedlog_authority;llx_actioncomm;llx_actioncomm_extrafields;llx_actioncomm_reminder;llx_actioncomm_resources"/>';
print '</td></tr>';

print '</table>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '" />';
print '</div>';

print '</form>';

dol_fiche_end();

llxFooter();
$db->close();
