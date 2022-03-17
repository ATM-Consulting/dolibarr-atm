<?php
/* Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/banking4dolibarr/lib/banking4dolibarr.lib.php
 * 	\ingroup	banking4dolibarr
 *	\brief      Functions for the module banking4dolibarr
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function banking4dolibarr_admin_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/banking4dolibarr/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;

    $langs->load("banks");
    $head[$h][0] = dol_buildpath("/banking4dolibarr/admin/accounts.php", 1);
    $head[$h][1] = $langs->trans("BankAccounts");
    $head[$h][2] = 'accounts';
    $h++;

    $head[$h][0] = dol_buildpath("/banking4dolibarr/admin/dictionaries.php", 1);
    $head[$h][1] = $langs->trans("Dictionary");
    $head[$h][2] = 'dictionaries';
    $h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'banking4dolibarr_admin');

	$head[$h][0] = dol_buildpath("/banking4dolibarr/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	$head[$h][0] = dol_buildpath("/banking4dolibarr/admin/changelog.php", 1);
	$head[$h][1] = $langs->trans("OpenDsiChangeLog");
	$head[$h][2] = 'changelog';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'banking4dolibarr_admin', 'remove');

	return $head;
}

/**
 * Get host url
 *
 * @param   array       $s                      $_SERVER handler
 * @param   bool        $use_forwarded_host     if use forwarded host
 * @return  string                              Return l'url host (ex: http://www.test.com)
 */
function banking4dolibarr_url_host( $s, $use_forwarded_host = false )
{
    $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on');
    $sp = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $s['SERVER_PORT'];
    $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
    $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}
