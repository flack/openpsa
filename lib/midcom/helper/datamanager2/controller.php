<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Data Manager controller base class.
 *
 * This class encapsulates a controlling instance of the Datamanager class system. You do not
 * need to use it, it is possible to implement your own, custom form processing solely on the
 * basis of form/datamanager classes. The controllers are intended to ease the integration work
 * and provide more advanced frameworks for example for multi-page forms or AJAX callbacks.
 *
 * The base class implements only a framework for controllers, along with a factory methods for
 * getting real instances which you need to initialize. For all instances, you have to set
 * the schema database using the load_schemadb() helper.
 *
 * See the individual
 * subclass documentations for details about the initialization procedure.
 *
 * <b>You cannot use this class directly, consider it as an abstract base class!</b>
 *
 * @package midcom.helper.datamanager2
 */
abstract class midcom_helper_datamanager2_controller extends midcom_baseclasses_components_purecode
{
    /**
     * The schemadb to handle by this controller.
     *
     * This is a list of midcom_helper_datamanager2_schema instances, indexed
     * by their name. Set this member using the load_schemadb or set_schemadb
     * helpers unless you know what you're doing.
     *
     * @var Array
     */
    var $schemadb = array();

    /**
     * The datamanager instance which is used for data I/O processing.
     *
     * Set this member using the set_storage() helper function unless you
     * definitely know what you're doing.
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    var $datamanager = null;

    /**
     * The form manager instance which is currently in use by this class.
     *
     * This should always be the a single instance, even for multi-page forms.
     * Usually, it is created by the controller class during initialization.
     *
     * @var midcom_helper_datamanager2_formmanager
     */
    var $formmanager = null;

    /**
     * Lock timeout defines the length of lock in seconds.
     *
     * @var integer
     */
    public $lock_timeout = null;

    /**
     * Override the whole locking scheme
     *
     * @var boolean
     */
    public $lock_object = true;

    /**
     * Empty default implementation, this calls won't do much.
     *
     * @param string $identifier The form identifier
     * @return boolean Indicating success.
     */
    function initialize($identifier = null)
    {
        if (is_null($this->lock_timeout))
        {
            $this->lock_timeout = midcom::get()->config->get('metadata_lock_timeout');
        }

        return true;
    }

    /**
     * Loads a schema definition from disk and creates the corresponding schema
     * class instances.
     *
     * If you have an array of schema classes already, use set_schemadb() instead.
     *
     * @param mixed $schemapath A schema database source suitable for use with
     *     midcom_helper_datamanager2_schema::load_database()
     * @see midcom_helper_datamanager2_schema::load_database()
     */
    function load_schemadb($schemapath)
    {
        // We were unable to shortcut, so we try to load the schema database now.
        $this->schemadb = midcom_helper_datamanager2_schema::load_database($schemapath);
    }

    /**
     * Uses an already loaded schema database. If you want to load a schema database
     * from disk, use the load_schemadb method instead.
     *
     * @param array &$schemadb The schema database to use, this must be an array of midcom_helper_datamanager2_schema
     *     instances, which is taken by reference.
     * @see load_schemadb()
     */
    function set_schemadb(array &$schemadb)
    {
        foreach ($schemadb as $value)
        {
            if (!is_a($value, 'midcom_helper_datamanager2_schema'))
            {
                debug_print_r('The database passed was:', $schemadb);
                throw new midcom_error('An invalid schema database has been passed.');
            }
        }

        $this->schemadb =& $schemadb;
    }

    /**
     * Sets the current datamanager instance to the storage object given, which may either
     * be a MidCOM DBA object (which is encapsulated by a midgard datamanager storage instance).
     *
     * You must load a schema database before actually
     *
     * @param object $storage Either an initialized datamanager, an initialized
     *     storage backend or a DBA compatible class instance.
     * @param string $schema This is an optional schema name that should be used to edit the
     *     storage object. If it is null, the controller will try to autodetect the schema
     *     to use by using the datamanager's autoset_storage interface.
     */
    function set_storage($storage, $schema = null)
    {
        if (count($this->schemadb) == 0)
        {
            throw new midcom_error('You cannot set a storage object for a DM2 controller object without loading a schema database previously.');
        }

        if ($storage instanceof midcom_helper_datamanager2_datamanager)
        {
            $this->datamanager = $storage;
        }
        elseif (   $storage instanceof midcom_helper_datamanager2_storage
                 || midcom::get()->dbclassloader->is_midcom_db_object($storage))
        {
            $this->datamanager = new midcom_helper_datamanager2_datamanager($this->schemadb);
            if ($schema === null)
            {
                if (!$this->datamanager->autoset_storage($storage))
                {
                    debug_print_r('We got this storage object:', $storage);
                    throw new midcom_error
                    (
                        'Failed to automatically create a datamanager instance for a storage object or a MidCOM type. See the debug level log for more information.'
                    );
                }
            }
            else
            {
                if (!$this->datamanager->set_schema($schema))
                {
                    debug_add("Tried to set the schema {$schema}");
                    debug_print_r('We got this storage object:', $storage);
                    throw new midcom_error('Failed to set the autocreated datamanager\'s schema. See the debug level log for more information.');
                }
                if (!$this->datamanager->set_storage($storage))
                {
                    debug_add("Tried to set the schema {$schema}");
                    debug_print_r('We got this storage object:', $storage);
                    throw new midcom_error('Failed to set the autocreated datamanager\'s storage object. See the debug level log for more information.');
                }
            }
        }
        else
        {
            debug_print_r('Storage object passed was:', $storage);
            throw new midcom_error('You must pass either a datamanager subclass, an initialized storage encapsulation or a MidCOM DBA object');
        }
    }

    /**
     * This is a static factory method which lets you dynamically create controller instances.
     * It takes care of loading the required class files. The returned instances will be created
     * but not initialized.
     *
     * On any error (class not found etc.) the factory method will throw midcom_error.
     *
     * @param string $type The type of the controller (the file name from the controller directory).
     * @return midcom_helper_datamanager2_controller A reference to the newly created controller instance.
     */
    public static function create($type)
    {
        $classname = "midcom_helper_datamanager2_controller_{$type}";

        return new $classname();
    }

    /**
     * This function should process the form data sent to the server. Its behavior is dependant
     * on the controller used, see the individual class documentations for details.
     *
     * @return string The exitcode of the form processing, usually related to the formmanager
     *     result constants.
     */
    abstract function process_form();

    /**
     * This function invokes the display_form() hook on the form manager class.
     */
    function display_form()
    {
        // Prevent temporary objects from failing
        if (   $this->lock_object
            && !empty($this->datamanager->storage->object->guid))
        {
            // Get the metadata object
            $metadata = $this->datamanager->storage->object->metadata;

            if ($metadata->is_locked())
            {
                // Drop us to uncached state when locked
                midcom::get()->cache->content->uncached();
                $this->show_unlock();
                return;
            }
        }

        $this->formmanager->display_form();
    }

    /**
     * Show the lock status
     */
    public function show_unlock()
    {
        midcom::get()->style->data['handler'] = $this;
        midcom::get()->style->show_midcom('midcom_helper_datamanager2_unlock');
    }
}
