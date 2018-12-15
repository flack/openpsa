<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom;
use midcom\datamanager\extension\transformer\blobTransformer;

class blobTransformerTest extends openpsa_testcase
{
    /**
     * @dataProvider provider_transform
     */
    public function test_transform($input, $expected)
    {
        $config = [
            'widget_config' => ['show_description' => false]
        ];
        $transformer = new blobTransformer($config);
        $this->assertEquals($expected, $transformer->transform($input));
    }

    /**
     * @dataProvider provider_transform
     */
    public function test_reverseTransform($expected, $input)
    {
        $config = [
            'widget_config' => ['show_description' => false]
        ];
        $transformer = new blobTransformer($config);
        $this->assertEquals($expected, $transformer->reverseTransform($input));
    }

    public function provider_transform()
    {
        $topic = $this->create_object(\midcom_db_topic::class);
        midcom::get()->auth->request_sudo('midcom.datamanager');
        $att = $topic->create_attachment('test', 'test', 'text/plain');
        $handle = $att->open('w');
        fwrite($handle, 'test');
        $time = time();
        $att->close();

        midcom::get()->auth->drop_sudo('midcom.datamanager');

        return [
           [null, null],
           [$att, [
               'object' => $att,
               'filename' => 'test',
               'description' => 'test',
               'title' => 'test',
               'mimetype' => 'text/plain',
               'url' => '/midcom-serveattachmentguid-' . $att->guid . '/test',
               'id' => $att->id,
               'guid' => $att->guid,
               'filesize' => 4,
               'formattedsize' => '4 Bytes',
               'lastmod' => $time,
               'isoformattedlastmod' => strftime('%Y-%m-%d %H:%M:%S', $time),
               'size_x' => null,
               'size_y' => null,
               'size_line' => null,
               'score' => 0,
               'identifier' => $att->guid,
           ]]
        ];
    }

    public function test_upload()
    {
        $config = [
            'widget_config' => ['show_description' => false]
        ];
        $transformer = new blobTransformer($config);

        $source_path = midcom::get()->config->get('midcom_tempdir') . '/test';
        $file = [
            'error' => 0,
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $source_path,
            'size' => 4
        ];
        $identifier = md5(implode('', $file));

        $target_path = midcom::get()->config->get('midcom_tempdir') . '/tmpfile-' . $identifier;
        file_put_contents($source_path, 'test');

        $input = [
            'title' => null,
            'identifier' => null,
            'file' => $file
        ];

        $rt_expected = new \midcom_db_attachment();
        $rt_expected->name = 'test.txt';
        $rt_expected->title = 'test.txt';
        $rt_expected->mimetype = 'text/plain';
        $rt_expected->location = $target_path;

        $this->assertEquals($rt_expected, $transformer->reverseTransform($input));

        // move_uploaded_file fails on our fake upload, so we do it manually
        rename($source_path, $target_path);
        $time = time();

        $t_expected = [
            'object' => $rt_expected,
            'filename' => 'test.txt',
            'description' => 'test.txt',
            'title' => 'test.txt',
            'mimetype' => 'text/plain',
            'url' => '/midcom-serveattachmentguid-' . $rt_expected->guid . '/test.txt',
            'id' => 0,
            'guid' => '',
            'filesize' => 4,
            'formattedsize' => '4 Bytes',
            'lastmod' => $time,
            'isoformattedlastmod' => strftime('%Y-%m-%d %H:%M:%S', $time),
            'size_x' => null,
            'size_y' => null,
            'size_line' => null,
            'score' => 0,
            'identifier' => 'tmpfile-' . $identifier,
        ];

        $this->assertEquals($t_expected, $transformer->transform($rt_expected));
    }

    public function test_upload_from_tmpfile()
    {
        $config = [
            'widget_config' => ['show_description' => false]
        ];
        $transformer = new blobTransformer($config);

        $path = midcom::get()->config->get('midcom_tempdir') . '/tmpfile-9dc7ded0fb8f77a341cda2ebd4a698df';
        file_put_contents($path, 'test');
        $time = time();

        $input = [
            'title' => 'test.txt',
            'identifier' => 'tmpfile-9dc7ded0fb8f77a341cda2ebd4a698df',
            'file' => null
        ];

        $rt_expected = new \midcom_db_attachment();
        $rt_expected->name = 'test.txt';
        $rt_expected->title = 'test.txt';
        $rt_expected->location = $path;

        $this->assertEquals($rt_expected, $transformer->reverseTransform($input));

        $t_expected = [
            'object' => $rt_expected,
            'filename' => 'test.txt',
            'description' => 'test.txt',
            'title' => 'test.txt',
            'mimetype' => '',
            'url' => '/midcom-serveattachmentguid-' . $rt_expected->guid . '/test.txt',
            'id' => 0,
            'guid' => '',
            'filesize' => 4,
            'formattedsize' => '4 Bytes',
            'lastmod' => $time,
            'isoformattedlastmod' => strftime('%Y-%m-%d %H:%M:%S', $time),
            'size_x' => null,
            'size_y' => null,
            'size_line' => null,
            'score' => 0,
            'identifier' => 'tmpfile-9dc7ded0fb8f77a341cda2ebd4a698df',
        ];

        $this->assertEquals($t_expected, $transformer->transform($rt_expected));
    }
}
