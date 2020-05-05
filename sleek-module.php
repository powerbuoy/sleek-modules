<?php
namespace Sleek\Modules;

abstract class Module {
	protected $templateData;
	protected $moduleName;
	protected $snakeName;
	protected $className;
	protected $acfKey;

	# Create module
	public function __construct () {
		# Name some stuff
		$this->className = (new \ReflectionClass($this))->getShortName(); # https://coderwall.com/p/cpxxxw/php-get-class-name-without-namespace;
		$this->snakeName = \Sleek\Utils\convert_case($this->className, 'snake');
		$this->moduleName = \Sleek\Utils\convert_case($this->className, 'kebab');

		# Store paths
		$this->path = get_stylesheet_directory() . '/modules/' . $this->moduleName;
		$this->uri = get_stylesheet_directory_uri() . '/modules/' . $this->moduleName;
	}

	# Lifecycle hook - init (called on page load regardless if module is used)
	public function init () {}

	# Returns all fields and potential defaults for this module
	public function fields () {
		return [];
	}

	# Additional template data
	public function data () {
		return [];
	}

	# Returns $this->fields() but passed through a filter
	public function filtered_fields () {
		$filteredFields = apply_filters('sleek/modules/fields', $this->fields(), $this->moduleName);

		# Make sure filter didn't fuck up fields array
		if (!is_array($filteredFields)) {
			trigger_error("Sleek\Modules\\{$this->className}->filtered_fields(): The filter 'sleek/modules/fields' did not return an array", E_USER_WARNING);

			$filteredFields = [];
		}

		return $filteredFields;
	}

	# Get a single field
	public function get_field ($name) {
		return $this->templateData[$name] ?? null;
	}

	# Render module
	public function render ($template = null, array $data = []) {
		# Get field defaults
		$defaultFields = $this->filtered_fields();

		# Merge passed in data with default fields
		foreach ($defaultFields as $defaultField) {
			if (isset($defaultField['name']) and !isset($data[$defaultField['name']])) {
				$data[$defaultField['name']] = $defaultField['default_value'] ?? null;
			}
		}

		# Store for rendering (NOTE: and before calling $this->data() which might use $this->get_field())
		$this->templateData = $data;

		# Work out path to template (NOTE: Uses $this->get_field() so needs to happen after templateData is set)
		$template = $template ?? $this->get_field('template') ?? 'template'; # Use passed in $template then potential field-template and default to template.php
		$templatePath = "modules/{$this->moduleName}/$template";

		# We found a template to render the module
		if (locate_template("$templatePath.php")) {
			\Sleek\Utils\get_template_part($templatePath, null, array_merge($this->templateData, $this->data()));
		}
		# No template found!
		else {
			trigger_error("Sleek\Modules\{$this->className}->render($template): failed opening '$template' for rendering", E_USER_WARNING);
		}
	}

	# Return array of templates for this module
	public function templates () {
		$path = $this->path . '/*.php';
		$templates = [];

		foreach (glob($path) as $template) {
			$filename = pathinfo($template)['filename'];

			if ($filename !== 'module' and substr($filename, 0, 2) !== '__') {
				$readmePath = "{$this->path}/$filename.md";
				$screenshotPath = "{$this->path}/$filename.png";
				$screenshotUrl = "{$this->uri}/$filename.png";

				$templates[] = [
					'filename' => $filename,
					'title' => $filename === 'template' ? __('Default Template', 'sleek') : \Sleek\Utils\convert_case($filename, 'title'),
					'readme' => file_exists($readmePath) ? htmlspecialchars(trim(file_get_contents($readmePath))) : null,
					'screenshot' => file_exists($screenshotPath) ? $screenshotUrl : null
				];
			}
		}

		sort($templates);

		return $templates;
	}

	# Return meta data about module
	public function meta () {
		$readmePath = $this->path . '/README.md';
		$screenshotPath = $this->path . '/template.png';
		$screenshotUrl = $this->uri . '/template.png';
		$iconPath = $this->path . '/icon.svg';
		$iconUrl = $this->uri . '/icon.svg';

		$title = \Sleek\Utils\convert_case($this->moduleName, 'title');
		$titlePlural = \Sleek\Utils\convert_case($this->moduleName, 'plural');

		return [
			'title' => $title,
			'title_plural' => $titlePlural,
			'readme' => file_exists($readmePath) ? htmlspecialchars(trim(file_get_contents($readmePath))) : null,
			'screenshot' => file_exists($screenshotPath) ? $screenshotUrl : null,
			'icon' => file_exists($iconPath) ? $iconUrl : null
		];
	}
}
