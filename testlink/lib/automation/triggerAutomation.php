<!DOCTYPE html>
<html>
	<head>
		<title>Start Automation</title>
		<script>
			function goBack() {
    		window.history.back();
			}
		</script>
		<link rel="stylesheet" href="/testlink/gui/themes/default/css/testlink.css"/>
	</head>
	<body>
		<?php
		require 'PHPMailer-master/PHPMailerAutoload.php';
		require_once('../../config.inc.php');
		require_once('../functions/common.php');
		require_once('../functions/users.inc.php');
		//require_once("Java.inc");
		testlinkInitPage($db);
		$args = init_args();
		$user = new tlUser($args->userID);
		$user->readFromDB($db);
		//var_dump($user->emailAddress);
		$node='hudson.maven.reporters.MavenMailer';
		$projectName=$_POST['projectName'];
		$projectPrefix=$_POST['project_prefix'];
		
		$job_name='Testing';
		
		/*$browserList=$_POST['browser_list'];
		$area=$_POST['environmentSelector'];*/
		
		//echo $projectName.'--->'.$projectPrefix;
		//echo '<br/>'.$browserList;
		//echo '<br/>'.$area.'<br/>';
		//$ids= $_POST['id'];
		//try{
		$suite=$_POST['suiteName'];
		$size=count($suite);
		$j=0;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL,'http://10.0.0.100:9090/api/xml');
		$result=curl_exec($curl);
	    curl_close($curl);
		//var_dump('hello '.$result[2]);
		//print_r($result);
		
		
		//exit();
		
		if($size>0){
			for($i=0;$i<$size;$i++){
				$j=$i+1;
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
				
				for($j=$i+1;1;$j++)
				{
					curl_setopt($curl, CURLOPT_URL,'http://10.0.0.100:9090/api/xml');
					$result=curl_exec($curl);
					if (strpos($result, 'job/Testing'.$j) !== false) /*If job is present in testlink then check whether job is executing or not*/
					{
					    $curl=curl_init('http://10.0.0.100:9090/job/'.$job_name.$j.'/lastBuild/api/json?tree=result');
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						$result=curl_exec($curl);
						if (strpos($result,'{"result":null}') !== false) /*if job is executing then go back to loop, else replace the config file and start this job*/
						{
							continue;
						}
						else 
						{
							$output=readJobConfig('Proof_Automation');
							$output=replacePOM($suite[$i],$output,$node);
							
							$curl = curl_init();
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml"));
							curl_setopt($curl, CURLOPT_POSTFIELDS, $output);
							curl_setopt($curl, CURLOPT_URL, 'http://10.0.0.100:9090/job/'.$job_name.$j.'/config.xml');
							$result=curl_exec($curl);
							
							startBuild($job_name.$j);
							
							break;
						}
					    //continue;
					}
					else {
						curl_close($curl);
						$output=readJobConfig('Proof_Automation');
						$output=replacePOM($suite[$i],$output,$node);
						
						$curl = curl_init();
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml"));
						curl_setopt($curl, CURLOPT_POSTFIELDS, $output);
						curl_setopt($curl, CURLOPT_URL, 'http://10.0.0.100:9090/createItem?name='.$job_name.$j);
						$result=curl_exec($curl);
						curl_close($curl);
						
						startBuild($job_name.$j);
						break;
					}
				}
			}
		}else{
			$result=startBuild('Proof_Automation');
			/*$curl = curl_init();
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_URL,'http://10.0.0.100:9090/job/Proof_Automation/buildWithParameters?token=trig');
			$result=curl_exec($curl);
			curl_close($curl);*/
		}
		?>
		<div class="x-tl-panel-header x-unselectable" id="ext-gen3"><span class="x-tl-panel-header-text" id="ext-gen6">Automation</span></div>
		<div class="x-tl-panel-bwrap" id="ext-gen4">
		<div class="x-tl-panel-body" id="ext-gen5" style="padding: 3px; background: rgb(200, 220, 232);">
		<?php
		if($result){
		?>
		<h1>Your automation has started. You will receive reports of this automation through email on <span style="color:red"><?php echo $user->emailAddress?></span></h1>
		<button onclick="goBack()">Go Back</button>
		<?php
		}
		else{
		?>
		<h1>Having some problem, please try after some time</h1>
		<?php
		}
		?>
		
		<?php
		function init_args()
		{
		  $iParams = array("firstName" => array("POST",tlInputParameter::STRING_N,0,30),
		                   "lastName" => array("REQUEST",tlInputParameter::STRING_N,0,30),
		                   "emailAddress" => array("REQUEST",tlInputParameter::STRING_N,0,100)
		                   );
		
		  $pParams = I_PARAMS($iParams);
		  
		  $args = new stdClass();
		  $args->user = new stdClass();
		  $args->user->firstName = $pParams["firstName"];
		  $args->user->lastName = $pParams["lastName"];
		  $args->user->emailAddress = $pParams["emailAddress"];
		  $args->userID = isset($_SESSION['currentUser']) ? $_SESSION['currentUser']->dbID : 0;
		        
		  return $args;
		}
		
		function startBuild($jobname){
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_URL,'http://10.0.0.100:9090/job/'.$jobname.'/buildWithParameters?token=trig');
			$result=curl_exec($curl);
			curl_close($curl);
			return $result;
		}
		
		function readJobConfig($jobname){
			$curl = curl_init("http://10.0.0.100:9090/job/Proof_Automation/config.xml");
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			$output = curl_exec($curl);
			curl_close($curl);
			return $output;
		}
		
		function replacePOM($suiteName,$output,$node){
			$suiteName=strtolower($suiteName);
			$suiteName=str_replace(' ','', $suiteName);
			$output=str_replace('pom.xml','pom_'.$suiteName.'.xml',$output);
						
			$xml = simplexml_load_string($output);
			$xml->reporters->$node->recipients.=','.$user->emailAddress;
			$output=$xml->asXML();
			return $output;
		}
		?>
	</body>
</html>