#!/usr/bin/env php
<?php 

// This file creates a new payment address and tells XChain to monitor it
//   run this once to create a new gateway

define(APPLICATION_ROOT, __DIR__.'/..');


// setup composer autoload
require(APPLICATION_ROOT.'/vendor/autoload.php');

// load the config
$config = require(file_exists(APPLICATION_ROOT.'/lib/config.php') ? APPLICATION_ROOT.'/lib/config.php' : APPLICATION_ROOT.'/lib/config.dist.php');
echo "\$config:\n".json_encode($config, 192)."\n";
exit();


// check config
if (!$config['xchain']['api_token']) { die("You need an XChain API Token and API Secret.  Please email devon@tokenly.co to request one.\n"); }


// get an API client
$xchain_client = new Tokenly\XChainClient\Client($config['xchain']['url'], $config['xchain']['api_token'], $config['xchain']['api_secret']);


// call xchain to create a new payment address
$payment_address_details = $xchain_client->newPaymentAddress();
echo <<<EOT
New vending machine address created:
ID: {$payment_address_details['id']}
Bitcoin address: {$payment_address_details['address']}


EOT;


// also tell xchain that we want to receive notifications when this address receives funds
$monitored_address_details = $xchain_client->newAddressMonitor($payment_address_details['address'], $config['gateway']['webhook_url'], 'receive', true);
echo <<<EOT
New address monitor created:

ID: {$monitored_address_details['id']}
Bitcoin address: {$monitored_address_details['address']}
Webhook Endpoint: {$monitored_address_details['webhook_endpoint']}
Monitored Type: {$monitored_address_details['monitor_type']}


EOT;


$replaced_config_example = str_replace('xxxxxxxx-xxxx-4xxx-1xxx-xxxxxxxxxxxx', $payment_address_details['id'], file_get_contents(APPLICATION_ROOT.'/lib/config.php'));

echo <<<EOT

Now update the 'payment_address_id' in config.php with the ID of the payment address.  Here is your config file with the payment_address_id already filled:

$replaced_config_example

EOT;

