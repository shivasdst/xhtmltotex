<?php

require_once 'constants.php';
require_once 'xhtmltotex.php';


$id = $argv[1];
// $stage = 1;

$xhtmltotex = new Xhtmltotex($id);
$xhtmlFiles = $xhtmltotex->getXhtmlFiles($id);
// var_dump($xhtmlFiles);

$xhtmltotex->processFiles($id,$xhtmlFiles);


?>
