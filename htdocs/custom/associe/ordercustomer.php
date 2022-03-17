<?php
/**
 *  \file       htdocs/product/stock/replenish.php
 *  \ingroup    produit
 *  \brief      Page to list stocks to replenish
 */

use App\Ass\Place;

require 'config.php';

ini_set('memory_limit', '1024M');
set_time_limit(0);

ini_set('display_errors', 1);
//error_reporting(E_ALL);

dol_include_once('/product/class/product.class.php');
dol_include_once('/core/class/html.formother.class.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/fourn/class/fornisseur.commande.class.php');
dol_include_once("/core/lib/admin.lib.php");
dol_include_once("/fourn/class/fournisseur.class.php");
dol_include_once('/associe/lib/function.lib.php');
dol_include_once("/commande/class/commande.class.php");
dol_include_once("/supplier_proposal/class/supplier_proposal.class.php");
dol_include_once('/associe/class/sofo.class.php');
if (!empty($conf->categorie->enabled)) {
	dol_include_once('/categories/class/categorie.class.php');
}
dol_include_once('/associe/class/cmdAss.class.php');

global $bc, $conf, $db, $langs, $user;

$prod = new Product($db);

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");
$langs->load("associe@associe");

$dolibarr_version35 = false;

if ((float)DOL_VERSION >= 3.5) {
	$dolibarr_version35 = true;
}
/*echo "<form name=\"formCreateSupplierOrder\" method=\"post\" action=\"ordercustomer.php\">";*/

// Security check
if ($user->societe_id) {
	$socid = $user->societe_id;
}
$result = restrictedArea($user, 'produit|service&associe');

//checks if a product has been ordered

$action = GETPOST('action', 'alpha');
$sref = GETPOST('sref', 'alpha');
$snom = GETPOST('snom', 'alpha');
$sall = GETPOST('sall', 'alpha');
$type = GETPOST('type', 'int');
$tobuy = GETPOST('tobuy', 'int');
$salert = GETPOST('salert', 'alpha');
$fourn_id = GETPOST('ass_id', 'intcomma');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$page = intval($page);
$selectedSupplier = GETPOST('useSameSupplier', 'int');

if (!$sortfield) {
	$sortfield = 'cd.rang';
}

if (!$sortorder) {
	$sortorder = 'ASC';
}
$conf->liste_limit = 1000; // Pas de pagination sur cet écran
$limit = $conf->liste_limit;
$offset = $limit * $page;


$TCategories = array();

if (!empty($conf->categorie->enabled)) {

	if (!isset($_REQUEST['categorie'])) {
		$TCategories = unserialize($conf->global->ASS_DEFAULT_PRODUCT_CATEGORY_FILTER);
	} else {
		$categories = GETPOST('categorie', 'none');

		if (is_array($categories)) {
			if (in_array(-1, $categories) && count($categories) > 1) {
				unset($categories[array_search(-1, $categories)]);
			}
			$TCategories = array_map('intval', $categories);
		} elseif ($categories > 0) {
			$TCategories = array(intval($categories));
		} else {
			$TCategories = array(-1);
		}
	}
}

$TCategoriesQuery = $TCategories;
if (!empty($TCategoriesQuery) && is_array($TCategoriesQuery)) {
	foreach ($TCategories as $categID) {
		if ($categID <= 0)
			continue;

		$cat = new Categorie($db);
		$cat->fetch($categID);

		$TSubCat = get_categs_enfants($cat);
		foreach ($TSubCat as $subCatID) {
			if (!in_array($subCatID, $TCategories)) {
				$TCategoriesQuery[] = $subCatID;
			}
		}
	}
}

if (is_array($TCategoriesQuery) && count($TCategoriesQuery) == 1 && in_array(-1, $TCategoriesQuery)) {
	$TCategoriesQuery = array();
}


/*
 * Actions
 */


if (isset($_POST['button_removefilter']) || in_array($action, array('valid-propal', 'valid-order'))) {
	$sref = '';
	$snom = '';
	$sal = '';
	$salert = '';
	$TCategoriesQuery = array();
	$TCategories = array(-1);
}

/*echo "<pre>";
print_r($_REQUEST);
echo "</pre>";
exit;*/

if (in_array($action, array('apply-all'))) {
	if(!empty($_POST['getallass'])){
		$selected = $_POST['getallass'];
	}
}

//orders creation
//FIXME: could go in the lib
if (in_array($action, array('valid-propal', 'valid-order'))) {


	$actionTarget = 'order';
	if ($action == 'valid-propal') {
		$actionTarget = 'propal';
	}


	$linecount = GETPOST('linecount', 'int');
	$box = false;
	unset($_POST['linecount']);
	if ($linecount > 0) {

		$suppliers = array();

		for ($i = 0; $i < $linecount; $i++) {

			if (GETPOST('check' . $i, 'alpha') === 'on' && (GETPOST('ass' . $i, 'int') > 0 || GETPOST('ass_free' . $i, 'int') > 0)) { //one line
				_prepareLine($i, $actionTarget);
			}
			unset($_POST[$i]);

		}

		//we now know how many orders we need and what lines they have
		$i = 0;
		$id = 0;
		$nb_orders_created = 0;
		$orders = array();
		$suppliersid = array_keys($suppliers);
		$projectid = GETPOST('projectid', 'int');

		foreach ($suppliers as $idsupplier => $supplier) {


			if ($actionTarget == 'propal') {
				$order = new SupplierProposal($db);
				$obj = _getSupplierProposalInfos($idsupplier, $projectid);
			} else {
				$order = new CommandeFournisseur($db);
				$obj = _getSupplierOrderInfos($idsupplier, $projectid);
			}

			$commandeClient = new Commande($db);
			$commandeClient->fetch($_REQUEST['id']);

			// Test recupération contact livraison
			if ($conf->global->ASSOCIE_CONTACT_DELIVERY) {
				$contact_ship = $commandeClient->getIdContact('external', 'SHIPPING');
				$contact_ship = $contact_ship[0];
			} else {
				$contact_ship = null;
			}


			//Si une commande au statut brouillon existe déjà et que l'option ASS_CREATE_NEW_SUPPLIER_ODER_ANY_TIME
			if ($obj && !$conf->global->ASS_CREATE_NEW_SUPPLIER_ODER_ANY_TIME) {

				$order->fetch($obj->rowid);
				$order->socid = $idsupplier;

				if (!empty($projectid)) {
					$order->fk_project = GETPOST('projectid', 'int');
				}

				// On vérifie qu'il n'existe pas déjà un lien entre la commande client et la commande associe dans la table element_element.
				// S'il n'y en a pas, on l'ajoute, sinon, on ne l'ajoute pas
				$order->fetchObjectLinked('', 'commande', $order->id, 'order_supplier');
				$order->add_object_linked('commande', $_REQUEST['id']);

				// cond reglement, mode reglement, delivery date
				_appliCond($order, $commandeClient);


				$id++; //$id doit être renseigné dans tous les cas pour que s'affiche le message 'Vos commandes ont été générées'
				$newCommande = false;

			} else {

				$order->socid = $idsupplier;
				if (!empty($projectid)) {
					$order->fk_project = GETPOST('projectid', 'int');
				}

				// cond reglement, mode reglement, delivery date
				_appliCond($order, $commandeClient);

				$id = $order->create($user);
				if ($contact_ship && $conf->global->ASSOCIE_CONTACT_DELIVERY)
					$order->add_contact($contact_ship, 'SHIPPING');
				$order->add_object_linked('commande', $_REQUEST['id']);
				$newCommande = true;

				$nb_orders_created++;
			}


			$order_id = $order->id;
			//trick to know which orders have been generated this way
			$order->source = 42;
			$MaxAvailability = 0;

			foreach ($supplier['lines'] as $line) {

				$done = false;

				$prodfourn = new ProductFournisseur($db);
				$prodfourn->fetch_product_fournisseur_price($_REQUEST['ass' . $i]);

				foreach ($order->lines as $lineOrderFetched) {
					if ($line->fk_product == $lineOrderFetched->fk_product) {

						$remise_percent = $lineOrderFetched->remise_percent;
						if ($line->remise_percent > $remise_percent)
							$remise_percent = $line->remise_percent;

						if ($order->element == 'order_supplier') {
							$order->updateline(
								$lineOrderFetched->id,
								$lineOrderFetched->desc,
								// FIXME: The current existing line may very well not be at the same purchase price
								$lineOrderFetched->pu_ht,
								$lineOrderFetched->qty + $line->qty,
								$remise_percent,
								$lineOrderFetched->tva_tx
							);
						} else if ($order->element == 'supplier_proposal') {

							$order->updateline(
								$lineOrderFetched->id,
								$prodfourn->fourn_unitprice, //$lineOrderFetched->pu_ht is empty,
								$lineOrderFetched->qty + $line->qty,
								$remise_percent,
								$lineOrderFetched->tva_tx,
								0, //$txlocaltax1=0,
								0, //$txlocaltax2=0,
								$lineOrderFetched->desc
							//$price_base_type='HT',
							//$info_bits=0,
							//$special_code=0,
							//$fk_parent_line=0,
							//$skip_update_total=0,
							//$fk_fournprice=0,
							//$pa_ht=0,
							//$label='',
							//$type=0,
							//$array_option=0,
							//$ref_fourn='',
							//$fk_unit=''
							);
						}

						$done = true;
						break;
						
					}
					
				}
				
				// On ajoute une ligne seulement si un "updateline()" n'a pas été fait et si la quantité souhaitée est supérieure à zéro
				
				if (!$done) {
					$totalsubprice += $line->subprice*$line->qty;
					$paht = 0;
					if($line->fk_product){
						$paht = CMDGrandCompte::getPaFourn($line->origin_id, $line->fk_product, $_GET["id"]);
					}			
					if ($order->element == 'order_supplier') {
						$order->addline(
							$line->desc,
							$line->subprice,
							$line->qty,
							$line->tva_tx,
							null,
							null,
							$line->fk_product,
							// We need to pass fk_prod_fourn_price to get the right price.
							$line->fk_prod_fourn_price,
							$line->ref_fourn,
							$line->remise_percent
							, 'HT'
							, 0
							, $line->product_type
							, $line->info_bits
							, FALSE // $notrigger
							, NULL // $date_start
							, NULL // $date_end
							, $line->array_options
							, null
							, 0
							, $line->origin
							, $line->origin_id
						);
						if($_POST["ugapeda"] == "on"){
							$remisepa = 0;
							if($line->fk_product){
								if($paht && $paht > 0){
									$remisepa = 100 - $paht * 100 / $line->subprice;
									$order->addline(
										$langs->trans('UGAPEDA'),
										-$line->subprice,
										$line->qty,
										20,
										null,
										null,
										0,
										0,
										'',
										$remisepa,
									);
								}
							}
							// var_dump($line);
						}
					} else if ($order->element == 'supplier_proposal') {
						$order->addline(
							$line->desc,
							$line->subprice,
							$line->qty,
							$line->tva_tx,
							null,
							null,
							$line->fk_product,
							$line->remise_percent,
							'HT',
							0, //$pu_ttc=0,
							$line->info_bits, //$info_bits=0,
							$line->product_type, //$type=0,
							-1, //$rang=-1,
							0, //$special_code=0, ,
							0, //$fk_parent_line=0, ,
							$line->fk_prod_fourn_price, //$fk_fournprice=0, ,
							0, //$pa_ht=0, ,
							'', //$label='',,
							$line->array_options, //$array_option=0, ,
							$line->ref_fourn, //$ref_fourn='', ,
							'', //$fk_unit='', ,
							$line->origin, //$origin='', ,
							$line->origin_id//$origin_id=0
						);


					}

				}

				$nb_day = (int)TASS::getMinAvailability($line->fk_product, $line->qty, 1, $prodfourn->fourn_id);
				if ($MaxAvailability < $nb_day) {
					$MaxAvailability = $nb_day;
				}


			}

			if($_POST["commEuroch"] == "on"){
				// var_dump($totalsubprice);
				// var_dump(CMDGrandCompte::getCommEurochef('CommEC',false));
				$comm = CMDGrandCompte::getCommEurochef('CommEC',false);
				$commpos = $_POST['commEC'];
				$remise = $comm[$commpos]->comm;
				$order->addline(
					$comm[$commpos]->ref,
					-$totalsubprice,
					1,
					20,
					null,
					null,
					$comm[$commpos]->rowid,
					0,
					'',
					100-$remise,
				);
			}

			if($_POST["bfaClient"] == "on"){
				// var_dump(CMDGrandCompte::getBfaTier('bfaTier',false));
				$bfa = CMDGrandCompte::getBfaTier('bfaTier',false);
				$bfapos = $_POST['bfaTier'];
				$remise = $bfa[$bfapos]->bfaClient;
				$order->addline(
					$bfa[$bfapos]->ref,
					-$totalsubprice,
					1,
					20,
					null,
					null,
					$bfa[$bfapos]->rowid,
					0,
					'',
					100-$remise,
				);
			}

			if (!empty($conf->global->ASS_USE_MAX_DELIVERY_DATE)) {
				$order->date_livraison = dol_now() + $MaxAvailability * 86400;
				$order->set_date_livraison($user, $order->date_livraison);
			}

			$order->cond_reglement_id = 0;
			$order->mode_reglement_id = 0;

			if ($id < 0) {
				$fail++; // FIXME: declare somewhere and use, or get rid of it!
				$msg = $langs->trans('OrderFail') . "&nbsp;:&nbsp;";
				$msg .= $order->error;
				setEventMessage($msg, 'errors');
			} else {
				// CODE de redirection s'il y a un seul associe (évite de le laisser sur la page sans comprendre)
				if ($conf->global->ASSOCIE_HEADER_SUPPLIER_ORDER) {
					if (count($suppliersid) == 1) {
						if ($action === 'valid-order')
							$link = dol_buildpath('/fourn/commande/card.php?id=' . $order_id, 1);
						else $link = dol_buildpath('/supplier_proposal/card.php?id=' . $order_id, 1);
						header('Location:' . $link);
					}
				}
			}
			$i++;
		}


	}

	if ($nb_orders_created > 0) {
		setEventMessages($langs->trans('associe_nb_orders_created', $nb_orders_created), array());
	}

	if ($box === false) {
	} else {

		foreach ($suppliers as $idSupplier => $lines) {
			$j = 0;
			foreach ($lines as $line) {
				$sql = "SELECT quantity";
				$sql .= " FROM " . MAIN_DB_PREFIX . "product_fournisseur_price";
				$sql .= " WHERE fk_soc = " . $idSupplier;
				$sql .= " AND fk_product = " . $line[$j]->fk_product;
				$sql .= " ORDER BY quantity ASC";
				$sql .= " LIMIT 1";
				$resql = $db->query($sql);
				if ($resql) {
					$resql = $db->fetch_object($resql);

					//echo $j;

					if ($line[$j]->qty < $resql->quantity) {
						$p = new Product($db);
						$p->fetch($line[$j]->fk_product);
						$f = new Fournisseur($db);
						$f->fetch($idSupplier);
						$rates[$f->name] = $p->label;
					} else {
						$p = new Product($db);
						$p->fetch($line[$j]->fk_product);
						$f = new Fournisseur($db);
						$f->fetch($idSupplier);
						$ajoutes[$f->name] = $p->label;
					}
				}

				/*echo "<pre>";
				print_r($rates);
				echo "</pre>";
				echo "<pre>";
				print_r($ajoutes);
				echo "</pre>";*/
				$j++;
			}
		}
		$mess = "";
		// FIXME: declare $ajoutes somewhere. It's unclear if it should be reinitialized or not in the interlocking loops.
		if ($ajoutes) {
			foreach ($ajoutes as $nomAssocie => $nomProd) {

				if ($actionTarget == 'propal') {
					$mess .= $langs->trans('ProductAddToSupplierQuotation', $nomProd, $nomAssocie) . '<br />';
				} else {
					$mess .= $langs->trans('ProductAddToSupplierOrder', $nomProd, $nomAssocie) . '<br />';
				}

			}
		}
		// FIXME: same as $ajoutes.
		if ($rates) {
			foreach ($rates as $nomAssocie => $nomProd) {
				$mess .= "Quantité insuffisante de ' " . $nomProd . " ' pour l'associé ' " . $nomAssocie . " '<br />";
			}
		}
		if ($rates) {
			setEventMessage($mess, 'warnings');
		} else {
			setEventMessage($mess, 'mesgs');
		}
	}
}

/*
 * View
 */

$TCachedProductId =& $_SESSION['TCachedProductId'];
if (empty($TCachedProductId))
	$TCachedProductId = array();
if (GETPOST('purge_cached_product', 'none') == 'yes')
	$TCachedProductId = array();

//Do we want include shared sotck to kwon what order
if (empty($conf->global->ASS_CHECK_STOCK_ON_SHARED_STOCK)) {
	$entityToTest = $conf->entity;
} else {
	$entityToTest = getEntity('stock');
}

$title = $langs->trans('ProductsToOrder');
$db->query("SET SQL_MODE=''");
$sql = 'SELECT p.rowid, p.ref, p.label, cd.description, p.price, SUM(cd.qty) as qty, cd.buy_price_ht';
$sql .= ', p.price_ttc, p.price_base_type,p.fk_product_type';
$sql .= ', p.tms as datem, p.duration, p.tobuy, p.seuil_stock_alerte, p.finished, cd.rang,';
$sql .= ' cd.rowid as lineid,';
$sql .= ' ( SELECT SUM(s.reel) FROM ' . MAIN_DB_PREFIX . 'product_stock s
		INNER JOIN ' . MAIN_DB_PREFIX . 'entrepot as entre ON entre.rowid=s.fk_entrepot WHERE s.fk_product=p.rowid
		AND entre.entity IN (' . $entityToTest . ')) as stock_physique';
$sql .= $dolibarr_version35 ? ', p.desiredstock' : "";
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'product as p';
$sql .= ' LEFT OUTER JOIN ' . MAIN_DB_PREFIX . 'commandedet as cd ON (p.rowid = cd.fk_product)';

if (!empty($TCategoriesQuery)) {
	$sql .= ' LEFT OUTER JOIN ' . MAIN_DB_PREFIX . 'categorie_product as cp ON (p.rowid = cp.fk_product)';
}

//$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_stock as s ON (p.rowid = s.fk_product)';
$sql .= ' WHERE p.fk_product_type IN (0,1) AND p.entity IN (' . getEntity("product", 1) . ')';

$fk_commande = GETPOST('id', 'int');

if ($fk_commande > 0)
	$sql .= ' AND cd.fk_commande = ' . $fk_commande;

if (!empty($TCategoriesQuery))
	$sql .= ' AND cp.fk_categorie IN ( ' . implode(',', $TCategoriesQuery) . ' ) ';

if ($sall) {
	$sql .= ' AND (p.ref LIKE "%' . $db->escape($sall) . '%" ';
	$sql .= 'OR p.label LIKE "%' . $db->escape($sall) . '%" ';
	$sql .= 'OR p.description LIKE "%' . $db->escape($sall) . '%" ';
	$sql .= 'OR p.note LIKE "%' . $db->escape($sall) . '%")';
}
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type)) {
	if ($type == 1) {
		$sql .= ' AND p.fk_product_type = 1';
	} else {
		$sql .= ' AND p.fk_product_type != 1';
	}
}
if ($sref) {
	//natural search
	$scrit = explode(' ', $sref);
	foreach ($scrit as $crit) {
		$sql .= ' AND p.ref LIKE "%' . $crit . '%"';
	}
}
if ($snom) {
	//natural search
	$scrit = explode(' ', $snom);
	foreach ($scrit as $crit) {
		$sql .= ' AND p.label LIKE "%' . $db->escape($crit) . '%"';
	}
}

$sql .= ' AND p.tobuy = 1';

$finished = GETPOST('finished', 'none');
if ($finished != '' && $finished != '-1')
	$sql .= ' AND p.finished = ' . $finished;
elseif (!isset($_REQUEST['button_search_x']) && isset($conf->global->ASS_DEFAUT_FILTER) && $conf->global->ASS_DEFAUT_FILTER >= 0)
	$sql .= ' AND p.finished = ' . $conf->global->ASS_DEFAUT_FILTER;

if (!empty($canvas)) {
	$sql .= ' AND p.canvas = "' . $db->escape($canvas) . '"';
}

if ($salert == 'on') {
	$sql .= " AND p.seuil_stock_alerte is not NULL ";

}

$sql .= ' GROUP BY p.rowid, p.ref, p.label, p.price';
$sql .= ', p.price_ttc, p.price_base_type,p.fk_product_type, p.tms';
$sql .= ', p.duration, p.tobuy, p.seuil_stock_alerte';
//$sql .= ', cd.rang';
//$sql .= ', p.desiredstock';
//$sql .= ', s.fk_product';

//if(!empty($conf->global->ASSOCIE_USE_ORDER_DESC)) {
$sql .= ', cd.description';
//}
//$sql .= ' HAVING p.desiredstock > SUM(COALESCE(s.reel, 0))';
//$sql .= ' HAVING p.desiredstock > 0';
if ($salert == 'on') {
	$sql .= ' HAVING stock_physique < p.seuil_stock_alerte ';
	$alertchecked = 'checked="checked"';
}

$sql2 = '';
//On prend les lignes libre
if ($_REQUEST['id'] && $conf->global->ASS_ADD_FREE_LINES) {
	$sql2 .= 'SELECT cd.rowid, cd.description, cd.qty as qty, cd.product_type, cd.price, cd.buy_price_ht
			 FROM ' . MAIN_DB_PREFIX . 'commandedet as cd
			 	LEFT JOIN ' . MAIN_DB_PREFIX . 'commande as c ON (cd.fk_commande = c.rowid)
			 WHERE c.rowid = ' . $_REQUEST['id'] . ' AND cd.product_type IN(0,1) AND fk_product IS NULL';
	if (!empty($conf->global->ASSOCIE_USE_ORDER_DESC)) {
		$sql2 .= ' GROUP BY cd.description';
	}
	//echo $sql2;
}
$sql .= $db->order($sortfield, $sortorder);

//echo $sql;

if (!$conf->global->ASS_USE_DELIVERY_TIME)
	$sql .= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);

if (isset($_REQUEST['DEBUG']) || $resql === false) {
	print $sql;
	exit;
}

if ($sql2 && $fk_commande > 0) {
	$sql2 .= $db->order($sortfield, $sortorder);
	$sql2 .= $db->plimit($limit + 1, $offset);
	$resql2 = $db->query($sql2);
}
//print $sql ;
$justOFforNeededProduct = !empty($conf->global->ASS_USE_ONLY_OF_FOR_NEEDED_PRODUCT) && empty($fk_commande);
$statutarray = array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
$form = new Form($db);

if ($resql || $resql2) {
	$num = $db->num_rows($resql);

	//pour chaque produit de la commande client on récupère ses sous-produits

	$TProducts= array(); //on rassemble produit et sous-produit dans ce tableau
	$i = 0;

	while ($i < min($num, $limit)) {

		//fetch le produit
		$objp = $db->fetch_object($resql);

		array_push($TProducts, $objp);

		$product = new Product($db);
		$product->fetch($objp->rowid);

		if(!empty($conf->global->PRODUIT_SOUSPRODUITS) && !empty($conf->global->ASS_VIRTUAL_PRODUCTS)) {

			//récupération des sous-produits
			$product->get_sousproduits_arbo();
			$prods_arbo = $product->get_arbo_each_prod();

			if (!empty($prods_arbo)) {

				$TProductToHaveQtys = array();        //tableau des dernières quantités à commander par niveau

				foreach ($prods_arbo as $key => $value) {

					//si on est au premier niveau, on réinitialise
					if ($value['level'] == 1) {
						$TProductToHaveQtys[$value['level']] = $objp->qty;
						$qtyParentToHave = $TProductToHaveQtys[$value['level']];
					}

					//si on est au niveau supérieur à 1, alors on récupère la quantité de produit parent à avoir
					if ($value['level'] > 1) {
						$qtyParentToHave = $TProductToHaveQtys[$value['level'] - 1];
					}


					//on définit l'objet sous produit

					$objsp = new stdClass();
					
					$sousproduit = new Product($db);
					$sousproduit->fetch($value['id']);

					$objsp->rowid = $sousproduit->id;
					$objsp->ref = $sousproduit->ref;
					$objsp->label = $sousproduit->label;
					$objsp->price = $sousproduit->price;
					$objsp->price_ttc = $sousproduit->price_ttc;
					$objsp->price_base_type = $sousproduit->price_base_type;
					$objsp->fk_product_type = $sousproduit->type;
					$objsp->datem = $sousproduit->date_modification;
					$objsp->duration = $sousproduit->duration_value;
					$objsp->tobuy = $sousproduit->status_buy;
					$objsp->seuil_stock_alert = $sousproduit->seuil_stock_alerte;
					$objsp->finished = $sousproduit->finished;
					$objsp->stock_physique = $sousproduit->stock_reel;
					$objsp->qty =  $qtyParentToHave * $value['nb'];			//qty du produit = quantité du produit parent commandé * nombre du sous-produit nécessaire pour le produit parent
					$objsp->desiredstock = $sousproduit->desiredstock;
					$objsp->fk_parent = $value['id_parent'];
					$objsp->level = $value['level'];

					//Sauvegarde du dernier stock commandé pour le niveau du sous-produit
					$TProductToHaveQtys[$value['level']] = $objsp->qty;

					//ajout du sous-produit dans le tableau
					array_push($TProducts, $objsp);

				}

			}
		}

		$i++;
	}

	$i = 0;
	$num = count($TProducts);
	$num2 = $sql2 ? $db->num_rows($resql2) : 0;

	$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|';
	$helpurl .= 'ES:M&oacute;dulo_Stocks';
	llxHeader('', $title, $helpurl, $title);

	$head = array();
	$head[0][0] = dol_buildpath('/associe/ordercustomer.php?id=' . $_REQUEST['id'], 2);
	$head[0][1] = $title;
	$head[0][2] = 'associe';


	if (!empty($conf->global->ASS_USE_NOMENCLATURE)) {
		$head[1][0] = dol_buildpath('/associe/dispatch_to_supplier_order.php?from=commande&fromid=' . $_REQUEST['id'], 2);
		$head[1][1] = $langs->trans('ProductsAssetsToOrder');
		$head[1][2] = 'associe_dispatch';
	}

	/*$head[1][0] = DOL_URL_ROOT.'/product/stock/replenishorders.php';
	$head[1][1] = $langs->trans("ReplenishmentOrders");
	$head[1][2] = 'replenishorders';*/
	dol_fiche_head($head, 'associe', $langs->trans('Replenishment'), 0, 'stock');


	if ($sref || $snom || $sall || $salert || GETPOST('search', 'alpha')) {
		$filters = '&sref=' . $sref . '&snom=' . $snom;
		$filters .= '&sall=' . $sall;
		$filters .= '&salert=' . $salert;

		if (!$conf->global->ASS_USE_DELIVERY_TIME) {

			print_barre_liste(
				$title,
				$page,
				'ordercustomer.php',
				$filters,
				$sortfield,
				$sortorder,
				'',
				$num);
		}

	} else {
		$filters = '&sref=' . $sref . '&snom=' . $snom;
		$filters .= '&fourn_id=' . $fourn_id;
		$filters .= (isset($type) ? '&type=' . $type : '');
		$filters .= '&salert=' . $salert;

		if (!$conf->global->ASS_USE_DELIVERY_TIME) {

			print_barre_liste(
				$title,
				$page,
				'ordercustomer.php',
				$filters,
				$sortfield,
				$sortorder,
				'',
				$num
			);

		}
	}

	print'</div>';
	print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $_REQUEST['id'] . '&projectid=' . $_REQUEST['projectid'] . '" method="post" name="formulaire">' .
		'<input type="hidden" name="id" value="' . $_REQUEST['id'] . '">' .
		'<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' .
		'<input type="hidden" name="sortfield" value="' . $sortfield . '">' .
		'<input type="hidden" name="sortorder" value="' . $sortorder . '">' .
		'<input type="hidden" name="type" value="' . $type . '">' .
		'<input type="hidden" name="linecount" value="' . ($num + $num2) . '">' .
		'<input type="hidden" name="fk_commande" value="' . GETPOST('fk_commande', 'int') . '">' .
		'<input type="hidden" name="show_stock_no_need" value="' . GETPOST('show_stock_no_need', 'none') . '">'/* .

		'<div style="text-align:right">
			<a href="' . $_SERVER["PHP_SELF"] . '?' . $_SERVER["QUERY_STRING"] . '&show_stock_no_need=yes">' . $langs->trans('ShowLineEvenIfStockIsSuffisant') . '</a>'*/;

	if (!empty($TCachedProductId)) {
		echo '<a style="color:red; font-weight:bold;" href="' . $_SERVER["PHP_SELF"] . '?' . $_SERVER["QUERY_STRING"] . '&purge_cached_product=yes">' . $langs->trans('PurgeSessionForCachedProduct') . '</a>';
	}

	print '	  </div>' .
		'<table class="liste" width="100%">';


	$colspan = 9;
	if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
		$colspan++;
	if (!empty($conf->of->enabled) && !empty($conf->global->OF_USE_DESTOCKAGE_PARTIEL)) {
		$colspan++;
	}
	if (!empty($conf->global->ASS_USE_DELIVERY_TIME)) {
		$colspan++;
	}
	if (!empty($conf->categorie->enabled) && !empty($conf->global->ASS_DISPLAY_CAT_COLUMN)) {
		$colspan++;
	}
	if (!empty($conf->service->enabled) && $type == 1) {
		$colspan++;
	}
	if ($dolibarr_version35) {
		$colspan++;
	}

	if (!empty($conf->global->ASS_USE_DELIVERY_TIME)) {
		$week_to_replenish = (int)GETPOST('week_to_replenish', 'int');


		print '<tr class="liste_titre">' .
			'<td colspan="' . $colspan . '">' . $langs->trans('NbWeekToReplenish') . '<input type="text" name="week_to_replenish" value="' . $week_to_replenish . '" size="2"> '
			. '<input type="submit" value="' . $langs->trans('ReCalculate') . '" /></td>';

		print '</tr>';


	}

	if (!empty($conf->categorie->enabled)) {
		print '<tr class="liste_titre_filter">';
		print '<td colspan="2" >';
		print $langs->trans("Categories");
		print '</td>';
		print '<td colspan="' . ($colspan - 2) . '" >';
		print getCatMultiselect('categorie', $TCategories);
		print '<a id="clearfilter" href="javascript:;">' . $langs->trans('DeleteFilter') . '</a>';
		?>
		<script type="text/javascript">
			$('a#clearfilter').click(function () {
				$('option:selected', $('select#categorie')).prop('selected', false);
				$('option[value=-1]', $('select#categorie')).prop('selected', true);
				$('form[name=formulaire]').submit();
				return false;
			})
		</script>
		<?php
		print '</td>';
		print '</tr>';
	}


	$param = (isset($type) ? '&type=' . $type : '');
	$param .= '&fourn_id=' . $fourn_id . '&snom=' . $snom . '&salert=' . $salert;
	$param .= '&sref=' . $sref;

	// Lignes des titres
	print '<tr class="liste_titre_filter">' .
		'<th class="liste_titre"><input type="checkbox" onClick="toggle(this)" /></th>';
	print_liste_field_titre(
		$langs->trans('Ref'),
		'ordercustomer.php',
		'p.ref',
		$param,
		'id=' . $_REQUEST['id'],
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans('Label'),
		'ordercustomer.php',
		'p.label',
		$param,
		'id=' . $_REQUEST['id'],
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans('Nature'),
		'ordercustomer.php',
		'p.label',
		$param,
		'id=' . $_REQUEST['id'],
		'',
		$sortfield,
		$sortorder
	);
	if (!empty($conf->categorie->enabled) && !empty($conf->global->ASS_DISPLAY_CAT_COLUMN)) {
		print_liste_field_titre(
			$langs->trans("Categories"),
			'ordercustomer.php',
			'cp.fk_categorie',
			$param,
			'id=' . $_REQUEST['id'],
			'',
			$sortfield,
			$sortorder
		);
	}
	if (!empty($conf->service->enabled) && $type == 1) {
		print_liste_field_titre(
			$langs->trans('Duration'),
			'ordercustomer.php',
			'p.duration',
			$param,
			'id=' . $_REQUEST['id'],
			'align="center"',
			$sortfield,
			$sortorder
		);
	}

	if ($dolibarr_version35) {
		print_liste_field_titre(
			$langs->trans('DesiredStock'),
			'ordercustomer.php',
			'p.desiredstock',
			$param,
			'id=' . $_REQUEST['id'],
			'align="right"',
			$sortfield,
			$sortorder
		);
	}

	/* On n'affiche "Stock Physique" que lorsque c'est effectivement le cas :
	 * - Si on est dans le cas d'un OF avec les produits nécessaires
	 * - Si on utilise les stocks virtuels (soit avec la conf globale Dolibarr, soit celle du module) ou qu'on utilise une plage de temps pour le besoin ou qu'on ne prend pas en compte les commandes clients
	 */
	if (empty($justOFforNeededProduct) && ($week_to_replenish > 0 || !empty($conf->global->USE_VIRTUAL_STOCK) || !empty($conf->global->ASS_USE_VIRTUAL_ORDER_STOCK) || empty($conf->global->ASS_DO_NOT_USE_CUSTOMER_ORDER))) {
		$stocklabel = $langs->trans('VirtualStock');
	} else {
		$stocklabel = $langs->trans('PhysicalStock');
	}
	print_liste_field_titre(
		$stocklabel,
		'ordercustomer.php',
		'stock_physique',
		$param,
		'id=' . $_REQUEST['id'],
		'align="right"',
		$sortfield,
		$sortorder
	);

	if ($conf->of->enabled && !empty($conf->global->OF_USE_DESTOCKAGE_PARTIEL)) {
		dol_include_once('/of/lib/of.lib.php');
		print_liste_field_titre(
			'Stock théo - OF',
			'ordercustomer.php',
			'stock_theo_of',
			$param,
			'id=' . $_REQUEST['id'],
			'align="right"',
			$sortfield,
			$sortorder
		);
	}

	print_liste_field_titre(
		$langs->trans('Ordered'),
		'ordercustomer.php',
		'',
		$param,
		'id=' . $_REQUEST['id'],
		'align="right"',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans('StockToBuy'),
		'ordercustomer.php',
		'',
		$param,
		'id=' . $_REQUEST['id'],
		'align="right"',
		$sortfield,
		$sortorder
	);

	//print '<td class="liste_titre" >fghf</td>';

	if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
		print_liste_field_titre($langs->trans("Availability"));

	print_liste_field_titre(
		$langs->trans('Associate'),
		'ordercustomer.php',
		'',
		$param,
		'id=' . $_REQUEST['id'],
		'align="right"',
		$sortfield,
		$sortorder
	);

	print '<th class="liste_titre" >&nbsp;</th>';

	print '</tr>' .
		// Lignes des champs de filtre
		'<tr class="liste_titre_filter">' .
		'<td class="liste_titre">&nbsp;</td>' .
		'<td class="liste_titre">' .
		'<input class="flat" type="text" name="sref" value="' . $sref . '">' .
		'</td>' .
		'<td class="liste_titre">' .
		'<input class="flat" type="text" name="snom" value="' . $snom . '">' .
		'</td>';

	if (!empty($conf->service->enabled) && $type == 1) {
		print '<td class="liste_titre">' .
			'&nbsp;' .
			'</td>';
	}

	$liste_titre = "";
	$liste_titre .= '<td class="liste_titre">' . $form->selectarray('finished', $statutarray, (!isset($_REQUEST['button_search_x']) && $conf->global->ASS_DEFAUT_FILTER != -1) ? $conf->global->ASS_DEFAUT_FILTER : GETPOST('finished', 'none'), 1) . '</td>';

	if (!empty($conf->categorie->enabled) && !empty($conf->global->ASS_DISPLAY_CAT_COLUMN)) {
		$liste_titre .= '<td class="liste_titre">';
		$liste_titre .= '</td>';
	}

	$liste_titre .= $dolibarr_version35 ? '<td class="liste_titre">&nbsp;</td>' : '';
	//$liste_titre .= '<td class="liste_titre" align="right">' . $langs->trans('AlertOnly') . '&nbsp;<input type="checkbox" name="salert" ' . $alertchecked . '></td>';

	if ($conf->of->enabled && !empty($conf->global->OF_USE_DESTOCKAGE_PARTIEL)) {
		$liste_titre .= '<td class="liste_titre" align="right"></td>';
	}

	$liste_titre .= '<td class="liste_titre" align="right">&nbsp;</td>' .
		'<td class="liste_titre">&nbsp;</td>' .
		'<td class="liste_titre">&nbsp;</td>' .
		'<td class="liste_titre" ' . ($conf->global->ASS_USE_DELIVERY_TIME ? 'colspan="2"' : '') . '>&nbsp;</td>' .
		/*'<td class="liste_titre" align="right">' .
		'<input type="image" class="liste_titre" name="button_search"' .
		'src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" alt="' . $langs->trans("Search") . '">' .
		'<input type="image" class="liste_titre" name="button_removefilter"
          src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">' .
		'</td>' .*/
		'<td class="liste_titre">&nbsp;</td>' .
		'</tr>';

	print $liste_titre;

	$prod = new Product($db);

	$var = True;

	if ($conf->global->ASS_USE_DELIVERY_TIME) {
		$form->load_cache_availability();
		$limit = 999999;
	}

	$TSupplier = array();
	foreach($TProducts as $objp){

		if ($conf->global->ASS_DISPLAY_SERVICES || $objp->fk_product_type == 0) {

			// Multilangs
			if (!empty($conf->global->MAIN_MULTILANGS)) {
				$sql = 'SELECT label';
				$sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_lang';
				$sql .= ' WHERE fk_product = ' . $objp->rowid;
				$sql .= ' AND lang = "' . $langs->getDefaultLang() . '"';
				$sql .= ' LIMIT 1';

				$result = $db->query($sql);
				if ($result) {
					$objtp = $db->fetch_object($result);
					if (!empty($objtp->label)) {
						$objp->label = $objtp->label;
					}
				}
			}


			$prod->ref = $objp->ref;
			$prod->id = $objp->rowid;
			$prod->type = $objp->fk_product_type;
			//$ordered = ordered($prod->id);

			$help_stock = $langs->trans('PhysicalStock') . ' : ' . (float)$objp->stock_physique;

			$stock_commande_client = 0;
			$stock_commande_associe = 0;

			if (!$justOFforNeededProduct) {

				if ($week_to_replenish > 0) {
					/* là ça déconne pas, on s'en fout, on dépote ! */
					if (empty($conf->global->ASS_DO_NOT_USE_CUSTOMER_ORDER)) {
						$stock_commande_client = _load_stats_commande_date($prod->id, date('Y-m-d', strtotime('+' . $week_to_replenish . 'week')));
						$help_stock .= ', ' . $langs->trans('Orders') . ' : ' . (float)$stock_commande_client;
					}

					$stock_commande_associe = _load_stats_commande_associe($prod->id, date('Y-m-d', strtotime('+' . $week_to_replenish . 'week')), $objp->stock_physique - $stock_commande_client);
					$help_stock .= ', ' . $langs->trans('SupplierOrders') . ' : ' . (float)$stock_commande_associe;


					$stock = $objp->stock_physique - $stock_commande_client + $stock_commande_associe;
				} else if ($conf->global->USE_VIRTUAL_STOCK || $conf->global->ASS_USE_VIRTUAL_ORDER_STOCK) {
					//compute virtual stockshow_stock_no_need
					$prod->fetch($prod->id);
					if ((!$conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER || $conf->global->ASS_USE_VIRTUAL_ORDER_STOCK)
						&& empty($conf->global->ASS_DO_NOT_USE_CUSTOMER_ORDER)) {
						$result = $prod->load_stats_commande(0, '1,2');
						if ($result < 0) {
							dol_print_error($db, $prod->error);
						}
						$stock_commande_client = $prod->stats_commande['qty'];
						//si c'est un sous-produit, on ajoute la quantité à commander calculée plus tôt en plus
						if(!empty($objp->level)) $stock_commande_client = $stock_commande_client + $objp->qty;
					} else {
						$stock_commande_client = 0;
					}

					if (!$conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER || $conf->global->ASS_USE_VIRTUAL_ORDER_STOCK) {
						if (!empty($conf->global->SUPPLIER_ORDER_STATUS_FOR_VIRTUAL_STOCK)){
							$result=$prod->load_stats_commande_fournisseur(0, $conf->global->SUPPLIER_ORDER_STATUS_FOR_VIRTUAL_STOCK, 1);
						} else {
							$result=$prod->load_stats_commande_fournisseur(0, '1,2,3,4', 1);
						}
						if ($result < 0) {
							dol_print_error($db, $prod->error);
						}

						//Requête qui récupère la somme des qty ventilés pour les cmd reçu partiellement
						$sqlQ = "SELECT SUM(cfd.qty) as qty";
						$sqlQ .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd";
						$sqlQ .= " INNER JOIN " . MAIN_DB_PREFIX . "commande_fournisseur cf ON (cf.rowid = cfd.fk_commande) AND cf.entity IN (".getEntity('commande_fornisseur').")";
						$sqlQ .= " LEFT JOIN " . MAIN_DB_PREFIX . 'entrepot as e ON cfd.fk_entrepot = e.rowid AND e.entity IN (' . $entityToTest . ')';
						$sqlQ .= " WHERE cf.fk_statut = 4";
						$sqlQ .= " AND cfd.fk_product = " . $prod->id;
						$sqlQ .= " ORDER BY cfd.rowid ASC";
						$resqlQ = $db->query($sqlQ);

						$stock_commande_associe = $prod->stats_commande_associe['qty'];
						if ($row = $db->fetch_object($resqlQ))
							$stock_commande_associe -= $row->qty;

					} else {
						$stock_commande_associe = 0;

					}

					if (! empty($conf->expedition->enabled)
						&& (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)))
					{
						require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
						$filterShipmentStatus = '';
						if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)) {
							$filterShipmentStatus = Expedition::STATUS_VALIDATED  . ',' . Expedition::STATUS_CLOSED;
						} elseif (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
							$filterShipmentStatus = Expedition::STATUS_CLOSED;
						}
						$result = $prod->load_stats_sending(0, '1,2', 1, $filterShipmentStatus);
						if ($result < 0) dol_print_error($this->db, $this->error);
						$stock_sending_client=$prod->stats_expedition['qty'];
						$help_stock .= ', '.$langs->trans('Expeditions').' : '.(float) $stock_sending_client;
					} else $stock_sending_client = 0;

					if ($stock_commande_client > 0) {
						$help_stock .= ', ' . $langs->trans('Orders') . ' : ' . (float)$stock_commande_client;
					}

					$help_stock .= ', ' . $langs->trans('SupplierOrders') . ' : ' . (float)$stock_commande_associe;

					$stock = $objp->stock_physique - $stock_commande_client + $stock_commande_associe + $stock_sending_client;
				} else {

					if (empty($conf->global->ASS_DO_NOT_USE_CUSTOMER_ORDER)) {
						$stock_commande_client = $objp->qty;
						$help_stock .= ', ' . $langs->trans('Orders') . ' : ' . (float)$stock_commande_client;
					}

					$stock = $objp->stock_physique - $stock_commande_client;


				}
			} else {
				$stock = $objp->stock_physique;
				$help_stock .= '(Juste OF) ';
			}

			$ordered = $stock_commande_client;

			// La quantité à commander correspond au stock désiré sur le produit additionné à la quantité souhaitée dans la commande :


			$stocktobuy = $objp->desiredstock - $stock;



			/*			if($stocktobuy<=0 && $prod->ref!='A0000753') {
							$i++;
							continue; // le stock est suffisant on passe
							}*/

			if ($conf->of->enabled) {

				/* Si j'ai des OF je veux savoir combien cela me coûte */

				define('INC_FROM_DOLIBARR', true);
				dol_include_once('/of/config.php');
				dol_include_once('/of/class/ordre_fabrication_asset.class.php');

				//$_REQUEST['DEBUG']=true;
				if ($week_to_replenish > 0) {
					$stock_of_needed = TAssetOF::getProductNeededQty($prod->id, false, true, date('Y-m-d', strtotime('+' . $week_to_replenish . 'week')));
					$stock_of_tomake = TAssetOF::getProductNeededQty($prod->id, false, true, date('Y-m-d', strtotime('+' . $week_to_replenish . 'week')), 'TO_MAKE');

				} else {
					$stock_of_needed = TAssetOF::getProductNeededQty($prod->id, false, true, '');
					$stock_of_tomake = TAssetOF::getProductNeededQty($prod->id, false, true, '', 'TO_MAKE');

				}

				$stocktobuy += $stock_of_needed - $stock_of_tomake;

				if (!$conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER || $conf->global->ASS_USE_VIRTUAL_ORDER_STOCK) {
					$stock -= $stock_of_needed - $stock_of_tomake;
				}

				$help_stock .= ', ' . $langs->trans('OF') . ' : ' . (float)($stock_of_needed - $stock_of_tomake);
			}

			$help_stock .= ', ' . $langs->trans('DesiredStock') . ' : ' . (float)$objp->desiredstock;

			if ($stocktobuy < 0) {
				$stocktobuy = 0;
				$objnottobuy = $objp->rowid;
			}

			//si le produit parent n'a pas besoin d'être commandé, alors les produits fils non plus
			if($objnottobuy == $objp->fk_parent && !empty($objnottobuy) && !empty($objp->fk_parent)) {
				$stocktobuy = 0;
			}

			/*if ((empty($prod->type) && $stocktobuy == 0 && GETPOST('show_stock_no_need', 'none') != 'yes') || ($prod->type == 1 && $stocktobuy == 0 && GETPOST('show_stock_no_need', 'none') != 'yes' && !empty($conf->global->STOCK_SUPPORTS_SERVICES))) {
				$i++;
				continue;
			}*/

			$var = !$var;
			print '<tr ' . $bc[$var] . ' data-productid="' . $objp->rowid . '"  data-i="' . $i . '"   >
						<td>
							<input type="checkbox" class="check" name="check' . $i . '"' . $disabled . ' checked>';

			$lineid = '';

			if (strpos($objp->lineid, '@') === false) { // Une seule ligne d'origine
				$lineid = $objp->lineid;
			}

			print '<input type="hidden" name="lineid' . $i . '" value="' . $lineid . '" />';
			print '</td>';

			print '<td  style="height:35px;" class="nowrap">';
			//affichage des indentations suivant le niveau de sous-produit
			if (!empty($objp->level)) {
				$k = 0;
				while ($k < $objp->level) {
					print img_picto("Auto fill", 'rightarrow');
					$k++;
				}
			}
			if (!empty($TDemandes)) {
				print $form->textwithpicto($prod->getNomUrl(1), 'Demande(s) de prix en cours :<br />' . implode(', ', $TDemandes), 1, 'help');
			} else {
				print $prod->getNomUrl(1);
			}

			print '</td>';
			print '<td>' . $objp->label . '</td>';

			print '<td>' . (empty($prod->type) ? $statutarray[$objp->finished] : '') . '</td>';


			if (!empty($conf->categorie->enabled) && !empty($conf->global->ASS_DISPLAY_CAT_COLUMN)) {
				print '<td >';
				$categorie = new Categorie($db);
				$Tcategories = $categorie->containing($objp->rowid, 'product', 'label');
				print implode(', ', $Tcategories);
				print '</td>';
			}

			if (!empty($conf->service->enabled) && $type == 1) {
				if (preg_match('/([0-9]+)y/i', $objp->duration, $regs)) {
					$duration = $regs[1] . ' ' . $langs->trans('DurationYear');
				} elseif (preg_match('/([0-9]+)m/i', $objp->duration, $regs)) {
					$duration = $regs[1] . ' ' . $langs->trans('DurationMonth');
				} elseif (preg_match('/([0-9]+)d/i', $objp->duration, $regs)) {
					$duration = $regs[1] . ' ' . $langs->trans('DurationDay');
				} else {
					$duration = $objp->duration;
				}

				print '<td align="center">' .
					$duration .
					'</td>';
			}


			//print $dolibarr_version35 ? '<td align="right">' . $objp->desiredstock . '</td>' : "".

			$champs = "";
			$champs .= $dolibarr_version35 ? '<td align="right">' . $objp->desiredstock . '</td>' : '';
			$champs .= '<td align="right">' .
				$warning . ((($conf->global->STOCK_SUPPORTS_SERVICES && $prod->type == 1) || empty($prod->type)) ? $stock : img_picto('', './img/no', '', 1)) . //$stocktobuy
				'</td>';
			if ($conf->of->enabled && !empty($conf->global->OF_USE_DESTOCKAGE_PARTIEL)) {
				/*					dol_include_once('/of/lib/of.lib.php');
									$prod->load_stock();
									list($qty_to_make, $qty_needed) = _calcQtyOfProductInOf($db, $conf, $prod);
									$qty = $prod->stock_theorique + $qty_to_make - $qty_needed;
				*/
				$prod->load_stock();
				$qty_of = $stock_of_needed - $stock_of_tomake;
				$qty = $prod->stock_theorique - $qty_of;
				$champs .= '<td align="right">' . $qty . '</td>';
			}

			$champs .= '<td align="right">' .

				$ordered . $picto .
				'</td>' .
				'<td align="right">' .
				'<input type="text" name="tobuy' . $i .
				'" value="' . $ordered . '" ' . $disabled . ' size="4">';


			$selectedPrice = $objp->buy_price_ht > 0 ? $objp->buy_price_ht : 0;
			$associesForm = CMDGrandCompte::getAssocies('ass'.$i,true,$selected);

			$champs .= '<td align="right" data-info="ass-price" >' .
				//TASS::select_product_fourn_price($prod->id, 'ass' . $i, $selectedSupplier, $selectedPrice) .
				$associesForm .
				'</td>';
			print $champs;

			if (empty($TSupplier)) 
				$TSupplier = $prod->list_suppliers();
			else $TSupplier = array_intersect($prod->list_suppliers(), $TSupplier);

			if ($conf->of->enabled && $user->rights->of->of->write && empty($conf->global->ASS_REMOVE_MAKE_BTN)) {
				print '<td><a href="' . dol_buildpath('/of/fiche_of.php', 1) . '?action=new&fk_product=' . $prod->id . '" class="butAction">Fabriquer</a></td>';
			} else {
				print '<td>&nbsp</td>';
			}
			print '</tr>';

			if (empty($fk_commande))
				$TCachedProductId[] = $prod->id; //mise en cache

		}

		$i++;
		//	if($prod->ref=='A0000753') exit;
	}

	//Lignes libre
	if ($resql2) {
		while ($j < min($num2, $limit)) {
			$objp = $db->fetch_object($resql2);
			if ($objp->product_type == 0)
				$picto = img_object($langs->trans("ShowProduct"), 'product');
			if ($objp->product_type == 1)
				$picto = img_object($langs->trans("ShowService"), 'service');

			print '<tr ' . $bc[$var] . '>' .
				'<td><input type="checkbox" class="check" name="check' . $i . '"' . $disabled . ' checked></td>' .
				'<td>' .
				$picto . " " . $objp->description .
				'</td>' .
				'<td>' . $objp->description;

			$picto = img_picto('', './img/no', '', 1);

			//pre($conf->global,1);
			//if(!empty($conf->global->ASSOCIE_USE_ORDER_DESC)) {
			print '<input type="hidden" name="desc' . $i . '" value="' . $objp->description . '" />';
			print '<input type="hidden" name="product_type' . $i . '" value="' . $objp->product_type . '" >';
			//	}

			print '</td>';

			print '<td></td>'; // Nature
			if (!empty($conf->categorie->enabled))
				print '<td></td>'; // Categories

			if (!empty($conf->service->enabled) && $type == 1) {
				if (preg_match('/([0-9]+)y/i', $objp->duration, $regs)) {
					$duration = $regs[1] . ' ' . $langs->trans('DurationYear');
				} elseif (preg_match('/([0-9]+)m/i', $objp->duration, $regs)) {
					$duration = $regs[1] . ' ' . $langs->trans('DurationMonth');
				} elseif (preg_match('/([0-9]+)d/i', $objp->duration, $regs)) {
					$duration = $regs[1] . ' ' . $langs->trans('DurationDay');
				} else {
					$duration = $objp->duration;
				}
				print '<td align="center">' .
					$duration .
					'</td>';
			}

			if ($dolibarr_version35)
				print '<td align="right">' . $picto . '</td>'; // Desired stock
			print '<td align="right">' . $picto . '</td>'; // Physical/virtual stock
			if ($conf->of->enabled && !empty($conf->global->OF_USE_DESTOCKAGE_PARTIEL))
				print '<td align="right">' . $picto . '</td>'; // Stock théorique OF

			print '<td align="right">
						<input type="text" name="tobuy_free' . $i . '" value="' . $objp->qty . '" size="4">
						<input type="hidden" name="lineid_free' . $i . '" value="' . $objp->rowid . '" >
					</td>'; // Ordered

			$associesForm = CMDGrandCompte::getAssocies('ass'.$i,true,$selected);
			$champs2 = '<td align="right" data-info="ass-price" >' .
				'<input type="text" name="price_free' . $i . '" value="' . (empty($conf->global->ASS_COST_PRICE_AS_BUYING) ? $objp->price : price($objp->buy_price_ht)) . '" size="5" style="text-align:right" hidden>' .
				//TASS::select_product_fourn_price($prod->id, 'ass' . $i, $selectedSupplier, $selectedPrice) .
				$associesForm .
				'</td>';
			print $champs2;
			// print '<td align="right">
			// 			<input type="text" name="price_free' . $i . '" value="' . (empty($conf->global->ASS_COST_PRICE_AS_BUYING) ? $objp->price : price($objp->buy_price_ht)) . '" size="5" style="text-align:right">€
			// 			' . $form->select_company((empty($socid) ? '' : $socid), 'ass_free' . $i, 's.fournisseur = 1', 1, 0, 0, array(), 0, 'minwidth100 maxwidth300') . '
			// 	   </td>'; // Supplier
			print '<td></td>'; // Action
			print '</tr>';
			$i++;
			$j++;
		}
	}

	// Formatage du tableau
	// $TCommonSupplier = array();
	// foreach ($TSupplier as $fk_fourn) {
	// 	if (!isset($TCommonSupplier[0]))
	// 		$TCommonSupplier[0] = '';
	// 	$fourn = new Fournisseur($db);
	// 	$fourn->fetch($fk_fourn);

	// 	$TCommonSupplier[$fk_fourn] = $fourn->name;
	// }

	print '</table>' .
		'<table width="100%" style="margin-top:15px;">';
	print '<tr>';
	print '<td align="right">';
	
	$associesForm = CMDGrandCompte::getAssocies('getallass',true,$selected);
	$allass .= '<td align="right">'. $langs->trans("AssToAll") .
				$associesForm . 
				'<button class="butAction" type="submit" name="action" value="apply-all">' . $langs->trans("ApplyAll") . '</button>' .
				'</td>';
	print $allass;

	print '</td>';
	print '</tr>';
	print '<tr><td>&nbsp;</td></tr>';

	$commEurochef = CMDGrandCompte::getCommEurochef('commEC',true);
	if(strpos($commEurochef, '<option value="-1">') != null){
		$commECs = '<tr><td align="left"><label><input type="checkbox" name="commEuroch" disabled/>' . $langs->trans("NoServiceComm") . ' : </label>' .
		'</td></tr>';
	}else{
		$commECs = '<tr><td align="left"><label><input type="checkbox" name="commEuroch" checked />' . $langs->trans("CommEurochef") . ' : </label>' .
					$commEurochef .
					'</td></tr>';
	}
	print $commECs;

	print '<tr><td align="left">' .
		  '<label><input type="checkbox" name="ugapeda" />' . $langs->trans("UGAPEDA") . '</label>' .
		  '</td></tr>';

	$bfaTiers = CMDGrandCompte::getBfaTier('bfaTier',true);
	if(strpos($bfaTiers, '<option value="-1">') != null){
		$bfaClient .= 	'<tr><td align="left"><label><input type="checkbox" name="bfaClient" disabled/>' . $langs->trans("bfaClient") . ' : </label>' .
		$bfaTiers .
		'</td>';
	}else{
		$bfaClient .= 	'<tr><td align="left"><label><input type="checkbox" name="bfaClient" />' . $langs->trans("bfaClient") . ' : </label>' .
					$bfaTiers .
					'</td>';
	}
	print $bfaClient;

	print '<td align="right">' .
		'<button class="butAction" type="submit" name="action" value="valid-order">' . $langs->trans("GenerateSupplierOrder") . '</button>' .
		'</td></tr></table>' .
		'</form>';


	if($resql){
		$db->free($resql);
	}
	print ' <script type="text/javascript">';


	if ($conf->global->ASS_USE_DELIVERY_TIME) {

		print '
	$( document ).ready(function() {
		//console.log( "ready!" );

		$("[data-info=\'ass-price\'] select").on("change", function() {
		    var productid = $(this).closest( "tr[data-productid]" ).attr( "data-productid" );
		    var rowi = $(this).closest( "tr[data-productid]" ).attr( "data-i" );
			if ( productid.length ) {
				var fk_price = $(this).val();
				var stocktobuy = $("[name=\'tobuy" + rowi +"\']" ).val();

				var targetUrl = "' . dol_buildpath('/associe/script/interface.php', 2) . '?get=availability&stocktobuy=" + stocktobuy + "&fk_product=" + productid + "&fk_price=" + fk_price ;

				$.get( targetUrl, function( data ) {
				  	$("tr[data-productid=\'" + productid + "\'] [data-info=\'availability\']").html( data );
				});


			}
		});

	});
	';
	}


	print ' function toggle(source)
     {
       var checkboxes = document.getElementsByClassName("check");
       for (var i=0; i < checkboxes.length;i++) {
         if (!checkboxes[i].disabled) {
            checkboxes[i].checked = source.checked;
        }
       }
     } </script>';


	dol_fiche_end();
} else {
	dol_print_error($db);
}

llxFooter();

function _prepareLine($i, $actionTarget = 'order')
{
	global $db, $suppliers, $box, $conf;

	if ($actionTarget == 'propal') {
		$line = new SupplierProposalLine($db);
	} else {
		$line = new CommandeFournisseurLigne($db); //$actionTarget = 'order'
	}

	//Lignes de produit
	if (!GETPOST('tobuy_free' . $i, 'none')) {
		// $supplierpriceid = GETPOST('ass'.$i, 'int');
		$supplierpriceid = $_POST['ass'.$i];
	
		//get all the parameters needed to create a line
		$qty = GETPOST('tobuy' . $i, 'int');
		$desc = GETPOST('desc' . $i, 'alpha');
		$lineid = GETPOST('lineid' . $i, 'int');
		
		$array_options = array();

		if (!empty($lineid)) {
			$commandeline = new OrderLine($db);
			$commandeline->fetch($lineid);
			if (empty($commandeline->id) && !empty($commandeline->rowid)) {
				$commandeline->id = $commandeline->rowid; // Pas positionné par OrderLine::fetch() donc le fetch_optionals() foire...
			}

			if (empty($commandeline->array_options) && method_exists($commandeline, 'fetch_optionals')) {
				$commandeline->fetch_optionals();
			}

			$array_options = $commandeline->array_options;

			$line->origin = 'commande';
			$line->origin_id = $commandeline->id;
		}

		$obj = $commandeline;

		if ($obj) {

			$line->qty = $qty;
			$line->desc = $desc;
			$line->fk_product = $obj->fk_product;
			$line->tva_tx = $obj->tva_tx;
			$line->subprice = $obj->price;
			$line->total_ht = $obj->price * $qty;
			$tva = $line->tva_tx / 100;
			$line->total_tva = $line->total_ht * $tva;
			$line->total_ttc = $line->total_ht + $line->total_tva;
			$line->ref_fourn = $obj->ref_fourn;

			// FIXME: Ugly hack to get the right purchase price since supplier references can collide
			// (eg. same supplier ref for multiple suppliers with different prices).
			$line->fk_prod_fourn_price = $supplierpriceid;
			$line->array_options = $array_options;
			
			// $suppliers[$obj->fk_soc]['lines'][] = $line;

			$assTier = CMDGrandCompte::getAssociesTier($supplierpriceid);
			$suppliers[$assTier]['lines'][] = $line;
			

		} else {
			$error = $db->lasterror();
			dol_print_error($db);
			dol_syslog('replenish.php: ' . $error, LOG_ERR);
		}
		if($resql){
			$db->free($resql);
		}
		unset($_POST['ass' . $i]);
	} //Lignes libres
	else {
		$supplierpriceid = $_POST['ass'.$i];

		// $box = $i;
		$qty = GETPOST('tobuy_free' . $i, 'int');
		$desc = GETPOST('desc' . $i, 'alpha');
		$product_type = GETPOST('product_type' . $i, 'int');
		$price = price2num(GETPOST('price_free' . $i, 'none'));
		$lineid = GETPOST('lineid_free' . $i, 'int');
		$assid = GETPOST('ass_free' . $i, 'int');
		$commandeline = new OrderLine($db);
		$commandeline->fetch($lineid);
		
		if (empty($commandeline->id) && !empty($commandeline->rowid)) {
			$commandeline->id = $commandeline->rowid; // Pas positionné par OrderLine::fetch() donc le fetch_optionals() foire...
		}

		if (empty($commandeline->array_options) && method_exists($commandeline, 'fetch_optionals')) {
			$array_options = $commandeline->fetch_optionals();
		}

		$obj = $commandeline;

		if ($obj) {

			$line->qty = $qty;
			$line->desc = $desc;
			$line->fk_product = $obj->fk_product;
			$line->tva_tx = $obj->tva_tx;
			$line->subprice = $obj->price;
			$line->total_ht = $obj->price * $qty;
			$tva = $line->tva_tx / 100;
			$line->total_tva = $line->total_ht * $tva;
			$line->total_ttc = $line->total_ht + $line->total_tva;
			$line->ref_fourn = $obj->ref_fourn;

			// FIXME: Ugly hack to get the right purchase price since supplier references can collide
			// (eg. same supplier ref for multiple suppliers with different prices).
			$line->fk_prod_fourn_price = $supplierpriceid;
			$line->array_options = $array_options;
			
			// $suppliers[$obj->fk_soc]['lines'][] = $line;

			$assTier = CMDGrandCompte::getAssociesTier($supplierpriceid);
			$suppliers[$assTier]['lines'][] = $line;
		}
	}

}


function _getSupplierPriceInfos($supplierpriceid)
{
	global $db;
	$sql = 'SELECT fk_product, fk_soc, ref_fourn';
	$sql .= ', tva_tx, unitprice, remise_percent FROM ';
	$sql .= MAIN_DB_PREFIX . 'product_fournisseur_price';
	$sql .= ' WHERE rowid = ' . $supplierpriceid;

	$resql = $db->query($sql);

	if ($resql && $db->num_rows($resql) > 0) {
		//might need some value checks
		return $db->fetch_object($resql);
	}

	return false;
}


function _getSupplierOrderInfos($idsupplier, $projectid = '')
{
	global $db, $conf;

	$sql = 'SELECT rowid, ref';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur';
	$sql .= ' WHERE fk_soc = ' . $idsupplier;
	$sql .= ' AND fk_statut = 0'; // 0 = DRAFT (Brouillon)

	if (!empty($conf->global->ASS_DISTINCT_ORDER_BY_PROJECT) && !empty($projectid)) {
		$sql .= ' AND fk_projet = ' . $projectid;
	}

	$sql .= ' AND entity IN(' . getEntity('commande_fournisseur') . ')';
	$sql .= ' ORDER BY rowid DESC';
	$sql .= ' LIMIT 1';

	$resql = $db->query($sql);

	if ($resql && $db->num_rows($resql) > 0) {
		//might need some value checks
		return $db->fetch_object($resql);
	}

	return false;
}


function _getSupplierProposalInfos($idsupplier, $projectid = '')
{
	global $db, $conf;

	$sql = 'SELECT rowid, ref';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'supplier_proposal';
	$sql .= ' WHERE fk_soc = ' . $idsupplier;
	$sql .= ' AND fk_statut = 0'; // 0 = DRAFT (Brouillon)

	if (!empty($conf->global->ASS_DISTINCT_ORDER_BY_PROJECT) && !empty($projectid)) {
		$sql .= ' AND fk_projet = ' . $projectid;
	}

	$sql .= ' AND entity IN(' . getEntity('supplier_proposal') . ')';
	$sql .= ' ORDER BY rowid DESC';
	$sql .= ' LIMIT 1';

	$resql = $db->query($sql);

	if ($resql && $db->num_rows($resql) > 0) {
		//might need some value checks
		return $db->fetch_object($resql);
	}

	return false;
}

function _appliCond($order, $commandeClient)
{
	global $db, $conf;

	if (!empty($conf->global->ASS_GET_INFOS_FROM_FOURN)) {
		$fourn = new Fournisseur($db);
		if ($fourn->fetch($order->socid) > 0) {
			$order->mode_reglement_id = $fourn->mode_reglement_supplier_id;
			$order->mode_reglement_code = getPaiementCode($order->mode_reglement_id);

			$order->cond_reglement_id = $fourn->cond_reglement_supplier_id;
			$order->cond_reglement_code = getPaymentTermCode($order->cond_reglement_id);
		}
	}

	if ($conf->global->ASS_GET_INFOS_FROM_ORDER) {
		$order->mode_reglement_code = $commandeClient->mode_reglement_code;
		$order->mode_reglement_id = $commandeClient->mode_reglement_id;
		$order->cond_reglement_id = $commandeClient->cond_reglement_id;
		$order->cond_reglement_code = $commandeClient->cond_reglement_code;
		$order->date_livraison = $commandeClient->date_livraison;
	}
}


$db->close();

function get_categs_enfants(&$cat)
{

	$TCat = array();

	$filles = $cat->get_filles();
	if (!empty($filles)) {
		foreach ($filles as &$cat_fille) {
			$TCat[] = $cat_fille->id;

			get_categs_enfants($cat_fille);
		}
	}

	return $TCat;
}

function partageCMDGrandCompte($idadh,$object){
	global $db ,$conf,$user;
	$srcfile=$conf->commande->dir_output.'/'.$object->ref.'/'.$object->ref.'.pdf';
	if(file_exists($srcfile)){
		$agence = new Place($db);
		$daoMulticompany = new DaoMulticompany($db);
		$daoMulticompany->fetch($idadh);
		$codeadh = $daoMulticompany->code;
		$agencenotifications=new Agencenotifications($db);
		$agencenotifications->subject='Partage Commande';
		$agencenotifications->text='Eurochef partage la commande numero '.$object->ref.' avec vous';
		$agencenotifications->status=1;
		$agencenotifications->codeadh=$codeadh;
	
		$idAdh = $agence->getIdAdh($codeadh);
		$dstdocpartage = '/'.$idAdh.'/associe/temp';
		$dol_document_root = (object)$conf->file->dol_document_root;
		$server = mb_substr($dol_document_root->main, 0, -7);

		// Pour verifier l'existence du dossier de destianation des commandes sinon le créer
		$fileroot = $server. '/documents'.$dstdocpartage;
		if (!file_exists($fileroot)) {
			mkdir($fileroot, 0777, true);
		}
		
		// Root path for file manager
		$dstfile = $server. '/documents' .$dstdocpartage.'/'.$object->ref.'.pdf';
		copy($srcfile, $dstfile);
		$agencenotifications->create($user);
		setEventMessage("Votre demande a bien été prise en compte", 'mesgs');
	} else {
		setEventMessage("Votre commande n'est pas encore partagée avec l'associé, ", 'warnings');
	}
	
}