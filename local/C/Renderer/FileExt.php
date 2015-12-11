<?php
namespace C\Renderer;

class FileExt implements \ArrayAccess, RendererInterface
{
    protected $extRenderer = [];

    public function __construct(){}

    /**
     * @param $ext
     * @param RendererInterface $renderer
     */
    public function register($ext, RendererInterface $renderer) {
        $this->extRenderer[$ext] = $renderer;
    }

    /**
     * Fetch rendered template
     *
     * @param  string $template Template pathname relative to templates directory
     * @param  array  $data     Associative array of template variables
     *
     * @return string
     */
    public function fetch($template, $data = [])
    {
        $extension = pathinfo($template, PATHINFO_EXTENSION);
        return $this->extRenderer[$extension]->fetch($template, $data);
    }
    /**
     * Output rendered template
     *
     * @param  string $template Template pathname relative to templates directory
     * @param  array $data Associative array of template variables
     * @return string
     */
    public function render($template, $data = [])
    {
        return $this->fetch($template, $data);
    }

    /********************************************************************************
     * ArrayAccess interface
     *******************************************************************************/
    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return reset($this->extRenderer)->offsetExists($key);
    }
    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return reset($this->extRenderer)->offsetGet($key);
    }
    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        foreach ($this->extRenderer as $renderer) {
            $renderer[$key] = $value;
        }
    }
    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        foreach ($this->extRenderer as $renderer) {
            unset($renderer[$key]);
        }
    }
    /********************************************************************************
     * Countable interface
     *******************************************************************************/
    /**
     * Get number of items in collection
     *
     * @return int
     */
    public function count()
    {
        return reset($this->extRenderer)->count();
    }
    /********************************************************************************
     * IteratorAggregate interface
     *******************************************************************************/
    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return reset($this->extRenderer)->getIterator();
    }
}