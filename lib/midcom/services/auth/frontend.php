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
 * All functions must be implemented, see their individual documentation about
 * what exactly they should do.
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
     * @param Request $request The request we're reading from
     * @return Array A simple associative array with the two indexes 'username' and
     *     'password' holding the information read by the driver or null if no
     *     information could be read.
     */
    public function read_authentication_data(Request $request);

    /**
     * This call should show the authentication form (or whatever means of input
     * you use). This content you print is assumed to work within an HTML DIV element,
     * so you should usually stick to a simple form, which should also by styleable
     * using CSS alone.
     *
     * You should use HTTP POST to submit the form data to the page you originated from.
     *
     * If you really need to redirect to some external page, ensure that you send
     * the user back to the original location unharmed. Be aware that this type of
     * operation is strongly discouraged.
     *
     * You MAY send HTTP Authentication headers if your auth driver uses them and stop
     * execution immediately afterwards. (2DO: How to treat sent content (it is
     * in the output buffer) at this point?)
     */
    public function show_authentication_form();
}
