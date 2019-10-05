<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_nav_backendTest extends openpsa_testcase
{
    public function test_singlenode()
    {
        $root_topic = $this->create_object(midcom_db_topic::class, ['component' => 'midcom.core.nullcomponent']);
        $backend = new midcom_helper_nav_backend($root_topic, []);
        $this->assertEquals($root_topic->id, $backend->get_root_node());
        $this->assertEquals($root_topic->id, $backend->get_current_node());
        $this->assertEquals([$root_topic->id], $backend->get_node_path());
        $this->assertEquals(-1, $backend->get_node_uplink($root_topic->id));
        $node = $backend->get_node($backend->get_current_node());
        $this->assertEquals('', $node[MIDCOM_NAV_RELATIVEURL]);
        $this->assertEquals('/', $node[MIDCOM_NAV_ABSOLUTEURL]);
    }

    public function test_nonpersistent_root()
    {
        $root_topic = new midcom_db_topic;
        $root_topic->component = 'midcom.core.nullcomponent';
        $backend = new midcom_helper_nav_backend($root_topic, []);
        $this->assertEquals($root_topic->id, $backend->get_root_node());
        $this->assertEquals($root_topic->id, $backend->get_current_node());
        $this->assertEquals([$root_topic->id], $backend->get_node_path());
        $this->assertEquals(-1, $backend->get_node_uplink($root_topic->id));
        $node = $backend->get_node($backend->get_current_node());
        $this->assertEquals('', $node[MIDCOM_NAV_RELATIVEURL]);
        $this->assertEquals('/', $node[MIDCOM_NAV_ABSOLUTEURL]);
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

        $context = midcom_core_context::enter("/$child_topic_name/$article_name/", $root_topic);
        $context->set_key(MIDCOM_CONTEXT_CONTENTTOPIC, $child_topic);

        $request = Request::create("/$child_topic_name/$article_name/");
        $request->attributes->set('context', $context);
        $GLOBALS['kernel']->handle($request, KernelInterface::SUB_REQUEST);

        $backend = new midcom_helper_nav_backend($root_topic, [$child_topic]);

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
