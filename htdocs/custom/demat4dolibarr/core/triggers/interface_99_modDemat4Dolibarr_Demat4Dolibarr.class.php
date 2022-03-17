<?php
/*  Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/demat4dolibarr/core/triggers/interface_99_modDemat4Dolibarr_Demat4Dolibarr.class.php
 *  \ingroup    demat4dolibarr
 *	\brief      File of class of triggers for demat4dolibarr module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for Demat4Dolibarr module
 */
class InterfaceDemat4Dolibarr extends DolibarrTriggers
{
	public $family = 'demat4dolibarr';
	public $description = "Triggers of this module catch triggers event for Demat4Dolibarr module.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'technic';


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->demat4dolibarr->enabled)) return 0;     // Module not active, we do nothing

		$origin = GETPOST('origin', 'alpha');
		if (empty($origin)) $origin = $object->origin;
		$origin_id = GETPOST('originid', 'int');
		if (empty($origin_id)) $origin_id = $object->origin_id;

		/**
		 * Propagation of extrafields : d4d_promise_code and d4d_contract_number
		 */
		if (!empty($origin) && $origin_id > 0 && $object->id > 0 && preg_match('/_CREATE$/', $action)) {
			// Parse element/subelement (ex: project_task)
			$element = $subelement = $origin;
			if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
				$element = $regs [1];
				$subelement = $regs [2];
			}

			// For compatibility
			if ($element == 'order') {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal') {
				$element = 'comm/propal';
				$subelement = 'propal';
			}
			if ($element == 'contract') {
				$element = $subelement = 'contrat';
			}
			if ($element == 'invoice' || $element == 'facture') {
				$element = 'compta/facture';
				$subelement = 'facture';
			}
			if ($element == 'inter') {
				$element = $subelement = 'ficheinter';
			}
			if ($element == 'shipping') {
				$element = $subelement = 'expedition';
			}
			if ($element == 'project') {
				$element = 'projet';
			}
            if ($element == 'supplierorder' || 'order_supplier') {
                $element = 'fourn';
                $subelement = 'fournisseur.commande';
            }

			dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');
			$classname = ucfirst($subelement);

            if ($classname == 'Fournisseur.commande') {
                $classname = 'CommandeFournisseur';
            }

			$srcobject = new $classname($this->db);
			if (method_exists($srcobject, 'fetch_optionals')) {
				$result = $srcobject->fetch($origin_id);
				if ($result > 0) {
					$srcobject->fetch_optionals();

					if (isset($srcobject->array_options['options_d4d_promise_code']) && isset($srcobject->array_options['options_d4d_contract_number'])) {
						$object->fetch_optionals();
						$object->array_options['options_d4d_promise_code'] = $srcobject->array_options['options_d4d_promise_code'];
						$object->array_options['options_d4d_contract_number'] = $srcobject->array_options['options_d4d_contract_number'];
						$object->insertExtraFields();
					}
				}
			}

			dol_syslog("Trigger '" . $this->name . "' for action '$action' [propagation of requests links] launched by " . __FILE__ . ". id=" . $object->id . " origin=" . $origin . " originid=" . $origin_id);
		}

        switch ($action) {
            // Bills
            case 'BILL_CREATE':
                $object->fetch_optionals();
                $insert_extra_fields = false;
                if (!($object->array_options['options_d4d_billing_mode'] > 0) && $conf->global->DEMAT4DOLIBARR_DEFAULT_BILLING_MODE > 0) {
                    $object->array_options['options_d4d_billing_mode'] = $conf->global->DEMAT4DOLIBARR_DEFAULT_BILLING_MODE;
					$insert_extra_fields = true;
                }
				if (!empty($object->context['createfromclone'])) {
					// Delete demat4dolibarr info on extrafields if it's a clone
					$object->array_options['options_d4d_job_create_on'] = null;
					$object->array_options['options_d4d_chorus_status'] = null;
					$object->array_options['options_d4d_invoice_status'] = null;
					$object->array_options['options_d4d_job_id'] = null;
					$object->array_options['options_d4d_job_owner'] = null;
					$object->array_options['options_d4d_job_status'] = null;
					$object->array_options['options_d4d_job_suspension_reason'] = null;
					$object->array_options['options_d4d_chorus_id'] = null;
					$object->array_options['options_d4d_chorus_invoice_id'] = null;
					$object->array_options['options_d4d_chorus_submit_date'] = null;
					$object->array_options['options_d4d_chorus_status_error_message'] = null;
					$object->array_options['options_d4d_invoice_id'] = null;
					$object->array_options['options_d4d_invoice_create_on'] = null;
					$insert_extra_fields = true;
				}
				if ($insert_extra_fields) {
					$object->insertExtraFields();
				}

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                break;
        }

		return 0;
	}
}