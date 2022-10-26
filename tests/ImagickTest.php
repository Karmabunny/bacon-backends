<?php

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use karmabunny\BaconBackends\ImagickDumbBackEnd;
use PHPUnit\Framework\TestCase;
use Zxing\QrReader;


class ImagickTest extends TestCase
{
    public function testImagickBackend()
    {
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new ImagickDumbBackEnd(),
        );
        $writer = new Writer($renderer);


        $expected = 'Hello World!';
        $blob = $writer->writeString($expected);

        $reader = new QrReader($blob, QrReader::SOURCE_TYPE_BLOB);
        $actual = $reader->text();

        $this->assertEquals($expected, $actual);
    }
}
