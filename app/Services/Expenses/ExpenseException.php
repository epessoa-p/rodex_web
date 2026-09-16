<?php

namespace App\Services\Expenses;

use RuntimeException;

/** Regla de negocio incumplida al registrar un gasto (código para la API). */
class ExpenseException extends RuntimeException
{
    public function __construct(string $message, public readonly string $code_)
    {
        parent::__construct($message);
    }

    /** `no_open_session` | `insufficient_balance` */
    public function businessCode(): string
    {
        return $this->code_;
    }
}
