<?php

namespace C\Layout;

/**
 * Class RegistryBlock
 *
 * @package C\Layout
 */
class RegistryBlock{

    public $blocks = [];

    /**
     * Record a block on the registry.
     *
     * @param $id
     * @param Block $block
     */
    public function set ($id, Block $block){
        $this->blocks[$id] = $block;
    }

    /**
     * @param $id
     * @return Block
     */
    public function get ($id){
        if( isset($this->blocks[$id]))
            return $this->blocks[$id];
        return null;
    }

    /**
     * Returns parent block if it exists.
     *
     * @param $id
     * @return Block|null
     */
    public function getParent ($id){
        foreach ($this->blocks as $block) {
            /* @var $block Block */
            if (in_array($id, $block->getDisplayedBlocksId()))
                return $block;
        }
        return null;
    }

    /**
     * @param $id
     * @return bool
     */
    public function has ($id){
        return array_key_exists($id, $this->blocks);
    }

    /**
     * True if it succeeds.
     *
     * @param $id
     * @return bool
     */
    public function remove ($id){
        if (isset($this->blocks[$id])) {
            unset($this->blocks[$id]);
            return true;
        }
        return false;
    }

    /**
     * Iterate blocks.
     *
     * @param $fn
     */
    public function each ($fn){
        foreach ($this->blocks as $id=>$block) {
            $fn($block, $id);
        }
    }
}
