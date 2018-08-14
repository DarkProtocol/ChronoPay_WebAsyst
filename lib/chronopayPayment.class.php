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
     * @param string $declineUrl
     *
     * @return sting
    */
    private function generatePaymentUrl($declineUrl)
    {    

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
        $url .= '&sign=' . urlencode($this->generatePaymentSign($price, $orderId));
        $url .= '&decline_url=' . urlencode($declineUrl);

        return $url;

    }


    /**
     * Generate payment sign
     *
     * @param float $orderPrice
     * @param string $orderId
     *
     * @return string
     */
    private function generatePaymentSign($orderPrice, $orderId)
    {    
        return md5(
            $this->getProductId() . '-' . 
            $orderPrice . '-' . 
            $orderId . '-' . $this->getSharedSec()
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