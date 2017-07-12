<?php
/**
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

namespace midcom\datamanager\indexer;

use Symfony\Component\Form\FormView;
use midcom\datamanager\datamanager;
use midcom\datamanager\template\view;
use midcom_services_indexer_document_midcom;
use midcom_error;

/**
 * This class is geared to ease indexing of datamanager driven documents. The
 * user invoking the indexing must have full read permissions to the object.
 *
 * <b>Basic indexing operation</b>
 *
 * This class uses a number of conventions, see below, to merge an existing
 * datamanager driven document into an indexing capable document. It requires
 * the callee to instantiate the datamanager, as this class would have no
 * idea where to take the schema database from.
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
 * If the schema contains a field <i>abstract</i>, it will also be used as
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
 * <b>Document title:</b>
 *
 * You should either have an auto-indexed title field, or an assortment
 * of other fields manually assigned to index to the title field.
 *
 * <b>Configurability using the Datamanager schema:</b>
 *
 * You can decorate datamanager fields with various directives influencing
 * the indexing. See the Datamanager's schema documentation for details.
 * Basically, you can choose from the following indexing methods using the
 * key 'index_method' for each field:
 *
 * - The default <i>auto</i> mode will use the above guidelines to determine
 *   the indexing destination automatically, adding data to the content, abstract,
 *   title and author fields respectively.
 * - You can specify <i>abstract</i>, <i>content</i>, <i>title</i> or
 *   <i>author</i> to indicate that the field should be used for the indicated
 *   document fields. The content selector may be specified more than once,
 *   indicating that the content of the relevant fields should be merged.
 * - Any date field can be indexed into its own, range-filterable field using
 *   the <i>date</i> method. In this case, two document fields will be created
 *   actually. One containing the filterable timestamp named directly after
 *   the schema field, and a second one, having the _TS postfix which is set as
 *   noindex containing the plain timestamp.
 * - Finally, you can explicitly index a field as a separate document field
 *   using one of the five field types <i>keyword</i>, <i>unindexed</i>,
 *   <i>unstored</i> or <i>text</i>. You can further control if the content
 *   of these fields is also added to the main content field. This is useful
 *   if you want to have fields searchable both by explicit field specification
 *   and the default field for simpler searches. This is controlled by setting
 *   the bolean key 'index_merge_with_content' in the field, which defaults
 *   to true.
 * - <i>noindex</i> will prevent indexing of this field.
 *
 * The documents type is "midcom_datamanager".
 *
 * @see midcom_services_indexer
 */
class document extends midcom_services_indexer_document_midcom
{
    /**
     * The datamanager instance of the document we need to index.
     *
     * @var datamanager
     */
    private $datamanager;

    /**
     * The constructor initializes the member variables and invokes
     * _process_datamanager, which will read and process the information
     * out of that instance.
     *
     * The document is ready for indexing after construction. On any
     * critical error, midcom_error is triggered.
     *
     * @param datamanager $datamanager The fully initialized datamanager2 instance to use
     */
    public function __construct($datamanager)
    {
        parent::__construct($datamanager->get_storage()->get_value());

        $this->_set_type('datamanager');

        $this->datamanager = $datamanager;

        $this->process_datamanager();
        $this->complete_fields();
    }

    /**
     * Completes all fields which are not yet complete:
     *
     * content is completed with author, title and, if necessary, abstract.
     *
     * The title is set to the documents' URL in case that no title is set yet. The title
     * is not added to the content field in that case.
     */
    private function complete_fields()
    {
        $this->content .= "{$this->author}\n{$this->title}\n";

        // Add the abstract only if we haven't done so already.
        if (strstr($this->abstract, $this->content) === false) {
            $this->content .= "{$this->abstract}\n";
        }

        if (! $this->title) {
            $this->title = $this->document_url;
        }
    }

    /**
     * Processes the information contained in the datamanager instance.
     *
     * The function iterates over the fields in the schema, and processes them
     * according to the rules given in the introduction.
     */
    private function process_datamanager()
    {
        $view = $this->datamanager->get_form()->createView();
        $renderer = $this->datamanager->get_renderer();
        $renderer->set_template($view, new view($renderer));
        foreach ($view as $name => $field) {
            $method = $field->vars['index_method'];
            if ($method == 'auto') {
                $method = $this->resolve_auto_method($field->vars['name']);
            }

            switch ($method) {
                case 'abstract':
                case 'title':
                case 'author':
                    $this->{$method} = $renderer->widget($field);
                    break;

                case 'content':
                    $this->content .= $renderer->widget($field) . "\n";
                    break;

                case 'date':
                    $this->add_as_date_field($name);
                    break;

                case 'attachment':
                    if (!empty($field->vars['value'])) {
                        //only index the first attachment for now
                        $attachment = array_shift($field->vars['value']);
                        if (   !$attachment instanceof \midcom_db_attachment
                            && !empty($attachment['object'])) {
                            //This is the form edit case
                            //@todo: In create case, nothing is found currently
                            $attachment = $attachment['object'];
                        }
                        if ($attachment instanceof \midcom_db_attachment) {
                            $att_doc = new \midcom_services_indexer_document_attachment($attachment, $view->vars['value']->get_value());
                            $this->content .= $att_doc->content;
                            $this->abstract .= $att_doc->abstract;
                        }
                    }

                    break;

                case 'unstored':
                case 'unindexed':
                case 'text':
                case 'keyword':
                    $data = $renderer->widget($field);
                    $function = 'add_' . $method;
                    $this->$function($name, $data);
                    if ($field->vars['index_merge_with_content']) {
                        $this->content .= $data . "\n";
                    }
                    break;

                case 'noindex':
                    break;

                default:
                    throw new midcom_error(" Unknown indexing method {$method} for field {$name} discovered, aborting.");
            }
        }

        if ($this->abstract == '') {
            $this->abstract = $this->html2text($this->content);
            if (strlen($this->abstract) > 200) {
                $this->abstract = substr($this->abstract, 0, 200) . ' ...';
            }
        }
    }

    /**
     * This function tries to convert the field $name into a date
     * representation. Unixdate fields are used directly (localtime is used,
     * not GMT), other fields will be parsed with strtodate.
     *
     * Invalid strings which are not parseable using strtotime will be
     * stored as a "0" timestamp.
     *
     * Be aware, that this will work only for current dates in range of an
     * UNIX timestamp. For all other cases you should use an ISO 8601 representation,
     * which should work as well with Lucene range queries.
     *
     * @todo Refactor this to use DateTime
     * @param string $name The name of the field that should be stored
     */
    private function add_as_date_field(FormView $field)
    {
        if ($field->vars['dm2_type'] == 'date') {
            $timestamp = 0;
            if (!$this->datamanager->types[$name]->is_empty()) {
                $timestamp = $this->datamanager->types[$name]->value->format('U');
            }
            $this->add_date_pair($name, $timestamp);
        } else {
            $string = $this->datamanager->types[$name]->convert_to_html();
            $timestamp = strtotime($string);
            if ($timestamp === -1) {
                debug_add("The string representation of the field {$name} could not be parsed into a timestamp; treating as 0.", MIDCOM_LOG_INFO);
                debug_print_r('String representation was:', $string);
                $timestamp = 0;
            }
            $this->add_date_pair($name, $timestamp);
        }
    }

    /**
     * @param string $name The field name
     * @return string index method
     */
    private function resolve_auto_method($name)
    {
        if (   $name == 'abstract'
            || $name == 'title'
            || $name == 'author') {
            return $name;
        }
        return 'content';
    }
}
