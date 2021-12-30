<?php

use CleaniqueCoders\RunningNumber\Enums\Organization;

return [
    /*
    |--------------------------------------------------------------------------
    | Running Number Types
    |--------------------------------------------------------------------------
    |
    | These value is the types of the running number you would like to create.
    | The values can be any list of strings. As long listed here, the types 
    | are supported by your application to generate the running number.
    | It is recommended to use Laravel Spatie Enum for these values.
    |
    | Following are the sample values based on organization perspective.
    | You may extend to documents, assets, etc.
    |
    */

    'types' => [
        Organization::organization()->value,
        Organization::division()->value,
        Organization::section()->value,
        Organization::unit()->value,
        Organization::profile()->value,
    ],

    'model' => \CleaniqueCoders\RunningNumber\Models\RunningNumber::class,

    /*
    |--------------------------------------------------------------------------
    | Running Number Generator
    |--------------------------------------------------------------------------
    |
    | Extend this claass to ovewrite the generate() method, to implement your
    | own generator.
    |
    */

    'generator' => \CleaniqueCoders\RunningNumber\Generator::class,

    /*
    |--------------------------------------------------------------------------
    | Running Number Concatenator
    |--------------------------------------------------------------------------
    |
    | This class how you expect the output of the running number after it 
    | was created. The desire format can be C0005, C-0005. 
    |
    */
    
    'presenter' => \CleaniqueCoders\RunningNumber\Presenter::class,

    /*
    |--------------------------------------------------------------------------
    | Running Number Padding Number
    |--------------------------------------------------------------------------
    |
    | This will reflect the generated code, such as 0005 if value set to 3.
    |
    */

    'padding' => 3,
];
