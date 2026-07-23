<?php

return [

    'v1' => [
        'url' => env('MONDIAL_RELAY_V1_URL', 'https://api.mondialrelay.com/Web_Services.asmx'),
        'enseigne' => env('MONDIAL_RELAY_ENSEIGNE'),
        'private_key' => env('MONDIAL_RELAY_PRIVATE_KEY'),
    ],

    'default_language' => env('MONDIAL_RELAY_LANGUAGE', 'FR'),

];
