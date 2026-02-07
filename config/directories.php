<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Directory tables config
    |--------------------------------------------------------------------------
    | Key = route segment (builders, teams, vendors...)
    | title = for UI
    | model = Eloquent model class
    | fields = editable fields on UI + validation rules
    | search = columns for search (optional)
    */

    'builders' => [
        'title'  => 'Builders',
        'model'  => App\Models\Builder::class,
        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required', 'string', 'max:255', 'unique:builders,name'],
            ],
        ],
        'search' => ['name'],
        'order'  => ['name' => 'asc'],
    ],

    'teams' => [
        'title'  => 'Teams',
        'model'  => App\Models\Team::class,
        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required', 'string', 'max:255', 'unique:teams,name'],
            ],
        ],
        'search' => ['name'],
        'order'  => ['name' => 'asc'],
    ],

    'vendors' => [
        'title'  => 'Vendors',
        'model'  => App\Models\Vendor::class,
        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required', 'string', 'max:255', 'unique:vendors,name'],
            ],
        ],
        'search' => ['name'],
        'order'  => ['name' => 'asc'],
    ],

    'codes' => [
        'title'  => 'Codes',
        'model'  => App\Models\Code::class,
        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required', 'string', 'max:255', 'unique:codes,name'],
            ],
            'code' => [
                'label' => 'Code',
                'rules' => ['required', 'string', 'max:100', 'unique:codes,code'],
            ],
        ],
        'search' => ['name', 'code'],
        'order'  => ['name' => 'asc'],
    ],

    'scopes' => [
        'title'  => 'Scopes',
        'model'  => App\Models\Scope::class,
        'fields' => [
            'scope' => [
                'label' => 'Scope',
                'rules' => ['required', 'string', 'max:255', 'unique:scopes,name'],
            ],
        ],
        'search' => ['scope'],
        'order'  => ['scope' => 'asc'],
    ],

    'process_names' => [
        'title'  => 'Process Names',
        'model'  => App\Models\ProcessName::class,
        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required', 'string', 'max:255', 'unique:process_names,name'],
            ],
        ],
        'search' => ['name'],
        'order'  => ['name' => 'asc'],
    ],

    'planes' => [
        'title'  => 'Planes',
        'model'  => App\Models\Plane::class,
        'fields' => [
            'type' => [
                'label' => 'Type',
                'rules' => ['required', 'string', 'max:255', 'unique:planes,name'],
            ],
        ],
        'search' => ['type'],
        'order'  => ['type' => 'asc'],
    ],

    'instructions' => [
        'title'  => 'Instructions',
        'model'  => App\Models\Instruction::class,
        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required', 'string', 'max:255', 'unique:instructions,name'],
            ],
        ],
        'search' => ['name'],
        'order'  => ['name' => 'asc'],
    ],

    'necessaries' => [
        'title'  => 'Necessaries',
        'model'  => App\Models\Necessary::class,
        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required', 'string', 'max:255', 'unique:necessaries,name'],
            ],
        ],
        'search' => ['name'],
        'order'  => ['name' => 'asc'],
    ],

    'roles' => [
        'title'  => 'Roles',
        'model'  => App\Models\Role::class,
        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required', 'string', 'max:255', 'unique:roles,name'],
            ],
        ],
        'search' => ['name'],
        'order'  => ['name' => 'asc'],
    ],

];
