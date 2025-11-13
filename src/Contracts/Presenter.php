<?php

namespace CleaniqueCoders\RunningNumber\Contracts;

/**
 * Contract for running number formatters/presenters
 */
interface Presenter
{
    /**
     * Format the running number
     *
     * @param  string  $type  The running number type/prefix
     * @param  int  $number  The sequence number
     * @return string The formatted running number
     */
    public function format(string $type, int $number): string;
}
