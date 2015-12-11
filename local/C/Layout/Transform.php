<?php
namespace C\Layout;

use C\Stream\StreamObjectTransform;

class Transform {

    public function setDefaultData($block, $data) {
        return function (Layout $layout, StreamObjectTransform $stream) use ($block, $data) {
            $layout
                ->getOrCreate($block)
                ->setDefaultData($data);
            $stream->push($layout);
        };
    }

    public function setTemplate($block, $template, $data=[]) {
        return function (Layout $layout, StreamObjectTransform $stream) use ($block, $template, $data) {
            $layout->getOrCreate($block)
                ->setTemplate($template)
                ->setDefaultData($data);
            $stream->push($layout);
        };
    }
}
