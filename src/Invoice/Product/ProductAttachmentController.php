<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Invoice\ProductImage\ProductImageRepository as piR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Rule\File;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Handles product file-attachment upload and binary download.
 * Extracted from ProductController to satisfy S1448 (≤20 methods per class).
 */
final class ProductAttachmentController extends BaseController
{
    protected string $controllerName = 'invoice/product';

    private const int MAX_IMAGE_UPLOAD_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private ValidatorInterface $validator,
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        \App\Invoice\Setting\SettingRepository $sR,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    /**
     * Upload a product image file.
     */
    public function imageAttachment(
        #[RouteArgument('id')] string $id,
        ProductRepository $pR,
        piR $piR,
        Request $request,
    ): Response {
        $aliases    = $this->sR->getProductimagesFilesFolderAliases();
        $targetPath = $aliases->get('@public_product_images');

        $loaded     = $id ? $pR->repoProductquery((int) $id) : null;
        $product    = $loaded ?: null;
        $product_id = ($product instanceof Product) ? $product->reqId() : null;
        if ($product_id === null) {
            return $this->webService->getRedirectResponse('product/index');
        }

        /** @var UploadedFileInterface|null $file */
        $file = $request->getUploadedFiles()['ImageAttachForm']['attachFile'] ?? null;
        if (!is_writable($targetPath)) {
            $this->flashMessage(
                'danger',
                $this->translator->translate('path') . $this->translator->translate('is.not.writable'),
            );
        } elseif (
            !$file instanceof UploadedFileInterface
            || $file->getError() === UPLOAD_ERR_NO_FILE
            || !$this->validateImageFile($file, $piR)
        ) {
            $this->flashMessage('warning', $this->translator->translate('productimage.no.file.uploaded'));
        } else {
            $original_file_name = preg_replace('/\s+/', '_', (string) $file->getClientFilename());
            if ($original_file_name === null || $original_file_name === '') {
                return $this->webService->getRedirectResponse('product/index');
            }
            $moved = $this->imageAttachmentMoveTo(
                $file,
                $targetPath . '/' . $original_file_name,
                $product_id,
                $original_file_name,
                $piR
            );
            if ($moved) {
                $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
            }
            // else: imageAttachmentMoveTo() already flashed its own,
            // more specific warning (duplicate filename / possible
            // upload attack) before returning false — nothing more to
            // add here.
        }
        // Was `$this->responseFactory->createResponse($content)` with
        // $content a rendered HTML string — DataResponseFactoryInterface
        // JSON-encodes whatever it's given (raw HTML included), which is
        // exactly why this used to render as an escaped JSON string
        // instead of a page. See ProductImageController::delete() for
        // the same anti-pattern, and this app's own established fix:
        // flash + redirect, a real ResponseInterface, never JSON-wrapped
        // HTML. The '#product-images' hash (no leading '#' — UrlGenerator
        // adds it) sends the customer back to the tab they were actually
        // using — see src/typescript/tab-hash-restore.ts, which reads it
        // back on load since Bootstrap's own tab JS never does.
        return $this->webService->getRedirectResponse(
            'product/view',
            ['id' => (string) $product_id],
            [],
            'product-images',
        );
    }

    /**
     * Whitelists extension and MIME type (sniffed from the real upload contents) and size.
     */
    private function validateImageFile(UploadedFileInterface $file, piR $piR): bool
    {
        /** @var array<string, string> $contentTypes */
        $contentTypes = $piR->getContentTypes();
        $result = $this->validator->validate($file, [
            new File(
                extensions: array_keys($contentTypes),
                mimeTypes: array_values($contentTypes),
                maxSize: self::MAX_IMAGE_UPLOAD_BYTES,
            ),
        ]);
        return $result->isValid();
    }

    public function downloadImageFile(
        #[RouteArgument('product_image_id')] string $product_image_id,
        piR $piR,
        \App\Invoice\Setting\SettingRepository $sR,
    ): void {
        if ($product_image_id) {
            $product_image = $piR->repoProductImagequery((int) $product_image_id);
            if (null !== $product_image) {
                $aliases                   = $sR->getProductimagesFilesFolderAliases();
                $targetPath                = $aliases->get('@productimages_files');
                $original_file_name        = $product_image->getFileNameOriginal();
                $target_path_with_filename = $targetPath . '/' . $original_file_name;
                $path_parts                = pathinfo($target_path_with_filename);
                $file_ext                  = $path_parts['extension'] ?? '';
                if (file_exists($target_path_with_filename)) {
                    $file_size = filesize($target_path_with_filename);
                    if ($file_size != false) {
                        $allowed_content_type_array = $piR->getContentTypes();
                        $save_ctype                 = isset($allowed_content_type_array[$file_ext]);
                        /** @var string $ctype */
                        $ctype = $save_ctype
                            ? $allowed_content_type_array[$file_ext]
                            : $piR->getContentTypeDefaultOctetStream();
                        header('Expires: -1');
                        header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
                        header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                        header('Content-Type: ' . $ctype);
                        header('Content-Length: ' . (string) $file_size);
                        echo file_get_contents($target_path_with_filename, true);
                    }
                    exit;
                }
                exit;
            }
            exit;
        }
        exit;
    }

    private function imageAttachmentMoveTo(
        UploadedFileInterface $file,
        string $target,
        int $product_id,
        string $fileName,
        piR $piR,
    ): bool {
        if (file_exists($target)) {
            $this->flashMessage('warning', $this->translator->translate('error.duplicate.file'));
            return false;
        }
        try {
            $file->moveTo($target);
        } catch (RuntimeException) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('productimage.possible.file.upload.attack') . $fileName
            );
            return false;
        }
        $track_file = new ProductImage();
        $track_file->setProductId($product_id);
        $track_file->setFileNameOriginal($fileName);
        $track_file->setFileNameNew($fileName);
        $track_file->setUploadedDate(new \DateTimeImmutable());
        $piR->save($track_file);
        $this->flashMessage(
            'info',
            $this->translator->translate('productimage.uploaded.to') . $target
        );
        return true;
    }
}
