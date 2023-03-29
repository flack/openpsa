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
     */
    private bool $auth_credentials_found = false;

    public function has_login_data() : bool
    {
        return $this->auth_credentials_found;
    }

    /**
     * This call checks whether the two form fields we have created are present, if yes
     * it reads and returns their values.
     *
     * @return array A simple associative array with the two indexes 'username' and
     *     'password' holding the information read by the driver or null if no
     *     information could be read.
     */
    public function read_login_data(Request $request) : ?array
    {
        if (   !$request->request->has('midcom_services_auth_frontend_form_submit')
            || !$request->request->has('username')
            || !$request->request->has('password')) {
            return null;
        }
        $this->auth_credentials_found = true;

        // There was form data sent before authentication was re-required
        if ($request->request->has('restore_form_data')) {
            foreach ($request->request->all('restored_form_data') as $key => $string) {
                $value = json_decode(base64_decode($string), true);
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
     * This call renders a simple form without any formatting (that is to be
     * done by the callee) that asks the user for his username and password.
     *
     * If you want to replace the form by some custom style, you can define
     * the style element <i>midcom_services_auth_frontend_form</i>.
     * In that case you should look into the source of it to see exactly what is required.
     * The output from the frontend is surrounded by a div tag whose CSS ID is set to
     * 'midcom_login_form'.
     */
    public function show_login_form()
    {
        // Store the submitted form if the session expired, but user wants to save the data
        if (!empty($_POST)) {
            $restored_form_data = [];

            foreach ($_POST as $key => $value) {
                if (preg_match('/(username|password|frontend_form_submit)/', $key)) {
                    continue;
                }

                $restored_form_data[$key] = base64_encode(json_encode($value));
            }
            midcom::get()->style->data['restored_form_data'] = $restored_form_data;
        }

        echo "<div id='midcom_login_form'>\n";
        midcom::get()->style->show_midcom('midcom_services_auth_frontend_form');
        echo "</div>\n";
    }
}
