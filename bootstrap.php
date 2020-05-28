<?php

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\DriverManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$cloader = require_once __DIR__ . '/vendor/autoload.php';

// Read application Configuration
$config = yaml_parse_file(__DIR__ . '/config/parameters.yaml');
setlocale(LC_ALL, $config['locale']);

// Twig
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader);

// Doctrine DBAL
$dbalconfig = new Doctrine\DBAL\Configuration();
$conn = DriverManager::getConnection($config['database'], $dbalconfig);

// Doctrine ORM
$ormconfig = new Doctrine\ORM\Configuration();
$cache = new ArrayCache();
$ormconfig->setQueryCacheImpl($cache);
$ormconfig->setProxyDir(__DIR__ . '/Entity');
$ormconfig->setProxyNamespace('EntityProxy');
$ormconfig->setAutoGenerateProxyClasses(true);

// ORM mapping by Annotation
Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    __DIR__ . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
$driver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
    new Doctrine\Common\Annotations\AnnotationReader(),
    array(__DIR__ . '/src/Entity')
);
$ormconfig->setMetadataDriverImpl($driver);
$ormconfig->setMetadataCacheImpl($cache);

// EntityManager
$em = Doctrine\ORM\EntityManager::create($config['database'],$ormconfig);

// The Doctrine Classloader
require __DIR__ . '/vendor/doctrine/common/lib/Doctrine/Common/ClassLoader.php';
$classLoader = new Doctrine\Common\ClassLoader('App\Entity', __DIR__.'/src/Entity');
$classLoader->register();
