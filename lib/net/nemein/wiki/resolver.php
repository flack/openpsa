<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage resolver
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_resolver
{
    /**
     * The topic ID we're starting from
     *
     * @var int
     */
    private $_topic;

    public function __construct($topic)
    {
        $this->_topic = $topic;
    }

    public function generate_page_url(net_nemein_wiki_wikipage $wikipage)
    {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($wikipage->topic);
        if (!$node) {
            return false;
        }

        if ($wikipage->name == 'index') {
            return $node[MIDCOM_NAV_FULLURL];
        }

        return "{$node[MIDCOM_NAV_FULLURL]}{$wikipage->name}/";
    }

    private function _list_wiki_nodes($node, $prefix = '') : array
    {
        static $nap = null;
        if ($nap === null) {
            $nap = new midcom_helper_nav();
        }

        $nodes = [];

        if ($prefix == '') {
            // This is the root node
            $node_identifier = '';
            $nodes['/'] = $node;
        } else {
            $node_identifier = $prefix . trim($node[MIDCOM_NAV_URL], '/');
            $nodes[$node_identifier] = $node;
        }

        // Load subnodes uncached, since one may have just been added (by the create handler)
        $mc = midcom_db_topic::new_collector('up', $node[MIDCOM_NAV_ID]);
        $mc->add_constraint('component', '=', 'net.nemein.wiki');
        $subnodes = $mc->get_values('id');

        foreach ($subnodes as $node_id) {
            $subnode = $nap->get_node($node_id);
            if ($subnode[MIDCOM_NAV_COMPONENT] != 'net.nemein.wiki') {
                // This is not a wiki folder, skip
                continue;
            }

            $subnode_children = $this->_list_wiki_nodes($subnode, "{$node_identifier}/");
            $nodes = array_merge($nodes, $subnode_children);
        }

        return $nodes;
    }

    /**
     * Traverse hierarchy of wiki folders or "name spaces" to figure out
     * if a page exists
     *
     * @return array containing midcom_db_topic and net_nemein_wiki_wikipage objects if found
     */
    public function path_to_wikipage($path, $force_resolve_folder_tree = false, $force_as_root = false) : array
    {
        $matches = [
            'wikipage' => null,
            'folder' => null,
            'latest_parent' => null,
            'remaining_path' => $path,
        ];

        $levels = explode('/', $path);
        $path = implode('/', array_map([midcom_helper_misc::class, 'urlize'], $levels));

        midcom::get()->auth->request_sudo('net.nemein.wiki');
        if (count($levels) == 1) {
            // The linked page is in same namespace
            $nap = new midcom_helper_nav();
            $folder = $nap->get_node($this->_topic);
        } else {
            $folder = $this->resolve_namespaces($path, $matches, $levels, $force_resolve_folder_tree, $force_as_root);
        }

        if (!empty($folder)) {
            // Check if the wikipage exists
            $qb = net_nemein_wiki_wikipage::new_query_builder();
            $qb->add_constraint('name', '=', basename($path));
            $qb->add_constraint('topic', '=', $folder[MIDCOM_NAV_ID]);
            $wikipages = $qb->execute();
            if (count($wikipages) == 1) {
                $matches['wikipage'] = $wikipages[0];
            } else {
                // No page found, go to create
                $matches['folder'] = $folder;
            }
        }
        midcom::get()->auth->drop_sudo();

        return $matches;
    }

    private function resolve_namespaces($path, array &$matches, $levels, $force_resolve_folder_tree, $force_as_root)
    {
        /* We store the Wiki folder hierarchy in a static array
         that is populated only once, and even then only the
         first time we encounter a namespaced wikilink */
        static $folder_tree = [];
        if (empty($folder_tree) || $force_resolve_folder_tree) {
            $folder_tree = $this->_resolve_folder_tree($force_as_root);
        }

        if ($path[0] != '/') {
            // This is a relative path, expand to full path
            foreach ($folder_tree as $prefix => $folder) {
                if ($folder[MIDCOM_NAV_ID] == $this->_topic) {
                    $path = "{$prefix}{$path}";
                    break;
                }
            }
        }

        if (array_key_exists($path, $folder_tree)) {
            // This is a direct link to a folder, return the index article
            $matches['remaining_path'] = 'index';
            return $folder_tree[$path];
        }

        // Resolve topic from path
        $directory = dirname($path);
        if (!array_key_exists($directory, $folder_tree)) {
            // Wiki folder is missing, go to create

            // Walk path downwards to locate latest parent
            $localpath = $path;
            $matches['latest_parent'] = $folder_tree['/'];
            $missing_levels = 0;
            while (   $localpath
                   && $localpath != '/') {
                $localpath = dirname($localpath);
                $missing_levels++;

                if (array_key_exists($localpath, $folder_tree)) {
                    $matches['latest_parent'] = $folder_tree[$localpath];
                    $matches['remaining_path'] = implode('/', array_slice($levels, -$missing_levels));
                    break;
                }
            }
            return null;
        }

        $matches['remaining_path'] = substr($path, strlen($directory) + 1);
        return $folder_tree[$directory];
    }

    private function _resolve_folder_tree($force_as_root) : array
    {
        $nap = new midcom_helper_nav();

        // Traverse the NAP tree upwards until we get the root-most wiki folder
        $folder = $nap->get_node($this->_topic);

        $root_folder = $folder;
        $max = 100;
        while (   $folder[MIDCOM_NAV_COMPONENT] == 'net.nemein.wiki'
               && (($parent = $nap->get_node_uplink($folder[MIDCOM_NAV_ID])) != -1)
               && $max > 0) {
            $root_folder = $folder;
            if ($force_as_root) {
                break;
            }
            $folder = $nap->get_node($parent);
            $max--;
        }

        return $this->_list_wiki_nodes($root_folder);
    }
}
