<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * midcom_exception_handler
 *
 * Class for intercepting PHP errors and unhandled exceptions. Each fault is caught
 * and converted into Exception handled by $_MIDCOM->generate_error() with
 * code 500 thus can be customized and make user friendly.
 *
 * @package midcom
 */
class midcom_exception_handler
{
    /**
     * Catch an Exception and show it as a HTTP error
     *
     * @see midcom_application::generate_error()
     * @see midcom_exception_handler::show()
     */
    public static function handle_exception(Exception $e)
    {
        if ($e instanceOf midgardmvc_exception_unauthorized)
        {
            throw $e;
        }
        if (   !isset($_MIDCOM)
            || !$_MIDCOM)
        {
            // We got an exception before MidCOM has been initialized, show it anyway
            debug_add('Exception before MidCOM initialization: ' . $e->getMessage(), MIDCOM_LOG_ERROR);

            if (!_midcom_headers_sent())
            {
                _midcom_header('HTTP/1.0 500 Server Error');
            }

            _midcom_stop_request('Failed to initialize MidCOM: ' . $e->getMessage());
        }

        $trace = $e->getTraceAsString();

        debug_print_r('Exception occured, generating error, exception trace:', $trace, MIDCOM_LOG_INFO);
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $e->getMessage() . ". See the debug log for more details");
        // This will exit
    }

    /**
     * Catch a PHP error and turn it into an Exception to unify error handling
     */
    public static function handle_error($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $msg = "PHP Error: {$errstr} \n in {$errfile} line {$errline}";
        if (MIDCOM_XDEBUG)
        {
            ob_start();
            echo "\n";
            var_dump($errcontext);
            $msg .= ob_get_clean();
        }
        switch ($errno)
        {
            case E_ERROR:
            case E_USER_ERROR:
                // PONDER: use throw new ErrorException($errstr, 0, $errno, $errfile, $errline); in stead?
                throw new Exception($msg, $errno);
                // I don't think we reach this
                return  true;
                break;
        }
        // Leave other errors for PHP to take care of
        return false;
    }

    /**
     * Show an error page.
     *
     * This function is a small helper, that will display a simple HTML Page reporting
     * the error described by $httpcode and $message. The $httpcode is also used to
     * send an appropriate HTTP Response.
     *
     * The error pages can be customized by creating style elements named midcom_error_$httpcode.
     *
     * For a list of the allowed HTTP codes see the MIDCOM_ERR... constants
     *
     * <b>Note:</b> This function will call _midcom_stop_request() after it is finished.
     *
     * @link http://www.midgard-project.org/documentation/styling-midcom-error-pages/ Styling MidCOM error pages
     * @param int $httpcode        The error code to send.
     * @param string $message    The message to print.
     */
    public function show($httpcode, $message)
    {
        debug_add("An error has been generated: Code: {$httpcode}, Message: {$message}");
        debug_print_function_stack('Stacktrace:');

        // Send error to special log or recipient as per in configuration.
        $this->send($httpcode, $message);

        if (_midcom_headers_sent())
        {
            debug_add("Generate-Error was called after sending the HTTP Headers!", MIDCOM_LOG_ERROR);
            debug_add("Unexpected Error: {$httpcode} - {$message}", MIDCOM_LOG_ERROR);
            _midcom_stop_request("Unexpected Error, this should display an HTTP {$httpcode} - " . htmlentities($message));
        }

        switch ($httpcode)
        {
            case MIDCOM_ERROK:
                $header = "HTTP/1.0 200 OK";
                $title = "OK";
                $code = 200;
                break;

            case MIDCOM_ERRNOTFOUND:
                $header = "HTTP/1.0 404 Not Found";
                $title = "Not Found";
                $code = 404;
                break;

            case MIDCOM_ERRFORBIDDEN:
                if (!is_null($_MIDCOM->auth))
                {
                    // The auth service is running, we relay execution to it so that it can
                    // correctly display an authentication field.
                    $_MIDCOM->auth->access_denied($message);
                    // This will exit().
                }
                $header = "HTTP/1.0 403 Forbidden";
                $title = "Forbidden";
                $code = 403;
                break;

            case MIDCOM_ERRAUTH:
                $header = "HTTP/1.0 401 Unauthorized";
                $title = "Unauthorized";
                $code = 401;
                break;

            default:
                debug_add("Unknown Errorcode {$httpcode} encountered, assuming 500");
                // Fall-through

            case MIDCOM_ERRCRIT:
                $header = "HTTP/1.0 500 Server Error";
                $title = "Server Error";
                $code = 500;
                break;
        }
        _midcom_header ($header);
        _midcom_header ('Content-Type: text/html');

        if (isset($_MIDCOM->style))
        {
            $style = $_MIDCOM->style;
        }
        else
        {
            $style = new midcom_helper__styleloader();
        }

        $style->data['error_title'] = $title;
        $style->data['error_message'] = $message;
        $style->data['error_code'] = $code;
        $style->data['error_handler'] = $this;

        if (!$style->show_midcom('midcom_error_' . $code))
        {
            $style->show_midcom('midcom_error');
        }

        debug_add("Error Page output finished, exiting now");
        $_MIDCOM->cache->content->no_cache();
        $_MIDCOM->finish();
        _midcom_stop_request();
    }

    /**
     * Send error for processing.
     *
     * If the given error code has an action configured for it, that action will be
     * performed. This means that system administrators can request email notifications
     * of 500 "Internal Errors" and a special log of 404 "Not Founds".
     *
     * @param int $httpcode        The error code to send.
     * @param string $message    The message to print.
     */
    private function send($httpcode, $message)
    {
        if (   !isset($GLOBALS['midcom_config']['error_actions'][$httpcode])
            || !is_array($GLOBALS['midcom_config']['error_actions'][$httpcode])
            || !isset($GLOBALS['midcom_config']['error_actions'][$httpcode]['action']))
        {
            // No action specified for this error code, skip
            return;
        }

        // Prepare the message
        $msg = "{$_SERVER['REQUEST_METHOD']} request to {$_SERVER['REQUEST_URI']}: ";
        $msg .= "{$httpcode} {$message}\n";
        if (isset($_SERVER['HTTP_REFERER']))
        {
            $msg .= "(Referrer: {$_SERVER['HTTP_REFERER']})";
        }

        // Send as email handler
        if ($GLOBALS['midcom_config']['error_actions'][$httpcode]['action'] == 'email')
        {
            if (   !isset($GLOBALS['midcom_config']['error_actions'][$httpcode]['email'])
                || empty($GLOBALS['midcom_config']['error_actions'][$httpcode]['email']))
            {
                // No recipient specified, skip
                return;
            }

            if (!$_MIDCOM->componentloader->is_installed('org.openpsa.mail'))
            {
                debug_add("Email sending library org.openpsa.mail, used for error notifications is not installed", MIDCOM_LOG_WARN);
                return;
            }

            $_MIDCOM->load_library('org.openpsa.mail');

            $mail = new org_openpsa_mail();
            $mail->to = $GLOBALS['midcom_config']['error_actions'][$httpcode]['email'];
            $mail->from = "\"MidCOM error notifier\" <webmaster@{$_SERVER['SERVER_NAME']}>";
            $mail->subject = "[{$_SERVER['SERVER_NAME']}] {$msg}";
            $mail->body = "{$_SERVER['SERVER_NAME']}:\n{$msg}";

            $stacktrace = $this->get_function_stack();

            $mail->body .= "\n{$stacktrace}";

            if (!$mail->send())
            {
                debug_add("failed to send error notification email to {$mail->to}, reason: " . $mail->get_error_message(), MIDCOM_LOG_WARN);
            }

            return;
        }

        // Append to log file handler
        if ($GLOBALS['midcom_config']['error_actions'][$httpcode]['action'] == 'log')
        {
            if (   !isset($GLOBALS['midcom_config']['error_actions'][$httpcode]['filename'])
                || empty($GLOBALS['midcom_config']['error_actions'][$httpcode]['filename']))
            {
                // No log file specified, skip
                return;
            }

            if (   !is_writable($GLOBALS['midcom_config']['error_actions'][$httpcode]['filename'])
                && !is_writable(dirname($GLOBALS['midcom_config']['error_actions'][$httpcode]['filename'])))
            {
                debug_add("Error logging file {$GLOBALS['midcom_config']['error_actions'][$httpcode]['filename']} is not writable", MIDCOM_LOG_WARN);
                return;
            }

            // Add the line to the error-specific log
            $logger = new midcom_debug($GLOBALS['midcom_config']['error_actions'][$httpcode]['filename']);
            $logger->setLoglevel(MIDCOM_LOG_INFO);
            $logger->log($msg, MIDCOM_LOG_INFO);

            return;
        }
    }

    public function get_function_stack()
    {
        $stacktrace = '';
        if (MIDCOM_XDEBUG)
        {
            $stack = xdebug_get_function_stack();
        }
        else
        {
            $stack = array_reverse(debug_backtrace(false));
        }

        $stacktrace .= "Stacktrace:\n";
        foreach ($stack as $number => $frame)
        {
            $stacktrace .= $number + 1;
            $stacktrace .= ": {$frame['file']}:{$frame['line']} ";
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

        unset($stack);
        return $stacktrace;
    }
}

// Register the error and Exception handlers
// 2009-01-08 rambo: Seems like the boolean expression does not work as intended, see my changes in the error handler itself
set_error_handler(array('midcom_exception_handler', 'handle_error'), E_ALL & ~E_NOTICE | E_WARNING);
set_exception_handler(array('midcom_exception_handler', 'handle_exception'));
?>
