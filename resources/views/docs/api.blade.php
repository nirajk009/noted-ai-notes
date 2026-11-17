<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Noted API documentation</title>
        @vite('resources/js/api-docs.js')
    </head>
    <body>
        <header class="docs-header">
            <strong>Noted API</strong>
            <nav aria-label="API documentation links">
                <a href="{{ route('notes.index') }}">Open notes</a>
                <a href="{{ route('docs.openapi') }}" download="openapi.json">Download OpenAPI</a>
                <a href="{{ route('docs.postman') }}">Download Postman collection</a>
            </nav>
        </header>
        <main id="swagger-ui" data-spec-url="{{ route('docs.openapi') }}"></main>
    </body>
</html>
