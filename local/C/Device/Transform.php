<?php
namespace C\Device;

use C\Stream\StreamObjectTransform;
use Psr\Http\Message\RequestInterface;

class Transform {

    /**
     * @var RequestInterface
     */
    public $request;
    public function setRequest (RequestInterface $request) {
        $this->request = $request;
    }

    public function forMobile() {
        $request = $this->request;
        return function ($chunk, StreamObjectTransform $stream) use ($request) {
            $ua = $request->getHeaderLine('user-agent');
            if (strpos(strtolower($ua), "iphone")!==false) $stream->push($chunk);
        };
    }

    public function forDesktop() {
        $request = $this->request;
        return function ($chunk, StreamObjectTransform $stream) use ($request) {
            $ua = $request->getHeaderLine('User-agent');
            if (strpos(strtolower($ua), "iphone")===false) $stream->push($chunk);
        };
    }

}