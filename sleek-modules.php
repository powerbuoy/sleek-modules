<?php
namespace Sleek\Modules;

#####################################
# Run all modules' created() callback
add_action('after_setup_theme', function () {
	$path = get_stylesheet_directory() . '/modules/**/module.php';

	foreach (glob($path) as $file) {
		$pathinfo = pathinfo($file);
		$className = \Sleek\Utils\convert_case(basename($pathinfo['dirname']), 'camel');
		$fullClassName = "Sleek\Modules\\$className";

		# Include the class
		require_once $file;

		# Create instance of class
		if (class_exists($fullClassName)) {
			$obj = new $fullClassName;

			# Run callback
			# TODO: Use do_action sleek_module_created_{$moduleName} instead?
			$obj->created();
		}
	}
});

######################
# Render single module
function render ($name, $fields = [], $template = null) {
	$className = \Sleek\Utils\convert_case($name, 'camel');
	$fullClassName = "Sleek\Modules\\$className";

	if (class_exists($fullClassName)) {
		$obj = new $fullClassName($fields);

		$obj->render($template);
	}
	else {
		# TODO: Support for template only module
		# \Sleek\Utils\get_template_part("...", null, $fields);
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
	$fields = [];

	foreach ($modules as $module) {
		$snakeName = \Sleek\Utils\convert_case($module, 'snake');
		$label = \Sleek\Utils\convert_case($module, 'title');
		$className = \Sleek\Utils\convert_case($module, 'camel');
		$fullClassName = "Sleek\Modules\\$className";
		$moduleFields = null;

		# TODO: Support for module->fieldConfig
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

		# Create module class
		if (class_exists($fullClassName)) {
			$obj = new $fullClassName;
			$moduleFields = $obj->get_fields($key);
		}

		# And get potential fields
		if ($moduleFields) {
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
