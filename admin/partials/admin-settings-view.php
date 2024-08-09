<?php
/**
 * @var array $wcTaxes
 * @var WP_Post[] $pages
 * @var string $wcCalcTaxes
 * @var array $ymTaxRatesEnum
 * @var array $ymTaxes
 * @var string $isHoldEnabled
 * @var string $descriptionTemplate
 * @var string $isReceiptEnabled
 * @var bool $testMode
 * @var string $isDebugEnabled
 * @var string $forceClearCart
 * @var bool|null $validCredentials
 * @var string $active_tab
 * @var bool $isNeededShowNps
 * @var string $flashpayNonce
 */

?>

<!-- Start tabs -->
<h2 class="nav-tab-wrapper">
    <a class="nav-tab <?php echo $active_tab == 'flashpay-settings' ? 'nav-tab-active' : ''; ?>"
       href="?page=flashpay_api_menu&tab=flashpay-settings">
        <?= __('Настройки модуля FlashPay для WooCommerce', 'flashpay'); ?>
    </a>
    <a class="nav-tab <?php echo $active_tab == 'flashpay-transactions' ? 'nav-tab-active' : ''; ?>"
       href="?page=flashpay_api_menu&tab=flashpay-transactions" style="display:none;">
        <?= __('Список платежей через модуль FlashPay', 'flashpay'); ?>
    </a>
</h2>
<div class="wrap">

    <div class="tab-panel" id="flashpay-settings" <?php echo $active_tab != 'flashpay-settings' ? 'style="display: none;' : ''; ?>>

        <div class="container-max">
            <div class="container-fluid">
                <div class="row padding-bottom">
                    <div class="col-md-12 qa-title">
                        <h2><?= __('Настройки модуля FlashPay для WooCommerce', 'flashpay'); ?></h2>
                    </div>
                 </div>
                <div class="row">
                    <div class="col-md-3 col-lg-2 qa-module-version">
                        <p><?= __('Версия модуля', 'flashpay') ?> <?= FLASHPAY_VERSION; ?></p>
                    </div>
                    <div class="col-md-8 qa-info-label">

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="flashpay-tab">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs nav-fill qa-layout-tabs" role="tablist">
                                <li id="tab-section1" class="nav-item" role="presentation">
                                    <a href="#section1" class="nav-link active" role="tab" data-toggle="tab"><?= __('Авторизация', 'flashpay'); ?></a>
                                </li>
                                <li id="tab-section2" class="nav-item" role="presentation">
                                    <a href="#section2" class="nav-link" role="tab" data-toggle="tab"><?= __('Доп. функции', 'flashpay'); ?></a>
                                </li>
                                <li id="tab-section3" class="nav-item" role="presentation">
                                    <a href="#section3" class="nav-link" role="tab" data-toggle="tab"><?= __('Чеки', 'flashpay'); ?></a>
                                </li>
                                <li id="tab-section4" class="nav-item" role="presentation">
                                    <a href="#section4" class="nav-link" role="tab" data-toggle="tab"><?= __('Настройка уведомлений', 'flashpay');?></a>
                                </li>
                                <li id="tab-section5" class="nav-item" role="presentation">
                                    <a href="#section5" class="nav-link" role="tab" data-toggle="tab"><?= __('Готово', 'flashpay'); ?></a>
                                </li>
                            </ul>
                            <!-- Tab panes -->
                            <div class="tab-content tabs">
                                <div role="tabpanel" class="tab-pane fade show active" id="section1"></div>
                                <div role="tabpanel" class="tab-pane fade show" id="section2"></div>
                                <div role="tabpanel" class="tab-pane fade show" id="section3"></div>
                                <div role="tabpanel" class="tab-pane fade show" id="section4"></div>
                                <div role="tabpanel" class="tab-pane fade show" id="section5"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="tab-panel" style="display:none;"
         id="flaspay-transactions" <?php echo $active_tab != 'flashpay-transactions' ? 'style="display: none;' : ''; ?>>
        <form id="events-filter" method="POST">
            <?php
            FlashPayTransactionsListTable::render();
            ?>
            <input name="form_nonce" type="hidden" value="<?=$flashpayNonce?>" />
        </form>
    </div>
</div>
<!-- End tabs -->
