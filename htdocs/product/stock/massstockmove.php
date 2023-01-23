<?php
/* Copyright (C) 2013-2020 Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014	   Regis Houssin		<regis.houssin@inodbox.com>
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
 *  \file       htdocs/product/stock/massstockmove.php
 *  \ingroup    stock
 *  \brief      This page allows to select several products, then incoming warehouse and
 *  			outgoing warehouse and create all stock movements for this.
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
dol_include_once('/cliama/class/assettransfert.class.php');
dol_include_once('/cliama/class/typeentrepot.class.php');
dol_include_once('/categories/class/categorie.class.php');

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders', 'productbatch', 'deliveries', 'cliama@cliama'));
//init Hook
$hookmanager->initHooks(array('massstockmove'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service');

//checks if a product has been ordered

$action = GETPOST('action', 'aZ09');
$id_product = GETPOST('productid', 'int');
$id_sw = GETPOST('id_sw', 'int');
$id_tw = GETPOST('id_tw', 'int');
$batch = GETPOST('batch');
$qty = GETPOST('qty');
$idline = GETPOST('idline');
$dateMovement = GETPOST('dateMovement', 'alphanohtml');
$timeMovement = GETPOST('timeMovement', 'alphanohtml');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1

if (!$sortfield) {
	$sortfield = 'p.ref';
}

if (!$sortorder) {
	$sortorder = 'ASC';
}
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;

$trans = new AssetTranfert($db);
$typeent = new TypeEntrepot($db);

$listofdata = array();
if (!empty($_SESSION['massstockmove'])) $listofdata = json_decode($_SESSION['massstockmove'], true);


/*
 * Actions
 */

if ($action == 'addline')
{
	if (!($id_product > 0))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
	}
	else {
        $p = new Product($db);
        $p->fetch($id_product);
        $p->get_sousproduits_arbo();

        $p->load_stock();
        if (array_key_exists($id_sw, $p->stock_warehouse)) {
            if ($p->stock_warehouse[$id_sw]->real < $qty) {
                setEventMessages($langs->trans('NotEnoughStock'), null, 'errors');
                $error++;
            }
        } else {
            setEventMessages($langs->trans('EmptyStock'), null, 'errors');
            $error++;
        }

        $res = $p->get_arbo_each_prod();
        if (!empty($res)) {
            setEventMessages($langs->trans('StockTransfertRefusedForComposed'), null, 'errors');
            $error++;
        }
    }
	if (!($id_sw > 0))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
	}
	if (!($id_tw > 0))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseTarget")), null, 'errors');
	}
	if ($id_sw > 0 && $id_tw == $id_sw)
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorWarehouseMustDiffers"), null, 'errors');
	}
	if (!$qty)
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
	}

    /*
     * Spé AMA
     * ajouter un contrôle en plus empêchant de transférer
     * des produits non sérialisés d'un entrepôt "neuf" vers un entrepôt "occasion" (lié à la catégorisation des entrepôts)
     */
    if (!empty($conf->global->CLIAMA_NEW_WAREHOUSE_CATEGORY)
        && !empty($conf->global->CLIAMA_USED_WAREHOUSE_CATEGORY)) {
        $categoryNew = new Categorie($db);
        $categoryUsed = new Categorie($db);
        $categoryNew->fetch($conf->global->CLIAMA_NEW_WAREHOUSE_CATEGORY);
        $categoryUsed->fetch($conf->global->CLIAMA_USED_WAREHOUSE_CATEGORY);

        if ($categoryNew->containsObject('stock', $id_sw) && $categoryUsed->containsObject('stock', $id_tw)) {
            $error++;
            setEventMessages($langs->trans("NewCategoryCantGoToUsed"), null, 'errors');
        }
    }
    /*
     * Fin Spé
     */



    // Check a batch number is provided if product need it
	if (!$error)
	{
		$producttmp = new Product($db);
		$producttmp->fetch($id_product);
		if ($producttmp->hasbatch())
		{
			if (empty($batch))
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorTryToMakeMoveOnProductRequiringBatchData", $producttmp->ref), null, 'errors');
			}
		}

        if (! $error &&  !empty($producttmp->array_options['options_type_asset']))
        {
            $assets = array();

            while ($i <= $qty)
            {
                $t = new AssetTranfert($db);
                $t->fk_product = $id_product;
                $t->source_serial = '';
                $t->target_serial = '';
                $t->type = 'Transfert';
                $t->fk_entrepot_source =  $id_sw;
                $t->fk_entrepot_dest =$id_tw;

				$t->date_mvt = dol_now();
				if (!empty($dateMovement)){
					$t->date_mvt = date_create_from_format('y-m-d H:i', $dateMovement.' '.$timeMovement);
					if (!$t->date_mvt){
						$t->date_mvt = dol_now();
					}
				}


                $t->num_inventaire = '';

                $res = $t->create($user);

                if ($res > 0) $assets[] = $res;

                /*$assets[] = array(
                    'fk_source_asset' => 0
                    ,'fk_target_asset' => 0
                    ,'type_trans' => 0
                    ,'fk_user' => 0
                    ,'date_mouv' => ''
                    ,'num_inventaire' => ''
                );*/
                $i++;
            }
        }
	}

	// TODO Check qty is ok for stock move. Note qty may not be enough yet, but we make a check now to report a warning.
	// What is more important is to have qty when doing action 'createmovements'
	if (!$error)
	{
		// Warning, don't forget lines already added into the $_SESSION['massstockmove']
		if ($producttmp->hasbatch())
		{
		} else {
		}
	}

	if (!$error)
	{
		if (count(array_keys($listofdata)) > 0) $id = max(array_keys($listofdata)) + 1;
		else $id = 1;
		$listofdata[$id]=array('id'=>$id, 'id_product'=>$id_product, 'qty'=>$qty, 'id_sw'=>$id_sw, 'id_tw'=>$id_tw, 'batch'=>$batch, 'assets'=>$assets);
		$_SESSION['massstockmove'] = json_encode($listofdata);

		//unset($id_sw);
		//unset($id_tw);
		unset($id_product);
		unset($batch);
		unset($qty);
	}
}

if ($action == 'delline' && $idline != '')
{
    if (! empty($listofdata[$idline])) {
        if (!empty($listofdata[$idline]['assets']))
        {
            foreach ($listofdata[$idline]['assets'] as $t_id)
            {
                $t = new AssetTranfert($db);
                $t->fetch($t_id);
                $t->delete($user);
            }
        }
        unset($listofdata[$idline]);
    }
	if (count($listofdata) > 0) $_SESSION['massstockmove'] = json_encode($listofdata);
	else unset($_SESSION['massstockmove']);
}

if ($action == 'createmovements')
{
	$error = 0;
    $nb_transfer=0;
	//  if (!GETPOST("label"))
	//  {
	//      $error++;
	//      setEventMessages($langs->trans("ErrorFieldRequired"), $langs->transnoentitiesnoconv("MovementLabel"), null, 'errors');
	//  }

	$db->begin();

	{
		$product = new Product($db);

	foreach ($listofdata as $key => $val)	// Loop on each movement to do
		{
		$iterationError = 0;

		$id = $val['id'];
		$id_product = $val['id_product'];
		$id_sw = $val['id_sw'];
		$id_tw = $val['id_tw'];
		$qty = price2num($val['qty']);
		$batch = $val['batch'];
		$dlc = -1; // They are loaded later from serial
		$dluo = -1; // They are loaded later from serial
		$assets = $val['assets'];

		if (!$error && $id_sw <> $id_tw && is_numeric($qty) && $id_product)
		{
			$result = $product->fetch($id_product);

			$product->load_stock('novirtual'); // Load array product->stock_warehouse

			// Define value of products moved
			$pricesrc = 0;
			if (!empty($product->pmp)) $pricesrc = $product->pmp;
			$pricedest = $pricesrc;

			//print 'price src='.$pricesrc.', price dest='.$pricedest;exit;

			if (empty($conf->productbatch->enabled) || ! $product->hasbatch())        // If product does not need lot/serial
			{
				if (empty($product->stock_warehouse[$id_sw]->real) || $product->stock_warehouse[$id_sw]->real < abs($qty)){
					$msgErr = $product->ref;
					$warehouse = new Entrepot($db);
					if ($warehouse->fetch($id_sw)>0){
						$msgErr.= ' : '.$warehouse->label;
						$msgErr.= ' '.$langs->trans('Qty').' '.$product->stock_warehouse[$id_sw]->real.' < '.abs($qty);
						$msgErr.= ' '.$langs->trans('Batch').' '.$batch;
					}

					setEventMessages($langs->trans('ErrorStockIsNotEnough').' '.$msgErr, array(), 'errors');
					$error++;
					break; // A la fin du traitement il y a un rollback général si erreurs alors autant arrêter le traitement si tel est le cas.
				}

				// Remove stock
				$result1 = $product->correct_stock(
					$user,
					$id_sw,
					$qty,
					1,
					GETPOST("label"),
					$pricesrc,
					GETPOST("codemove")
				);

				if ($result1 < 0){
					$error++;
					setEventMessages($product->errors, $product->errorss, 'errors');
					break; // A la fin du traitement il y a un rollback général si erreurs alors autant arrêter le traitement si tel est le cas.
				}


				// Add stock
				$result2 = $product->correct_stock(
				$user,
				$id_tw,
				$qty,
				0,
				GETPOST("label"),
				$pricedest,
				GETPOST("codemove")
				);
				if ($result2 < 0)
				{
					$error++;
					setEventMessages($product->errors, $product->errorss, 'errors');
					break; // A la fin du traitement il y a un rollback général si erreurs alors autant arrêter le traitement si tel est le cas.
				}
			}
			else {
				$arraybatchinfo = $product->loadBatchInfo($batch);
				if (count($arraybatchinfo) > 0)
				{
					$firstrecord = array_shift($arraybatchinfo);
					$dlc = $firstrecord['eatby'];
					$dluo = $firstrecord['sellby'];
					//var_dump($batch); var_dump($arraybatchinfo); var_dump($firstrecord); var_dump($dlc); var_dump($dluo); exit;
				} else {
					$dlc = '';
					$dluo = '';
				}


				if (empty($product->stock_warehouse[$id_sw]->detail_batch[$batch]->qty) || $product->stock_warehouse[$id_sw]->detail_batch[$batch]->qty < abs($qty)){
					$error++;

					$msgErr = $product->ref;
					$warehouse = new Entrepot($db);
					if ($warehouse->fetch($id_sw)>0){
						$msgErr.= ' : '.$warehouse->label;
						$msgErr.= ' '.$langs->trans('Batch').' '.$batch;
						$msgErr.= ' '.$langs->trans('Qty').' '.$product->stock_warehouse[$id_sw]->detail_batch[$batch]->qty.' < '.abs($qty);
					}

					setEventMessages($langs->trans('ErrorStockIsNotEnough').' '.$msgErr, array(), 'errors');
					break; // A la fin du traitement il y a un rollback général si erreurs alors autant arrêter le traitement si tel est le cas.
				}

						// Remove stock
				$result1 = $product->correct_stock_batch(
						$user,
						$id_sw,
						$qty,
						1,
						GETPOST("label"),
						$pricesrc,
						$dlc,
						$dluo,
						$batch,
						GETPOST("codemove")
				);

				if ($result1 < 0){
					$error++;
					setEventMessages($product->errors, $product->errorss, 'errors');
					break; // A la fin du traitement il y a un rollback général si erreurs alors autant arrêter le traitement si tel est le cas.
				}

				// Add stock
				$result2 = $product->correct_stock_batch(
						$user,
						$id_tw,
						$qty,
						0,
						GETPOST("label"),
						$pricedest,
						$dlc,
						$dluo,
						$batch,
						GETPOST("codemove")
				);

				if ($result2 < 0){
					$error++;
					setEventMessages($product->errors, $product->errorss, 'errors');
					break; // A la fin du traitement il y a un rollback général si erreurs alors autant arrêter le traitement si tel est le cas.
				}
			}
		}else {
			// dol_print_error('',"Bad value saved into sessions");
			$error++;
		}
	}
	}

	if (!$error)
	{
		unset($_SESSION['massstockmove']);

		$db->commit();
		setEventMessages($langs->trans("StockMovementRecorded"), null, 'mesgs');
		header("Location: ".DOL_URL_ROOT.'/product/stock/movement_list.php'); // Redirect to avoid pb when using back
		exit;
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}



/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formproduct = new FormProduct($db);
$productstatic = new Product($db);
$warehousestatics = new Entrepot($db);
$warehousestatict = new Entrepot($db);

$title = $langs->trans('MassMovement');

llxHeader('', $title);

print load_fiche_titre($langs->trans("MassStockTransferShort"), '', 'stock');

$titletoadd = $langs->trans("Select");
$buttonrecord = $langs->trans("RecordMovement");
$titletoaddnoent = $langs->transnoentitiesnoconv("Select");
$buttonrecordnoent = $langs->transnoentitiesnoconv("RecordMovement");
print '<span class="opacitymedium">'.$langs->trans("SelectProductInAndOutWareHouse", $titletoaddnoent, $buttonrecordnoent).'</span><br>';
print '<br>'."\n";

// Form to add a line
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addline">';


print '<div class="div-table-responsive-no-min">';
print '<table class="liste centpercent">';
//print '<div class="tagtable centpercent">';

$param = '';

print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('WarehouseSource'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('WarehouseTarget'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('ProductRef'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
if ($conf->productbatch->enabled) {
	print getTitleFieldOfList($langs->trans('Batch'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
}
print getTitleFieldOfList($langs->trans('Qty'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
print getTitleFieldOfList('', 0);
print '</tr>';


print '<tr class="oddeven">';
// From warehouse
print '<td>';
print $formproduct->selectWarehouses($id_sw, 'id_sw', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
print '</td>';
// To warehouse
print '<td>';
print $formproduct->selectWarehouses($id_tw, 'id_tw', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
print '</td>';
// Product
print '<td class="titlefield">';
$filtertype = 0;
if (!empty($conf->global->STOCK_SUPPORTS_SERVICES)) $filtertype = '';
if ($conf->global->PRODUIT_LIMIT_SIZE <= 0) {
	$limit = '';
} else {
	$limit = $conf->global->PRODUIT_LIMIT_SIZE;
}
$id_product = GETPOST('productid', 'int');
$form->select_produits($id_product, 'productid', $filtertype, $limit, 0, -1, 2, '', 1, array(), 0, '1', 0, 'minwidth200imp maxwidth300', 1);
print '</td>';
// Batch number
if ($conf->productbatch->enabled)
{
	print '<td>';
	/*** SPE AMA ***/
	print $form->selectarray("batch", array(), "batch", 1, 0, 0, '', 0, 0, 0, '', '', 1);
	//  print '<input type="text" name="batch" class="flat maxwidth50" value="'.$batch.'">';
	/*** FIN SPE AMA ***/
	print '</td>';
}
// Qty
print '<td class="center"><input type="number" step="any" min="0" class="flat maxwidth50" name="qty" value="'.$qty.'"></td>';
// Button to add line
print '<td class="right"><input type="submit" class="button" name="addline" value="'.dol_escape_htmltag($titletoadd).'"></td>';

print '</tr>';


foreach ($listofdata as $key => $val)
{
	$productstatic->fetch($val['id_product']);
	$warehousestatics->fetch($val['id_sw']);
	$warehousestatict->fetch($val['id_tw']);

	print '<tr class="oddeven">';
	print '<td>';
	print $warehousestatics->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $warehousestatict->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $productstatic->getNomUrl(1).' - '.$productstatic->label;
	print '</td>';
	if ($conf->productbatch->enabled)
	{
		print '<td>';
		print $val['batch'];
		print '</td>';
	}
	print '<td class="center">'.$val['qty'].'</td>';
	print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=delline&idline='.$val['id'].'">'.img_delete($langs->trans("Remove")).'</a></td>';

	print '</tr>';
}

print '</table>';
print '</div>';

print '</form>';


print '<br>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire2" class="formconsumeproduce">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="createmovements">';

// Button to record mass movement
$codemove = (GETPOSTISSET("codemove") ? GETPOST("codemove", 'alpha') : dol_print_date(dol_now(), '%Y%m%d%H%M%S'));
$labelmovement = GETPOST("label") ? GETPOST('label') : $langs->trans("StockTransfer").' '.dol_print_date($now, '%Y-%m-%d %H:%M');

print '<div class="center">';
print '<span class="fieldrequired">'.$langs->trans("InventoryCode").':</span> ';
print '<input type="text" name="codemove" class="maxwidth300" value="'.dol_escape_htmltag($codemove).'"> &nbsp; ';
print '<span class="clearbothonsmartphone"></span>';
print $langs->trans("MovementLabel").': ';
print '<input type="text" name="label" class="minwidth300" value="'.dol_escape_htmltag($labelmovement).'">';


/***** SPE AMA ******/
print ' '.$langs->trans("DateMovement").': ';
$inputDateMovement = GETPOSTISSET('dateMovement') ? GETPOST('dateMovement') : date('Y-m-d');
$inputTimeMovement = GETPOSTISSET('timeMovement') ? GETPOST('timeMovement') : date('H:i');
print '<input type="date" name="dateMovement"  value="'.$inputDateMovement.'" required /><input type="time" name="timeMovement" min="00:00" max="23:59" value="'.$inputTimeMovement.'" required /><br>';
/***** FIN SPE AMA ******/

print '<br><br>';

print '<div class="center"><input class="button" type="submit" name="valid" value="'.dol_escape_htmltag($buttonrecord).'"></div>';

print '<br>';
print '</div>';

print '</form>';

$jsonConf = array(
	'productbatchEnabled' => !empty($conf->productbatch->enabled)
);

?>
    <script type="text/javascript">
        $(document).ready(function(){

        	var massstockmoveConf = <?php print json_encode($jsonConf); ?>;

        	/***** SPE AMA : TODO : a un jour intégrer coeur dolibarr ******/
			// rechagement ajax de l'autocompletion des num de lot
			if (massstockmoveConf.productbatchEnabled)
			{
				updateBatchOptions();

				$(document).on("change", '#id_sw, #productid', function(event) {
					updateBatchOptions();
				});

				$(document).on("change", 'form[name=formulaire] select[name=batch]', function(event) {
					var maxQty = $(this).find('option:selected').attr("data-qty")
					if(maxQty != undefined){
						$('form[name=formulaire] input[name=qty]').attr('max', maxQty);
						if(maxQty>0){
							$('form[name=formulaire] input[name=qty]').val(1);
						}
					}
				});

			}


			function updateBatchOptions() {
				var data = {
					'fk_warehouse': $('#id_sw').val(),
					'fk_product': $('#productid').val()
				};

				if(data.fk_warehouse < 1 || data.fk_product < 1){
					return ;
				}

				// clean max input qty for batch
				$('form[name=formulaire] input[name=qty]').removeAttr('max').prop( "disabled", false );
				$('form[name=formulaire] input[name=addline]').prop( "disabled", false );

				$.ajax({
					url: "<?php print dol_buildpath('cliama/script/interface.php', 1) . '?get=available-stock'; ?>",
					method: "POST",
					data: data,
					dataType: "json",
					// La fonction à apeller si la requête aboutie
					success: function (data) {
						// Loading data
						if (data.result > 0) {
							let inputBatch = $('form[name=formulaire] select[name=batch]');
							updateImputOptions(inputBatch, data.TBatch, inputBatch.val());
							//data-width="100%"
							inputBatch.trigger('change');
							inputBatch.select2({
								width: 'resolve' // need to override the changed default
							});

							if(data.TBatch.length == 0 && data.hasbatch){
								$('form[name=formulaire] input[name=qty]').attr('max', 0).prop( "disabled", true );
								$('form[name=formulaire] input[name=addline]').prop( "disabled", true );
							}
							else if(!data.hasbatch){
								$('form[name=formulaire] input[name=qty]').attr('max', data.warehouseStock);
							}

						} else {
							if (data.errorMessage.length > 0) {
								$.jnotify(data.errorMessage, 'error', {timeout: 5, type: 'error', css: 'error'});
							} else {
								$.jnotify('UnknowError', 'error', {timeout: 5, type: 'error', css: 'error'});
							}
						}
					},
					// La fonction à appeler si la requête n'a pas abouti
					error: function (jqXHR, textStatus) {
						$.jnotify('Ajax Request failed : ' + textStatus, 'error', {timeout: 5, type: 'error', css: 'error'});
					}
				});
			}

			/**
			 * add array element into select field
			 *
			 * @param {jQuery} target The select input jquery element
			 * @param {array} data an array of object
			 * @param {string} selected The current selected value
			 */
			function updateImputOptions(target, data = false, selected = -1 )
			{

				/* Remove all options from the select list */
				target.empty();
				target.prop("disabled", true);

				if(Array.isArray(data))
				{
					/* Insert the new ones from the array above */
					for(var i= 0; i < data.length; i++)
					{
						let item = data[i];
						let newOption =  $('<option>', {
							value: item.id,
							text : item.label,
							'data-qty' : item.qty
						});

						if(selected == item.id){
							newOption.prop('selected');
						}

						target.append(newOption);
					}

					if(data.length > 0){
						target.prop("disabled", false);
					}
				}
			}

			/***** FIN SPE AMA ******/

            $('.savetransfert').on('click', function(e){
                var tr = $(this).closest('tr');
                var id = $(this).data('id');
                var table = $(this).closest('table');
                var type = table.find('[name="type_trans"]').val();
                var date = table.find('[name="date_mouv"]').val();
                var source = tr.find('[name="source_serial"]').val();
                var target = tr.find('[name="target_serial"]').val();
                var user_id = tr.find('[name^="user_id"]').val();
                if (user_id == -1) user_id = 0;
                var num_inv = tr.find('[name="num_inventaire"]').val();

                $.ajax({
                    url: "<?php echo dol_buildpath('/cliama/script/interface.php', 1) ?>"
                    ,data: {
                        put:'save_transfert'
                        ,id:id
                        ,type:type
                        ,date:date
                        ,source_serial:source
                        ,target_serial:target
                        ,user_id:user_id
                        ,num_inventaire:num_inv
                    }
                    ,dataType:'json'
                }).done(function(data){
                    if(data.success) $(this).hide();
                })
            })


            $('#type_trans').on('change', function(e){
                val = $(this).val();
                parent_id = $(this).data('parent');
                $('.type_trans[data-parent="'+parent_id+'"]').each(function(i, item){
                    if ($(item).val() != val)
                    {
                        $(item).val(val);
                        $(item).parent().parent().parent()
                            .find('.savetransfert')
                            .trigger('click');
                    }
                    else
                    {
                        $(item).parent().parent().parent()
                            .find('.savetransfert')
                            .trigger('click');
                    }
                })
            });

            $('#date_mouv').datepicker('option', 'onSelect', function() {
                val = $(this).val();
                parent_id = $(this).parent().parent().prev().val();

                $('[name="parent_id"][value="'+parent_id+'"]').each(function(i, item){
                    date = $(item).closest('table').find('#date_mouv');
                    date.val(val);
                    $(item).parent().parent()
                        .find('.savetransfert')
                        .trigger('click');
                });
            });


            $('[name="source_serial"]').each(function(i, item){
                $(item).autocomplete({
                    source: function( request, response ) {
                        $.ajax({
                            url: "<?php echo dol_buildpath('/cliama/script/interface.php', 1) ?>",
                            dataType: "json",
                            data: {
                                serial: request.term
                                ,product_id:$(item).data('product')
                                ,ent_id:$(item).data('ent_id')
                                , get: 'autocompleteAsset'
                            }
                            ,success: function( data ) {
                                $(item).removeClass('ui-autocomplete-loading');
                                console.log(data);
                                var c = [];
                                $.each(data, function(i, sn)
                                {
                                    c.push({ value: i, label:'  '+i, object:i});
                                })
                                response(c);
                            }
                        })
                    },
                    minLength: 1,
                    select: function( event, ui ) {
                        $(item).parent().parent().find('#target_serial').val(ui.item.value);
                        $(item).val(ui.item.value)
                        $(item).parent().parent().find('.savetransfert').click();
                    }
                });

                $( '[name="source_serial"]' ).autocomplete().data("uiAutocomplete")._renderItem = function( ul, item ) {

                    $li = $( "<li />" )
                        .attr( "data-value", item.value )
                        .append( item.label )
                        .appendTo( ul );

                    return $li;
                };
            });

            $('[name^="user_id"]').on('change', function(){
                $(this).parent().parent()
                    .find('.savetransfert')
                    .trigger('click');
            });

            $('[name="target_serial"]').on('change', function(e){
                source = $(this).parent().parent().find('#source_serial').val();
                product = $(this).parent().parent().find('#source_serial').data('product');
                span = $(this).next();
                dest = $(this).val();


                if (source != dest)
                {
                    assetexists(dest, product, span)
                }
                else{
                    span.hide();
                    $('[name="valid"]').attr('disabled', false);
                    $(this).parent().parent()
                        .find('.savetransfert')
                        .trigger('click');

                }
            })


            $('[name="num_inventaire"]').on('keyup', function(e){
                $(this).parent().parent()
                    .find('.savetransfert')
                    .trigger('click');
            })

            $('[name="target_serial"]').each(function(i, item){
                source = $(item).parent().parent().find('#source_serial').val();
                product = $(item).parent().parent().find('#source_serial').data('product');
                span = $(item).next();
                dest = $(item).val();

                if (source != dest)
                {
                    assetexists(dest, product, span)
                }
            });

            function assetexists(target_serial, product, element)
            {
                $.ajax({
                    url:"<?php echo dol_buildpath('/cliama/script/interface.php', 1) ?>",
                    dataType: "json",
                    data: {
                        serial: target_serial
                        ,product_id:product
                        , get: 'existingAsset'
                    }
                }).done(function(data){
                    if (data.success == true) {
                        if (data.response == true){
                            element.show();
                            $('[name="valid"]').attr('disabled', true);
                        }
                        else{
                            element.hide();
                            $('[name="valid"]').attr('disabled', false);
                            element.parent().parent()
                                .find('.savetransfert')
                                .trigger('click');
                        }
                    }
                });
            }

        });



    </script>
<?php
// End of page
llxFooter();
$db->close();
