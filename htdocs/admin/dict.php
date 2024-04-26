<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2022	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2021	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2011		Remy Younes				<ryounes@gmail.com>
 * Copyright (C) 2012-2015	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012		Christophe Battarel		<christophe.battarel@ltairis.fr>
 * Copyright (C) 2011-2022	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2015		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2016		Raphaël Doursenaud		<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2019-2022  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020-2022  Open-Dsi                <support@open-dsi.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/admin/dict.php
 *		\ingroup    setup
 *		\brief      Page to administer data tables
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array("errors", "admin", "main", "companies", "resource", "holiday", "accountancy", "hrm", "orders", "contracts", "projects", "propal", "bills", "interventions", "ticket"));

$action = GETPOST('action', 'alpha') ?GETPOST('action', 'alpha') : 'view';
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'alpha');
$entity = GETPOST('entity', 'int');
$code = GETPOST('code', 'alpha');


const DICT_FORME_JURIDIQUE = 1;
const DICT_DEPARTEMENTS = 2;
const DICT_REGIONS = 3;
const DICT_COUNTRY = 4;
const DICT_CIVILITY = 5;
const DICT_ACTIONCOMM = 6;
const DICT_CHARGESOCIALES = 7;
const DICT_TYPENT = 8;
const DICT_CURRENCIES = 9;
const DICT_TVA = 10;
const DICT_TYPE_CONTACT = 11;
const DICT_PAYMENT_TERM = 12;
const DICT_PAIEMENT = 13;
const DICT_ECOTAXE = 14;
const DICT_PAPER_FORMAT = 15;
const DICT_PROSPECTLEVEL = 16;
const DICT_TYPE_FEES = 17;
const DICT_SHIPMENT_MODE = 18;
const DICT_EFFECTIF = 19;
const DICT_INPUT_METHOD = 20;
const DICT_AVAILABILITY = 21;
const DICT_INPUT_REASON = 22;
const DICT_REVENUESTAMP = 23;
const DICT_TYPE_RESOURCE = 24;
const DICT_TYPE_CONTAINER = 25;
const DICT_STCOMM = 27;
const DICT_HOLIDAY_TYPES = 28;
const DICT_LEAD_STATUS = 29;
const DICT_FORMAT_CARDS = 30;
const DICT_HRM_PUBLIC_HOLIDAY = 32;
const DICT_HRM_DEPARTMENT = 33;
const DICT_HRM_FUNCTION = 34;
const DICT_EXP_TAX_CAT = 35;
const DICT_EXP_TAX_RANGE = 36;
const DICT_UNITS = 37;
const DICT_SOCIALNETWORKS = 38;
const DICT_PROSPECTCONTACTLEVEL = 39;
const DICT_STCOMMCONTACT = 40;
const DICT_TRANSPORT_MODE = 41;
const DICT_PRODUCT_NATURE = 42;
const DICT_PRODUCTBATCH_QCSTATUS = 43;
const DICT_ASSET_DISPOSAL_TYPE = 44;

$allowed = $user->admin;
if ($id == DICT_CHARGESOCIALES && $user->hasRight('accounting', 'chartofaccount')) {
	$allowed = 1; // Tax page allowed to manager of chart account
}
if ($id == DICT_TVA && $user->hasRight('accounting', 'chartofaccount')) {
	$allowed = 1; // Vat page allowed to manager of chart account
}
if ($id == DICT_TYPE_FEES && $user->hasRight('accounting', 'chartofaccount')) {
	$allowed = 1; // Dictionary with type of expense report and accounting account allowed to manager of chart account
}
if (!$allowed) {
	accessforbidden();
}

$acts = array(); $actl = array();
$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"');

$listoffset = GETPOST('listoffset');
$listlimit = GETPOST('listlimit') > 0 ?GETPOST('listlimit') : 1000; // To avoid too long dictionaries
$active = 1;

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $listlimit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_country_id = GETPOST('search_country_id', 'int');
if (!GETPOSTISSET('search_country_id') && $search_country_id == '' && ($id == DICT_DEPARTEMENTS || $id == DICT_REGIONS || $id == DICT_TVA)) {	// Not a so good idea to force on current country for all dictionaries. Some tables have entries that are for all countries, we must be able to see them, so this is done for dedicated dictionaries only.
	$search_country_id = $mysoc->country_id;
}
$search_code = GETPOST('search_code', 'alpha');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admin', 'dictionaryadmin'));

// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Sort order to show dictionary (0 is space). All other dictionaries (added by modules) will be at end of this.
$taborder = array(
    DICT_CURRENCIES,
    DICT_PAPER_FORMAT,
    DICT_FORMAT_CARDS,
    0,
    DICT_COUNTRY,
    DICT_REGIONS,
    DICT_DEPARTEMENTS,
    0,
    DICT_FORME_JURIDIQUE,
    DICT_TYPENT,
    DICT_EFFECTIF,
    DICT_PROSPECTLEVEL,
    DICT_PROSPECTCONTACTLEVEL,
    DICT_STCOMM,
    DICT_STCOMMCONTACT,
    DICT_SOCIALNETWORKS,
    0,
    DICT_CIVILITY,
    DICT_TYPE_CONTACT,
    0,
    DICT_ACTIONCOMM,
    DICT_TYPE_RESOURCE,
    0,
    DICT_LEAD_STATUS,
    0,
    DICT_HRM_DEPARTMENT,
    DICT_HRM_FUNCTION,
    DICT_HRM_PUBLIC_HOLIDAY,
    DICT_HOLIDAY_TYPES,
    DICT_TYPE_FEES,
    DICT_EXP_TAX_CAT,
    DICT_EXP_TAX_RANGE,
    0,
    DICT_TVA,
    DICT_REVENUESTAMP,
    DICT_PAYMENT_TERM,
    DICT_PAIEMENT,
    DICT_CHARGESOCIALES,
    0,
    DICT_ECOTAXE,
    0,
    DICT_INPUT_REASON,
    DICT_INPUT_METHOD,
    DICT_SHIPMENT_MODE,
    DICT_AVAILABILITY,
    DICT_TRANSPORT_MODE,
    0,
    DICT_UNITS,
    DICT_PRODUCT_NATURE,
    0,
    DICT_PRODUCTBATCH_QCSTATUS,
    0,
    DICT_TYPE_CONTAINER,
    0,
    DICT_ASSET_DISPOSAL_TYPE,
    0
);

// Name of SQL tables of dictionaries
$tabname = array();
$tabname[DICT_FORME_JURIDIQUE] = "c_forme_juridique";
$tabname[DICT_DEPARTEMENTS] = "c_departements";
$tabname[DICT_REGIONS] = "c_regions";
$tabname[DICT_COUNTRY] = "c_country";
$tabname[DICT_CIVILITY] = "c_civility";
$tabname[DICT_ACTIONCOMM] = "c_actioncomm";
$tabname[DICT_CHARGESOCIALES] = "c_chargesociales";
$tabname[DICT_TYPENT] = "c_typent";
$tabname[DICT_CURRENCIES] = "c_currencies";
$tabname[DICT_TVA] = "c_tva";
$tabname[DICT_TYPE_CONTACT] = "c_type_contact";
$tabname[DICT_PAYMENT_TERM] = "c_payment_term";
$tabname[DICT_PAIEMENT] = "c_paiement";
$tabname[DICT_ECOTAXE] = "c_ecotaxe";
$tabname[DICT_PAPER_FORMAT] = "c_paper_format";
$tabname[DICT_PROSPECTLEVEL] = "c_prospectlevel";
$tabname[DICT_TYPE_FEES] = "c_type_fees";
$tabname[DICT_SHIPMENT_MODE] = "c_shipment_mode";
$tabname[DICT_EFFECTIF] = "c_effectif";
$tabname[DICT_INPUT_METHOD] = "c_input_method";
$tabname[DICT_AVAILABILITY] = "c_availability";
$tabname[DICT_INPUT_REASON] = "c_input_reason";
$tabname[DICT_REVENUESTAMP] = "c_revenuestamp";
$tabname[DICT_TYPE_RESOURCE] = "c_type_resource";
$tabname[DICT_TYPE_CONTAINER] = "c_type_container";
//$tabname[26]= "c_units";
$tabname[DICT_STCOMM] = "c_stcomm";
$tabname[DICT_HOLIDAY_TYPES] = "c_holiday_types";
$tabname[DICT_LEAD_STATUS] = "c_lead_status";
$tabname[DICT_FORMAT_CARDS] = "c_format_cards";
//$tabname[31]= "accounting_system";
$tabname[DICT_HRM_PUBLIC_HOLIDAY] = "c_hrm_public_holiday";
$tabname[DICT_HRM_DEPARTMENT] = "c_hrm_department";
$tabname[DICT_HRM_FUNCTION] = "c_hrm_function";
$tabname[DICT_EXP_TAX_CAT] = "c_exp_tax_cat";
$tabname[DICT_EXP_TAX_RANGE] = "c_exp_tax_range";
$tabname[DICT_UNITS] = "c_units";
$tabname[DICT_SOCIALNETWORKS] = "c_socialnetworks";
$tabname[DICT_PROSPECTCONTACTLEVEL] = "c_prospectcontactlevel";
$tabname[DICT_STCOMMCONTACT] = "c_stcommcontact";
$tabname[DICT_TRANSPORT_MODE] = "c_transport_mode";
$tabname[DICT_PRODUCT_NATURE] = "c_product_nature";
$tabname[DICT_PRODUCTBATCH_QCSTATUS] = "c_productbatch_qcstatus";
$tabname[DICT_ASSET_DISPOSAL_TYPE] = "c_asset_disposal_type";

// Dictionary labels
$tablib = array();
$tablib[DICT_FORME_JURIDIQUE] = "DictionaryCompanyJuridicalType";
$tablib[DICT_DEPARTEMENTS] = "DictionaryCanton";
$tablib[DICT_REGIONS] = "DictionaryRegion";
$tablib[DICT_COUNTRY] = "DictionaryCountry";
$tablib[DICT_CIVILITY] = "DictionaryCivility";
$tablib[DICT_ACTIONCOMM] = "DictionaryActions";
$tablib[DICT_CHARGESOCIALES] = "DictionarySocialContributions";
$tablib[DICT_TYPENT] = "DictionaryCompanyType";
$tablib[DICT_CURRENCIES] = "DictionaryCurrency";
$tablib[DICT_TVA] = "DictionaryVAT";
$tablib[DICT_TYPE_CONTACT] = "DictionaryTypeContact";
$tablib[DICT_PAYMENT_TERM] = "DictionaryPaymentConditions";
$tablib[DICT_PAIEMENT] = "DictionaryPaymentModes";
$tablib[DICT_ECOTAXE] = "DictionaryEcotaxe";
$tablib[DICT_PAPER_FORMAT] = "DictionaryPaperFormat";
$tablib[DICT_PROSPECTLEVEL] = "DictionaryProspectLevel";
$tablib[DICT_TYPE_FEES] = "DictionaryFees";
$tablib[DICT_SHIPMENT_MODE] = "DictionarySendingMethods";
$tablib[DICT_EFFECTIF] = "DictionaryStaff";
$tablib[DICT_INPUT_METHOD] = "DictionaryOrderMethods";
$tablib[DICT_AVAILABILITY] = "DictionaryAvailability";
$tablib[DICT_INPUT_REASON] = "DictionarySource";
$tablib[DICT_REVENUESTAMP] = "DictionaryRevenueStamp";
$tablib[DICT_TYPE_RESOURCE] = "DictionaryResourceType";
$tablib[DICT_TYPE_CONTAINER] = "DictionaryTypeOfContainer";
//$tablib[26]= "DictionaryUnits";
$tablib[DICT_STCOMM] = "DictionaryProspectStatus";
$tablib[DICT_HOLIDAY_TYPES] = "DictionaryHolidayTypes";
$tablib[DICT_LEAD_STATUS] = "DictionaryOpportunityStatus";
$tablib[DICT_FORMAT_CARDS] = "DictionaryFormatCards";
//$tablib[31]= "DictionaryAccountancysystem";
$tablib[DICT_HRM_PUBLIC_HOLIDAY] = "DictionaryPublicHolidays";
$tablib[DICT_HRM_DEPARTMENT] = "DictionaryDepartment";
$tablib[DICT_HRM_FUNCTION] = "DictionaryFunction";
$tablib[DICT_EXP_TAX_CAT] = "DictionaryExpenseTaxCat";
$tablib[DICT_EXP_TAX_RANGE] = "DictionaryExpenseTaxRange";
$tablib[DICT_UNITS] = "DictionaryMeasuringUnits";
$tablib[DICT_SOCIALNETWORKS] = "DictionarySocialNetworks";
$tablib[DICT_PROSPECTCONTACTLEVEL] = "DictionaryProspectContactLevel";
$tablib[DICT_STCOMMCONTACT] = "DictionaryProspectContactStatus";
$tablib[DICT_TRANSPORT_MODE] = "DictionaryTransportMode";
$tablib[DICT_PRODUCT_NATURE] = "DictionaryProductNature";
$tablib[DICT_PRODUCTBATCH_QCSTATUS] = "DictionaryBatchStatus";
$tablib[DICT_ASSET_DISPOSAL_TYPE] = "DictionaryAssetDisposalType";

// Requests to extract data
$tabsql = array();
$tabsql[DICT_FORME_JURIDIQUE] = "SELECT f.rowid as rowid, f.code, f.libelle, c.code as country_code, c.label as country, f.active FROM ".MAIN_DB_PREFIX."c_forme_juridique as f, ".MAIN_DB_PREFIX."c_country as c WHERE f.fk_pays=c.rowid";
$tabsql[DICT_DEPARTEMENTS] = "SELECT d.rowid as rowid, d.code_departement as code, d.nom as libelle, d.fk_region as region_id, r.nom as region, c.code as country_code, c.label as country, d.active FROM ".MAIN_DB_PREFIX."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_country as c WHERE d.fk_region=r.code_region and r.fk_pays=c.rowid and r.active=1 and c.active=1";
$tabsql[DICT_REGIONS] = "SELECT r.rowid as rowid, r.code_region as state_code, r.nom as libelle, r.fk_pays as country_id, c.code as country_code, c.label as country, r.active FROM ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_country as c WHERE r.fk_pays=c.rowid and c.active=1";
$tabsql[DICT_COUNTRY] = "SELECT c.rowid as rowid, c.code, c.label, c.active, c.favorite, c.eec FROM ".MAIN_DB_PREFIX."c_country AS c";
$tabsql[DICT_CIVILITY] = "SELECT c.rowid as rowid, c.code as code, c.label, c.active FROM ".MAIN_DB_PREFIX."c_civility AS c";
$tabsql[DICT_ACTIONCOMM] = "SELECT a.id    as rowid, a.code as code, a.libelle AS libelle, a.type, a.active, a.module, a.color, a.position FROM ".MAIN_DB_PREFIX."c_actioncomm AS a";
$tabsql[DICT_CHARGESOCIALES] = "SELECT a.id    as rowid, a.code as code, a.libelle AS libelle, a.accountancy_code as accountancy_code, c.code as country_code, c.label as country, a.fk_pays as country_id, a.active FROM ".MAIN_DB_PREFIX."c_chargesociales AS a, ".MAIN_DB_PREFIX."c_country as c WHERE a.fk_pays=c.rowid and c.active=1";
$tabsql[DICT_TYPENT] = "SELECT t.id	 as rowid, t.code as code, t.libelle, t.fk_country as country_id, c.code as country_code, c.label as country, t.position, t.active FROM ".MAIN_DB_PREFIX."c_typent as t LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON t.fk_country=c.rowid";
$tabsql[DICT_CURRENCIES] = "SELECT c.code_iso as code, c.label, c.unicode, c.active FROM ".MAIN_DB_PREFIX."c_currencies AS c";
$tabsql[DICT_TVA] = "SELECT t.rowid, t.code, t.taux, t.localtax1_type, t.localtax1, t.localtax2_type, t.localtax2, c.label as country, c.code as country_code, t.fk_pays as country_id, t.recuperableonly, t.note, t.active, t.accountancy_code_sell, t.accountancy_code_buy FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c WHERE t.fk_pays=c.rowid";
$tabsql[DICT_TYPE_CONTACT] = "SELECT t.rowid as rowid, t.element, t.source, t.code, t.libelle, t.position, t.active FROM ".MAIN_DB_PREFIX."c_type_contact AS t";
$tabsql[DICT_PAYMENT_TERM] = "SELECT c.rowid as rowid, c.code, c.libelle, c.libelle_facture, c.deposit_percent, c.nbjour, c.type_cdr, c.decalage, c.active, c.sortorder, c.entity FROM ".MAIN_DB_PREFIX."c_payment_term AS c WHERE c.entity = ".getEntity($tabname[DICT_PAYMENT_TERM]);
$tabsql[DICT_PAIEMENT] = "SELECT c.id    as rowid, c.code, c.libelle, c.type, c.active, c.entity FROM ".MAIN_DB_PREFIX."c_paiement AS c WHERE c.entity = ".getEntity($tabname[DICT_PAIEMENT]);
$tabsql[DICT_ECOTAXE] = "SELECT e.rowid as rowid, e.code as code, e.label, e.price, e.organization, e.fk_pays as country_id, c.code as country_code, c.label as country, e.active FROM ".MAIN_DB_PREFIX."c_ecotaxe AS e, ".MAIN_DB_PREFIX."c_country as c WHERE e.fk_pays=c.rowid and c.active=1";
$tabsql[DICT_PAPER_FORMAT] = "SELECT rowid   as rowid, code, label as libelle, width, height, unit, active FROM ".MAIN_DB_PREFIX."c_paper_format";
$tabsql[DICT_PROSPECTLEVEL] = "SELECT code, label as libelle, sortorder, active FROM ".MAIN_DB_PREFIX."c_prospectlevel";
$tabsql[DICT_TYPE_FEES] = "SELECT id      as rowid, code, label, accountancy_code, active FROM ".MAIN_DB_PREFIX."c_type_fees";
$tabsql[DICT_SHIPMENT_MODE] = "SELECT rowid   as rowid, code, libelle, tracking, active FROM ".MAIN_DB_PREFIX."c_shipment_mode";
$tabsql[DICT_EFFECTIF] = "SELECT id      as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_effectif";
$tabsql[DICT_INPUT_METHOD] = "SELECT rowid   as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_input_method";
$tabsql[DICT_AVAILABILITY] = "SELECT c.rowid as rowid, c.code, c.label, c.type_duration, c.qty, c.active, c.position FROM ".MAIN_DB_PREFIX."c_availability AS c";
$tabsql[DICT_INPUT_REASON] = "SELECT rowid   as rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_input_reason";
$tabsql[DICT_REVENUESTAMP] = "SELECT t.rowid as rowid, t.taux, t.revenuestamp_type, c.label as country, c.code as country_code, t.fk_pays as country_id, t.note, t.active, t.accountancy_code_sell, t.accountancy_code_buy FROM ".MAIN_DB_PREFIX."c_revenuestamp as t, ".MAIN_DB_PREFIX."c_country as c WHERE t.fk_pays=c.rowid";
$tabsql[DICT_TYPE_RESOURCE] = "SELECT rowid   as rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_type_resource";
$tabsql[DICT_TYPE_CONTAINER] = "SELECT rowid   as rowid, code, label, active, module FROM ".MAIN_DB_PREFIX."c_type_container as t WHERE t.entity = ".getEntity($tabname[DICT_TYPE_CONTAINER]);
//$tabsql[26]= "SELECT rowid   as rowid, code, label, short_label, active FROM ".MAIN_DB_PREFIX."c_units";
$tabsql[DICT_STCOMM] = "SELECT id      as rowid, code, libelle, picto, active FROM ".MAIN_DB_PREFIX."c_stcomm";
$tabsql[DICT_HOLIDAY_TYPES] = "SELECT h.rowid as rowid, h.code, h.label, h.affect, h.delay, h.newbymonth, h.fk_country as country_id, c.code as country_code, c.label as country, h.block_if_negative, h.sortorder, h.active FROM ".MAIN_DB_PREFIX."c_holiday_types as h LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON h.fk_country=c.rowid";
$tabsql[DICT_LEAD_STATUS] = "SELECT rowid   as rowid, code, label, percent, position, active FROM ".MAIN_DB_PREFIX."c_lead_status";
$tabsql[DICT_FORMAT_CARDS] = "SELECT rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active FROM ".MAIN_DB_PREFIX."c_format_cards";
//$tabsql[31]= "SELECT s.rowid as rowid, pcg_version, s.label, s.active FROM ".MAIN_DB_PREFIX."accounting_system as s";
$tabsql[DICT_HRM_PUBLIC_HOLIDAY] = "SELECT a.id    as rowid, a.entity, a.code, a.fk_country as country_id, c.code as country_code, c.label as country, a.dayrule, a.day, a.month, a.year, a.active FROM ".MAIN_DB_PREFIX."c_hrm_public_holiday as a LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON a.fk_country=c.rowid AND c.active=1";
$tabsql[DICT_HRM_DEPARTMENT] = "SELECT rowid, pos, code, label, active FROM ".MAIN_DB_PREFIX."c_hrm_department";
$tabsql[DICT_HRM_FUNCTION] = "SELECT rowid, pos, code, label, c_level, active FROM ".MAIN_DB_PREFIX."c_hrm_function";
$tabsql[DICT_EXP_TAX_CAT] = "SELECT c.rowid, c.label, c.active, c.entity FROM ".MAIN_DB_PREFIX."c_exp_tax_cat c";
$tabsql[DICT_EXP_TAX_RANGE] = "SELECT r.rowid, r.fk_c_exp_tax_cat, r.range_ik, r.active, r.entity FROM ".MAIN_DB_PREFIX."c_exp_tax_range r";
$tabsql[DICT_UNITS] = "SELECT r.rowid, r.code, r.sortorder, r.label, r.short_label, r.unit_type, r.scale, r.active FROM ".MAIN_DB_PREFIX."c_units r";
$tabsql[DICT_SOCIALNETWORKS] = "SELECT s.rowid, s.entity, s.code, s.label, s.url, s.icon, s.active FROM ".MAIN_DB_PREFIX."c_socialnetworks as s WHERE s.entity = ".getEntity($tabname[DICT_SOCIALNETWORKS]);
$tabsql[DICT_PROSPECTCONTACTLEVEL] = "SELECT code, label as libelle, sortorder, active FROM ".MAIN_DB_PREFIX."c_prospectcontactlevel";
$tabsql[DICT_STCOMMCONTACT] = "SELECT id      as rowid, code, libelle, picto, active FROM ".MAIN_DB_PREFIX."c_stcommcontact";
$tabsql[DICT_TRANSPORT_MODE] = "SELECT rowid as rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_transport_mode";
$tabsql[DICT_PRODUCT_NATURE] = "SELECT rowid as rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_product_nature";
$tabsql[DICT_PRODUCTBATCH_QCSTATUS] = "SELECT rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_productbatch_qcstatus";
$tabsql[DICT_ASSET_DISPOSAL_TYPE] = "SELECT rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_asset_disposal_type";

// Criteria to sort dictionaries
$tabsqlsort = array();
$tabsqlsort[DICT_FORME_JURIDIQUE] = "country ASC, code ASC";
$tabsqlsort[DICT_DEPARTEMENTS] = "country ASC, code ASC";
$tabsqlsort[DICT_REGIONS] = "country ASC, code ASC";
$tabsqlsort[DICT_COUNTRY] = "code ASC";
$tabsqlsort[DICT_CIVILITY] = "label ASC";
$tabsqlsort[DICT_ACTIONCOMM] = "a.type ASC, a.module ASC, a.position ASC, a.code ASC";
$tabsqlsort[DICT_CHARGESOCIALES] = "c.label ASC, a.code ASC, a.libelle ASC";
$tabsqlsort[DICT_TYPENT] = "country DESC,".(!empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? ' t.position ASC,' : '')." libelle ASC";
$tabsqlsort[DICT_CURRENCIES] = "label ASC";
$tabsqlsort[DICT_TVA] = "country ASC, code ASC, taux ASC, recuperableonly ASC, localtax1 ASC, localtax2 ASC";
$tabsqlsort[DICT_TYPE_CONTACT] = "t.element ASC, t.source ASC, t.position ASC, t.code ASC";
$tabsqlsort[DICT_PAYMENT_TERM] = "sortorder ASC, code ASC";
$tabsqlsort[DICT_PAIEMENT] = "code ASC";
$tabsqlsort[DICT_ECOTAXE] = "country ASC, e.organization ASC, code ASC";
$tabsqlsort[DICT_PAPER_FORMAT] = "rowid ASC";
$tabsqlsort[DICT_PROSPECTLEVEL] = "sortorder ASC";
$tabsqlsort[DICT_TYPE_FEES] = "code ASC";
$tabsqlsort[DICT_SHIPMENT_MODE] = "code ASC, libelle ASC";
$tabsqlsort[DICT_EFFECTIF] = "id ASC";
$tabsqlsort[DICT_INPUT_METHOD] = "code ASC, libelle ASC";
$tabsqlsort[DICT_AVAILABILITY] = "position ASC, type_duration ASC, qty ASC";
$tabsqlsort[DICT_INPUT_REASON] = "code ASC, label ASC";
$tabsqlsort[DICT_REVENUESTAMP] = "country ASC, taux ASC";
$tabsqlsort[DICT_TYPE_RESOURCE] = "code ASC, label ASC";
$tabsqlsort[DICT_TYPE_CONTAINER] = "t.module ASC, t.code ASC, t.label ASC";
//$tabsqlsort[26]="code ASC";
$tabsqlsort[DICT_STCOMM] = "code ASC";
$tabsqlsort[DICT_HOLIDAY_TYPES] = "sortorder ASC, country ASC, code ASC";
$tabsqlsort[DICT_LEAD_STATUS] = "position ASC";
$tabsqlsort[DICT_FORMAT_CARDS] = "code ASC";
//$tabsqlsort[31]="pcg_version ASC";
$tabsqlsort[DICT_HRM_PUBLIC_HOLIDAY] = "country, year ASC, month ASC, day ASC";
$tabsqlsort[DICT_HRM_DEPARTMENT] = "code ASC";
$tabsqlsort[DICT_HRM_FUNCTION] = "code ASC";
$tabsqlsort[DICT_EXP_TAX_CAT] = "c.label ASC";
$tabsqlsort[DICT_EXP_TAX_RANGE] = "r.fk_c_exp_tax_cat ASC, r.range_ik ASC";
$tabsqlsort[DICT_UNITS] = "sortorder ASC";
$tabsqlsort[DICT_SOCIALNETWORKS] = "rowid, code ASC";
$tabsqlsort[DICT_PROSPECTCONTACTLEVEL] = "sortorder ASC";
$tabsqlsort[DICT_STCOMMCONTACT] = "code ASC";
$tabsqlsort[DICT_TRANSPORT_MODE] = "code ASC";
$tabsqlsort[DICT_PRODUCT_NATURE] = "code ASC";
$tabsqlsort[DICT_PRODUCTBATCH_QCSTATUS] = "code ASC";
$tabsqlsort[DICT_ASSET_DISPOSAL_TYPE] = "code ASC";

// Field names in select result for dictionary display
$tabfield = array();
$tabfield[DICT_FORME_JURIDIQUE] = "code,libelle,country";
$tabfield[DICT_DEPARTEMENTS] = "code,libelle,region_id,region,country"; // "code,libelle,region,country_code-country"
$tabfield[DICT_REGIONS] = "code,libelle,country_id,country";
$tabfield[DICT_COUNTRY] = "code,label";
$tabfield[DICT_CIVILITY] = "code,label";
$tabfield[DICT_ACTIONCOMM] = "code,libelle,type,color,position";
$tabfield[DICT_CHARGESOCIALES] = "code,libelle,country,accountancy_code";
$tabfield[DICT_TYPENT] = "code,libelle,country_id,country".(!empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? ',position' : '');
$tabfield[DICT_CURRENCIES] = "code,label,unicode";
$tabfield[DICT_TVA] = "country_id,country,code,taux,localtax1_type,localtax1,localtax2_type,localtax2,recuperableonly,accountancy_code_sell,accountancy_code_buy,note";
$tabfield[DICT_TYPE_CONTACT] = "element,source,code,libelle,position";
$tabfield[DICT_PAYMENT_TERM] = "code,libelle,libelle_facture,deposit_percent,nbjour,type_cdr,decalage,sortorder,entity";
$tabfield[DICT_PAIEMENT] = "code,libelle,type,entity";
$tabfield[DICT_ECOTAXE] = "code,label,price,organization,country";
$tabfield[DICT_PAPER_FORMAT] = "code,libelle,width,height,unit";
$tabfield[DICT_PROSPECTLEVEL] = "code,libelle,sortorder";
$tabfield[DICT_TYPE_FEES] = "code,label,accountancy_code";
$tabfield[DICT_SHIPMENT_MODE] = "code,libelle,tracking";
$tabfield[DICT_EFFECTIF] = "code,libelle";
$tabfield[DICT_INPUT_METHOD] = "code,libelle";
$tabfield[DICT_AVAILABILITY] = "code,label,qty,type_duration,position";
$tabfield[DICT_INPUT_REASON] = "code,label";
$tabfield[DICT_REVENUESTAMP] = "country_id,country,taux,revenuestamp_type,accountancy_code_sell,accountancy_code_buy,note";
$tabfield[DICT_TYPE_RESOURCE] = "code,label";
$tabfield[DICT_TYPE_CONTAINER] = "code,label";
//$tabfield[26]= "code,label,short_label";
$tabfield[DICT_STCOMM] = "code,libelle,picto";
$tabfield[DICT_HOLIDAY_TYPES] = "code,label,affect,delay,newbymonth,country_id,country,block_if_negative,sortorder";
$tabfield[DICT_LEAD_STATUS] = "code,label,percent,position";
$tabfield[DICT_FORMAT_CARDS] = "code,name,paper_size,orientation,metric,leftmargin,topmargin,nx,ny,spacex,spacey,width,height,font_size,custom_x,custom_y";
//$tabfield[31]= "pcg_version,label";
$tabfield[DICT_HRM_PUBLIC_HOLIDAY] = "code,dayrule,year,month,day,country_id,country";
$tabfield[DICT_HRM_DEPARTMENT] = "code,label";
$tabfield[DICT_HRM_FUNCTION] = "code,label";
$tabfield[DICT_EXP_TAX_CAT] = "label";
$tabfield[DICT_EXP_TAX_RANGE] = "range_ik,fk_c_exp_tax_cat";
$tabfield[DICT_UNITS] = "code,label,short_label,unit_type,scale,sortorder";
$tabfield[DICT_SOCIALNETWORKS] = "code,label,url,icon,entity";
$tabfield[DICT_PROSPECTCONTACTLEVEL] = "code,libelle,sortorder";
$tabfield[DICT_STCOMMCONTACT] = "code,libelle,picto";
$tabfield[DICT_TRANSPORT_MODE] = "code,label";
$tabfield[DICT_PRODUCT_NATURE] = "code,label";
$tabfield[DICT_PRODUCTBATCH_QCSTATUS] = "code,label";
$tabfield[DICT_ASSET_DISPOSAL_TYPE] = "code,label";

// Edit field names for editing a record
$tabfieldvalue = array();
$tabfieldvalue[DICT_FORME_JURIDIQUE] = "code,libelle,country";
$tabfieldvalue[DICT_DEPARTEMENTS] = "code,libelle,region"; // "code,libelle,region"
$tabfieldvalue[DICT_REGIONS] = "code,libelle,country";
$tabfieldvalue[DICT_COUNTRY] = "code,label";
$tabfieldvalue[DICT_CIVILITY] = "code,label";
$tabfieldvalue[DICT_ACTIONCOMM] = "code,libelle,type,color,position";
$tabfieldvalue[DICT_CHARGESOCIALES] = "code,libelle,country,accountancy_code";
$tabfieldvalue[DICT_TYPENT] = "code,libelle,country".(!empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? ',position' : '');
$tabfieldvalue[DICT_CURRENCIES] = "code,label,unicode";
$tabfieldvalue[DICT_TVA] = "country,code,taux,localtax1_type,localtax1,localtax2_type,localtax2,recuperableonly,accountancy_code_sell,accountancy_code_buy,note";
$tabfieldvalue[DICT_TYPE_CONTACT] = "element,source,code,libelle,position";
$tabfieldvalue[DICT_PAYMENT_TERM] = "code,libelle,libelle_facture,deposit_percent,nbjour,type_cdr,decalage,sortorder";
$tabfieldvalue[DICT_PAIEMENT] = "code,libelle,type";
$tabfieldvalue[DICT_ECOTAXE] = "code,label,price,organization,country";
$tabfieldvalue[DICT_PAPER_FORMAT] = "code,libelle,width,height,unit";
$tabfieldvalue[DICT_PROSPECTLEVEL] = "code,libelle,sortorder";
$tabfieldvalue[DICT_TYPE_FEES] = "code,label,accountancy_code";
$tabfieldvalue[DICT_SHIPMENT_MODE] = "code,libelle,tracking";
$tabfieldvalue[DICT_EFFECTIF] = "code,libelle";
$tabfieldvalue[DICT_INPUT_METHOD] = "code,libelle";
$tabfieldvalue[DICT_AVAILABILITY] = "code,label,qty,type_duration,position";
$tabfieldvalue[DICT_INPUT_REASON] = "code,label";
$tabfieldvalue[DICT_REVENUESTAMP] = "country,taux,revenuestamp_type,accountancy_code_sell,accountancy_code_buy,note";
$tabfieldvalue[DICT_TYPE_RESOURCE] = "code,label";
$tabfieldvalue[DICT_TYPE_CONTAINER] = "code,label";
//$tabfieldvalue[26]= "code,label,short_label";
$tabfieldvalue[DICT_STCOMM] = "code,libelle,picto";
$tabfieldvalue[DICT_HOLIDAY_TYPES] = "code,label,affect,delay,newbymonth,country,block_if_negative,sortorder";
$tabfieldvalue[DICT_LEAD_STATUS] = "code,label,percent,position";
$tabfieldvalue[DICT_FORMAT_CARDS] = "code,name,paper_size,orientation,metric,leftmargin,topmargin,nx,ny,spacex,spacey,width,height,font_size,custom_x,custom_y";
//$tabfieldvalue[31]= "pcg_version,label";
$tabfieldvalue[DICT_HRM_PUBLIC_HOLIDAY] = "code,dayrule,day,month,year,country";
$tabfieldvalue[DICT_HRM_DEPARTMENT] = "code,label";
$tabfieldvalue[DICT_HRM_FUNCTION] = "code,label";
$tabfieldvalue[DICT_EXP_TAX_CAT] = "label";
$tabfieldvalue[DICT_EXP_TAX_RANGE] = "range_ik,fk_c_exp_tax_cat";
$tabfieldvalue[DICT_UNITS] = "code,label,short_label,unit_type,scale,sortorder";
$tabfieldvalue[DICT_SOCIALNETWORKS] = "code,label,url,icon";
$tabfieldvalue[DICT_PROSPECTCONTACTLEVEL] = "code,libelle,sortorder";
$tabfieldvalue[DICT_STCOMMCONTACT] = "code,libelle,picto";
$tabfieldvalue[DICT_TRANSPORT_MODE] = "code,label";
$tabfieldvalue[DICT_PRODUCT_NATURE] = "code,label";
$tabfieldvalue[DICT_PRODUCTBATCH_QCSTATUS] = "code,label";
$tabfieldvalue[DICT_ASSET_DISPOSAL_TYPE] = "code,label";

// Field names in the table for inserting a record
$tabfieldinsert = array();
$tabfieldinsert[DICT_FORME_JURIDIQUE] = "code,libelle,fk_pays";
$tabfieldinsert[DICT_DEPARTEMENTS] = "code_departement,nom,fk_region";
$tabfieldinsert[DICT_REGIONS] = "code_region,nom,fk_pays";
$tabfieldinsert[DICT_COUNTRY] = "code,label";
$tabfieldinsert[DICT_CIVILITY] = "code,label";
$tabfieldinsert[DICT_ACTIONCOMM] = "code,libelle,type,color,position";
$tabfieldinsert[DICT_CHARGESOCIALES] = "code,libelle,fk_pays,accountancy_code";
$tabfieldinsert[DICT_TYPENT] = "code,libelle,fk_country".(!empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? ',position' : '');
$tabfieldinsert[DICT_CURRENCIES] = "code_iso,label,unicode";
$tabfieldinsert[DICT_TVA] = "fk_pays,code,taux,localtax1_type,localtax1,localtax2_type,localtax2,recuperableonly,accountancy_code_sell,accountancy_code_buy,note";
$tabfieldinsert[DICT_TYPE_CONTACT] = "element,source,code,libelle,position";
$tabfieldinsert[DICT_PAYMENT_TERM] = "code,libelle,libelle_facture,deposit_percent,nbjour,type_cdr,decalage,sortorder,entity";
$tabfieldinsert[DICT_PAIEMENT] = "code,libelle,type,entity";
$tabfieldinsert[DICT_ECOTAXE] = "code,label,price,organization,fk_pays";
$tabfieldinsert[DICT_PAPER_FORMAT] = "code,label,width,height,unit";
$tabfieldinsert[DICT_PROSPECTLEVEL] = "code,label,sortorder";
$tabfieldinsert[DICT_TYPE_FEES] = "code,label,accountancy_code";
$tabfieldinsert[DICT_SHIPMENT_MODE] = "code,libelle,tracking";
$tabfieldinsert[DICT_EFFECTIF] = "code,libelle";
$tabfieldinsert[DICT_INPUT_METHOD] = "code,libelle";
$tabfieldinsert[DICT_AVAILABILITY] = "code,label,qty,type_duration,position";
$tabfieldinsert[DICT_INPUT_REASON] = "code,label";
$tabfieldinsert[DICT_REVENUESTAMP] = "fk_pays,taux,revenuestamp_type,accountancy_code_sell,accountancy_code_buy,note";
$tabfieldinsert[DICT_TYPE_RESOURCE] = "code,label";
$tabfieldinsert[DICT_TYPE_CONTAINER] = "code,label";
//$tabfieldinsert[26]= "code,label,short_label";
$tabfieldinsert[DICT_STCOMM] = "code,libelle,picto";
$tabfieldinsert[DICT_HOLIDAY_TYPES] = "code,label,affect,delay,newbymonth,fk_country,block_if_negative,sortorder";
$tabfieldinsert[DICT_LEAD_STATUS] = "code,label,percent,position";
$tabfieldinsert[DICT_FORMAT_CARDS] = "code,name,paper_size,orientation,metric,leftmargin,topmargin,nx,ny,spacex,spacey,width,height,font_size,custom_x,custom_y";
//$tabfieldinsert[31]= "pcg_version,label";
//$tabfieldinsert[DICT_HRM_PUBLIC_HOLIDAY]= "code,label,range_account,sens,category_type,formula,position,fk_country";
$tabfieldinsert[DICT_HRM_PUBLIC_HOLIDAY] = "code,dayrule,day,month,year,fk_country";
$tabfieldinsert[DICT_HRM_DEPARTMENT] = "code,label";
$tabfieldinsert[DICT_HRM_FUNCTION] = "code,label";
$tabfieldinsert[DICT_EXP_TAX_CAT] = "label";
$tabfieldinsert[DICT_EXP_TAX_RANGE] = "range_ik,fk_c_exp_tax_cat";
$tabfieldinsert[DICT_UNITS] = "code,label,short_label,unit_type,scale,sortorder";
$tabfieldinsert[DICT_SOCIALNETWORKS] = "code,label,url,icon,entity";
$tabfieldinsert[DICT_PROSPECTCONTACTLEVEL] = "code,label,sortorder";
$tabfieldinsert[DICT_STCOMMCONTACT] = "code,libelle,picto";
$tabfieldinsert[DICT_TRANSPORT_MODE] = "code,label";
$tabfieldinsert[DICT_PRODUCT_NATURE] = "code,label";
$tabfieldinsert[DICT_PRODUCTBATCH_QCSTATUS] = "code,label";
$tabfieldinsert[DICT_ASSET_DISPOSAL_TYPE] = "code,label";

// Rowid name of field depending if field is autoincrement on or off..
// Use "" if id field is "rowid" and has autoincrement on
// Use "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid = array();
$tabrowid[DICT_FORME_JURIDIQUE] = "";
$tabrowid[DICT_DEPARTEMENTS] = "";
$tabrowid[DICT_REGIONS] = "";
$tabrowid[DICT_COUNTRY] = "rowid";
$tabrowid[DICT_CIVILITY] = "rowid";
$tabrowid[DICT_ACTIONCOMM] = "id";
$tabrowid[DICT_CHARGESOCIALES] = "id";
$tabrowid[DICT_TYPENT] = "id";
$tabrowid[DICT_CURRENCIES] = "code_iso";
$tabrowid[DICT_TVA] = "";
$tabrowid[DICT_TYPE_CONTACT] = "rowid";
$tabrowid[DICT_PAYMENT_TERM] = "";
$tabrowid[DICT_PAIEMENT] = "id";
$tabrowid[DICT_ECOTAXE] = "";
$tabrowid[DICT_PAPER_FORMAT] = "";
$tabrowid[DICT_PROSPECTLEVEL] = "code";
$tabrowid[DICT_TYPE_FEES] = "id";
$tabrowid[DICT_SHIPMENT_MODE] = "rowid";
$tabrowid[DICT_EFFECTIF] = "id";
$tabrowid[DICT_INPUT_METHOD] = "";
$tabrowid[DICT_AVAILABILITY] = "rowid";
$tabrowid[DICT_INPUT_REASON] = "rowid";
$tabrowid[DICT_REVENUESTAMP] = "";
$tabrowid[DICT_TYPE_RESOURCE] = "";
$tabrowid[DICT_TYPE_CONTAINER] = "";
//$tabrowid[26]= "";
$tabrowid[DICT_STCOMM] = "id";
$tabrowid[DICT_HOLIDAY_TYPES] = "";
$tabrowid[DICT_LEAD_STATUS] = "";
$tabrowid[DICT_FORMAT_CARDS] = "";
//$tabrowid[31]= "";
$tabrowid[DICT_HRM_PUBLIC_HOLIDAY] = "id";
$tabrowid[DICT_HRM_DEPARTMENT] = "rowid";
$tabrowid[DICT_HRM_FUNCTION] = "rowid";
$tabrowid[DICT_EXP_TAX_CAT] = "";
$tabrowid[DICT_EXP_TAX_RANGE] = "";
$tabrowid[DICT_UNITS] = "";
$tabrowid[DICT_SOCIALNETWORKS] = "";
$tabrowid[DICT_PROSPECTCONTACTLEVEL] = "code";
$tabrowid[DICT_STCOMMCONTACT] = "id";
$tabrowid[DICT_TRANSPORT_MODE] = "";
$tabrowid[DICT_PRODUCT_NATURE] = "rowid";
$tabrowid[DICT_PRODUCTBATCH_QCSTATUS] = "rowid";
$tabrowid[DICT_ASSET_DISPOSAL_TYPE] = "rowid";

// Condition to show dictionary in setup page
$tabcond = array();
$tabcond[DICT_FORME_JURIDIQUE] = (isModEnabled("societe"));
$tabcond[DICT_DEPARTEMENTS] = true;
$tabcond[DICT_REGIONS] = true;
$tabcond[DICT_COUNTRY] = true;
$tabcond[DICT_CIVILITY] = (isModEnabled("societe") || isModEnabled('adherent'));
$tabcond[DICT_ACTIONCOMM] = isModEnabled('agenda');
$tabcond[DICT_CHARGESOCIALES] = isModEnabled('tax');
$tabcond[DICT_TYPENT] = isModEnabled("societe");
$tabcond[DICT_CURRENCIES] = true;
$tabcond[DICT_TVA] = true;
$tabcond[DICT_TYPE_CONTACT] = (isModEnabled("societe"));
$tabcond[DICT_PAYMENT_TERM] = (isModEnabled('commande') || isModEnabled("propal") || isModEnabled('facture') || (isModEnabled("fournisseur") && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || isModEnabled("supplier_invoice") || isModEnabled("supplier_order"));
$tabcond[DICT_PAIEMENT] = (isModEnabled('commande') || isModEnabled("propal") || isModEnabled('facture') || (isModEnabled("fournisseur") && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || isModEnabled("supplier_invoice") || isModEnabled("supplier_order"));
$tabcond[DICT_ECOTAXE] = (isModEnabled("product") && (isModEnabled('ecotax') || !empty($conf->global->MAIN_SHOW_ECOTAX_DICTIONNARY)));
$tabcond[DICT_PAPER_FORMAT] = true;
$tabcond[DICT_PROSPECTLEVEL] = (isModEnabled("societe") && empty($conf->global->SOCIETE_DISABLE_PROSPECTS));
$tabcond[DICT_TYPE_FEES] = (isModEnabled('deplacement') || isModEnabled('expensereport'));
$tabcond[DICT_SHIPMENT_MODE] = isModEnabled("expedition") || isModEnabled("reception");
$tabcond[DICT_EFFECTIF] = isModEnabled("societe");
$tabcond[DICT_INPUT_METHOD] = (isModEnabled("fournisseur") && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || isModEnabled("supplier_order");
$tabcond[DICT_AVAILABILITY] = isModEnabled("propal");
$tabcond[DICT_INPUT_REASON] = (isModEnabled('commande') || isModEnabled("propal"));
$tabcond[DICT_REVENUESTAMP] = true;
$tabcond[DICT_TYPE_RESOURCE] = isModEnabled('resource');
$tabcond[DICT_TYPE_CONTAINER] = isModEnabled('website');
//$tabcond[26]= isModEnabled("product");
$tabcond[DICT_STCOMM] = isModEnabled("societe");
$tabcond[DICT_HOLIDAY_TYPES] = isModEnabled('holiday');
$tabcond[DICT_LEAD_STATUS] = isModEnabled('project');
$tabcond[DICT_FORMAT_CARDS] = (isModEnabled('label') || isModEnabled('barcode') || isModEnabled('adherent'));	// stickers format dictionary
//$tabcond[31]= isModEnabled('accounting');
$tabcond[DICT_HRM_PUBLIC_HOLIDAY] = (isModEnabled('holiday') || isModEnabled('hrm'));
$tabcond[DICT_HRM_DEPARTMENT] = isModEnabled('hrm');
$tabcond[DICT_HRM_FUNCTION] = isModEnabled('hrm');
$tabcond[DICT_EXP_TAX_CAT] = isModEnabled('expensereport') && !empty($conf->global->MAIN_USE_EXPENSE_IK);
$tabcond[DICT_EXP_TAX_RANGE] = isModEnabled('expensereport') && !empty($conf->global->MAIN_USE_EXPENSE_IK);
$tabcond[DICT_UNITS] = isModEnabled("product");
$tabcond[DICT_SOCIALNETWORKS] = isModEnabled('socialnetworks');
$tabcond[DICT_PROSPECTCONTACTLEVEL] = (isModEnabled("societe") && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && !empty($conf->global->THIRDPARTY_ENABLE_PROSPECTION_ON_ALTERNATIVE_ADRESSES));
$tabcond[DICT_STCOMMCONTACT] = (isModEnabled("societe") && !empty($conf->global->THIRDPARTY_ENABLE_PROSPECTION_ON_ALTERNATIVE_ADRESSES));
$tabcond[DICT_TRANSPORT_MODE] = isModEnabled('intracommreport');
$tabcond[DICT_PRODUCT_NATURE] = isModEnabled("product");
$tabcond[DICT_PRODUCTBATCH_QCSTATUS] = isModEnabled("product") && isModEnabled('productbatch') && $conf->global->MAIN_FEATURES_LEVEL >= 2;
$tabcond[DICT_ASSET_DISPOSAL_TYPE] = isModEnabled('asset');

// List of help for fields (no more used, help is defined into tabcomplete)
$tabhelp = array();

// Table to store complete informations (will replace all other table). Key is table name.
$tabcomplete = array(
	'c_forme_juridique'=>array('picto'=>'company', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_departements'=>array('picto'=>'state', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_regions'=>array('picto'=>'region', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_country'=>array('picto'=>'country', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_civility'=>array('picto'=>'contact', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_actioncomm'=>array('picto'=>'action', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'color'=>$langs->trans("ColorFormat"), 'position'=>$langs->trans("PositionIntoComboList"))),
	'c_chargesociales'=>array('picto'=>'bill', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_typent'=>array('picto'=>'company', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'position'=>$langs->trans("PositionIntoComboList"))),
	'c_currencies'=>array('picto'=>'multicurrency', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'unicode'=>$langs->trans("UnicodeCurrency"))),
	'c_tva'=>array('picto'=>'bill', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'taux'=>$langs->trans("SellTaxRate"), 'recuperableonly'=>$langs->trans("RecuperableOnly"), 'localtax1_type'=>$langs->trans("LocalTaxDesc"), 'localtax2_type'=>$langs->trans("LocalTaxDesc"))),
	'c_type_contact'=>array('picto'=>'contact', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'position'=>$langs->trans("PositionIntoComboList"))),
	'c_payment_term'=>array('picto'=>'bill', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'type_cdr'=>$langs->trans("TypeCdr", $langs->transnoentitiesnoconv("NbOfDays"), $langs->transnoentitiesnoconv("Offset"), $langs->transnoentitiesnoconv("NbOfDays"), $langs->transnoentitiesnoconv("Offset")))),
	'c_paiement'=>array('picto'=>'bill', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_ecotaxe'=>array('picto'=>'bill', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_paper_format'=>array('picto'=>'generic', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_prospectlevel'=>array('picto'=>'company', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_type_fees'=>array('picto'=>'trip', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_shipment_mode'=>array('picto'=>'shipment', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'tracking'=>$langs->trans("UrlTrackingDesc"))),
	'c_effectif'=>array('picto'=>'company', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_input_method'=>array('picto'=>'order', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_input_reason'=>array('picto'=>'order', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'position'=>$langs->trans("PositionIntoComboList"))),
	'c_availability'=>array('picto'=>'shipment', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_revenuestamp'=>array('picto'=>'bill', 'help'=>array('revenuestamp_type'=>$langs->trans('FixedOrPercent'))),
	'c_type_resource'=>array('picto'=>'resource', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_type_container'=>array('picto'=>'website', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_stcomm'=>array('picto'=>'company', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'picto'=>$langs->trans("PictoHelp"))),
	'c_holiday_types'=>array('picto'=>'holiday', 'help'=>array('affect'=>$langs->trans("FollowedByACounter"), 'delay'=>$langs->trans("MinimumNoticePeriod"), 'newbymonth'=>$langs->trans("NbAddedAutomatically"))),
	'c_lead_status'=>array('picto'=>'project', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'percent'=>$langs->trans("OpportunityPercent"), 'position'=>$langs->trans("PositionIntoComboList"))),
	'c_format_cards'=>array('picto'=>'generic', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'name'=>$langs->trans("LabelName"), 'paper_size'=>$langs->trans("LabelPaperSize"))),
	'c_hrm_public_holiday'=>array('picto'=>'holiday', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'dayrule'=>"Keep empty for a date defined with month and day (most common case).<br>Use a keyword like 'easter', 'eastermonday', ... for a date predefined by complex rules.", 'country'=>$langs->trans("CountryIfSpecificToOneCountry"), 'year'=>$langs->trans("ZeroMeansEveryYear"))),
	'c_hrm_department'=>array('picto'=>'hrm', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_hrm_function'=>array('picto'=>'hrm', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_exp_tax_cat'=>array('picto'=>'expensereport', 'help'=>array()),
	'c_exp_tax_range'=>array('picto'=>'expensereport', 'help'=>array('range_ik'=>$langs->trans('PrevRangeToThisRange'))),
	'c_units'=>array('picto'=>'product', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'unit_type' => $langs->trans('Measuringtype_durationDesc'), 'scale' => $langs->trans('MeasuringScaleDesc'))),
	'c_socialnetworks'=>array('picto'=>'share-alt', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'url' => $langs->trans('UrlSocialNetworksDesc'), 'icon' => $langs->trans('FafaIconSocialNetworksDesc'))),
	'c_prospectcontactlevel'=>array('picto'=>'company', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_stcommcontact'=>array('picto'=>'company', 'help'=>array('code'=>$langs->trans("EnterAnyCode"), 'picto'=>$langs->trans("PictoHelp"))),
	'c_transport_mode'=>array('picto'=>'incoterm', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_product_nature'=>array('picto'=>'product', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_productbatch_qcstatus'=>array('picto'=>'lot', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
	'c_asset_disposal_type'=>array('picto'=>'asset', 'help'=>array('code'=>$langs->trans("EnterAnyCode"))),
);


// Complete all arrays with entries found into modules
complete_dictionary_with_modules($taborder, $tabname, $tablib, $tabsql, $tabsqlsort, $tabfield, $tabfieldvalue, $tabfieldinsert, $tabrowid, $tabcond, $tabhelp, $tabcomplete);

// Complete the table $tabcomplete
$i = 0;
foreach ($tabcomplete as $key => $value) {
	$i++;
	// When a dictionnary is commented
	if (!isset($tabcond[$i])) {
		continue;
	}
	$tabcomplete[$key]['id'] = $i;
	$tabcomplete[$key]['cond'] = $tabcond[$i];
	$tabcomplete[$key]['rowid'] = $tabrowid[$i];
	$tabcomplete[$key]['fieldinsert'] = $tabfieldinsert[$i];
	$tabcomplete[$key]['fieldvalue'] = $tabfieldvalue[$i];
	$tabcomplete[$key]['lib'] = $tablib[$i];
	$tabcomplete[$key]['sql'] = $tabsql[$i];
	$tabcomplete[$key]['sqlsort'] = $tabsqlsort[$i];
	$tabcomplete[$key]['field'] = $tabfield[$i];
}

$keytable = '';
if ($id > 0) {
	$arrayofkeys = array_keys($tabcomplete);
	$keytable = $arrayofkeys[$id - 1];
}

// Defaut sortorder
if (empty($sortfield)) {
	$tmp1 = explode(',', empty($tabcomplete[$keytable]['sqlsort']) ? '' : $tabcomplete[$keytable]['sqlsort']);
	$tmp2 = explode(' ', $tmp1[0]);
	$sortfield = preg_replace('/^.*\./', '', $tmp2[0]);
	$sortorder = (!empty($tmp2[1]) ? $tmp2[1] : '');
	//var_dump($sortfield);var_dump($sortorder);
}


// Define elementList and sourceList (used for dictionary type of contacts "llx_c_type_contact")
$elementList = array();
$sourceList = array();
if ($id == DICT_TYPE_CONTACT) {
	$elementList = array(
		'' => '',
		'agenda' => img_picto('', 'action', 'class="pictofixedwidth"').$langs->trans('Agenda'),
		'dolresource' => img_picto('', 'resource', 'class="pictofixedwidth"').$langs->trans('Resource'),
		'societe' => img_picto('', 'company', 'class="pictofixedwidth"').$langs->trans('ThirdParty'),
		// 'proposal' => $langs->trans('Proposal'),
		// 'order' => $langs->trans('Order'),
		// 'invoice' => $langs->trans('Bill'),
		// 'intervention' => $langs->trans('InterventionCard'),
		// 'contract' => $langs->trans('Contract'),
		'project' => img_picto('', 'project', 'class="pictofixedwidth"').$langs->trans('Project'),
		'project_task' => img_picto('', 'projecttask', 'class="pictofixedwidth"').$langs->trans('Task'),
		'propal' => img_picto('', 'propal', 'class="pictofixedwidth"').$langs->trans('Proposal'),
		'commande' => img_picto('', 'order', 'class="pictofixedwidth"').$langs->trans('Order'),
		'facture' => img_picto('', 'bill', 'class="pictofixedwidth"').$langs->trans('Bill'),
		'fichinter' => img_picto('', 'intervention', 'class="pictofixedwidth"').$langs->trans('InterventionCard'),
		'contrat' => img_picto('', 'contract', 'class="pictofixedwidth"').$langs->trans('Contract'),
		'ticket' => img_picto('', 'ticket', 'class="pictofixedwidth"').$langs->trans('Ticket'),
		'supplier_proposal' => img_picto('', 'supplier_proposal', 'class="pictofixedwidth"').$langs->trans('SupplierProposal'),
		'order_supplier' => img_picto('', 'supplier_order', 'class="pictofixedwidth"').$langs->trans('SupplierOrder'),
		'invoice_supplier' => img_picto('', 'supplier_invoice', 'class="pictofixedwidth"').$langs->trans('SupplierBill'),
	);
	if (!empty($conf->global->MAIN_FEATURES_LEVEL) && $conf->global->MAIN_FEATURES_LEVEL >= 2) {
		$elementList['conferenceorbooth'] = img_picto('', 'eventorganization', 'class="pictofixedwidth"').$langs->trans('ConferenceOrBooth');
	}

	complete_elementList_with_modules($elementList);

	//asort($elementList);
	$sourceList = array(
		'internal' => $langs->trans('Internal'),
		'external' => $langs->trans('External')
	);
}

// Define localtax_typeList (used for dictionary "llx_c_tva")
$localtax_typeList = array(
	"0" => $langs->trans("No"),
	"1" => $langs->trans("Yes").' ('.$langs->trans("Type")." 1)", //$langs->trans("%ageOnAllWithoutVAT"),
	"2" => $langs->trans("Yes").' ('.$langs->trans("Type")." 2)", //$langs->trans("%ageOnAllBeforeVAT"),
	"3" => $langs->trans("Yes").' ('.$langs->trans("Type")." 3)", //$langs->trans("%ageOnProductsWithoutVAT"),
	"4" => $langs->trans("Yes").' ('.$langs->trans("Type")." 4)", //$langs->trans("%ageOnProductsBeforeVAT"),
	"5" => $langs->trans("Yes").' ('.$langs->trans("Type")." 5)", //$langs->trans("%ageOnServiceWithoutVAT"),
	"6" => $langs->trans("Yes").' ('.$langs->trans("Type")." 6)"	//$langs->trans("%ageOnServiceBeforeVAT"),
);


/*
 * Actions
 */

$object = new stdClass();
$parameters = array(
	'id'			=>$id,
	'rowid'			=>$rowid,
	'code'			=>$code,
	'confirm'		=>$confirm,
	'entity'		=>$entity,
	'taborder'		=>$taborder,
	'tabname'		=>$tabname,
	'tablib'		=>$tablib,
	'tabsql'		=>$tabsql,
	'tabsqlsort'	=>$tabsqlsort,
	'tabfield'		=>$tabfield,
	'tabfieldvalue'	=>$tabfieldvalue,
	'tabfieldinsert'=>$tabfieldinsert,
	'tabrowid'		=>$tabrowid,
	'tabcond'		=>$tabcond,
	'tabhelp'		=>$tabhelp,
	'tabcomplete'	=>$tabcomplete
);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha')) {
	$search_country_id = '';
	$search_code = '';
}

if (empty($reshook)) {
	// Actions add or modify an entry into a dictionary
	if (GETPOST('actionadd') || GETPOST('actionmodify')) {
		$listfield = explode(',', str_replace(' ', '', $tabfield[$id]));
		$listfieldinsert = explode(',', $tabfieldinsert[$id]);
		$listfieldmodify = explode(',', $tabfieldinsert[$id]);
		$listfieldvalue = explode(',', $tabfieldvalue[$id]);

		// Check that all mandatory fields are filled
		$ok = 1;
		foreach ($listfield as $f => $value) {
			// Discard check of mandatory fields for country for some tables
			if ($value == 'country_id' && in_array($tablib[$id], array('DictionaryPublicHolidays', 'DictionaryVAT', 'DictionaryRegion', 'DictionaryCompanyType', 'DictionaryHolidayTypes', 'DictionaryRevenueStamp', 'DictionaryAccountancysystem', 'DictionaryAccountancyCategory'))) {
				continue; // For some pages, country is not mandatory
			}
			if ($value == 'country' && in_array($tablib[$id], array('DictionaryPublicHolidays', 'DictionaryCanton', 'DictionaryCompanyType', 'DictionaryHolidayTypes', 'DictionaryRevenueStamp'))) {
				continue; // For some pages, country is not mandatory
			}
			// Discard check of mandatory fiedls for other fields
			if ($value == 'localtax1' && !GETPOST('localtax1_type')) {
				continue;
			}
			if ($value == 'localtax2' && !GETPOST('localtax2_type')) {
				continue;
			}
			if ($value == 'color' && !GETPOST('color')) {
				continue;
			}
			if ($value == 'formula' && !GETPOST('formula')) {
				continue;
			}
			if ($value == 'dayrule' && !GETPOST('dayrule')) {
				continue;
			}
			if ($value == 'sortorder') {
				continue; // For a column name 'sortorder', we use the field name 'position'
			}
			if ((!GETPOSTISSET($value) || GETPOST($value) == '')
					&& (!in_array($value, array('decalage', 'module', 'accountancy_code', 'accountancy_code_sell', 'accountancy_code_buy', 'tracking', 'picto', 'deposit_percent'))  // Fields that are not mandatory
					&& ($id != 10 || ($value != 'code' && $value != 'note')) // Field code and note is not mandatory for dictionary table 10
					)
				) {
					$ok = 0;
					$fieldnamekey = $value;
					// We take translate key of field
				if ($fieldnamekey == 'libelle' || ($fieldnamekey == 'label')) {
					$fieldnamekey = 'Label';
				}
				if ($fieldnamekey == 'libelle_facture') {
					$fieldnamekey = 'LabelOnDocuments';
				}
				if ($fieldnamekey == 'deposit_percent') {
					$fieldnamekey = 'DepositPercent';
				}
				if ($fieldnamekey == 'nbjour') {
					$fieldnamekey = 'NbOfDays';
				}
				if ($fieldnamekey == 'decalage') {
					$fieldnamekey = 'Offset';
				}
				if ($fieldnamekey == 'module') {
					$fieldnamekey = 'Module';
				}
				if ($fieldnamekey == 'code') {
					$fieldnamekey = 'Code';
				}
				if ($fieldnamekey == 'note') {
					$fieldnamekey = 'Note';
				}
				if ($fieldnamekey == 'taux') {
					$fieldnamekey = 'Rate';
				}
				if ($fieldnamekey == 'type') {
					$fieldnamekey = 'Type';
				}
				if ($fieldnamekey == 'position') {
					$fieldnamekey = 'Position';
				}
				if ($fieldnamekey == 'unicode') {
					$fieldnamekey = 'Unicode';
				}
				if ($fieldnamekey == 'deductible') {
					$fieldnamekey = 'Deductible';
				}
				if ($fieldnamekey == 'sortorder') {
					$fieldnamekey = 'SortOrder';
				}
				if ($fieldnamekey == 'category_type') {
					$fieldnamekey = 'Calculated';
				}
				if ($fieldnamekey == 'revenuestamp_type') {
					$fieldnamekey = 'TypeOfRevenueStamp';
				}
				if ($fieldnamekey == 'use_default') {
					$fieldnamekey = 'UseByDefault';
				}

				setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
			}
		}
		// Other checks
		if (GETPOST('actionadd') && $tabname[$id] == "c_actioncomm" && GETPOSTISSET("type") && in_array(GETPOST("type"), array('system', 'systemauto'))) {
			$ok = 0;
			setEventMessages($langs->transnoentities('ErrorReservedTypeSystemSystemAuto'), null, 'errors');
		}
		if (GETPOSTISSET("code")) {
			if (GETPOST("code") == '0') {
				$ok = 0;
				setEventMessages($langs->transnoentities('ErrorCodeCantContainZero'), null, 'errors');
			}
		}
		if (GETPOSTISSET("country") && (GETPOST("country") == '0') && ($id != DICT_DEPARTEMENTS)) {
			if (in_array($tablib[$id], array('DictionaryCompanyType', 'DictionaryHolidayTypes'))) {	// Field country is no mandatory for such dictionaries
				$_POST["country"] = '';
			} else {
				$ok = 0;
				setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities("Country")), null, 'errors');
			}
		}
		if (($id == DICT_REGIONS || $id == DICT_PRODUCT_NATURE) && !is_numeric(GETPOST("code"))) {
			$ok = 0;
			setEventMessages($langs->transnoentities("ErrorFieldMustBeANumeric", $langs->transnoentities("Code")), null, 'errors');
		}

		// Clean some parameters
		if ((GETPOST("localtax1_type") || (GETPOST('localtax1_type') == '0')) && !GETPOST("localtax1")) {
			$_POST["localtax1"] = '0'; // If empty, we force to 0
		}
		if ((GETPOST("localtax2_type") || (GETPOST('localtax2_type') == '0')) && !GETPOST("localtax2")) {
			$_POST["localtax2"] = '0'; // If empty, we force to 0
		}
		if (GETPOST("accountancy_code") <= 0) {
			$_POST["accountancy_code"] = ''; // If empty, we force to null
		}
		if (GETPOST("accountancy_code_sell") <= 0) {
			$_POST["accountancy_code_sell"] = ''; // If empty, we force to null
		}
		if (GETPOST("accountancy_code_buy") <= 0) {
			$_POST["accountancy_code_buy"] = ''; // If empty, we force to null
		}
		if ($id == DICT_TVA && GETPOSTISSET("code")) {  // Spaces are not allowed into code for tax dictionary
			$_POST["code"] = preg_replace('/[^a-zA-Z0-9_\-\+]/', '', GETPOST("code"));
		}

		$tablename = $tabname[$id];
		$tablename = preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename);

		// If check ok and action add, add the line
		if ($ok && GETPOST('actionadd')) {
			if ($tabrowid[$id]) {
				// Get free id for insert
				$newid = 0;
				$sql = "SELECT MAX(".$tabrowid[$id].") as newid FROM ".MAIN_DB_PREFIX.$tablename;
				$result = $db->query($sql);
				if ($result) {
					$obj = $db->fetch_object($result);
					$newid = ($obj->newid + 1);
				} else {
					dol_print_error($db);
				}
			}

			// Add new entry
			$sql = "INSERT INTO ".MAIN_DB_PREFIX.$tablename." (";
			// List of fields
			if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
				$sql .= $tabrowid[$id].",";
			}
			$sql .= $tabfieldinsert[$id];
			$sql .= ",active)";
			$sql .= " VALUES(";

			// List of values
			if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
				$sql .= $newid.",";
			}
			$i = 0;
			foreach ($listfieldinsert as $f => $value) {
				$keycode = $listfieldvalue[$i];
				if (empty($keycode)) {
					$keycode = $value;
				}

				if ($value == 'price' || preg_match('/^amount/i', $value)) {
					$_POST[$keycode] = price2num(GETPOST($keycode), 'MU');
				} elseif ($value == 'taux' || $value == 'localtax1') {
					$_POST[$keycode] = price2num(GETPOST($keycode), 8);	// Note that localtax2 can be a list of rates separated by coma like X:Y:Z
				} elseif ($value == 'entity') {
					$_POST[$keycode] = getEntity($tablename);
				}

				if ($i) {
					$sql .= ",";
				}

				if ($keycode == 'sortorder') {		// For column name 'sortorder', we use the field name 'position'
					$sql .= (int) GETPOST('position', 'int');
				} elseif (GETPOST($keycode) == '' && !($keycode == 'code' && $id == DICT_TVA)) {
					$sql .= "null"; // For vat, we want/accept code = ''
				} elseif ($keycode == 'content') {
					$sql .= "'".$db->escape(GETPOST($keycode, 'restricthtml'))."'";
				} elseif (in_array($keycode, array('joinfile', 'private', 'pos', 'position', 'scale', 'use_default'))) {
					$sql .= (int) GETPOST($keycode, 'int');
				} else {
					$sql .= "'".$db->escape(GETPOST($keycode, 'alphanohtml'))."'";
				}

				$i++;
			}
			$sql .= ",1)";

			dol_syslog("actionadd", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql) {	// Add is ok
				setEventMessages($langs->transnoentities("RecordCreatedSuccessfully"), null, 'mesgs');

				// Clean $_POST array, we keep only id of dictionary
				if ($id == DICT_TVA && GETPOST('country', 'int') > 0) {
					$search_country_id = GETPOST('country', 'int');
				}
				$_POST = array('id'=>$id);
			} else {
				if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
				} else {
					dol_print_error($db);
				}
			}
		}

		// If verif ok and action modify, modify the line
		if ($ok && GETPOST('actionmodify')) {
			if ($tabrowid[$id]) {
				$rowidcol = $tabrowid[$id];
			} else {
				$rowidcol = "rowid";
			}

			// Modify entry
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET ";
			// Modifie valeur des champs
			if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldmodify)) {
				$sql .= $tabrowid[$id]."=";
				$sql .= "'".$db->escape($rowid)."', ";
			}
			$i = 0;
			foreach ($listfieldmodify as $field) {
				$keycode = $listfieldvalue[$i];
				if (empty($keycode)) {
					$keycode = $field;
				}

				if ($field == 'price' || preg_match('/^amount/i', $field)) {
					$_POST[$keycode] = price2num(GETPOST($keycode), 'MU');
				} elseif ($field == 'taux' || $field == 'localtax1') {
					$_POST[$keycode] = price2num(GETPOST($keycode), 8);	// Note that localtax2 can be a list of rates separated by coma like X:Y:Z
				} elseif ($field == 'entity') {
					$_POST[$keycode] = getEntity($tablename);
				}

				if ($i) {
					$sql .= ",";
				}
				$sql .= $field."=";
				if ($listfieldvalue[$i] == 'sortorder') {		// For column name 'sortorder', we use the field name 'position'
					$sql .= (int) GETPOST('position', 'int');
				} elseif (GETPOST($keycode) == '' && !($keycode == 'code' && $id == DICT_TVA)) {
					$sql .= "null"; // For vat, we want/accept code = ''
				} elseif ($keycode == 'content') {
					$sql .= "'".$db->escape(GETPOST($keycode, 'restricthtml'))."'";
				} elseif (in_array($keycode, array('joinfile', 'private', 'pos', 'position', 'scale', 'use_default'))) {
					$sql .= (int) GETPOST($keycode, 'int');
				} else {
					$sql .= "'".$db->escape(GETPOST($keycode, 'alphanohtml'))."'";
				}

				$i++;
			}
			if (in_array($rowidcol, array('code', 'code_iso'))) {
				$sql .= " WHERE ".$rowidcol." = '".$db->escape($rowid)."'";
			} else {
				$sql .= " WHERE ".$rowidcol." = ".((int) $rowid);
			}
			if (in_array('entity', $listfieldmodify)) {
				$sql .= " AND entity = ".((int) getEntity($tablename, 0));
			}

			dol_syslog("actionmodify", LOG_DEBUG);
			//print $sql;
			$resql = $db->query($sql);
			if (!$resql) {
				setEventMessages($db->error(), null, 'errors');
			}
		}
	}

	if ($action == 'confirm_delete' && $confirm == 'yes') {       // delete
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		$tablename = $tabname[$id];
		$tablename = preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$tablename." WHERE ".$rowidcol." = '".$db->escape($rowid)."'".($entity != '' ? " AND entity = ".(int) $entity : '');

		dol_syslog("delete", LOG_DEBUG);
		$result = $db->query($sql);
		if (!$result) {
			if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
				setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
			} else {
				dol_print_error($db);
			}
		}
	}

	// activate
	if ($action == $acts[0]) {
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		$tablename = $tabname[$id];
		$tablename = preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename);

		if ($rowid) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET active = 1 WHERE ".$rowidcol." = '".$db->escape($rowid)."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		} elseif ($code) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET active = 1 WHERE code = '".$db->escape(dol_escape_htmltag($code))."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		}

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}

	// disable
	if ($action == $acts[1]) {
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		$tablename = $tabname[$id];
		$tablename = preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename);

		if ($rowid) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET active = 0 WHERE ".$rowidcol." = '".$db->escape($rowid)."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		} elseif ($code) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET active = 0 WHERE code = '".$db->escape(dol_escape_htmltag($code))."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		}

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}

	// favorite
	if ($action == 'activate_favorite') {
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		$tablename = $tabname[$id];
		$tablename = preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename);

		if ($rowid) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET favorite = 1 WHERE ".$rowidcol." = '".$db->escape($rowid)."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		} elseif ($code) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET favorite = 1 WHERE code = '".$db->escape(dol_escape_htmltag($code))."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		}

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}

	// disable favorite
	if ($action == 'disable_favorite') {
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		$tablename = $tabname[$id];
		$tablename = preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename);

		if ($rowid) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET favorite = 0 WHERE ".$rowidcol." = '".$db->escape($rowid)."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		} elseif ($code) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET favorite = 0 WHERE code = '".$db->escape(dol_escape_htmltag($code))."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		}

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}

	// Is in EEC - Activate
	if ($action == 'activate_eec') {
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		$tablename = $tabname[$id];
		$tablename = preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename);

		if ($rowid) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET eec = 1 WHERE ".$rowidcol." = '".$db->escape($rowid)."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		} elseif ($code) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET eec = 1 WHERE code = '".$db->escape(dol_escape_htmltag($code))."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		}

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}

	// Is in EEC - Disable
	if ($action == 'disable_eec') {
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		$tablename = $tabname[$id];
		$tablename = preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename);

		if ($rowid) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET eec = 0 WHERE ".$rowidcol." = '".$db->escape($rowid)."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		} elseif ($code) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$tablename." SET eec = 0 WHERE code = '".$db->escape(dol_escape_htmltag($code))."'".($entity != '' ? " AND entity = ".(int) $entity : '');
		}

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

$title = $langs->trans("DictionarySetup");

llxHeader('', $title);

$linkback = '';
if ($id) {
	$title .= ' - '.$langs->trans($tablib[$id]);
	$linkback = '<a href="'.$_SERVER['PHP_SELF'].'">'.$langs->trans("BackToDictionaryList").'</a>';
}
$titlepicto = 'title_setup';
if ($id == DICT_TVA && GETPOST('from') == 'accountancy') {
	$title = $langs->trans("MenuVatAccounts");
	$titlepicto = 'accountancy';
}
if ($id == DICT_CHARGESOCIALES && GETPOST('from') == 'accountancy') {
	$title = $langs->trans("MenuTaxAccounts");
	$titlepicto = 'accountancy';
}

print load_fiche_titre($title, $linkback, $titlepicto);

if (empty($id)) {
	print '<span class="opacitymedium">'.$langs->trans("DictionaryDesc");
	print " ".$langs->trans("OnlyActiveElementsAreShown")."<br>\n";
	print '</span><br>';
}


$param = '&id='.urlencode($id);
if ($search_country_id > 0) {
	$param .= '&search_country_id='.urlencode($search_country_id);
}
if ($search_code != '') {
	$param .= '&search_code='.urlencode($search_code);
}
if ($entity != '') {
	$param .= '&entity='.(int) $entity;
}
$paramwithsearch = $param;
if ($sortorder) {
	$paramwithsearch .= '&sortorder='.urlencode($sortorder);
}
if ($sortfield) {
	$paramwithsearch .= '&sortfield='.urlencode($sortfield);
}
if (GETPOST('from')) {
	$paramwithsearch .= '&from='.urlencode(GETPOST('from', 'alpha'));
}


// Confirmation of the deletion of the line
if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'rowid='.urlencode($rowid).'&code='.urlencode($code).$paramwithsearch, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}


/*
 * Show a dictionary
 */
if ($id > 0) {
	// Complete search values request with sort criteria
	$sql = $tabsql[$id];

	if (!preg_match('/ WHERE /', $sql)) {
		$sql .= " WHERE 1 = 1";
	}
	if ($search_country_id > 0) {
		$sql .= " AND c.rowid = ".((int) $search_country_id);
	}
	if ($search_code != '' && $id == DICT_CURRENCIES) {
		$sql .= natural_search("code_iso", $search_code);
	} elseif ($search_code != '' && $id == DICT_HOLIDAY_TYPES) {
		$sql .= natural_search("h.code", $search_code);
	} elseif ($search_code != '' && ($id == DICT_CHARGESOCIALES || $id == DICT_HRM_PUBLIC_HOLIDAY)) {
		$sql .= natural_search("a.code", $search_code);
	} elseif ($search_code != '' && $id == DICT_REGIONS) {
		$sql .= natural_search("r.code_region", $search_code);
	} elseif ($search_code != '' && ($id == DICT_TYPENT || $id == DICT_TVA)) {
		$sql .= natural_search("t.code", $search_code);
	} elseif ($search_code != '' && $id == DICT_FORME_JURIDIQUE) {
		$sql .= natural_search("f.code", $search_code);
	} elseif ($search_code != '' && $id == DICT_DEPARTEMENTS) {
		$sql .= natural_search("d.code_departement", $search_code);
	} elseif ($search_code != '' && $id == DICT_ECOTAXE) {
		$sql .= natural_search("e.code", $search_code);
	} elseif ($search_code != '' && $id != DICT_CURRENCIES) {
		$sql .= natural_search("code", $search_code);
	}

	if ($sortfield) {
		// If sort order is "country", we use country_code instead
		if ($sortfield == 'country') {
			$sortfield = 'country_code';
		}
		$sql .= $db->order($sortfield, $sortorder);
		$sql .= ", ";
		// Clear the required sort criteria for the tabsqlsort to be able to force it with selected value
		$tabsqlsort[$id] = preg_replace('/([a-z]+\.)?'.$sortfield.' '.$sortorder.',/i', '', $tabsqlsort[$id]);
		$tabsqlsort[$id] = preg_replace('/([a-z]+\.)?'.$sortfield.',/i', '', $tabsqlsort[$id]);
	} else {
		$sql .= " ORDER BY ";
	}
	$sql .= $tabsqlsort[$id];
	$sql .= $db->plimit($listlimit + 1, $offset);
	//print $sql;

	if (empty($tabfield[$id])) {
		dol_print_error($db, 'The table with id '.$id.' has no array tabfield defined');
		exit;
	}
	$fieldlist = explode(',', $tabfield[$id]);

	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';

	if ($id == DICT_TVA && empty($conf->global->FACTURE_TVAOPTION)) {
		print info_admin($langs->trans("VATIsUsedIsOff", $langs->transnoentities("Setup"), $langs->transnoentities("CompanyFoundation")));
		print "<br>\n";
	}

	// Form to add a new line
	if ($tabname[$id]) {
		$withentity = null;

		$fieldlist = explode(',', $tabfield[$id]);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		// Line for title
		print '<!-- line title to add new entry -->';
		$tdsoffields = '<tr class="liste_titre">';
		foreach ($fieldlist as $field => $value) {
			if ($value == 'entity') {
				$withentity = getEntity($tabname[$id]);
				continue;
			}

			// Define field friendly name from its technical name
			$valuetoshow = ucfirst($value); // Par defaut
			$valuetoshow = $langs->trans($valuetoshow); // try to translate
			$class = '';

			if ($value == 'pos') {
				$valuetoshow = $langs->trans("Position"); $class = 'right';
			}
			if ($value == 'source') {
				$valuetoshow = $langs->trans("Contact");
			}
			if ($value == 'price') {
				$valuetoshow = $langs->trans("PriceUHT");
			}
			if ($value == 'taux') {
				if ($tabname[$id] != "c_revenuestamp") {
					$valuetoshow = $langs->trans("Rate");
				} else {
					$valuetoshow = $langs->trans("Amount");
				}
				$class = 'center';
			}
			if ($value == 'localtax1_type') {
				$valuetoshow = $langs->trans("UseLocalTax")." 2"; $class = "center"; $sortable = 0;
			}
			if ($value == 'localtax1') {
				$valuetoshow = $langs->trans("RateOfTaxN", '2'); $class = "center";
			}
			if ($value == 'localtax2_type') {
				$valuetoshow = $langs->trans("UseLocalTax")." 3"; $class = "center"; $sortable = 0;
			}
			if ($value == 'localtax2') {
				$valuetoshow = $langs->trans("RateOfTaxN", '3'); $class = "center";
			}
			if ($value == 'organization') {
				$valuetoshow = $langs->trans("Organization");
			}
			if ($value == 'lang') {
				$valuetoshow = $langs->trans("Language");
			}
			if ($value == 'type') {
				if ($tabname[$id] == "c_paiement") {
					$valuetoshow = $form->textwithtooltip($langs->trans("Type"), $langs->trans("TypePaymentDesc"), 2, 1, img_help(1, ''));
				} else {
					$valuetoshow = $langs->trans("Type");
				}
			}
			if ($value == 'code') {
				$valuetoshow = $langs->trans("Code"); $class = 'maxwidth100';
			}
			if ($value == 'libelle' || $value == 'label') {
				$valuetoshow = $form->textwithtooltip($langs->trans("Label"), $langs->trans("LabelUsedByDefault"), 2, 1, img_help(1, ''));
			}
			if ($value == 'libelle_facture') {
				$valuetoshow = $form->textwithtooltip($langs->trans("LabelOnDocuments"), $langs->trans("LabelUsedByDefault"), 2, 1, img_help(1, ''));
			}
			if ($value == 'deposit_percent') {
				$valuetoshow = $langs->trans('DepositPercent');
				$class = 'right';
			}
			if ($value == 'country') {
				if (in_array('region_id', $fieldlist)) {
					print '<td>&nbsp;</td>'; continue;
				}		// For region page, we do not show the country input
				$valuetoshow = $langs->trans("Country");
			}
			if ($value == 'recuperableonly') {
				$valuetoshow = $langs->trans("NPR"); $class = "center";
			}
			if ($value == 'nbjour') {
				$valuetoshow = $langs->trans("NbOfDays");
				$class = 'right';
			}
			if ($value == 'type_cdr') {
				$valuetoshow = $langs->trans("AtEndOfMonth"); $class = "center";
			}
			if ($value == 'decalage') {
				$valuetoshow = $langs->trans("Offset");
				$class = 'right';
			}
			if ($value == 'width' || $value == 'nx') {
				$valuetoshow = $langs->trans("Width");
			}
			if ($value == 'height' || $value == 'ny') {
				$valuetoshow = $langs->trans("Height");
			}
			if ($value == 'unit' || $value == 'metric') {
				$valuetoshow = $langs->trans("MeasuringUnit");
			}
			if ($value == 'region_id' || $value == 'country_id') {
				$valuetoshow = '';
			}
			if ($value == 'accountancy_code') {
				$valuetoshow = $langs->trans("AccountancyCode");
			}
			if ($value == 'accountancy_code_sell') {
				$valuetoshow = $langs->trans("AccountancyCodeSell");
			}
			if ($value == 'accountancy_code_buy') {
				$valuetoshow = $langs->trans("AccountancyCodeBuy");
			}
			if ($value == 'pcg_version' || $value == 'fk_pcg_version') {
				$valuetoshow = $langs->trans("Pcg_version");
			}
			if ($value == 'account_parent') {
				$valuetoshow = $langs->trans("Accountparent");
			}
			if ($value == 'pcg_type') {
				$valuetoshow = $langs->trans("Pcg_type");
			}
			if ($value == 'pcg_subtype') {
				$valuetoshow = $langs->trans("Pcg_subtype");
			}
			if ($value == 'sortorder') {
				$valuetoshow = $langs->trans("SortOrder");
				$class = 'center';
			}
			if ($value == 'short_label') {
				$valuetoshow = $langs->trans("ShortLabel");
			}
			if ($value == 'fk_parent') {
				$valuetoshow = $langs->trans("ParentID"); $class = 'center';
			}
			if ($value == 'range_account') {
				$valuetoshow = $langs->trans("Range");
			}
			if ($value == 'sens') {
				$valuetoshow = $langs->trans("Sens");
			}
			if ($value == 'category_type') {
				$valuetoshow = $langs->trans("Calculated");
			}
			if ($value == 'formula') {
				$valuetoshow = $langs->trans("Formula");
			}
			if ($value == 'paper_size') {
				$valuetoshow = $langs->trans("PaperSize");
			}
			if ($value == 'orientation') {
				$valuetoshow = $langs->trans("Orientation");
			}
			if ($value == 'leftmargin') {
				$valuetoshow = $langs->trans("LeftMargin");
			}
			if ($value == 'topmargin') {
				$valuetoshow = $langs->trans("TopMargin");
			}
			if ($value == 'spacex') {
				$valuetoshow = $langs->trans("SpaceX");
			}
			if ($value == 'spacey') {
				$valuetoshow = $langs->trans("SpaceY");
			}
			if ($value == 'font_size') {
				$valuetoshow = $langs->trans("FontSize");
			}
			if ($value == 'custom_x') {
				$valuetoshow = $langs->trans("CustomX");
			}
			if ($value == 'custom_y') {
				$valuetoshow = $langs->trans("CustomY");
			}
			if ($value == 'percent') {
				$valuetoshow = $langs->trans("Percentage");
			}
			if ($value == 'affect') {
				$valuetoshow = $langs->trans("WithCounter");
			}
			if ($value == 'delay') {
				$valuetoshow = $langs->trans("NoticePeriod");
			}
			if ($value == 'newbymonth') {
				$valuetoshow = $langs->trans("NewByMonth");
			}
			if ($value == 'fk_tva') {
				$valuetoshow = $langs->trans("VAT");
			}
			if ($value == 'range_ik') {
				$valuetoshow = $langs->trans("RangeIk");
			}
			if ($value == 'fk_c_exp_tax_cat') {
				$valuetoshow = $langs->trans("CarCategory");
			}
			if ($value == 'revenuestamp_type') {
				$valuetoshow = $langs->trans('TypeOfRevenueStamp');
			}
			if ($value == 'use_default') {
				$valuetoshow = $langs->trans('Default'); $class = 'center';
			}
			if ($value == 'unit_type') {
				$valuetoshow = $langs->trans('TypeOfUnit');
			}
			if ($value == 'public' && $tablib[$id] == 'TicketDictCategory') {
				$valuetoshow = $langs->trans('TicketGroupIsPublic'); $class = 'center';
			}
			if ($value == 'block_if_negative') {
				$valuetoshow = $langs->trans('BlockHolidayIfNegative');
			}
			if ($value == 'type_duration') {
				$valuetoshow = $langs->trans('Unit');
			}

			if ($id == DICT_DEPARTEMENTS) {	// Special case for state page
				if ($value == 'region_id') {
					$valuetoshow = '&nbsp;'; $showfield = 1;
				}
				if ($value == 'region') {
					$valuetoshow = $langs->trans("Country").'/'.$langs->trans("Region"); $showfield = 1;
				}
			}

			if ($valuetoshow != '') {
				$tooltiphelp = (isset($tabcomplete[$tabname[$id]]['help'][$value]) ? $tabcomplete[$tabname[$id]]['help'][$value] : '');

				$tdsoffields .= '<th'.($class ? ' class="'.$class.'"' : '').'>';
				if ($tooltiphelp && preg_match('/^http(s*):/i', $tooltiphelp)) {
					$tdsoffields .= '<a href="'.$tooltiphelp.'" target="_blank">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
				} elseif ($tooltiphelp) {
					$tdsoffields .= $form->textwithpicto($valuetoshow, $tooltiphelp);
				} else {
					$tdsoffields .= $valuetoshow;
				}
				$tdsoffields .= '</th>';
			}
		}

		if ($id == DICT_COUNTRY) {
			$tdsoffields .= '<th></th>';
			$tdsoffields .= '<th></th>';
		}
		$tdsoffields .= '<th>';
		$tdsoffields .= '<input type="hidden" name="id" value="'.$id.'">';
		if (!is_null($withentity)) {
			$tdsoffields .= '<input type="hidden" name="entity" value="'.$withentity.'">';
		}
		$tdsoffields .= '</th>';
		$tdsoffields .= '<th style="min-width: 26px;"></th>';
		$tdsoffields .= '<th style="min-width: 26px;"></th>';
		$tdsoffields .= '</tr>';

		print $tdsoffields;


		// Line to enter new values
		print '<!-- line input to add new entry -->';
		print '<tr class="oddeven nodrag nodrop nohover">';

		$obj = new stdClass();
		// If data was already input, we define them in obj to populate input fields.
		if (GETPOST('actionadd')) {
			foreach ($fieldlist as $key => $val) {
				if (GETPOST($val) != '') {
					$obj->$val = GETPOST($val);
				}
			}
		}

		$tmpaction = 'create';
		$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
		$reshook = $hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
		$error = $hookmanager->error; $errors = $hookmanager->errors;

		if ($id == DICT_REGIONS) {
			unset($fieldlist[2]); // Remove field ??? if dictionary Regions
		}

		if (empty($reshook)) {
			fieldList($fieldlist, $obj, $tabname[$id], 'add');
		}

		if ($id == DICT_COUNTRY) {
			print '<td></td>';
			print '<td></td>';
		}
		print '<td colspan="3" class="center">';
		if ($action != 'edit') {
			print '<input type="submit" class="button button-add" name="actionadd" value="'.$langs->trans("Add").'">';
		}
		print '</td>';

		print "</tr>";

		print '</table>';
		print '</div>';
	}

	print '</form>';


	print '<br>';


	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';

	// List of available record in database
	dol_syslog("htdocs/admin/dict", LOG_DEBUG);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		// There is several pages
		if ($num > $listlimit || $page) {
			print_fleche_navigation($page, $_SERVER["PHP_SELF"], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>'.$langs->trans("Page").' '.($page + 1).'</span></li>');
			print '<div class="clearboth"></div>';
		}

		print '<div class="div-table-responsive">';
		print '<table class="noborder centpercent">';

		// Title line with search input fields
		print '<!-- line title to search record -->'."\n";
		print '<tr class="liste_titre_filter">';
		$filterfound = 0;
		foreach ($fieldlist as $field => $value) {
			if ($value == 'entity') {
				continue;
			}

			$showfield = 1; // By default

			if ($value == 'region_id' || $value == 'country_id') {
				$showfield = 0;
			}

			if ($showfield) {
				if ($value == 'country') {
					print '<td class="liste_titre">';
					print $form->select_country($search_country_id, 'search_country_id', '', 28, 'minwidth100 maxwidth150 maxwidthonsmartphone');
					print '</td>';
					$filterfound++;
				} elseif ($value == 'code') {
					print '<td class="liste_titre">';
					print '<input type="text" class="maxwidth100" name="search_code" value="'.dol_escape_htmltag($search_code).'">';
					print '</td>';
					$filterfound++;
				} else {
					print '<td class="liste_titre">';
					print '</td>';
				}
			}
		}
		if ($id == DICT_COUNTRY) {
			print '<td></td>';
			print '<td></td>';
		}
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre right" colspan="2">';
		if ($filterfound) {
			$searchpicto = $form->showFilterAndCheckAddButtons(0);
			print $searchpicto;
		}
		print '</td>';
		print '</tr>';

		// Title of lines
		print '<!-- line title of record -->'."\n";
		print '<tr class="liste_titre">';
		foreach ($fieldlist as $field => $value) {
			if ($value == 'entity') {
				continue;
			}

			if (in_array($value, array('label', 'libelle', 'libelle_facture')) && empty($tabcomplete[$tabname[$id]]['help'][$value])) {
				if (!is_array($tabcomplete[$tabname[$id]]['help'])) {	// protection when $tabcomplete[$tabname[$id]]['help'] is a an empty string, we must force it into an array
					$tabcomplete[$tabname[$id]]['help'] = array();
				}
				$tabcomplete[$tabname[$id]]['help'][$value] = $langs->trans('LabelUsedByDefault');
			}

			// Determines the name of the field in relation to the possible names
			// in data dictionaries
			$showfield = 1; // By default
			$cssprefix = '';
			$sortable = 1;
			$valuetoshow = ucfirst($value); // By default
			$valuetoshow = $langs->trans($valuetoshow); // try to translate

			// Special cases
			if ($value == 'source') {
				$valuetoshow = $langs->trans("Contact");
			}
			if ($value == 'price') {
				$valuetoshow = $langs->trans("PriceUHT");
			}
			if ($value == 'taux') {
				if ($tabname[$id] != "c_revenuestamp") {
					$valuetoshow = $langs->trans("Rate");
				} else {
					$valuetoshow = $langs->trans("Amount");
				}
				$cssprefix = 'center ';
			}

			if ($value == 'localtax1_type') {
				$valuetoshow = $langs->trans("UseLocalTax")." 2"; $cssprefix = "center "; $sortable = 0;
			}
			if ($value == 'localtax1') {
				$valuetoshow = $langs->trans("RateOfTaxN", '2'); $cssprefix = "center "; $sortable = 0;
			}
			if ($value == 'localtax2_type') {
				$valuetoshow = $langs->trans("UseLocalTax")." 3"; $cssprefix = "center "; $sortable = 0;
			}
			if ($value == 'localtax2') {
				$valuetoshow = $langs->trans("RateOfTaxN", '3'); $cssprefix = "center "; $sortable = 0;
			}
			if ($value == 'organization') {
				$valuetoshow = $langs->trans("Organization");
			}
			if ($value == 'lang') {
				$valuetoshow = $langs->trans("Language");
			}
			if ($value == 'type') {
				$valuetoshow = $langs->trans("Type");
			}
			if ($value == 'code') {
				$valuetoshow = $langs->trans("Code");
			}
			if (in_array($value, array('pos', 'position'))) {
				$valuetoshow = $langs->trans("Position"); $cssprefix = 'right ';
			}
			if ($value == 'libelle' || $value == 'label') {
				$valuetoshow = $langs->trans("Label");
			}
			if ($value == 'libelle_facture') {
				$valuetoshow = $langs->trans("LabelOnDocuments");
			}
			if ($value == 'deposit_percent') {
				$valuetoshow = $langs->trans('DepositPercent');
				$cssprefix = 'right ';
			}
			if ($value == 'country') {
				$valuetoshow = $langs->trans("Country");
			}
			if ($value == 'recuperableonly') {
				$valuetoshow = $langs->trans("NPR"); $cssprefix = "center ";
			}
			if ($value == 'nbjour') {
				$valuetoshow = $langs->trans("NbOfDays");
				$cssprefix = 'right ';
			}
			if ($value == 'type_cdr') {
				$valuetoshow = $langs->trans("AtEndOfMonth"); $cssprefix = "center ";
			}
			if ($value == 'decalage') {
				$valuetoshow = $langs->trans("Offset");
				$cssprefix = 'right ';
			}
			if ($value == 'width' || $value == 'nx') {
				$valuetoshow = $langs->trans("Width");
			}
			if ($value == 'height' || $value == 'ny') {
				$valuetoshow = $langs->trans("Height");
			}
			if ($value == 'unit' || $value == 'metric') {
				$valuetoshow = $langs->trans("MeasuringUnit");
			}
			if ($value == 'accountancy_code') {
				$valuetoshow = $langs->trans("AccountancyCode");
			}
			if ($value == 'accountancy_code_sell') {
				$valuetoshow = $langs->trans("AccountancyCodeSell"); $sortable = 0;
			}
			if ($value == 'accountancy_code_buy') {
				$valuetoshow = $langs->trans("AccountancyCodeBuy"); $sortable = 0;
			}
			if ($value == 'fk_pcg_version') {
				$valuetoshow = $langs->trans("Pcg_version");
			}
			if ($value == 'account_parent') {
				$valuetoshow = $langs->trans("Accountsparent");
			}
			if ($value == 'pcg_type') {
				$valuetoshow = $langs->trans("Pcg_type");
			}
			if ($value == 'pcg_subtype') {
				$valuetoshow = $langs->trans("Pcg_subtype");
			}
			if ($value == 'sortorder') {
				$valuetoshow = $langs->trans("SortOrder");
				$cssprefix = 'center ';
			}
			if ($value == 'short_label') {
				$valuetoshow = $langs->trans("ShortLabel");
			}
			if ($value == 'fk_parent') {
				$valuetoshow = $langs->trans("ParentID"); $cssprefix = 'center ';
			}
			if ($value == 'range_account') {
				$valuetoshow = $langs->trans("Range");
			}
			if ($value == 'sens') {
				$valuetoshow = $langs->trans("Sens");
			}
			if ($value == 'category_type') {
				$valuetoshow = $langs->trans("Calculated");
			}
			if ($value == 'formula') {
				$valuetoshow = $langs->trans("Formula");
			}
			if ($value == 'paper_size') {
				$valuetoshow = $langs->trans("PaperSize");
			}
			if ($value == 'orientation') {
				$valuetoshow = $langs->trans("Orientation");
			}
			if ($value == 'leftmargin') {
				$valuetoshow = $langs->trans("LeftMargin");
			}
			if ($value == 'topmargin') {
				$valuetoshow = $langs->trans("TopMargin");
			}
			if ($value == 'spacex') {
				$valuetoshow = $langs->trans("SpaceX");
			}
			if ($value == 'spacey') {
				$valuetoshow = $langs->trans("SpaceY");
			}
			if ($value == 'font_size') {
				$valuetoshow = $langs->trans("FontSize");
			}
			if ($value == 'custom_x') {
				$valuetoshow = $langs->trans("CustomX");
			}
			if ($value == 'custom_y') {
				$valuetoshow = $langs->trans("CustomY");
			}
			if ($value == 'percent') {
				$valuetoshow = $langs->trans("Percentage");
			}
			if ($value == 'affect') {
				$valuetoshow = $langs->trans("WithCounter");
			}
			if ($value == 'delay') {
				$valuetoshow = $langs->trans("NoticePeriod");
			}
			if ($value == 'newbymonth') {
				$valuetoshow = $langs->trans("NewByMonth");
			}
			if ($value == 'fk_tva') {
				$valuetoshow = $langs->trans("VAT");
			}
			if ($value == 'range_ik') {
				$valuetoshow = $langs->trans("RangeIk");
			}
			if ($value == 'fk_c_exp_tax_cat') {
				$valuetoshow = $langs->trans("CarCategory");
			}
			if ($value == 'revenuestamp_type') {
				$valuetoshow = $langs->trans('TypeOfRevenueStamp');
			}
			if ($value == 'use_default') {
				$valuetoshow = $langs->trans('Default'); $cssprefix = 'center ';
			}
			if ($value == 'unit_type') {
				$valuetoshow = $langs->trans('TypeOfUnit');
			}
			if ($value == 'public' && $tablib[$id] == 'TicketDictCategory') {
				$valuetoshow = $langs->trans('TicketGroupIsPublic'); $cssprefix = 'center ';
			}
			if ($value == 'block_if_negative') {
				$valuetoshow = $langs->trans('BlockHolidayIfNegative');
			}
			if ($value == 'type_duration') {
				$valuetoshow = $langs->trans('Unit');
			}

			if ($value == 'region_id' || $value == 'country_id') {
				$showfield = 0;
			}

			// Show field title
			if ($showfield) {
				$tooltiphelp = (isset($tabcomplete[$tabname[$id]]['help'][$value]) ? $tabcomplete[$tabname[$id]]['help'][$value] : '');

				if ($tooltiphelp && preg_match('/^http(s*):/i', $tooltiphelp)) {
					$newvaluetoshow = '<a href="'.$tooltiphelp.'" target="_blank">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
				} elseif ($tooltiphelp) {
					$newvaluetoshow = $form->textwithpicto($valuetoshow, $tooltiphelp);
				} else {
					$newvaluetoshow = $valuetoshow;
				}

				print getTitleFieldOfList($newvaluetoshow, 0, $_SERVER["PHP_SELF"], ($sortable ? $value : ''), ($page ? 'page='.$page.'&' : ''), $param, '', $sortfield, $sortorder, $cssprefix);
			}
		}
		// Favorite & EEC - Only activated on country dictionary
		if ($id == DICT_COUNTRY) {
			print getTitleFieldOfList($langs->trans("InEEC"), 0, $_SERVER["PHP_SELF"], "eec", ($page ? 'page='.$page.'&' : ''), $param, 'align="center"', $sortfield, $sortorder, '', 0, $langs->trans("CountryIsInEEC"));
			print getTitleFieldOfList($langs->trans("Favorite"), 0, $_SERVER["PHP_SELF"], "favorite", ($page ? 'page='.$page.'&' : ''), $param, 'align="center"', $sortfield, $sortorder);
		}

		print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "active", ($page ? 'page='.$page.'&' : ''), $param, 'align="center"', $sortfield, $sortorder);
		print getTitleFieldOfList('');
		print getTitleFieldOfList('');
		print '</tr>';

		if ($num) {
			// Lines with values
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				//print_r($obj);
				print '<tr class="oddeven" id="rowid-'.(empty($obj->rowid) ? '' : $obj->rowid).'">';
				if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
					$tmpaction = 'edit';
					$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
					$reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
					$error = $hookmanager->error; $errors = $hookmanager->errors;

					// Show fields
					if (empty($reshook)) {
						$withentity = fieldList($fieldlist, $obj, $tabname[$id], 'edit');
					}

					print '<td colspan="3" class="center">';
					print '<div name="'.(!empty($obj->rowid) ? $obj->rowid : $obj->code).'"></div>';
					print '<input type="hidden" name="page" value="'.dol_escape_htmltag($page).'">';
					print '<input type="hidden" name="rowid" value="'.dol_escape_htmltag($rowid).'">';
					if (!is_null($withentity)) {
						print '<input type="hidden" name="entity" value="'.$withentity.'">';
					}
					print '<input type="submit" class="button button-edit small" name="actionmodify" value="'.$langs->trans("Modify").'">';
					print '<input type="submit" class="button button-cancel small" name="actioncancel" value="'.$langs->trans("Cancel").'">';
					print '</td>';
				} else {
					$tmpaction = 'view';
					$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
					$reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

					$error = $hookmanager->error; $errors = $hookmanager->errors;

					if (empty($reshook)) {
						$withentity = null;

						foreach ($fieldlist as $field => $value) {
							//var_dump($fieldlist);
							$class = '';
							$showfield = 1;
							$valuetoshow = empty($obj->{$value}) ? '' : $obj->{$value};
							$titletoshow = '';

							if ($value == 'entity') {
								$withentity = $valuetoshow;
								continue;
							}

							if ($value == 'element') {
								$valuetoshow = isset($elementList[$valuetoshow]) ? $elementList[$valuetoshow] : $valuetoshow;
							} elseif ($value == 'source') {
								$valuetoshow = isset($sourceList[$valuetoshow]) ? $sourceList[$valuetoshow] : $valuetoshow;
							} elseif ($valuetoshow == 'all') {
								$valuetoshow = $langs->trans('All');
							} elseif ($value == 'country') {
								if (empty($obj->country_code)) {
									$valuetoshow = '-';
								} else {
									$key = $langs->trans("Country".strtoupper($obj->country_code));
									$valuetoshow = ($key != "Country".strtoupper($obj->country_code) ? $obj->country_code." - ".$key : $obj->country);
								}
							} elseif ($value == 'recuperableonly' || $value == 'deductible' || $value == 'category_type') {
								$valuetoshow = yn($valuetoshow ? 1 : 0);
								$class = "center";
							} elseif ($value == 'type_cdr') {
								if (empty($valuetoshow)) {
									$valuetoshow = $langs->trans('None');
								} elseif ($valuetoshow == 1) {
									$valuetoshow = $langs->trans('AtEndOfMonth');
								} elseif ($valuetoshow == 2) {
									$valuetoshow = $langs->trans('CurrentNext');
								}
								$class = "center";
							} elseif ($value == 'price' || preg_match('/^amount/i', $value)) {
								$valuetoshow = price($valuetoshow);
							}
							if ($value == 'private') {
								$valuetoshow = yn($valuetoshow);
							} elseif ($value == 'libelle_facture') {
								$langs->load("bills");
								$key = $langs->trans("PaymentCondition".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "PaymentCondition".strtoupper($obj->code) ? $key : $obj->{$value});
								$valuetoshow = nl2br($valuetoshow);
							} elseif ($value == 'label' && $tabname[$id] == 'c_country') {
								$key = $langs->trans("Country".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "Country".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'label' && $tabname[$id] == 'c_availability') {
								$langs->load("propal");
								$key = $langs->trans("AvailabilityType".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "AvailabilityType".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_actioncomm') {
								$key = $langs->trans("Action".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "Action".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif (!empty($obj->code_iso) && $value == 'label' && $tabname[$id] == 'c_currencies') {
								$key = $langs->trans("Currency".strtoupper($obj->code_iso));
								$valuetoshow = ($obj->code_iso && $key != "Currency".strtoupper($obj->code_iso) ? $key : $obj->{$value});
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_typent') {
								$key = $langs->trans(strtoupper($obj->code));
								$valuetoshow = ($key != strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_prospectlevel') {
								$key = $langs->trans(strtoupper($obj->code));
								$valuetoshow = ($key != strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'label' && $tabname[$id] == 'c_civility') {
								$key = $langs->trans("Civility".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "Civility".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_type_contact') {
								$langs->load('agenda');
								$key = $langs->trans("TypeContact_".$obj->element."_".$obj->source."_".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "TypeContact_".$obj->element."_".$obj->source."_".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_payment_term') {
								$langs->load("bills");
								$key = $langs->trans("PaymentConditionShort".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "PaymentConditionShort".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_paiement') {
								$langs->load("bills");
								$key = $langs->trans("PaymentType".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "PaymentType".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'type' && $tabname[$id] == 'c_paiement') {
								$payment_type_list = array(0=>$langs->trans('PaymentTypeCustomer'), 1=>$langs->trans('PaymentTypeSupplier'), 2=>$langs->trans('PaymentTypeBoth'));
								$valuetoshow = $payment_type_list[$valuetoshow];
							} elseif ($value == 'label' && $tabname[$id] == 'c_input_reason') {
								$key = $langs->trans("DemandReasonType".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "DemandReasonType".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_input_method') {
								$langs->load("orders");
								$key = $langs->trans($obj->code);
								$valuetoshow = ($obj->code && $key != $obj->code) ? $key : $obj->{$value};
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_shipment_mode') {
								$langs->load("sendings");
								$key = $langs->trans("SendingMethod".strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != "SendingMethod".strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'libelle' && $tabname[$id] == 'c_paper_format') {
								$key = $langs->trans('PaperFormat'.strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != 'PaperFormat'.strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'label' && $tabname[$id] == 'c_type_fees') {
								$langs->load('trips');
								$key = $langs->trans(strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != strtoupper($obj->code) ? $key : $obj->{$value});
							} elseif ($value == 'region_id' || $value == 'country_id') {
								$showfield = 0;
							} elseif ($value == 'unicode') {
								$valuetoshow = $langs->getCurrencySymbol($obj->code, 1);
							} elseif ($value == 'label' && $tabname[GETPOST("id", 'int')] == 'c_units') {
								$langs->load("products");
								$valuetoshow = $langs->trans($obj->{$value});
							} elseif ($value == 'short_label' && $tabname[GETPOST("id", 'int')] == 'c_units') {
								$langs->load("products");
								$valuetoshow = $langs->trans($obj->{$value});
							} elseif (($value == 'unit') && ($tabname[$id] == 'c_paper_format')) {
								$key = $langs->trans('SizeUnit'.strtolower($obj->unit));
								$valuetoshow = ($obj->code && $key != 'SizeUnit'.strtolower($obj->unit) ? $key : $obj->{$value});
							} elseif ($value == 'localtax1' || $value == 'localtax2') {
								$class = "center";
							} elseif ($value == 'localtax1_type') {
								if ($obj->localtax1 != 0) {
									$valuetoshow = $localtax_typeList[$valuetoshow];
								} else {
									$valuetoshow = '';
								}
								$class = "center";
							} elseif ($value == 'localtax2_type') {
								if ($obj->localtax2 != 0) {
									$valuetoshow = $localtax_typeList[$valuetoshow];
								} else {
									$valuetoshow = '';
								}
								$class = "center";
							} elseif ($value == 'taux') {
								$valuetoshow = price($valuetoshow, 0, $langs, 0, 0);
								$class = "center";
							} elseif (in_array($value, array('recuperableonly'))) {
								$class = "center";
							} elseif ($value == 'accountancy_code' || $value == 'accountancy_code_sell' || $value == 'accountancy_code_buy') {
								if (isModEnabled('accounting')) {
									require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
									$tmpaccountingaccount = new AccountingAccount($db);
									$tmpaccountingaccount->fetch(0, $valuetoshow, 1);
									$titletoshow = $langs->transnoentitiesnoconv("Pcgtype").': '.$tmpaccountingaccount->pcg_type;
								}
								$valuetoshow = length_accountg($valuetoshow);
							} elseif ($value == 'fk_tva') {
								foreach ($form->cache_vatrates as $key => $Tab) {
									if ($form->cache_vatrates[$key]['rowid'] == $valuetoshow) {
										$valuetoshow = $form->cache_vatrates[$key]['label'];
										break;
									}
								}
							} elseif ($value == 'fk_c_exp_tax_cat') {
								$tmpid = $valuetoshow;
								$valuetoshow = getDictionaryValue('c_exp_tax_cat', 'label', $tmpid);
								$valuetoshow = $langs->trans($valuetoshow ? $valuetoshow : $tmpid);
							} elseif ($tabname[$id] == 'c_exp_tax_cat') {
								$valuetoshow = $langs->trans($valuetoshow);
							} elseif ($value == 'label' && $tabname[$id] == 'c_units') {
								$langs->load('other');
								$key = $langs->trans($obj->label);
								$valuetoshow = ($obj->label && $key != strtoupper($obj->label) ? $key : $obj->{$value});
							} elseif ($value == 'code' && $id == DICT_REGIONS) {
								$valuetoshow = $obj->state_code;
							} elseif ($value == 'label' && $tabname[$id] == 'c_product_nature') {
								$langs->load("products");
								$valuetoshow = $langs->trans($obj->{$value});
							} elseif ($fieldlist[$field] == 'label' && $tabname[$id] == 'c_productbatch_qcstatus') {
								$langs->load("productbatch");
								$valuetoshow = $langs->trans($obj->{$value});
							} elseif ($value == 'block_if_negative') {
								$valuetoshow = yn($obj->{$value});
							} elseif ($value == 'icon') {
								$valuetoshow = $obj->{$value}." ".img_picto("",  $obj->{$value});
							} elseif ($value == 'type_duration') {
								$TDurationTypes = array('y'=>$langs->trans('Years'), 'm'=>$langs->trans('Month'), 'w'=>$langs->trans('Weeks'), 'd'=>$langs->trans('Days'), 'h'=>$langs->trans('Hours'), 'i'=>$langs->trans('Minutes'));
								$valuetoshow =$TDurationTypes[$obj->{$value}];
							}
							$class .= ($class ? ' ' : '').'tddict';
							if ($value == 'note' && $id == DICT_TVA) {
								$class .= ' tdoverflowmax200';
							}
							if ($value == 'tracking') {
								$class .= ' tdoverflowauto';
							}
							if (in_array($value, array('nbjour', 'decalage', 'pos', 'position', 'deposit_percent'))) {
								$class .= ' right';
							}
							if (in_array($value, array('localtax1_type', 'localtax2_type'))) {
								$class .= ' nowraponall';
							}
							if (in_array($value, array('use_default', 'fk_parent', 'sortorder'))) {
								$class .= ' center';
							}
							if ($value == 'public') {
								$class .= ' center';
							}
							// Show value for field
							if ($showfield) {
								print '<!-- '. $value .' --><td class="'.$class.'"'.($titletoshow ? ' title="'.dol_escape_htmltag($titletoshow).'"' : '').'>'.$valuetoshow.'</td>';
							}
						}
					}

					// Can an entry be erased or disabled ?
					// all true by default
					$iserasable = 1;
					$canbedisabled = 1;
					$canbemodified = 1;
					if (isset($obj->code) && $id != DICT_TVA && $id != DICT_PRODUCT_NATURE) {
						if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i', $obj->code))) {
							$iserasable = 0; $canbedisabled = 0;
						} elseif ($obj->code == 'RECEP') {
							$iserasable = 0; $canbedisabled = 0;
						} elseif ($obj->code == 'EF0') {
							$iserasable = 0; $canbedisabled = 0;
						}
					}
					if ($id == DICT_TYPE_CONTAINER && in_array($obj->code, array('banner', 'blogpost', 'other', 'page'))) {
						$iserasable = 0; $canbedisabled = 0;
						if (in_array($obj->code, array('banner'))) {
							$canbedisabled = 1;
						}
					}
					if (isset($obj->type) && in_array($obj->type, array('system', 'systemauto'))) {
						$iserasable = 0;
					}
					if (in_array(empty($obj->code) ? '' : $obj->code, array('AC_OTH', 'AC_OTH_AUTO')) || in_array(empty($obj->type) ? '' : $obj->type, array('systemauto'))) {
						$canbedisabled = 0; $canbedisabled = 0;
					}
					$canbemodified = $iserasable;

					if (!empty($obj->code) && $obj->code == 'RECEP') {
						$canbemodified = 1;
					}
					if ($tabname[$id] == "c_actioncomm") {
						$canbemodified = 1;
					}

					// Build Url. The table is id=, the id of line is rowid=
					$rowidcol = $tabrowid[$id];
					// If rowidcol not defined
					if (empty($rowidcol) || in_array($id, array(DICT_ACTIONCOMM, DICT_CHARGESOCIALES, DICT_TYPENT, DICT_PAIEMENT, DICT_TYPE_FEES, DICT_EFFECTIF, DICT_STCOMM, DICT_HRM_PUBLIC_HOLIDAY))) {
						$rowidcol = 'rowid';
					}
					$url = $_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(isset($obj->{$rowidcol}) ? $obj->{$rowidcol} : (!empty($obj->code) ? urlencode($obj->code) : '')).'&code='.(!empty($obj->code) ?urlencode($obj->code) : '');
					if (!empty($param)) {
						$url .= '&'.$param;
					}
					if (!is_null($withentity)) {
						$url .= '&entity='.$withentity;
					}
					$url .= '&';

					// Favorite & EEC
					// Only activated on country dictionary
					if ($id == DICT_COUNTRY) {
						print '<td class="nowrap center">';
						// Is in EEC
						if ($iserasable) {
							print '<a class="reposition" href="'.$url.'action='.$acts[$obj->eec].'_eec&token='.newToken().'">'.$actl[$obj->eec].'</a>';
						} else {
							print '<span class="opacitymedium">'.$langs->trans("AlwaysActive").'</span>';
						}
						print '</td>';
						print '<td class="nowrap center">';
						// Favorite
						if ($iserasable) {
							print '<a class="reposition" href="'.$url.'action='.$acts[$obj->favorite].'_favorite&token='.newToken().'">'.$actl[$obj->favorite].'</a>';
						} else {
							print '<span class="opacitymedium">'.$langs->trans("AlwaysActive").'</span>';
						}
						print '</td>';
					}

					// Active
					print '<td class="nowrap center">';
					if ($canbedisabled) {
						print '<a class="reposition" href="'.$url.'action='.$acts[$obj->active].'&token='.newToken().'">'.$actl[$obj->active].'</a>';
					} else {
						if (in_array($obj->code, array('AC_OTH', 'AC_OTH_AUTO'))) {
							print $langs->trans("AlwaysActive");
						} elseif (isset($obj->type) && in_array($obj->type, array('systemauto')) && empty($obj->active)) {
							print $langs->trans("Deprecated");
						} elseif (isset($obj->type) && in_array($obj->type, array('system')) && !empty($obj->active) && $obj->code != 'AC_OTH') {
							print $langs->trans("UsedOnlyWithTypeOption");
						} else {
							print '<span class="opacitymedium">'.$langs->trans("AlwaysActive").'</span>';
						}
					}
					print "</td>";

					// Modify link
					if ($canbemodified) {
						print '<td align="center"><a class="reposition editfielda" href="'.$url.'action=edit&token='.newToken().'">'.img_edit().'</a></td>';
					} else {
						print '<td>&nbsp;</td>';
					}

					// Delete link
					if ($iserasable) {
						print '<td class="center">';
						if ($user->admin) {
							print '<a class="reposition" href="'.$url.'action=delete&token='.newToken().'">'.img_delete().'</a>';
						}
						//else print '<a href="#">'.img_delete().'</a>';    // Some dictionary can be edited by other profile than admin
						print '</td>';
					} else {
						print '<td>&nbsp;</td>';
					}

					print "</tr>\n";
				}
				$i++;
			}
		}

		print '</table>';
		print '</div>';
	} else {
		dol_print_error($db);
	}

	print '</form>';
} else {
	/*
	 * Show list of dictionary to show
	 */

	$lastlineisempty = false;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Dictionary").'</td>';
	print '<td></td>';
	print '<td class="hideonsmartphone"></td>';
	print '</tr>';

	$showemptyline = '';
	foreach ($taborder as $i) {
		if (isset($tabname[$i]) && empty($tabcond[$i])) {
			continue;
		}

		if ($i) {
			if ($showemptyline) {
				print '<tr class="oddeven"><td></td><td></td><td class="hideonsmartphone"></td></tr>';
				$showemptyline = 0;
			}


			$value = $tabname[$i];
			print '<tr class="oddeven"><td class="minwidth200">';
			if (!empty($tabcond[$i])) {
				$tabnamenoprefix = preg_replace('/'.MAIN_DB_PREFIX.'/', '', $tabname[$i]);
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$i.'">';
				if (!empty($tabcomplete[$tabnamenoprefix]['picto'])) {
					print img_picto('', $tabcomplete[$tabnamenoprefix]['picto'], 'class="pictofixedwidth paddingrightonly"');
				}
				print $langs->trans($tablib[$i]);
				print '</a>';
			} else {
				print $langs->trans($tablib[$i]);
			}
			print '</td>';
			print '<td>';
			print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$i.'">';
			print img_picto('Edit', 'edit', '');
			print '</a>';
			print '</td>';
			print '<td class="right hideonsmartphone">';
			print $form->textwithpicto('', $langs->trans("Table").': '.MAIN_DB_PREFIX.$tabname[$i]);
			print '</td>';
			print '</tr>';
			$lastlineisempty = false;
		} else {
			if (!$lastlineisempty) {
				$showemptyline = 1;
				$lastlineisempty = true;
			}
		}
	}
	print '</table>';
	print '</div>';
}

print '<br>';

// End of page
llxFooter();
$db->close();


/**
 *	Show fields in insert/edit mode
 *
 * 	@param		array		$fieldlist		Array of fields
 * 	@param		Object		$obj			If we show a particular record, obj is filled with record fields
 *  @param		string		$tabname		Name of SQL table
 *  @param		string		$context		'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we dont want it to be rendered
 *	@return		string						'' or value of entity into table
 */
function fieldList($fieldlist, $obj = '', $tabname = '', $context = '')
{
	global $conf, $langs, $db, $mysoc;
	global $form;
	global $region_id;
	global $elementList, $sourceList, $localtax_typeList;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);
	$formaccounting = new FormAccounting($db);

	$withentity = '';

	foreach ($fieldlist as $field => $value) {
		if ($value == 'entity') {
			$withentity = $obj->{$value};
			continue;
		}

		if (in_array($value, array('code', 'libelle', 'type')) && $tabname == "c_actioncomm" && in_array($obj->type, array('system', 'systemauto'))) {
			$hidden = (!empty($obj->{$value}) ? $obj->{$value}:'');
			print '<td>';
			print '<input type="hidden" name="'. $value .'" value="'.$hidden.'">';
			print $langs->trans($hidden);
			print '</td>';
		} elseif ($value == 'country') {
			if (in_array('region_id', $fieldlist)) {
				print '<td>';
				print '</td>';
				continue;
			}	// For state page, we do not show the country input (we link to region, not country)
			print '<td>';
			$fieldname = 'country';
			print $form->select_country((!empty($obj->country_code) ? $obj->country_code : (!empty($obj->country) ? $obj->country : '')), $fieldname, '', 28, 'minwidth100 maxwidth150 maxwidthonsmartphone');
			print '</td>';
		} elseif ($value == 'country_id') {
			if (!in_array('country', $fieldlist)) {	// If there is already a field country, we don't show country_id (avoid duplicate)
				$country_id = (!empty($obj->{$value}) ? $obj->{$value} : 0);
				print '<td class="tdoverflowmax100">';
				print '<input type="hidden" name="'. $value .'" value="'.$country_id.'">';
				print '</td>';
			}
		} elseif ($value == 'region') {
			print '<td>';
			$formcompany->select_region($region_id, 'region');
			print '</td>';
		} elseif ($value == 'region_id') {
			$region_id = (!empty($obj->{$value}) ? $obj->{$value}:0);
			print '<td>';
			print '<input type="hidden" name="'. $value .'" value="'.$region_id.'">';
			print '</td>';
		} elseif ($value == 'lang') {
			print '<td>';
			print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT, 'lang');
			print '</td>';
		} elseif (in_array($value, array('element', 'source'))) {	// Example: the type and source of the element (for contact types)
			$tmparray = array();
			if ($value == 'element') {
				$tmparray = $elementList;
			} else {
				$tmparray = $sourceList;
			}
			print '<td>';
			print $form->selectarray($value, $tmparray, (!empty($obj->{$value}) ? $obj->{$value}:''), 0, 0, 0, '', 0, 0, 0, '', 'maxwidth250');
			print '</td>';
		} elseif (in_array($value, array('public', 'use_default'))) {
			// Fields 0/1 with a combo select Yes/No
			print '<td class="center">';
			print $form->selectyesno($value, (!empty($obj->{$value}) ? $obj->{$value}:''), 1);
			print '</td>';
		} elseif ($value == 'private') {
			// Fields 'no'/'yes' with a combo select Yes/No
			print '<td>';
			print $form->selectyesno("private", (!empty($obj->{$value}) ? $obj->{$value}:''));
			print '</td>';
		} elseif ($value == 'type' && $tabname == "c_actioncomm") {
			$type = (!empty($obj->type) ? $obj->type : 'user'); // Check if type is different of 'user' (external module)
			print '<td>';
			print $type.'<input type="hidden" name="type" value="'.$type.'">';
			print '</td>';
		} elseif ($value == 'type' && $tabname == 'c_paiement') {
			print '<td>';
			$select_list = array(0=>$langs->trans('PaymentTypeCustomer'), 1=>$langs->trans('PaymentTypeSupplier'), 2=>$langs->trans('PaymentTypeBoth'));
			print $form->selectarray($value, $select_list, (!empty($obj->{$value}) ? $obj->{$value}:'2'));
			print '</td>';
		} elseif ($value == 'recuperableonly' || $value == 'type_cdr' || $value == 'deductible' || $value == 'category_type') {
			if ($value == 'type_cdr') {
				print '<td class="center">';
			} else {
				print '<td>';
			}
			if ($value == 'type_cdr') {
				print $form->selectarray($value, array(0=>$langs->trans('None'), 1=>$langs->trans('AtEndOfMonth'), 2=>$langs->trans('CurrentNext')), (!empty($obj->{$value}) ? $obj->{$value}:''));
			} else {
				print $form->selectyesno($value, (!empty($obj->{$value}) ? $obj->{$value}:''), 1);
			}
			print '</td>';
		} elseif (in_array($value, array('nbjour', 'decalage', 'taux', 'localtax1', 'localtax2'))) {
			$class = "right";
			if (in_array($value, array('taux', 'localtax1', 'localtax2'))) {
				$class = "center"; // Fields aligned on right
			}
			print '<td class="'.$class.'">';
			print '<input type="text" class="flat" value="'.(isset($obj->{$value}) ? $obj->{$value} : '').'" size="3" name="'. $value .'">';
			print '</td>';
		} elseif (in_array($value, array('libelle_facture'))) {
			print '<td>';
			$transfound = 0;
			$transkey = '';
			// Special case for labels
			if ($tabname == 'c_payment_term') {
				$langs->load("bills");
				$transkey = "PaymentCondition".strtoupper($obj->code);
				if ($langs->trans($transkey) != $transkey) {
					$transfound = 1;
					print $form->textwithpicto($langs->trans($transkey), $langs->trans("GoIntoTranslationMenuToChangeThis"));
				}
			}
			if (!$transfound) {
				print '<textarea cols="30" rows="'.ROWS_2.'" class="flat" name="'. $value .'">'.(!empty($obj->{$value}) ? $obj->{$value}:'').'</textarea>';
			} else {
				print '<input type="hidden" name="'. $value .'" value="'.$transkey.'">';
			}
			print '</td>';
		} elseif ($value == 'price' || preg_match('/^amount/i', $value)) {
			print '<td><input type="text" class="flat minwidth75" value="'.price((!empty($obj->{$value}) ? $obj->{$value}:'')).'" name="'. $value .'"></td>';
		} elseif ($value == 'code' && isset($obj->{$value})) {
			print '<td><input type="text" class="flat minwidth75 maxwidth100" value="'.(!empty($obj->{$value}) ? $obj->{$value}:'').'" name="'. $value .'"></td>';
		} elseif ($value == 'unit') {
			print '<td>';
			$units = array(
				'mm' => $langs->trans('SizeUnitmm'),
				'cm' => $langs->trans('SizeUnitcm'),
				'point' => $langs->trans('SizeUnitpoint'),
				'inch' => $langs->trans('SizeUnitinch')
			);
			print $form->selectarray('unit', $units, (!empty($obj->{$value}) ? $obj->{$value}:''), 0, 0, 0);
			print '</td>';
		} elseif ($value == 'localtax1_type' || $value == 'localtax2_type') {
			// Le type de taxe locale
			print '<td class="center">';
			print $form->selectarray($value, $localtax_typeList, (!empty($obj->{$value}) ? $obj->{$value}:''));
			print '</td>';
		} elseif ($value == 'accountancy_code' || $value == 'accountancy_code_sell' || $value == 'accountancy_code_buy') {
			print '<td>';
			if (isModEnabled('accounting')) {
				$fieldname = $value;
				$accountancy_account = (!empty($obj->$fieldname) ? $obj->$fieldname : 0);
				print $formaccounting->select_account($accountancy_account, '.'. $value, 1, '', 1, 1, 'maxwidth200 maxwidthonsmartphone');
			} else {
				$fieldname = $value;
				print '<input type="text" size="10" class="flat" value="'.(isset($obj->$fieldname) ? $obj->$fieldname : '').'" name="'. $value .'">';
			}
			print '</td>';
		} elseif ($value == 'fk_tva') {
			print '<td>';
			print $form->load_tva('fk_tva', $obj->taux, $mysoc, new Societe($db), 0, 0, '', false, -1);
			print '</td>';
		} elseif ($value == 'fk_c_exp_tax_cat') {
			print '<td>';
			print $form->selectExpenseCategories($obj->fk_c_exp_tax_cat);
			print '</td>';
		} elseif ($value == 'fk_range') {
			print '<td>';
			print $form->selectExpenseRanges($obj->fk_range);
			print '</td>';
		} elseif ($value == 'block_if_negative') {
			print '<td>';
			print $form->selectyesno("block_if_negative", (empty($obj->block_if_negative) ? '' : $obj->block_if_negative), 1);
			print '</td>';
		} elseif ($value == 'type_duration') {
			print '<td>';
			print $form->selectTypeDuration('', (empty($obj->type_duration) ? '' : $obj->type_duration), array('i','h'));
			print '</td>';
		} else {
			$fieldValue = isset($obj->{$value}) ? $obj->{$value}: '';
			$classtd = ''; $class = '';

			if ($value == 'sortorder') {
				$fieldlist[$field] = 'position';
			}

			if ($fieldlist[$field] == 'code') {
				$class = 'maxwidth100';
			}
			if (in_array($fieldlist[$field], array('deposit_percent'))) {
				$classtd = 'right'; $class = 'maxwidth50 right';
			}
			if (in_array($fieldlist[$field], array('pos', 'position'))) {
				$classtd = 'right'; $class = 'maxwidth50 right';
			}
			if (in_array($fieldlist[$field], array('dayrule', 'day', 'month', 'year', 'use_default', 'affect', 'delay', 'public', 'sortorder', 'sens', 'category_type', 'fk_parent'))) {
				$class = 'maxwidth50 center';
			}
			if (in_array($fieldlist[$field], array('use_default', 'public', 'fk_parent'))) {
				$classtd = 'center';
			}
			if (in_array($fieldlist[$field], array('libelle', 'label', 'tracking'))) {
				$class = 'quatrevingtpercent';
			}
			// Fields that must be suggested as '0' instead of ''
			if ($fieldlist[$field] == 'fk_parent') {
				if (empty($fieldValue)) {
					$fieldValue = '0';
				}
			}

			// Labels Length
			$maxlength = '';
			if (in_array($fieldlist[$field], array('libelle', 'label'))) {
				switch ($tabname) {
					case 'c_accounting_category':
					case 'c_ecotaxe':
					case 'c_email_senderprofile':
					case 'c_forme_juridique':
					case 'c_holiday_types':
					case 'c_payment_term':
					case 'c_transport_mode':
						$maxlength = ' maxlength="255"';
						break;
					case 'c_email_templates':
						$maxlength = ' maxlength="180"';
						break;
					case 'c_socialnetworks':
						$maxlength = ' maxlength="150"';
						break;
					default:
						$maxlength = ' maxlength="128"';
				}
			}

			print '<td class="'.$classtd.'">';
			$transfound = 0;
			$transkey = '';
			if (in_array($fieldlist[$field], array('label', 'libelle'))) {		// For label
				// Special case for labels
				if ($tabname == 'c_civility' && !empty($obj->code)) {
					$transkey = "Civility".strtoupper($obj->code);
				}
				if ($tabname == 'c_payment_term' && !empty($obj->code)) {
					$langs->load("bills");
					$transkey = "PaymentConditionShort".strtoupper($obj->code);
				}
				if ($transkey && $langs->trans($transkey) != $transkey) {
					$transfound = 1;
					print $form->textwithpicto($langs->trans($transkey), $langs->trans("GoIntoTranslationMenuToChangeThis"));
				}
			}
			if (!$transfound) {
				print '<input type="text" class="flat'.($class ? ' '.$class : '').'"'.($maxlength ? ' '.$maxlength : '').' value="'.dol_escape_htmltag($fieldValue).'" name="'.$fieldlist[$field].'">';
			} else {
				print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$transkey.'">';
			}
			print '</td>';
		}
	}

	return $withentity;
}
