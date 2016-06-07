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
		if($size>0){
			for($i=0;$i<$size;$i++){
				$curl = curl_init("http://10.0.0.100:9090/job/Proof_Automation/config.xml");
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
				$output = curl_exec($curl);
				curl_close($curl);
				
				$suiteName=strtolower($suite[$i]);
				$suiteName=str_replace(' ','', $suiteName);
				$output=str_replace('pom.xml','pom_'.$suiteName.'.xml',$output);
				
				$xml = simplexml_load_string($output);
				$xml->reporters->$node->recipients.=','.$user->emailAddress;
				$output=$xml->asXML();
				//var_dump($output);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml"));
				curl_setopt($curl, CURLOPT_POSTFIELDS, $output);
				curl_setopt($curl, CURLOPT_URL, 'http://10.0.0.100:9090/createItem?name='.$job_name.($i+1));
				$result=curl_exec($curl);
				curl_close($curl);
				
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_URL,'http://10.0.0.100:9090/job/'.$job_name.($i+1).'/buildWithParameters?token=trig');
				$result=curl_exec($curl);
				curl_close($curl);
			}
		}else{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_URL,'http://10.0.0.100:9090/job/Proof_Automation/buildWithParameters?token=trig');
			$result=curl_exec($curl);
			curl_close($curl);
		}
		//}catch(Exception $e){
			/*$curl = curl_init();
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_URL,'http://10.0.0.100:9090/job/Proof_Automation/buildWithParameters?token=trig');
			$result=curl_exec($curl);*/
		//	echo 'error '.$e;
		//	exit();
		//}
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
		/*$demo = new java("Test");
		$result=$demo->display();
		echo result;
		exit();*/
		
		//$demo = new java("javabridgedemo.JavaBridgeDemo");
		
		/*$test = new java("javabridgedemo.Test");
		echo $test->display();*/
		
		/*$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->SMTPSecure = 'ssl';
		$mail->SMTPAuth = true;
		$mail->Host = 'smtp.gmail.com';
		$mail->Port = 465;
		$mail->Username = 'email@weboapps.com';
		$mail->Password = 'weboqa6186';
		$mail->setFrom('aameer.ausekar@gmail.com');
		$mail->addAddress('email@weboapps.com');
		//$mail->addAddress('ausekar9@gmail.com');
		
		foreach($suite as $suiteName){
			$mail->Subject = 'Trigger '.$projectName.' '.$suiteName;
			$mail->Body = 'Trigger '.$projectName.' '.$suiteName;
			if (!$mail->send()) {
		  		echo "ERROR: " . $mail->ErrorInfo;
				exit();
			}	
		}
		echo 'Automation will start in 5 minutes';
		*/
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
		?>
	</body>
</html>