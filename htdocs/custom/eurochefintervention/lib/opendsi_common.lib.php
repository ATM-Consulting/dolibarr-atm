<?php
/* Copyright (C) 2005-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2017       Open-DSI                <support@open-dsi.fr>
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
 *	\file       htdocs/module/lib/opendsi_common.lib.php
 * 	\ingroup	module
 *	\brief      Common functions opendsi for the module
 */

/**
 * Gives the changelog. First check ChangeLog-la_LA.md then ChangeLog.md
 *
 * @param	string	  $moduleName			    Name of module
 *
 * @return  string                              Content of ChangeLog
 */
function opendsi_common_getChangeLog($moduleName)
{
    global $langs;
    $langs->load("admin");

    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    include_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';

    $filefound = false;

    $modulePath = dol_buildpath('/'.strtolower($moduleName), 0);

    // Define path to file README.md.
    // First check README-la_LA.md then README.md
    $pathoffile = $modulePath . '/ChangeLog-' . $langs->defaultlang . '.md';
    if (dol_is_file($pathoffile)) {
        $filefound = true;
    }
    if (!$filefound) {
        $pathoffile = $modulePath . '/ChangeLog.md';
        if (dol_is_file($pathoffile)) {
            $filefound = true;
        }
    }

    $content = '';

    if ($filefound)     // Mostly for external modules
    {
        $moduleUrlPath = dol_buildpath('/'.strtolower($moduleName), 1);
        $content = file_get_contents($pathoffile);

        if ((float)DOL_VERSION >= 6.0) {
            @include_once DOL_DOCUMENT_ROOT . '/core/lib/parsemd.lib.php';
            $content = dolMd2Html($content, 'parsedown', array('doc/' => $moduleUrlPath . '/doc/'));
        } else {
            $content = opendsi_common_dolMd2Html('codenaf', $content, 'parsedown', array('doc/' => $moduleUrlPath . '/doc/'));
        }

    }

    return $content;
}

/**
 * Function to parse MD content into HTML
 *
 * @param	string	  $moduleName			Name of module
 * @param	string	  $content			    MD content
 * @param   string    $parser               'parsedown' or 'nl2br'
 * @param   string    $replaceimagepath     Replace path to image with another path. Exemple: ('doc/'=>'xxx/aaa/')
 *
 * @return	string                          Parsed content
 */
function opendsi_common_dolMd2Html($moduleName, $content, $parser='parsedown',$replaceimagepath=null)
{
    if (is_array($replaceimagepath)) {
        foreach ($replaceimagepath as $key => $val) {
            $keytoreplace = '](' . $key;
            $valafter = '](' . $val;
            $content = preg_replace('/' . preg_quote($keytoreplace, '/') . '/m', $valafter, $content);
        }
    }

    if ($parser == 'parsedown') {
        dol_include_once('/' . strtolower($moduleName) . '/includes/parsedown/Parsedown.php');
        $Parsedown = new Parsedown();
        $content = $Parsedown->text($content);
    } else {
        $content = nl2br($content);
    }

    return $content;
}

/**
 * Generate natural SQL search string for a criteria (this criteria can be tested on one or several fields)
 *
 * @param 	string|string[]	$fields 	String or array of strings, filled with the name of all fields in the SQL query we must check (combined with a OR). Example: array("p.field1","p.field2")
 * @param 	string[]		$nullfields	Array of strings, filled with the name of the field in the SQL query we must check if the searched fields is NULL (when mode = 4). Example: array("p.field1"=>"p.field3")
 * @param 	string 			$value 		The value to look for.
 *                          		    If param $mode is 0, can contains several keywords separated with a space or |
 *                                         like "keyword1 keyword2" = We want record field like keyword1 AND field like keyword2
 *                                         or like "keyword1|keyword2" = We want record field like keyword1 OR field like keyword2
 *                             			If param $mode is 1, can contains an operator <, > or = like "<10" or ">=100.5 < 1000"
 *                             			If param $mode is 2, can contains a list of int id separated by comma like "1,3,4"
 *                             			If param $mode is 3, can contains a list of string separated by comma like "a,b,c"
 *                             			If param $mode is 4, can contains a datetime or a date and an operator <, > or = of string like "<=YYYY-MM-DD HH:mm:ss" or "<=YYYY-MM-DD HH:mm" or "=YYYY-MM-DD" or ">YYYY" ( support &, | and () )
 * @param	integer			$mode		0=value is list of keyword strings, 1=value is a numeric test (Example ">5.5 <10"), 2=value is a list of id separated with comma (Example '1,3,4')
 * @param	integer			$nofirstand	1=Do not output the first 'AND'
 * @return 	string 			$res 		The statement to append to the SQL query
 */
function opendsi_natural_search($fields, $value, $mode=0, $nofirstand=0, $nullfields=array())
{
    global $db;
    if ($mode == 4) {
        if (!is_array($fields)) $fields = array($fields);

        $criterias = array();
        if (preg_match_all('/\s*(&|\|)?\s*(\()?\s*([<>=]+)?\s*([0-9]{4})(?:\s*-\s*([0-9]{2}))?(?:\s*-\s*([0-9]{2}))?(?:\s+([0-9]{2})?(?:\s*\:\s*([0-9]{2}))?(?:\s*\:\s*([0-9]{2}))?)?\s*(\))?\s*/', $value, $matches, PREG_SET_ORDER)) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
            foreach ($matches as $match) {
                $operatorSQL = !empty($match[1]) && $match[1] == '&' ? ' AND ' : ' OR ';
                $openingParenthesis = !empty($match[2]) ? $match[2] : '';
                $operator = !empty($match[3]) ? $match[3] : '=';
                $end_limit = $operator == '<=' || $operator == '>';
                $date = $match[4];                                                                                                                          // Year
                $date .= '-' . (!empty($match[5]) ? $match[5] : ($end_limit ? '12' : '00'));                                                                // Month
                $date .= '-' . (!empty($match[6]) ? $match[6] : ($end_limit ? (!empty($match[5]) ? dol_get_last_day($match[4], $match[5]) : '31') : '00')); // Day
                $date .= ' ' . (!empty($match[7]) ? $match[7] : ($end_limit ? '23' : '00'));                                                                // Hour
                $date .= ':' . (!empty($match[8]) ? $match[8] : ($end_limit ? '59' : '00'));                                                                // Minute
                $date .= ':' . (!empty($match[9]) ? $match[9] : ($end_limit ? '59' : '00'));                                                                // second
                $date = "'" . $db->escape($date) . "'";
                $closingParenthesis = !empty($match[10]) ? $match[10] : '';

                $not_complete = empty($match[9]) || empty($match[8]) || empty($match[7]) || empty($match[6]) || empty($match[5]);
                if ($operator == '=' && $not_complete) {
                    $criterias[] = array($operatorSQL, $openingParenthesis.'(', '>=', $date, $closingParenthesis);

                    $end_limit = true;
                    $date = $match[4];                                                                                                                          // Year
                    $date .= '-' . (!empty($match[5]) ? $match[5] : ($end_limit ? '12' : '00'));                                                                // Month
                    $date .= '-' . (!empty($match[6]) ? $match[6] : ($end_limit ? (!empty($match[5]) ? dol_get_last_day($match[4], $match[5]) : '31') : '00')); // Day
                    $date .= ' ' . (!empty($match[7]) ? $match[7] : ($end_limit ? '23' : '00'));                                                                // Hour
                    $date .= ':' . (!empty($match[8]) ? $match[8] : ($end_limit ? '59' : '00'));                                                                // Minute
                    $date .= ':' . (!empty($match[9]) ? $match[9] : ($end_limit ? '59' : '00'));                                                                // second
                    $date = "'" . $db->escape($date) . "'";
                    $operatorSQL = ' AND ';
                    $openingParenthesis = '';
                    $operator = '<=';
                    $closingParenthesis .= ')';
                }

                $criterias[] = array($operatorSQL, $openingParenthesis, $operator, $date, $closingParenthesis);
            }
        }

        $to_print = array();
        foreach ($fields as $field) {
            $ifnull = isset($nullfields[$field]) ? $nullfields[$field] : '';
            $statementSQL = '';
            foreach ($criterias as $criteria) {
                $statementSQL .= $criteria[0] . $criteria[1] . (!empty($ifnull) ? $db->ifsql($field . ' IS NULL', $ifnull, $field) : $field) . ' ' . $criteria[2] . ' ' . $criteria[3] . $criteria[4];
            }
            $statementSQL = preg_replace('/^( (?:AND|OR) )/', '', $statementSQL);
            if (!empty($statementSQL)) $to_print[] = $statementSQL;
        }

        return (!empty($to_print) ? ($nofirstand ? "" : " AND ") . "((" . implode(') OR (', $to_print) . "))" : '');
    } else {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
        return natural_search($fields, $value, $mode, $nofirstand);
    }
}

/**
 *  Return the handle of the object of the specified element
 *
 * @param	DoliDB	    $db		        Database handler
 * @param	string	    $element_type	Type of the element
 * @param	int		    $element_id	    Id of the element
 * @return 	object                      Object handler
 */
function opendsi_get_object($db, $element_type, $element_id)
{
    // Parse element/subelement (ex: project_task)
    $element = $subelement = $element_type;
    if (preg_match('/^([^_]+)_([^_]+)/i', $element_type, $regs)) {
        $element = $regs [1];
        $subelement = $regs [2];
    }

    $classpath = $element;
    if ($element_type == 'order' || $element_type == 'commande') {
        $classpath = $subelement = 'commande';
    } else if ($element_type == 'propal') {
        $classpath = 'comm/propal';
        $subelement = 'propal';
    } else if ($element_type == 'invoice' || $element_type == 'facture') {
        $classpath = 'compta/facture';
        $subelement = 'facture';
    } else if ($element_type == 'contract') {
        $classpath = $subelement = 'contrat';
    } else if ($element_type == 'shipping') {
        $classpath = $subelement = 'expedition';
    } else if ($element_type == 'deplacement') {
        $classpath = 'compta/deplacement';
        $subelement = 'deplacement';
    } else if ($element_type == 'order_supplier') {
        $classpath = 'fourn';
        $subelement = 'fournisseur.commande';
    } else if ($element_type == 'invoice_supplier') {
        $classpath = 'fourn';
        $subelement = 'fournisseur.facture';
	} else if ($element_type == 'chargesociales') {
		$classpath = 'compta/sociales';
	} else if ($element_type == 'chequereceipt') {
		$classpath = 'compta/paiement/cheque';
		$subelement = 'remisecheque';
	} else if ($element_type == 'widthdraw') {
		$classpath = 'compta/prelevement';
		$subelement = 'bonprelevement';
	}

    dol_include_once('/' . $classpath . '/class/' . $subelement . '.class.php');

    if ($element_type == 'order_supplier') {
        $classname = 'CommandeFournisseur';
    } else if ($element_type == 'invoice_supplier') {
        $classname = 'FactureFournisseur';
    } else $classname = ucfirst($subelement);

    $srcobject = new $classname($db);
    $srcobject->fetch($element_id);

    return $srcobject;
}
