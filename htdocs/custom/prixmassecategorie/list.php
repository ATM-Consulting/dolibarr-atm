<?php
/* 
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
 *	\file       htdocs/prixmassecategorie/list.php
 *	\ingroup    prixmassecategorie
 *	\brief      Page to list orders
 */
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

dol_include_once("/prixmassecategorie/class/prixmassecategorie.class.php");

$langs->load("prixmassecategorie@prixmassecategorie");

$action = GETPOST('action','aZ09');
$massaction = GETPOST('massaction','alpha');
$confirm = GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'prixmassecategorielist';
$rowid = GETPOST('rowid', 'int');

$search_dyear = GETPOST("search_dyear","int");
$search_dmonth = GETPOST("search_dmonth","int");
$search_dday = GETPOST("search_dday","int");

$search_user_author_id = GETPOST('search_user_author_id','int');
$search_cat_id = GETPOST('search_cat_id','int');
$search_price_ht = GETPOST('search_price_ht');
$search_percent = GETPOST('search_percent');

$optioncss = GETPOST('optioncss','alpha');
$search_btn = GETPOST('button_search','alpha');
$search_remove_btn = GETPOST('button_removefilter','alpha');

// Security check
$id = GETPOST('id','int');
$result = restrictedArea($user, 'prixmassecategorie', $id,'');

$diroutputmassaction = $conf->prixmassecategorie->dir_output . '/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='e.rowid';
if (! $sortorder) $sortorder='DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new PrixMasseCategorie($db);
$hookmanager->initHooks(array('prixmassecategorielist'));

$search_array_options = array();

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();

$arrayfields=array(
    'e.fk_object'=>array('label'=>$langs->trans("ModCategory"), 'checked'=>1),
	'e.percent'=>array('label'=>$langs->trans("ModPercent"), 'checked'=>1),
    'e.level'=>array('label'=>$langs->trans("ModLevel"), 'checked'=>1),
	'e.price_ht'=>array('label'=>$langs->trans("ModPriceHT"), 'checked'=>1),
	'e.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>1),
	'e.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
);

/*
 * Actions
 */

$error = 0;

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha')) { $massaction=''; }

$parameters=array('socid'=>'');
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		$search_dyear = '';
		$search_dmonth = '';
		$search_dday = '';

        $search_cat_id = '';
        $search_price_ht = '';
        $search_percent = '';
		$search_user_author_id = '';

		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')
	 || GETPOST('button_search_x','alpha') || GETPOST('button_search.x','alpha') || GETPOST('button_search','alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
    }
    
    if ($action == 'confirm_delmod' && GETPOST('confirm') == 'yes' && $user->rights->prixmassecategorie->supprimer)
    {
        $prixmassecategorie = new PrixMasseCategorie($db);
        $prixmassecategorie->fetch($rowid);

        $result = $prixmassecategorie->delete($user);

        if ($result < 0)
        {
            setEventMessages($prixmassecategorie->error, $prixmassecategorie->errors, 'errors');   
        } 
        else
        {
            setEventMessages($langs->trans("MassPercentDeleted"), null, 'mesgs');
        }
    }

    if ($action == 'add' && $user->rights->prixmassecategorie->creer)
    {
        $catids = GETPOST('catids', 'array');
        $operators = GETPOST('operators', 'array');

        $products_ids = -1;
        $fk_object = '';

        $operator = '';

        if (!GETPOST('mass_percent') && !GETPOST('price_ht'))
        {	
            setEventMessages($langs->trans('MassPercentIsEmpty'), null, 'errors');
        }
        else
        {
            // Get products
            if (count($catids) > 0)
            {
                foreach ($catids as $i => $cat_id)
                {
                    if ($cat_id > 0)
                    {
                        $category = new Categorie($db);
                        $category->fetch($cat_id);
                        
                        $pids = array();

                        $catproducts = $category->getObjectsInCateg(Categorie::TYPE_PRODUCT, 1);
                        $subcats = $category->get_filles();
        
                        if (is_array($catproducts) && count($catproducts) > 0)
                        {
                            $pids += $catproducts; 
                        }

                        if (is_array($subcats) && count($subcats) > 0)
                        {
                            foreach ($subcats as $cat)
                            {
                                $catproducts = $cat->getObjectsInCateg(Categorie::TYPE_PRODUCT, 1);

                                if (is_array($catproducts) && count($catproducts) > 0)
                                {
                                    $pids += $catproducts;            
                                }
                            }
                        }


                        if ($products_ids == -1) 
                        {
                            $products_ids = array_unique($pids);
                        } 
                        else 
                        {
                            if ($operator == PrixMasseCategorie::OPERATOR_AND) 
                            {
                                $products_ids = array_intersect($products_ids, $pids);
                            }

                            if ($operator == PrixMasseCategorie::OPERATOR_BUT) 
                            {
                                $products_ids = array_diff($products_ids, $pids);
                            } 
                        }

                        $operator = $operators[$i];
                    
                        $fk_object .= implode(',', array($cat_id, $operator));
                        $fk_object .= ";";
                    }
                }
            }

            $pids = array_unique($products_ids);

            $products = array();

            if (is_array($pids) && count($pids) > 0)
            {
               foreach ($pids as $pid) {
                    $productstatic = new Product($db);
                    $productstatic->fetch($pid);

                    $products[] = $productstatic;
               } 
            }

            if (is_array($products) && count($products) > 0)
            {
                $price_ht = GETPOST('price_ht') ? price2num(GETPOST('price_ht'), 'MT') : 0;
                $mass_percent = GETPOST('mass_percent') ? price2num(GETPOST('mass_percent')) : 0;
                $level = GETPOST('level', 'int');

                $prixmassecategorie = new PrixMasseCategorie($db);
                $prixmassecategorie->fk_object = $fk_object;
                $prixmassecategorie->percent = $mass_percent;
                $prixmassecategorie->price_ht = $price_ht;
                $prixmassecategorie->level = $level;
        
                if ($prixmassecategorie->create($user, $products) > 0)
                {
                    unset($_POST['price_ht']);
                    unset($_POST['mass_percent']);
                    unset($_POST['catids']);
                    unset($_POST['operators']);
                    unset($_POST['level']);

                    setEventMessages($langs->trans("MassPercentApplied"), null, 'mesgs');
                }
                else
                {
                    setEventMessages($prixmassecategorie->error, $prixmassecategorie->errors, 'errors');
                }
            }
            else
            {
                setEventMessages($langs->trans("NoProductsFound"), null, 'warnings');
            }
        }
    }
}


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);

$userstatic = new User($db);

$title = $langs->trans("ProductsMassModifications");
$help_url = "";

$sql = 'SELECT';
if ($sall) $sql = 'SELECT DISTINCT';

$sql.= " e.rowid, e.fk_object, e.percent, e.price_ht, e.level, c.label as clabel, c.rowid as cid, e.datec, e.fk_user_author, e.entity, e.tms ";

// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'prixmassecategorie as e';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c on (c.rowid = e.fk_object)";
$sql.= ' WHERE e.entity IN ('.getEntity('prixmassecategorie').')';

if ($search_dmonth > 0)
{
	if ($search_dyear > 0 && empty($search_dday))
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_get_first_day($search_dyear, $search_dmonth, false))."' AND '".$db->idate(dol_get_last_day($search_dyear, $search_dmonth, false))."'";
	else if ($search_dyear > 0 && ! empty($search_dday))
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_dmonth, $search_dday, $search_dyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_dmonth, $search_dday, $search_dyear))."'";
	else
	$sql.= " AND date_format(e.datec, '%m') = '".$search_dmonth."'";
}
else if ($search_dyear > 0)
{
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_get_first_day($search_dyear, 1, false))."' AND '".$db->idate(dol_get_last_day($search_dyear, 12, false))."'";
}

if (!empty($search_percent)) $sql.= " AND e.percent = ".price2num(price($percent));
if (!empty($search_price)) $sql.= " AND e.price_ht = ".price2num(price($search_price));

if ($search_cat_id > 0) $sql.= " AND e.fk_object = '".$db->escape($search_cat_id)."'";

if ($search_user_author_id > 0) $sql.= " AND e.fk_user_author = " .$search_user_author_id;

// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);

	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit + 1,$offset);

$resql = $db->query($sql);
if ($resql)
{
	$title = $langs->trans('ListOfPrixMasseCategories');

	$num = $db->num_rows($resql);

	llxHeader('',$title,$help_url);

	$param='';

	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);

	if ($search_dday)      		$param.='&search_dday='.urlencode($search_dday);
	if ($search_dmonth)      		$param.='&search_dmonth='.urlencode($search_dmonth);
	if ($search_dyear)       		$param.='&search_dyear='.urlencode($search_dyear);

	if ($search_percent)      		$param.='&search_percent='.urlencode($search_percent);
	if ($search_price_ht)      		$param.='&search_price_ht='.urlencode($search_price_ht);
	if ($search_cat_id > 0)      		$param.='&search_cat_id='.urlencode($search_cat_id);

	if ($search_user_author_id > 0) 		$param.='&search_user_author_id='.urlencode($search_user_author_id);

	if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);

    print load_fiche_titre($langs->trans("AddMassPrice"),'','');

    $prixmassecategorie = new PrixMasseCategorie($db);
    $operators = $prixmassecategorie->getOperators();
    $levels = $prixmassecategorie->getLevels();

    $static_categs = new Categorie($db);
    $tab_categs = $static_categs->get_full_arbo(Categorie::TYPE_PRODUCT);

    $select = '<select class="flat minwidth100" name="catids[]">';
    $select.= '<option value="-1">&nbsp;</option>';
    if (is_array($tab_categs))
    {
        foreach ($tab_categs as $categ)
        {
            $select.= '<option value="'.$categ['id'].'"';
            if ($categ['id'] == GETPOST('fk_object')) $select.= ' selected';
            $select.='>'.dol_trunc($categ['fulllabel'], 50, 'middle').'</option>';
        }
    }
    $select.= '</select>';

    $select.= '&nbsp;<select class="flat minwidth100" name="operators[]">';
    $select.= '<option value="">&nbsp;</option>';
    if (count($operators) > 0)
    {
        foreach ($operators as $code => $label) {
            $select.= '<option value="'.$code.'">'.$label.'</option>';
        }
    }
    $select.= '</select>';

	// Lines of title fields

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

    print '<table class="border" width="100%">';

    print '<tr class="catelref" style="display:none">';
    print '<td class="titlefieldcreate" style="width: 35%">' . $langs->trans('ModCategory') . '</td>';
    print '<td colspan="2">';
    print $select;
    print '</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="titlefieldcreate" style="width: 35%">' . $langs->trans('ModCategory') . '</td>';
    print '<td>';
    print $select;
    print '</td><td>';
    print '<a id="add-cat" class="button"><i class="fa fa-plus"></i></a>';
    print '&nbsp;<a id="remove-cat" class="button"><i class="fa fa-minus"></i></a>';
    print '</td>';
    print '</tr>';


    print '<tr>';
    print '<td class="titlefieldcreate">' . $langs->trans('MassPercent') . '</td>';
    print '<td colspan="2"><input type="text" name="mass_percent" value="'.GETPOST('mass_percent').'"></td>';
    print '</tr>';
    
    print '<tr>';
    print '<td class="titlefieldcreate">' . $langs->trans('TargetPriceHT') . '</td>';
    print '<td colspan="2"><input type="text" name="price_ht" value="'.GETPOST('price_ht').'"></td>';
    print '</tr>';

    print '<tr>';
    print '<td class="titlefieldcreate">' . $langs->trans('ModLevel') . '</td>';
    print '<td colspan="2">';
    print  $form->selectarray('level', $levels, GETPOST('level'));
    print '</td>';
    print '</tr>';

    print '<tr>';
    print '<td colspan="3" align="center">';
    print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('ModCreate') . '">';
    print '</td>';
    print '</tr>';
    
    print '</table>';

    print '</form>';

    print '<br />';
    print '<p>'.$langs->trans('ModDetails').'</p>';

    print '<br /><br />';

    print '<script type="text/javascript">'."\r\n";
    print '$(document).ready(function() {'."\r\n";
    print ' $("#add-cat").click(function(e){'."\r\n";
    print '     var $tr = $(this).closest("tr"); '."\r\n";
    print '     var $newtr = $(".catelref").clone().show().addClass("catel").removeClass("catelref"); '."\r\n";
    print '     $newtr.insertAfter($tr); '."\r\n";
    print ' }); '."\r\n";
    print ' $("#remove-cat").click(function(e){'."\r\n";
    print '     $(".catel").last().remove(); '."\r\n";
    print ' }); '."\r\n";
    print '});'."\r\n";
    print '</script>';

	$massactionbutton='';
    $newcardbutton='';
    
	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';


	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'prixmassecategorie@prixmassecategorie', 0, $newcardbutton, '', $limit);

	$topicmail = "";
	$modelmail = "";
	$objecttmp = new PrixMasseCategorie($db);
	$trackid = 'par'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';


	if ($action == 'delmod')
    {
        $generic_category = new Categorie($db);

        $prixmassecategorie = new PrixMasseCategorie($db);
        $prixmassecategorie->fetch($rowid);

        $cats = explode(';', $prixmassecategorie->fk_object);
        $label = '';
        if (count($cats)) {
            foreach ($cats as $cat) {
                $catop = explode(',', $cat);

                $cat_id = $catop[0];
                if ($cat_id > 0) {
                    $generic_category->fetch($cat_id); 
                    $label.= $generic_category->label;
                }

                if (count($catop) > 1) {
                    $operator = $catop[1];
                    $label.= ' '.(isset($operators[$operator]) ? '<strong>'.$operators[$operator].'</strong>' : '').' ';
                }
            }
        }

        print $form->formconfirm($_SERVER["PHP_SELF"] . '?rowid='.$rowid, $langs->trans('DeleteMassMod'), $langs->trans('ConfirmDeleteMassMod', dol_print_date($prixmassecategorie->date_creation, 'day'), $label), 'confirm_delmod', '', 'yes', 1); 
    }


	$moreforfilter='';

	// If the user can view other users
	if ($user->rights->user->user->lire)
	{
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('CreatedByUsers'). ': ';
		$moreforfilter.=$form->select_dolusers($search_user_author_id, 'search_user_author_id', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
	 	$moreforfilter.='</div>';
	}

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (! empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre_filter">';
	
	// Ref
	if (! empty($arrayfields['e.fk_object']['checked']))
	{
        print '<td class="liste_titre">';
        print $formother->select_categories(Categorie::TYPE_PRODUCT, $search_cat_id, 'search_cat_id',1);
		print '</td>';
	}

	if (! empty($arrayfields['e.percent']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_percent" value="'.$search_percent.'">';
		print '</td>';
	}

    if (! empty($arrayfields['e.level']['checked']))
    {
        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';
    }

	if (! empty($arrayfields['e.price_ht']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_price_ht" value="'.$search_price_ht.'">';
		print '</td>';
	}

	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Date de saisie
	if (! empty($arrayfields['e.datec']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="left">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_dday" value="'.$search_dday.'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_dmonth" value="'.$search_dmonth.'">';
		$formother->select_year($search_dyear?$search_dyear:-1,'search_dyear',1, 20, 5);
		print '</td>';
	}

	// Date modification
	if (! empty($arrayfields['e.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';

	if (! empty($arrayfields['e.fk_object']['checked']))            print_liste_field_titre($arrayfields['e.fk_object']['label'],$_SERVER["PHP_SELF"],'e.fk_object','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.percent']['checked']))            print_liste_field_titre($arrayfields['e.percent']['label'],$_SERVER["PHP_SELF"],'e.percent','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['e.level']['checked']))            print_liste_field_titre($arrayfields['e.level']['label'],$_SERVER["PHP_SELF"],'e.level','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['e.price_ht']['checked']))            print_liste_field_titre($arrayfields['e.price_ht']['label'],$_SERVER["PHP_SELF"],'e.price_ht','',$param,'',$sortfield,$sortorder);

	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (! empty($arrayfields['e.datec']['checked']))     print_liste_field_titre($arrayfields['e.datec']['label'],$_SERVER["PHP_SELF"],'e.datec','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.tms']['checked']))       print_liste_field_titre($arrayfields['e.tms']['label'],$_SERVER["PHP_SELF"],"e.tms","",$param,'align="left" class="nowrap"',$sortfield,$sortorder);

    print_liste_field_titre('', $_SERVER["PHP_SELF"],"",'',$param,'align="center"',$sortfield,$sortorder,'maxwidthsearch ');

	print '</tr>'."\n";

    $generic_prixmassecategorie = new PrixMasseCategorie($db);
    $generic_category = new Categorie($db);

	$generic_user = new User($db);

    $operators = $generic_prixmassecategorie->getOperators();
	$i=0;
	$totalarray=array();
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

		$generic_prixmassecategorie->id = $obj->rowid;
        $generic_prixmassecategorie->ref = $obj->ref;
        $generic_prixmassecategorie->percent = $obj->percent;
        $generic_prixmassecategorie->level = $obj->level;
        $generic_prixmassecategorie->price_ht = $obj->price_ht;
		$generic_prixmassecategorie->datec = $db->jdate($obj->datec);



		print '<tr class="oddeven">';

		// Ref
		if (! empty($arrayfields['e.fk_object']['checked']))
		{
			print '<td class="nowrap">';

            $cats = explode(';', $obj->fk_object);
            if (count($cats)) {
                foreach ($cats as $cat) {
                    $catop = explode(',', $cat);

                    $cat_id = $catop[0];
                    if ($cat_id > 0) {
                        $generic_category->fetch($cat_id); 
                        $generic_category->color = '255,255,255';           
                        print $generic_category->getNomUrl(0);
                    }

                    if (count($catop) > 1) {
                        $operator = $catop[1];
                        print ' '.(isset($operators[$operator]) ? '<strong>'.$operators[$operator].'</strong>' : '').' ';
                    }
                }
            }

			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		if (! empty($arrayfields['e.percent']['checked']))
		{
			print '<td align="left">';
			print $generic_prixmassecategorie->percent != 0 ? price($generic_prixmassecategorie->percent, 0, $langs, 1, -1, 2).'%' : '';
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

        if (! empty($arrayfields['e.level']['checked']))
        {
            print '<td align="left">';
            print isset($levels[$generic_prixmassecategorie->level]) ? $levels[$generic_prixmassecategorie->level] : '';
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

		if (! empty($arrayfields['e.price_ht']['checked']))
		{
			print '<td class="left">';
			print $generic_prixmassecategorie->price_ht > 0 ? price($generic_prixmassecategorie->price_ht, 0, $langs, 1, -1, -1, $conf->currency) : '';
			print '</td>';
		}

	
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// 
		if (! empty($arrayfields['e.datec']['checked']))
		{
			print '<td align="left">';
			print dol_print_date($db->jdate($obj->datec), 'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Date modification
		if (! empty($arrayfields['e.tms']['checked']))
		{
			print '<td align="left" class="nowrap">';
			print dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

        // Action column
        print '<td class="nowrap" align="center">';
        if ($i == 0 && $user->rights->prixmassecategorie->supprimer)
        {
            print '<a href="' . $_SERVER["PHP_SELF"] . '?action=delmod&amp;rowid=' . $obj->rowid . '">';
            print img_delete();
            print '</a>';
        }
        print '</td>';
        
		print "</tr>\n";

		$i++;
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>';

	print '</form>'."\n";

}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
