<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class SimpleQrSvg
{
    public static function svg(string $text, int $pixels = 96): string
    {
        $pixels = max(64, min(180, $pixels));
        if (! class_exists(ImageRenderer::class) || ! class_exists(RendererStyle::class) || ! class_exists(SvgImageBackEnd::class) || ! class_exists(Writer::class)) {
            return sprintf(
                '<img src="https://api.qrserver.com/v1/create-qr-code/?size=%1$dx%1$d&amp;data=%2$s" width="%1$d" height="%1$d" alt="QR">',
                $pixels,
                rawurlencode($text)
            );
        }

        $renderer = new ImageRenderer(
            new RendererStyle($pixels, 4),
            new SvgImageBackEnd()
        );

        $svg = (new Writer($renderer))->writeString($text);

        return (string) preg_replace('/^<\?xml[^>]+>\s*/', '', $svg);
    }
}
