<?php

return [
    // Local Process Vendor selection captured on 19/Nov/2026. Applied by Settings > Defaults.
    'defaults' => [
        'visible' => [
            'Anodizing', 'Anodizing AT', 'Anodizing stripping',
            'CAD', 'Cad plate', 'Cad stripping',
            'Chrome plating', 'Chrome plating, Thin Dens', 'Chrome stripping',
            'E - Nickel plating', 'E - Nickel stripping', 'Passivation',
            'S - Nickel plating', 'S - Nickel stripping',
            'Shimmy Damper O/H', 'Shot peening', 'Traveler',
        ],
        'groups' => [
            ['name' => 'CAD', 'enabled' => true, 'members' => ['CAD', 'Cad plate', 'Cad stripping']],
            ['name' => 'Anod', 'enabled' => true, 'members' => ['Anodizing', 'Anodizing AT', 'Anodizing stripping']],
            ['name' => 'CHrome', 'enabled' => true, 'members' => ['Chrome plating', 'Chrome plating, Thin Dens', 'Chrome stripping']],
            ['name' => 'Nickel', 'enabled' => true, 'members' => ['E - Nickel plating', 'E - Nickel stripping', 'S - Nickel Machining', 'S - Nickel plating', 'S - Nickel stripping']],
            ['name' => 'Silver', 'enabled' => true, 'members' => ['Silver plating', 'Silver stripping']],
            ['name' => 'NDT', 'enabled' => true, 'members' => ['BNI', 'NDT', 'NDT-1', 'NDT-4', 'NDT-6 Eddy Current', 'NDT-7 BNI', 'NDT-7 Ultra Sound', 'Nital Etch Inspection']],
        ],
    ],
];
