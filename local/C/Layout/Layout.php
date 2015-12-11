<?php

namespace C\Layout;

use C\Renderer\RendererInterface;
use st\Event\EventDispatcherInterface;
use im\Event\GenericEvent;
use C\Misc\Utils;

/**
 * Class Layout
 * represents the layout object with block definitions
 * to render a page.
 *
 * It can hold a list of block via is BlockRegistry
 * It can add, or remove blocks.
 * It can render the layout appropriately by traversing them in cascade.
 * It can provide a tag resource object for http caching.
 *
 * Block content are produced in two steps : resolve then render.
 *
 * Resolve will invoke the view and process it,
 * but it won t resolve sub blocks.
 * The resulting content will contains placeholder for each sub block to display.
 *
 * Render operations, invoke the resolve operation of the block,
 * It then replace placeholders of each block with their rendered content.
 *
 * @package C\Layout
 */
class Layout{

    /**
     * Layout's id
     *
     * @var string
     */
    public $id;
    /**
     * Layout's description
     *
     * @var string
     */
    public $description;

    /**
     * id of the block to start display from
     *
     * @var string
     */
    public $block;

    /**
     * @var RendererInterface
     */
    public $renderer;

    /**
     * @var RegistryBlock
     */
    public $registry;

    /**
     * enable or disable debug tools.
     *
     * @var bool
     */
    public $debugEnabled;

    /**
     * The layout object is event-able.
     *
     * @var EventDispatcherInterface
     */
    public $dispatcher;

    /**
     * The tag resource object
     * to sign a layout object appropriately.
     *
     * @var TagedResource
     */
    public $tagResource;
    /**
     * Use the global resource tag array
     * to inject global values
     * to the tag resource object.
     *
     * @var array
     */
    public $globalResourceTags = [];

    /**
     * The default options applied
     * by default to each new block.
     *
     * @var array
     */
    public $defaultOptions = [
        'options'=>[],
        'meta'=>[],
    ];

    /**
     *
     */
    public function __construct () {
        $this->registry = new RegistryBlock();
        $this->block = 'root';
    }

    #region initialization
    public function setDispatcher (EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function enableDebug ($enabled) {
        $this->debugEnabled = $enabled;
    }

    /**
     * The ID of the layout.
     *
     * @param $layoutId
     */
    public function setId ($layoutId) {
        $this->id = $layoutId;
    }

    /**
     * A description of the layout for human readers.
     *
     * @param $description
     */
    public function setDescription ($description) {
        $this->description = $description;
    }

    /**
     * @param RendererInterface $renderer
     */
    public function setRenderer (RendererInterface $renderer) {
        $this->renderer = $renderer;
    }
    #endregion

    #region block rendering
    /**
     * Resolve the block to produce it s content.
     *
     * Returns the content of the block
     * to be rendered within the layout.
     *
     * It emits those event in order,
     *      before_block_resolve
     *      before_resolve_[%blockId%]
     *          --> resolve <--
     *      after_resolve_[%blockId%]
     *      after_block_resolve
     *
     * @param $id
     * @return Block
     */
    public function resolve ($id){
        $parentBlock = null;
        $this->emit('before_block_resolve', $id);
        $this->emit('before_resolve_' . $id);
        $block = $this->registry->get($id);
        if ($block) {
            $block->resolve($this->renderer);
            foreach($block->getDisplayedBlocksId() as $displayedBlockId) {
                $displayedBlock = $this->registry->get($displayedBlockId);
                if ($displayedBlock) $displayedBlock->setParentRenderBlock($block->id);

            }
        }
        $this->emit('after_block_resolve', $id);
        $this->emit('after_resolve_' . $id);
        return $block;
    }

    /**
     * Renders a block and returns its content.
     * It it tries to display sub block not yet resolved,
     * it will resolve them JIT.
     *
     * Returns the final content of the block.
     *
     *      before_block_render
     *      before_render_[%blockId%]
     *          --> render <--
     *      after_render_[%blockId%]
     *      after_block_render
     *
     * @param $id
     * @return mixed|string
     */
    public function getContent ($id) {
        $body = "";
        $block = $this->registry->get($id);
        if ($block) {
            if(!$block->resolved) {
                $block->resolve($this->renderer);
            }
            $this->emit('before_block_render', $id);
            $this->emit('before_render_' . $id);
            $body = $block->body;
            foreach($block->getDisplayedBlocksId() as $displayedBlockId) {
                $body = str_replace("<!-- placeholder for block $displayedBlockId -->",
                    $this->getContent($displayedBlockId),
                    $body);
            }
            $block->body = $body;
        } else {
            $this->emit('before_block_render', $id);
            $this->emit('before_render_' . $id);
        }
        $this->emit('after_render_' . $id);
        $this->emit('after_block_render', $id);
        if ($block) {
            $body = $block->body;
        }
        return $body;
    }

    /**
     * @deprecated Resolves all blocks of the layout.
     * Most of the time it is not suitable.
     *
     */
    public function resolveAllBlocks (){
        $layout = $this;
        $this->registry->each(function ($block) use($layout) {
            $layout->resolve($block->id);
        });
    }

    /**
     * Resolve blocks in cascade given a starting block ID.
     * This prevents to render and process block which are not part of the layout original start block.
     *
     * @param $startBlock
     */
    public function resolveInCascade ($startBlock){
        $layout = $this;
        $layout->resolve($startBlock);
        $block = $this->get($startBlock);
        if ($block) {
            foreach ($block->getDisplayedBlocksId() as $id) {
                $this->resolveInCascade($id);
            }
        }
    }

    /**
     * Renders a layout given it s starting block.
     * Returns the content of the layout.
     *
     *      before_layout_resolve
     *          --> resolve blocks <--
     *      after_layout_resolve
     *
     *      before_layout_render
     *          --> render blocks <--
     *      after_layout_render
     *
     *
     * @return string
     */
    public function render (){
        $this->emit('before_layout_resolve');
        $this->resolveInCascade($this->block);
        $this->emit('after_layout_resolve');

        $this->emit('before_layout_render'); // mhh
        $this->getContent ($this->block);
        $this->emit('after_layout_render');

        return $this->getRoot()->body;
    }

    /**
     * echo the content of a block.
     * It always resolve / render a block and it s tree.
     *
     * @param $id
     */
    public function displayBlock ($id){
        echo $this->getContent($id);
    }
    #endregion

    #region block manipulation
    /**
     * Get the root block of the layout.
     * The block from which the render process in cascade occurs.
     *
     * @return Block
     */
    public function getRoot (){
        return $this->get($this->block);
    }

    /**
     * @return array
     */
    public function getBlocks (){
        return $this->registry->blocks;
    }

    /**
     * @param $id
     * @return Block
     */
    /**
     * Get a block object given it s ID.
     * If it does not exists, it returns null.
     *
     * @param $id
     * @return Block
     */
    public function get ($id){
        return $this->registry->get($id);
    }

    /**
     * Configure a new block with
     * the layout default block options.
     *
     * @param $block
     * @param array $options
     */
    function configureBlock ($block, $options=[]){
        foreach($this->defaultOptions as $n=>$v) {
            if (isset($options[$n]) && is_array($options[$n]) && is_array($v)) {
                $options[$n] = array_merge($options[$n], $v);
            }
        }
        foreach($options as $n=>$v) {
            if (isset($block->{$n}) && is_array($block->{$n}) && is_array($v)) {
                $block->{$n} = array_merge($block->{$n}, $v);
            } else {
                $block->{$n} = $v;
            }
        }
    }

    /**
     * Gets or creates a block configuration.
     *
     * @param $id
     * @return Block
     */
    function getOrCreate ($id){
        if (!($id instanceof Block)) {
            $block = $this->registry->get($id);
            if (!$block) {
                $block = new Block($id);
                $this->registry->set($id, $block);
                if ($this->debugEnabled) {
                    $block->stack = Utils::getStackTrace();
                }
            }
        } else {
            $block = $id;
        }
        return $block;
    }

    /**
     * Configure the given block id with provided options.
     *
     * @param $id
     * @param array $options
     * @return Block
     */
    function set ($id, $options=[]){
        $block = $id instanceof Block ? $id : $this->getOrCreate($id) ;
        $this->configureBlock($block, $options);
        return $block;
    }

    /**
     * Configure multiple blocks at once.
     *
     * @param array $options
     */
    function setMultiple($options=[]){
        foreach($options as $target => $opts) {
            $this->set($target, $opts);
        }
    }

    /**
     * Remove given block id from the registry.
     *
     * @param $id
     */
    function remove($id){
        $this->registry->remove($id);
    }
    #endregion

    #region bulk block manipulation
    /**
     * @deprecated.
     *
     * @param $pattern
     */
    function keepOnly($pattern){
        $blocks = $this->registry->blocks;
        foreach($blocks as $block) {
            if (!preg_match($pattern, $block->id)) {
                $this->registry->remove($block->id);
            }
        }
    }
    #endregion


    #region event dispatching
    /**
     * emit given ID.
     * Arguments will be forwarded.
     *
     * @param $id
     */
    public function emit ($id){
        $args = func_get_args();
        $id = array_shift($args);
        $event = new GenericEvent($id, $args);
        if ($this->dispatcher)
            $this->dispatcher->dispatch($id, $event);
    }
    /**
     * @param string   $eventName The event to listen on
     * @param callable $listener  The listener
     * @param int      $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function on ($eventName, $listener, $priority=0){
        if ($this->dispatcher)
            call_user_func_array([$this->dispatcher, 'addListener'], func_get_args());
    }
    public function off ($id, $fn){
        if ($this->dispatcher)
            call_user_func_array([$this->dispatcher, ' removeListener'], func_get_args());
    }

    /**
     * Event emitted once the controller loose
     * its hand on the layout and will stop to modify it.
     * @param $fn
     */
    public function onControllerBuildFinish ($fn){
        $layout = $this;
        $this->on('controller_build_finish', function($event) use($layout, $fn){
            $fn($event, $layout, $event->getArgument(0));
        });
    }

    /**
     * Event emitted once all transform operations are finished.
     * It should occur before response forging.
     *
     * @param $fn
     */
    public function onLayoutBuildFinish ($fn){
        $layout = $this;
        $this->on('layout_build_finish', function($event) use($layout, $fn){
            $fn($event, $layout, $event->getArgument(0));
        });
    }
    /**
     * @param callable $listener  The listener
     * @param int      $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function beforeRender ($listener, $priority=0) {
        $layout = $this;
        $this->on('before_layout_render', function($event) use($layout, $listener){
            $listener($event, $layout);
        }, $priority);
    }
    /**
     * @param callable $listener  The listener
     * @param int      $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function afterRender ($listener, $priority=0) {
        $layout = $this;
        $this->on('after_layout_render', function($event) use($layout, $listener){
            $listener($event, $layout);
        }, $priority);
    }
    public function beforeRenderAnyBlock ($fn){
        $layout = $this;
        $this->on('before_block_render', function($event) use($layout, $fn){
            /* @var $event \st\Event\EventInterface */
            $fn($event, $layout, $event->getArgument(0));
        });
    }
    public function afterRenderAnyBlock ($fn){
        $layout = $this;
        $this->on('after_block_render', function($event) use($layout, $fn){
            /* @var $event \st\Event\EventInterface */
            $fn($event, $layout, $event->getArgument(0));
        });
    }
    public function beforeBlockRender ($id, $fn){
        $layout = $this;
        $this->on('before_render_'.$id, function($event) use($layout, $id, $fn){
            $fn($event, $layout, $id);
        });
    }
    public function afterBlockRender ($id, $fn){
        $layout = $this;
        $this->on('after_render_'.$id, function($event) use($layout, $id, $fn){
            $fn($event, $layout, $id);
        });
    }

    public function beforeResolve ($listener, $priority=0) {
        $layout = $this;
        $this->on('before_layout_resolve', function($event) use($layout, $listener){
            $listener($event, $layout);
        }, $priority);
    }
    public function afterResolve ($listener, $priority=0) {
        $layout = $this;
        $this->on('after_layout_resolve', function($event) use($layout, $listener){
            $listener($event, $layout);
        }, $priority);
    }
    public function beforeResolveAnyBlock ($fn){
        $layout = $this;
        $this->on('before_block_resolve', function($event) use($layout, $fn){
            /* @var $event \st\Event\EventInterface */
            $fn($event, $layout, $event->getArgument(0));
        });
    }
    public function afterResolveAnyBlock ($fn){
        $layout = $this;
        $this->on('after_block_resolve', function($event) use($layout, $fn){
            /* @var $event \st\Event\EventInterface */
            $fn($event, $layout, $event->getArgument(0));
        });
    }
    public function beforeBlockResolve ($id, $fn){
        $layout = $this;
        $this->on('before_resolve_'.$id, function($event) use($layout, $id, $fn){
            $fn($event, $layout, $id);
        });
    }
    public function afterBlockResolve ($id, $fn){
        $layout = $this;
        $this->on('after_resolve_'.$id, function($event) use($layout, $id, $fn){
            $fn($event, $layout, $id);
        });
    }
    #endregion




    #region reference-able assets
    /**
     * @var array
     */
    public $referencedAssets = [];

    /**
     * @return array
     */
    public function getReferencedAssets () {
        return $this->referencedAssets;
    }

    /**
     * @param $alias
     * @param $path
     * @param $version
     * @param $target
     * @param bool|false $first
     * @param array $wellKnownRequires
     */
    public function registerAsset ($alias,
                                   $path,
                                   $version,
                                   $target,
                                   $first=false,
                                   $wellKnownRequires=[]) {
        $this->referencedAssets["$alias:$version"] = [
            $alias, $path, $version, $target, $first, $wellKnownRequires
        ];
    }
    #endregion

    #region block iteration
    /**
     * Traverse block given their structure.
     * This traverse implementation can occur only after the layout has been rendered.
     * It will traverse the root block, and all its displayed blocks.
     * And so on for each block found.
     * It helps to escape non displayed block from your iteration.
     *
     * @param Block $block
     * @param Layout $layout
     * @param $then
     * @param null $path
     */
    function traverseBlocksWithStructure (Block $block, Layout $layout, $then, $path=null){
        $parentId = $block->id;
        if ($path===null) {
            $path = "/$parentId";
            $then($parentId, null, $path, ['block'=>$block,'shown'=>true,'exists'=>true]);
        }
        foreach ($block->displayed_blocks as $displayed_block) {

            $subId = $displayed_block['id'];
            $sub = $layout->get($subId);
            if ($sub) $subId = $sub->id;

            $then($subId,
                $parentId,
                "$path/$subId",
                ['block'=>$sub,'shown'=>$displayed_block['shown'],'exists'=>!!$sub]);

            if ($sub) $this->traverseBlocksWithStructure($sub, $layout, $then, "$path/$subId");
        }
    }

    /**
     * Given a block ID, retrieve all displayed block objects.
     * @param $blockId
     * @return array
     */
    public function getDisplayedBlocksId($blockId) {
        $displayed = [];
        $block = $this->get($blockId);
        $displayed = array_merge($displayed, $block->getDisplayedBlocksId());
        foreach ($displayed as $d) {
            $displayed = array_merge($displayed, $this->getDisplayedBlocksId($d));
        }
        return ($displayed);
    }
    #endregion
}
