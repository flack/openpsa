<?php
/**
 * Class for sending Ajax replies to clients. Preferably used together with the Ajax JavaScript utilities
 * provided in /midcom-static/org.openpsa.helpers/ajaxutils.js
 *
 * @package org.openpsa.helpers
 * @author Eero af Heurlin, http://www.iki.fi/rambo
 * @version $Id: ajax.php 26504 2010-07-06 12:19:31Z rambo $
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers_ajax extends midcom_baseclasses_components_purecode
{
    /**
     * Character encoding to use for the XML messages
     * @todo determine on the fly
     * @var String Character encoding
     */
    var $encoding = 'UTF-8';

    /**
     * Initialize the Ajax messages class
     */
    function __construct()
    {
        parent::__construct();
        $this->_component='org.openpsa.helpers';
    }

    /**
     * Create and send a simple Ajax reply compatible with our ajaxutils.js
     * This will exit the script.
     * @param boolean $result Whether the Ajax request succeeded or failed
     * @param string $errstr The status message to send to the user
     * @param boolean $override
     */
    function simpleReply($result, $errstr = '', $override = false)
    {
        $this->start();

        if (!$errstr)
        {
            $errstr = 'Unknown status';
        }

        debug_add("addTag('status', {$errstr})");
        $this->addTag('status', $errstr);
        debug_add("addTag('result', (int){$result})");
        $this->addTag('result', (int)$result);
        if ($override !== false)
        {
            debug_add("addTag('valueoverride', {$override})");
            $this->addTag('valueoverride', $override);
        }
        $this->end();
    }

    /**
     * Add XML elements to responses
     * @param string $tagname Name of the XML element
     * @param string $value Value of the XML element
     */
    function addTag($tagname, $value)
    {
        echo '  <' . $tagname . '>' . $value . '</' . $tagname . ">\n";
    }

    /**
     * Prepare MidCOM cache & style engine for plain XML output
     * @access private
     */
    function _prepare()
    {
        $_MIDCOM->cache->content->content_type('text/xml');
        $_MIDCOM->header('Content-type: text/xml; charset=' . $this->encoding);
    }

    /**
     * Start XML output response
     * @access private
     */
    function _xmlheader()
    {
        echo '<?xml version="1.0" encoding="' . $this->encoding . '" standalone="yes"?>' . "\n";
        echo "<response>\n";
    }

    /**
     * End XML output response
     * @access private
     */
    function _xmlfooter()
    {
        echo "</response>\n";
    }

    /**
     * Finalize the response, this will exit the script.
     * @access private
     */
    function _close()
    {
        $_MIDCOM->finish();
        _midcom_stop_request();
    }

    /**
     * Shortcut for getting XML output started
     */
    public function start()
    {
        $this->_prepare();
        $this->_xmlheader();
    }

    /**
     * Shortcut for finalizing the XML output, this will exit the script.
     */
    public function end()
    {
        $this->_xmlfooter();
        $this->_close();
    }
}

?>