$(document).ready(function()
{
	
	var priorityList="";
	$(".selectBox").click(function(e){
			e.stopPropagation();
			$("#checkboxes").css("display",($("#checkboxes").css("display")=="block")?"none":"block");
		});
	$(".dropdownCheckBox").change(function(){
		if($(this).is(":checked")){
			priorityList+=$(this).attr("value")+",";
		}
		else{
			priorityList=priorityList.replace($(this).attr("value")+",","");
		}
		
		if(priorityList==""){
			$(".selectBox option").text("Select Priority");
			$("#priority").attr("value","none");
		}
		else{
			$(".selectBox option").text(priorityList);
			$("#priority").attr("value",priorityList);
		}
	});
	$(".okBtn").click(function(){
		closePopUp();
	});

});