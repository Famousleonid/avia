<?php

use Spatie\Permission\Models\Role;

return [

    'builders' => [
        'title' => 'Builders',
        'model' => App\Models\Builder::class,
        'fields' => [
            'name' => 'Name',
        ],
    ],

    'codes' => [
        'title' => 'Codes',
        'model' => App\Models\Code::class,
        'fields' => [
            'name' => 'Name',
            'code' => 'Code',
        ],
    ],

    'instructions' => [
        'title' => 'Instructions',
        'model' => App\Models\Instruction::class,
        'fields' => [
            'name' => 'Name',
        ],
    ],

    'necessaries' => [
        'title' => 'Necessaries',
        'model' => App\Models\Necessary::class,
        'fields' => [
            'name' => 'Name',
        ],
    ],

    'planes' => [
        'title' => 'Planes',
        'model' => App\Models\Plane::class,
        'fields' => [
            'type' => 'Type',
        ],
    ],

    'process_names' => [
        'title' => 'Process names',
        'model' => App\Models\ProcessName::class,
        'fields' => [
            'name' => 'Name',
        ],
    ],

    'roles' => [
        'title' => 'Roles',
        'model' => App\Models\Role::class,
        'fields' => [
            'name' => 'Name',
        ],
    ],

    'scopes' => [
        'title' => 'Scopes',
        'model' => App\Models\Scope::class,
        'fields' => [
            'scope' => 'Scope',
        ],
    ],

    'teams' => [
        'title' => 'Teams',
        'model' => App\Models\Team::class,
        'fields' => [
            'name' => 'Name',
        ],
    ],

    'vendors' => [
        'title' => 'Vendors',
        'model' => App\Models\Vendor::class,
        'fields' => [
            'name' => 'Name',
        ],
    ],
];

