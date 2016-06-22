<?php 
/*require_once("../../config.inc.php");
require_once("../functions/common.php");
require_once("../functions/xml.inc.php");
testlinkInitPage($db);*/
$conn = new mysqli('localhost','root','','testlink');

$order = $_GET["order"];
$obj = json_decode($order);
$fileName = $obj -> {"xmlFile"};
//$projectName=$obj -> {"projectName"};
$status=$obj -> {"status"};

$projectName='Proof';
var_dump($fileName);
var_dump($status);
//$fileName='automation.xml';
//echo $fileName."<br/>";
/*$sql='update cfield_design_values set status='.$status.' where id in (select id from cfield_design_values inner join nodes_hierarchy on node_id=id where node_type_id=4 and value='.$fileName.' and parent_id in (select id from nodes_hierarchy where node_type_id=2 and parent_id in (select id from nodes_hierarchy where name='.
$projectName.')))';*/

//$sql='select id from cfield_design_values where value='.$fileName.' and node_id in (select id from nodes_hierarchy where parent_id in (select id from nodes_hierarchy where node_type_id=3 parent_id in (select id from nodes_hierarchy where name='.
//$projectName.' and node_type_id=2)))';

/*$sql="select id from cfield_design_values inner join nodes_hierarchy on node_id = id inner join tcversions on node_id=tcversions.id where value='".$fileName."' and execution_type=2 and nodes_hierarchy.parent_id in (select id from nodes_hierarchy where parent_id in (select id from nodes_hierarchy where parent_id in (select id from nodes_hierarchy where name='".$projectName."')))";
$result=$db->exec_query($sql);
while($myrow = $db->fetch_array($result)){
	echo $myrow['id'].'<br/>';
}*/
?>