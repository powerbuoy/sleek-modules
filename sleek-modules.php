<?php
namespace Sleek\Modules;

require_once __DIR__ . '/add-new-module-preview.php';
require_once __DIR__ . '/global-modules.php';
require_once __DIR__ . '/inline-edit-flex-module.php';
require_once __DIR__ . '/preview-dummy-module.php';
require_once __DIR__ . '/preview-flex-module.php';
require_once __DIR__ . '/render-dummies.php';

################################
# Run all modules' init callback
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
			$obj->init();
		}
		else {
			trigger_error("\Sleek\Modules\create_module($className): module '$className' does not exist", E_USER_WARNING);
		}
	}
});

######################################
# Checks whether module exists in area
function has_module ($module, $area, $id = null) {
	$id = $id ?? get_the_ID();

	if ($modules = get_field($area, $id)) {
		foreach ($modules as $mod) {
			$moduleName = \Sleek\Utils\convert_case($mod['acf_fc_layout'], 'kebab');

			if ($moduleName === $module) {
				return true;
			}
		}
	}

	return false;
}

######################
# Render single module
function render ($name, $fields = null, $template = null) {
	$fields = $fields ?? get_the_ID();
	$className = \Sleek\Utils\convert_case($name, 'pascal');
	$fullClassName = "Sleek\Modules\\$className";

	# A modules/module-name/module.php type module
	if (class_exists($fullClassName)) {
		do_action('sleek/modules/pre_render', $name, $fields, $template);

		$obj = new $fullClassName;
		$obj->render($fields, $template);
	}
	else {
		# A modules/module-name.php type module
		if (locate_template("modules/$name.php")) {
			do_action('sleek/modules/pre_render', $name, $fields, $template);

			\Sleek\Utils\get_template_part("modules/$name", null, $fields);
		}
		# No class and no template
		else {
			trigger_error("Sleek\Modules\\render({$name}): module '$name' does not exist", E_USER_WARNING);
		}
	}
}

###############################
# Render flexible content field
function render_flexible ($where, $id = null) {
	$id = $id ?? get_the_ID();

	if (!function_exists('get_field')) {
		trigger_error("get_field() does not exist, have you installed Advanced Custom Fields?", E_USER_WARNING);

		return;
	}

	if ($modules = get_field($where, $id)) {
		do_action('sleek/modules/pre_render_flexible', $where, $id);

		$i = 0;

		foreach ($modules as $module) {
			$moduleName = \Sleek\Utils\convert_case($module['acf_fc_layout'], 'kebab');

			do_action('sleek/modules/pre_render_flexible_module', $where, $id, $moduleName, $module, $i++);

			render($moduleName, $module);
		}
	}
}

####################################
# Returns all ACF fields for modules
# $layout can be one of 'flexible', 'tabs', 'accordion', or 'normal'
function get_module_fields (array $modules, $layout = 'normal', $withTemplates = true) {
	$fields = [];

	foreach ($modules as $module) {
		# TODO: Move to get_single_module_fields ? yes but without group, tabs etc, only flat fields with templates
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
			$field['wrapper'] = [
				'class' => 'sleek-module-group'
			];

			# With tabs
			if ($layout === 'tabs') {
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
			$moduleFields = $obj->filtered_fields();
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

		# Insert module templates
		# TODO: Support for HIDDEN modules??
		if ($withTemplates and ($tmp = get_module_templates($module))) {
			$templates = [];

			foreach ($tmp as $t) {
				$templates[$t['filename']] = $t['title'] . ($t['readme'] ? ' - ' . $t['readme'] : '');
			}

			if (count($templates) > 1) {
				array_unshift($field['sub_fields'], [
					'name' => 'template',
					'label' => __('Template', 'sleek'),
					'type' => 'select',
					'choices' => $templates,
					'default_value' => 'template',
					'ui' => true
				]);
			}
		}

		$fields[] = $field;
	}

	# Generate unique keys for each field
	return $fields;
}

#################################################
# Return array of available templates for $module
# TODO: Should be Module->get_templates()
function get_module_templates ($module) {
	$path = get_stylesheet_directory() . '/modules/' . $module . '/*.php';
	$templates = [];

	foreach (glob($path) as $template) {
		$pathinfo = pathinfo($template);

		if ($pathinfo['filename'] !== 'module' and substr($pathinfo['filename'], 0, 2) !== '__') {
			$readmePath = get_stylesheet_directory() . '/modules/' . $module . '/README-' . $pathinfo['filename'] . '.md';
			$screenshotPath = get_stylesheet_directory() . '/modules/' . $module . '/' . $pathinfo['filename'] . '.png';
			$screenshotUrl = get_stylesheet_directory_uri() . '/modules/' . $module . '/' . $pathinfo['filename'] . '.png';
			$templates[] = [
				'filename' => $pathinfo['filename'],
				'title' => $pathinfo['filename'] === 'template' ? __('Default Template', 'sleek') : \Sleek\Utils\convert_case($pathinfo['filename'], 'title'),
				'readme' => file_exists($readmePath) ? trim(file_get_contents($readmePath)) : null,
				'screenshot' => file_exists($screenshotPath) ? $screenshotUrl : null
			];
		}
	}

	sort($templates);

	return $templates;
}
