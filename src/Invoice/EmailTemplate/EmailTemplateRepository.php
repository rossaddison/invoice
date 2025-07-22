<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;
use App\Invoice\Setting\SettingRepository;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

/**
 * @template TEntity of EmailTemplate
 *
 * @extends Select\Repository<TEntity>
 */
final class EmailTemplateRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

        return $this->prepareDataReader($query);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|EmailTemplate|null $emailtemplate): void
    {
        $this->entityWriter->write([$emailtemplate]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|EmailTemplate|null $emailtemplate): void
    {
        $this->entityWriter->delete([$emailtemplate]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'email_template_title', 'email_template_from_name', 'email_template_from_email'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoEmailTemplateCount(string $email_template_id): int
    {
        return $this
            ->select()
            ->where(['id' => $email_template_id])
            ->count();
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoEmailTemplatequery(string $email_template_id): ?EmailTemplate
    {
        $query = $this
            ->select()
            ->where(['id' => $email_template_id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoEmailTemplateType(string $email_template_type): EntityReader
    {
        $query = $this
            ->select()
            ->where(['email_template_type' => $email_template_type]);

        return $this->prepareDataReader($query);
    }

    public static function getSettings(SettingRepository $setting): SettingRepository
    {
        $setting->load_settings();

        return $setting;
    }

    // resources/views/invoice/template/public||pdf
    public function get_invoice_templates(string $pdf_or_public): array
    {
        $pdf_template_directory    = dirname(__DIR__, 3).'/resources/views/invoice/template/invoice/pdf';
        $public_template_directory = dirname(__DIR__, 3).'/resources/views/invoice/template/invoice/public';
        $templates                 = [];
        $php_only                  = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->only('*.php');
        if ('pdf' === $pdf_or_public) {
            $templates = FileHelper::findFiles($pdf_template_directory, [
                'filter'    => $php_only,
                'recursive' => false,
            ]);
        } elseif ('public' === $pdf_or_public) {
            $templates = FileHelper::findFiles($public_template_directory, [
                'filter'    => $php_only,
                'recursive' => false,
            ]);
        }
        if (!empty($templates)) {
            $extension_remove = $this->remove_extension($templates);

            return $this->remove_path($extension_remove);
        }

        return $templates;
    }

    /**
     * @psalm-param 'pdf' $type
     */
    public function get_quote_templates(string $pdf_or_public): array
    {
        $pdf_template_directory    = dirname(__DIR__, 3).'/resources/views/invoice/template/quote/pdf';
        $public_template_directory = dirname(__DIR__, 3).'/resources/views/invoice/template/quote/public';
        $templates                 = [];
        $pdf_only                  = (new PathMatcher())
            ->doNotCheckFilesystem()
            ->only('*.pdf');
        if ('pdf' === $pdf_or_public) {
            $templates = FileHelper::findFiles($pdf_template_directory, [
                'filter'    => $pdf_only,
                'recursive' => false,
            ]);
        } elseif ('public' === $pdf_or_public) {
            $templates = FileHelper::findFiles($public_template_directory, [
                'filter'    => $pdf_only,
                'recursive' => false,
            ]);
        }
        if (!empty($templates)) {
            $extension_remove = $this->remove_extension($templates);

            return $this->remove_path($extension_remove);
        }

        return $templates;
    }

    private function remove_extension(array $files): array
    {
        /**
         * @var string $key
         * @var string $file
         */
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }

        return $files;
    }

    private function remove_path(array $files): array
    {
        // https://stackoverflow.com/questions/1418193/how-do-i-get-a-file-name-from-a-full-path-with-php
        /**
         * @var string $key
         * @var string $file
         */
        foreach ($files as $key => $file) {
            $files[$key] = basename($file);
        }

        return $files;
    }
}
