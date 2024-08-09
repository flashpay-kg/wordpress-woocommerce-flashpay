<?php

/** @var string $flashpayNonce */
?>
<form id="flashpay-form-4" class="flashpay-form">
    <div class="col-md-12">

        <div class="row qa-notification">
            <div class="col-md-6 padding-bottom">
                <div class="row">
                    <div class="col-md-12">
                        <div class="info-block">
                            <span class="dashicons dashicons-info" aria-hidden="true"></span>
                            <p class="qa-info">
                                <?= __("Пропишите URL для уведомлений в <a data-qa-settings-link='https://lk.flashpay.kg/' target='_blank' href='https://lk.flashpay.kg'>настройках личного кабинета FlashPay</a>.", 'flashpay'); ?><br>
                                <?= __('Он позволит изменять статус заказа после оплаты в вашем магазине.', 'flashpay'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row padding-bottom">
                    <div class="col-md-12">
                        <div class="input-group mb-3">
                            <input type="text" id="notify_url" name="notify_url" class="form-control qa-input" readonly="readonly" aria-describedby="button-copy"
                                   value="<?=site_url('/?flashpay=callback', 'https');?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary copy-button" type="button" id="button-copy" data-toggle="tooltip" data-placement="top"
                                        data-clipboard-text="<?=site_url('/?flashpay=callback', 'https');?>"
                                        data-success="<?=__('Скопировано!', 'flashpay');?>" data-error="<?=__('Попробуйте Ctr+C!', 'flashpay');?>">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 col-md-offset-1 help-side">

            </div>
        </div>

        <div class="row form-footer">
            <div class="col-md-12">
                <button class="btn btn-default btn-back qa-back-button" data-tab="section3"><?= __('Назад', 'flashpay'); ?></button>
                <button class="btn btn-primary btn-forward qa-forward-button" data-tab="section5"><?= __('Сохранить и продолжить', 'flashpay'); ?></button>
            </div>
        </div>
    </div>
    <input name="form_nonce" type="hidden" value="<?=$flashpayNonce?>" />
</form>
