<?php
namespace karmabunny\BaconBackends;

use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Image\ImageBackEndInterface;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Path\Path;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use Exception;

/**
 * A backend using the SVG renderer + imagick CLI tools.
 *
 * This is truly dumb but also works a treat. It first renders using the SVG
 * backend provided by bacon-qr and then runs it through the `convert` CLI
 * tool provided by imagick to convert to a raster image.
 *
 * @package karmabunny\BaconBackends
 */
class ImagickDumbBackEnd implements ImageBackEndInterface
{

    const PARAMS = [
        'background',
        'alpha',
        'quality',
        'resize',
        'density',
    ];

    /** @var string */
    public $filetype = 'png';

    /** @var string */
    public $background = 'white';

    /** @var string */
    public $alpha = 'background';

    /** @var int|null percentage, between 1 and 100 */
    public $quality = null;

    /** @var int|null percentage, between 1 and 100 */
    public $resize = null;

    /** @var int|null DPI, dots per inch */
    public $density;

    /** @var bool */
    public $debug = false;

    /** @var SvgImageBackEnd */
    private $wrap;


    /**
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }

        $this->wrap = new SvgImageBackEnd();

        $output = null;
        $return = 0;
        @exec('which convert', $output, $return);

        if ($return != 0) {
            throw new Exception('Imagick `convert` not found');
        }
    }


    /** @inheritdoc */
    public function new(int $size, ColorInterface $backgroundColor): void
    {
        $this->wrap->new($size, $backgroundColor);
    }


    /** @inheritdoc */
    public function scale(float $size): void
    {
        $this->wrap->scale($size);
    }


    /** @inheritdoc */
    public function translate(float $x, float $y): void
    {
        $this->wrap->translate($x, $y);
    }


    /** @inheritdoc */
    public function rotate(int $degrees): void
    {
        $this->wrap->rotate($degrees);
    }


    /** @inheritdoc */
    public function push(): void
    {
        $this->wrap->push();
    }


    /** @inheritdoc */
    public function pop(): void
    {
        $this->wrap->pop();
    }


    /** @inheritdoc */
    public function drawPathWithColor(Path $path, ColorInterface $color): void
    {
        $this->wrap->drawPathWithColor($path, $color);
    }


    /** @inheritdoc */
    public function drawPathWithGradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        $this->wrap->drawPathWithGradient($path, $gradient, $x, $y, $width, $height);
    }


    /** @inheritdoc */
    public function done(): string
    {
        $cmd = $this->buildCommand();
        $args = $this->buildArgs();

        $cmd = self::escapeCommand($cmd, $args);
        if ($this->debug) echo $cmd, "\n";

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes = [];

        $res = proc_open($cmd, $descriptors, $pipes);

        if (!$res) {
            throw new Exception('Failed to execute imagick');
        }

        if ($this->debug) {
            echo json_encode(@proc_get_status($res), JSON_PRETTY_PRINT), "\n";
        }

        try {
            // Render the SVG one.
            $svg = $this->wrap->done();

            // Write it into convert.
            fwrite($pipes[0], $svg);
            fclose($pipes[0]);

            $errors = '';
            $output = '';

            for (;;) {
                $eof1 = feof($pipes[1]);
                $eof2 = feof($pipes[2]);

                // Read data.
                if (!$eof1) {
                    $chunk = @fread($pipes[1], 1024);

                    if ($chunk === false) {
                        throw new Exception('Failed to read from imagick');
                    }

                    $output .= $chunk;
                }

                // Also read the error stream, if any.
                if (!$eof2) {
                    $errors .= @fread($pipes[2], 1024);
                }

                if ($eof1 and $eof2) break;
            }

            // Oh noes!
            if (!$output) {
                $message = "Failed to write image.";

                // Check the error stream for anything useful.
                if ($errors) {
                    $message = $errors;
                }

                throw new Exception($message);
            }

            return $output;
        }
        finally {
            proc_close($res);
        }
    }


    /**
     * Build a command string from the renderer parameters.
     *
     * @return string
     */
    protected function buildCommand(): string
    {
        $cmd = "convert";

        foreach (self::PARAMS as $param) {
            if ($this->$param === null) continue;
            $cmd .= " -{$param} {{$param}}";
        }

        // Input stdin.
        $cmd .= ' -';

        // Output stdin.
        $cmd .= ' {type}:-';

        return $cmd;
    }


    /**
     * Build command args.
     *
     * @return array
     */
    protected function buildArgs(): array
    {
        $args = [];

        foreach (self::PARAMS as $param) {
            if ($this->$param === null) continue;
            $args[$param] = $this->$param;
        }

        $args['type'] = $this->filetype;

        return $args;
    }


    /**
     * Interpolate args into a command with proper escaping.
     *
     * Pinched from karmabunny/kb.
     *
     * @param string $cmd
     * @param array $args
     * @return string|string[]|null
     */
    protected static function escapeCommand(string $cmd, array $args)
    {
        return preg_replace_callback('/{([^}]+)}/', function($matches) use ($args) {
            $index = $matches[1];
            return escapeshellarg($args[$index] ?? '');
        }, $cmd);
    }
}
