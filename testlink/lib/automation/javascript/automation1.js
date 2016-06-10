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
	}
	else{
		$("#files"+id).attr("disabled",true);
		}
	});		
			
	$(".testSuiteName").click(function(e){
		e.stopPropagation();
		var id=$(this).attr("id");
		$("#tc"+id).slideToggle();
		if($("#status"+id).attr("value")=="-"){
			$(this).attr("src","/testlink/gui/drag_and_drop/images/dhtmlgoodies_plus.gif");
			$("#status"+id).attr("value","+");
		}
		else{
			$(this).attr("src","/testlink/gui/drag_and_drop/images/dhtmlgoodies_minus.gif");
			$("#status"+id).attr("value","-");
		}
		expand=!expand;
	});
			
	$(".suiteCheckbox").change(function(e){
		e.stopPropagation();
		var id=$(this).attr("id");
		if($(this).is(":checked")){
			$("#name"+id).attr("disabled",false);
			$(".checkbox-"+id).prop("checked",true).change();
		}
		else{
			$("#name"+id).attr("disabled",true);
			$(".checkbox-"+id).prop("checked",false).change();			
		}
	});
			
	$(".selectBox").click(function(e){
		e.stopPropagation();
		$("#checkboxes").css("display",($("#checkboxes").css("display")=="block")?"none":"block");
	});
			
	/*$(".parent").click(function(e){
		e.stopPropagation();
		if($("#checkboxes").css("display")=="block")
		{
			$("#checkboxes").css("display","none");
		}
	});*/
	
	$(".execute").click(function(e){
		e.stopPropagation();
		$(this).hide();
		$(".environmentSelector").show();
	});
			
	$("#selectField").change(function(e){
		e.stopPropagation();
		$(".multiselect").show();
	});
		
	$("#browser").change(function(e){
		e.stopPropagation();
		$(".continue").show();
	});
				
	$(window).bind("pageshow", function() {
		$("form").get(0).reset();
	});
});