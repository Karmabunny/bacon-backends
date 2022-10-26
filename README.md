
# Additional Bacon QR backends

Some extra backends for Bacon QR.

These might work a bit more reliably than others, particularly in constrained hosting environments.

- ImagickDumbBackEnd
- GdImageBackEnd


### ImagickDumbBackEnd

The built-in imagick backend is (currently) broken in PHP 8. So this is a backend that wraps the SVG backend and pipes it through the `convert` CLI tool provided by imagick.

Generally the PHP imagick extension is questionable anyway. The `convert` tool is seemingly impenetrable. So it's a nice to have something a bit more robust.

Caveats: Might not work on Windows. Makes heavy use of `proc_open()`.


### GdImageBackEnd

This is a _very_ limited backend but has enough bits to draw a basic QR code.

GD is widely available, more than imagick. So this is a big win for those tight environments.

Caveats: Many.

 - No gradients.
 - No rotation.
 - No layers.
 - No curves or ellipsis.

You might ask, why not write a renderer instead of an image backend? The answer is - I didn't even realise that was an option. It might be easier, might be harder. Might actually be far more robust. So maybe that's a thing for the future.


## Usage

```php
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use karmabunny\BaconBackends\GdImageBackEnd;
use karmabunny\BaconBackends\ImagickDumbBackEnd;

$renderer = new ImageRenderer(
    new RendererStyle(400),
    new ImagickDumbBackEnd(),
    // or new GdImageBackEnd(),
);
$writer = new Writer($renderer);
$writer->writeFile('Hello World!', 'qrcode.png');
```


## Contributing

Be sure to write a test. We've got some pretty funky stuff going on in there but it validates the use case.

Run tests with `composer test`.

Pleas also run `composer analyse` over anything before releasing.


### Future

Some things to do, for fun and profit.

- GD renderer (as opposed to the image backend)
- HTML renderer
