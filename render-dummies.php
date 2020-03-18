<?php
namespace Sleek\Modules;

######################
# Render dummy modules
function render_dummies ($modules) {
	foreach ($modules as $module) {
		$className = \Sleek\Utils\convert_case($module, 'pascal');
		$fullClassName = "Sleek\Modules\\$className";
		$templates = get_module_templates($module);
		$fields = (new $fullClassName)->filtered_fields();

		foreach ($templates as $template) {
			$template = $template['filename'];
			$data = [];

			foreach ($fields as $field) {
				$data[$field['name']] = render_dummies_apply_filters(null, $module, $template, $field);
			}

			do_action('sleek/modules/render_dummies/pre_render_module', $module, $data, $template);
			render($module, $data, $template);
			do_action('sleek/modules/render_dummies/post_render_module', $module, $data, $template);
		}
	}
}

#########################
# Render one dummy module
function render_dummy ($module, $template) {
	$className = \Sleek\Utils\convert_case($module, 'pascal');
	$fullClassName = "Sleek\Modules\\$className";
	$fields = (new $fullClassName)->filtered_fields();
	$data = [];

	foreach ($fields as $field) {
		$data[$field['name']] = render_dummies_apply_filters(null, $module, $template, $field);
	}

	render($module, $data, $template);
}

######################################
# Helper function to apply all filters
function render_dummies_apply_filters ($value, $module, $template, $field) {
	$value = apply_filters('sleek/modules/get_dummy_field/?type=' . $field['type'] . '&name=' . $field['name'] . '&module=' . $module, null, $module, $template, $field) ??
			apply_filters('sleek/modules/get_dummy_field/?type=' . $field['type'] . '&name=' . $field['name'], null, $module, $template, $field) ??
			apply_filters('sleek/modules/get_dummy_field/?type=' . $field['type'], null, $module, $template, $field);

	return	$value;
}

#######
# Basic
# Text Title
# TODO: Check prepend/append
add_filter('sleek/modules/get_dummy_field/?type=text&name=title', function ($value, $module, $template, $field) {
	return \Sleek\Utils\convert_case($module, 'title') . ': ' . $template;
}, 10, 4);

# Text
# TODO: Check prepend/append
add_filter('sleek/modules/get_dummy_field/?type=text', function ($value, $module, $template, $field) {
	return 'Lorem ipsum dolor sit amet.';
}, 10, 4);

# Textarea
# TODO: Check maxlength
add_filter('sleek/modules/get_dummy_field/?type=textarea', function ($value, $module, $template, $field) {
	return 'Lorem ipsum dolor sit amet. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.';
}, 10, 4);

# Number
# TODO: Check min/max + prepend/append
add_filter('sleek/modules/get_dummy_field/?type=number', function ($value, $module, $template, $field) {
	return rand(0, 999);
}, 10, 4);

# Range
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=range', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Email
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=email', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# URL
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=url', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Password
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=password', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

#########
# Content
# Image
add_filter('sleek/modules/get_dummy_field/?type=image', function ($value, $module, $template, $field) {
	# TODO: Limit to only images (not PDF etc)
	$rows = get_posts([
		'post_type' => 'attachment',
		'numberposts' => -1
	]);

	if ($rows) {
		if (isset($field['return_format']) and strtolower($field['return_format']) === 'id') {
			return $rows[array_rand($rows)]->ID;
		}
		elseif (isset($field['return_format']) and strtolower($field['return_format']) === 'url') {
			# TODO: Return URL
		}
		# NOTE: Default to array - is that correct?
		else {
			# TODO: Return full image (as wp_get_attachment_image or what? maybe an ACF function is needed)
		}
	}

	return null;
}, 10, 4);

# File
add_filter('sleek/modules/get_dummy_field/?type=file', function ($value, $module, $template, $field) {
	# TODO: Limit to $field['mime_types'] if set
	$rows = get_posts([
		'post_type' => 'attachment',
		'numberposts' => -1
	]);

	if ($rows) {
		if (isset($field['return_format']) and strtolower($field['return_format']) === 'id') {
			return $rows[array_rand($rows)]->ID;
		}
		elseif (isset($field['return_format']) and strtolower($field['return_format']) === 'url') {
			# TODO: Return URL
		}
		# NOTE: Default to array - is that correct?
		else {
			if (function_exists('acf_get_attachment')) {
				return acf_get_attachment($rows[array_rand($rows)]->ID);
			}
			else {
				trigger_error("sleek/modules/get_dummy_field/?type=file: acf_get_attachment() is not defined (have you enabled ACF?), unable to return value", E_USER_WARNING);
			}
		}
	}

	return null;
}, 10, 4);

# WYSIWYG
# TODO: Randomize text, add images, lists, etc
add_filter('sleek/modules/get_dummy_field/?type=wysiwyg', function ($value, $module, $template, $field) {
	return '
		<p>Lorem ipsum dolor sit amet. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
		<p>Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
	';
}, 10, 4);

# oEmbed
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=oembed', function ($value, $module, $template, $field) {
	return wp_oembed_get('https://www.youtube.com/watch?v=_T6SGPFTHWU');
}, 10, 4);

# Gallery
# TODO: Check min, max, more?
add_filter('sleek/modules/get_dummy_field/?type=gallery', function ($value, $module, $template, $field) {
	# TODO: Limit to images
	$rows = get_posts([
		'post_type' => 'attachment',
		'numberposts' => -1
	]);

	if ($rows) {
		shuffle($rows);

		$rows = array_slice($rows, 0, rand(min(count($rows), 2), min(count($rows), 6)));
		$tmp = [];

		foreach ($rows as $row) {
			if (function_exists('acf_get_attachment')) {
				$tmp[] = acf_get_attachment($row->ID);
			}
			else {
				$tmp[] = [
					'id' => $row->ID,
					'url' => wp_get_attachment_url($row->ID),
					'title' => get_the_title($row->ID),
					'caption' => wp_get_attachment_caption($row->ID),
					'description' => get_post_field('content', $row->ID)
				];
			}
		}

		return $tmp;
	}

	return null;
}, 10, 4);

########
# Choice
# Select
# TODO: Check return_format
add_filter('sleek/modules/get_dummy_field/?type=select', function ($value, $module, $template, $field) {
	if (isset($field['choices'])) {
		$rand = array_rand($field['choices']);

		return \Sleek\Utils\is_sequential_array($field['choices']) ? $field['choices'][$rand] : $rand;
	}

	return null;
}, 10, 4);

# Radio button
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=radio', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Button group
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=button_group', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# True/false
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=true_false', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

############
# Relational
# Link
# TODO: Randomize URLs, targets and more texts (+ return_format?)
add_filter('sleek/modules/get_dummy_field/?type=link', function ($value, $module, $template, $field) {
	$texts = [__('Read more', 'sleek'), __('Click here', 'sleek'), __('Contact us', 'sleek')];

	return [
		'url' => 'https://www.google.com',
		'target' => '',
		'title' => $texts[array_rand($texts)]
	];
}, 10, 4);

# Post Object
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=post_object', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Page Link
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=page_link', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Relationship
# TODO: Check min, max, return_format, more?
add_filter('sleek/modules/get_dummy_field/?type=relationship', function ($value, $module, $template, $field) {
	if (isset($field['min']) and isset($field['max'])) {
		$limit = rand($field['min'], $field['max']);
	}
	elseif (isset($field['min'])) {
		$limit = rand($field['min'], $field['min'] * 2);
	}
	elseif (isset($field['max'])) {
		$limit = rand(ceil($field['max'] / 2), $field['max']);
	}
	else {
		$limit = rand(3, 9);
	}

	$args = [
		'numberposts' => $limit
	];

	if (isset($field['post_type'])) {
		$args['post_type'] = $field['post_type'];
	}

	return get_posts($args);
}, 10, 4);

# Taxonomy
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=taxonomy', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# User
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=user', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

########
# jQuery
# Google Map
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=google_map', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Date Picker
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=date_picker', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Date Time Picker
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=datetime_picker', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

# Color Picker
# TODO...
add_filter('sleek/modules/get_dummy_field/?type=color_picker', function ($value, $module, $template, $field) {
	return null;
}, 10, 4);

########
# Layout
# Repeater
add_filter('sleek/modules/get_dummy_field/?type=repeater', function ($value, $module, $template, $field) {
	$subFields = [];

	# Random number of repeats
	for ($i = 0; $i < rand(1, 5); $i++) {
		$subField = [];

		foreach ($field['sub_fields'] as $sField) {
			$subField[$sField['name']] = render_dummies_apply_filters(null, $module, $template, $sField);
		}

		$subFields[] = $subField;
	}

	return $subFields;
}, 10, 4);

# TODO: Message, Accordion, Tab, Group, Flexible Content, Clone
