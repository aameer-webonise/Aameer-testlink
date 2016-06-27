
 <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  <script>
  $(function() {
    $( "#draggable" ).draggable({
    	containment: ".dragParent",
    	stop: function(event, ui) {
		    // Show dropped position.
		     var Stoppos = $(this).position();
		    //document.write("STOP: \nLeft: "+ Stoppos.left + "\nTop: " + Stoppos.top);
		}
    });
  });
  </script>
  <div class="dragParent">
	<div class="container">
		<div class="row">
			<div class="col-md-6"  id="draggable">
				<div class="panel panel-default">
        <div class="panel-heading testcaseInfoHeading">
          <h4>
          <img src="/testlink/gui/drag_and_drop/images/dhtmlgoodies_minus.gif" id="minmax"/>
            Module wise automation information
          </h4>
        </div>
        <div class="testcaseInfo">
        <table class="table table-fixed simple_tableruler">
          <thead class="container tableHeading ">
            <tr class="row">
              <th class="col-xs-2">Module</th>
			  <th class="col-xs-2">Total Test Cases</th>
			  <th class="col-xs-2">Mark For Automation</th>
			  <th class="col-xs-2">Automated</th>
			  <th class="col-xs-2">Not Automatable</th>
			  <th class="col-xs-2">Automated (%)</th>
            </tr>
          </thead>
          <tbody class="container">
          {foreach item=row from=$gui->tableRows}
            <tr class="row">
              <td class="col-md-2 max" title="{$row['testSuit']}">{$row['testSuit']}</td>
              <td class="col-md-2 max">{$row['count']}</td>
              <td class="col-md-2 max">{$row['automatableCount']}</td>
              <td class="col-md-2 max">{$row['automatedCount']}</td>
              <td class="col-md-2 max">{$row['manualCount']}</td>
              <td class="col-md-2">{$row['automatedPercentage']}%</td>
            </tr>
           
          {/foreach}
        <!--  <tfoot class="container">
          	 <tr class="row">
            	<td class="col-md-2">Total</td>
				<td class="col-md-2">{$gui->Totalcount}</td>
				<td class="col-md-2">{$gui->TotalautomatableCount}</td>
				<td class="col-md-2">{$gui->TotalautomatedCount}</td>
				<td class="col-md-2">{$gui->TotalmanualCount}</td>
				<td class="col-md-2">{$gui->totalAutomatedPer}%</td>
            </tr>
          <tfoot>-->
          </tbody>
        </table>
        </div>
      </div>
			</div>
			
			</div>
			
		</div>
</div>
