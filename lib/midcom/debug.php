<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

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
 * This file declares shortcuts like debug_add (see below).
 *
 * @package midcom
 */
class midcom_debug
{
    /**
     * Logfile name
     *
     * @var string
     */
    private $_filename;

    /**
     * Current loglevel
     *
     * @var int
     */
    private $_loglevel;

    /**
     * All available loglevels
     *
     * @var array
     */
    private $_loglevels = array
    (
        MIDCOM_LOG_DEBUG => "debug",
        MIDCOM_LOG_INFO  => "info",
        MIDCOM_LOG_WARN  => "warn",
        MIDCOM_LOG_ERROR => "error",
        MIDCOM_LOG_CRIT  => "critical"
    );

    /**
     * Flag which is true if the debugger is enabled.
     *
     * @var boolean
     */
    private $_enabled;

    /**
     * Access to installed FirePHP logger
     *
     * @var FirePHP
     */
    public $firephp = null;

    /**
     * Standard constructor
     */
    public function __construct($filename = null)
    {
        if (null === $filename)
        {
            $filename = midcom::get('config')->get('log_filename');
        }
        $this->_filename = $filename;
        $this->_enabled = true;
        $this->_loglevel = midcom::get('config')->get('log_level');

        // Load FirePHP logger if enabled
        if (midcom::get('config')->get('log_firephp'))
        {
            include_once('FirePHPCore/FirePHP.class.php');
            if (class_exists('FirePHP'))
            {
                $this->firephp = FirePHP::getInstance(true);
            }
        }
    }

    /**
     * Enable the Debugger
     */
    function enable()
    {
        $this->_enabled = true;
    }

    /**
     * Disable the Debugger
     */
    function disable()
    {
        $this->_enabled = false;
    }

    /**
     * Is the debugger enabled?
     *
     * @return boolean    Debugger state
     */
    function is_enabled()
    {
        return $this->_enabled;
    }

    /**
     * Set log level
     *
     * @param int $loglevel        New log level
     */
    function set_loglevel($loglevel)
    {
        $this->_loglevel = $loglevel;
    }

    /**
     * Get log level
     */
    function get_loglevel()
    {
        return $this->_loglevel;
    }

    /**
     * Log a message
     *
     * @param string $message    The message to be logged
     * @param int $loglevel        The log level
     */
    function log($message, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (   ! $this->_enabled
            || $this->_loglevel < $loglevel)
        {
            return;
        }

        $file = fopen($this->_filename, 'a+');

        if (function_exists('xdebug_memory_usage'))
        {
            static $lastmem = 0;
            $curmem = xdebug_memory_usage();
            $delta = $curmem - $lastmem;
            $lastmem = $curmem;

            $prefix = sprintf("%s (%012.9f, %9s, %7s):\t",
                date('M d Y H:i:s'),
                xdebug_time_index(),
                number_format($curmem, 0, ',', '.'),
                number_format($delta, 0, ',', '.')
            );
        }
        else
        {
            $prefix = date('M d Y H:i:s') . "\t";
        }

        if (array_key_exists($loglevel, $this->_loglevels))
        {
            $prefix .= '[' . $this->_loglevels[$loglevel] . '] ';
        }

        //find the proper caller
        $bt = debug_backtrace(false);
        $prefix .= $this->_get_caller($bt);
        fputs($file, $prefix . trim($message) . "\n");
        fclose($file);

        if (   $this->firephp
            && !_midcom_headers_sent())
        {
            try
            {
                $log_method = $this->_loglevels[$loglevel];
                if ($loglevel == MIDCOM_LOG_DEBUG)
                {
                    $log_method = 'log';
                }
                if ($loglevel == MIDCOM_LOG_CRIT)
                {
                    $log_method = 'error';
                }
                $this->firephp->$log_method($message);
            }
            catch (Exception $e)
            {
                // Ignore FirePHP errors for now
            }
        }
    }

    private function _get_caller($bt)
    {
        $return = '';

        while ($bt)
        {
            $caller = array_shift($bt);
            if (   array_key_exists('class', $caller)
                && $caller['class'] == 'midcom_debug')
            {
                continue;
            }

            if (   !array_key_exists('function', $bt[0])
                || $bt[0]['function'] != 'require')
            {
                $caller = array_shift($bt);
            }

            break;
        }

        if (array_key_exists('class', $caller))
        {
            $return .= $caller['class'] . '::';
        }
        if (   array_key_exists('function', $caller)
            && substr($caller['function'], 0, 6) != 'debug_')
        {
            $return .= $caller['function'] . ': ';
        }
        else
        {
            $return .= $caller['file'] . ' (' . $caller['line']. '): ';
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
    function print_r($message, $variable, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (   !$this->_enabled
            || $this->_loglevel < $loglevel)
        {
            return;
        }

        ob_start();
        print_r($variable);
        $varstring = ob_get_contents();
        ob_end_clean();

        $type = gettype($variable);
        if ($type == "object")
        {
            $type .= ": " . get_class($variable);
        }

        $this->log(trim($message) . "\nVariable Type: $type\n" . $varstring, $loglevel);
    }

    /**
     * Dump stack trace, only working when XDebug is present.
     *
     * @param string $message    The message to be logged
     * @param int $loglevel        The log level
     * @link http://www.xdebug.org/ xdebug.org
     */
    function print_function_stack($message, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (   ! $this->_enabled
            || $this->_loglevel < $loglevel)
        {
            return;
        }

        if (function_exists('xdebug_get_function_stack'))
        {
            $stack = array_reverse(xdebug_get_function_stack());
        }
        else
        {
            $stack = debug_backtrace(false);
        }
        //the last two levels are already inside the debugging system, so skip those
        array_shift($stack);
        array_shift($stack);

        $stacktrace = "";
        foreach ($stack as $number => $frame)
        {
            $stacktrace .= $number + 1;
            if (isset($frame['file']))
            {
                $stacktrace .= ": {$frame['file']}:{$frame['line']} ";
            }
            if (array_key_exists('class', $frame))
            {
                $stacktrace .= "{$frame['class']}::{$frame['function']}";
            }
            else if (array_key_exists('function', $frame))
            {
                $stacktrace .= $frame['function'];
            }
            else
            {
                $stacktrace .= 'require, include or eval';
            }
            $stacktrace .= "\n";
        }

        $this->log (trim ($message) . "\n{$stacktrace}", $loglevel);
    }

    /**
     * Dump a variables type (by reference)
     *
     * @param string $message    The message to be logged
     * @param mixed &$variable    The variable of which the type should be logged
     * @param int $loglevel        The log level
     */
    function print_type ($message, &$variable, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (   ! $this->_enabled
                || $this->_loglevel < $loglevel)
        {
            return;
        }

        $type = gettype($variable);
        if ($type == "object")
        {
            $type .= ": " . get_class($variable);
        }

        $this->log (trim ($message) . "\nVariable Type: $type", $loglevel);
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
    function print_dump_mem ($message, $loglevel = MIDCOM_LOG_DEBUG)
    {
        if (   ! $this->_enabled
            || $this->_loglevel < $loglevel)
        {
            return;
        }

        if (!function_exists('memory_get_usage'))
        {
            return false;
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

/**
 * Shortcut: Log a message
 *
 * @param string $message    The message to be logged
 * @param int $loglevel        The log level
 */
function debug_add($message, $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get('debug')->log($message, $loglevel);
}

/**
 * Shortcut: Dump a variable
 *
 * @param string $message    The message to be logged
 * @param mixed $variable    The variable to be logged
 * @param int $loglevel        The log level
 */
function debug_print_r($message, $variable, $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get('debug')->print_r($message, $variable, $loglevel);
}

/**
 * Shortcut: Create a stack trace and dump it.
 *
 * @param string $message    The message to be logged
 * @param int $loglevel        The log level
 */
function debug_print_function_stack($message, $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get('debug')->print_function_stack($message, $loglevel);
}

/**
 * Shortcut: Dump a variables type (by reference)
 *
 * @param string $message    The message to be logged
 * @param mixed &$variable    The variable of which the type should be logged
 * @param int $loglevel        The log level
 */
function debug_print_type($message, &$variable, $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get('debug')->print_type($message, $variable, $loglevel);
}

/**
 * Shortcut: Dump the current memory usage and the delta to the last call of this function.
 *
 * @param string $message    The message to be logged
 * @param int $loglevel        The log level
*/
function debug_dump_mem($message, $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get('debug')->print_dump_mem($message, $loglevel);
}
?>