<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: mailtemplate.php 25326 2010-03-18 17:19:32Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is a E-Mail template engine. It basically takes a template out of a
 * mailtemplate datamanager field, fills it in with the parameters that have been
 * set and sends it using the PEAR Mail classes.
 *
 * <b>Prerequisites</b>
 *
 * - PEAR Package: Mail
 * - PEAR Package: Mail_mime
 *
 * <b>E-Mail template language</b>
 *
 * Four types of variables can be inserted into the E-Mail's subject or body.
 * Every value has an associated key, which is searched as array key in the
 * parameter array. Key-Names are matched case-sensitive. Note, that of course
 * the double-quotes in the identifier examples are only to distinguish them
 * from the flowed text.
 *
 * 1. String values
 *
 * They are identified by "__KEY__" and are inserted directly. There is no line
 * wrapping.
 *
 * 2. Associative arrays
 *
 * If you want to pass an array as parameter, ensure, that both key and value are
 * convertible to a string by PHP implicitly. Ideally, you have only strings, of
 * course. In the following example, "KEY" refers to the key of the array within
 * the parameter array, and "SUBKEY" refers to the key of a value within the
 * actual array.
 *
 * Again, you can access the (whole) array using "__KEY__". In that case you will
 * get a formatted output of all keys and values, consisting of "SUBKEY: VALUE"
 * entries. The value gets word-wrapped and indented automatically at about 76
 * chars to keep the output easily readable.
 *
 * If you want to access a specific value from this array, you have to use
 * "__KEY_SUBKEY__" to identify it. This syntax is treated like a string value.
 *
 * 3. Generic objects
 *
 * You can pass any object as a value. In this case, the same semantic as with an
 * Array can be used to access the object: "__KEY__" will give you a complete
 * dump, while "__KEY_SUBKEY__" accesses a specific property.
 *
 * The complete dump will omit all properties that are prefixed with an "_";
 * according to the MidCOM namespace conventions, these are private members
 * of a class and should not be touched. You can still access them with the
 * direct indexer, though this is strongly discouraged within a MidCOM context.
 * Also note, that variables with more then one underscore as a prefix might cause
 * trouble with the regular expression used to parse the template.
 *
 * 4. Datamanager objects
 *
 * This is a special mode, usable to easily interface with the MidCOM datamanager.
 *
 * Again, this is to be used like an Array value, supporting both "__KEY__" and
 * "__KEY_SUBKEY__". This mode will use the CSV (string-only) representation of
 * the various datatypes as a replacement value. This way even BLOB-Types and
 * the like can be safely put into an E-Mail.
 *
 * The dump syntax will omit all values tagged as "hidden", they
 * are accessible through the element indexers though.
 *
 * Variables with more then one underscore as a prefix might cause trouble with
 * the regular expression used to parse the template, so you should not rely on
 * them.
 *
 * <b>Character encoding issues</b>
 *
 * The template engine tries its best to work with the various character
 * encodings it might encounter. For a start, it will try to convert each value
 * into the character set that has been specified in the E-Mail template.
 *
 * You can avoid many problems, if you specify, what encoding the parameters you
 * pass to the class are in, the set_parameters call has an (optional) parameter
 * for this.
 *
 * If you omit this, the engine tries to detect the encoding of the values using
 * the Multi-Byte function mb_detect_encoding. If the
 * encoding can't be detected or is one of the ISO-8859-* encoding, it defaults
 * to ISO-8859-15 (Latin-1). Both might very well lead into a really corrupt
 * E-Mail. So try to specify the source encoding, if possible.
 *
 * <b>Sending Mails</b>
 *
 * Currently, the engine will send the E-Mails through the PHP mail function, so
 * ensure that it is correctly configured. In the future alternated backends (out
 * of the PEAR Mail packages) might become available through MidCOM.
 *
 * <b>Example usage code</b>
 *
 * <code>
 * $template = new midcom_helper_mailtemplate($this->_config_dm->data["mail_newreservation"]);
 * $parameters = Array(
 *     "RESOURCE" => $this->_resource->dm,
 *        "RESERVATION" => $this->dm,
 *     "ISOSTART" => $this->dm->data["start"]["strfulldate"],
 *     "ISOEND" => $this->dm->data["end"]["strfulldate"],
 *     "LOCALSTART" => $this->dm->data["start"]["local_strfulldate"],
 *     "LOCALEND" => $this->dm->data["end"]["local_strfulldate"],
 * );
 * $template->set_parameters($parameters);
 * $template->parse();
 * $failed = $template->send($this->dm->data["email"]);
 * if ($failed > 0)
 * {
 *     debug_add("$failed E-Mails could not be sent.", MIDCOM_LOG_WARN);
 * }
 * </code>
 *
 * This code could for example use a Template subject / body like this:
 *
 * <pre>
 * Subject: New Reservation for __RESOURCE_name__
 *
 * Your reservation has been received, you will receive a confirmation E-Mail shortly:
 *
 * Start: __ISOSTART__
 * End: __ISOEND__
 * __RESERVATION__
 * </pre>
 *
 * @package midcom
 */
class midcom_helper_mailtemplate
{

    /**
     * The Mail template, a mailtemplate datamanager type.
     *
     * @var Array
     * @access private
     */
    var $_template;

    /**
     * The parameters to use for the Mail template.
     *
     * @var Array
     * @access private
     */
    var $_parameters;

    /**
     * The parameter character encoding hint, if unknown, this is null.
     *
     * @var string
     * @access private
     */
    var $_parameters_encoding;

    /**
     * Flag indicating if the template has already been parsed
     *
     * @var boolean
     * @access private
     */
    var $_parsed;

    /**
     * The parsed subject.
     *
     * @var string
     * @access private
     */
    var $_subject;

    /**
     * The parsed body.
     *
     * @var string
     * @access private
     */
    var $_body;

    /**
     * PEAR class in use.
     *
     * @var Mail_mime
     * @access private
     */
    var $_mail_mime;

    /**
     * PEAR class in use.
     *
     * @var Mail
     * @access private
     */
    var $_mail;

    /**
     * Number of E-Mails sent successfully.
     * Note, that due to the design of the PEAR Mime classes, this is
     * not reliably.
     *
     * @var int
     */
    var $sent;

    /**
     * Number of E-Mails that failed sending.
     * Note, that due to the design of the PEAR Mime classes, this is
     * not reliably.
     *
     * @var int
     */
    var $failed;

    /**
     * I18n service object reference, used for charset conversions.
     *
     * @access private
     * @var midcom_helper_service_i18n
     */
    var $_i18n = null;

    /**
     * Constructs the template engine on the base of the passed template.
     *
     * This will load the pear modules Mail and Mail_Mime.
     *
     * @param Array $template    The value of a Datamanager mailtemplate type
     */
    function __construct ($template)
    {
        /* First, we include all necessary PEAR classes */
        debug_add('Mailtemplate::c\'tor: loading PEAR package Mail (if not already required)...');
        require_once("Mail.php");
        debug_add('Mailtemplate::c\'tor: loading PEAR package Mail_Mime (if not already required)...');
        require_once("Mail/mime.php");

        /* Now we initialize the whole stuff */
        $this->_template = $template;
        $this->_parameters = Array();
        $this->_parameters_encoding = null;
        $this->_subject = "";
        $this->_body = "";
        $this->_parsed = false;
        $this->_i18n = $_MIDCOM->get_service('i18n');

        $this->_mail_mime = new Mail_mime("\n");
        $this->_mail = Mail::factory('mail');
    }

    /**
     * This will set the template parameters accordingly.
     *
     * If you omit the encoding, a detection will be attempted (see above).
     * To avoid trouble, try to specify an encoding every time.
     *
     * @param Array $parameters    The template parameters.
     * @param string $encoding    The character encoding in which $parameters are passed.
     */
    function set_parameters ($parameters, $encoding = null)
    {
        $this->_parameters = $parameters;
        $this->_parameters_encoding = $encoding;
    }

    /**
     * Returns the currently set parameters.
     *
     * @return Array    Current parameter set.
     */
    function get_parameters ()
    {
        return $this->_parameters;
    }

    /**
     * Retrieve the current parameter character encoding.
     *
     * @return string    The encoding name.
     */
    function get_parameters_encoding ()
    {
        return $this->_parameters_encoding;
    }

    /**
     * Parses the template and generates the message body and subject.
     *
     * Internally, it relies heavily on Perl Regular Expressions to
     * replace the template parameters with their values.
     */
    function parse ()
    {
        /* For each parameter, add values to the preg search and
         * replace arrays, and, where necessary, do some charset
         * conversions.
         */

        $patterns = Array();
        $replacements = Array();

        foreach ($this->_parameters as $key => $value)
        {
            /* Different parameters:
             * - Single value (anything that is neither an object or an array), replace directly
             * - Array and objects, allow access to subkeys or dump the whole thing.
             * - Datamanager objects have special treatment with datatype recognition.
             *
             * Syntax for single values:
             * __KEY__ will be replaced by its value
             *
             * Syntax for arrays, objects and datamanager classes:
             * __KEY__ will yield a dump of the complete object
             * __KEY_SUBKEY__ will yield the value of the element SUBKEY of the given
             *  array or object
             *
             * Datamanager notes: Currently the get_csv interface to get a string
             * representation of a given datatype. Should be ok for now, at least
             * until the Datamanager v3 arrives.
             *
             * Note, that all key's will be compared case-insensitive.
             */
            if (is_array($value))
            {
                $patterns[] = "/__{$key}__/";
                $replacements[] = $this->_charset_convert($this->_format_array($value));
                $patterns[] = "/__{$key}_([^ \.>\"-]*?)__/e";
                $replacements[] =  '$this->_charset_convert($this->_parameters["' . $key . '"]["\1"])';
            }
            else if (is_object($value))
            {
                if (is_a($value, "midcom_helper_datamanager"))
                {
                    $patterns[] = "/__{$key}__/";
                    $replacements[] = $this->_charset_convert($this->_format_dm($value));
                    $patterns[] = "/__{$key}_([^ \.>\"-]*?)__/e";
                    $replacements[] = '$this->_charset_convert($this->_parameters["'
                                      . $key . '"]->_datatypes["\1"]->get_csv_data())';
                }
                else
                {
                    $patterns[] = "/__{$key}__/";
                    $replacements[] = $this->_charset_convert($this->_format_object($value));
                    $patterns[] = "/__{$key}_([^ \.>\"-]*?)__/e";
                    $replacements[] = '$this->_charset_convert($this->_parameters["'
                                      . $key . '"]->\1)';
                }
            }
            else
            {
                $patterns[] = "/__{$key}__/";
                $replacements[] = $this->_charset_convert($value);
            }
        }

        debug_print_r("Complete list of patterns:", $patterns);
        debug_print_r("Complete list of replacements:", $replacements);

        $this->_subject = preg_replace($patterns, $replacements, $this->_template["subject"]);
        $this->_body = preg_replace($patterns, $replacements, $this->_template["body"]);
        $this->_parsed = true;

        debug_print_r("Parsed subject:", $this->_subject);
        debug_print_r("Parsed body:", $this->_body);
    }

    /**
     * Sends the email to both the addresses specified in the template and the
     * addresses passed through $to. You can either pass a single address as a string
     * or an array of addresses.
     *
     * This call will complete the headers (Date, X-Mailer etc.) and transmit the
     * E-Mail to all recipients. It will return the number of failed addresses, the
     * details can be queried to the member variable $failed.
     *
     * A "To:" line will be created accordingly.
     *
     * If the template has not yet been explicitly parsed, this is done silently.
     *
     * @param mixed $to        Recipients, either a single string (one recipient), or an Array of strings (multiple recipients).
     * @return boolean            Indicating success.
     */
    function send ($to)
    {
        if (! $this->_parsed)
        {
            debug_add("Template was not parsed, doing it now.");
            $this->parse();
        }

        /* Will send everything, return value is the number of addresses that failed
         * sending.
         */

         /* Determine all recipients */
        $all = array();
        if ($this->_template['cc'] != '')
        {
            $cc = explode(",", $this->_template["cc"]);
            $all = array_merge($all, $cc);
            debug_print_r("CC Recipients:", $cc);
        }
        if ($this->_template['bcc'] != '')
        {
            $bcc = explode(",", $this->_template["bcc"]);
            $all = array_merge($all, $bcc);
            debug_print_r("BCC Recipients:", $bcc);
        }
        if (! is_array($to))
            $to = explode(",", $to);
        $all = array_merge($all, $to);
        if ($to == '')
        {
            debug_add("Missing $to. Cannot sent email.");
            return false;
        }


        $this->sent = Array();
        $this->failed = Array();

        debug_print_r("All Recipients:", $all);
        debug_print_r("To Recipients:", $to);

        $params = Array();
        $params["head_charset"] = $this->_template["charset"];
        if (strpos($this->_template["body_mime_type"], "html") !== false)
        {
            $params["html_charset"] = $this->_template["charset"];
            $this->_mail_mime->setHTMLBody($this->_body);
        }
        else
        {
            $params["text_charset"] = $this->_template["charset"];
            $this->_mail_mime->setTXTBody($this->_body);
        }

        $headers = Array();
        $headers["From"] = $this->_template["from"];
        if (strlen($this->_template["reply-to"]) > 0)
            $headers["Reply-To"] = $this->_template["reply-to"];
        $headers["X-Mailer"] = "PHP/" . phpversion() . " MidCOM/" . $GLOBALS["midcom_version"] . "/MailTemplate";
        if (strlen($this->_template["x-mailer"]) > 0)
            $headers["X-Mailer"] .= " (" . $this->_template["x-mailer"] . ")";
        $headers["Date"] = date("r");
        $headers["Subject"] = $this->_subject;

        $body =& $this->_mail_mime->get($params);
        $hdrs =& $this->_mail_mime->headers($headers);

        // Kill these from the headers again, it seems that the Mail package
        // adds these again, resulting in a broken E-Mail. So we rather skip
        // the encoding lines entirely.
        // unset($hdrs['Content-Type']);
        // unset($hdrs['Content-Transfer-Encoding']);

        debug_print_r("Custom Headers:", $headers);
        debug_print_r("Computed Headers:", $hdrs);
        debug_print_r("Body:", $body);

        return $this->_mail->send($all, $hdrs, $body);
    }

    /**
     * Helper function to convert an object into a string representation.
     *
     * Uses word wrapping and skips members beginning with an underscore
     * (which are private per definition). Relies on reflection to parse
     * the object.
     *
     * @param mixed $obj    Any PHP object that can be parsed with get_object_vars().
     * @return string        String representation.
     * @access private
     */
    function _format_object ($obj)
    {
        $result = "";
        foreach (get_object_vars($obj) as $key => $value)
        {
            if (substr($key, 0, 1) == "_")
                continue;
            $key = trim($key);
            if (is_object($value))
            {
                $value = get_class($value) . " object";
                debug_add("The key {$key} contains another object of type {$value}, can't dump this.");
            }
            if (is_array($value))
            {
                $value = "Array";
                debug_add("The key {$key} contains an array, can't dump this.");
            }
            $value = trim($value);
            $result .= "$key: ";
            $result .= wordwrap($value, 74 - strlen($key), "\n" . str_repeat(" ", 2 + strlen($key)));
            $result .= "\n";
        }
        return $result;
    }

    /**
     * Helper function to convert a filled datamanager instance into a
     * string representation.
     *
     * Uses word wrapping and skips fields which are declared hidden.
     * Accesses the datamanagers internal field database directly (so this is
     * not 100% clean).
     *
     * @param midcom_helper_datamanager $dm        A fully initialized Datamanager instance.
     * @return string        String representation.
     * @access private
     */
    function _format_dm ($dm)
    {
        $result = "";
        foreach ($dm->_fields as $name => $desc)
        {
            if ($desc["hidden"] == true)
            {
                continue;
            }

            $key = trim($desc["description"]);
            $value = trim($dm->_datatypes[$name]->get_csv_data());
            $result .= "{$key}: ";
            $result .= wordwrap($value, 74 - strlen($key), "\n" . str_repeat(" ", 2 + strlen($key)));
            $result .= "\n";
        }
        return $result;
    }

    /**
     * Helper function to convert an array into a string representation
     *
     * Uses word wrapping and skips recursive Arrays or objects.
     *
     * @param Array $array    The array to be dumped.
     * @return string        String representation.
     * @access private
     */
    function _format_array ($array)
    {
        $result = "";
        foreach ($array as $key => $value)
        {
            $key = trim($key);
            if (is_object($value))
            {
                $value = get_class($value) . " object";
                debug_add("The key {$key} contains another object of type {$value}, can't dump this.");
            }

            if (is_array($value))
            {
                $value = "Array";
                debug_add("The key {$key} contains an array, can't dump this.");
            }
            $value = trim($value);
            $result .= "{$key}: ";
            $result .= wordwrap($value, 74 - strlen($key), "\n" . str_repeat(" ", 2 + strlen($key)));
            $result .= "\n";
        }
        return $result;
    }

    /**
     * Internal helper doing character conversion for the parameters.
     *
     * If the parameter encoding is unknown, it tries to detect it
     * with mb_detect_encoding if available or does nothing as a last
     * resort. Detection sequence is currently
     * "UTF-8, UTF-7, ASCII, ISO-8859-15", so you'd better ensure
     * that the parameter encoding is known.
     *
     * @param string $string    String in source encoding.
     * @return string            String in destination encoding.
     * @access private
     */
    function _charset_convert ($string)
    {
        $dst = strtoupper($this->_template["charset"]);
        debug_add("We should deliver $dst");
        if (is_null($this->_parameters_encoding))
        {
            /* Try to guess source encoding */
            $src = mb_detect_encoding($string, "UTF-8, UTF-7, ASCII, ISO-8859-15");
            debug_add("mb_detect_encoding got $src");
        }
        else
        {
            $src = strtoupper($this->_parameters_encoding);
        }
        if ($src == $dst)
        {
            return $string;
        }
        return $this->_i18n->iconv($src, $dst, $string);
    }
}
?>