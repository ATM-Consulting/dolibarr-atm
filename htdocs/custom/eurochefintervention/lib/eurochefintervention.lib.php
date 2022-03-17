<?php
/* Copyright (C) 2021      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/eurochefintervention/lib/eurochefintervention.lib.php
 * 	\ingroup	eurochefintervention
 *	\brief      Functions for the module eurochefintervention
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function eurochefintervention_admin_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/eurochefintervention/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'eurochefintervention_admin');

	$head[$h][0] = dol_buildpath("/eurochefintervention/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	$head[$h][0] = dol_buildpath("/eurochefintervention/admin/changelog.php", 1);
	$head[$h][1] = $langs->trans("OpenDsiChangeLog");
	$head[$h][2] = 'changelog';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'eurochefintervention_admin', 'remove');

	return $head;
}

/**
 * Get product by this ID (cached)
 *
 * @param	DoliDB		$db				Database handler
 * @param 	int			$product_id		Product ID
 * @return	Product						Product handler
 */
function eurochefintervention_get_product($db, $product_id) {
	global $eurochefintervention_product_cached;

	if (!isset($eurochefintervention_product_cached)) $eurochefintervention_product_cached = array();

	if (!isset($eurochefintervention_product_cached[$product_id])) {
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		$product_static = new Product($db);
		$product_static->fetch($product_id);

		$eurochefintervention_product_cached[$product_id] = $product_static;
	}

	return $eurochefintervention_product_cached[$product_id];
}
