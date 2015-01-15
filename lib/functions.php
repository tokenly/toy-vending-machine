<?php 

// log
function simpleLog($text) {
    $fd = fopen(APPLICATION_ROOT.'/log/trace.log', 'a');

    $msg = "[".date("Y-m-d H:i:s")."] ".rtrim($text)."\n";

    fwrite($fd, $msg);
}


// ####################################
// Data Store

function initDB() {
    static $INITED;
    if (!isset($INITED)) {
        R::setup('sqlite:'.APPLICATION_ROOT.'/data/data.db');
        $INITED = true;
    }
}

function findOrCreateTransaction($txid) {
    $tx = findTransactionByTXID($txid);
    if (!$tx) {
        $tx = newTransaction($txid);
    }
    return $tx;
}

function newTransaction($txid, $confirmations=0, $processed=false, $processed_txid='', $timestamp=null) {
    initDB();

    $tx = R::dispense('tx');

    $tx->txid           = $txid;
    $tx->confirmations  = $confirmations;
    $tx->processed      = $processed;
    $tx->processed_txid = $processed_txid;
    $tx->timestamp      = ($timestamp === null ? time() : $timestamp);

    $id = R::store( $tx );
    return $tx;
}

function storeTransaction($tx) {
    initDB();

    R::store($tx);
}

function findAllTransactions() {
    initDB();

    $txs = R::getAll('SELECT * FROM `tx` ORDER BY `timestamp`'); 
    return $txs;
}
function findTransactionByTXID($txid) {
    initDB();

    return R::findOne( 'tx', ' txid = ? ', [ $txid ] );
}
function deleteTransaction($tx) {
    initDB();

    R::trash($tx); 
}


// ####################################
// Error handling

// handle exceptions
function my_error_handler($e) {
    simpleLog("ERROR: ".$e->getMessage()." at ".$e->getFile().", line ".$e->getLine());

    // make sure to send a non-2xx response
    http_response_code(500);
    echo "An error occurred.";
}

// catch errors
function setupWebErrorHandler() {
    set_exception_handler('my_error_handler');
}




