#! /usr/bin/php
<?php
require_once('autoloader.php');
require_once('../test/autoloader.php');
require_once('../vendor/PHP-Parser/lib/bootstrap.php');
$file_name = $argv[1];
$test_name = $argv[2];
$fast_forward = $argv[3];

$should_exit = false;
$remaining_statements_to_read = '';
//while (!$should_exit)
//{
    $output = array();
    $return_status = null;
    exec("./test_builder_runtime.php $file_name $test_name $fast_forward \"$remaining_statements_to_read\"",
        $output, $return_status);
    if ($return_status === 0)
    {
        // We are done when the runtime exits successfully
        var_dump($output);
        echo "bye!\n";
        //break;
    }
    elseif ($return_status === 255)
    {
        var_dump($output);
    }
    if (count($output) > 0)
    {
        $remaining_statements_to_read = $output[0];
    }
//}
