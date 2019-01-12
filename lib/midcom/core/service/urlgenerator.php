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
interface midcom_core_service_urlgenerator
{
    public function from_string($string);
}
