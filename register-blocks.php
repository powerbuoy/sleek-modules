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
			$templates = $mod->block_templates();

			if ($templates) {
				# Default block config
				$config = [
					'name' => $moduleName,
					'title' => $meta['name'] ?? 'N/A',
					'description' => $meta['description'] ?? '',
					'category' => $meta['category'] ?? '',
					'icon' => !empty($meta['dashicon']) ? str_replace('dashicon-', '', $meta['dashicon']) : (!empty($meta['icon_path']) ? file_get_contents($meta['icon_path']) : ''),
					'supports' => ['align' => false]
				];

				# If module has a block.css
				if ($meta['block_style'] and is_admin()) {
					$config['enqueue_style'] = $meta['block_style'];
				}

				# Merge module config
				$config = array_merge($config, $mod->block_config());

				# Now add render callback
				$config['render_callback'] = function ($block, $content = '', $is_preview = false, $post_id = 0) use ($moduleName) {
					\Sleek\Utils\get_template_part(
						'modules/' . $moduleName . '/' . $block['data']['template'], null,
						array_merge($block['data'], [
							'_acf_data' => [
								'block' => $block,
								'content' => $content,
								'is_preview' => $is_preview,
								'post_id' => $post_id
							]
						])
					);
				};

				# Now register block
				add_action('acf/init', function () use ($config, $meta, $moduleName, $mod, $templates) {
					$key = 'sleek_acf_block_' . \Sleek\Utils\convert_case($moduleName, 'snake');
					$fields = $mod->filtered_fields();

					# NOTE: Always add templates field, otherwise if a template is added later on, the previously added blocks won't have the template variable
				#	if (count($templates) > 1) {
						array_unshift($fields, get_templates_acf_field($templates, $meta['default_block_template']));
				#	}

					$fieldGroup = [
						'key' => 'group_' . $key,
						'title' => $meta['name'],
						'location' => [[['param' => 'block', 'operator' => '==', 'value' => 'acf/' . $moduleName]]],
						'fields' => \Sleek\Acf\generate_keys($fields, $key)
					];

					acf_register_block_type($config);
					acf_add_local_field_group($fieldGroup);
				});
			}
		}
	}
}, 99);
