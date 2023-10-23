<?php

return [
    'admin' => [
        'name' => 'admin',
        'type' => 'role',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
        'children' => [
            'viewInv',
            'editInv',
            'viewPayment',
            'editPayment',
            'editUser',
            'editClientPeppol'
        ],
    ],
    
    // Accountant with the permission to create and record payments to invoices for assigned clients
    'accountant' => [
        'name' => 'accountant',
        'type' => 'role',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
        'children' => [
           'viewInv',
           'viewPayment',
           'editPayment'
        ]    
    ],
    
    // Users with the right to view quotes
    'observer' => [
        'name' => 'observer',
        'type' => 'role',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
        'children' => [
           'viewInv',
           'viewPayment',
           'editUserInv',
           'editClientPeppol'
        ]    
    ],
    
    'viewInv'=>[
        'name' => 'viewInv',
        'type' => 'permission',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
    ],
    
    'editInv'=>[
        'name' => 'editInv',
        'type' => 'permission',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
    ],
    
    'viewPayment'=>[
        'name' => 'viewPayment',
        'type' => 'permission',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
    ],
    
    'editPayment'=>[
        'name' => 'editPayment',
        'type' => 'permission',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
    ],
    
    'editUser'=>[
        'name' => 'editUser',
        'type' => 'permission',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
    ],
    
    'editUserInv'=>[
        'name' => 'editUserInv',
        'type' => 'permission',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
    ],
    
    'editClientPeppol'=>[
        'name' => 'editClientPeppol',
        'type' => 'permission',
        'updatedAt' => 1599036348,
        'createdAt' => 1599036348,
    ],
];
