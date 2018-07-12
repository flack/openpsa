<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use Symfony\Component\Form\AbstractRendererEngine;
use Symfony\Component\Form\FormView;

/**
 * Experimental renderer engine
 */
class engine extends AbstractRendererEngine
{
    /**
     * {@inheritdoc}
     */
    public function renderBlock(FormView $view, $resource, $blockName, array $variables = [])
    {
        $data = array_merge($view->vars, $variables);
        return $resource->$blockName($view, $data);
    }

    public function setTheme(FormView $view, $themes, $useDefaultThemes = true)
    {
        $this->themes = [];
        $this->resources = [];
        parent::setTheme($view, $themes, $useDefaultThemes);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadResourceForBlockName($cacheKey, FormView $view, $blockName)
    {
        // Check each theme whether it contains the searched block
        if (isset($this->themes[$cacheKey])) {
            for ($i = count($this->themes[$cacheKey]) - 1; $i >= 0; --$i) {
                if (is_callable([$this->themes[$cacheKey][$i], $blockName])) {
                    $this->resources[$cacheKey][$blockName] = $this->themes[$cacheKey][$i];
                    return true;
                }
            }
        }

        // If we did not find anything in the themes of the current view, proceed
        // with the themes of the parent view
        if ($view->parent) {
            $parentCacheKey = $view->parent->vars[self::CACHE_KEY_VAR];

            if (!isset($this->resources[$parentCacheKey][$blockName])) {
                $this->loadResourceForBlockName($parentCacheKey, $view->parent, $blockName);
            }

            // If a template exists in the parent themes, cache that template
            // for the current theme as well to speed up further accesses
            if ($this->resources[$parentCacheKey][$blockName]) {
                $this->resources[$cacheKey][$blockName] = $this->resources[$parentCacheKey][$blockName];

                return true;
            }
        }

        // Cache that we didn't find anything to speed up further accesses
        $this->resources[$cacheKey][$blockName] = false;

        return false;
    }
}
