<?php 

namespace CleaniqueCoders\RunningNumber;

use CleaniqueCoders\RunningNumber\Contracts\Presenter as Contract;

class Presenter implements Contract {
    public function format($type, $number): string
    {
        return $type.str_pad($number, config('running-number.padding'), '0', STR_PAD_LEFT);;
    }
}