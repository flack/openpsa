<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\api\error\exception as mgd_exception;

/**
 * midcom_exception_handler
 *
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
     * Register the error and Exception handlers
     */
    public static function register()
    {
        if (!defined('OPENPSA2_UNITTEST_RUN')) {
            $handler = new self;
            set_error_handler([$handler, 'handle_error'], E_ALL ^ (E_NOTICE | E_WARNING));
            set_exception_handler([$handler, 'handle_exception']);
        }
    }

    private function _generate_http_response()
    {
        if (midcom::get()->config->get('auth_login_form_httpcode') == 200) {
            _midcom_header('HTTP/1.0 200 OK');
            return;
        }
        _midcom_header('HTTP/1.0 403 Forbidden');
    }

    /**
     * This is called by throw new midcom_error_forbidden(...) if and only if
     * the headers have not yet been sent. It will display the error message and appends the
     * login form below it.
     *
     * The function will clear any existing output buffer, and the sent page will have the
     * 403 - Forbidden HTTP Status. The login will relocate to the same URL, so it should
     * be mostly transparent.
     *
     * The login message shown depends on the current state:
     * - If an authentication attempt was done but failed, an appropriated wrong user/password
     *   message is shown.
     * - If the user is authenticated, a note that he might have to switch to a user with more
     *   privileges is shown.
     * - Otherwise, no message is shown.
     *
     * This function will exit() unconditionally.
     *
     * If the style element <i>midcom_services_auth_access_denied</i> is defined, it will be shown
     * instead of the default error page. The following variables will be available in the local
     * scope:
     *
     * $title contains the localized title of the page, based on the 'access denied' string ID of
     * the main MidCOM L10n DB. $message will contain the notification what went wrong and
     * $login_warning will notify the user of a failed login. The latter will either be empty
     * or enclosed in a paragraph with the CSS ID 'login_warning'.
     *
     * @param string $message The message to show to the user.
     */
    private function access_denied($message)
    {
        debug_print_function_stack("access_denied was called from here:");

        // Determine login message
        $login_warning = '';
        if (!is_null(midcom::get()->auth->user)) {
            // The user has insufficient privileges
            $login_warning = midcom::get()->i18n->get_string('login message - insufficient privileges', 'midcom');
        } elseif (midcom::get()->auth->auth_credentials_found) {
            $login_warning = midcom::get()->i18n->get_string('login message - user or password wrong', 'midcom');
        }

        $title = midcom::get()->i18n->get_string('access denied', 'midcom');

        // Emergency check, if headers have been sent, kill MidCOM instantly, we cannot output
        // an error page at this point (dynamic_load from site style? Code in Site Style, something
        // like that)
        if (_midcom_headers_sent()) {
            debug_add('Cannot render an access denied page, page output has already started. Aborting directly.', MIDCOM_LOG_INFO);
            echo "<br />{$title}: {$login_warning}";
            debug_add("Emergency Error Message output finished, exiting now");
            midcom::get()->finish();
        }

        // Drop any output buffer first.
        midcom::get()->cache->content->disable_ob();

        $this->_generate_http_response();

        midcom::get()->cache->content->no_cache();

        midcom::get()->style->data['midcom_services_auth_access_denied_message'] = $message;
        midcom::get()->style->data['midcom_services_auth_access_denied_title'] = $title;
        midcom::get()->style->data['midcom_services_auth_access_denied_login_warning'] = $login_warning;

        midcom::get()->style->show_midcom('midcom_services_auth_access_denied');

        debug_add("Error Page output finished, exiting now");
        midcom::get()->finish();
    }

    /**
     * Catch an Exception and show it as a HTTP error
     *
     * @see midcom_exception_handler::show()
     */
    public function handle_exception($e)
    {
        $this->_exception = $e;
        $trace = $e->getTraceAsString();
        $httpcode = $e->getCode();
        $message = $e->getMessage();
        debug_print_r('Exception occurred: ' . $httpcode . ', Message: ' . $message . ', exception trace:', $trace);

        if (!in_array($httpcode, [MIDCOM_ERROK, MIDCOM_ERRNOTFOUND, MIDCOM_ERRFORBIDDEN, MIDCOM_ERRAUTH, MIDCOM_ERRCRIT])) {
            debug_add("Unknown Errorcode {$httpcode} encountered, assuming 500");
            $httpcode = MIDCOM_ERRCRIT;
        }

        // Send error to special log or recipient as per in configuration.
        $this->send($httpcode, $message);

        if (PHP_SAPI !== 'cli') {
            $this->show($httpcode, $message);
            // This will exit
        }
        throw $e;
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
     * Show an error page.
     *
     * This will display a simple HTML Page reporting the error described by $httpcode and $message.
     * The $httpcode is also used to send an appropriate HTTP Response.
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
        if (!$this->_exception) {
            debug_add("An error has been generated: Code: {$httpcode}, Message: {$message}");
            debug_print_function_stack('Stacktrace:');
        }

        if (_midcom_headers_sent()) {
            debug_add("Generate-Error was called after sending the HTTP Headers!", MIDCOM_LOG_ERROR);
            debug_add("Unexpected Error: {$httpcode} - {$message}", MIDCOM_LOG_ERROR);
            _midcom_stop_request("Unexpected Error, this should display an HTTP {$httpcode} - " . htmlentities($message));
        }

        switch ($httpcode) {
            case MIDCOM_ERROK:
                $header = "HTTP/1.0 200 OK";
                $title = "OK";
                break;

            case MIDCOM_ERRNOTFOUND:
                $header = "HTTP/1.0 404 Not Found";
                $title = "Not Found";
                break;

            case MIDCOM_ERRFORBIDDEN:
                // show access denied
                $this->access_denied($message);

                $header = "HTTP/1.0 403 Forbidden";
                $title = "Forbidden";
                break;

            case MIDCOM_ERRAUTH:
                $header = "HTTP/1.0 401 Unauthorized";
                $title = "Unauthorized";
                break;

            case MIDCOM_ERRCRIT:
                $header = "HTTP/1.0 500 Server Error";
                $title = "Server Error";
                break;
        }
        _midcom_header($header);
        _midcom_header('Content-Type: text/html');

        $style = midcom::get()->style;

        $style->data['error_title'] = $title;
        $style->data['error_message'] = $message;
        $style->data['error_code'] = $httpcode;
        $style->data['error_exception'] = $this->_exception;
        $style->data['error_handler'] = $this;

        if (!$style->show_midcom('midcom_error_' . $httpcode)) {
            $style->show_midcom('midcom_error');
        }

        debug_add("Error Page output finished, exiting now");
        midcom::get()->cache->content->no_cache();
        midcom::get()->finish();
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
            $stack = array_reverse(debug_backtrace(false));
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
                $line .= $frame['function'];
            } elseif (array_key_exists('function', $frame)) {
                $line .= $frame['function'];
            } else {
                $line .= 'require, include or eval';
            }
            $stacktrace[] = $line;
        }

        return $stacktrace;
    }
}

/**
 * Basic MidCOM exception
 *
 * @package midcom
 */
class midcom_error extends Exception
{
    public function __construct($message, $code = MIDCOM_ERRCRIT)
    {
        parent::__construct($message, $code);
    }

    public function log($loglevel = MIDCOM_LOG_ERROR)
    {
        debug_add($this->getMessage(), $loglevel);
    }
}

/**
 * MidCOM not found exception
 *
 * @package midcom
 */
class midcom_error_notfound extends midcom_error
{
    public function __construct($message, $code = MIDCOM_ERRNOTFOUND)
    {
        parent::__construct($message, $code);
    }

    public function log($loglevel = MIDCOM_LOG_INFO)
    {
        parent::log($loglevel);
    }
}

/**
 * MidCOM unauthorized exception
 *
 * @package midcom
 */
class midcom_error_forbidden extends midcom_error
{
    public function __construct($message = null, $code = MIDCOM_ERRFORBIDDEN)
    {
        if (is_null($message)) {
            $message = midcom::get()->i18n->get_string('access denied', 'midcom');
        }
        parent::__construct($message, $code);
    }

    public function log($loglevel = MIDCOM_LOG_DEBUG)
    {
        parent::log($loglevel);
    }
}

/**
 * MidCOM wrapped Midgard exception
 *
 * @package midcom
 */
class midcom_error_midgard extends midcom_error
{
    public function __construct(mgd_exception $e, $id = null)
    {
        //catch last error which might be from dbaobject
        $last_error = midcom_connection::get_error();

        if (!is_null($id)) {
            if ($last_error === MGD_ERR_NOT_EXISTS) {
                $code = MIDCOM_ERRNOTFOUND;
                $message = "The object with identifier {$id} was not found.";
            } elseif ($last_error == MGD_ERR_ACCESS_DENIED) {
                $code = MIDCOM_ERRFORBIDDEN;
                $message = midcom::get()->i18n->get_string('access denied', 'midcom');
            } elseif ($last_error == MGD_ERR_OBJECT_DELETED) {
                $code = MIDCOM_ERRNOTFOUND;
                $message = "The object with identifier {$id} was deleted.";
            }
        }
        //If other options fail, go for the server error
        if (!isset($code)) {
            $code = MIDCOM_ERRCRIT;
            $message = $e->getMessage();
        }
        parent::__construct($message, $code);
    }

    public function log($loglevel = MIDCOM_LOG_WARN)
    {
        parent::log($loglevel);
    }
}
