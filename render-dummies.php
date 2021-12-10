<?php
namespace Sleek\Modules;

######################
# Render dummy modules
function render_dummies ($modules) {
	foreach ($modules as $module) {
		$fullClassName = "Sleek\Modules\\" . \Sleek\Utils\convert_case($module, 'pascal');
		$mod = new $fullClassName;
		$templates = $mod->templates();;
		$fields = $mod->filtered_fields();

		foreach ($templates as $template) {
			$template = $template['filename'];
			$data = [];

			foreach ($fields as $field) {
				$data[$field['name']] = apply_filters('sleek/modules/dummy_field_value', null, $field, $module, $template, 1);
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
	$fullClassName = "Sleek\Modules\\" . \Sleek\Utils\convert_case($module, 'pascal');
	$fields = (new $fullClassName)->filtered_fields();
	$data = [];

	foreach ($fields as $field) {
		$data[$field['name']] = apply_filters('sleek/modules/dummy_field_value', null, $field, $module, $template, 1);
	}

	render($module, $data, $template);
}

# All dummy data
add_filter('sleek/modules/dummy_field_value', function ($value, $field, $module, $template, $level) {
	#########
	# Special
	# Module Title
	if ($field['type'] === 'text' and $field['name'] === 'title' and $level === 1) {
		$moduleTitle =	__(\Sleek\Utils\convert_case($module, 'title'), 'sleek_admin');
		$templateTitle = $template === 'template' ? __('Default Template', 'sleek_admin') : __(\Sleek\Utils\convert_case($template, 'title'), 'sleek_admin');

		return "$moduleTitle <small>$templateTitle</small>";
	}

	# Module Description
	if ($field['type'] === 'wysiwyg' and $field['name'] === 'description' and $level === 1 and file_exists(get_stylesheet_directory() . '/modules/' . $module . '/README.md')) {
		return wpautop(file_get_contents(get_stylesheet_directory() . '/modules/' . $module . '/README.md'));
	}

	#######
	# Basic
	# Text
	# TODO: Check prepend/append
	elseif ($field['type'] === 'text') {
		return 'Lorem ipsum dolor sit amet.';
	}

	# Textarea
	# TODO: Check maxlength
	elseif ($field['type'] === 'textarea') {
		if (isset($field['return_format']) and $field['return_format'] === 'array') {
			return ['Lorem ipsum', 'Dolor sit amet', 'Donec eu libero', 'Quam egestas semper'];
		}

		return 'Lorem ipsum dolor sit amet. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.';
	}

	# Number
	# TODO: Check min/max + prepend/append
	elseif ($field['type'] === 'number') {
		return rand(0, 999);
	}

	# Range
	# TODO...
	elseif ($field['type'] === 'range') {
		return null;
	}

	# Email
	# TODO...
	elseif ($field['type'] === 'email') {
		return 'me@mydomain.com';
	}

	# URL
	# TODO...
	elseif ($field['type'] === 'url') {
		return 'https://www.google.com';
	}

	# Password
	# TODO...
	elseif ($field['type'] === 'password') {
		return 'password';
	}

	#########
	# Content
	# Image
	elseif ($field['type'] === 'image') {
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
	}

	# File
	elseif ($field['type'] === 'file') {
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
			}
		}

		return null;
	}

	# WYSIWYG
	# TODO: Randomize text, add images, lists, etc
	elseif ($field['type'] === 'wysiwyg') {
		return '
			<p>Lorem ipsum dolor sit amet. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
			<p>Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
		';
	}

	# oEmbed
	# TODO...
	elseif ($field['type'] === 'oembed') {
		return apply_filters('embed_oembed_html', wp_oembed_get('https://www.youtube.com/watch?v=M7g7Pfx6zjg'), 'https://www.youtube.com/watch?v=M7g7Pfx6zjg');
	}

	# Gallery
	# TODO: Check min, max, more?
	elseif ($field['type'] === 'gallery') {
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
	}

	########
	# Choice
	# Select
	# TODO: Check return_format
	elseif ($field['type'] === 'select') {
		if (isset($field['choices'])) {
			$rand = array_rand($field['choices']);

			return \Sleek\Utils\is_sequential_array($field['choices']) ? $field['choices'][$rand] : $rand;
		}

		return null;
	}

	# Radio button
	# TODO...
	elseif ($field['type'] === 'radio') {
		return null;
	}

	# Button group
	# TODO...
	elseif ($field['type'] === 'button_group') {
		return null;
	}

	# True/false
	# TODO...
	elseif ($field['type'] === 'true_false') {
		return null;
	}

	############
	# Relational
	# Link
	# TODO: Randomize URLs, targets and more texts (+ return_format?)
	elseif ($field['type'] === 'link') {
		$texts = [__('Read more', 'sleek_admin'), __('Click here', 'sleek_admin'), __('Contact us', 'sleek_admin')];

		return [
			'url' => 'https://www.google.com',
			'target' => '',
			'title' => $texts[array_rand($texts)]
		];
	}

	# Post Object
	# TODO...
	elseif ($field['type'] === 'post_object') {
		return null;
	}

	# Page Link
	# TODO...
	elseif ($field['type'] === 'page_link') {
		return null;
	}

	# Relationship
	# TODO: Check min, max, return_format, more?
	elseif ($field['type'] === 'relationship') {
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
	}

	# Taxonomy
	# TODO...
	elseif ($field['type'] === 'taxonomy') {
		return null;
	}

	# User
	# TODO: return_format and more probably
	elseif ($field['type'] === 'user') {
		$rows = get_users();
		$return = [];

		if ($rows) {
			shuffle($rows);
			$rows = array_slice($rows, 0, rand(min(count($rows), 2), min(count($rows), 6)));

			if (isset($field['return_format']) and strtolower($field['return_format']) === 'id') {
				$rows = array_map(function ($row) {
					return $row->ID;
				}, $rows);
			}
			elseif (isset($field['return_format']) and strtolower($field['return_format']) === 'object') {
				# Do nothing, it's already an object
			}
			# Default to array TODO: This is not the same array as ACF returns :(
			else {
				$rows = array_map(function ($row) {
					return (array) $row->data;
				}, $rows);
			}
		}

		if (!isset($field['multiple']) or $field['multiple'] === false) {
			$rows = $rows[0];
		}

		return $rows;
	}

	########
	# jQuery
	# Google Map
	# TODO...
	elseif ($field['type'] === 'google_map') {
		return [
			'lat' => '40.6974034',
			'lng' => '-74.1197633'
		];
	}

	# Date Picker
	# TODO...
	elseif ($field['type'] === 'date_picker') {
		return null;
	}

	# Date Time Picker
	# TODO...
	elseif ($field['type'] === 'datetime_picker') {
		return null;
	}

	# Color Picker
	# TODO...
	elseif ($field['type'] === 'color_picker') {
		return null;
	}

	########
	# Layout
	# Repeater
	elseif ($field['type'] === 'repeater') {
		$subFields = [];

		# Random number of repeats
		for ($i = 0; $i < rand(1, 5); $i++) {
			$subField = [];

			foreach ($field['sub_fields'] as $sField) {
				$subField[$sField['name']] = apply_filters('sleek/modules/dummy_field_value', null, $sField, $module, $template, 2);
			}

			$subFields[] = $subField;
		}

		return $subFields;
	}

	# TODO: Message, Accordion, Tab, Group, Flexible Content, Clone

	return $value;
}, 10, 5);
