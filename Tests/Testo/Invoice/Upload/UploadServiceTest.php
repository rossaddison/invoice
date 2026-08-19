<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Upload;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Upload\Upload;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Upload\UploadRepository;
use App\Invoice\Upload\UploadService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers UploadService: saveUpload's client-relation persistence,
 * uploaded_date parsing (skipping the assignment on an invalid date, unlike
 * the sibling ProductImageService which falls back to "now"), field
 * assignment, and deleteUpload.
 *
 * See PR #998 "Possible issues found": deleteUpload() has the exact same
 * backwards guard as ProductImageService::deleteProductImage() —
 * `str_starts_with($realTargetPath, $realFilePath)` checks whether the
 * (short) target *directory* starts with the (longer) *file* path, which
 * is only ever true when they're identical strings. For any real file
 * inside the folder this is false, so FileHelper::unlink() never runs —
 * the uploaded file is never actually deleted from disk, while
 * `$this->repository->delete($model)` (outside the ternary) still removes
 * the DB row, leaving an orphaned file behind on every upload deletion.
 */
#[Test]
final class UploadServiceTest
{
    private function makeService(
        ?UploadRepository $repository = null,
        ?CR $cR = null,
    ): UploadService {
        /** @var UploadRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(UploadRepository::class);
        /** @var CR&m\MockInterface $cR */
        $cR = $cR ?? m::mock(CR::class);
        return new UploadService($repository, $cR);
    }

    public function saveUploadSetsAllFieldsAndSaves(): void
    {
        $model = new Upload();
        $array = [
            'client_id' => 3,
            'url_key' => 'abc123',
            'file_name_original' => 'invoice.pdf',
            'file_name_new' => 'x1y2z3.pdf',
            'uploaded_date' => '2026-05-10',
            'description' => 'Signed contract',
        ];

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        $reqIdExpectation = $client->shouldReceive('reqId');
        $reqIdExpectation->andReturn(3);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(3)->andReturn($client);

        /** @var UploadRepository&m\MockInterface $repository */
        $repository = m::mock(UploadRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveUpload($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same(3, $model->reqClientId());
        Assert::same('abc123', $model->getUrlKey());
        Assert::same('invoice.pdf', $model->getFileNameOriginal());
        Assert::same('x1y2z3.pdf', $model->getFileNameNew());
        Assert::same('Signed contract', $model->getDescription());
        Assert::same('2026-05-10', $model->getUploadedDate()->format('Y-m-d'));
    }

    public function saveUploadKeepsConstructorDefaultDateWhenInvalid(): void
    {
        $model = new Upload();
        $defaultDate = $model->getUploadedDate()->format('Y-m-d');
        $array = ['client_id' => 4, 'uploaded_date' => 'not-a-date'];

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        $reqIdExpectation = $client->shouldReceive('reqId');
        $reqIdExpectation->andReturn(4);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(4)->andReturn($client);

        /** @var UploadRepository&m\MockInterface $repository */
        $repository = m::mock(UploadRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveUpload($model, $array);

        Assert::same($defaultDate, $model->getUploadedDate()->format('Y-m-d'));
    }

    public function deleteUploadCallsRepositoryDeleteButNeverDeletesFileFromDisk(): void
    {
        $tempDir = sys_get_temp_dir() . '/invoice-uploads-' . uniqid();
        mkdir($tempDir, 0777, true);
        $filePath = $tempDir . '/x1y2z3.pdf';
        file_put_contents($filePath, 'fake-pdf-bytes');

        try {
            $model = new Upload();
            $model->setFileNameNew('x1y2z3.pdf');

            $aliases = new Aliases(['@customer_files' => $tempDir]);
            /** @var SettingRepository&m\MockInterface $sR */
            $sR = m::mock(SettingRepository::class);
            $e = $sR->shouldReceive('getCustomerFilesFolderAliases');
            $e->once()->andReturn($aliases);

            /** @var UploadRepository&m\MockInterface $repository */
            $repository = m::mock(UploadRepository::class);
            $e2 = $repository->expects('delete');
            $e2->once()->with($model);

            $service = $this->makeService($repository);
            $service->deleteUpload($model, $sR);

            Assert::true(file_exists($filePath));
        } finally {
            @unlink($filePath);
            @rmdir($tempDir);
        }
    }

    public function deleteUploadSkipsEntirelyWhenTargetFolderDoesNotExist(): void
    {
        $model = new Upload();
        $model->setFileNameNew('missing.pdf');

        $aliases = new Aliases(['@customer_files' => sys_get_temp_dir() . '/invoice-does-not-exist-' . uniqid()]);
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $e = $sR->shouldReceive('getCustomerFilesFolderAliases');
        $e->once()->andReturn($aliases);

        /** @var UploadRepository&m\MockInterface $repository */
        $repository = m::mock(UploadRepository::class);
        $repository->shouldNotReceive('delete');

        $service = $this->makeService($repository);
        $service->deleteUpload($model, $sR);
    }
}
