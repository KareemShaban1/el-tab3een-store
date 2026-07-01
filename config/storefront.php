<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tab3een catalog API
    |--------------------------------------------------------------------------
    |
    | External ERP/catalog endpoint used to load category-grouped products on
    | the storefront welcome page.
    |
    */
    'tab3een_catalog_api_url' => env('TAB3EEN_CATALOG_API_URL', 'http://127.0.0.1:8000/api/tab3een'),

    /*
    |--------------------------------------------------------------------------
    | Tab3een orders API
    |--------------------------------------------------------------------------
    |
    | Servo endpoint for creating store orders. Defaults to {catalog_url}/orders.
    |
    */
    'tab3een_orders_api_url' => env('TAB3EEN_ORDERS_API_URL'),

    'support_phone' => env('STOREFRONT_SUPPORT_PHONE', '19900'),
    'support_email' => env('STOREFRONT_SUPPORT_EMAIL', 'info@eltab3een.com'),

];
