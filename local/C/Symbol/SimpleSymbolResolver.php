<?php
namespace C\Symbol;

class SimpleSymbolResolver implements SymbolResolverInterface{
    public $basePath;
    public function __construct ($basePath) {
            $this->basePath = $basePath;
    }
    public function get ($symbol) {
        return [
            'absolute_path' => "{$this->basePath}{$symbol}"
        ];
    }
}
