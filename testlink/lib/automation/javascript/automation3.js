$(document).ready(function(){
			var browserList="";
			var expand=true;
			$(".environmentSelector").hide();
			$(".multiselect").hide();
			$(".continue").hide();
			$(".files").attr("disabled",true);
			$(".suite").attr("disabled",true);
			$(".tcCheckbox").change(function(e){
				e.stopPropagation();
				var id=$(this).attr("id");
				if($(this).is(":checked")){
					$("#files"+id).attr("disabled",false);
					$(".suite").attr("disabled",false);
				}
				else{
					$("#files"+id).attr("disabled",true);
					$(".suite").attr("disabled",true);
				}
			});
			
			
			$(".testSuiteName").click(function(e){
				e.stopPropagation();
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
			
			$(".suiteCheckbox").change(function(e){
				e.stopPropagation();
				var id=$(this).attr("id");
				$(".checkbox-"+id).prop("checked", ($(this).is(":checked"))?true:false).change();
			});
			
			$(".selectBox").click(function(e){
				e.stopPropagation();
				$("#checkboxes").css("display",($("#checkboxes").css("display")=="block")?"none":"block");
			});
			
			$("label").click(function(e){
				e.stopPropagation();
			});
			$(".dropdownCheckBox").change(function(e){
				e.stopPropagation();
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
			
			$(".parent").click(function(e){
				e.stopPropagation();
				if($("#checkboxes").css("display")=="block")
				{
					$("#checkboxes").css("display","none");
				}
			});
			$(".execute").click(function(e){
				e.stopPropagation();
				$(this).hide();
				$(".environmentSelector").show();
			});
			
			$("#selectField").change(function(e){
				e.stopPropagation();
				$(".multiselect").show();
			});
			
			
			$(window).bind("pageshow", function() {
				$("form").get(0).reset();
			});
		});