<?php
use NWM\Renderer\Renderer;

require __DIR__ . '/../vendor/autoload.php';

$renderer = new Renderer( default_html: file_get_contents(__DIR__ . '/base.php'), lang: 'en', default_title: 'My Site');

$renderer->render('myPage.php', dataToRender:[
    'title' => 'Welcome to My Site', "message" => "<strong>Hello, World!</strong>"]);