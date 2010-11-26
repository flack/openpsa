<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: controller.php 26507 2010-07-06 13:31:06Z rambo $
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
 * @abstract
 */
class midcom_helper_datamanager2_controller extends midcom_baseclasses_components_purecode
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
    var $schemadb = Array();

    /**
     * The datamanager instance which is used for data I/O processing.
     *
     * Set this member using the set_storage() helper function unless you
     * definitely know what you're doing.
     *
     * @var midcom_helper_datamanager2
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
     * @access public
     * @var integer
     */
    var $lock_timeout = null;

    /**
     * Override the whole locking scheme
     *
     * @access public
     * @var boolean
     */
    var $lock_object = true;

    /**
     * Initializes the class. The real startup is done by the initialize() call.
     */
    function __construct()
    {
         $this->_component = 'midcom.helper.datamanager2';
         parent::__construct();
    }

    /**
     * Empty default implementation, this calls won't do much.
     *
     * @return boolean Indicating success.
     */
    function initialize()
    {
        if (is_null($this->lock_timeout))
        {
            $this->lock_timeout = $GLOBALS['midcom_config']['metadata_lock_timeout'];
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
    function set_schemadb(&$schemadb)
    {
        foreach ($schemadb as $value)
        {
            if (! is_a($value, 'midcom_helper_datamanager2_schema'))
            {
                debug_print_r('The database passed was:', $schemadb);
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    'An invalid schema database has been passed to the midcom_helper_datamanager2_controller::set_schemadb method.');
                // This will exit.
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
     * @param object &$storage A reference to either an initialized datamanager, an initialized
     *     storage backend or to a DBA compatible class instance.
     * @param string $schema This is an optional schema name that should be used to edit the
     *     storage object. If it is null, the controller will try to autodetect the schema
     *     to use by using the datamanager's autoset_storage interface.
     */
    function set_storage(&$storage, $schema = null)
    {
        if (count($this->schemadb) == 0)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'You cannot set a storage object for a DM2 controller object without loading a schema database previously.');
            // This will exit.
        }

        if ($storage instanceof midcom_helper_datamanager2_datamanager)
        {
            $this->datamanager = $storage;
        }
        else if (   $storage instanceof midcom_helper_datamanager2_storage
                 || $_MIDCOM->dbclassloader->is_midcom_db_object($storage))
        {
            $this->datamanager = new midcom_helper_datamanager2_datamanager($this->schemadb);
            if ($schema === null)
            {
                if (! $this->datamanager->autoset_storage($storage))
                {
                    debug_print_r('We got this storage object:', $storage);
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        'Failed to automatically create a datamanager instance for a storage object or a MidCOM type. See the debug level log for more information.');
                    // This will exit().
                }
            }
            else
            {
                if (! $this->datamanager->set_schema($schema))
                {
                    debug_add("Tried to set the schema {$schema}");
                    debug_print_r('We got this storage object:', $storage);
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        'Failed to set the autocreated datamanager\'s schema. See the debug level log for more information.');
                    // This will exit().
                }
                if (! $this->datamanager->set_storage($storage))
                {
                    debug_add("Tried to set the schema {$schema}");
                    debug_print_r('We got this storage object:', $storage);
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        'Failed to set the autocreated datamanager\'s storage object. See the debug level log for more information.');
                    // This will exit().
                }
            }
        }
        else
        {
            debug_print_r('Storage object passed was:', $storage);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'You must pass either a datamanager subclass, an initialized storage encapsulation or a MidCOM DBA object to datamanager2_controller::set_storage()');
            // This will exit.
        }
    }

    /**
     * This is a static factory method which lets you dynamically create controller instances.
     * It takes care of loading the required class files. The returned instances will be created
     * but not initialized.
     *
     * On any error (class not found etc.) the factory method will call generate_error.
     *
     * <b>This function must be called statically.</b>
     *
     * @param string $type The type of the controller (the file name from the controller directory).
     * @return midcom_helper_datamanager2_controller A reference to the newly created controller instance.
     * @static
     */
    function create($type)
    {
        $filename = MIDCOM_ROOT . "/midcom/helper/datamanager2/controller/{$type}.php";
        $classname = "midcom_helper_datamanager2_controller_{$type}";

        require_once($filename);
        return new $classname();
    }

    /**
     * This function should process the form data sent to the server. Its behavior is dependant
     * on the controller used, see the individual class documentations for details.
     *
     * @return string The exitcode of the form processing, usually related to the formmanager
     *     result constants.
     */
    function process_form()
    {
        _midcom_stop_request('The function ' . __CLASS__ . '::' . __FUNCTION__ . ' must be implemented in subclasses.');
    }

    /**
     * This function invokes the display_form() hook on the form manager class.
     */
    function display_form()
    {
        // Prevent temporary objects from failing
        if (   $this->lock_object
            && isset($this->datamanager->storage)
            && isset($this->datamanager->storage->object)
            && isset($this->datamanager->storage->object->guid))
        {
            // Get the metadata object
            $metadata = $this->datamanager->storage->object->metadata;

            if ($metadata->is_locked())
            {
                // Drop us to uncached state when locked
                $_MIDCOM->cache->content->uncached();
                $this->show_unlock();
                return;
            }
        }

        $this->formmanager->display_form();
    }

    /**
     * Show the lock status
     *
     * @access public
     */
    function show_unlock()
    {
        if (   function_exists('mgd_is_element_loaded')
            && mgd_is_element_loaded('midcom_helper_datamanager2_unlock'))
        {
            midcom_show_element('midcom_helper_datamanager2_unlock');
        }
        else
        {
            $metadata = $this->datamanager->storage->object->metadata;
            $person = new midcom_db_person($this->datamanager->storage->object->metadata->locker);
            ?>
                <div class="midcom_helper_datamanager2_unlock">
                    <h2><?php echo $this->_l10n->get('object locked'); ?></h2>
                    <p>
                        <?php echo sprintf($this->_l10n->get('this object was locked by %s'), $person->name); ?>.
                        <?php echo sprintf($this->_l10n->get('lock will expire on %s'), strftime('%x %X', ($metadata->get('locked') + ($GLOBALS['midcom_config']['metadata_lock_timeout'] * 60)))); ?>.
                    </p>
            <?php
            if ($metadata->can_unlock())
            {
                echo "<form method=\"post\">\n";
                echo "    <p class=\"unlock\">\n";
                echo "        <input type=\"hidden\" name=\"midcom_helper_datamanager2_object\" value=\"{$this->datamanager->storage->object->guid}\" />\n";
                echo "        <input type=\"submit\" name=\"midcom_helper_datamanager2_unlock\" value=\"" . $this->_l10n->get('break the lock') . "\" class=\"unlock\" />\n";
                echo "    </p>\n";
                echo "</form>\n";
            }
            ?>
                </div>
            <?php
        }
    }
}
?>