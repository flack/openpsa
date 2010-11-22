<?php

/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: xmlshell.php 23305 2009-09-07 14:14:16Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** XML Communitcation driver library */ 
require_once 'XMLCommClient.php';

/**
 * XMLComm implementation using a shell interface.
 * 
 * ...
 * 
 * @abstract Abstract indexer backend class
 * @package midcom.services
 * @see midcom_services_indexer
 * @see midcom_services_indexer_backend
 * @see midcom_services_indexer_XMLComm_RequestWriter
 * @see midcom_services_indexer_XMLComm_ResponseParser
 * 
 * @todo Check if there is a better way to handle the exec loop, which looks rather PHP-workaroundy right now.
 */

class midcom_services_indexer_backend_xmlshell implements midcom_services_indexer_backend
{
    /**
     * The request to execute.
     * 
     * @access private
     * @var midcom_services_indexer_XMLComm_RequestWriter
     */
    var $_request = null;
    
    /**
     * The response received.
     * 
     * @access private
     * @var midcom_services_indexer_XMLComm_ResponseParser
     */
    var $_response = null;
    
    
    /**
     * Adds a document to the index.
     * 
     * Any warning will be treated as error.
     * 
     * Note, that $document may also be an array of documents without further
     * changes to this backend.
     * 
     * @param Array $documents A list of midcom_services_indexer_document objects.
     * @return boolean Indicating success.
     */   
    function index ($documents)
    {
        if (!is_array($documents))
        {
            $documents = array( $documents );
        }

        $added = false;
        foreach ($documents as $document )
        {
            if (!$document->actually_index)
            {
                continue;
            }
            $added = true;
            break;
        }
        if (!$added)
        {
            return true;
        }
        
        $this->_request = new midcom_services_indexer_XMLComm_RequestWriter();
        $this->_request->add_index(0, $documents);
        $this->_exec();
        return (! array_key_exists(0, $this->_response->warnings));
    }
    
    /**
     * Removes the document with the given resource identifier from the index.
     * 
     * @param string $RI The resource identifier of the document that should be deleted.
     * @return boolean Indicating success.
     */
    function delete ($RI)
    {
        $this->_request = new midcom_services_indexer_XMLComm_RequestWriter();
        $this->_request->add_delete(0, $RI);
        $this->_exec();
        return (! array_key_exists(0, $this->_response->warnings));
    }
    
    /**
     * Clear the index completely.
     * 
     * This will drop the current index.
     * 
     * @return boolean Indicating success.
     */
    function delete_all()
    {
        $this->_request = new midcom_services_indexer_XMLComm_RequestWriter();
        $this->_request->add_deleteall(0);
        $this->_exec();
        return (! array_key_exists(0, $this->_response->warnings));
    }
    
    /**
     * Query the index and, if set, restrict the query by a given filter.
     * 
     * ...
     * 
     * @param string $query The query, which must suite the backends query syntax.
     * @param midcom_services_indexer_filter $filter An optional filter used to restrict the query. This may be null indicating no filter.
     * @return Array An array of documents matching the query, or false on a failure.
     */
    function query ($query, $filter)
    {
        $this->_request = new midcom_services_indexer_XMLComm_RequestWriter();
        $this->_request->add_query(0, $query, $filter);
        $this->_exec();
        if (array_key_exists(0, $this->_response->warnings))
        {
            return false;
        }
        return $this->_response->resultsets[0];
    }
    
    
    /**
     * This private helper will launch the Indexer interface and run the
     * request in the $xml parameter, which must be a request writer object.
     * It will return the parsed request.
     * 
     * The function tries to use timeouts to guard against deadlocks when the
     * pipes' output buffers are full. 
     * 
     * Note, that both classes call generate_error on critical errors.
     * 
     * @todo protect against deadlocks when stderr gets swamped while stdin is still being written. 
     *     This might be difficult actually, as I have no idea if you can set the timeout of an output
     *     stream in PHP safely. For now it seems to work, even for larger requests. 
     */
    function _exec ()
    {
        debug_push('midcom_services_indexer_backend_xmlshell');
        $descriptors = Array
        (
            0 => Array('pipe', 'r'), // stdin, the child will read from it
            1 => Array('pipe', 'w'), // stdout, the child will write to it.
            2 => Array('pipe', 'w'), // stderr, the child will write to it.
        );
        
        chdir($GLOBALS['midcom_config']['indexer_xmlshell_working_directory']);
        $process = proc_open($GLOBALS['midcom_config']['indexer_xmlshell_executable'], $descriptors, $pipes);
        if (! is_resource($process))
        {
            debug_print_r('Tried to open these pipes:', $descriptors);
            debug_print_r('Global config was:', $GLOBALS['midcom_config']);
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to execute the indexer process.');
            // This will exit.
        }
        
        // This function works without any multibyte awareness
        $old_encoding = mb_internal_encoding();
        mb_internal_encoding('ISO-8859-1');
        debug_add("Old Encoding: {$old_encoding}, Current Encoding: " . mb_internal_encoding());
               
        // Write the request to the client
        // fwrite($pipes[0], $xml);
        // fclose($pipes[0]);
       
        // Read both pipes simultaneously to guard against full pipe deadlocks 
        $response = '';
        $stderr = '';
        $xml = $this->_request->get_xml();
        $sent_offset = 0;
        $data_length = strlen($xml);
        $stdin_open = true;
        
        debug_print_r('Will send this request to the xmlshell extension:', $xml);
        
        while (   $stdin_open 
               || ! feof($pipes[1]) 
               || ! feof($pipes[2]))
        {
            if ($sent_offset < $data_length)
            {
                debug_add('We still need to write ' . ($data_length - $sent_offset) . ' Bytes.');
                socket_set_timeout($pipes[0], 0, 100);
                $sent = fwrite($pipes[0], $xml);
                $status = socket_get_status($pipes[0]);
                debug_add("We sent {$sent} Bytes successfully");
                $xml = substr($xml, $sent);
                $sent_offset += $sent; 
            }
            
            if ($stdin_open && $sent_offset >= $data_length)
            {
                debug_add("Closing STDIN");
                fclose($pipes[0]);
                $stdin_open = false;
            }
            
            $bytes_read = 0;
            if (! feof($pipes[1]))
            {
                socket_set_blocking($pipes[1], false);
                $data = fread($pipes[1], 16384);
                $status = socket_get_status($pipes[1]);
                if ($status['timed_out'])
                {
                    debug_add('Timed out when reading from stdout, stream state was:', $status);
                }
                $bytes_read += strlen($data);
                debug_add('Read ' . strlen($data) . ' Bytes from stdout.');
                $response .= $data;
            }
            if (! feof($pipes[2]))
            {
                socket_set_blocking($pipes[2], false);
                $data = fread($pipes[2], 16384);
                $status = socket_get_status($pipes[2]);
                if ($status['timed_out'])
                {
                    debug_add('Timed out when reading from stderr, stream state was:', $status);
                }
                $bytes_read += strlen($data);
                debug_add('Read ' . strlen($data) . ' Bytes from stderr.');
                $stderr .= $data;
            }
            
            if ($bytes_read == 0)
            {
                debug_add('We sleep 50 ms, as there was no data read during the last pass.');
                usleep(50000);
            }
        }
        
        // Close the output pipes too.
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        // Restore mb_string settings
        mb_internal_encoding($old_encoding);
        
        if (strlen($stderr) > 0)
        {
            debug_print_r('The external process returned these error messages:', $stderr, MIDCOM_LOG_ERROR); 
        }
        else
        {
            debug_add('STDERR was empty, so we are fine.');
        }
        debug_print_r('We got this response:', $response);
        
        $return = proc_close($process);
        if ($return != 0)
        {
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 
                "The xmlshell indexer executable returned {$return}, this is critical. Check the Debug Log."); 
            // This will exit.
        }
        
        $this->_response = new midcom_services_indexer_XMLComm_ResponseReader();
        $this->_response->parse($response);
        foreach ($this->_response->warnings as $id => $warning)
        {
            debug_add("Failed to execute Request {$id}: {$warning}", MIDCOM_LOG_WARN); 
        }
        debug_pop();
    }
}

?>