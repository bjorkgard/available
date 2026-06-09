<?php

namespace App\Support;

class UserAgentParser
{
    public function __construct(private ?string $userAgent) {}

    /**
     * Extract the marketing browser name from the user agent string.
     */
    public function browser(): string
    {
        if ($this->isEmptyOrNull()) {
            return 'Unknown';
        }

        $ua = $this->userAgent;

        // Order matters: check more specific browsers before generic ones.
        // Edge must come before Chrome (Edge UA contains "Chrome").
        // Opera/OPR must come before Chrome (Opera UA contains "Chrome").
        // Samsung Browser must come before Chrome (Samsung UA contains "Chrome").
        $browsers = [
            '/\bEdg(?:e|A|iOS)?\//' => 'Edge',
            '/\bOPR\//' => 'Opera',
            '/\bOpera\//' => 'Opera',
            '/\bSamsungBrowser\//' => 'Samsung Browser',
            '/\bVivaldi\//' => 'Vivaldi',
            '/\bBrave\//' => 'Brave',
            '/\bChrome\//' => 'Chrome',
            '/\bCriOS\//' => 'Chrome',
            '/\bFirefox\//' => 'Firefox',
            '/\bFxiOS\//' => 'Firefox',
            '/\bSafari\//' => 'Safari',
        ];

        foreach ($browsers as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                // Special case: "Safari" in UA without "Chrome" or other browsers
                // is actually Safari. But if Chrome is also present, it's not Safari.
                if ($name === 'Safari' && preg_match('/\bChrome\//', $ua)) {
                    continue;
                }

                return $name;
            }
        }

        return 'Unknown';
    }

    /**
     * Extract the marketing OS name from the user agent string.
     */
    public function os(): string
    {
        if ($this->isEmptyOrNull()) {
            return 'Unknown';
        }

        $ua = $this->userAgent;

        // Order matters: check more specific patterns first.
        // "iPhone" and "iPad" indicate iOS, "Android" indicates Android.
        // "Mac OS X" or "Macintosh" indicates macOS.
        // "Windows" indicates Windows.
        // "Linux" is checked last (Android UAs also contain "Linux").
        $operatingSystems = [
            '/\b(?:iPhone|iPad|iPod)\b/' => 'iOS',
            '/\bAndroid\b/' => 'Android',
            '/\bWindows\b/' => 'Windows',
            '/\bMac OS X\b|\bMacintosh\b/' => 'macOS',
            '/\bCrOS\b/' => 'Chrome OS',
            '/\bLinux\b/' => 'Linux',
        ];

        foreach ($operatingSystems as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }

        return 'Unknown';
    }

    /**
     * Determine the device type based on mobile indicators in the user agent.
     */
    public function deviceType(): string
    {
        if ($this->isEmptyOrNull()) {
            return 'desktop';
        }

        $ua = $this->userAgent;

        // Check for mobile indicators
        if (preg_match('/\bMobile\b|\biPhone\b|\biPod\b/', $ua)) {
            return 'mobile';
        }

        // Android without "Mobile" is typically a tablet — classify as desktop
        // Android with "Mobile" is a phone — but "Mobile" is already caught above

        return 'desktop';
    }

    private function isEmptyOrNull(): bool
    {
        return $this->userAgent === null || trim($this->userAgent) === '';
    }
}
