<?php


class FlashPayPaymentChargeDispatcher
{
    public function __construct()
    {

    }

    /**
     * @param string $paymentId
     * @throws Exception
     */
    public function tryChargePayment($paymentId)
    {

    }

    /**
     * @param $id
     *
     * @return null|WC_Order
     */
    private function getOrderIdByPayment($id)
    {
        global $wpdb;

        $query  = "
            SELECT *
            FROM {$wpdb->prefix}postmeta
            WHERE meta_value = %s AND meta_key = '_transaction_id'
        ";
        $sql    = $wpdb->prepare($query, $id);
        $result = $wpdb->get_row($sql);

        if ($result) {
            $orderId = $result->post_id;
            $order   = new WC_Order($orderId);

            return $order;
        } else {
            $query  = "
                SELECT *
                FROM {$wpdb->prefix}flashpay_payment
                WHERE payment_id = %s
            ";
            $sql    = $wpdb->prepare($query, $id);
            $result = $wpdb->get_row($sql);

            if ($result) {
                $orderId = $result->order_id;
                $order   = new WC_Order($orderId);

                return $order;
            }
        }

        return null;
    }
}
