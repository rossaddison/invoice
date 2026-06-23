<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Libraries\Cryptor;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

trait SettingMiscTrait
{

    /**
     * @return array
     */
    public function numberFormats(): array
    {
        /*
         | -------------------------------------------------------------------
         | Number formats
         | -------------------------------------------------------------------
         | This is a list of available number formats that are used by
         | the settings:
         |
         | US/UK format...................... 1,000,000.00
         | European format................... 1.000.000,00
         | ISO 80000-1 with decimal point.... 1 000 000.00
         | ISO 80000-1 with decimal comma.... 1 000 000,00
         | Compact with decimal point........   1000000.00
         | Compact with decimal comma........   1000000,00
         |
         */

        return [
            'number_format_us_uk'
                => [
                    'label' => 'number.format.us.uk',
                    'decimal_point' => '.',
                    'thousands_separator' => ',',
                ],
            'number_format_european'
                => [
                    'label' => 'number.format.european',
                    'decimal_point' => ',',
                    'thousands_separator' => '.',
                ],
            'number_format_iso80k1_point'
                => [
                    'label' => 'number.format.iso80k1.point',
                    'decimal_point' => '.',
                    'thousands_separator' => ' ',
                ],
            'number_format_iso80k1_comma'
                => [
                    'label' => 'number.format.iso80k1.comma',
                    'decimal_point' => ',',
                    'thousands_separator' => ' ',
                ],
            'number_format_compact_point'
                => [
                    'label' => 'number.format.compact.point',
                    'decimal_point' => '.',
                    'thousands_separator' => '',
                ],
            'number_format_compact_comma'
                => [
                    'label' => 'number.format.compact.comma',
                    'decimal_point' => ',',
                    'thousands_separator' => '',
                ],
        ];
    }

    /**
     * @param string $period
     * @return array
     */
    public function range(string $period): array
    {
        $range = [];
        $now = new \DateTimeImmutable('now');
        $oneMonth = \DateInterval::createFromDateString('1 month');
        $twoMonths = \DateInterval::createFromDateString('2 months');
        $threeMonths = \DateInterval::createFromDateString('3 months');
        $sixMonths = \DateInterval::createFromDateString('6 months');
        $oneYear = \DateInterval::createFromDateString('12 months');
        $twoYears = \DateInterval::createFromDateString('24 months');
        switch ($period) {
            default:
            case 'this-month':
                $range['upper'] = $now;
                $range['lower'] = $this->subInterval($now, $oneMonth);
                break;
            case 'last-month':
                $range['upper'] = $this->subInterval($now, $oneMonth);
                $range['lower'] = $this->subInterval($now, $twoMonths);
                break;
            case 'this-quarter':
                $range['upper'] = $now;
                $range['lower'] = $this->subInterval($now, $threeMonths);
                break;
            case 'last-quarter':
                $range['upper'] = $this->subInterval($now, $threeMonths);
                $range['lower'] = $this->subInterval($now, $sixMonths);
                break;
            case 'this-year':
                $range['upper'] = $now;
                $range['lower'] = $this->subInterval($now, $oneYear);
                break;
            case 'last-year':
                $range['upper'] = $this->subInterval($now, $oneYear);
                $range['lower'] = $this->subInterval($now, $twoYears);
                break;
        }
        return $range;
    }

    private function subInterval(\DateTimeImmutable $now, \DateInterval|false $interval): \DateTimeImmutable
    {
        return $interval !== false ? $now->sub($interval) : $now;
    }

    /**
     * @param int $code
     * @return string
     */
    public function codeToMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the'
            . ' upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the'
            . ' MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
            default => 'Unknown upload error',
        };
    }

    /**
     * Related logic: see ..\src\ViewInjection\LayoutViewInjection
     * @param bool $debugMode
     */
    public function debugMode(bool $debugMode): void
    {
        if ($debugMode) {            $count = $this->repoCount('debug_mode');
            if ($count == 1) {
                $setting = $this->withKey('debug_mode');
                if (null !== $setting) {
                    $setting->setSettingValue('1');
                    $this->save($setting);
                }
            } else {
                $setting = new Setting();
                $setting->setSettingKey('debug_mode');
                $setting->setSettingValue('1');
                $this->save($setting);
            }
        }
        if (!$debugMode) {
            $count = $this->repoCount('debug_mode');
            if ($count == 1) {
                $setting = $this->withKey('debug_mode');
                if (null !== $setting) {
                    $setting->setSettingValue('0');
                    $this->save($setting);
                }
            } else {
                $setting = new Setting();
                $setting->setSettingKey('debug_mode');
                $setting->setSettingValue('0');
                $this->save($setting);
            }
        }
    }

    /**
     * Related logic: see ..\src\ViewInjection\LayoutViewInjection
     * @param bool $signupAutomaticallyAssignClient
     */
    public function signupAutomaticallyAssignClient(
                                    bool $signupAutomaticallyAssignClient): void
    {
        if ($signupAutomaticallyAssignClient == true) {
            $count = $this->repoCount('signup_automatically_assign_client');
            if ($count == 1) {
                $setting = $this->withKey('signup_automatically_assign_client');
                if (null !== $setting) {
                    $setting->setSettingValue('1');
                    $this->save($setting);
                }
            } else {
                $setting = new Setting();
                $setting->setSettingKey('signup_automatically_assign_client');
                $setting->setSettingValue('1');
                $this->save($setting);
            }
        }
        if (!$signupAutomaticallyAssignClient) {
            $count = $this->repoCount('signup_automatically_assign_client');
            if ($count == 1) {
                $setting = $this->withKey('signup_automatically_assign_client');
                if (null !== $setting) {
                    $setting->setSettingValue('0');
                    $this->save($setting);
                }
            } else {
                $setting = new Setting();
                $setting->setSettingKey('signup_automatically_assign_client');
                $setting->setSettingValue('0');
                $this->save($setting);
            }
        }
    }

    /**
     * @return string
     */
    public function isDebugMode(int $key): string
    {
        // If the default has changed from true to false in the
        //  layout/main.php return false otherwise stick to default
        // Do not return the file location if not in debug mode
        if ($this->getSetting('debug_mode') === '0') {
            return '';
        }
        // Return the file location if in debug_mode
        if ($this->getSetting('debug_mode') === '1') {
            return $this->debugModeFileLocation($key);
        }
        return '';
    }

    /**
     * @param int $key
     * @return string
     */
    public function debugModeFileLocation(int $key): string
    {
        $layout = '..resources/views/layout/';
        $common_route = '..resources/views/invoice/';
        $array = [//0
            $layout . 'invoice',
            //1
            $common_route . 'inv/view',
            //2
            $common_route . 'invitem/_item_form_product',
            //3
            $common_route . 'invitem/_item_form_task',
            //4
            $common_route . 'inv/view_custom_fields',
            //5
            $common_route . 'inv/partial_inv_attachments',
            //6
            $common_route . 'inv/partial_inv_delivery_location',
            //7
            $common_route . 'inv/partial_item_table',
            //8
            $common_route . 'product/views/partial_product_image',
            //9
            $common_route . 'product/views/partial_product_gallery',
            //10
            $layout . 'quote',
            //11
            $common_route . 'quote/view',
            //12
            $common_route . 'quoteitem/_item_edit_form',
            //13
            $common_route . 'quoteitem/_item_form_product',
            //14
            $common_route . 'quoteitem/_item_form_task',
            //15
            $common_route . 'invitem/_item_edit_product',
            //16
            $common_route . 'inv/modal_message_layout',
            //17
            $common_route . 'inv/modal_message',
            //18
            $common_route . 'inv/modal_message_action',
            //19
            $common_route . 'quoteitem/partial_item_table',
            //20
            $common_route. 'salesorder/partial_item_table',
        ];
        return $array[$key];
    }

    /**
     * @return string
     */
    public function publicLogo(): string
    {
        if (!empty($this->getSetting('public_logo_png_prefix'))) {
            return $this->getSetting('public_logo_png_prefix');
        }
        // If no logo has been set use the default file 'logo.png' provided
        //  in the public directory
        $logo_prefix = new Setting();
        $logo_prefix->setSettingKey('public_logo_png_prefix');
        $logo_prefix->setSettingValue('logo');
        $this->save($logo_prefix);

        return $this->getSetting('public_logo_png_prefix');
    }

    /**
     * @param OffsetPaginator $paginator
     * @param TranslatorInterface $translator
     * @param int $max
     * @param string $entity_plural
     * @param string $status_string
     * @return string
     */
    public function gridSummary(
        OffsetPaginator $paginator, TranslatorInterface $translator,
                int $max, string $entity_plural, string $status_string): string
    {
        $pageSize = $paginator->getCurrentPageSize();
        if ($pageSize > 0) {
            return (string) Html::tag(
                'b',
                sprintf($translator->translate('showing.of')
                      . $translator->translate('max')
                      . ' ' . (string) $max . ' '
                      . $entity_plural
                      . $translator->translate('per.page.total')
                      . $entity_plural . ': '
                      . (string) $paginator->getTotalItems(), $pageSize,
                                            $paginator->getTotalItems()) . ' ',
                ['class' => 'card-header bg-warning text-black'],
            ) . (!empty($status_string)
            ? (string) Html::tag('b', $status_string,
                    ['class' => 'card-header bg-info text-black']) : '');
        }
        return '';
    }

    /**
     * @param string $input
     * @return string
     */
    public function snakeToCamel(string $input): string
    {
        return lcfirst(ucwords(str_replace('_', ' ', $input)));
    }

    /**
     * Related logic:
     * resources/views/invoice/setting/views/partial_settings_online_payment
     * @param string $data
     * @return mixed $decrypted
     */
    public function decode(string $data): mixed
    {
        $key = '';
        if (empty($data)) {
            return '';
        }
        
        if (preg_match('/^base64:(.*)$/', $this->decryptKey, $matches)) {
            $key = base64_decode($matches[1]);
        }
        
        /** @var mixed $decrypted */
        return Cryptor::Decrypt($data, $key);
    }

    /**
     * @param string $data
     * @return mixed $encrypted
     */
    public function encode(string $data): mixed
    {
        $key = '';
        if (preg_match('/^base64:(.*)$/', $this->decryptKey, $matches)) {
            $key = base64_decode($matches[1]);
        }
        
        /** @var mixed $encrypted */
        return Cryptor::Encrypt($data, $key);
    }
}
