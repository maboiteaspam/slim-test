<?php
namespace C\Twig;

use C\Renderer\RendererInterface;

class Twig implements \ArrayAccess, RendererInterface
{
    /**
     * Twig loader
     *
     * @var \Twig_LoaderInterface
     */
    protected $loader;
    /**
     * Twig environment
     *
     * @var \Twig_Environment
     */
    protected $environment;
    /**
     * Default view variables
     *
     * @var array
     */
    protected $defaultVariables = [];
    /********************************************************************************
     * Constructors and service provider registration
     *******************************************************************************/
    /**
     * Create new Twig view
     *
     * @param string $path     Path to templates directory
     * @param array  $settings Twig environment settings
     */
    public function __construct($path, $settings = [])
    {
        $this->loader = new \Twig_Loader_Filesystem($path);
        $this->environment = new \Twig_Environment($this->loader, $settings);
    }
    /********************************************************************************
     * Methods
     *******************************************************************************/
    /**
     * Proxy method to add an extension to the Twig environment
     *
     * @param \Twig_ExtensionInterface $extension A single extension instance or an array of instances
     */
    public function addExtension(\Twig_ExtensionInterface $extension)
    {
        $this->environment->addExtension($extension);
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
        $data = array_merge($this->defaultVariables, $data);
        return $this->environment->loadTemplate($template)->render($data);
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
     * Accessors
     *******************************************************************************/
    /**
     * Return Twig loader
     *
     * @return \Twig_LoaderInterface
     */
    public function getLoader()
    {
        return $this->loader;
    }
    /**
     * Return Twig environment
     *
     * @return \Twig_Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
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