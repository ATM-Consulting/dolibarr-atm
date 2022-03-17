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


class ActionsEurochefIntervention
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
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
     * @param        DoliDB $_db Database handler
     */
    public function __construct($_db)
    {
        global $db;
        $this->db = is_object($_db) ? $_db : $db;
    }

    /**
     * Overloading the createFrom function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function createFrom($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user;

        $context = explode(':', $parameters['context']);

		if (in_array('globalcard', $context) && $action == 'add' && in_array($object->element, array('propal', 'facture')) && $object->id > 0) {
            $srcobject = $parameters['objFrom'];

            // Manage the lines from the intervention for proposals and invoices
            if ($srcobject->element == 'fichinter' && $srcobject->id > 0) {
                dol_include_once('/eurochefintervention/lib/eurochefintervention.lib.php');

                $selectedLines = GETPOST('toselect', 'array');
                $service_line_ids = array();
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE > 0) $service_line_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE;
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE > 0) $service_line_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE;
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE > 0) $service_line_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE;
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE > 0) $service_line_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE;
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE > 0) $service_line_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE;

                // Save lines
                $lines = $object->lines;
                if (empty($lines) && method_exists($object, 'fetch_lines')) {
                    $object->fetch_lines();
                    $lines = $object->lines;
                }

                $service_invoice_ids = array();
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE > 0) $service_invoice_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE;
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE > 0) $service_invoice_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE;
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE > 0) $service_invoice_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE;
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE > 0) $service_invoice_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE;
                if ($conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE > 0) $service_invoice_ids[] = $conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE;

                // Save invoice lines
                $invoice_lines = count($service_invoice_ids);
                if (empty($invoice_lines) && method_exists($object, 'fetch_lines')) {
                    $object->fetch_lines();
                    $invoice_lines = $service_invoice_ids;
                }

                // Delete intervention lines
                if (!empty($service_line_ids) || $object->element == 'propal') {
                    foreach ($lines as $line) {
                        if (empty($line->fk_product) && $line->product_type == Product::TYPE_SERVICE) {
                            $result = $object->deleteline($line->id);
                            if ($result < 0) {
                                return -1;
                            }
                        }
                    }
                }

                // Add product lines
                $count_product = $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_COUNT;
                if ($count_product > 0) {
                    $product_pattern = $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_PATTERN;
                    $product_qty_pattern = $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_QTY_PATTERN;

                    for ($idx = 1; $idx <= $count_product; $idx++) {
                        $product_id = $srcobject->array_options['options_' . str_replace('__NUM__', $idx, $product_pattern)];
                        if ($product_id > 0) {
                            if (!in_array('eip' . $idx, $selectedLines) && $object->element == 'facture') { // Not supported on proposal card
                                continue; // Skip unselected lines
                            }
                            $product_static = eurochefintervention_get_product($this->db, $product_id);

                            // todo a gere la traduction de la description, le niveaux de prix pour la tva et le multi currency ?
                            $product_description = $product_static->description;
                            $product_qty = $srcobject->array_options['options_' . str_replace('__NUM__', $idx, $product_qty_pattern)];
                            $product_type = $product_static->type;
                            $product_price = price($product_static->price);
                            $product_vat = $product_static->tva_tx;
                            $product_unit = $product_static->fk_unit;
                            $product_fk_fournprice = 0;
                            $product_pa_ht = $product_static->cost_price;
                            $product_multicurrency_price = 0;
                            $product_array_options = array();

                            $result = 0;
                            if ($object->element == 'propal') {
                                $result = $object->addline($product_description, $product_price, $product_qty,
                                    $product_vat, 0, 0, $product_id, 0,
                                    'HT', 0, 0, $product_type, -1, 0,
                                    0, $product_fk_fournprice, $product_pa_ht, '', 0, 0,
                                    $product_array_options, $product_unit, '', 0, $product_multicurrency_price);
                            } elseif ($object->element == 'facture') {
                                $result = $object->addline($product_description, $product_price, $product_qty,
                                    $product_vat, 0, 0, $product_id, 0, 0, 0, 0, 0, 0, 'HT',
                                    0, $product_type, -1, 0, '', 0, 0,
                                    $product_fk_fournprice, $product_pa_ht, '', $product_array_options, 100, '',
                                    $product_unit, $product_multicurrency_price);
                            }
                            if ($result < 0) {
                                return -1;
                            }
                        }
                    }
                }

                // Add service lines of the intervention if an invoice
                if ($object->element == 'facture' && !empty($service_line_ids)) {
                    foreach ($service_line_ids as $service_line_id) {
                        $product_static = eurochefintervention_get_product($this->db, $service_line_id);

                        // todo a gere le niveaux de prix pour la tva et le multi currency ?
                        $product_id = $service_line_id;
                        $product_type = $product_static->type;
                        $product_price = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? price($user->thm) : price($product_static->price);
                        $product_vat = $product_static->tva_tx;
                        $product_fk_fournprice = 0;
                        $product_pa_ht = 0;
                        $product_multicurrency_price = 0;

                        foreach ($lines as $line) {
                            if (empty($line->fk_product) && $line->product_type == Product::TYPE_SERVICE) {
                                $result = 0;
                                if ($object->element == 'propal') {
                                    $result = $object->addline($line->desc, $product_price, $line->qty,
                                        $product_vat, 0, 0, $product_id, $line->remise_percent,
                                        'HT', 0, $line->info_bits, $product_type, -1, $line->special_code,
                                        0, $product_fk_fournprice, $product_pa_ht, '', $line->date_start, $line->date_end,
                                        $line->array_options, $line->fk_unit, '', 0, $product_multicurrency_price);
                                } elseif ($object->element == 'facture') {
                                    $result = $object->addline($line->desc, $product_price, $line->qty, $product_vat, 0, 0, $product_id,
                                        $line->remise_percent, $line->date_start, $line->date_end, 0, $line->info_bits, 0, 'HT',
                                        0, $product_type, -1, $line->special_code, '', 0, 0,
                                        $product_fk_fournprice, $product_pa_ht, '', $line->array_options, 100, '', $line->fk_unit, $product_multicurrency_price);
                                }
                                if ($result < 0) {
                                    return -1;
                                }
                            }
                        }
                    }
                }

                // Add service lines of the intervention if an invoice
                if ($object->element == 'facture' && !empty($service_invoice_ids)) {
                    foreach ($service_invoice_ids as $service_line_id) {
                        $product_static = eurochefintervention_get_product($this->db, $service_line_id);

                        // todo a gere le niveaux de prix pour la tva et le multi currency ?
                        $product_id = $service_line_id;
                        $product_type = $product_static->type;
                        $product_desc = $product_static->description;
                        $product_fk_unit = $product_static->fk_unit;
                        $product_price = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? price($user->thm) : price($product_static->price);
                        $product_vat = $product_static->tva_tx;
                        $product_fk_fournprice = 0;
                        $product_pa_ht = 0;
                        $product_multicurrency_price = 0;

                        $result = $object->addline($product_desc, $product_price, 1 /* qty */, $product_vat, 0, 0, $product_id,
                            0 /*remise_percent*/, '' /*date_start*/, '' /*date_end*/, 0, 0 /*info_bits*/, 0, 'HT',
                            0, $product_type, -1, '' /*special_code*/, '', 0, 0,
                            $product_fk_fournprice, $product_pa_ht, '', array() /*array_options*/, 100, '', $product_fk_unit, $product_multicurrency_price);
                        if ($result < 0) {
                            return -1;
                        }
                    }
                }

                // Add intervention fees package
                $company_field_feestravel = (!empty($conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_THIRDPARTY_FIELD_TRAVELFEES) && $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_THIRDPARTY_FIELD_TRAVELFEES != '-1') ? $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_THIRDPARTY_FIELD_TRAVELFEES : '';

                if ($object->element == 'facture' && !empty($company_field_feestravel)) {
                    $object->fetch_thirdparty();
                    $service_line_id = $object->thirdparty->array_options[$company_field_feestravel];

                    if ($service_line_id > 0) {
                        $product_static = eurochefintervention_get_product($this->db, $service_line_id);

                        // todo a gere le niveaux de prix pour la tva et le multi currency ?
                        $product_id = $service_line_id;
                        $product_type = $product_static->type;
                        $product_desc = $product_static->description;
                        $product_fk_unit = $product_static->fk_unit;
                        $product_price = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? price($user->thm) : price($product_static->price);
                        $product_vat = $product_static->tva_tx;
                        $product_fk_fournprice = 0;
                        $product_pa_ht = 0;
                        $product_multicurrency_price = 0;

                        $result = $object->addline($product_desc, $product_price, 1 /* qty */, $product_vat, 0, 0, $product_id,
                            0 /*remise_percent*/, '' /*date_start*/, '' /*date_end*/, 0, 0 /*info_bits*/, 0, 'HT',
                            0, $product_type, -1, '' /*special_code*/, '', 0, 0,
                            $product_fk_fournprice, $product_pa_ht, '', array() /*array_options*/, 100, '', $product_fk_unit, $product_multicurrency_price);
                        if ($result < 0) {
                            return -1;
                        }
                    }
                }
            }
        }

        return 0;
    }
}