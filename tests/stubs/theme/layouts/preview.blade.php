{{-- Smoke-test preview-layout stub.

admin/pages/preview-frame.blade.php extends `themes::layouts.preview`.
Production supplies this via whatever theme is active; the plugin test
harness does not load a theme, so without this stub the preview-frame
GET route 500s with "View [layouts.preview] not found", which is a
test-env gap rather than a product defect.

Keep this minimal: the smoke test checks `status < 500`, not markup. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'test-preview-stub')</title>
</head>
<body>
    @yield('content')
</body>
</html>
