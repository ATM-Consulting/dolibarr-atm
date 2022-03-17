<?php

/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2017-2018       INOVEA CONSEIL       <info@inovea-conseil.com>
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
 * or see http://www.gnu.org/
 */

/**
 * 	\file       htdocs/core/modules/facture/doc/pdf_crabe.modules.php
 * 	\ingroup    facture
 * 	\brief      File of class to generate customers invoices from crabe model
 */
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

/**
 * 	Class to manage PDF invoice template Crabe
 */
class pdf_remind extends ModelePDFFactures
{

    var $db;
    var $name;
    var $description;
    var $type;
    var $phpmin = array(4, 3, 0); // Minimum version of PHP required by module
    var $version = 'dolibarr';
    var $page_largeur;
    var $page_hauteur;
    var $format;
    var $marge_gauche;
    var $marge_droite;
    var $marge_haute;
    var $marge_basse;
    var $emetteur; // Objet societe qui emet

    /**
     * @var bool Situation invoice type
     */
    public $situationinvoice;

    /**
     * @var float X position for the situation progress column
     */
    public $posxprogress;

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
        $this->name = "remind";
        $this->description = $langs->trans('PDFRemindDescription');

        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
        $this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
        $this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
        $this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;



        // Get source company
        $this->emetteur = $mysoc;
        if (empty($this->emetteur->country_code))
            $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if was not defined
    }

    /**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int         	    			1=OK, 0=KO
     */
    function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = array())
    {
        global $user, $langs, $conf, $mysoc, $db, $hookmanager;

        if (!is_object($outputlangs))
            $outputlangs = $langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF))
            $outputlangs->charset_output = 'UTF-8';

        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("products");

        $nblignes = count($object->lines);

        // Loop on each lines to detect if there is at least one image to show
        $realpatharray = array();
        if (!empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE)) {
            for ($i = 0; $i < $nblignes; $i++) {
                if (empty($object->lines[$i]->fk_product))
                    continue;

                $objphoto = new Product($this->db);
                $objphoto->fetch($object->lines[$i]->fk_product);

                $pdir = get_exdir($object->lines[$i]->fk_product, 2, 0, 0, $objphoto, 'product') . $object->lines[$i]->fk_product . "/photos/";
                $dir = $conf->product->dir_output . '/' . $pdir;

                $realpath = '';
                foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
                    $filename = $obj['photo'];
                    //if ($obj['photo_vignette']) $filename='thumbs/'.$obj['photo_vignette'];
                    $realpath = $dir . $filename;
                    break;
                }

                if ($realpath)
                    $realpatharray[$i] = $realpath;
            }
        }

        if (count($realpatharray) == 0)
            $this->posxpicture = $this->posxtva;

        if ($conf->facture->dir_output) {
            $object->fetch_thirdparty();


            // Definition of $dir and $file
            if ($object->specimen) {
                $dir = $conf->facture->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $objectref = dol_sanitizeFileName($object->ref);
                $dir = $conf->facture->dir_output . "/temp/massgeneration";
                $file = $dir . "/" . $objectref . ".pdf";
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }

                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
                // Set nblignes with the new facture lines content after hook
                $nblignes = count($object->lines);
                $nbpayments = count($object->getListOfPayments());

                // Create pdf instance
                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
                $pdf->SetAutoPageBreak(1, 0);

                $heightforinfotot = 50 + (4 * $nbpayments); // Height reserved to output the info and total part and payment part
                $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }

                $pdf->SetFont(pdf_getPDFFont($outputlangs));

                // Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output . '/' . $conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("Invoice"));
                $pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("Invoice") . " " . $outputlangs->convToOutputCharset($object->thirdparty->name));
                if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION))
                    $pdf->SetCompression(false);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                // Positionne $this->atleastonediscount si on a au moins une remise
                // New page
                $pdf->AddPage();
                if (!empty($tplidx))
                    $pdf->useTemplate($tplidx);
                $pagenb++;

                $this->_pagehead2($pdf, $object, 1, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');  // Set interline to 3
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 90;
                $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 10);
                $tab_height = 130;
                $tab_height_newpage = 150;


                $iniY = $tab_top + 7;
                $curY = $tab_top + 7;
                $nexY = $tab_top + 7;

                $pdf->SetY($nexY);


                $totalpaye = $object->getSommePaiement();
                $totalcreditnotes = $object->getSumCreditNotesUsed();
                $totaldeposits = $object->getSumDepositsUsed();

                $totalttc = $object->total_ttc;
                if(!empty($object->multicurrency_total_ttc))
                    $totalttc = $object->multicurrency_total_ttc;

                $money = $conf->global->MAIN_MONNAIE;
                if(!empty($object->multicurrency_code));
                $money = $object->multicurrency_code;

                $resteapayer = price2num($totalttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');

                // Make substitution
                $ad = "ADDING" . $moreparams['number'];
                $ad2 = floatval($conf->global->$ad) / 100;
                $adtype = "select_ADDING" . $moreparams['number'];
                if ($conf->global->$adtype == 1 && !empty($conf->global->$ad)) {
                    if ($moreparams['number'] > 0 && !empty($conf->global->FEESSUP)) {
                        $majo = $resteapayer + ($resteapayer * $ad2) + $conf->global->FEESSUP;
                    } else {
                        $majo = $resteapayer + ($resteapayer * $ad2);
                    }

                    $majotype = "%";
                } else {
                    if ($moreparams['number'] > 0 && !empty($conf->global->FEESSUP)) {
                        $majo = $resteapayer + floatval($conf->global->$ad) + $conf->global->FEESSUP;
                    } else {
                        $majo = $resteapayer + floatval($conf->global->$ad);
                    }

                    $majotype = $conf->global->MAIN_MONNAIE;
                }

                $lastn = $firstn = "";
                $contactr = $object->liste_contact(-1, 'external', 0, 'REMINDER');
                if (!empty($contactr)) {
                    $j = 0;
                    while ($j < count($contactr)) {
                        if (empty($firstn) && !empty($contactr[$j]['email'])) {
                            $lastn = $contactr[$j]['lastname'];
                            $firstn = $contactr[$j]['firstname'];
                        }
                        $j++;
                    }
                }
                if (empty($firstn)) {
                    $contactf = $object->liste_contact(-1, 'external', 0, 'BILLING');
                    if (!empty($contactf)) {
                        $j = 0;
                        while ($j < count($contactf)) {
                            if (empty($firstn) && !empty($contactf[$j]['email'])) {
                                $lastn = $contactf[$j]['lastname'];
                                $firstn = $contactf[$j]['firstname'];
                            }
                            $j++;
                        }
                    }
                }

                $it = "ITREMINDDATE" . $moreparams['number'];
                if (empty($moreparams['substitutionArray'])) {
                    $substitutionarray = array(
                        '__REF_FACTURE__' => $object->ref,
                        '__DATE_FACTURE__' => dol_print_date($object->date, 'daytext'),
                        '__TOTAL_TTC__' => round($object->total_ttc, 2),
                        '__DATE_ECHEANCE__' => dol_print_date($object->date_lim_reglement, 'daytext'),
                        '__DETTE__' => round($resteapayer, 2),
                        '__MONEY__' => $langs->trans('Currency'.$money),
                        '__MAJORATION__' => floatval($conf->global->$ad),
                        '__MAJORATIONTYPE__' => $majotype,
                        '__MAJORATION_TOTAL__' => round($majo, 2),
                        '__FRAISSUP__' => $conf->global->FEESUP,
                        '__FIRSTNAME__' => $firstn,
                        '__NAME__' => $lastn,
                        '__ID__' => $object->id,
                        '__EMAIL__' => $object->thirdparty->email,
                        '__RETARD__' => preg_replace("/[^0-9]/", "", "ITREMINDDATE" . $conf->global->$it),
                        '__USER_SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? $user->signature : ''));  // Done into actions_sendmails
                }

                $re = "REMINDER" . $moreparams['number'] . "_" . $outputlangs->defaultlang;
                $text = make_substitutions($conf->global->$re, $substitutionarray);
                $pdf->writeHTML($text);

                // Charge la facture depuis le dossier
                $ff = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref) . '/' . dol_sanitizeFileName($object->ref) . ".pdf";
                $pagecount = $pdf->setSourceFile($ff);
                for ($i = 1; $i <= $pagecount; $i++) {
                    $tplidx = $pdf->importPage($i);
                    $s = $pdf->getTemplatesize($tplidx);
                    $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                    $pdf->useTemplate($tplidx);
                    if ($moreparams['number'] > 0)
                        pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $langs->trans('watermark'.$moreparams['number']));
                }


                $pdf->Close();

                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);

                global $action;
                if(empty($conf->swissbanking->enabled))
                    $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

                if (!empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

                return 1;   // No error
            }
            else {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
            return 0;
        }

        $this->error = $langs->transnoentities("ErrorUnknown");
        return 0;   // Erreur par defaut
    }

    /**
     *  Show top header of page.
     *
     *  @param	PDF			$pdf     		Object PDF
     *  @param  Object		$object     	Object to show
     *  @param  int	    	$showaddress    0=no, 1=yes
     *  @param  Translate	$outputlangs	Object lang for output
     *  @return	void
     */
    function _pagehead2(&$pdf, $object, $showaddress, $outputlangs)
    {
        global $conf, $langs;

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("companies");

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);



        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $w = 110;

        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
        if ($this->emetteur->logo) {
            if (is_readable($logo)) {
                $height = pdf_getHeightForLogo($logo);
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else {
            $text = $this->emetteur->name;
            $pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
        }

        $pdf->SetFont('', '', $default_font_size - 2);
        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell($w, 4, $outputlangs->transnoentities($this->emetteur->town) . " : " . dol_print_date(time(), "day", false, $outputlangs), '', 'R');


        $posy += 1;



        if ($showaddress) {
            // Sender properties
            $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty);

            // Show sender
            $posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
            $posx = $this->marge_gauche;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
                $posx = $this->page_largeur - $this->marge_droite - 80;

            $hautcadre = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40;
            $widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82;


            // Show sender frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx, $posy - 5);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
            $pdf->SetTextColor(0, 0, 60);

            // Show sender name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
            $posy = $pdf->getY();

            // Show sender information
            $pdf->SetXY($posx + 2, $posy);
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');



            // If BILLING contact defined on invoice, we use it
            $usecontact = false;
            $arrayidcontact = $object->getIdContact('external', 'BILLING');
            if (count($arrayidcontact) > 0) {
                $usecontact = true;
                $result = $object->fetch_contact($arrayidcontact[0]);
            }

            //Recipient name
            // On peut utiliser le nom de la societe du contact
            if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
                $thirdparty = $object->contact;
            } else {
                $thirdparty = $object->thirdparty;
            }

            $carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, 'target', $object);

            // Show recipient
            $widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100;
            if ($this->page_largeur < 210)
                $widthrecbox = 84; // To work with US executive format
            $posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
                $posx = $this->marge_gauche;

            // Show recipient frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + 2, $posy - 5);

            // Show recipient name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

            $posy = $pdf->getY();

            // Show recipient information
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->SetXY($posx + 2, $posy);
            $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
        }

        $pdf->SetTextColor(0, 0, 0);
    }
}
