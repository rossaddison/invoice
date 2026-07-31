<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ProductImage;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository;
use App\Invoice\ProductImage\ProductImageService;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers ProductImageService: saveProductImage's product-relation
 * persistence, uploaded_date parsing (including the invalid-date fallback
 * to "now"), field assignment, and deleteProductImage.
 *
 * See PR #998 "Possible issues found": deleteProductImage()'s guard is
 * `str_starts_with($realTargetPath, $realFilePath)` — backwards. It checks
 * whether the (short) target *directory* starts with the (longer) *file*
 * path, which is only ever true when they're equal, so for any real file
 * inside the folder the condition is false and FileHelper::unlink() is
 * never called — the file is never actually deleted from disk. The
 * `$this->repository->delete($model)` call sits outside that ternary, so
 * the DB row is still removed regardless, leaving an orphaned file behind.
 */
#[Test]
final class ProductImageServiceTest
{
    private function makeService(
        ?ProductImageRepository $repository = null,
        ?PR $pR = null,
    ): ProductImageService {
        $repository = $repository ?? m::mock(ProductImageRepository::class);
        $pR = $pR ?? m::mock(PR::class);
        return new ProductImageService($repository, $pR);
    }

    public function saveProductImageSetsAllFieldsAndSaves(): void
    {
        $model = new ProductImage();
        $array = [
            'product_id' => 4,
            'file_name_original' => 'photo.png',
            'file_name_new' => 'a1b2c3.png',
            'description' => 'Front view',
            'uploaded_date' => '2026-05-10',
        ];

        $product = m::mock(Product::class);
        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e */
        $e = $pR->shouldReceive('repoProductquery');
        $e->once()->with(4)->andReturn($product);

        $repository = m::mock(ProductImageRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $pR);
        $service->saveProductImage($model, $array);

        Assert::same($product, $model->getProduct());
        Assert::same(4, $model->reqProductId());
        Assert::same('photo.png', $model->getFileNameOriginal());
        Assert::same('a1b2c3.png', $model->getFileNameNew());
        Assert::same('Front view', $model->getDescription());
        Assert::same('2026-05-10', $model->getUploadedDate()->format('Y-m-d'));
    }

    public function saveProductImageFallsBackToNowWhenDateInvalid(): void
    {
        $model = new ProductImage();
        $array = ['uploaded_date' => 'not-a-date'];

        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');

        $repository = m::mock(ProductImageRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $pR);
        $service->saveProductImage($model, $array);

        Assert::null($model->getProduct());
        Assert::same((new \DateTimeImmutable())->format('Y-m-d'), $model->getUploadedDate()->format('Y-m-d'));
    }

    public function deleteProductImageCallsRepositoryDeleteButNeverDeletesFileFromDisk(): void
    {
        $tempDir = sys_get_temp_dir() . '/invoice-product-images-' . uniqid();
        mkdir($tempDir, 0777, true);
        $filePath = $tempDir . '/a1b2c3.png';
        file_put_contents($filePath, 'fake-image-bytes');

        try {
            $model = new ProductImage();
            $model->setFileNameNew('a1b2c3.png');

            $aliases = new Aliases(['@public_product_images' => $tempDir]);
            $sR = m::mock(SettingRepository::class);
            /** @var \Mockery\Expectation $e */
            $e = $sR->shouldReceive('getProductimagesFilesFolderAliases');
            $e->once()->andReturn($aliases);

            /** @var ProductImageRepository&m\MockInterface $repository */
            $repository = m::mock(ProductImageRepository::class);
            /** @var \Mockery\Expectation $e2 */
            $e2 = $repository->expects('delete');
            $e2->once()->with($model);

            $service = $this->makeService($repository);
            $service->deleteProductImage($model, $sR);

            Assert::true(file_exists($filePath));
        } finally {
            @unlink($filePath);
            @rmdir($tempDir);
        }
    }

    public function deleteProductImageSkipsEntirelyWhenTargetFolderDoesNotExist(): void
    {
        $model = new ProductImage();
        $model->setFileNameNew('missing.png');

        $aliases = new Aliases(['@public_product_images' => sys_get_temp_dir() . '/invoice-does-not-exist-' . uniqid()]);
        $sR = m::mock(SettingRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $sR->shouldReceive('getProductimagesFilesFolderAliases');
        $e->once()->andReturn($aliases);

        $repository = m::mock(ProductImageRepository::class);
        $repository->shouldNotReceive('delete');

        $service = $this->makeService($repository);
        $service->deleteProductImage($model, $sR);
    }
}
