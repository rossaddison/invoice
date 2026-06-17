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
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Handles product file-attachment upload and binary download.
 * Extracted from ProductController to satisfy S1448 (≤20 methods per class).
 */
final class ProductAttachmentController extends BaseController
{
    protected string $controllerName = 'invoice/product';

    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
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
    ): Response {
        $aliases    = $this->sR->getProductimagesFilesFolderAliases();
        $targetPath = $aliases->get('@public_product_images');
        $product_id = $id;

        if ($product_id) {
            if (!is_writable($targetPath)) {
                return $this->responseFactory->createResponse(
                    $this->imageAttachmentNotWritable((int) $product_id)
                );
            }

            $product = $pR->repoProductquery((int) $product_id) ?: null;
            if ($product instanceof Product) {
                $product_id = $product->reqId();
                if ($product_id) {
                    if (!empty($_FILES)) {
                        /** @var array $_FILES['ImageAttachForm'] */
                        /** @var string $_FILES['ImageAttachForm']['tmp_name']['attachFile'] */
                        $temporary_file = $_FILES['ImageAttachForm']['tmp_name']['attachFile'];
                        /** @var string $_FILES['ImageAttachForm']['name']['attachFile'] */
                        $original_file_name = preg_replace(
                            '/\s+/', '_',
                            $_FILES['ImageAttachForm']['name']['attachFile']
                        );
                        if (null !== $original_file_name) {
                            $target_path_with_filename = $targetPath . '/' . $original_file_name;
                            if ($this->imageAttachmentMoveTo(
                                $temporary_file, $target_path_with_filename,
                                $product_id, $original_file_name, $piR
                            )) {
                                return $this->responseFactory->createResponse(
                                    $this->imageAttachmentSuccessfullyCreated($product_id)
                                );
                            }
                            return $this->responseFactory->createResponse(
                                $this->imageAttachmentNoFileUploaded($product_id)
                            );
                        }
                    } else {
                        return $this->responseFactory->createResponse(
                            $this->imageAttachmentNoFileUploaded($product_id)
                        );
                    }
                }
                return $this->webService->getRedirectResponse('product/index');
            }
            return $this->webService->getRedirectResponse('product/index');
        }
        return $this->webService->getRedirectResponse('product/index');
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
        string $tmp,
        string $target,
        int $product_id,
        string $fileName,
        piR $piR,
    ): bool {
        if (!file_exists($target)) {
            if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $target)) {
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
            $this->flashMessage(
                'warning',
                $this->translator->translate('productimage.possible.file.upload.attack') . $tmp
            );
            return false;
        }
        $this->flashMessage('warning', $this->translator->translate('error_duplicate_file'));
        return false;
    }

    private function imageAttachmentNotWritable(int $product_id): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            [
                'heading' => $this->translator->translate('errors'),
                'message' => $this->translator->translate('path')
                    . $this->translator->translate('is.not.writable'),
                'url' => 'product/view', 'id' => $product_id,
            ],
        );
    }

    private function imageAttachmentSuccessfullyCreated(int $product_id): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            [
                'heading' => '',
                'message' => $this->translator->translate('record.successfully.created'),
                'url' => 'product/view', 'id' => $product_id,
            ],
        );
    }

    private function imageAttachmentNoFileUploaded(int $product_id): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            [
                'heading' => $this->translator->translate('errors'),
                'message' => $this->translator->translate('productimage.no.file.uploaded'),
                'url' => 'product/view', 'id' => $product_id,
            ],
        );
    }
}
