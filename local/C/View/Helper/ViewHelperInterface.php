<?php
namespace C\View\Helper;

use C\Layout\Block;

interface ViewHelperInterface {

    public function setBlockToRender (Block $block);

}
