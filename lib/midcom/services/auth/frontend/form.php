<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Form-based authentication frontend. This one is rather simple, it just renders a
 * two-field (username/password) form which is targeted at the current URL.
 *
 * @package midcom.services
 */
class midcom_services_auth_frontend_form implements midcom_services_auth_frontend
{
    /**
     * This call checks whether the two form fields we have created are present, if yes
     * it reads and returns their values.
     *
     * @return Array A simple associative array with the two indexes 'username' and
     *     'password' holding the information read by the driver or null if no
     *     information could be read.
     */
    public function read_authentication_data()
    {
        if (   !array_key_exists('midcom_services_auth_frontend_form_submit', $_REQUEST)
            || !array_key_exists('username', $_REQUEST)
            || !array_key_exists('password', $_REQUEST)) {
            return null;
        }

        return array
        (
            'username' => trim($_REQUEST['username']),
            'password' => trim($_REQUEST['password'])
        );
    }

    /**
     * This call renders a simple form without any formatting (that is to be
     * done by the callee) that asks the user for his username and password.
     *
     * The default should be quite useable through its CSS.
     *
     * If you want to replace the form by some custom style, you can define
     * the style- or page-element <i>midcom_services_auth_frontend_form</i>. If this
     * element is present, it will be shown instead of the default style
     * included in this function. In that case you should look into the source
     * of it to see exactly what is required.
     */
    public function show_authentication_form()
    {
        // Store the submitted form if the session expired, but user wants to save the data
        if (count($_POST) > 0) {
            $data =& midcom_core_context::get()->get_custom_key('request_data');

            $data['restored_form_data'] = array();

            foreach ($_POST as $key => $value) {
                if (preg_match('/(username|password|frontend_form_submit)/', $key)) {
                    continue;
                }

                $data['restored_form_data'][$key] = base64_encode(serialize($value));
            }
            midcom::get()->style->data = array_merge(midcom::get()->style->data, $data);
        }

        midcom::get()->style->show_midcom('midcom_services_auth_frontend_form');
    }
}
