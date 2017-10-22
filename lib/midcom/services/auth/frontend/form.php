<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Form-based authentication frontend. This one is rather simple, it just renders a
 * two-field (username/password) form which is targeted at the current URL.
 *
 * @package midcom.services
 */
class midcom_services_auth_frontend_form implements midcom_services_auth_frontend
{
    /**
     * Flag, which is set to true if the system encountered any new login credentials
     * during startup. If this is true, but no user is authenticated, login did fail.
     *
     * @var boolean
     */
    private $auth_credentials_found = false;

    /**
     * This call checks whether the two form fields we have created are present, if yes
     * it reads and returns their values.
     *
     * @return Array A simple associative array with the two indexes 'username' and
     *     'password' holding the information read by the driver or null if no
     *     information could be read.
     */
    public function read_login_data(Request $request)
    {
        if (   !$request->request->has('midcom_services_auth_frontend_form_submit')
            || !$request->request->has('username')
            || !$request->request->has('password')) {
            return null;
        }
        $this->auth_credentials_found = true;

        // There was form data sent before authentication was re-required
        if ($request->request->has('restore_form_data')) {
            foreach ($request->request->get('restored_form_data', []) as $key => $string) {
                $value = @unserialize(base64_decode($string));
                $request->request->set($key, $value);
            }
            $request->overrideGlobals();
        }

        return [
            'username' => trim($request->request->get('username')),
            'password' => trim($request->request->get('password'))
        ];
    }

    /**
     * If the current style has an element called <i>midcom_services_auth_login_page</i>
     * it will be shown instead. The local scope will contain the two variables
     * $title and $login_warning. $title is the localized string 'login' from the main
     * MidCOM L10n DB, login_warning is empty unless there was a failed authentication
     * attempt, in which case it will have a localized warning message enclosed in a
     * paragraph with the ID 'login_warning'.
     */
    public function show_login_page()
    {
        // Drop any output buffer first
        midcom::get()->cache->content->disable_ob();

        if (midcom::get()->config->get('auth_login_form_httpcode') == 200) {
            _midcom_header('HTTP/1.0 200 OK');
        } else {
            _midcom_header('HTTP/1.0 403 Forbidden');
        }

        midcom::get()->cache->content->no_cache();

        $title = midcom::get()->i18n->get_string('login', 'midcom');

        // Determine login warning so that wrong user/pass is shown.
        $login_warning = '';
        if ($this->auth_credentials_found) {
            $login_warning = midcom::get()->i18n->get_string('login message - user or password wrong', 'midcom');
        }

        // Pass our local but very useful variables on to the style element
        midcom::get()->style->data['midcom_services_auth_show_login_page_title'] = $title;
        midcom::get()->style->data['midcom_services_auth_show_login_page_login_warning'] = $login_warning;

        midcom::get()->style->show_midcom('midcom_services_auth_login_page');

        midcom::get()->finish();
    }

    /**
     * This call renders a simple form without any formatting (that is to be
     * done by the callee) that asks the user for his username and password.
     *
     * If you want to replace the form by some custom style, you can define
     * the style element <i>midcom_services_auth_frontend_form</i>. If this
     * element is present, it will be shown instead of the default style.
     * In that case you should look into the source
     * of it to see exactly what is required.
     */
    public function show_login_form()
    {
        // Store the submitted form if the session expired, but user wants to save the data
        if (count($_POST) > 0) {
            $data =& midcom_core_context::get()->get_custom_key('request_data');

            $data['restored_form_data'] = [];

            foreach ($_POST as $key => $value) {
                if (preg_match('/(username|password|frontend_form_submit)/', $key)) {
                    continue;
                }

                $data['restored_form_data'][$key] = base64_encode(serialize($value));
            }
            midcom::get()->style->data = array_merge(midcom::get()->style->data, $data);
        }

        echo "<div id='midcom_login_form'>\n";
        midcom::get()->style->show_midcom('midcom_services_auth_frontend_form');
        echo "</div>\n";
    }
}
