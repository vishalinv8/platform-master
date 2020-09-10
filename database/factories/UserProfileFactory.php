<?php

use Faker\Generator as Faker;

$factory->define(App\UserProfile::class, function (Faker $faker) {

    // weight the visibility type toward public (id: 3):
    $post_status_ids = [ 1, 2, 3, 3, 3, 3 ];
    $post_status_id = $post_status_ids[ array_rand($post_status_ids) ];

    return [
        //
        'description' => $faker->realText,
        'image_url' => $faker->url,
        'uses_calendar' => array_rand([true, false]),
        'twitter' => $faker->url,
        'instagram' => $faker->url,
        'facebook' => $faker->url,
        'post_status_id' => $post_status_id,
        'birth_date' => $faker->date,

        // Note: Because user_profile needs to know the user.ids, we generater
        // Users for the user table here too.
        'user_id' => factory(App\User::class)->create()->id,

        'location_id' => factory(App\Location::class)->create()->id,
        'gender_id' => rand(1,3),
        'skill_level_id' => rand(1,3),
        'age_group_id' => rand(1,3)
    ];
});
