<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Authentication frontend, responsible for rendering the login screen, reading
 * the credentials and displaying access denied information.
 *
 * Configuration, if necessary, should be done using the MidCOM configuration
 * system, prefixing all values with 'auth_frontend_$name_', e.g.
 * 'auth_frontend_form_cssclass'.
 *
 * @package midcom.services
 */
interface midcom_services_auth_frontend
{
    /**
     * This call should process the current authentication credentials and return
     * the username / password pair that should be tried to authentication
     * or null for anonymous access.
     *
     * @return Array A simple associative array with the two indexes 'username' and
     *     'password' holding the information read by the driver or null if no
     *     information could be read.
     */
    public function read_login_data(Request $request) : ?array;

    /**
     * Were login credentials found
     */
    public function has_login_data() : bool;

    /**
     * This call should show the authentication form (or whatever means of input
     * you use).
     *
     * You should use HTTP POST to submit the form data to the page you originated from.
     *
     * You MAY send HTTP Authentication headers if your auth driver uses them and stop
     * execution immediately afterwards. (2DO: How to treat sent content (it is
     * in the output buffer) at this point?)
     */
    public function show_login_form();
}
