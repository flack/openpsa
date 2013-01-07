<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
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
     */
    private $_product;

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        if (!$this->_config->get('api_products_enable'))
        {
            _midcom_stop_request();
        }

        //Content-Type
        midcom::get()->skip_page_style = true;
        midcom::get('cache')->content->no_cache();

        $this->_load_datamanager();
    }

    /**
     * Internal helper, loads the datamanager for the current product. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb_product']);
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    private function _create_product($title, $productgroup)
    {
        $product = new org_openpsa_products_product_dba();
        $product->productGroup = $productgroup;
        $product->title = $title;

        if (! $product->create())
        {
            debug_print_r('We operated on this object:', $product);
            return null;
        }

        // Generate URL name
        if ($product->code == '')
        {
            $generator = midcom::get('serviceloader')->load('midcom_core_service_urlgenerator');
            $product->code = $generator->from_string($product->title);
            $tries = 0;
            $maxtries = 999;
            while(   !$product->update()
                  && $tries < $maxtries)
            {
                $product->code = $generator->from_string($product->title);
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

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_options($handler_id, array $args, array &$data)
    {
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_options($handler_id, array &$data)
    {
        midcom_show_style('api_product_options');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_product_get($handler_id, array $args, array &$data)
    {
        $this->_product = new org_openpsa_products_product_dba($args[0]);

        if (!$this->_datamanager->autoset_storage($this->_product))
        {
            throw new midcom_error("Product {$args[0]} could not be loaded with Datamanager.");
        }

        midcom::get('cache')->content->content_type('text/xml');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_product_get($handler_id, array &$data)
    {
        $data['datamanager'] =& $this->_datamanager;
        $data['view_product'] = $this->_datamanager->get_content_html();
        $data['product'] =& $this->_product;
        midcom_show_style('api_product_get');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_product_list($handler_id, array $args, array &$data)
    {
        $qb = org_openpsa_products_product_dba::new_query_builder();

        midcom::get()->disable_limits();

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
        midcom::get('cache')->content->content_type('text/xml');

        $qb->add_order('code');
        $qb->add_order('title');

        $data['products'] = $qb->execute();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_product_list($handler_id, array &$data)
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
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_product_create($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user('basic');

         if (!isset($_POST['title']))
        {
            throw new midcom_error('Missing argument: string title');
        }

        if (!isset($_POST['productgroup']))
        {
            throw new midcom_error('Missing argument: int productgroup');
        }

        $this->_product = $this->_create_product($_POST['title'], (int) $_POST['productgroup']);
        if (   !$this->_product
            || !$this->_product->guid)
        {
            throw new midcom_error('Failed to create product: ' . midcom_connection::get_error_string());
        }

        if (!$this->_datamanager->autoset_storage($this->_product))
        {
            $errstr = midcom_connection::get_error_string();
            $this->_product->delete();
            throw new midcom_error("Failed to initialize DM2 for product: {$errstr}");
        }

        foreach ($this->_datamanager->types as $key => $type)
        {
            if (isset($_POST[$key]))
            {
                $this->_datamanager->types[$key]->value = $_POST[$key];
            }
        }

        if (!$this->_datamanager->save())
        {
            $errstr = midcom_connection::get_error_string();
            $this->_product->delete();
            throw new midcom_error("Failed to create product: {$errstr}");
        }

        $this->_send_reply('Product created');
    }

    private function _send_reply($message)
    {
        $midcom = midcom::get();
        $midcom->header('Content-Type: text/html');
        $midcom->header('HTTP/1.0 200 OK');
        echo $message . ': ' . midcom_connection::get_error_string();

        midcom::get('cache')->content->no_cache();
        $midcom->finish();
        _midcom_stop_request();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_product_update($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user('basic');

        $this->_product = new org_openpsa_products_product_dba($args[0]);

        if (!$this->_datamanager->autoset_storage($this->_product))
        {
            throw new midcom_error( 'Failed to initialize DM2 for product: ' . midcom_connection::get_error_string());
        }

        foreach ($this->_datamanager->types as $key => $type)
        {
            if (isset($_POST[$key]))
            {
                $this->_datamanager->types[$key]->value = $_POST[$key];
            }
        }

        if (!$this->_datamanager->save())
        {
            throw new midcom_error('Failed to update product: ' . midcom_connection::get_error_string());
        }

        $this->_send_reply('Product updated');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_product_delete($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user('basic');

        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            throw new midcom_error('Failed to delete product: POST request expected.');
        }

        $this->_product = new org_openpsa_products_product_dba($args[0]);

        if (!$this->_product->delete())
        {
            throw new midcom_error('Failed to delete product: ' . midcom_connection::get_error_string());
        }

        // Update the index
        $indexer = midcom::get('indexer');
        $indexer->delete($this->_product->guid);

        $this->_send_reply('Product deleted');
    }
}
?>