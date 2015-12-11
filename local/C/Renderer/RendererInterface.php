<?php
namespace C\Renderer;

interface RendererInterface {
    public function render($symbol, $data=[]);
}
