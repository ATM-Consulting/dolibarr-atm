<?php

/**
 * Script créé par Got pour traitement du Ticket ATM DA021683
 * Quelques informations :
 *
 * Ils utilisent le module pamplemousse qui permet un restockage sur validation des avoirs, or si on a supprimé auparavant l'expédition, ça fait un double restockage...
 * Malgré ça, les restockages peuvent être manuels, issus d'un avoir avec pamplemousse ou de la suppression d'une expédition, ou encore peuvent provenir d'une réception de commande fournisseur
 *
 * Maintenant ils utilisent les lots donc entrées de stocks uniquement sur réception commandes fournisseurs
 * Sur suppression d'une expédition, un restockage est fait avec un prix à 0, donc pas de recalcul de PMP
 *
 * Mon script recalcule le pmp en fonction des entrées et sorties de stock ayant lieu au fur et à mesure de l'utilisation de dolibarr.
 * Si un mouvement de stock provient d'une réception de commande fournisseur le script va chercher la ligne de commande fournisseur d'origine pour vérifier s'il n'y avait pas une remise, et de ce fait adapte le pmp en conséquence
 *
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("./main.inc.php")) $res=@include("./main.inc.php");
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

// On récupère la liste des produits qui existent encore ayant été entrés en stock depuis l'activation de la configuration STOCK_EXCLUDE_DISCOUNT_FOR_PMP
$sql = "SELECT DISTINCT sm.fk_product
		FROM llx_product p
		INNER JOIN llx_stock_mouvement sm ON (sm.fk_product = p.rowid)
		WHERE sm.tms >= '2022-05-13 08:53:00' AND sm.tms < '2022-06-21 10:50:00'";
//echo $sql;exit;
// requête de test local :
//$sql = "SELECT DISTINCT sm.fk_product
//		FROM llx_product p
//		INNER JOIN llx_stock_mouvement sm ON (sm.fk_product = p.rowid)
//		WHERE sm.fk_product = 23782";
//echo $sql;



$TProducts=array();
$TReceptionLines = array();
$resql = $db->query($sql);
while($res = $db->fetch_object($resql)) $TProducts[] = $res->fk_product;
//var_dump($TProducts);exit;
if(!empty($TProducts)) {

	$i=0;
	foreach ($TProducts as $fk_product) {
		$sql2 = 'SELECT sm.*, p.pmp, p.stock as current_stock, p.ref as ref_prod FROM llx_stock_mouvement sm
				 INNER JOIN llx_product p ON (p.rowid = sm.fk_product)
				 WHERE sm.fk_product = '.((int) $fk_product).'
				 ORDER by sm.rowid ASC';

		$resql2 = $db->query($sql2);
		if(!empty($resql2)) calculPMPWithStockMouvements($resql2, $i);
		$i++;
	}

}
//var_dump($TReceptionLines);
function calculPMPWithStockMouvements(&$resql2, $i) {

	global $db, $TReceptionLines;

	$stock = $fk_product = $pmp = $i = 0;

	while($res2 = $db->fetch_object($resql2)) {
		// Données actuelles du produit :
		$fk_product = $res2->fk_product;
		$current_product_stock = $res2->current_stock;
		$ref_prod = $res2->ref_prod;

		// Calculs au fil des itérations
		$old_pmp_product = $res2->pmp;
		$current_stock = $stock;
		$stock+=$res2->value;

		// C'est une entrée en stock et le prix est supérieur à 0, donc on MAJ le PMP
		if($res2->value > 0 && $res2->price > 0) {

			// Gestion spécifique pour les entrées en stock provenant des commandes réceptions de fournisseurs
			if($res2->origintype === 'order_supplier') {

				// On charge le tableau des lignes de réception des commandes fournisseurs
				if(empty($TReceptionLines[$res2->fk_origin.'_'.$fk_product])) {
					$TReceptionLines[$res2->fk_origin.'_'.$fk_product] = getReceptionLinesArray($res2->fk_origin, $fk_product);
				}

				$line_price = $TReceptionLines[$res2->fk_origin.'_'.$fk_product][$TReceptionLines[$res2->fk_origin.'_'.$fk_product]['current_key']]->subprice;

				// S'il n'y a pas de prix, c'est probablement que la commande fournisseur a été supprimée, dans ce cas on conserve le prix présent dans la ligne de mouvement de stock
				if(empty($line_price)) {
					$line_price = $res2->price;
				}
				else {
					if (!empty($TReceptionLines[$res2->fk_origin.'_'.$fk_product][$TReceptionLines[$res2->fk_origin.'_'.$fk_product]['current_key']]->remise_percent)
						&& (float)$TReceptionLines[$res2->fk_origin.'_'.$fk_product][$TReceptionLines[$res2->fk_origin.'_'.$fk_product]['current_key']]->remise_percent > 0
						&& (float)$TReceptionLines[$res2->fk_origin.'_'.$fk_product][$TReceptionLines[$res2->fk_origin.'_'.$fk_product]['current_key']]->remise_percent < 100) {
//						echo '*'.$fk_product.'-'.$res2->fk_origin.'*';
						$line_price = $line_price * (1 - ((float)$TReceptionLines[$res2->fk_origin.'_'.$fk_product][$TReceptionLines[$res2->fk_origin.'_'.$fk_product]['current_key']]->remise_percent) / 100);
					}
				}

				$pmp = ($pmp * $current_stock + $line_price * $res2->value) / $stock;

				// On incrémente l'itérateur qui concerne une commande et un produit pour matcher le bon mouvement de stock avec
				$TReceptionLines[$res2->fk_origin.'_'.$fk_product]['current_key']++;

			} else {
				$pmp = ($pmp * $current_stock + $res2->price * $res2->value) / $stock;
			}
		}

	}

	if(round($old_pmp_product, 5) != round($pmp, 5)) {
		echo 'Produit ref;' . $ref_prod . ';OLD PMP;' . round($old_pmp_product, 5) . ';NEW PMP;' . round($pmp, 5)/* . ';current_stock;' . $current_product_stock . ';new_stock;' . $stock*/.'<br>';
	}

}

function getReceptionLinesArray($fk_supplier_order, $fk_product) {

	global $db;

	$TReceptionLines=array();

	$sql = 'SELECT * FROM llx_commande_fournisseur_dispatch dispatch
         	INNER JOIN llx_commande_fournisseurdet det ON (det.rowid = dispatch.fk_commandefourndet)
			WHERE dispatch.fk_commande = '.((int) $fk_supplier_order).' AND dispatch.fk_product = '.((int) $fk_product).' ORDER BY dispatch.rowid ASC';
//echo $sql.'<br>';
	$resql = $db->query($sql);
	if(!empty($resql)) {

		while ($res = $db->fetch_object($resql)) {
			$TReceptionLines[] = $res;
		}

	}

	$TReceptionLines['current_key'] = 0;
	return $TReceptionLines;

}
