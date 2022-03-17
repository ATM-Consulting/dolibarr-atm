<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
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
 *	\file       htdocs/prixmassecategorie/lib/prixmassecategorie.lib.php
 *	\brief      Ensemble de fonctions de base pour le module prixmassecategorie
 * 	\ingroup	prixmassecategorie
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

dol_include_once("/prixmassecategorie/class/prixmassecategorie.class.php");

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function prixmassecategorie_completesubstitutionarray(&$substitutionarray,$outputlangs,$object,$parameters)
{
	global $db, $langs, $conf, $user;
	$outputlangs->load("prixmassecategorie@prixmassecategorie");


	if ($object && $object->id > 0)
	{
		$prixmassecategorie = new PrixMasseCategorie($db);
		$prixmassecategorie->fetch('', $object->id);		

		$badge = count($prixmassecategorie->mods);
		
		$substitutionarray['PRIXMASSECATEGORIENUMMODS'] = '<span class="badge">'.$badge.'</span>';
	}

	return 1;
}
