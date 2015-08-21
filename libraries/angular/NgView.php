<?php

namespace dee\angular;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\helpers\Inflector;
use yii\web\View as WebView;

/**
 * Description of NgView
 *
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class NgView extends \yii\base\Widget
{
    /**
     *
     * @var array
     */
    public $routes = [];

    /**
     *
     * @var array
     */
    public $resources = [];

    /**
     *
     * @var string
     */
    public $name = 'dApp';

    /**
     * @var boolean If `true` will render attribute `ng-app="appName"` in widget.
     */
    public $useNgApp = true;

    /**
     * @var array
     */
    public $requires = [];

    /**
     * @var string
     */
    public $tag = 'ng-view';

    /**
     *
     * @var string
     */
    public $js;

    /**
     *
     * @var string
     */
    public $controller;

    /**
     * @var array
     */
    public static $requireAssets = [
        'ui.bootstrap' => 'dee\angular\AngularBootstrapAsset',
        'dee.angular' => 'dee\angular\DeeAngularAsset',
        'ngRoute' => 'dee\angular\AngularRouteAsset',
        'ngResource' => 'dee\angular\AngularResourceAsset',
    ];
    private $_varName;

    /**
     *
     * @var static 
     */
    public static $instance;

    /**
     * @inheritdoc
     */
    public function init()
    {
        static::$instance = $this;
        $this->_varName = Inflector::variablize($this->name);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $routeProviders = [];
        $controllers = [];
        $view = $this->getView();
        $templates = [];
        foreach ($this->routes as $path => $route) {
            $show = ArrayHelper::remove($route, 'show', true);
            list($routeProvider, $controller, $template) = $this->applyRoute($route, $path);

            if($path == 'otherwise'){
                $routeProviders[] = "\$routeProvider.otherwise({$routeProvider});";
            }elseif ($show) {
                $p = Json::htmlEncode($path);
                $routeProviders[] = "\$routeProvider.when({$p},{$routeProvider});";
            }
            if ($controller) {
                $controllers[$controller[0]] = $controller[1];
            }
            if ($template) {
                $templates[$path] = $template;
            }
        }

        $js = [];
        $js[] = "{$this->_varName} = (function(){";
        $js[] = $this->renderModule();
        $js[] = $this->renderTemplates($templates);
        $js[] = $this->renderRouteProviders($routeProviders);
        $js[] = $this->renderControllers($controllers);
        $js[] = $this->renderResources();
        $js[] = "\nreturn {$this->_varName};\n})();";

        $view->registerJs(implode("\n", $js), WebView::POS_END);

        static::$instance = null;
        return Html::tag($this->tag, '', ['ng-app' => $this->useNgApp ? $this->name : false, 'ng-view' => $this->tag != 'ng-view']);
    }

    protected function applyRoute($route, $path)
    {
        $view = $this->getView();
        $routeProvider = $controller = $template = null;

        if (is_string($route)) {
            $routeProvider = Json::htmlEncode(['redirectTo' => $route]);
        } elseif (isset($route['link'])) {
            $link = Json::htmlEncode($route['link']);
            unset($route['link'], $route['view'], $route['controller'], $route['show']);
            $route = Json::htmlEncode($route);
            $routeProvider = "angular.extend({},{$this->_varName}.templates[{$link}],{$route})";
        } else {
            $injection = ArrayHelper::remove($route, 'injection', []);

            if (empty($route['controller'])) {
                $route['controller'] = Inflector::camelize(ArrayHelper::getValue($route, 'view', $path)) . 'Ctrl';
            }
            $this->controller = $route['controller'];
            $controller = [$this->controller, $injection];

            if (isset($route['js'])) {
                $this->renderJs($route['js']);
                unset($route['js']);
            }
            if (isset($route['view'])) {
                $route['template'] = $view->render($route['view'], ['widget' => $this]);
                unset($route['view']);
            }
            $template = $route;

            $path = Json::htmlEncode($path);
            $routeProvider = "{$this->_varName}.templates[{$path}]";
        }
        $this->controller = null;
        return [$routeProvider, $controller, $template];
    }

    /**
     * Use to add required module to application
     *
     * @param sting|array $requires
     */
    public function requires($requires)
    {
        $this->requires = array_unique(array_merge($this->requires, (array) $requires));
    }

    /**
     * Render script create module. The result are
     * ```javascript
     * appName = angular.module('appName',[requires,...]);
     * ```
     */
    protected function renderModule()
    {
        $view = $this->getView();
        if (!empty($this->resources)) {
            $this->requires = array_unique(array_merge($this->requires, ['ngResource']));
        }
        $requires = array_unique(array_merge(['ngRoute'], $this->requires));
        AngularAsset::register($view);
        foreach ($requires as $module) {
            if (isset(static::$requireAssets[$module])) {
                $class = static::$requireAssets[$module];
                $class::register($view);
            }
        }
        $js = "{$this->_varName} = angular.module('{$this->name}'," . Json::htmlEncode($requires) . ");";

        if ($this->js !== null) {
            $js .= "\n" . static::parseBlockJs($view->render($this->js));
        }
        return $js;
    }

    protected function renderTemplates($templates)
    {
        return "{$this->_varName}.templates = " . Json::htmlEncode($templates) . ';';
    }

    /**
     * Render script config for $routeProvider
     * @param array $routeProviders
     */
    protected function renderRouteProviders($routeProviders)
    {
        $routeProviders = implode("\n", $routeProviders);
        return "{$this->_varName}.config(['\$routeProvider',function(\$routeProvider){\n{$routeProviders}\n}]);";
    }

    /**
     * Render script create controllers
     * ```javascript
     * appName.controller('CtrlName',['$scope',...,
     *     function($scope,...){
     *         ...
     *     }]);
     * ```
     * @param array $controllers
     */
    protected function renderControllers($controllers)
    {
        $js = [];
        $view = $this->getView();
        foreach ($controllers as $name => $injection) {
            $injection = array_unique(array_merge(['$scope', '$injector'], (array) $injection));
            $injectionStr = rtrim(Json::htmlEncode($injection),']');
            $injectionVar = implode(", ", $injection);
            $function = implode("\n", ArrayHelper::getValue($view->js, $name, []));
            $js[] = "{$this->_varName}.controller('$name',{$injectionStr},\nfunction($injectionVar){\n{$function}\n}]);";
        }
        return implode("\n", $js);
    }

    /**
     * Render script resource
     * ```javascript
     * appName.factory(ResName,['$resource',function($resource){
     *     return ...;
     * }]);
     * ```
     */
    protected function renderResources()
    {
        if (!empty($this->resources)) {
            $js = [];
            foreach ($this->resources as $name => $config) {
                $url = Json::htmlEncode($config['url']);
                if (empty($config['paramDefaults'])) {
                    $paramDefaults = '{}';
                } else {
                    $paramDefaults = Json::htmlEncode($config['paramDefaults']);
                }
                if (empty($config['actions'])) {
                    $actions = '{}';
                } else {
                    $actions = Json::htmlEncode($config['actions']);
                }

                $js[] = <<<JS
{$this->_varName}.factory('$name',['\$resource',function(\$resource){
    return \$resource({$url},{$paramDefaults},{$actions});
}]);
JS;
            }
            return implode("\n", $js);
        }
        return '';
    }

    /**
     * Only get script inner of `script` tag.
     * @param string $js
     * @return string
     */
    public static function parseBlockJs($js)
    {
        $jsBlockPattern = '|^<script[^>]*>(?P<block_content>.+?)</script>$|is';
        if (preg_match($jsBlockPattern, trim($js), $matches)) {
            $js = trim($matches['block_content']);
        }
        return $js;
    }

    /**
     * Register script to controller.
     * 
     * @param string $viewFile
     * @param array $params
     * @param integer|string $pos
     */
    public function renderJs($viewFile, $params = [], $pos = null)
    {
        $params['widget'] = $this;
        $js = $this->view->render($viewFile, $params);
        $this->registerJs($js, $pos);
    }

    /**
     * Register script to controller.
     *
     * @param string $js
     * @param integer|string $pos
     */
    public function registerJs($js, $pos = null)
    {
        $pos = $pos ? : ($this->controller ? : WebView::POS_END);
        $this->view->registerJs(static::parseBlockJs($js), $pos);
    }
}