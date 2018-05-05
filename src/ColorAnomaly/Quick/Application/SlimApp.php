<?php

/*
 * This software is a property of Marine Research Center - Maldives.
 * Use or reuse of this software for commercial purposes is strictly
 * prohibited.
 */

namespace ColorAnomaly\Quick\Application;

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

use ColorAnomaly\Quick\Domain\Service\QueueService;

/**
 * Description of SlimApp
 *
 * @author Hussain Nazan Naeem <hussennaeem@gmail.com>
 */
class SlimApp extends \Slim\Slim {

    protected $pdo;
    protected $dm;
    protected $qs;
    protected $userContext;
    protected $config;
    protected $env;
    protected $path;

    public function __construct($userSettings, $config, $path, $env = 'development') {
        parent::__construct($userSettings);

        $this->pdo = null;

        $this->dm = null;

        $this->qs = null;

        $this->userContext = null;

        $this->config = $config;

        $this->env = $env;

        $this->path = $path;
    }

    public function getPDO() {
        if (is_null($this->pdo)) {
            $this->pdo = new \PDO("mysql:host={$this->config['app']['db']['host']};dbname={$this->config['app']['db']['dbname']}", $this->config['app']['db']['username'], $this->config['app']['db']['password']);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        return $this->pdo;
    }

    public function getDM() {
        if (is_null($this->dm)) {
            $config = $this->config['app']['odm'];

            //$connectionString = "mongodb://{$config['ds']['user']}:{$config['ds']['password']}@{$config['ds']['server']}:{$config['ds']['port']}/{$config['ds']['dbname']}";
            $connectionString = "mongodb://{$config['ds']['server']}:{$config['ds']['port']}/{$config['ds']['dbname']}";

            $connection = new Connection($connectionString);
            $c = new Configuration();

            $c->setProxyDir($this->path . '/' . $config['tmp_dir'] . '/Proxies');
            $c->setProxyNamespace('Proxies');
            $c->setHydratorDir($this->path . '/' . $config['tmp_dir'] . '/Hydrators');
            $c->setHydratorNamespace('Hydrators');
            $c->setDefaultDB($config['ds']['dbname']);

            $c->setMetadataDriverImpl(AnnotationDriver::create($this->path . $config['domain_class_path']));

            AnnotationDriver::registerAnnotationClasses();

            $this->dm = DocumentManager::create($connection, $c);
        }

        return $this->dm;
    }

    public function getQueueService() {
        if (is_null($this->qs)) {
            $this->qs = new QueueService($this->getDM(), $this->config);
        }

        return $this->qs;
    }

    public function getUserContext() {
        if (is_null($this->userContext)) {
            $config = $this->getConfig();

            $identifyingKey = $config['app']['server_var']['id_key'];

            if ($identifyingKey == 'HTTP_USER_AGENT') {
                $identifyingKey = get_browser(null, true);
                $identifyingKey = strtolower($identifyingKey['browser']);
            } else {
                $identifyingKey = $_SERVER[$identifyingKey];
            }

            $qs = $this->getQueueService();

            $this->userContext = $qs->getUserContextFor($identifyingKey);
        }

        return $this->userContext;
    }

    public function getConfig() {
        return $this->config;
    }

}
