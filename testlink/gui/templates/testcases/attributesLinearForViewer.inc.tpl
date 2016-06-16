{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
@filesource attributesLinearForViewer.inc.tpl
*}
<script src="/testlink/third_party/jquery/jquery-2.0.3.min.js"></script>
<link rel="stylesheet" href="/testlink/gui/themes/default/css/multiSelect.css"/>
<link rel="stylesheet" href="/testlink/gui/themes/default/css/popup.css"/>


<script>
$(document).ready(function(){
var noOFPriority={$gui->selectedPriority};

		var list="";
		var digits = (""+noOFPriority).split("");
		var size=digits.length;
		for(var i=0;i<size;i++){
			switch(digits[i]){
				case "1":
					$("#Regression").prop("checked",true);
					list+="Regression,";
					break;
				case "2":
					$("#Smoke").prop("checked",true);
					list+="Smoke,";
					break;
				case "3":
					$("#Functional").prop("checked",true);
					list+="Functional,";
					break;
			}
		}
		$("#priority").attr("value",list);
		$(".selectBox option").text(list);
		
var priorityList=$("#priority").attr("value");
	
	$(".selectBox").click(function(e){
			e.stopPropagation();
			$("#checkboxes").css("display",($("#checkboxes").css("display")=="block")?"none":"block");
		});
		
	$(".dropdownCheckBox").change(function(){
		if($(this).is(":checked")){
			//priorityList=$("#priority").attr("value");
			priorityList+=$(this).attr("value")+",";
		}
		else{
			priorityList=priorityList.replace($(this).attr("value")+",","");
		}
		
		if(priorityList==""){
			$(".selectBox option").text("Select Priority");
			$("#priority").attr("value","none");
			showPopUp();
		}
		else{
			$(".selectBox option").text(priorityList);
			$("#priority").attr("value",priorityList);
			submit();
		}
	});
	$(".okBtn").click(function(){
		closePopUp();
	});	
		
		
	$("#some").click(function(){
		document.write(priorityList);
	});
});
</script>
<script>
	function showPopUp(){
		var modal = document.getElementById('myModal');
		modal.style.display = "block";
	}
	
	function closePopUp(){
		var modal = document.getElementById('myModal');
		modal.style.display = "none";
	}
	
	function submit(){
		$("#priorityForm_"+{$args_testcase.id}).submit();
	}
</script>
<script>
$(document).ready(function(){

//$("#Smoke").prop("checked",true).change();
});
</script>

<p>
<fieldset>

<legend></legend>
<h1>attributeLinear_viewer</h1>

<form style="display:inline;" 
      id="statusForm_{$args_testcase.id}" name="statusForm_{$args_testcase.id}"  
      method="post" action="lib/testcases/tcEdit.php">
  <input type="hidden" name="doAction" id="doAction" value="setStatus">
  <input type="hidden" name="testcase_id" value="{$args_testcase.testcase_id}" />
  <input type="hidden" name="tcversion_id" value="{$args_testcase.id}" />

  <span class="labelHolder" title="{$tcView_viewer_labels.onchange_save}">
  {$tcView_viewer_labels.status}{$smarty.const.TITLE_SEP}</span>
  {if $edit_enabled}
  <select name="status" id="status" onchange="document.getElementById('statusForm_{$args_testcase.id}').submit();">
    {html_options options=$gui->domainTCStatus selected=$args_testcase.status}
  </select>
  {else}
    {$gui->domainTCStatus[$args_testcase.status]}
  {/if}
</form>

{if $session['testprojectOptions']->testPriorityEnabled}
   <form style="display:inline;" id="importanceForm_{$args_testcase.id}" 
         name="importanceForm_{$args_testcase.id}" method="post" 
         action="lib/testcases/tcEdit.php">

    <input type="hidden" name="doAction" id="doAction" value="setImportance">
    <input type="hidden" name="testcase_id" value="{$args_testcase.testcase_id}" />
    <input type="hidden" name="tcversion_id" value="{$args_testcase.id}" />
    
  <span class="labelHolder" title="{$tcView_viewer_labels.onchange_save}"
        style="margin-left:20px;">{$tcView_viewer_labels.importance}{$smarty.const.TITLE_SEP}</span>
    {if $edit_enabled}
    <select name="importance" onchange="document.getElementById('importanceForm_{$args_testcase.id}').submit();" >
          {html_options options=$gsmarty_option_importance selected=$args_testcase.importance}
    </select>
    {else}
      {$gsmarty_option_importance[$args_testcase.importance]}
    {/if}
   </form>
{/if}

{if $session['testprojectOptions']->automationEnabled}
<form style="display:inline;" id="priorityForm_{$args_testcase.id}" 
      name="priorityForm_{$args_testcase.id}" method="post" action="lib/testcases/tcEdit.php">
    <input type="hidden" name="doAction" id="doAction" value="setPriority">
     <input type="hidden" name="priority" id="priority" value="">
    <input type="hidden" name="testcase_id" value="{$args_testcase.testcase_id}" />
    <input type="hidden" name="tcversion_id" value="{$args_testcase.id}" />
  <span class="labelHolder" title="{$tcView_viewer_labels.onchange_save}" 
        style="margin-left:20px;">Priority:</span>
  {if $edit_enabled}
    <div class="multiselect">
        <div class="selectBox">
            <select>
                <option>Select Priority</option>
            </select>
            <div class="overSelect"></div>
        </div>
        <div id="checkboxes">
				<label for="Regression"><input type="checkbox" class="dropdownCheckBox" id="Regression" value="Regression"/>Regression</label>
	            <label for="Smoke"><input type="checkbox" class="dropdownCheckBox" id="Smoke" value="Smoke"/>Smoke</label>
	            <label for="Functional"><input type="checkbox" class="dropdownCheckBox" id="Functional" value="Functional"/>Functional</label>
        </div>
    </div>
	<input type="button" id="some" value="click">

  {else}
    {$gui->execution_types[$args_testcase.execution_type]}
  {/if}
</form>
{/if}
			<!-- The Modal -->
			<div id="myModal" class="modal">
			  <!-- Modal content -->
			  <div class="modal-content">
			    <span class="close" onclick="closePopUp();">x</span>
			    <p>Please select at least one priority for testcase</p>
			    <input type="button" value="OK" class="okBtn">
			  </div>
			</div>



{if $session['testprojectOptions']->automationEnabled}
<form style="display:inline;" id="execTypeForm_{$args_testcase.id}" 
      name="execTypeForm_{$args_testcase.id}" method="post" action="lib/testcases/tcEdit.php">
    <input type="hidden" name="doAction" id="doAction" value="setExecutionType">
    <input type="hidden" name="testcase_id" value="{$args_testcase.testcase_id}" />
    <input type="hidden" name="tcversion_id" value="{$args_testcase.id}" />
  <span class="labelHolder" title="{$tcView_viewer_labels.onchange_save}" 
        style="margin-left:20px;">{$tcView_viewer_labels.execution_type}{$smarty.const.TITLE_SEP}</span>
  {if $edit_enabled}
    <select name="exec_type" onchange="document.getElementById('execTypeForm_{$args_testcase.id}').submit();" >
      {html_options options=$gui->execution_types selected=$args_testcase.execution_type}
    </select>
  {else}
    {$gui->execution_types[$args_testcase.execution_type]}
  {/if}
</form>
{/if}

<form style="display:inline;" id="estimatedExecDurationForm_{$args_testcase.id}" 
      name="estimatedExecDurationForm_{$args_testcase.id}" method="post"
      action="lib/testcases/tcEdit.php">
  <input type="hidden" name="doAction" id="doAction" value="setEstimatedExecDuration">
  <input type="hidden" name="testcase_id" value="{$args_testcase.testcase_id}" />
  <input type="hidden" name="tcversion_id" value="{$args_testcase.id}" />

  <span class="labelHolder" title="{$tcView_viewer_labels.estimated_execution_duration}"
        style="margin-left:20px;">{$tcView_viewer_labels.estimated_execution_duration_short}{$smarty.const.TITLE_SEP}</span>

  {if $edit_enabled}
  <span>
  <input type="text" name="estimated_execution_duration" id="estimated_execution_duration"
       size="{#EXEC_DURATION_SIZE#}" maxlength="{#EXEC_DURATION_MAXLEN#}"
       title="{$tcView_viewer_labels.estimated_execution_duration}" 
       value="{$args_testcase.estimated_exec_duration}" {$tlCfg->testcase_cfg->estimated_execution_duration->required}>
  <input type="submit" name="setEstimated" value="{$tcView_viewer_labels.btn_save}" />
  </span>
  {else}
    {$args_testcase.estimated_exec_duration}
  {/if}

</form>
</fieldset>