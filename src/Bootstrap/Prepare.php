<?php
/**
 * This file is part of the Zemit Framework.
 *
 * (c) Zemit Team <contact@zemit.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Zemit\Bootstrap;

use Phalcon\Debug;
use Phalcon\Di\Injectable;
use Zemit\Events\EventsAwareTrait;

/**
 * Class Prepare
 * Prepare raw php stuff early in the bootstrap
 * @package Zemit\Bootstrap
 */
class Prepare extends Injectable
{
    
    use EventsAwareTrait;
    
    public $debug;
    
    /**
     * Prepare raw php stuff
     * - Initialize
     * - Random fixes
     * - Define constants
     * - Force debug
     * - Force PHP settings
     */
    public function __construct() {
        $this->initialize();
        $this->forwarded();
        $this->define();
        $this->debug();
//        $this->php();
    }
    
    /**
     * Initialisation
     */
    public function initialize() {
    
    }
    
    /**
     * Fix for forwarded https
     */
    protected function forwarded() {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $_SERVER['HTTPS'] = 'on';
        }
    }
    
    /**
     * Prepare application environment variables
     * @TODO centralize everything inside "ENV"
     * - APPLICATION_ENV
     * - APP_ENV
     * - ENV
     */
    protected function define() {
        defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
        defined('APP_ENV') || define('APP_ENV', (getenv('APP_ENV') ? getenv('APP_ENV') : APPLICATION_ENV));
        defined('ENV') || define('ENV', (getenv('ENV') ? getenv('ENV') : APPLICATION_ENV));
    }
    
    /**
     * Prepare debugging
     * - Prepare error reporting and display errors natively with PHP
     * - Listen with phalcon debugger
     * @TODO prevent this if in production, please ;)
     */
    protected function debug() {
        // Enable error reporting and display
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Prepare the phalcon debug listener
        $this->debug = new Debug();
        $this->debug->listen();
    }
    
    /**
     * Prepare some PHP config
     */
    public function php($config = null) {
        $config = $config ?? $this->getDI()->get('config');
        
//        setlocale(LC_ALL, 'fr_CA.' . $encoding, 'French_Canada.1252');
        date_default_timezone_set($config->app->timezone ?? 'America/Montreal');
        mb_internal_encoding($config->app->encoding ?? 'UTF-8');
        mb_http_output($config->app->encoding ?? 'UTF-8');
        ini_set('memory_limit', $config->app->memoryLimit ?? '256M');
        ini_set('post_max_size', $config->app->postLimit ?? '20M');
        ini_set('upload_max_filesize', $config->app->postLimit ?? '20M');
        ini_set('max_execution_time', $config->app->timeoutLimit ?? '60');
        set_time_limit($config->app->timeoutLimit ?? '60');
    }
}