<?php
require __DIR__ . '/../vendor/autoload.php';

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use karmabunny\BaconBackends\GdImageBackEnd;
use karmabunny\BaconBackends\ImagickDumbBackEnd;

$black = new RendererStyle(400);
$purple = new RendererStyle(
    400, 4, null, null,
    Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(255, 0, 150)),
);

$renderer = new ImageRenderer(
    $black,
    new SvgImageBackEnd(),
);

$writer = new Writer($renderer);
$writer->writeFile('Hello World!', __DIR__ . '/qrcode.svg');
echo "ok\n";


$renderer = new ImageRenderer(
    $black,
    new ImagickDumbBackEnd(),
);

$writer = new Writer($renderer);
$writer->writeFile('Hello World!', __DIR__ . '/dumb_qrcode.png');
echo "ok\n";


$renderer = new ImageRenderer(
    $purple,
    new GdImageBackEnd(),
);

$writer = new Writer($renderer);
$writer->writeFile('Hello World!', __DIR__ . '/gd_qrcode.png');
echo "ok\n";
