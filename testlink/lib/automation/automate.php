<!DOCTYPE html>
<html>
	<head>
		<title>Trigger Automation</title>
		<script src="/testlink/third_party/jquery/jquery-2.0.3.min.js"></script>
		<link rel="stylesheet" href="/testlink/gui/themes/default/css/testlink.css"/>
		<link rel="stylesheet" href="./css/dropDownStyle2.css"/>
		<link rel="stylesheet" href="./css/treeStyle2.css"/>
		<script src="./javascript/automation.js"></script>
		<?php
		require_once ("../../config.inc.php");
		require_once ("../functions/common.php");
		require_once ("../functions/xml.inc.php");
		testlinkInitPage($db);
		$templateCfg = templateConfiguration();
		$args = init_args();
		$projectName = $args -> tproject_name;
		$tproject_id = $args -> tproject_id;
		?>
	</head>
	<body>
		<?php
		$sql = 'select prefix from testprojects where id=' . $tproject_id;
		$result = $db -> exec_query($sql);
		$myrow = $db -> fetch_array($result);
		$project_prefix = $myrow['prefix'];

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
		<h1 class="title">Select Test Cases To Automate</h1>
		<form action="triggerAutomation.php" method="post">
		<div class="parent">
		<input type="text" name="projectName" value="<?php echo $projectName; ?>" readonly>
		<input type="hidden" value="<?php echo $project_prefix; ?>" name="project_prefix">
		<input type="hidden" id="browser_list" name="browser_list" value="NA">
		<input type="submit" value="Start Project Automation">
		<div class="x-tl-panel testcaseTree">
		<div class="x-tl-panel-header x-unselectable" id="ext-gen3"><span class="x-tl-panel-header-text" id="ext-gen6">Test Cases</span></div>
		<div class="x-tl-panel-bwrap" id="ext-gen4">
		<div class="x-tl-panel-body" id="ext-gen5" style="padding: 3px; background: rgb(200, 220, 232);">
		<ul class="treeUl">
		<?php 
		while($myrow = $db->fetch_array($result))
		{
			$ID = $myrow['id'];
			$name= $myrow['name'];
			$sql='select id, name from nodes_hierarchy where parent_id='.$ID.' and id in (select parent_id from nodes_hierarchy where id in (select id from tcversions where execution_type=2))';
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
		<li><label><input type="checkbox" class="checkbox-ch-<?php echo $ID; ?> tcCheckbox" name="testcase" value="<?php echo $row['name']; ?>" id="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></label>
		<?php
		$sql = 'select value from cfield_design_values inner join nodes_hierarchy on node_id=id where node_type_id=4 and parent_id=' . $row['id'];
		$fileNames = $db -> exec_query($sql);
		$fileRow = $db -> fetch_array($fileNames);
		?>
		<input type="hidden" name="id[]" class="files" id="files<?php echo $row['id']; ?>" value="<?php echo $fileRow['value']; ?>"></li>
		<?php
		}
		?>
		</ul></li>
		<?php
		}
		}
		?>
		</ul></div></div></div>
		<input type="button" value="Execute >>" class="execute">
		<div class="environment">
		<div class="environmentSelector">
			<select id="selectField" name="environmentSelector">
				<option selected hidden>Choose Environment</option>
				<option>Test</option>
				<option>Stage</option>
			</select>
		</div>
		</div>
		
		<div class="multiselect">
			<div class="selectBox">
				<select>
					<option>Select Browsers</option>
				</select>
				<div class="overSelect"></div>
			</div>
			<div id="checkboxes">
				<label for="chrome"><input type="checkbox" class="dropdownCheckBox" id="chrome" value="Chrome"/>Chrome</label>
				<label for="firefox"><input type="checkbox" class="dropdownCheckBox" id="firefox" value="Fire Fox"/>Fire Fox</label>
				<label for="safari"><input type="checkbox" class="dropdownCheckBox" id="safari" value="Safari"/>Safari</label>
			</div>
		</div>
		<input type="submit" value="Continue" class="continue">
		</div>
		</form>
	</body>
</html>