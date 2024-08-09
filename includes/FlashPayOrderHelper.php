<?php


class FlashPayOrderHelper
{
    const WC_STATUS_COMPLETED = 'wc-completed';
    const WC_STATUS_PENDING = 'wc-pending';
    const WC_STATUS_CANCELLED = 'wc-cancelled';
    const WC_STATUS_ON_HOLD = 'wc-on-hold';
    const WC_STATUS_PROCESSING = 'wc-processing';

    /**
     * @param string $id
     * @return null|WC_Order
     */
    public static function getOrderIdByPayment($id)
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

    /**
     * @param WC_Order $order
     * @return string
     */
    public static function getTotal(WC_Order $order)
    {
        return (version_compare(WOOCOMMERCE_VERSION, "3.0", ">="))
            ? $order_total = (string)$order->get_total()
            : $order_total = number_format($order->order_total, 2, '.', '');
    }
}
