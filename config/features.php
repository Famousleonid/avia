<?php

/**
 * Feature access gates.
 *
 * Gate name: feature.{key}, for example feature.paint.
 * label: human readable name for System > Access.
 * group: grouping label for System > Access.
 * managed: when true, System > Access can grant/revoke explicit user access.
 * roles: any matching role grants access.
 * user_ids: optional explicit user whitelist.
 * allow_is_admin: when true, User::isAdmin() grants access.
 */
return [

    'paint' => [
        'label' => 'Paint',
        'roles' => ['Admin', 'Manager', 'Paint'],
        //'user_ids' => [2, 5, 8],
        'allow_is_admin' => true,
    ],

    'machining' => [
        'label' => 'Machining',
        'roles' => ['Admin', 'Manager', 'Machining'],
        'allow_is_admin' => true,
    ],

    'marketing' => [
        'label' => 'Marketing',
        'group' => 'Pages',
        'managed' => true,
        'roles' => [],
        'allow_is_admin' => false,
    ],

    'quality_assurance' => [
        'label' => 'Quality Assurance',
        'group' => 'Pages',
        'managed' => true,
        'roles' => [],
        'allow_is_admin' => false,
    ],

    'ec' => [
        'label' => 'EC',
        'group' => 'Pages',
        'managed' => true,
        'roles' => [],
        'allow_is_admin' => false,
    ],

    'vendor_tracking' => [
        'label' => 'Vendor Tracking',
        'group' => 'Pages',
        'managed' => true,
        'roles' => [],
        'allow_is_admin' => false,
    ],

    'certificates.sign' => [
        'label' => 'Can sign certificates',
        'group' => 'Capabilities',
        'managed' => true,
        'roles' => [],
        'allow_is_admin' => false,
    ],

    'manuals.full' => [
        'label' => 'Manuals full access',
        'group' => 'Capabilities',
        'managed' => true,
        'roles' => [],
        'allow_is_admin' => false,
    ],

    'manuals.locked_processes' => [
        'label' => 'Manage locked manual processes',
        'group' => 'Capabilities',
        'managed' => true,
        'roles' => [],
        'allow_is_admin' => false,
    ],

    'manuals.locked_parts' => [
        'label' => 'Manage locked manual parts',
        'group' => 'Capabilities',
        'managed' => true,
        'roles' => [],
        'allow_is_admin' => false,
    ],

    // Example: whitelist only, no roles.
   // 'one_off_tool' => [
    //    'roles' => [],
  //      'user_ids' => [1, 2],
  //  ],

];
