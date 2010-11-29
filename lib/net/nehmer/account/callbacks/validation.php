<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: validation.php 25323 2010-03-18 15:54:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Form Validation helper methods.
 *
 * This is a collection of static classes which are used in the more complex form validation
 * cycles, like username uniqueing and the like.
 *
 * All functions are statically callable. (Have to be for QuickForm to work.)
 *
 * @package net.nehmer.account
 */
class net_nehmer_account_callbacks_validation extends midcom_baseclasses_components_purecode
{
    public function __construct()
    {
        $this->_component = 'net.nehmer.account';
        parent::__construct();
    }

    /**
     * This function checks a username against the database. If the username already exists,
     * it will deny the update. You have to pass the current username to the callback, as it
     * will not treat the current user name as an error. This is not checked against the
     * currently authenticated user to keep flexibility. For newly registered users you can
     * pass an empty string to "disable" this check.
     *
     * @param string $username The username to check.
     * @param string $current_name The current name of the user.
     * @return boolean Indicating validity.
     */
    function check_user_name($username, $current_name)
    {
        if ($username == $current_name)
        {
            return true;
        }

        $test = $_MIDCOM->auth->get_user_by_name($username);
        if (! $test)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * This function checks a username against the database for existence.
     *
     * @param string $username The username to check.
     * @return boolean Indicating existence.
     */
    function verify_existing_user_name($username)
    {
        $test = $_MIDCOM->auth->get_user_by_name($username);
        if (! $test)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * This function checks an email against the database for existence.
     *
     * @param string $email The email to check.
     * @return boolean Indicating existence.
     */
    function verify_existing_user_email($email)
    {
        $test = $_MIDCOM->auth->get_user_by_email($email);
        if (! $test)
        {
            return false;
        }
        else
        {
            return true;
        }
    }
}
?>