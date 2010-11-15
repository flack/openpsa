<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: email.php 25321 2010-03-18 15:18:49Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * E-Mail import handler.
 *
 * This uses the OpenPSA 2 email importer MDA system. Emails are imported
 * into blog, with a possible attached image getting stored using 'image'
 * type in schema if available.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_api_email extends midcom_baseclasses_components_handler
{
    /**
     * The article to operate on
     *
     * @var midcom_db_article
     * @access private
     */
    var $_article;

    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     * @access private
     */
    var $_content_topic = null;

    /**
     * Email importer
     *
     * @var org_openpsa_mail
     * @access private
     */
    var $_decoder;

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    function _on_initialize()
    {
        $this->_content_topic =& $this->_request_data['content_topic'];
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    function _create_article($title)
    {
        $this->_article = new midcom_db_article();
        $author = $this->_find_email_person($this->_request_data['from']);
        if (!$author)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Author '{$this->_request_data['from']}' not found", MIDCOM_LOG_WARN);
            debug_pop();
            if ($this->_config->get('api_email_abort_authornotfound') !== false)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Author '{$this->_request_data['from']}' not found");
                // This will exit()
            }
            $this->_article->author = midcom_connection::get_user();
        }
        else
        {
            // TODO: This code needs a bit of rethinking
            $author_user = $_MIDCOM->auth->get_user($author->guid);
            if (!$this->_content_topic->can_do('midgard:create', $author_user))
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Author doesn\'t have posting privileges');
            }
            $this->_article->author = $author->id;
        }

        //Default to first user in DB if author is not set
        if (!$this->_article->author)
        {
            $qb = midcom_db_person::new_query_builder();
            $qb->add_constraint('username', '<>', '');
            $qb->set_limit(1);
            $results = $qb->execute();
            unset($qb);
            if (empty($results))
            {
                //No users found
                debug_pop();
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Cannot set any author for the article');
                // This will exit.
            }
            $this->_article->author = $results[0]->id;
        }

        $_MIDCOM->load_library('midcom.helper.reflector');
        $this->_article->topic = $this->_content_topic->id;
        $this->_article->title = $title;
        $this->_article->allow_name_catenate = true;
        $this->_article->name = midcom_helper_reflector_tree::generate_unique_name($this->_article, 'title');
        if (empty($this->_article->name))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not generate unique name for the new article from title, using timestamp', MIDCOM_LOG_INFO);
            debug_pop();
            $this->_article->name = time();
            if (!midcom_helper_reflector_tree::name_is_unique($this->_article))
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to create unique name for the new article, aborting.');
                // This will exit.
            }
        }

        if (! $this->_article->create())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('Failed to create article:', $this->_article);
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to create a new article, cannot continue. Last Midgard error was: ' . midcom_connection::get_error_string());
            // This will exit.
        }

        $this->_article->parameter('midcom.helper.datamanager2', 'schema_name', $this->_config->get('api_email_schema'));

        return true;
    }

    /**
     * Internal helper, loads the datamanager for the current article. Any error triggers a 500.
     *
     * @access private
     */
    function _load_datamanager()
    {
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb']);

        if (   ! $this->_datamanager
            || ! $this->_datamanager->set_schema($this->_config->get('api_email_schema'))
            || ! $this->_datamanager->set_storage($this->_article))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for article {$this->_article->id}.");
            // This will exit.
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_import($handler_id, $args, &$data)
    {
        if (!$this->_config->get('api_email_enable'))
        {
            return false;
        }
        if ($handler_id === 'api-email-basicauth')
        {
            $_MIDCOM->auth->require_valid_user('basic');
        }

        //Content-Type
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->content_type('text/plain');

        if (!isset($this->_request_data['schemadb'][$this->_config->get('api_email_schema')]))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Schema "' . $this->_config->get('api_email_schema') . '" not found in schemadb "' . $this->_config->get('schemadb') . '"');
            // this will exit()
        }
        $schema_instance =& $this->_request_data['schemadb'][$this->_config->get('api_email_schema')];

        // Parse email
        $this->_decode_email();
        $this->_parse_email_persons();

        $_MIDCOM->auth->request_sudo('net.nehmer.blog');

        // Create article
        $this->_create_article($this->_decoder->subject);

        // Load the article to DM2
        $this->_load_datamanager();

        // Find image and tag fields in schema
        foreach ($schema_instance->fields as $name => $field)
        {
            if (is_a($this->_datamanager->types[$name], 'midcom_helper_datamanager2_type_image'))
            {
                $this->_request_data['image_field'] = $name;
                continue;
            }

            if (is_a($this->_datamanager->types[$name], 'midcom_helper_datamanager2_type_tags'))
            {
                $data['tags_field'] = $name;
                continue;
            }
        }

        // Try to find tags in email content
        $content = $this->_decoder->body;
        $content_tags = '';
        $_MIDCOM->componentloader->load_graceful('net.nemein.tag');
        if (class_exists('net_nemein_tag_handler'))
        {
            // unconditionally tag
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("content before machine tag separation\n===\n{$content}\n===\n");
            $content_tags = net_nemein_tag_handler::separate_machine_tags_in_content($content);
            if (!empty($content_tags))
            {
                debug_add("found machine tags string: {$content_tags}");
                net_nemein_tag_handler::tag_object($this->_article, net_nemein_tag_handler::string2tag_array($content_tags));
            }
            debug_add("content AFTER machine tag separation\n===\n{$content}\n===\n");
            debug_pop();
        }

        // Populate rest of the data
        $this->_datamanager->types['content']->value = $content;
        if (!empty($data['tags_field']))
        {
            // if we have tags field put content_tags value there as well or they will get deleted!
            $this->_datamanager->types[$data['tags_field']]->value = $content_tags;
        }
        $body_switched = false;

        foreach ($this->_decoder->attachments as $att)
        {
            debug_add("processing attachment {$att['name']}");

            switch (true)
            {
                case (strpos($att['mimetype'], 'image/') !== false):
                    $this->_add_image($att);
                    break;
                case (strtolower($att['mimetype']) == 'text/plain'):
                    if (!$body_switched)
                    {
                        // Use first text/plain part as the content
                        $this->_datamanager->types['content']->value = $att['content'];
                        $body_switched = true;
                        break;
                    }
                    // Fall-through if not switching
                default:
                    $this->_add_attachment($att);
            }
        }

        if (!$this->_datamanager->save())
        {
            // Remove the article, but get errstr first
            $errstr = midcom_connection::get_error_string();
            $this->_article->delete();

            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'DM2 failed to save the article, aborting. Last Midgard error was: ' . $errstr);
            // This will exit()
        }

        // Index the article
        $indexer = $_MIDCOM->get_service('indexer');
        net_nehmer_blog_viewer::index($this->_datamanager, $indexer, $this->_content_topic);

        if ($this->_config->get('api_email_autoapprove'))
        {
            $metadata = midcom_helper_metadata::retrieve($this->_article);
            if (!$metadata->force_approve())
            {
                // Remove the article, but get errstr first
                $errstr = midcom_connection::get_error_string();
                $this->_article->delete();

                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to force approval on article, aborting. Last Midgard error was: ' . $errstr);
                // This will exit()
            }
        }

        $_MIDCOM->auth->drop_sudo();
        debug_pop();
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_import($handler_id, &$data)
    {
        //All done
        echo "OK\n";
    }

    function _decode_email()
    {
        //Load o.o.mail
        $_MIDCOM->load_library('org.openpsa.mail');

        //Make sure we have the components we use and the Mail_mimeDecode package
        if (!class_exists('org_openpsa_mail'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'library org.openpsa.mail could not be loaded.');
            // This will exit.
        }

        $this->_decoder = new org_openpsa_mail();

        if (!class_exists('Mail_mimeDecode'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Cannot decode attachments, aborting.');
            // This will exit.
        }

        //Make sure the message_source is POSTed
        if (   !array_key_exists('message_source', $_POST)
            || empty($_POST['message_source']))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, '_POST[\'message_source\'] not present or empty.');
            // This will exit.
        }
        debug_push_class(__CLASS__, __FUNCTION__);

        $this->_decoder = new org_openpsa_mail();
        $this->_decoder->body = $_POST['message_source'];
        $this->_decoder->mime_decode();
    }

    function _parse_email_persons()
    {
        //Parse email addresses
        $regex = '/<?([a-zA-Z0-9_.-]+?@[a-zA-Z0-9_.-]+)>?[ ,]?/';
        $emails = array();
        if (preg_match_all($regex, $this->_decoder->headers['To'], $matches_to))
        {
            foreach ($matches_to[1] as $email)
            {
                //Each address only once
                $emails[$email] = $email;
            }
        }
        if (preg_match_all($regex, $this->_decoder->headers['Cc'], $matches_cc))
        {
            foreach ($matches_cc[1] as $email)
            {
                //Each address only once
                $emails[$email] = $email;
            }
        }
        $from = false;
        if (preg_match_all($regex, $this->_decoder->headers['From'], $matches_from))
        {
            foreach ($matches_from[1] as $email)
            {
                //Each address only once
                $emails[$email] = $email;
                //It's unlikely that we'd get multiple matches in From, but we use the latest
                $this->_request_data['from'] = $email;
            }
        }
    }

    function _add_image($att)
    {
        if (!array_key_exists('image_field', $this->_request_data))
        {
            // No image fields in schema, revert to regular attachment handling
            return $this->_add_attachment($att);
        }

        // Save image to a temp file
        $tmp_name = tempnam($GLOBALS['midcom_config']['midcom_tempdir'], 'net_nehmer_blog_handler_api_email_');
        $fp = fopen($tmp_name, 'w');

        if (!fwrite($fp, $att['content']))
        {
            //Could not write, clean up and continue
            debug_add("Error when writing file {$tmp_name}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            fclose($fp);
            return false;
        }

        return $this->_datamanager->types[$this->_request_data['image_field']]->set_image($att['name'], $tmp_name, $att['name']);
    }

    function _add_attachment($att)
    {
        return false;

        $attobj = $this->_article->create_attachment($att['name'], $att['name'], $att['mimetype']);
        if (!$attobj)
        {
            //Could not create attachment
            debug_add("Could not create attachment '{$att['name']}', errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            continue;
        }
        $fp = @$attobj->open('w');
        if (!$fp)
        {
            //Could not open for writing, clean up and continue
            debug_add("Could not open attachment {$attobj->guid} for writing, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            $attobj->delete();
            continue;
        }
        if (!fwrite($fp, $att['content'], strlen($att['content'])))
        {
            //Could not write, clean up and continue
            debug_add("Error when writing attachment {$attobj->guid}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            fclose($fp);
            $attobj->delete();
            continue;
        }
        fclose($fp);

        if (   isset($att['part'])
            && isset($att['part']->headers)
            && isset($att['part']->headers['content-id']))
        {
            //Attachment is embed, add tag to end of note
            if (!$embeds_added)
            {
                $this->_article->content .= "<p>";
                $embeds_added = true;
            }
            $this->_article->content .= "<a href=\"{$_MIDGARD['self']}midcom-serveattachmentguid-{$attobj->guid}/{$attobj->name}\">{$attobj->title}</a><br />";
        }
        else
        {
            //Add normal attachments as links to end of note
            if (!$attachments_added)
            {
                //We hope the client handles these so that embeds come first and attachments then so we can avoid double pass over this array
                $this->_article->content .= "\n\n";
                $attachments_added = true;
            }
            $this->_article->content .= "[{$attobj->title}]({$_MIDGARD['self']}midcom-serveattachmentguid-{$attobj->guid}/{$attobj->name}), ";
        }
    }

    function _find_email_person($email, $prefer_user = true)
    {
        // TODO: Use the new helpers for finding persons by email (a person might have multiple ones...)
        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('email', '=', $email);
        $results = $qb->execute();
        if (empty($results))
        {
            return false;
        }
        if (!$prefer_user)
        {
            return $results[0];
        }
        foreach ($results as $person)
        {
            if (!empty($person->username))
            {
                return $person;
            }
        }
        return $person;
    }
}
