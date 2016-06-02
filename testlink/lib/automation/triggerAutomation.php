<?php
require 'PHPMailer-master/PHPMailerAutoload.php';
//require_once("Java.inc");
$projectName=$_POST['projectName'];
$projectPrefix=$_POST['project_prefix'];

/*$browserList=$_POST['browser_list'];
$area=$_POST['environmentSelector'];*/

//echo $projectName.'--->'.$projectPrefix;
//echo '<br/>'.$browserList;
//echo '<br/>'.$area.'<br/>';
//$ids= $_POST['id'];

//$suite=$_POST['suiteName'];

$curl = curl_init("http://10.0.0.100:9090/job/Proof/config.xml");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
$output = curl_exec($curl);

//$fh = fopen("out.xml", 'w');
//fwrite($fh, $output);
//fclose($fh);

$output=str_replace('pom.xml','pom1.xml',$output);

curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml"));
curl_setopt($curl, CURLOPT_POSTFIELDS, $output);
curl_setopt($curl, CURLOPT_URL, 'http://10.0.0.100:9090/createItem?name=AA_TEST_JOB6');
$result=curl_exec($curl);

curl_setopt($curl, CURLOPT_URL,'http://10.0.0.100:9090/job/AA_TEST_JOB6/buildWithParameters?token=trig');
$result=curl_exec($curl);

var_dump($result);
curl_close($curl);

/*$demo = new java("Test");
$result=$demo->display();
echo result;
exit();*/

//$demo = new java("javabridgedemo.JavaBridgeDemo");

/*$test = new java("javabridgedemo.Test");
echo $test->display();*/
exit();

$mail = new PHPMailer;
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

?>