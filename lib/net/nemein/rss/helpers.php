<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package net.nemein.rss
 */
class net_nemein_rss_helpers
{
    /**
     * Add default RSS config options to component config schema.
     *
     * Used with array_merge
     *
     * @return array of datamanager schema fields
     */
    public static function default_rss_config_schema_fields(string $component) : array
    {
        return [
            'rss_enable' => [
                'title' => midcom::get()->i18n->get_string('rss_enable', 'net.nemein.rss'),
                'storage' => [
                    'location' => 'configuration',
                    'domain' => $component,
                    'name' => 'rss_enable',
                ],
                'type' => 'select',
                'type_config' => [
                    'options' => [
                        '' => 'default setting',
                        '1' => 'yes',
                        '0' => 'no',
                    ],
                ],
                'widget' => 'select',
                'start_fieldset' => [
                    'title' =>  midcom::get()->i18n->get_string('rss output settings', 'net.nemein.rss'),
                ],
            ],
            'rss_count' => [
                'title' => midcom::get()->i18n->get_string('rss_count', 'net.nemein.rss'),
                'storage' => [
                    'location' => 'configuration',
                    'domain' => $component,
                    'name' => 'rss_count',
                ],
                'type' => 'number',
                'widget' => 'text',
            ],
            'rss_title' => [
                'title' => midcom::get()->i18n->get_string('rss_title', 'net.nemein.rss'),
                'storage' => [
                    'location' => 'configuration',
                    'domain' => $component,
                    'name' => 'rss_title',
                ],
                'type' => 'text',
                'widget' => 'text',
            ],
            'rss_description' => [
                'title' => midcom::get()->i18n->get_string('rss_description', 'net.nemein.rss'),
                'storage' => [
                    'location' => 'configuration',
                    'domain' => $component,
                    'name' => 'rss_description',
                ],
                'type' => 'text',
                'widget' => 'text',
            ],
            'rss_webmaster' => [
                'title' => midcom::get()->i18n->get_string('rss_webmaster', 'net.nemein.rss'),
                'storage' => [
                    'location' => 'configuration',
                    'domain' => $component,
                    'name' => 'rss_webmaster',
                ],
                'type' => 'text',
                'widget' => 'text',
            ],
            'rss_language' => [
                'title' => midcom::get()->i18n->get_string('rss_language', 'net.nemein.rss'),
                'storage' => [
                    'location' => 'configuration',
                    'domain' => $component,
                    'name' => 'rss_language',
                ],
                'type' => 'text',
                'widget' => 'text',
            ],
        ];
    }
}
