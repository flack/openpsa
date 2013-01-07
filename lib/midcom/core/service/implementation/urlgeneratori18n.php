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
    public function from_string($string, $replacer = '-')
    {
        // TODO: sanity-check $replacer ?
        return $this->_convert_string($string, $replacer);
    }

    private function _convert_string($string, $replacer, $r = 0)
    {
        if ($r > 5)
        {
            debug_add('$r > 5, aborting', MIDCOM_LOG_ERROR);
            return $string;
        }
        if (empty($string))
        {
            debug_add('$string was empty(), aborting', MIDCOM_LOG_WARN);
            return '';
        }

        // Try to transliterate non-latin strings to URL-safe format
        $string = midgardmvc_helper_urlize::string($string, $replacer);

        /**
         * Quick and dirty workaround for http://trac.midgard-project.org/ticket/1530 by recursing
         */
        // Recurse until we make no changes to the string
        if ($string === midgardmvc_helper_urlize::string($string, $replacer))
        {
            return $string;
        }

        return $this->_convert_string($string, $replacer, $r + 1);
    }
}
?>