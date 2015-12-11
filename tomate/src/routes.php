<?php

use \C\Layout\Transform as Layout;
use \C\Device\Transform as Device;
use \C\Stream\StreamObjectTransform as Stream;

// Routes
$app->get('/[{name}]', function ($req, $res, $args) {
    $this->logger->info("Slim-Skeleton '/' route");

    $args = array_merge($args, ['name'=>'Clement']);

    $Device = new Device();
    $Layout = new Layout();
    $Device->setRequest($req);

    $any = Stream::through();
    $any->pipe($Layout->setDefaultData('root', $args));

    $desktop = Stream::through($Device->forDesktop());
    $desktop->pipe($Layout->setTemplate('root', 'index.twig'));

    $mobile = Stream::through($Device->forMobile());
    $mobile->pipe($Layout->setTemplate('root', 'mobile.php'));

    $any->pipe($desktop);
    $any->pipe($mobile);

    $any->write($this->get('layout'));

    return $this->get('layout')->render();
});
