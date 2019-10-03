<?php
namespace Sleek\Modules;

abstract class Module {
	protected $templateData;
	protected $inflector;
	protected $moduleName;
	protected $snakeName;
	protected $className;
	protected $acfKey;

	public function __construct (array $fields = [], $acfKey = null) {
		# Name some stuff
		$this->inflector = \ICanBoogie\Inflector::get('en');
		$this->className = (new \ReflectionClass($this))->getShortName(); # https://coderwall.com/p/cpxxxw/php-get-class-name-without-namespace;
		$this->snakeName = $this->inflector->underscore($this->className);
		$this->moduleName = str_replace('_', '-', $this->snakeName);
		$this->acfKey = $acfKey;

		# Get field defaults
		$defaultFields = apply_filters('sleek_module_fields', $this->fields());

		# Merge passed in with default
		foreach ($defaultFields as $defaultField) {
			if (isset($defaultField['name']) and !isset($fields[$defaultField['name']])) {
				$fields[$defaultField['name']] = $defaultField['default_value'] ?? null;
			}
		}

		# Store for rendering
		$this->templateData = $fields;
	}

	public function fields () {
		return [];
	}

	public function data () {
		return [];
	}

	public function created () {

	}

	public function get_field ($name) {
		return $this->templateData[$name] ?? null;
	}

	public function render ($template = null) {
		# Work out path to template
		$template = $template ?? apply_filters('sleek_modules_default_template', 'template'); # Default to template.php
		$modulesPath = apply_filters('sleek_modules_path', '/modules/');
		$templatePath = locate_template("$modulesPath{$this->moduleName}/$template.php");

		# We found a template to render the module
		if ($templatePath) {
			\Sleek\Utils\get_template_part("$modulesPath{$this->moduleName}/$template", null, array_merge($this->data(), $this->templateData));
		}
	}
}
