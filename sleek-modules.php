<?php
namespace Sleek\Modules;

##########################################
# Get array of file meta data in /modules/
function get_file_meta () {
	$path = get_stylesheet_directory() . apply_filters('sleek_modules_path', '/modules/') . '**/module.php';
	$inflector = \ICanBoogie\Inflector::get('en');
	$files = [];

	foreach (glob($path) as $file) {
		$pathinfo = pathinfo($file);
		$name = basename($pathinfo['dirname']);
		$snakeName = $inflector->underscore($name);
		$className = $inflector->camelize($name);
		$label = $inflector->titleize($name);
		$labelPlural = $inflector->pluralize($label);
		$slug = str_replace('_', '-', $snakeName);

		$files[] = (object) [
			'pathinfo' => $pathinfo,
			'name' => $name,
			'filename' => $pathinfo['filename'],
			'snakeName' => $snakeName,
			'className' => $className,
			'fullClassName' => "Sleek\Modules\\$className",
			'label' => $label,
			'labelPlural' => $labelPlural,
			'slug' => $slug,
			'path' => $file
		];
	}

	return $files;
}

####################
# Create all modules
add_action('after_setup_theme', function () {
	if ($files = get_file_meta()) {
		foreach ($files as $file) {
			# Include the class
			require_once $file->path;

			# Create instance of class
			$obj = new $file->fullClassName;

			# Run callback
			$obj->created();
		}
	}
});

######################
# Render single module
# TODO: Support for template only module?
function render ($name, $fields = [], $template = null) {
	$inflector = \ICanBoogie\Inflector::get('en');
	$snakeName = $inflector->underscore($name);
	$className = $inflector->camelize($name);
	$fullClassName = "Sleek\Modules\\$className";

	# Fields is assumed to be an ACF ID
	if (!is_array($fields)) {
		$fields = get_field($snakeName, $fields);
	}

	# Make sure we have some fields
	if ($fields !== null) {
		$obj = new $fullClassName($fields);

		$obj->render($template);
	}
}

###############################
# Render flexible content field
function render_flexible ($name, $postId) {
	if ($modules = get_field($name, $postId)) {
		foreach ($modules as $module) {
			$moduleName = str_replace('_', '-', $module['acf_fc_layout']);

			render($moduleName, $module); # TODO: template
		}
	}
}

####################################
# Returns all ACF fields for modules
# TODO: Support for template only module?
function get_module_fields (array $modules, $key, $layout = 'tabbed') {
	$inflector = \ICanBoogie\Inflector::get('en');
	$fields = [];

	foreach ($modules as $module) {
		$className = $inflector->camelize($module);
		$fullClassName = "Sleek\Modules\\$className";
		$snakeName = $inflector->underscore($module);
		$label = $inflector->titleize($module);

		# TODO: Support for module->fieldConfig
		$field = [
			'name' => $snakeName,
			'label' => __($label, 'sleek'),
			'sub_fields' => []
		];

		# Flexible module
		if ($layout === 'flexible') {
			# TODO: Add template field
		}
		# Sticky module
		else {
			$field['type'] = 'group';

			# With tabs
			if ($layout === 'tabbed') {
				$fields[] = [
					'name' => $snakeName . '_tab',
					'label' => $label,
					'type' => 'tab'
				];
			}
		}

		# Create module class
		# TODO: Support for no class module
		$obj = new $fullClassName($fields, $key);

		# And get potential fields
		if ($moduleFields = apply_filters('sleek_module_fields', $obj->fields())) {
			$field['sub_fields'] = $moduleFields;
		}
		# No fields for this module
		else {
			$field['sub_fields'][] = [
				'name' => 'message',
				'label' => __('No config', 'sleek'),
				'message' => __('This module requires no configuration.', 'sleek')
			];
		}

		$fields[] = $field;
	}

	return \Sleek\Acf\generate_keys($fields, $key);
}
