<?php
# https://wordpress.stackexchange.com/questions/218588/post-preview-mechanism-architecture
namespace Sleek\Modules;

# Return module at index from area/postId
function get_flexible_module_by_area_index ($area, $postId, int $index) {
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
					$wp_query->query_vars['sleek_modules_preview_flex_module_post_id'],
					$wp_query->query_vars['sleek_modules_preview_flex_module_index']
				);

				if ($module and isset($module['acf_fc_layout'])) {
					$moduleName = \Sleek\Utils\convert_case($module['acf_fc_layout'], 'kebab');
					?>
					<!DOCTYPE html>
					<html <?php language_attributes() ?> <?php body_class('prefers-reduced-motion') ?>>
						<head>
							<?php wp_head() ?>
							<style>
								#wpadminbar,
								#cookie-consent {
									display: none;
								}
							</style>
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
