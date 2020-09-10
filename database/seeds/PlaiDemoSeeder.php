<?php

use Illuminate\Database\Seeder;

class PlaiDemoSeeder extends Seeder
{
    /**OrganizationFactory.php
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $this->call([
            UsersWithProfileSeeder::class,
            EventsTableSeeder::class,
            OrganizationsTableSeeder::class,
        ]);
    }
}
