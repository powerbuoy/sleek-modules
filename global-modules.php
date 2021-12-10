<?php
namespace Sleek\Modules;

add_action('acf/init', function () {
	if ($args = get_theme_support('sleek/modules/global_modules')) {
		$moduleDirectories = array_filter(glob(get_stylesheet_directory() . '/modules/*'), 'is_dir');
		$moduleDirectories = array_map('basename', $moduleDirectories);
		$allowedModules = array_values($moduleDirectories);
		$allowedModules = array_diff($allowedModules, ['global-module']);

		if (isset($args[0]) and is_array($args[0])) {
			$allowedModules = $args[0];
		}

		# Create Global Module Post Type
		register_post_type('sleek_global_module', [
			'labels' => [
				'name' => __('Global Modules', 'sleek_admin'),
				'singular_name' => __('Global Modules', 'sleek_admin')
			],
			'public' => false,
			'show_ui' => true,
			'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg width="1792" height="1792" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path fill="#fff" d="M1728 1098q0 81-44.5 135t-123.5 54q-41 0-77.5-17.5t-59-38-56.5-38-71-17.5q-110 0-110 124 0 39 16 115t15 115v5q-22 0-33 1-34 3-97.5 11.5t-115.5 13.5-98 5q-61 0-103-26.5t-42-83.5q0-37 17.5-71t38-56.5 38-59 17.5-77.5q0-79-54-123.5t-135-44.5q-84 0-143 45.5t-59 127.5q0 43 15 83t33.5 64.5 33.5 53 15 50.5q0 45-46 89-37 35-117 35-95 0-245-24-9-2-27.5-4t-27.5-4l-13-2q-1 0-3-1-2 0-2-1v-1024q2 1 17.5 3.5t34 5 21.5 3.5q150 24 245 24 80 0 117-35 46-44 46-89 0-22-15-50.5t-33.5-53-33.5-64.5-15-83q0-82 59-127.5t144-45.5q80 0 134 44.5t54 123.5q0 41-17.5 77.5t-38 59-38 56.5-17.5 71q0 57 42 83.5t103 26.5q64 0 180-15t163-17v2q-1 2-3.5 17.5t-5 34-3.5 21.5q-24 150-24 245 0 80 35 117 44 46 89 46 22 0 50.5-15t53-33.5 64.5-33.5 83-15q82 0 127.5 59t45.5 143z"/></svg>'),
			'menu_position' => 50,
			'supports' => ['title']
		]);

		# Add a Flexible Content Field to it
		acf_add_local_field_group([
			'key' => 'group_global_modules',
			'title' => __('Global Modules', 'sleek_admin'),
			'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'sleek_global_module']]],
			'menu_order' => 1,
			'fields' => [
				[
					'key' => 'field_global_modules',
					'name' => 'global_modules',
					'type' => 'flexible_content',
					'label' => __('Global Modules', 'sleek_admin'),
					'button_label' => __('Global Modules', 'sleek_admin'),
					'layouts' => \Sleek\Acf\generate_keys(
						get_module_fields(
							apply_filters('sleek/modules/global_modules', $allowedModules), # TODO: Deprecate
							'flexible', true
						),
						'field_global_modules'
					)
				]
			]
		]);

		# Enable translations of global modules
		add_filter('pll_get_post_types', function ($postTypes, $isSettings) {
			if ($isSettings) {
				$postTypes['sleek_global_module'] = 'sleek_global_module';
			}

			return $postTypes;
		}, 10, 2);
	}
});
