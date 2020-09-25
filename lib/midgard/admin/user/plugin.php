<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * user editor interface
 *
 * @package midgard.admin.user
 */
class midgard_admin_user_plugin extends midcom_baseclasses_components_plugin
{
    public function _on_initialize()
    {
        midcom::get()->auth->require_user_do('midgard.admin.user:access', null, 'midgard_admin_user_plugin');
        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midgard.admin.user'), $this->_request_data);

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.user/usermgmt.css');
    }

    /**
     * Generate one password
     *
     * @param int $length
     */
    public static function generate_password(int $length = 8, bool $no_similars = true) : string
    {
        // Safety
        if ($length < 3) {
            $length = 8;
        }

        // Valid characters for default password (PONDER: make configurable ?)
        if ($no_similars) {
            $first_last_chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        } else {
            $first_last_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        }
        $passwdchars = $first_last_chars . '.,-*!:+=()/&%$<>?#@';

        //make sure password doesn't begin or end in punctuation character
        $password = midcom_helper_misc::random_string(1, $first_last_chars);
        $password .= midcom_helper_misc::random_string($length - 2, $passwdchars);
        $password .= midcom_helper_misc::random_string(1, $first_last_chars);
        return $password;
    }
}
