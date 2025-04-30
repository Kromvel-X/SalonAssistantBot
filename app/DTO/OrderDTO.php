<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

class OrderDTO implements JsonSerializable
{
    /**
     * Array of products in the order
     * @var array<int, array{sku: string, count: int}>
     */
    private array $products;

    /**
     * Number of items in the order
     * @var int
     */
    private int $itemNumber;

    /**
     * Array of discounts applied to the order
     * @var array<string, int>
     */
    private array $discount;

    /**
     * Payment method used for the order
     * @var string
     */
    private string $paymentMethod;

    public function __construct()
    {
        $this->products = [];
        $this->itemNumber = 0;
        $this->discount = [];
        $this->paymentMethod = '';
    }

    /**
     * Set the SKU of the product in the order
     *
     * @param string $sku
     * @return void
     */
    public function setSku(string $sku): void
    {
        $this->products[$this->itemNumber]['sku'] = $sku;
    }

    /**
     * Set the count of the product in the order
     *
     * @param int $count
     * @return void
     */
    public function setCount(int $count): void
    {
        $this->products[$this->itemNumber]['count'] = $count;
        $this->itemNumber++;
    }

    /**
     * Set the discount percentage for the order
     *
     * @param int $value
     * @return void
     */
    public function setDiscountPercent(int $value): void
    {
        $this->discount['percent'] = $value;
    }

    /**
     * Set the fixed discount amount for the order
     *
     * @param int $value
     * @return void
     */
    public function setDiscountFixed(int $value): void
    {
        $this->discount['fixed'] = $value;
    }

    /**
     * Set the payment method for the order
     *
     * @param string $method
     * @return void
     */
    public function setPaymentMethod(string $method): void
    {
        $this->paymentMethod = $method;
    }

    /**
     * Get products in the order
     *
     * @return array<int, array{sku: string, count: int}>
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * Get discount information for the order
     *
     * @return array<string, int>
     */
    public function getDiscount(): array
    {
        return $this->discount;
    }

    /**
     * Get the payment method used for the order
     *
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

     /**
     * Transform the order data into an array
     *
     * @return array{products: array<int, array{sku: string, count: int}>, discount: array<string, int>, payment_method: string}
     */
    public function toArray(): array
    {
        return [
            'products' => $this->products,
            'discount' => $this->discount,
            'payment_method' => $this->paymentMethod,
        ];
    }

    /**
     * Serialize the order data to JSON
     *
     * @return array{products: array<int, array{sku: string, count: int}>, discount: array<string, int>, payment_method: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}