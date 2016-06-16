$(document).ready(function(){

	$(".tcversion").attr("disabled",true);
	$(".suite").attr("disabled",true);
	
	$(".testcaseCheckbox").change(function(e){
	e.stopPropagation();
	var id=$(this).attr("id");
	if($(this).is(":checked")){
		$("#tcversion_"+id).attr("disabled",false);
	}
	else{
		$("#tcversion_"+id).attr("disabled",true);
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

	$(window).bind("pageshow", function() {
		$("form").get(0).reset();
	});
});