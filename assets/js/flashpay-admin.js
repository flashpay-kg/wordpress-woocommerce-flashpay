(function ($) {
    'use strict';

    function activeTab(tab) {
        $('.flashpay-tab .nav-tabs a[href="#' + tab + '"]').tab('show');
    }
    function settingsModeChange () {
        let $mode = $('#shop-mode');
        $('.tooltip-text').hide();
        $('#tooltip-' + $mode.val()).show();
        $('.nav-tabs > li').hide();
    }

    function showTooltip(elem, msg, autohide) {
        $(elem).tooltip({trigger: 'manual', title: msg}).tooltip('show');
        if (autohide !== undefined) {
            setTimeout(function () {
                $(elem).tooltip('hide');
            }, autohide);
        }
    }

    function buttonToggle () {
        $(document).on('click', '.btn-toggle', function() {
            $(this).find('.btn').toggleClass('active');
            if ($(this).find('.btn-primary').size()>0) {
                $(this).find('.btn').toggleClass('btn-primary');
            }
            if ($(this).find('.btn-danger').size()>0) {
                $(this).find('.btn').toggleClass('btn-danger');
            }
            if ($(this).find('.btn-success').size()>0) {
                $(this).find('.btn').toggleClass('btn-success');
            }
            if ($(this).find('.btn-info').size()>0) {
                $(this).find('.btn').toggleClass('btn-info');
            }
            $(this).find('.btn').toggleClass('btn-default');
        });
    }

    function getAllFormInputs(form) {
        let fields = [];
        form.find('input,select,textarea').each(function (index, elm) {
            if (elm.name) {
                fields.push(elm.name.replace(/\[(.+)\]/g,''));
            }
        });
        return $.unique(fields);
    }

    buttonToggle();
    let clip = new ClipboardJS('button.copy-button');
    clip.on('success', function (e) {
        showTooltip(e.trigger, $(e.trigger).data('success'), 1000);
    });
    clip.on('error', function (e) {
        showTooltip(e.trigger, $(e.trigger).data('error'), 1000);
    });
    $(document).on('click', 'button.copy-button', function(e) { e.preventDefault(); });
    $(document).on('click', 'button.btn-forward', function(e) {
        e.preventDefault();
        let self = $(this).prop('disabled', true);
        if (self.hasClass('skip-send')) {
            activeTab(self.data('tab'));
            return;
        }
        let post = self.closest('.flashpay-form').serializeArray();
        let fields = getAllFormInputs(self.closest('.flashpay-form'));
        post.push({name: 'page_options', value: fields.join(',')});
        $.post(ajaxurl + '?action=flashpay_save_settings', post, function (res) {
            if (res.status === 'success') {
                activeTab(self.data('tab'));
            } else {
                console.log(res);
                self.prop('disabled', false);
            }
        });
    });
    $(document).on('click', 'button.btn-back', function(e) {
        e.preventDefault();
        activeTab($(this).data('tab'));
    });
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        let target = $(e.target).attr("href");
        $(target).html('<div class="text-center offset-md-1"><div class="loader"></div></div>');
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'flashpay_get_tab',
                tab: target.replace('#', '')
            },
            method : 'GET',
            success : function (data) {
                $(target).html(data);
                $(target).find('[data-toggle="tooltip"]').tooltip();
                $(target).find('#flashpay_pay_mode').on('change', function(e){
                    $('.pay-mode-block').hide();
                    $('#pay-mode-' + $(this).val()).show();
                    const saveCardBlock = $('div#save-card');
                    $(this).val() == '0' ? saveCardBlock.show() : saveCardBlock.hide();
                });
            },
            error : function(error){ console.log(error) }
        });
    });
    $('a[data-toggle="tab"].active').trigger('shown.bs.tab');

    function getRadioValue(elements) {
        for (let i = 0; i < elements.length; ++i) {
            if (elements[i].checked) {
                return elements[i].value;
            }
        }
        return elements.length ? elements[0].value : null;
    }

    function triggerPaymentMode(value) {
        if (value == '0') {
            $('.selectPayShop').slideUp();
            $('.selectPayKassa').slideDown();
        } else {
            $('.selectPayShop').slideDown();
            $('.selectPayKassa').slideUp();
        }
    }

    let paymentMode = $('input[name=flashpay_pay_mode]');
    paymentMode.change(function () {
        triggerPaymentMode(this.value);
    });
    triggerPaymentMode(getRadioValue(paymentMode));

    let flashpayNpsClose = $('.flashpay_nps_close');
    function flashpay_nps_close() {
        $.post(flashpayNpsClose.data('link'), {action: 'vote_nps'})
            .done(function () {
                $('.flashpay_nps_block').slideUp();
            });
    }

    function flashpay_nps_goto() {
        window.open('https://yandex.ru/poll/5f1ioMjEgV4Ha3DixySw3f');
        flashpay_nps_close();
    }

    $('.flashpay_nps_link').on('click', flashpay_nps_goto);
    flashpayNpsClose.on('click', flashpay_nps_close);

    /**
     * Переключение radio button между СМЗ и ИП
     */
    $(document).on('click', '.custom-switch-radio label', function() {
        $('div.content').hide().filter('.'+$(this).data('target')).show();
    });

})(jQuery);
