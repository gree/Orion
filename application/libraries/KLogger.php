<?php
    /**
     * Finally, a light, permissions-checking logging class.
     *
     * Author    : Kenny Katzgrau <katzgrau@gmail.com>
     * Date      : July 26, 2008
     * Comments  : Originally written for use with wpSearch
     * Website   : http://codefury.net
     * Version   : 1.0
     *
     * Usage:
     *        $log = new KLogger ( "log.txt" , KLogger::INFO );
     *        $log->_logInfo("Returned a million search results");    //Prints to the log file
     *        $log->_logFatal("Oh dear.");  //Prints to the log file
     *        $log->_logDebug("x = 5");     //Prints nothing due to priority setting
     */

    /**
     * Class documentation
     */
    class KLogger
    {

        const DEBUG     = 1;    // Most Verbose
        const INFO      = 2;    // ...
        const WARN      = 3;    // ...
        const ERROR     = 4;    // ...
        const FATAL     = 5;    // Least Verbose
        const OFF       = 6;    // Nothing at all.

        const LOG_OPEN    = 1;
        const OPEN_FAILED = 2;
        const LOG_CLOSED  = 3;

        /* Public members: Not so much of an example of encapsulation, but that's okay. */
        private $_logStatus         = self::LOG_CLOSED;
        private static $_defaultPriority   = self::DEBUG;
        private static $_dateFormat        = "Y-m-d H:i:s";
        private static $_defaultPermissions= 0777;
        private $_messageQueue      = array();
        private $_logFile           = NULL;
        private $_exceptionLogFile  = NULL;
        private $_priority          = self::INFO;
        private $_fileHandle        = NULL;
        private $_exceptionFileHandle = NULL;

        private static $instances = array();

        public static function instance($logDirectory = FALSE, $priority = FALSE)
        {
            if ($priority === FALSE) {
                $priority = self::$_defaultPriority;
            }

            if ($logDirectory === FALSE) {
                if (count(self::$instances) > 0) {
                    return self::$instances[0];
                } else {
                    $logDirectory = dirname(__FILE__);
                }
            }

            if (in_array($logDirectory, self::$instances)) {
                return self::$instances[$logDirectory];
            }

            self::$instances[$logDirectory] = new KLogger($logDirectory, $priority);

            return self::$instances[$logDirectory];
        }

        public function __construct($logDirectory, $priority)
        {
            $CI =& get_instance();
            $logDirectory = rtrim($logDirectory, '/');

            if ($priority == self::OFF) {
                return;
            }

            $this->_logFile  = $logDirectory
                               . DIRECTORY_SEPARATOR
                               . $CI->orion_config['LOG_FILE_BASE']
                               . '-'
                               . date('Y-m-d')
                               . '.log';

            $this->_exceptionLogFile  = $logDirectory
                               . DIRECTORY_SEPARATOR
                               . $CI->orion_config['LOG_FILE_BASE']
                               . '-errors-'
                               . date('Y-m-d')
                               . '.log';

            $this->_priority = $priority;
            if (!file_exists($logDirectory)) {
                mkdir($logDirectory, self::$_defaultPermissions, TRUE);
            }

            if (file_exists($this->_logFile)) {
                if (!is_writable($this->_logFile)) {
                    $this->_logStatus = self::OPEN_FAILED;
                    $this->_messageQueue[] = "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.";
                    return;
                }
            }

            if (file_exists($this->_exceptionLogFile)) {
                if (!is_writable($this->_exceptionLogFile)){
                    $this->_logStatus = self::OPEN_FAILED;
                    $this->_messageQueue[] = "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.";
                    return;
                }
            }

            if (($this->_fileHandle = fopen($this->_logFile, "a" ))) {
                $this->_logStatus = self::LOG_OPEN;
                $this->_messageQueue[] = "The log file was opened successfully.";
            } else {
                $this->_logStatus = self::OPEN_FAILED;
                $this->_messageQueue[] = "The file could not be opened. Check permissions.";
            }

            if (($this->_exceptionFileHandle = fopen($this->_exceptionLogFile, "a" ))) {
                $this->_logStatus = self::LOG_OPEN;
                $this->_messageQueue[] = "The exception log file was opened successfully.";
            } else {
                $this->_logStatus = self::OPEN_FAILED;
                $this->_messageQueue[] = "The file could not be opened. Check permissions.";
            }
        }

        public function __destruct()
        {
            if ($this->_fileHandle) {
                fclose($this->_fileHandle);
            }
        }

        public function logInfo($line)
        {
            $this->log($line, self::INFO);
        }

        public function logDebug($line)
        {
            $this->log($line, self::DEBUG);
        }

        public function logWarn($line)
        {
            $this->log($line, self::WARN);
        }

        public function logError($line)
        {
            $this->log($line, self::ERROR, TRUE);
        }

        public function logFatal($line)
        {
            $this->log($line, KLogger::FATAL);
        }

        public function logException($line) {
            $this->log($line, self::ERROR, TRUE);
        }

        public function log($line, $priority, $is_exception=FALSE)
        {
            if ($this->_priority <= $priority) {
                $status = $this->_getTimeLine($priority);

                $this->writeFreeFormLine(''.$status.' - '.$line."\n", $is_exception);
            }
        }

        public function writeFreeFormLine($line, $is_exception)
        {
            if ($this->_logStatus == self::LOG_OPEN && $this->_priority != self::OFF) {
                if ($is_exception) {
                    if (fwrite($this->_exceptionFileHandle, $line) === FALSE) {
                        $this->_messageQueue[] = "The file could not be written to. Check that appropriate permissions have been set.";
                    }
                }

                if (fwrite($this->_fileHandle, $line) === FALSE)
                {
                    $this->_messageQueue[] = "The file could not be written to. Check that appropriate permissions have been set.";
                }
            }
        }

        private function _getTimeLine($level)
        {
            $time = date(self::$_dateFormat);

            switch ($level)
            {
                case self::DEBUG:
                    return "DEBUG  ".$time.' -';
                case self::INFO:
                    return "INFO   ".$time.' -';
                case self::WARN:
                    return "WARN   ".$time.' -';
                case self::ERROR:
                    return "ERROR  ".$time.' -';
                case self::FATAL:
                    return "ERROR  ".$time.' -';
                default:
                    return "ERROR  ".$time.' -';
            }
        }

    }


?>
