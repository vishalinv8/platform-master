<?php

use Illuminate\Database\Seeder;

class UsersWithProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // Note: The UserProfile factory also generates the attached users:
        factory(App\UserProfile::class, 50)->create();

        // 2018-08-31 This is no longer used; instead the user_profiles factory creates users.
/*
        factory(App\User::class, 50)->create()->each(function ($u) {
            $u->profile()->save(factory(App\UserProfile::class)->make());
        });
*/
    }
}
