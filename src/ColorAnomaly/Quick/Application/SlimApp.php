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

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;

use Mike42\Escpos\CapabilityProfile;

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

    public function sendPrint($queue, $token) {
        $c = $this->getConfig()['app']['printer'];

        $connector = new NetworkPrintConnector($c['ip'], $c['port']);
    
        if($c['feature_set'] == 'full') { // For printers with full feature set.
            $printer = new Printer($connector);
        } else { // For printers with minimal feature set.
            $profile = CapabilityProfile::load("simple");
            $printer = new Printer($connector, $profile);
        }

        try {
            $printer->initialize();
            $printer->setPrintLeftMargin(15);
            $printer->setPrintWidth(586);
            $printer->setFont(Printer::FONT_A);
            
            $printer->setEmphasis(true);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            
            $printer->setLineSpacing(60);
            $printer->setTextSize(1, 1);
            
            $printer->text("Allied Insurance Company");
            $printer->feed();

            
            $printer->setTextSize(2, 2);
            
            $printer->text($queue->getName());
            $printer->feed();
            
            
            $printer->setTextSize(3, 3);
            
            $printer->text($token);
            $printer->feed();

            
            $printer->setTextSize(1, 1);

            $printer->text(date("d/m/Y H:i:s"));
            $printer->feed();

            $aheadCount = $queue->getEnqueueLast() - $queue->getDequeueLast();
            if($aheadCount > 0) {
                $printer->text("$aheadCount customers ahead of you.");
            } else {
                $printer->text("You are next in line.");
            }
            $printer->feed(1);
            
            $printer->cut(Printer::CUT_FULL, 3); // Printer::CUT_FULL or Printer::CUT_PARTIAL
            $printer->close();
        } finally {
            $printer->close();
        }
    }

    public function printerTest() {
        $c = $app->getConfig()['app']['printer'];

        $connector = new NetworkPrintConnector($c['ip'], $c['port']);
        
        if($c['feature_set'] == 'full') { // For printers with full feature set.
            $printer = new Printer($connector);
        } else { // For printers with minimal feature set.
            $profile = CapabilityProfile::load("simple");
            $printer = new Printer($connector, $profile);
        }

        try {
            $printer->initialize();
            $printer->setPrintLeftMargin(15);
            $printer->setPrintWidth(586);
            $printer->setFont(Printer::FONT_B);
            $printer->setLineSpacing(56);

            $printer->setEmphasis(true);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            
            $printer->setTextSize(2, 2);
            $printer->text("Allied Insurance Company");
            $printer->feed(1);

            $printer->setTextSize(1, 1);
            $printer->text("<Queue Name Here>");
            $printer->feed();
            
            //$printer->barcode("656565656", Printer::BARCODE_CODE39);
            //$printer->qrCode("Hello World");
            
            //$printer->feed(3);
            $printer->cut(Printer::CUT_FULL, 3); // Printer::CUT_FULL or Printer::CUT_PARTIAL
            $printer->close();
        } finally {
            $printer->close();
        }
    }

    public function getConfig() {
        return $this->config;
    }

}
