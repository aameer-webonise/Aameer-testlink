{*
@name - automationDashboard.tpl
@desc - automation Dashboard Template
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
{*
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
*}


<div style="width: 20.5%;float: align=center;margin-left:12.5px;">
<h2><span style="float: right;clear: both"></span> Total Automation %  </h2>
<table class="simple">
<tr><th>Total Automation % 
{*DG Added*}	<td>{math equation= "round((( y) * 100 )/x)" x=$gui->Total_cases y=$gui->AD_Total }</td>
</th>
</tr>
<tr><th>Total Automation Backlog % 
{*DG Added*}	<td>{math equation= "round((( y + z+ w) * 100 )/x)" x=$gui->Total_cases y=$gui->AR_Total z=$gui->ANR_Total w=$gui->ANM_Total}</td>
</th>
</tr>
<tr><th>Total Cannot be Automated % 
{*DG Added*}	<td>{math equation= "round((( y) * 100 )/x)" x=$gui->Total_cases y=$gui->CA_Total}</td>
</th>
</tr>
</table>
</div>



<div class="portletView" style="margin:0 1% 1% 1%;width:98%">
<h2><span style="float: right;clear: both"> <a href="lib/results/resultsAutomationStatusByTestPlan.php?releaseID={$gui->release_id}">View By Test Plan for selected release |<a href="lib/results/resultsAutomationStatusByTestPlan.php?trpoject_id={$gui->tproject_id}">View By Test Plan for all releases </a> </span> Total Module based Automation Status </h2>
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
	
	   
	      
	       {if $count == 8 }
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

    
	




</div>
</body>
</html>