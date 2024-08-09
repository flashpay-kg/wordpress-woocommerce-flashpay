<?php

/** @var int $isReceiptEnabled */
/** @var int $isSecondReceiptEnabled */
/** @var array $ymTaxRatesEnum */
/** @var array $ymTaxSystemCodesEnum */
/** @var array $ymTaxes */
/** @var string $wcCalcTaxes */
/** @var string $orderStatusReceipt */
/** @var array $wcTaxes */
/** @var array $paymentSubjectEnum */
/** @var array $paymentModeEnum */
/** @var array $wcOrderStatuses */
/** @var bool $isSelfEmployed */
/** @var string $flashpayNonce */
?>
<form id="flashpay-form-3" class="flashpay-form">
    <div class="col-md-12">

        <div class="row padding-bottom">
            <div class="col-md-12 form-group">
                <div class="custom-control custom-switch qa-enable-receipt-control">
                    <input type="hidden" name="flashpay_enable_receipt" value="0">
                    <input <?=($isReceiptEnabled)?' checked':'';?> type="checkbox" class="custom-control-input" id="flashpay_enable_receipt" name="flashpay_enable_receipt" value="1" data-toggle="collapse" data-target="#tax-collapsible" aria-controls="tax-collapsible">
                    <label class="custom-control-label" for="flashpay_enable_receipt">
                        <?= __('Автоматическая отправка чеков', 'flashpay'); ?>
                    </label>
                </div>
            </div>
        </div>

        <div id="tax-collapsible" class="in collapse<?=($isReceiptEnabled) ? ' show' : ''; ?>">

            <div class="row padding-bottom">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="flashpay_fiscal_email">Email</label>
                        <input type="email" id="flashpay_fiscal_email" name="flashpay_fiscal_email"
                               value="<?php echo $flashPayFiscalEmail; ?>" class="form-control"
                               placeholder="<?= __('Заполните поле', 'flashpay'); ?>" />
                        <p class="help-block help-block-error"></p>
                    </div>
                </div>
            </div>

            <h6 class="qa-title"><?= __('Выберите ваш статус:', 'flashpay'); ?></h6>

            <div class="row">
                <div class="col-sm-4 col-md-4 col-lg-3 form-group">
                    <div class="custom-control custom-switch-radio qa-enable-self-employed-control">
                        <label for="flashpay_legal_entity" data-target="flashpay_legal_entity">
                            <input <?= (!$isSelfEmployed) ? ' checked' : ''; ?> type="radio" id="flashpay_legal_entity" name="flashpay_self_employed" value="0">
                            <span><?= __('ИП или юрлицо', 'flashpay'); ?></span>
                        </label>
                        <label for="flashpay_self_employed" data-target="flashpay_self_employed">
                            <input <?= ($isSelfEmployed) ? ' checked' : ''; ?> type="radio" id="flashpay_self_employed" name="flashpay_self_employed" value="1">
                            <span><?= __('Самозанятый', 'flashpay'); ?></span>
                        </label>
                    </div>
                </div>
            </div>


            <div class="content flashpay_self_employed in collapse <?= ($isSelfEmployed) ? 'show' : ''; ?>">
                <div><strong><?= __('Чтобы платёж прошёл и чек отправился:', 'flashpay');?></strong></div>
                <ol>
                    <li>
                        <?= __('Количество может быть только целым числом, дробные использовать нельзя.<br> Например, 1.5 — не пройдёт, а 2 — пройдёт.', 'flashpay');?>
                    </li>
                </ol>
            </div>

            <div class="content flashpay_legal_entity in collapse <?= (!$isSelfEmployed) ? 'show' : ''; ?>">

                <div class="row padding-bottom">
                    <div class="col-md-6 qa-tax-system">
                        <label class="qa-title" for="flashpay_default_tax_system_code"><?= __('Система налогообложения', 'flashpay'); ?></label>
                        <div class="qa-tax-system-control">
                            <p class="help-block text-muted"><?= __('Выберите систему налогообложения', 'flashpay'); ?></p>
                            <select id="flashpay_default_tax_system_code" name="flashpay_default_tax_system_code" class="form-control">
                                <option value="">-</option>
                                <?php foreach ($ymTaxSystemCodesEnum as $taxCodeId => $taxCodeName) : ?>
                                    <option value="<?= $taxCodeId ?>" <?= $taxCodeId == get_option('flashpay_default_tax_system_code') ? 'selected="selected"' : ''; ?>><?= $taxCodeName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row padding-bottom">
                    <div class="col-md-6 qa-vat">
                        <label for="flashpay_default_tax_rate qa-title"><?= __('Ставка НДС', 'flashpay'); ?></label>
                        <div class="qa-vat-control">
                            <p class="help-block text-muted"><?= __('Выберите ставку, которая будет в чеке', 'flashpay'); ?></p>
                            <select id="flashpay_default_tax_rate" name="flashpay_default_tax_rate" class="form-control">
                                <?php foreach ($ymTaxRatesEnum as $taxId => $taxName) : ?>
                                    <option value="<?= $taxId ?>" <?= $taxId == get_option('flashpay_default_tax_rate') ? 'selected="selected"' : ''; ?>><?= $taxName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <?php if ($wcCalcTaxes == 'yes' && $wcTaxes) : ?>
                    <div class="qa-match-rates">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="qa-title"><?= __('Сопоставьте ставки', 'flashpay'); ?></h4>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-6 col-md-3">
                                <label><?= __('Ставка в вашем магазине', 'flashpay'); ?></label>
                            </div>
                            <div class="col-xs-6 col-md-3">
                                <label><?= __('Ставка для чека в налоговую', 'flashpay'); ?></label>
                            </div>
                        </div>
                        <?php foreach ($wcTaxes as $wcTax) : ?>
                            <div class="row mb-1">
                                <div class="col-xs-6 col-md-3 qa-shop-rate"><?= round($wcTax->tax_rate) ?>%</div>
                                <div class="col-xs-6 col-md-3">
                                    <?php $selected = isset($ymTaxes[$wcTax->tax_rate_id]) ? $ymTaxes[$wcTax->tax_rate_id] : null; ?>
                                    <select id="flashpay_tax_rate[<?= $wcTax->tax_rate_id ?>]" name="flashpay_tax_rate[<?= $wcTax->tax_rate_id ?>]" class="form-control qa-control">
                                        <?php foreach ($ymTaxRatesEnum as $taxId => $taxName) : ?>
                                            <option value="<?= $taxId ?>" <?= $selected == $taxId ? 'selected' : ''; ?> ><?= $taxName ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="qa-calculation-method">
                    <div class="row padding-bottom padding-top">
                        <div class="col-md-6">
                            <h4 class="qa-title"><?= __('Предмет расчёта и способ расчёта (ФФД 1.05)', 'flashpay'); ?></h4>
                            </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-6 col-md-3 qa-calculation-subject">
                            <label for="flashpay_payment_subject_default"><?= __('Предмет расчёта', 'flashpay'); ?></label>
                            <select id="flashpay_payment_subject_default" name="flashpay_payment_subject_default" class="form-control">
                                <?php foreach ($paymentSubjectEnum as $id => $subjectName) : ?>
                                    <option value="<?= $id ?>" <?= $id == get_option('flashpay_payment_subject_default') ? 'selected="selected"' : ''; ?>><?= $subjectName ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block"></p>
                        </div>
                        <div class="col-xs-6 col-md-3 qa-calculation-method">
                            <label for="flashpay_payment_mode_default"><?= __('Способ расчёта', 'flashpay'); ?></label>
                            <select id="flashpay_payment_mode_default" name="flashpay_payment_mode_default" class="form-control">
                                <?php foreach ($paymentModeEnum as $id => $modeName) : ?>
                                    <option value="<?= $id ?>" <?= $id == get_option('flashpay_payment_mode_default') ? 'selected="selected"' : ''; ?>><?= $modeName ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block"></p>
                        </div>
                    </div>

                    <div class="row padding-bottom">
                        <div class="col-xs-6 col-md-3 qa-delivery-subject">
                            <label for="flashpay_shipping_payment_subject_default"><?= __('Предмет расчёта для доставки', 'flashpay'); ?></label>
                            <select id="flashpay_shipping_payment_subject_default" name="flashpay_shipping_payment_subject_default" class="form-control">
                                <?php foreach ($paymentSubjectEnum as $id => $subjectName) : ?>
                                    <option value="<?= $id ?>" <?= $id == get_option('flashpay_shipping_payment_subject_default') ? 'selected="selected"' : ''; ?>><?= $subjectName ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block"></p>
                        </div>
                        <div class="col-xs-6 col-md-3 qa-delivery-method">
                            <label for="flashpay_shipping_payment_mode_default"><?= __('Способ расчёта для доставки', 'flashpay'); ?></label>
                            <select id="flashpay_shipping_payment_mode_default" name="flashpay_shipping_payment_mode_default" class="form-control">
                                <?php foreach ($paymentModeEnum as $id => $modeName) : ?>
                                    <option value="<?= $id ?>" <?= $id == get_option('flashpay_shipping_payment_mode_default') ? 'selected="selected"' : ''; ?>><?= $modeName ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block"></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row form-footer">
            <div class="col-md-12">
                <button class="btn btn-default btn-back qa-back-button" data-tab="section2"><?= __('Назад', 'flashpay'); ?></button>
                <button class="btn btn-primary btn-forward qa-forward-button" data-tab="section4"><?= __('Сохранить и продолжить', 'flashpay'); ?></button>
            </div>
        </div>
    </div>
    <input name="form_nonce" type="hidden" value="<?=$flashpayNonce?>" />
</form>
