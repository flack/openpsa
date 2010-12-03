<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * URL name generation interface class
 *
 * @package midcom
 */
class midcom_core_service_implementation_urlgeneratori18n implements midcom_core_service_urlgenerator
{
    public function from_string($string, $replacer = '_')
    {
        // TODO: sanity-check $replacer ?
        $orig_string = $string;
        // Try to transliterate non-latin strings to URL-safe format
        require_once(MIDCOM_ROOT . '/midcom/helper/utf8_to_ascii.php');
        $string = utf8_to_ascii($string, $replacer);
        $string = trim(str_replace('[?]', '', $string));

        // Ultimate fall-back, if we couldn't get anything out of the transliteration we use the UTF-8 character hexes as the name string to have *something*
        if (   empty($string)
            || preg_match("/^{$replacer}+$/", $string))
        {
            $i = 0;
            // make sure this is not mb_strlen (ie mb automatic overloading off)
            $len = strlen($orig_string);
            $string = '';
            while ($i < $len)
            {
                $byte = $orig_string[$i];
                $string .= str_pad(dechex(ord($byte)), '0', STR_PAD_LEFT);
                $i++;
            }
        }

        // Rest of spaces to underscores
        $string = preg_replace('/\s+/', '_', $string);

        // Regular expression for characters to replace (the ^ means an inverted character class, ie characters *not* in this class are replaced)
        $regexp = '/[^a-zA-Z0-9_-]/';
        // Replace the unsafe characters with the given replacer (which is supposed to be safe...)
        $safe = preg_replace($regexp, $replacer, $string);

        // Strip trailing {$replacer}s and underscores from start and end of string
        $safe = preg_replace("/^[{$replacer}_]+|[{$replacer}_]+$/", '', $safe);

        // Clean underscores around $replacer
        $safe = preg_replace("/_{$replacer}|{$replacer}_/", $replacer, $safe);

        // Any other cleanup routines ?

        // We're done here, return $string lowercased
        return strtolower($safe);
    }
}
?>