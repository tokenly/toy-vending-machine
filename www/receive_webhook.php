<?php 

// This file receives a webhook call from the XChain server and handles it

define(APPLICATION_ROOT, __DIR__.'/..');


// setup composer autoload
require(APPLICATION_ROOT.'/vendor/autoload.php');

// load the config
$config = require(file_exists(APPLICATION_ROOT.'/lib/config.php') ? APPLICATION_ROOT.'/lib/config.php' : APPLICATION_ROOT.'/lib/config.dist.php');

// and load some helper functions
require(APPLICATION_ROOT.'/lib/functions.php');


// handle errors
setupWebErrorHandler();

// log the raw request
simpleLog("Incoming webhook notification.");


// receive the notification
//   this will throw an Exception if it fails
$receiver = new Tokenly\XChainClient\WebHookReceiver($config['xchain']['api_token'], $config['xchain']['api_secret']);
$webhook_data = $receiver->validateAndParseWebhookNotificationFromCurrentRequest();
$xchain_notification = $webhook_data['payload'];



// check for a receive event and then execute the swap
if ($xchain_notification['event'] == 'receive') {
    // load or create a new transaction from the database
    $tx_record = findOrCreateTransaction($xchain_notification['txid']);
    if (!$tx_record) { throw new Exception("Unable to access database", 1); }


    // check for blacklisted sources
    $should_process = !in_array($xchain_notification['sources'][0], $config['gateway']['blacklisted_addresses']);
    if (in_array($xchain_notification['notifiedAddress'], $xchain_notification['sources'])) { $should_process = false; }
    if (!$should_process) { simpleLog("ignoring send from {$xchain_notification['sources'][0]}"); }


    if ($should_process AND $tx_record->processed) {
        simpleLog("Transaction {$xchain_notification['txid']} has already been processed.  We'll ignore it.", 192);
    }

    if ($should_process AND !$tx_record->processed AND $xchain_notification['confirmed']) {
        // this transaction has not been processed yet
        // calculate the send
        foreach ($config['gateway']['exchanges'] as $io_type => $exchange_config) {
            if ($xchain_notification['asset'] == $exchange_config['in']) {
                // we recieved an asset - exchange 'in' for 'out'

                // assume the first source should get paid
                $destination = $xchain_notification['sources'][0];

                // calculate the receipient's quantity and asset
                $quantity = $xchain_notification['quantity'] * $exchange_config['rate'];
                $asset = $exchange_config['out'];

                // log the attempt to send
                simpleLog("Received {$xchain_notification['quantity']} {$xchain_notification['asset']} from {$xchain_notification['sources'][0]}.  Will vend {$quantity} {$asset} to {$destination}.");

                // call xchain
                $xchain_client = new Tokenly\XChainClient\Client($config['xchain']['url'], $config['xchain']['api_token'], $config['xchain']['api_secret']);
                $send_details = $xchain_client->send($config['gateway']['payment_address_id'], $destination, $quantity, $asset);
                simpleLog("Successful send sent: ".json_encode($send_details, 192));

                // save the txid
                $tx_record->processed = true;
                $tx_record->processed_txid = $send_details['txid'];
                $tx_record->timestamp = time();
                storeTransaction($tx_record);
            }
        }
    }
}


// everything is ok - return a 200 OK response
echo "done";

