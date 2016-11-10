<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 captcha base type. Does not provide any functionality, just an
 * empty base type so that the DM2 Formmanager can link into the system.
 * All configuration is set using the widget code. The type should be set to the
 * null storage target.
 *
 * <b>Available configuration options:</b>
 *
 * None
 *
 * See the Captcha Widget for details.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_captcha extends midcom_helper_datamanager2_type
{
    public function convert_from_storage($source)
    {
    }

    public function convert_to_storage()
    {
        return null;
    }

    public function convert_from_csv($source)
    {
    }

    public function convert_to_csv()
    {
        return null;
    }

    public function convert_to_html()
    {
        return null;
    }
}
