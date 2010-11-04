<?php

/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: XMLCommClient.php 22991 2009-07-23 16:09:46Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class provides an interface to the MRFC 14 XML driven indexer
 * backends.
 *
 * This class is responsible for producing an XML Request file.
 *
 * ...
 *
 * @package midcom.services
 * @see midcom_services_indexer
 * @see midcom_services_indexer_XMLComm_ResponseReader
 */

class midcom_services_indexer_XMLComm_RequestWriter
{
    /**
     * The index name to use
     *
     * @var string
     * @access private
     */
    var $_index_name = null;

    /**
     * The current request content. This does not include the XML header
     * and the enclosing request tag. This will be added upon retrieval of
     * this string.
     */
    var $_request = '';

    /**
     * Initialize an XMLComm Request Writer.
     *
     * All double-quotes will be silently removed from the index name.
     *
     * @param string $index_name The name of the Index to be used. If not specified, the midcom config key 'indexer_index_name' is used.
     */
    function __construct($index_name = null)
    {
        // Nothing to do yet.
        if (is_null($index_name))
        {
            $this->_index_name = $GLOBALS['midcom_config']['indexer_index_name'];
        }
        else
        {
            $this->_index_name = $index_name;
        }
        $this->_index_name = $this->_encode_argument($this->_index_name);
    }

    /**
     * Surrounds the request data with an xml header and the request tags
     * and returns the result.
     *
     * @param boolean $mask_request Set this to true to mask all occurrences of </request> in the content with
     *     </request_>, which is required for the TCP backends. (PHP cannot close a TCP socket in one direction
     *     only, so EOF checks won't work.
     * @return string XML request including all headers and the DOCTYPE declaration.
     */
    function get_xml($mask_request = false)
    {
        if ($mask_request)
        {
            $this->_request = preg_replace('|^</request>$|', '</request>_', $this->_request);
        }
        $prefix = <<<EOF
<?xml version="1.0" encoding='UTF-8' ?>
<!DOCTYPE request SYSTEM "xml-communication-request.dtd">
<request index="{$this->_index_name}">
EOF;
        $postfix = <<<EOF
</request>
EOF;
        return "{$prefix}\n{$this->_request}\n{$postfix}\n";
    }

    /**
     * Add a query to the request.
     *
     * @param string $id The ID of the request.
     * @param string $query The query string (added as CDATA).
     * @param midcom_services_indexer_filter $filter An optional filter for the resultset.
     */
    function add_query($id, $query, $filter = null)
    {
        $id = $this->_encode_argument($id);
        $query = $this->_encode_cdata($query);
        $this->_request .= "<query id='{$id}'>\n";
        $this->_request .= "  <string>{$query}</string>\n";
        if (! is_null($filter))
        {
            $this->_request .= "  <filter>\n";
            switch ($filter->type)
            {
                case 'datefilter':
                    $field = $this->_encode_argument($filter->get_field());
                    $start = $filter->get_start();
                    $end = $filter->get_end();

                    $this->_request .= "    <datefilter field='{$field}'>\n";
                    if ($start > 0)
                    {
                        $start = strftime('%Y-%m-%dT%H:%M:%S', $start);
                        $this->_request .= "      <from>{$start}</from>\n";
                    }
                    if ($end > 0)
                    {
                        $end = strftime('%Y-%m-%dT%H:%M:%S', $end);
                        $this->_request .= "      <to>{$end}</to>\n";
                    }
                    $this->_request .= "    </datefilter>\n";
                    break;

                default:
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Indexer XMLCommClient: Encountered unknown filter type {$filter->type}.");
                    // This will exit.
            }
            $this->_request .= "  </filter>\n";
        }
        $this->_request .= "</query>\n";
    }

    /**
     * Index one or more documents.
     *
     * @param string $id The ID of the request.
     * @param mixed $document One or more documents to be indexed, so this is either a
     *           midcom_services_indexer_document or an Array of these objects.
     */
    function add_index ($id, $documents)
    {
        if (! is_array($documents))
        {
            $documents = Array($documents);
        }
        if (count($documents) == 0)
        {
            // Nothing to do.
            return;
        }

        $id = $this->_encode_argument($id);
        $this->_request .= "<index id='{$id}'>\n";

        foreach ($documents as $document)
        {
            $RI = $this->_encode_argument($document->RI);
            $this->_request .= "  <document id='{$RI}'>\n";

            foreach ($document->list_fields() as $field_name)
            {
                $field = $document->get_field_record($field_name);
                $field['name'] = $this->_encode_argument($field['name']);
                $field['content'] = $this->_encode_cdata($field['content']);
                $this->_request .= "    <{$field['type']} name='{$field['name']}'>{$field['content']}</{$field['type']}>\n";
            }

            $this->_request .= "  </document>\n";
        }

        $this->_request .= "</index>\n";
    }

    /**
     * Adds a delete request.
     *
     * @param string $id The ID of the request.
     * @param string $RI The document's resource identifier.
     */
    function add_delete($id, $RI)
    {
        $id = $this->_encode_argument($id);
        $RI = $this->_encode_argument($RI);
        $this->_request .= "<delete id='{$id}' documentid='{$RI}' />\n";
    }

    /**
     * Adds a drop index request.
     *
     * @param string $id The ID of the request.
     */
    function add_deleteall($id)
    {
        $id = $this->_encode_argument($id);
        $this->_request .= "<deleteall id='{$id}' />\n";
    }


    // HELPERS

    /**
     * Encodes an argument masking both single and double quotes.
     *
     * @param string $string String to modify.
     * @return string The modified string.
     */
    function _encode_argument($string)
    {
        return str_replace(Array('"', "'"), Array('&quot;', '&apos;'), $this->_clean_content_control_chars($string));
    }

    /**
     * Encodes a string using CDATA encoding.
     *
     * @param string $string The original string.
     * @return string The encoded string including the CDATA tags.
     */
    function _encode_cdata($string)
    {
        return '<![CDATA[' . str_replace(']]>', ']]&gt;', $this->_clean_content_control_chars($string)) . ']]>';
    }
    /**
     * Removes all control characters except LF from the content, as this might throw the indexer
     * off-track (valid XML and the like).
     *
     * WARNING: This function could break UTF-8 sequences right now, I'm on the lookout for a
     * better regex, as I do not know if the /u pattern modifier is enough. I just hope so.
     *
     * @param string $string The string to process.
     * @todo Finish Regex.
     */
    function _clean_content_control_chars($string)
    {
        return preg_replace('/[\x01-\x09\x0b-\x1f]/u', '', $string);
    }
}


require_once ('XML/Parser.php');
/**
 * This class provides an interface to the MRFC 14 XML driven indexer
 * backends.
 *
 * This class is responsible for parsing a Respons XML file. It uses the
 * PEAR XML_Parser base class.
 *
 * Note, that Expat does currently *not* support Input DTD validation. We trust
 * the source for now. Eventually, this might get rewritten top libXML.
 *
 * ...
 *
 * @package midcom.services
 * @see midcom_services_indexer
 * @see midcom_services_indexer_XMLComm_ResponseReader
 */
class midcom_services_indexer_XMLComm_ResponseReader extends XML_Parser
{
    /**
     * Current Data collected by the CDATA handler, will be processed by
     * the end-element handlers.
     *
     * @access private
     * @var string
     */
    var $_current_data = '';

    /**
     * An array of resultsets. A resultset is an array of documents.
     *
     * They are indexed by the request id.
     *
     * Only valid after parsing the Request.
     *
     * @var Array
     */
    var $resultsets = Array();

    /**
     * An array of warnings that have been found.
     *
     * They are indexed by the request id.
     *
     * Only valid after parsing the Request.
     *
     * @var Array
     */
    var $warnings = Array();

    /**
     * The current document, before being added to the resultset.
     *
     * @access private
     * @var midcom_services_indexer_document
     */
    var $_current_document = null;

    /**
     * The current request ID.
     *
     * @access private
     * @var string
     */
    var $_current_id = '';

    /**
     * The name of the current field, which is collected in _current_data at this time.
     *
     * @access private
     * @var string
     */
    var $_current_field = null;

    /**
     * The RI of the document currently being parsed.
     *
     * @access private
     * @var string
     */
    var $_current_document_id = '';


    /**
     * Initialize the XML Parser.
     */
    function __construct()
    {
        // Initialize as event parser, the parse method will change that.
        // See this Bug Report for details:
        // http://pear.php.net/bugs/bug.php?id=3555
        // parent::__construct(null, 'event', 'UTF-8');
        parent::__construct(null, 'event');
    }


    /**
     * The character data collector.
     *
     * This is an Expat Callback.
     *
     * @param object $parser The XML Parser resource
     * @param string $data The read data.
     * @access private
     */
    function cdataHandler($parser, $data)
    {
        $this->_current_data .= $data;
    }

    /**
     * Start a new resultset.
     *
     * This will store the resultset id, the rest of the work is done by the
     * document tag handlers.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @param Array $attribs The attributes to the element
     * @access private
     */
    function xmltag_resultset($parser, $name, $attribs)
    {
        $this->_current_id = $attribs['ID'];
        $this->resultsets[$this->_current_id] = Array();
    }

    /**
     * The resultset is complete.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @access private
     */
    function xmltag_resultset_($parser, $name)
    {
        $this->_current_id = '';
    }

    /**
     * Start a new document.
     *
     * This will prepare a new document and save the corresponding ID.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @param Array $attribs The attributes to the element
     * @access private
     */
    function xmltag_document($parser, $name, $attribs)
    {
        $this->_current_document = new midcom_services_indexer_document();
        $this->_current_document->score = (double) $attribs['SCORE'];
        $this->_current_document_id = $attribs['ID'];
    }

   /**
     * The document is complete.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @access private
     */
    function xmltag_document_($parser, $name)
    {
        $this->_current_document->dump("Document complete:");
        $this->resultsets[$this->_current_id][] = $this->_current_document;
        $this->_current_document = null;
        $this->_current_document_id = '';
    }

    /**
     * Start a new document field.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @param Array $attribs The attributes to the element
     * @access private
     */
    function xmltag_field($parser, $name, $attribs)
    {
        $this->_current_field = $attribs['NAME'];
        $this->_current_data = '';
    }

    /**
     * The field is complete.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @access private
     */
    function xmltag_field_($parser, $name)
    {
        $this->_current_document->add_result($this->_current_field, $this->_current_data);
        $this->_current_field = null;
    }

    /**
     * Start a new warning record.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @param Array $attribs The attributes to the element
     * @access private
     */
     function xmltag_warning($parser, $name, $attribs)
    {
        $this->_current_id = $attribs['ID'];
        $this->_current_data = '';
    }

    /**
     * The warning is complete.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @access private
     */
    function xmltag_warning_($parser, $name)
    {
        $this->warnings[$this->_current_id] = $this->_current_data;
    }

    /**
     * Start an error record.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @param Array $attribs The attributes to the element
     * @access private
     */
    function xmltag_error($parser, $name, $attribs)
    {
        $this->_current_id = $attribs['ID'];
        $this->_current_data = '';
    }

    /**
     * The error is complete.
     *
     * This will call generate_error with the error message, as this is a
     * rather critical error.
     *
     * @param object $parser The XML parser resource
     * @param string $name The name of the element being parsed
     * @access private
     */
    function xmltag_error_($parser, $name)
    {
            $msg = "The MidCOM Indexer failed critically:\n{$this->_current_data}";
            // $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $msg);
            // This will exit.
            debug_add($msg, MIDCOM_LOG_ERROR);
    }

    /**
     * This function will parse the given string. Any error from Expat
     * will trigger generate_error.
     *
     * @param string $string The XML data to be parsed.
     */
    function parse ($string)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        // Set the mode now, so that $this is a valid reference.
        $this->setMode('func');

        $result = parent::parseString($string, true);

        if ($result !== true)
        {
            $msg = "The XML Parser failed crticially:\n" . $result->toString();
            // $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $msg);
            // This will exit.
            debug_add($msg, MIDCOM_LOG_ERROR);
        }
        debug_pop();
    }
}





?>