<?php
namespace Sleek\Modules;

abstract class Module {
	public function __construct () {

	}

	public function created () {

	}

	public function get_field ($name) {

	}

	public function fields () {
		return [];
	}

	public function data () {
		return [];
	}

	public function render ($template = null, $fields = []) {
		$inflector = \ICanBoogie\Inflector::get('en');

		# Work out path to template
		$template = $template ?? apply_filters('sleek_modules_default_template', 'template'); # Default to template.php
		$modulesPath = apply_filters('sleek_modules_path', '/modules/');
		$className = (new \ReflectionClass($this))->getShortName(); # https://coderwall.com/p/cpxxxw/php-get-class-name-without-namespace
		$snakeName = $inflector->underscore($className);
		$moduleName = str_replace('_', '-', $snakeName);
		$templatePath = locate_template("$modulesPath$moduleName/$template.php");

		# We found a template to render the module
		if ($template) {
			# Get field defaults
			$defaultFields = $this->fields();

			# Passed in fields is ACF post_id
			if (!is_array($fields)) {
				$fields = get_field($snakeName, $fields);
			}

			# Merge passed in with default
			foreach ($defaultFields as $field) {
				if (isset($field['name']) and !isset($fields[$field['name']])) {
					$fields[$field['name']] = $field['default_value'] ?? null;
				}
			}

			# Include template - pass in fields and data
			\Sleek\Utils\get_template_part("$modulesPath$moduleName/$template", null, array_merge($this->data(), $fields));
		}
	}
}
