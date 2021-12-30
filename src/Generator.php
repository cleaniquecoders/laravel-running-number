<?php 

namespace CleaniqueCoders\RunningNumber;

use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;
use CleaniqueCoders\RunningNumber\Contracts\Presenter;
use CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException;
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

class Generator implements GeneratorContract {

    protected $toUpperCase = true;

    public function __construct()
    {
        $presenter = config('running-number.presenter');
        $this->presenter = new $presenter();
    }

    public static function make(): GeneratorContract
    {
        return new self();
    }

    public function formatter(Presenter $presenter): GeneratorContract
    {
        $this->presenter = $presenter;

        return $this;
    }

    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    // Casting prefix will be good.
    public function toUpperCase($value)
    {
        $this->toUpperCase = $value;

        return $this;
    }

    public function generate(): string
    {
        if (! in_array($this->type, config('running-number.types'))) {
            throw new InvalidRunningNumberTypeException('Unsupported ' . $this->type);
        }

        $this->createRunningNumberTypeIfNotExists();

        $running_number = config('running-number.model')::where('type', $this->getType())->first();
        $running_number->increment('number');
        $running_number->save();
        $running_number->refresh();

        return $this->presenter->format($this->getType(), $running_number->number);
    }

    private function getType()
    {
        return $this->toUpperCase ? strtoupper($this->type) : $this->type;
    }

    private function createRunningNumberTypeIfNotExists()
    {
        if(! config('running-number.model')::where('type', $this->getType())->exists()) {
            config('running-number.model')::create(['type' => $this->getType()]);
        }
    }
}