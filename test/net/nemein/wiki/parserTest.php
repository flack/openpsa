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
class net_nemein_wiki_parserTest extends openpsa_testcase
{
    protected static $_page;

    public static function setUpBeforeClass()
    {
        $topic = self::get_component_node('net.nemein.wiki');
        $attributes = [
            'topic' => $topic->id,
            'title' => uniqid(__CLASS__)
        ];
        self::$_page = self::create_class_object(net_nemein_wiki_wikipage::class, $attributes);
    }

    /**
     * @dataProvider provider_find_links_in_content
     */
    public function test_find_links_in_content($text, $result)
    {
        self::$_page->content = $text;
        midcom::get()->auth->request_sudo('net.nemein.wiki');
        self::$_page->update();
        midcom::get()->auth->drop_sudo();

        $parser = new net_nemein_wiki_parser(self::$_page);
        $links = $parser->find_links_in_content();
        $this->assertEquals($result, $links);
    }

    public function provider_find_links_in_content()
    {
        return [
            '1' => [
                'filler [link|Link Title] filler',
                ['link' => 'Link Title']
            ],
            '2' => [
                'filler [link] filler',
                ['link' => 'link']
            ],
            '3' => [
                'filler filler',
                []
            ],
        ];
    }
}
