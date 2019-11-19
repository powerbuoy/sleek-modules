<?php
namespace Sleek\Modules;

###################################
# Run all modules' created callback
add_action('after_setup_theme', function () {
	$path = get_stylesheet_directory() . '/modules/**/module.php';

	foreach (glob($path) as $file) {
		$pathinfo = pathinfo($file);
		$className = \Sleek\Utils\convert_case(basename($pathinfo['dirname']), 'pascal');
		$fullClassName = "Sleek\Modules\\$className";

		# Include the class
		require_once $file;

		# Create instance of class and run callback
		if (class_exists($fullClassName)) {
			$obj = new $fullClassName;
			$obj->created();
		}
		else {
			trigger_error("\Sleek\Modules\create_module($className): module '$className' does not exist", E_USER_WARNING);
		}
	}
});

######################
# Render single module
function render ($name, $fields = [], $template = null) {
	$className = \Sleek\Utils\convert_case($name, 'pascal');
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
			trigger_error("Sleek\Modules\\render({$className}): module '$className' does not exist", E_USER_WARNING);
		}
	}
}

###############################
# Render flexible content field
function render_flexible ($name, $id = null) {
	$id = $id ?? get_the_ID();

	if (!function_exists('get_field')) {
		trigger_error("get_field() does not exist, have you installed Advanced Custom Fields?", E_USER_WARNING);

		return null;
	}

	if ($modules = get_field($name, $id)) {
		foreach ($modules as $module) {
			$moduleName = \Sleek\Utils\convert_case($module['acf_fc_layout'], 'kebab');
			$template = $module['template'] ?? 'template';

			render($moduleName, $module, $template);
		}
	}
	else {
		trigger_error("Sleek\Modules\\render_flexible($name): no modules found", E_USER_NOTICE);
	}
}

#################################################
# Return array of available templates for $module
function get_module_templates ($module) {
	$path = get_stylesheet_directory() . '/modules/' . $module . '/*.php';
	$templates = [];

	foreach (glob($path) as $template) {
		$pathinfo = pathinfo($template);

		if ($pathinfo['filename'] !== 'module' and substr($pathinfo['filename'], 0, 2) !== '__') {
			$templates[] = $pathinfo['filename'];
		}
	}

	sort($templates);

	return $templates;
}

####################################
# Returns all ACF fields for modules
# $layout can be one of 'flexible', 'tabbed' or 'normal'
function get_module_fields (array $modules, $key, $layout = 'normal') {
	$fields = [];

	foreach ($modules as $module) {
		$snakeName = \Sleek\Utils\convert_case($module, 'snake');
		$label = \Sleek\Utils\convert_case($module, 'title');
		$className = \Sleek\Utils\convert_case($module, 'pascal');
		$fullClassName = "Sleek\Modules\\$className";
		$moduleFields = null;

		# Create field group
		$field = [
			'name' => $snakeName,
			'label' => __($label, 'sleek'),
			'sub_fields' => []
		];

		# Sticky module
		if ($layout !== 'flexible') {
			$field['type'] = 'group';

			# With tabs
			if ($layout === 'tabbed') {
				$fields[] = [
					'name' => $snakeName . '_tab',
					'label' => $label,
					'type' => 'tab'
				];
			}
			# Accordion
			elseif ($layout === 'accordion') {
				$fields[] = [
					'name' => $snakeName . '_accordion',
					'label' => $label,
					'type' => 'accordion'
				];
			}
		}

		# Create module class and get fields
		if (class_exists($fullClassName)) {
			$obj = new $fullClassName;
			$moduleFields = $obj->get_acf_fields($key);
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

		# Flexible module - insert templates
		# TODO: Support for HIDDEN modules??
		if ($layout === 'flexible' and ($tmp = get_module_templates($module))) {
			$templates = [];

			foreach ($tmp as $t) {
				$templates[$t] = \Sleek\Utils\convert_case($t, 'title');
			}

			if (count($templates) > 1) {
				array_unshift($field['sub_fields'], [
					'name' => 'template',
					'label' => __('Template', 'sleek'),
					'type' => 'select',
					'choices' => $templates,
					'default_value' => 'template'
				]);
			}
		}

		$fields[] = $field;
	}

	# Generate unique keys for each field
	return \Sleek\Acf\generate_keys($fields, $key);
}
