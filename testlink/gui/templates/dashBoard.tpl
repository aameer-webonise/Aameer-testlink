<link rel="stylesheet" href="/testlink/gui/themes/default/css/dashBoard.css"/>
<script src="/testlink/gui/themes/default/javascript/dashboard.js"></script>

	<div class="dashBoard testcaseDashboard">
		<div class="dashboardHeading">
			<span>
			<img src="/testlink/gui/drag_and_drop/images/dhtmlgoodies_minus.gif" id="minmax"/>
			Testcase Dashboard
			</span>
		</div>
	
		<div class="dashBoardContent">
			<table class="simple_tableruler testcaseInfo">
				 <thead class="testcaseInfoHeading">
				 <tr>
					<th>Module</th>
					<th>Total Test Cases</th>
					<th>Mark For Automation</th>
					<th>Automated</th>
					<th>Not Automatable</th>
					<th>Automated Percentage</th>
				 </tr>
				 </thead>
				 <tbody class="testcaseInfoBody">
				{foreach item=row from=$gui->tableRows}
					<tr>
						<td>{$row['testSuit']}</td>
						<td>{$row['count']}</td>
						<td>{$row['automatableCount']}</td>
						<td>{$row['automatedCount']}</td>
						<td>{$row['manualCount']}</td>
						<td>{$row['automatedPercentage']}%</td>
					<tr>
				{/foreach}
				 </tbody>
				 <tfoot>
				 	<tr>
				 		<td>Total</td>
				 		<td>{$gui->Totalcount}</td>
						<td>{$gui->TotalautomatableCount}</td>
						<td>{$gui->TotalautomatedCount}</td>
						<td>{$gui->TotalmanualCount}</td>
				 	<tr>
				 </tfoot>
			</table>
		</div>
		
	</div>
