<?php

use Illuminate\Database\Seeder;

class ActivityTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('activity_types')->insert([
            [ 'name' => 'basketball', 'display_name' => 'Basketball' ],
            [ 'name' => 'bike', 'display_name' => 'Bike' ],
            [ 'name' => 'bowl', 'display_name' => 'Bowl' ],
            [ 'name' => 'camping', 'display_name' => 'Camping' ],
            [ 'name' => 'crossfit', 'display_name' => 'Crossfit' ],
            [ 'name' => 'dance', 'display_name' => 'Dance' ],
            [ 'name' => 'disc', 'display_name' => 'Disc' ],
            [ 'name' => 'dog_walk', 'display_name' => 'Dog Walk' ],
            [ 'name' => 'football', 'display_name' => 'Football' ],
            [ 'name' => 'golf', 'display_name' => 'Golf' ],
            [ 'name' => 'kickball', 'display_name' => 'Kickball' ],
            [ 'name' => 'pilates', 'display_name' => 'Pilates' ],
            [ 'name' => 'pingpong', 'display_name' => 'Pingpong' ],
            [ 'name' => 'run', 'display_name' => 'Run' ],
            [ 'name' => 'skate', 'display_name' => 'Skate' ],
            [ 'name' => 'snow', 'display_name' => 'Snow' ],
            [ 'name' => 'soccer', 'display_name' => 'Soccer' ],
            [ 'name' => 'softball', 'display_name' => 'Softball' ],
            [ 'name' => 'surf', 'display_name' => 'Surf' ],
            [ 'name' => 'swim', 'display_name' => 'Swim' ],
            [ 'name' => 'tennis', 'display_name' => 'Tennis' ],
            [ 'name' => 'volleyball', 'display_name' => 'Volleyball' ],
            [ 'name' => 'yoga', 'display_name' => 'Yoga' ],
            [ 'name' => 'other', 'display_name' => 'Other' ],
        ]);
    }
}
