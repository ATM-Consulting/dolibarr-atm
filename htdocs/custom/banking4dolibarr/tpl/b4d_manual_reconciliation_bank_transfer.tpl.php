<?php
/* Copyright (C) 2020      Open-DSI              <support@open-dsi.fr>
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
 *  \file		htdocs/banking4dolibarr/tpl/b4d_manual_reconciliation_bank_transfer.tpl.php
 *  \ingroup	banking4dolibarr
 *  \brief		Template to show manuel reconciliation content for the type 'bank_transfer'
 */

$langs->loadLangs(array("banks", "multicurrency"));

$account_id = GETPOST('account_id','int');
$label = GETPOST('label', 'alpha');
$account_amount = GETPOST('account_amount','int');

if ($action == 'update_manual_reconciliation_type') {
    $label = $object->label . (!empty($object->comment) ? ' - ' . $object->comment : '');
}

/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);

print load_fiche_titre($langs->trans("Banking4DolibarrNewBankTransfers"), '', 'title_bank.png');

dol_fiche_head('', '');

if ($user->rights->banque->transfer) {
    print '<table class="border" width="100%">';

    // Transfer from bank account
    print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("TransferFrom") . '</td>';
    print '<td>';
    if ($object->amount > 0)
        $form->select_comptes($account_id, 'account_id', 0, '', 0, '', empty($conf->multicurrency->enabled) ? 0 : 1);
    else print $account->getNomUrl(1);
    print "</td>";
    print "</tr>\n";

    // Transfer to bank account
    print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("TransferTo") . '</td>';
    print '<td>';
    if ($object->amount > 0)
        print $account->getNomUrl(1);
    else $form->select_comptes($account_id, 'account_id', 0, '', 0, '', empty($conf->multicurrency->enabled) ? 0 : 1);
    print "</td>";
    print "</tr>\n";

    // Description
    print '<tr><td class="fieldrequired">' . $langs->trans("Description") . '</td>';
    print '<td>';
    print '<input name="label" class="flat quatrevingtpercent" type="text" value="' . dol_escape_htmltag($label) . '">';
    print "</td>";
    print "</tr>\n";

    // Amount To Other Currency
    print '<tr style="display:none" class="multicurrency"><td class="fieldrequired">' . $langs->trans($object->amount > 0 ? "Banking4DolibarrAmountFromOtherCurrency" : "AmountToOthercurrency") . '</td>';
    print '<td>';
    print '<input name="account_amount" class="flat" type="text" size="10" value="' . dol_escape_htmltag($account_amount) . '">';
    print "</td>";
    print "</tr>\n";

    print "</table>";

    $account_currency_code = dol_escape_js($account->currency_code, 1);
    $ajax_url = dol_escape_js(DOL_URL_ROOT . '/core/ajax/getaccountcurrency.php', 1);
    print <<<SCRIPT
<script type="text/javascript">
    $(document).ready(function () {
        $(".selectbankaccount").change(function() {
            console.log("We change bank account");
            init_page();
        });

        function init_page() {
            console.log("Set fields according to currency");
            var account_to = $("#selectaccount_id");
            var account2 = account_to.val();
            var currencycode1="$account_currency_code";
            var currencycode2="";

            account_to.find('option[value="{$account->id}"]').remove();

            $.get("$ajax_url", {id: account2})
                .done(function( data ) {
                    if (data != null)
                    {
                        var item=$.parseJSON(data);
                        if (item.num==-1) {
                            console.error("Error: "+item.error);
                        } else if (item.num!==0) {
                            currencycode2 = item.value;
                        }

                        if (currencycode2!==currencycode1 && currencycode2!=="" && currencycode1!=="") {
                            $(".multicurrency").show();
                        } else {
                            $(".multicurrency").hide();
                        }
                    }
                else {
                    console.error("Error: Ajax url has returned an empty page. Should be an empty json array.");
                }
            }).fail(function( data ) {
                console.error("Error: has returned an empty page. Should be an empty json array.");
            });
        }

        init_page();
    });
</script>
SCRIPT;
} else {
    print '<br><span style="color: red;">' . $langs->trans('NotEnoughPermissions') . '</span>';
}

dol_fiche_end();
