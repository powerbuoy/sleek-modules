# [Sleek Modules](https://github.com/powerbuoy/sleek-modules/)

[![Packagist](https://img.shields.io/packagist/vpre/powerbuoy/sleek-modules.svg?style=flat-square)](https://packagist.org/packages/powerbuoy/sleek-modules)
[![GitHub license](https://img.shields.io/github/license/powerbuoy/sleek-modules.svg?style=flat-square)](https://github.com/powerbuoy/sleek-modules/blob/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/powerbuoy/sleek-modules.svg?style=flat-square)](https://github.com/powerbuoy/sleek-modules/issues)
[![GitHub forks](https://img.shields.io/github/forks/powerbuoy/sleek-modules.svg?style=flat-square)](https://github.com/powerbuoy/sleek-modules/network)
[![GitHub stars](https://img.shields.io/github/stars/powerbuoy/sleek-modules.svg?style=flat-square)](https://github.com/powerbuoy/sleek-modules/stargazers)

Create modules by creating classes in `/modules/`.

## Theme Support

### `sleek/modules/add_new_module_preview`

Enable screenshots and descriptions of modules when clicking "Add a Module".

### `sleek/modules/global_modules`

Enable "Global Modules" (WIP).

### `sleek/modules/inline_edit`

Enable inline editing of flexible modules.

### `sleek/modules/module_preview`

Enable module preview in admin.

## Filters

### `sleek/modules/global_modules(array $modules)`

Return an array of module names here to enable them as global modules.

### `sleek/modules/dummy_field_value($value, $field, $module, $template, $level)`

Return a `$value` from here to use that value when rendering the field with dummy data.

### `sleek/modules/fields(array $fields, $moduleName, $args)`

Filter the ACF fields for modules before they're added. This allows you to add "global" fields to several modules at once.

## Actions

TODO...

## Functions

### `Sleek\Modules\render($module, $fields, $template)`

Render module `$module` using (optional) fields `$fields` (or ACF location like a term, options page or set to `null` to fetch fields from `get_the_ID()`) using (optional) template `$template`.

### `Sleek\Modules\render_flexible($area, $id)`

Render flexible modules contained in flexible content area `$area` using (optional) `$id` as ACF location.

### `Sleek\Modules\get_module_fields(array $modules, $layout, $withTemplates)`

Fetch ACF fields for all `$modules` and use layout `$layout` (`tabs`, `accordion`, `normal` or `flexible`). Optionally give every module group a `Template` dropdown using `$withTemplates = true`.

### `Sleek\Modules\render_dummies(array $modules)`

Render all `$modules` using dummy data.

## Classes

### `Sleek\Modules\Module`

Extend this class to create a module.

#### `Module::init()`

This method is called once on every page load. It allows you to add hooks or do whatever you like related to your module. Note that it runs whether or not the module is used on the current page.

#### `Module::fields()`

Return an array of ACF fields from here and they will be added to the module.

#### `Module::data()`

Return an array from here and each array property will be available in the module template.

#### `Module::get_field($name)`

Return the value of any field returned from `fields()`. Useful inside `data()` to check module configuration.
