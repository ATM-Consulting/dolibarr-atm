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
	* 	\file		../infraspackplus/admin/adresses.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup adresses for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->load("admin");
	$langs->load('companies');
	$langs->load('errors');
	$langs->load('dict');
	$langs->load('infraspackplus@infraspackplus');

	// Access control *******************************
	$accessright					= !empty($user->admin) || !empty($user->rights->infraspackplus->paramBkpRest) ? 2 : (!empty($user->rights->infraspackplus->paramAdresses) ? 1 : 0);
	if (empty($accessright))		accessforbidden();

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther($db);
	$formcompany	= new FormCompany($db);
	$object			= new Societe($db);
	if (class_exists('Address')) {
		$addresses			= new Address($db);
		$addresses->lines	= array();
	}
	$object->id						= 0;
	$btnAction						= 'value = "add" name = "add">'.$langs->trans('Add');
	$confirm_mesg					= '';
	$action							= GETPOST('action', 'alpha');
	$confirm						= GETPOST('confirm', 'alpha');
	$result							= '';
	infraspackplus_test_new_fields('infraspackplus');
	//Sauvegarde / Restauration
	if ($action == 'bkupParams')	$result	= infraspackplus_bkup_module ('infraspackplus');
	if ($action == 'restoreParams')	$result	= infraspackplus_restore_module ('infraspackplus');
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Opt'	=> array('INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT', 'INFRASPLUS_PDF_FREE_LIVR_EXF', 'INFRASPLUS_PDF_TYPE_SOUS_TRAITANT'));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	if (GETPOST('cancel'))	header('Location: '.$_SERVER["PHP_SELF"]);
	if($action == 'edit' && class_exists('Address')) {
		$objaddress	= new Address($db);
		$objaddress->fetch_address(GETPOST('id', 'int'));
		$btnAction	= 'value = "save" name = "save">'.$langs->trans('Save');
	}
	if(($action == 'add' || GETPOST('save')) && !GETPOST('cancel')) {
		$parms_ok	= true;
		if (GETPOST('label', 'alpha') == '' || GETPOST('label', 'alpha') == $langs->trans('RequiredField')) {
			$parms_ok	= false;
			setEventMessages($langs->trans('InfraSPlusParamAliasIsRequired'), null, 'errors');
		}
		if (GETPOST('name', 'alpha') == '' || GETPOST('label', 'alpha') == $langs->trans('RequiredField')) {
			$parms_ok	= false;
			setEventMessages($langs->trans('InfraSPlusParamNameIsRequired'), null, 'errors');
		}
		if (GETPOST('save') && $parms_ok) {
			$sql_address	= 'SELECT rowid, label, name';
			$sql_address	.= ' FROM '.MAIN_DB_PREFIX.'societe_address';
			$sql_address	.= ' WHERE label = "'.GETPOST('label', 'alpha').'"';
			$sql_address	.= ' AND name = "'.GETPOST('name', 'alpha').'"';
			$sql_address	.= ' AND entity = '.$conf->entity;
			$sql_address	.= ' AND rowid <> '.GETPOST('id', 'int');
			$result_address	= $db->query($sql_address);
			if ($db->num_rows($result_address) === 1)
				setEventMessages($langs->trans('InfraSPlusParamAddressAlredyExists'), null, 'errors');
			else {
				$sql_update				= 'UPDATE '. MAIN_DB_PREFIX.'societe_address';
				$sql_update				.= ' SET label = "'.GETPOST('label', 'alpha').'",';
				$sql_update				.= ' name = "'.GETPOST('name', 'alpha').'",';
				$sql_update				.= ' address = "'.GETPOST('address', 'alpha').'",';
				$sql_update				.= ' zip = "'.GETPOST('zipcode', 'alpha').'",';
				$sql_update				.= ' town = "'.GETPOST('town', 'alpha').'",';
				$sql_update				.= ' fk_pays = "'.GETPOST('country_id', 'alpha').'",';
				$sql_update				.= ' phone = "'.GETPOST('phone', 'alpha').'",';
				$sql_update				.= ' fax = "'.GETPOST('fax', 'alpha').'",';
				$sql_update				.= ' email = "'.GETPOST('email', 'alpha').'",';
				$sql_update				.= ' url = "'.GETPOST('url', 'alpha').'",';
				$sql_update				.= ' note = "'.GETPOST('note', 'restricthtml').'",';
				$sql_update				.= ' fk_user_modif = "'.$user->id.'"';
				$sql_update				.= ' WHERE rowid = '.GETPOST('id', 'int');
				$result_update			= $db->query($sql_update);
				if ($result_update < 0)	setEventMessages($langs->trans('InfraSPlusParamErrorSavingAddress'), null, 'errors');
				else					setEventMessages($langs->trans('InfraSPlusParamAddressUpdated'), null, 'mesgs');
				$db->free($result_update);
			}
			$db->free($result_address);
		}
		if (($action == 'add' && $parms_ok) && !GETPOST('save')) {
			$sql_address	= 'SELECT DISTINCT label, name';
			$sql_address	.= ' FROM '.MAIN_DB_PREFIX.'societe_address';
			$sql_address	.= ' WHERE label = "'.GETPOST('label', 'alpha').'"';
			$sql_address	.= ' AND name = "'.GETPOST('name', 'alpha').'"';
			$result_address	= $db->query($sql_address);
			if ($db->num_rows($result_address) === 1)
				setEventMessages($langs->trans('InfraSPlusParamAddressAlredyExists'), null, 'errors');
			else {
				$now			= dol_now();
				$sql_insert		= 'INSERT INTO '. MAIN_DB_PREFIX.'societe_address(datec, label, name, address, zip, town, fk_pays, phone, fax, email, url, note, fk_user_creat, entity)';
				$sql_insert		.= 'VALUES ("'.$db->idate($now).'",'
											.' "'.GETPOST('label', 'alpha').'",'
											.' "'.GETPOST('name', 'alpha').'",'
											.' "'.GETPOST('address', 'alpha').'",'
											.' "'.GETPOST('zipcode', 'alpha').'",'
											.' "'.GETPOST('town', 'alpha').'",'
											.' "'.GETPOST('country_id', 'int').'",'
											.' "'.GETPOST('phone', 'alpha').'",'
											.' "'.GETPOST('fax', 'alpha').'",'
											.' "'.GETPOST('email', 'alpha').'",'
											.' "'.GETPOST('url', 'alpha').'",'
											.' "'.GETPOST('note', 'restricthtml').'",'
											.' "'.$user->id.'",'
											.' "'.$conf->entity.'")';
				$result_insert	= $db->query($sql_insert);
				if (!$result_insert || $result_insert < 0)
					setEventMessages($langs->trans('InfraSPlusParamErrorSavingAddress'), null, 'errors');
				else	setEventMessages($langs->trans('InfraSPlusParamAddressSaved'), null, 'mesgs');
				$db->free($result_insert);
			}
			$db->free($result_address);
		}
	}
	if ($action == 'defaultL')	$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_DEFAULT_ADDR_DELIV', GETPOST('defaultaddrdeliv'),'chaine',0,'',$conf->entity);
	if ($action == 'delete')
		$confirm_mesg	= $form->formconfirm($_SERVER['PHP_SELF'].'?id='.GETPOST('id', 'int'), $langs->trans('InfraSPlusParamDeleteAddress'), $langs->trans('InfraSPlusParamConfirmDeleteAddress'), 'delete_ok', '', 1, (int) $conf->use_javascript_ajax);
	if ($action == 'delete_ok' && $confirm == 'yes') {
		$sql_supp				= 'DELETE FROM '. MAIN_DB_PREFIX.'societe_address ';
		$sql_supp				.= ' WHERE rowid = '.GETPOST('id', 'int');
		$result_supp			= $db->query($sql_supp);
		if ($result_supp < 0)	setEventMessages($langs->trans('InfraSPlusParamErrorDeletingAddress'), null, 'errors');
		else					setEventMessages($langs->trans('InfraSPlusParamDeleted'), null, 'mesgs');
		$db->free($result_supp);
	}
	$sql_show		= 'SELECT a.rowid as id, a.label, a.name, a.address, a.datec as date_creation, a.tms as date_modification, a.fk_soc';
	$sql_show		.= ', a.zip, a.town, a.fk_pays as country_id, a.phone, a.fax, a.email, a.url, a.note';
	$sql_show		.= ', c.code as country_code, c.label as country';
	$sql_show		.= ' FROM '.MAIN_DB_PREFIX.'societe_address as a';
	$sql_show		.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON a.fk_pays = c.rowid';
	$sql_show		.= ' WHERE a.fk_soc = '.$object->id;
	$sql_show		.= ' AND a.entity = '.$conf->entity;
	$result_show	= $db->query($sql_show);
	if ($result_show && class_exists('Address')) {
		$num	= $db->num_rows($result_show);
		$i		= 0;
		while ($i < $num) {
			$objp						= $db->fetch_object($result_show);
			$line						= new AddressLine($db);
			$line->id					= $objp->id;
			$line->date_creation		= $db->jdate($objp->date_creation);
			$line->date_modification	= $db->jdate($objp->date_modification);
			$line->label				= $objp->label;
			$line->name					= $objp->name;
			$line->address				= $objp->address;
			$line->zip					= $objp->zip;
			$line->town					= $objp->town;
			$line->country_id			= $objp->country_id;
			$line->country_code			= $objp->country_id?$objp->country_code:'';
			$line->country				= $objp->country_id?($langs->trans('Country'.$objp->country_code)!='Country'.$objp->country_code?$langs->trans('Country'.$objp->country_code):$objp->country):'';
			$line->phone				= $objp->phone;
			$line->fax					= $objp->fax;
			$line->email				= $objp->email;
			$line->url					= $objp->url;
			$line->note					= $objp->note;
			$addresses->lines[$i]		= $line;
			$i++;
		}
	}
	elseif (class_exists('Address'))	dol_syslog(get_class($addresses).'::Fetch Erreur: aucune adresse', LOG_ERR);
	$db->free($result_show);
	if ($action == 'setExfAddrLivr')	$result	= infraspackplus_search_extf (true);

	if ($result == 1)	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	if ($result == -1)	setEventMessages($langs->trans('Error'), null, 'errors');

	// init variables *******************************
	$fckeditor			= !empty($conf->global->MAIN_MODULE_FCKEDITOR)						? 1																			: 0;
	$fckeditorEnable	= isset($conf->global->FCKEDITOR_ENABLE_DETAILS)					? $conf->global->FCKEDITOR_ENABLE_DETAILS									: 0;
	$result				= dolibarr_set_const($db, 'SOCIETE_ADDRESSES_MANAGEMENT', dolibarr_get_const($db, 'INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', $conf->entity), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	$typeSsT			= isset($conf->global->INFRASPLUS_PDF_TYPE_SOUS_TRAITANT)			? $conf->global->INFRASPLUS_PDF_TYPE_SOUS_TRAITANT							: '';
	$rowSpan			= $conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT					? 8																			: 7;
	$rowSpan			+= empty($conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON)	? (!empty($conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION) ? 4	: 3)	: 0;
	$rowSpan			+= $conf->global->MAIN_MODULE_CUSTOMLINK							? (!empty($conf->global->INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT) ? 3 : 2)		: 0;

	// View *****************************************
	$page_name					= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsAdresses');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	if (! empty($user->admin))	$linkback	= '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	else						$linkback	= '';
	print_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption				= img_picto($langs->trans('Setup'), 'setup');

	// Configuration header *************************
	$head				= infraspackplus_admin_prepare_head();
	$picto				= 'infraspackplus@infraspackplus';
	dol_fiche_head($head, 'adresses', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (class_exists('Address')) {
		if ($conf->use_javascript_ajax) {
			print '	<script src = "'.dol_buildpath('/infraspackplus/includes/js/jquery.cookie.js', 1).'"></script>
					<script type = "text/javascript">
						jQuery(document).ready(function() {
							var tblAexp = "";
							$.isSet = function(testVar){ return typeof(testVar) !== "undefined" && testVar !== null && testVar !== ""; };
							if ($.cookie && $.isSet($.cookie("tblAexp"))) { tblAexp = $.cookie("tblAexp"); }
							$(".toggle_bloc").hide();
							if (tblAexp != "") { $("[name=" + tblAexp + "]").toggle(); }
							$("#label").focus(function() { hideMessage("label","'.$langs->trans('RequiredField').'"); });
							$("#label").blur(function() { displayMessage("label","'.$langs->trans('RequiredField').'"); });
							$("#name").focus(function() { hideMessage("name","'.$langs->trans('RequiredField').'"); });
							$("#name").blur(function() { displayMessage("name","'.$langs->trans('RequiredField').'"); });
							displayMessage("label","'.$langs->trans('RequiredField').'");
							displayMessage("name","'.$langs->trans('RequiredField').'");
							$("#label").css("color","grey");
							$("#name").css("color","grey");
						});
						$(function () {
							$(".foldable .toggle_bloc_title").click(function() {
								if ($(this).siblings().is(":visible")) { $(".toggle_bloc").hide(); }
								else {
									$(".toggle_bloc").hide();
									$(this).siblings().show();
								}
								$.cookie("tblAexp", "", { expires: 1, path: "/" });
								$(".toggle_bloc").each(function() {
									if ($(this).is(":visible")) { $.cookie("tblAexp", $(this).attr("name"), { expires: 1, path: "/" }); }
								});
							});
						});
					</script>';
		}
		print '	<form action = "'.$_SERVER["PHP_SELF"].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">
					<input type = "hidden" name = "action" value = "add"/>
					<input type = "hidden" name = "id" value = "'.$objaddress->id.'"/>';
		//Sauvegarde / Restauration
		if ($accessright == 2)	infraspackplus_print_backup_restore();
		print '		<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamGestionAdresses').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/corp.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '			<table name = "tblGA-3" class = "noborder" width = "100%">';
		$metas	= array('125', '400px', '125px', '*', '120px');
		infraspackplus_print_colgroup($metas);
		$metas	= array(array(5), 'InfraSPlusParamNewAdresse');
		infraspackplus_print_liste_titre($metas);
		if (! empty($accessright)) {
			print '			<tr>
								<td class = "fieldrequired">'.fieldLabel('InfraSPlusParamAdressAlias', 'label', 1).'</td>
								<td><input type = "text" class = "minwidth300" id = "label" name = "label" value="'.($objaddress->label ? $objaddress->label : $langs->trans('RequiredField')).'" style = "margin: 0; padding: 0;"></td>
								<td class = "fieldrequired">'.fieldLabel('InfraSPlusParamAdressName', 'name', 1).'</td>
								<td><input type = "text" class = "minwidth300" id = "name" name = "name" value = "'.($objaddress->name ? $objaddress->name : $langs->trans('RequiredField')).'" style = "margin: 0; padding: 0;"></td>
								<td rowspan = "7" align="center">
									<button class = "button" style = "width: 110px;" type = "submit" '.$btnAction.'</button>
									<br/><br/>
									<button class = "button" style = "width: 110px;" type = "submit" value = "cancel" name = "cancel">'.$langs->trans('Cancel').'</button>
								</td>
							</tr>';
			print '			<tr>
								<td class = "tdtop">'.fieldLabel('Address', 'address').'</td>
								<td colspan = "3"><textarea name = "address" id = "address" class = "quatrevingtpercent" rows = "3" wrap = "soft">'.$objaddress->address.'</textarea></td>
							</tr>';
			print '			<tr>
								<td>'.fieldLabel('Zip', 'zipcode').'</td>
								<td>'.$formcompany->select_ziptown($objaddress->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6).'</td>
								<td>'.fieldLabel('Town', 'town').'</td>
								<td>'.$formcompany->select_ziptown($objaddress->town, 'town', array('zipcode', 'selectcountry_id', 'state_id')).'</td>
							</tr>';
			print '			<tr>
								<td>'.fieldLabel('Country', 'selectcounty_id').'</td>
								<td colspan = "3">'.$form->select_country($objaddress->country_id, 'country_id').info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'),1).'</td>
							</tr>';
			print '			<tr>
								<td>'.fieldLabel('Phone', 'phone').'</td>
								<td><input type = "text" class = "minwidth300" id = "phone" name = "phone" value = "'.$objaddress->phone.'" style = "margin: 0; padding: 0;"></td>
								<td>'.fieldLabel('Fax', 'fax').'</td>
								<td><input type = "text" class = "minwidth300" id = "fax" name = "fax" value = "'.$objaddress->fax.'" style = "margin: 0; padding: 0;"></td>
							</tr>';
			print '			<tr>
								<td>'.fieldLabel('Email', 'email').'</td>
								<td ><input type = "text" class = "minwidth300" id = "email" name = "email" value = "'.$objaddress->email.'" style = "margin: 0; padding: 0;"></td>
								<td>'.fieldLabel('Web', 'url').'</td>
								<td ><input type = "text" class = "minwidth300" id = "url" name = "url" value = "'.$objaddress->url.'" style = "margin: 0; padding: 0;"></td>
							</tr>';
			print '			<tr>
								<td class = "tdtop">'.fieldLabel('Note', 'note').'</td>
                                <td colspan = "3">';
			if (!empty($fckeditor)) {
				$doleditor	= new DolEditor('note', $objaddress->note, '', 80, 'dolibarr_notes');
				print $doleditor->Create();
			}
			else
				print '			<textarea name = "note" id = "note" class = "quatrevingtpercent" rows = "6" wrap = "soft">'.$objaddress->note.'</textarea>';
			print '				</td>
            				</tr>';
			print '			<tr class="oddeven">
								<td colspan = "4">
									'.fieldLabel(''.$langs->trans('InfraSPlusParamDefaultAddrDeliv').'', 'defaultaddrdeliv').'
									<select name = "defaultaddrdeliv" class = "select2-choice" style = "margin: 0; padding: 0; cursor: pointer;">
										<option name = "defaultaddrdeliv" value = "">'.$langs->trans('InfraSPlusParamNoAddrDeliv').'</option>';
			$selected_addr	= $conf->global->INFRASPLUS_PDF_DEFAULT_ADDR_DELIV;
			foreach ($addresses->lines as $lineaddress) {
				print '					<option name = "defaultaddrdeliv" value = "'.$lineaddress->label.'"';
				if ($selected_addr === $lineaddress->label)	print ' selected';
				print '					>'.$lineaddress->label.'</option>';
			}
			print '					</select>
								</td>
								<td align="center"><button class = "button" style = "width: 110px;" type = "submit" value = "defaultL" name = "action">'.$langs->trans('Validate').'</button></td>
							</tr>';
		}
		print '			</table>
					</div>
				</form>';
		print '	<form action = "'.$_SERVER["PHP_SELF"].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">
					<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamAddressesForMyCompany').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/list.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '			<table name = "tblLA-3" class = "noborder toggle_bloc" width = "100%">
							<tr class = "liste_titre">
								<td>'.$langs->trans('InfraSPlusParamAdressAlias').'</td>
								<td>'.$langs->trans('InfraSPlusParamAdressName').'</td>
								<td>'.$langs->trans('Address').'</td>
								<td>'.$langs->trans('Country').'</td>
								<td>'.$langs->trans('Email').'</td>
								<td>'.$langs->trans('Web').'</td>
								<td colspan = "2">&nbsp;</td>
							</tr>';
		if (! empty($accessright)) {
			if ($num > 0) {
				foreach ($addresses->lines as $lineaddress) {
					print '	<tr class="oddeven">
								<td>'.$lineaddress->label.'</td>
								<td>'.$lineaddress->name.'</td>
								<td>'.$lineaddress->address.' - '.$lineaddress->zip.' '.$lineaddress->town.'</td>
								<td>'.$lineaddress->country.'</td>
								<td>'.$lineaddress->email.'</td>
								<td>'.$lineaddress->url.'</td>
								<td><a href = "'.$_SERVER['PHP_SELF'].'?action=edit&id='.$lineaddress->id.'" class = "deletefilelink">'.img_edit().'</a></td>
								<td><a href = "'.$_SERVER['PHP_SELF'].'?action=delete&id='.$lineaddress->id.'" class = "deletefilelink">'.img_delete().'</a></td>
							</tr>';
				}
			}
			infraspackplus_print_final(8);
		}
		print '			</table>
					</div>
				</form>
				<form action="'.$_SERVER['PHP_SELF'].'" method = "post" enctype="multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">
					<div class = "foldable">';
		print load_fiche_titre('<FONT color = "#382453" size = "4">'.$langs->trans('InfraSPlusParamAddressesSetup').'</FONT>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '			<table name = "tblOPT-3" class = "noborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '170px', '120px');
		infraspackplus_print_colgroup($metas);
		$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infraspackplus_print_liste_titre($metas);
		if (! empty($accessright)) {
			$num	= 1;
			infraspackplus_print_btn_action('Opt', '<FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCautionSave'), $rowSpan, 3);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_CUSTOM_COUNTRY_ADDR', 'on_off', $langs->trans('InfraSPlusParamUseCustomCountryAddr'), '', array(), 1, 1, '', $num);
			if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_COMPANY_NAME_PLUS_CONTACT', 'on_off', $langs->trans('InfraSPlusParamSocNamePlusContact'), '', array(), 1, 1, '', $num);
			else	$num++;
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_COUNTRY', 'on_off', $langs->trans('InfraSPlusParamWithCountry'), '', array(), 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_PARENT_ADDR_FACT', 'on_off', $langs->trans('InfraSPlusParamFactureParentAddrFact'), '', array(), 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SENDER_ALIAS', 'on_off', $langs->trans('InfraSPlusParamShowSenderAlias'), '', array(), 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT', 'input', $langs->trans('InfraSPlusParamCodeAddrFact1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamCodeAddrFact2'), '', array(), 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SI_FACT', 'on_off', $langs->trans('InfraSPlusParamAddrLivrSiFact'), '', array(), 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 'on_off', $langs->trans('InfraSPlusParamUseDoliAdrLivr'), '', array(), 1, 1, '', $num);
			if (empty($conf->global->INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON)) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON', 'on_off', $langs->trans('InfraSPlusParamShowAdrLivr'), '', array(), 1, 1, '', $num);
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', 'on_off', $langs->trans('InfraSPlusParamShowAdrRecep'), '', array(), 1, 1, '', $num);
				if ($conf->global->INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION) {
					$exfIsSet	= infraspackplus_search_extf ();
					$textDesc	= $langs->trans('InfraSPlusParamSetExfAddrLivr', $conf->global->INFRASPLUS_PDF_FREE_LIVR_EXF);
					$desc		= $langs->trans('InfraSPlusParamFreeLivrExf').($exfIsSet == 0 ? ' <button class = "button" style = "height: 20px; padding: 0px !important;" type = "submit" value = "setExfAddrLivr" name = "action">'.$textDesc.'</button>': '');
					$num	= infraspackplus_print_input('INFRASPLUS_PDF_FREE_LIVR_EXF', 'input', $desc, '', array(), 1, 1, '', $num);
				}
				else	$num++;
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_ADRESSE_LIVRAISON_MIXTE', 'on_off', $langs->trans('InfraSPlusParamAdrLivrMixte'), '', array(), 1, 1, '', $num);
				$num++;
			}
			else {
				$num	+= 4;
				infraspackplus_print_input('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 'on_off', $langs->trans('InfraSPlusParamDoliAdrLivrRecep'), '', array(), 1, 1, '', $num);
			}
			if ($conf->global->MAIN_MODULE_CUSTOMLINK) {
				infraspackplus_print_hr(3);
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT', 'on_off', $langs->trans('InfraSPlusParamAdrTiersSsT'), '', array(), 1, 1, '', $num);
				if (!empty($conf->global->INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT))
					$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_SOUS_TRAITANT', 'selectTypeContact', $langs->trans('InfraSPlusParamTypeContactSsT'), '', array($object, $typeSsT, 'external', 'position', 1, 'minwidth100imp'), 1, 1, '', $num);
				else	$num++;
			}
			else	$num	+= 2;
		}
		print '			</table>
					</div>
				</form>';
	}
	else {
		print'<table width=100%>
				<body>
					<tr><td align = "center"><img src = "'.dol_buildpath('/infraspackplus/img/InfraS.gif', 1).'" width = "30%"/></td></tr>
					<tr><td align = "center"><FONT color = "red">'.$langs->trans('InfraSPlusCaution').'</FONT> '.$langs->trans('InfraSPlusParamNoAddressClass').'</td></tr>
				</body>
			  </table>';
	}
	dol_fiche_end();
	llxFooter();
	$db->close();
?>