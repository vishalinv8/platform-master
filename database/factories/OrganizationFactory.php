<?php

use Faker\Generator as Faker;
use App\User;
use App\Location;

$factory->define(App\Organization::class, function (Faker $faker) {

    // Create a user_id if none exists
    if ( !App\User::first()->id ) {
        factory(App\User::class)->create();
    }

    $enrollment_policy_ids = [ 1, 2, 2, 3, 3 ];
    $enrollment_policy_id = $enrollment_policy_ids[ array_rand($enrollment_policy_ids) ];

    return [
        //
        'name' => $faker->company,
        'description' => $faker->bs,
        'url' => $faker->url,
        'phone' => $faker->phoneNumber,
        'organization_email' => $faker->safeEmail,

        'twitter' => $faker->url,
        'instagram' => $faker->url,
        'facebook' => $faker->url,

        'image_url' => $faker->url,
        'video_url' => $faker->url,

        'user_id' => App\User::all(['id'])->random(),
        'location_id' => factory(App\Location::class)->create()->id,

        'enrollment_policy_id' => $enrollment_policy_id,
    ];
});
