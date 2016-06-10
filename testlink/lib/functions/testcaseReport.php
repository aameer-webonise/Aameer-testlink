<html>
	<head>
		<title>
			Testcase Report
		</title>
		<link rel="stylesheet" href="/testlink/gui/themes/default/css/testlink.css"/>
		<link rel="stylesheet" href="/testlink/gui/themes/default/css/customcss.css"/>
		<?php
		require_once ("../../config.inc.php");
		require_once ("common.php");
		function init_args() {
				$_REQUEST = strings_stripSlashes($_REQUEST);
				$args = new stdClass();
				$args -> tproject_name = $_SESSION['testprojectName'];
				$args -> tproject_id = $_SESSION['testprojectID'];
				return $args;
			}
		testlinkInitPage($db);
		$args = init_args();
		$projectName = $args -> tproject_name;
		$tproject_id = $args -> tproject_id;
		$tproject_mgr = new testproject($db);
		$no_of_tc=$tproject_mgr->count_testcases($tproject_id);
		$sql='select count(*) as num from nodes_hierarchy where parent_id in (select id from nodes_hierarchy where node_type_id=2 and (parent_id=3535 or parent_id in (select id from nodes_hierarchy where node_type_id=2 and parent_id=3535))) and id in (select parent_id from nodes_hierarchy where id in (select id from tcversions where execution_type=2))';
		$data=$db->exec_query($sql);
		if($data->_numOfRows>0)
		{
			$row=$db->fetch_array($data);
			$noOfAutomatedTC=$row['num'];
		}
		
		$sql='select count(*) as num from nodes_hierarchy where parent_id in (select id from nodes_hierarchy where node_type_id=2 and (parent_id=3535 or parent_id in (select id from nodes_hierarchy where node_type_id=2 and parent_id=3535))) and id in (select parent_id from nodes_hierarchy where id in (select id from tcversions where execution_type=3))';
		$data=$db->exec_query($sql);
		if($data->_numOfRows>0)
		{
			$row=$db->fetch_array($data);
			$noOfAutomableTC=$row['num'];
		}
		
		$sql='select count(*) as num from nodes_hierarchy where parent_id in (select id from nodes_hierarchy where node_type_id=2 and (parent_id=3535 or parent_id in (select id from nodes_hierarchy where node_type_id=2 and parent_id=3535))) and id in (select parent_id from nodes_hierarchy where id in (select id from tcversions where execution_type=1))';
		$data=$db->exec_query($sql);
		if($data->_numOfRows>0)
		{
			$row=$db->fetch_array($data);
			$noOfManualTC=$row['num'];
		}
		
		$perc_automated=  number_format(($noOfAutomatedTC/$no_of_tc)*100, 2, '.', '');
		$perc_automable=  number_format(($noOfAutomableTC/$no_of_tc)*100, 2, '.', '');
		$perc_manual=  number_format(($noOfManualTC/$no_of_tc)*100, 2, '.', '');
		?>
	</head>
	<body>
		<div class="x-tl-panel testcaseTree">
			<div class="x-tl-panel-header x-unselectable" id="ext-gen3">
				<h1 class="title">Testcase Report</h1>
			</div>
			<div class="x-tl-panel-bwrap" id="ext-gen4">
				<div class="x-tl-panel-body" id="ext-gen5" style="padding: 3px; background: rgb(200, 220, 232);">
					<table>
						<tbody>
							<tr>
								<td>Test Project</td><td> : </td>
								<td>
									<span style="color:black; font-weight:bold; text-decoration: underline;"><?php echo $projectName; ?></span>
								</td>
							</tr>
						</tbody>
					</table>
					<table class="testcasesReport">
						<tbody>
						<tr>
							<th></th>
							<th></th>
							<th>Percent(%)</th>
						</tr>
						<tr>
							<td>No. Of Automated Testcases</td>
							<td><?php echo $noOfAutomatedTC; ?></td>
							<td><?php echo $perc_automated."%";?></td>
						</tr>
						<tr>
							<td>No. Of Automatable Testcases</td>
							<td><?php echo $noOfAutomableTC; ?></td>
							<td><?php echo $perc_automable."%";?></td>
						</tr>
						<tr>
							<td>No. Of Manual Testcases</td>
							<td><?php echo $noOfManualTC; ?></td>
							<td><?php echo $perc_manual."%";?></td>
						</tr>
						<tr>
							<td>Total Testcases</td>
							<td><?php echo $no_of_tc; ?></td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</body>
</html>