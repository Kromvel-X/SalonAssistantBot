<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

class OrderDTO implements JsonSerializable
{
    private array $products;
    private int $itemNumber;
    private array $discount;
    private string $paymentMethod;

    public function __construct()
    {
        $this->products = [];
        $this->itemNumber = 0;
        $this->discount = [];
        $this->paymentMethod = '';
    }

    public function setSku(string $sku): void
    {
        $this->products[$this->itemNumber]['sku'] = $sku;
    }

    public function setCount(int $count): void
    {
        $this->products[$this->itemNumber]['count'] = $count;
        $this->itemNumber++;
    }

    public function setDiscountPercent(int $value): void
    {
        $this->discount['percent'] = $value;
    }

    public function setDiscountFixed(int $value): void
    {
        $this->discount['fixed'] = $value;
    }

    public function setPaymentMethod(string $method): void
    {
        $this->paymentMethod = $method;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getDiscount(): array
    {
        return $this->discount;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function toArray(): array
    {
        return [
            'products' => $this->products,
            'discount' => $this->discount,
            'payment_method' => $this->paymentMethod,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}