<?php
/**
 * Created on 2007-08-15
 * @author Henri Bergius, Niels Breet
 * @package org.openpsa.products
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * The midcom_baseclasses_components_handler class defines a bunch of helper vars
 *
 * @see midcom_baseclasses_components_handler
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_downloads extends midcom_baseclasses_components_handler
{
    function _list_products($limit = 5, $product_group = '', $orderby = 'delivery')
    {
        $product_qb = new org_openpsa_qbpager('org_openpsa_products_product_dba', 'downloaded_products');
        $this->_request_data['product_qb'] =& $product_qb;
        $product_qb->results_per_page = $limit;
        $product_qb->set_limit($limit);

        $product_qb->add_constraint('delivery', '>', 0);

        if ($product_group != '')
        {
            $group_qb = org_openpsa_products_product_group_dba::new_query_builder();
            $group_qb->add_constraint('code', '=', $product_group);
            $group_qb->set_limit(1);
            $groups = $group_qb->execute();

            if (count($groups) == 0)
            {
                return false;
                // No matching group
            }
            else
            {
                $categories_mc = org_openpsa_products_product_group_dba::new_collector('up', $groups[0]->id);
                $categories_mc->add_value_property('id');
                $categories_mc->execute();
                $categories = $categories_mc->list_keys();
                $categories_in = array();
                if (count($categories) == 0)
                {
                    /* No matching categories belonging to this group
                     * So we can search for the application using only
                     * this group id
                     */
                    $product_qb->add_constraint('productGroup', 'INTREE', $groups[0]->id);
                }
                else
                {
                    foreach ($categories as $guid => $empty)
                    {
                        $categories_in[] = $categories_mc->get_subkey($guid, 'id');
                    }
                    $product_qb->add_constraint('productGroup', 'IN', $categories_in);
                }

            }

        }

        if ($this->_config->get('enable_scheduling'))
        {
            $product_qb->add_constraint('start', '<=', time());
            $product_qb->begin_group('OR');
                /*
                 * List products that either have no defined end-of-market dates
                 * or are still in market
                 */
                $product_qb->add_constraint('end', '=', 0);
                $product_qb->add_constraint('end', '>=', time());
            $product_qb->end_group();
        }

        $product_qb->add_order($orderby, 'DESC');

        $this->_request_data['products'] = $product_qb->execute();
        $this->_request_data['product_group'] = $product_group;
    }

    /**
     * The handler for the group_list article.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_downloads($handler_id, $args, &$data)
    {
        if ($handler_id == 'downloads_products_intree')
        {
            $product_group = $args[0];
            $show_products = (int) $args[1];
        }
        else
        {
            $show_products = (int) $args[0];
            $product_group = '';
        }
        $this->_list_products($show_products, $product_group);

        // Prepare datamanager
        $data['datamanager_product'] = new midcom_helper_datamanager2_datamanager($data['schemadb_product']);

        return true;
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_downloads($handler_id, &$data)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        if (count($data['products']) > 0)
        {
            midcom_show_style('downloads_products_header');

            foreach ($data['products'] as $product)
            {
                $data['product'] = $product;
                if (! $data['datamanager_product']->autoset_storage($product))
                {
                    debug_add("The datamanager for product #{$product->id} could not be initialized, skipping it.");
                    debug_print_r('Object was:', $product);
                    continue;
                }
                $data['view_product'] = $data['datamanager_product']->get_content_html();

                if ($product->code)
                {
                    if (   $handler_id == 'downloads_products_intree'
                        || $handler_id == 'downloads_by_score_products_intree')
                    {
                        $data['view_product_url'] = "{$prefix}product/" . $data['product_group'] . "/{$product->code}/";
                    }
                    else
                    {
                        $data['view_product_url'] = "{$prefix}product/{$product->code}/";
                    }
                }
                else
                {
                    $data['view_product_url'] = "{$prefix}product/{$product->guid}/";
                }

                midcom_show_style('downloads_products_item');
            }

            midcom_show_style('downloads_products_footer');
        }

        midcom_show_style('group_footer');
    }

    function _handler_downloads_by_score($handler_id, $args, &$data)
    {
        if ($handler_id == 'downloads_by_score_products_intree')
        {
            $product_group = $args[0];
            $show_products = (int) $args[1];
        }
        else
        {
            $product_group = '';
            $show_products = (int) $args[0];
        }
        $this->_list_products($show_products, $product_group, 'metadata.score');

        // Prepare datamanager
        $data['datamanager_product'] = new midcom_helper_datamanager2_datamanager($data['schemadb_product']);

        return true;
    }

    function _show_downloads_by_score($handler_id, &$data)
    {
        $this->_show_downloads($handler_id, $data);
    }

    /**
     * The handler for the group_list article.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_feed($handler_id, $args, &$data)
    {
        $_MIDCOM->cache->content->content_type("text/xml; charset=UTF-8");
        $_MIDCOM->header("Content-type: text/xml; charset=UTF-8");
        $_MIDCOM->skip_page_style = true;

        $this->_list_products($this->_config->get('show_items_in_feed'));

        // Prepare datamanager
        $data['datamanager_product'] = new midcom_helper_datamanager2_datamanager($data['schemadb_product']);

        return true;
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_feed($handler_id, &$data)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        $_MIDCOM->load_library('de.bitfolge.feedcreator'); //Load the feed library here, because we disabled autoloading.

        $data['rss_creator'] = new UniversalFeedCreator();
        $data['rss_creator']->title = $this->_topic->extra;
        $data['rss_creator']->link = $prefix;
        $data['rss_creator']->syndicationURL = "{$prefix}rss.xml";
        $data['rss_creator']->cssStyleSheet = false;

        if (count($data['products']) > 0)
        {
            foreach ($data['products'] as $product)
            {
                $data['product'] = $product;
                if (! $data['datamanager_product']->autoset_storage($product))
                {
                    debug_add("The datamanager for product #{$product->id} could not be initialized, skipping it.");
                    debug_print_r('Object was:', $product);
                    continue;
                }
                $data['view_product'] = $data['datamanager_product']->get_content_html();

                if ($product->code)
                {
                    $data['view_product_url'] = "{$prefix}product/{$product->code}/";
                }
                else
                {
                    $data['view_product_url'] = "{$prefix}product/{$product->guid}/";
                }

                midcom_show_style('feed_products_item');
            }
        }

        $data['rss'] = $data['rss_creator']->createFeed('RSS2.0');
        echo $data['rss'];

    }
}
?>