<?php
// Inclusion du fichier principal Dolibarr
require_once('config.php');

// Connexion à la base de données
$db = new Db();
if (!$db->connect()) {
    dol_print_error($db);
    exit;
}
?>

<?php
// Exécution de la commande SQL
$sql = "UPDATE llx_societe SET fk_pays = 1;";
$res = $db->query($sql);
if (!$res) {
    dol_print_error($db);
    exit;
}
?>