<?php

use Interop\Container\ContainerInterface as ContainerInterface;


// DIC configuration
$container = $app->getContainer();
// view renderer
$container['renderer'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], Monolog\Logger::DEBUG));
    return $logger;
};

// layout
$container['renderer'] = function (ContainerInterface $c) {
    $Renderer = new \C\Renderer\FileExt();
    $phpR = new C\Php\Php();
    $phpR->setResolver(new \C\Symbol\SimpleSymbolResolver(__DIR__."/../templates/"));
    $Renderer->register('php', $phpR);
    $Renderer->register('twig', new C\Twig\Twig(__DIR__."/../templates/"));
    $Renderer['context'] = new C\View\Context();
    return $Renderer;
};
$container['layout'] = function (ContainerInterface $c) {
    $Layout = new \C\Layout\Layout();
    $Layout->setRenderer($c->get('renderer'));
    return $Layout;
};
