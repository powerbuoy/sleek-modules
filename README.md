# [Sleek Modules](https://github.com/powerbuoy/sleek-modules/)

[![Packagist](https://img.shields.io/packagist/vpre/powerbuoy/sleek-modules.svg?style=flat-square)](https://packagist.org/packages/powerbuoy/sleek-modules)
[![GitHub license](https://img.shields.io/github/license/powerbuoy/sleek-modules.svg?style=flat-square)](https://github.com/powerbuoy/sleek-modules/blob/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/powerbuoy/sleek-modules.svg?style=flat-square)](https://github.com/powerbuoy/sleek-modules/issues)
[![GitHub forks](https://img.shields.io/github/forks/powerbuoy/sleek-modules.svg?style=flat-square)](https://github.com/powerbuoy/sleek-modules/network)
[![GitHub stars](https://img.shields.io/github/stars/powerbuoy/sleek-modules.svg?style=flat-square)](https://github.com/powerbuoy/sleek-modules/stargazers)

Create modules by creating classes in `/modules/`.

## Theme Support

### `sleek/modules/inline_edit`

Enable inline editing of flexible modules.

### `sleek/modules/module_preview`

Enable module preview in admin.

## Hooks

### `sleek/modules/fields`

Filter the ACF fields for modules before they're added. This allows you to add "global" fields to several modules at once.

### `sleek/modules/get_dummy_field/?type=?&name=?&module=?`

Filter dummy data used by `render_dummies()`.

### `sleek/modules/pre_render`

Hook that runs before rendering a single module.

### `sleek/modules/pre_render_flexible`

Hook that runs before rendering an array of flexible modules.

### `sleek/modules/pre_render_flexible_module`

Hook that runs before rendering a single module in an an array of flexible modules.

## Functions

### `Sleek\Modules\has_module($module, $area, $id)`

Check whether `$module` exists in `$area` at (optional) location `$id` (defaults to `get_the_ID()`).

### `Sleek\Modules\render($module, $fields, $template)`

Render module `$module` using (optional) fields `$fields` (or ACF location like a term, options page or set to `null` to fetch fields from `get_the_ID()`) using (optional) template `$template`.

### `Sleek\Modules\render_flexible($where, $id)`

Render flexible modules contained in flexible content area `$where` using (optional) `$id` as ACF location.

### `Sleek\Modules\get_module_fields(array $modules, $layout, $withTemplates)`

Fetch ACF fields for all `$modules` and use layout `$layout` (`tabs`, `accordion`, `normal` or `flexible`). Optionally give every module group a `Template` dropdown using `$withTemplates = true`.

### `Sleek\Modules\get_module_templates($module)`

Return all templates for `$module`.

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
