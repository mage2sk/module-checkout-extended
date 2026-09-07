<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Model\Color;

class Shade
{
    public function normalize(string $hex): ?string
    {
        $hex = trim($hex);
        if (preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hex, $m) !== 1) {
            return null;
        }
        $digits = strtolower($m[1]);
        if (strlen($digits) === 3) {
            $digits = $digits[0] . $digits[0] . $digits[1] . $digits[1] . $digits[2] . $digits[2];
        }

        return '#' . $digits;
    }

    public function darken(string $hex, float $ratio): ?string
    {
        $normalized = $this->normalize($hex);
        if ($normalized === null) {
            return null;
        }
        $ratio = max(0.0, min(1.0, $ratio));
        $out = '#';
        foreach ([1, 3, 5] as $offset) {
            $channel = (int) hexdec(substr($normalized, $offset, 2));
            $channel = (int) round($channel * (1 - $ratio));
            $out .= str_pad(dechex(max(0, min(255, $channel))), 2, '0', STR_PAD_LEFT);
        }

        return $out;
    }

    public function luminance(string $hex): ?float
    {
        $normalized = $this->normalize($hex);
        if ($normalized === null) {
            return null;
        }
        $r = hexdec(substr($normalized, 1, 2));
        $g = hexdec(substr($normalized, 3, 2));
        $b = hexdec(substr($normalized, 5, 2));

        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    }
}
