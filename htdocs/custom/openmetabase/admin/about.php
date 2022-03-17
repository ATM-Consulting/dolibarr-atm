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
 *	    \file       htdocs/openmetabase/admin/about.php
 *		\ingroup    openmetabase
 *		\brief      Page about of openmetabase module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists('../../main.inc.php'))     $res = @include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists('../../../main.inc.php'))  $res = @include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (!$res) die('Include of main fails');
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/openmetabase/lib/openmetabase.lib.php');

$langs->loadLangs(array('admin', 'openmetabase@openmetabase', 'opendsi@openmetabase'));

if (!$user->admin)  accessforbidden();


/**
 * View
 */
$help_url = 'EN:OpenMetabase_En|FR:OpenMetabase_Fr|ES:OpenMetabase_Es';
llxHeader('', $langs->trans('OpenMetabaseSetup'), $help_url);

$linkback='<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($langs->trans('OpenMetabaseSetup'), $linkback, 'title_setup');
print '<br>';

$head = openmetabase_admin_prepare_head();

dol_fiche_head($head, 'about', $langs->trans('About'), 0, 'opendsi@openmetabase');

print '<table width="100%"><tr>';
print '<td width="310px"><img src="../img/opendsi_dolibarr_preferred_partner.png" /></td>';
print '<td align="left" valign="top"><p>' . $langs->trans('OpenDsiAboutDesc') . '</p></td>';
print '</tr></table>';

dol_fiche_end();

llxFooter();

$db->close();
