<?php
/**
 * @package org.openpsa.qbpager
 */

/**
 * Pages QB resultsets
 *
 * @package org.openpsa.qbpager
 */
class org_openpsa_qbpager extends midcom_core_querybuilder
{
    use midcom_baseclasses_components_base;

    public $results_per_page = 25;
    public $display_pages = 10;
    public $string_next = 'next';
    public $string_previous = 'previous';
    protected $_pager_id;
    protected $_prefix = '';
    private $_current_page = 1;
    private $total;

    public function __construct(string $classname, string $pager_id)
    {
        $this->initialize($pager_id);
        parent::__construct($classname);
    }

    protected function initialize(string $pager_id)
    {
        $this->_component = 'org.openpsa.qbpager';
        if (empty($pager_id)) {
            throw new midcom_error('pager_id is not set (needed for distinguishing different instances on same request)');
        }

        $this->_pager_id = $pager_id;
        $this->_prefix = 'org_openpsa_qbpager_' . $pager_id . '_';
    }

    /**
     * Get the current page number
     */
    public function get_current_page() : int
    {
        return $this->_current_page;
    }

    /**
     * Fetch all $_GET variables
     */
    private function _get_query_string(string $page_var, int $page_number) : string
    {
        $query = [$page_var => $page_number];

        foreach ($_GET as $key => $value) {
            if (!in_array($key, [$page_var, ''])) {
                $query[$key] = $value;
            }
        }

        return '?' . http_build_query($query);
    }

    /**
     * Displays previous/next selector
     */
    function show_previousnext()
    {
        $page_count = $this->count_pages();
        //Skip the header in case we only have one page
        if ($page_count <= 1) {
            return;
        }
        //@todo Move to style element
        //TODO: "showing results (offset)-(offset+limit)
        $page_var = $this->_prefix . 'page';
        echo '<div class="org_openpsa_qbpager_previousnext">';

        if ($this->_current_page > 1) {
            $previous = $this->_current_page - 1;
            echo "\n<a class=\"previous_page\" href=\"" . $this->_get_query_string($page_var, $previous) . "\" rel=\"prev\">" . $this->_l10n->get($this->string_previous) . "</a>";
        }

        if ($this->_current_page < $page_count) {
            $next = $this->_current_page + 1;
            echo "\n<a class=\"next_page\" href=\"" . $this->_get_query_string($page_var, $next) . "\" rel=\"next\">" . $this->_l10n->get($this->string_next) . "</a>";
        }

        echo "\n</div>\n";
    }

    public function get_pages() : array
    {
        $pages = [];
        $page_count = $this->count_pages();

        if ($page_count < 1) {
            return $pages;
        }

        $page_var = $this->_prefix . 'page';
        $display_start = max(($this->_current_page - ceil($this->display_pages / 2)), 1);
        $display_end = min(($this->_current_page + ceil($this->display_pages / 2)), $page_count);

        if ($this->_current_page > 1) {
            $previous = $this->_current_page - 1;
            if ($previous > 1) {
                $pages[] = [
                    'class' => 'first',
                    'href' => $this->_get_query_string($page_var, 1),
                    'rel' => 'prev',
                    'label' => $this->_l10n->get('first'),
                    'number' => 1
                ];
            }
            $pages[] = [
                'class' => 'previous',
                'href' => $this->_get_query_string($page_var, $previous),
                'rel' => 'prev',
                'label' => $this->_l10n->get($this->string_previous),
                'number' => $previous
            ];
        }
        $page = $display_start - 1;
        while ($page++ < $display_end) {
            $href = false;
            if ($page != $this->_current_page) {
                $href = $this->_get_query_string($page_var, $page);
            }
            $pages[] = [
                'class' => 'current',
                'href' => $href,
                'rel' => false,
                'label' => $page,
                'number' => $page
            ];
        }

        if ($this->_current_page < $page_count) {
            $next = $this->_current_page + 1;
            $pages[] = [
                'class' => 'next',
                'href' => $this->_get_query_string($page_var, $next),
                'rel' => 'next',
                'label' => $this->_l10n->get($this->string_next),
                'number' => $next
            ];

            if ($next < $page_count) {
                $pages[] = [
                    'class' => 'last',
                    'href' => $this->_get_query_string($page_var, $page_count),
                    'rel' => 'next',
                    'label' => $this->_l10n->get('last'),
                    'number' => $page_count
                ];
            }
        }

        return $pages;
    }

    private function show(string $name, array $data)
    {
        $context = midcom_core_context::enter();
        $context->set_custom_key('request_data', $data);
        midcom::get()->style->prepend_component_styledir($this->_component);
        midcom::get()->style->enter_context($context);
        midcom_show_style('show_' . $name);
        midcom::get()->style->leave_context();
        midcom_core_context::leave();
    }

    /**
     * Displays page selector
     */
    public function show_pages()
    {
        $this->show('pages', ['pages' => $this->get_pages()]);
    }

    /**
     * Displays page selector as list
     */
    function show_pages_as_list()
    {
        $this->show('pages_as_list', ['pages' => $this->get_pages()]);
    }

    /**
     * Check $_REQUEST for variables and sets LIMIT and OFFSET for requested page
     */
    protected function parse_variables()
    {
        $page_var = $this->_prefix . 'page';
        if (!empty($_REQUEST[$page_var])) {
            debug_add("{$page_var} has value: {$_REQUEST[$page_var]}");
            $this->_current_page = max(1, (int) $_REQUEST[$page_var]);
        }
        $results_var = $this->_prefix . 'results';
        if (!empty($_REQUEST[$results_var])) {
            debug_add("{$results_var} has value: {$_REQUEST[$results_var]}");
            $this->results_per_page = max(1, (int) $_REQUEST[$results_var]);
        }
        if ($this->results_per_page < 1) {
            throw new LogicException('results_per_page is set to ' . $this->results_per_page);
        }
        $this->_offset = ($this->_current_page - 1) * $this->results_per_page;
        debug_add("set offset to {$this->_offset} and limit to {$this->results_per_page}");
    }

    /**
     * Returns number of total pages for query
     *
     * By default returns a number of pages without any ACL checks
     */
    public function count_pages()
    {
        $this->parse_variables();
        return ceil($this->count_unchecked() / $this->results_per_page);
    }

    public function execute() : array
    {
        $this->parse_variables();
        $this->set_limit($this->results_per_page);
        $this->set_offset($this->_offset);
        return parent::execute();
    }

    /**
     * Returns total count before pagination
     */
    public function count_unchecked() : int
    {
        if (!$this->total) {
            $doctrine_qb = $this->_query->get_doctrine();
            $offset = $doctrine_qb->getFirstResult();
            $limit = $doctrine_qb->getMaxResults();
            $doctrine_qb->setFirstResult(null);
            $doctrine_qb->setMaxResults(null);
            $this->total = $this->_query->count();
            $doctrine_qb->setFirstResult($offset);
            $doctrine_qb->setMaxResults($limit);
        }
        return $this->total;
    }
}
