<?php 
require_once ("../../../config.inc.php");
require_once ("../../../lib/functions/common.php");
require_once ("../../../lib/functions/xml.inc.php");
testlinkInitPage($db);
$tpName=  $_GET["tpName"];
$tpID=  $_GET["tpID"];
$tcversion=$_POST["tcversions"];
$tcversionCount=count($tcversion);
$flag=true;
 if($tcversionCount>0){
	 for($index=0;$index<$tcversionCount;$index++){
		 $sql="insert into testplan_tcversions (testplan_id,tcversion_id,platform_id) values('".$tpID."','".$tcversion[$index]."',1)";
		 $result=$db->exec_query($sql);
		 if(!result){
		 	$flag=FALSE;
			 break;
		 }
	 }
	 if($flag){
		 echo "Testcases Added to $tpName Test Plan";
	 }
	 else{
	 	echo "Problem";
	 }

 }
 else{
	 echo "testcase not selected";
 }
?>