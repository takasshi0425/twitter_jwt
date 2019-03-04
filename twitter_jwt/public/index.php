<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;
use Phalcon\Session\Adapter\Files as Session;
use Phalcon\Http\Request\File;


try{
  // オートローダにディレクトリを登録する
  $loader = new Loader();
  $loader->registerDirs(array(
       '../app/controllers/',
       '../app/models/'
   ))->register();

  $loader->registerNamespaces(
    [
      'Store\Products' => '../app/models/',
    ]
  );

  $loader->register();


  // DIコンテナを作る
  $di = new FactoryDefault();

  // データベースサービスのセットアップ
  $di->set(
    'db',
    function () {
      return new PdoMysql(
        [
          "host"     => "localhost",
          "username" => "root",
          "password" => "iTdBhF08SBslOjU1",
          "dbname"   => "product",
          "options" => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
          )
        ]
      );
    }
  );

  $di->set('view', function(){
    $view = new \Phalcon\Mvc\View();
    $view->setViewsDir('../app/views/');
    return $view;
  });

  $di->set('url', function(){
    $url = new \Phalcon\Mvc\Url();
    $url->setBaseUri('/twitter_jwt/');
    return $url;
  });

  $di->setShared('session', function () {
      $session = new Session();
      $session->start();
      return $session;
  });

  $di->set(
    "crypt",
    function () {
        $crypt = new Crypt();

        // Set a global encryption key
        $crypt->setKey(
            "%31.1e$i86e$f!8jz"
        );

        return $crypt;
    },
    true
);

  $application = new \Phalcon\Mvc\Application($di);
  echo $application->handle()->getContent();
} catch(\Phalcon\Exception $e) {
  echo "PhalconException: ", $e->getMessage();
}
