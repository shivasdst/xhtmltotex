<?php

// require_once 'constants.php';
require_once 'xhtmltotex.php';

$xhtmltotex = new Xhtmltotex;

// $id = $argv[1];
// $stage = 1;

$xhtmlFiles = $xhtmltotex->getXhtmlFiles();
// var_dump($xhtmlFiles);

$xhtmltotex->processFiles($xhtmlFiles);


?>
