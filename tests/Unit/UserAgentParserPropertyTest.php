<?php

use App\Support\UserAgentParser;

// Feature: session-management, Property 4: Browser name extraction without version
// For any user agent string containing a known browser identifier followed by a version number,
// the parser SHALL return only the marketing name without any numeric version component.
// **Validates: Requirements 3.1**
test('property 4: browser name extraction never contains version numbers', function (string $userAgent, string $expectedBrowser) {
    $parser = new UserAgentParser($userAgent);

    $result = $parser->browser();

    expect($result)->toBe($expectedBrowser);
    expect($result)->not->toMatch('/\d/');
})->with(function () {
    $browsers = [
        ['identifier' => 'Chrome/', 'name' => 'Chrome'],
        ['identifier' => 'Firefox/', 'name' => 'Firefox'],
        ['identifier' => 'Edg/', 'name' => 'Edge'],
        ['identifier' => 'OPR/', 'name' => 'Opera'],
        ['identifier' => 'Vivaldi/', 'name' => 'Vivaldi'],
        ['identifier' => 'SamsungBrowser/', 'name' => 'Samsung Browser'],
    ];

    for ($i = 0; $i < 100; $i++) {
        $browser = $browsers[array_rand($browsers)];

        $major = random_int(1, 200);
        $minor = random_int(0, 99);
        $patch = random_int(0, 9999);

        $versionFormats = [
            "$major.$minor.$patch",
            "$major.$minor",
            "$major",
            "$major.$minor.$patch.".random_int(0, 999),
        ];
        $version = $versionFormats[array_rand($versionFormats)];

        // Build a realistic-looking user agent with the browser identifier and version
        $prefix = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) ';
        $ua = $prefix.$browser['identifier'].$version;

        // For Chrome-based browsers, include Chrome/ in the string as real UAs do
        if (in_array($browser['name'], ['Edge', 'Opera', 'Vivaldi', 'Samsung Browser'])) {
            $chromeVersion = random_int(80, 130).'.0.'.random_int(1000, 9999).'.'.random_int(0, 999);
            $ua = $prefix."Chrome/$chromeVersion ".$browser['identifier'].$version;
        }

        yield "iteration $i: {$browser['name']} v$version" => [$ua, $browser['name']];
    }
});

// Feature: session-management, Property 5: Operating system extraction without version
// For any user agent string containing a known OS identifier followed by a version number,
// the parser SHALL return only the marketing OS name without any numeric version component.
// **Validates: Requirements 3.2**
test('property 5: OS extraction never contains version numbers', function (string $userAgent, string $expectedOs) {
    $parser = new UserAgentParser($userAgent);

    $result = $parser->os();

    expect($result)->toBe($expectedOs);
    expect($result)->not->toMatch('/\d/');
})->with(function () {
    $operatingSystems = [
        ['pattern' => 'Windows NT %s', 'name' => 'Windows'],
        ['pattern' => 'Macintosh; Intel Mac OS X %s', 'name' => 'macOS'],
        ['pattern' => 'Linux; Android %s', 'name' => 'Android'],
        ['pattern' => 'iPhone; CPU iPhone OS %s like Mac OS X', 'name' => 'iOS'],
        ['pattern' => 'iPad; CPU OS %s like Mac OS X', 'name' => 'iOS'],
    ];

    for ($i = 0; $i < 100; $i++) {
        $os = $operatingSystems[array_rand($operatingSystems)];

        $major = random_int(1, 20);
        $minor = random_int(0, 15);
        $patch = random_int(0, 10);

        $versionFormats = [
            "$major.$minor.$patch",
            "$major.$minor",
            "$major",
            str_replace('.', '_', "$major.$minor.$patch"),  // iOS-style underscores
        ];
        $version = $versionFormats[array_rand($versionFormats)];

        $osString = sprintf($os['pattern'], $version);
        $ua = "Mozilla/5.0 ($osString) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";

        yield "iteration $i: {$os['name']} v$version" => [$ua, $os['name']];
    }
});

// Feature: session-management, Property 6: Device type classification
// For any user agent string, if it contains a mobile indicator (e.g., "Mobile", "iPhone", "Android" with "Mobile"),
// the parser SHALL classify it as "mobile"; otherwise it SHALL classify it as "desktop".
// **Validates: Requirements 3.3**
test('property 6: device type classification based on mobile indicators', function (string $userAgent, string $expectedDeviceType) {
    $parser = new UserAgentParser($userAgent);

    $result = $parser->deviceType();

    expect($result)->toBe($expectedDeviceType);
    expect($result)->toBeIn(['mobile', 'desktop']);
})->with(function () {
    $mobileIndicators = ['Mobile', 'iPhone', 'iPod'];

    $desktopBases = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36',
        'Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:%s) Gecko/20100101 Firefox/%s',
        'Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/%s Safari/604.1',
    ];

    $mobileBases = [
        'Mozilla/5.0 (iPhone; CPU iPhone OS 16_%d like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (iPod touch; CPU iPhone OS 15_%d like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android %d; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Linux; Android %d; SM-S908B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
    ];

    for ($i = 0; $i < 100; $i++) {
        $isMobile = (bool) random_int(0, 1);

        if ($isMobile) {
            // Generate a mobile user agent
            $base = $mobileBases[array_rand($mobileBases)];
            $version = random_int(10, 17);
            $ua = sprintf($base, $version);
            $expectedType = 'mobile';
        } else {
            // Generate a desktop user agent (no mobile indicators)
            $base = $desktopBases[array_rand($desktopBases)];
            $version = random_int(80, 130).'.0.'.random_int(1000, 9999).'.0';
            $ua = sprintf($base, $version, $version);
            $expectedType = 'desktop';
        }

        yield "iteration $i: expected $expectedType" => [$ua, $expectedType];
    }
});
