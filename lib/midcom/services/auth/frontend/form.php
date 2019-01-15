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
     * @param Request $request The request object
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
     * If the current style has an element called <i>midcom_services_auth_login_page</i>
     * it will be shown instead. The local scope will contain the two variables
     * $title and $login_warning. $title is the localized string 'login' from the main
     * MidCOM L10n DB, login_warning is empty unless there was a failed authentication
     * attempt, in which case it will have a localized warning message enclosed in a
     * paragraph with the ID 'login_warning'.
     */
    public function show_login_page()
    {
        $this->generate_http_response();

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
     * This will display the error message and appends the login form below it if and only if
     * the headers have not yet been sent.
     *
     * The function will clear any existing output buffer, and the sent page will have the
     * 403 - Forbidden HTTP Status. The login will relocate to the same URL, so it should
     * be mostly transparent.
     *
     * The login message shown depends on the current state:
     * - If an authentication attempt was done but failed, an appropriated wrong user/password
     *   message is shown.
     * - If the user is authenticated, a note that he might have to switch to a user with more
     *   privileges is shown.
     * - Otherwise, no message is shown.
     *
     * This function will exit() unconditionally.
     *
     * If the style element <i>midcom_services_auth_access_denied</i> is defined, it will be shown
     * instead of the default error page. The following variables will be available in the local
     * scope:
     *
     * $title contains the localized title of the page, based on the 'access denied' string ID of
     * the main MidCOM L10n DB. $message will contain the notification what went wrong and
     * $login_warning will notify the user of a failed login. The latter will either be empty
     * or enclosed in a paragraph with the CSS ID 'login_warning'.
     *
     * @param string $message The message to show to the user.
     */
    public function show_access_denied($message)
    {
        // Determine login message
        $login_warning = '';
        if (midcom::get()->auth->user !== null) {
            // The user has insufficient privileges
            $login_warning = midcom::get()->i18n->get_string('login message - insufficient privileges', 'midcom');
        } elseif ($this->auth_credentials_found) {
            $login_warning = midcom::get()->i18n->get_string('login message - user or password wrong', 'midcom');
        }

        $title = midcom::get()->i18n->get_string('access denied', 'midcom');

        // Emergency check, if headers have been sent, kill MidCOM instantly, we cannot output
        // an error page at this point (dynamic_load from site style? Code in Site Style, something
        // like that)
        if (_midcom_headers_sent()) {
            debug_add('Cannot render an access denied page, page output has already started. Aborting directly.', MIDCOM_LOG_INFO);
            echo "<br />{$title}: {$login_warning}";
            debug_add("Emergency Error Message output finished, exiting now");
            midcom::get()->finish();
        }

        $this->generate_http_response();

        midcom::get()->style->data['midcom_services_auth_access_denied_message'] = $message;
        midcom::get()->style->data['midcom_services_auth_access_denied_title'] = $title;
        midcom::get()->style->data['midcom_services_auth_access_denied_login_warning'] = $login_warning;

        midcom::get()->style->show_midcom('midcom_services_auth_access_denied');

        midcom::get()->finish();
    }

    private function generate_http_response()
    {
        // Drop any output buffer first.
        midcom::get()->cache->content->disable_ob();

        if (midcom::get()->config->get('auth_login_form_httpcode') == 200) {
            _midcom_header('HTTP/1.0 200 OK');
        } else {
            _midcom_header('HTTP/1.0 403 Forbidden');
        }
        midcom::get()->cache->content->no_cache();
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
        if (count($_POST) > 0) {
            $data =& midcom_core_context::get()->get_custom_key('request_data');

            $data['restored_form_data'] = [];

            foreach ($_POST as $key => $value) {
                if (preg_match('/(username|password|frontend_form_submit)/', $key)) {
                    continue;
                }

                $data['restored_form_data'][$key] = base64_encode(json_encode($value));
            }
            midcom::get()->style->data = array_merge(midcom::get()->style->data, $data);
        }

        echo "<div id='midcom_login_form'>\n";
        midcom::get()->style->show_midcom('midcom_services_auth_frontend_form');
        echo "</div>\n";
    }
}
