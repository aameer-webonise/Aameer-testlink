<?php 
	require_once("../../config.inc.php");
	require_once("common.php");
	require_once("opt_transfer.php");
	require_once("web_editor.php");
	
	$templateCfg = templateConfiguration('newTC');

	$smarty = new TLSmarty();
	$smarty->display($templateCfg->template_dir . $templateCfg->default_template);
	$gui = new stdClass();
	$gui->aameer='ausekar';
	$smarty->assign('gui',$gui);
	 //$smarty->display("/testlink/gui/templates/help/newTC.tpl");
?>