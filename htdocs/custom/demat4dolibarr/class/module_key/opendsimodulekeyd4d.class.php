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
 * \file    htdocs/demat4dolibarr/class/module_key/opendsimodulekeyb4d.class.php
 * \ingroup demat4dolibarr
 * \brief
 */

if (class_exists('OpenDsiModuleKeyD4D', false))
	return 0;
dol_include_once('/demat4dolibarr/class/module_key/opendsimodulekeybase.class.php');

/**
 * OpenDsiModuleKeyD4D
 */
class OpenDsiModuleKeyD4D extends OpenDsiModuleKeyBase
{
	/**
	 * Init variables
	 */
	protected static function init()
	{
		global $langs;
		$langs->load('opendsipartner@opendsipartner');

		self::$errors = array(
			'module_key_not_provided' => $langs->trans('Demat4DolibarrErrorModuleKeyNotProvided'),
			'invalid_module_key' => $langs->trans('Demat4DolibarrErrorInvalidModuleKey'),
			'encrypt_module_key_fail' => $langs->trans('Demat4DolibarrErrorEncryptModuleKeyFail'),
			'decrypt_module_key_fail' => $langs->trans('Demat4DolibarrErrorDecryptModuleKeyFail'),
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
		if (empty($module_key->authentication_api_url) || empty($module_key->api_url) || empty($module_key->api_key) ||
			empty($module_key->user_username) || empty($module_key->user_password) || empty($module_key->document_type_id) ||
			empty($module_key->pack_chorus)
		) {
			return false;
		}

		return true;
	}
}