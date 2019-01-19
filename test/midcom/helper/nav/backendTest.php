<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use midcom\httpkernel\kernel;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_backendTest extends openpsa_testcase
{
    public function test_singlenode()
    {
        $root_topic = $this->create_object(midcom_db_topic::class, ['component' => 'midcom.core.nullcomponent']);
        $context = new midcom_core_context(null, $root_topic);
        $backend = new midcom_helper_nav_backend($context->id);
        $this->assertEquals($root_topic->id, $backend->get_root_node());
        $this->assertEquals($root_topic->id, $backend->get_current_node());
        $this->assertEquals([$root_topic->id], $backend->get_node_path());
        $this->assertEquals(-1, $backend->get_node_uplink($root_topic->id));
    }

    public function test_tree()
    {
        $root_topic_name = uniqid('root');
        $child_topic_name = uniqid('child');
        $article_name = uniqid('article');
        $root_topic = $this->create_object(midcom_db_topic::class, [
            'name' => $root_topic_name,
            'component' => 'midcom.core.nullcomponent'
        ]);
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
        $context->set_key(MIDCOM_CONTEXT_CONTENTTOPIC, $child_topic);

        $request = Request::create("/$child_topic_name/$article_name/");
        $request->attributes->set('context', $context);
        kernel::get()->handle($request);

        $backend = new midcom_helper_nav_backend($context->id);

        $this->assertEquals($root_topic->id, $backend->get_root_node());
        $this->assertEquals($child_topic->id, $backend->get_current_node());
        $this->assertEquals($root_topic->id, $backend->get_current_upper_node());
        $this->assertEquals($root_topic->id, $backend->get_node_uplink($child_topic->id));
        $this->assertEquals([$root_topic->id, $child_topic->id], $backend->get_node_path());
        $this->assertEquals([$child_topic->id], $backend->list_nodes($root_topic->id, true));

        $this->assertEquals([$child_topic->id . '-' . $article->id], $backend->list_leaves($child_topic->id, true));

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
        $this->assertInternalType('array', $actual);
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $actual[$key]);
        }

        $this->assertEquals($child_topic->id, $backend->get_leaf_uplink($leaf_id));
        $this->assertEquals($leaf_id, $backend->get_current_leaf());
    }
}
