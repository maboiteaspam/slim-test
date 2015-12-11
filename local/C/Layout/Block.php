<?php
namespace C\Layout;

use C\Renderer\RendererInterface;
use C\TagableResource\TagedResource;
use C\TagableResource\TagableResourceInterface;
use C\TagableResource\UnwrapableResourceInterface;

/**
 * Class Block
 * represents a render-able element of a layout.
 * It has a template, options, data(s), meta, and can get attached assets.
 * It is being executed to render a portion of the whole page.
 * It can declare and render sub blocks.
 * It has an id, its id is unique across the whole render operation.
 *
 *
 * @package C\Layout
 */
class Block implements TagableResourceInterface{

    /**
     * Unique id of the block.
     *
     * @var string
     */
    public $id;
    /**
     * The HTML content of the block.
     * If it is set, template option is ignored for rendering.
     *
     * @var string
     */
    public $body;
    /**
     * Id of the parent block which rendered the current instance.
     *
     * @var string
     */
    protected $parentId;

    /**
     * Yet resolved or not.
     *
     * @var bool
     */
    public $resolved = false;

    /**
     * a mixin array of options
     * @var array
     */
    public $options = [
        // template
    ];
    /**
     * A mixin array of data to inject
     * in the view as regular php variable.
     * @var array
     */
    public $data = [];
    /**
     * An array of target => (file) assets attached to this block.
     * [
     *  header => [ assets1, assets2, ],
     *  footer => [ assets1, assets2, ],
     * ]
     *
     * @var array
     */
    public $assets = [];
    /**
     * An array of target => (inline) assets attached to this block.
     * [
     *  header => [ inlineAssets1, inlineAssets2, ],
     *  footer => [ inlineAssets1, inlineAssets2, ],
     * ]
     *
     * @var array
     */
    public $inline = [];
    /**
     * An array of assets requirements such
     * vendor-asset-alias:semver => target asset block id
     * [
     *  jquery:2.x => footer,
     *  normalize.css:1.x => header,
     * ]
     *
     * @var array
     */
    public $requires = [];

    /**
     * @deprecated
     * @todo to remove
     * @var array
     */
    public $intl = [];

    /**
     * A mixin array of meta to attach to the block.
     *
     * @var array
     */
    public $meta = [
        'from' => false,
        'etag' => '',
    ];

    /**
     * holds a list of blocks this
     * block has attempted to render.
     *
     * @var array
     */
    public $displayed_blocks = [
        /* [array,of,block,id,displayed]*/
    ];

    // this are runtime data to help debug and so on.
    /**
     * Stack should be a stack trace representation.
     * This should help to find out which transforms
     * made which changes at which location in the code.
     *
     * @notes to be improved...
     *
     * @var array
     */
    public $stack = [];


    /**
     * @param $id string
     */
    public function __construct($id) {
        $this->id = $id;
    }

    /**
     * clear some settings of the block.
     *
     * $what can be one of
     * - all
     * - template
     * - data
     * - options
     * - assets
     * - meta
     *
     * You can also pass in a string such
     * template, options
     * to clear multiple elements at once.
     *
     * @param string $what
     */
    public function clear ($what='all') {
        if ($what==='all' || $what==='') {
            $this->body = "";
            $this->data = [];
            $this->assets = [];
            $this->options = [
                'template' => ''
            ];
        } else {
            if (strpos($what, "template")!==false) {
                $this->options['template'] = '';
            }
            if (strpos($what, "data")!==false) {
                $this->data = [];
            }
            if (strpos($what, "options")!==false) {
                $this->options = ["template" => ""];
            }
            if (strpos($what, "assets")!==false) {
                $this->assets = [];
            }
            if (strpos($what, "meta")!==false) {
                $this->meta = [];
            }
        }
    }

    /**
     * Resolves the view within the block context.
     *
     * if template is defined,
     * it s included within the context of ViewContext object.
     *
     * if body is not empty, it is used as the rendered result,
     * in that case, template value is ignored.
     *
     *
     * @param RendererInterface $renderer
     * @throws \Exception
     */
    public function resolve (RendererInterface $renderer){
        if (!$this->resolved) {
            $this->resolved = true;
            $template   = $this->getTemplate();
            $data       = $this->unwrapData(['block']);
            $this->body = $renderer->render($template, $data);
        }
    }

    /**
     * $template can be a file path
     * or a module file target (My/Module:/path/file.ext)
     *
     * @param $template
     * @return $this
     */
    public function setTemplate($template){
        $this->options['template'] = $template;
        return $this;
    }

    /**
     * The template value of the block.
     * @return bool|string
     */
    public function getTemplate(){
        $template = false;
        if (isset($this->options['template']))
            if ($this->options['template'])
                $template = $this->options['template'];
        return $template;
    }

    /**
     * Set the parent block id whom display that instance.
     * @param $parentId string
     */
    public function setParentRenderBlock($parentId){
        $this->parentId = $parentId;
    }

    /**
     * Get teh parent block id whom displayed that instance.
     *
     * @return string
     */
    public function getParentBlockId(){
        return $this->parentId;
    }


    #region scripts / css
    /**
     * An array of assets to move to top
     * [
     *  asset1,
     *  asset2,
     * ]
     *
     * @var array
     */
    public $firstAssets = [];
    /**
     * Add inline asset content of JS / CSS to
     * one of available $target block
     * $target is one of first/head/foot/last
     *
     * @param $target
     * @param $type
     * @param $content
     */
    public function addInline($target, $type, $content){
        if (!isset($this->inline[$target]))
            $this->inline[$target] = [];
        $this->inline[$target][] = [
            'type'      => $type,
            'content'   => $content,
        ];
    }

    /**
     * Get inline asset contents.
     *
     * @return array
     */
    public function getInline(){
        return $this->inline;
    }

    /**
     * @return array
     */
    public function getAssets(){
        return $this->assets;
    }

    /**
     * @return array
     */
    public function getFirstAssets(){
        return $this->firstAssets;
    }

    /**
     * Attach a new asset to this block.
     * $assets is an array such
     * [
     *  'target'=>[
     *      assets1.css,
     *      assets2.jpeg,
     *  ]
     * ]
     *
     * target is a block id relate to your base template.
     * It is probably something of
     * - template_head_css
     * - page_head_css
     * - template_head_js
     * - page_head_js
     * ----
     * - template_footer_css
     * - page_footer_css
     * - template_footer_js
     * - page_footer_js
     *
     * @param array $assets
     * @param bool|false $first
     */
    public function addAssets($assets=[], $first=false){
        foreach($assets as $targetAssetGroupName => $files) {
            if(!isset($this->assets[$targetAssetGroupName]))
                $this->assets[$targetAssetGroupName] = [];
            $this->assets[$targetAssetGroupName] = array_merge($this->assets[$targetAssetGroupName], $files);
            if ($first) $this->firstAssets = array_merge($this->firstAssets, $files);
        }
    }

    /**
     * Add an asset requirement on the block
     *
     * $require is expected to be of the form
     * vendor-asset-alias:semver
     *
     * @param $requires
     */
    public function addAssetRequire($requires){
        if (is_string($requires)) {
            if (!in_array($requires, $this->requires)) $this->requires[] = $requires;
        }
        else $this->requires = array_merge($this->requires, $requires);
    }
    #endregion

    /**
     * Compute attached resources to that block
     * as a resource tag object.
     *
     * @return TagedResource
     * @throws \Exception
     */
    public function getTaggedResource (){
        $res = new TagedResource();

        if ($this->resolved) {
            $res->addResource($this->id);
            if (isset($this->options['template'])) {
                $template = $this->options['template'];
                if ($template) {
                    $res->addResource($template, 'template');
                }
            }
            foreach($this->assets as $target=>$assets) {
                foreach($assets as $i=>$asset){
                    if ($asset) {
                        $res->addResource($target);
                        $res->addResource($i);
                        $res->addResource($asset, 'asset');
                    }
                }
            }
            foreach($this->intl as $i=>$intl) {
                $res->addResource($i);
                $res->addResource($intl, 'intl');
            }

            foreach($this->data as $name => $data){
                if ($data instanceof TagableResourceInterface) {
                    $res->addTaggedResource($data->getTaggedResource(), $name);
                } else {
                    $res->addResource($data, 'po', $name);
                }
            }
        }

        return $res;
    }

    /**
     * Get all unwrapped data attached to this block.
     *
     * $notNames is an array of string of the data
     * to exclude.
     *
     * @param array $notNames
     * @return array
     * @throws \Exception
     */
    public function unwrapData ($notNames=[]) {
        $unwrapped = [];
        foreach($this->data as $name => $data){
            if (!in_array($name, $notNames, true)) {
                $unwrapped[$name] = $this->getData($name);
            } else {
                throw new \Exception("Forbidden data name '$name' is forbidden and can t be overwritten");
            }
        }
        return $unwrapped;
    }

    /**
     * Get a specific unwrapped data
     *
     * @param $name
     * @return mixed
     */
    public function getData ($name) {
        $data = $this->data[$name];
        if ($data instanceof UnwrapableResourceInterface) {
            $data = $data->unwrap();
        }
        return $data;
    }

    /**
     * Sets default data of a block.
     * It won t override existing data.
     *
     * @param array $data
     * @return $this
     */
    public function setDefaultData($data=[]){
        $this->data = array_merge($data, $this->data);
        return $this;
    }

    /**
     * Sets default meta of a block.
     * It won t override existing meta.
     *
     * @param array $meta
     * @return $this
     */
    public function setDefaultMeta($meta=[]){
        $this->meta = array_merge($meta, $this->meta);
        return $this;
    }

    /**
     * Returns the list of blocks
     * this view has tried to display.
     *
     * @return array
     */
    public function getDisplayedBlocksId () {
        $displayed = [];
        foreach ($this->displayed_blocks as $d) {
            $displayed[] = $d['id'];
        }
        return $displayed;
    }

    /**
     * Register the id of a block
     * this view has displayed.
     *
     * @param $id
     * @param bool $shown
     */
    public function registerDisplayedBlock($id, $shown=true) {
        $this->displayed_blocks[] = ["id"=>$id, "shown"=>$shown];
    }

    /**
     * Update the list of displayed block
     * to register a new id after $afterId.
     *
     * @param $afterId
     * @param $id
     * @param bool $shown
     */
    public function registerDisplayedBlockAfter($afterId, $id, $shown=true) {
        $index = array_keys($this->getDisplayedBlocksId(), $afterId);
        if (count($index)) {
            $index = $index[0];
            array_splice($this->displayed_blocks, $index+1, 0, [["id"=>$id, "shown"=>$shown]]);
        } else {
            $this->displayed_blocks[] = ["id"=>$id, "shown"=>$shown];
        }
    }

    /**
     * Update the list of displayed block to register a new id before $beforeId.
     *
     * @param $beforeId
     * @param $id
     * @param bool $shown
     */
    public function registerDisplayedBlockBefore($beforeId, $id, $shown=true) {
        $index = array_keys($this->getDisplayedBlocksId(), $beforeId);
        if (count($index)) {
            $index = $index[0];
            array_splice($this->displayed_blocks, $index, 0, [["id"=>$id, "shown"=>$shown]]);
        } else {
            array_unshift($this->displayed_blocks, ["id"=>$id, "shown"=>$shown]);
        }
    }
}
