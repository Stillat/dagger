# Changelog

## [v1.4.0](https://github.com/Stillat/dagger/compare/v1.3.1...v1.4.0) - 2025-06-15

- Adds the ability to compile components using a callback function

## [v1.3.1](https://github.com/Stillat/dagger/compare/v1.3.0...v1.3.1) - 2025-05-07

- Corrects dependencies for Laravel 12 (#28)

## [v1.3.0](https://github.com/Stillat/dagger/compare/v1.2.0...v1.3.0) - 2025-05-04

- Adds support for the `#when` compiler attribute to conditionally render Dagger components
- Adds support for the `#for` compiler attribute to render a component for each item in a list
- Improves internal compiler hot reloading behavior when using tools such as Vite
- Adds a new `hasSlot` helper method to components
- Adds a new `hasDefaultSlot` helper method to components

## [v1.2.0](https://github.com/Stillat/dagger/compare/v1.1.1...v1.2.0) - 2025-02-23

- Enable installation in Laravel 12 projects

## [v1.1.1](https://github.com/Stillat/dagger/compare/v1.1.0...v1.1.1) - 2025-02-10

- Improves automatic cache invalidation and manifest lifecycle

## [v1.1.0](https://github.com/Stillat/dagger/compare/v1.0.6...v1.1.0) - 2025-02-08

- Adds a new "Compile Time Rendering" system, which can render components at compile time and inline the static output
- Adds compiler support for circular component references, like nested comment threads
- Adds a `#cache` compiler attribute, which may be used to cache the results of any Dagger component
- Bumps the minimum Laravel version to `11.23`, for `Cache::flexible` support
- Improves compilation of custom functions declared within a component's template
- Reduces overall memory utilization
- Simplifies serialized output of dynamic and circular components

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