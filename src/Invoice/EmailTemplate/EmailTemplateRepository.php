<?php
declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\Entity\EmailTemplate;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

/**
 * @template TEntity of EmailTemplate
 * @extends Select\Repository<TEntity>
 */
final class EmailTemplateRepository extends Select\Repository
{
    private EntityWriter $entityWriter;
    
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|EmailTemplate|null $emailtemplate
     * @throws Throwable 
     * @return void
     */
    public function save(array|EmailTemplate|null $emailtemplate): void
    {
        $this->entityWriter->write([$emailtemplate]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|EmailTemplate|null $emailtemplate
     * @throws Throwable 
     * @return void
     */
    public function delete(array|EmailTemplate|null $emailtemplate): void
    {
        $this->entityWriter->delete([$emailtemplate]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'email_template_title', 'email_template_from_name', 'email_template_from_email'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * 
     * @param string $email_template_id
     * @return int
     */
    public function repoEmailTemplateCount(string $email_template_id) : int {
        $count = $this
            ->select()
            ->where(['id' => $email_template_id])
            ->count();
        return  $count;        
    }   
    
    /**
     * @return EmailTemplate|null
     *
     * @psalm-return TEntity|null
     */
    public function repoEmailTemplatequery(string $email_template_id):EmailTemplate|null
    {
        $query = $this
            ->select()
            ->where(['id' => $email_template_id]);
        return  $query->fetchOne() ?: null;        
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
    
    /**
     * 
     * @param SettingRepository $setting
     * @return SettingRepository
     */
    public static function getSettings(SettingRepository $setting): SettingRepository
    {
        $setting->load_settings();
        return $setting;
    }
    
    // resources/views/invoice/template/public||pdf
    /**
     * 
     * @param string $pdf_or_public
     * @return array
     */
    public function get_invoice_templates(string $pdf_or_public) : array
    {
        $pdf_template_directory = dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/template/invoice/pdf'; 
        $public_template_directory = dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/template/invoice/public';
        $templates = [];
        $php_only = (new PathMatcher())
        ->doNotCheckFilesystem()
        ->only('*.php');
        if ($pdf_or_public === 'pdf') {
              $templates = FileHelper::findFiles($pdf_template_directory, [
                                        'filter' => $php_only,
                                        'recursive' => false,
                                ]);
        } elseif ($pdf_or_public === 'public') {
              $templates = FileHelper::findFiles($public_template_directory, [
                                        'filter' => $php_only,
                                        'recursive' => false,
                                ]);
        }
        if (!empty($templates)) {
            $extension_remove = $this->remove_extension($templates);
            $templates = $this->remove_path($extension_remove);
            return $templates;
        }
        return $templates;
    }
    
    /**
     * @psalm-param 'pdf' $type
     */
    public function get_quote_templates(string $pdf_or_public) : array
    {
        $pdf_template_directory = dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/template/quote/pdf'; 
        $public_template_directory = dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/template/quote/public';
        $templates = [];
        $pdf_only = (new PathMatcher())
        ->doNotCheckFilesystem()
        ->only('*.pdf');
        if ($pdf_or_public === 'pdf') {
            $templates = FileHelper::findFiles($pdf_template_directory, [
                                        'filter' => $pdf_only,
                                        'recursive' => false,
                                ]);
        } elseif ($pdf_or_public === 'public') {
            $templates = FileHelper::findFiles($public_template_directory, [
                                        'filter' => $pdf_only,
                                        'recursive' => false,
                                ]);
        }
        if (!empty($templates)) {
            $extension_remove = $this->remove_extension($templates);
            $templates = $this->remove_path($extension_remove);
            return $templates;
        }
        return $templates;
    }
    
    /**
     * 
     * @param array $files
     * @return array
     */
    private function remove_extension(array $files) : array
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
    
    /**
     * 
     * @param array $files
     * @return array
     */
    private function remove_path(array $files) : array
    {
        //https://stackoverflow.com/questions/1418193/how-do-i-get-a-file-name-from-a-full-path-with-php
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
