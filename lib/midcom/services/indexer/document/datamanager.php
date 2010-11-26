<?php

/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: datamanager.php 22991 2009-07-23 16:09:46Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is geared to ease indexing of datamanager driven documents. The
 * user invoking the indexing must have full read permissions to the object
 * otherwise the NAP or Metadata objects can probably not be loaded successfully.
 *
 * <b>Basic indexing operation</b>
 *
 * This class uses a number of conventions, see below, to merge an existing,
 * datamanager driven document into an indexing capable document. It requires
 * the callee to instantiate the datamanager, as this class would have no
 * idea where to take the schema database from.
 *
 * Additional information is taken out of the Metadata record and the NAP
 * record, both of which have to be available to the indexer.
 *
 * The RI (the GUID) from the base class is left untouched.
 *
 * <b>Indexing field defaults:</b>
 *
 * Unless you specify anything else explicitly in the schema,
 * the class will merge all text based fields together to form the <i>content</i>
 * field of the index record, to allow for easy searching of the document.
 * This will *not* include any metadata like keywords or summaries.
 *
 * If the schema contains a field <i>abstract,</i> it will also be used as
 * abstract field for the indexing process. In the same way, fields named
 * <i>title</i> or <i>author</i> will be used for the index document's title
 * or author respectively. The contents of abstract, title and author will also
 * be appended to the content field at the end of the object construction,
 * easing searching over this fields.
 *
 * If no abstract field is present, the first 200 characters of the content
 * area are used instead.
 *
 * Not all types can be indexed, check the various types in question about their
 * indexing capabilities. In general, if the system should index any non-text
 * field, it will use the CSV representation for implicit conversion.
 *
 * Metadata processing is done by the base class.
 *
 * <b>NAP interaction:</b>
 *
 * NAP is used to determine the title of the object if the schema does not
 * contain a field which is indexed as title. Otherwise, NAP is not yet
 * required.
 *
 * In case the NAP information cannot be retrieved, it uses the URL of the
 * document to populate the title as a last resort.
 *
 * Due to the performance drawbacks it should be avoided to rely on this
 * behavior. Instead, if you don't have a field called 'title' set to auto-indexing,
 * set another field to the index-method title (see below). Note, that you should
 * configure that field in a way so that it is not allowed to leave the field empty,
 * as an empty field would again trigger the NAP fallback.
 *
 * <b>Configurability using the Datamanager schema:</b>
 *
 * You can decorate datamanager fields with various directives influencing
 * the indexing. See the Datamanager's schema documentation for details.
 * Basically, you can choose from the following indexing methods using the
 * key 'index_method' for each field:
 *
 * - The default <i>auto</i> mode will use the above guidelines to determine
 *   the indexing destination automatically.
 * - You can specify <i>abstract,</i> <i>content,</i> <i>title</i> or
 *   <i>author</i> to indicate that the field should be used for the indicated
 *   document fields. The content selector may be specified more then once,
 *   indicating that the content of the relevant fields should be merged.
 * - Any date field can be indexed into its own, range-filterable field using
 *   the <i>date</i> method. In this case, two document fields will be created
 *   actually. One containing the filterable timestamp named directly after
 *   the schema field, and a second one, having the _TS postfix which is set as
 *   noindex containing the plain timestamp.
 * - Finally, you can explicitly index a field as a separate document field
 *   using one of the five field types <i>keyword,</i> <i>unindexed,</i>
 *   <i>unstored</i> or <i>text.</i>  You can further control if the content
 *   of these fields is also added to the main content field. This is useful
 *   if you want to have fields searchable both by explicit field specification
 *   and the default field for simpler searches. This is controlled by setting
 *   the key 'index_merge_with_content' in the field to true or false respectively.
 *   If omitted it defaults to true.
 * - <i>noindex</i> will prevent indexing of this field.
 *
 * The documents type is "midcom_datamanager".
 *
 * @todo Add support for indexing blobs using a yet-to-be-written document_blob
 * @package midcom.services
 * @see midcom_services_indexer
 * @see midcom_helper_datamanager
 */

class midcom_services_indexer_document_datamanager extends midcom_services_indexer_document_midcom
{
    /**
     * The datamanager instance of the document we need to index.
     * This is passed by reference through the constructor.
     *
     * @access private
     * @var midcom_helper_datamanager
     */
    var $_datamanager = null;

    /**
     * The schema in use.
     *
     * @access private
     * @var Array
     */
    var $_schema = null;

    /**
     * The constructor initializes the member variables and invokes
     * _process_datamanager, which will read and process the information
     * out of that instance.
     *
     * The document is ready for indexing after construction. On any
     * critical error, generate_error is triggered.
     *
     * @param midcom_helper_datamanager $datamanager The fully initialized datamanager instance to use
     */
    function __construct(&$datamanager)
    {
        parent::__construct($datamanager->_storage);

        $this->_set_type('datamanager');

        $this->_datamanager =& $datamanager;
        $schemadb = $this->_datamanager->get_layout_database();
        $this->_schema = $schemadb[$this->_datamanager->get_schema_name()];

        $this->_process_datamanager();
        $this->_process_nap();

        $this->content .= "{$this->author}\n{$this->title}\n";

        // Add the abstract only if we haven't done so already.
        if (strstr($this->abstract, $this->content) === false)
        {
            $this->content .= "{$this->abstract}\n";
        }
    }


    /**
     * Processes the information contained in the datamanager instance.
     *
     * The function iterates over the fields in the schema, and processes them
     * according to the rules given in the introduction.
     */
    function _process_datamanager()
    {
        debug_add('Processing datamanager schema ' . $this->_datamanager->get_schema_name());

        foreach ($this->_schema['fields'] as $name => $field)
        {
            debug_print_r("Processing field {$name}:", $field);
            if (! array_key_exists('index_method', $field))
            {
                $field['index_method'] = 'auto';
            }
            if (! array_key_exists('index_merge_with_content', $field))
            {
                $field['index_merge_with_content'] = true;
            }

            switch ($field['index_method'])
            {
                case 'auto':
                    $this->_process_auto_field($name);
                    break;

                case 'abstract':
                    debug_add("Adding field {$name} as abstract");
                    $this->abstract = $this->_get_text_representation($name);
                    break;

                case 'content':
                    debug_add("Adding field {$name} to content");
                    $this->content .= $this->_get_text_representation($name) . "\n";
                    break;

                case 'title':
                    debug_add("Adding field {$name} as title");
                    $this->title = $this->_get_text_representation($name);
                    break;

                case 'author':
                    debug_add("Adding field {$name} as author");
                    $this->author = $this->_get_text_representation($name);
                    break;

                case 'date':
                    debug_add("Adding field {$name} as date");
                    $this->_add_as_date_field($name);
                    break;

                case 'unstored':
                case 'unindexed':
                case 'text':
                case 'keyword':
                    debug_add("Adding field {$name} as {$field['index_method']}");
                    $data = $this->_get_text_representation($name);
                    $this->_add_field($name, $field['index_method'], $data);
                    if ($field['index_merge_with_content'])
                    {
                        $this->content .= $data . "\n";
                    }
                    break;

                case 'noindex':
                    debug_add("Not indexing field {$name}");
                    break;

                default:
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        "Datamanager Indexer Document: Unknown indexing method {$field['index_method']} for field {$name} discovered, aborting.");
                    // This will exit.
            }
        }

        if ($this->abstract == '')
        {
            if (strlen($this->content) > 200)
            {
                $this->abstract = substr($this->content, 0, 200) . ' ...';
            }
            else
            {
                $this->abstract = $this->content;
            }
        }

        return false;
    }


    /**
     * Returns a textual representation of the corresponding field.
     *
     * Actual behavior is dependent on the datatype. Text fields are
     * accessed directly, for other fields, the CSV representation is
     * used.
     *
     * Text fields run through the html2text converter of the document
     * base class.
     *
     * Attention: This function accesses originally private datamanager
     * members. It is the only possible way to access the CSV interface
     * of individual fields.
     *
     * @access private
     * @param string $name The name of the field that should be queried
     * @return string The textual representation of the field.
     * @see midcom_services_indexer_document::html2text()
     */
    function _get_text_representation($name)
    {
        return $this->datamanager_get_text_representation($this->_datamanager, $name);
    }

    /**
     * Returns a textual representation of the specified datamanager
     * field.
     *
     * Actual behavior is dependent on the datatype. Text fields are
     * accessed directly, for other fields, the CSV representation is
     * used.
     *
     * Text fields run through the html2text converter of the document
     * base class.
     *
     * Attention: This function accesses originally private datamanager
     * members. It is the only possible way to access the CSV interface
     * of individual fields.
     *
     * @param midcom_helper_datamanager $datamanager A reference to the
     *     datamanager instance.
     * @param string $name The name of the field that should be queried
     * @return string The textual representation of the field.
     * @see midcom_services_indexer_document::html2text()
     */
    function datamanager_get_text_representation(&$datamanager, $name)
    {
        switch ($datamanager->_layout['fields'][$name]['datatype'])
        {
            case 'text':
                return $this->html2text($datamanager->data[$name]);

            // Types with no defined representation:
            case 'blob':
            case 'image':
            case 'collection':
            case 'mailtemplate':
            case 'account':
            case 'multiselect':
                return '';
        }

        // Default:
        return $datamanager->_datatypes[$name]->get_csv_data();
    }

    /**
     * This function tries to convert the field $name into a date
     * representation. Unixdate fields are used directly (localtime is used,
     * not GMT), other fields will be parsed with strtodate.
     *
     * Invalid strings which are not parseable using strtotime will be
     * stored as a "0" timestamp.
     *
     * @access private
     * @param string $name The name of the field that should be stored
     */
    function _add_as_date_field($name)
    {
        if ($this->_schema['fields'][$name]['datatype'] == 'unixdate')
        {
            $this->add_date_pair($name, $this->_datamanager->data[$name]['timestamp']);
        }
        else
        {
            $string = $this->_get_text_representation($name);
            $timestamp = strtotime($string);
            if ($timestamp === -1)
            {
                debug_add("The string representation of the field {$name} could not be parsed into a timestamp; treating as 0.", MIDCOM_LOG_INFO);
                debug_print_r('"String representation was:', $string);
                $timestamp = 0;
            }
            $this->add_date_pair($name, $timestamp);
        }
    }

    /**
     * This helper will process the given field using the guidelines given in
     * the class documentation.
     *
     * @param string $name The name of the field that should be automatically processed.
     */
    function _process_auto_field($name)
    {
        switch ($name)
        {
            case 'abstract':
                debug_add("Adding field {$name} as abstract");
                $this->abstract = $this->_get_text_representation($name);
                break;

            case 'title':
                debug_add("Adding field {$name} as title");
                $this->title = $this->_get_text_representation($name);
                break;

            case 'author':
                debug_add("Adding field {$name} as author");
                $this->author = $this->_get_text_representation($name);
                break;

            default:
                debug_add("Adding field {$name} to content");
                $this->content .= $this->_get_text_representation($name) . "\n";
                break;

        }
    }


    /**
     * If necessary, process the available NAP information to fill in the
     * title field.
     *
     * In case the NAP information cannot be retrieved, it uses the URL of the
     * document as a last resort.
     */
    function _process_nap()
    {
        if (strlen(trim($this->title)) == 0)
        {
            debug_add('The document does not yet have a title. We load the NAP record therefore to fill this gap.');
            $nap = new midcom_helper_nav();
            $object = $nap->resolve_guid($this->source);
            if ($object !== false)
            {
                $this->title = $object[MIDCOM_NAV_NAME];
            }
            else
            {
                $this->title = $object->document_url;
            }
        }
    }
}

?>