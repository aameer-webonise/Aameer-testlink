<?php
require 'PHPMailer-master/PHPMailerAutoload.php';

$projectName=$_POST['projectName'];
$projectPrefix=$_POST['project_prefix'];
$browserList=$_POST['browser_list'];
$area=$_POST['environmentSelector'];
//echo $projectName.'--->'.$projectPrefix;
//echo '<br/>'.$browserList;
//echo '<br/>'.$area.'<br/>';
$ids= $_POST['id'];
$suite=$_POST['suiteName'];
//print_r($ids);
//print_r($suite);
//exit();
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
$mail->Subject = 'Trigger '.$projectName.' '.$suite[0];
$mail->Body = 'Trigger '.$projectName.' '.$suite[0];
if (!$mail->send()) {
    echo "ERROR: " . $mail->ErrorInfo;
} else {
    echo "Your Automation will start in 5 minutes";
}
?>