<?php

namespace Itscaro\App;

use Monolog\Logger,
    Monolog\Handler as MonologHandler;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class Application extends SymfonyApplication {

    private $_config;

    /**
     *
     * @var Logger
     */
    private $_logger;

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->_logger = new Logger('default');
        $this->_logger->pushHandler(new MonologHandler\NullHandler(Logger::DEBUG));
        $this->_logger->pushHandler(new MonologHandler\StreamHandler('./warning.log', Logger::WARNING));
        $this->_logger->pushHandler(new MonologHandler\StreamHandler('./info.log', Logger::INFO));
        $this->_logger->pushHandler(new MonologHandler\StreamHandler('./debug.log', Logger::DEBUG));
        $this->_logger->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
    }

    /**
     * 
     * @return Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Returns configuration array
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Set configuration array
     * @param array $config
     * @return Application
     */
    public function setConfig(array $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Load YAML config file
     * @param string $filepath
     */
    public function loadConfigFromFile($filepath)
    {
        $config = Yaml::parse($filepath);
        $this->setConfig($config);
    }

    /**
     * If the application is running as a Phar
     * @return boolean
     */
    public function isPhar()
    {
        return (strpos(ROOTDIR, 'phar') === 0);
    }

    /**
     * Returns the path to the data store file
     * @param string $storeFileName
     * @return string
     */
    protected function _getDataStorePath($storeFileName)
    {
        if ($this->isPhar()) {
            $home = getenv('HOME');
            if (empty($home)) {
                if (isset($_SERVER['HOMEDRIVE']) && isset($_SERVER['HOMEPATH'])) {
                    // home on windows
                    $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
                } else {
                    $home = '.';
                }
            }
            $rootdir = $home . '/.flickr-sync';

            return $rootdir . '/data/' . $storeFileName;
        } else {
            $rootdir = ROOTDIR;
        }
        return $rootdir . '/data/' . $storeFileName;
    }

    public function getDataStore($storeFileName)
    {
        $filename = $this->_getDataStorePath($storeFileName);

        //Create your own folder in the cache directory
        $fs = new Filesystem();
        try {
            if (!$fs->exists(dirname($filename))) {
                echo $fs->mkdir(dirname($filename));
            }
        } catch (IOException $e) {
            echo "An error occured while creating your directory";
        }

        if ($fs->exists($filename)) {
            return unserialize(file_get_contents($filename));
        } else {
            return array();
        }
    }

    public function setDataStore($storeFileName, array $data)
    {
        $filename = $this->_getDataStorePath($storeFileName);

        //Create your own folder in the cache directory
        $fs = new Filesystem();
        try {
            if (!$fs->exists(dirname($filename))) {
                $fs->mkdir(dirname($filename));
            }
        } catch (IOException $e) {
            echo "An error occured while creating your directory";
        }

        if (!$fs->exists($filename)) {
            $fs->touch($filename);
        }

        return file_put_contents($filename, serialize($data));
    }

}
