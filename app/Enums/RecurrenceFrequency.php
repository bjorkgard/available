<?php

namespace App\Enums;

enum RecurrenceFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /**
     * Get the display label for the frequency.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
