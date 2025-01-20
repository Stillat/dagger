# Dagger Components for Laravel Blade

Dagger is a component authoring library for Laravel's Blade templating engine. Dagger components are heavily inspired by Laravel's [anonymous components](https://laravel.com/docs/blade#anonymous-components). Dagger's differentiating features are its compiler and expanded capabilities.

The Dagger compiler works hard to inline your component's code, perform various optimizations, as well as enable powerful new features, such as the [Attribute Cache](#attribute-cache), [Attribute Forwarding](#attribute-forwarding), and [Slot Forwarding](#slot-forwarding). The end result is a powerful, performant component authoring library with a familiar syntax.

The main visual difference when working with Dagger components is the use of the `<c-` prefix instead of `<x-`, this is to help differentiate them from Blade components and make interoperability easier:

```blade
<!-- /resources/dagger/views/alert.blade.php -->

@props(['type' => 'info', 'message'])

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>
```

```blade
<!-- /resources/views/layout.blade.php -->

<c-alert type="error" :message="$message" class="mb-4"/>
```

- [Frequently Asked Questions](#frequently-asked-questions)
- [Installation](#installation)
- [Dagger Component View Paths](#dagger-component-view-paths)
  - [Index Components](#index-components)
  - [Moving Dagger Component Views](#moving-dagger-component-views)
- [Component Syntax and the Component Builder](#component-syntax-and-the-component-builder)
  - [Slots](#slots)
    - [Named/Scoped Slots](#namedscoped-slots)
  - [Custom PHP When Using the Component Function](#custom-php-when-using-the-component-function)
  - [Calling Component Builder Methods](#calling-component-builder-methods)
  - [Renaming the Component Variable](#renaming-the-component-variable)
- [Data Properties/Attributes](#data-properties--attributes)
- [Notes on Conditional aware and props Directives](#notes-on-conditional-aware-and-props-directives)
- [Accessing Parent Data](#accessing-parent-data)
  - [Using the aware Directive](#using-the-aware-directive)
  - [Using the aware Builder Method](#using-the-aware-builder-method)
  - [Accessing Arbitrary Parent Data](#accessing-arbitrary-parent-data)
- [Property Validation](#property-validation)
  - [Shorthand Validation Rules](#shorthand-validation-rules)
- [Compiler Attributes](#compiler-attributes)
  - [Escaping Compiler Attributes](#escaping-compiler-attributes)
- [Component Name](#component-name)
- [Component Depth](#component-depth)
- [Attribute Forwarding](#attribute-forwarding)
  - [Nested Attribute Forwarding](#nested-attribute-forwarding)
  - [Variable Bindings and Attribute Forwarding](#variable-bindings-and-attribute-forwarding)
- [Slot Forwarding](#slot-forwarding)
  - [Nested Slot Forwarding](#nested-slot-forwarding)
- [Output Trimming](#output-trimming)
- [Stencils](#stencils)
  - [Rendering Default Stencil Content](#rendering-default-stencil-content)
  - [Additional Notes on Stencils](#additional-notes-on-stencils)
- [Mixins](#mixins)
  - [Mixin Methods](#mixin-methods)
  - [Accessing the Component Instance Inside Mixins](#accessing-the-component-instance-inside-mixins)
  - [Additional Notes on Mixins](#additional-notes-on-mixins)
- [Attribute Cache](#attribute-cache)
  - [Slot Variables and the Attribute Cache](#slot-variables-and-the-attribute-cache)
  - [Considerations](#considerations)
- [Static Template Optimizations](#static-template-optimizations)
- [Dynamic Components](#dynamic-components)
- [Custom Component Paths and Namespaces](#custom-component-paths-and-namespaces)
  - [Blade Component Prefix](#blade-component-prefix)
- [The View Manifest](#the-view-manifest)
- [License](#license)

## Frequently Asked Questions

- [How does the Dagger compiler differ from Laravel's component compiler?](#how-does-the-dagger-compiler-differ-from-laravels-component-compiler)
- [Are class-based components supported?](#are-class-based-components-supported)
- [Will this magically make my existing Blade components faster?](#will-this-magically-make-my-existing-blade-components-faster)
- [Can I use regular Blade components with Dagger components?](#can-i-use-regular-blade-components-with-dagger-components)
- [Why are there JSON files in my compiled view folder?](#why-are-there-json-files-in-my-compiled-view-folder)
- [Are circular component hierarchies supported?](#are-circular-component-hierarchies-supported)
- [Why build all of this?](#why-build-all-of-this)

### How does the Dagger compiler differ from Laravel's component compiler?

The Dagger compiler is a multi-stage compiler that recursively parses and compiles a component's template ahead of time. Components compiled with the Dagger compiler will become *part of the view's* compiled output. Because of this, Laravel's related view events will *not* be fired when Dagger components are loaded.

### Are class-based components supported?

Dagger only supports anonymous components, and there are no plans to support class-based components at this time. However, you may use [Mixins](#mixins) to gain back some of the benefits of class-based components when authoring Dagger components.

### Will this magically make my existing Blade components faster?

No. The Dagger compiler only interacts with components using one of the registered component prefixes (`<c-`, by default). While great care has been taken to support Laravel's features, such as `@props`, `@aware`, slots, etc., certain features, such as the attribute cache, may subtly change the behavior of components if they were not designed with these features in mind.

Converting existing anonymous components to Dagger components is a relatively painless process, however.

### Can I use regular Blade components with Dagger components?

Yes. You may use both Blade and Dagger components within the same project.

Dagger components are also interopable with Blade components, and will add themselves to Laravel's component stack, making it seamless to use both. Because of this, you can use features such as `@props` and `@aware` between Dagger and Blade components like normal.

### Why are there JSON files in my compiled view folder?

This is due to the View Manifest. The Dagger compiler and runtime will store which component files were used to create the final output in a JSON file, which is later used for cache-invalidation. The Dagger compiler inlines component templates, which prevents typical file-based cache invalidation from working; the View Manifest solves that problem.

### Are circular component hierarchies supported?

A circular component hierarchy is one where Component A includes Component B, which might conditionally include Component A again. Because the compiler inlines components, circular components are not supported and may result in infinite loops.

### Why build all of this?

Because I wanted to.

But more specifically, I am working on a number of projects that involve *a lot* of components and wanted to reduce the amount of overhead. Additionally, I also wanted to explore what could be done with a more advanced/involved compilation step to support features like Attribute Forwarding, Slot Forwarding, and the Attribute Cache.

## Installation

Dagger requires at *least* Laravel version 11.9 and PHP 8.2.

To install Dagger, you may run the following:

```bash
composer require stillat/dagger
```

Afterwards you can run the following Artisan command to scaffold the required paths if you'd like to build Dagger components within your application:

```bash
php artisan dagger:install
```

After running the `dagger:install` command, you will find new directories within your application's resources directory:

```text
resources/
  dagger/
    views/
```

## Dagger Component View Paths

The views for Dagger components will live in `resources/dagger/views/` instead of `resources/views/components`. This is to help differentiate them from Blade's anonymous components. The rules for Dagger component paths are the same as those for Blade's anonymous components.

If you had defined a component at `resources/dagger/views/alert.blade.php`, you may render it like so:

```blade
<c-alert />
```

Like with Blade's anonymous components, you may use the `.` character to indicate if a component is contained within a sub-directory. For a component defined at `resources/dagger/views/inputs/button.blade.php`, you may render it like so:

```blade
<c-inputs.button />
```

### Index Components

Dagger components follow the same rules as Laravel's [Anonymous Index Components](https://laravel.com/docs/blade#anonymous-index-components), allowing you to group components into their own self-contained directories.

Assuming the following component directory structure:

```text
/resources/dagger/views/accordion.blade.php
/resources/dagger/views/accordion/item.blade.php
```

You could render the accordion component and the nested item like so:

```blade
<c-accordion>
    <c-accordion.item>
        ...
    </c-accordion.item>
</c-accordion>
```

If you'd prefer to not have the "root" view be located within the `/resources/dagger/views/` directory, you may create a file with the same name as the component within the component's directory:

```text
/resources/dagger/views/accordion/accordion.blade.php
/resources/dagger/views/accordion/item.blade.php
```

Alternatively, you may also create a view named `index` within the component's directory:

```text
/resources/dagger/views/accordion/index.blade.php
/resources/dagger/views/accordion/item.blade.php
```

### Moving Dagger Component Views

While it is *strongly discouraged* to move Dagger components into Laravel's `resources/views/components` directory, it is technically possible, and you are free to do what you want in your own project. If you'd like to move the Dagger component path, you may add the following to your applications service provider:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stillat\Dagger\Facades\Compiler;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Compiler::registerComponentPath(
            'c',
            resource_path('views/components')
        );
    }
}
```

While you *can* do this, it will be difficult to tell Dagger and Blade components apart when they are commingled.

## Component Syntax and the Component Builder

The syntax for Dagger components is very similar to Blade components. Instead of `<x-`, you would use `<c-`:

```blade
<c-alert />
```

The Dagger compiler supports Blade's `@props` and `@aware` directives, but also provides a new functional approach to defining components. When using the functional approach, the *first* thing within your component definition *must* be a PHP block, where the component is defined.

The following component definitions would produce identical output.

Using the component builder:

```blade
@php
use function Stillat\Dagger\component;

component()->props(['type' => 'info', 'message']);
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>
```

Using Blade directives:

```blade
@props(['type' => 'info', 'message'])
 
<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>
```

### Slots

Dagger slots behave the same as Blade's component slots. Default slot content is specified between component tag pairs. Assuming the following component definition:

```blade
<!-- /resources/dagger/views/item.blade.php -->

<div {{ $attributes }}>
    {{ $slot }}
</div>
```

Slot content may be specified like so:

```blade
<c-button class="bg-red-500 text-white">
    The Slot Content
</c-button>
```

#### Named/Scoped Slots

Dagger components also support named or *scoped* slots. Accessing scoped slots is done through the `$slots` variable, which is different from Blade components. This is done to help prevent collisions with variables that may have the same name as desireable slot names.


Assuming the following component definition:

```blade
<!-- /resources/dagger/views/panel.blade.php -->
<div {{ $slots->header->attributes }}>
    {{ $slots->header }}
</div>

{{ $slot }}

<div {{ $slots->footer->attributes }}>
    {{ $slots->footer }}
</div>
```

You may specify content for each slot like so:

```blade
<c-docs.namedslot>
    <c-slot:header class="header classes here">
        Header Content
    </c-slot:header>
    
    <c-slot:footer class="header classes here">
        Footer Content
    </c-slot:footer>
    
    Default Slot Content
</c-docs.namedslot>
```

### Custom PHP When Using the Component Function

When using the functional syntax, it is important to note that any PHP code that appears *before* the `component()` call will be removed from the compiled output:

```blade
@php
use function Stillat\Dagger\component;

$myCustomVariable = 'the value';

component()->props(['type' => 'info', 'message']);
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message ?? $myCustomVariable }}
</div>
```

When the above component is rendered you will receive an error stating the `$myCustomVariable` is not defined. To resolve this, any custom PHP code you'd like to execute should appear after the `component()` call:

```blade
@php
use function Stillat\Dagger\component;

component()->props(['type' => 'info', 'message']);

$myCustomVariable = 'the value';
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message ?? $myCustomVariable }}
</div>
```

This does not apply to `use` statements. Any custom PHP code that appears before the `component()` call will be executed at *compile* time, however.

### Calling Component Builder Methods

When using the `component()` builder function, you should only call the `component()` function *once*. Do not make repeated calls to it:

```blade
@php
use function Stillat\Dagger\component;

component()->props(['type' => 'info', 'message']);
component()->aware(['message']); ❌
@endphp

...
```

Instead, always chain the builder methods:

```blade
@php
use function Stillat\Dagger\component;

component()->props(['type' => 'info', 'message'])
    ->aware(['message']); ✅
@endphp

...
```

### Renaming the Component Variable

If you'd like to rename the automatic `$component` variable within your component's definition, you may simply assign the results of the `component()` function to a variable:

```blade
@php
use function Stillat\Dagger\component;

$theAlert = component()->props(['type' => 'info', 'message']);
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$theAlert->type]) }}>
    {{ $theAlert->message }}
</div>
```

> [!NOTE]
> The component instance will still be accessible via the `$component` variable inside slots, even if it has been renamed within the component itself. This is intentional to provide a consistent experience for component consumers.

## Data Properties / Attributes

To ease development, and provide a familar starting place, the Dagger compiler supports Blade's `@props` directive to help differentiate between which data is a *property* of the component, and what data should be placed inside the component's [attribute bag](https://laravel.com/docs/blade#component-attributes).

```blade
<!-- /resources/dagger/views/alert.blade.php -->

@props(['type' => 'info', 'message'])

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>
```

Given the component above, we may render it like so:

```blade
<c-alert type="error" :message="$message" class="mb-4"/>
```

We can also use the `props` component builder method to achieve the same results:

```blade
@php
use function Stillat\Dagger\component;

component()->props(['type' => 'info', 'message']);
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>
```

## Notes on Conditional aware and props Directives

Both the `@aware` and `@props` directives are supported by the Dagger compiler. However, because the compiler needs to know about their values ahead of time, conditional use of these directives is *not* supported in Dagger components.

You **must not** have Dagger component templates like the following:

```blade
@if ($someCondition)
    @props(['title'])
@else
    @props(['title', 'somethingElse'])
    @aware(['title'])
@endif

```

## Accessing Parent Data

You have multiple options for accessing parent data from within a child component.

- Using the aware directive
- Using the aware Builder Method
- Accessing Arbitrary Parent Data

### Using the aware Directive

The most familiar option will be to use Blade's `@aware` directive.

Assuming we had a menu component with a parent `<c-menu>` and a child `<c-menu.item>` component:

```blade
<c-menu color="purple">
    <c-menu.item>...</c-menu.item>
    <c-menu.item>...</c-menu.item>
</c-menu>
```

The `<c-menu>` component may have an implementation like the following:

```blade
<!-- /resources/dagger/views/menu/index.blade.php -->
 
@props([
    'color' => 'gray'
])
 
<ul {{ $attributes->merge(['class' => 'bg-'.$color.'-200']) }}>
    {{ $slot }}
</ul>
```

Because the `color` prop was passed into the parent (`<c-menu>`), it won't be immediately available inside `<c-menu.item>`. We can use the `@aware` directive to make that variable available inside `<c-menu.item>` as well:

```blade
<!-- /resources/dagger/views/menu/item.blade.php -->

@aware([
    'color' => 'gray'
])

<li {{ $attributes->merge(['class' => 'text-'.$color.'-800']) }}>
    {{ $slot }}
</li>
```

### Using the aware Builder Method

Alternatively, we may also use the `aware` builder method to specify variables that should be passed to our child component:

```blade
<!-- /resources/dagger/views/menu/item.blade.php -->

@php
    Stillat\Dagger\component()->aware(['color' => 'gray']);
@endphp

<li {{ $attributes->merge(['class' => 'text-'.$color.'-800']) }}>
    {{ $slot }}
</li>
```

### Accessing Arbitrary Parent Data

We can also access the parent component instance directly using the `parent()` method:

```blade
<!-- /resources/dagger/views/menu/item.blade.php -->

<li {{ $attributes->merge(['class' => 'text-'.$component->parent()->color.'-800']) }}>
    {{ $slot }}
</li>
```

If you are in a deeply nested component, you may access parent instances on parent instances:

```blade
{{ $component->parent()->parent()->someValue }}
```

You may also supply the name of the parent component you'd like to retrieve data from. Doing so will return access to the *nearest* parent instance with that name:

```blade
{{ $component->parent('nav')->someValue }}
```

## Property Validation

You may use Laravel's [validation](https://laravel.com/docs/validation) features to validate the *props* of your Dagger components. To do this, you may use the `validateProps` builder method to specify the prop and validation rules you'd like to enforce. As an example, the following would ensure that a `title` property was supplied to the `button` component:

```blade
<!-- /resources/dagger/views/button.blade.php -->
@php
use function Stillat\Dagger\component;

component()
    ->props(['title'])
    ->validateProps([
        'title' => 'required',
    ]);
@endphp

{{ $title }}
```

The following would not trigger a validation error:

```blade
<c-button title="The Title" />
```

while the following would:

```blade
<c-button />
```

The following data sources are also considered when validating components:

- Data made available via. the `aware` builder method or directive
- Data provided by mixins

### Shorthand Validation Rules

If you are using only string-based validation rules, you can skip the additional method call and specify them on the props directly. To do this, separate the prop name from the rules using the `|` character:

```blade
<!-- /resources/dagger/views/button.blade.php -->
@php
use function Stillat\Dagger\component;

component()
    ->props(['title|required']);
@endphp

{{ $title }}
```

You can still specify default prop values when adding shorthand validation rules:

```blade
@php
use function Stillat\Dagger\component;

component()
    ->props([
        'size|numeric|min:1|max:5' => 3,
    ]);
@endphp

The Size: {{ $size }}
```

## Compiler Attributes

The Dagger compiler introduces the concept of "compiler attributes", which have special meaning to the compiler. The most common of these is the `#id` attribute, which may be used to name a nested component:

```blade
<!-- /resources/dagger/views/component.blade.php -->

<c-nested.component #id="theNestedComponentName" />
```

Compiler attribute values **must** be static and **cannot** contain PHP expressions or reference variables.

### Escaping Compiler Attributes

If you need to output an attribute beginning with `#`, you may escape compiler attributes by prefixing it with another `#` character:

```blade
<!-- /resources/dagger/views/component.blade.php -->

<c-nested.component ##id="the-escaped-id-attribute" />
```

In general, you should avoid using props or attributes beginning with `#` as they are likely to be further developed upon and made available as an extension point, or may conflict with forwarded attributes. The following list of compiler attributes are currently in use, or are reserved for future internal use:

- `#id`
- `#name`
- `#compiler`
- `#style`
- `#def`
- `#group`
- `#styledef`
- `#classdef`
- `#cache`
- `#precompile`

## Component Name

You may access the name of the current component through the `name` property on the component instance:

```blade
<!-- /resources/dagger/views/button.blade.php -->

{{-- Displays "button" --}}
{{ $component->name }}
```

## Component Depth

You may get the current depth of the current component using the `depth` property on the component instance:

```blade

{{ $component->depth }}
```

Each time a component is nested, the depth is increased by one. Depth is also incremented when the parent component is a Blade component.

## Attribute Forwarding

Attribute forwarding is a powerful feature that allows you to set and override props and attributes on *nested* components. For this to work, nested components must have an identifier, which is set using the `#id` compiler attribute.

Imagine we have the following simple toolbar component:

```blade
<!-- /resources/dagger/views/toolbar.blade.php -->

<div>
    <c-docs.button #id="saveButton" text="The Save Button" />
    <c-docs.button #id="cancelButton" text="The Cancel Button" />
</div>
```

and the following button component:

```blade
<!-- /resources/dagger/views/button.blade.php -->
@php
    \Stillat\Dagger\component()
        ->props(['text'])
        ->trimOutput();
@endphp

<button {{ $attributes }}>{{ $text }}</button>

```

If we were to render the following template:

```blade
<c-toolbar />
```

we would receive output similar to the following:

```html
<div>
    <button>The Save Button</button>
    <button>The Cancel Button</button>
</div>
```

If we wanted to allow consumers of the `toolbar` component to modify both the cancel and save buttons, we historically would have to create dedicated props on the parent and pass the values to each child component or define extra slots and pass in our nested components. However, because each of the nested button components has an `#id`, we can use attribute forwarding to set props and attributes on the nested components.

If we adjusted our *template* to the following:

```blade
<c-toolbar
    #saveButton:text="A New Save Button"
    #saveButton:class="mr-0"
    #cancelButton:text="A New Cancel Button"
    #cancelButton:style="display:none"
/>
```

we would now get output similar to the following:

```html
<div>
    <button class="mr-0">A New Save Button</button>
    <button style="display:none">A New Cancel Button</button>
</div>
```

When using attribute forwarding we specify the `#id` of the nested component followed by the `:` character, and then the name of the prop or attribute to update.

### Nested Attribute Forwarding

Attributes and props can be forwarded to nested components. To do so, we separate each nested component name with the `.` character. Imagine we had the following components:

```blade
<!-- /resources/dagger/views/nested_one.blade.php -->
<c-nested_two #id="nestedTwo" />
```

```blade
<!-- /resources/dagger/views/nested_two.blade.php -->
<c-nested_three #id="nestedThree" />
```

```blade
<!-- /resources/dagger/views/nested_three.blade.php -->
@props(['title'])

{{ $title }}
```

We can set the `title` prop on the nested `<c-nested_three>` component from the template using attribute forwarding like so:

```blade
<c-nested_one
    #nestedTwo.nestedThree:title="The Nested Title"
/>
```

### Variable Bindings and Attribute Forwarding

You may pass in variable references when using attribute forwarding by prefixing the forwarded attribute with the `:` character:

```blade
<c-nested_one
    :#nestedTwo.nestedThree:title="$title"
/>
```

## Slot Forwarding

We may specify slot contents on *nested* components. To do so, the nested components must have an identifier specified using the `#id` compiler attribute. Imagine we have the following components:

```blade
<!-- /resources/dagger/views/root.blade.php -->
<c-nested_one #id="componentOne" />
```

```blade
<!-- /resources/dagger/views/nested_one.blade.php -->

<div {{ $slots->header->attributes }}>
    {{ $slots->header }}
</div>

{{ $slot }}

<div {{ $slots->footer->attributes }}>
    {{ $slots->footer }}
</div>
```

We could specify the slot contents on the nested `componentOne` within our template using slot forwarding:

```blade
<c-root>
    <c-slot:componentOne.header class="header classes here">
        Nested Header Content
    </c-slot:componentOne.header>
    
    <c-slot:componentOne.footer class="footer classes here">
        Nested Footer Content
    </c-slot:componentOne.footer>

    <c-slot:componentOne.default>
        Nested Default Content
    </c-slot:componentOne.default>
</c-root>
```

We do not use the `#` symbol when referencing nested slots, and instead separate the nested path using the `.` character. You will also notice that we were able to specify the *default* slot content using the `.default` name.

### Nested Slot Forwarding

We may also set the contents of deeply nested components. To do so, we continue to add the names of nested components, separated by the `.` character. Consider the following components:

```blade
<!-- /resources/dagger/views/root.blade.php -->
<c-nested_one #id="componentOne" />
```

```blade
<!-- /resources/dagger/views/nested_one.blade.php -->
<c-nested_two #id="componentTwo" />
```

```blade
<!-- /resources/dagger/views/nested_two.blade.php -->

<div {{ $slots->header->attributes }}>
    {{ $slots->header }}
</div>

{{ $slot }}

<div {{ $slots->footer->attributes }}>
    {{ $slots->footer }}
</div>
```

we could set the nested slot contents like so:

```blade
<c-root>
    <c-slot:componentOne.componentTwo.header class="header classes here">
        Nested Header Content
    </c-slot:componentOne.componentTwo.header>
    
    <c-slot:componentOne.componentTwo.footer class="footer classes here">
        Nested Footer Content
    </c-slot:componentOne.componentTwo.footer>

    <c-slot:componentOne.componentTwo.default>
        Nested Default Content
    </c-slot:componentOne.componentTwo.default>
</c-root>
```

## Output Trimming

There are times you may wish to trim the output of a component before it is rendered on the client. Instead of manually capturing output, or carefully ensuring that each component file does not contain a final newline, you can instead use the `trimOutput` builder method:

```blade
@php
    \Stillat\Dagger\component()
        ->props(['name'])
        ->trimOutput();
@endphp



{{ $name }}




```

The Dagger compile will now trim the output of the component before adding it to the rest of the response.

> [!NOTE]
> Any leading content, such as HTML comments, before the first `@php ... @endphp` block will be considered content when trimming component output.

## Stencils

Stencils allow consumers of components to override named sections of a component *without* having to publish the component's views. They are similar to slots, but work in a very different way. Slots are a runtime feature, where content is evaluated within the consumer's variable scope and the results are injected in the component as a variable. Stencils, on the other hand, are a *compile time* substitution and become part of the component's compiled output.

Stencils, by themselves, simply create a "named" section of a component's template that the compiler can replace. These regions are created using the special `<c-stencil>` component. Imagine we had the following list component:

```blade
<!-- /resources/dagger/views/list/index.blade.php -->
@props(['items'])

<ul>
    @foreach ($items as $item)
        <c-stencil:list_item>
            <li>{{ $item }}</li>
        </c-stencil:list_item>
    @endforeach
</ul>
```

If we were to render the component like so:

```blade
<c-list
    :items="['Alice', 'Bob', 'Charlie']"
/>
```

Our output would resemble the following:

```html
<ul>
    <li>Alice</li>
    <li>Bob</li>
    <li>Charlie</li>
</ul>
```

However, because the component defined a `list_item` stencil, we can replace that section of the component's template entirely:

```blade
<c-docs.list :items="['Alice', 'Bob', 'Charlie']">
    <c-stencil:list_item>
        <li class="mt-2">{{ Str::upper($item) }}</li>
    </c-stencil:list_item>
</c-docs.list>
```

Rendering the new template would produce output similar to the following:

```html
<ul>
    <li class="mt-2">ALICE</li>
    <li class="mt-2">BOB</li>
    <li class="mt-2">CHARLIE</li>
</ul>
```

### Rendering Default Stencil Content

There may be times where you'd like to change a stencil's template, but conditionally render the original. Building on the list example above, we can accomplish this by using a special `default` modifier provided by the stencil component:

```blade
<c-docs.list :items="['Alice', 'Bob', 'Charlie']">
    <c-stencil:list_item>
        @if ($item === 'Alice')
            <li data-something="special-for-alice">{{ $item }}</li>
        @else
            <c-stencil:list_item.default />
        @endif
    </c-stencil:list_item>
</c-docs.list>
```

Rendering this template would now produce the following output:

```html
<ul>
    <li data-something="special-for-alice">Alice</li>
    <li>Bob</li>
    <li>Charlie</li>
</ul>
```

### Additional Notes on Stencils

A few things to remember when using stencils:

- Stencils do *not* have access to the consumer's scope
- Stencil templates become part of the component's compiled output and have access to the component's internal scope
- Default stencil templates can be injected using the `<c-stencil:stencil_name.default />` component, where `stencil_name` is the name of the stencil
- Stencils, by themselves, have no additional overhead once compiled

## Mixins

Mixins provide a way to inject data and common behaviors into components. Mixins are specified by calling the `mixin` component builder method and supplying either a single class name, or multiple mixin class names.

Mixin classes may define a `data` method, which should return an array of key/value pairs. Imagine we had the following mixin class providing common theme data:

```php
<?php

namespace App\Mixins;

class ThemeData
{
    public function data(): array
    {
        return [
            'background' => 'bg-indigo-500',
        ];
    }
}
```

We could include this mixin in our component like so:

```blade
<!-- /resources/dagger/views/mixin.blade.php -->
@php
    \Stillat\Dagger\component()->mixin([
        \App\Mixins\ThemeData::class,
    ])->props(['background']);
@endphp

<div {{ $attributes->merge(['class' => $background]) }}>
    ...
</div>

```

Data returned by mixins will be injected as variables, like regular props. Prop values will override any values provided by a mixin:

```blade
<c-mixin background="bg-blue-500" />
```

A mixin's `data` method will be invoked *last* when registering the mixin with the component.

### Mixin Methods

Public methods defined within a mixin will be injected as variables within the component's view:

```php
<?php

namespace App\Mixins;

class ProfileMixin
{
    public function sayHello(string $name): string
    {
        return "Hello, {$name}.";
    }
}
```

```blade
<!-- /resources/dagger/views/profile.blade.php -->
@php
    \Stillat\Dagger\component()->mixin([
        \App\Mixins\ProfileMixin::class,
    ])->props(['name']);
@endphp

<div>
    {{ $sayHello($name) }}
</div>

```

If you prefer not to use variables as methods, you may also access mixin methods on the `$component` instance. This is also helpful if you have props that share the same name as methods:

```blade
<!-- /resources/dagger/views/profile.blade.php -->
@php
    \Stillat\Dagger\component()->mixin([
        \App\Mixins\ProfileMixin::class,
    ])->props(['name']);
@endphp

<div>
    {{ $component->sayHello($name) }}
</div>

```

### Accessing the Component Instance Inside Mixins

You can gain access to the `$component` instance within a mixin class by defining a `withComponent` method that accepts the component as its only argument. The `withComponent` method will be invoked *first* if it exists:

```php
<?php

namespace App\Mixins;

use Illuminate\Support\Str;
use Stillat\Dagger\Runtime\Component;

class ComponentMixin
{
    protected ?Component $component = null;

    public function withComponent(Component $component): void
    {
        $this->component = $component;
    }

    public function data(): array
    {
        return [
            'name_upper' => Str::upper($this->component->name),
        ];
    }
}
```

```blade
<!-- /resources/dagger/views/component.blade.php -->
@php
    \Stillat\Dagger\component()->mixin([
        \App\Mixins\ComponentMixin::class,
    ]);
@endphp

<div>
    {{ $name_upper }}
</div>

```

### Additional Notes on Mixins

* Mixin instances are resolved from the service container *each* time a component is used
* The `withComponent` method is always called first, if present
* The `data` method is always called last, if present
* Data provided to mixins via. `data` methods will *not* become part of the `$attributes`, even if they are not listed in the props
* Public methods defined within a mixin will be made available as variables within the component
  * The `withComponent` and `data` methods will *not* be made available

## Attribute Cache

The Dagger compiler and runtime provide an opt-in feature called the attribute cache. This cache mechanism is able to cache the results of components, while still allowing for dynamic slot substition. The attribute cache may be used to prevent re-rendering components when the same attributes are supplied to it, helping to improve performance for heavy, large, or complicated components.

To use the attribute cache simply call the `cache` component builder method:

```blade
<!-- /resources/dagger/views/cached.blade.php -->
@php
    \Stillat\Dagger\component()
        ->props(['title'])
        ->cache();
@endphp

{{ $title }}
```

Assuming the following template, the `<c-cached />` component internals would only be evaulated once because the attributes and props remain the same across both renderings:

```blade
<c-cached title="The Title" />
<c-cached title="The Title" />
```

However, the `<c-cached />` component would be evaluated *twice* in the following template:

```blade
<c-cached title="The Title" />
<c-cached title="The Title" />

<c-cached title="The Second Title" />
<c-cached title="The Second Title" />
```

Any attributes supplied on named/scoped slots will also be added to the internal cache key. All instances of a component will share the same internal attribute cache.

### Slot Variables and the Attribute Cache

Components with slots are still able to take advantage of the attribute cache. Internally, the Dagger compiler will perform string substition on the cached component output. Because of this behavior, you should avoid performing operations on the *results* of slots when enabling the attribute cache:

```blade
@php
    \Stillat\Dagger\component()
        ->props(['title'])
        ->cache();
@endphp

{{ Str::upper($slot) }} // ❌ Don't change the output of slots when using the attribute cache.
```

### Considerations

The attribute cache is a powerful feature that can help to improve performance for heavily re-used components, but there are some things to keep in mind before deciding to use it. Any custom PHP code or dynamic behavior within your component will only be evaluated for each unique rendering of the component.

Consider the following component:

```blade
<!-- /resources/dagger/views/time.blade.php -->
@php
    \Stillat\Dagger\component()
        ->cache();
@endphp

{{ time() }}
```

The results of the `time()` function call would be the same for all of the following renders, since it was cached:

```blade
<c-time />
<c-time />
<c-time />
```

If you're component's internal execution needs to remain dynamic you *should not* use the attribute cache. Because the Dagger compiler inlines components, performance should remain relatively stable in this scenarios, even for heavily-used components.

## Static Template Optimizations

If a component's template does *not* contain any PHP code after compilation, the Dagger compiler will **not** output any component infrastructure for that component. Instead, it will simply inline the static component's content in the view's compiled output. Dagger maintains a "view manifest" which tracks components like this, allowing things like hot reloading and cache busting to continue functioning.

Imagine we had a footer component that contained static HTML contents:

```html
<!-- /resources/dagger/views/footer.blade.php -->
<footer>
    ...
</footer>
```

Rendering the `<c-footer />` component would not output any additional PHP code in the final, compiled output.

## Dynamic Components

If you need to render dynamic Dagger components, you may use the `dynamic-component` component to render a component based on a runtime value or variable:

```blade
// $componentName = "button";

<c-dynamic-component :component="$componentName" class="mt-4" />
```

You may also supply slot content to dynamic components:

```blade
<c-dynamic-component :component="$componentName">
    The Slot Contents
</c-dynamic-component>
```

Dynamic components will be compiled and cached, taking into account any slots, forwarded attributes, or forwarded slots. Dynamic Dagger components can also take advantage of the Attribute Cache.

You may use any of the following aliases to render dynamic components:

```blade
<c-delegate-component :$component />
<c-dynamic-component :$component />
<c-proxy :$component />
```

All three aliases share the same internal behavior.

## Custom Component Paths and Namespaces

If you are writing a component library, you may wish to register your own component namespace and not have to rely on the `<c-` prefix. You may do this within your package's service provider by providing your desired namespace and the path to your component's views:

```php
<?php

namespace Your\Package\Namespace;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Stillat\Dagger\Facades\Compiler;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        Compiler::registerComponentPath(
            'lenz',
            __DIR__.'./../path/to/component/views'
        );
    }
}
```

The first argument is the component prefix or namespace that will be used for your components, and the second argument is the path to the component's views. In the previous example, we supplied `lenz` as the prefix, which means consumers of the components can now write the following, assuming the component views exist:

```blade
<lenz:button class="mt-4" />
<lenz-button class="mt-4" />
```

Custom components can leverage all features of the Dagger compiler using their custom prefix.

### Blade Component Prefix

You are **not** allowed to register the prefix `x` with the Dagger compiler; attempting to do so will raise an `InvalidArgumentException`.

## The View Manifest

You may have noticed JSON files being written to your compiled view folder. These files are created by Dagger's "view manifest", which tracks dependencies of compiled views.

Because the Dagger compiler inlines component views into one larger compiled file, as well as optimizes static templates, the Dagger runtime uses these JSON files to help with cache invalidation.

## License

Dagger is free software, released under the MIT license. Please see [LICENSE.md](LICENSE.md) for more details.
