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
     */
    private static array $_filters = [
        'h' => '',
        'p' => '',
        'u' => 'rawurlencode',
        'f' => 'nl2br',
    ];

    /**
     * Register PHP function as string formatter to the Midgard formatting engine.
     */
    public static function register(string $name, callable $function)
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
        return self::$_filters[$name]($content);
    }

    /**
     * Compile template to php code
     */
    public static function compile(string $content) : string
    {
        return preg_replace_callback("%&\(([^)]*)\);%i", function ($variable)
        {
            $parts = explode(':', $variable[1]);
            $variable = '$' . str_replace('.', '->', $parts[0]);

            if (   isset($parts[1])
                && array_key_exists($parts[1], self::$_filters)) {
                if ($parts[1] == 'p') {
                    $command = 'eval(\'?>\' . ' . $variable . ')';
                } else {
                    $function = self::$_filters[$parts[1]];
                    $command = 'echo ' . $function . '(' . $variable . ')';
                }
            } else {
                $command = 'echo htmlentities(' . $variable . ', ENT_COMPAT, midcom::get()->i18n->get_current_charset())';
            }

            return "<?php $command; ?>";
        }, $content);
    }
}
