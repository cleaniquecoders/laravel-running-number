<?php 

namespace CleaniqueCoders\RunningNumber\Contracts;

interface Presenter {
    public function format($type, $number): string;
}