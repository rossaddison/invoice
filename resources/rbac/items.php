<?php

return [
    [
        'name' => 'admin',
        'type' => 'role',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
        'children' => [
            'viewInv',
            'editInv',
            'viewPayment',
            'editPayment',
            'editUser',
            'editClientPeppol',
            'changePasswordForAnyUser',
        ],
    ],
    [
        'name' => 'accountant',
        'type' => 'role',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
        'children' => [
            'viewInv',
            'viewPayment',
            'editPayment',
        ],
    ],
    [
        'name' => 'observer',
        'type' => 'role',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
        'children' => [
            'viewInv',
            'viewPayment',
            'editUserInv',
            'editClientPeppol',
        ],
    ],
    [
        'name' => 'viewInv',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'editInv',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'viewPayment',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'editPayment',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'editUser',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'editUserInv',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'editClientPeppol',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'changePasswordForAnyUser',
        'type' => 'permission',
        'updated_at' => 1748873425,
        'created_at' => 1748873425,
    ],
    [
        'name' => 'noEntryToBaseController',
        'type' => 'permission',
        'updated_at' => 1749663993,
        'created_at' => 1749663993,
    ],
    [
        'name' => 'entryToBaseController',
        'type' => 'permission',
        'updated_at' => 1749663993,
        'created_at' => 1749663993,
    ],
];
