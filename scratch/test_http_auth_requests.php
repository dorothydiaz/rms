<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('username', 'peter')->first();
if (!$user) {
    echo "User peter not found!\n";
    exit(1);
}

// Log in as peter
\Illuminate\Support\Facades\Auth::login($user);

$urls = [
    '/inventory/recipe-management',
    '/inventory/stocks-overview',
    '/inventory/production',
];

foreach ($urls as $url) {
    $request = \Illuminate\Http\Request::create($url, 'GET');
    $request->setUserResolver(fn() => $user);
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    $content = $response->getContent();

    echo "URL: $url -> Status: $status (Body len: " . strlen($content) . ")\n";

    if ($status >= 400 || str_contains($content, 'Exception') || str_contains($content, 'Cannot end a section')) {
        echo "  [ERROR DETECTED]\n";
        preg_match('/<title>(.*?)<\/title>/is', $content, $m);
        if (!empty($m[1])) echo "  Title: " . trim($m[1]) . "\n";
        preg_match('/<div class="message">(.*?)<\/div>/is', $content, $m2);
        if (!empty($m2[1])) echo "  Message: " . trim($m2[1]) . "\n";
    } else {
        echo "  [SUCCESS: Rendered cleanly without server error]\n";
        preg_match('/<title>(.*?)<\/title>/is', $content, $m);
        if (!empty($m[1])) echo "  Page Title: " . trim($m[1]) . "\n";
    }
}
