<?php

namespace Source\Models;

class ServiceReport
{
    private string $serviceName;
    private string $carBrand;
    private string $carNumber;
    private string $carClass; // Добавляем поле для класса
    private string $saleDate;
    private string $paymentMethod;
    private float $total;
    private float $card;
    private float $cash;
    private float $markup; // Добавляем поле наценки

    public function __construct(
        string $serviceName,
        string $carBrand,
        string $carNumber,
        string $carClass, // Добавляем параметр
        string $saleDate,
        string $paymentMethod,
        float $total,
        float $card,
        float $cash,
        float $markup = 0.0 // Добавляем параметр наценки
    ) {
        $this->serviceName = $serviceName;
        $this->carBrand = $carBrand;
        $this->carNumber = $carNumber;
        $this->carClass = $carClass; // Инициализируем
        $this->saleDate = $saleDate;
        $this->paymentMethod = $paymentMethod;
        $this->total = $total;
        $this->card = $card;
        $this->cash = $cash;
        $this->markup = $markup; // Инициализируем наценку
    }

    public function serviceName(): string
    {
        return $this->serviceName;
    }

    public function carBrand(): string
    {
        return $this->carBrand;
    }

    public function carNumber(): string
    {
        return $this->carNumber;
    }

    public function carClass(): string // Добавляем метод
    {
        return $this->carClass;
    }

    public function saleDate(): string
    {
        return $this->saleDate;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function total(): float
    {
        return $this->total;
    }

    public function card(): float
    {
        return $this->card;
    }

    public function cash(): float
    {
        return $this->cash;
    }

    public function markup(): float
    {
        return $this->markup;
    }
}