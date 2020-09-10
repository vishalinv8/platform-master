<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

use Hootlex\Friendships\Status;
use Webpatser\Uuid\Uuid;

use Illuminate\Database\Query\Builder;

use Image;

// PHP doesn't have these built-in.
function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}


// Some jerk made this protected inside the library
function invalidOperator($operator)
{
    $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    return ! in_array(strtolower($operator), $operators, true);
}


class Utils
{
    public static function newFileUUID($path_prefix = null, $file_suffix = null)
    {
        $path = "";

        while (1) {
            // Break out when an non-existing filename UUID is found.
            $uuid = (string) Uuid::generate(4);
            $path = $path_prefix . $uuid . $file_suffix;

            $exists = Storage::exists($path);
            if (!$exists) {
                break;
            }
        }

        return $path;
    }

    public static function whereIsFriendWithUserID($query_builder, $user_id, $table_name, $keyname="user_id")
    {
        $query_builder->where(function ($query) use ($user_id, $table_name, $keyname) {
            $query->whereExists(function ($q) use ($user_id, $table_name, $keyname) {
                // We invited them, or 
                $q->select(DB::raw(1))->from('friendships')
                ->where('sender_id', '=', $user_id)
                ->where('sender_type', '=', "App\User")
                ->whereRaw('`recipient_id` = `'.$table_name.'`.`'.$keyname.'`')
                ->where('recipient_type', '=', "App\User")
                ->where('friendships.status', '=', Status::ACCEPTED);
            })->orWhereExists(function ($q)  use ($user_id, $table_name, $keyname) {
                // they invited us
                $q->select(DB::raw(1))->from('friendships')
                ->whereRaw('`sender_id` = `'.$table_name.'`.`'.$keyname.'`')
                ->where('sender_type', '=', "App\User")
                ->where('recipient_id', '=', $user_id)
                ->where('recipient_type', '=', "App\User")
                ->where('friendships.status', '=', Status::ACCEPTED);
            });
        });

        return $query_builder;
    }


    public static function whereIsAdminOfOrg($query_builder, $user_id, $table_name)
    {
        $query_builder->whereExists(function ($query) use ($user_id, $query_builder, $table_name) {
            //$table_name = $query_builder->getModel()->getTable();

            $query->select(DB::raw(1))->from('organization_user_admins')
            ->where('organization_user_admins.user_id', '=', $user_id)
            ->where('organization_user_admins.organization_id', '=', $table_name.'.`organization_id`');
        });

        return $query_builder;
    }

    public static function whereIsPosterOfOrg($query_builder, $user_id, $table_name)
    {
        $query_builder->whereExists(function ($query) use ($query_builder, $user_id, $table_name) {
            $query->select(DB::raw(1))->from('organization_user_posters')
            ->where('organization_user_posters.user_id', '=', $user_id)
            ->where('organization_user_posters.organization_id', '=', $table_name.'.`organization_id`');
        });

        return $query_builder;
    }

    public static function whereIsMemberOfOrg($query_builder, $user_id, $table_name)
    {
        $query_builder->whereExists(function ($query) use ($user_id, $query_builder, $table_name) {
            //$table_name = $query_builder->getModel()->getTable();

            $query->select(DB::raw(1))->from('organization_user_members')
            ->where('organization_user_members.user_id', '=', $user_id)
            ->where('organization_user_members.organization_id', '=', $table_name.'.`organization_id`');
        });

        return $query_builder;
    }

    public static function whereIsInOrg($query_builder, $user_id, $table_name)
    {
        $query_builder = Utils::whereIsAdminOfOrg($query_builder, $user_id, $table_name);
        $query_builder = Utils::whereIsPosterOfOrg($query_builder, $user_id, $table_name);
        $query_builder = Utils::whereIsMemberOfOrg($query_builder, $user_id, $table_name);

        return $query_builder;
    }


    public static function whereHasUserReadPermission($query_builder, $user_id, $table_name) 
    {
        // $friends_id = PostStatus::where('name', '=', 'friends');
        $friends_id = 2;
        // $public_id = PostStatus::where('name', '=', 'public');
        $public_id = 3;

        $query_builder->where(function ($query) use ($query_builder, $user_id, $table_name, $public_id, $friends_id) {
            // The requestor (request user) must be the owner
            $query->where($table_name.'.user_id', '=', $user_id)->
            // or it must be public
            orWhere($table_name.'.post_status_id', '=', "$public_id")->
            // or it must be visible to friends, and the requestor is a 'friend':
            orWhere(function ($q) use ($user_id, $friends_id, $table_name) {
              $q->where($table_name.'.post_status_id', '=', "$friends_id");
              Utils::whereIsFriendWithUserID($q, $user_id, $table_name);
            });
        });
    }

    public static function whereHasOrgReadPermission($query_builder, $user_id, $table_name) 
    {
        // $admins_id = OrganizationPostStatus::where('name', '=', 'admins');
        $admins_id = 2;
        // $posters_id = OrganizationPostStatus::where('name', '=', 'posters');
        $posters_id = 3;
        // $members_id = OrganizationPostStatus::where('name', '=', 'members');
        $members_id = 4;
        // $public_id = OrganizationPostStatus::where('name', '=', 'public');
        $public_id = 5;

        $query_builder->where(function ($query) use ($public_id, $admins_id, $posters_id, $members_id, $query_builder, $user_id, $table_name) 
        {
            // The requestor (request user) must be the owner
            $query->where($table_name.'.user_id', '=', $user_id)->
            // or it must be public
            orWhere($table_name.'.organization_post_status_id', '=', "$public_id")->
            // or it must be visible to admins, and the requestor is a 'admin':
            orWhere(function ($q) use ($user_id, $admins_id, $table_name) {
              $q->where($table_name.'.organization_post_status_id', '=', "$admins_id");
              Utils::whereIsAdminOfOrg($q, $user_id, $table_name);
            })->
            // or it must be visible to posters, and the requestor is a 'poster':
            orWhere(function ($q) use ($posters_id, $user_id, $table_name) {
              $q->where($table_name.'.organization_post_status_id', '=', "$posters_id");
              Utils::whereIsPosterOfOrg($q, $user_id, $table_name);
            })->
            // or it must be visible to members, and the requestor is an 'member':
            orWhere(function ($q) use ($members_id, $user_id, $table_name) {
              $q->where($table_name.'.organization_post_status_id', '=', "$members_id");
              Utils::whereIsMemberOfOrg($q, $user_id, $table_name);
            });
        });
        
        return $query_builder;
    }

    public static function whereHasReadPermission($query_builder, $user_id, $table_name = null) 
    {
        // Default to using the Model's table name for post_status_id, organization_id, etc.
        if ($table_name == null) {
            $table_name = $query_builder->getModel()->getTable();
        }

        $query_builder->where(function ($query) use ($query_builder, $user_id, $table_name) {

            // Treat a non-existent organization_id (like user_profile) the same as NULL:
            if (Schema::hasColumn($table_name, 'organization_id')) {
                //
                // This table has organization_id. Check if it's NULL:
                //
                $query->where(function ($q) use ($user_id, $table_name) {
                    // If organization_id is not set, use user rules:
                    $q->where($table_name.'.organization_id', '=', null);
                    Utils::whereHasUserReadPermission($q, $user_id, $table_name);
                 })
                 ->orWhere(function ($q) use ($user_id, $table_name) {
                    // If organization_id is set, use organization rules:
                    $q->where($table_name.'.organization_id', '!=', null);
                    Utils::whereHasOrgReadPermission($q, $user_id, $table_name);
                 });

            } else {
                //
                // This table does not have organization_id. Just use user rules:
                //
                $query->where(function ($q) use ($user_id, $table_name) {
                    Utils::whereHasUserReadPermission($q, $user_id, $table_name);
                 });
             }

         });

         return $query_builder;
    }


    public static function hasUpdatePermission($request, $model) 
    {
        /*
            For existing post:

            For existing post with org_id:
            User must be org admin, or the owner

            For existing post without org_id:
            User must be the owner.
        */
        if ($model->organization_id) {
            $organization = App\Organization::findOrFail($model->organization_id)->with('admins');

            if ( $organization->admins()->find($request->user()->id) ) {
                // Organization admins can always edit org posts, regardless of who the owner is.
                return true;
            }
        }

        // It's a personal user post; only the owner can edit it (via this api).
        if ($request->user()->id == $model->user_id) {
          return true;
        }

        // Not an admin, and not the owner.
        return false;
    }

    public static function hasCreatePermission($request)
    {
        // For a new post (create a new entry):
        //
        // Must not be an org (personal user posts are always ok), or:
        //   User must be an admin in the org, or 
        //   user must be a poster in the org.
        if ( ! $request->has('organization_id')  ||  ! $request->input('organization_id') ) { 
            // There is no organization, or is '' or null. Personal user posts are always ok.
            return true; 
        }

        // The user wants to associate an organization.
        // The user must be an admin or poster of the org.
        $organization = Organization::findOrFail($request->input('organization_id'))->with('admins')->with('posters');

        if ( $organization->admins()->find($request->user()->id) || 
             $organization->posters()->find($request->user()->id) ) {
            return true;
        }
        return false;
    }

    public static function uniquely_starts_with($prefix, $word_array)
    {
        $matches = [];

        foreach ($word_array as $word) {
            if (startsWith($word, $prefix)) {
                array_push($matches, $word);
            }
        }
        
        if ( count($matches) == 1 ) {
            // There was exactly one match.
            return $matches[0];
        }
        
        // There were zero, two, or more matches.
        return null;
    }


    public static function requestHasSearchableColumn($request, $columns_to_search)
    {
        foreach ($request->all() as $request_key => $request_value) {
            $completed_column_name = Utils::uniquely_starts_with($request_key, $columns_to_search);
            if ($completed_column_name) {
                return true;
            }
        }
        
        return false;
    }

    public static function whereOperatorSearch($query_builder, $request, $columns_to_search)
    {
        if (! Utils::requestHasSearchableColumn($request, $columns_to_search)) { return $query_builder; }

        // Search the model's own table.
        $query_builder->where(function ($query) use ($query_builder, $request, $columns_to_search) {
            $table_name = $query_builder->getModel()->getTable();
            Utils::whereOperatorSearchTable($query, $request, $columns_to_search, $table_name, 'id', 'id');
        });
        return $query_builder;
    }

    public static function whereOperatorSearchBelongsToTable($query_builder, $request, $columns_to_search, $table_name, $fk_column_name)
    {
        if (! Utils::requestHasSearchableColumn($request, $columns_to_search)) { return $query_builder; }

        $query_builder->where(function ($query) use ($query_builder, $request, $columns_to_search, $table_name, $fk_column_name) {
            // Use the foreign key name on the model's table, matched against 'id' in the searched table.
            // $table_fk = "id", $model_fk = $fk_column_name
            Utils::whereOperatorSearchTable($query, $request, $columns_to_search, $table_name, 'id', $fk_column_name);
        });
        return $query_builder;
    }

    public static function whereOperatorSearchHasOneTable($query_builder, $request, $columns_to_search, $table_name, $fk_column_name)
    {
        if (! Utils::requestHasSearchableColumn($request, $columns_to_search)) { return $query_builder; }

        $query_builder->where(function ($query) use ($query_builder, $request, $columns_to_search, $table_name, $fk_column_name) {
            // Use the foreign key name on the searched table, matched against 'id' in the model's table
            // $table_fk = $fk_column_name, $model_fk = "id"
            return Utils::whereOperatorSearchTable($query, $request, $columns_to_search, $table_name, $fk_column_name, 'id');
        });
        return $query_builder;
    }

    public static function addOperatorWheres($query, $request, $columns_to_search, $column_prefix = null)
    {
        foreach ($request->all() as $request_key => $request_value) {
            
            $completed_column_name = Utils::uniquely_starts_with($request_key, $columns_to_search);
            if (! $completed_column_name) {
                continue;  // Unrecognized request variable. Ignore it.
            }
            
            //
            // The variable name uniquely identifies one of the search columns.
            // Add a where() for =value, or else as pairs of [operator, value, ...] as an array.
            //

            // If it's just a string value (not an array), look for an exact match.
            // This depends on the PHP parse_str() function, which converts input
            // variables to PHP arrays if they have trailing "[]" characters on the end.
            // See: http://php.net/manual/en/function.parse-str.php
            if (! is_array($request_value) ) {
                // Just look for this exact value in this field.
                $query->where($column_prefix.$completed_column_name, '=', $request_value);                
                continue;
            }

            //
            // It is an array. We expect it to be in the form of [operator, search_value, ...] pairs.
            //
            $operator = array_shift($request_value);
            $search_value = array_shift($request_value);
            
            while ($operator != null) {
                // If it ends with 'day', 'month', 'year', 'time', 'date', 
                // we treat is as a date field operator. Remove the modifier text
                // and use the remainder as the operator.
                if (endsWith($operator, 'day')) {
                    $operator = trim( str_replace('day', '', $operator) );
                    // Make sure the operator is in the list of valid ones, such as
                    // '<', '>', '=', '<=', '>=', '<>', '!=', 'like', etc. 
                    if (invalidOperator($operator)) continue;
                    $query->whereDay($column_prefix.$completed_column_name, $operator, $search_value);
                }
                else if (endsWith($operator, 'month')) {
                    $operator = trim( str_replace('month', '', $operator) );
                    if (invalidOperator($operator)) continue;
                    $query->whereMonth($column_prefix.$completed_column_name, $operator, $search_value);
                }
                else if (endsWith($operator, 'year')) {
                    $operator = trim( str_replace('year', '', $operator) );
                    if (invalidOperator($operator)) continue;
                    $query->whereYear($column_prefix.$completed_column_name, $operator, $search_value);
                }
                else if (endsWith($operator, 'time')) {
                    $operator = trim( str_replace('time', '', $operator) );
                    if (invalidOperator($operator)) continue;
                    $query->whereTime($column_prefix.$completed_column_name, $operator, $search_value);
                }
                else if (endsWith($operator, 'date')) {
                    $operator = trim( str_replace('date', '', $operator) );
                    if (invalidOperator($operator)) continue;
                    $query->whereDate($column_prefix.$completed_column_name, $operator, $search_value);
                } else {
                    // It's not a date-modified operator. Just use it as given (but trimmmed).
                    $operator = trim( $operator );
                    if (invalidOperator($operator)) continue;                    
                    $query->where($column_prefix.$completed_column_name, $operator, $search_value);                
                }

                // Grab the next operator/value pair, if any:
                $operator = array_shift($request_value);
                $search_value = array_shift($request_value);
            }
        }  // end for
    }

    public static function whereOperatorSearchTable($query_builder, $request, $columns_to_search, $table_name, $table_fk, $model_fk)
    {
        $model_table = $query_builder->getModel()->getTable();
        $query_builder->whereExists(function ($query) use ($request, $columns_to_search, $table_name, $table_fk, $model_table, $model_fk) {
                // We use FROM $table_name AS ... syntax here, so this SQL query still works
                // in the case the searched $table_name is the same table as the Model table.
                $as_table = $table_name . "__searched__";
                $query->select(DB::raw(1))->from($table_name." AS ".$as_table)
                    // Using whereRaw() here because where() wraps single-quotes around
                    // the model's table name, like a literal string (instead of the ` backtick
                    // quotes needed for MySQL to treat it as a `table`.`field` name).
                    ->whereRaw("`".$as_table."`.`".$table_fk."` = `".$model_table."`.`".$model_fk."`");
                
                $column_prefix = $as_table.".";  // Add the separator . for table.column:
                Utils::addOperatorWheres($query, $request, $columns_to_search, $column_prefix);
            });

        return $query_builder;
    }

    public static function whereWordSearch($query_builder, $request, $columns_to_search)
    {
        if (! $request->has('q')) { return $query_builder; }  // Do nothing. 

        // Search the model's own table.
        $query_builder->where(function ($query) use ($query_builder, $request, $columns_to_search) {
            $table_name = $query_builder->getModel()->getTable();
            Utils::whereWordSearchTable($query, $request, $columns_to_search, $table_name, 'id', 'id');
        });
        return $query_builder;
    }

    public static function orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, $table_name, $fk_column_name, $model_table=null)
    {
        if (! $request->has('q')) { return $query_builder; }  // Do nothing. 

        $query_builder->orWhere(function ($query) use ($query_builder, $request, $columns_to_search, $table_name, $fk_column_name, $model_table) {
            // Use the foreign key name on the model's table, matched against 'id' in the searched table.
            // $table_fk = "id", $model_fk = $fk_column_name
            Utils::whereWordSearchTable($query, $request, $columns_to_search, $table_name, 'id', $fk_column_name, $model_table);
        });
        return $query_builder;
    }

    public static function orWhereWordSearchHasOneTable($query_builder, $request, $columns_to_search, $table_name, $fk_column_name, $model_table=null)
    {
        if (! $request->has('q')) { return $query_builder; }  // Do nothing. 

        $query_builder->orWhere(function ($query) use ($query_builder, $request, $columns_to_search, $table_name, $fk_column_name, $model_table) {
            // Use the foreign key name on the searched table, matched against 'id' in the model's table
            // $table_fk = $fk_column_name, $model_fk = "id"
            return Utils::whereWordSearchTable($query, $request, $columns_to_search, $table_name, $fk_column_name, 'id', $model_table);
        });
        return $query_builder;
    }

    public static function whereWordSearchTable($query_builder, $request, $columns_to_search, $table_name, $table_fk, $model_fk, $model_table=null)
    {
        //
        // All search words must appear someplace in the provided list of varchar columns.
        //

        // Parse the search words on whitespace. They are searched individually.
        $search_words = preg_split("@[\s+　]@u", $request->input('q'));

        // For every search word, add it as a single where clause, with a bunch of
        // nested orWhere conditions so it can show up in any of the fields.
        if ($model_table == null) {
            $model_table = $query_builder->getModel()->getTable();
        }
        
        foreach ($search_words as $search_word) {
            // All search_words are ANDed together, so all words must appear.
            $query_builder->whereExists(function ($query) use ($search_word, $columns_to_search, $table_name, $table_fk, $model_table, $model_fk) {

                // We use FROM $table_name AS ... here, so this SQL query still works
                // in the case the searched $table_name is the same table as the Model table.
                $as_table = $table_name . "__searched__";
                $query->select(DB::raw(1))->from($table_name." AS ".$as_table)
                    // Using whereRaw() here because where() wraps literal string 
                    // single-quotes around the model table's name (instead of the ` backtick
                    // quotes needed for MySQL to treat it as a `table`.`field` name).
                    ->whereRaw("`".$as_table."`.`".$table_fk."` = `".$model_table."`.`".$model_fk."`");
                
                
                // The OR'd column results need to be in parens () after the FK match.
                $query->where(function ($q) use ($columns_to_search, $as_table, $search_word)
                {
                    $first_column = true;  // We need where(), not orWhere(), for the first one.
                    foreach ($columns_to_search as $column) {
                        // We use surrounding wildard % chars, so the search term can 
                        // appear anywhere within the column's text.
                        if ($first_column == true) {
                            $first_column = false;

                            $q->where($as_table.".".$column, 'like', "%".$search_word."%");
                            continue;
                        }
                        // All column results are ORed together, so it can appear in 
                        // any of the columns_to_search to return the row.
                        $q->orWhere($as_table.".".$column, 'like', "%".$search_word."%");                
                    }
                });

            });
        }
        
        return $query_builder;
    }


    public static function maxMiles($query_builder, $table_name, $table_fk, $model_table, $model_fk, $max_miles, $near_lat=null, $near_lon=null)
    {
        if ($near_lat == null || $near_lon == null) {
            // The default distance is relative to the requestor:
            $near_lat = request()->user()->user_profile->location->latitude;
            $near_lon = request()->user()->user_profile->location->longitude;
        }

        //
        // Security note:
        // The query builder fails with the distance SQL functions, when trying to 
        // use safe ? parameters in an array. We use raw SQL to get around that,
        // but cast the inputs to floats to mitigate SQL injection here.
        // 
        $near_lat = (float)$near_lat;
        $near_lon = (float)$near_lon;
        $max_miles = (float)$max_miles;

        $query_builder->whereExists(function ($query) use ($table_name, $table_fk, $model_table, $model_fk, $near_lat, $near_lon, $max_miles) {
            // We use FROM $table_name AS ... syntax here, so this SQL query still works
            // in the case the searched $table_name is the same table as the Model table.
            $as_table = $table_name . "__max_miles__";
            $query->select(DB::raw(1))->from($table_name." AS ".$as_table)
                ->leftJoin('locations', 'locations.id', '=', $as_table.".location_id")
                // Using whereRaw() here because where() wraps single-quotes around
                // the model's table name, like a literal string (instead of the ` backtick
                // quotes needed for MySQL to treat it as a `table`.`field` name).
                ->whereRaw("`".$as_table."`.`".$table_fk."` = `".$model_table."`.`".$model_fk."`")
                ->whereRaw("2 * 3961 * asin(sqrt(POWER((sin(radians((locations.latitude - ".$near_lat." ) / 2))), 2) + cos(radians( ".$near_lat." )) * cos(radians(locations.latitude)) * POWER((sin(radians((locations.longitude - ".$near_lon." ) / 2))), 2)) )  < ".$max_miles."" );
        });

        return $query_builder;
    }

    public static function futureOnly($query_builder, $table_name, $table_fk, $model_table, $model_fk)
    {
        $query_builder->whereExists(function ($query) use ($table_name, $table_fk, $model_table, $model_fk) {
            // We use FROM $table_name AS ... syntax here, so this SQL query still works
            // in the case the searched $table_name is the same table as the Model table.
            $as_table = $table_name . "__future_only__";
            $query->select(DB::raw(1))->from($table_name." AS ".$as_table)
                // Using whereRaw() here because where() wraps single-quotes around
                // the model's table name, like a literal string (instead of the ` backtick
                // quotes needed for MySQL to treat it as a `table`.`field` name).
                ->whereRaw("`".$as_table."`.`".$table_fk."` = `".$model_table."`.`".$model_fk."`")
                ->whereDate("start_datetime" , ">=", date("Y-m-d") );
        });

        return $query_builder;
    }

    public static function todayOnly($query_builder, $table_name, $table_fk, $model_table, $model_fk)
    {
        $query_builder->whereExists(function ($query) use ($table_name, $table_fk, $model_table, $model_fk) {
            // We use FROM $table_name AS ... syntax here, so this SQL query still works
            // in the case the searched $table_name is the same table as the Model table.
            $as_table = $table_name . "__today_only__";
            $query->select(DB::raw(1))->from($table_name." AS ".$as_table)
                // Using whereRaw() here because where() wraps single-quotes around
                // the model's table name, like a literal string (instead of the ` backtick
                // quotes needed for MySQL to treat it as a `table`.`field` name).
                ->whereRaw("`".$as_table."`.`".$table_fk."` = `".$model_table."`.`".$model_fk."`")
                ->whereDate("start_datetime" , "=", date("Y-m-d") );
        });

        return $query_builder;
    }

    public static function saveFile($request, $file_path, $filename, $file_object)
    {
        if (Storage::exists($file_path."/".$filename)) {
            Storage::delete($file_path."/".$filename);
        }

        $file_object->storeAs($file_path, $filename);
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
        // Encode special characters...
        $file_url = $protocol. $request->server('SERVER_NAME') . $port . "/". "storage/" . str_replace("public/", '', $file_path) . urlencode($filename);
        
        return $file_url;
    }

    public static function saveImage($request, $file_path, $filename, $file_object)
    {
        $image_url = Utils::saveFile($request, $file_path, $filename, $file_object);

        //
        // Save the various thumbnail resolutions:
        //
        $image_object = Image::make($file_object);

        $width = 640;
        $height = 480;
        Utils::saveThumbnail($width, $height, $file_path, $filename, $image_object);

        $width = 320;
        $height = 240;
        Utils::saveThumbnail($width, $height, $file_path, $filename, $image_object);

        // Passing 'null' causes it to auto-size keeping the aspect ratio the same:
        $width = 640;
        $height = null;
        Utils::saveThumbnail($width, $height, $file_path, $filename, $image_object);

        $width = 320;
        $height = null;
        Utils::saveThumbnail($width, $height, $file_path, $filename, $image_object);

        $width = null;
        $height = 800;
        Utils::saveThumbnail($width, $height, $file_path, $filename, $image_object);

        $width = null;
        $height = 480;
        Utils::saveThumbnail($width, $height, $file_path, $filename, $image_object);

        return $image_url;
    }


    public static function saveThumbnail($width, $height, $file_path, $filename, $image_object) {
        // Also save the thumbnails. (The URLS are based on convention -- clients must know to request it.)

        // http://image.intervention.io/api/reset
        //
        // The API does not have a copy() or clone(), instead it provides this
        // backup()/restore() API.
        $image_object->backup();

        // https://developer-paradize.blogspot.com/2013/05/get-file-name-without-file-extension-in.html
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filename_no_extension = basename($filename, "." . $extension);
        $size_string = "$width" . "x" . "$height";
        $thumbnail_filename = $filename_no_extension . "-" . $size_string . "." . $extension;

        $image_object->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
        // Save the scaled version under the new thumbnail's name:
        // We are not using the Storage driver so we need to fix the path:
        //$image_object->save($file_path."/".$thumbnail_filename);
        $image_object->save(str_replace("public/", 'storage/', $file_path).$thumbnail_filename);

        // Reset back to the pre-scaled state so other thumbnails can be created
        // from the original resolution:
        $image_object->reset();

        // No return value.
    }

}
