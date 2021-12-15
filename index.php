<?php 
require_once('CekResiJne.php');

// Input your delivery receipt number here
$cekResiJne = new CekResiJne('TJR1023645846835');
echo json_encode($cekResiJne->getAllData());
