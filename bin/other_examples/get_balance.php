#!/usr/bin/env php
<?php 

// Get a list of balances from XChain

define(APPLICATION_ROOT, __DIR__.'/../..');

// setup composer autoload
require(APPLICATION_ROOT.'/vendor/autoload.php');

// get config
$config = require(file_exists(APPLICATION_ROOT.'/lib/config.php') ? APPLICATION_ROOT.'/lib/config.php' : APPLICATION_ROOT.'/lib/config.dist.php');


// command line options
// specify the spec as human readable text and run validation and help:
$values = CLIOpts\CLIOpts::run("
  Usage: <address>
  -s, --satoshis show balances in satoshis
  -h, --help show this help
");


$address = $values['address'];


// get an API client
$xchain_client = new Tokenly\XChainClient\Client($config['xchain']['url'], $config['xchain']['api_token'], $config['xchain']['api_secret']);
$balances = $xchain_client->getBalances($address, isset($values['s']));
echo "\$balances:\n\n";
echo json_encode($balances, 192)."\n";


