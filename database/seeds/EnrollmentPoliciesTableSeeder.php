<?php

use Illuminate\Database\Seeder;

class EnrollmentPoliciesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('enrollment_policies')->insert([
            [ 'name' => 'closed', 'display_name' => 'Closed: Members can only be added by the owner, or another administrator'],
            [ 'name' => 'open_for_members', 'display_name' => 'Open Enrollment: Anyone can join and become a member'],
            [ 'name' => 'open_for_posters', 'display_name' => 'Public Forum: Anyone can join and post content to members'],
        ]);
    }
}
