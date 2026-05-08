<?php

declare(strict_types=1);

/**
 * Related logic: see src/Invoice/InvoiceController function phpinfo e.g.
 *
 * public function phpinfo(#[RouteArgument('selection')] string $selection = '-1') : Response
    {
        $view = $this->webViewRenderer->renderPartialAsString('//invoice/info/phpinfo', ['selection' => (int)$selection]);
        return $this->webViewRenderer->render('info/view', ['topic'=> $view]);
    }
 *
 *
 * @var string $topic
 */

echo $topic;
