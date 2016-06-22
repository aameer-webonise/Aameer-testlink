{*
@name - userDashboard.tpl
@desc - User Dashboard Template
@author - RSakhale
*}

{lang_get var='labels' s='title_testproject_management,wrelease_show_execution_portletarning_req_tc_assignment_impossible,href_cfields_management,
href_cfields_tproject_assign,href_assign_user_roles,href_keywords_manage,href_keywords_assign,href_platform_management,
href_issuetracker_management,href_search_tc,href_build_new,href_plan_mstones,dashboard_quicklinks,dashboard_testcases_pending_me,
no_records_found,project_progress,dashboard_testplan_progress,href_add_remove_test_cases,href_update_tplan,
href_newest_tcversions,href_tc_exec_assignment,href_plan_assign_urgency,href_platform_assign,dashboard_title_define_project_components,
dashboard_title_tc_plan_mgmt,dashboard_title_tl_config,dashboard_assign_roles_testplan,dashboard_report_based_product_area,desc_system_health,
dashboard_report_based_build,href_releases,href_my_testcase_assignments,dashboard_portlet_automation_status,href_cfr,dashboard_report_based_platform,dashboard_title_admin_config'}

{assign var="flag10" value="true" scope=global}
{include file="inc_head.tpl" popup="yes" openHead="yes"}
{include file="inc_ext_js.tpl" bResetEXTCss=1}	

{foreach from=$gui->tableSet key=idx item=matrix name="initializer1"}
	{assign var=tableID value=$matrix->tableID}
	{if $smarty.foreach.initializer1.first}
		{$matrix->renderCommonGlobals()}	
		{if $matrix instanceof tlExtTable}
			{include file="inc_ext_table.tpl"}
		{/if}
	{/if}	
	{$matrix->renderHeadSection()}
{/foreach}
{assign var=tplan_metric value=$gui->tplan_metrics}
<script type="text/javascript">
Ext.onReady(function() {ldelim}
	{foreach key=key item=value from=$gui->project_metrics}
    new Ext.ProgressBar({ldelim}
        text:'&nbsp;&nbsp;{lang_get s=$value.label_key}: {$value.value}% [{$tplan_metric.total.$key}/{$tplan_metric.total.active}]',
        width:'450',
        cls:'left-align',
        renderTo:'{$key}',       
        value:'{$value.value/100}'
    {rdelim});
    {/foreach}          
{rdelim});
</script>
<style type="text/css">
table.simple th{
	font: bold 0.8em Tahoma;
}
</style>
</head>
<body>
<div style="display:block;overflow:auto;">
<div class="quicklinks">
<div class="portletView">
<h2>{$labels.dashboard_quicklinks}</h2>
<span>
	{if $gui->grants.cfield_management == "yes" or $gui->grants.keywords_view == "yes"}
	<b>{$labels.dashboard_title_define_project_components}</b><hr />
	<ul>
    {if $gui->grants.cfield_management == "yes"}
	      	<li><a href="lib/cfields/cfieldsView.php">{$labels.href_cfields_management}</a></li>
			<li><a href="lib/cfields/cfieldsTprojectAssign.php">{$labels.href_cfields_tproject_assign}</a></li>
    {/if}
    {* --- keywords management ---  *}
	  {if $gui->grants.keywords_view == "yes"}
	  		<li><a href="lib/keywords/keywordsView.php">{$labels.href_keywords_manage}</a></li>
	  {/if} {* view_keys_rights *}
	  {* --- keywords management ---  *}
	  {if $gui->grants.keywords_view == "yes"}
	    {if $gui->grants.keywords_edit == "yes"}
  			<li><a href="{$gui->launcher}?feature=keywordsAssign">{$labels.href_keywords_assign}</a></li>
		  {/if}
	  {/if}
	  </ul>
	  {/if}
	  <b>{$labels.dashboard_title_tc_plan_mgmt}</b><hr />
	  <ul>	 
		{if $gui->grants.testplan_planning == "yes" and $gui->countPlans > 0}        	
           	<li><a href="lib/platforms/platformsAssign.php">{$labels.href_platform_assign}</a></li>					
			<li><a href="{$gui->launcher}?feature=planAddTC">{$labels.href_add_remove_test_cases}</a></li>	   
			<li><a href="{$gui->launcher}?feature=planUpdateTC">{$labels.href_update_tplan}</a></li>
	   		<li><a href="{$gui->launcher}?feature=newest_tcversions">{$labels.href_newest_tcversions}</a></li>
			<li><a href="{$gui->launcher}?feature=tc_exec_assignment">{$labels.href_tc_exec_assignment}</a></li>
	   	{/if}
	   	{if $gui->hasTestCases}
	  		<li><a href="{$gui->launcher}?feature=searchTc">{$labels.href_search_tc}</a></li>
      	{/if} 
      	{if $gui->grants.testplan_execute == "yes" and $gui->countPlans > 0}
      		<li><a href="lib/testcases/tcAssignedToUser.php">{$labels.href_my_testcase_assignments}</a></li>
      	{/if}
      	{if $gui->grants.mgt_release_view == "yes"}
      		<li><a href="lib/plan/releaseView.php">{$labels.href_releases}</a></li>
      	{/if}
      	{if $gui->grants.mgt_cfr_view == "yes"}
      		<li><a href="lib/plan/cfrView.php">{$labels.href_cfr}</a></li>
      	{/if}
       	{if $gui->grants.testplan_create_build == "yes" and $gui->countPlans > 0}
	    	<li><a href="lib/plan/buildView.php">{$labels.href_build_new}</a></li>
      	{/if} {* testplan_create_build *}
		{* Commented as this feature is not much used - RSakhale *}
		{* if $gui->grants.testplan_planning == "yes" and $session['testprojectOptions']->testPriorityEnabled  and $gui->countPlans > 0}
			<li><a href="{$gui->launcher}?feature=test_urgency">{$labels.href_plan_assign_urgency}</a></li>		    
		{/if*}
		</ul>
		 {if $gui->grants.project_edit == "yes" or $gui->grants.platform_management == "yes" or $gui->grants.issuetracker_management == "yes"}
			<b>{$labels.dashboard_title_tl_config}</b><hr />
			<ul>
		 	{if $gui->grants.project_edit == "yes"}
	  			<li><a href="lib/project/projectView.php">{$labels.title_testproject_management}</a></li>
    		{/if}
     		{if $gui->grants.mgt_config_view == "yes"}
     			<li><a href="lib/general/adminView.php">{$labels.dashboard_title_admin_config}</a></li>
     		{/if}
			{if $gui->grants.platform_management == "yes"}
	  			<li><a href="lib/platforms/platformsView.php">{$labels.href_platform_management}</a></li>
		{/if}
			{if $gui->grants.issuetracker_management == "yes"}
	  			<li><a href="lib/issuetrackers/issueTrackerView.php">{$labels.href_issuetracker_management}</a></li>
			{/if}
			{if $gui->grants.system_health == "yes"}
				<li><a href="third_party/phpsysinfo-3.1.6/">{$labels.desc_system_health}</a></li>
			{/if}
		 	</ul>		 
      	{/if}
      	<b>Detail Report Dashboard</b><hr />
      	<ul>
      	<li><a href="lib/general/automationDashboard.php">Automation Detail Report	</a></li>
      	</ul>
			
</span>
</div>
</div>
<div class="leftPortlets">
{if $gui->count_tplan_metrics > 0}
<div class="portletView">
<h2><span style="float: right;clear: both">{if $gui->grants.mgt_cfr_view == 'yes'}<a href="lib/results/resultsByCFR.php?tprojectID={$gui->tproject_id}">CFR Report</a> | {/if}<a href="lib/results/traceabilityReport.php?tproject_id={$gui->tproject_id}">View Traceability</a> | <a href="lib/results/metricsDashboard.php">View All Metrics</a></span>{$labels.project_progress} for {$gui->release_name}</h2>
<div style="float:right;clear:both" id="overallPie">
<img src="{$gui->pieUrl}" />
</div>
<span>
{foreach from=$gui->project_metrics key=key item=metric}
	<div id="{$key}"></div>
	{if $key == "executed"}
		<br />
	{/if}
{/foreach}
</span>
</div>
{/if}
{if $gui->release_show_execution_portlet}
<div style="width: 49.5%;float: left;">
{if count($gui->custom_testplan_metrics) > 0}
<div class="portletView">
<h2>{$labels.dashboard_report_based_product_area}</h2>
<table class="simple">
<tr><th>Product Name</th><th>Total</th><th>Passed</th><th>Failed</th><th>Blocked</th><th>Not Run</th><th> % Execution</th></tr>
{foreach from=$gui->custom_testplan_metrics key=key item=metric name="outer"}{*DG Added*}
 {assign var="flag10" value="true"} 
 {foreach from=$gui->regressionMetrics item=metric1 name="inner"}
 {if $metric1.product_area == $metric.name } 
 
<tr><td>{$metric.name}</td><td>{$metric.total-$metric1.total}</td><td>{$metric.passed-$metric1.passed}</td><td>{$metric.failed-$metric1.failed}</td><td>{$metric.blocked-$metric1.blocked}</td><td>{$metric.not_run-$metric1.not_run}</td><td>{math equation= "round((( (x -z) - ( y - a) ) * 100 )/ (x-z))" x=$metric.total y=$metric.not_run z=$metric1.total a=$metric1.not_run }</td></tr>
   {assign var="flag10" value="false"}
  
  {break name="inner"}
  {/if}
  
{/foreach}
  
  {if $flag10 == "true"}
 <tr><td>{$metric.name}</td><td>{$metric.total}</td><td>{$metric.passed}</td><td>{$metric.failed}</td><td>{$metric.blocked}</td><td>{$metric.not_run}</td><td>{math equation= "round((( x - y ) * 100 )/x)" x=$metric.total y=$metric.not_run  }</td></tr>
 
  {/if}
{/foreach}
</table>
</div>
{/if}
{*
{if count($gui->platformReport) > 0}
<div class="portletView">
<h2>{$labels.dashboard_report_based_platform}</h2>
<table class="simple">
<tr><th>Platform Name</th><th>Total</th><th>Passed</th><th>Failed</th><th>Blocked</th><th>Not Run</th></tr>
{foreach from=$gui->platformReport key=key item=metric}
<tr><td>{$metric.platform_name}</td><td>{$metric.total}</td><td>{$metric.passed}</td><td>{$metric.failed}</td><td>{$metric.blocked}</td><td>{$metric.not_run}</td></tr>
{/foreach}
</table>
</div>
{/if}
*}
{if count($gui->regressionMetrics) > 0}
<div class="portletView">
<h2><span style="float: right;clear: both"><a href="lib/results/regressionResult.php?release_id={$gui->release_id}">View Detailed Report</a></span>Regression Metrics</h2>
<table class="simple">
<tr><th>Product Area</th><th>Total</th><th>Passed</th><th>Failed</th><th>Blocked</th><th>Not Run</th><th> % Execution</th></tr>
{foreach from=$gui->regressionMetrics item=metric}
<tr>
	<td>{$metric.product_area}</td>
	<td>{$metric.total}</td>
	<td>{$metric.passed}</td>
	<td>{$metric.failed}</td>
	<td>{$metric.blocked}</td>
	<td>{$metric.not_run}</td>
{*DG Added*}	<td>{math equation= "round((( x - y ) * 100 )/x)" x=$metric.total y=$metric.not_run  }</td>
</tr>
{/foreach}
</table>
</div>
{/if}
</div>
{* Commented as this feature is not much used - Dgogawale *}
{*
<div style="width: 49%;float: right;">
{if count($gui->buildReport) > 0}
<div class="portletView">
<h2><span style="float: right;clear: both"><a href="lib/results/buildReport.php?trpoject_id={$gui->tproject_id}">View All Builds</a></span>{$labels.dashboard_report_based_build}</h2>
<table class="simple">
<tr><th>Build Name</th><th>Total</th><th>Passed</th><th>Failed</th><th>Blocked</th></tr>
{foreach from=$gui->buildReport key=key item=metric}
<tr><td>{$metric.build_name}</td><td>{$metric.total}</td><td>{$metric.passed}</td><td>{$metric.failed}</td><td>{$metric.blocked}</td></tr>
{/foreach}
</table>
</div>
{/if}
*}
</div>
{/if}
</div>
</div>
{*DG- Added new automation report *}
{if $gui->showAutomationStatusPortlet}

<div class="portletView" style="margin:0 1% 1% 1%;width:98%">
<h2><span style="float: right;clear: both"> <a href="lib/results/resultsAutomationStatusByTestPlan.php?releaseID={$gui->release_id}">View By Test Plan for selected release |<a href="lib/results/resultsAutomationStatusByTestPlan.php?trpoject_id={$gui->tproject_id}">View By Test Plan for all releases </a> </span>{$labels.dashboard_portlet_automation_status} </h2>

<table class="simple">
<tr>
{foreach from=$gui->automationStatusColumns key=key item=asColumn}
<th>{$asColumn}</th>
{/foreach}
<th> % Automated </th>
</tr>
 
{foreach from=$gui->automationStatusReport key=key item=asDetailRow}
<tr>
    {counter assign = count start=1 }
	{foreach from=$asDetailRow key=innerKey item=asDetail}	
	
	      
	    <td>{$asDetail}</td>
	      {counter}
	   
	     {*DG added for % automation *}
	     {if $count == 4	 }
	      {assign var= count_3 value=$asDetail }
	      
	      {/if}
	
	   
	      
	       {if $count == 9 }
	      {assign var= count_9 value=$asDetail }
	      
	      {/if}
	  
	 {/foreach}
	  <td> {math equation= "round(( x * 100 )/ (y))" x=$count_3 y=$count_9  } </td>
	 {*Dg Added 
	 {foreach from=$gui->custom_testplan_metrics key=key item=metric}
	   {foreach from=$asDetailRow key=innerKey item=asDetail}	
	      
	    
	         {if $metric.name == $asDetail and $asDetail != '0'   }
	            
	            <td>{$metric.total}</td>
	       {/if}
	      
	    {/foreach}
	  {/foreach}
	  *}
</tr>

 
 {/foreach}
</table>
</div>


{/if}
{if $gui->resultSet ne null}
<div class="portletView" style="margin:0.5% 1% 1% 1%;width:98%">
<h2>{$labels.dashboard_testcases_pending_me}<a style="float: right;margin-right: 15px;" href="lib/testcases/tcAssignedToUser.php">View All</a></h2>
	{foreach from=$gui->tableSet key=idx item=matrix}		
		{if $idx != 'tl_table_metrics_dashboard'}
		{assign var=tableID value=$matrix->tableID}
		{$matrix->renderBodySection()}
		{/if}
	{/foreach}
</div>
{/if}
<div class="portletView" style="margin:0.5% 1% 1% 1%;width:98%">
<h2>{$labels.dashboard_testplan_progress}  {$gui->release_name}<span style="float: right;margin-right: 15px;"><a href="lib/results/testplanProgressReport.php?tproject_id=88008&release_id={$gui->release_id}&format=0"><img title="View Test Plan Progress Report" src="{$tlImages.direct_link}" /></a> <a href="lib/results/testplanProgressReport.php?tproject_id=88008&release_id={$gui->release_id}&format=3"><img src="{$tlImages.xls}" title="Download Test Plan Progress report" /></a></span></h2>
{foreach from=$gui->tableSet key=idx item=matrix}
		{if $idx == 'tl_table_metrics_dashboard'}
			{assign var=tableID value=$matrix->tableID}
   			{$matrix->renderBodySection()}
   		{/if}
{/foreach}
</div>
</body>
</html>