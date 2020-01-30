<?php
namespace Sleek\Modules;

abstract class Module {
	protected $templateData;
	protected $moduleName;
	protected $snakeName;
	protected $className;
	protected $acfKey;

	# Create module
	public function __construct ($templateData = []) {
		# Name some stuff
		$this->className = (new \ReflectionClass($this))->getShortName(); # https://coderwall.com/p/cpxxxw/php-get-class-name-without-namespace;
		$this->snakeName = \Sleek\Utils\convert_case($this->className, 'snake');
		$this->moduleName = \Sleek\Utils\convert_case($this->className, 'kebab');

		# Set up template data
		$this->set_template_data($templateData);
	}

	# Lifecycle hook - created (called on page load regardless if module is used)
	public function created () {

	}

	# Returns all fields and potential defaults for this module
	public function fields () {
		return [];
	}

	# Additional template data
	public function data () {
		return [];
	}

	# Get a single field
	public function get_field ($name) {
		return $this->templateData[$name] ?? null;
	}

	# Set template data before rendering based on default values and passed in
	public function set_template_data ($templateData = []) {
		# If not an array it's assumed to be an ACF ID
		if (!is_array($templateData) and function_exists('get_field')) {
			$acfData = get_field($this->snakeName, $templateData); # Get the data from ACF
			$templateData = $acfData ? $acfData : []; # Fall back to empty array (NOTE: Not using ?? because an empty string should also fall back to []) (NOTE 2: An empty string can occur if a field by this name once existed on this ID, for example if posts once had a "next-post" module that was later removed ACF still stores an empty string in the database for some reason) (???)
		}

		# Get field defaults
		$defaultFields = apply_filters('sleek_module_fields', $this->fields(), $this->moduleName, null);

		# Merge passed in templateData with default fields
		foreach ($defaultFields as $defaultField) {
			if (isset($defaultField['name']) and !isset($templateData[$defaultField['name']])) {
				$templateData[$defaultField['name']] = $defaultField['default_value'] ?? null;
			}
		}

		# Store for rendering
		$this->templateData = $templateData;
	}

	# Render module
	public function render ($template = null) {
		# Work out path to template
		$template = $template ?? $this->get_field('template') ?? 'template'; # Default to template.php
		$modulesPath = '/modules/';
		$templatePath = locate_template("$modulesPath{$this->moduleName}/$template.php");

		# We found a template to render the module
		if ($templatePath) {
			\Sleek\Utils\get_template_part("$modulesPath{$this->moduleName}/$template", null, array_merge($this->data(), $this->templateData));
		}
		else {
			trigger_error("Sleek\Modules\\{$this->className}->render($template): failed opening '$template' for rendering", E_USER_WARNING);
		}
	}
}
