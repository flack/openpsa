<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: api.php 25319 2010-03-18 12:44:12Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MetaWeblog API handler for the blog component
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_api extends midcom_baseclasses_components_handler
{
    /**
     * The product to operate on
     *
     * @var org_openpsa_products_product_dba
     * @access private
     */
    var $_product;

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    function _on_initialize()
    {
        if (!$this->_config->get('api_products_enable'))
        {
            return false;
        }

        //$_MIDCOM->auth->require_valid_user('basic');

        //Content-Type
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->no_cache();

        $this->_load_datamanager();
        $_MIDCOM->load_library('midcom.helper.xml');

        return true;
    }

    /**
     * Internal helper, loads the datamanager for the current product. Any error triggers a 500.
     *
     * @access private
     */
    function _load_datamanager()
    {
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb_product']);

        if (!$this->_datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance.");
            // This will exit.
        }
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    function _create_product($title, $productgroup)
    {
        $product = new org_openpsa_products_product_dba();
        $product->productGroup = $productgroup;
        $product->title = $title;

        if (! $product->create())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('We operated on this object:', $product);
            debug_pop();
            return null;
        }

        // Generate URL name
        if ($product->code == '')
        {
            $product->code = midcom_generate_urlname_from_string($product->title);
            $tries = 0;
            $maxtries = 999;
            while(   !$product->update()
                  && $tries < $maxtries)
            {
                $product->code = midcom_generate_urlname_from_string($product->title);
                if ($tries > 0)
                {
                    // Append an integer if products with same name exist
                    $product->code .= sprintf("-%03d", $tries);
                }
                $tries++;
            }
        }

        $product->parameter('midcom.helper.datamanager2', 'schema_name', $this->_config->get('api_products_schema'));

        return $product;
    }

    /*
    // products.list_product_groups
    function list_product_groups($message)
    {
        $args = $this->_params_to_args($message);

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_application::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();
        if ($args[0] == 0)
        {
            $product_group_id = 0;
            $product_group_label = 'root';
        }
        else
        {
            $product_group = org_openpsa_products_product_group_dba($args[0]);
            if (!$product_group)
            {
                return new XML_RPC_Response(0, midcom_application::get_error(), 'Product group ID not found.');
            }
            $product_group_id = $product_group->id;
            $product_group_label = $product_group->code;
        }

        $response = array();

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $qb->add_constraint('up', '=', $product_group_id);
        $qb->add_order('code');
        $qb->add_order('title');

        $product_groups = $qb->execute();
        foreach ($product_groups as $product_group)
        {

            $arg = $product_group->code ? $product_group->code : $product_group->guid;
            $link = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "{$arg}/";

            $response_array = array
            (
                'guid'        => new XML_RPC_Value($product_group->guid, 'string'),
                'code'        => new XML_RPC_Value($product_group->code, 'string'),
                'title'       => new XML_RPC_Value($product_group->title, 'string'),
                'link'        => new XML_RPC_Value($link, 'string'),
                'description' => new XML_RPC_Value($product_group->description, 'string'),
                'published'   => new XML_RPC_Value(gmdate("Ymd\TH:i:s\Z", $product_group->metadata->published), 'dateTime.iso8601'),
                'productGroup' => new XML_RPC_Value($product_group_label, 'string'),
            );

            $response[$category] = new XML_RPC_Value($response_array, 'struct');
        }

        return new XML_RPC_Response(new XML_RPC_Value($response, 'struct'));
    }

    // products.add_file
    function add_file($message)
    {
        $args = $this->_params_to_args($message);

        if ($args[0] != $this->_content_topic->guid)
        {
            return new XML_RPC_Response(0, midcom_application::get_error(), 'Blog ID does not match this folder.');
        }

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_application::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        if (count($args) < 3)
        {
            return new XML_RPC_Response(0, midcom_application::get_error(), 'Invalid file data.');
        }

        if (!$args[3]['name'])
        {
            return new XML_RPC_Response(0, midcom_application::get_error(), 'No filename given.');
        }

        // Clean up possible path information
        $attachment_name = basename($args[3]['name']);

        $attachment = $this->_content_topic->get_attachment($attachment_name);
        if (!$attachment)
        {
            // Create new attachment
            $attachment = $this->_content_topic->create_attachment($attachment_name, $args[3]['name'], $args[3]['type']);

            if (!$attachment)
            {
                return new XML_RPC_Response(0, midcom_application::get_error(), 'Failed to create attachment: ' . midgard_connection::get_error_string());
            }
        }

        if (!$attachment->copy_from_memory($args[3]['bits']))
        {
            return new XML_RPC_Response(0, midcom_application::get_error(), 'Failed to store contents to attachment: ' . midgard_connection::get_error_string());
        }

        $attachment_array = array
        (
            'url'  => new XML_RPC_Value("{$GLOBALS['midcom_config']['midcom_site_url']}midcom-serveattachmentguid-{$attachment->guid}/{$attachment->name}", 'string'),
            'guid' => new XML_RPC_Value($attachment->guid, 'string'),
        );
        return new XML_RPC_Response(new XML_RPC_Value($attachment_array, 'struct'));
    }
    */
    
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_options($handler_id, $args, &$data)
    {
        $_MIDCOM->skip_page_style = false;
        
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_options($handler_id, &$data)
    {
        midcom_show_style('api_product_options');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_product_get($handler_id, $args, &$data)
    {
        $this->_product = new org_openpsa_products_product_dba($args[0]);
        if (   !$this->_product
            || !$this->_product->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Product {$args[0]} could not be found.");
            // This will exit
        }

        if (!$this->_datamanager->autoset_storage($this->_product))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Product {$args[0]} could not be loaded with Datamanager.");
            // This will exit
        }

        $_MIDCOM->cache->content->content_type('text/xml');

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_product_get($handler_id, &$data)
    {
        $data['datamanager'] =& $this->_datamanager;
        $data['view_product'] = $this->_datamanager->get_content_html();
        $data['product'] =& $this->_product;
        midcom_show_style('api_product_get');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_product_list($handler_id, $args, &$data)
    {
        $qb = org_openpsa_products_product_dba::new_query_builder();

        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);

        if ($handler_id != 'api_product_list_all')
        {
            if ($args[0] == "0")
            {
                // List only toplevel
                $qb->add_constraint('productGroup', '=', 0);
            }
            else
            {
                $product_group = new org_openpsa_products_product_group_dba($args[0]);
                if (   !$product_group
                    || !$product_group->guid)
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Product group {$args[0]} could not be found.");
                    // This will exit
                }

                if ($handler_id == 'api_product_list_intree')
                {
                    $qb->add_constraint('productGroup', 'INTREE', $product_group->id);
                }
                else
                {
                    $qb->add_constraint('productGroup', '=', $product_group->id);
                }
            }
        }
        $_MIDCOM->cache->content->content_type('text/xml');

        $qb->add_order('code');
        $qb->add_order('title');

        $data['products'] = $qb->execute();

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_product_list($handler_id, &$data)
    {
        midcom_show_style('api_product_list_header');
        foreach ($data['products'] as $product)
        {
            $data['product'] =& $product;

            midcom_show_style('api_product_list_item');
        }
        midcom_show_style('api_product_list_footer');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_product_create($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user('basic');

         if (!isset($_POST['title']))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Missing argument: string title');
            // This will exit
        }

        if (!isset($_POST['productgroup']))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Missing argument: int productgroup');
            // This will exit
        }

        $this->_product = $this->_create_product($_POST['title'], (int) $_POST['productgroup']);
        if (   !$this->_product
            || !$this->_product->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to create product: ' . midcom_application::get_error_string());
            // This will exit
        }

        if (!$this->_datamanager->autoset_storage($this->_product))
        {
            $errstr = midcom_application::get_error_string();
            $this->_product->delete();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize DM2 for product: {$errstr}");
            // This will exit
        }

        foreach($this->_datamanager->types as $key => $type)
        {
            if (isset($_POST[$key]))
            {
                $this->_datamanager->types[$key]->value = $_POST[$key];
            }
        }

        if (!$this->_datamanager->save())
        {
            $errstr = midcom_application::get_error_string();
            $this->_product->delete();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create product: {$errstr}");
            // This will exit
        }

        $_MIDCOM->generate_error(MIDCOM_ERROK, 'Product created: ' . midcom_application::get_error_string());
        // This will exit
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_product_update($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user('basic');

        $this->_product = new org_openpsa_products_product_dba($args[0]);
        if (   !$this->_product
            || !$this->_product->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Product {$args[0]} could not be found.");
            // This will exit
        }

        if (!$this->_datamanager->autoset_storage($this->_product))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to initialize DM2 for product: ' . midcom_application::get_error_string());
            // This will exit
        }

        foreach($this->_datamanager->types as $key => $type)
        {
            if (isset($_POST[$key]))
            {
                $this->_datamanager->types[$key]->value = $_POST[$key];
            }
        }

        if (!$this->_datamanager->save())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to update product: ' . midcom_application::get_error_string());
            // This will exit
        }

        $_MIDCOM->generate_error(MIDCOM_ERROK, 'Product updated: ' . midcom_application::get_error_string());
        // This will exit
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_product_delete($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user('basic');

        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to delete product: POST request expected.');
            // This will exit
        }

        $this->_product = new org_openpsa_products_product_dba($args[0]);
        if (   !$this->_product
            || !$this->_product->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Product {$args[0]} could not be found.");
            // This will exit
        }

        if (!$this->_product->delete())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to delete product: ' . midcom_application::get_error_string());
            // This will exit
        }

        // Update the index
        $indexer = $_MIDCOM->get_service('indexer');
        $indexer->delete($this->_product->guid);

        $_MIDCOM->generate_error(MIDCOM_ERROK, 'Product deleted: ' . midcom_application::get_error_string());
        // This will exit
    }

}
