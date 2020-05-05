<?php
namespace Sleek\Modules;

add_action('acf/init', function () {
	if (get_theme_support('sleek/modules/global_modules')) {
		# Add the Global Modules Options Page
		acf_add_options_page([
			'page_title' => __('Global Modules', 'sleek'),
			'menu_slug' => 'global_modules',
			'post_id' => 'global_modules'
		]);

		# Add a Flexible Content Field to it
		acf_add_local_field_group([
			'key' => 'group_global_modules',
			'title' => __('Global Modules', 'sleek'),
			'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => 'global_modules']]],
			'menu_order' => 1,
			'fields' => [
				[
					'key' => 'field_global_modules',
					'name' => 'global_modules',
					'type' => 'flexible_content',
					'label' => __('Nothing here', 'sleek'),
					'button_label' => __('Add a Module', 'sleek'),
					'layouts' => \Sleek\Acf\generate_keys(
						get_module_fields(
							apply_filters('sleek/modules/global_modules', ['text-block', 'text-blocks']),
							'flexible', true
						),
						'field_global_modules'
					)
				]
			]
		]);
	}
});
