<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class encapsulates a single indexer document. It is used for both indexing
 * and retrieval.
 *
 * A document consists of a number of fields, each field has different properties
 * when handled by the indexer (exact behavior depends, as always, on the indexer
 * backend in use). On retrieval, this field information is lost, all fields being
 * of the same type (naturally). The core indexer backend supports these field
 * types:
 *
 * - <i>date</i> is a date-wrapped field suitable for use with the Date Filter.
 * - <i>keyword</i> is store and indexed, but not tokenized.
 * - <i>unindexed</i> is stored but neither indexed nor tokenized.
 * - <i>unstored</i> is not stored, but indexed and tokenized.
 * - <i>text</i> is stored, indexed and tokenized.
 *
 * This class should not be instantiated directly, a new instance of this class
 * can be obtained using the midcom_services_indexer class.
 *
 * A number of predefined fields are available using member fields. These fields
 * are all meta-fields. See their individual documentation for details. All fields
 * are mandatory unless mentioned otherwise explicitly and, as always, assumed to
 * be in the local charset.
 *
 * Remember, that both date and unstored fields are not available on retrieval.
 * For the core fields, all timestamps are stored twice therefore, once as searchable
 * field, and once as readable timestamp.
 *
 * The class will automatically pass all data to the i18n charset conversion functions,
 * thus you work using your site's charset like usual. UTF-8 conversion is done
 * implicitly.
 *
 * @package midcom.services
 * @see midcom_services_indexer
 * @todo The Type field is not yet handled properly.
 */
class midcom_services_indexer_document
{
    /**
     * An associative array containing all fields of the current document.
     *
     * Each field is indexed by its name (a string). The value is another
     * array containing the fields "name", type" and "content".
     *
     * @var Array
     */
    private $_fields = array();

    /**
     * A reference to the i18n service, used for charset conversion.
     *
     * @var midcom_services_i18n
     */
    protected $_i18n = null;

    /**
     * This is the score of this document. Only populated on resultset documents,
     * of course.
     *
     * @var double
     */
    public $score = 0.0;

    /* ------ START OF DOCUMENT FIELDS --------- */

    /**
     * The Resource Identifier of this document.
     *
     * Must be UTF-8 on assignment already.
     *
     * This field is mandatory.
     *
     * @var string
     */
    public $RI = '';

    /**
     * Two letter language code of the document content
     *
     * This field is optional.
     *
     * @var string
     */
    public $lang = '';

    /**
     * The GUID of the topic the document is assigned to.
     *
     * May be empty for non-midgard resources.
     *
     * This field is mandatory.
     *
     * @var string GUID
     */
    public $topic_guid = '';

    /**
     * The name of the component responsible for the document.
     *
     * May be empty for non-midgard resources.
     *
     * This field is mandatory.
     *
     * @var string
     */
    public $component = '';

    /**
     * The fully qualified URL to the document, this should be a PermaLink.
     *
     * This field is mandatory.
     *
     * @var string
     */
    public $document_url = '';

    /**
     * The time of document creation, this is a UNIX timestamp.
     *
     * This field is mandatory.
     *
     * @var int
     */
    public $created = 0;

    /**
     * The time of the last document modification, this is a UNIX timestamp.
     *
     * This field is mandatory.
     *
     * @var int
     */
    public $edited = 0;

    /**
     * The timestamp of indexing.
     *
     * This field is added automatically and to be considered read-only.
     *
     * @var int
     */
    public $indexed = 0;

    /**
     * The MidgardPerson who created the object.
     *
     * This is optional.
     *
     * @var midcom_db_person
     */
    public $creator = null;

    /**
     * The MidgardPerson who modified the object the last time.
     *
     * This is optional.
     *
     * @var midcom_db_person
     */
    public $editor = null;

    /**
     * The title of the document
     *
     * This is mandatory.
     *
     * @var string
     */
    public $title = '';

    /**
     * The content of the document
     *
     * This is mandatory.
     *
     * This field is empty on documents retrieved from the index.
     *
     * @var string
     */
    public $content = '';

    /**
     * The abstract of the document
     *
     * This is optional.
     *
     * @var string
     */
    public $abstract = '';

    /**
     * The author of the document
     *
     * This is optional.
     *
     * @var string
     */
    public $author = '';

    /**
     * An additional tag indicating the source of the document for use by the
     * component doing the indexing.
     *
     * This value is not indexed and should not be used by anybody except the
     * component doing the indexing.
     *
     * This is optional.
     *
     * @var string
     */
    public $source = '';

    /**
     * The full path to the topic that houses the document.
     *
     * For external resources, this should be either a MidCOM topic, to which this
     * resource is associated or some "directory" after which you could filter.
     * You may also leave it empty prohibiting it to appear on any topic-specific search.
     *
     * The value should be fully qualified, as returned by MIDCOM_NAV_FULLURL, including
     * a trailing slash, f.x. https://host/path/to/topic/
     *
     * This is optional.
     *
     * @var string
     */
    public $topic_url = '';

    /**
     * The type of the document, set by subclasses and added to the index
     * automatically.
     *
     * The type *must* reflect the original type hierarchy. It is to be set
     * using the $this->_set_type call <i>after</i> initializing the base class.
     *
     * @see is_a()
     * @see _set_type
     * @var string
     */
    public $type = '';

    /**
     * Security mechanism used to determine the availability of a search result.
     * Can be one of:
     *
     * - 'default': Use only built-in processing (topic and metadata visibility checks), this is, as you might have guessed, the default.
     * - 'component': Invoke the _on_check_document_visible component interface method of the component after doing default checks.
     *   This security class absolutely requires the document to contain a valid topic GUID, otherwise access control will fail anyway.
     * - 'function:$function_name': Invoke the globally available function $function_name, its signature is <i>boolean $function_name ($document, $topic)</i>,
     *   if you don't change the document during the check, you don't need to pass by-reference, so this is up to you. The topic passed is the
     *   Return true if the document is visible, false otherwise.
     * - 'class:$class_name': Like above, but using a class instead. The class must provide a statically callable <i>get_instance()</i> method, which
     *   returns a usable instance of the class (mostly, this should be a singleton, for performance reasons). The instance returned is assigned
     *   by-reference. On that object, the method check_document_permissions, whose signature must be identical to the function callback.
     *
     * @var string
     * @see midcom_baseclasses_components_interface::_on_check_document_permissions()
     */
    public $security = 'default';

    /**
     * This is have support for #651 without rewriting all components' index methods
     *
     * If set to false the indexer backend will silently skip this document.
     *
     * @see http://trac.midgard-project.org/ticket/651
     * @var boolean
     */
    public $actually_index = true;

    /* ------ END OF DOCUMENT FIELDS --------- */

    /**
     * Initialize the object, nothing fancy here.
     */
    public function __construct()
    {
        $this->_i18n = midcom::get()->i18n;
    }

    /**
     * Returns the contents of the field name or false on failure.
     *
     * @param string $name The name of the field.
     * @return mixed The content of the field or false on failure.
     */
    public function get_field($name)
    {
        if (!array_key_exists($name, $this->_fields))
        {
            debug_add("Field {$name} not found in the document.", MIDCOM_LOG_INFO);
            return false;
        }
        return $this->_i18n->convert_from_utf8($this->_fields[$name]['content']);
    }

    /**
     * Returns the complete internal field record, including type and UTF-8 encoded
     * content.
     *
     * This should normally not be used from the outside, it is geared towards the
     * indexer backends, which need the full field information on indexing.
     *
     * @param string $name The name of the field.
     * @return Array The full content record.
     */
    public function get_field_record($name)
    {
        if (!array_key_exists($name, $this->_fields))
        {
            debug_add("Field {$name} not found in the document.", MIDCOM_LOG_INFO);
            return false;
        }
        return $this->_fields[$name];
    }

    /**
     * Returns a list of all defined fields.
     *
     * @return Array Fieldname list.
     */
    public function list_fields()
    {
        return array_keys($this->_fields);
    }

    /**
     * Remove a field from the list. Nonexistent fields are ignored silently.
     *
     * @param string $name The name of the field.
     */
    public function remove_field($name)
    {
        unset($this->_fields[$name]);
    }

    /**
     * Add a date field. A timestamp is expected, which is automatically
     * converted to a suitable ISO timestamp before storage.
     *
     * Direct specification of the ISO timestamp is not yet possible due
     * to lacking validation outside the timestamp range.
     *
     * If a field of the same name is already present, it is overwritten
     * silently.
     *
     * @param string $name The field's name.
     * @param int $timestamp The timestamp to store.
     */
    public function add_date($name, $timestamp)
    {
        // This is always UTF-8 conformant.
        $this->_add_field($name, 'date', gmstrftime('%Y-%m-%dT%H:%M:%SZ', $timestamp), true);
    }

    /**
     * Create a normal date field and an unindexed _TS-postfixed timestamp field at the same time.
     *
     * This is useful because the date fields are not in a readable format,
     * it can't even be determined that they were a date in the first place.
     * so the _TS field is quite useful if you need the original value for the
     * timestamp.
     *
     * @param string $name The field's name, "_TS" is appended for the plain-timestamp field.
     * @param int $timestamp The timestamp to store.
     */
    public function add_date_pair($name, $timestamp)
    {
        $this->add_date($name, $timestamp);
        $this->add_unindexed("{$name}_TS", $timestamp);
    }

    /**
     * Add a keyword field.
     *
     * @param string $name The field's name.
     * @param string $content The field's content.
     */
    public function add_keyword($name, $content)
    {
        $this->_add_field($name, 'keyword', $content);
    }

    /**
     * Add a unindexed field.
     *
     * @param string $name The field's name.
     * @param string $content The field's content.
     */
    public function add_unindexed($name, $content)
    {
        $this->_add_field($name, 'unindexed', $content);
    }

    /**
     * Add a unstored field.
     *
     * @param string $name The field's name.
     * @param string $content The field's content.
     */
    public function add_unstored($name, $content)
    {
        $this->_add_field($name, 'unstored', $this->html2text($content));
    }

    /**
     * Add a text field.
     *
     * @param string $name The field's name.
     * @param string $content The field's content.
     */
    public function add_text($name, $content)
    {
        $this->_add_field($name, 'text', $this->html2text($content));
    }

    /**
     * Add a search result field, this should normally not be done
     * manually, the indexer will call this function when creating a
     * document out of a search result.
     *
     * @param string $name The field's name.
     * @param string $content The field's content, which is <b>assumed to be UTF-8 already</b>
     */
    public function add_result($name, $content)
    {
        $this->_add_field($name, 'result', $content, true);
    }

    /**
     * Add a person field.
     *
     * @param string $name The field's name.
     * @param midcom_db_person $person The field's content.
     */
    private function add_person($name, $person)
    {
        if (!is_object($person))
        {
            if (!is_null($person))
            {
                debug_print_r("Warning, person is not an object:", $person, MIDCOM_LOG_INFO);
            }
            $this->add_text($name, '');
        }
        else
        {
            $this->add_text($name, $person->guid);
        }
    }

    /**
     * This will translate all member variables into appropriate
     * field records, the backend should call this immediately before
     * indexing.
     *
     * This call will automatically populate indexed with time()
     * and author with the name of the creator (if set).
     */
    public function members_to_fields()
    {
        // Complete fields
        $this->indexed = time();
        if (   $this->author == ''
            && isset($this->creator->name))
        {
            $this->author = $this->creator->name;
        }

        // __RI does not need to be populated, this is done by backends.
        $this->add_unindexed('__LANG', $this->lang);
        $this->add_text('__TOPIC_GUID', $this->topic_guid);
        $this->add_text('__COMPONENT', $this->component);
        $this->add_unindexed('__DOCUMENT_URL', $this->document_url);
        $this->add_text('__TOPIC_URL', $this->topic_url);
        $this->add_date_pair('__CREATED', $this->created);
        $this->add_date_pair('__EDITED', $this->edited);
        $this->add_date_pair('__INDEXED', $this->indexed);
        $this->add_text('title', $this->title);
        $this->add_unstored('content', $this->content);

        $this->add_unindexed('__SOURCE', $this->source);
        $this->add_person('__CREATOR', $this->creator);
        $this->add_person('__EDITOR', $this->editor);

        $this->add_text('author', $this->author);
        $this->add_text('abstract', $this->abstract);
        $this->add_text('__TYPE', $this->type);
        $this->add_unindexed('__SECURITY', $this->security);
    }

    /**
     * Populate all relevant members with the respective values after
     * retrieving a document from the index
     */
    public function fields_to_members()
    {
        $this->RI = $this->get_field('__RI');
        $this->lang = $this->get_field('__LANG');
        $this->topic_guid = $this->get_field('__TOPIC_GUID');
        $this->component = $this->get_field('__COMPONENT');
        $this->document_url = $this->get_field('__DOCUMENT_URL');
        $this->topic_url = $this->get_field('__TOPIC_URL');
        $this->created = $this->get_field('__CREATED_TS');
        $this->edited = $this->get_field('__EDITED_TS');
        $this->indexed = $this->get_field('__INDEXED_TS');
        $this->title = $this->get_field('title');

        $this->source = $this->get_field('__SOURCE');
        $this->creator = $this->get_field('__CREATOR');
        if ($this->creator != '')
        {
            $this->creator = $this->read_person($this->creator);
        }
        $this->editor = $this->get_field('__EDITOR');
        if ($this->editor != '')
        {
            $this->editor = $this->read_person($this->editor);
        }
        $this->author = $this->get_field('author');
        $this->abstract = $this->get_field('abstract');
        $this->type = $this->get_field('__TYPE');
        $this->security = $this->get_field('__SECURITY');
    }

    /**
     * Internal helper which actually stores a field.
     *
     * @param string $name The field's name.
     * @param string $type The field's type.
     * @param string $content The field's content.
     * @param boolean $is_utf8 Set this to true explicitly, to override charset conversion and assume $content is UTF-8 already.
     */
    protected function _add_field($name, $type, $content, $is_utf8 = false)
    {
        $this->_fields[$name] = array
        (
            'name' => $name,
            'type' => $type,
            'content' => ($is_utf8 ? $content : $this->_i18n->convert_to_utf8($content))
        );
    }

    /**
     * Convert HTML to plain text (relatively simple):
     *
     * Basically, JavaScript blocks and
     * HTML Tags are stripped, and all HTML Entities
     * are converted to their native equivalents.
     *
     * Don't replace with an empty string but with a space, so that constructs like
     * <li>torben</li><li>nehmer</li> are recognized correctly.
     *
     * @param string $text The text to convert to text
     * @return string The converted text.
     */
    public function html2text($text)
    {
        $search = array
        (
            "'\s*<script[^>]*?>.*?</script>\s*'si", // Strip out javascript
            "'\s*<[\/\!]*?[^<>]*?>\s*'si", // Strip out html tags
        );
        $replace = array
        (
            ' ',
            ' ',
        );
        $result = $this->_i18n->html_entity_decode(preg_replace($search, $replace, $text));
        return trim(preg_replace('/\s+/s', ' ', $result));
    }

    /**
     * Checks whether the given document is an instance of given document type.
     *
     * This is equivalent to the is_a object hierarchy check, except that it
     * works with MidCOM documents.
     *
     * @see $type
     * @see _set_type()
     * @param string $document_type The base type to search for.
     * @return boolean Indicating relationship.
     */
    public function is_a($document_type)
    {
        return (strpos($this->type, $document_type) === 0);
    }

    /**
     * Sets the type of the object, reflecting the inheritance hierarchy.
     *
     * @see $type
     * @see is_a()
     * @param string $type The name of this document type
     */
    protected function _set_type($type)
    {
        if (strlen($this->type) == 0)
        {
            $this->type = $type;
        }
        else
        {
            $this->type .= "_{$type}";
        }
    }

    /**
     * Tries to resolve created, revised, author, editor and creator for the document from Midgard object
     *
     * @param midgard_object $object object to use as source for the info
     */
    public function read_metadata_from_object($object)
    {
        // Published is set to non-empty value, use it as creation data
        if (   !empty($object->metadata->published)
            && !preg_match('/0{1,4}-0{1,2}0{1,2}\s+0{1,2}:0{1,2}:0{1,2}/', $object->metadata->published))
        {
            $this->created = $this->read_unixtime($object->metadata->published);
        }
        elseif (isset($object->metadata->created))
        {
            $this->created = $this->read_unixtime($object->metadata->created);
        }
        // Revised
        if (isset($object->metadata->revised))
        {
            $this->edited = $this->read_unixtime($object->metadata->revised);
        }
        // Heuristics to determine author
        if (!empty($object->metadata->authors))
        {
            $this->author = $this->read_authorname($object->metadata->authors);
        }
        elseif (!empty($object->metadata->creator))
        {
            $this->author = $this->read_authorname($object->metadata->creator);
        }
        // Creator
        if (isset($object->metadata->creator))
        {
            $this->creator = $this->read_person($object->metadata->creator);
        }
        // Editor
        if (isset($object->metadata->revisor))
        {
            $this->editor = $this->read_person($object->metadata->revisor);
        }
    }

    /**
     * Heuristics to determine how to convert given timestamp to local unixtime
     *
     * @param string $stamp ISO or unix datetime
     * @return unixtime
     */
    private function read_unixtime($stamp)
    {
        if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $stamp))
        {
            // ISO Datetime
            return @strtotime($stamp);
        }
        // Unix timestamp
        return (int)$stamp;
    }

    /**
     * Get person by given ID, caches results.
     *
     * @param string $id GUID or ID to get person for
     * @return midcom_db_person object
     */
    private function read_person($id)
    {
        try
        {
            return midcom_db_person::get_cached($id);
        }
        catch (midcom_error $e)
        {
            return false;
        }
    }

    /**
     * Gets person name for given ID (in case it's imploded_wrapped of multiple GUIDs it will use the first)
     *
     * @param string $id GUID or ID to get person for
     * @return string $author->name
     */
    private function read_authorname($id)
    {
        // Check for imploded_wrapped DM2 select storage.
        if (strpos($id, '|') !== false)
        {
            $id_arr = array_filter(explode('|', $id));
            // Find first non-empty value in the array and use that
            $id = (!empty($id_arr)) ? array_shift($id_arr) : false;
        }

        $author = midcom::get()->auth->get_user($id);
        if (!$author)
        {
            return '';
        }
        return $author->name;
    }
}
