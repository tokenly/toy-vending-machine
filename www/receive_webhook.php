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
simpleLog("Incoming webhook notification.  Raw data: ".file_get_contents('php://input'));


// receive the notification
//   this will throw an Exception if it fails
$receiver = new Tokenly\XChainClient\WebHookReceiver($config['xchain']['api_token'], $config['xchain']['api_secret']);
$webhook_data = $receiver->validateAndParseWebhookNotificationFromCurrentRequest();
$notification = $webhook_data['payload'];

// log the notification
simpleLog("Notification received: ".json_encode($notification, 192));


// check for a receive event and then execute the swap
simpleLog("\$notification['event']=".json_encode($notification['event'], 192));
if ($notification['event'] == 'receive') {
    $tx_record = findOrCreateTransaction($event['txid']);
    if (!$tx_record) { throw new Exception("Unable to access database", 1); }

    // check for blacklisted sources
    $should_process = !in_array($notification['sources'][0], $config['gateway']['blacklisted_addresses']);
    if (!$should_process) {
        simpleLog("ignoring send from {$notification['sources'][0]}");
    }

    simpleLog("\$tx_record=".json_encode($tx_record, 192));
    if ($should_process AND !$tx_record->processed AND $notification['confirmed']) {

        // this transaction has not been processed yet
        // calculate the send
        foreach ($config['gateway']['exchanges'] as $io_type => $exchange_config) {
            if ($notification['asset'] == $exchange_config['in']) {
                // we recieved an asset - exchange 'in' for 'out'

                // assume the first source should get paid
                $destination = $notification['sources'][0];

                // calculate the receipient's quantity and asset
                $quantity = $notification['quantity'] * $exchange_config['rate'];
                $asset = $exchange_config['out'];

                // log the attempt
                simpleLog("Received {$notification['quantity']} {$notification['asset']} from {$notification['sources'][0]}.  Will vend {$quantity} {$asset} to {$destination}.");

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

