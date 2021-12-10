<?php
namespace Sleek\Modules;

require_once __DIR__ . '/add-new-module-preview.php';
require_once __DIR__ . '/global-modules.php';
require_once __DIR__ . '/inline-edit-flex-module.php';
require_once __DIR__ . '/module-icons.php';
require_once __DIR__ . '/preview-dummy-module.php';
require_once __DIR__ . '/preview-flex-module.php';
require_once __DIR__ . '/register-blocks.php';
require_once __DIR__ . '/render-dummies.php';

################################
# Run all modules' init callback
add_action('after_setup_theme', function () {
	$path = get_stylesheet_directory() . '/modules/**/module.php';

	foreach (glob($path) as $file) {
		$moduleName = basename(dirname($file));
		$className = \Sleek\Utils\convert_case($moduleName, 'pascal');
		$fullClassName = "Sleek\Modules\\$className";

		# Include the class
		require_once $file;

		# Create instance of class and run callback
		if (class_exists($fullClassName)) {
			$mod = new $fullClassName;
			$mod->init();
		}
		else {
			trigger_error("\Sleek\Modules\create_module($className): module '$className' does not exist even though the file does: '$file'", E_USER_WARNING);
		}
	}
});

######################
# Render single module
function render ($name, $fields = null, $template = null) {
	$fields = $fields ?? get_the_ID();
	$snakeName = \Sleek\Utils\convert_case($name, 'snake');
	$className = \Sleek\Utils\convert_case($name, 'pascal');
	$fullClassName = "Sleek\Modules\\$className";

	# A modules/module-name/module.php type module
	if (class_exists($fullClassName)) {
		do_action('sleek/modules/pre_render', $name, $fields, $template);

		# If data is not an array it's assumed to be an ACF ID
		if (!is_array($fields)) {
			$acfData = null;

			if (function_exists('get_field')) {
				$acfData = get_field($snakeName, $fields);
			}

			# Fall back to empty array
			# (NOTE: Not using ?? because an empty string should also fall back to [])
			# (NOTE 2: An empty string can occur if a field by this name once existed on this ID,
			# for example if posts once had a "next-post" module that was later removed
			# ACF still stores an empty string in the database for some reason) (???)
			$fields = $acfData ? $acfData : [];
		}

		# Create and render the module
		$mod = new $fullClassName;
		$mod->render($template, $fields);
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
function render_flexible ($area, $id = null) {
	$id = $id ?? get_the_ID();

	if (!function_exists('get_field')) {
		trigger_error("get_field() does not exist, have you installed Advanced Custom Fields?", E_USER_WARNING);

		return;
	}

	if ($modules = get_field($area, $id)) {
		do_action('sleek/modules/pre_render_flexible', $area, $id);

		$i = 0;

		foreach ($modules as $moduleData) {
			$moduleName = \Sleek\Utils\convert_case($moduleData['acf_fc_layout'], 'kebab');

			do_action('sleek/modules/pre_render_flexible_module', $area, $id, $moduleName, $moduleData, $i++);
			render($moduleName, $moduleData);
		}
	}
}

####################################
# Returns all ACF fields for modules
# $layout can be one of 'flexible', 'tabs', 'accordion', or 'normal'
function get_module_fields (array $modules, $layout = 'normal', $withTemplates = true) {
	$cacheKey = md5(json_encode($modules) . $layout . ($withTemplates ? 'true' : 'false'));

	if ($cache = wp_cache_get($cacheKey, 'sleek_get_module_fields')) {
		return $cache;
	}

	$fields = [];

	foreach ($modules as $module) {
		# TODO: Move to get_single_module_fields ? yes but without group, tabs etc, only flat fields with templates
		$snakeName = \Sleek\Utils\convert_case($module, 'snake');
		$label = \Sleek\Utils\convert_case($module, 'title');
		$className = \Sleek\Utils\convert_case($module, 'pascal');
		$fullClassName = "Sleek\Modules\\$className";
		$moduleFields = null;
		$templates = null;
		$meta = null;

		# Create module class and get fields
		if (class_exists($fullClassName)) {
			$mod = new $fullClassName;
			$moduleFields = $mod->filtered_fields();
			$templates = $mod->templates();
			$meta = $mod->meta();
			$label = $meta['name'];
		}

		# Create field group
		$field = [
			'name' => $snakeName,
			'label' => $label,
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

		# We have fields
		if ($moduleFields) {
			$field['sub_fields'] = $moduleFields;
		}
		# No fields for this module
		else {
			$field['sub_fields'][] = [
				'name' => 'message',
				'type' => 'message',
				'label' => __('No config', 'sleek_admin'),
				'message' => __('This module requires no configuration.', 'sleek_admin')
			];
		}

		# Insert module templates
		if ($withTemplates and $templates and count($templates) > 1) {
			array_unshift($field['sub_fields'], get_templates_acf_field($templates, ($meta ? $meta['default_template'] : 'template')));
		}

		$fields[] = $field;
	}

	wp_cache_set($cacheKey, $fields, 'sleek_get_module_fields');

	return $fields;
}

# TODO: Support for HIDDEN modules??
function get_templates_acf_field ($templates, $defaultTemplate = 'template') {
	$cleanTemplates = [];

	foreach ($templates as $t) {
		$screenshot = !empty($t['screenshot']) ? '<img src="' . $t['screenshot'] . '" class="sleek-module-template-screenshot">' : '';
		$description = !empty($t['description']) ? '<small class="sleek-module-template-description">' . $t['description'] . '</small>' : '';
		$cleanTemplates[$t['filename']] = $screenshot . $t['name'] . $description;
	}

	return [
		'name' => 'template',
		'label' => __('Template', 'sleek_admin'),
		'type' => 'select',
		'choices' => $cleanTemplates,
		'default_value' => $defaultTemplate,
		'ui' => true
	];
}

#############################
# Style the template dropdown
add_action('admin_head', function () {
	?>
	<style>
		.select2-selection__rendered .sleek-module-template-description {
			display: none;
		}

		img.sleek-module-template-screenshot {
			display: none;
		}

		.select2-container--default .select2-results > .select2-results__options[id$="_template-results"] {
			overflow: visible;
		}

		span.select2-results > ul > li {
			position: relative;
		}

		span.select2-results > ul > li > img.sleek-module-template-screenshot {
			position: absolute;
			left: -1.5rem;
			top: 50%;
			z-index: 99;

			display: block;
			width: 10rem;
			border: 0.25rem solid white;
			box-shadow: 0 0 0.4rem 0 rgba(0, 0, 0, .75);

			transform: translate(-100%, -50%) scale(0);
			transform-origin: right 50%;
			transition: transform 0.25s ease;
		}

		span.select2-results > ul > li:hover > img.sleek-module-template-screenshot {
			transform: translate(-100%, -50%) scale(1);
		}

		span.select2-results > ul > li > small.sleek-module-template-description {
			display: block;
		}
	</style>
	<?php
});
