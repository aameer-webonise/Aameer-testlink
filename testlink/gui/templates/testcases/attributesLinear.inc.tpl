{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
@filesource attributes.inc.tpl
*}


<script src="/testlink/gui/themes/default/javascript/multiCheck2.js"></script>
<link rel="stylesheet" href="/testlink/gui/themes/default/css/multiSelect.css"/>


<script>
$(document).ready(function(){
	var noOFPriority={$gui->selectedPriority};
		//document.write(noOFPriority);
		var digits = (""+noOFPriority).split("");
		var size=digits.length;
		for(var i=0;i<size;i++){
			switch(digits[i]){
				case "1":
					$("#Regression").prop("checked",true).change();
					break;
				case "2":
					$("#Smoke").prop("checked",true).change();
					break;
				case "3":
					$("#Functional").prop("checked",true).change();
					break;
			}
		}

});
</script>
<script>
	function submit(){
		$("#priorityForm_"+{$args_testcase.id}).submit();
	}
</script>
<div>

<h1>attributeLinear</h1>
<span class="labelHolder">{$labels.status}</span>
<span>
<select name="tc_status" id="tc_status" 
    onchange="content_modified = true">
{html_options options=$gui->domainTCStatus selected=$gui->tc.status}
</select>
</span>

{if $session['testprojectOptions']->testPriorityEnabled}
  <span class="labelHolder" style="margin-left:20px;">{$labels.importance}</span>
  <span>
  <select name="importance" onchange="content_modified = true">
    {html_options options=$gsmarty_option_importance selected=$gui->tc.importance}
  </select>
  </span>
{/if}


{if $session['testprojectOptions']->automationEnabled}
<input type="hidden" name="priority" id="priority" value="none">
  <span class="labelHolder" style="margin-left:20px;">Priority:</span>
  <span>
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
  </span>
{/if}


{if $session['testprojectOptions']->automationEnabled}
  <span class="labelHolder" style="margin-left:20px;">{$labels.execution_type}</span>
  <span>
  <select name="exec_type" onchange="content_modified = true">
      {html_options options=$gui->execution_types selected=$gui->tc.execution_type}
  </select>
  </span>
{/if}



<span class="labelHolder" style="margin-left:20px;">{$labels.estimated_execution_duration}</span>
<span>
<input type="text" name="estimated_execution_duration" id="estimated_execution_duration"
     size="{#EXEC_DURATION_SIZE#}" maxlength="{#EXEC_DURATION_MAXLEN#}"
     title="{$labels.estimated_execution_duration}" 
     value="{$gui->tc.estimated_exec_duration}" {$tlCfg->testcase_cfg->estimated_execution_duration->required}>
</span>
</div>

