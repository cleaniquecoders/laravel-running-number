<?php 

namespace CleaniqueCoders\RunningNumber\Contracts;

interface Generator
{
    public static function make(): Generator;
    public function formatter(Presenter $presenter): Generator;
    public function generate(): string;
}