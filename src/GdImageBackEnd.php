<?php

namespace karmabunny\BaconBackends;

use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Image\ImageBackEndInterface;
use BaconQrCode\Renderer\Path\Close;
use BaconQrCode\Renderer\Path\Curve;
use BaconQrCode\Renderer\Path\EllipticArc;
use BaconQrCode\Renderer\Path\Line;
use BaconQrCode\Renderer\Path\Move;
use BaconQrCode\Renderer\Path\Path;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use GdImage;
use RuntimeException;

/**
 * A GD image backend for BaconQrCode.
 *
 * Note, this has very limited support.
 *
 * - No gradients.
 * - No rotation.
 * - No layers.
 * - No curves or ellipsis.
 *
 * @package karmabunny\BaconBackends
 */
class GdImageBackEnd implements ImageBackEndInterface
{

    /** @var GdImage */
    protected $image;

    /** @var ColorInterface */
    protected $background;

    /** @var float */
    protected $scale = 1.0;

    /** @var float[] [x, y] */
    protected $shift = [0, 0];

    /** @var bool */
    public $debug = false;


    /** @inheritdoc */
    public function new(int $size, ColorInterface $backgroundColor): void
    {
        if ($this->debug) echo "size: {$size}\n";

        $this->image = imagecreatetruecolor($size, $size);
        $this->background = $backgroundColor;

        $color = $this->getColor($backgroundColor);
        imagefill($this->image, 0, 0, $color);
    }


    /** @inheritdoc */
    public function __destroy()
    {
        imagedestroy($this->image);
    }


    /** @inheritdoc */
    public function scale(float $size): void
    {
        if ($this->debug) echo "scale: {$size}\n";
        $this->scale = $size;
    }


    /** @inheritdoc */
    public function translate(float $x, float $y): void
    {
        if ($this->debug) echo "translate: {$x} {$y}\n";
        $this->shift = [$x, $y];
    }


    /** @inheritdoc */
    public function rotate(int $degrees): void
    {
        if ($this->debug) echo "rotate: {$degrees}\n";

        // $color = $this->getColor($this->background);
        // $this->image = imagerotate($this->image, $degrees, $color);
    }


    /** @inheritdoc */
    public function push(): void
    {
        if ($this->debug) echo "push\n";
    }


    /** @inheritdoc */
    public function pop(): void
    {
        if ($this->debug) echo "pop\n";
    }


    /** @inheritdoc */
    public function drawPathWithColor(Path $path, ColorInterface $color): void
    {
        if ($this->debug) echo "draw\n";

        $fg_color = $this->getColor($color);
        $bg_color = $this->getColor($this->background);

        [$tx, $ty] = $this->shift;

        $points = [];

        foreach ($path as $op) {
            if ($op instanceof Move) {
                $points[] = ($op->getX() + $tx) * $this->scale;
                $points[] = ($op->getY() + $ty) * $this->scale;
                continue;
            }

            if ($op instanceof Line) {
                $points[] = ($op->getX() + $tx) * $this->scale;
                $points[] = ($op->getY() + $ty) * $this->scale;
                continue;
            }

            if ($op instanceof EllipticArc) {
                $points[] = ($op->getX() + $tx) * $this->scale;
                $points[] = ($op->getY() + $ty) * $this->scale;
                continue;
            }

            if ($op instanceof Curve) {
                $points[] = ($op->getX1() + $tx) * $this->scale;
                $points[] = ($op->getY1() + $ty) * $this->scale;

                $points[] = ($op->getX2() + $tx) * $this->scale;
                $points[] = ($op->getY2() + $ty) * $this->scale;

                $points[] = ($op->getX3() + $tx) * $this->scale;
                $points[] = ($op->getY3() + $ty) * $this->scale;
                continue;
            }

            if ($op instanceof Close) {
                if (empty($points)) continue;

                [$x, $y] = $points;
                $at = imagecolorat($this->image, (int) round($x), (int) round($y));

                $color =
                    ($at == $fg_color)
                    ? $bg_color
                    : $fg_color;

                $num = count($points) / 2;
                imagefilledpolygon($this->image, $points, $num, $color);

                // debug
                if ($this->debug) {
                    $color = $this->invertColor($color);
                    $j = ($j ?? -1) + 1;
                    $size = 8;

                    $font = '/usr/share/fonts/truetype/open-sans/OpenSans-Regular.ttf';
                    $bits = array_chunk($points, 2);
                    foreach ($bits as $i => [$x, $y]) {
                        imagettftext($this->image, $size, 0, $x + 1, $y + $size, $color, $font, "{$j}:$i");
                    }
                }

                $points = [];
                continue;
            }

            throw new RuntimeException('Unexpected draw operation: ' . get_class($op));
        }
    }


    /** @inheritdoc */
    public function drawPathWithGradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        $color = $gradient->getStartColor();
        $this->drawPathWithColor($path, $color);
    }


    /** @inheritdoc */
    public function done(): string
    {
        ob_start();
        imagepng($this->image);
        return ob_get_clean();
    }


    /**
     * Convert to a GD color.
     *
     * @link https://www.php.net/manual/en/function.imagecreatetruecolor.php#113063
     *
     * @param ColorInterface $color
     * @return int
     */
    protected function getColor(ColorInterface $color): int
    {
        $rgb = $color->toRgb();
        $color = 0;

        $color |= $rgb->getRed() << 16;
        $color |= $rgb->getGreen() << 8;
        $color |= $rgb->getBlue();

        return $color;
    }


    /**
     * Invert a GD color.
     *
     * @param int $color
     * @return int
     */
    protected function invertColor(int $color): int
    {
        $r = ($color >> 16) & 0xFF;
        $g = ($color >> 8) & 0xFF;
        $b = $color & 0xFF;

        $r = 255 - $r;
        $g = 255 - $g;
        $b = 255 - $b;

        $color = 0;
        $color |= $r << 16;
        $color |= $g << 8;
        $color |= $b;

        return $color;
    }
}
