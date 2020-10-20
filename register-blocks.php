<?php
namespace Sleek\Modules;

add_action('after_setup_theme', function () {
	$path = get_stylesheet_directory() . '/modules/**/module.php';

	foreach (glob($path) as $file) {
		$moduleName = basename(dirname($file));
		$className = \Sleek\Utils\convert_case($moduleName, 'pascal');
		$fullClassName = "Sleek\Modules\\$className";

		if (class_exists($fullClassName)) {
			$mod = new $fullClassName;
			$meta = $mod->meta();

			if ($meta['block_template']) {
				$key = 'sleek_acf_block_' . \Sleek\Utils\convert_case($moduleName, 'snake');
				$fields = $mod->filtered_fields();

				# Default block config
				$config = [
					'name' => $moduleName,
					'title' => $meta['name'],
					'description' => $meta['description'],
					'category' => $meta['category'],
					'icon' => $meta['dashicon'] ? str_replace('dashicon-', '', $meta['dashicon']) : ($meta['icon_path'] ? file_get_contents($meta['icon_path']) : ''),
					'render_template' => 'modules/' . $moduleName . '/block-template.php',
					'supports' => ['align' => false]
				];

				# If module has a block.css
				if ($meta['block_style'] and is_admin()) {
					$config['enqueue_style'] = $meta['block_style'];
				}

				# Merge module config
				$config = array_merge($config, $mod->acf_block_config());

				# Now register block
				add_action('acf/init', function () use ($config, $key, $meta, $moduleName, $fields) {
					acf_register_block_type($config);

					acf_add_local_field_group([
						'key' => 'group_' . $key,
						'title' => $meta['name'],
						'location' => [[['param' => 'block', 'operator' => '==', 'value' => 'acf/' . $moduleName]]],
						'fields' => \Sleek\Acf\generate_keys($fields, $key)
					]);
				});
			}
		}
	}
}, 99);
