<?php

use App\Services\Premium\Rules\AgeCoefficientRule;
use App\Services\Premium\Rules\GroupDiscountRule;

return [

    'rules' => [
        'age_coefficient' => AgeCoefficientRule::class,
        'group_discount' => GroupDiscountRule::class,
    ],

    'scale' => 2,

];
