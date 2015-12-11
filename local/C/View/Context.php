<?php
namespace C\View;

use C\Layout\Block;
use C\Misc\ArrayHelpers;
use C\View\Helper\ViewHelperInterface;

/**
 * Class Context
 * Is the context within which the view is rendered.
 * It s the $this you ll use within a view file.
 *
 * It has helpers, and a magic method __call
 * to help your developers to access helpers facilities more easily.
 *
 * @package C\View
 */
class Context {

    /**
     * @var ArrayHelpers
     */
    public $helpers;

    /**
     * @var Block
     */
    public $block;

    public function __construct () {
        $this->helpers = new ArrayHelpers();
    }

    /**
     * @param ViewHelperInterface $helper
     * @deprecated use $helpers directly
     */
    public function addHelper (ViewHelperInterface $helper) {
        $this->helpers->append($helper);
    }

    /**
     * @param ViewHelperInterface $helper
     * @deprecated use $helpers directly
     */
    public function prependHelper (ViewHelperInterface $helper) {
        $this->helpers->prepend($helper);
    }

    /**
     * Set the next block top render on each helpers.
     *
     * @param Block $block
     */
    public function setBlockToRender (Block $block) {
        $this->block = $block;
        foreach($this->helpers as $helper) {
            /* @var ViewHelperInterface $helper */
            $helper->setBlockToRender($block);
        }
    }

    /**
     * Call a method on helpers.
     * Stops after first valid helper found.
     *
     * @param $method
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args){
        foreach($this->helpers as $helper) {
            if (method_exists($helper, $method)) {
                return call_user_func_array([$helper, $method], $args);
            }
        }
        throw new \Exception("unknown function $method");
    }

}
