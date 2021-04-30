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
 */
class midcom_services_indexer_document_midcom extends midcom_services_indexer_document
{
    /**
     * @var midcom_core_dbaobject
     */
    private $object;

    /**
     * The constructor initializes the content object, loads the metadata object
     * and populates the metadata fields accordingly.
     *
     * The source member is automatically populated with the GUID of the document,
     * the RI is set to it as well. The URL is set to an on-site permalink.
     */
    public function __construct(midcom_core_dbaobject $object)
    {
        parent::__construct();

        if (!midcom::get()->config->get('indexer_backend')) {
            return;
        }

        $this->_set_type('midcom');

        $this->object = $object;
        $this->source = $object->guid;
        $this->lang = $this->_i18n->get_current_language();
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
        $this->read_metadata_from_object($this->object);
        foreach ($this->read_metadata() as $key => $value) {
            /**
             * @see parent::read_metadata_from_object()
             */
            if (!in_array($key, ['revised', 'revisor', 'created', 'creator'])) {
                if (in_array($key, ['keywords', 'tags'])) {
                    $this->content .= $value . "\n";
                }
                $this->add_text("META_{$key}", $value);
            }
        }
    }

    /**
     * Usually, documents are processed in batches, and constructing the dm for each
     * document is pretty wasteful, so we keep the instance around and reuse it
     */
    private function read_metadata() : array
    {
        static $meta_dm;
        if ($meta_dm === null) {
            $meta_dm = $this->object->metadata->get_datamanager();
        }
        return $meta_dm->set_storage($this->object)->get_content_html();
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
