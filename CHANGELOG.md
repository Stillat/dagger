# Changelog

## [v1.0.6](https://github.com/Stillat/dagger/compare/v1.0.5...v1.0.6) - 2025-01-31

- Corrects an issue where Blade stack compilation results in array index errors

## [v1.0.5](https://github.com/Stillat/dagger/compare/v1.0.4...v1.0.5) - 2025-01-27

- `@aware` variables are automatically removed from the attribute bag, without needing to redefine them in `@props`
- Adds support for passing attributes via. the `<c-component {{ $attributes }} />` attribute
- Bumps minimum version of `stillat/blade-parser` to 1.10.3
- Adds support for compiling component attributes of the form `<c-component attribute={{ $value }} />`
- Adds support for compiling component attributes of the form `<c-component attribute={{{ $value }}} />`
- Adds support for compiling component attributes of the form `<c-component attribute={!! $value !!} />`
- Multi-word prop values will be available on nested components when passing `$attributes` to a child component
- Internal component model instances will be cached during compilation, improving performance for heavily re-used components
- Improves compatibility with [Volt](https://livewire.laravel.com/docs/volt)

## [v1.0.4](https://github.com/Stillat/dagger/compare/v1.0.3...v1.0.4) - 2025-01-21

- Improves compilation of hyphenated attributes, preventing them from becoming camelCased

## [v1.0.3](https://github.com/Stillat/dagger/compare/v1.0.2...v1.0.3) - 2025-01-21

- Variable assignments are no longer removed from components when they appear before the `component()` builder function call
- Parity: A default `$slot` variable is now available

## [v1.0.2](https://github.com/Stillat/dagger/compare/v1.0.1...v1.0.2) - 2025-01-21

- Reverts changes from 1.0.1 to prevent overriding core directives, reduce amount of reflection
- Improves Blade stack injection
- Corrects an issue where Dagger components may not compile when used inside Blade component slots

## [v1.0.1](https://github.com/Stillat/dagger/compare/v1.0.0...v1.0.1) - 2025-01-21

- Corrects an issue where Blade stack injection would fail when using multi-line attributes

## [v1.0.0](https://github.com/Stillat/dagger/compare/v1.0.0...v1.0.0) - 2025-01-20

- The Beginning