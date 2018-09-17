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
    'cbUrl' => array(
        'value'       => '',
        'title'       => 'Cb Url',
        'description' => 'URL для отправки уведомления о платеже. (Оставьте поле пустым, если вы не делали свой cb_url)',
        'control_type' => waHtmlControl::INPUT,
    ),
    'cbType' => array(
        'value'       => '',
        'title'       => 'Cb Type',
        'description' => 'Метод отправки уведомления о платеже',
        'control_type' => waHtmlControl::INPUT,
    ),
    'successUrl' => array(
        'value'       => '',
        'title'       => 'Success Url',
        'description' => 'URL страницы в системе Продавца для перенаправления Покупателя в случае успешной оплаты.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'declineUrl' => array(
        'value'       => '',
        'title'       => 'Decline Url',
        'description' => 'URL страницы в системе Продавца для перенаправления Покупателя в случае неуспешной попытки оплаты.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'paymentTypeGroupId' => array(
        'value'       => '',
        'title'       => 'Payment Type Group Id',
        'description' => 'Идентификатор Платежного инструмента, который используется для его автовыбора.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'language' => array(
        'value'       => 'ru',
        'title'       => 'Language',
        'description' => 'Язык отображаемых Покупателю страниц в процессе оформления им платежа на стороне платежной платформы.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'orderTimelimit' => array(
        'value'       => '',
        'title'       => 'Order Time Limit',
        'description' => 'Максимальное время нахождения Покупателя на платежной странице в минутах. Укажите целые числа. (Укажите либо orderTimelimit, либо orderExpiretime)',
        'control_type' => waHtmlControl::INPUT,
    ),
    'orderExpiretime' => array(
        'value'       => '',
        'title'       => 'Order Expire Time',
        'description' => 'Дата и время истечения резерва заказа. Укажите количество минут в целых числах. (Укажите либо orderTimelimit, либо orderExpiretime)',
        'control_type' => waHtmlControl::INPUT,
    ),
);