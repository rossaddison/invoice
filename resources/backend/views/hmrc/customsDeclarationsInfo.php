<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Informational page for the Customs Declarations API -- see
 * HmrcController::customsDeclarationsInfo()'s own docblock for why
 * this catalogue entry gets an explanation page rather than a live
 * test like every other one built this session: it's write-only XML
 * submissions with no minimal-effort payload HMRC would actually
 * accept, so there's nothing safe to genuinely exercise here.
 *
 * @var string $alert
 * @var string $eori
 * @var App\Invoice\Setting\SettingRepository $s
 */

echo $s->getSetting('disable_flash_messages') === '0' ? $alert : '';

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-10 offset-md-1']);

   echo H::openTag('div', ['class' => 'card mb-3']);
    echo H::openTag('div', [
        'class' => 'card-header d-flex justify-content-between'
            . ' align-items-center',
    ]);
     echo H::tag('strong', 'Customs Declarations 1.0');
     echo H::a('← Back', '/backend/hmrc', [
         'class' => 'btn btn-sm btn-outline-secondary',
     ]);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);

     echo H::tag(
         'div',
         'This API has no live test on this page. Every one of its'
             . ' five endpoints submits a genuine customs/trade-'
             . ' compliance XML document -- unlike every other HMRC'
             . ' API in this app, there is no minimal-effort payload'
             . ' HMRC would actually accept, so a "test" submission'
             . ' would only fail, without proving anything real. The'
             . ' real request/response shapes below are confirmed'
             . ' live against HMRC\'s own OAS spec.',
         ['class' => 'alert alert-info'],
     );

     echo H::openTag('p');
     echo 'EORI on file: ' . H::tag(
         'code',
         $eori === '' ? '— not set —' : H::encode($eori),
     );
     if ($eori === '') {
         echo ' ' . H::a(
             'Set it under Settings → Making Tax Digital',
             '/setting/tab_index',
             ['class' => 'small'],
         );
     }
     echo H::closeTag('p');

     echo H::tag(
         'p',
         H::tag(
             'strong',
             'Identification: one HTTP header, not a URL parameter',
         ),
         ['class' => 'mb-1'],
     );
     echo H::tag(
         'p',
         'Unlike NINO/VRN/UTR elsewhere in this app, this API'
             . ' identifies the submitter via one of three request'
             . ' headers: '
             . H::tag('code', 'X-Badge-Identifier')
             . ', '
             . H::tag('code', 'X-Submitter-Identifier')
             . ', or '
             . H::tag('code', 'X-Eori-Identifier')
             . '. Content-Type is '
             . H::tag('code', 'application/xml; charset=UTF-8')
             . ' for every endpoint -- the only API in this catalogue'
             . ' that is not JSON.',
         ['class' => 'text-muted'],
     );

     echo H::openTag('div', ['class' => 'table-responsive mt-3']);
     echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
      echo H::openTag('thead', ['class' => 'table-light']);
       echo H::openTag('tr');
        foreach (['Method', 'Path', 'Scope'] as $col) {
            echo H::tag('th', $col);
        }
       echo H::closeTag('tr');
      echo H::closeTag('thead');
      echo H::openTag('tbody');
       $endpoints = [
           ['POST', '/customs/declarations/'],
           ['POST', '/customs/declarations/cancellation-requests'],
           ['POST', '/customs/declarations/file-upload'],
           ['POST', '/customs/declarations/amend'],
           ['POST', '/customs/declarations/arrival-notification'],
       ];
       foreach ($endpoints as [$method, $path]) {
           echo H::openTag('tr');
            echo H::tag(
                'td',
                H::tag('span', $method, ['class' => 'badge bg-primary']),
            );
            echo H::tag('td', H::tag('code', $path, ['class' => 'small']));
            echo H::tag(
                'td',
                H::tag('code', 'write:customs-declaration', ['class' => 'small']),
            );
           echo H::closeTag('tr');
       }
      echo H::closeTag('tbody');
     echo H::closeTag('table');
     echo H::closeTag('div');

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
