<?php
use Slim\Slim;
use Slim\Views\Twig;
use Slim\Middleware\SessionCookie;

class Xhgui_ServiceContainer extends Pimple
{
    protected static $_instance;

    public static function instance()
    {
        if (empty(static::$_instance)) {
            static::$_instance = new self();
        }
        return static::$_instance;
    }

    public function __construct()
    {
        $this['config'] = include XHGUI_ROOT_DIR . '/config/config.php';
        $this->_slimApp();
        $this->_services();
        $this->_controllers();
    }

    // Create the Slim app.
    protected function _slimApp()
    {
        $this['app'] = $this->share(function ($c) {
            $app = new Slim($c['config']);

            // Enable cookie based sessions
            $app->add(new SessionCookie(array(
                'httponly' => true,
            )));

            // Configure Twig view for slim
            $view = new Twig();
            $view->parserOptions = array(
                'charset' => 'utf-8',
                'cache' => XHGUI_ROOT_DIR . '/cache',
                'auto_reload' => true,
                'strict_variables' => false,
                'autoescape' => true
            );
            $view->parserExtensions = array(
                new Xhgui_Twig_Extension($app)
            );
            $app->view($view);

            return $app;
        });
    }

    /**
     * Add common service objects to the container.
     */
    protected function _services()
    {
        $this['db'] = $this->share(function ($c) {
            $config = $c['config'];
            $mongo = new MongoClient($config['db.host']);
            return $mongo->{$config['db.db']};
        });

        $this['watchFunctions'] = function ($c) {
            return new Xhgui_WatchFunctions($c['db']);
        };

        $this['profiles'] = function ($c) {
            return new Xhgui_Profiles($c['db']);
        };
    }

    /**
     * Add controllers to the DI container.
     */
    protected function _controllers()
    {
        $this['watchController'] = $this->share(function ($c) {
            return new Xhgui_Controller_Watch($c['app'], $c['watchFunctions']);
        });

        $this['runController'] = $this->share(function ($c) {
            return new Xhgui_Controller_Run($c['app'], $c['profiles'], $c['watchFunctions']);
        });

        $this['customController'] = $this->share(function ($c) {
            return new Xhgui_Controller_Custom($c['app'], $c['profiles']);
        });
    }

}