<?php

declare(strict_types=1);

namespace App\Auth\Trait;

trait ClassList
{
    private function classList(): array
    {
        return [
            1 => 'container py-5 h-100',
            2 => 'row d-flex justify-content-center align-items-center'
                    . ' h-100',
            3 => 'col-12 col-md-8 col-lg-6 col-xl-5',
            4 => 'card border border-dark shadow-2-strong rounded-3',
            5 => 'card-header bg-dark text-white',
            6 => 'fw-normal h3 text-center',
            7 => 'text-center',
            8 => 'card-body p-2 text-center fade-out',
            9 => 'badge bg-primary',
            10 => 'card-body p-2 text-center',
            11 => 'form-control',
            12 => 'form-check form-switch text-start mt-2',
            13 => 'form-check-input form-control',
            14 => 'form-check-label',
            15 => 'btn btn-primary',
            16 => 'my-1 mx-0',
        ];
    }
}
