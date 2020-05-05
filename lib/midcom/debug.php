<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Monolog\Logger;

/**
 * This is a debugger class.
 *
 * Helps in debugging your code. It lets you decide which messages are logged into the
 * logfile by setting loglevels for the debugger and for each message.
 *
 * There are five loglevel constants you can use when setting the loglevel or when
 * logging messages:
 *
 * - MIDCOM_LOG_DEBUG
 * - MIDCOM_LOG_INFO
 * - MIDCOM_LOG_WARN
 * - MIDCOM_LOG_ERROR
 * - MIDCOM_LOG_CRIT
 *
 * @package midcom
 */
class midcom_debug
{
    /**
     * Current loglevel
     *
     * @var int
     */
    private $_loglevel;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Standard constructor
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->_loglevel = midcom::get()->config->get('log_level');
    }

    /**
     * Converts MidCOM log levels to Monolog
     */
    public static function convert_level(int $level) : int
    {
        $level_map = [
            MIDCOM_LOG_DEBUG => Logger::DEBUG,
            MIDCOM_LOG_INFO  => Logger::INFO,
            MIDCOM_LOG_WARN  => Logger::WARNING,
            MIDCOM_LOG_ERROR => Logger::ERROR,
            MIDCOM_LOG_CRIT  => Logger::CRITICAL
        ];
        return $level_map[$level] ?? $level;
    }

    /**
     * Set log level
     *
     * @param int $loglevel        New log level
     */
    public function set_loglevel($loglevel)
    {
        $this->_loglevel = $loglevel;
    }

    /**
     * Get log level
     */
    public function get_loglevel() : int
    {
        return $this->_loglevel;
    }

    public function log_php_error($loglevel)
    {
        $error = error_get_last();
        if (!empty($error['message'])) {
            $this->log('Last PHP error was: ' . $error['message'], $loglevel);
        }
    }

    /**
     * Log a message
     *
     * @param string $message    The message to be logged
     * @param int $loglevel        The log level
     */
    public function log($message, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (!$this->check_level($loglevel)) {
            return;
        }

        $context = [
            'caller' => $this->_get_caller()
        ];
        if (function_exists('xdebug_memory_usage')) {
            static $lastmem = 0;
            $context['time'] = xdebug_time_index();
            $context['curmem'] = xdebug_memory_usage();
            $context['delta'] = $context['curmem'] - $lastmem;
            $lastmem = $context['curmem'];
        }

        $this->logger->addRecord(self::convert_level($loglevel), trim($message), $context);
    }

    private function check_level(int $loglevel) : bool
    {
        return $this->_loglevel >= $loglevel;
    }

    private function _get_caller() : string
    {
        $return = '';
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        while ($bt) {
            $caller = array_shift($bt);
            if (   !array_key_exists('class', $caller)
                || $caller['class'] != midcom_debug::class) {
                if (   !array_key_exists('function', $bt[0])
                    || $bt[0]['function'] != 'require') {
                    $caller = array_shift($bt);
                }

                break;
            }
        }

        if (array_key_exists('class', $caller)) {
            $return .= $caller['class'] . '::';
        }
        if (   array_key_exists('function', $caller)
            && substr($caller['function'], 0, 6) != 'debug_') {
            $return .= $caller['function'];
        } else {
            $return .= $caller['file'] . ' (' . $caller['line']. ')';
        }
        return $return;
    }

    /**
     * Dump a variable
     *
     * @param string $message    The message to be logged
     * @param mixed $variable    The variable to be logged
     * @param int $loglevel        The log level
     */
    public function print_r($message, $variable, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (!$this->check_level($loglevel)) {
            return;
        }

        $cloner = new VarCloner();
        $dumper = new CliDumper();

        $varstring = $dumper->dump($cloner->cloneVar($variable), true);

        $this->log(trim($message) . ' ' . $varstring, $loglevel);
    }

    /**
     * Dump stack trace, only working when XDebug is present.
     *
     * @param string $message    The message to be logged
     * @param int $loglevel        The log level
     * @link http://www.xdebug.org/ xdebug.org
     */
    public function print_function_stack($message, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (!$this->check_level($loglevel)) {
            return;
        }

        if (function_exists('xdebug_get_function_stack')) {
            $stack = array_reverse(xdebug_get_function_stack());
        } else {
            $stack = debug_backtrace(0);
        }
        //the last two levels are already inside the debugging system, so skip those
        array_shift($stack);
        array_shift($stack);

        $stacktrace = "";
        foreach ($stack as $number => $frame) {
            $stacktrace .= $number + 1;
            if (isset($frame['file'])) {
                $stacktrace .= ": {$frame['file']}:{$frame['line']} ";
            }
            if (array_key_exists('class', $frame)) {
                if (!array_key_exists('function', $frame)) {
                    // workaround for what is most likely a bug in xdebug 2.4.rc3 and/or PHP 7.0.3
                    continue;
                }

                $stacktrace .= "{$frame['class']}::{$frame['function']}";
            } elseif (array_key_exists('function', $frame)) {
                $stacktrace .= $frame['function'];
            } else {
                $stacktrace .= 'require, include or eval';
            }
            $stacktrace .= "\n";
        }

        $this->log(trim($message) . "\n{$stacktrace}", $loglevel);
    }

    /**
     * Dump a variable's type
     *
     * @param string $message    The message to be logged
     * @param mixed $variable    The variable of which the type should be logged
     * @param int $loglevel        The log level
     */
    public function print_type($message, $variable, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (!$this->check_level($loglevel)) {
            return;
        }

        $type = gettype($variable);
        if ($type == "object") {
            $type .= ": " . get_class($variable);
        }

        $this->log(trim($message) . "\nVariable Type: $type", $loglevel);
    }

    /**
     * Dump the current memory usage and the delta to the last call of this function.
     * Useful for tracking memory leaks.
     *
     * Format will be:
     *
     * $curmem (delta $delta): $message
     *
     * @param string $message    The message to be logged
     * @param int $loglevel        The log level
     */
    public function print_dump_mem($message, $loglevel)
    {
        if (!$this->check_level($loglevel)) {
            return;
        }

        static $lastmem = 0;
        $curmem = memory_get_usage();
        $delta = $curmem - $lastmem;
        $lastmem = $curmem;

        $curmem = str_pad(number_format($curmem), 10, " ", STR_PAD_LEFT);
        $delta = str_pad(number_format($delta), 10, " ", STR_PAD_LEFT);
        $this->log("{$curmem} (delta {$delta}): {$message}", $loglevel);
    }
}
