<?php
/* Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FI8TNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/theme/eldy/theme_vars.inc.php
 *	\brief      File to declare variables of CSS style sheet
 *  \ingroup    core
 *
 *  To include file, do this:
 *              $var_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
 *              if (is_readable($var_file)) include $var_file;
 */

global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;
$theme_bordercolor = array(235, 235, 224);
$theme_datacolor = array(array(137, 86, 161), array(60, 147, 183), array(250, 190, 80), array(191, 75, 57), array(80, 166, 90), array(140, 140, 220), array(190, 120, 120), array(190, 190, 100), array(115, 125, 150), array(100, 170, 20), array(150, 135, 125), array(85, 135, 150), array(150, 135, 80), array(150, 80, 150));
if (!defined('ISLOADEDBYSTEELSHEET'))	// File is run after an include of a php page, not by the style sheet, if the constant is not defined.
{
	if (!empty($conf->global->MAIN_OPTIMIZEFORCOLORBLIND)) // user is loaded by dolgraph.class.php
	{
		if ($conf->global->MAIN_OPTIMIZEFORCOLORBLIND == 'flashy')
		{
			$theme_datacolor = array(array(157, 56, 191), array(0, 147, 183), array(250, 190, 30), array(221, 75, 57), array(0, 166, 90), array(140, 140, 220), array(190, 120, 120), array(190, 190, 100), array(115, 125, 150), array(100, 170, 20), array(150, 135, 125), array(85, 135, 150), array(150, 135, 80), array(150, 80, 150));
		}
		else
		{
			// for now we use the same configuration for all types of color blind
			$theme_datacolor = array(array(248, 220, 1), array(9, 85, 187), array(42, 208, 255), array(0, 0, 0), array(169, 169, 169), array(253, 102, 136), array(120, 154, 190), array(146, 146, 55), array(0, 52, 251), array(196, 226, 161), array(222, 160, 41), array(85, 135, 150), array(150, 135, 80), array(150, 80, 150));
		}
	}
}

$theme_bgcolor = array(hexdec('F4'), hexdec('F4'), hexdec('F4'));
$theme_bgcoloronglet = array(hexdec('DE'), hexdec('E7'), hexdec('EC'));

// Colors
$colorbackhmenu1 = '68,68,90'; // topmenu
$colorbackvmenu1 = '250,250,250'; // vmenu
$colortopbordertitle1 = '200,200,200'; // top border of title
$colorbacktitle1 = '233,234,237'; // title of tables,list
$colorbacktabcard1 = '255,255,255'; // card
$colorbacktabactive = '234,234,234';
$colorbacklineimpair1 = '255,255,255'; // line impair
$colorbacklineimpair2 = '255,255,255'; // line impair
$colorbacklinepair1 = '250,250,250'; // line pair
$colorbacklinepair2 = '250,250,250'; // line pair
$colorbacklinepairhover = '230,237,244'; // line hover
$colorbacklinepairchecked = '230,237,244'; // line checked
$colorbacklinebreak = '233,228,230'; // line break
$colorbackbody = '255,255,255';
$colortexttitlenotab = '0,113,121'; // 140,80,10 or 10,140,80
$colortexttitle = '0,0,0';
$colortext = '0,0,0';
$colortextlink = '10, 20, 100';
$fontsize = '0.86em';
$fontsizesmaller = '0.75em';
$topMenuFontSize = '1.2em';
$toolTipBgColor = 'rgba(255, 255, 255, 0.96)';
$toolTipFontColor = '#333';

// text color
$textSuccess   = '#28a745';
$colorblind_deuteranopes_textSuccess = '#37de5d';
$textWarning   = '#a37c0d'; // See $badgeWarning
$textDanger    = '#9f4705'; // See $badgeDanger
$colorblind_deuteranopes_textWarning = $textWarning; // currently not tested with a color blind people so use default color


// Badges colors
$badgePrimary   = '#007bff';
$badgeSecondary = '#cccccc';
$badgeSuccess   = '#55a580';
$badgeWarning   = '#a37c0d'; // See $textDanger bc9526
$badgeDanger    = '#9f4705'; // See $textDanger
$badgeInfo      = '#aaaabb';
$badgeDark      = '#343a40';
$badgeLight     = '#f8f9fa';

// badge color ajustement for color blind
$colorblind_deuteranopes_badgeSuccess   = '#37de5d'; //! text color black
$colorblind_deuteranopes_badgeSuccess_textColor7 = '#000';
$colorblind_deuteranopes_badgeWarning   = '#e4e411';

/* default color for status : After a quick check, somme status can have oposite function according to objects
*  So this badges status uses default value according to theme eldy status img
*  TODO: use color definition vars above for define badges color status X -> exemple $badgeStatusValidate, $badgeStatusClosed, $badgeStatusActive ....
*/
$badgeStatus0 = '#cbd3d3';
$badgeStatus1 = '#cbd3d3';
//$badgeStatus2 = '#689FE0'; //bleu
$badgeStatus2 = '#E4D92E'; //jaune
$badgeStatus3 = '#E06D03'; //orange
//$badgeStatus4 = '#689FE0'; //bleu
$badgeStatus4 = '#689FE0'; //Bleu
$badgeStatus5 = '#E070D7'; // Rose
$badgeStatus6 = '#6B6767'; //gris
$badgeStatus7 = '#53AD4D'; //vert
$badgeStatus8 = '#12B08E'; //vert/bleu
$badgeStatus10 = '#993013';
$badgeStatus9 = '#E02E2B'; //Rouge
$badgeStatus11 = '#33D4FF';
$badgeStatus12 = '#21B426';
$badgeStatus13 = '#9321B4';
$badgeStatus14 = '#7ECC74';
$badgeStatus15 = '#E4D92E';
$badgeStatus16 = '#689FE0';
$badgeStatus17 = '#A93C8B';
$badgeStatus18 = '#E4D92E';
$badgeStatus19 = '#000000';
$badgeStatus20 = '#A93C8B';
$badgeStatus21 = '#A93C8B';
$badgeStatus22 = '#A93C8B';

//specifique tableau carafinance
$badgeStatusrenov0 = '#cbd3d3';
$badgeStatusrenov1 = '#cbd3d3';
//$badgeStatus2 = '#689FE0'; //bleu
$badgeStatusrenov2 = '#E02E2B'; //Rouge
$badgeStatusrenov3 = '#E06D03'; //orange
//$badgeStatus4 = '#689FE0'; //bleu
$badgeStatusrenov4 = '#12B08E';//'#689FE0'; //Bleu
$badgeStatusrenov5 = '#E070D7'; // Rose
$badgeStatusrenov6 = '#E4D92E'; //Jaune
$badgeStatusrenov7 = '#689FE0'; //Bleu
$badgeStatusrenov8 = '#B9B9B6'; //Gris clair
$badgeStatusrenov9 = '#B9B9B6'; //Gris clair
$badgeStatusrenov10 = '#53AD4D'; //Vert
$badgeStatusrenov11 = '#E06D03'; //Orange
$badgeStatusrenov12 = '#E4D92E'; //Jaune
$badgeStatusrenov13 = '#8F8F8C'; //Bleu
$badgeStatusrenov14 = '#cbd3d3'; //Blanc


$badgeStatusfin0 = '#cbd3d3';
$badgeStatusfin1 = '#E4D92E';
$badgeStatusfin2 = '#E02E2B'; //Rouge
$badgeStatusfin3 = '#689FE0'; 
$badgeStatusfin4 = '#53AD4D'; 
$badgeStatusfin5 = '#12B08E';//'#689FE0'; //Bleu
$badgeStatusfin6 = '#cbd3d3';
$badgeStatusfin7 = '#E070D7';
$badgeStatusfin8 = '#cbd3d3';
$badgeStatusfin9 = '#E4D92E';
$badgeStatusfin10 = '#E06D03';
$badgeStatusfin11 = '#53AD4D';

$badgeStatusppv0 = '#cbd3d3';
$badgeStatusppv1 = '#E4D92E';
$badgeStatusppv2 = '#E02E2B'; //Rouge
$badgeStatusppv3 = '#689FE0'; 
$badgeStatusppv4 = '#53AD4D'; 
$badgeStatusppv5 = '#12B08E';//'#689FE0'; //Bleu
$badgeStatusppv6 = '#cbd3d3';
$badgeStatusppv7 = '#E070D7';
$badgeStatusppv8 = '#cbd3d3';
$badgeStatusppv9 = '#E4D92E';
$badgeStatusppv10 = '#E06D03';
$badgeStatusfin11 = '#2A563D';

$badgeStatuscomission0 = '#cbd3d3';
$badgeStatuscomission1 = '#E4D92E';
$badgeStatuscomission2 = '#E02E2B'; //Rouge
$badgeStatuscomission3 = '#53AD4D'; 
$badgeStatuscomission4 = '#12B08E'; 
$badgeStatuscomission5 = '#EE5F08';//'#689FE0'; //Bleu
$badgeStatuscomission6 = '#E02E2B';
$badgeStatuscomission7 = '#E070D7';
$badgeStatuscomission8 = '#cbd3d3';
$badgeStatuscomission9 = '#E4D92E';
$badgeStatuscomission10 = '#E06D03';
$badgeStatuscomission11 = '#53AD4D';

$badgeStatusdevis0 = '#cbd3d3';
$badgeStatusdevis1 = '#cbd3d3';
$badgeStatusdevis2 = '#E06D03'; 
$badgeStatusdevis3 = '#E4D92E';
$badgeStatusdevis4 = '#12B08E'; 
$badgeStatusdevis5 = '#EE5F08';//'#689FE0'; //Bleu
$badgeStatusdevis6 = '#E02E2B';
$badgeStatusdevis7 = '#E070D7';
$badgeStatusdevis8 = '#cbd3d3';
$badgeStatusdevis9 = '#E02E2B';
$badgeStatusdevis10 = '#E06D03';
$badgeStatusdevis11 = '#53AD4D';

// status color ajustement for color blind
$colorblind_deuteranopes_badgeStatus4 = $colorblind_deuteranopes_badgeStatus7 = $colorblind_deuteranopes_badgeSuccess; //! text color black
$colorblind_deuteranopes_badgeStatus_textColor4 = $colorblind_deuteranopes_badgeStatus_textColor7 = '#000';
$colorblind_deuteranopes_badgeStatus1 = $colorblind_deuteranopes_badgeWarning;
$colorblind_deuteranopes_badgeStatus_textColor1 = '#000';
