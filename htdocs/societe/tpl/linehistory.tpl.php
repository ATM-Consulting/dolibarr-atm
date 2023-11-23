<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}
// print '<div class="fichecenter">';
//         print '<table class="border tableforfield" width="100%">';
dol_include_once('/deviscaraiso/class/history.class.php');
$histo=new Histo($db);
$histo->fetch($object->id);
$histo->get_rendezvous();
$histo->get_devis();
$histo->get_pose();


$out .= '<div class="div-table-responsive-no-min">';
$out .= '<table class="noborder centpercent" >';

$out .= '<tr class="liste_titre">';
$out .= '<td class="liste_titre" colspan=4><b>'.$langs->trans('Suivi des rendez vous').'</b></td>';
$out .= '</tr>';
$out .= '<tr class="liste_titre">';
$out .= '<td class="liste_titre">Date</td><td class="liste_titre">Utilisateur</td><td class="liste_titre">Commentaire</td><td class="liste_titre">Status</td>';
$out .= '</tr>';

foreach($histo->linesrdv as $key=>$line){
	$date_planif=new DateTime($line->datep);
	$url_agenda=dol_buildpath('comm/action/index.php',1).'?userid='.$line->fk_user_action.'&day='.$date_planif->format("Y-m-d");
	$out.= '<tr><td width="150"><a href="'.$url_agenda.'">'.$date_planif->format("d/m/Y").'</a><td width="200">'.$line->firstname.' '.$line->lastname.'</td>';
	$out.= '<td>'.$line->note.'</td><td><div class="status-list">
	<span class="circle" style="background:'.$line->color.'">&nbsp;'.$line->name.'&nbsp;</span>
	</div></td></tr>';
	
}

//$out .= '<td class="liste_titre maxwidth100onsmartphone"><input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="'.$filters['search_agenda_label'].'"></td>';
// Action column
//$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
$out .= '</tr>';

print $out;


print '</td></tr></table></div>';
$out = '<div class="div-table-responsive-no-min">';
$out .= '<table class="noborder centpercent" >';

$out .= '<tr class="liste_titre">';
$out .= '<td class="liste_titre" colspan=5><b>'.$langs->trans('Suivi des devis').'</b></td>';
$out .= '</tr>';
$out .= '<tr class="liste_titre">';
$out .= '<td class="liste_titre">Date Planification</td><td class="liste_titre">Commercial</td><td>Type</td><td class="liste_titre">Commentaire</td><td>Status</td>';
$out .= '</tr>';
include_once DOL_DOCUMENT_ROOT.dol_buildpath("deviscaraiso/class/deviscaraiso.class.php",1);
$iso=new deviscaraiso($db);
include_once DOL_DOCUMENT_ROOT.dol_buildpath("deviscararep/class/deviscararep.class.php",1);
$rep=new deviscararep($db);
foreach($histo->lines as $key=>$line){
	$url=dol_buildpath('deviscara'.$line->ext.'/card.php',1).'?id='.$line->id_devis;
	$date_planif=new DateTime($line->date_planif);
	//$url_agenda=dol_buildpath('comm/action/index.php',1).'?userid='.$line->fk_usercomm.'&day='.$date_planif->format("Y-m-d");
	$url=dol_buildpath('deviscara'.$line->type.'/card.php',1).'?id='.$line->rowid;
	$out.='<tr><td width="150"><a href='.$url.'>'.$line->date_creation.'</a></td><td width="200">'.$line->firstname.' '.$line->lastname.'</td>';
	$out.='<td><a href="'.$url.'">'.$line->type.'</a></td>';
	$out.='<td>'.$line->description.'</td>';
	if($line->type=='rep'){
		$rep->planification=$line->status;
		$out.='<td>'.$rep->LibStatutPlanif(5,$url).'</td>';
	}
	else{
		$iso->planification=$line->status;
		$out.='<td>'.$iso->LibStatutPlanif(5,$url).'</td>';
	}
	$out.'</tr>';
	
}
print $out;
print '</td></tr></table></div>';

$out = '<div class="div-table-responsive-no-min">';
$out .= '<table class="noborder centpercent">';
$out.= '<tr class="liste_titre"><td  colspan=6><b>';
$out.= $langs->trans('Suivi des poses');
$out.= '</b></td>';
$out .= '</tr>';
$out .= '<tr class="liste_titre">';
$out .= '<td class="liste_titre">Date planification</td><td class="liste_titre">Poseur</td><td>type</td><td class="liste_titre">Commentaire</td><td>Status</td><td>Facturation</td>';
$out .= '</tr>';


foreach($histo->linespos as $key=>$line){
	
	$url=dol_buildpath('deviscara'.$line->ext.'/card.php',1).'?id='.$line->id_devis;
	$date_planif=new DateTime($line->date_planif);
	$url_agenda=dol_buildpath('comm/action/index.php',1).'?userid='.$line->fk_user_pos.'&day='.$date_planif->format("Y-m-d");
	
	$out.= '<tr><td width="150"> <a href="'.$url_agenda.'">'.$date_planif->format("d/m/Y").'</a><td width="200">'.$line->firstname.' '.$line->lastname.'</td>';
	$out.= '<td><a href="'.$url.'">'.$line->ext.'</a></td>';
	$out.= '<td>'.$line->description.'</td>';
	$out.='<td>'.$histo->LibStatutPose($line->status).'</td>';
	$url_facture=$url=dol_buildpath('/deviscarapos/core/actions_facturations.php', 1).'?action=create&fk_soc='.$object->id.'&type='.$line->ext.'&id='.$line->id_devis.'&id_pos='.$line->rowid;
	if($user->rights->facture->creer)
		$out.='<td><a class="badge  badge-status1 badge-status" href="'.$url_facture.'">Facturer</a></td>';
	else
	$out.='<td></td>';
	$out.='</tr>';
	
}
$out.= '</td></tr>';
$out.= '</table></div>';
print $out;