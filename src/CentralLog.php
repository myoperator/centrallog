<?php
namespace MyOperator;

use \Logger;
use MyOperator\DetailLogRenderer;

defined('LOG_LEVEL_ERROR') or define('LOG_LEVEL_ERROR', 'error');
defined('LOG_LEVEL_WARN') or define('LOG_LEVEL_WARN', 'warn');
defined('LOG_LEVEL_INFO') or define('LOG_LEVEL_INFO', 'info');

class CentralLog {


    protected $app = 'centrallogs';
    protected $defaultPattern = '%logger: (%date) [%level]- %msg%n';
    protected $defaultMaxSize = '15MB';

    public static $instance = null;

    public function __construct($app = null)
    {
        // Setting application name by default
        if($app)
            $this->app = $app;

        $this->acl = array(
            'developer' => 1,
            'support' => 2,
            'customer' => 4
        );
    }

	public function getDefaultConfig()
	{
		return array(
			'defaultRenderer' => DetailLogRenderer::class,
			'rootLogger' => array(
				'appenders' => array('default'),
			),
			'appenders' => array(
				'default' => array(
					'class' => 'LoggerAppenderRollingFile',
					'layout' => array(
						'class' => 'LoggerLayoutPattern',
						'params' => array('conversionPattern' => $this->defaultPattern)
					),
					'params' => array(
						'file' => $this->outputPath,
						'maxFileSize' => $this->defaultMaxSize,
						'maxBackupIndex' => 500	
					)
				)
			)
		);
	}

    public function setApplication($app = null) {
        if($app) $this->app = $app;
        if(!$this->class) {
            $this->setClassName($this->app);
        }
        $this->hashuid = $this->hash($this->app);
    }

    public static function getInstance()
    {
        if(!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	public static function setConfigurator($path)
	{
        $instance = self::getInstance();
		if(is_array($path) || ($path === null)) {
            $instance->setConfig($path);
        } else {
            $instance->setConfigPath($path);
        }
        \Logger::configure($instance->getConfig());
	}

    public static function configure($outputpath = null, $server = null, $class = null, $pattern=null, $maxsize=null)
    {
        $instance = self::getInstance();
        $instance->_configure($outputpath, $server, $class, $pattern, $maxsize);
        \Logger::configure($instance->getDefaultConfig());
    }

    public static function getLogger($app)
    {
        $instance = self::getInstance();
        $instance->setApplication($app);
        $logger = \Logger::getLogger($app);
        $instance->setLogger($logger);
        return $instance;
    }

    protected function hash($name)
    {
        return base64_encode($name);
    }

    public function setConfigPath($path, $relative=false)
    {
        $path = $relative === true ? (dirname(__FILE__) . $path) :  $path;
        if(file_exists($path)) {
            $this->config = $path;
            return true;
        }
        return false;
    }

    public function setConfig($config = null)
    {
		if($config === null) $config = $this->getDefaultConfig();
        if(is_array($config)) {
            $this->config = $config;
            return true;
        }
        return false;
    }

	public function getConfig()
	{
		return $this->config ?: $this->getDefaultConfig();
	}

    public function setLogger($logger)
    {
        if(!($logger instanceof \Logger)) {
            throw new \Exception("Logger must be instance of log4php Logger");
        }
        $this->logger = $logger;
    }

    public function logme($message, $acl = null, $uid = null)
    {
        $this->log($message, $acl, $uid, LOG_LEVEL_INFO);
    }

    public function info($message, $acl = null, $uid = null)
    {
        $this->log($message, $acl, $uid, LOG_LEVEL_INFO);
    }

    public function error($message, $acl = null, $uid = null)
    {
        $this->log($message, $acl, $uid, LOG_LEVEL_ERROR);
    }

    public function warn($message, $acl = null, $uid = null)
    {
        $this->log($message, $acl, $uid, LOG_LEVEL_WARN);
    }

    public function slog($message, $uid = null, $level = null)
    {
        $this->log($message, $this->acl['support'], $uid, $level);
    }

    public function clog($message, $uid = null, $level = null)
    {
        $this->log($message, $this->acl['customer'], $uid, $level);
    }

    public function dlog($message, $uid = null, $level = null)
    {
        $this->log($message, $this->acl['developer'], $uid, $level);
    }


    public function log($message, $acl = null, $uid = null, $level = null)
    {
        if(!$uid) $uid = $this->hashuid;
        if(!$acl) $acl = $this->acl['developer']; //By default, logs should be open to developers only
        if(!$level) {
            $level = ($message instanceof \Exception) ? LOG_LEVEL_ERROR : LOG_LEVEL_INFO;
        }

        $message = $this->formatMessage($message, $uid, $acl);
        $this->logger->info($message);
    }

    private function formatMessage($message, $uid, $acl)
    {
        return array(
            'time' => time(),
            'mc_time' => round(microtime(true) * 1000),
            'ip' => $this->server,
            'class' => $this->class,
            'data' => array(
                'dmsg' => ($this->acl['developer'] === $acl) ? $message : '',
                'smsg' => ($this->acl['support'] === $acl) ? $message : '',
                'cmsg' => ($this->acl['customer'] === $acl) ? $message : '',
                'acl' => $acl,
                'uid' => $uid
            )
        );
    }

    

    public function setOutputFilename($name = null, $path=null)
    {
        if(!$name) {
            $name = $app . '-' . date('Y-m-d') . '.log';
        }

        if($path !== null && is_string($path)) {
            $name = rtrim($path, '/') . '/' . $name;
        }

        $this->outputPath = $name;
    }

    public function setPattern($pattern = null)
    {
        if($pattern === null) $pattern = $this->defaultPattern;
        $this->pattern = $pattern;
    }


    public function setMaxSize($maxsize = null)
    {
        if($maxsize === null) $maxsize = $this->defaultMaxSize;
        $this->maxsize = $maxsize;
    }

    public function setServer($server = null)
    {
        if($server === null) {
            $server = gethostname() ?: $this->app;
        }

        $this->server = $server;
    }

    public function setClassName($class = null)
    {
        $this->class = $class;
    }

    private function _configure($outputpath = null, $server = null, $class = null, $pattern=null, $maxsize=null)
    {
        $this->setPattern($pattern);
        $this->setMaxSize($maxsize);
        $this->setServer($server);
        $this->setClassName($class);
		$this->setOutputFilename($outputpath);
		$this->setConfig();

    }
}
