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
		define('MANUAL', '1');
		define('AUTOMATED', '2');
		define('AUTOMATABLE', '3');
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
		
		$noOfAutomatedTC=countTestcases($db,$args->tproject_id,AUTOMATED);
		$noOfAutomableTC=countTestcases($db,$args->tproject_id,AUTOMATABLE);
		$noOfManualTC=countTestcases($db,$args->tproject_id,MANUAL);
		
		$perc_automated=  calculatePercentage($noOfAutomatedTC,$no_of_tc);
		$perc_automable=  calculatePercentage($noOfAutomableTC,$no_of_tc);
		$perc_manual=  calculatePercentage($noOfManualTC,$no_of_tc);
		
		function calculatePercentage($value,$total){
			return number_format(($value/$total)*100, 2, '.', '');
		}
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
					<table class="simple_tableruler testcasesReport">
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
						
						</tbody>
						<tfoot>
							<tr>
								<td>Total Testcases</td>
								<td colspan="2"><?php echo $no_of_tc; ?></td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</body>
</html>