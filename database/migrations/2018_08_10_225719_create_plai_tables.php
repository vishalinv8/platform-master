<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaiTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('cross_street')->nullable();
            $table->string('address')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('cc')->nullable();

						$table->decimal('latitude', 9, 6)->nullable();
						$table->decimal('longitude', 9, 6)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('foursquare_venues', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('foursquare_uuid')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('url')->nullable();

            $table->string('twitter')->nullable();
            $table->string('phone')->nullable();
            $table->string('formatted_phone')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('facebook_username')->nullable();
            $table->string('facebook_name')->nullable();

            $table->string('rating')->nullable();

            $table->string('short_url')->nullable();
            $table->string('time_zone')->nullable();
            $table->string('best_image_url')->nullable();
            $table->string('best_image_width')->nullable();
            $table->string('best_image_height')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('location_foursquare_venue', function (Blueprint $table) {
            $table->unsignedInteger('location_id')->index();
						$table->foreign('location_id')
									->references('id')->on('locations')
									->onDelete('cascade');

            $table->unsignedInteger('foursquare_venue_id')->index();
						$table->foreign('foursquare_venue_id')
									->references('id')->on('foursquare_venues')
									->onDelete('cascade');
        });

        Schema::create('post_statuses', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name')->index();
            $table->string('display_name');

            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('genders', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name')->index();
            $table->string('display_name');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('skill_levels', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name')->index();
            $table->string('display_name');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('age_groups', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name')->index();
            $table->string('display_name');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('uses_calendar')->nullable();

            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();

            $table->date('birth_date')->nullable();

            $table->unsignedInteger('user_id')->index();
						$table->foreign('user_id')
									->references('id')->on('users')
									->onDelete('cascade');

            $table->unsignedInteger('location_id')->index();
						$table->foreign('location_id')
									->references('id')->on('locations')
									->onDelete('cascade');

            $table->unsignedInteger('post_status_id')->index();
						$table->foreign('post_status_id')
									->references('id')->on('post_statuses')
									->onDelete('cascade');

            $table->unsignedInteger('gender_id')->index()->nullable();
						$table->foreign('gender_id')
									->references('id')->on('genders')
									->onDelete('cascade');

            $table->unsignedInteger('skill_level_id')->index()->nullable();
						$table->foreign('skill_level_id')
									->references('id')->on('skill_levels')
									->onDelete('cascade');

            $table->unsignedInteger('age_group_id')->index()->nullable();
						$table->foreign('age_group_id')
									->references('id')->on('age_groups')
									->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_types', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name')->index();
            $table->string('display_name');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_type_user_profiles', function (Blueprint $table) {
            $table->unsignedInteger('activity_type_id')->index();
						$table->foreign('activity_type_id')
									->references('id')->on('activity_types')
									->onDelete('cascade');

            $table->unsignedInteger('user_profile_id')->index();
						$table->foreign('user_profile_id')
									->references('id')->on('user_profiles')
									->onDelete('cascade');
        });

        Schema::create('enrollment_policies', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name')->index();
            $table->string('display_name');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('url')->nullable();
            $table->string('phone')->nullable();
            $table->string('organization_email')->nullable();

            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();

            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();

            $table->unsignedInteger('user_id')->index();
					$table->foreign('user_id')
								->references('id')->on('users')
								->onDelete('cascade');

            $table->unsignedInteger('location_id')->index();
						$table->foreign('location_id')
									->references('id')->on('locations')
									->onDelete('cascade');

            $table->unsignedInteger('enrollment_policy_id')->index();
						$table->foreign('enrollment_policy_id')
									->references('id')->on('enrollment_policies')
									->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('organization_user_admins', function (Blueprint $table) {            
            $table->unsignedInteger('organization_id')->index();
						$table->foreign('organization_id')
									->references('id')->on('organizations')
									->onDelete('cascade');

            $table->unsignedInteger('user_id')->index();
						$table->foreign('user_id')
									->references('id')->on('users')
									->onDelete('cascade');
        });

        Schema::create('organization_user_posters', function (Blueprint $table) {            
            $table->unsignedInteger('organization_id')->index();
						$table->foreign('organization_id')
									->references('id')->on('organizations')
									->onDelete('cascade');

            $table->unsignedInteger('user_id')->index();
						$table->foreign('user_id')
									->references('id')->on('users')
									->onDelete('cascade');
        });

        Schema::create('organization_user_members', function (Blueprint $table) {            
            $table->unsignedInteger('organization_id')->index();
						$table->foreign('organization_id')
									->references('id')->on('organizations')
									->onDelete('cascade');

            $table->unsignedInteger('user_id')->index();
						$table->foreign('user_id')
									->references('id')->on('users')
									->onDelete('cascade');
        });

        Schema::create('location_organization', function (Blueprint $table) {            
            $table->unsignedInteger('location_id')->index();
						$table->foreign('location_id')
									->references('id')->on('locations')
									->onDelete('cascade');

            $table->unsignedInteger('organization_id')->index();
						$table->foreign('organization_id')
									->references('id')->on('organizations')
									->onDelete('cascade');
        });

        Schema::create('organization_post_statuses', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name')->index();
            $table->string('display_name');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('title')->nullable();
            $table->string('description')->nullable();

            $table->string('url')->nullable();
            $table->string('phone')->nullable();
            $table->string('event_email')->nullable();

            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();

            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();

            $table->unsignedInteger('desired_user_going_count')->nullable();;
            
            $table->dateTime('start_datetime')->nullable()->index();
            $table->dateTime('end_datetime')->nullable();

            $table->unsignedInteger('post_status_id')->index();
						$table->foreign('post_status_id')
									->references('id')->on('post_statuses')
									->onDelete('cascade');

            $table->unsignedInteger('organization_id')->nullable()->index();
						$table->foreign('organization_id')
									->references('id')->on('organizations')
									->onDelete('cascade');

            $table->unsignedInteger('organization_post_status_id')->nullable()->index();
						$table->foreign('organization_post_status_id')
									->references('id')->on('organization_post_statuses')
									->onDelete('cascade');

            $table->unsignedInteger('user_id')->index();
						$table->foreign('user_id')
									->references('id')->on('users')
									->onDelete('cascade');

            $table->unsignedInteger('location_id')->index();
						$table->foreign('location_id')
									->references('id')->on('locations')
									->onDelete('cascade');
            
            $table->unsignedInteger('gender_id')->index();
						$table->foreign('gender_id')
									->references('id')->on('genders')
									->onDelete('cascade');

            $table->unsignedInteger('age_group_id')->index();
						$table->foreign('age_group_id')
									->references('id')->on('age_groups')
									->onDelete('cascade');

            $table->unsignedInteger('activity_type_id')->index();
						$table->foreign('activity_type_id')
									->references('id')->on('activity_types')
									->onDelete('cascade');

            $table->unsignedInteger('skill_level_id')->index();
						$table->foreign('skill_level_id')
									->references('id')->on('skill_levels')
									->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_user_going', function (Blueprint $table) {            
            $table->unsignedInteger('event_id')->index();
						$table->foreign('event_id')
									->references('id')->on('events')
									->onDelete('cascade');

            $table->unsignedInteger('user_id')->index();
						$table->foreign('user_id')
									->references('id')->on('users')
									->onDelete('cascade');
        });

        Schema::create('event_user_alerting', function (Blueprint $table) {
            $table->unsignedInteger('event_id')->index();
						$table->foreign('event_id')
									->references('id')->on('events')
									->onDelete('cascade');

            $table->unsignedInteger('user_id')->index();
						$table->foreign('user_id')
									->references('id')->on('users')
									->onDelete('cascade');
        });

        Schema::create('event_organization', function (Blueprint $table) {            
            $table->unsignedInteger('event_id')->index();
						$table->foreign('event_id')
									->references('id')->on('events')
									->onDelete('cascade');

            $table->unsignedInteger('organization_id')->index();
						$table->foreign('organization_id')
									->references('id')->on('organizations')
									->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
				Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('locations');
        Schema::dropIfExists('foursquare_venues');
        Schema::dropIfExists('location_foursquare_venue');        
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('activity_types');
        Schema::dropIfExists('activity_type_user_profiles');
        Schema::dropIfExists('genders');
        Schema::dropIfExists('skill_levels');
        Schema::dropIfExists('age_groups');
        Schema::dropIfExists('post_statuses');
        Schema::dropIfExists('organization_post_statuses');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_user_going');          
        Schema::dropIfExists('event_user_alerting');
        Schema::dropIfExists('enrollment_policies');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('organization_user_admins');        
        Schema::dropIfExists('organization_user_posters');        
        Schema::dropIfExists('organization_user_members');
        Schema::dropIfExists('event_organization');
        Schema::dropIfExists('location_organization');        

				Schema::enableForeignKeyConstraints();
    }
}
