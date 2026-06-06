<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Setting\SettingRepository as SRepo;

/**
 * Date-format configuration methods shared by DateHelper.
 *
 * Extracted to keep DateHelper under the S1448 method-count threshold.
 * All methods here depend only on the 'date_format' / 'first_day_of_week'
 * settings and are free of date arithmetic or domain logic.
 */
abstract class AbstractDateHelper
{
    public function __construct(protected readonly SRepo $s) {}

    public function style(): string
    {
        $this->s->loadSettings();
        return $this->dateFormats()[$this->s->getSetting('date_format')]['setting'];
    }

    public function datepickerDateFormat(): string
    {
        $this->s->loadSettings();
        return $this->dateFormats()[$this->s->getSetting('date_format')]['datepicker-dateFormat'] ?? 'd-m-Y';
    }

    public function datepickerFirstDay(): string
    {
        $this->s->loadSettings();
        return $this->dateFormats()[$this->s->getSetting('date_format')]['datepicker-firstDay'] ?? 'monday';
    }

    public function display(): string
    {
        $this->s->loadSettings();
        return $this->dateFormats()[$this->s->getSetting('date_format')]['display'] ?? 'dd/mm/yyyy';
    }

    public function separator(): string
    {
        $this->s->loadSettings();
        return $this->dateFormats()[$this->s->getSetting('date_format')]['separator'];
    }

    /**
     * @return array{'d/m/Y': array{setting: 'd/m/Y', 'datepicker-dateFormat': 'dd/mm/yy', 'datepicker-firstDay': string, display: 'dd/mm/yyyy', separator: '/'}, 'd-m-Y': array{setting: 'd-m-Y', 'datepicker-dateFormat': 'dd-mm-yy', 'datepicker-firstDay': string, display: 'dd-mm-yyyy', separator: '-'}, 'd-M-Y': array{setting: 'd-M-Y', 'datepicker-dateFormat': 'dd-M-yy', 'datepicker-firstDay': string, display: 'dd-M-yyyy', separator: '-'}, 'd.m.Y': array{setting: 'd.m.Y', 'datepicker-dateFormat': 'dd.mm.yy', 'datepicker-firstDay': string, display: 'dd.mm.yyyy', separator: '.'}, 'j.n.Y': array{setting: 'j.n.Y', 'datepicker-dateFormat': 'd.m.yy', 'datepicker-firstDay': string, display: 'd.m.yyyy', separator: '.'}, 'd M,Y': array{setting: 'd M,Y', 'datepicker-dateFormat': 'dd M,yy', 'datepicker-firstDay': string, display: 'dd M,yyyy', separator: ','}, 'm/d/Y': array{setting: 'm/d/Y', 'datepicker-dateFormat': 'mm/dd/yy', 'datepicker-firstDay': string, display: 'mm/dd/yyyy', separator: '/'}, 'm-d-Y': array{setting: 'm-d-Y', 'datepicker-dateFormat': 'mm-dd-yy', 'datepicker-firstDay': string, display: 'mm-dd-yyyy', separator: '-'}, 'm.d.Y': array{setting: 'm.d.Y', 'datepicker-dateFormat': 'mm.dd.yy', 'datepicker-firstDay': string, display: 'mm.dd.yyyy', separator: '.'}, 'Y/m/d': array{setting: 'Y/m/d', 'datepicker-dateFormat': 'yy/mm/dd', 'datepicker-firstDay': string, display: 'yyyy/mm/dd', separator: '/'}, 'Y-m-d': array{setting: 'Y-m-d', 'datepicker-dateFormat': 'yy-mm-dd', 'datepicker-firstDay': string, display: 'yyyy-mm-dd', separator: '-'}, 'Y-m-d H:i:s': array{setting: 'Y-m-d H:i:s', 'datepicker-dateFormat': 'yy-mm-dd', 'datepicker-firstDay': string, display: 'yyyy-mm-dd', separator: '-'}, 'Y.m.d': array{setting: 'Y.m.d', 'datepicker-dateFormat': 'yy.mm.dd', 'datepicker-firstDay': string, display: 'yyyy.mm.dd', separator: '.'}}
     */
    public function dateFormats(): array
    {
        $firstDay = $this->s->getSetting('first_day_of_week');
        return [
            'd/m/Y'       => ['setting' => 'd/m/Y',        'datepicker-dateFormat' => 'dd/mm/yy',  'datepicker-firstDay' => $firstDay, 'display' => 'dd/mm/yyyy',  'separator' => '/'],
            'd-m-Y'       => ['setting' => 'd-m-Y',        'datepicker-dateFormat' => 'dd-mm-yy',  'datepicker-firstDay' => $firstDay, 'display' => 'dd-mm-yyyy',  'separator' => '-'],
            'd-M-Y'       => ['setting' => 'd-M-Y',        'datepicker-dateFormat' => 'dd-M-yy',   'datepicker-firstDay' => $firstDay, 'display' => 'dd-M-yyyy',   'separator' => '-'],
            'd.m.Y'       => ['setting' => 'd.m.Y',        'datepicker-dateFormat' => 'dd.mm.yy',  'datepicker-firstDay' => $firstDay, 'display' => 'dd.mm.yyyy',  'separator' => '.'],
            'j.n.Y'       => ['setting' => 'j.n.Y',        'datepicker-dateFormat' => 'd.m.yy',    'datepicker-firstDay' => $firstDay, 'display' => 'd.m.yyyy',    'separator' => '.'],
            'd M,Y'       => ['setting' => 'd M,Y',        'datepicker-dateFormat' => 'dd M,yy',   'datepicker-firstDay' => $firstDay, 'display' => 'dd M,yyyy',   'separator' => ','],
            'm/d/Y'       => ['setting' => 'm/d/Y',        'datepicker-dateFormat' => 'mm/dd/yy',  'datepicker-firstDay' => $firstDay, 'display' => 'mm/dd/yyyy',  'separator' => '/'],
            'm-d-Y'       => ['setting' => 'm-d-Y',        'datepicker-dateFormat' => 'mm-dd-yy',  'datepicker-firstDay' => $firstDay, 'display' => 'mm-dd-yyyy',  'separator' => '-'],
            'm.d.Y'       => ['setting' => 'm.d.Y',        'datepicker-dateFormat' => 'mm.dd.yy',  'datepicker-firstDay' => $firstDay, 'display' => 'mm.dd.yyyy',  'separator' => '.'],
            'Y/m/d'       => ['setting' => 'Y/m/d',        'datepicker-dateFormat' => 'yy/mm/dd',  'datepicker-firstDay' => $firstDay, 'display' => 'yyyy/mm/dd',  'separator' => '/'],
            'Y-m-d'       => ['setting' => 'Y-m-d',        'datepicker-dateFormat' => 'yy-mm-dd',  'datepicker-firstDay' => $firstDay, 'display' => 'yyyy-mm-dd',  'separator' => '-'],
            'Y-m-d H:i:s' => ['setting' => 'Y-m-d H:i:s', 'datepicker-dateFormat' => 'yy-mm-dd',  'datepicker-firstDay' => $firstDay, 'display' => 'yyyy-mm-dd',  'separator' => '-'],
            'Y.m.d'       => ['setting' => 'Y.m.d',        'datepicker-dateFormat' => 'yy.mm.dd',  'datepicker-firstDay' => $firstDay, 'display' => 'yyyy.mm.dd',  'separator' => '.'],
        ];
    }
}
