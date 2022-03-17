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
 * \file    htdocs/custom/module/class/module_key/opendsimodulekeybase.class.php
 * \ingroup opendsi
 * \brief
 */

if (class_exists('OpenDsiModuleKeyBase', false))
	return 0;

/**
 * OpenDsiModuleKeyBase
 */
class OpenDsiModuleKeyBase
{
    /**
     * @var array Errors
     */
    protected static $errors = array(
		'module_key_not_provided' => 'Module key not provided',
		'invalid_module_key' => 'Invalid module key',
		'encrypt_module_key_fail' => 'Encrypt module key fail',
		'decrypt_module_key_fail' => 'Decrypt module key fail',
	);

	/**
	 * Encode the module key
	 *
	 * @param	stdClass	$module_key		Module key
	 * @return	array						array('error' => 'string') if KO, array('key' => 'string') if OK
	 */
	public static function encode($module_key)
	{
		static::init();

		if (empty($module_key)) {
			dol_syslog(__METHOD__ . " - Module key empty.", LOG_ERR);
			return array('error' => self::$errors['module_key_not_provided']);
		}

		// Check module key
		if (!static::verify($module_key)) {
			dol_syslog(__METHOD__ . " - Check module key fail.", LOG_ERR);
			return array('error' => self::$errors['invalid_module_key']);
		}

		// Encode to JSON format
		$module_key = json_encode($module_key);
		if ($module_key === false) {
			dol_syslog(__METHOD__ . " - Encode to JSON format fail.", LOG_ERR);
			return array('error' => self::$errors['encrypt_module_key_fail']);
		}

		// Encrypt
		// Todo put the encrypt code here

		// Encode to Base64 format
		$module_key = base64_encode($module_key);

		// Mask Base64 format
		$rand = rand(5, 100);
		$equal_count = substr_count($module_key, '=');
		$module_key = $rand . self::randomString($rand) . $equal_count . rtrim($module_key,"=");

		return array('key' => $module_key);
	}

	/**
	 * Decode the module key
	 *
	 * @param	string		$module_key		Module key
	 * @return	array						array('error' => 'string') if KO, array('key' => stdClass()) if OK
	 */
	public static function decode($module_key)
	{
		static::init();

		if (empty($module_key)) {
			dol_syslog(__METHOD__ . " - Module key empty.", LOG_ERR);
			return array('error' => self::$errors['module_key_not_provided']);
		}

		// Unmask Base64 format
		if (!preg_match('/^(\d+)(.*)/', $module_key, $matches)) {
			dol_syslog(__METHOD__ . " - Unmask Base64 format fail.", LOG_ERR);
			return array('error' => self::$errors['invalid_module_key']);
		}
		$rand = $matches[1];
		$module_key = $matches[2];
		$equal_count = substr($module_key, $rand, 1);
		$module_key = substr($module_key, $rand + 1) . str_pad('', $equal_count,"=");

		// Decode to Base64 format
		$module_key = base64_decode($module_key);
		if ($module_key === false) {
			dol_syslog(__METHOD__ . " - Decode to Base64 format fail.", LOG_ERR);
			return array('error' => self::$errors['decrypt_module_key_fail']);
		}

		// Decrypt
		// Todo put the decrypt code here

		// Decode to JSON format
		$module_key = json_decode($module_key);
		if ($module_key === null) {
			dol_syslog(__METHOD__ . " - Decode to JSON format fail.", LOG_ERR);
			return array('error' => self::$errors['decrypt_module_key_fail']);
		}

		// Check module key
		if (!static::verify($module_key)) {
			dol_syslog(__METHOD__ . " - Check module key fail.", LOG_ERR);
			return array('error' => self::$errors['invalid_module_key']);
		}

		return array('key' => $module_key);
	}

	/**
	 * Init variables
	 */
	protected static function init() {}

	/**
	 * Verify the module key
	 *
	 * @param	stdClass	$module_key		Module key
	 * @return	bool
	 */
	public static function verify($module_key) { return false; }

	/**
	 * Generate a random string
	 *
	 * @param	int			$size	String size
	 * @return	string
	 */
	protected static function randomString($size)
	{
		$string = '';
		$total_size = 0;

		do {
			$random = rtrim(base64_encode(uniqid(rand(100, 30000), true)), '=');
			$string .= substr($random, 0, $size - $total_size);
			$string = ltrim($string, "0123456789");
			$total_size = strlen($string);
		} while ($total_size < $size);

		return $string;
	}
}