<?php

namespace CleaniqueCoders\RunningNumber;

use CleaniqueCoders\RunningNumber\Contracts\Presenter as Contract;

/**
 * Default presenter for running numbers
 *
 * Formats running numbers as: TYPE + PADDED_NUMBER
 * Example: INVOICE001, ORDER0042
 */
class Presenter implements Contract
{
    /**
     * Format the running number
     *
     * @param  string  $type  The running number type/prefix
     * @param  int  $number  The sequence number
     * @return string The formatted running number (e.g., "INVOICE001")
     */
    public function format(string $type, int $number): string
    {
        $padding = (int) config('running-number.padding', 3);

        return $type.str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
    }
}
