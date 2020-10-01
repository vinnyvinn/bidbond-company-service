<?php

use App\Company;
use Faker\Generator as Faker;

$postal_ct = 5; // PostalCode::count();
$postal_id = rand(1, $postal_ct);
$items = ["approved", "pending", "rejected"];

$factory->define(Company::class, function (Faker $faker) use ($postal_ct, $items) {

    return [
        'name' => $faker->unique()->company,
        'crp' => str_random(6),
        'email' => $faker->unique()->safeEmail,
        'phone_number' => $faker->e164PhoneNumber,
        'physical_address' => $faker->address,
        'postal_address' => rand(1, 999),
        'postal_code_id' => rand(1, $postal_ct),
        'paid' => random_int(0, 1),
        'approval_status' => $items[array_rand($items)]
    ];
});
