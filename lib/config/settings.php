<?php
return array(
    'paymentsUrl' => array(
        'value'       => 'https://payments.chronopay.com/',
        'title'       => 'Payments Url',
        'description' => 'URL перенаправления на страницу оплаты (если не знаете, что это, оставьте поле пустым)',
        'control_type' => waHtmlControl::INPUT,
    ),
    'productId' => array(
        'value'        => '',
        'title'        => 'Product ID',
        'description' => 'Product ID из личного кабинета ChronoPay',
        'control_type' => waHtmlControl::INPUT,
    ),
    'sharedSec' => array(
        'value'       => '',
        'title'       => 'SharedSec',
        'description' => 'SharedSec из личного кабинета ChronoPay',
        'control_type' => waHtmlControl::INPUT,
    ),
);