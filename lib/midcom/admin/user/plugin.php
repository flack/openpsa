<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * user editor interface for on-site editing of user elements, CSS and JavaScript
 * files and pictures
 *
 * @package midcom.admin.user
 */
class midcom_admin_user_plugin extends midcom_baseclasses_components_plugin
{
    public function _on_initialize()
    {
        midcom::get()->auth->require_user_do('midcom.admin.user:access', null, 'midcom_admin_user_plugin');
    }

    /**
     * Generate one password
     *
     * @param int $length
     */
    public static function generate_password($length = 8, $no_similars = true, $strong = true)
    {
        $similars = array(
            'I', 'l', '1', '0', 'O',
        );

        $string = '';
        for ($x = 0; $x < (int) $length; $x++) {
            do {
                $char = chr(rand(48, 122));
            } while (   !preg_match('/[a-zA-Z0-9]/', $char)
                   || (   $strong
                       && strlen($string) > 0
                       && strstr($string, $char))
                   || (   $no_similars
                       && in_array($char, $similars)));

            $string .= $char;
        }

        return $string;
    }
}
