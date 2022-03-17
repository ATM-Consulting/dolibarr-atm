<?php
/* Copyright (C) 2021 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    contactcommande/class/actions_contactcommande.class.php
 * \ingroup contactcommande
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

use Sabre\VObject\Parameter;

/**
 * Class ActionsContactCommande
 */
class ActionsContactCommande
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
		$error = 0; // Error counter
		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('ordercard')) && $action != 'create' && $action != 'add'){
			if ($object->statut=='0' && !$object->liste_contact(-1, 'internal')){
				$object->add_contact($object->user_author_id, 91, 'internal');
			}
		}
		if (in_array($parameters['currentcontext'], array('propalcard')) && $action != 'create' && $action != 'add'){
			if ($object->statut==0 && !$object->liste_contact(-1, 'internal')){
				$object->add_contact($object->user_author_id, 31, 'internal');
			}
		}
		if (in_array($parameters['currentcontext'], array('invoicecard')) && $action != 'create' && $action != 'add'){
			if ($object->statut==0 && !$object->liste_contact(-1, 'internal')){
				$object->add_contact($object->user_author, 50, 'internal');
			}
		}
		if (in_array($parameters['currentcontext'], array('invoicesuppliercard')) && $action != 'create' && $action != 'add'){
			if ($object->statut==0 && !$object->liste_contact(-1, 'internal')){
				$object->add_contact($object->author, 70, 'internal');
			}
		}
		if (in_array($parameters['currentcontext'], array('interventioncard')) && $action != 'create' && $action != 'add'){ //TODO
			if ($object->statut==0 && !$object->liste_contact(-1, 'internal')){
				$object->add_contact($object->user_creation, 120, 'internal');
			}
		}
		if (in_array($parameters['currentcontext'], array('ordersuppliercard')) && $action != 'create' && $action != 'add'){
			if ($object->statut==0 && !$object->liste_contact(-1, 'internal')){
				$object->add_contact($object->user_author_id, 140, 'internal');
			}
		}
		if (in_array($parameters['currentcontext'], array('supplier_proposalcard')) && $action != 'create' && $action != 'add'){
			if ($object->statut==0 && !$object->liste_contact(-1, 'internal')){
				$object->add_contact($object->user_author_id, 110, 'internal');
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}