<?php

namespace CleaniqueCoders\RunningNumber\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Model;

class RunningNumber extends Model
{
    use InteractsWithUuid;

    protected $guarded = [];
}
