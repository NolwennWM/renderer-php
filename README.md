# Renderer PHP

A lightweight rendering engine inspired by Twig, designed for personal PHP projects.

## Features

- Simple template syntax: `{{ variable }}`, `{{ variable | filter }}`, `{{ function(arg) }}`
- Custom filter support (pipe syntax)
- Custom function support
- Clear separation between variables, functions, and filters
- Security: automatic variable escaping (except with the `raw` filter)
- Customizable 404 page in case of view not found.

## Installation

This package is intended to be used as a VCS repository. In your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/NolwennWM/renderer-php"
    }
  ],
  "require": {
    "nwm/renderer-php": "^0.2"
  }
}
```

Then run:

```bash
composer update
```

## Environment Variables

You can configure the renderer with the following environment variables:

- `DEFAULT_PAGE_NOT_FOUND` : Path to your custom 404 page (default: `404.php`)
- `ROOT_PATH` : Root path for your templates and error pages (default: current directory)

## Basic Usage

```php
use NWM\Renderer\Renderer;

$renderer = new Renderer();
$data = [
    'title' => 'Welcome',
    'message' => '<b>Hello World</b>',
];
$renderer->render('path/to/template.php', $data);
```

## Example Template

```php
<div>
    <h1>{{ title }}</h1>
    <p>{{ message }}</p>
    <p>{{ message | raw }}</p>
    <p>Current year: {{ currentYear() }}</p>
</div>
```

## Adding a Filter or Function

```php
$renderer->getFunctionRegistry()->registerFilter('raw', fn($value) => $value);
$renderer->getFunctionRegistry()->registerFunction('currentYear', fn() => date('Y'));
```

## Customization

- Default HTML template
- Default language and title
- Custom 404 page

## License

MIT
