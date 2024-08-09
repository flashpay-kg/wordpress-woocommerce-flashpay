<?php

if (!class_exists('WC_Payment_Gateway')) {
    //return;
}

class FlashPayGateway extends WC_Payment_Gateway
{
    /**
     * @var string
     */
    const RETURN_URI_PATTERN = "flashpay/returnUrl?flashpay-order-id=%s";
    const RETURN_SIMPLE_PATTERN = "?flashpay=returnUrl&flashpay-order-id=%s";

    const MINIMUM_SUBSCRIBE_AMOUNT = 1;

    public $paymentMethod;

    /**
     * @var string default description for payment method (if title  empty)
     */
    public $defaultDescription = '';

    /**
     * @var string default title for payment method (if description empty)
     */
    public $defaultTitle = '';

    /**
     * @var string gateway description (admin panel)
     */
    public $method_description;

    /**
     * @var string gateway title (admin panel)
     */
    public $method_title;

    /**
     * @var string path to payment icon
     */
    public $icon;

    /**
     * @var bool
     */
    protected $subscribe = false;

    /**
     * @var float
     */
    protected $amount = 0.0;

    protected $enableRecurrentPayment;

    private $recurentPaymentMethodId;


    public function __construct()
    {
        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_settings();
        $this->title       = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->supports    = array(
            'products',
        );

        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action(
                'woocommerce_update_options_payment_gateways_'.$this->id,
                array(
                    $this,
                    'process_admin_options',
                )
            );
        } else {
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        }

        add_action('woocommerce_receipt_'.$this->id, array($this, 'receipt_page'));

        if (class_exists('WC_Subscriptions_Order')) {
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
            add_action('woocommerce_subscription_cancelled_' . $this->id, array($this, 'subscription_canceled'), 10, 2);
            add_action('woocommerce_subscription_expired_' . $this->id, array($this, 'subscription_expired'), 10, 2);
        }

    }

    /**
     * Init settings for gateways.
     */
    public function init_settings()
    {
        parent::init_settings();

        $paymentSubjectEnum = array(
            FlashPayPaymentSubject::COMMODITY             => 'Товар',
            FlashPayPaymentSubject::EXCISE                => 'Подакцизный товар',
            FlashPayPaymentSubject::JOB                   => 'Работа',
            FlashPayPaymentSubject::SERVICE               => 'Услуга',
            FlashPayPaymentSubject::GAMBLING_BET          => 'Ставка в азартной игре',
            FlashPayPaymentSubject::GAMBLING_PRIZE        => 'Выигрыш в азартной игре',
            FlashPayPaymentSubject::LOTTERY               => 'Лотерейный билет',
            FlashPayPaymentSubject::LOTTERY_PRIZE         => 'Выигрыш в лотерею',
            FlashPayPaymentSubject::INTELLECTUAL_ACTIVITY => 'Результаты интеллектуальной деятельности',
            FlashPayPaymentSubject::PAYMENT               => 'Платеж',
            FlashPayPaymentSubject::AGENT_COMMISSION      => 'Агентское вознаграждение',
            FlashPayPaymentSubject::COMPOSITE             => 'Несколько вариантов',
            FlashPayPaymentSubject::ANOTHER               => 'Другое',
        );

        $paymentModeEnum = array(
            //FlashPayPaymentMode::FULL_PREPAYMENT    => 'Полная предоплата',
            //FlashPayPaymentMode::PARTIAL_PREPAYMENT => 'Частичная предоплата',
            //FlashPayPaymentMode::ADVANCE            => 'Аванс',
            FlashPayPaymentMode::FULL_PAYMENT       => 'Полный расчет',
            //FlashPayPaymentMode::PARTIAL_PAYMENT    => 'Частичный расчет и кредит',
            //FlashPayPaymentMode::CREDIT             => 'Кредит',
            //FlashPayPaymentMode::CREDIT_PAYMENT     => 'Выплата по кредиту',
        );

        $this->addReceiptAttribute('flashpay_payment_subject', __('Признак предмета расчета', 'flashpay'), $paymentSubjectEnum);
        $this->addReceiptAttribute('flashpay_payment_mode', __('Признак способа расчёта', 'flashpay'), $paymentModeEnum);
    }

    /**
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'     => array(
                'title'   => __('Включить/Выключить', 'flashpay'),
                'type'    => 'checkbox',
                'label'   => $this->method_description,
                'default' => 'no',
            ),
            'title'       => array(
                'title'       => __('Заголовок', 'flashpay'),
                'type'        => 'text',
                'description' => __('Название, которое пользователь видит во время оплаты', 'flashpay'),
                'default'     => $this->defaultTitle,
            ),
            'description' => array(
                'title'       => __('Описание', 'flashpay'),
                'type'        => 'textarea',
                'description' => __('Описание, которое пользователь видит во время оплаты', 'flashpay'),
                'default'     => $this->defaultDescription,
            ),
        );
    }

    /**
     * @return void
     */
    public function admin_options()
    {
        echo '<h5>'.__(
                'Для работы с модулем необходимо подключить магазин к FlashPay. После подключения вы получите параметры для приема платежей (идентификатор магазина — shopId  и секретный ключ).',
                'flashpay'
            ).'</h5>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    /**
     *  There are no payment fields, but we want to show the description if set.
     */
    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
    }

    /**
     * @param $orderId
     * @return void
     */
    public function processReturnUrl($orderId)
    {
        FlashPayLogger::info(
            'Return process init.'
        );
        global $woocommerce;
        $order_id = wc_get_order_id_by_order_key(wc_clean(wp_unslash($orderId)));
        $order    = wc_get_order($order_id);
        if ( class_exists('sitepress') ) {
            do_action( 'wpml_switch_language', get_post_meta($order_id, 'wpml_language')[0] );
        }
        $paymentId = $order->get_transaction_id();
        FlashPayLogger::info(
            sprintf(__('Пользователь вернулся с формы оплаты. Id заказа - %1$s. Идентификатор платежа - %2$s.',
                'flashpay'), $order_id, $paymentId)
        );
        try {
            if ($this->isPaymentSuccess($payment)) {
                $woocommerce->cart->empty_cart();
                wp_redirect($this->get_success_fail_url('flashpay_success', $order));
            } else {
                wp_redirect($this->get_success_fail_url('flashpay_fail', $order));
            }
        } catch (\Exception $e) {
            FlashPayLogger::error('Api error: '.$e->getMessage());
        }
    }

    /**
     * @param Payment $payment
     * @return bool
     */
    private function isPaymentSuccess($payment)
    {
        if ($payment->getMetadata()->offsetExists('subscribe_trial')
            && in_array($payment->getStatus(), self::getValidForTrialStatuses())) {
            return true;
        } else if (in_array($payment->getStatus(), self::getValidPaidStatuses())) {
            return true;
        } else if ($payment->getStatus() === FlashPayPaymentStatus::SUCCESS && $payment->getPaid()) {
            return true;
        }
        return false;
    }

    protected static function getValidPaidStatuses()
    {
        return array(
            FlashPayPaymentStatus::SUCCESS,
        );
    }

    protected function getValidForTrialStatuses()
    {
        return array(
            FlashPayPaymentStatus::CANCELED,
            FlashPayPaymentStatus::WAITING_FOR_CAPTURE,
        );
    }

    public function createPayment($order)
    {

        $builder        = $this->getBuilder($order);
        $paymentRequest = $builder->build();
        if (FlashPayHandler::isReceiptEnabled()) {
            $receipt = $paymentRequest->getReceipt();
            if ($receipt instanceof Receipt) {
                $receipt->normalize($paymentRequest->getAmount());
            }
        }
        $serializer     = new CreatePaymentRequestSerializer();
        $serializedData = $serializer->serialize($paymentRequest);
        FlashPayLogger::info('Create payment request: '.json_encode($serializedData));
        try {
            $response = $this->getApiClient()->createPayment($paymentRequest);
            FlashPayLogger::info('Create payment response: '.json_encode($response->toArray()));
            return $response;
        } catch (ApiException $e) {
            FlashPayLogger::error('Api error: '.$e->getMessage());
            return new WP_Error($e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            FlashPayLogger::error('Create payment response: '.json_encode($e));
        }
    }

    public function subscription_canceled($subscription)
    {
        FlashPayLogger::info('Subscription id = ' . $subscription->get_id() . ' was canceled');
    }

    public function subscription_expired($subscription)
    {
        FlashPayLogger::info('Subscription id = ' . $subscription->get_id() . ' is expired');
    }

    public function scheduled_subscription_payment($amount, $order)
    {
        $this->recurentPaymentMethodId = $order->get_meta('_flashpay_saved_payment_id');
        $this->amount = $amount;
        FlashPayLogger::info(
            sprintf('Start subscription payment, recurentId = %s and amount = %s', $this->recurentPaymentMethodId, $amount)
        );
        $this->process_payment($order->get_id());
    }


    /**
     * Process the payment and return the result
     *
     * @param $order_id
     *
     * @return array
     * @throws WC_Data_Exception
     * @throws Exception
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if (FlashPayHandler::isReceiptEnabled() && FlashPayHandler::isSelfEmployed()) {
            try {
                FlashPayHandler::checkConditionForSelfEmployed($order);
            } catch (Exception $e) {
                FlashPayLogger::warning(sprintf(__('Не удалось создать платеж. Для заказа %1$s', 'flashpay'), $order_id) . ' ' . strip_tags($e->getMessage()));
                wc_add_notice($e->getMessage(), 'error');
                return array('result' => 'fail', 'redirect' => '');
            }
        }

        if (class_exists('WC_Subscriptions_Cart')
            && WC_Subscriptions_Cart::cart_contains_subscription()) {
            $this->subscribe = true;
        }

        $isTest = (bool)get_option('flashpay_is_test', '0');
        $url = $isTest?get_option('flashpay_api_url_test'):get_option('flashpay_api_url');
        $shopId = $isTest?get_option('flashpay_shop_id_test'):get_option('flashpay_shop_id');
        $secretKey = $isTest?get_option('flashpay_shop_password_test'):get_option('flashpay_shop_password');
        $clearCart = $this->get_option('flashpay_force_clear_cart');

        $paymentData = [
            'project_id' => $shopId,
            'order_id'   => $order_id,
            'payment'    => [
                'amount'   => WC()->cart->total * 100,
                'currency' => $order->get_currency(),
            ],
            'payment_data' => [
                'method_type' => $this->paymentMethodType,
            ],
            'customer' => array_filter([
                'id'         => $order->get_customer_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'phone'      => $order->get_billing_phone(),
                'email'      => $order->get_billing_email(),
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
            ]),
            'return_url' => $this->get_return_url( $order ),
        ];

        $isReceiptEnabled = get_option('flashpay_enable_receipt');

        if ($isReceiptEnabled) {
            $paymentData['fiscal_data'] = [];

            $items = [];

            foreach ( WC()->cart->get_cart() as $cartItem ) {
                /** @var WC_Product_Simple $product */
                $product = $cartItem['data'];

                $items[] = [
                    'Name'          => mb_substr($product->get_name(), 0, 64, 'UTF-8'),
                    'Price'         => round($product->get_price() * 100),
                    'Quantity'      => round($cartItem['quantity'], 3, PHP_ROUND_HALF_UP),
                    'Amount'        => round($product->get_price() * $cartItem['quantity'] * 100),
                    'PaymentMethod' => trim(get_option('flashpay_payment_mode', get_option('flashpay_payment_mode_default'))),
                    'PaymentObject' => trim(get_option('flashpay_payment_subject', get_option('flashpay_payment_subject_default'))),
                    'Tax'           => $this->getFlashPayTax($this->getTaxRate($product->get_tax_class())),
                ];
            }

            foreach( $order->get_items( 'shipping' ) as $item ){
                if (!empty(floatval($item->get_total()))) {
                    $items[] = [
                        'Name'          => mb_substr($item->get_name(), 0, 64,  'UTF-8'),
                        'Price'         => round($item->get_total() * 100),
                        'Quantity'      => 1,
                        'Amount'        => round($item->get_total() * 100),
                        'PaymentMethod' => trim(get_option('flashpay_shipping_payment_mode', get_option('flashpay_shipping_payment_mode_default'))),
                        'PaymentObject' => trim(get_option('flashpay_shipping_payment_subject', get_option('flashpay_shipping_payment_subject_default'))),
                        'Tax'           => $this->getFlashPayTax($this->getTaxRate($item->get_taxes())),
                    ];

                }
            }

            $paymentData['fiscal_data']['Receipt'] = [
                'EmailCompany' => get_option('flashpay_fiscal_email'),
                'Email'        => $order->get_billing_email(),
                'Phone'        => $order->get_billing_phone(),
                'Taxation'     => get_option('flashpay_default_tax_system_code'),
                'Items'        => $items,
            ];
        }

        $redirectUrl = rtrim($url, '/') . '/payment?' . http_build_query(
            [
                'language' => FlashPayLanguage::getLanguage(get_option('WPLANG')),
                'body' => json_encode($paymentData),
                'signature' => $this->getSignatureOfString(json_encode($paymentData), $secretKey),
            ]
            );

        if ($clearCart) {
            WC()->cart->empty_cart();
        }

        return [
            'result' => 'success',
            'redirect' => $redirectUrl
        ];
    }

    /**
     * @param $taxes
     *
     * @return int
     */
    private function getTaxRate($taxes)
    {
        $taxRatesRelations = get_option('flashpay_tax_rate');
        $defaultTaxRate    = (int)get_option('flashpay_default_tax_rate');

        if ($taxRatesRelations) {
            $taxesSubtotal = $taxes['total'];
            if ($taxesSubtotal) {
                $wcTaxIds = array_keys($taxesSubtotal);
                $wcTaxId = $wcTaxIds[0];
                if (isset($taxRatesRelations[$wcTaxId])) {
                    return (int)$taxRatesRelations[$wcTaxId];
                }
            }
        }

        return $defaultTaxRate;
    }

    /**
     * @param int|null $tax
     * @return string
     */
    private function getFlashPayTax($tax = null)
    {
        $arrayTax = [
            1 => 'none',
            2 => '0',
            3 => '10',
            4 => '20',
            5 => '10/110',
            6 => '20/120',
        ];

        return isset($arrayTax[$tax]) ? $arrayTax[$tax] : 'none';
    }


    public function showMessage($content)
    {
        return '<div class="box '.$this->msg['class'].'-box">'.$this->msg['message'].'</div>'.$content;
    }

    // get all pages
    public function get_pages($title = false, $indent = true)
    {
        $wp_pages  = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) {
            $page_list[] = $title;
        }
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while ($has_parent) {
                    $prefix     .= ' - ';
                    $next_page  = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix.$page->post_title;
        }

        return $page_list;
    }

    /**
     * @param $name
     * @param WC_Order $order
     *
     * @return string
     */
    protected function get_success_fail_url($name, $order)
    {
        switch (get_option($name)) {
            case "wc_success":
                return $order->get_checkout_order_received_url();
            case "wc_checkout":
                return $order->get_view_order_url();
            case "wc_payment":
                return $order->get_checkout_payment_url();
            default:
                return get_page_link(get_option($name));
        }
    }

    /**
     * @return bool
     */
    private function saveNewPaymentMethod()
    {
        $savePaymentMethod = is_checkout() && !empty($_POST["wc-{$this->id}-new-payment-method"]);

        return $savePaymentMethod;
    }

    protected function getTitle()
    {
        $title = $this->title ? $this->title : $this->defaultTitle;
        return __($title, 'flashpay');
    }

    protected function getDescription()
    {
        $description = $this->description ? $this->description : $this->defaultDescription;
        return __($description, 'flashpay');
    }

    /**
     * Update payment data into database
     *
     * @param string $paymentId
     * @param array $data
     * @return null|bool
     */
    public function updatePaymentData($paymentId, $data=array())
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'flashpay_payment';
        if (!empty($data)) {
            $result = $wpdb->update(
                $table_name,
                array_merge(
                    $data,
                    array('updated_at' => date('Y-m-d H:i:s'))
                ),
                array(
                    'payment_id' => $paymentId,
                )
            );
        } else {
            $result = null;
        }

        if (!$result) {
            FlashPayLogger::error('Не удалось обновить данные платежа '.$paymentId.' в базе данных!');
        }

        return $result;
    }

    /**
     * @return string
     */
    public static function getReturnUrlPattern()
    {
        global $wp_rewrite;

        return empty($wp_rewrite->permalink_structure) ? self::RETURN_SIMPLE_PATTERN : self::RETURN_URI_PATTERN;
    }

    /**
     * @param string $attributeName
     * @param string $rawName
     * @param array $terms
     */
    public function addReceiptAttribute($attributeName, $rawName, $terms)
    {
        $isAttributeCreated = wc_attribute_taxonomy_id_by_name($attributeName);
        if (!$isAttributeCreated) {

            $args = array(
                'name' => $rawName,
                'slug' => $attributeName,
            );
            wc_create_attribute($args);

            $taxonomy_name = wc_attribute_taxonomy_name($attributeName);
            register_taxonomy(
                $taxonomy_name,
                apply_filters('woocommerce_taxonomy_objects_'.$taxonomy_name, array('product')),
                apply_filters('woocommerce_taxonomy_args_'.$taxonomy_name, array(
                    'labels'       => array(
                        'name' => $rawName,
                    ),
                    'hierarchical' => true,
                    'show_ui'      => false,
                    'query_var'    => true,
                    'rewrite'      => false,
                ))
            );
            foreach ($terms as $term => $description) {
                $insert_result = wp_insert_term($term, $taxonomy_name, array(
                    'description' => $description,
                    'parent'      => 0,
                    'slug'        => $term,
                ));
            }
        }
    }

    /**
     * @param string $data
     * @return string
     */
    public function getSignatureOfString($data, $secretKey)
    {
        return hash_hmac('sha256', $data, $secretKey);
    }

    public function checkWebhookInputParams($webhookData) {
        $validationErrorMsg = [];

        if (empty($webhookData['project_id'])) {
            $validationErrorMsg[] = 'project_id is empty';
        }

        if (empty($webhookData['order_id'])) {
            $validationErrorMsg[] = 'order_id is empty';
        }

        if (empty($webhookData['transaction_id'])) {
            $validationErrorMsg[] = 'transaction_id is empty';
        }

        if (empty($webhookData['operation']['id'])) {
            $validationErrorMsg[] = 'operation.id is empty';
        }

        if (empty($webhookData['operation']['amount'])) {
            $validationErrorMsg[] = 'operation.amount is empty';
        }

        if (empty($webhookData['operation']['currency'])) {
            $validationErrorMsg[] = 'operation.currency is empty';
        }

        if (empty($webhookData['operation']['status'])) {
            $validationErrorMsg[] = 'operation.status is empty';
        }

        return $validationErrorMsg;
    }
}
