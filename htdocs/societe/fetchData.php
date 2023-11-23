<?php
require '../main.inc.php';
if(isset($_POST['search']))
    $search = $_POST['search'];
else $search='vitel';
   
$sql= 'select rowid, nom from llx_societe where nom like "%'.$search.'%" or phone like "%'.$search.'%" or fax like "%'.$search.'%" or url like "%'.$search.'%" order by nom limit 20';
// or 
$resql = $db->query($sql);
if ($resql)
{
    $num_rows = $db->num_rows($resql);
    $i = 0;
    while ($i < $num_rows)
    {
        $obj = $db->fetch_object($resql);
        if ($obj)
        {
            $response[]=array('value'=>$obj->rowid,'label'=>$obj->nom);
        }
        $i++;
    }

    echo json_encode($response);
}
?>