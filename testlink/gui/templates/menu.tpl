<link rel="stylesheet" href="/testlink/gui/themes/default/css/bootstrap.min.css"/>
<link rel="stylesheet" href="/testlink/gui/themes/default/css/table.css"/>
<link rel="stylesheet" href="/testlink/gui/themes/default/css/menu.css"/>
<script src="/testlink/third_party/jquery/jquery-2.0.3.min.js"></script>

{lang_get var='labels' s='title_product_mgmt,href_tproject_management,href_admin_modules,
                          href_assign_user_roles,href_cfields_management,system_config,
                          href_cfields_tproject_assign,href_keywords_manage,
                          title_user_mgmt,href_user_management,
                          href_roles_management,title_requirements,
                          href_req_spec,href_req_assign,link_report_test_cases_created_per_user,
                          title_test_spec,href_edit_tc,href_browse_tc,href_search_tc,
                          href_search_req, href_search_req_spec,href_inventory,
                          href_platform_management, href_inventory_management,
                          href_print_tc,href_keywords_assign, href_req_overview,
                          href_print_req, title_documentation,href_issuetracker_management,
                          href_reqmgrsystem_management,current_test_plan,ok,testplan_role,msg_no_rights_for_tp,
             title_test_execution,href_execute_test,href_rep_and_metrics,
             href_update_tplan,href_newest_tcversions,
             href_my_testcase_assignments,href_platform_assign,
             href_tc_exec_assignment,href_plan_assign_urgency,
             href_upd_mod_tc,title_test_plan_mgmt,title_test_case_suite,
             href_plan_management,href_assign_user_roles,
             href_build_new,href_plan_mstones,href_plan_define_priority,
             href_metrics_dashboard,href_add_remove_test_cases'}
             
{$menuLayout=$tlCfg->gui->layoutMainPageLeft}


{$display_right_block_1=false}
{$display_right_block_2=false}
{$display_right_block_3=false}

{* DO NOT GET CONFUSED this are SMARTY variables NOT JS *}
{$display_left_block_1=false}
{$display_left_block_2=false}
{$display_left_block_3=false}
{$display_left_block_4=false}
{$display_left_block_5=$tlCfg->userDocOnDesktop}

{if $gui->testprojectID && 
   ($gui->grants.project_edit == "yes" || 
    $gui->grants.tproject_user_role_assignment == "yes" ||
    $gui->grants.cfield_management == "yes" || 
    $gui->grants.platform_management == "yes" || 
    $gui->grants.keywords_view == "yes")}
    
    {$display_left_block_1=true}

    <script  type="text/javascript">
    function display_left_block_1()
    {
        var p1 = new Ext.Panel({
                                title: '{$labels.title_product_mgmt}',
                                collapsible:false,
                                collapsed: false,
                                draggable: false,
                                contentEl: 'testproject_topics',
                                baseCls: 'x-tl-panel',
                                bodyStyle: "background:#c8dce8;padding:3px;",
                                renderTo: 'menu_left_block_{$menuLayout.testProject}',
                                width:'100%'
                                });
     }

	
    </script>
{/if}

{if $gui->testprojectID && 
   ($gui->grants.cfield_management == "yes" || $gui->grants.issuetracker_management || $gui->grants.issuetracker_view)}
   {$display_left_block_2=true}

    <script  type="text/javascript">
    function display_left_block_2()
    {
      var p1 = new Ext.Panel({
                              title: '{$labels.system_config}',
                              collapsible:false,
                              collapsed: false,
                              draggable: false,
                              contentEl: 'system_topics',
                              baseCls: 'x-tl-panel',
                              bodyStyle: "background:#c8dce8;padding:3px;",
                              renderTo: 'menu_left_block_2',
                              width:'100%'
                             });
     }
    </script>
{/if}

{if $gui->testprojectID && $gui->opt_requirements == TRUE && ($gui->grants.reqs_view == "yes" || $gui->grants.reqs_edit == "yes")}
    {$display_left_block_3=true}



    <script type="text/javascript">
    function display_left_block_3()
    {
        var p3 = new Ext.Panel({
                                title: '{$labels.title_requirements}',
                                collapsible:false,
                                collapsed: false,
                                draggable: false,
                                contentEl: 'requirements_topics',
                                baseCls: 'x-tl-panel',
                                bodyStyle: "background:#c8dce8;padding:3px;",
                                renderTo: 'menu_left_block_{$menuLayout.requirements}',
                                width:'100%'
                                });
     }
    </script>
{/if}

{if $gui->testprojectID && $gui->grants.view_tc == "yes"}
    {$display_left_block_4=true}

    <script type="text/javascript">
    function display_left_block_4()
    {
        var p4 = new Ext.Panel({
                                title: '{$labels.title_test_spec}',
                                collapsible:false,
                                collapsed: false,
                                draggable: false,
                                contentEl: 'testspecification_topics',
                                baseCls: 'x-tl-panel',
                                bodyStyle: "background:#c8dce8;padding:3px;",
                                renderTo: 'menu_left_block_{$menuLayout.testSpecification}',
                                width:'100%'
                                });
     }
   </script>
{/if}

{if $gui->grants.testplan_planning == "yes" || $gui->grants.mgt_testplan_create == "yes" ||
	  $gui->grants.testplan_user_role_assignment == "yes" or $gui->grants.testplan_create_build == "yes"}
   {$display_right_block_1=true}

    <script  type="text/javascript">
    function display_right_block_1()
    {
      var rp1 = new Ext.Panel({ title:'{$labels.title_test_plan_mgmt}',
                                collapsible:false, collapsed: false, draggable: false,
                                contentEl: 'test_plan_mgmt_topics', baseCls: 'x-tl-panel',
                                bodyStyle: "background:#c8dce8;padding:3px;", width:'100%',
                                renderTo: 'menu_right_block_{$menuLayout.testPlan}'
                                });
    }
    </script>
{/if}

{if $gui->countPlans > 0 && ($gui->grants.testplan_execute == "yes" || $gui->grants.testplan_metrics == "yes")}
   {$display_right_block_2=true}

    <script  type="text/javascript">
    function display_right_block_2()
    {
      var rp2 = new Ext.Panel({ title: '{$labels.title_test_execution}',
                                collapsible: false, collapsed: false, draggable: false,
                                contentEl: 'test_execution_topics', baseCls: 'x-tl-panel',
                                bodyStyle: "background:#c8dce8;padding:3px;", width: '100%',
                                renderTo: 'menu_right_block_{$menuLayout.testExecution}'                       
                              });
     }
    </script>
{/if}

{if $gui->countPlans > 0 && $gui->grants.testplan_planning == "yes"}
   {$display_right_block_3=true}

    <script  type="text/javascript">
    function display_right_block_3()
    {
      var rp3 = new Ext.Panel({ title: '{$labels.title_test_case_suite}',
                                collapsible:false, collapsed: false, draggable: false,
                                contentEl: 'testplan_contents_topics', baseCls: 'x-tl-panel',
                                bodyStyle: "background:#c8dce8;padding:3px;", width: '100%',
                                renderTo: 'menu_right_block_{$menuLayout.testPlanContents}'
                              });
     }
    </script>

{/if}

<script>
function hideMenu(){
	$(".dropdown").removeClass("open");
	$(".dropdown").children(".dropdown-toggle").attr("aria-expanded","true");
}
$(document).ready(function(){
	$(document).click(hideMenu);
	
	$("#minmax").click(function(){
		$(".testcaseInfo").slideToggle();
		if($("#minmax").attr("src")=="/testlink/gui/drag_and_drop/images/dhtmlgoodies_minus.gif"){
			$("#minmax").attr("src","/testlink/gui/drag_and_drop/images/dhtmlgoodies_plus.gif");
		}
		else{
			$("#minmax").attr("src","/testlink/gui/drag_and_drop/images/dhtmlgoodies_minus.gif");
		}
	});
	
	$(".dropdown").click(function(e){
		e.stopPropagation();
		hideMenu();
		$(this).addClass("open");
		$(this).children(".dropdown-toggle").attr("aria-expanded","true");
	});
	

	
});
</script>

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
     
        {if $display_left_block_1}
		    <li class="dropdown">
	          <a class="dropdown-toggle" data-toggle="dropdown">Test Project<span class="caret"></span></a>
	          <ul class="dropdown-menu">
					{if $gui->grants.project_edit == "yes"}
				      <li><a href="lib/project/projectView.php">{$labels.href_tproject_management}</a></li>
				    {/if}
				    
				    {if $gui->grants.tproject_user_role_assignment == "yes"}
				      <li><a href="lib/usermanagement/usersAssign.php?featureType=testproject&amp;featureID={$gui->testprojectID}">{$labels.href_assign_user_roles}</a></li>
				    {/if}
				
				    {if $gui->grants.cfield_management == "yes"}
				      <li><a href="lib/cfields/cfieldsTprojectAssign.php">{$labels.href_cfields_tproject_assign}</a></li>
				    {/if}
				    
				    {if $gui->grants.keywords_view == "yes"}
				      <li><a href="lib/keywords/keywordsView.php">{$labels.href_keywords_manage}</a></li>
				    {/if}
				    
				    {if $gui->grants.platform_management == "yes"}
				      <li><a href="lib/platforms/platformsView.php">{$labels.href_platform_management}</a></li>
				    {/if}
				
				    {if $gui->grants.project_inventory_view}
				      <li><a href="lib/inventory/inventoryView.php">{$labels.href_inventory}</a></li>
				    {/if}
	          </ul>
	        </li>
		 {/if}
		  {if $display_left_block_2}
	        <li class="dropdown">
	          <a class="dropdown-toggle" data-toggle="dropdown">System<span class="caret"></span></a>
	          <ul class="dropdown-menu">
	            {if $gui->grants.cfield_management == "yes"}
			      <li><a href="lib/cfields/cfieldsView.php">{$labels.href_cfields_management}</a></li>
			    {/if}
		     
			    {if $gui->grants.issuetracker_management || $gui->grants.issuetracker_view}
			      <li><a href="lib/issuetrackers/issueTrackerView.php">{$labels.href_issuetracker_management}</a></li>
			    {/if}
	          </ul>
	        </li>
			{/if}
		
		{if $display_left_block_3}
		<li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown">Requirements<span class="caret"></span></a>
          <ul class="dropdown-menu">
            {if $gui->grants.reqs_view == "yes"}
		       <li> <a href="{$gui->launcher}?feature=reqSpecMgmt">{$labels.href_req_spec}</a> </li>
		       <li> <a href="lib/requirements/reqOverview.php">{$labels.href_req_overview}</a> </li>
		       <li> <a href="{$gui->launcher}?feature=searchReq">{$labels.href_search_req}</a> </li>
		       <li> <a href="{$gui->launcher}?feature=searchReqSpec">{$labels.href_search_req_spec}</a> </li>
		     {/if}
		       
		    {if $gui->grants.reqs_edit == "yes"}
		      <li><a href="lib/general/frmWorkArea.php?feature=assignReqs">{$labels.href_req_assign}</a> </li>
		     <li> <a href="{$gui->launcher}?feature=printReqSpec">{$labels.href_print_req}</a> </li>
		    {/if}
          </ul>
        </li>
		{/if}
		
		{if $display_left_block_4}
			<li class="dropdown">
	          <a class="dropdown-toggle" data-toggle="dropdown">Test Specification<span class="caret"></span></a>
	          <ul class="dropdown-menu">
 					<li><a href="{$gui->launcher}?feature=editTc">
			        {if $gui->grants.modify_tc eq "yes"}
			          Create Test Cases
			       {else}
			          {lang_get s='href_browse_tc'}
			       {/if}
			      </a>
			      </li>
			      {if $gui->hasTestCases}
			        <li><a href="lib/testcases/tcSearch.php?doAction=userInput&tproject_id={$gui->testprojectID}">{$labels.href_search_tc}</a></li>
			      {/if}    
			      
			    {if $gui->hasKeywords}  
			      {if $gui->grants.keywords_view == "yes"}
			        {if $gui->grants.keywords_edit == "yes"}
			            <li><a href="{$gui->launcher}?feature=keywordsAssign">{$labels.href_keywords_assign}</a></li>
			        {/if}
			      {/if}
			    {/if}
			      
			     {if $gui->grants.modify_tc eq "yes"}
			       <li><a href="lib/results/tcCreatedPerUserOnTestProject.php?do_action=uinput&tproject_id={$gui->testprojectID}">{$labels.link_report_test_cases_created_per_user}</a></li>
			     {/if}
	          </ul>
	        </li>
		{/if}
		
		{if $display_left_block_5}
			<li class="dropdown">
	          <a class="dropdown-toggle" data-toggle="dropdown">Testlink Application<span class="caret"></span></a>
	          <ul class="dropdown-menu">
	           <script type="text/javascript">
			    function display_left_block_5()
			    {
			      var p5 = new Ext.Panel({
			                              title: '{$labels.title_documentation}',
			                              collapsible:false,
			                              collapsed: false,
			                              draggable: false,
			                              contentEl: 'testlink_application',
			                              baseCls: 'x-tl-panel',
			                              bodyStyle: "background:#c8dce8;padding:3px;",
			                              renderTo: 'menu_left_block_{$menuLayout.general}',
			                              width:'100%'
			                              });
			    }
			    </script>
			
			
			    <div id='testlinkApplication' class="tabcontent menu">
			      <form style="display:inline;">
			        <select class="menu_combo" style="font-weight:normal;" name="docs" size="1"
			                onchange="javascript:get_docs(this.form.docs.options[this.form.docs.selectedIndex].value, 
			                '{$basehref}');" >
			            <option value="leer"> -{lang_get s='access_doc'}-</option>
			            {if $gui->docs}
			              {foreach from=$gui->docs item=doc}
			                  <option value="{$doc}">{$doc}</option>
			              {/foreach}
			            {/if}
			        </select>
			      </form>
			    </div>
	          </ul>
	        </li>
		{/if}
		
		{if $display_right_block_1}
			<li class="dropdown">
	          <a class="dropdown-toggle" data-toggle="dropdown">Test Plan<span class="caret"></span></a>
	          <ul class="dropdown-menu">
				{if $gui->grants.mgt_testplan_create == "yes"}
		       		<li><a href="lib/plan/planView.php">{$labels.href_plan_management}</a></li>
			    {/if}
			    
			    {if $gui->grants.testplan_create_build == "yes" and $gui->countPlans > 0}
		       		<li><a href="lib/plan/buildView.php">{$labels.href_build_new}</a></li>
		      {/if}
			    
		      {if $gui->grants.testplan_milestone_overview == "yes" and $gui->countPlans > 0}
		         <li><a href="lib/plan/planMilestonesView.php">{$labels.href_plan_mstones}</a></li>
		      {/if}
	          </ul>
	        </li>
	     {/if}
		
		{if $display_right_block_2}
			<li class="dropdown">
	          <a class="dropdown-toggle" data-toggle="dropdown">Test Execution<span class="caret"></span></a>
	          <ul class="dropdown-menu">
	            {if $gui->grants.testplan_execute == "yes"}
					<li><a href="{$gui->launcher}?feature=executeTest">{$labels.href_execute_test}</a></li>
					
      				{if $gui->grants.exec_testcases_assigned_to_me == "yes"}
					<li><a href="{$gui->url.testcase_assignments}">{$labels.href_my_testcase_assignments}</a></li>
     				{/if} 
				{/if} 
      
		{if $gui->grants.testplan_metrics == "yes"}
			<li><a href="{$gui->launcher}?feature=showMetrics">{$labels.href_rep_and_metrics}</a></li>
  			<li><a href="{$gui->url.metrics_dashboard}">{$labels.href_metrics_dashboard}</a></li>
		{/if}
	          </ul>
	        </li>
		{/if}
		
		{if $display_right_block_3}
			<li class="dropdown">
	          <a class="dropdown-toggle" data-toggle="dropdown">Test Plan Contents<span class="caret"></span></a>
	          <ul class="dropdown-menu">
	          	 {if $gui->grants.testplan_add_remove_platforms == "yes"}
			  	  <li><a href="lib/platforms/platformsAssign.php?tplan_id={$gui->testplanID}">{$labels.href_platform_assign}</a></li>
			    {/if} 
	            <li><a href="{$gui->launcher}?feature=planAddTC">{$labels.href_add_remove_test_cases}</a></li>
	            
	            <li><a href="{$gui->launcher}?feature=tc_exec_assignment">{$labels.href_tc_exec_assignment}</a></li>
					
			    {if $session['testprojectOptions']->testPriorityEnabled && 
			        $gui->grants.testplan_set_urgent_testcases == "yes"}
			      <li><a href="{$gui->launcher}?feature=test_urgency">{$labels.href_plan_assign_urgency}</a></li>
			    {/if}
			
			    {if $gui->grants.testplan_update_linked_testcase_versions == "yes"}
				   <li>	<a href="{$gui->launcher}?feature=planUpdateTC">{$labels.href_update_tplan}</a></li>
			    {/if} 
			
			    {if $gui->grants.testplan_show_testcases_newest_versions == "yes"}
				   <li>	<a href="{$gui->launcher}?feature=newest_tcversions">{$labels.href_newest_tcversions}</a></li>
			    {/if} 
	          </ul>
       		 </li>
	     {/if}
		
		<li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown">Automation<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="lib/generate_xml/generateXML.html">Create XML</a></li>
		  	<li><a href="lib/automation/automate.php">Trigger Automation</a></li>
          </ul>
        </li>
        <li><a href="lib/usermanagement/usersView.php">Users/Roles</a></li>
        <li><a href="lib/general/frmWorkArea.php?feature=showMetrics">Test Reports</a></li>
      </ul>
    </div>
  </div>
</nav>
