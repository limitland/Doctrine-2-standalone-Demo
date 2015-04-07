<?php 

require_once __DIR__ . '/../vendor/autoload.php';

$config = yaml_parse_file(__DIR__ . '/../app/config/parameters.yml');
setlocale(LC_ALL, $config['locale']);

// Twig
$loader = new Twig_Loader_Filesystem(__DIR__ . '/../views');
$twig = new Twig_Environment($loader);

// Doctrine DBAL
$dbalconfig = new Doctrine\DBAL\Configuration();
$conn = Doctrine\DBAL\DriverManager::getConnection($config['database'], $dbalconfig);

// Doctrine ORM
$ormconfig = new Doctrine\ORM\Configuration();
$cache = new Doctrine\Common\Cache\ArrayCache();
$ormconfig->setQueryCacheImpl($cache);
$ormconfig->setProxyDir(__DIR__ . '/../model/EntityProxy');
$ormconfig->setProxyNamespace('EntityProxy');
$ormconfig->setAutoGenerateProxyClasses(true);
 
// ORM mapping by Annotation
Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
$driver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
    new Doctrine\Common\Annotations\AnnotationReader(),
    array(__DIR__ . '/../model/Entity')
);
$ormconfig->setMetadataDriverImpl($driver);
$ormconfig->setMetadataCacheImpl($cache);
 
// EntityManager
$em = Doctrine\ORM\EntityManager::create($config['database'],$ormconfig);

// The Doctrine Classloader
require __DIR__ . '/../vendor/doctrine/common/lib/Doctrine/Common/ClassLoader.php';
$classLoader = new Doctrine\Common\ClassLoader('Entity', __DIR__ . '/../model');
$classLoader->register();
