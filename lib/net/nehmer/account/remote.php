<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Account Management remote interface class
 *
 * Use this class if you want to access account information from other components.
 * The following code will retrieve a valid instance of this class:
 *
 * <code>
 * $_MIDCOM->componentloader->load('net.nehmer.account');
 * $interface = $_MIDCOM->componentloader->get_interface_class('net.nehmer.account');
 * $remote = $interface->create_remote_controller($this->_config->get('account_topic'));
 * </code>
 *
 * @package net.nehmer.account
 */

class net_nehmer_account_remote extends midcom_baseclasses_components_purecode
{
    /**
     * The topic we are managing.
     *
     * @var midcom_db_topic
     * @access private
     */
    private $_topic = null;

    /**
     * The schema database in use at this topic. This is loaded on-demand only.
     *
     * @var Array
     * @access private
     */
    private $_schemadb = null;

    /**
     * Internal helper, which contains a list of all midcom_core_group records
     * for the defined account types.
     *
     * @var Array
     * @access private
     */
    private $_type_groups = null;

    /**
     * Creates an instance of this class bound to the topic referenced by the
     * argument.
     *
     * @param string $guid The topic to bind to.
     */
    public function __construct($guid)
    {
        $this->_component = 'net.nehmer.account';
        parent::__construct();

        $this->_topic = new midcom_db_topic($guid);
        if (! $this->_topic)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Tried to load the n.n.account remote interface with an invalid topic.');
            // This will exit.
        }
        $this->_load_topic_configuration($this->_topic);
    }

    /**
     * Internal helper, which loads the schema database into the member _schemadb if it
     * has not yet been loaded.
     */
    private function _load_schema_db()
    {
        if ($this->_schemadb === null)
        {
            $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_account'));
        }
    }

    /**
     * Internal helper, which loads the account group listing into the member _type_groups
     * if it has not yet been loaded.
     */
    private function _load_type_groups()
    {
        if ($this->_type_groups === null)
        {
            $this->_type_groups = Array();
            foreach ($this->_schemadb as $name => $schema)
            {
                $this->_type_groups[$name] =& $_MIDCOM->auth->get_midgard_group_by_name($name);
                if (! $this->_type_groups[$name])
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        "DB inconsistency, we could not load the account group {$name}.");
                    // This will exit.
                }
            }
        }
    }

    /**
     * This helper lists an associative array with account types and their titles.
     *
     * @return Array Account name => Description listing.
     */
    function list_account_types()
    {
        $this->_load_schema_db();

        $result = Array();
        foreach ($this->_schemadb as $name => $schema)
        {
            $result[$name] = $schema->description;
        }
        return $result;
    }

    /**
     * This function returns a schema class instance matching the user passed to the
     * method. It defaults to the currently active user.
     *
     * @param mixed $type This is either a midcom_core_user instance (in which case the type
     *     is determined from the account), an explicit account type (string), or null, in
     *     which case the call defaults to the current user.
     * @return midcom_helper_datamanager2_schema The schema database in use for the given user.
     */
    function get_account_schema($type = null)
    {
        // This will load the schema database and the type midgard groups if required.
        if (   $type === null
            || is_a($type, 'midcom_core_user'))
        {
            $type = $this->get_account_type($type);
        }
        else if (! is_string($type))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Incorrect arguments for get_account_schema, need either null, string or mdicom_core_user.');
            // This will exit.
        }
        else
        {
            $this->_load_schema_db();
        }

        if (! array_key_exists($type, $this->_schemadb))
        {
            if ($this->_config->get('fallback_type'))
            {
                $type = $this->_config->get('fallback_type');
            }
            else
            {
                debug_print_r('We were working on this schema database (keys only)', array_keys($this->_schemadb));
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Key {$type} was not found in the schema database. Please correct the system configuration.");
                // This will exit.
            }
        }
        return $this->_schemadb[$type];
    }

    /**
     * This function will create a flat array suitable for usage as defaults with a
     * DM2 Form manager. You can use this to build new objects based on the current account.
     *
     * You have to ensure that the system has read privileges on the user account you want
     * to access, if loading of the user account fails, generate_error is triggered.
     *
     * @return Array A defaults list suitable for usage with the DM2 nullstorage controller
     */
    function get_defaults_from_account($user = null)
    {
        $this->_load_schema_db();

        if ($user === null)
        {
            $user =& $_MIDCOM->auth->user;
        }
        $storage = $user->get_storage();
        if (! $storage)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to load the storage object for the user {$user->name}, this most probably means insufficient permissions.");
            // This will exit.
        }

        $datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        $datamanager->autoset_storage($storage);
        return $datamanager->get_content_raw();
    }

    /**
     * This function will create a flat array suitable for rendering. You can use this to
     * display specific account data anywhere you need it..
     *
     * You have to ensure that the system has read privileges on the user account you want
     * to access, if loading of the user account fails, generate_error is triggered.
     *
     * @return Array An array suitable for rendering.
     */
    function get_content_from_account($user = null)
    {
        $this->_load_schema_db();

        if ($user === null)
        {
            $user =& $_MIDCOM->auth->user;
        }
        $storage = $user->get_storage();
        if (! $storage)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to load the storage object for the user {$user->name}, this most probably means insufficient permissions.");
            // This will exit.
        }

        $datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        $datamanager->autoset_storage($storage);
        return $datamanager->get_content_html();
    }

    /**
     * Returns the account type of the specified user, defaulting to the currently authenticated
     * user. It uses group membership tests to validate the assignment. In case of multiple matches,
     * the first schema match is returned, in case of no matches false is returned. Critical errors
     * trigger generate_error.
     *
     * @param midcom_core_user $user The user to check for account type membership.
     * @return string The name of the account type, or false on failure.
     */
    function get_account_type($user = null)
    {
        $this->_load_schema_db();
        $this->_load_type_groups();

        if ($user === null)
        {
            $user =& $_MIDCOM->auth->user;
        }

        foreach ($this->_type_groups as $name => $grp)
        {
            if ($user->is_in_group($grp))
            {
                return $name;
            }
        }

        if ($this->_config->get('fallback_type'))
        {
            return $this->_config->get('fallback_type');
        }
        else
        {
            return false;
        }
    }
}
?>