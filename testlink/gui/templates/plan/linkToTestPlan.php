<!DOCTYPE html>
<html>
	<head>
		<title>Link Testcases To Testplan</title>
		<script src="/testlink/third_party/jquery/jquery-2.0.3.min.js"></script>
		<link rel="stylesheet" href="/testlink/gui/themes/default/css/testlink.css"/>
		<!--<link rel="stylesheet" href="./css/dropDownStyle2.css"/>-->
		<link rel="stylesheet" href="/testlink/lib/automation/css/treeStyle.css"/>
		<!--<script src="/testlink/lib/automation/javascript/automation1.js"></script>-->
		<script src="/testlink/gui/themes/default/javascript/testplan.js"></script>
		<?php
			require_once ("../../../config.inc.php");
			require_once ("../../../lib/functions/common.php");
			require_once ("../../../lib/functions/xml.inc.php");
			testlinkInitPage($db);
			$templateCfg = templateConfiguration();
			$args = init_args();
			$projectName = $args -> tproject_name;
			$tproject_id = $args -> tproject_id;
			$tpID=$_GET["tpID"];
			$tpName=$_GET["Name"];
			//var_dump($tpID);
			//var_dump($tpName);
			
		?>
	</head>
	<body>
		<?php
			$sql = 'select prefix from testprojects where id=' . $tproject_id;
			$result = $db -> exec_query($sql);
			$myrow = $db -> fetch_array($result);
			$project_prefix = $myrow['prefix'];
			
			$sql='select id,name from nodes_hierarchy where node_type_id=5 and parent_id='.$tproject_id;
			$testPlanResult = $db -> exec_query($sql);
			
			
			$sql = 'select id, name from nodes_hierarchy where node_type_id=2 and (parent_id=' . $tproject_id . ' or parent_id in (select id from nodes_hierarchy where node_type_id=2 and parent_id=' . $tproject_id . '))';
			//$sql='select id, name from nodes_hierarchy where node_type_id=2 and parent_id='.$tproject_id;
			$result = $db -> exec_query($sql);
			function init_args() {
				$_REQUEST = strings_stripSlashes($_REQUEST);
				$args = new stdClass();
				$args -> tproject_name = $_SESSION['testprojectName'];
				$args -> tproject_id = $_SESSION['testprojectID'];
				return $args;
			}
		?>
		
		
		<form action="/testlink/gui/templates/plan/addToTestPlan2.php?tpName=<?php echo $tpName?>&tpID=<?php echo $tpID; ?>" method="post">
		<div class="parent">
		<input type="hidden" value="<?php echo $project_prefix; ?>" name="project_prefix">
		<input type="hidden" id="browser_list" name="browser_list" value="NA">
		<div class="x-tl-panel testcaseTree">
		<div class="x-tl-panel-header x-unselectable" id="ext-gen3"><span class="x-tl-panel-header-text" id="ext-gen6">Select Testcases to be Linked With <?php echo $tpName; ?></span></div>
		<div class="x-tl-panel-bwrap" id="ext-gen4">
		<div class="x-tl-panel-body" id="ext-gen5" style="padding: 3px; background: rgb(200, 220, 232);">
		<ul class="treeUl">
		<?php 
			while($myrow = $db->fetch_array($result))
			{
				$ID = $myrow['id'];
				$name= $myrow['name'];
				$sql='select id,name from nodes_hierarchy where parent_id='.$ID.' and node_type_id=3';
				//$sql='select id, name from nodes_hierarchy where parent_id='.$ID.' and id in (select parent_id from nodes_hierarchy where id in (select id from tcversions where execution_type=2))';
				//$sql='select n2.id as tcID, n2.name as tcName ,n1.name as tsName from nodes_hierarchy n1 inner join nodes_hierarchy n2 on n2.parent_id=n1.id inner join nodes_hierarchy n3 on n3.parent_id=n2.id inner join tcversions tc on n3.id=tc.id where n1.node_type_id=2 and tc.execution_type=2 and n1.parent_id='.$tproject_id;
				$data=$db->exec_query($sql);
				if($data->_numOfRows>0)
				{
		?>
		<img src="/testlink/gui/drag_and_drop/images/dhtmlgoodies_plus.gif" id="<?php echo $ID; ?>" class="testSuiteName">
		<label><input type="hidden" value="+" id="status<?php echo $ID?>">
			<img src="/testlink/gui/drag_and_drop/images/dhtmlgoodies_folder.gif">
			<input type="checkbox" class="suiteCheckbox" id="ch-<?php echo $ID; ?>">
			<span style="cursor: pointer;"><?php echo $name; ?></span>
		</label>
		<input type="hidden" name="suiteName[]" id="namech-<?php echo $ID?>" class="suite" value="<?php echo $name; ?>">
		<li><ul class="testcaseList" id="tc<?php echo $ID; ?>" class="treeUl">
		<?php
		while($row=$db->fetch_array($data))
		{
		?>
		<?php 
				$sql='select id from nodes_hierarchy where node_type_id=4 and parent_id='.$row['id'].' and id not in (select tcversion_id from testplan_tcversions where testplan_id='.$tpID.')';	
				$tcversion=$db->exec_query($sql);
				$tcversionIDRow=$db->fetch_array($tcversion);
				if($tcversion->_numOfRows>0)
				{
			?>
		<li>
			<label><input type="checkbox" class="checkbox-ch-<?php echo $ID; ?> testcaseCheckbox" name="testcase" value="<?php echo $row['name']; ?>" id="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></label>
			<input type="hidden" value="<?php echo $tcversionIDRow['id'];?>" name="tcversions[]" class="tcversion" id="tcversion_<?php echo $row['id'];?>">
		</li>
		<?php
				}
		}
		?>
		</ul></li>
		<?php
		}
		}
		?>
		</ul></div></div></div>
		</div>
	
	<!--	<div>
			<span>Select Test Plan</span>
			<select name="testPlan" class="testPlan">
				<option selected hidden>--Select Test Plan--</option>
			<?php 
			while($testPlan = $db -> fetch_array($testPlanResult)){
			?>
				<option id="<?php echo $testPlan['id']; ?>"><?php echo $testPlan['name']; ?></option>
			<?php
			}
			?>
			</select>
			
			<input type="text" name="testplanID" value="none" id="testplanID">
	</div>-->
		<input type="submit" value="Add Testcases">
		
		
		</form>
	</body>
</html>