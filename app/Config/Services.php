<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Logtail\Monolog\LogtailHandlerBuilder;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */

    //  public static function customView($getShared = true)
    // {
    //     if ($getShared)
    //     {
    //         return static::getSharedInstance('customView');
    //     }

    //     // Define your custom view path
    //     // return Services::renderer(APPPATH.'/Modules/Core/Views/', null, false);
    //     $view = new \CodeIgniter\View\View();

    // }

    public static function grantslib($getShared = true)
    {
          if ($getShared) {
              return static::getSharedInstance('grantslib');
          }
     
          return new \App\Libraries\System\GrantsLibrary();
    }

    public static function logger($getShared = true)
      {
          if ($getShared) {
              return static::getSharedInstance('logger');
          }
     
        $logger = new Logger('safina');
        $stdoutHandler = new StreamHandler('php://stdout', Level::Info);
        $logtailHandler = LogtailHandlerBuilder::withSourceToken(env('LOGTAIL_TOKEN'))
        ->withEndpoint("https://s1353094.eu-nbg-2.betterstackdata.com")
        ->build();
        $logger->pushHandler($stdoutHandler);
        $logger->pushHandler($logtailHandler);
        
        return $logger;
      }
}
