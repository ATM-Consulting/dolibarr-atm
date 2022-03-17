<?php
/* Copyright (C) 2020      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/banking4dolibarr/class/module_key/opendsimodulekeyb4d.class.php
 * \ingroup banking4dolibarr
 * \brief
 */

if (class_exists('OpenDsiModuleKeyB4D', false))
	return 0;
dol_include_once('/banking4dolibarr/class/module_key/opendsimodulekeybase.class.php');

/**
 * OpenDsiModuleKeyB4D
 */
class OpenDsiModuleKeyB4D extends OpenDsiModuleKeyBase
{
	/**
	 * Init variables
	 */
	protected static function init()
	{
		global $langs;
		$langs->load('opendsipartner@opendsipartner');

		self::$errors = array(
			'module_key_not_provided' => $langs->trans('Banking4DolibarrErrorModuleKeyNotProvided'),
			'invalid_module_key' => $langs->trans('Banking4DolibarrErrorInvalidModuleKey'),
			'encrypt_module_key_fail' => $langs->trans('Banking4DolibarrErrorEncryptModuleKeyFail'),
			'decrypt_module_key_fail' => $langs->trans('Banking4DolibarrErrorDecryptModuleKeyFail'),
		);
	}

	/**
	 * Verify the module key
	 *
	 * @param	stdClass	$module_key		Module key
	 * @return	bool
	 */
	public static function verify($module_key)
	{
		if (empty($module_key->api_url) || empty($module_key->client_id) ||
			empty($module_key->key) || empty($module_key->customer_id) ||
			empty($module_key->bridge_url)
		) {
			return false;
		}

		$module_key->webview_language = in_array($module_key->webview_language, array('fr', 'en')) ? $module_key->webview_language : 'fr';
		$module_key->bank_quota = !empty($module_key->bank_quota) ? $module_key->bank_quota : 0;
		$module_key->bank_account_quota = !empty($module_key->bank_account_quota) ? $module_key->bank_account_quota : 0;

		return true;
	}
}