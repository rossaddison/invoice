<?php

return [
    [
        'name' => 'admin',
        'type' => 'role',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
        'children' => [
            'view.inv',
            'edit.inv',
            'view.payment',
            'edit.payment',
            'edit.user.inv',
            'edit.client.peppol',
        ],
    ],
    [
        'name' => 'accountant',
        'type' => 'role',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
        'children' => [
            'view.inv',
            'view.payment',
            'edit.payment',
        ],
    ],
    [
        'name' => 'observer',
        'type' => 'role',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
        'children' => [
            'view.inv',
            'view.payment',
            'edit.user.inv',
            'edit.client.peppol',
        ],
    ],
    [
        'name' => 'view.inv',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'edit.inv',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'view.payment',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'edit.payment',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'edit.user.inv',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'edit.client.peppol',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'no.entry.to.base.controller',
        'type' => 'permission',
        'updated_at' => 1749663993,
        'created_at' => 1749663993,
    ],
    [
        'name' => 'entry.to.base.controller',
        'type' => 'permission',
        'updated_at' => 1749663993,
        'created_at' => 1749663993,
    ],
];
