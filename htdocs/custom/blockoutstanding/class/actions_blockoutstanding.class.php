<?php
/* Copyright (C) 2020 SuperAdmin <francis.appels@z-application.com>
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
 * \file    blockoutstanding/class/actions_blockoutstanding.class.php
 * \ingroup blockoutstanding
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsBlockOutstanding
 */
class ActionsBlockOutstanding
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
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db,$langs,$conf,$user;
		$this->resprints = '';
		return 0;
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
		global $conf, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('propalcard','ordercard','invoicecard','ordersuppliercard','invoicesuppliercard'))) {
			if (! empty($conf->global->BLOCKOUTSTANDING_ADDLINE) && ($action == 'addline' || $action == 'updateligne' || $action == 'updateline')) {
				$blockCards = array();
				if (! empty($conf->global->BLOCKOUTSTANDING_PROPOSAL)) {
					$blockCards[] = 'propalcard';
				}
				if (! empty($conf->global->BLOCKOUTSTANDING_ORDER)) {
					$blockCards[] = 'ordercard';
				}
				if (! empty($conf->global->BLOCKOUTSTANDING_INVOICE)) {
					$blockCards[] = 'invoicecard';
				}
				if (! empty($conf->global->BLOCKOUTSTANDING_SUPPLIER_ORDER)) {
					$blockCards[] = 'ordersuppliercard';
				}
				if (! empty($conf->global->BLOCKOUTSTANDING_SUPPLIER_INVOICE)) {
					$blockCards[] = 'invoicesuppliercard';
				}
				if (in_array($parameters['currentcontext'], $blockCards)) {
					$langs->load("blockoutstanding@blockoutstanding");
					$object->fetch_thirdparty();
					$soc = $object->thirdparty;
					if ($soc->id > 0) {
						$outstandingLimit = $soc->outstanding_limit;
						if (!isset($soc->outstanding_limit)) {
							$outstandingLimit = price2num($conf->global->BLOCKOUTSTANDING_DEFAULT_AMOUNT, 'MT');
						} else {
							$outstandingLimit = $soc->outstanding_limit;
						}
						if (empty(GETPOST('tva_tx'))) {
							$tva_tx = 0;
						} else {
							$tva_tx = GETPOST('tva_tx');
						}
						if (!empty(GETPOST('price_ttc'))) {
							$priceTTC = GETPOST('price_ttc');
						} elseif (!empty('price_ht')) {
							$priceTTC = GETPOST('price_ht') * (1 + $tva_tx/100);
						} else {
							$priceTTC = 0;
						}
						if (empty(GETPOST('qty'))) {
							$qty = 0;
						} else {
							$qty = GETPOST('qty');
						}
						if (in_array($parameters['currentcontext'], array('ordersuppliercard','invoicesuppliercard'))) {
							if (empty($priceTTC)) {
								dol_include_once('fourn/class/fournisseur.product.class.php');
								$supplierProduct = new ProductFournisseur($this->db);
								if ($supplierProduct->fetch_product_fournisseur_price(GETPOST('idprodfournprice', 'int')) > 0) {
									$linePrice = $supplierProduct->fourn_price * $qty;
								}
							} else {
								$linePrice = $priceTTC * $qty;
							}
							$mode = 'supplier';
						} else {
							$mode = 'customer';
							$linePrice = $priceTTC * $qty;
						}
						$arrayoutstandingbills = $soc->getOutstandingBills($mode);
						$outstandingbill = $arrayoutstandingbills['opened'];
						if ($action == 'updateligne' || $action == 'updateline') {
							$lineId = GETPOST('lineid');
							foreach ($object->lines as $id => $line) {
								if ($line->id == $lineId) {
									$linePrice -= $line->total_ttc;
								}
							}
						}
						if ($outstandingbill > 0 &&
							(isset($soc->outstanding_limit) || !empty($outstandingLimit)) &&
							$outstandingbill + $object->total_ttc + $linePrice > $outstandingLimit) {
							if ($conf->global->BLOCKOUTSTANDING_LEVEL == '1') {
								$this->errors[] = $langs->trans('BlockedOutstanding', price($outstandingLimit), price($object->total_ttc + $linePrice), price($outstandingbill));
								$error++;
							} else {
								setEventMessage($langs->trans('BlockedOutstanding', price($outstandingLimit), price($object->total_ttc + $linePrice), price($outstandingbill)), 'warnings');
							}
						}
					}
				}
			}
		}

		if (! $error) {
			return 0; // ok or warning
		} else {
			unset($_POST['price_ht']);
			unset($_POST['price_ttc']);
			unset($_POST['qty']);
			unset($_POST['lineid']);
			unset($_POST['idprodfournprice']);
			return -1;
		}
	}
}
