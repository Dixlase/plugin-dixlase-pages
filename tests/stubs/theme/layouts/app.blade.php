{{-- Smoke-test theme stub.

Preview-mode admin views (admin/pages/preview-frame.blade.php) extend
`themes::layouts.app`, which production supplies via whatever theme is
active. The plugin test harness does not load a theme, so without this
stub the preview GET route resolves its view against an unregistered
`themes` namespace and `AdminGetRoutesSmokeTest` sees a false 500 that
is really a test-env gap.

Keep this minimal: the smoke test checks `status < 500`, not markup. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'test-theme-stub')</title>
</head>
<body>
    @yield('content')
</body>
</html>
