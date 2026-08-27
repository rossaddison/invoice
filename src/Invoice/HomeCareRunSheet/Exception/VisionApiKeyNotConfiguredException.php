<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet\Exception;

use RuntimeException;

/**
 * Thrown by HomeCareRunSheetVisionService::readScan() when
 * `homecare_vision_api_key` has no value in Settings → HomeCare — caught in
 * HomeCareRunSheetController::performScan(), which flashes a message
 * pointing the office back at Settings instead of letting the request crash
 * to a raw debug page (see project_homecare_run_signoff_design memory).
 */
final class VisionApiKeyNotConfiguredException extends RuntimeException
{
}
