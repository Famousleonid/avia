<?php

/**
 * Feature access gates.
 *
 * Gate name: feature.{key}, for example feature.paint.
 * roles: any matching role grants access.
 * user_ids: optional explicit user whitelist.
 * allow_is_admin: when true, User::isAdmin() grants access.
 */
return [

    'paint' => [
        'roles' => ['Admin', 'Manager', 'Paint'],
        //'user_ids' => [2, 5, 8],
        'allow_is_admin' => true,
    ],

    'machining' => [
        'roles' => ['Admin', 'Manager', 'Machining'],
        'allow_is_admin' => true,
    ],

    'marketing' => [
        'roles' => ['Admin', 'Manager'],
        'allow_is_admin' => true,
    ],

    // Example: whitelist only, no roles.
   // 'one_off_tool' => [
    //    'roles' => [],
  //      'user_ids' => [1, 2],
  //  ],

];
