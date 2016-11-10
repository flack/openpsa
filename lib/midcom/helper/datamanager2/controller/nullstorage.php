<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Data Manager null storage controller class.
 *
 * This is a very simple controller class intended for usage directly without a true storage
 * backend. Its main intent is for forms you don't need in the database directly.
 *
 * The class allows you to select a schema and a set of default values suitable for the types
 * you use in the schema.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_controller_nullstorage extends midcom_helper_datamanager2_controller
{
    /**
     * The defaults to initialize the form manager with. This array is indexed
     * by field names.
     *
     * @var Array
     */
    public $defaults = array();

    /**
     * The name of the schema to use. If this is left to null, the first schema
     * from the database is used instead.
     */
    var $schemaname = null;

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
            throw new midcom_error('You must set a schema database before initializing midcom_helper_datamanager2_controller_nullstorage.');
        }
        if ($this->schemaname === null)
        {
            $schemas = array_keys($this->schemadb);
            $this->schemaname = array_shift($schemas);
        }

        // Startup the DM infrastructure
        $storage = new midcom_helper_datamanager2_storage_null($this->schemadb[$this->schemaname], $this->defaults);
        $this->datamanager = new midcom_helper_datamanager2_datamanager($this->schemadb);
        $this->datamanager->set_schema($this->schemaname);
        $this->datamanager->set_storage($storage);
        $this->formmanager = new midcom_helper_datamanager2_formmanager($this->datamanager->schema, $this->datamanager->types);
        return $this->formmanager->initialize($identifier);
    }

    /**
     * This function wraps the form manager processing. If processing is successful, (that is,
     * not 'cancel' or 'edit').
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
     * Note, that this call does not actually execute the save call to the datamanager, there
     * is nothing to save at this point.
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

        $result = $this->formmanager->process_form();

        // Handle successful save explicitly.
        if (   $result == 'save'
            || $result == 'next')
        {
            if (!$this->datamanager->validate())
            {
                debug_add('Type validation failed. Reverting to edit mode transparently.');
                debug_print_r('Validation error listing:', $this->datamanager->validation_errors);
                $result = 'edit';
            }
        }
        // cancel and edit stay untouched.

        return $result;
    }
}
