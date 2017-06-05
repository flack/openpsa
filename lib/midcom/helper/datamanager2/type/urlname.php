<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 URL-name datatype, text encapsulated here is checked for
 * name cleanliness and url-safety semantics and for uniqueness. This extends
 * the normal text datatype with the following config additions:
 *
 * <b>New configuration options:</b>
 *
 * - <i>string title_field:</i> Defaults to 'title', this is the name of the field
 *   (in same schema) to use for title information (used when autogenerating values)
 * - <i>bool allow_catenate:</i> Defaults to false, if this is set to true then on
 *   name value clash, we autogenerate a new unique name and use it transparently
 *   instead of raising a validation error.
 * - <i>bool allow_unclean:</i> Defaults to false, if this is set to true then we
 *   do not check name for "cleanlines"
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_urlname extends midcom_helper_datamanager2_type_text
{
    /**
     * Do we allow automatic catenation to make the name unique?
     *
     * @var boolean
     */
    public $allow_catenate = false;

    /**
     * Do we allow "unclean" names
     *
     * @var boolean
     */
    public $allow_unclean = false;

    /**
     * The field (in the same schema) that we use for title value
     *
     * @var string
     */
    public $title_field = 'title';

    /**
     * Keep the original value in store
     */
    var $_orig_value = null;

    /**
     * This event handler is called after construction, so passing references to $this to the
     * outside is safe at this point.
     */
    public function _on_initialize()
    {
        /**
         * If write_privilege is not set, default to midcom:urlname.
         *
         * NOTE: In theory we're a bit late here with manipulating this value
         * but since it ATM only affects widgets (which are initialized later) anyway
         * and in fact we want it to only freeze the widget this is fine
         */
        $schema =& $this->_datamanager->schema->fields[$this->name];
        if (!isset($schema['write_privilege'])) {
            $schema['write_privilege'] = [
                'privilege' => 'midcom:urlname',
            ];
        }
    }

    public function convert_from_storage($source)
    {
        $this->_orig_value = $source;
        parent::convert_from_storage($source);
    }

    /**
     * Helper to get copy instead of reference to given object
     *
     * This is to avoid messing with the original values when using the name uniqueness checks
     */
    function _copy_object($object)
    {
        return $object;
    }

    /**
     * Make sure our name is nice and clean
     *
     * @see http://trac.midgard-project.org/ticket/809
     */
    public function _on_validate()
    {
        $schema = $this->storage->_schema->fields[$this->name];
        $copy = $this->_copy_object($this->storage->object);
        $property = $schema['storage']['location'];
        $resolver = new midcom_helper_reflector_nameresolver($copy);
        $generator = midcom::get()->serviceloader->load('midcom_core_service_urlgenerator');

        if (empty($this->value)) {
            if (   isset($this->_datamanager->types[$this->title_field])
                && $this->_datamanager->types[$this->title_field]->value) {
                $copy->{$property} = $generator->from_string($this->_datamanager->types[$this->title_field]->value);
                $this->value = $resolver->generate_unique_name();
            }
        }

        $copy->{$property} = $this->value;

        if (!$resolver->name_is_safe($property)) {
            $this->validation_error = sprintf($this->_l10n->get('type urlname: name is not "URL-safe", try "%s"'), $generator->from_string($this->value));
            return false;
        }

        if (   !$this->allow_unclean
            && !$resolver->name_is_clean($property)) {
            $this->validation_error = sprintf($this->_l10n->get('type urlname: name is not "clean", try "%s"'), $generator->from_string($this->value));
            return false;
        }

        if (!$resolver->name_is_unique()) {
            if (!$this->allow_catenate) {
                $this->validation_error = sprintf($this->_l10n->get('type urlname: name is already taken, try "%s"'), $new_name);
                return false;
            }
            // If allowed to, silently use the generated name
            $new_name = $resolver->generate_unique_name();
            $this->value = $new_name;
            $this->_orig_value = $new_name;
            $copy->{$property} = $this->value;
        }

        return true;
    }
}
