<?php

use App\PostalCode;
use Faker\Generator as Faker;

$factory->define(PostalCode::class, function (Faker $faker) {
    return [
        'code' => random_int(001, 101),
        'name' => $faker->city,
        'constituency' => $faker->city,
        'county' => $faker->city
    ];
});
