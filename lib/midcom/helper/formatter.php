<?php
/**
 * @package midcom.helper
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Formatting helpers for style elements.
 *
 * @package midcom.helper
 */
class midcom_helper_formatter
{
    /**
     * Filter registry
     *
     * @var array
     */
    private static $_filters = [
        'h' => '',
        'H' => '',
        'p' => '',
        'u' => 'rawurlencode',
        'f' => 'nl2br',
        's' => '',
    ];

    /**
     * Register PHP function as string formatter to the Midgard formatting engine.
     */
    public static function register($name, $function)
    {
        self::$_filters["x{$name}"] = $function;
    }

    /**
     * Return a string as formatted by the specified filter
     * Note: The p filter is not supported here
     */
    public static function format(string $content, string $name) : string
    {
        if (!isset(self::$_filters[$name]) || !is_callable(self::$_filters[$name])) {
            return $content;
        }
        return call_user_func(self::$_filters[$name], $content);
    }

    /**
     * Compile template to php code
     */
    public static function compile(string $content) : string
    {
        return preg_replace_callback("%&\(([^)]*)\);%i", function ($variable)
        {
            $variable_parts = explode(':', $variable[1]);
            $variable = '$' . $variable_parts[0];

            if (strpos($variable, '.') !== false) {
                $parts = explode('.', $variable);
                $variable = $parts[0] . '->' . $parts[1];
            }

            if (    isset($variable_parts[1])
                && array_key_exists($variable_parts[1], self::$_filters)) {
                    switch ($variable_parts[1]) {
                        case 's':
                            //display as-is
                        case 'h':
                        case 'H':
                            //According to documentation, these two should do something, but actually they don't...
                            $command = 'echo ' . $variable;
                            break;
                        case 'p':
                            $command = 'eval(\'?>\' . ' . $variable . ')';
                            break;
                        default:
                            $function = self::$_filters[$variable_parts[1]];
                            $command = 'echo ' . $function . '(' . $variable . ')';
                            break;
                    }
                } else {
                    $command = 'echo htmlentities(' . $variable . ', ENT_COMPAT, midcom::get()->i18n->get_current_charset())';
                }

                return "<?php $command; ?>";
        }, $content);
    }
}
