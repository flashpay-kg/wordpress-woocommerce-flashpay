<?php


class FlashPay
{

    public static $pluginUrl;

    protected $loader;

    protected $plugin_name;

    protected $version;

    public function __construct()
    {

        $this->plugin_name = 'flashpay';
        $this->version     = '1.0.0';
        self::$pluginUrl   = plugin_dir_url(dirname(__FILE__));

        $this->loadDependencies();
        $this->setLocale();
        $this->defineAdminHooks();
        $this->definePaymentHooks();
//        $this->defineShopHooks();
//        $this->defineChangeOrderStatuses();

    }

    private function loadDependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/FlashPayLoader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/FlashPayI18N.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'enums/FlashPayAbstractEnum.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'enums/FlashPayPaymentMode.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'enums/FlashPayPaymentStatus.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'enums/FlashPayPaymentMethodType.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'enums/FlashPayPaymentSubject.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'enums/FlashPayLanguage.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/FlashPayAdmin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/FlashPayTransactionsListTable.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/FlashPayPaymentChargeDispatcher.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/FlashPayPayment.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/FlashPayLogger.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/FlashPayHandler.php';

        $this->loader = new FlashPayLoader();
    }

    private function setLocale()
    {

        $plugin_i18n = new FlashPayI18N();

        $this->loader->addAction('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    private function defineAdminHooks()
    {
        $plugin_admin = new FlashPayAdmin($this->getPluginName(), $this->getVersion());

        $this->loader->addAction('admin_menu', $plugin_admin, 'addMenu');
        $this->loader->addAction('admin_init', $plugin_admin, 'registerSettings');
        $this->loader->addAction('wp_ajax_vote_nps', $plugin_admin, 'voteNps');
    }

    private function definePaymentHooks()
    {
        $paymentKernel = new FlashPayPayment($this->getPluginName(), $this->getVersion());

        $this->loader->addAction('plugins_loaded', $paymentKernel, 'loadGateways');
        $this->loader->addAction('parse_request', $paymentKernel, 'processCallback');

        $this->loader->addFilter('woocommerce_payment_gateways', $paymentKernel, 'addGateways');
    }


    public function run()
    {
        $this->loader->run();
    }

    public function getPluginName()
    {
        return $this->plugin_name;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function getVersion()
    {
        return $this->version;
    }

}
