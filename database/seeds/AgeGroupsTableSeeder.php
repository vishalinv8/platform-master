<?php

use Illuminate\Database\Seeder;

class AgeGroupsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('age_groups')->insert([
            [ 'name' => 'any', 'display_name' => 'Any' ],
            [ 'name' => 'adult', 'display_name' => 'Adult' ],
            [ 'name' => 'senior', 'display_name' => 'Senior' ],
            [ 'name' => 'youth', 'display_name' => 'Youth' ],
        ]);
    }
}
