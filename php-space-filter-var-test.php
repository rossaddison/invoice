<?php

declare(strict_types=1);
/**
     * Related logic: see https://www.php.net/manual/en/function.filter-var.php divinity76 at gmail dot com
     * Testing various values that may be input as an environment variable in a .env file using 'filter_var'
     * To test run on the command line e.g. C:\wamp64\www\invoice>php php-space-filter-var-test.php
     */
$vals = ['true', 'True', 'TRUE', 'false', 'False', 'FALSE', 'false', 'FALSE', true, false, 0, 1];
foreach ($vals as $val) {
    echo var_export($val, true) . ': ';
    var_dump(filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
}
