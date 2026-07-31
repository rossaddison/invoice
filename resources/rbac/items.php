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
            'entry.to.base.controller',
            'manage.hmrc',
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
            'entry.to.base.controller',
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
            'entry.to.base.controller',
        ],
    ],
    [
        // Field worker (HomeCare) — read-only, scoped to invoices allocated
        // to them (see InvRepository::repoWorkerVisible()). No edit.inv, so
        // like observer they cannot reach staff-side inv/index, only
        // inv/guest. Deliberately narrower than observer: no view.payment
        // (payment info isn't relevant to the worker) and no
        // edit.user.inv/edit.client.peppol (edit-type permissions, not
        // relevant to a read-only field role).
        'name' => 'worker',
        'type' => 'role',
        'updated_at' => 1784151222,
        'created_at' => 1784151222,
        'children' => [
            'view.inv',
            'entry.to.base.controller',
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
        'name' => 'entry.to.base.controller',
        'type' => 'permission',
        'updated_at' => 1749663993,
        'created_at' => 1749663993,
    ],
    [
        'name' => 'manage.hmrc',
        'type' => 'permission',
        'updated_at' => 1784151222,
        'created_at' => 1784151222,
    ],
];
