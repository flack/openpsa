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
    public function from_string($string)
    {
        if (empty($string)) {
            return '';
        }
        return midgardmvc_helper_urlize::string($string, '-');
    }
}
