 <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
 <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  <script>
  $(function() {
    $( "#draggable" ).draggable();
  });
  </script>
	<div class="container">
		<div class="row">
			<div class="col-md-6"  id="draggable">
				<div class="panel panel-default">
        <div class="panel-heading testcaseInfoHeading">
          <h4>
          <img src="/testlink/gui/drag_and_drop/images/dhtmlgoodies_minus.gif" id="minmax"/>
            Testcase Information
          </h4>
        </div>
        <div class="testcaseInfo">
        <table class="table table-fixed simple_tableruler">
          <thead class="container tableHeading ">
            <tr class="row">
              <th class="col-xs-3">Module</th>
			  <th class="col-xs-2">Total Test Cases</th>
			  <th class="col-xs-2">Mark For Automation</th>
			  <th class="col-xs-2">Automated</th>
			  <th class="col-xs-2">Not Automatable</th>
			  <th class="col-xs-1">(%)</th>
            </tr>
          </thead>
          <tbody class="container">
          
          {foreach item=row from=$gui->tableRows}
            <tr class="row">
              <td class="col-md-3">{$row['testSuit']}</td>
              <td class="col-md-2">{$row['count']}</td>
              <td class="col-md-2">{$row['automatableCount']}</td>
              <td class="col-md-2">{$row['automatedCount']}</td>
              <td class="col-md-2">{$row['manualCount']}</td>
              <td class="col-md-1">{$row['automatedPercentage']}%</td>
            </tr>
           
          {/foreach}
          
          	 <tr class="row">
            	<td class="col-md-3">Total</td>
				<td class="col-md-2">{$gui->Totalcount}</td>
				<td class="col-md-2">{$gui->TotalautomatableCount}</td>
				<td class="col-md-2">{$gui->TotalautomatedCount}</td>
				<td class="col-md-2">{$gui->TotalmanualCount}</td>
				<td class="col-md-1"></td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>
			</div>
			
			</div>
		</div>

