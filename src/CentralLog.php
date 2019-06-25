<?php
namespace MyOperator;

use \Logger;
use MyOperator\DetailLogRenderer;

defined('LOG_LEVEL_ERROR') or define('LOG_LEVEL_ERROR', 'error');
defined('LOG_LEVEL_WARN') or define('LOG_LEVEL_WARN', 'warn');
defined('LOG_LEVEL_INFO') or define('LOG_LEVEL_INFO', 'info');

/**
 * CentralLog is a wrapper above log4php to streamline 
 * myoperator logs.
 *
 * MyOperator logs requires some additional formatting as
 * well as some extra parameters to be able to detailed
 * properly. Hence, this wrapper provides the functionality
 * to streaming the logging process for PHP applications.
 *
 * Example Usage:
 * CentralLog::configure("mylog.log");
 *
 * $logger = CentralLog::getLogger('myLogger');
 * $logger->log("Some generic log");
 *
 * @package MyOperator\CentralLog
 * @author Ashutosh Chaudhary <ashutosh.chaudhary@myoperator.co>
 * @see https://myoperator.co
 **/
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

    /**
     * Returns the default logger configurator after parent
     * logger has been configured. This configuration is
     * passed to the logger.
     *
     * @access public
     * @return array configurator array
     **/
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

    /**
     * Configures the logger. Same as log4php::configure, with some added parameters
     *
     * @param string $outputpath Output path of the log file
     * @param string $server Server on which the application is running. Ex- S6, API01
     * @param string|class $class Class name under which the logger is being used
     * @param string $pattern logger pattern as described in ({@link https://logging.apache.org/log4php/docs/layouts/pattern.html})
     * @param string $maxsize Maximum size per log
     *
     * @access public
     **/
    public static function configure($outputpath = null, $server = null, $class = null, $pattern=null, $maxsize=null)
    {
        $instance = self::getInstance();
        $instance->_configure($outputpath, $server, $class, $pattern, $maxsize);
        \Logger::configure($instance->getDefaultConfig());
    }

    /**
     * Get logger instance to log the items
     *
     * @param string $app Application name to be used for the logging
     *
     * @return self Instance
     **/
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

    /**
     * The old logme function. Its a mimic of `log` function but kept for backward compatibility
     *
     * @param mixed $message Item to be logged
     * @param int $acl The ACL to be used to log the item. (optional)
     * @param string $uid The unique id of item. In case of sync script, this can be engine uid. (optional)
     **/
    public function logme($message, $acl = null, $uid = null)
    {
        $this->log($message, $acl, $uid, LOG_LEVEL_INFO);
    }

    /**
     * The info log function.
     *
     * @param mixed $message Item to be logged
     * @param int $acl The ACL to be used to log the item. (optional)
     * @param string $uid The unique id of item. In case of sync script, this can be engine uid. (optional)
     **/
    public function info($message, $acl = null, $uid = null)
    {
        $this->log($message, $acl, $uid, LOG_LEVEL_INFO);
    }

    /**
     * The error log function.
     *
     * @param mixed $message Item to be logged
     * @param int $acl The ACL to be used to log the item. (optional)
     * @param string $uid The unique id of item. In case of sync script, this can be engine uid. (optional)
     **/
    public function error($message, $acl = null, $uid = null)
    {
        $this->log($message, $acl, $uid, LOG_LEVEL_ERROR);
    }

    /**
     * The warn log function.
     *
     * @param mixed $message Item to be logged
     * @param int $acl The ACL to be used to log the item. (optional)
     * @param string $uid The unique id of item. In case of sync script, this can be engine uid. (optional)
     **/
    public function warn($message, $acl = null, $uid = null)
    {
        $this->log($message, $acl, $uid, LOG_LEVEL_WARN);
    }

    /**
     * The support log function.
     *
     * @param mixed $message Item to be logged
     * @param string $uid The unique id of item. In case of sync script, this can be engine uid. (optional)
     * @param string $level Log level (warn, info, error) can be passed as optional third param
     **/
    public function slog($message, $uid = null, $level = null)
    {
        $this->log($message, $this->acl['support'], $uid, $level);
    }

    /**
     * The client log function.
     *
     * @param mixed $message Item to be logged
     * @param string $uid The unique id of item. In case of sync script, this can be engine uid. (optional)
     * @param string $level Log level (warn, info, error) can be passed as optional third param
     **/

    public function clog($message, $uid = null, $level = null)
    {
        $this->log($message, $this->acl['customer'], $uid, $level);
    }

    /**
     * The develop log function.
     *
     * @param mixed $message Item to be logged
     * @param string $uid The unique id of item. In case of sync script, this can be engine uid. (optional)
     * @param string $level Log level (warn, info, error) can be passed as optional third param
     **/

    public function dlog($message, $uid = null, $level = null)
    {
        $this->log($message, $this->acl['developer'], $uid, $level);
    }

    /**
     * The log function.
     *
     * @param mixed $message Item to be logged
     * @param int $acl The ACL to be used to log the item. (optional)
     * @param string $uid The unique id of item. In case of sync script, this can be engine uid. (optional)
     * @param string $level Log level (warn, info, error) can be passed as optional third param
     **/

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
