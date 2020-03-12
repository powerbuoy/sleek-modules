<?php
namespace Sleek\Modules;

# Add rewrite rule for our inline edit form
add_action('init', function () {
	if (get_theme_support('sleek/modules/inline_edit')) {
		add_filter('query_vars', function ($vars) {
			$vars[] = 'sleek_modules_inline_edit_area';
			$vars[] = 'sleek_modules_inline_edit_post_id';
			$vars[] = 'sleek_modules_inline_edit_index';

			return $vars;
		});

		add_rewrite_rule(
			'^sleek-modules-inline-edit/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?sleek_modules_inline_edit_area=$matches[1]&sleek_modules_inline_edit_post_id=$matches[2]&sleek_modules_inline_edit_index=$matches[3]',
			'top'
		);

		add_action('template_redirect', function () {
			global $wp_query;

			if (isset($wp_query->query_vars['sleek_modules_inline_edit_area'])) {
				# Enable jQuery
				remove_theme_support('sleek/disable_jquery');

				# Disable sleek styling
				add_action('wp_enqueue_scripts', function () {
					wp_dequeue_style('sleek');
					wp_dequeue_script('sleek');
					wp_dequeue_script('sleek_google_maps');
				}, 99);

				include __DIR__ . '/inline-edit-form.php';
				exit;
			}
		});
	}
});

add_action('after_setup_theme', function () {
	if (get_theme_support('sleek/modules/inline_edit') and current_user_can('edit_posts') and !is_admin()) {
		add_action('wp_head', function () {
			?>
			<style>
				.sleek-modules-inline-edit-iframe {
					border: 0;
					width: 100%;
					height: 70vh;
				}

				.sleek-modules-inline-edit-dialog {
					--dialog-width: 60rem;
				}
			</style>
			<?php
		});

		add_action('sleek/modules/pre_render_flexible', function ($where, $id) {
			?>
			<div class="dialog sleek-modules-inline-edit-dialog" id="dialog-sleek-modules-inline-edit-<?php echo $where ?>-<?php echo $id ?>">
				<iframe class="sleek-modules-inline-edit-iframe"></iframe>
			</div>
			<?php
		}, 10, 2);

		add_action('sleek/modules/pre_render_flexible_module', function ($where, $id, $module, $data, $index) {
			?>
			<nav class="sleek-modules-inline-edit">
				<a href="#dialog-sleek-modules-inline-edit-<?php echo $where ?>-<?php echo $id ?>"
					class="sleek-modules-inline-edit-module"
					data-dialog-data='<?php echo json_encode(['area' => $where, 'post_id' => $id, 'index' => $index]) ?>'>
					<?php _e('Edit Module', 'sleek') ?>
				</a>
			</nav>
			<?php
		}, 10, 5);

		add_action('wp_footer', function () {
			?>
			<script>
				window.addEventListener('sleek-ui-dialog-trigger-open', function (e) {
					if (e.detail && e.detail.data) {
						var dialog = e.detail.dialog;
						var data = JSON.parse(e.detail.data);
						var iframe = dialog.querySelector('iframe');
						var src = '<?php echo home_url('/sleek-modules-inline-edit/') ?>' + data.area + '/' + data.post_id + '/' + data.index + '/';

						if (iframe && iframe.src !== src) {
							iframe.src = src;
						}
					}
				});

				window.addEventListener('message', function (e) {
					if (e.origin === '<?php echo home_url() ?>' && e.data && e.data.sleekModulesInlineEditUpdated === true) {
						window.location.reload();
					}
				});
			</script>
			<?php
		});
	}
});
