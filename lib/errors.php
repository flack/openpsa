<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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
     * @var Throwable
     */
    private $error;

    public function __construct(Throwable $error)
    {
        $this->error = $error;
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
     */
    public function render()
    {
        $httpcode = $this->error->getCode();
        $message = $this->error->getMessage();
        debug_print_r('Exception occurred: ' . $httpcode . ', Message: ' . $message . ', exception trace:', $this->error->getTraceAsString());

        if (!in_array($httpcode, [MIDCOM_ERROK, MIDCOM_ERRNOTFOUND, MIDCOM_ERRFORBIDDEN, MIDCOM_ERRAUTH, MIDCOM_ERRCRIT])) {
            debug_add("Unknown Errorcode {$httpcode} encountered, assuming 500");
            $httpcode = MIDCOM_ERRCRIT;
        }

        // Send error to special log or recipient as per configuration.
        $this->send($httpcode, $message);

        if (PHP_SAPI === 'cli') {
            throw $this->error;
        }

        if ($httpcode == MIDCOM_ERRFORBIDDEN) {
            return new midcom_response_accessdenied($message);
        }
        if ($httpcode == MIDCOM_ERRAUTH) {
            if ($this->error instanceof midcom_error_forbidden) {
                return new midcom_response_login($this->error->get_method());
            }

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
        $style->data['error_exception'] = $this->error;
        $style->data['error_handler'] = $this;

        ob_start();
        if (!$style->show_midcom('midcom_error_' . $httpcode)) {
            $style->show_midcom('midcom_error');
        }
        $content = ob_get_clean();

        return new Response($content, $httpcode);
    }

    /**
     * Send error for processing.
     *
     * If the given error code has an action configured for it, that action will be
     * performed. This means that system administrators can request email notifications
     * of 500 "Internal Errors" and a special log of 404 "Not Founds".
     */
    private function send(int $httpcode, string $message)
    {
        $error_actions = midcom::get()->config->get_array('error_actions');
        if (!isset($error_actions[$httpcode]['action'])) {
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

    private function _log(string $msg, array $config)
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
        $logger = new Logger(__CLASS__);
        $logger->pushHandler(new StreamHandler($config['filename']));
        $logger = new midcom_debug($logger);
        $logger->log($msg, MIDCOM_LOG_INFO);
    }

    private function _send_email(string $msg, array $config)
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
        $stack = $this->error->getTrace();

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
