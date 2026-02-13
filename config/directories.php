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
                'rules' => ['required', 'string', 'max:255', 'unique:scopes,scope'],
            ],
        ],
        'search' => ['scope'],
        'order'  => ['scope' => 'asc'],
    ],

    'process_names' => [
        'title' => 'Process Names',
        'model' => \App\Models\ProcessName::class,
        'order' => ['id' => 'desc'],

        'fields' => [
            'name' => [
                'label' => 'Name',
                'rules' => ['required','string','max:255'],
            ],
            'process_sheet_name' => [
                'label' => 'Process sheet name',
                'rules' => ['required','string','max:255'], // или nullable, см. ниже
            ],
            'form_number' => [
                'label' => 'Form number',
                'rules' => ['nullable','string','max:255'], // или required/int
            ],
        ],
    ],

    'planes' => [
        'title'  => 'Planes',
        'model'  => App\Models\Plane::class,
        'fields' => [
            'type' => [
                'label' => 'Type',
                'rules' => ['required', 'string', 'max:255', 'unique:planes,type'],
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
