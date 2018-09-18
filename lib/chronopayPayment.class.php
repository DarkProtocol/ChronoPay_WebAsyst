<?php
/**
 *
 * @author Stepan Kushtuev
 * @name Chronopay
 * @description Chronopay Payment plugin.
 */
class chronopayPayment extends waPayment implements waIPayment
{   

    /**
     * Order number separator
     */
    const SEPARATOR = '__';

    /**
     * Purchase transaction status
     */
    const PURCHASE_TRANSACTION_TYPE = 'Purchase';


    /**
     * Refund transaction status
     */
    const REFUND_TRANSACTION_TYPE = 'Refund';


    /** @var waOrder $order */
    private $order = null;


    /** @int $orderId */
    private $orderId = null;


    /** 
     * Get payments url
     *
     * @return sting
     */
    public function getPaymentsUrl()
    {
        return $this->paymentsUrl;
    }


    /** 
     * Get sharedSec
     *
     * @return sting
     */
    public function getSharedSec()
    {
        return $this->sharedSec;
    }


    /** 
     * Get Product ID
     *
     * @return sting
     */
    public function getProductId()
    {
        return $this->productId;
    }


    /**
     * Get param cbUrl
     *
     * @return string | null 
     */
    public function getCbUrl()
    {
        return $this->cbUrl;
    }


    /**
     * Get param cbType
     *
     * @return string | null 
     */
    public function getCbType()
    {
        return $this->cbType;
    }


    /**
     * Get param successUrl
     *
     * @return string | null 
     */
    public function getSuccessUrl()
    {
        return $this->successUrl;
    }


    /**
     * Get param declineUrl
     *
     * @return string | null 
     */
    public function getDeclineUrl()
    {
        return $this->declineUrl;
    }


    /**
     * Get param paymentTypeGroupId
     *
     * @return string | null 
     */
    public function getPaymentTypeGroupId()
    {
        return $this->paymentTypeGroupId;
    }


    /**
     * Get param language
     *
     * @return string | null 
     */
    public function getLanguage()
    {
        return $this->language;
    }


    /**
     * Get param orderTimelimit
     *
     * @return int | null 
     */
    public function getOrderTimelimit()
    {
        return $this->orderTimelimit === null ? $this->orderTimelimit : (int) $this->orderTimelimit;
    }


    /**
     * Get param orderExpiretime
     *
     * @return int | null 
     */
    public function getOrderExpiretime()
    {
        return $this->orderExpiretime === null ? $this->orderExpiretime : (int) $this->orderExpiretime;
    }



    /**
     * Returns array of ISO3 codes of enabled currencies (from settings)
     *
     * @return array
    */
    public function allowedCurrency()
    {
        return array(
            'RUB',
            'EUR',
            'USD',
        );
    }


    /**
     * Redirect payment
     *
     * @param array $paymentFormData
     * @param waOrder $orderData
     * @param waOrder $autoSubmit
     * @return string Payment form HTML
     * @throws waException
    */
    public function payment($paymentFormData, $orderData, $autoSubmit = false)
    {   
        $this->order = waOrder::factory($orderData);

        //decline url
        $transaction_data = array(
          'order_id' => $this->order->id
        );

        $declineUrl = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);

        if (!$this->getSharedSec() || !$this->getProductId() || !$this->getPaymentsUrl()) {
            return wa()->getResponse()->redirect($declineUrl);
        }


        // generate view
        $view = wa()->getView();
        $view->assign(array(
            'url' => $this->generatePaymentUrl($declineUrl),
        ));

        return $view->fetch($this->path.'/templates/payment.html');


    }


    /**
     * Generate payment url
     *
     * @param string | null $declineUrl
     *
     * @return sting
     */
    private function generatePaymentUrl($declineUrl = null)
    {   

        // get decline url
        if (strlen($this->getDeclineUrl()) > 0) {
            $declineUrl = $this->getDeclineUrl();
        }


        // generate order_id
        $orderId = $this->app_id . self::SEPARATOR . 
                   $this->merchant_id . self::SEPARATOR .
                   $this->order->id;

        // calculate product_price
        $price = 0;
        foreach ($this->order->items as $item) {
            $price += (float) ifset($item['total'], 0);
        }

        // generate url
        $url = $this->getPaymentsUrl();
        $url .= '?product_id=' . urlencode($this->getProductId());
        $url .= '&product_price=' . urlencode($price);
        $url .= '&order_id=' . urlencode($orderId);
        $url .= $declineUrl ? '&decline_url=' . urlencode($declineUrl) : '';

        // add success_url
        if (strlen($this->getSuccessUrl()) > 0) {
            $url .= '&success_url=' . urlencode($this->getSuccessUrl());
        }

        // add cb_url
        if (strlen($this->getCbUrl()) > 0) {
            $url .= '&cb_url=' . urlencode($this->getCbUrl());
        } 

        // add cb_type
        if (strlen($this->getCbType()) > 0) {
            $url .= '&cb_type=' . $this->getCbType();
        } 

        // add payment_type_group_id
        if (strlen($this->getPaymentTypeGroupId()) > 0) {
            $url .= '&payment_type_group_id=' . $this->getPaymentTypeGroupId();
        } 

        // add language
        if (strlen($this->getLanguage()) > 0) {
            $url .= '&language=' . $this->getLanguage();
        } 

        // add orderTimelimit
        if ($this->getOrderTimelimit() != null) {

            $url .= '&orderTimelimit=' . $this->getOrderTimelimit();
            $url .= '&sign=' . $this->generatePaymentSign($price, $orderId, $this->getOrderTimelimit());

        } elseif ($this->getOrderExpiretime() != null) {

            $orderExpiretime = date('Y-m-d\TH:i:sO', time() + $this->getOrderExpiretime() * 60);
            $url .= '&orderExpiretime=' . urlencode($orderExpiretime);
            $url .= '&sign=' . $this->generatePaymentSign($price, $orderId, $orderExpiretime);

        } else {
            $url .= '&sign=' . $this->generatePaymentSign($price, $orderId);
        }


        /* CLIENT ADDRESS DATA */
        // add country
        if ($this->order->billing_address['country'] != null) {
            $url .= '&country=' . strtoupper($this->order->billing_address['country']);
        } 


        // add city
        if ($this->order->billing_address['city'] != null) {
            $url .= '&city=' . urlencode($this->order->billing_address['city']);
        } 

        // add state
        if ($this->order->billing_address['region'] != null) {
            $url .= '&state=' . urlencode($this->order->billing_address['region']);
        } 

        // add street
        if ($this->order->billing_address['street'] != null) {
            $url .= '&street=' . urlencode($this->order->billing_address['street']);
        } 

        // add zip
        if ($this->order->billing_address['zip'] != null) {
            $url .= '&zip=' . urlencode($this->order->billing_address['zip']);
        } 


        /* CLIENT NAME DATA */

        if (
            $this->order->billing_address['firstname'] != null 
            || $this->order->billing_address['lastname'] != null
        ) {

            // add f_name
            if ($this->order->billing_address['firstname'] != null) {
                $url .= '&f_name=' . urlencode($this->order->billing_address['firstname']);
            } 

            // add s_name
            if ($this->order->billing_address['lastname'] != null) {
                $url .= '&s_name=' . urlencode($this->order->billing_address['lastname']);
            } 

        } elseif ($this->order->contact_name != null) {

            $name = explode(' ', $this->order->contact_name);

            // add f_name
            $url .= '&f_name=' . urlencode($name[0]);

            // add s_name
            if (count($name) > 1) {
                $url .= '&s_name=' . urlencode($name[1]);
            }

        }

        // add phone
        if ($this->order->contact_phone != null) {
            $url .= '&phone=' . urlencode($this->order->contact_phone);
        }

        // add email
        if ($this->order->contact_email != null) {
            $url .= '&email=' . urlencode($this->order->contact_email);
        }

        return $url;

    }


    /**
     * Generate payment sign
     *
     * @param float $orderPrice
     * @param string $orderId
     * @param string | null $additionalParam
     *
     * @return string
     */
    private function generatePaymentSign($orderPrice, $orderId, $additionalParam = null)
    {   

        $additionalParamString = '';

        if ($additionalParam != null) {
            $additionalParamString = $additionalParam . '-' ;
        }

        return md5(
            $this->getProductId() . '-' . 
            $orderPrice . '-' . 
            $orderId . '-' . $additionalParamString . $this->getSharedSec()
        );
    }


    /**
     * Check callback sign
     *
     * @param string $sign
     * @param string $customerId
     * @param string $transactionId
     * @param string $transactionType
     * @param string $total
     *
     * @return bool
     */
    public function checkCallbackSign($sign, $customerId, $transactionId, $transactionType, $total)
    {
        $generatedSign = md5($this->getSharedSec() . $customerId . $transactionId . $transactionType . $total);
        return $sign === $generatedSign;
    } 


    /**
     * @inheritdoc
     */
    protected function callbackInit($request)
    {   

        if (!empty($request['order_id'])) {

            $data = explode(self::SEPARATOR, $request['order_id']);

            if (count($data) < 3) {
                throw new waPaymentException('Empty required order_id field(s)');
            }

            $this->app_id = $data[0];
            $this->merchant_id = $data[1];
            $this->orderId = $data[2];

        } else {
            throw new waPaymentException('Empty required order_id field(s)');
        }

        return parent::callbackInit($request);
    }


    /**
     * @inheritdoc
     */
    public function callbackHandler($request)
    {   

        // check all params
        $customerId = wa()->getRequest()->request('customer_id'); 
        $transactionId = wa()->getRequest()->request('transaction_id');
        $transactionType = wa()->getRequest()->request('transaction_type');
        $total = wa()->getRequest()->request('total'); 
        $sign = wa()->getRequest()->request('sign');


        if (!$customerId || !$transactionId || !$transactionType || !$total || !$sign) {
            throw new waPaymentException('Empty required field(s)');
        }

        // сheck plugin 
        if (empty($this->getSharedSec())) {
            throw new waPaymentException('Empty SharedSec');
        }

        // check sign
        if (!$this->checkCallbackSign($sign, $customerId, $transactionId, $transactionType, $total)) {
            throw new waPaymentException('Sign not verififed');
        }

        // Convert request data into acceptable format and save transaction
        
        if ($transactionType == self::PURCHASE_TRANSACTION_TYPE) {
            // if purchase
            $transactionData = $this->formalizePurchaseData($request);
            $transactionData = $this->saveTransaction($transactionData, $request);
            $result = $this->execAppCallback(self::CALLBACK_PAYMENT, $transactionData);
        }

        
        if ($transactionType == self::REFUND_TRANSACTION_TYPE) { 
            // if refund
            $transactionData = $this->formalizeRefundData($request);
            $transactionData = $this->saveTransaction($transactionData, $request);
            $result = $this->execAppCallback(self::CALLBACK_REFUND, $transactionData);
        }

        if (!empty($result['error'])) {
            throw new waPaymentException('Error on save: ' . $result['error']);
        }

        // response
        wa()->getResponse()->addHeader('Content-Type', 'application/json', true);
        echo json_encode(array('success' => 'ok'));
        return array('template' => false);
    }


    /**
     * Get last transaction for order
     *
     * @param int $orderId
     *
     * @return array | null
     */
    private function getLastTransactionForOrder($orderId)
    {   
        $transactions = $this->getTransactionsByFields(array(
            'plugin'      => $this->id,
            'order_id'    => $orderId,
            'app_id'      => $this->app_id,
            'merchant_id' => $this->key,
        )); 

        if (!$transactions) {
            return null;
        }

        return end($transactions);
    }   


    /**
     * Converts transaction raw purchase data to formatted data.
     *
     * @param array $request
     * @return array
     */
    private function formalizePurchaseData($request)
    {
        $transactionData = $this->formalizeData($request);
        $transactionData['type'] = self::OPERATION_AUTH_CAPTURE;
        $transactionData['state'] = self::STATE_CAPTURED;

        return $transactionData;
    }


    /**
     * Converts transaction raw purchase data to formatted data.
     *
     * @param array $request
     * @return array
     */
    private function formalizeRefundData($request)
    {   
        $transactionData = $this->formalizeData($request);
        $transactionData['type'] = self::OPERATION_REFUND;
        $transactionData['state'] = self::STATE_REFUNDED;

        // set parent_id
        if ($lastTransaction = $this->getLastTransactionForOrder($this->orderId)) {
            $transactionData['parent_id'] = 
                $lastTransaction['parent_id'] ? 
                $lastTransaction['parent_id'] : 
                $lastTransaction['id'];
        }

        return $transactionData;
    }


    /**
     * @inheritdoc
     */
    protected function formalizeData($request)
    {

        $viewData = '';
        $transactionData = parent::formalizeData($request);

        // generate view_data
        $viewData .= 'Имя держателя карты: ' . wa()->getRequest()->request('name', 'Неизвестно') . "\n";
        $viewData .= 'Email плательщика: ' . wa()->getRequest()->request('email', 'Неизвестно') . "\n";
        $viewData .= 'Номер карты: ' . wa()->getRequest()->request('creditcardnumber', 'Неизвестно');

        $transactionData = array_merge(
            $transactionData,
            array(
                'native_id'   => ifset($request['transaction_id']),
                'amount'      => ifset($request['total']),
                'currency_id' => strtoupper(ifset($request['currency'])),
                'result'      => 1,
                'order_id'    => $this->orderId,
                'view_data'   => $viewData,
            )
        );

        return $transactionData;
    }

}
