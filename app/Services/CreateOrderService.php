<?php

declare(strict_types=1);

namespace App\Services;
use Automattic\WooCommerce\Client;
use App\Services\CouponService;
use App\Exceptions\BotException;

/**
 * Class CreateOrderService
 *
 * This class is responsible for creating orders in WooCommerce.
 * It handles the creation of orders, setting payment methods, and applying coupon codes.
 */
class CreateOrderService
{
    /**
     * @var Client
     */
    private Client $woocommerce;

    /**
     * @var CouponService|null
     */
    private ?CouponService $couponCode = null;

    public function __construct(Client $woocommerce)
    {
        $this->woocommerce = $woocommerce;
    }

    /**
     * Create an order in WooCommerce
     *
     * @param array $productData
     * @return object|null
     */
    public function createOrder(array $productData): ?object
    { 
        $lineItems = $this->getProductBySKU($productData);
        if (is_null($lineItems)) {
            return null;
        }
        $address = [
            'first_name' => 'Order from the Telegram Bot',
        ];
        $orderData = [
            'payment_method'       => 'stripe',
            'payment_method_title' => 'Credit Card',
            'set_paid'             => false,
            'billing'              => $address,
            'line_items'           => $lineItems,
        ];
        $couponPercent = $productData['discount']['percent'] ?? null;
        if (!is_null($couponPercent)) {
            $coupon = str_replace('%', '', $couponPercent);
            $orderData['coupon_lines'] = [
                ['code' => 'tgbot_yonka_' . $coupon],
            ];
        }
        $order = $this->woocommerce->post('orders', $orderData);

        if (!empty($order->id) && !empty($productData['payment_method'])) {
            $this->setPaymentMethod($order->id, $productData['payment_method']);
        }
        return $order;
    }

    /**
     * Get the product by SKU
     *
     * @param array $productData
     * @return array|null
     */
    public function getProductBySKU(array $productData): ?array
    {
        $lineItems = [];
        foreach ($productData['products'] as $product) {
            $response = $this->woocommerce->get('products', ['sku' => $product['sku']]);
            if (!empty($response) && isset($response[0]->id)) {
                $lineItems[] = [
                    'product_id' => $response[0]->id,
                    'quantity'   => $product['count'],
                ];
            }
        }
        if (empty($lineItems)) {
            throw new BotException('Failed to find products by the SKUs transferred');
        }
        return $lineItems;
    }

    /**
     * Set the payment method for an order
     *
     * @param int    $orderId
     * @param string $paymentMethod
     * @return object
     */
    public function setPaymentMethod(int $orderId, string $paymentMethod): object
    {
        return $this->woocommerce->put("orders/{$orderId}", [
            'meta_data' => [
                [
                    'key'   => 'payment_method',
                    'value' => $paymentMethod,
                ],
            ],
        ]);
    }

    /**
     * Update the order with a coupon code
     *
     * @param int    $orderID
     * @param array  $coupon_lines
     * @param string $discount
     * @return object
     */
    public function updateOrder(int $orderID, array $coupon_lines, string $discount): object
    {
        if (empty($orderID) || empty($discount)) {
            throw new BotException('Empty order ID or discount');
        }
        $couponCode = $this->getCouponService($this->woocommerce)->applyCoupon($discount);
        if (empty($couponCode)) {
            throw new BotException('Failed to process coupon');
        }
        $coupons = [];
        foreach ($coupon_lines as $coupon) {
            $coupons[] = [
                'code' => $coupon->code,
            ];
        }
        $coupons[] = ['code' => $couponCode];
        return $this->woocommerce->put("orders/{$orderID}", [
            'coupon_lines' => $coupons,
        ]);
    }

    /**
     * Get the order data
     *
     * @return CouponService
    */
    private function getCouponService(Client $woocommerce): CouponService
    {
        return $this->couponCode ??= new CouponService($woocommerce);
    }
}