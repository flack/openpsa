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
class midcom_helper_reflector_nameresolverTest extends openpsa_testcase
{
    public function test_get_object_name()
    {
        $article = new midcom_db_article;
        $article->name = 'test';
        $resolver = new midcom_helper_reflector_nameresolver($article);
        $this->assertEquals('test', $resolver->get_object_name());
        $this->assertFalse($resolver->get_object_name('nonexistent'));
    }

    public function test_name_is_unique()
    {
        $name1 = uniqid('name1');
        $name2 = uniqid('name2');
        $topic1 = $this->create_object('midcom_db_topic', ['name' => $name1]);
        $topic2 = $this->create_object('midcom_db_topic', ['name' => $name2, 'up' => $topic1->id]);

        $resolver = new midcom_helper_reflector_nameresolver($topic2);
        $this->assertTrue($resolver->name_is_unique());
        $article = new midcom_db_article;
        $article->topic = $topic1->id;
        $article->name = $name1;
        $resolver = new midcom_helper_reflector_nameresolver($article);
        $this->assertTrue($resolver->name_is_unique());
        $article->name = $name2;
        $resolver = new midcom_helper_reflector_nameresolver($article);

        $this->assertFalse($resolver->name_is_unique());
    }

    public function test_name_is_safe()
    {
        $article = new midcom_db_article;
        $article->name = 'gathering-09';
        $article->allow_name_catenate = true;
        $resolver = new midcom_helper_reflector_nameresolver($article);

        $this->assertTrue($resolver->name_is_safe());
    }

    public function test_name_is_clean()
    {
        $article = new midcom_db_article;
        $article->name = 'gathering-09';
        $article->allow_name_catenate = true;
        $resolver = new midcom_helper_reflector_nameresolver($article);

        $this->assertTrue($resolver->name_is_clean());
    }
}
