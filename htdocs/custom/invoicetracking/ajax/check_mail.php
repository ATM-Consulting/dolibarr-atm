<?php

/*
 * Copyright (C) 2015-2017 Inovea Conseil	<info@inovea-conseil.com>
 */

$res = 0;
if (!$res && file_exists("../main.inc.php"))
    $res = @include '../main.inc.php';     // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php"))
    $res = @include '../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include '../../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (!$res)
    die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

global $conf, $langs, $db;

$fact = new Facture($db);
$id = $_GET['id'];
if (empty($id) || $id == '-1')
    return 0;

$fact->fetch($id);
$sendto = "";
$contactr = $fact->liste_contact(-1, 'external', 0, 'REMINDER');
if (!empty($contactr)) {
    $sendto = $contactr[0]['email'];
}

if (empty($sendto)) {
    $contactf = $fact->liste_contact(-1, 'external', 0, 'BILLING');
    if (!empty($contacf)) {
        $sendto = $contactf[0]['email'];
    }
}

$soc = new Societe($db);
$soc->fetch($fact->socid);
if (empty($soc->email) && empty($sendto)) {
    $return = img_picto($langs->trans("NoEMail"), 'warning.png');
} else {
    $return = '<input type="checkbox" name="toSendemail" value="1" ></input>';
}

echo $return;
