#!/usr/bin/env php
<?php 

// Get a list of balances from XChain

define(APPLICATION_ROOT, __DIR__.'/../..');

// setup composer autoload
require(APPLICATION_ROOT.'/vendor/autoload.php');

// get config
$config = require(file_exists(APPLICATION_ROOT.'/lib/config.php') ? APPLICATION_ROOT.'/lib/config.php' : APPLICATION_ROOT.'/lib/config.dist.php');

// get an API client
$xchain_client = new Tokenly\XChainClient\Client($config['xchain']['url'], $config['xchain']['api_token'], $config['xchain']['api_secret']);
$address_details = $xchain_client->getPaymentAddress($config['gateway']['payment_address_id']);
echo "\$address_details:\n".json_encode($address_details, 192)."\n";


