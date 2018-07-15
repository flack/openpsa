<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_backendTest extends openpsa_testcase
{
    public function test_singlenode()
    {
        $root_topic = $this->create_object(midcom_db_topic::class);
        $context = new midcom_core_context(null, $root_topic);
        $backend = new midcom_helper_nav_backend($context->id);
        $this->assertEquals($backend->get_root_node(), $root_topic->id);
        $this->assertEquals($backend->get_current_node(), $root_topic->id);
        $this->assertEquals($backend->get_node_path(), [$root_topic->id]);
        $this->assertEquals($backend->get_node_uplink($root_topic->id), -1);
    }

    public function test_tree()
    {
        $root_topic_name = uniqid('root');
        $child_topic_name = uniqid('child');
        $article_name = uniqid('article');
        $root_topic = $this->create_object(midcom_db_topic::class, ['name' => $root_topic_name]);
        $child_attributes = [
            'name' => $child_topic_name,
            'up' => $root_topic->id,
            'component' => 'net.nehmer.static'
        ];
        $child_topic = $this->create_object(midcom_db_topic::class, $child_attributes);
        $article_attributes = [
            'name' => $article_name,
            'topic' => $child_topic->id,
        ];
        $article = $this->create_object(midcom_db_article::class, $article_attributes);
        $leaf_id = $child_topic->id . '-' . $article->id;
        midcom_baseclasses_components_configuration::set($child_topic->component, 'active_leaf', $article->id);

        $context = new midcom_core_context(null, $root_topic);
        $context->set_current();
        $context->parser = new midcom_core_service_implementation_urlparsertopic;

        $context->parser->parse([$child_topic_name, $article_name]);
        $context->parser->get_object();
        $context->handle($child_topic);
        $backend = new midcom_helper_nav_backend($context->id);

        $this->assertEquals($backend->get_root_node(), $root_topic->id);
        $this->assertEquals($backend->get_current_node(), $child_topic->id);
        $this->assertEquals($backend->get_current_upper_node(), $root_topic->id);
        $this->assertEquals($backend->get_node_uplink($child_topic->id), $root_topic->id);
        $this->assertEquals($backend->get_node_path(), [$root_topic->id, $child_topic->id]);
        $this->assertEquals($backend->list_nodes($root_topic->id, true), [$child_topic->id]);

        $this->assertEquals($backend->list_leaves($child_topic->id, true), [$child_topic->id . '-' . $article->id]);

        $expected = [
            MIDCOM_NAV_URL => $article->name . '/',
            MIDCOM_NAV_NAME => $article->name,
            MIDCOM_NAV_GUID => $article->guid,
            MIDCOM_NAV_NODEID => $child_topic->id,
            MIDCOM_NAV_ID => $leaf_id,
            MIDCOM_NAV_TYPE => 'leaf',
            MIDCOM_NAV_SCORE => 0,
            MIDCOM_NAV_FULLURL => 'http://localhost/' . $child_topic->name . '/' . $article->name . '/',
            MIDCOM_NAV_PERMALINK => 'http://localhost/midcom-permalink-' . $article->guid,
            MIDCOM_NAV_NOENTRY => false,
            MIDCOM_NAV_RELATIVEURL => $child_topic->name . '/' . $article->name . '/',
            MIDCOM_NAV_ABSOLUTEURL => '/' . $child_topic->name . '/' . $article->name . '/',
            MIDCOM_NAV_ICON => null,
            MIDCOM_NAV_LEAFID => $article->id,
            MIDCOM_NAV_SORTABLE => true,
        ];

        $actual = $backend->get_leaf($leaf_id);
        $this->assertTrue(is_array($actual));
        foreach ($expected as $key => $value) {
            $this->assertEquals($actual[$key], $value);
        }

        $this->assertEquals($backend->get_leaf_uplink($leaf_id), $child_topic->id);
        $this->assertEquals($backend->get_current_leaf(), $leaf_id);
    }
}
