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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $object, $user;

if ($this->element != 'fichinter' || !in_array($object->element, array('propal', 'facture')) || !empty($this->context['eurochefintervention_lines_replaced'])) {
	return 0;
}

dol_include_once('/eurochefintervention/lib/eurochefintervention.lib.php');

$count_product = $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_COUNT;
if (empty($this->context['eurochefintervention_product_lines_added']) && $count_product > 0) {
	$this->context['eurochefintervention_product_lines_added'] = true;

	$product_pattern = $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_PATTERN;
	$product_qty_pattern = $conf->global->EUROCHEFINTERVENTION_EXTRAFIELDS_PRODUCT_QTY_PATTERN;

	for ($idx = 1; $idx <= $count_product; $idx++) {
		$product_id = $this->array_options['options_' . str_replace('__NUM__', $idx, $product_pattern)];
		if ($product_id > 0) {
			$product_static = eurochefintervention_get_product($this->db, $product_id);
			$product_qty = $this->array_options['options_' . str_replace('__NUM__', $idx, $product_qty_pattern)];

			// todo a gere la traduction de la description, le niveaux de prix pour la tva et le multi currency ?

			?>
			<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
			<?php
			print '<tr class="oddeven">';
			print '<td>' . $product_static->getNomUrl(1) . ' - ' . (!empty($product_static->label) ? $product_static->label : $product_static->description) . '</td>';
			print '<td>' . $product_static->description . '</td>';
			print '<td class="right">' . vatrate($product_static->tva_tx, true) . '</td>';
			print '<td class="right">' . price($product_static->price) . '</td>';
			if (!empty($conf->multicurrency->enabled)) {
				print '<td class="right">' . price(0) . '</td>';
			}

			print '<td class="right">' . (int)$product_qty . '</td>';
			if (!empty($conf->global->PRODUCT_USE_UNITS)) {
				print '<td class="left">' . $langs->trans($product_static->getLabelOfUnit('long')) . '</td>';
			}

			print '<td class="right">' . vatrate(0, true) . '</td>';

			$selected = 1;
			if (!empty($selectedLines) && !in_array('eip' . $idx, $selectedLines)) {
				$selected = 0;
			}
			print '<td class="center">';
			print '<input id="cbeip' . $idx . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="eip' . $idx . '"' . ($selected ? ' checked="checked"' : '') . '>';
			print '</td>';
			print '</tr>' . "\n";
			?>
			<!-- END PHP TEMPLATE originproductline.tpl.php -->
			<?php
		}
	}
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE > 0 && empty($line->fk_product)) {
	$product_static = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_LINE);

	// todo a gere le niveaux de prix pour la tva et le multi currency ?

	$this->tpl['label'] = $product_static->getNomUrl(1);
	$this->tpl['label'] .= ' - ' . (!empty($product_static->label) ? $product_static->label : $product_static->description);

	$this->tpl['vat_rate'] = vatrate($product_static->tva_tx, true);

	$this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static->price;
	$this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE > 0 && empty($line->fk_product)) {
    $product_static2 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_LINE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static2->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static2->label) ? $product_static2->label : $product_static2->description);

    $this->tpl['vat_rate'] = vatrate($product_static2->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static2->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE > 0 && empty($line->fk_product)) {
    $product_static3 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_LINE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static3->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static3->label) ? $product_static3->label : $product_static3->description);

    $this->tpl['vat_rate'] = vatrate($product_static3->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static3->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE > 0 && empty($line->fk_product)) {
    $product_static4 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_LINE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static4->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static4->label) ? $product_static4->label : $product_static4->description);

    $this->tpl['vat_rate'] = vatrate($product_static4->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static4->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE > 0 && empty($line->fk_product)) {
    $product_static5 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_LINE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static5->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static5->label) ? $product_static5->label : $product_static5->description);

    $this->tpl['vat_rate'] = vatrate($product_static5->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static5->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE > 0 && empty($line->fk_product)) {
    $product_static6 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_1_FOR_INVOICE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static6->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static6->label) ? $product_static6->label : $product_static6->description);

    $this->tpl['vat_rate'] = vatrate($product_static6->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static6->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE > 0 && empty($line->fk_product)) {
    $product_static7 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_2_FOR_INVOICE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static7->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static7->label) ? $product_static7->label : $product_static7->description);

    $this->tpl['vat_rate'] = vatrate($product_static7->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static7->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE > 0 && empty($line->fk_product)) {
    $product_static8 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_3_FOR_INVOICE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static8->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static8->label) ? $product_static8->label : $product_static8->description);

    $this->tpl['vat_rate'] = vatrate($product_static8->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static8->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE > 0 && empty($line->fk_product)) {
    $product_static9 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_4_FOR_INVOICE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static9->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static9->label) ? $product_static9->label : $product_static9->description);

    $this->tpl['vat_rate'] = vatrate($product_static9->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static9->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE > 0 && empty($line->fk_product)) {
    $product_static10 = eurochefintervention_get_product($this->db, $conf->global->EUROCHEFINTERVENTION_SERVICE_5_FOR_INVOICE);

    // todo a gere le niveaux de prix pour la tva et le multi currency ?

    $this->tpl['label'] = $product_static10->getNomUrl(1);
    $this->tpl['label'] .= ' - ' . (!empty($product_static10->label) ? $product_static10->label : $product_static10->description);

    $this->tpl['vat_rate'] = vatrate($product_static10->tva_tx, true);

    $this->tpl['price'] = !empty($conf->global->EUROCHEFINTERVENTION_SERVICE_PRICE_USER) ? $user->thm : $product_static10->price;
    $this->tpl['multicurrency_price'] = price(0);
}

if ($object->element == 'propal') {
	$this->context['eurochefintervention_lines_replaced'] = true;
	return 1;
}

?>

<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
<?php
print '<tr class="oddeven'.(empty($this->tpl['strike']) ? '' : ' strikefordisabled').'">';
print '<td>'.$this->tpl['label'].'</td>';
print '<td>'.$this->tpl['description'].'</td>';
print '<td class="right">'.$this->tpl['vat_rate'].'</td>';
print '<td class="right">'.$this->tpl['price'].'</td>';
if (!empty($conf->multicurrency->enabled)) {
	print '<td class="right">'.$this->tpl['multicurrency_price'].'</td>';
}

print '<td class="right">'.$this->tpl['qty'].'</td>';
if (!empty($conf->global->PRODUCT_USE_UNITS)) {
	print '<td class="left">'.$langs->trans($this->tpl['unit']).'</td>';
}

print '<td class="right">'.$this->tpl['remise_percent'].'</td>';

$selected = 1;
if (!empty($selectedLines) && !in_array($this->tpl['id'], $selectedLines)) {
	$selected = 0;
}
print '<td class="center">';
print '<input id="cb'.$this->tpl['id'].'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$this->tpl['id'].'"'.($selected ? ' checked="checked"' : '').'>';
print '</td>';
print '</tr>'."\n";
?>
<!-- END PHP TEMPLATE originproductline.tpl.php -->
