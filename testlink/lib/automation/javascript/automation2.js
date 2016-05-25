$(document).ready(function(){
			var browserList="";
			var expand=true;
			$(".environmentSelector").hide();
			$(".multiselect").hide();
			$(".continue").hide();
			$(".files").attr("disabled",true);
			
			$(".tcCheckbox").change(function(){
				var id=$(this).attr("id");
				if($(this).is(":checked")){
					$("#files"+id).attr("disabled",false);
				}
				else{
					$("#files"+id).attr("disabled",true);
				}
			});
			
			
			$(".testSuiteName").click(function(){
				var id=$(this).attr("id");
				$("#tc"+id).slideToggle();
				if($("#status"+id).attr("value")=="-"){
					$("#suite"+id).attr("src","/testlink/gui/drag_and_drop/images/dhtmlgoodies_plus.gif");
					$("#status"+id).attr("value","+");
				}
				else{
					$("#suite"+id).attr("src","/testlink/gui/drag_and_drop/images/dhtmlgoodies_minus.gif");
					$("#status"+id).attr("value","-");
				}
				expand=!expand;
			});
			$(".suiteCheckbox").change(function(){
				var id=$(this).attr("id");
				$(".checkbox-"+id).prop("checked", ($(this).is(":checked"))?true:false).change();
			});
			
			$(".selectBox").click(function(){
				$("#checkboxes").css("display",($("#checkboxes").css("display")=="block")?"none":"block");
			});
			
			$(".dropdownCheckBox").change(function(){
				if($(this).is(":checked")){
					browserList+=$(this).attr("value")+",";
				}
				else{
					browserList=browserList.replace($(this).attr("value")+",","");
				}
				
				if(browserList==""){
					$(".selectBox option").text("Select Browsers");
					$("#browser_list").attr("value","NA");
					$(".continue").hide();
				}
				else{
					$(".selectBox option").text(browserList);
					$("#browser_list").attr("value",browserList);
					$(".continue").show();
				}
			});
			
			$(".execute").click(function(){
				$(this).hide();
				$(".environmentSelector").show();
			});
			
			$("#selectField").change(function(){
				$(".multiselect").show();
			});
			
			$(window).bind("pageshow", function() {
				$("form").get(0).reset();
			});
		});