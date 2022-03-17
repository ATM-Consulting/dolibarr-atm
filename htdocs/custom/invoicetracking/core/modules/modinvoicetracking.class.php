<?php

/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2014-2018 INOVEA CONSEIL <info@inovea-conseil.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *    \defgroup    mymodule    MyModule module
 *    \brief        MyModule module descriptor.
 *    \file        core/modules/modMyModule.class.php
 *    \ingroup    mymodule
 *    \brief        Description and activation file for module MyModule
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";
include_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

/**
 * Description and activation class for module MyModule
 */
class modInvoiceTracking extends DolibarrModules
{

    /**
     *    Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf, $user;

        $this->db = $db;

        // Id for module (must be unique).
        // Use a free id here
        // (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 432414;
        $this->rights_class = 'invoicetracking';
        $this->family = "Inovea Conseil";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Module432414Desc";
        $this->descriptionlong = "";
        $this->editor_name = 'Inovea Conseil';
        $this->version = '3.10.13';
        $this->url_last_version = "https://www.dolibiz.com/wp-content/uploads/lastversion/last_version-invoicetracking.txt";
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 0;
        $this->picto = 'inoveaconseil@invoicetracking';
        $this->module_parts = array('models' => 1);
        $this->dirs = array();
        $this->config_page_url = array("admin.php@invoicetracking");
        $this->depends = array('modCron');
        $this->requiredby = array();
        $this->phpmin = array(5, 3);
        $this->need_dolibarr_version = array(3, 5);
        $this->langfiles = array("invoicetracking@invoicetracking"); // langfiles@mymodule
        $this->const = array(
            0 => array(

                'REMINDER1_fr_FR',
                'chaine',
                '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Objet : Retard de paiement &ndash; Relance &nbsp;<br />
<br />
Madame, Monsieur, &nbsp;<br />
<br />
Sauf erreur ou omission de notre part, le paiement de la facture n&deg; __REF_FACTURE__&nbsp;dat&eacute;e du&nbsp;__DATE_FACTURE__&nbsp;pour un montant de &nbsp;__TOTAL_TTC__ euros TTC et restant &agrave; percevoir __DETTE__ euros ne nous est pas parvenu.&nbsp;Nous vous adressons, &agrave; toutes fins utiles, un duplicata de cette facture en pi&egrave;ce jointe.&nbsp;<br />
<br />
L&rsquo;&eacute;ch&eacute;ance &eacute;tant d&eacute;pass&eacute;e depuis le __DATE_ECHEANCE__, nous vous demandons de bien vouloir proc&eacute;der &agrave; son r&egrave;glement dans les meilleurs d&eacute;lais par retour de courrier.<br />
Dans le cas o&ugrave; votre r&egrave;glement aurait &eacute;t&eacute; adress&eacute; entre temps, nous vous prions de ne pas tenir compte de la pr&eacute;sente.<br />
<br />
Vous remerciant par avance, nous vous prions d&#39;agr&eacute;er, Madame, Monsieur, l&rsquo;expression de nos salutations distingu&eacute;es.<br />
<br />
Le service Comptabilit&eacute;',
                '',
                0
            ),
            1 => array(
                'REMINDER2_fr_FR',
                'chaine',
                '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Objet : Retard de paiement &ndash; Deuxi&egrave;me relance<br />
<br />
Madame, Monsieur,<br />
<br />
Sauf erreur ou omission de notre part, nous sommes toujours dans l&rsquo;attente du paiement de la facture n&deg; __REF_FACTURE__ dat&eacute;e du __DATE_FACTURE__ pour un montant de __TOTAL_TTC__ euros TTC et restant &agrave; percevoir __DETTE__ euros , malgr&eacute; notre pr&eacute;c&eacute;dent rappel.<br />
Nous vous adressons, &agrave; toutes fins utiles, un duplicata de cette facture en pi&egrave;ce jointe.&nbsp;<br />
<br />
L&rsquo;&eacute;ch&eacute;ance &eacute;tant d&eacute;pass&eacute;e, nous vous demandons de bien vouloir proc&eacute;der d&egrave;s &agrave; pr&eacute;sent &agrave; son r&egrave;glement dans les meilleurs d&eacute;lais. Dans le cas o&ugrave; votre r&egrave;glement aurait &eacute;t&eacute; adress&eacute; entre temps, nous vous prions de ne pas tenir compte de la pr&eacute;sente.<br />
<br />
Vous remerciant par avance, nous vous prions d&#39;agr&eacute;er, Madame, Monsieur, l&rsquo;expression de nos salutations distingu&eacute;es. &nbsp;<br />
<br />
Le Service comptabilit&eacute;',
                'This is another constant to add',
                0
            ),
            2 => array(
                'REMINDER3_fr_FR',
                'chaine',
                '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Objet : Retard de paiement &ndash; Mise en demeure de r&eacute;gler<br />
<br />
LETTRE RECOMMANDEE AVEC AR<br />
<br />
Madame, Monsieur,<br />
<br />
Malgr&eacute; les lettres de rappel, vous ne nous avez toujours pas r&eacute;gl&eacute; la somme de __DETTE__ euros que vous restez nous devoir, correspondant &agrave; la facture __REF_FACTURE__ du __DATE_FACTURE__.&nbsp;Nous vous adressons, &agrave; toutes fins utiles, un duplicata de cette facture en pi&egrave;ce jointe.&nbsp;<br />
Par la pr&eacute;sente, nous vous mettons en demeure de vous acquitter de cette somme dans les 15 jours suivants la r&eacute;ception de ce pr&eacute;sent courrier. A d&eacute;faut, nous nous verrons dans l&rsquo;obligation de faire proc&eacute;der &agrave; une sommation de payer par huissier.<br />
<br />
Vous remerciant par avance, nous vous prions d&#39;agr&eacute;er, Madame, Monsieur, l&rsquo;expression de nos salutations distingu&eacute;es.<br />
&nbsp;<br />
Le Service Comptabilit&eacute;',
                'This is another constant to add',
                0
            ),
            3 => array(
                'ITSUBJECT1_fr_FR',
                'chaine',
                'Retard de paiement - Relance',
                'This is another constant to add',
                0
            ),
            4 => array(
                'ITSUBJECT2_fr_FR',
                'chaine',
                'Retard de paiement - Deuxieme relance',
                'This is another constant to add',
                0
            ),
            5 => array(
                'ITSUBJECT3_fr_FR',
                'chaine',
                'Retard de paiement - Derniere relance',
                'This is another constant to add',
                0
            ),
            6 => array(
                'ITCONTENT1_fr_FR',
                'chaine',
                'Bonjour __FIRSTNAME__ __NAME__,<br />
<br />
&nbsp;Sauf erreur ou omission de notre part, le paiement de la facture n&deg; __REF_FACTURE__&nbsp;dat&eacute;e du&nbsp;__DATE_FACTURE__&nbsp;pour un montant de &nbsp;__TOTAL_TTC__ euros TTC et restant &agrave; percevoir __DETTE__ euros ne nous est pas parvenu.&nbsp;Nous vous adressons, &agrave; toutes fins utiles, un duplicata de cette facture en pi&egrave;ce jointe.&nbsp;<br />
<br />
L&rsquo;&eacute;ch&eacute;ance &eacute;tant d&eacute;pass&eacute;e depuis le __DATE_ECHEANCE__, nous vous demandons de bien vouloir proc&eacute;der &agrave; son r&egrave;glement dans les meilleurs d&eacute;lais par retour de courrier.<br />
Dans le cas o&ugrave; votre r&egrave;glement aurait &eacute;t&eacute; adress&eacute; entre temps, nous vous prions de ne pas tenir compte de la pr&eacute;sente.<br />
<br />
__USER_SIGNATURE__',
                'This is another constant to add',
                0
            ),
            7 => array(
                'ITCONTENT2_fr_FR',
                'chaine',
                'Bonjour __FIRSTNAME__ __NAME__,<br />
<br />
Sauf erreur ou omission de notre part, nous sommes toujours dans l&rsquo;attente du paiement de la facture n&deg; __REF_FACTURE__ dat&eacute;e du __DATE_FACTURE__ pour un montant de __TOTAL_TTC__ euros TTC et restant &agrave; percevoir __DETTE__ euros , malgr&eacute; notre pr&eacute;c&eacute;dent rappel.<br />
<br />
Nous vous adressons, &agrave; toutes fins utiles, un duplicata de cette facture en pi&egrave;ce jointe.&nbsp;<br />
<br />
L&rsquo;&eacute;ch&eacute;ance &eacute;tant d&eacute;pass&eacute;e, nous vous demandons de bien vouloir proc&eacute;der d&egrave;s &agrave; pr&eacute;sent &agrave; son r&egrave;glement dans les meilleurs d&eacute;lais. Dans le cas o&ugrave; votre r&egrave;glement aurait &eacute;t&eacute; adress&eacute; entre temps, nous vous prions de ne pas tenir compte de la pr&eacute;sente.<br />
<br />
__USER_SIGNATURE__',
                'This is another constant to add',
                0
            ),
            8 => array(
                'ITCONTENT3_fr_FR',
                'chaine',
                'Bonjour __FIRSTNAME__ __NAME__,<br />
<br />
Malgr&eacute; les lettres de rappel, vous ne nous avez toujours pas r&eacute;gl&eacute; la somme de __DETTE__ euros que vous restez nous devoir, correspondant &agrave; la facture __REF_FACTURE__ du __DATE_FACTURE__.&nbsp;Nous vous adressons, &agrave; toutes fins utiles, un duplicata de cette facture en pi&egrave;ce jointe.&nbsp;<br />
Par la pr&eacute;sente, nous vous mettons en demeure de vous acquitter de cette somme dans les 15 jours suivants la r&eacute;ception de ce pr&eacute;sent courriel. A d&eacute;faut, nous nous verrons dans l&rsquo;obligation de faire proc&eacute;der &agrave; une sommation de payer par huissier.<br />
<br />
__USER_SIGNATURE__',
                'This is another constant to add',
                0
            ),
            9 => array(
                'LIMITSELECT',
                'chaine',
                '100',
                'This is another constant to add',
                0
            ),
            10 => array(
                'ITREMINDDATE',
                'chaine',
                '+15 days',
                'This is another constant to add',
                0
            ),
            11 => array(
                'ITSUBJECT0_fr_FR',
                'chaine',
                'Rappel : Votre facture arrive bient&ocirc;t &agrave; &eacute;ch&eacute;ance',
                'This is another constant to add',
                0
            ),
            12 => array(
                'ITCONTENT0_fr_FR',
                'chaine',
                'Bonjour,<br />
<br />
Nous vous rappelons que votre facture&nbsp;n&deg; __REF_FACTURE__ dat&eacute;e du __DATE_FACTURE__ pour un montant de __TOTAL_TTC__ euros TTC (et restant &agrave; percevoir __DETTE__ euros) arrive prochainement &agrave; &eacute;ch&eacute;ance : le&nbsp;__DATE_ECHEANCE__.<br />
<br />
Vous pouvez effectuer votre r&egrave;glement par virement ou par ch&egrave;que. Si vous avez besoin de plus de pr&eacute;cisions, n&#39;h&eacute;sitez pas &agrave; revenir vers nous.<br />
<br />
Bien cordialement,<br />
<br />
Le Service Comptabilit&eacute;',
                'This is another constant to add',
                0
            ),
            13 => array(
                'REMINDER0_fr_FR',
                'chaine',
                '<p>Objet : Votre facture prochainement &agrave; &eacute;ch&eacute;ance</p>

<p>&nbsp;</p>

<p>Madame, Monsieur,</p>

<p>Nous vous rappelons que votre facture&nbsp;n&deg; __REF_FACTURE__ dat&eacute;e du __DATE_FACTURE__ pour un montant de __TOTAL_TTC__ euros TTC (et restant &agrave; percevoir __DETTE__ euros) arrive prochainement &agrave; &eacute;ch&eacute;ance : le&nbsp;__DATE_ECHEANCE__.<br />
Nous vous adressons, &agrave; toutes fins utiles, un duplicata de cette facture en pi&egrave;ce jointe.<br />
<br />
Notre service comptabilit&eacute; se tient &agrave; votre disposition pour toute question.<br />
<br />
Vous remerciant par avance, nous vous prions d&#39;agr&eacute;er, Madame, Monsieur, l&rsquo;expression de nos salutations distingu&eacute;es.</p>

<p>Le service Comptabilit&eacute;</p>
',
                '',
                0
            ),
            14 => array(
                'ITREMINDDATE0',
                'chaine',
                '-5 days',
                'This is another constant to add',
                0
            ),
            15 => array(
                'ITREMINDDATE1',
                'chaine',
                '+15 days',
                'This is another constant to add',
                0
            ),
            16 => array(
                'ITREMINDDATE2',
                'chaine',
                '+30 days',
                'This is another constant to add',
                0
            ),
            17 => array(
                'ITREMINDDATE3',
                'chaine',
                '+45 days',
                'This is another constant to add',
                0
            ),
            18 => array(
                'REMINDER1_de_DE',
                'chaine',
                '<div>Kontoauszug</div>\r\n&nbsp;\r\n\r\n<div>Sehr geehrte Damen und Herren,</div>\r\n\r\n<div><br />\r\nWir haben festgestellt, dass unsere Forderung noch nicht beglichen ist:<br />\r\nLaut unserer Buchhaltung ist die Rechnung&nbsp; __REF_FACTURE__&nbsp;vom __DATE_FACTURE__&nbsp;&uuml;ber&nbsp; __TOTAL_TTC__ &euro; mit einem Restbetrag von __DETTE__ &euro; noch offen.&nbsp;<br />\r\nIn der Anlage finden Sie ein Duplikat der Rechnung.\r\n<div>Bitte benachrichtigen Sie uns im Falle von Buchungsfehlern unsererseits oder R&uuml;ckfragen, ansonsten erwarten wir umgehend Ihre Zahlung.</div>\r\n</div>\r\n&nbsp;<br />\r\n<br />\r\nMit freundlichen Gr&uuml;&szlig;en<br />\r\n<br />\r\nBuchhaltung',
                '',
                0
            ),
            19 => array(
                'REMINDER2_de_DE',
                'chaine',
                '<div>Mahnung<br />\r\n<br />\r\nLaut unserer Buchhaltung ist die Rechnung&nbsp; __REF_FACTURE__&nbsp;vom __DATE_FACTURE__&nbsp;&uuml;ber&nbsp; __TOTAL_TTC__ &euro;&nbsp; mit einem Restbetrag von __DETTE__ &euro; noch offen.&nbsp;<br />\r\nIn der Anlage finden Sie ein Duplikat der Rechnung.\r\n<div><br />\r\nBitte benachrichtigen Sie uns im Falle von Buchungsfehlern unsererseits oder R&uuml;ckfragen, ansonsten erwarten wir umgehend Ihre Zahlung..<br />\r\n<br />\r\nMit freundlichen Gr&uuml;&szlig;en<br />\r\n<br />\r\nBuchhaltung</div>\r\n</div>\r\n',
                '',
                0
            ),
            20 => array(
                'REMINDER3_de_DE',
                'chaine',
                '<div>Letzte Mahnung<br />\r\n<br />\r\nLaut unserer Buchhaltung ist die Rechnung&nbsp; __REF_FACTURE__&nbsp;vom __DATE_FACTURE__&nbsp;&uuml;ber&nbsp; __TOTAL_TTC__ &euro; mit einem Restbetrag von __DETTE__ &euro; noch offen.&nbsp;<br />\r\nIn der Anlage finden Sie ein Duplikat der Rechnung.\r\n<div><br />\r\nBitte benachrichtigen Sie uns im Falle von Buchungsfehlern unsererseits oder R&uuml;ckfragen, ansonsten erwarten wir umgehend Ihre Zahlung..<br />\r\n<br />\r\nMit freundlichen Gr&uuml;&szlig;en<br />\r\n<br />\r\nBuchhaltung</div>\r\n</div>\r\n',
                '',
                0
            ),
            21 => array(
                'ITCONTENT1_de_DE',
                'chaine',
                'Sehr geehrte __FIRSTNAME__ __NAME__,<br />\r\n<br />\r\nLaut unserer Buchhaltung ist die Rechnung&nbsp; __REF_FACTURE__&nbsp;vom __DATE_FACTURE__&nbsp;&uuml;ber&nbsp; __TOTAL_TTC__ &euro; mit einem Restbetrag von __DETTE__ &euro; noch offen.&nbsp;<br />\r\nIn der Anlage finden Sie ein Duplikat der Rechnung.\r\n<div><br />\r\nBitte benachrichtigen Sie uns im Falle von Buchungsfehlern unsererseits oder R&uuml;ckfragen, ansonsten erwarten wir umgehend Ihre Zahlung..<br />\r\n<br />\r\nMit freundlichen Gr&uuml;&szlig;en<br />\r\n<br />\r\nBuchhaltung\r\n</div>\r\n',
                '',
                0
            ),
            22 => array(
                'ITCONTENT2_de_DE',
                'chaine',
                'Sehr geehrte __FIRSTNAME__ __NAME__,<br />\r\n<br />\r\nLaut unserer Buchhaltung ist die Rechnung&nbsp; __REF_FACTURE__&nbsp;vom __DATE_FACTURE__&nbsp;&uuml;ber&nbsp; __TOTAL_TTC__ &euro; mit einem Restbetrag von __DETTE__ &euro; noch offen.&nbsp;<br />\r\nIn der Anlage finden Sie ein Duplikat der Rechnung.\r\n<div><br />\r\nBitte benachrichtigen Sie uns im Falle von Buchungsfehlern unsererseits oder R&uuml;ckfragen, ansonsten erwarten wir umgehend Ihre Zahlung..<br />\r\n<br />\r\nMit freundlichen Gr&uuml;&szlig;en<br />\r\n<br />\r\nBuchhaltung</div>\r\n\r\n<div>\r\n</div>\r\n',
                '',
                0
            ),
            23 => array(
                'ITCONTENT3_de_DE',
                'chaine',
                'Sehr geehrte __FIRSTNAME__ __NAME__,<br />\r\n<br />\r\nLaut unserer Buchhaltung ist die Rechnung&nbsp; __REF_FACTURE__&nbsp;vom __DATE_FACTURE__&nbsp;&uuml;ber&nbsp; __TOTAL_TTC__ &euro; mit einem Restbetrag von __DETTE__ &euro; noch offen.&nbsp;<br />\r\nIn der Anlage finden Sie ein Duplikat der Rechnung.\r\n<div><br />\r\nWir erwarten umgehend Ihre Zahlung..<br />\r\n<br />\r\nMit freundlichen Gr&uuml;&szlig;en<br />\r\n<br />\r\nBuchhaltung</div>\r\n',
                '',
                0
            ),
            24 => array(
                'ITSUBJECT1_de_DE',
                'chaine',
                'Ihr Kontoauszug',
                '',
                0
            ),
            25 => array(
                'ITSUBJECT2_de_DE',
                'chaine',
                'Ihre Mahnung',
                '',
                0
            ),
            26 => array(
                'ITSUBJECT3_de_DE',
                'chaine',
                'Letzte Mahnung',
                '',
                0
            ),
        );

        $country = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
        if ($country[0] == $conf->entity && $country[2] == "France")
            $this->editor_url = "<a target='_blank' href='https://www.inovea-conseil.com/'>www.inovea-conseil.com</a> (<a target='_blank' href='https://www.dolibiz.com/wp-content/uploads/attestation/attestation-" . strtolower($this->name) . "-" . $this->version . ".pdf'>Attestation NF525</a>)";
        else $this->editor_url = 'https://www.inovea-conseil.com';

        // Array to add new pages in new tabs
        // Example:
        // New pages on tabs
        $this->tabs = array(
            'thirdparty:+invoicetracking:Invoicetracking:invoicetracking@invoicetracking:$user->rights->facture->lire:/invoicetracking/tabs/invoicetracking.php?id=__ID__',
            'invoice:+invoicetracking:Reminder:invoicetracking@invoicetracking:empty($user->societe_id) && $user->rights->facture->lire:/invoicetracking/tabs/invoicetracking_facture.php?id=__ID__'
        );

        if (!isset($conf->invoicetracking->enabled)) {
            $conf->invoicetracking = new stdClass();
            $conf->invoicetracking->enabled = 0;
        }

        $this->dictionnaries = array();

        $this->boxes[$r][1] = "box_invoicetracking@invoicetracking";

        $this->rights = array();  // Permission array used by this module
        $r++;
        $this->rights[$r][0] = 43241401; // id de la permission
        $this->rights[$r][1] = $langs->trans("IT-RIGHT1"); // libelle de la permission
        $this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
        $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'seeall';


        $r = 0;
        $this->menus = array(); // List of menus to add
        $r = 0;

        if ((int) DOL_VERSION >= 7) {
            $this->menu[$r] = array(
                //	// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
                'fk_menu' => 'fk_mainmenu=billing',
                //	// This is a Left menu entry
                //	'type'=>'left',
                'titre' => 'Invoicetracking',
                'mainmenu' => 'billing',
                'leftmenu' => 'invoicetracking',
                'url' => '/invoicetracking/index.php?action=list',
                //	// Lang file to use (without .lang) by module.
                //	// File must be in langs/code_CODE/ directory.
                'langs' => 'invoicetracking@invoicetracking',
                //	'position'=>100,
                //	// Define condition to show or hide menu entry.
                //	// Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                //	// Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                'enabled' => '$conf->invoicetracking->enabled && $user->rights->invoicetracking->seeall',
                //	// Use 'perms'=>'$user->rights->mymodule->level1->level2'
                //	// if you want your menu with a permission rules
                'perms' => '$user->rights->invoicetracking->seeall',
                //	'target'=>'',
                //	// 0=Menu for internal users, 1=external users, 2=both
                'user' => 2
            );
            $r++;
            $this->menu[$r] = array(
                //	// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
                'fk_menu' => 'fk_mainmenu=billing,fk_leftmenu=invoicetracking',
                //	// This is a Left menu entry
                'type' => 'left',
                'titre' => 'Addtracking',
                //	'mainmenu'=>'invoicetracking',
                //	'leftmenu'=>'invoicetracking',
                'url' => '/invoicetracking/index.php?action=add',
                //	// Lang file to use (without .lang) by module.
                //	// File must be in langs/code_CODE/ directory.
                'langs' => 'invoicetracking@invoicetracking',
                //	'position'=>100,
                //	// Define condition to show or hide menu entry.
                //	// Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                //	// Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                'enabled' => '$conf->invoicetracking->enabled && $user->rights->invoicetracking->seeall',
                //	// Use 'perms'=>'$user->rights->mymodule->level1->level2'
                //	// if you want your menu with a permission rules
                'perms' => '$user->rights->invoicetracking->seeall',
                //	'target'=>'',
                //	// 0=Menu for internal users, 1=external users, 2=both
                'user' => 2
            );
            $r++;
            $this->menu[$r] = array(
                'fk_menu' => 'fk_mainmenu=billing,fk_leftmenu=invoicetracking',
                'type' => 'left',
                'titre' => 'Masstracking',
                'url' => '/invoicetracking/list.php',
                'langs' => 'invoicetracking@invoicetracking',
                'enabled' => '$conf->invoicetracking->enabled && $user->rights->invoicetracking->seeall',
                'perms' => '$user->rights->invoicetracking->seeall',
                'user' => 2
            );
            $r = 1;
        } else {
            $this->menu[$r] = array(
                //	// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
                'fk_menu' => 'fk_mainmenu=accountancy',
                //	// This is a Left menu entry
                //	'type'=>'left',
                'titre' => 'Invoicetracking',
                'mainmenu' => 'accountancy',
                'leftmenu' => 'invoicetracking',
                'url' => '/invoicetracking/index.php?action=list',
                //	// Lang file to use (without .lang) by module.
                //	// File must be in langs/code_CODE/ directory.
                'langs' => 'invoicetracking@invoicetracking',
                //	'position'=>100,
                //	// Define condition to show or hide menu entry.
                //	// Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                //	// Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                'enabled' => '$conf->invoicetracking->enabled && $user->rights->invoicetracking->seeall',
                //	// Use 'perms'=>'$user->rights->mymodule->level1->level2'
                //	// if you want your menu with a permission rules
                'perms' => '$user->rights->invoicetracking->seeall',
                //	'target'=>'',
                //	// 0=Menu for internal users, 1=external users, 2=both
                'user' => 2
            );
            $r++;
            $this->menu[$r] = array(
                //	// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
                'fk_menu' => 'fk_mainmenu=accountancy,fk_leftmenu=invoicetracking',
                //	// This is a Left menu entry
                'type' => 'left',
                'titre' => 'Addtracking',
                //	'mainmenu'=>'invoicetracking',
                //	'leftmenu'=>'invoicetracking',
                'url' => '/invoicetracking/index.php?action=add',
                //	// Lang file to use (without .lang) by module.
                //	// File must be in langs/code_CODE/ directory.
                'langs' => 'invoicetracking@invoicetracking',
                //	'position'=>100,
                //	// Define condition to show or hide menu entry.
                //	// Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                //	// Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                'enabled' => '$conf->invoicetracking->enabled && $user->rights->invoicetracking->seeall',
                //	// Use 'perms'=>'$user->rights->mymodule->level1->level2'
                //	// if you want your menu with a permission rules
                'perms' => '$user->rights->invoicetracking->seeall',
                //	'target'=>'',
                //	// 0=Menu for internal users, 1=external users, 2=both
                'user' => 2
            );
            $r++;
            $this->menu[$r] = array(
                'fk_menu' => 'fk_mainmenu=accountancy,fk_leftmenu=invoicetracking',
                'type' => 'left',
                'titre' => 'Masstracking',
                'url' => '/invoicetracking/list.php',
                'langs' => 'invoicetracking@invoicetracking',
                'enabled' => '$conf->invoicetracking->enabled && $user->rights->invoicetracking->seeall',
                'perms' => '$user->rights->invoicetracking->seeall',
                'user' => 2
            );
            $r = 1;
        }
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus
     * (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return        int                    1 if OK, 0 if KO
     */
    public function init($options = '')
    {

        global $user, $db, $langs, $conf;
        $result = $this->loadTables();

        if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
        if (! defined('NOSTYLECHECK')) define('NOSTYLECHECK', '1');

        require_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';
        $cronjob = new Cronjob($db);

        $cronjob->fetch_all('DESC', 't.rowid', 0, 0, 1, array('label' => 'createFullAuto', 'entity' => $conf->entity));

        if (count($cronjob->lines) == 0 || $cronjob->lines == 0) {
            $cronjob->label = 'createFullAuto';
            $cronjob->jobtype = 'method';
            $cronjob->datestart = date('Hm') > 1730 ? mktime(17, 30, 0, date('n', strtotime("+1 day")), date('j', strtotime("+1 day")), date('Y', strtotime("+1 day"))) : mktime(17, 30, 0);
            $cronjob->module_name = 'invoicetracking';
            $cronjob->classesname = 'invoicetracking/class/invoicetracking.class.php';
            $cronjob->objectname = 'invoicetracking';
            $cronjob->methodename = 'createFullAuto';
            $cronjob->unitfrequency = 86400;
            $cronjob->frequency = DOL_VERSION < 4 ? '86400' : 1;
            $cronjob->status = 1;
            $cronjob->params = '';
            $cronjob->test = '$conf->invoicetracking->enabled';

            $res = $cronjob->create($user);
        }

        $extrafields = new ExtraFields($this->db);
        $extrafields_c = $extrafields->fetch_name_optionals_label('facture');
        $pos = count($extrafields_c);
        $extrafields->addExtraField('reminder', $langs->trans("ReminderInvoice"), 'boolean', $pos++, null, 'facture', 0, 0, '', 0, true, '', 1, 0);

        dolibarr_set_const($this->db, "CHECKLASTVERSION_EXTERNALMODULE", '1', 'int', 0, '', $conf->entity);
        return $this->_init($sql = array(), $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return        int                    1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        /* $sql[] = "DELETE FROM " . MAIN_DB_PREFIX . "const WHERE name='REMINDER1'";
          $sql[] = "DELETE FROM " . MAIN_DB_PREFIX . "const WHERE name='REMINDER2'";
          $sql[] = "DELETE FROM " . MAIN_DB_PREFIX . "const WHERE name='REMINDER3'"; */
        return $this->_remove($sql, $options);
    }

    /**
     * Create tables, keys and data required by module
     * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * and create data commands must be stored in directory /mymodule/sql/
     * This function is called by this->init
     *
     * @return        int        <=0 if KO, >0 if OK
     */
    private function loadTables()
    {
        return $this->_load_tables('/invoicetracking/sql/');
    }
}
