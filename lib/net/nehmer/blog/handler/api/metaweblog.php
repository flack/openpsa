<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// Include PEAR XML-RPC library
error_reporting(E_ERROR);
/** @ignore */
include_once("XML/RPC/Server.php");
error_reporting(E_ALL);

/**
 * MetaWeblog API handler for the blog component
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_api_metaweblog extends midcom_baseclasses_components_handler
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     * @access private
     */
    private $_content_topic = null;

    private $_positioning = false;

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->_content_topic =& $this->_request_data['content_topic'];

        if (!class_exists('XML_RPC_Server'))
        {
            throw new midcom_error('XML-RPC Server libraries not installer, aborting.');
        }

        if ($GLOBALS['midcom_config']['positioning_enable'])
        {
            if (!class_exists('org_routamc_positioning_object'))
            {
                // Load the positioning library
                $_MIDCOM->load_library('org.routamc.positioning');
            }
            $this->_positioning = true;
        }

        $_MIDCOM->cache->content->enable_live_mode();
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    private function _create_article($title)
    {
        $author = $_MIDCOM->auth->user->get_storage();

        $article = new midcom_db_article();
        $article->topic = $this->_content_topic->id;
        $article->title = $title;

        //Figure out author
        $article->author = $author->id;

        if (! $article->create())
        {
            debug_print_r('We operated on this object:', $article);
            return null;
        }

        // Generate URL name
        if ($article->name == '')
        {
            $article->name = midcom_helper_misc::generate_urlname_from_string($article->title);
            $tries = 0;
            $maxtries = 999;
            while(   !$article->update()
                  && $tries < $maxtries)
            {
                $article->name = midcom_helper_misc::generate_urlname_from_string($article->title);
                if ($tries > 0)
                {
                    // Append an integer if articles with same name exist
                    $article->name .= sprintf("-%03d", $tries);
                }
                $tries++;
            }
        }

        $article->parameter('midcom.helper.datamanager2', 'schema_name', $this->_config->get('api_metaweblog_schema'));

        return $article;
    }

    /**
     * Internal helper, loads the datamanager for the current article. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb']);

        if (!$this->_datamanager)
        {
            throw new midcom_error("Failed to create a DM2 instance.");
        }
    }

    private function _params_to_args($message)
    {
        $args = array();

        foreach ($message->params as $param)
        {
            $args[] = XML_RPC_decode($param);
        }

        return $args;
    }

    // metaWeblog.newPost
    function newPost($message)
    {
        $args = $this->_params_to_args($message);

        if (count($args) != 5)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid arguments.');
        }

        if ($args[0] != $this->_content_topic->guid)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Blog ID does not match this folder.');
        }

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        if (   !array_key_exists('title', $args[3])
            || $args[3]['title'] == '')
        {
            // Create article with title coming from datetime
            $new_title = strftime('%x %X');
        }
        else
        {
            $new_title = html_entity_decode($args[3]['title'], ENT_QUOTES, 'UTF-8');
        }

        $article = $this->_create_article($new_title);
        if (   !$article
            || !$article->guid)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to create article: ' . midgard_connection::get_error_string());
        }

        if (!$this->_datamanager->autoset_storage($article))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to initialize DM2 for article: ' . midgard_connection::get_error_string());
        }

        foreach ($args[3] as $field => $value)
        {
            switch ($field)
            {
                case 'title':
                    $this->_datamanager->types['title']->value = $new_title;
                    break;

                case 'mt_excerpt':
                    $this->_datamanager->types['abstract']->value = $value;
                    break;

                case 'description':
                    $this->_datamanager->types['content']->value = $value;
                    break;

                case 'link':
                    // TODO: We may have to bulletproof this a bit
                    $this->_datamanager->types['name']->value = str_replace('.html', '', basename($args[3]['link']));
                    break;

                case 'categories':
                    if (array_key_exists('categories', $this->_datamanager->types))
                    {
                        $this->_datamanager->types['categories']->selection = $value;
                        break;
                    }

                case 'http://www.georss.org/georss/':
                    if ($this->_positioning)
                    {
                        foreach ($value as $feature => $val)
                        {
                            switch ($feature)
                            {
                                case 'point':

                                    $coordinates = explode(' ', $val);
                                    if (count($coordinates) != 2)
                                    {
                                        break;
                                    }

                                    $log = new org_routamc_positioning_log_dba();
                                    $log->date = $article->metadata->published;
                                    $log->latitude = (float) $coordinates[0];
                                    $log->longitude = (float) $coordinates[1];
                                    $log->accuracy = ORG_ROUTAMC_POSITIONING_ACCURACY_MANUAL;
                                    $log->create();

                                    break;
                            }
                            // TODO: Handle different relationshiptags as per http://georss.org/simple/
                        }
                    }
                    break;
            }
        }

        if (!$this->_datamanager->save())
        {
            $article->delete();
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to update article: ' . midgard_connection::get_error_string());
        }

        // TODO: Map the publish property to approval

        // Index the article
        $indexer = $_MIDCOM->get_service('indexer');
        net_nehmer_blog_viewer::index($this->_datamanager, $indexer, $this->_content_topic);

        return new XML_RPC_Response(new XML_RPC_Value($article->guid, 'string'));
    }

    // metaWeblog.getPost
    function getPost($message)
    {
        $args = $this->_params_to_args($message);

        if (count($args) != 3)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid arguments.');
        }

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        try
        {
            $article = new midcom_db_article($args[0]);
        }
        catch (midcom_error $e)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Article not found: ' . $e->getMessage());
        }

        if (!$this->_datamanager->autoset_storage($article))
        {
           return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to load DM2 for the article.');
        }

        $arg = $article->name ? $article->name : $article->guid;
        if ($this->_config->get('view_in_url'))
        {
            $link = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "view/{$arg}/";
        }
        else
        {
            $link = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "{$arg}/";
        }

        if (array_key_exists('categories', $this->_datamanager->types))
        {
            $categories = $this->_datamanager->types['categories']->selection;
        }
        else
        {
            $categories = array();
        }

        $response_array = array
        (
            'postid'      => new XML_RPC_Value($article->guid, 'string'),
            'title'       => new XML_RPC_Value($article->title, 'string'),
            'permaLink'   => new XML_RPC_Value($_MIDCOM->permalinks->create_permalink($article->guid), 'string'),
            'link'        => new XML_RPC_Value($link, 'string'),
            'description' => new XML_RPC_Value($article->content, 'string'),
            'mt_excerpt'  => new XML_RPC_Value($article->abstract, 'string'),
            'dateCreated' => new XML_RPC_Value(gmdate("Ymd\TH:i:s\Z", $article->metadata->published), 'dateTime.iso8601'),
            'categories'  => XML_RPC_encode($categories),
        );

        if ($this->_positioning)
        {
            $object_position = new org_routamc_positioning_object($article);
            $coordinates = $object_position->get_coordinates();
            $georss_array = array
            (
                'point' => new XML_RPC_Value("{$coordinates['latitude']} {$coordinates['longitude']}", 'string'),
            );
            $response_array['http://www.georss.org/georss/'] = new XML_RPC_Value($georss_array, 'struct');
        }

        return new XML_RPC_Response(new XML_RPC_Value($response_array, 'struct'));
    }


    // metaWeblog.editPost
    function editPost($message)
    {
        $args = $this->_params_to_args($message);

        if (count($args) != 5)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid arguments.');
        }

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        try
        {
            $article = new midcom_db_article($args[0]);
        }
        catch (midcom_error $e)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Article not found: ' . $e->getMessage());
        }

        if (!$this->_datamanager->autoset_storage($article))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to initialize DM2 for article: ' . midgard_connection::get_error_string());
        }

        foreach ($args[3] as $field => $value)
        {
            switch ($field)
            {
                case 'title':
                    $this->_datamanager->types['title']->value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                    break;

                case 'mt_excerpt':
                    $this->_datamanager->types['abstract']->value = $value;
                    break;

                case 'description':
                    $this->_datamanager->types['content']->value = $value;
                    break;

                case 'link':
                    // TODO: We may have to bulletproof this a bit
                    $this->_datamanager->types['name']->value = str_replace('.html', '', basename($args[3]['link']));
                    break;

                case 'categories':
                    if (array_key_exists('categories', $this->_datamanager->types))
                    {
                        $this->_datamanager->types['categories']->selection = $value;
                        break;
                    }

                case 'http://www.georss.org/georss/':
                    if ($this->_positioning)
                    {
                        foreach ($value as $feature => $val)
                        {
                            switch ($feature)
                            {
                                case 'point':

                                    $coordinates = explode(' ', $val);
                                    if (count($coordinates) != 2)
                                    {
                                        break;
                                    }

                                    $log = new org_routamc_positioning_log_dba();
                                    $log->date = $article->metadata->published;
                                    $log->latitude = (float) $coordinates[0];
                                    $log->longitude = (float) $coordinates[1];
                                    $log->accuracy = ORG_ROUTAMC_POSITIONING_ACCURACY_MANUAL;
                                    $log->create();

                                    break;
                            }
                            // TODO: Handle different relationshiptags as per http://georss.org/simple/
                        }
                    }
                    break;
            }
        }

        if (!$this->_datamanager->save())
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to update article: ' . midgard_connection::get_error_string());
        }

        // TODO: Map the publish property to approval

        // Index the article
        $indexer = $_MIDCOM->get_service('indexer');
        net_nehmer_blog_viewer::index($this->_datamanager, $indexer, $this->_content_topic);

        return new XML_RPC_Response(new XML_RPC_Value($article->guid, 'string'));
    }

    // metaWeblog.getRecentPosts
    function getRecentPosts($message)
    {
        $args = $this->_params_to_args($message);

        if (count($args) != 4)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid arguments.');
        }

        if ($args[0] != $this->_content_topic->guid)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Blog ID does not match this folder.');
        }

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        $response = array();

        $qb = midcom_db_article::new_query_builder();
        $qb->set_limit($args[3]);
        $qb->add_constraint('topic', '=', $this->_content_topic->id);
        $qb->add_order('metadata.published', 'DESC');

        $articles = $qb->execute();
        foreach ($articles as $article)
        {
            if (!$this->_datamanager->autoset_storage($article))
            {
                // This article has something wrong, skip it
                continue;
            }

            $arg = $article->name ? $article->name : $article->guid;
            if ($this->_config->get('view_in_url'))
            {
                $link = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "view/{$arg}/";
            }
            else
            {
                $link = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "{$arg}/";
            }

            if (array_key_exists('categories', $this->_datamanager->types))
            {
                $categories = $this->_datamanager->types['categories']->selection;
            }
            else
            {
                $categories = array();
            }

            $response_array = array
            (
                'postid'      => new XML_RPC_Value($article->guid, 'string'),
                'title'       => new XML_RPC_Value($article->title, 'string'),
                'permaLink'   => new XML_RPC_Value($_MIDCOM->permalinks->create_permalink($article->guid), 'string'),
                'link'        => new XML_RPC_Value($link, 'string'),
                'description' => new XML_RPC_Value($article->content, 'string'),
                'mt_excerpt'  => new XML_RPC_Value($article->abstract, 'string'),
                'dateCreated' => new XML_RPC_Value(gmdate("Ymd\TH:i:s\Z", $article->metadata->published), 'dateTime.iso8601'),
                'categories'  => XML_RPC_encode($categories),
            );

            if ($this->_positioning)
            {
                $object_position = new org_routamc_positioning_object($article);
                $coordinates = $object_position->get_coordinates();
                $response_array['georss:point'] = new XML_RPC_Value("{$coordinates['latitude']} {$coordinates['longitude']}", 'string');
            }

            $response[] = new XML_RPC_Value($response_array, 'struct');
        }

        return new XML_RPC_Response(new XML_RPC_Value($response, 'array'));
    }

    // metaWeblog.getCategories
    function getCategories($message)
    {
        $args = $this->_params_to_args($message);

        if (count($args) != 3)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid arguments.');
        }

        if ($args[0] != $this->_content_topic->guid)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Blog ID does not match this folder.');
        }

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        $response = array();

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        foreach ($this->_request_data['categories'] as $category)
        {
            $response_array = array
            (
                'description' => new XML_RPC_Value($category, 'string'),
                'htmlUrl' => new XML_RPC_Value("{$prefix}category/" . rawurlencode($category), 'string'),
                'rssUrl' => new XML_RPC_Value("{$prefix}feeds/category/" . rawurlencode($category), 'string'),
            );

            $response[$category] = new XML_RPC_Value($response_array, 'struct');
        }

        return new XML_RPC_Response(new XML_RPC_Value($response, 'struct'));
    }

    // metaWeblog.newMediaObject
    function newMediaObject($message)
    {
        $args = $this->_params_to_args($message);

        if (count($args) != 4)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid arguments.');
        }

        if ($args[0] != $this->_content_topic->guid)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Blog ID does not match this folder.');
        }

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        if (count($args) < 3)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid file data.');
        }

        if (!$args[3]['name'])
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'No filename given.');
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
                return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to create attachment: ' . midgard_connection::get_error_string());
            }
        }

        if (!$attachment->copy_from_memory($args[3]['bits']))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to store contents to attachment: ' . midgard_connection::get_error_string());
        }

        $attachment_array = array
        (
            'url'  => new XML_RPC_Value("{$GLOBALS['midcom_config']['midcom_site_url']}midcom-serveattachmentguid-{$attachment->guid}/{$attachment->name}", 'string'),
            'guid' => new XML_RPC_Value($attachment->guid, 'string'),
        );
        return new XML_RPC_Response(new XML_RPC_Value($attachment_array, 'struct'));
    }

    // blogger.deletePost
    function deletePost($message)
    {
        $args = $this->_params_to_args($message);

        if (count($args) != 5)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid arguments.');
        }

        if (!$_MIDCOM->auth->login($args[2], $args[3]))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        try
        {
            $article = new midcom_db_article($args[1]);
        }
        catch (midcom_error $e)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Article not found: ' . $e->getMessage());
        }

        if (!$article->delete())
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Failed to delete article: ' . midgard_connection::get_error_string());
        }

        // Update the index
        $indexer = $_MIDCOM->get_service('indexer');
        $indexer->delete($article->guid);

        return new XML_RPC_Response(new XML_RPC_Value(true, 'boolean'));
    }

    // metaWeblog.getUsersBlogs
    function getUsersBlogs($message)
    {
        $args = $this->_params_to_args($message);

        if (count($args) != 3)
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Invalid arguments.');
        }

        if (!$_MIDCOM->auth->login($args[1], $args[2]))
        {
            return new XML_RPC_Response(0, midcom_connection::get_error(), 'Authentication failed.');
        }
        $_MIDCOM->auth->initialize();

        $response = array();

        $topic = $this->_topic;
        if (!$topic->can_do('midgard:create'))
        {
            // Skip this blog, user cannot edit
            continue;
        }

        $nap = new midcom_helper_nav();
        $node = $nap->get_node($topic->id);
        if (!$node)
        {
            // This topic isn't on site
            continue;
        }

        $response_array = array
        (
            'url'      => new XML_RPC_Value($node[MIDCOM_NAV_FULLURL], 'string'),
            'blogid'   => new XML_RPC_Value($topic->guid, 'string'),
            'blogName' => new XML_RPC_Value($node[MIDCOM_NAV_NAME], 'string'),
        );

        $response[] = new XML_RPC_Value($response_array, 'struct');

        return new XML_RPC_Response(new XML_RPC_Value($response, 'array'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_rsd($handler_id, $args, &$data)
    {
        //Content-Type
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->content_type('text/xml');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_rsd($handler_id, &$data)
    {
        $data['content_topic'] = $this->_content_topic;
        midcom_show_style('rsd');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_server($handler_id, $args, &$data)
    {
        if (!$this->_config->get('api_metaweblog_enable'))
        {
            throw new midcom_error('Metaweblog API is disabled');
        }

        //Content-Type
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->no_cache();
        $_MIDCOM->cache->content->content_type('text/xml');

        $this->_load_datamanager();

        // Populate the XML-RPC dispatch map
        $data['dispatchmap'] = array
        (
            // MetaWebLog API
            'metaWeblog.newPost' => array
            (
                'function' => array($this, 'newPost'),
            ),
            'metaWeblog.getPost' => array
            (
                'function' => array($this, 'getPost'),
            ),
            'metaWeblog.editPost' => array
            (
                'function' => array($this, 'editPost'),
            ),
            'metaWeblog.getRecentPosts' => array
            (
                'function' => array($this, 'getRecentPosts'),
            ),
            'metaWeblog.getCategories' => array
            (
                'function' => array($this, 'getCategories'),
            ),
            'metaWeblog.newMediaObject' => array
            (
                'function' => array($this, 'newMediaObject'),
            ),
            // Blogger API
            'blogger.deletePost' => array
            (
                'function' => array($this, 'deletePost'),
            ),
            'blogger.getUsersBlogs' => array
            (
                'function' => array($this, 'getUsersBlogs'),
            )
        );
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_server($handler_id, &$data)
    {
        // Serve the RPC request
        new XML_RPC_Server($data['dispatchmap']);
    }
}
