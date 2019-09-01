<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wrapper for acces denied page responses
 *
 * @package midcom
 */
class midcom_response_accessdenied extends midcom_response_login
{
    private $message;

    /**
     * @param string $message The message to show to the user.
     */
    public function __construct(string $message)
    {
        $this->message = $message;
        parent::__construct();
    }

    /**
     * This will display the error message and appends the login form below it
     *
     * The login message shown depends on the current state:
     * - If an authentication attempt was done but failed, an appropriated wrong user/password
     *   message is shown.
     * - If the user is authenticated, a note that he might have to switch to a user with more
     *   privileges is shown.
     * - Otherwise, no message is shown.
     *
     * If the style element <i>midcom_services_auth_access_denied</i> is defined, it will be shown
     * instead of the default error page. The following variables will be available in the local
     * scope:
     *
     * $title contains the localized title of the page, based on the 'access denied' string ID of
     * the main MidCOM L10n DB. $message will contain the notification what went wrong and
     * $login_warning will notify the user of a failed login. The latter will either be empty
     * or enclosed in a paragraph with the CSS ID 'login_warning'.
     */
    protected function render() : string
    {
        // Determine login message
        $login_warning = '';
        if (midcom::get()->auth->user !== null) {
            // The user has insufficient privileges
            $login_warning = midcom::get()->i18n->get_string('login message - insufficient privileges', 'midcom');
        } elseif (midcom::get()->auth->has_login_data()) {
            $login_warning = midcom::get()->i18n->get_string('login message - user or password wrong', 'midcom');
        }

        $title = midcom::get()->i18n->get_string('access denied', 'midcom');

        midcom::get()->style->data['midcom_services_auth_access_denied_message'] = $this->message;
        midcom::get()->style->data['midcom_services_auth_access_denied_title'] = $title;
        midcom::get()->style->data['midcom_services_auth_access_denied_login_warning'] = $login_warning;

        ob_start();
        midcom::get()->style->show_midcom('midcom_services_auth_access_denied');
        return ob_get_clean();
    }
}
