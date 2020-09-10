<?php

use Illuminate\Database\Seeder;

class OrganizationPostStatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('organization_post_statuses')->insert([
            [ 'name' => 'unposted', 'display_name' => 'Unposted (Draft Edit)' ],
            [ 'name' => 'admins', 'display_name' => 'Administrators Only' ],
            [ 'name' => 'posters', 'display_name' => 'Posting Members Only' ],
            [ 'name' => 'members', 'display_name' => 'Members Only' ],
            [ 'name' => 'public', 'display_name' => 'Everyone (General Public)' ],
        ]);
    }
}
