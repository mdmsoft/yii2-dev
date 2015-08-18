<?php
$params = array_merge(
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-web',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\controllers',
    'bootstrap' => [
        'log',
        'api',
        'admin',
    ],
    'modules' => [
        'api' => [
            'class' => 'biz\api\Module',
        ],
        'client' => [
            'class' => 'biz\client\Module',
        ],
        'admin'=>[
            'class' => 'dee\admin\Module',
        ]
    ],
    'components' => [
        'user' => [
            'identityClass' => 'app\models\ar\User',
            'loginUrl' => ['user/login'],
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'authManager'=>[
            'class'=>'yii\rbac\DbManager',
        ]
    ],
    'params' => $params,
];
