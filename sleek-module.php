<?php
namespace Sleek\Modules;

abstract class Module {
	protected $templateData;
	protected $moduleName;
	protected $snakeName;
	protected $className;
	protected $acfKey;
	protected static $fileHeaders = [
		'name' => 'Name',
		'description' => 'Description',
		'category' => 'Category',
		'default_template' => 'Default Template',
		'default_block_template' => 'Default Block Template',
		'author' => 'Author',
		'author_uri' => 'Author URI',
		'version' => 'Version',
		'tags' => 'Tags',
		'requires_wp'  => 'Requires at least',
		'requires_php' => 'Requires PHP',
		'dashicon' => 'Dashicon'
	];

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

	# ACF Gutenberg block config data
	public function block_config () {
		return [];
	}

	# Get a single field
	public function get_field ($name) {
		return $this->templateData[$name] ?? null;
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
			$additionalData = $this->data();

			if (!is_array($additionalData)) {
				trigger_error("Sleek\Modules\\{$this->className}->data() did not return an array", E_USER_WARNING);

				$additionalData = [];
			}

			\Sleek\Utils\get_template_part($templatePath, null, array_merge($additionalData, $this->templateData));
		}
		# No template found!
		else {
			trigger_error("Sleek\Modules\\{$this->className}->render($template): failed opening '$template' for rendering", E_USER_WARNING);
		}
	}

	# Return array of templates for this module
	public function templates () {
		# Get all templates
		$templates = $this->get_templates($this->path . '/*.php');

		# Filter out block templates
		$templates = array_filter($templates, function ($template) {
			return substr($template['filename'], 0, strlen('block-')) !== 'block-';
		});

		return $templates;
	}

	# Return array of block templates for this module
	public function block_templates () {
		return $this->get_templates($this->path . '/block-*.php', 'block-template');
	}

	# Return array of templates (all files not named module.php and not prefixed with __ in $path)
	public function get_templates ($path, $defaultTemplate = 'template') {
		$templates = [];

		foreach (glob($path) as $template) {
			$filename = pathinfo($template)['filename'];

			if ($filename !== 'module' and substr($filename, 0, 2) !== '__') {
				$screenshotPath = "{$this->path}/$filename.png";
				$screenshotUrl = "{$this->uri}/$filename.png";

				if (is_admin()) {
					$meta = get_file_data($template, self::$fileHeaders);
				}

				$meta['screenshot'] = file_exists($screenshotPath) ? $screenshotUrl : null;
				$meta['filename'] = $filename;

				if ($filename === $defaultTemplate && empty($meta['name'])) {
					$meta['name'] = __('Default Template', 'sleek_admin');
				}
				elseif ($filename !== $defaultTemplate) {
					$meta['name'] = empty($meta['name']) ? \Sleek\Utils\convert_case($filename, 'title') : $meta['name'];
				}

				$templates[] = $meta;
			}
		}

		sort($templates);

		return count($templates) ? array_values($templates) : null;
	}

	# Return meta data about module
	public function meta () {
		$iconPath = $this->path . '/icon.svg';
		$iconUrl = $this->uri . '/icon.svg';

		if (is_admin()) {
			$meta = get_file_data($this->path . '/module.php', self::$fileHeaders);
		}

		$meta['name'] = empty($meta['name']) ? \Sleek\Utils\convert_case($this->moduleName, 'title') : $meta['name'];
		$meta['icon'] = file_exists($iconPath) ? $iconUrl : null;
		$meta['icon_path'] = file_exists($iconPath) ? $iconPath : null;
		$meta['default_template'] = empty($meta['default_template']) ? 'template' : $meta['default_template'];
		$meta['default_block_template'] = empty($meta['default_block_template']) ? 'block-template' : $meta['default_block_template'];
		$meta['block_template'] = file_exists($this->path . '/block-template.php') ? $this->uri . '/block-template.php' : null;
		$meta['block_style'] = file_exists($this->path . '/block.css') ? $this->uri . '/block.css' : null;

		if (!file_exists($this->path . '/' . $meta['default_template'] . '.php')) {
			trigger_error("Sleek\Modules\\{$this->className}: The default template ({$meta['default_template']}) for this module does not exist", E_USER_ERROR);
		}

		return $meta;
	}
}
