<?php

namespace App\Services;

use App\Exceptions\ColorGenerationException;

class ColorService
{
    private const MIN_DISTANCE = 25.0;

    private const MAX_ATTEMPTS = 100;

    /**
     * Validate hex color format.
     */
    public static function isValidHex(string $hex): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $hex);
    }

    /**
     * Check that a color has sufficient CIEDE2000 distance from all sibling colors.
     *
     * @param  list<string>  $siblingColors  Hex colors of sibling congregations
     */
    public function isDistinctFromAll(string $color, array $siblingColors): bool
    {
        if ($siblingColors === []) {
            return true;
        }

        foreach ($siblingColors as $sibling) {
            if ($this->ciede2000Distance($color, $sibling) < self::MIN_DISTANCE) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a random color with sufficient distance from existing siblings.
     *
     * @param  list<string>  $siblingColors  Hex colors of sibling congregations
     * @return string Generated hex color in #RRGGBB format
     *
     * @throws ColorGenerationException
     */
    public function generateDistinctColor(array $siblingColors): string
    {
        if ($siblingColors === []) {
            return '#'.strtoupper(str_pad(dechex(random_int(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT));
        }

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $color = '#'.strtoupper(str_pad(dechex(random_int(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT));

            if ($this->isDistinctFromAll($color, $siblingColors)) {
                return $color;
            }
        }

        throw new ColorGenerationException;
    }

    /**
     * Convert hex RGB to CIELAB color space using D65/2° observer.
     *
     * @return array{L: float, a: float, b: float}
     *
     * @throws \InvalidArgumentException
     */
    public function hexToLab(string $hex): array
    {
        if (! self::isValidHex($hex)) {
            throw new \InvalidArgumentException("Invalid hex color: {$hex}");
        }

        // Step 1: Parse hex to RGB integers (0-255)
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));

        // Step 2: Normalize to 0-1 range and apply gamma correction (sRGB to linear)
        $r = $this->srgbToLinear($r / 255.0);
        $g = $this->srgbToLinear($g / 255.0);
        $b = $this->srgbToLinear($b / 255.0);

        // Step 3: Convert linear RGB to XYZ using D65/2° observer matrix
        $x = 0.4124564 * $r + 0.3575761 * $g + 0.1804375 * $b;
        $y = 0.2126729 * $r + 0.7151522 * $g + 0.0721750 * $b;
        $z = 0.0193339 * $r + 0.1191920 * $g + 0.9503041 * $b;

        // Step 4: Normalize XYZ by D65 reference white
        $xn = 0.95047;
        $yn = 1.00000;
        $zn = 1.08883;

        $fx = $this->labF($x / $xn);
        $fy = $this->labF($y / $yn);
        $fz = $this->labF($z / $zn);

        // Step 5-6: Calculate L*, a*, b*
        $L = 116.0 * $fy - 16.0;
        $a = 500.0 * ($fx - $fy);
        $b = 200.0 * ($fy - $fz);

        return ['L' => $L, 'a' => $a, 'b' => $b];
    }

    /**
     * Apply sRGB gamma correction to convert to linear RGB.
     */
    private function srgbToLinear(float $value): float
    {
        if ($value <= 0.04045) {
            return $value / 12.92;
        }

        return pow(($value + 0.055) / 1.055, 2.4);
    }

    /**
     * Calculate CIEDE2000 distance between two hex colors.
     *
     * @return float Non-negative distance rounded to 4 decimal places
     *
     * @throws \InvalidArgumentException
     */
    public function ciede2000Distance(string $hex1, string $hex2): float
    {
        if (! self::isValidHex($hex1)) {
            throw new \InvalidArgumentException("Invalid hex color: {$hex1}");
        }

        if (! self::isValidHex($hex2)) {
            throw new \InvalidArgumentException("Invalid hex color: {$hex2}");
        }

        $lab1 = $this->hexToLab($hex1);
        $lab2 = $this->hexToLab($hex2);

        $L1 = $lab1['L'];
        $a1 = $lab1['a'];
        $b1 = $lab1['b'];
        $L2 = $lab2['L'];
        $a2 = $lab2['a'];
        $b2 = $lab2['b'];

        // Step 1: Calculate C'ab (chroma) with G correction
        $Cab1 = sqrt($a1 * $a1 + $b1 * $b1);
        $Cab2 = sqrt($a2 * $a2 + $b2 * $b2);
        $CabMean = ($Cab1 + $Cab2) / 2.0;

        $CabMean7 = pow($CabMean, 7);
        $G = 0.5 * (1.0 - sqrt($CabMean7 / ($CabMean7 + pow(25, 7))));

        $aPrime1 = $a1 * (1.0 + $G);
        $aPrime2 = $a2 * (1.0 + $G);

        $CPrime1 = sqrt($aPrime1 * $aPrime1 + $b1 * $b1);
        $CPrime2 = sqrt($aPrime2 * $aPrime2 + $b2 * $b2);

        // Step 2: Calculate h' (hue angle in degrees, 0-360)
        $hPrime1 = $this->hueAngle($b1, $aPrime1);
        $hPrime2 = $this->hueAngle($b2, $aPrime2);

        // Step 3: Calculate ΔL', ΔC', ΔH'
        $deltaLPrime = $L2 - $L1;
        $deltaCPrime = $CPrime2 - $CPrime1;

        // Calculate Δh'
        if ($CPrime1 * $CPrime2 == 0.0) {
            $deltahPrime = 0.0;
        } elseif (abs($hPrime2 - $hPrime1) <= 180.0) {
            $deltahPrime = $hPrime2 - $hPrime1;
        } elseif ($hPrime2 - $hPrime1 > 180.0) {
            $deltahPrime = $hPrime2 - $hPrime1 - 360.0;
        } else {
            $deltahPrime = $hPrime2 - $hPrime1 + 360.0;
        }

        $deltaHPrime = 2.0 * sqrt($CPrime1 * $CPrime2) * sin(deg2rad($deltahPrime / 2.0));

        // Step 4: Calculate arithmetic mean values
        $LPrimeMean = ($L1 + $L2) / 2.0;
        $CPrimeMean = ($CPrime1 + $CPrime2) / 2.0;

        // Calculate h̄' (mean hue angle)
        if ($CPrime1 * $CPrime2 == 0.0) {
            $hPrimeMean = $hPrime1 + $hPrime2;
        } elseif (abs($hPrime1 - $hPrime2) <= 180.0) {
            $hPrimeMean = ($hPrime1 + $hPrime2) / 2.0;
        } elseif ($hPrime1 + $hPrime2 < 360.0) {
            $hPrimeMean = ($hPrime1 + $hPrime2 + 360.0) / 2.0;
        } else {
            $hPrimeMean = ($hPrime1 + $hPrime2 - 360.0) / 2.0;
        }

        // Step 5: Calculate T
        $T = 1.0
            - 0.17 * cos(deg2rad($hPrimeMean - 30.0))
            + 0.24 * cos(deg2rad(2.0 * $hPrimeMean))
            + 0.32 * cos(deg2rad(3.0 * $hPrimeMean + 6.0))
            - 0.20 * cos(deg2rad(4.0 * $hPrimeMean - 63.0));

        // Step 6: Calculate SL, SC, SH
        $LPrimeMeanMinus50Sq = ($LPrimeMean - 50.0) * ($LPrimeMean - 50.0);
        $SL = 1.0 + 0.015 * $LPrimeMeanMinus50Sq / sqrt(20.0 + $LPrimeMeanMinus50Sq);
        $SC = 1.0 + 0.045 * $CPrimeMean;
        $SH = 1.0 + 0.015 * $CPrimeMean * $T;

        // Step 7: Calculate RT (rotation term)
        $deltaTheta = 30.0 * exp(-(($hPrimeMean - 275.0) / 25.0) * (($hPrimeMean - 275.0) / 25.0));
        $CPrimeMean7 = pow($CPrimeMean, 7);
        $RC = 2.0 * sqrt($CPrimeMean7 / ($CPrimeMean7 + pow(25, 7)));
        $RT = -sin(deg2rad(2.0 * $deltaTheta)) * $RC;

        // Step 8: Calculate ΔE00
        $ratioL = $deltaLPrime / $SL;
        $ratioC = $deltaCPrime / $SC;
        $ratioH = $deltaHPrime / $SH;

        $deltaE = sqrt($ratioL * $ratioL + $ratioC * $ratioC + $ratioH * $ratioH + $RT * $ratioC * $ratioH);

        return round($deltaE, 4);
    }

    /**
     * Calculate hue angle in degrees (0-360) from b and a' components.
     */
    private function hueAngle(float $b, float $aPrime): float
    {
        if ($b == 0.0 && $aPrime == 0.0) {
            return 0.0;
        }

        $h = rad2deg(atan2($b, $aPrime));

        if ($h < 0.0) {
            $h += 360.0;
        }

        return $h;
    }

    /**
     * Lab conversion function f(t).
     */
    private function labF(float $t): float
    {
        $threshold = pow(6.0 / 29.0, 3); // (6/29)^3 ≈ 0.008856

        if ($t > $threshold) {
            return pow($t, 1.0 / 3.0);
        }

        return (1.0 / 3.0) * pow(29.0 / 6.0, 2) * $t + 4.0 / 29.0;
    }
}
