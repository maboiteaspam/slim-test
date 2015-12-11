<?php
namespace C\Php;

use C\Renderer\RendererInterface;
use C\Symbol\SymbolResolverInterface;

class Php implements \ArrayAccess, RendererInterface
{
    /**
     * Default view variables
     *
     * @var array
     */
    protected $defaultVariables = [];

    /**
     * @var SymbolResolverInterface
     */
    protected $resolver;

    public function __construct(){}

    /**
     * @param SymbolResolverInterface $resolver
     */
    public function setResolver(SymbolResolverInterface $resolver){
        $this->resolver = $resolver;
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
        $template = $this->resolver->get($template)['absolute_path'];
        $data = array_merge($this->defaultVariables, $data);
        $fn = function () use($template, $data) {
            ob_start();
            extract($data, EXTR_SKIP);
            require ($template);
            return ob_get_clean();
        };
        return $fn();
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
        return array_key_exists($key, $this->defaultVariables);
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
        return $this->defaultVariables[$key];
    }
    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        $this->defaultVariables[$key] = $value;
    }
    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        unset($this->defaultVariables[$key]);
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
        return count($this->defaultVariables);
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
        return new \ArrayIterator($this->defaultVariables);
    }
}