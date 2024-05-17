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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

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
    private Logger $logger;

    /**
     * Standard constructor
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function on_request(RequestEvent $event)
    {
        if ($event->isMainRequest()) {
            $this->log("Start of MidCOM run " . $event->getRequest()->server->get('REQUEST_URI', ''));
        }
    }

    public function on_terminate(TerminateEvent $event)
    {
        $this->log("End of MidCOM run: " . $event->getRequest()->server->get('REQUEST_URI'));
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

    public function log_php_error(int $loglevel)
    {
        $error = error_get_last();
        if (!empty($error['message'])) {
            $this->log('Last PHP error was: ' . $error['message'], $loglevel);
        }
    }

    /**
     * Log a message
     */
    public function log(string $message, int $loglevel = MIDCOM_LOG_DEBUG)
    {
        $bt = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1);
        $this->logger->pushProcessor(function(array $record) use ($bt) {
            return $this->get_caller($record, $bt);
        });
        $this->logger->addRecord(self::convert_level($loglevel), trim($message));
        $this->logger->popProcessor();
    }

    /**
     * @internal
     */
    public function get_caller(array $record, array $bt) : array
    {
        $record['extra']['caller'] = '';
        while ($bt) {
            $caller = array_shift($bt);
            if (!in_array($caller['class'] ?? '', [midcom_debug::class])) {
                break;
            }
        }

        $record['extra']['caller'] .= $caller['file'] . ':' . $caller['line'] . '';
        return $record;
    }

    /**
     * Dump a variable
     */
    public function print_r(string $message, $variable, int $loglevel = MIDCOM_LOG_DEBUG)
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
     * Dump stack trace
     */
    public function print_function_stack(string $message, int $loglevel = MIDCOM_LOG_DEBUG)
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
