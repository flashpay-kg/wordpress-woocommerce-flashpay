<?php
/**
 * @var string $shopId
 * @var string $password
 * @var string $flashpayNonce
*/
?>
<div class="col-md-12">
    <div class="row">
        <div class="col-md-6 padding-bottom">
            <h5><?= __('Настройки подключения', 'flashpay') ?></h5>
        </div>
    </div>
</div>
<form id="flashpay-form-1" class="flashpay-form">

    <div class="col-md-12">
        <h6 class="qa-title"><?= __('Режим:', 'flashpay'); ?></h6>

        <div class="row">
            <div class="col-sm-4 col-md-4 col-lg-3 form-group">
                <div class="custom-control custom-switch-radio qa-enable-self-employed-control">
                    <label for="flashpay_is_test_y" data-target="flashpay_is_test_y">
                        <input <?= (!$isTest) ? ' checked' : ''; ?> type="radio" id="flashpay_is_test_y" name="flashpay_is_test" value="0">
                        <span><?= __('Production', 'flashpay'); ?></span>
                    </label>
                    <label for="flashpay_is_test_n" data-target="flashpay_is_test_n">
                        <input <?= ($isTest) ? ' checked' : ''; ?> type="radio" id="flashpay_is_test_n" name="flashpay_is_test" value="1">
                        <span><?= __('Тестирование', 'flashpay'); ?></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <p class="title"><b><?= __('Доступы', 'flashpay'); ?></b></p>
                <div class="form-group">
                    <label for="flashpay_api_url">URL</label>
                    <input type="text" id="flashpay_api_url" name="flashpay_api_url"
                           value="<?php echo $flashPayUrl; ?>" class="form-control"
                           placeholder="<?= __('Заполните поле', 'flashpay'); ?>" />
                    <p class="help-block help-block-error"></p>
                </div>
                <div class="form-group">
                    <label for="flashpay_shop_id">shopId</label>
                    <input type="text" id="flashpay_shop_id" name="flashpay_shop_id"
                           value="<?php echo $shopId; ?>" class="form-control"
                           placeholder="<?= __('Заполните поле', 'flashpay'); ?>" />
                    <p class="help-block help-block-error"></p>
                </div>
                <div class="form-group">
                    <label for="flashpay_shop_password"><?= __('Секретный ключ', 'flashpay') ?></label>
                    <input type="password" id="flashpay_shop_password" name="flashpay_shop_password"
                           value="<?php echo $password ?>" class="form-control"
                           placeholder="<?= __('Заполните поле', 'flashpay'); ?>" />
                    <p class="help-block help-block-error"></p>
                </div>
            </div>
            <div class="col-md-6">
                <p class="title"><b><?= __('Доступы (режим тестирования)', 'flashpay'); ?></b></p>
                <div class="form-group">
                    <label for="flashpay_api_url_test">URL</label>
                    <input type="text" id="flashpay_api_url_test" name="flashpay_api_url_test"
                           value="<?php echo $flashPayUrlTest; ?>" class="form-control"
                           placeholder="<?= __('Заполните поле', 'flashpay'); ?>" />
                    <p class="help-block help-block-error"></p>
                </div>
                <div class="form-group">
                    <label for="flashpay_shop_id_test">shopId</label>
                    <input type="text" id="flashpay_shop_id_test" name="flashpay_shop_id_test"
                           value="<?php echo $shopIdTest ?>" class="form-control"
                           placeholder="<?= __('Заполните поле', 'flashpay'); ?>" />
                    <p class="help-block help-block-error"></p>
                </div>
                <div class="form-group">
                    <label for="flashpay_shop_password_test"><?= __('Секретный ключ', 'flashpay') ?></label>
                    <input type="password" id="flashpay_shop_password_test" name="flashpay_shop_password_test"
                           value="<?php echo $passwordTest ?>" class="form-control"
                           placeholder="<?= __('Заполните поле', 'flashpay'); ?>" />
                    <p class="help-block help-block-error"></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 col-lg-9">
                <div class="row form-footer">
                    <div class="col-md-12">
                        <button class="btn btn-primary btn-forward" data-tab="section2">
                            <?= __('Сохранить и продолжить', 'flashpay'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input name="form_nonce" type="hidden" value="<?=$flashpayNonce?>" />
</form>
<div class="col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="row mt-3 auth-error-alert d-none qa-connect-error-block">
                <div class="col-md-8 col-lg-9">
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong><?= __('Ошибка', 'flashpay'); ?>!</strong>
                        <span><?= __('Пожалуйста, попробуйте перезагрузить страницу', 'flashpay'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
