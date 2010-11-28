<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: attachment.php 26670 2010-09-26 16:40:49Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is a class geared at indexing attachments. It requires you to "assign" the
 * attachment to a topic, which is used as TOPIC_URL for permission purposes. In addition
 * you may set another MidgardObject as source object, its GUID is stored in the
 * __SOURCE field of the index.
 *
 * The documents type is "midcom_attachment", though it is *not* derived from midcom
 * for several reasons directly. They should be compatible though, in terms of usage.
 *
 * <b>Example Usage:</b>
 *
 * <code>
 * $document = new midcom_services_indexer_document_attachment($attachment, $object);
 * $indexer->index($document);
 * </code>
 *
 * Where $attachment is the attachment to be indexed and $object is the object the object
 * is associated with. The corresponding topic will be detected using the object's GUID
 * through NAP. If this fails, you have to set the members $topic_guid, $topic_url and
 * $component manually.
 *
 * @todo More DBA stuff: use DBA classes, which allow you to implicitly load the parent
 *     object using get_parent.
 *
 * @package midcom.services
 * @see midcom_services_indexer
 * @see midcom_helper_metadata
 */
class midcom_services_indexer_document_attachment extends midcom_services_indexer_document
{
    var $_attachment;
    var $_source;

    /**
     * Create a new attachment document
     *
     * @param MidgardAttachment $attachment The Attachment to index.
     * @param MidgardObject $source The source objece to which the attachment is bound.
     */
    function __construct($attachment, $source)
    {
        //before doing anything else, verify that the attachment is readable, otherwise we might get stuck in endless loops later on
        $test = $attachment->open('r');
        if (!$test)
        {
            debug_add('Attachment ' . $attachment->guid . ' cannot be read, aborting. Last midgard error: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return false;
        }
        else
        {
            fclose($test);
        }

        parent::__construct();

        $this->_set_type('midcom_attachment');

        $this->_attachment = $attachment;
        $this->_source = $source;

        debug_print_r("Processing this attachment:", $attachment);

        $this->source = $this->_source->guid;
        $this->RI = $this->_attachment->guid;
        $this->document_url = "{$GLOBALS['midcom_config']['midcom_site_url']}midcom-serveattachmentguid-{$this->RI}/{$this->_attachment->name}";

        $this->_process_attachment();
        $this->_process_topic();
    }

    /**
     * Tries to determine the topic GUID and component using NAPs reverse-lookup capabilities.
     *
     * If this fails, you have to set the members $topic_guid, $topic_url and
     * $component manually.
     */
    function _process_topic()
    {
        $nav = new midcom_helper_nav();
        $object = $nav->resolve_guid($this->source);
        if (! $object)
        {
            debug_add("Failed to resolve the topic, skipping autodetection.");
            return;
        }
        if ($object[MIDCOM_NAV_TYPE] == 'leaf')
        {
            $object = $nav->get_node($object[MIDCOM_NAV_NODEID]);
        }
        $this->topic_guid = $object[MIDCOM_NAV_GUID];
        $this->topic_url = $object[MIDCOM_NAV_FULLURL];
        $this->component = $object[MIDCOM_NAV_COMPONENT];
    }


    function _process_attachment()
    {
        if (   !isset($this->_attachment->metadata)
            || !is_object($this->_attachment->metadata))
        {
            return;
        }
        $this->creator = new midcom_db_person($this->_attachment->metadata->creator);
        $this->created = $this->_attachment->metadata->created;
        $this->editor = $this->creator;
        $this->edited = $this->created;
        $this->author = $this->creator->name;
        $this->add_text('mimetype', $this->_attachment->mimetype);
        $this->add_text('filename', $this->_attachment->name);

        $mimetype = explode("/", $this->_attachment->mimetype);
        debug_print_r("Evaluating this Mime Type:", $mimetype);
        switch($mimetype[0])
        {
            case 'text':
                switch ($mimetype[1])
                {
                    case 'html':
                        $this->_process_mime_html();
                        break;

                    case 'richtext':
                        $this->_process_mime_richtext();
                        break;

                    default:
                        $this->_process_mime_plaintext();
                        break;
                }
                break;

            case 'application':
                switch ($mimetype[1])
                {
                    case 'xml':
                        $this->_process_mime_html();
                        break;

                    case 'xml-dtd':
                        $this->_process_mime_plaintext();
                        break;

                    case 'pdf':
                        $this->_process_mime_pdf();
                        break;

                    case 'msword':
                    case 'vnd.ms-word':
                        $this->_process_mime_word();
                        break;
                    case 'rtf':
                        $this->_process_mime_richtext();
                        break;

                    default:
                        $this->_process_mime_binary();
                        break;
                }
                break;

            default:
                $this->_process_mime_binary();
                break;
        }

        if (strlen(trim($this->_attachment->title)) > 0)
        {
            $this->title =  "{$this->_attachment->title} ({$this->_attachment->name})";
            $this->content .= "\n{$this->_attachment->title}\n{$this->_attachment->name}";
        }
        else
        {
            $this->title =  $this->_attachment->name;
            $this->content .= "\n{$this->_attachment->name}";
        }

        if (strlen($this->content) > 200)
        {
            $this->abstract = substr($this->content, 0, 200) . ' ...';
        }
        else
        {
            $this->abstract = $this->content;
        }
    }

    /**
     * Convert a Word attachment to plain text and index it.
     */
    function _process_mime_word()
    {
        if (is_null($GLOBALS['midcom_config']['utility_catdoc']))
        {
            debug_add('Could not find catdoc, indexing as binary.', MIDCOM_LOG_INFO);
            $this->_process_mime_binary();
            return;
        }

        debug_add("Converting Word-Attachment to plain text");
        $wordfile = $this->_write_attachment_tmpfile();
        $txtfile = "{$wordfile}.txt";
        $encoding = (strtoupper($this->_i18n->get_current_charset()) == 'UTF-8') ? 'utf-8' : '8859-1';

        $command = "{$GLOBALS['midcom_config']['utility_catdoc']} -d{$encoding} -a $wordfile > $txtfile";
        debug_add("Executing: {$command}");
        exec ($command, $result, $returncode);
        debug_print_r("Execution returned {$returncode}: ", $result);

        unlink ($wordfile);

        if (!file_exists($txtfile))
        {
            // We were unable to read the document into text
            $this->_process_mime_binary();
            return;
        }

        $handle = fopen($txtfile, "r");
        $this->content = $this->_get_attachment_content($handle);
        // Kill all ^L (FF) characters
        $this->content = str_replace("\x0C", '', $this->content);
        fclose($handle);

        unlink ($txtfile);
    }

    /**
     * Convert a PDF attachment to plain text and index it.
     */
    function _process_mime_pdf()
    {
        if (is_null($GLOBALS['midcom_config']['utility_pdftotext']))
        {
            debug_add('Could not find pdftotext, indexing as binary.', MIDCOM_LOG_INFO);
            $this->_process_mime_binary();
            return;
        }

        debug_add("Converting PDF-Attachment to plain text");
        $pdffile = $this->_write_attachment_tmpfile();
        $txtfile = "{$pdffile}.txt";
        $encoding = (strtoupper($this->_i18n->get_current_charset()) == 'UTF-8') ? 'UTF-8' : 'Latin1';

        $command = "{$GLOBALS['midcom_config']['utility_pdftotext']} -enc {$encoding} -nopgbrk -eol unix $pdffile $txtfile 2>&1";
        debug_add("Executing: {$command}");
        exec ($command, $result, $returncode);
        debug_print_r("Execution returned {$returncode}: ", $result);

        unlink ($pdffile);

        if (!file_exists($txtfile))
        {
            // We were unable to read the document into text
            $this->_process_mime_binary();
            return;
        }

        $handle = fopen($txtfile, 'r');
        $this->content = $this->_get_attachment_content($handle);
        fclose($handle);

        unlink ($txtfile);
    }

    /**
     * Convert an RTF attachment to plain text and index it.
     */
    function _process_mime_richtext()
    {
        if (is_null($GLOBALS['midcom_config']['utility_unrtf']))
        {
            debug_add('Could not find unrtf, indexing as binary.', MIDCOM_LOG_INFO);
            $this->_process_mime_binary();
            return;
        }

        debug_add("Converting RTF-Attachment to plain text");
        $rtffile = $this->_write_attachment_tmpfile();
        $txtfile = "{$rtffile}.txt";

        // Kill the first five lines, they are crap from the converter.
        $command = "{$GLOBALS['midcom_config']['utility_unrtf']} --nopict --text $rtffile | sed '1,5d' > $txtfile";
        debug_add("Executing: {$command}");
        exec ($command, $result, $returncode);
        debug_print_r("Execution returned {$returncode}: ", $result);

        unlink ($rtffile);

        if (!file_exists($txtfile))
        {
            // We were unable to read the document into text
            $this->_process_mime_binary();
            return;
        }

        $handle = fopen($txtfile, 'r');
        $this->content = $this->_i18n->convert_to_current_charset($this->_get_attachment_content($handle));
        fclose($handle);

        unlink ($txtfile);
    }

    /**
     * Simple plain-text driver, just copies the attachment.
     */
    function _process_mime_plaintext()
    {
        $this->content = $this->_i18n->convert_to_current_charset($this->_get_attachment_content());
    }

    /**
     * Processes HTML-style attachments (should therefore work with XML too),
     * strips tags and resolves entities.
     */
    function _process_mime_html()
    {
        $this->content = $this->_i18n->convert_to_current_charset($this->html2text($this->_get_attachment_content()));
    }

    /**
     * Any binary file will have its name in the abstract unless no title
     * is defined, in which case the documents title already contains the file's
     * name.
     */
    function _process_mime_binary()
    {
        if (strlen(trim($this->title)) > 0)
        {
            $this->abstract = $this->_attachment->name;
        }
    }

    /**
     * Returns the first four megabytes of the File referenced by $handle.
     * The limit is in place to
     * avoid clashes with the PHP Memory limit, it should be enough for most text
     * based attachments anyway.
     *
     * If you omit $handle, a handle to the documents' attachment is created. If no
     * handle is specified, it is automatically closed after reading the data, otherwise
     * you have to close it yourselves afterwards.
     *
     * @param resource $handle A valid file-handle to read from, or null to automatically create a
     *        handle to the current attachment.
     */
    function _get_attachment_content($handle = null)
    {
        // Read a max of 4 MB
        debug_add("Returning File content of handle {$handle}");
        $max = 4194304;
        $close = false;
        if (is_null($handle))
        {
            $handle = $this->_attachment->open('r');
            $close = true;
        }
        $content = fread($handle, $max);
        if ($close)
        {
            fclose($handle);
        }
        return $content;
    }

    /**
     * Creates a temporary copy of the attachment, the callee must delete it manually
     * after completing procesing.
     *
     * @return string The name of the temporary file.
     */
    function _write_attachment_tmpfile()
    {
        $tmpname = tempnam($GLOBALS['midcom_config']['midcom_tempdir'], 'midcom-indexer');
        debug_add("Creating an attachment copy as {$tmpname}");

        $in = $this->_attachment->open('r');
        $out = fopen($tmpname, 'w');
        while (! feof($in))
        {
            fwrite($out, fread($in, 131072));
        }
        fclose($out);
        fclose($in);
        return $tmpname;
    }
}
?>