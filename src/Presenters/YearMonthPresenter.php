<?php

namespace CleaniqueCoders\RunningNumber\Presenters;

use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class YearMonthPresenter implements Presenter
{
    protected string $separator;

    /**
     * Create a new YearMonthPresenter instance
     *
     * @param  string  $separator  Separator between parts (default: '-')
     */
    public function __construct(string $separator = '-')
    {
        $this->separator = $separator;
    }

    /**
     * Format the running number with year-month prefix
     * Example: INVOICE-2025-11-001
     *
     * @param  string  $type  The type of running number
     * @param  int  $number  The sequence number
     * @return string Formatted running number
     */
    public function format($type, $number): string
    {
        $padding = config('running-number.padding', 3);
        $paddedNumber = str_pad($number, $padding, '0', STR_PAD_LEFT);
        $year = date('Y');
        $month = date('m');

        return implode($this->separator, [
            $type,
            $year,
            $month,
            $paddedNumber,
        ]);
    }
}
