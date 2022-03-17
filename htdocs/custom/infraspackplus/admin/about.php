<?php
	/************************************************
	* Copyright (C) 2016-2022	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	************************************************/

	/************************************************
	* 	\file		../infraspackplus/admin/about.php
	* 	\ingroup	InfraS
	* 	\brief		about page
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->load("admin");
	$langs->load('infraspackplus@infraspackplus');

	// Actions **************************************
	$action							= GETPOST('action','alpha');
	if ($action == 'dwnChangelog')	$result	= infraspackplus_dwnChangelog('infraspackplus');

	// Access control *******************************
	$accessright																											= 0;
	if (! empty($user->admin) || ! empty($user->rights->infraspackplus->paramDolibarr) || ! empty($user->rights->infraspackplus->paramInfraSPlus)
		|| ! empty($user->rights->infraspackplus->paramImages) || ! empty($user->rights->infraspackplus->paramAdresses))	$accessright	= 1;
	if (empty($accessright))																								accessforbidden();

	// View *****************************************
	$page_name					= $langs->trans('infrasplussetup') .' - '. $langs->trans('About');
	llxHeader('', $page_name);
	if (! empty($user->admin))	$linkback	= '<a href = "'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head = infraspackplus_admin_prepare_head();
	$picto	= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'about', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// About page goes here *************************
	$currentversion	= infraspackplus_getLocalVersionMinDoli('infraspackplus');
	$ChangeLog		= infraspackplus_getChangeLog('infraspackplus', $currentversion[2], $currentversion[3], 1);
	print '	<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	print $ChangeLog;
	print infraspackplus_getSupportInformation($currentversion[0]);
	print '	</form>';
	dol_fiche_end();
	llxFooter();
	$db->close();
?>
