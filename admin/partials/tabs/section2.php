<?php

/** @var string $descriptionTemplate */
/** @var WP_Post[] $pages */
/** @var bool $forceClearCart */
/** @var bool $isDebugEnabled */
/** @var array $kassaCurrencies */
/** @var string $kassaCurrency */
/** @var string $flashpayNonce */
?>
<form id="flashpay-form-2" class="flashpay-form">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-7">
                <div class="form-group qa-payment-description">
                    <label class="control-label qa-title" for="flashpay_description_template">
                        <?= __('Описание платежа', 'flashpay'); ?>
                        <span class="dashicons dashicons-info qa-tooltip-contriol" aria-hidden="true" data-toggle="tooltip"
                              title="<?= __('Это описание транзакции, которое пользователь увидит при оплате, а вы — в личном кабинете ЮKassa. Например, «Оплата заказа №72». '.
                                  'Чтобы в описание подставлялся номер заказа (как в примере), поставьте на его месте %order_number% (Оплата заказа №%order_number%). '.
                                  'Ограничение для описания — 128 символов.',
                                  'flashpay'); ?>"><span>
                    </label>
                    <textarea type="text" id="flashpay_description_template" name="flashpay_description_template" class="form-control qa-input"
                              placeholder="<?= __('Заполните поле', 'flashpay'); ?>"><?= $descriptionTemplate ?></textarea>
                    <p class="help-block help-block-error"></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="form-group qa-success-page-data">
                    <label class="control-label qa-title" for="flashpay_success">
                        <?= __('Страница успеха', 'flashpay'); ?>
                        <span class="dashicons dashicons-info qa-tooltip-contriol" aria-hidden="true" data-toggle="tooltip"
                              title="<?= __('Эту страницу увидит покупатель, когда оплатит заказ', 'flashpay'); ?>"><span>
                    </label>
                    <select id="flashpay_success" name="flashpay_success" class="form-control qa-control">
                        <option value="wc_success" <?php echo((get_option('flashpay_success') == 'wc_success') ? ' selected' : ''); ?>>
                            <?= __('Страница "Заказ принят" от WooCommerce', 'flashpay'); ?>
                        </option>
                        <option value="wc_checkout" <?php echo((get_option('flashpay_success') == 'wc_checkout') ? ' selected' : ''); ?>>
                            <?= __('Страница оформления заказа от WooCommerce', 'flashpay'); ?>
                        </option>
                        <?php
                        if ($pages) {
                            foreach ($pages as $page) {
                                $selected = ($page->ID == get_option('flashpay_success')) ? ' selected' : '';
                                echo '<option value="' . $page->ID . '"' . $selected . '>' . $page->post_title . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="help-block help-block-error"></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="form-group qa-cancel-page-data">
                    <label class="control-label qa-title" for="flashpay_fail">
                        <?= __('Страница отказа', 'flashpay'); ?>
                        <span class="dashicons dashicons-info qa-tooltip-contriol" aria-hidden="true" data-toggle="tooltip"
                              title="<?= __('Эту страницу увидит покупатель, если что-то пойдет не так: например, если ему не хватит денег на карте',
                                  'flashpay'); ?>"><span>
                    </label>
                    <select id="flashpay_fail" name="flashpay_fail" class="form-control qa-control">
                        <option value="wc_checkout" <?= ((get_option('flashpay_fail') == 'wc_checkout') ? ' selected' : ''); ?>>
                            <?= __('Страница оформления заказа от WooCommerce', 'flashpay'); ?>
                        </option>
                        <option value="wc_payment" <?= ((get_option('flashpay_fail') == 'wc_payment') ? ' selected' : ''); ?>>
                            <?= __('Страница оплаты заказа от WooCommerce', 'flashpay'); ?>
                        </option>
                        <?php
                        if ($pages) {
                            foreach ($pages as $page) {
                                $selected = ($page->ID == get_option('flashpay_fail')) ? ' selected' : '';
                                echo '<option value="' . $page->ID . '"' . $selected . '>' . $page->post_title . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="help-block help-block-error"></p>
                </div>
            </div>
        </div>

        <div class="row"><div class="col-md-7"><hr></div></div>

        <div class="row"><div class="col-md-7"><hr></div></div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="custom-control custom-checkbox qa-cart-checkbox">
                        <input type="hidden" name="flashpay_force_clear_cart" value="0">
                        <input class="custom-control-input" type="checkbox" id="flashpay_force_clear_cart" name="flashpay_force_clear_cart" value="1" <?= $forceClearCart ? ' checked="checked" ' : '' ?>>
                        <label class="custom-control-label" for="flashpay_force_clear_cart">
                            <?= __('Удалить товары из корзины, когда покупатель переходит к оплате', 'flashpay'); ?>
                        </label>
                        <p class="help-block help-block-error"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group qa-log-data">
                    <div class="custom-control custom-checkbox qa-log-checkbox">
                        <input type="hidden" name="flashpay_debug_enabled" value="0">
                        <input class="custom-control-input" type="checkbox" id="flashpay_debug_enabled" name="flashpay_debug_enabled" value="1" <?= $isDebugEnabled ? ' checked="checked" ' : '' ?>>
                        <label class="custom-control-label" for="flashpay_debug_enabled">
                            <?= __('Запись отладочной информации', 'flashpay'); ?>
                        </label>
                    </div>
                    <p class="help-block help-block-error qa-log-info">
                        <?= __('Настройку нужно будет поменять, только если попросят специалисты FlashPay', 'flashpay'); ?>
                    </p>
                    <?php if ($isDebugEnabled && file_exists(WP_CONTENT_DIR.'/flashpay-debug.log')): ?>
                        <p>
                            <a class="btn-link qa-log-link" href="<?= content_url(); ?>/flashpay-debug.log"
                               target="_blank" rel="nofollow" download="debug.log"><?= __('Скачать лог', 'flashpay'); ?></a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row form-footer">
            <div class="col-md-12">
                <button class="btn btn-default btn-back qa-back-button" data-tab="section1"><?= __('Назад', 'flashpay'); ?></button>
                <button class="btn btn-primary btn-forward qa-forward-button" data-tab="section3"><?= __('Сохранить и продолжить', 'flashpay'); ?></button>
            </div>
        </div>
    </div>
    <input name="form_nonce" type="hidden" value="<?=$flashpayNonce?>" />
</form>
