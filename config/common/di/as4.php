<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Invoice\As4\As4DuplicateDetector;
use App\Invoice\As4\As4DuplicateDetectorInterface;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4PayloadHandlerInterface;
use App\Invoice\As4\As4ReceiptGenerator;
use App\Invoice\As4\As4ReceiptGeneratorInterface;
use App\Invoice\As4\As4UserMessageHandlerInterface;
use App\Invoice\As4\As4UserMessageHandlerService;
use App\Invoice\As4\NullAs4PayloadHandler;

return [
    As4MessageRepositoryInterface::class  => CycleOrmAs4MessageRepository::class,
    As4DuplicateDetectorInterface::class  => As4DuplicateDetector::class,
    As4ReceiptGeneratorInterface::class   => As4ReceiptGenerator::class,
    As4UserMessageHandlerInterface::class => As4UserMessageHandlerService::class,
    As4PayloadHandlerInterface::class     => NullAs4PayloadHandler::class,
];
