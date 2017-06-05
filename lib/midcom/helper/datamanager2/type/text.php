<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Michelf\MarkdownExtra;

/**
 * Datamanager 2 Simple text datatype. The text value encapsulated by this type is
 * passed as-is to the storage layers, no specialties done, just a string.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>int maxlength:</i> The maximum length of the string allowed for this field.
 *   This includes any newlines, which account as at most two characters, depending
 *   on the OS. Set this to 0 (the default) to have unlimited input.
 * - <i>string output_mode:</i> This option controls how convert_to_html operates. The
 *   default 'specialchars' will just pass the data entered in the field through
 *   htmlspecialchars(). See below for a full option listing.
 *
 * Available output modes:
 *
 * - 'html': No conversion is done.
 * - 'specialchars': The value is run through htmlspecialchars() (the default).
 * - 'nl2br': The value is run through htmlspecialchars() and nl2br()
 * - 'midgard_f': Uses the Midgard :f formatter.
 * - 'midgard_F': Uses the Midgard :F formatter.
 * - 'markdown': Uses Michelf\MarkdownExtra.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_text extends midcom_helper_datamanager2_type
{
    /**
     * The current string encapsulated by this type.
     *
     * @var string
     */
    public $value;

    /**
     * Maximum length of the string encapsulated by this type. 0 means no limit.
     * This is checked during validation.
     *
     * @param int
     */
    public $maxlength = 0;

    /**
     * Sets output formatting. See class introduction.
     *
     * @var string
     */
    public $output_mode = 'specialchars';

    /**
     * Run HTML contents through the HTML Purifier library to ensure safe XHTML compatibility.
     *
     * If left undefined global config according to output_mode is used.
     */
    var $purify = null;

    /**
     * Configuration values for HTML Purifier
     */
    var $purify_config = null;

    /**
     * Whether Markdown fields should be purified when converted to HTML for output to remove disallowed tags
     *
     * If left undefined global config is used.
     */
    var $purify_markdown_on_output = null;

    /**
     * Define the quotes behavior when htmlspecialchars() is called
     *
     * @see http://www.php.net/htmlspecialchars
     */
    var $specialchars_quotes = ENT_QUOTES;

    /**
     * Define the charset to use when htmlspecialchars() is called
     *
     * @see http://www.php.net/htmlspecialchars
     */
    var $specialchars_charset = 'UTF-8';

    /**
     * Define array of forbidden patterns in this field
     *
     * Example:
     * <pre>
     * 'type_config' => Array
     * (
     *     'output_mode' => 'markdown',
     *     'forbidden_patterns' => array
     *     (
     *         array
     *         (
     *             'type' => 'regex',
     *             'pattern' => '%(<a href=[^>]*>[^<]*</a>)%i',
     *             'explanation' => 'HTML-links are disallowed to combat spam',
     *         ),
     *     ),
     * ),
     * </pre>
     */
    var $forbidden_patterns = null;

    /**
     * Define array of forbidden patterns in this field
     *
     * Example:
     * <pre>
     * 'type_config' => Array
     * (
     *     'allowed_patterns' => array
     *     (
     *         array
     *         (
     *             'type' => 'regex',
     *             'pattern' => '%^http://(mydomain|othernicedomain)\.(org|net)%i',
     *             'explanation' => 'Domains other than mydomain.net/org and othernicedomain.net/org are forbidden',
     *         ),
     *     ),
     * ),
     * </pre>
     */
    var $allowed_patterns = null;

    public function _on_initialize()
    {
        // if purification is not explicitly set use according to config variables and output mode
        if (!isset($this->purify)) {
            $this->purify = ($this->output_mode == 'html') ? $this->_config->get('html_purify') : false;
        }
        // Just in case someone *really* needs to specify html purifier configs here
        if (!isset($this->purify_config)) {
            $this->purify_config = $this->_config->get('html_purify_config');
        }
        if (!isset($this->purify_markdown_on_output)) {
            $this->purify_markdown_on_output = $this->_config->get('html_purify_markdown');
        }

        if (   !empty($this->forbidden_patterns)
            && !empty($this->allowed_patterns)) {
            debug_add('Both allowed and forbidden patterns are set, allowed has precedence', MIDCOM_LOG_ERROR);
            $this->forbidden_patterns = null;
        }
    }

    public function convert_from_storage($source)
    {
        $this->value = $source;
    }

    function purify_string($content)
    {
        if (   isset($this->purify_config['Cache']['SerializerPath'])
            && !file_exists($this->purify_config['Cache']['SerializerPath'])) {
            mkdir($this->purify_config['Cache']['SerializerPath']);
        }

        //This is a bit of a bogus test, but this class needs to be loaded so that necessary constants
        //are defined.
        if (!class_exists('HTMLPurifier_Bootstrap')) {
            throw new midcom_error('HTMLPurifier_Bootstrap is missing, cannot continue');
        }

        // For some reason we lose this along the way!
        error_reporting(E_ALL);

        $purifier_config_object = HTMLPurifier_Config::createDefault();
        $purifier_config_object->loadArray($this->purify_config);

        // Set local IDPrefix to field name...
        if (!empty($this->purify_config['Attr']['IDPrefix'])) {
            $purifier_config_object->set('Attr.IDPrefixLocal', "{$this->name}_");
        }

        // Load custom element/attribute definitions
        $config_defs = $this->_config->get('html_purify_HTMLDefinition');
        if (   is_array($config_defs)
            && !empty($config_defs)
            && $def = $purifier_config_object->maybeGetRawHTMLDefinition(true)) {
            if (   !empty($config_defs['addAttribute'])
                && is_array($config_defs['addAttribute'])) {
                foreach (array_filter($config_defs['addAttribute'], 'is_array') as $attrdef) {
                    call_user_func_array([$def, 'addAttribute'], $attrdef);
                }
            }
            if (   !empty($config_defs['addElement'])
                && is_array($config_defs['addElement'])) {
                foreach (array_filter($config_defs['addElement'], 'is_array') as $elemdef) {
                    call_user_func_array([$def, 'addElement'], $elemdef);
                }
            }
        }
        $purifier = new HTMLPurifier($purifier_config_object);

        // FIXME figure out why this always tries to put something to the default cache dir (it does put stuff to the defined one as well)
        try {
            $ret = $purifier->purify($content);
        } catch (Exception $e) {
            debug_add("HTML Purifier failed to purify contents of field {$this->name}: " . $e->getMessage(), MIDCOM_LOG_WARN);
        }

        return $ret;
    }

    function purify_content()
    {
        $this->value = $this->purify_string((string) $this->value);
    }

    public function convert_to_storage()
    {
        // Normalize line breaks to the UNIX format
        $this->value = preg_replace("/\n\r|\r\n|\r/", "\n", (string) $this->value);

        if ($this->purify) {
            $this->purify_content();
        }

        return $this->value;
    }

    public function convert_from_csv($source)
    {
        $this->value = $source;
    }

    public function convert_to_csv()
    {
        return (string) $this->value;
    }

    /**
     * The validation callback ensures that we don't have an array or an object
     * as a value, which would be wrong.
     *
     * @return boolean Indicating validity.
     */
    public function _on_validate()
    {
        if (   is_array($this->value)
            || is_object($this->value)) {
            $this->validation_error = $this->_l10n->get('type text: value may not be array or object');
            return false;
        }

        $this->value = (string) $this->value;

        if ($this->purify) {
            $this->purify_content();
        }

        if (   $this->maxlength > 0
            && strlen($this->value) > $this->maxlength) {
            $this->validation_error = sprintf($this->_l10n->get('type text: value is longer than %d characters'),
                $this->maxlength);
            return false;
        }

        $stat = $this->validate_forbidden_patterns([$this->name => $this->value]);
        if (is_array($stat)) {
            $this->validation_error = $stat[$this->name];
            return false;
        }

        $stat = $this->validate_allowed_patterns([$this->name => $this->value]);
        if (is_array($stat)) {
            $this->validation_error = $stat[$this->name];
            return false;
        }

        return true;
    }

    public function validate_allowed_patterns($fields)
    {
        if (   !empty($this->allowed_patterns)
            && is_array($this->allowed_patterns)) {
            foreach ($this->allowed_patterns as $condition) {
                if (!isset($condition['explanation'])) {
                    $condition['explanation'] = '';
                }
                switch ($condition['type']) {
                    case 'regex':
                        $matches = [];
                        if (!preg_match($condition['pattern'], $fields[$this->name], $matches)) {
                            return [$this->name => sprintf($this->_l10n->get('type text: value is not allowed. %s'), $condition['explanation'])];
                        }
                        break;
                    default:
                        // We do not know how to handle this
                        $msg = "Unsupported pattern type '{$condition['type']}'";
                        debug_add($msg, MIDCOM_LOG_WARN);
                        return [$this->name => $msg];
                }
            }
        }
        return true;
    }

    public function validate_forbidden_patterns($fields)
    {
        if (empty($this->forbidden_patterns)) {
            return true;
        }
        foreach ($this->forbidden_patterns as $condition) {
            if (!isset($condition['explanation'])) {
                $condition['explanation'] = '';
            }

            switch ($condition['type']) {
                case 'text':
                    $pos = strpos($this->value, $condition['pattern']);
                    if ($pos !== false) {
                        $offense = substr($fields[$this->name], $pos, strlen($condition['pattern']));
                        return [$this->name => sprintf($this->_l10n->get('type text: value contains an expression that is not allowed: "%s". %s'), htmlentities($offense), $condition['explanation'])];
                    }
                    break;
                case 'regex':
                    $matches = [];
                    if (preg_match($condition['pattern'], $fields[$this->name], $matches)) {
                        return [$this->name => sprintf($this->_l10n->get('type text: value contains an expression that is not allowed: "%s". %s'), htmlentities($matches[0]), $condition['explanation'])];
                    }
                    break;
                default:
                    // We do not know how to handle this
                    $msg = "Unsupported pattern type '{$condition['type']}'";
                    debug_add($msg, MIDCOM_LOG_WARN);
                    return [$this->name => $msg];
            }
        }
        return true;
    }

    public function convert_to_html()
    {
        $this->value = (string) $this->value;

        switch ($this->output_mode) {
            case 'code':
                return '<pre style="overflow:auto">' . htmlspecialchars($this->value, $this->specialchars_quotes, $this->specialchars_charset) . '</pre>';

            case 'pre':
                return '<pre style="white-space: pre-wrap">' . htmlspecialchars($this->value, $this->specialchars_quotes, $this->specialchars_charset) . '</pre>';

            case 'specialchars':
                return htmlspecialchars($this->value, $this->specialchars_quotes, $this->specialchars_charset);

            case 'nl2br':
                return nl2br(htmlentities($this->value, $this->specialchars_quotes, $this->specialchars_charset));

            case 'midgard_f':
                return midcom_helper_formatter::format($this->value, 'f');

            case 'midgard_F':
                return midcom_helper_formatter::format($this->value, 'F');

            case 'markdown':
                if (   !$this->purify
                    || !$this->purify_markdown_on_output) {
                    // Return the Markdown straight away
                    return MarkdownExtra::defaultTransform($this->value);
                }

                // Run the Markdown-generated HTML through Purifier to ensure consistency. This is expensive, however
                return $this->purify_string(MarkdownExtra::defaultTransform($this->value));

            case (substr($this->output_mode, 0, 1) == 'x'):
                // Run the contents through a custom formatter registered via mgd_register_filter
                return midcom_helper_formatter::format($this->value, $this->output_mode);

            default:
            case 'html':
                return $this->value;
        }
    }
}
