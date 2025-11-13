<?php

namespace CleaniqueCoders\RunningNumber\Presenters;

use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class DatePrefixPresenter implements Presenter
{
    protected string $dateFormat;

    protected string $separator;

    /**
     * Create a new DatePrefixPresenter instance
     *
     * @param  string  $dateFormat  PHP date format (default: 'Y-m-d')
     * @param  string  $separator  Separator between parts (default: '-')
     */
    public function __construct(string $dateFormat = 'Y-m-d', string $separator = '-')
    {
        $this->dateFormat = $dateFormat;
        $this->separator = $separator;
    }

    /**
     * Format the running number with date prefix
     *
     * @param  string  $type  The type of running number
     * @param  int  $number  The sequence number
     * @return string Formatted running number
     */
    public function format($type, $number): string
    {
        $padding = config('running-number.padding', 3);
        $paddedNumber = str_pad($number, $padding, '0', STR_PAD_LEFT);
        $datePrefix = date($this->dateFormat);

        return implode($this->separator, [
            $type,
            $datePrefix,
            $paddedNumber,
        ]);
    }
}
