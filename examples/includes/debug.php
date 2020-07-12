<?php
namespace {
    function d()
    {
        call_user_func_array('debug\dump', func_get_args());
    }
    function dc()
    {
        call_user_func_array('debug\dumpCli', func_get_args());
    }
    function o()
    {
        call_user_func_array('debug\out', func_get_args());
    }
}

namespace debug {
    function out()
    {
        if (PHP_SAPI !== 'cli') {
            http_response_code(530);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET,POST,PATCH,PUT,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
            header('Access-Control-Max-Age: 3600');
            echo '</script>'.PHP_EOL;
            echo '<pre>'.PHP_EOL;
        } else {
            ini_set('xdebug.overload_var_dump', false);
            echo PHP_EOL . '============================================' . PHP_EOL;
        }
        foreach (func_get_args() as $arg) {
            ob_start();
            var_dump($arg);
            echo PHP_EOL;
            $output = ob_get_clean();
            $output = strip_tags($output);
            $output = html_entity_decode($output);
            echo $output;
            flush();
        }
        if (PHP_SAPI !== 'cli') {
            echo '<hr/>'.PHP_EOL;
            echo '</pre>'.PHP_EOL;
        } else {
            echo '^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^' . PHP_EOL . PHP_EOL;
        }
    }

    function dump()
    {
        ini_set('html_errors', false);
        ini_set('xdebug.var_display_max_depth', 10);
        ini_set('xdebug.var_display_max_children', 256);
        ini_set('xdebug.var_display_max_data', 10000);
        if (PHP_SAPI !== 'cli') {
            // if (!headers_sent()) {
            //     http_response_code(530);
            //     header('Access-Control-Allow-Origin: *');
            //     header('Access-Control-Allow-Methods: GET,POST,PATCH,PUT,OPTIONS');
            //     header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
            //     header('Access-Control-Max-Age', '3600');
            // }
            echo '</script>'.PHP_EOL;
            echo '<pre>'.PHP_EOL;
        }
        echo PHP_EOL;
        foreach (func_get_args() as $arg) {
            ob_start();
            var_dump($arg);
            $output = ob_get_clean();
            $output = strip_tags($output);
            $output = html_entity_decode($output);
            echo $output;
            flush();
            echo PHP_EOL;
        }
        if (PHP_SAPI !== 'cli') {
            echo '<hr/>'.PHP_EOL;
            echo '<h2>$_REQUEST</h2>'.PHP_EOL;
            var_dump($_REQUEST);
            echo '<hr/>'.PHP_EOL;
            echo '<h2>Stack Trace</h2>'.PHP_EOL;
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            echo '<hr/>'.PHP_EOL;
            echo '</pre>'.PHP_EOL;
        } else {
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            echo PHP_EOL . PHP_EOL;
        }
        die(__FILE__ . PHP_EOL);
    }

    function dumpCli()
    {
        ini_set('xdebug.overload_var_dump', 0);
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach (func_get_args() as $arg) {
            // echo json_encode($arg, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;
            var_dump($arg);
        }
        die(__FILE__ . PHP_EOL);
    }
}
