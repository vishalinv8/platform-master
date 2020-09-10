<?php

use Faker\Generator as Faker;

$factory->define(App\Location::class, function (Faker $faker) {
    return [
      'cross_street' => $faker->streetName . " & " . $faker->streetName,
      'address' => $faker->streetAddress,
      'address2' => array_rand([null, null, null, null, null, null, null, $faker->buildingNumber]),
      'city' => $faker->city,
      'state' => array_rand([$faker->state, $faker->stateAbbr]),
      'country'=> $faker->country,
      'postal_code' => $faker->postcode,
      'cc' =>  $faker->countryCode, // I'm not sure this is 'cc' is "country code" It's from FourSquare.
			'latitude' =>  $faker->latitude,
			'longitude' =>  $faker->longitude,
    ];
});
