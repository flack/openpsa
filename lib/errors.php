<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Debug\Exception\FatalErrorException;

/**
 * Class for intercepting PHP errors and unhandled exceptions. Each fault is caught
 * and converted into Exception handled by midcom_exception_handler::show() with
 * code 500 thus can be customized and make user friendly.
 *
 * @package midcom
 */
class midcom_exception_handler
{
    /**
     * Holds the current exception
     *
     * @var Exception
     */
    private $_exception;

    /**
     * @var HttpKernel
     */
    private $kernel;

    /**
     * Register the error and Exception handlers
     */
    public static function register(HttpKernel $kernel)
    {
        if (!defined('OPENPSA2_UNITTEST_RUN')) {
            $handler = new self;
            $handler->set_kernel($kernel);
            set_error_handler([$handler, 'handle_error'], E_ALL ^ (E_NOTICE | E_WARNING));
            set_exception_handler([$handler, 'handle_exception']);
        }
    }

    public function set_kernel(HttpKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * This is mostly there because symfony doesn't catch Errors for some reason
     *
     * @param Throwable $error
     * @throws Exception
     */
    public function handle_exception($error)
    {
        if ($error instanceof Error) {
            $exception = new FatalErrorException($error->getMessage(), $error->getCode(), 0, $error->getFile(), $error->getLine(), null, true, $error->getTrace());
            $this->kernel->terminateWithException($exception);
        } else {
            throw $error;
        }
    }

    /**
     * Catch a PHP error and turn it into an Exception to unify error handling
     */
    public function handle_error($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $msg = "PHP Error: {$errstr} \n in {$errfile} line {$errline}";
        if (!empty($errcontext)) {
            debug_print_r('Error context', $errcontext);
        }

        // PONDER: use throw new ErrorException($errstr, 0, $errno, $errfile, $errline); instead?
        throw new midcom_error($msg, $errno);
    }

    /**
     * Render an error response.
     *
     * This will display a simple HTML Page reporting the error described by $httpcode and $message.
     * The $httpcode is also used to send an appropriate HTTP Response.
     *
     * The error pages can be customized by creating style elements named midcom_error_$httpcode.
     *
     * For a list of the allowed HTTP codes see the MIDCOM_ERR... constants
     *
     * @param Exception $e The exception we're working with
     */
    public function render($e)
    {
        $this->_exception = $e;
        $httpcode = $e->getCode();
        $message = $e->getMessage();
        debug_print_r('Exception occurred: ' . $httpcode . ', Message: ' . $message . ', exception trace:', $e->getTraceAsString());

        if (!in_array($httpcode, [MIDCOM_ERROK, MIDCOM_ERRNOTFOUND, MIDCOM_ERRFORBIDDEN, MIDCOM_ERRAUTH, MIDCOM_ERRCRIT])) {
            debug_add("Unknown Errorcode {$httpcode} encountered, assuming 500");
            $httpcode = MIDCOM_ERRCRIT;
        }

        // Send error to special log or recipient as per configuration.
        $this->send($httpcode, $message);

        if (PHP_SAPI === 'cli') {
            throw $e;
        }

        if ($httpcode == MIDCOM_ERRFORBIDDEN) {
            return new midcom_response_accessdenied($message);
        }
        if ($httpcode == MIDCOM_ERRAUTH) {
            return new midcom_response_login;
        }

        switch ($httpcode) {
            case MIDCOM_ERROK:
                $title = "OK";
                break;

            case MIDCOM_ERRNOTFOUND:
                $title = "Not Found";
                break;

            case MIDCOM_ERRCRIT:
                $title = "Server Error";
                break;
        }

        $style = midcom::get()->style;

        $style->data['error_title'] = $title;
        $style->data['error_message'] = $message;
        $style->data['error_code'] = $httpcode;
        $style->data['error_exception'] = $e;
        $style->data['error_handler'] = $this;

        return new StreamedResponse(function() use ($httpcode, $style) {
            if (!$style->show_midcom('midcom_error_' . $httpcode)) {
                $style->show_midcom('midcom_error');
            }
        }, $httpcode);
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
        $error_actions = midcom::get()->config->get('error_actions');
        if (   !isset($error_actions[$httpcode])
            || !isset($error_actions[$httpcode]['action'])) {
            // No action specified for this error code, skip
            return;
        }

        // Prepare the message
        $msg = "{$_SERVER['REQUEST_METHOD']} request to {$_SERVER['REQUEST_URI']}: ";
        $msg .= "{$httpcode} {$message}\n";
        if (isset($_SERVER['HTTP_REFERER'])) {
            $msg .= "(Referrer: {$_SERVER['HTTP_REFERER']})\n";
        }

        // Send as email handler
        if ($error_actions[$httpcode]['action'] == 'email') {
            $this->_send_email($msg, $error_actions[$httpcode]);
        }
        // Append to log file handler
        elseif ($error_actions[$httpcode]['action'] == 'log') {
            $this->_log($msg, $error_actions[$httpcode]);
        }
    }

    private function _log($msg, array $config)
    {
        if (empty($config['filename'])) {
            // No log file specified, skip
            return;
        }

        if (   !is_writable($config['filename'])
            && !is_writable(dirname($config['filename']))) {
            debug_add("Error logging file {$config['filename']} is not writable", MIDCOM_LOG_WARN);
            return;
        }

        // Add the line to the error-specific log
        $logger = new midcom_debug($config['filename']);
        $logger->set_loglevel(MIDCOM_LOG_INFO);
        $logger->log($msg, MIDCOM_LOG_INFO);
    }

    private function _send_email($msg, array $config)
    {
        if (empty($config['email'])) {
            // No recipient specified, skip
            return;
        }

        if (!midcom::get()->componentloader->is_installed('org.openpsa.mail')) {
            debug_add("Email sending library org.openpsa.mail, used for error notifications is not installed", MIDCOM_LOG_WARN);
            return;
        }

        $mail = new org_openpsa_mail();
        $mail->to = $config['email'];
        $mail->from = "\"MidCOM error notifier\" <webmaster@{$_SERVER['SERVER_NAME']}>";
        $mail->subject = "[{$_SERVER['SERVER_NAME']}] {$msg}";
        $mail->body = "{$_SERVER['SERVER_NAME']}:\n{$msg}";

        $stacktrace = $this->get_function_stack();

        $mail->body .= "\n" . implode("\n", $stacktrace);

        if (!$mail->send()) {
            debug_add("failed to send error notification email to {$mail->to}, reason: " . $mail->get_error_message(), MIDCOM_LOG_WARN);
        }
    }

    public function get_function_stack()
    {
        if ($this->_exception) {
            $stack = $this->_exception->getTrace();
        } elseif (function_exists('xdebug_get_function_stack')) {
            $stack = xdebug_get_function_stack();
        } else {
            $stack = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        }

        $stacktrace = [];
        foreach ($stack as $number => $frame) {
            $line = $number + 1;
            if (array_key_exists('file', $frame)) {
                $file = str_replace(MIDCOM_ROOT, '[midcom_root]', $frame['file']);
                $line .= ": {$file}:{$frame['line']}  ";
            } else {
                $line .= ': [internal]  ';
            }
            if (array_key_exists('class', $frame)) {
                $line .= $frame['class'];
                if (array_key_exists('type', $frame)) {
                    $line .= $frame['type'];
                } else {
                    $line .= '::';
                }
            }
            if (array_key_exists('function', $frame)) {
                $line .= $frame['function'];
            } else {
                $line .= 'require, include or eval';
            }
            $stacktrace[] = $line;
        }

        return $stacktrace;
    }
}
