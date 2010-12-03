<?php
/**
 * @package org.openpsa.interviews
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Phone interview MidCOM interface class.
 *
 * @package org.openpsa.interviews
 */
class org_openpsa_interviews_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    public function __construct()
    {
        $this->_autoload_libraries = array
        (
            'midcom.helper.datamanager2',
        );
    }

    public function _on_initialize()
    {
        $_MIDCOM->componentloader->load('org.openpsa.directmarketing');
        return true;
    }
}
?>