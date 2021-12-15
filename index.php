<?php 
require_once('CekResiJne.php');

$cekResiJne = new CekResiJne('020040091880021');
echo json_encode($cekResiJne->getAllData());