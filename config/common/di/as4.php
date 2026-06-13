<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Invoice\As4\As4DuplicateDetector;
use App\Invoice\As4\As4DuplicateDetectorInterface;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4ReceiptGenerator;
use App\Invoice\As4\As4ReceiptGeneratorInterface;

return [
    As4MessageRepositoryInterface::class  => CycleOrmAs4MessageRepository::class,
    As4DuplicateDetectorInterface::class  => As4DuplicateDetector::class,
    As4ReceiptGeneratorInterface::class   => As4ReceiptGenerator::class,
];
