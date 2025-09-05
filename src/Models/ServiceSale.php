<?php
namespace Source\Models;

class ServiceSale
{
    public function __construct(
        private $id,
        private $sale_date,
        private $client_id,
        private $employee_id,
        private $total_amount,
        private $payment_method,
        private $service_id,
        private $status,
        private $car_id,
        private $cash_amount = null,
        private $non_cash_amount = null,
        private $markup = 0.0
    ) {
    }

    public function id()
    {
        return $this->id;
    }

    public function sale_date()
    {
        return $this->sale_date;
    }

    public function client_id()
    {
        return $this->client_id;
    }

    public function employee_id()
    {
        return $this->employee_id;
    }

    public function total_amount()
    {
        return $this->total_amount;
    }

    public function payment_method()
    {
        return $this->payment_method;
    }

    public function service_id()
    {
        return $this->service_id;
    }

    public function status()
    {
        return $this->status;
    }

    public function car_id()
    {
        return $this->car_id;
    }

    public function cash_amount()
    {
        return $this->cash_amount;
    }

    public function non_cash_amount()
    {
        return $this->non_cash_amount;
    }

    public function markup()
    {
        return $this->markup;
    }
}