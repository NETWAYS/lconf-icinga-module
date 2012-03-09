#!/usr/bin/php

<?php
if(!isset($argv[1])) {
    echo "Please supply resource cfg as first argument\n";
    die();
}

echo "Reading ".$argv[1]." \n";
$output = file_get_contents($argv[1]);

$res = array();
$output = preg_replace("/^#.*\n/m","",$output);
echo "Fetching config tokens";
$output = preg_match_all("/([0-9A-Za-z\$ \/_]+)[ \t]*=[ \t]*([0-9A-Za-z\$ \/_]+)/"/*[ \t]*?=[ \t]*?[A-Za-z0-9]+/"*/,$output,$res,PREG_SET_ORDER);
$tokens = array();

foreach($res as $cfgEntry) {
    $tokens[trim($cfgEntry[1])] = trim($cfgEntry[2]);
}
print_r($tokens);


?>
