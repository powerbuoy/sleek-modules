<?php
namespace Sleek\Modules;

###################################
# Run all modules' created callback
add_action('after_setup_theme', function () {
	$path = get_stylesheet_directory() . '/modules/**/module.php';

	foreach (glob($path) as $file) {
		$pathinfo = pathinfo($file);
		$className = \Sleek\Utils\convert_case(basename($pathinfo['dirname']), 'camel');
		$fullClassName = "Sleek\Modules\\$className";

		# Include the class
		require_once $file;

		# Create instance of class and run callback
		if (class_exists($fullClassName)) {
			# $obj = new $fullClassName;
			# $obj->created(); # TODO: Use do_action sleek_module_created($moduleName) instead?
		}
	}
});

######################
# Render single module
function render ($name, $fields = [], $template = null) {
	$className = \Sleek\Utils\convert_case($name, 'camel');
	$fullClassName = "Sleek\Modules\\$className";

	# A modules/module-name/module.php type module
	if (class_exists($fullClassName)) {
		$obj = new $fullClassName($fields);

		$obj->render($template);
	}
	else {
		# A modules/module-name.php type module
		if (locate_template("modules/$name.php")) {
			\Sleek\Utils\get_template_part("modules/$name", null, $fields);
		}
		# No class and no template
		else {
			# TODO: throw exception? message on the page?
		}
	}
}

###############################
# Render flexible content field
function render_flexible ($name, $id) {
	if ($modules = get_field($name, $id)) {
		foreach ($modules as $module) {
			$moduleName = \Sleek\Utils\convert_case($module['acf_fc_layout'], 'kebab');

			render($moduleName, $module); # TODO: template
		}
	}
	else {
		# TODO: throw exception? message on the page?
	}
}

####################################
# Returns all ACF fields for modules
# $layout can be one of 'flexible', 'tabbed' or 'normal'
function get_module_fields (array $modules, $key, $layout = 'normal') {
	$fields = [];

	foreach ($modules as $module) {
		$snakeName = \Sleek\Utils\convert_case($module, 'snake');
		$label = \Sleek\Utils\convert_case($module, 'title');
		$className = \Sleek\Utils\convert_case($module, 'camel');
		$fullClassName = "Sleek\Modules\\$className";
		$moduleFields = null;

		# TODO: Support for module->fieldConfig (or apply_filters sleek_module_fields_config)
		$field = [
			'name' => $snakeName,
			'label' => __($label, 'sleek'),
			'sub_fields' => []
		];

		# Flexible module
		if ($layout === 'flexible') {
			# TODO: Add template field (and "HIDDEN" if module->fieldConfig->is_hidable)
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

		# Create module class and get fields
		if (class_exists($fullClassName)) {
			$obj = new $fullClassName;
			$moduleFields = $obj->get_fields($key);
		}

		# We have fields
		if ($moduleFields) {
			$field['sub_fields'] = $moduleFields;
		}
		# No fields for this module
		else {
			$field['sub_fields'][] = [
				'name' => 'message',
				'type' => 'message',
				'label' => __('No config', 'sleek'),
				'message' => __('This module requires no configuration.', 'sleek')
			];
		}

		$fields[] = $field;
	}

	# Generate unique keys for each field
	return \Sleek\Acf\generate_keys($fields, $key);
}
