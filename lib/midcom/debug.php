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
     * @var Logger
     */
    private $logger;

    /**
     * Standard constructor
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
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
        $this->logger->pushProcessor([$this, 'get_caller']);
        $this->logger->addRecord(self::convert_level($loglevel), trim($message));
        $this->logger->popProcessor();
    }

    /**
     * @internal
     */
    public function get_caller(array $record) : array
    {
        $record['extra']['caller'] = '';
        $bt = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 3);

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
            $record['extra']['caller'] .= $caller['class'] . '::';
        }
        if (   array_key_exists('function', $caller)
            && substr($caller['function'], 0, 6) != 'debug_') {
            $record['extra']['caller'] .= $caller['function'];
        } else {
            $record['extra']['caller'] .= $caller['file'] . ' (' . $caller['line']. ')';
        }
        return $record;
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
        $this->logger->pushProcessor(function(array $record) use ($variable) {
            $cloner = new VarCloner();
            $dumper = new CliDumper();
            $record['message'] .= ' ' . $dumper->dump($cloner->cloneVar($variable), true);
            return $record;
        });
        $this->log($message, $loglevel);
        $this->logger->popProcessor();
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
        $this->logger->pushProcessor(function(array $record) {
            // the last four levels are already inside the debugging system, so skip those
            $stack = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 4);
            $stacktrace = "";
            foreach ($stack as $number => $frame) {
                $stacktrace .= $number + 1;
                if (isset($frame['file'])) {
                    $stacktrace .= ": {$frame['file']}:{$frame['line']} ";
                }
                if (array_key_exists('class', $frame)) {
                    $stacktrace .= "{$frame['class']}::{$frame['function']}";
                } elseif (array_key_exists('function', $frame)) {
                    $stacktrace .= $frame['function'];
                } else {
                    $stacktrace .= 'require, include or eval';
                }
                $stacktrace .= "\n";
            }

            $record['message'] .= "\n{$stacktrace}";
            return $record;
        });
        $this->log($message, $loglevel);
        $this->logger->popProcessor();
    }
}
