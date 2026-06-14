<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\Delivery\DeliveryRepository as delRepo;
use DateTime;
use DateInterval;

class DateHelper extends AbstractDateHelper
{
    /**
     * @param \DateTimeImmutable $datetimeimmutable
     * @return string
     */
    public function getTimeFromDateTime(\DateTimeImmutable $datetimeimmutable): string
    {
        return DateTime::createFromImmutable($datetimeimmutable)->format('H:m:s');
    }

    /**
     * @param \DateTimeImmutable $datetimeimmutable
     * @return string
     */
    public function getYearFromDateTime(\DateTimeImmutable $datetimeimmutable): string
    {
        return DateTime::createFromImmutable($datetimeimmutable)->format('Y');
    }

    /**
     * @param \DateTimeImmutable $datetimeimmutable
     * @return string
     */
    public function dateFromMysql(\DateTimeImmutable $datetimeimmutable): string
    {
        return DateTime::createFromImmutable($datetimeimmutable)->format($this->style());
    }

    /**
     * @param \DateTimeImmutable $datetimeimmutable
     * @return DateTime
     */
    public function dateFromMysqlWithoutStyle(\DateTimeImmutable $datetimeimmutable): DateTime
    {
        return DateTime::createFromImmutable($datetimeimmutable);
    }

    /**
     * @param string $date
     *
     * @return string
     */
    public function dateToMysql(string $date): string
    {
        $mydate = DateTime::createFromFormat($this->s->getSetting('date_format'), $date);
        return $mydate->format('Y-m-d');
    }

    /**
     * @param string $y_m_d
     * @return \DateTimeImmutable
     */
    public function ymdToImmutable(string $y_m_d): \DateTimeImmutable
    {
        $year = (int) substr($y_m_d, 0, 4);
        $month = (int) substr($y_m_d, 6, 2);
        $day = (int) substr($y_m_d, 9, 2);
        return (new \DateTimeImmutable())->setDate($year, $month, $day);
    }

    // Used in ReportController/sales_by_year_index

    /**
     * @return \DateTimeImmutable
     */
    public function taxYearToImmutable(): \DateTimeImmutable
    {
        $year = $this->s->getSetting('this_tax_year_from_date_year') ?: (new \DateTimeImmutable('now'))->format('Y');
        $month = $this->s->getSetting('this_tax_year_from_date_month') ?: (new \DateTimeImmutable('now'))->format('m');
        $day = $this->s->getSetting('this_tax_year_from_date_day') ?: (new \DateTimeImmutable('now'))->format('d');
        return (new \DateTimeImmutable())->setDate((int) $year, (int) $month, (int) $day);
    }

    public function now01(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(date('Y-m-01'));
    }

    public function nowT(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(date('Y-m-01'));
    }

    /**
     * @param \DateTimeImmutable $datetimeimmutable
     * @return string
     */
    public function dateForPaymentForm(\DateTimeImmutable $datetimeimmutable): string
    {
        return DateTime::createFromImmutable($datetimeimmutable)->format($this->style());
    }

    /**
     * @param string $date
     * @return bool
     */
    public function isDate(string $date): bool
    {
        $d = DateTime::createFromFormat($this->style(), $date);
        return $d && $d->format($this->style()) == $date;
    }

    /**
     * @param string $string_date
     */
    public function datetimeZoneStyle(string $string_date): DateTime|false
    {
        $datetime = new DateTime();
        $datetime->setTimezone(new \DateTimeZone($this->s->getSetting('time_zone') ?: 'Europe/London'));
        $datetime->format($this->style());
        $date = $this->dateToMysql($string_date);
        // Prevent Failed to parse time string at position 0 error
        $str_replace = str_replace($this->separator(), '-', $date);
        return $datetime->modify($str_replace);
    }

    /**
     * @param \DateTimeImmutable $date
     * @param string $increment
     * @return string
     */
    public function incrementUserDate(\DateTimeImmutable $date, string $increment): string
    {
        $this->s->loadSettings();

        $mysql_date = $this->dateFromMysql($date);

        $new_date = new DateTime($mysql_date);
        $new_date->add(new DateInterval('P' . $increment));

        return $new_date->format($this->s->getSetting('date_format'));
    }

    /**
     * @param \DateTimeImmutable $date
     * @param string $increment
     * @return string
     */
    public function addToImmutable(\DateTimeImmutable $date, string $increment): string
    {
        return $date->add(new DateInterval('P' . $increment))
                    ->format('Y-m-d');
    }

    /**
     * @param string $date
     * @param string $increment
     * @return string
     */
    public function incrementDate(string $date, string $increment): string
    {
        $new_date = new DateTime($date);
        $new_date->add(new DateInterval('P' . $increment));
        return $new_date->format('Y-m-d');
    }

    /**
     * @param string $date
     * @param string $increment
     * @return \DateTime
     */
    public function incrementDateStringToDateTime(string $date, string $increment): DateTime
    {
        $new_date = new DateTime($date);
        $new_date->add(new DateInterval('P' . $increment));
        return $new_date;
    }

    /**
     * @param mixed $input
     * @return DateTime|false|string|null
     */
    public function getOrSetWithStyle(mixed $input): string|false|DateTime|null
    {
        /** @var \DateTimeImmutable|mixed|null $date */
        $date = $input ?? null;
        // Get with style
        if ($date instanceof \DateTimeImmutable) {
            $return_date = $this->dateFromMysql($date);
            // Set with style
        } elseif (null !== $date) {
            $return_date = $this->datetimeZoneStyle((string) $date);
        } else {
            $return_date = null;
        }
        return $return_date;
    }

    /**
     * If a delivery period has been setup for the invoice, use it instead of the month's beginning and end date
     * Related logic: see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/
     * @param Inv $invoice
     * @param DateTime $datetime
     * @param delRepo $delRepo
     * @return array
     */
    public function invoicePeriodStartEnd(Inv $invoice, DateTime $datetime, delRepo $delRepo): array
    {
        // If invoice's Delivery period setup => use it and not beginning and end of month values
        $deliveryId = $invoice->getDeliveryId();
        if ($deliveryId > 0) {
            $delivery = $delRepo->repoDeliveryquery($deliveryId);
            if (null !== $delivery) {
                $deliveryStartDate = $delivery->getStartDate();
                $deliveryEndDate = $delivery->getEndDate();
                return [
                    'StartDate' => null !== $deliveryStartDate ? $deliveryStartDate->format('Y-m-d') : '',
                    'EndDate' => null !== $deliveryEndDate ? $deliveryEndDate->format('Y-m-d') : '',
                ];
            }
            return [
                'StartDate' => $datetime->format('Y-m-01'),
                'EndDate' => $datetime->format('Y-m-t'),
            ];
        }
        return [
            'StartDate' => $datetime->format('Y-m-01'),
            'EndDate' => $datetime->format('Y-m-t'),
        ];
    }
}
