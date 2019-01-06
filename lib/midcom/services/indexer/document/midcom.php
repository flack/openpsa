<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is a base class which is targeted at MidCOM content object indexing. It should
 * be used whenever MidCOM documents are indexed, either directly or as a base class.
 *
 * It will take an arbitrary Midgard Object, for which Metadata must be available.
 * The document class will then load the metadata information out of the database
 * and populate all metadata fields of the document from there.
 *
 * If you want to index datamanager driven objects, you should instead look at
 * the datamanager's document class.
 *
 * The GUID of the object being referred is used as a RI.
 *
 * The documents type is "midcom".
 *
 * @package midcom.services
 * @see midcom_services_indexer
 * @see midcom_helper_metadata
 */
class midcom_services_indexer_document_midcom extends midcom_services_indexer_document
{
    /**
     * The metadata instance attached to the object to be indexed.
     *
     * @var midcom_helper_metadata
     */
    protected $_metadata;

    /**
     * The constructor initializes the content object, loads the metadata object
     * and populates the metadata fields accordingly.
     *
     * The source member is automatically populated with the GUID of the document,
     * the RI is set to it as well. The URL is set to an on-site permalink.
     *
     * @param mixed $object The content object to load, passed to the metadata constructor.
     * @see midcom_helper_metadata
     */
    public function __construct($object)
    {
        parent::__construct();

        if (!midcom::get()->config->get('indexer_backend')) {
            return;
        }

        $this->_set_type('midcom');

        if (is_a($object, midcom_helper_metadata::class)) {
            $this->_metadata = $object;
        } else {
            $this->_metadata = midcom_helper_metadata::retrieve($object);
            if ($this->_metadata == false) {
                debug_add('document_midcom: Failed to retrieve a Metadata object, aborting.');
                return;
            }
        }

        $this->source = $this->_metadata->object->guid;
        $this->lang = midcom::get()->i18n->get_content_language();
        // Add language code to RI as well so that different language versions of the object have unique identifiers
        $this->RI = "{$this->source}_{$this->lang}";
        $this->document_url = midcom::get()->permalinks->create_permalink($this->source);

        $this->_process_metadata();
    }

    /**
     * Processes the information contained in the metadata instance.
     */
    private function _process_metadata()
    {
        $this->read_metadata_from_object($this->_metadata->object);
        $metadata = $this->_metadata->get_datamanager()->get_content_html();
        foreach ($metadata as $key => $value) {
            switch ($key) {
                /**
                 * @see parent::read_metadata_from_object()
                 */
                case 'revised':
                case 'revisor':
                case 'created':
                case 'creator':
                    break;

                case 'keywords':
                case 'tags':
                    $this->content .= $value . "\n";
                    // Fall-through intentional
                default:
                    $this->add_text("META_{$key}", $value);
                    break;
            }
        }
        $this->_metadata->release_datamanager();
    }

    /**
     * This will translate all member variables into appropriate
     * field records, missing topic data is auto-detected
     */
    public function members_to_fields()
    {
        if (   empty($this->topic_guid)
            || empty($this->topic_url)
            || empty($this->component)) {
            //if one of those is missing, we override all three to ensure consistency
            $this->process_topic();
        }

        parent::members_to_fields();
    }
}
