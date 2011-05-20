<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Compatibility wrapper that provides the API found in Ragnaroek's $_MIDCOM superglobal
 *
 * @package midcom
 * @property midcom_helper_serviceloader $serviceloader
 * @property midcom_services_i18n $i18n
 * @property midcom_helper__componentloader $componentloader
 * @property midcom_services_dbclassloader $dbclassloader
 * @property midcom_helper__dbfactory $dbfactory
 * @property midcom_helper_head $head
 * @property midcom_helper__styleloader $style
 * @property midcom_services_permalinks $permalinks
 * @property midcom_services_tmp $tmp
 * @property midcom_services_toolbars $toolbars
 * @property midcom_services_uimessages $uimessages
 * @property midcom_services_metadata $metadata
 * @property midcom_services_rcs $rcs
 * @property midcom_services__sessioning $session
 * @property midcom_services_indexer $indexer
 */
class midcom_compat_superglobal
{
    /**
     * Redirect calls to the midcom_application singleton
     */
    public function __call($method, $arguments)
    {
        switch (count($arguments))
        {
            case 0:
                return midcom::get()->{$method}();
                break;
            case 1:
                return midcom::get()->{$method}($arguments[0]);
                break;
            case 2:
                return midcom::get()->{$method}($arguments[0], $arguments[1]);
                break;
            case 3:
                return midcom::get()->{$method}($arguments[0], $arguments[1], $arguments[3]);
                break;
            default:
                //There is no function in midcom_application with more than 3 parameters, but you never know...
                return call_user_func_array(array(midcom::get(), $method), $arguments);
                break;
        }
    }

    /**
     * Magic getter for service loading
     */
    public function __get($key)
    {
        return $this->get_service($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value)
    {
        return midcom::get()->$key = $value;
    }

    /**
     * Return a reference to a given service.
     *
     * Returns the MidCOM Object Service indicated by $name. If the service cannot be
     * found, an HTTP 500 is triggered.
     *
     * See the documentation of the various services for further details.
     *
     * @param string $name        The name of the service being requested.
     * @return mixed    A reference(!) to the service requested.
     */
    function get_service($name)
    {
        return midcom::get($name);
    }

    /**
     * Return the reference to the component loader.
     *
     * @return midcom_helper__componentloader The reference of the component loader in use.
     */
    public function get_component_loader()
    {
        return midcom::get('componentloader');
    }

    /**
     * Access the MidCOM component context
     *
     * @param int param1    Either the ID of the context (two parameters) or the key requested (one parameter).
     * @param int param2    Either the key requested (two parameters) or null (one parameter, the default).
     * @return mixed    The content of the key being requested.
     */
    public function get_context_data($param1, $param2 = null)
    {
        if (is_null($param2))
        {
            $context = midcom_core_context::get();
            $key = $param1;
        }
        else
        {
            $context = midcom_core_context::get($param1);
            $key = $param2;
        }

        if (!$context)
        {
            return false;
        }

        return $context->get_key($key);
    }

    /**
     * Update the component context
     *
     * @param mixed $value    The value to be stored
     * @param int $param1    See get_context_data()
     * @param int $param2    See get_context_data()
     * @see get_context_data()
     */
    function _set_context_data($value, $param1, $param2 = null)
    {
        if (is_null($param2))
        {
            $context = midcom_core_context::get();
            $key = $param1;
        }
        else
        {
            $context = midcom_core_context::get($param1);
            $key = $param2;
        }

        if (!$context)
        {
            return false;
        }

        $context->set_key($key, $value);
    }

    /**
     * Store arbitrary, component-specific information in the component context
     *
     * @param mixed $key        The key associated to the value.
     * @param mixed $value    The value to store. (This is stored by-reference!)
     * @param int $contextid    The context to associated this data with (defaults to the current context)
     */
    function set_custom_context_data ($key, &$value, $contextid = null)
    {
        $context = midcom_core_context::get($contextid);
        if (!$context)
        {
            return;
        }
        $component = $this->get_context_data(MIDCOM_CONTEXT_COMPONENT);

        $context->set_custom_key($component, $key, $value);
    }

    /**
     * Retrieve arbitrary, component-specific information in the component context
     *
     * @param int $param1    See get_context_data()
     * @param int $param2    See get_context_data()
     * @return mixed        The requested value, which is returned by Reference!
     */
    function & get_custom_context_data($param1, $param2 = null)
    {
        if (is_null($param2))
        {
            $context = midcom_core_context::get();
            $key = $param1;
        }
        else
        {
            $context = midcom_core_context::get($param1);
            $key = $param2;
        }

        if (!$context)
        {
            $result = false;
            return $result;
        }

        $component = $this->get_context_data(MIDCOM_CONTEXT_COMPONENT);

        return $context->get_custom_key($component, $key);
    }

    /**
     * Returns the ID of the currently active context. This is false if there is no
     * context running.
     *
     * @return int The context ID.
     */
    function get_current_context ()
    {
        return midcom_core_context::get()->id;
    }

    /**
     * Returns the complete context data array
     *
     * @return array The data of all contexts
     */
    function get_all_contexts ()
    {
        return midcom_core_context::get_all();
    }

    /**
     * Sets a new context, doing some minor sanity checking.
     *
     * @return boolean    Indicating if the switch was successful.
     */
    public function _set_current_context($id)
    {
        return midcom_core_context::set_current($id);
    }

    /**
     * Sets the page title for the current context.
     *
     * This can be retrieved by accessing the component context key
     * MIDCOM_CONTEXT_PAGETITLE.
     *
     * @param string $string    The title to set.
     */
    public function set_pagetitle($string)
    {
        midcom::get('head')->set_pagetitle($string);
    }


    /* *************************************************************************
     * Generic Helper Functions not directly related with MidCOM:
     *
     * generate_error     - Generate HTTP Error
     * serve_snippet      - Serves snippet including all necessary headers
     * serve_attachment   - Serves attachment including all necessary headers
     * _l10n_edit_wrapper - Invokes the l10n string editor
     * add_jsfile         - Add a JavaScript URL to the load queue
     * add_jscript        - Add JavaScript code to the load queue
     * add_jsonload       - Add a JavaScript method call to the bodies onload tag
     * add_object_head    - Add object links to the page's head.
     * add_style_head     - Add style  tags to the page's head.
     * add_meta_head      - Add metatags to the page's head.
     * print_head_elements     - Print the queued-up JavaScript code (for inclusion in the HEAD section)
     * print jsonload     - Prints the onload command if required (for inclusion as a BODY attribute)
     * check_memberships  - Checks whether the user is in a given group
     * relocate           - executes a HTTP relocation to the given URL
     * _showdebuglog      - internal helper for the debuglog URL method.
     */

    /**
     * Generate an error page.
     *
     * This function is a small helper, that will display a simple HTML Page reporting
     * the error described by $httpcode and $message. The $httpcode is also used to
     * send an appropriate HTTP Response.
     *
     * For a list of the allowed HTTP codes see the MIDCOM_ERR... constants
     *
     * <b>Note:</b> This function will call _midcom_stop_request() after it is finished.
     *
     * @see midcom_exception_handler::show()
     * @param int $httpcode        The error code to send.
     * @param string $message    The message to print.
     */
    public function generate_error($httpcode, $message)
    {
        static $error_shown = false;
        if ($error_shown)
        {
            return;
        }

        $error_shown = true;
        $error_handler = new midcom_exception_handler();
        $error_handler->show($httpcode, $message);
        // This will exit
    }

    /**
     * Register JavaScript File for referring in the page.
     *
     * @param string $url    The URL to the file to-be referenced.
     * @param boolean $prepend Whether to add the JS include to beginning of includes
     */
    public function add_jsfile($url, $prepend = false)
    {
        midcom::get('head')->add_jsfile($url, $prepend);
    }

    /**
     * Register JavaScript Code for output directly in the page.
     *
     * @param string $script    The code to be included directly in the page.
     */
    public function add_jscript($script, $defer = '', $prepend = false)
    {
        midcom::get('head')->add_jscript($script, $defer, $prepend);
    }

    /**
     * Register JavaScript snippets to jQuery states.
     *
     * @param string $script    The code to be included in the state.
     * @param string $state    The state where to include the code to. Defaults to document.ready
     */
    public function add_jquery_state_script($script, $state = 'document.ready')
    {
        midcom::get('head')->add_jquery_state_script($script, $state);
    }

    /**
     * Register some object tags to be added to the head element.
     *
     * @param  string $script    The input between the <object></object> tags.
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     */
    public function add_object_head ($script, $attributes = null)
    {
        midcom::get('head')->add_object_head ($script, $attributes);
    }

    /**
     *  Register a metatag  to be added to the head element.
     *
     *  @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     */
    public function add_meta_head($attributes = null)
    {
        midcom::get('head')->add_meta_head($attributes);
    }

    /**
     * Register a styleblock / style link  to be added to the head element.
     *
     * @param  string $script    The input between the <style></style> tags.
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     */
    public function add_style_head($script, $attributes = null)
    {
        midcom::get('head')->add_style_head($script, $attributes);
    }

    /**
     * Register a linkelement to be placed in the pagehead.
     *
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     */
    public function add_link_head( $attributes = null )
    {
        return midcom::get('head')->add_link_head($attributes);
    }

    /**
     * Convenience shortcut for adding CSS files
     *
     * @param string $url The stylesheet URL
     * @param string $media The media type(s) for the stylesheet, if any
     */
    public function add_stylesheet($url, $media = false)
    {
        midcom::get('head')->add_stylesheet($url, $media);
    }

    /**
     * Register a JavaScript method for the body onload event
     *
     * @param string $method    The name of the method to be called on page startup, including parameters but excluding the ';'.
     */
    public function add_jsonload($method)
    {
        midcom::get('head')->add_jsonload($method);
    }

    /**
     * Echo the registered javascript code.
     */
    public function print_jsonload()
    {
        midcom::get('head')->print_jsonload();
    }

    /**
     * Echo the _head elements added.
     */
    public function print_head_elements()
    {
        midcom::get('head')->print_head_elements();
    }

    /**
     * Init jQuery
     */
    public function enable_jquery($version = null)
    {
        midcom::get('head')->enable_jquery($version);
    }

    /**
     * Echo the jquery statuses
     */
    public function print_jquery_statuses()
    {
        midcom::get('head')->print_jquery_statuses();
    }
}
?>