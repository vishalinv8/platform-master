<?php

use Illuminate\Database\Seeder;

class PostStatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('post_statuses')->insert([
            [ 'name' => 'unposted', 'display_name' => 'Just Me (Draft Edit)' ],
            [ 'name' => 'friends', 'display_name' => 'Friends Only' ],
            [ 'name' => 'public', 'display_name' => 'Everyone (General Public)' ],
        ]);
    }
}
