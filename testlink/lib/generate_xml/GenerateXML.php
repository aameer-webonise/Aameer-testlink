<?php
 //echo $_POST['value1'];
// echo $_POST['label'];
	$keyNamePrefix='key';
	$valueNamePrefix='value';
 if(isset($_COOKIE['count'])) { 
        $fieldCount=$_COOKIE['count'];
    }
 $fileName='xmlFile.xml';
	header('Pragma: public' );
	header('Content-Type: text/plain; name=' . $fileName );
	header('Content-Transfer-Encoding: BASE64;' );
	header('Content-Disposition: attachment; filename="' . $fileName .'"');
	$content='<suite name="Suite" parallel="none">'."\n";
	$content.='<Proof_login_valid>'."\n".'<datasets>'."\n".'<dataset>'."\n";
	//echo $content;
for($i=1;$i<=$fieldCount;$i++){
	$content.='<'.$_POST['key'.$i].'>'.$_POST['value'.$i].'</'.$_POST['key'.$i].'>'."\n";
}
$content.='</dataset>'."\n".'</datasets>'."\n".'</Proof_login_valid>'."\n".'</suite>';
echo $content;
?>