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
		foreach ($modules as $module) {
			$moduleName = \Sleek\Utils\convert_case($module['acf_fc_layout'], 'kebab');

			render($moduleName, $module);
		}
	}
}

####################################
# Returns all ACF fields for modules
# $layout can be one of 'flexible', 'tabbed' or 'normal'
function get_module_fields (array $modules, $layout = 'normal', $withTemplates = true) {
	$fields = [];

	foreach ($modules as $module) {
		# TODO: Move to get_single_module_fields ?
		# TODO: Add support for sleek_module_fields-filter to auto-add fields to all modules (like background-color etc)
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
			$moduleFields = $obj->fields();
		}

		# We have fields
		if ($moduleFields) {
			$field['sub_fields'] = apply_filters('sleek_module_fields', $moduleFields, $module, $layout);
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
function get_module_templates ($module) {
	$path = get_stylesheet_directory() . '/modules/' . $module . '/*.php';
	$templates = [];

	foreach (glob($path) as $template) {
		$pathinfo = pathinfo($template);

		if ($pathinfo['filename'] !== 'module' and substr($pathinfo['filename'], 0, 2) !== '__') {
			$readmePath = get_stylesheet_directory() . '/modules/' . $module . '/README-' . $pathinfo['filename'] . '.md';
			$templates[] = [
				'filename' => $pathinfo['filename'],
				'title' => $pathinfo['filename'] === 'template' ? __('Default Template', 'sleek') : \Sleek\Utils\convert_case($pathinfo['filename'], 'title'),
				'readme' => file_exists($readmePath) ? trim(file_get_contents($readmePath)) : null
			];
		}
	}

	sort($templates);

	return $templates;
}

######################
# Render dummy modules
function render_dummies ($modules) {
	foreach ($modules as $module) {
		$className = \Sleek\Utils\convert_case($module, 'pascal');
		$fullClassName = "Sleek\Modules\\$className";
		$templates = get_module_templates($module);
		$fields = apply_filters('sleek_module_fields', (new $fullClassName)->fields(), $module, null);

		foreach ($templates as $template) {
			$template = $template['filename'];
			$data = [];

			foreach ($fields as $field) {
				$data[$field['name']] = render_dummies_apply_filters(null, $module, $template, $field);
			}

			render($module, $data, $template);
		}
	}
}

function render_dummies_apply_filters ($value, $module, $template, $field) {
	$value = apply_filters('sleek_get_dummy_field/?type=' . $field['type'] . '&name=' . $field['name'] . '&module=' . $module, null, $module, $template, $field) ??
			apply_filters('sleek_get_dummy_field/?type=' . $field['type'] . '&name=' . $field['name'], null, $module, $template, $field) ??
			apply_filters('sleek_get_dummy_field/?type=' . $field['type'], null, $module, $template, $field);

	return	$value;
}

#######
# Basic
# Text Title
# TODO: Check prepend/append
add_filter('sleek_get_dummy_field/?type=text&name=title', function ($value, $module, $template, $field) {
	return \Sleek\Utils\convert_case($module, 'title') . ': ' . $template;
}, 10, 4);

# Text
# TODO: Check prepend/append
add_filter('sleek_get_dummy_field/?type=text', function ($value, $module, $template, $field) {
	return 'Lorem ipsum dolor sit amet.';
}, 10, 4);

# Textarea
# TODO: Check maxlength
add_filter('sleek_get_dummy_field/?type=textarea', function ($value, $module, $template, $field) {
	return 'Lorem ipsum dolor sit amet. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.';
}, 10, 4);

# Number
# TODO: Check min/max + prepend/append
add_filter('sleek_get_dummy_field/?type=number', function ($value, $module, $template, $field) {
	return rand(0, 999);
}, 10, 4);

# Range
# TODO...
add_filter('sleek_get_dummy_field/?type=range', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Email
# TODO...
add_filter('sleek_get_dummy_field/?type=email', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# URL
# TODO...
add_filter('sleek_get_dummy_field/?type=url', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Password
# TODO...
add_filter('sleek_get_dummy_field/?type=password', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

#########
# Content
# Image
add_filter('sleek_get_dummy_field/?type=image', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# File
add_filter('sleek_get_dummy_field/?type=file', function ($value, $module, $template, $field) {
	$file = null;

	# TODO: Limit to $field['mime_types'] if set
	$rows = get_posts([
		'post_type' => 'attachment',
		'numberposts' => -1
	]);

	if ($rows) {
		if (isset($field['return_format']) and $field['return_format'] === 'id') {
			return $rows[array_rand($rows)]->ID;
		}
		elseif (isset($field['return_format']) and $field['return_format'] === 'url') {
			# TODO: Return URL
		}
		# NOTE: Default to array - is that correct?
		else {
			return acf_get_attachment($rows[array_rand($rows)]->ID);
		}
	}

	return $file;
}, 10, 4);

# WYSIWYG
add_filter('sleek_get_dummy_field/?type=wysiwyg', function ($value, $module, $template, $field) {
	return '
		<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
		<p>Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
	';
}, 10, 4);

# oEmbed
# TODO...
add_filter('sleek_get_dummy_field/?type=oembed', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Gallery
# TODO...
add_filter('sleek_get_dummy_field/?type=gallery', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

########
# Choice
# Select
# TODO...
add_filter('sleek_get_dummy_field/?type=select', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Radio button
# TODO...
add_filter('sleek_get_dummy_field/?type=radio', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Button group
# TODO...
add_filter('sleek_get_dummy_field/?type=button_group', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# True/false
# TODO...
add_filter('sleek_get_dummy_field/?type=true_false', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

############
# Relational
# Link
# TODO...
add_filter('sleek_get_dummy_field/?type=link', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Post Object
# TODO...
add_filter('sleek_get_dummy_field/?type=post_object', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Page Link
# TODO...
add_filter('sleek_get_dummy_field/?type=page_link', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Relationship
# TODO...
add_filter('sleek_get_dummy_field/?type=relationship', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Taxonomy
# TODO...
add_filter('sleek_get_dummy_field/?type=taxonomy', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# User
# TODO...
add_filter('sleek_get_dummy_field/?type=user', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

########
# jQuery
# Google Map
# TODO...
add_filter('sleek_get_dummy_field/?type=google_map', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Date Picker
# TODO...
add_filter('sleek_get_dummy_field/?type=date_picker', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Date Time Picker
# TODO...
add_filter('sleek_get_dummy_field/?type=datetime_picker', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Color Picker
# TODO...
add_filter('sleek_get_dummy_field/?type=color_picker', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

########
# Layout
# Repeater
add_filter('sleek_get_dummy_field/?type=repeater', function ($value, $module, $template, $field) {
	$subFields = [];

	# Random number of repeats
	for ($i = 0; $i < rand(1, 12); $i++) {
		$subField = [];

		foreach ($field['sub_fields'] as $sField) {
			$subField[$sField['name']] = render_dummies_apply_filters(null, $module, $template, $sField);
		}

		$subFields[] = $subField;
	}

	return $subFields;
}, 10, 4);

# TODO: Message, Accordion, Tab, Group, Flexible Content, Clone
