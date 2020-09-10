<?php

use Illuminate\Database\Seeder;

class GendersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('genders')->insert([
            [ 'name' => 'm', 'display_name' => 'Male' ],
            [ 'name' => 'f', 'display_name' => 'Female' ],
            [ 'name' => 'coed', 'display_name' => 'Co-Ed' ],
            [ 'name' => 'none', 'display_name' => 'Unspecified' ]
        ]);
    }
}