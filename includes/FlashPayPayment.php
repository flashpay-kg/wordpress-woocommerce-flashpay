<?php


class FlashPayPayment
{

    /** @var FlashPayPaymentsTableModel */
    private $paymentsTableModel;

    private $plugin_name;

    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        $this->initRouter();
    }

    public function initRouter()
    {
        add_action( 'wp_loaded', function () {
            add_rewrite_endpoint('flashpay/returnUrl', EP_ALL);
        });

        add_filter('query_vars', function( $query_vars ) {
            if (in_array('flashpay/returnUrl', $query_vars)) {
                $query_vars[] = 'flashpay-order-id';
            }
            return $query_vars;
        });

        add_action('template_redirect', function() {
            $orderId = get_query_var('flashpay-order-id');
            if (!empty($orderId)) {
                $gateway = new FlashPayGateway();
                $gateway->processReturnUrl($orderId);
            }
        });
    }

    /**
     * @return FlashPayPaymentsTableModel
     */
    public function getPaymentTableModel()
    {
        if (!$this->paymentsTableModel) {
            global $wpdb;
            $this->paymentsTableModel = new FlashPayPaymentsTableModel($wpdb);
        }
        return $this->paymentsTableModel;
    }

    /**
     * @param $viewPath
     * @param $args
     *
     * @return false|string
     */
    private function render($viewPath, $args)
    {
        ob_start();
        extract($args);
        include (plugin_dir_path(__FILE__) . $viewPath);
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }


    public function addGateways($methods)
    {
        global $woocommerce;

        $methods[] = 'FlashPayGatewayCard';

        return $methods;
    }

    public function loadGateways()
    {
        require_once plugin_dir_path(dirname(__FILE__)).'includes/gateway/FlashPayGateway.php';
        require_once plugin_dir_path(dirname(__FILE__)).'includes/gateway/FlashPayGatewayCard.php';
    }

    public function processCallback()
    {
        if (
            $_SERVER['REQUEST_METHOD'] === "POST" &&
            isset($_REQUEST['flashpay'])
            && $_REQUEST['flashpay'] === 'callback'
        ) {

            FlashPayLogger::info('Notification init');
            $body           = @file_get_contents('php://input');
            $callbackParams = json_decode($body, true);
            FlashPayLogger::info('Notification body: '.$body);

            if (!json_last_error()) {
                try {
                    $this->processNotification($callbackParams);
                } catch (Exception $e) {
                    FlashPayLogger::error("Error while process notification: ".$e->getMessage());
                }
            } else {
                FlashPayLogger::info('Notification json error');
                header("HTTP/1.1 400 Bad Request");
                header("Status: 400 Bad Request");
            }
            exit();
        }
    }


    protected function processNotification($callbackParams)
    {
        FlashPayLogger::info('Init notification process');

        $gateway = new FlashPayGateway();

        $inputParamsError = $gateway->checkWebhookInputParams($callbackParams);

        if (!empty($inputParamsError)) {
            FlashPayLogger::error('Notification: invalid params (' . json_encode($inputParamsError) . ')');
            header("HTTP/1.1 400 Bad request");
            header("Status: 400 Bad request");
            exit();
        }

        $WP_REST_Request = new WP_REST_Request();
        $signature = $WP_REST_Request->get_header( 'X-Signature' );
        $isTest = (bool)get_option('flashpay_is_test', '0');
        $secretKey = $isTest?get_option('flashpay_shop_password_test'):get_option('flashpay_shop_password');

//        if ($signature !== $gateway->getSignatureOfString(json_encode($callbackParams), $secretKey)) {
//            FlashPayLogger::error('Notification: invalid signature');
//            header("HTTP/1.1 401 Unauthorized");
//            header("Status: 401 Unauthorized");
//            exit();
//        }

        $order = wc_get_order($callbackParams['order_id']);

        if (empty($order)) {
            FlashPayLogger::error('Notification: order not found');
            header("HTTP/1.1 404 Not found");
            header("Status: 404 Not found");
            exit();
        }

        $paymentStatus = strtolower($callbackParams['operation']['status']);

        FlashPayLogger::info('Notification: payment status ' . $paymentStatus);

        echo json_encode($callbackParams);
        switch (true) {
            case $paymentStatus === FlashPayPaymentStatus::SUCCESS:
                FlashPayLogger::info('Notification: payment success');
                $order->payment_complete($callbackParams['transaction_id']);
                break;
            case $paymentStatus === FlashPayPaymentStatus::DECLINE:
                FlashPayLogger::info('Notification: payment decline');
                $order->update_status('cancelled');
                break;
            default:
                $order->update_status('pending');
                FlashPayLogger::info('Notification: pending. Status');
                header("HTTP/1.1 200");
                header("Status: 200 OK");
        }
    }

    public function validStatuses()
    {
        return array('processing', 'completed', 'on-hold', 'pending');
    }

    public function checkPaymentStatus()
    {
        $order_id  = sanitize_key($_GET['order-id']);
        FlashPayLogger::info('CheckPaymentStatus Init: ' . $order_id);

        $order     = wc_get_order($order_id);
        $paymentId = $order->get_transaction_id();

        if (!$this->isFlashPayOrder($order)) {
            FlashPayLogger::info('Payment method is not FlashPay!');
            wp_die();
        }

        try {
            $payment = $this->getApiClient()->getPaymentInfo($paymentId);
            $result = json_encode(array(
                'result' => 'success',
                'status' => $payment->getStatus(),
                'redirectUrl' => $order->get_checkout_payment_url()
            ));
            FlashPayLogger::info('CheckPaymentStatus: ' . $result);
            echo $result;
        } catch (Exception $e) {
            FlashPayLogger::error('CheckPaymentStatus Error: ' . $e->getMessage());
        }
        wp_die();
    }

    /**
     * @param int $order_id
     *
     * @throws Exception
     */
    public function changeOrderStatusToProcessing($order_id)
    {
        FlashPayLogger::info('Init changeOrderStatusToProcessing');
        if (!get_option('flashpay_enable_hold')) {
            return;
        }
        if (!$order_id) {
            return;
        }

        $order     = wc_get_order($order_id);
        $paymentId = $order->get_transaction_id();

        if (!$this->isFlashPayOrder($order)) {
            FlashPayLogger::info('Payment method is not FlashPay!');
            return;
        }

    }

    /**
     * @param int $order_id
     *
     * @throws Exception
     */
    public function changeOrderStatusToCancelled($order_id)
    {
        FlashPayLogger::info('Init changeOrderStatusToCancelled');
        if (!get_option('flashpay_enable_hold')) {
            return;
        }
        if (!$order_id) {
            return;
        }

        $order     = wc_get_order($order_id);
        $paymentId = $order->get_transaction_id();

        if (!$this->isFlashPayOrder($order)) {
            FlashPayLogger::info('Payment method is not FlashPay!');
            return;
        }

    }

    protected function cancelPayment($payment)
    {
        return false;
    }

    /**
     * @param WC_Order $order
     * @return bool
     */
    private function isFlashPayOrder(WC_Order $order)
    {
        $wcPaymentMethod = $order->get_payment_method();
        FlashPayLogger::info('Check PaymentMethod: ' . $wcPaymentMethod);

        return (strpos($wcPaymentMethod, 'flashpay_') !== false);
    }
}
