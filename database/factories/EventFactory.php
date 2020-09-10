<?php

use Faker\Generator as Faker;
use App\User;
use App\Location;

$factory->define(App\Event::class, function (Faker $faker) {

        $titles = [
            'Pickup Game',
            'Quick Tennis Match',
            'Tennis, anyone?',
            'Easy dog walk on the beach',
            'Hill training',
            'Water aerobics',
            'Indoor Soccer',
            'Skating lessons',
            'Bowling for kids',
            'Yoga',
            'Yoga class Lvl 2',
            'P90X3 Week 4',
            'Family Biking',
            'Explore the locks by bike',
            'Limebike city tour. Bring a helmet!',
            'Crossfit for Beginners',
            'Hot Yoga',
            'Watershed Tour, 1 mile boardwalk',
            'Group Weight Training',
            'Wrestling Lessons',
            'Karate Lessons',
            'Sport boxing, intermediate',
            'Swimming Laps',
            'Water Polo',
            'Scuba Class',
            'Basketball',
            'Half-court',
            'Street Hockey at the Park'
        ];
        $title = $titles[ array_rand($titles) ];
    
    $start_datetime = $faker->dateTimeThisYear;

    // Note that add() weirdly modifies the existing object, instead of
    // returning a modified object. So we need to make a clone of start_datetime.
    $end_datetime = clone $start_datetime;
    
    // Choose a random duration as DateInterval string $interval_spec
    // Weighted so PT1H is more likely... 1 hour seems most realistic
    $durations =
    [
        "PT15M",
        "PT30M",
        "PT45M",
        "PT1H",
        "PT1H",
        "PT1H",
        "PT2H",
        "PT3H",
        "PT4H",
    ];
    $duration = $durations[ array_rand($durations) ];
    
    $end_datetime->add(new DateInterval($duration));
            
    //
    // We have some non-NULL, foreign key (FK) relationships in the database. 
    // This requires us to have valid FK values, so we create at least one
    // entry if none exist.
    //

    // Create a user_id if none exists
    if ( !App\User::first()->id ) {
        factory(App\User::class)->create();
    }

    // weight the visibility type toward public (id: 3):
    $post_status_ids = [ 1, 2, 3, 3, 3, 3 ];
    $post_status_id = $post_status_ids[ array_rand($post_status_ids) ];
        
    $organization_post_status_ids = [ 1, 2, 3, 3 ,3, 3, 4, 5 ];
    $organization_post_status_id = $organization_post_status_ids[ array_rand($organization_post_status_ids) ];

    return [
        //
        'title' => $title,
        'description' => $faker->realText,

        'start_datetime' => $start_datetime,
        'end_datetime' => $end_datetime,

        'user_id' => App\User::all(['id'])->random(),
        'location_id' => factory(App\Location::class)->create()->id,

        'url' => $faker->url,
        'phone' => $faker->phoneNumber,
        'event_email' => $faker->safeEmail,

        'twitter' => $faker->url,
        'instagram' => $faker->url,
        'facebook' => $faker->url,

        'image_url' => $faker->url,
        'video_url' => $faker->url,

        'desired_user_going_count' => rand( 2 , 12 ),

        'gender_id' => rand(1,3),
        'age_group_id' => rand(1,4),
        'activity_type_id' => rand(1,7),
        'skill_level_id' => rand(1,3),
        'post_status_id' => $post_status_id,
        'organization_post_status_id' => $organization_post_status_id,
    ];
});
