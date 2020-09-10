<?php

use Illuminate\Database\Seeder;

class SkillLevelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('skill_levels')->insert([
            [ 'name' => 'casual', 'display_name' => 'Casual' ],
            [ 'name' => 'intermediate', 'display_name' => 'Intermediate' ],
            [ 'name' => 'advanced', 'display_name' => 'Advanced' ]
        ]);
    }
}
