<?php

use Faker\Generator as Faker;

//use Laravolt\Avatar\Avatar;
use Laravolt\Avatar\Facade as Avatar;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Utils;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\User::class, function (Faker $faker) {


    // Create a default avatar locally, and save the relative URL.
    // https://10.0.3.232/storage/avatars/fbdb37e6-4040-44fe-aa1b-7d838742dcea.png
    $avatar_path = Utils::newFileUUID("public/avatars/", ".png");

    // Create the default image URL:
    $name = $faker->name;
    $avatar = Avatar::create($name);

    // FIXME: config/laravolt/avatar.php is not being read. Figure out why.
    // Workaround is to configure it dynamically:
    $avatar->setBorder(1, 'background');
    //$avatar->setShape('square');

    // Save it using Storage instead of Avatar::save()
    $image = $avatar->getImageObject();
    Storage::put($avatar_path, $image->stream('png'));

    // FIXME: This is not called via HTTP, so there is no IP address to use.
    // Just hard-code the current Azure instance for now. Later we could use
    // a .env option, or other logic.
    $server_ip = "40.78.81.126";
    $avatar_url = "https://" .$server_ip. "/storage/" . str_replace("public/", '', $avatar_path);

    return [
        'name' => $name,
        'nickname' => $faker->userName,
        'email' => $faker->unique()->safeEmail,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'avatar_url' => $avatar_url,
        'remember_token' => str_random(10),
    ];
});
