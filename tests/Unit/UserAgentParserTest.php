<?php

use App\Support\UserAgentParser;

/*
|--------------------------------------------------------------------------
| Browser Detection Tests
|--------------------------------------------------------------------------
| Validates: Requirements 3.1 — browser marketing name without version
*/

test('detects Chrome browser', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    expect($parser->browser())->toBe('Chrome');
});

test('detects Firefox browser', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');

    expect($parser->browser())->toBe('Firefox');
});

test('detects Safari browser', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15');

    expect($parser->browser())->toBe('Safari');
});

test('detects Edge browser', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0');

    expect($parser->browser())->toBe('Edge');
});

test('detects Opera browser', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0');

    expect($parser->browser())->toBe('Opera');
});

test('browser name does not contain version numbers', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    expect($parser->browser())->not->toMatch('/\d/');
});

/*
|--------------------------------------------------------------------------
| Operating System Detection Tests
|--------------------------------------------------------------------------
| Validates: Requirements 3.2 — OS marketing name without version
*/

test('detects Windows OS', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    expect($parser->os())->toBe('Windows');
});

test('detects macOS', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15');

    expect($parser->os())->toBe('macOS');
});

test('detects Linux OS', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    expect($parser->os())->toBe('Linux');
});

test('detects iOS from iPhone user agent', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1');

    expect($parser->os())->toBe('iOS');
});

test('detects Android OS', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.43 Mobile Safari/537.36');

    expect($parser->os())->toBe('Android');
});

test('OS name does not contain version numbers', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    expect($parser->os())->not->toMatch('/\d/');
});

/*
|--------------------------------------------------------------------------
| Device Type Classification Tests
|--------------------------------------------------------------------------
| Validates: Requirements 3.3 — mobile vs desktop classification
*/

test('classifies mobile Chrome on Android as mobile', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.43 Mobile Safari/537.36');

    expect($parser->deviceType())->toBe('mobile');
});

test('classifies Safari on iPhone as mobile', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1');

    expect($parser->deviceType())->toBe('mobile');
});

test('classifies desktop Chrome as desktop', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    expect($parser->deviceType())->toBe('desktop');
});

test('classifies desktop Firefox as desktop', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');

    expect($parser->deviceType())->toBe('desktop');
});

test('classifies desktop Safari on macOS as desktop', function () {
    $parser = new UserAgentParser('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15');

    expect($parser->deviceType())->toBe('desktop');
});

/*
|--------------------------------------------------------------------------
| Null, Empty, and Unknown Input Tests
|--------------------------------------------------------------------------
| Validates: Requirements 3.4 — fallback to "Unknown"/"Unknown"/"desktop"
*/

test('null user agent returns Unknown browser', function () {
    $parser = new UserAgentParser(null);

    expect($parser->browser())->toBe('Unknown');
});

test('null user agent returns Unknown OS', function () {
    $parser = new UserAgentParser(null);

    expect($parser->os())->toBe('Unknown');
});

test('null user agent returns desktop device type', function () {
    $parser = new UserAgentParser(null);

    expect($parser->deviceType())->toBe('desktop');
});

test('empty string returns Unknown browser', function () {
    $parser = new UserAgentParser('');

    expect($parser->browser())->toBe('Unknown');
});

test('empty string returns Unknown OS', function () {
    $parser = new UserAgentParser('');

    expect($parser->os())->toBe('Unknown');
});

test('empty string returns desktop device type', function () {
    $parser = new UserAgentParser('');

    expect($parser->deviceType())->toBe('desktop');
});

test('gibberish input returns Unknown browser', function () {
    $parser = new UserAgentParser('totally-not-a-browser/xyzzy');

    expect($parser->browser())->toBe('Unknown');
});

test('gibberish input returns Unknown OS', function () {
    $parser = new UserAgentParser('totally-not-a-browser/xyzzy');

    expect($parser->os())->toBe('Unknown');
});

test('gibberish input returns desktop device type', function () {
    $parser = new UserAgentParser('totally-not-a-browser/xyzzy');

    expect($parser->deviceType())->toBe('desktop');
});

test('whitespace-only string returns Unknown browser', function () {
    $parser = new UserAgentParser('   ');

    expect($parser->browser())->toBe('Unknown');
});

test('whitespace-only string returns Unknown OS', function () {
    $parser = new UserAgentParser('   ');

    expect($parser->os())->toBe('Unknown');
});

test('whitespace-only string returns desktop device type', function () {
    $parser = new UserAgentParser('   ');

    expect($parser->deviceType())->toBe('desktop');
});
