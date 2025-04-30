<?php 

namespace App\Services;

use Automattic\WooCommerce\Client;

/**
 * Class CouponService
 * @package App\Services
 *
 * This class handles the creation and management of coupons in WooCommerce.
 */
class CouponService
{
    /**
     * @var Client
     * WooCommerce client instance.
     */
    private Client $woocommerce;

    public function __construct(Client $woocommerce)
    {
        $this->woocommerce = $woocommerce;
    }

    /**
     * Applies a coupon to the order.
     *
     * @param string $discount The discount percentage.
     * @return string|null The coupon code if successful, null otherwise.
     */
    public function applyCoupon(string $discount): ?string
    {
        $couponCode = $this->generateCouponCode($discount);
        $coupon = $this->getCoupon($couponCode);
        if(is_null($coupon)){
            $this->createCoupon($couponCode, $discount);
        }
        return $couponCode;
    }

    /**
     * Creates a coupon in WooCommerce.
     *
     * @param string $couponCode The coupon code.
     * @param string $discount   The discount percentage.
     * @return void
     */
    public function createCoupon(string $couponCode, string $discount): void
    {
        $this->woocommerce->post('coupons', [
            'code' => $couponCode,
            'discount_type' => 'fixed_cart',
            'amount' => $discount,
        ]);
    }

    /**
     * Retrieves a coupon from WooCommerce.
     *
     * @param string $couponCode The coupon code.
     * @return string|null The coupon code if found, null otherwise.
     */
    public function getCoupon(string $couponCode): ?string
    {
        /** @var array<int, \stdClass> $response */
        $response = $this->woocommerce->get('coupons', ['code' => $couponCode]);
        if (count($response) === 0) {
            return null;
        }
        return $response[0]['code'] ?? null;
    }

    /**
     * Generates a coupon code based on the discount.
     *
     * @param string $discount The discount percentage.
     * @return string The generated coupon code.
     */
    public function generateCouponCode(string $discount): string
    {
        $couponPrefix = 'tgbot_fixed_';
        $couponCode = $couponPrefix.$discount;
        return $couponCode;
    }
}