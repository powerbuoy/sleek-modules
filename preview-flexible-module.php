<?php
# https://wordpress.stackexchange.com/questions/218588/post-preview-mechanism-architecture
namespace Sleek\Modules;

function get_flexible_module_by_area_index ($area, $index, $postId) {
	$autosave = wp_get_post_autosave($postId, get_current_user_id());
	$postId = $autosave ? $autosave->ID : $postId;
	$modules = get_field($area, $postId);
	$i = 0;

	if ($modules) {
		foreach ($modules as $module) {
			if ($i++ === $index) {
				return $module;
			}
		}
	}

	return null;
}

add_action('wp_ajax_sleek_preview_flexible_module', __NAMESPACE__ . '\\preview_flexible_module');
add_action('wp_ajax_nopriv_sleek_preview_flexible_module', __NAMESPACE__ . '\\preview_flexible_module');

function preview_flexible_module () {
	$area = $_GET['area'] ?? null;
	$index = (int) $_GET['index'] ?? null;
	$postId = $_GET['post_id'] ?? null;

	if ($area !== null and $index !== null and $postId !== null) {
		$module = get_flexible_module_by_area_index($area, $index, $postId);

		if ($module and isset($module['acf_fc_layout'])) {
			$moduleName = \Sleek\Utils\convert_case($module['acf_fc_layout'], 'snake');
			?>
			<!DOCTYPE html>
			<html <?php language_attributes() ?> <?php body_class() ?>>
				<head>
					<?php wp_head() ?>
				</head>
				<body>
					<?php
					render($moduleName, $module);
					?>
					<?php wp_footer() ?>
				</body>
			</html>
			<?php
		}
	}

	die;
}

add_action('admin_head', function () {
	if (get_theme_support('sleek/modules/module_preview')) {
		$currentScreen = get_current_screen();

		if ($currentScreen->base === 'post') {
			global $post;

			?>
			<style>
				.acf-flexible-content .layout .acf-fc-layout-controls .acf-icon.-picture {
					visibility: hidden;
				}

				.acf-flexible-content .layout:hover .acf-fc-layout-controls .acf-icon.-picture {
					visibility: visible;
				}
			</style>

			<script>
				window.addEventListener('DOMContentLoaded', e => {
					document.querySelectorAll('div.acf-flexible-content > div.values div.layout').forEach(el => {
						var postId = document.getElementById('post_ID');

						if (postId) {
							postId = postId.value;

							var controls = el.querySelector('div.acf-fc-layout-controls');
							var index = el.dataset.id.substring('row-'.length);
							var data = el.querySelector(':scope > input[type=hidden]').name;
							var previewButton = document.createElement('a');
							var matches = data.match(/acf\[(.*?)\]/);
							var area = matches[1];

							previewButton.classList.add('acf-icon', '-picture', 'small', 'light', 'acf-js-tooltip', 'thickbox');
							previewButton.href = '/wp-admin/admin-ajax.php?action=sleek_preview_flexible_module&area=' + area + '&index=' + index + '&post_id=' + postId + '&TB_iframe=true';

						//	previewButton.href = '<?php echo get_preview_post_link($post->ID) ?>';
						//	previewButton.target = 'wp-preview-<?php echo $post->ID ?>';

							previewButton.setAttribute('title', 'Preview');

							controls.prepend(previewButton);
						}
					});
				});
			</script>
			<?php
		}
	}
});
