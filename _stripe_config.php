<?php
/**
 * Stripe API configuration
 * 
 * Test mode: nu mișcă bani reali. Folosește carduri test Stripe (4242 4242 4242 4242 cu orice CVC + dată viitoare).
 * Live mode: bani reali. Schimbă 'mode' la 'live' DOAR după ce flow-ul e validat în test mode.
 */

return [
    'mode' => 'live', // 'test' sau 'live' — controlează ce keys se folosesc

    'test' => [
        'publishable_key' => 'pk_test_51QKyfGE9ccwn9QwAYFCi2oZ27Af06GmldRvlmAX34IswgVvtWZcscBKQrfWj1lclB664ZOFIpIQZVJj88IBT0O6h008GeJdtHf',
        'secret_key'      => 'sk_test_51QKyfGE9ccwn9QwAPmwKv0RfCZW03lG88V6Fu1cZnbj8VbEOoXccWZnN75n66AbGkQQ1I0aBKpX3E5zC1L7C5SvN00sar2rMsE',
        'webhook_secret'  => 'whsec_wjwxY7j83IGKR9flDSIskDL5VOP3W7UF',
    ],

    'live' => [
        'publishable_key' => 'pk_live_51QKyfGE9ccwn9QwAeQC6CQuB6LQEI6HYh4QcpqdGwHxOOAvT0otLQQXFU6AQEKiGdtfykCZwB7hvG4Pi9tLHuDHa0000JonHUa',
        'secret_key'      => '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',
        'webhook_secret'  => 'whsec_4F0wSdgCVUTu8pR2j2Zso48mdWP01QuU',
    ],

    // Pricing în cents — SERVER-SIDE doar, nu accept din client (security)
    'prices' => [
        'standard'     => 399, // 3,99 EUR
        'einschreiben' => 899, // 8,99 EUR
    ],

    // Labels human-readable pentru Stripe Checkout
    'tier_labels' => [
        'standard'     => 'Versand Standard — Brief per Deutsche Post',
        'einschreiben' => 'Versand mit Einwurfeinschreiben — Brief mit Zustellnachweis',
    ],

    // Site URL pentru success/cancel redirects
    'site_url' => 'https://www.kuendigungexpress.de',
];
