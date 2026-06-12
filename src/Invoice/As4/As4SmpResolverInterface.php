<?php

declare(strict_types=1);

namespace App\Invoice\As4;

interface As4SmpResolverInterface
{
    /**
     * @throws \UnexpectedValueException                  On unparseable or incomplete SMP XML
     * @throws \Psr\Http\Client\ClientExceptionInterface  On transport failure
     */
    public function resolve(As4SmpQuery $query): As4SmpEndpoint;
}
