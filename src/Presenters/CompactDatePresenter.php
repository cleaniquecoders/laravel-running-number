<?php

namespace CleaniqueCoders\RunningNumber\Presenters;

use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CompactDatePresenter implements Presenter
{
    protected string $dateFormat;

    protected string $separator;

    /**
     * Create a new CompactDatePresenter instance
     *
     * @param  string  $dateFormat  PHP date format (default: 'Ymd' for 20251113)
     * @param  string  $separator  Separator between type and date-number (default: '-')
     */
    public function __construct(string $dateFormat = 'Ymd', string $separator = '-')
    {
        $this->dateFormat = $dateFormat;
        $this->separator = $separator;
    }

    /**
     * Format the running number with compact date
     * Example: INVOICE-20251113001
     *
     * @param  string  $type  The type of running number
     * @param  int  $number  The sequence number
     * @return string Formatted running number
     */
    public function format(string $type, int $number): string
    {
        $padding = (int) config('running-number.padding', 3);
        $paddedNumber = str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
        $datePrefix = date($this->dateFormat);

        return $type.$this->separator.$datePrefix.$paddedNumber;
    }
}
