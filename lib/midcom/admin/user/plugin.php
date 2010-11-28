<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: plugin.php 24736 2010-01-15 21:14:20Z adrenalin $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * user editor interface for on-site editing of user elements, CSS and JavaScript
 * files and pictures
 *
 * @package midcom.admin.user
 */
class midcom_admin_user_plugin extends midcom_baseclasses_components_handler
{
    /**
     * Get the plugin handlers, which act alike with Request Switches of MidCOM
     * Baseclasses Components (midcom.baseclasses.components.request)
     *
     * @return mixed Array of the plugin handlers
     */
    public function get_plugin_handlers()
    {
        $_MIDCOM->load_library('midgard.admin.asgard');
        $_MIDCOM->load_library('midcom.admin.user');
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $_MIDCOM->auth->require_user_do('midcom.admin.user:access', null, 'midcom_admin_user_plugin');

        return array
        (
            /**
             * user editor for onsite user editing
             */
            /**
             * List users
             *
             * Match /user-editor/
             */
            'user_list' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_list',
                    'list',
                ),
            ),
            /**
             * Edit a user
             *
             * Match /user-editor/edit/<guid>/
             */
            'user_edit' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_user_edit',
                    'edit',
                ),
                'fixed_args' => array
                (
                    'edit',
                ),
                'variable_args' => 1,
            ),
            /**
             * Generate random passwords
             *
             * Match /user-editor/password/
             */
            'user_passwords' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_user_edit',
                    'passwords',
                ),
                'fixed_args' => array
                (
                    'password',
                ),
            ),
            /**
             * Generate random passwords
             *
             * Match /user-editor/password/email/
             */
            'user_passwords_batch' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_user_edit',
                    'batch',
                ),
                'fixed_args' => array
                (
                    'password',
                    'batch',
                ),
            ),
            /**
             * Edit a user's password
             *
             * Match /user-editor/edit/<guid>/
             */
            'user_edit_password' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_user_edit',
                    'edit',
                ),
                'fixed_args' => array
                (
                    'password',
                ),
                'variable_args' => 1,
            ),
            /**
             * Create new user
             *
             * Match /create/
             *
             */
             'user_create' => array
             (
                 'handler' => array
                 (
                     'midcom_admin_user_handler_user_create',
                     'create',
                 ),
                'fixed_args' => array
                (
                    'create',
                ),
             ),
            /**
             * List groups
             *
             * Match /user-editor/group/
             */
            'group_list' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_group_list',
                    'list',
                ),
                'fixed_args' => array
                (
                    'group',
                ),
            ),
            /**
             * Move a group
             *
             * Match /user-editor/group/
             */
            'group_move' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_group_list',
                    'move',
                ),
                'fixed_args' => array
                (
                    'group',
                    'move',
                ),
                'variable_args' => 1,
            ),
            /**
             * Edit a group
             *
             * Match /user-editor/group/edit/<guid>/
             */
            'group_edit' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_group_edit',
                    'edit',
                ),
                'fixed_args' => array
                (
                    'group',
                    'edit',
                ),
                'variable_args' => 1,
            ),
            /**
             * List folders group has privileges to
             *
             * Match /user-editor/group/folders/<guid>/
             */
            'group_folders' => array
            (
                'handler' => array
                (
                    'midcom_admin_user_handler_group_permissions',
                    'folders',
                ),
                'fixed_args' => array
                (
                    'group',
                    'folders',
                ),
                'variable_args' => 1,
            ),
            /**
             * Create new user
             *
             * Match /create/
             *
             */
             'group_create' => array
             (
                 'handler' => array
                 (
                     'midcom_admin_user_handler_group_create',
                     'create',
                 ),
                 'fixed_args' => array
                 (
                     'group',
                     'create',
                 ),
             ),
        );
    }

    /**
     * Static method for generating one password
     *
     * @static
     * @param int $length
     */
    public function generate_password($length = 8, $no_similars = true, $strong = true)
    {
        $similars = array
        (
            'I', 'l', '1', '0', 'O',
        );

        $string = '';
        for ($x = 0; $x < (int) $length; $x++)
        {
            $rand = (int) rand(48, 122);
            $char = chr($rand);

            $k = 0;

            while (   !preg_match('/[a-zA-Z0-9]/', $char)
                   || (   $strong
                       && strlen($string) > 0
                       && strstr($string, $char))
                   || (   $no_similars
                       && in_array($char, $similars)))
            {
                $rand = (int) rand(48, 122);
                $char = chr($rand);

                $k++;
            }
            $string .= $char;
        }

        return $string;
    }
}
?>