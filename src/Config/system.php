<?php

return [
    [
        'key'    => 'sales.payment_methods.payu',
        'info' => 'Paytm extension created for Bagisto by <a href="https://www.vfixtechnology.com" target="_blank" style="color: blue;">Vfix Technology</a>.',
        'name'   => 'Payu Payment Gateway',
        'sort'   => 6,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'Payu',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ], [
                'name'          => 'description',
                'title'         => 'Description',
                'type'          => 'textarea',
                'channel_based' => false,
                'locale_based'  => true,
            ],
            [
                'name'          => 'payu_merchant_key',
                'title'         => 'Merchant Key',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ],
			[
                'name'          => 'salt_key',
                'title'         => 'Merchant Salt',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ],
            [
                'name'    => 'payu-website',
                'title'   => 'Status',
                'type'    => 'select',
                'options' => [
                    [
                        'title' => 'Sandbox',
                        'value' => 'Sandbox',
                    ], [
                        'title' => 'Live',
                        'value' => 'DEFAULT',
                    ],
                ],
            ],
            [
                'name'          => 'active',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.status',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ]
        ]
    ]
];