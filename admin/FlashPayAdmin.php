<?php


class FlashPayAdmin
{
    private $plugin_name;

    private $version;

    private $npsRetryAfterDays = 90;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        add_action( 'wp_ajax_flashpay_get_tab', array( $this, 'get_tab_content' ) );
        add_action( 'wp_ajax_flashpay_save_settings', array( $this, 'save_settings' ) );
    }

    public function enqueue_styles()
    {
        wp_register_style(
            'bootstrap',
            FlashPay::$pluginUrl . 'assets/css/bootstrap.min.css',
            array(),
            '4.5.3',
            'all'
        );
        wp_enqueue_style( 'bootstrap' );

        wp_register_style(
            $this->plugin_name . '-admin',
            FlashPay::$pluginUrl . 'assets/css/flashpay-admin.css',
            array('bootstrap'),
            $this->version,
            'all'
        );
        wp_enqueue_style( $this->plugin_name . '-admin' );

    }

    public function enqueue_scripts()
    {
        wp_register_script(
            'bootstrap',
            FlashPay::$pluginUrl . 'assets/js/bootstrap.bundle.min.js',
            array('jquery'),
            '4.5.3',
            false
        );
        wp_enqueue_script( 'bootstrap' );

        wp_register_script(
            $this->plugin_name . '-admin',
            FlashPay::$pluginUrl . 'assets/js/flashpay-admin.js',
            array('jquery', 'bootstrap', 'clipboard'),
            $this->version,
            true
        );
        wp_enqueue_script( $this->plugin_name . '-admin' );
    }

    public function addMenu()
    {
        $hook = add_submenu_page(
            'woocommerce',
            __('Настройки FlashPay', 'flashpay'),
            __('Настройки FlashPay', 'flashpay'),
            'manage_options',
            'flashpay_api_menu',
            array($this, 'renderAdminPage')
        );


        add_action(
            "admin_print_styles-$hook",
            array ( $this, 'enqueue_styles' )
        );
        add_action(
            "admin_print_scripts-$hook",
            array ( $this, 'enqueue_scripts' )
        );
    }

    public function registerSettings()
    {
        register_setting('woocommerce-flashpay', 'flashpay_is_test');
        register_setting('woocommerce-flashpay', 'flashpay_api_url');
        register_setting('woocommerce-flashpay', 'flashpay_api_url_test');
        register_setting('woocommerce-flashpay', 'flashpay_shop_id');
        register_setting('woocommerce-flashpay', 'flashpay_shop_password');
        register_setting('woocommerce-flashpay', 'flashpay_shop_id_test');
        register_setting('woocommerce-flashpay', 'flashpay_shop_password_test');
        register_setting('woocommerce-flashpay', 'flashpay_epl_installments');
        register_setting('woocommerce-flashpay', 'flashpay_add_installments_block');
        register_setting('woocommerce-flashpay', 'flashpay_success');
        register_setting('woocommerce-flashpay', 'flashpay_fail');
        register_setting('woocommerce-flashpay', 'flashpay_tax_rates_enum');
        register_setting('woocommerce-flashpay', 'flashpay_description_template');
        register_setting('woocommerce-flashpay', 'flashpay_enable_receipt');
        register_setting('woocommerce-flashpay', 'flashpay_fiscal_email');
        register_setting('woocommerce-flashpay', 'flashpay_debug_enabled');
        register_setting('woocommerce-flashpay', 'flashpay_default_tax_rate');
        register_setting('woocommerce-flashpay', 'flashpay_default_tax_system_code');
        register_setting('woocommerce-flashpay', 'flashpay_force_clear_cart');
        register_setting('woocommerce-flashpay', 'flashpay_tax_rate');
        register_setting('woocommerce-flashpay', 'flashpay_payment_subject_default');
        register_setting('woocommerce-flashpay', 'flashpay_payment_mode_default');
        register_setting('woocommerce-flashpay', 'flashpay_shipping_payment_subject_default');
        register_setting('woocommerce-flashpay', 'flashpay_shipping_payment_mode_default');
        register_setting('woocommerce-flashpay', 'flashpay_kassa_currency');
        register_setting('woocommerce-flashpay', 'flashpay_kassa_currency_convert');
        register_setting('woocommerce-flashpay', 'flashpay_fiscalization_enabled');
        register_setting('woocommerce-flashpay', 'flashpay_self_employed');

        update_option(
            'flashpay_tax_rates_enum',
            [
                1 => __('Не облагается', 'flashpay'),
                2 => '0%',
                3 => '10%',
                4 => '20%',
                5 => __('Расчетная ставка 10/110', 'flashpay'),
                6 => __('Расчетная ставка 20/120', 'flashpay'),
            ]
        );

        update_option(
            'flashpay_tax_system_codes_enum',
            [
                1 => __('Общая система налогообложения', 'flashpay'),
                2 => __('Упрощенная (УСН, доходы)', 'flashpay'),
                3 => __('Упрощенная (УСН, доходы минус расходы)', 'flashpay'),
                4 => __('Единый налог на вмененный доход (ЕНВД)', 'flashpay'),
                5 => __('Единый сельскохозяйственный налог (ЕСН)', 'flashpay'),
                6 => __('Патентная система налогообложения', 'flashpay'),
            ]
        );
    }

    private function get_all_settings()
    {
        $wcTaxes                = $this->getAllTaxes();
        $wcCalcTaxes            = get_option('woocommerce_calc_taxes');
        $fpTaxRatesEnum         = get_option('flashpay_tax_rates_enum');
        $fpTaxSystemCodesEnum   = get_option('flashpay_tax_system_codes_enum');
        $pages                  = get_pages();
        $fpTaxes                = get_option('flashpay_tax_rate');
        $descriptionTemplate    = get_option('flashpay_description_template',
            __('Оплата заказа №%order_number%', 'flashpay'));
        $isReceiptEnabled       = get_option('flashpay_enable_receipt');
        $fiscalEmail       = get_option('flashpay_fiscal_email');
        $orderStatusReceipt     = get_option('flashpay_second_receipt_order_status', 'wc-completed');
        $isDebugEnabled         = (bool)get_option('flashpay_debug_enabled', '0');
        $forceClearCart         = (bool)get_option('flashpay_force_clear_cart', '0');
        $active_tab             = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'flashpay-settings';

        $isTest             = (bool)get_option('flashpay_is_test', '0');
        $flashPayUrl                 = get_option('flashpay_api_url');
        $flashPayUrlTest                 = get_option('flashpay_api_url_test');
        $shopId                 = get_option('flashpay_shop_id');
        $shopIdTest                 = get_option('flashpay_shop_id_test');
        $password               = get_option('flashpay_shop_password');
        $passwordTest               = get_option('flashpay_shop_password_test');
        $npsVoteTime            = get_option('flashpay_nps_vote_time');
        $payMode                = get_option('flashpay_pay_mode');

        $kassaCurrency          = get_option('flashpay_kassa_currency');
        $kassaCurrencyConvert   = get_option('flashpay_kassa_currency_convert');
        $isFiscalizationEnabled = (bool)get_option('flashpay_fiscalization_enabled', '0');
        $isSelfEmployed         = (bool)get_option('flashpay_self_employed', '0');

        $validCredentials = null;


        $isNeededShowNps = time() > (int)$npsVoteTime + $this->npsRetryAfterDays * 86400
            && substr($password, 0, 5) === 'live_'
            && get_locale() === 'ru_RU';

        $paymentSubjectEnum = array(
            FlashPayPaymentSubject::COMMODITY             => __('Товар', 'flashpay') . ' ('.FlashPayPaymentSubject::COMMODITY.')',
            FlashPayPaymentSubject::EXCISE                => __('Подакцизный товар', 'flashpay') . ' ('.FlashPayPaymentSubject::EXCISE.')',
            FlashPayPaymentSubject::JOB                   => __('Работа', 'flashpay') . ' ('.FlashPayPaymentSubject::JOB.')',
            FlashPayPaymentSubject::SERVICE               => __('Услуга', 'flashpay') . ' ('.FlashPayPaymentSubject::SERVICE.')',
            FlashPayPaymentSubject::GAMBLING_BET          => __('Ставка в азартной игре', 'flashpay') . ' ('.FlashPayPaymentSubject::GAMBLING_BET.')',
            FlashPayPaymentSubject::GAMBLING_PRIZE        => __('Выигрыш в азартной игре', 'flashpay') . ' ('.FlashPayPaymentSubject::GAMBLING_PRIZE.')',
            FlashPayPaymentSubject::LOTTERY               => __('Лотерейный билет', 'flashpay') . ' ('.FlashPayPaymentSubject::LOTTERY.')',
            FlashPayPaymentSubject::LOTTERY_PRIZE         => __('Выигрыш в лотерею', 'flashpay') . ' ('.FlashPayPaymentSubject::LOTTERY_PRIZE.')',
            FlashPayPaymentSubject::INTELLECTUAL_ACTIVITY => __('Результаты интеллектуальной деятельности', 'flashpay') . ' ('.FlashPayPaymentSubject::INTELLECTUAL_ACTIVITY.')',
            FlashPayPaymentSubject::PAYMENT               => __('Платеж', 'flashpay') . ' ('.FlashPayPaymentSubject::PAYMENT.')',
            FlashPayPaymentSubject::AGENT_COMMISSION      => __('Агентское вознаграждение', 'flashpay') . ' ('.FlashPayPaymentSubject::AGENT_COMMISSION.')',
            FlashPayPaymentSubject::COMPOSITE             => __('Несколько вариантов', 'flashpay') . ' ('.FlashPayPaymentSubject::COMPOSITE.')',
            FlashPayPaymentSubject::ANOTHER               => __('Другое', 'flashpay') . ' ('.FlashPayPaymentSubject::ANOTHER.')',
        );

        $paymentModeEnum = [
            //FlashPayPaymentMode::FULL_PREPAYMENT    => __('Полная предоплата', 'flashpay'),
            //FlashPayPaymentMode::PARTIAL_PREPAYMENT => __('Частичная предоплата', 'flashpay'),
            //FlashPayPaymentMode::ADVANCE            => __('Аванс', 'flashpay'),
            FlashPayPaymentMode::FULL_PAYMENT       => __('Полный расчет', 'flashpay'),
            //FlashPayPaymentMode::PARTIAL_PAYMENT    => __('Частичный расчет и кредит', 'flashpay'),
            //FlashPayPaymentMode::CREDIT             => __('Кредит', 'flashpay'),
            //FlashPayPaymentMode::CREDIT_PAYMENT     => __('Выплата по кредиту', 'flashpay'),
        ];

        $wcOrderStatuses = wc_get_order_statuses();
        $wcOrderStatuses = array_filter($wcOrderStatuses, function ($k) {
            return in_array($k, self::getValidOrderStatuses());
        }, ARRAY_FILTER_USE_KEY);

        //$kassaCurrencies = $this->createKassaCurrencyList();
        $kassaCurrencies = [];

        return array(
            'wcTaxes'                => $wcTaxes,
            'pages'                  => $pages,
            'wcCalcTaxes'            => $wcCalcTaxes,
            'ymTaxRatesEnum'         => $fpTaxRatesEnum,
            'ymTaxSystemCodesEnum'   => $fpTaxSystemCodesEnum,
            'ymTaxes'                => $fpTaxes,
            'descriptionTemplate'    => $descriptionTemplate,
            'isReceiptEnabled'       => $isReceiptEnabled,
            'fiscalEmail'            => $fiscalEmail,
            'orderStatusReceipt'     => $orderStatusReceipt,
            'isDebugEnabled'         => $isDebugEnabled,
            'forceClearCart'         => $forceClearCart,
            'validCredentials'       => $validCredentials,
            'active_tab'             => $active_tab,
            'isNeededShowNps'        => $isNeededShowNps,
            'paymentModeEnum'        => $paymentModeEnum,
            'paymentSubjectEnum'     => $paymentSubjectEnum,
            'payMode'                => $payMode,
            'wcOrderStatuses'        => $wcOrderStatuses,
            'kassaCurrencies'        => $kassaCurrencies,
            'kassaCurrency'          => $kassaCurrency,
            'kassaCurrencyConvert'   => $kassaCurrencyConvert,
            'flashPayUrl'             => $flashPayUrl,
            'flashPayUrlTest'         => $flashPayUrlTest,
            'shopId'                 => $shopId,
            'shopIdTest'             => $shopIdTest,
            'password'               => $password,
            'passwordTest'           => $passwordTest,
            'isTest'             => $isTest,
            'isFiscalizationEnabled' => $isFiscalizationEnabled,
            'flashpayNonce'           => wp_create_nonce('flashpay-nonce'),
            'isSelfEmployed'         => $isSelfEmployed,
        );
    }

    public function renderAdminPage()
    {
        $this->render(
            'partials/admin-settings-view.php',
            $this->get_all_settings()
        );
    }

    /**
     * @return array
     */
    public static function getValidOrderStatuses()
    {
        return array('wc-processing', 'wc-completed');
    }

    /**
     * Get tab settings
     */
    public function get_tab_content ()
    {
        $file = 'partials/tabs/' . sanitize_key($_GET['tab']) . '.php';
        if (is_file(plugin_dir_path(__FILE__) . $file)) {
            $this->render($file, $this->get_all_settings());
        } else {
            echo 'Error! File "' . $file . '" not found';
        }
        wp_die();
    }

    /**
     * Save settings
     */
    public function save_settings()
    {
        header('Content-Type: application/json');

        $this->isRequestSecure();

        if ($options = explode(',', wp_unslash($_POST['page_options']))) {
            $user_language_old = get_user_locale();
            // Save options
            array_map(function ($option) {
                $option = trim($option);
                if (isset($_POST[$option])) {
                    if (is_array($_POST[$option])) {
                        $value = $_POST[$option];
                        array_walk_recursive($value, function (&$item) {
                            $item = sanitize_textarea_field(wp_unslash(trim($item)));
                        });
                    } else {
                        $value = sanitize_textarea_field(wp_unslash(trim($_POST[$option])));
                    }
                } else {
                    $value = null;
                }
                update_option($option, $value);
            }, $options);

            unset($GLOBALS['locale']);
            $user_language_new = get_user_locale();
            if ($user_language_old !== $user_language_new) {
                load_default_textdomain($user_language_new);
            }
        } else {
            echo json_encode(array('status' => 'error', 'error' => 'Unknown', 'code' => 'unknown'));
            wp_die();
        }

        echo json_encode(array('status' => 'success'));
        wp_die();
    }

    public function voteNps()
    {
        update_option('flashpay_nps_vote_time', time());
    }

    public function getAllTaxes()
    {
        global $wpdb;

        $query = "
            SELECT *
            FROM {$wpdb->prefix}woocommerce_tax_rates
            WHERE 1 = 1
        ";

        $order_by = ' ORDER BY tax_rate_order';

        return $wpdb->get_results($query.$order_by);
    }

    private function isRequestSecure()
    {
        if (!is_ajax()) {
            echo json_encode(array('status' => 'error', 'error' => 'Unknown', 'code' => 'unknown'));
            wp_die();
        }

        if( !current_user_can('manage_woocommerce') && !current_user_can('administrator') ) {
            wp_die('Forbidden', 'Forbidden', 403);
        }

        json_encode([$_POST['form_nonce']]);

        if (!isset($_POST['form_nonce']) || !wp_verify_nonce($_POST['form_nonce'],'flashpay-nonce')) {
            wp_die(json_encode([$_POST['form_nonce'], wp_verify_nonce($_POST['form_nonce'],'flashpay-nonce')]), 'Bad request', 400);
            //wp_die('Bad request', 'Bad request', 400);
        }
    }

    private function render($viewPath, $args)
    {
        extract($args);

        include(plugin_dir_path(__FILE__) . $viewPath);
    }

    /**
     * @return array
     */
    private function createKassaCurrencyList()
    {
        $allCurrencies = get_woocommerce_currencies();
        $currentCurrency = get_woocommerce_currency();
        $kassa_currencies = CurrencyCode::getEnabledValues();

        $available_currencies = array(CurrencyCode::RUB);
        if (in_array($currentCurrency, $kassa_currencies)) {
            $available_currencies[] = $currentCurrency;
        }

        $return_currencies = array();
        foreach (array_unique($available_currencies) as $code) {
            $return_currencies[$code] = $allCurrencies[$code];
        }
        return $return_currencies;
    }
}
