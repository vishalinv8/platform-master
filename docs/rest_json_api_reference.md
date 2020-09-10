
# Plai API Reference

----

# Application Context

----

## Get Context (Lists of Types, Roles, Policies, Permission Levels, etc.)

```
GET /api/context
```

### Description

This gets the list of activity_types, age_groups, genders, skill_levels, post_statuses, organization_post_statuses, and enrollment_policies. These values say what list of "select" or "multiple select" options to provide in input forms. 

The id values here should be used for the input values of `activity_type_id`, `age_group_id`, `gender_id`, `skill_level_id`, `post_status_id`, `organization_post_status_id`, and `enrollment_policy_id` when creating or updating content.

See above for a recent example of the available values. (Ignore the created_at, updated_at, and deleted_at fields for this data.)


----

# Authentication

----

## Register as New Local User

```
POST /api/auth/register
```

### Parameters

- **name**: The Plai username.
- **email**: The user's email address. This email address must not already exist in the database.
- **password**: The user's login password, which can be used at /api/auth/login

#### Optional Parameters

- **nickname**: An optional screen name.
- **avatar_url**: An optional URL to an avatar image. If this is not provided, an avatar image will be generated for the user on the Plai server. 
- **avatar_file**: An optional file upload to use as the user's avatar.

### Description

The provided email address must not already exist -- email is the unique key used for identifying logins.

----

## Login as a Local User

```
POST /api/auth/login
```

### Parameters

- **email**: The user's email address, to identify them
- **password**: The user's login password. This can be changed using `PUT /api/users/{user}`

### Description

The provided email address must not already exist.

----

## Logout (invalidate the current Auth token)

```
POST /api/auth/logout
```
----

## Refresh Auth Token

```
POST /api/auth/refresh
```

### Description

This will invalidate the current auth token and return a new one, with a new expiration time.

Clients should POST to this url before the current token expires, in order to keep a login session alive.

----

----

# Events

----

## List or Search Events

```
GET /api/events
```

### Parameters

- **future_only=1** to hide past events.
- **today_only=1** to limit events to those occurring today.
- **max_miles=NUMBER** to limit the events to those within max_miles. The location will use the requestor's profile latitude and longitude, unless **near_lat** and **near_lon** are also supplied in the request to override the default.


### Supported Fields for Operator Searches

Any of the following field names can be used with operator searches.

```
$columns_to_search = [ 'title', 'description', 'url', 'phone', 'event_email', 
    'twitter', 'instagram', 'facebook', 'image_url', 'video_url', 'organization_id', 
    'start_datetime', 'end_datetime', 'post_status_id', 'organization_post_status_id', 
    'user_id', 'gender_id', 'age_group_id', 'activity_type_id', 'skill_level_id', 
    'cross_street', 'address', 'address2', 'city', 'state', 'country', 'postal_code', 
    'cc', 'latitude', 'longitude' 'name' ];
```

### Description

Event list, from oldest to newest.

----

## Create Event

```
POST /api/events
```

### Parameters

- **image_file**: An optional file upload for the image. The field `image_url` will be point to to the copy of the file on the server.

All the (non-computed, writable) fields in an Event record can be supplied. 

The following fields are required:

```
'post_status_id',
'user_id',
'location_id',
'gender_id',
'age_group_id',
'activity_type_id',
'skill_level_id',
```



### Description

Create an Event with the given values. The saved event object will be returned. Any user can create an event, but admin or poster permission is required if an **organization_id** is also supplied.

----

## Event Detail

```
GET /api/events/{event}
```

### Description

Get a specific event entry. Unlike the event list, this also includes the fields 

```
    "users_going": [],
    "users_alerting": []
```

Which is the list of users attending (or being alerted) on this event, including their profiles (if their profile permissions allow).

----

## Update Event

```
PUT /api/events/{event}
PATCH /api/events/{event}
```

### Parameters

- **image_file**: An optional file upload for the image. The field `image_url` will be point to to the copy of the file on the server.

All the (non-computed, writable) fields in an Event record can be supplied. 

----

## Delete Event

```
DELETE /api/events/{event}
```
----

## Event: User Going

```
POST /api/events/{event}/going
```

### Description

Mark the authenticated user as going to the provided event. The event detail will be returned, which should now include the current user in the `users_going` array.

----

## Event: User Not Going

```
POST /api/events/{event}/notgoing
```

### Description

Mark the authenticated user as NOT going to the provided event. The event detail will be returned, which should not include the current user in the `users_going` array.

----

## Event: User Alerting

```
POST /api/events/{event}/alerting
```

### Description

Mark the authenticated user as wanting alerts on the provided event. The event detail will be returned, which should now include the current user in the `users_alerting` array.

----

## Event: User Not Alerting

```
POST /api/events/{event}/notalerting
```

### Description

Mark the authenticated user as NOT wanting alerts on the provided event. The event detail will be returned, which should not include the current user in the `users_alerting` array.

----

# Users

----

> NOTE: Users are not created like other items, with `POST users/`. Instead user creation is handled by `/api/auth/register`, below.

> NOTE: Similary, there is no `DELETE users`. That must be handled seperately by the admin panel, as it is not a feature supported by the API.

----

## List or Search Users

```
GET /api/users
```

### Parameters

- **max_miles=NUMBER** to limit the organizations to those within max_miles. The location will use the requestor's profile latitude and longitude, unless **near_lat** and **near_lon** are also supplied in the request to override the default.


### Supported Fields for Operator Searches

Any of the following field names can be used with operator searches.

```
$columns_to_search = [ 'name', 'user_id', 'gender_id', 
                        'skill_level_id', 'age_group_id' ];
```

### Description

User list, from newest to oldest (by created_by).

----

## User Detail

```
GET /api/users/{user}
```

### Description

Get a specific user entry. Unlike the user list, this also includes the fields 

```
"organizations_where_admin": [],
"organizations_where_poster": [
    {
        "id": 1,
        "name": "Kiehn Inc",
        "description": "whiteboard visionary functionalities",
        "url": "http://www.prohaska.com/magni-et-vitae-minus-vitae-quibusdam.html",
        "phone": "1-940-576-0727 x0812",
        "organization_email": "jacobson.kenton@example.com",
        "twitter": "http://bednar.com/at-id-ratione-aliquid-dolorem",
        "instagram": "http://www.ebert.info/qui-assumenda-hic-fuga",
        "facebook": "http://www.ortiz.com/voluptas-fugiat-omnis-quis-sit-dignissimos-illum-dolorem",
        "image_url": "http://upton.com/",
        "video_url": "https://www.dach.com/iste-quia-quisquam-cum-ab-animi-itaque",
        "user_id": 3,
        "location_id": 101,
        "enrollment_policy_id": 3,
        "created_at": "2018-09-13 01:45:08",
        "updated_at": "2018-09-13 01:45:08",
        "deleted_at": null,
        "pivot": {
            "user_id": 51,
            "organization_id": 1
        }
    }
],
"organizations_where_member": []
```

In this example there is one entry in the `organizations_where_poster` list, but `organizations_where_admin` and `organizations_where_member` are empty.

----

## User: Get Me

```
GET /api/users/me
```

### Description

Get the User Detail record of the currently authenticated user. This is useful if don't know your own user id but need it for a query, or want to display the user's profile.

----

## User: Get Me

```
GET /api/users/me
```

### Description

Get the User Detail record of the currently authenticated user. This is useful if need to know your own user id a query, or want to display the user's profile.

----

## Update User

```
PUT /api/users/{user}
PATCH /api/users/{user}
```

### Parameters

- **image_file**: An optional file upload for the image. The field `image_url` will be point to to the copy of the file on the server.
- **avatar_file**: An optional file upload to use as the user's avatar.

All the (non-computed, writable) fields in an Organization record can be supplied. 

### Description

Get the User Detail record of the currently authenticated user. This is useful if don't know your own user id but need it for a query, or want to display the user's profile.

----
Users: Friends
----

## Users: Friends: Send a friend request (invite)

```
POST /api/users/friends/requests/{recipient}
```

### Description

Invite another user to be a friend.

The {recipient} argument is a user's id integer key. (The sender is the authenticated user.) The result will be the currently pending friend request entry, which includes the `sender_id` (the authenticated user) and the `recipient_id`:

```
{
    "data": {
        "recipient_id": 1,
        "recipient_type": "App\\User",
        "status": 0,
        "sender_type": "App\\User",
        "sender_id": 52,
        "updated_at": "2018-09-14 04:57:34",
        "created_at": "2018-09-14 04:57:34",
        "id": 1
    }
}
```

If you try to friend someone more than once, you'll receive an error:

```
{
    "error": "Failed (perhaps it's a duplicate request?)"
}
```

----

## Users: Friends: List pending friend requests

```
GET /api/users/friends/requests/pending
```

### Description

Get the list of unanswered invites for the authenticated user.

----

## Users: Friends: Accept a friend request

```
POST /api/users/friends/requests/accept/{sender}
```

----

## Users: Friends: Deny a friend request

```
POST /api/users/friends/requests/deny/{sender}
```

This will set the relationship to the given `sender` to be `Status::DENIED`.

It does not check whether a friend request was ever even sent by `sender`,
so it is possible to pre-emptively deny friend requests from any other user.

The result returned is the return code of the `update()` function as the value
with key name `data`. I think it should always be zero, but check the code to be sure:

```
{
    "data": 0
}
```


----

## Users: Friends: List denied friend requests

```
GET /api/users/friends/requests/denied
```

----

## Users: Friends: Block a user

```
POST /api/users/friends/block/{friend}
```

----

## Users: Friends: List blocked friend requests

```
GET /api/users/friends/blocked
```

----

## Users: Friends: Unblock a user

```
POST /api/users/friends/unblock/{friend}
```

----

## Users: Friends: List friends

```
GET /api/users/friends
```

----

## Users: Friends: List friends of friends

```
GET /api/users/friends_of_friends
```

----

## Users: Friends: List mutual friends with another user

```
GET /api/users/mutual_friends/{other}
```

----

----
# Organizations
----

## List or Search Organizations

```
GET /api/organizations
```

### Parameters

- **max_miles=NUMBER** to limit the organizations to those within max_miles. The location will use the requestor's profile latitude and longitude, unless **near_lat** and **near_lon** are also supplied in the request to override the default.

### Supported Fields for Operator Searches

Any of the following field names can be used with operator searches.

```
$columns_to_search = ['name', 'description', 'url', 'phone', 'organization_email', 
    'twitter', 'instagram', 'facebook', 'image_url', 'video_url', 'user_id', 
    'enrollment_policy_id', 'cross_street', 'address', 'address2', 'city', 'state', 
    'country', 'postal_code', 'cc', 'latitude', 'longitude' ];
```

### Description

Organization list, from closest to furthest (by lat/lon).

----

## Create Organization

```
POST /api/organizations
```

### Parameters

- **image_file**: An optional file upload for the image. The field `image_url` will be point to to the copy of the file on the server.

All the (non-computed, writable) fields in an Organization record can be supplied. 

### Description

Create an Organization with the given values. The saved organization object will be returned. As of 2018-09-13, any user can create an organization.

----

## Organization Detail

```
GET /api/organizations/{organization}
```

### Description

Get a specific organization entry. Unlike the organization list, this also includes the fields 

```
    "members": [],
    "posters": [],
    "admins": [],
    "events": []
```

Which is the list of users in their roles (with profiles), and also events associated with this organization. 

----

## Update Organization

```
PUT /api/organizations/{organization}
PATCH /api/organizations/{organization}
```

### Parameters

- **image_file**: An optional file upload for the image. The field `image_url` will be point to to the copy of the file on the server.

All the (non-computed, writable) fields in an Organization record can be supplied. 

### Description

Only the creator-owner can delete an organization. Administrators cannot delete it.

----

## Delete Organization

```
DELETE /api/organizations/{organization}
```

### Description

Only the creator-owner can delete an organization. Administrators cannot delete it.

----

## Organization: Join

```
POST /api/organizations/{organization}/join/{user}
```

### Description

Have the provided user join the organization with the role specified by the enrollment_policy_name. It assumes open enrollment. 

The user id must be specified in the URL. A user can join themselves (and must provide their own user id in the URL), or an organization administrator can join another user to their organization.

----

## Organization: Leave

```
POST /api/organizations/{organization}/leave/{user}
```

### Description

Have the provided user leave the organization, regardless of what role they have.

The user id must be specified in the URL. A user can leave themselves (and must provide their own user id in the URL), or an organization administrator can remove another user from their organization.

----

## Organization: Members, Posters, and Administrators

```
# Members:
GET /api/organizations/{organization}/members
POST /api/organizations/{organization}/members/{user}
DELETE /api/organizations/{organization}/members/{user}

# Posters:
GET /api/organizations/{organization}/posters
POST /api/organizations/{organization}/posters/{user}
DELETE /api/organizations/{organization}/posters/{user}

# Administrators:
GET /api/organizations/{organization}/admins
POST /api/organizations/{organization}/admins/{user}
DELETE /api/organizations/{organization}/admins/{user}
```

### Description

An organization administrator (or its creator-owner) can assign other users to particular roles in the organization using `POST` to these endpoints.

When doing a GET for the roles, only a user summary is provided. Full user profiles are not included. For that, use `GET /api/organizations/{organization}`.

The DELETE methods here assume the user has the specified role. To remove a user from an organization regardless of their role, use `POST /api/organizations/{organization}/leave/{user}`.

----
