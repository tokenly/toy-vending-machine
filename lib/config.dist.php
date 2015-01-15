<?php 

return [
    // xchain
    'xchain' => [
        'url'        => 'http://xchain.dev01.tokenly.co',
        'api_token'  => '', // needed
        'api_secret' => '', // needed
    ],

    // my gateway
    'gateway' => [
        // fill this with the URL of your running application
        'webhook_url'        => 'http://my.website.co/receive_webhook.php',
        'payment_address_id' => 'xxxxxxxx-xxxx-4xxx-1xxx-xxxxxxxxxxxx', // fill this with the uuid of the payment address

        // blacklist addresses
        // in order to load up your vending machine
        //   transactions from these addresses will be ignored for vending
        'blacklisted_addresses' => [
            '1aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],

        // in/out config
        'exchanges' => [
            'sell' => [
                'in'   => 'BTC',
                'out'  => 'SOUP',
                'rate' => 999,  // receive 1 BTC and send 999 SOUP
            ],
            'redeem' => [
                'in'   => 'SOUP',
                'out'  => 'BTC',
                'rate' => 0.001,  // receive 1 SOUP and send 0.001 BTC
            ],
        ],
    ],
];
