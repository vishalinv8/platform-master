<?php

use Illuminate\Database\Seeder;

class PlaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		  $this->call([
		  		EnrollmentPoliciesTableSeeder::class,
		  		ActivityTypesTableSeeder::class,
		  		AgeGroupsTableSeeder::class,
		  		GendersTableSeeder::class,
		  		SkillLevelsTableSeeder::class,
		  		PostStatusesTableSeeder::class,
		  		OrganizationPostStatusesTableSeeder::class,
		  ]);
    }
}
