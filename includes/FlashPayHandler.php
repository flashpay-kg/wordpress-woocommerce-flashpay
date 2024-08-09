<?php


class FlashPayHandler
{
    const VAT_CODE_1 = 1;

    /**
     * @return bool
     */
    public static function isReceiptEnabled()
    {
        return get_option('flashpay_enable_receipt');
    }

    /**
     * @return bool
     */
    public static function isLegalEntity()
    {
        $taxRatesRelations = get_option('flashpay_tax_rate');
        $defaultTaxRate    = get_option('flashpay_default_tax_rate');

        return ($taxRatesRelations || $defaultTaxRate) && !self::isSelfEmployed();
    }

    /**
     * @return bool
     */
    public static function isSelfEmployed()
    {
        return (bool)get_option('flashpay_self_employed', '0');
    }

    public static function setReceiptIfNeeded($builder, WC_Order $order, $subscribe = false)
    {
        if (!self::isReceiptEnabled()) {
            return;
        }

        if ($order->get_billing_email()) {
            $builder->setReceiptEmail($order->get_billing_email());
        }
        if ($order->get_billing_phone()) {
            $builder->setReceiptPhone(preg_replace('/[^\d]/', '', $order->get_billing_phone()));
        }

        $items = $order->get_items();

        /** @var WC_Order_Item_Product $item */
        foreach ($items as $item) {
            $amount = flashpayOrderHelper::getAmountByCurrency($item->get_total() / $item->get_quantity() + $item->get_total_tax() / $item->get_quantity());
            if ($subscribe && $amount <= 0) {
                $amount = flashpayGateway::MINIMUM_SUBSCRIBE_AMOUNT;
            }

            if (self::isSelfEmployed()) {
                $builder->addReceiptItem(
                    $item['name'],
                    $amount->getValue(),
                    $item->get_quantity(),
                    self::VAT_CODE_1
                );
            }

            if (self::isLegalEntity()) {
                $builder->addReceiptItem(
                    $item['name'],
                    $amount->getValue(),
                    $item->get_quantity(),
                    self::getFpTaxRate($item->get_taxes()),
                    self::getPaymentMode($item),
                    self::getPaymentSubject($item)
                );
            }
        }

        $orderData = $order->get_data();
        $shipping = $orderData['shipping_lines'];

        if (count($shipping)) {
            $shippingData = array_shift($shipping);
            if (self::isSelfEmployed()) {
                $builder->addReceiptShipping(
                    __('Доставка', 'flashpay'),
                    $shippingData['total'],
                    self::VAT_CODE_1
                );
            }

            if (self::isLegalEntity()) {
                $amount = flashpayOrderHelper::getAmountByCurrency($shippingData['total'] + $shippingData['total_tax']);
                $taxes = $shippingData->get_taxes();
                $builder->addReceiptShipping(
                    __('Доставка', 'flashpay'),
                    $amount->getValue(),
                    self::getFpTaxRate($taxes),
                    self::getShippingPaymentMode(),
                    self::getShippingPaymentSubject()
                );
            }
        }

        if (self::isLegalEntity()) {
            $defaultTaxSystemCode = get_option('flashpay_default_tax_system_code');
            if (!empty($defaultTaxSystemCode)) {
                $builder->setTaxSystemCode($defaultTaxSystemCode);
            }
        }
    }

    /**
     * @param WC_Order $order
     * @param PaymentInterface $payment
     */
    public static function updateOrderStatus(WC_Order $order, PaymentInterface $payment)
    {
        switch ($payment->getStatus()) {
            case FlashPayPaymentStatus::SUCCESS:
                self::completeOrder($order, $payment);
                break;
            case FlashPayPaymentStatus::DECLINE:
                self::cancelOrder($order, $payment);
                break;
            default:
                self::pendingOrder($order, $payment);
                break;
        }
        self::logOrderStatus($order->get_status());
    }

    /**
     * @param Client $apiClient
     * @param WC_Order $order
     * @param PaymentInterface $payment
     *
     * @return PaymentInterface|\flashpay\Request\Payments\Payment\CreateCaptureResponse
     * @throws Exception
     * @throws \flashpay\Common\Exceptions\ApiException
     * @throws \flashpay\Common\Exceptions\BadApiRequestException
     * @throws \flashpay\Common\Exceptions\ForbiddenException
     * @throws \flashpay\Common\Exceptions\InternalServerError
     * @throws \flashpay\Common\Exceptions\NotFoundException
     * @throws \flashpay\Common\Exceptions\ResponseProcessingException
     * @throws \flashpay\Common\Exceptions\TooManyRequestsException
     * @throws \flashpay\Common\Exceptions\UnauthorizedException
     */
    public static function capturePayment(Client $apiClient, WC_Order $order, PaymentInterface $payment)
    {
        $builder = CreateCaptureRequest::builder();
        $builder->setAmount(flashpayOrderHelper::getTotal($order));
        self::setReceiptIfNeeded($builder, $order);
        $captureRequest = $builder->build();

        $payment = $apiClient->capturePayment($captureRequest, $payment->getId());

        return $payment;
    }

    /**
     * @param WC_Order $order
     * @param PaymentInterface $payment
     */
    public static function completeOrder(WC_Order $order, PaymentInterface $payment)
    {
        $message = '';
        if ($payment->getPaymentMethod()->getType() == PaymentMethodType::B2B_SBERBANK) {
            $payerBankDetails = $payment->getPaymentMethod()->getPayerBankDetails();

            $fields  = array(
                'fullName'   => 'Полное наименование организации',
                'shortName'  => 'Сокращенное наименование организации',
                'adress'     => 'Адрес организации',
                'inn'        => 'ИНН организации',
                'kpp'        => 'КПП организации',
                'bankName'   => 'Наименование банка организации',
                'bankBranch' => 'Отделение банка организации',
                'bankBik'    => 'БИК банка организации',
                'account'    => 'Номер счета организации',
            );
            $message = '';

            foreach ($fields as $field => $caption) {
                if (isset($requestData[$field])) {
                    $message .= $caption . ': ' . $payerBankDetails->offsetGet($field) . '\n';
                }
            }
        }
        flashpayLogger::info(
            sprintf(__('Успешный платеж. Id заказа - %1$s. Данные платежа - %2$s.', 'flashpay'),
                $order->get_id(), json_encode($payment))
        );
        $order->payment_complete($payment->getId());
        $order->add_order_note(sprintf(
                __('Номер транзакции в ЮKassa: %1$s. Сумма: %2$s', 'flashpay' . $message
                ), $payment->getId(), $payment->getAmount()->getValue())
        );
    }

    /**
     * @param WC_Order $order
     * @param PaymentInterface $payment
     */
    public static function competeSubscribe(WC_Order $order, PaymentInterface $payment)
    {
        flashpayLogger::info(
            sprintf(__('Успешная подписка. Id заказа - %1$s. Данные платежа - %2$s.', 'flashpay'),
                $order->get_id(), json_encode($payment))
        );
        $order->payment_complete($payment->getId());
    }

    /**
     * @param WC_Order $order
     * @param PaymentInterface $payment
     */
    public static function cancelOrder(WC_Order $order, PaymentInterface $payment)
    {
        flashpayLogger::warning(
            sprintf(__('Неуспешный платеж. Id заказа - %1$s. Данные платежа - %2$s.', 'flashpay'),
                $order->get_id(), json_encode($payment))
        );
        $order->update_status(flashpayOrderHelper::WC_STATUS_CANCELLED);
    }

    /**
     * @param WC_Order $order
     * @param PaymentInterface $payment
     */
    public static function pendingOrder(WC_Order $order, PaymentInterface $payment)
    {
        flashpayLogger::warning(
            sprintf(__('Платеж в ожидании оплаты. Id заказа - %1$s. Данные платежа - %2$s.', 'flashpay'),
                $order->get_id(), json_encode($payment))
        );
        $order->update_status(flashpayOrderHelper::WC_STATUS_PENDING);
    }

    /**
     * @param WC_Order $order
     * @param PaymentInterface $payment
     */
    public static function holdOrder(WC_Order $order, PaymentInterface $payment)
    {
        flashpayLogger::warning(
            sprintf(__('Платеж ждет подтверждения. Id заказа - %1$s. Данные платежа - %2$s.', 'flashpay'),
                $order->get_id(), json_encode($payment))
        );
        $order->update_status(flashpayOrderHelper::WC_STATUS_ON_HOLD);
        $order->add_order_note(sprintf(
                __('Поступил новый платёж. Он ожидает подтверждения до %1$s, после чего автоматически отменится',
                    'flashpay'
                ), $payment->getExpiresAt()->format('d.m.Y H:i'))
        );
    }

    /**
     * @param string $status
     */
    public static function logOrderStatus($status)
    {
        FlashPayLogger::info(sprintf(__('Статус заказа. %1$s', 'flashpay'), $status));
    }

    /**
     * @param WC_Order $order
     * @return void
     * @throws Exception
     */
    public static function checkConditionForSelfEmployed(WC_Order $order)
    {
        $items = $order->get_items(['shipping', 'line_item']);

        foreach ($items as $item) {
            if (!is_int($item->get_quantity())) {
                throw new Exception(
                    __('<b>Нельзя добавить позицию с дробным количеством </b><br>Только с целым. Свяжитесь с магазином, чтобы исправили значение и помогли сделать заказ.', 'flashpay')
                );
            }
        }
    }

    /**
     * @param $taxes
     *
     * @return int
     */
    private static function getFpTaxRate($taxes)
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
     * @return mixed
     * @throws Exception
     */
    private static function getShippingPaymentMode()
    {
        $paymentModeValue = get_option('flashpay_shipping_payment_mode_default');
        self::checkValidModeOrSubject($paymentModeValue, true);
        return $paymentModeValue;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    private static function getShippingPaymentSubject()
    {
        $paymentSubjectValue = get_option('flashpay_shipping_payment_subject_default');
        self::checkValidModeOrSubject($paymentSubjectValue, true);
        return $paymentSubjectValue;
    }

    /**
     * @param WC_Order_Item_Product $item
     * @return mixed
     * @throws Exception
     */
    private static function getPaymentMode($item)
    {
        if ($product = $item->get_product()) {
            $paymentModeValue = $product->get_attribute('pa_flashpay_payment_mode');
        }

        if (empty($paymentModeValue)) {
            $paymentModeValue = get_option('flashpay_payment_mode_default');
        }
        self::checkValidModeOrSubject($paymentModeValue);

        return $paymentModeValue;
    }

    /**
     * @param WC_Order_Item_Product $item
     * @return mixed
     * @throws Exception
     */
    private static function getPaymentSubject($item)
    {
        if ($product = $item->get_product()) {
            $paymentSubjectValue = $product->get_attribute('pa_flashpay_payment_subject');
        }

        if (empty($paymentSubjectValue)) {
            $paymentSubjectValue = get_option('flashpay_payment_subject_default');
        }
        self::checkValidModeOrSubject($paymentSubjectValue);

        return $paymentSubjectValue;
    }

    /**
     * @param $value
     * @param bool $isShipping
     * @throws Exception
     */
    private static function checkValidModeOrSubject($value, $isShipping = false)
    {
        if (!empty($value)) {
            return;
        }

        $errorMessage = 'Оплата временно не работает — ошибка на сайте. Пожалуйста, сообщите в техподдержку: «Не установлены признаки предмета или способа расчёта»';
        if ($isShipping) {
            $errorMessage = 'Оплата временно не работает — ошибка на сайте. Пожалуйста, сообщите в техподдержку: «Не установлены признаки предмета или способа расчёта для доставки»';
        }

        throw new Exception(
            __($errorMessage, 'flashpay')
        );
    }

}
