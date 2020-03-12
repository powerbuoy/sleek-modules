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
			</style>
			<?php
		});

		add_action('wp_footer', function () {
			?>
			<script>
				window.addEventListener('message', function (e) {
					if (e.origin === '<?php echo home_url() ?>' && e.data && e.data.sleekModulesInlineEditUpdated === true) {
						window.location.reload();
					}
				});
			</script>
			<?php
		});

		add_action('sleek/modules/pre_render_flexible_module', function ($where, $id, $module, $data, $index) {
			?>
			<div class="dialog dialog--large" id="dialog-sleek-modules-inline-edit-<?php echo $where ?>-<?php echo $id ?>-<?php echo $index ?>">
				<iframe src="<?php echo home_url("/sleek-modules-inline-edit/$where/$id/$index/") ?>" class="sleek-modules-inline-edit-iframe"></iframe>
			</div>

			<nav class="sleek-modules-inline-edit">
				<a href="#dialog-sleek-modules-inline-edit-<?php echo $where ?>-<?php echo $id ?>-<?php echo $index ?>" class="sleek-modules-inline-edit-module">
					<?php _e('Edit Module', 'sleek') ?>
				</a>
			</nav>
			<?php
		}, 10, 5);
	}
});
