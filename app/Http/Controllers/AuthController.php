<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


// Examples for comparison:
// https://jwt-auth.readthedocs.io/en/develop/quick-start/
// https://blog.pusher.com/build-rest-api-laravel-api-resources/

// If not use a default guard of 'api':
// https://github.com/tymondesigns/jwt-auth/issues/1404#issuecomment-362944871


// app/Http/Controllers/AuthController.php

// remember to add this to the top of the file
use App\User;
use App\Location;
use App\UserProfile;
use App\PostStatus;
use App\Utils;
use App\Comment;

//use Laravolt\Avatar\Avatar;
use Laravolt\Avatar\Facade as Avatar;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use Socialite;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
      $this->middleware('auth:api', ['except' => ['login', 'register', 'facebook', 'google_ios', 'google_android', 'google_web' ]]);
    }

	public function register(Request $request)
	{


        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4',
        ]);

        // User::create() doesn't work here because 'password' is not fillable.
        $user = new User;
        $user->name = $request->name;
        $user->nickname = $request->nickname;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        
        // Is there an avatar_url included? If so, set it.
        if ($request->has('avatar_url')) {
            // Probably a remotely-hosted Google or Facebook profile avatar URL.
            $user->avatar_url = $request->avatar_url;
        } else {
            // Create a default avatar locally, and save the relative URL.
            // 
            $avatar_path = Utils::newFileUUID("public/avatars/", ".png");
            // Create the default image URL:
            $avatar = Avatar::create($user->name);

            // FIXME: config/laravolt/avatar.php is not being read. Figure out why.
            // Workaround is to configure it dynamically:
            $avatar->setBorder(1, 'background');
            //$avatar->setShape('square');

            // Save it using Storage instead of Avatar::save()
            $image = $avatar->getImageObject();
            Storage::put($avatar_path, $image->stream('png'));
            
            # Set the avatar URL. The storage driver uses a different path than
            # the Apache web server. See also: php artisan storage:link
            #
            # $avatar_path public/avatars/fbdb37e6-4040-44fe-aa1b-7d838742dcea.png
            # $avatar_url  https://10.0.3.232/storage/avatars/fbdb37e6-4040-44fe-aa1b-7d838742dcea.png
            # 
            # Strip the 'public/' prefix, and replace it with 'storage/'
            
            $protocol = "https://";
            if (! $request->isSecure()) {
                $protocol = "http://";  // Must be a development machine. Play nice.
            }
            $hostname = $_SERVER['SERVER_ADDR'];
            $port = $_SERVER['SERVER_PORT'];
            
            if ($port==443 || $port==80) {
                // Just let it default based on the protocol. It looks cleaner.
                $port = "";
            } else {
                $port = ":$port";
            }   

            // Remove the incorrect public/ prefix from the generated filename.
            $avatar_url = $protocol. $request->server('SERVER_NAME') . $port . "/". "storage/" . str_replace("public/", '', $avatar_path);
            $user->avatar_url = $avatar_url;
        }
        $user->save();

        // Create an empty UserProfile (with empty Location) to go with this user:
        $location = new Location;
        $location->save();

        $user_profile = new UserProfile;
        $user_profile->user_id = $user->id;
        $user_profile->location_id = $location->id;
        $user_profile->post_status_id = PostStatus::where('name', '=', 'unposted')->first()->id;

        $user_profile->save();

		$token = auth('api')->login($user);

		return $this->respondWithToken($token);
	}

	public function login(Request $request)
	{
		$credentials = $request->only(['email', 'password']);

		if (!$token = auth('api')->attempt($credentials)) {
		  return response()->json(['error' => 'Unauthorized'], 401);
		}

		return $this->respondWithToken($token);
	}

    //
    // 2019-04-03: Facebook uses a single APP_ID and KEY,
    // even across different platforms.
    // Google Developer Consoler generates a unique key
    // for each platform type. So we have these functions
    // to configure the Socialite driver for "google" with
    // the correct key that will allow us to get user information.
    // The normal way is to just configure it in config/services.php,
    // but that is just a static global config -- we set it on a per-URL basis.
    //
    public function google_ios(Request $request)
    {
        // To dynamically change what APP_ID and KEY are used:
        //config(['services.mailgun' => $arrayWithNewSettings]);
        $new_settings = [
            'client_id'     => env('GOOGLE_IOS_CLIENT_ID'),
            'client_secret' => env('GOOGLE_IOS_CLIENT_SECRET'),
            'redirect'      => env('GOOGLE_IOS_URL'),
        ];
        config(['services.google' => $new_settings]);

        return $this->social_login($request, "google");
    }


    public function google_android(Request $request)
    {
        $new_settings = [
                'client_id'     => env('GOOGLE_ANDROID_CLIENT_ID'),
                'client_secret' => env('GOOGLE_ANDROID_CLIENT_SECRET'),
                'redirect'      => env('GOOGLE_ANDROID_URL'),
        ];
        config(['services.google' => $new_settings]);

        return $this->social_login($request, "google");
    }

    public function google_web(Request $request)
    {
        $new_settings = [
                'client_id'     => env('GOOGLE_WEB_CLIENT_ID'),
                'client_secret' => env('GOOGLE_WEB_CLIENT_SECRET'),
                'redirect'      => env('GOOGLE_WEB_URL'),
        ];
        config(['services.google' => $new_settings]);

        return $this->social_login($request, "google");
    }

    public function facebook(Request $request)
    {
        // The ID and SECRET are always the same for facebook:
        return $this->social_login($request, "facebook");
    }

    /**
    * Get the authenticated User, from a Facebook/Google Auth token.
    *
    * @return \Illuminate\Http\JsonResponse
    */
    public function social_login(Request $request, string $driver_name)
    {
//return "out";
        // The OAuth2 APP_ID and KEY are located in config/services.php
        $social_token = request()->social_token;

        $social_user = Socialite::driver($driver_name)->stateless()->userFromToken($social_token);

        $user = User::where(['email' => $social_user->getEmail()])->first();

        if ($user) {
            // The user was already in the database. Update their info with the
            // the latest upstream info:
            $user->name = $social_user->getName();  // Just name for now.

            $user->save();

        } else {

/* Fails due to guarded:
            $user = User::create([
                'name'          => $social_user->getName(),
                'email'         => $social_user->getEmail(),
                'avatar_url'         => $social_user->getAvatar(),
            ]);
*/
            $user = new User;
            $user->name = $social_user->getName();
            $user->email = $social_user->getEmail();
            $user->avatar_url = $social_user->getAvatar();
            $user->password = $driver_name;
            $user->save();

            // Create an empty UserProfile (with empty Location) to go with this user:
            $location = new Location;
            $location->save();

            $user_profile = new UserProfile;
            $user_profile->user_id = $user->id;
            $user_profile->location_id = $location->id;
            $user_profile->post_status_id = PostStatus::where('name', '=', 'unposted')->first()->id;

            $user_profile->save();
        }

        // This line is from examples online:
        //$token = JWTAuth::fromUser($user);

        // NOTE: This is how it is done for local users like $user, using login()
	    $token = auth('api')->login($user);
        return $this->respondWithToken($token);

        // Or does it make more sense to respond with the full user data?
        //return response()->json(auth('api')->user());
    }

// google_android
// google_ios

  /**
   * Get the authenticated User.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function me()
  {
      return response()->json(auth('api')->user());
  }

  /**
   * Refresh a token.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function refresh()
  {
      return $this->respondWithToken(auth('api')->refresh());
  }


  /**
   * Log the user out (Invalidate the token).
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function logout()
  {
      auth('api')->logout();

      return response()->json(['message' => 'Successfully logged out']);
  }

	protected function respondWithToken($token)
	{
		return response()->json([
		  'access_token' => $token,
		  'token_type' => 'bearer',
		  'expires_in' => auth('api')->factory()->getTTL() * 60
		]);
	}
}
