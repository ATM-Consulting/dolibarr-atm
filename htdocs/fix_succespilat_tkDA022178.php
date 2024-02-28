<?php
require './main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

$db->query('UPDATE llx_societe SET code_client = null, code_fournisseur = null, code_compta = null, code_compta_fournisseur = null');
$sql = 'SELECT rowid FROM llx_societe ORDER BY rowid ASC';
$resql = $db->query($sql);
if(!empty($resql)) {

	$i_client=$i_fourn=1;
	$nb_update=0;
	while($res = $db->fetch_object($resql)) {

		$s = new Societe($db);
		$s->fetch($res->rowid);
		$s->code_client = $s->code_fournisseur = $s->code_compta_client = $s->code_compta_fournisseur = '';
		if(!empty($s->client) || !empty($s->prospect)) {
			$s->code_client = str_pad($i_client, 5, 0, STR_PAD_LEFT);
			$i_client++;
		}

		if(!empty($s->fournisseur)) {
			$s->code_fournisseur = 'S' . str_pad($i_fourn, 4, 0, STR_PAD_LEFT);
			$i_fourn++;
		}

		if($s->update($s->id, $user, 1, 1, 1) > 0) $nb_update++;

	}
	var_dump($nb_update.' tiers MAJ');

}
