<?php

/*
 * This file is part of Laravel Hashids.
 *
 * (c) Vincent Klaiber <hello@vinkla.com>
 * Adapted by Maru Amallo (amamarul) <ama_marul@hotmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use as
    | your default connection for all work. Of course, you may use many
    | connections at once using the manager class.
    |
    */

    'default' => 'main',
    'prefix-separator' => '-',

    /*
    |--------------------------------------------------------------------------
    | Hashids Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application. Example
    | configuration has been included, but you may add as many connections as
    | you would like.
    |
    */

    'connections' => [

        'main' => [
            'salt' => 'pangunahingkode',
            'length' => '10',
            'alphabet' => '0123456789QWERTYUIOPSDFGHJKLZXCVBNM',
            'prefix' => null,
        ],

        'voucher_code' => [
            'salt' => 'patunaynabagongbiliparasayo',
            'length' => '15',
            'alphabet' => '0123456789QWERTYUIOPSDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm',
        ],
    ],

];
