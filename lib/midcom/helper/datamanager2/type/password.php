<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: password.php 24734 2010-01-15 21:11:55Z adrenalin $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 midgard_person compatible password datatype
 *
 * This encapsulates a Midgard password. When loading, the type automatically detects
 * crypted and plain text passwords. A crypted password is represented by a null value
 * (not an empty string). The '**' prefix of plain text passwords is not part of the
 * value.
 *
 * Internally, the type holds a copy of the password value in crypted / uncrypted form,
 * depending on configuration. The type value, if set, transformed into an appropriate
 * storage represenation on transfrom-to-storage operations.
 *
 * This type does not allow you to unset your password as attempts to set an empty
 * password are ignored.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>boolean crypted:</i> Set this to true if you want to store the password crypted.
 *   This is enabled by default. Crypt mode is currently enforcing standard crypt
 *   operation, which is used in Midgard Databases.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_password extends midcom_helper_datamanager2_type
{
    /**
     * The current clear text value of the current password, if available, or
     * null in case of a crypted password. Set this to the new password value
     * if you want to store anything. The password must be non-null and a non-empty
     * string for any storage operation to take place.
     *
     * @var string
     * @access public
     */
    var $value = null;

    /**
     * The real value as stored in the object. This takes crypting etc. into account.
     *
     * @var string
     * @access public
     */
    var $_real_value = '';

    /**
     * Indicating crypted operation
     *
     * @param boolean
     * @access public
     */
    var $crypted = true;

    function convert_from_storage ($source)
    {
        $this->_real_value = $source;
        if (substr($source, 0, 2) == '**')
        {
            $this->value = null;
        }
        else
        {
            $this->value = substr($source, 2);
        }
    }

    function convert_to_storage()
    {
        if ($this->value)
        {
            $this->_update_real_value();
        }
        return $this->_real_value;
    }

    function convert_from_csv ($source)
    {
        $this->convert_from_storage($source);
    }

    function convert_to_csv()
    {
        return $this->convert_to_storage();
    }

    /**
     * Internal helper function, which converts the currently set value (the clear-text
     * password) to the desired storage format, either crypting or prepending a double
     * asterisk.
     *
     * @access protected
     */
    function _update_real_value()
    {
        if ($this->crypted)
        {
            // Enforce crypt mode
            $salt = chr(rand(64,126)) . chr(rand(64,126));
            $this->_real_value = crypt($this->value, $salt);
        }
        else
        {
            $this->_real_value = "**{$this->value}";
        }
    }
    
    /** 
     * HTML display for password always displays asterisks only
     * 
     * @access public
     * @return string
     */
    function convert_to_html()
    {
        return '**********';
    }
}
?>