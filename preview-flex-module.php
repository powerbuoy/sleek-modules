<?php
# https://wordpress.stackexchange.com/questions/218588/post-preview-mechanism-architecture
namespace Sleek\Modules;

# Return module at index from area/postId
function get_flexible_module_by_area_index ($area, $postId, $index) {
	# TODO: Sometimes the latest revision is actually _older_ than the post itself!?
	$latestRev = wp_get_post_revisions($postId, ['numberposts' => 1]);
	$postId = ($latestRev and count($latestRev)) ? array_key_first($latestRev) : $postId;
#	$autosave = wp_get_post_autosave($postId, get_current_user_id());
#	$postId = $autosave ? $autosave->ID : $postId;
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

# Add rewrite rule for our module preview
add_action('init', function () {
	if (get_theme_support('sleek/modules/module_preview')) {
		add_filter('query_vars', function ($vars) {
			$vars[] = 'sleek_modules_preview_flex_module_area';
			$vars[] = 'sleek_modules_preview_flex_module_post_id';
			$vars[] = 'sleek_modules_preview_flex_module_index';

			return $vars;
		});

		add_rewrite_rule(
			'^__SLEEK__/modules/flex-module-preview/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?sleek_modules_preview_flex_module_area=$matches[1]&sleek_modules_preview_flex_module_post_id=$matches[2]&sleek_modules_preview_flex_module_index=$matches[3]',
			'top'
		);

		add_action('template_redirect', function () {
			global $wp_query;

			if (isset($wp_query->query_vars['sleek_modules_preview_flex_module_area'])) {
				$module = get_flexible_module_by_area_index(
					$wp_query->query_vars['sleek_modules_preview_flex_module_area'],
					(int) $wp_query->query_vars['sleek_modules_preview_flex_module_post_id'],
					(int) $wp_query->query_vars['sleek_modules_preview_flex_module_index']
				);

				if ($module and isset($module['acf_fc_layout'])) {
					$moduleName = \Sleek\Utils\convert_case($module['acf_fc_layout'], 'snake');
					?>
					<!DOCTYPE html>
					<html <?php language_attributes() ?> <?php body_class() ?>>
						<head>
							<?php wp_head() ?>
							<meta name="robots" content="noindex,nofollow">
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

				exit;
			}
		});
	}
});

add_action('admin_head', function () {
	$currentScreen = get_current_screen();

	if (get_theme_support('sleek/modules/module_preview') and $currentScreen->base === 'post') {
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

						// TODO: Create new revision when clicking button... HOW!?
						previewButton.classList.add('acf-icon', '-picture', 'small', 'light', 'acf-js-tooltip', 'thickbox');
						previewButton.href = '<?php echo home_url('/__SLEEK__/modules/flex-module-preview/') ?>' + area + '/' + postId + '/' + index + '/?TB_iframe=true';

						previewButton.setAttribute('title', 'Preview');
						controls.prepend(previewButton);
					}
				});
			});
		</script>
		<?php
	}
});
