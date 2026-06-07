<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * @var string $alert
 * @var string $csrf
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

$applyRoute = 'entry/apply-tax-year-locale';

/**
 * Locale list: [label, month, day, notes]
 * Grouped: non-calendar-year start dates first, then calendar-year (1 Jan) countries.
 * @var array<int, array{0: string, 1: string, 2: string, 3: string}> $nonCalendar
 */
$nonCalendar = [
    ['United Kingdom',      '04', '06', 'UK tax year starts 6 April (historic tax-farming quirk)'],
    ['Australia',           '07', '01', 'Australian financial year 1 July – 30 June'],
    ['New Zealand',         '04', '01', 'NZ tax year 1 April – 31 March'],
    ['India',               '04', '01', 'Indian fiscal year 1 April – 31 March'],
    ['Japan',               '04', '01', 'Japanese fiscal year 1 April – 31 March'],
    ['Singapore',           '04', '01', 'Singapore fiscal year commonly 1 April – 31 March'],
    ['Hong Kong',           '04', '01', 'HK government financial year 1 April – 31 March'],
    ['South Africa',        '03', '01', 'South African tax year 1 March – 28/29 February'],
    ['Bangladesh',          '07', '01', 'Bangladesh fiscal year 1 July – 30 June'],
    ['Pakistan',            '07', '01', 'Pakistan fiscal year 1 July – 30 June'],
    ['Egypt',               '07', '01', 'Egypt fiscal year 1 July – 30 June'],
    ['Kenya',               '07', '01', 'Kenya fiscal year 1 July – 30 June'],
    ['Ethiopia',            '07', '01', 'Ethiopia fiscal year 1 July – 30 June (Ethiopian calendar EFY)'],
    ['Iran',                '04', '01', 'Iranian fiscal year starts approx. 1 April (Farvardin 1)'],
    ['Myanmar',             '10', '01', 'Myanmar fiscal year 1 October – 30 September'],
    ['Thailand (govt)',     '10', '01', 'Thai government fiscal year 1 October – 30 September'],
];

/**
 * @var array<int, array{0: string, 1: string, 2: string, 3: string}> $calendarYear
 */
$calendarYear = [
    ['United States',       '01', '01', 'Federal tax year equals calendar year (individuals & most corps)'],
    ['Canada',              '01', '01', 'Canadian fiscal year equals calendar year for individuals'],
    ['Germany',             '01', '01', 'German tax year equals calendar year'],
    ['France',              '01', '01', 'French fiscal year equals calendar year'],
    ['Italy',               '01', '01', 'Italian fiscal year equals calendar year'],
    ['Spain',               '01', '01', 'Spanish fiscal year equals calendar year'],
    ['Netherlands',         '01', '01', 'Dutch fiscal year equals calendar year'],
    ['Belgium',             '01', '01', 'Belgian fiscal year equals calendar year'],
    ['Sweden',              '01', '01', 'Swedish fiscal year equals calendar year'],
    ['Norway',              '01', '01', 'Norwegian fiscal year equals calendar year'],
    ['Denmark',             '01', '01', 'Danish fiscal year equals calendar year'],
    ['Finland',             '01', '01', 'Finnish fiscal year equals calendar year'],
    ['Switzerland',         '01', '01', 'Swiss fiscal year equals calendar year'],
    ['Austria',             '01', '01', 'Austrian fiscal year equals calendar year'],
    ['Portugal',            '01', '01', 'Portuguese fiscal year equals calendar year'],
    ['Ireland',             '01', '01', 'Irish fiscal year equals calendar year'],
    ['Poland',              '01', '01', 'Polish fiscal year equals calendar year'],
    ['Czech Republic',      '01', '01', 'Czech fiscal year equals calendar year'],
    ['Hungary',             '01', '01', 'Hungarian fiscal year equals calendar year'],
    ['Romania',             '01', '01', 'Romanian fiscal year equals calendar year'],
    ['Russia',              '01', '01', 'Russian fiscal year equals calendar year'],
    ['Ukraine',             '01', '01', 'Ukrainian fiscal year equals calendar year'],
    ['Turkey',              '01', '01', 'Turkish fiscal year equals calendar year'],
    ['China',               '01', '01', 'Chinese fiscal year equals calendar year'],
    ['South Korea',         '01', '01', 'South Korean fiscal year equals calendar year'],
    ['Taiwan',              '01', '01', 'Taiwanese fiscal year equals calendar year'],
    ['Indonesia',           '01', '01', 'Indonesian fiscal year equals calendar year'],
    ['Malaysia',            '01', '01', 'Malaysian fiscal year equals calendar year'],
    ['Philippines',         '01', '01', 'Philippine fiscal year equals calendar year'],
    ['Vietnam',             '01', '01', 'Vietnamese fiscal year equals calendar year'],
    ['Thailand (corps)',    '01', '01', 'Thai corporate fiscal year equals calendar year'],
    ['Brazil',              '01', '01', 'Brazilian fiscal year equals calendar year'],
    ['Mexico',              '01', '01', 'Mexican fiscal year equals calendar year'],
    ['Argentina',           '01', '01', 'Argentine fiscal year equals calendar year (individuals)'],
    ['Chile',               '01', '01', 'Chilean fiscal year equals calendar year'],
    ['Colombia',            '01', '01', 'Colombian fiscal year equals calendar year'],
    ['Peru',                '01', '01', 'Peruvian fiscal year equals calendar year'],
    ['Nigeria',             '01', '01', 'Nigerian fiscal year equals calendar year (individuals)'],
    ['Ghana',               '01', '01', 'Ghanaian fiscal year equals calendar year'],
    ['Saudi Arabia',        '01', '01', 'Saudi fiscal year equals calendar year (Gregorian)'],
    ['UAE',                 '01', '01', 'UAE fiscal year equals calendar year'],
    ['Israel',              '01', '01', 'Israeli fiscal year equals calendar year'],
    ['Greece',              '01', '01', 'Greek fiscal year equals calendar year'],
];

echo $alert;

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-lg-10 offset-lg-1']);

   echo H::openTag('div', ['class' => 'card']);
    echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', 'Tax Year Start Dates by Locale');
     echo H::a('← Back to Purchase Entries',
         $urlGenerator->generate('entry/index'),
         ['class' => 'btn btn-sm btn-outline-secondary']);
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'card-body p-0']);

     echo H::openTag('div', ['class' => 'alert alert-info m-3 mb-0 small']);
      echo H::tag('strong', 'How this works: ');
      echo H::encode(
          'Clicking Apply saves the month and day of the selected locale as your tax year start date. ' .
          'The year is preserved if already set, or defaults to the current year if not. ' .
          'You can then adjust the year under Settings → Taxes.'
      );
     echo H::closeTag('div');

     // Non-calendar-year countries
     echo H::openTag('div', ['class' => 'card-header bg-warning-subtle fw-semibold mt-3 ms-3 me-3 rounded']);
      echo '📅 Non-Calendar-Year Start Dates';
     echo H::closeTag('div');

     echo H::openTag('table', ['class' => 'table table-sm table-hover mb-0']);
      echo H::openTag('thead', ['class' => 'table-secondary small']);
       echo H::openTag('tr');
        echo H::tag('th', 'Country / Region');
        echo H::tag('th', 'Tax Year Start');
        echo H::tag('th', 'Notes');
        echo H::tag('th', '');
       echo H::closeTag('tr');
      echo H::closeTag('thead');
      echo H::openTag('tbody');
      foreach ($nonCalendar as [$label, $month, $day, $notes]) {
          $monthName = date('F', mktime(0, 0, 0, (int) $month, 1));
          echo H::openTag('tr');
           echo H::tag('td', H::encode($label), ['class' => 'fw-semibold']);
           echo H::tag('td', H::encode($day . ' ' . $monthName));
           echo H::tag('td', H::encode($notes), ['class' => 'text-muted small']);
           echo H::openTag('td');
            echo (new Form())
                ->post($urlGenerator->generate($applyRoute))
                ->csrf($csrf)
                ->open();
             echo H::hiddenInput('month', $month);
             echo H::hiddenInput('day', $day);
             echo H::submitButton('Apply')
                 ->addAttributes(['class' => 'btn btn-sm btn-outline-primary']);
            echo (new Form())->close();
           echo H::closeTag('td');
          echo H::closeTag('tr');
      }
      echo H::closeTag('tbody');
     echo H::closeTag('table');

     // Calendar-year countries
     echo H::openTag('div', ['class' => 'card-header bg-light fw-semibold mt-3 ms-3 me-3 rounded']);
      echo '📅 Calendar-Year Start Dates (1 January)';
     echo H::closeTag('div');

     echo H::openTag('table', ['class' => 'table table-sm table-hover mb-0']);
      echo H::openTag('thead', ['class' => 'table-secondary small']);
       echo H::openTag('tr');
        echo H::tag('th', 'Country / Region');
        echo H::tag('th', 'Tax Year Start');
        echo H::tag('th', 'Notes');
        echo H::tag('th', '');
       echo H::closeTag('tr');
      echo H::closeTag('thead');
      echo H::openTag('tbody');
      foreach ($calendarYear as [$label, $month, $day, $notes]) {
          $monthName = date('F', mktime(0, 0, 0, (int) $month, 1));
          echo H::openTag('tr');
           echo H::tag('td', H::encode($label), ['class' => 'fw-semibold']);
           echo H::tag('td', H::encode($day . ' ' . $monthName));
           echo H::tag('td', H::encode($notes), ['class' => 'text-muted small']);
           echo H::openTag('td');
            echo (new Form())
                ->post($urlGenerator->generate($applyRoute))
                ->csrf($csrf)
                ->open();
             echo H::hiddenInput('month', $month);
             echo H::hiddenInput('day', $day);
             echo H::submitButton('Apply')
                 ->addAttributes(['class' => 'btn btn-sm btn-outline-secondary']);
            echo (new Form())->close();
           echo H::closeTag('td');
          echo H::closeTag('tr');
      }
      echo H::closeTag('tbody');
     echo H::closeTag('table');

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
