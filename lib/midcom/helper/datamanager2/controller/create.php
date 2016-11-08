<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Data Manager object creation controller class.
 *
 * This class requires a callback that creates the actual object that should be created in a
 * correct way. The implementation contained in this class should be able to avoid ghost object
 * in almost all cases. The only thing that is not fully catchable here are cases where
 * the type validation fails. But since all type validation rules are mappable to QuickForm
 * rules, this problem should not appear.
 *
 * Temporary objects (as initially created by the nullstorge implementations) will be
 * memorized using a hidden form variable.
 *
 * <b>Creation callback</b>
 *
 * The callback is defined using the $callback_object and $callback_method members of
 * this class. Only the first one is mandatory, the method name defaults to
 * dm2_create_callback.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_controller_create extends midcom_helper_datamanager2_controller
{
    /**
     * The name of the hidden field used to remember the temporary object id.
     *
     * @var string
     */
    private $_tmpid_fieldname = '__midcom_helper_datamanager2_controller_create_tmpid';

    /**
     * Unique identifier form a creation instance, used mainly in AJAX mode
     *
     * @var string
     */
    public $form_identifier = '';

    /**
     * Whether we're in AJAX mode
     *
     * @var boolean
     */
    public $ajax_mode = false;

    /**
     * Options to pass for the AJAX controller
     *
     * @var Array
     */
    public $ajax_options = Array();

    /**
     * Wide mode switch
     *
     * @var boolean
     */
    public $wide_mode = false;

    /**
     * Window mode switch
     *
     * @var boolean
     */
    public $window_mode = false;

    /**
     * The defaults to initialize the form manager with. This array is indexed
     * by field names.
     *
     * @var Array
     */
    public $defaults = Array();

    /**
     * The name of the schema to use. If this is left to null, the first schema
     * from the database is used instead.
     */
    var $schemaname = null;

    /**
     * A reference to the object containing the creation callback. This reference
     * must be set prior initialization.
     *
     * @var object
     */
    public $callback_object = null;

    /**
     * The name of the callback method to execute. This defaults to _dm2_create_callback.
     * If this member is changed, it must be done prior initialization.
     *
     * @var object
     */
    public $callback_method = 'dm2_create_callback';

    /**
     * You need to set the schema database before calling this function. Optionally
     * you may set defaults and the schemaname to use as well.
     *
     * @param string $identifier The form identifier
     * @return boolean Indicating success.
     */
    function initialize($identifier = null)
    {
        if (count($this->schemadb) == 0)
        {
            throw new midcom_error('You must set a schema database before initializing midcom_helper_datamanager2_controller_create.');
        }
        if (!is_object($this->callback_object))
        {
            throw new midcom_error('You must set a valid callback_object prior initialization: Object is undefined.');
        }
        if (!method_exists($this->callback_object, $this->callback_method))
        {
            throw new midcom_error("You must set a valid callback_object prior initialization: Method {$this->callback_method} is undefined.");
        }

        if ($this->schemaname === null)
        {
            $schemas = array_keys($this->schemadb);
            $this->schemaname = array_shift($schemas);
        }

        // Prepare the storage backend:
        // We use either a null or a tmp storage backend, depending on current state.
        if (array_key_exists($this->_tmpid_fieldname, $_REQUEST))
        {
            $tmpid = $_REQUEST[$this->_tmpid_fieldname];
            $object = midcom::get()->tmp->request_object($tmpid);

            if (!empty($object->guid))
            {
                $storage = new midcom_helper_datamanager2_storage_tmp($this->schemadb[$this->schemaname], $this->defaults, $object);
            }
            else
            {
                $storage = new midcom_helper_datamanager2_storage_null($this->schemadb[$this->schemaname], $this->defaults);
            }
        }
        else
        {
            $storage = new midcom_helper_datamanager2_storage_null($this->schemadb[$this->schemaname], $this->defaults);
        }

        // Prepare the DM itself
        $this->datamanager = new midcom_helper_datamanager2_datamanager($this->schemadb);
        $this->datamanager->set_schema($this->schemaname);
        $this->datamanager->set_storage($storage);

        if ($this->ajax_mode)
        {
            $this->formmanager = new midcom_helper_datamanager2_formmanager_ajax($this->datamanager->schema, $this->datamanager->types);
            $this->process_ajax();
        }
        else
        {
            $this->formmanager = new midcom_helper_datamanager2_formmanager($this->datamanager->schema, $this->datamanager->types);
        }

        return $this->formmanager->initialize($identifier);
    }

    /**
     * Process AJAX-style creation requests
     *
     *@todo: Merge with similar code in ajax controller if possible
     */
    function process_ajax()
    {
        $this->form_identifier = "dm2_composite_{$this->form_identifier}";
        $this->formmanager = new midcom_helper_datamanager2_formmanager_ajax($this->datamanager->schema, $this->datamanager->types);

        $mode = 'inline';
        if ($this->wide_mode)
        {
            $mode = 'wide';
        }
        else if ($this->window_mode)
        {
            $mode = 'window';
        }

        $config = "{mode: '{$mode}'}";
        $script = "jQuery.dm2.ajax_editor.init('{$this->form_identifier}', {$config}, true);";

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/jquery.dm2_ajax_editor.js');
        midcom::get()->head->add_jquery_state_script($script);

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/dm2_ajax_editor.css", 'screen');

        if (!empty($_REQUEST[$this->form_identifier . '_action']))
        {
            $this->formmanager->initialize($this->form_identifier . '_qf');

            switch ($_REQUEST[$this->form_identifier . '_action'])
            {
                case 'edit':
                    $this->formmanager->display_form($this->form_identifier);
                    break;
                case 'preview':
                    $this->formmanager->process_form();
                    $this->formmanager->display_view($this->form_identifier);
                    break;
                case 'save':
                    // Pre process check for validation etc, we create a new object at this point if everything
                    // looks fine. The change of the storage backend will only be done if we have a clear
                    // save/next result from the QF layer.
                    if ($this->formmanager->compute_form_result() == 'save')
                    {
                        $this->_cast_to_storage_object();
                    }

                    if ($this->formmanager->process_form() == 'save')
                    {
                        $this->datamanager->save();
                        $this->formmanager->display_view($this->form_identifier, "dm2_ajax_{$this->datamanager->storage->object->guid}");
                    }
                    else
                    {
                        $this->formmanager->display_form($this->form_identifier);
                    }
                    break;
                case 'cancel':
                    $this->formmanager->display_view($this->form_identifier);
                    break;
            }
            midcom::get()->finish();
        }
    }

    /**
     * This function wraps the form manager processing. If processing is successful and the form is
     * in 'save'ed state, the storage backend is cast to a standard midgard object, any temporary
     * resources are moved to there, and the formdata is saved.
     *
     * There are several possible return values:
     *
     * - <i>save</i> and the variants <i>next</i> and <i>previous</i> (for wizard usage) suggest
     *   successful form processing. The form has already been validated, synchronized with and
     *   saved to the data source.
     * - <i>cancel</i> the user cancelled the form processing, no I/O has been done.
     * - <i>edit</i>, <i>previous</i>, <i>next</i> indicates that the form is not yet successfully
     *   completed. This can mean many things, including validation errors, which the renderer
     *   already outlines in the Form output. No I/O processing has been done.
     *
     * The form will be automatically validated for 'save' and 'next', but not for 'previous'.
     *
     * Normally, all validation should be done during the Form processing, but sometimes this is
     * not possible. These are the cases where type validation rules fail instead of form validation
     * ones. At this time, the integration of type validation is rudimentary and will
     * transparently return edit instead of validation.
     *
     * @return string One of 'save', 'cancel', 'next', 'previous', 'edit', depending on the schema
     *     configuration.
     * @todo Integrate type validation checks cleanly.
     */
    function process_form()
    {
        if ($this->formmanager === null)
        {
            throw new midcom_error('You must initialize a controller class before using it.');
        }

        // Pre process check for validation etc, we create a new object at this point if everything
        // looks fine. The change of the storage backend will only be done if we have a clear
        // save/next result from the QF layer.
        if ($this->formmanager->compute_form_result() == 'save')
        {
            $this->_cast_to_storage_object();
        }

        // Do the actual I/O
        $result = $this->formmanager->process_form();

        if (   $result == 'save'
            || $result == 'next')
        {
            // Ok, we can save now. At this point we already have a content object.
            if (!$this->datamanager->validate())
            {
                // In case that the type validation fails, we return to edit mode
                foreach ($this->datamanager->validation_errors as $field => $error)
                {
                    $this->formmanager->form->setElementError($field, $error);
                }

                if (   $this->datamanager->storage->object
                    && $this->datamanager->storage->object->guid)
                {
                    $this->datamanager->storage->object->delete();
                }
                return 'edit';
            }

            if (   $result == 'save'
                && !$this->datamanager->save())
            {
                // Get the error message
                $midgard_error = midcom_connection::get_error_string();

                // Delete the object as saving failed
                $this->datamanager->storage->object->delete();

                // We seem to have a critical error.
                throw new midcom_error("Failed to save the data to disk: {$midgard_error}. Check the debug level log for more information.");
            }
        }

        // While editing, we keep any temporary storage object known.
        if (   $result != 'save'
            && $result != 'cancel'
            && $this->datamanager->storage->object)
        {
            // Save temporary object ID.
            $this->formmanager->form->addElement('hidden', $this->_tmpid_fieldname, $this->datamanager->storage->object->id);
        }

        return $result;
    }

    /**
     * cast $storage to a simple midgard storage implementation. the reference should propagate this.
     * use a callback to work.
     */
    private function _cast_to_storage_object()
    {
        $object = $this->callback_object->{$this->callback_method}($this);

        // Process temporary object
        if ($this->datamanager->storage->object)
        {
            $tmp_object = $this->datamanager->storage->object;

            if (empty($tmp_object->guid))
            {
                throw new midcom_error('Failed to get the temporary object');
            }

            $tmp_object->move_extensions_to_object($object);
            $tmp_object->delete();
        }

        // Store used schema on the storage object
        $object->set_parameter('midcom.helper.datamanager2', 'schema_name', $this->schemaname);

        $storage = new midcom_helper_datamanager2_storage_midgard($this->datamanager->schema, $object);
        $this->datamanager->set_storage($storage);
    }
}
