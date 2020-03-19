<?php
namespace Sleek\Modules;

add_action('init', function () {
	if (get_theme_support('sleek/modules/inline_edit')) {
		# Add rewrite rule for our inline edit form
		add_filter('query_vars', function ($vars) {
			$vars[] = 'sleek_modules_inline_edit_area';
			$vars[] = 'sleek_modules_inline_edit_post_id';
			$vars[] = 'sleek_modules_inline_edit_index';

			return $vars;
		});

		add_rewrite_rule(
			'^__SLEEK__/modules/inline-edit/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?sleek_modules_inline_edit_area=$matches[1]&sleek_modules_inline_edit_post_id=$matches[2]&sleek_modules_inline_edit_index=$matches[3]',
			'top'
		);

		add_action('template_redirect', function () {
			global $wp_query;

			if (isset($wp_query->query_vars['sleek_modules_inline_edit_area'])) {
				if (!current_user_can('edit_posts')) {
					status_header(404); # Sets 404 header
					$wp_query->set_404(); # Shows 404 template
				}
				else {
					# Enable jQuery
					remove_theme_support('sleek/disable_jquery');

					# Disable sleek styling
					add_action('wp_enqueue_scripts', function () {
						wp_dequeue_style('sleek');
						wp_dequeue_script('sleek');
						wp_dequeue_script('sleek_google_maps');
					}, 99);

					# Render form
					?>
					<?php acf_form_head() ?>
					<!DOCTYPE html>
					<html <?php language_attributes() ?> <?php body_class('prefers-reduced-motion') ?>>
						<head>
							<?php wp_head() ?>
							<style>
								body {
									color: #444;
									font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
									font-size: 13px;
									line-height: 1.4em;
									margin: 0;
								}

								/* Hide admin bar and cookie consent */
								#wpadminbar,
								#cookie-consent {
									display: none;
								}

								html {
									margin-top: 0 !important;
								}

								/* This style isn't added by acf_form_head() */
								.screen-reader-text {
									display: none;
								}

								/* Full size media modal (because it looks really weird with a modal inside a modal) */
								div.media-modal {
									left: 0;
									top: 0;
									right: 0;
									bottom: 0;
								}

								/* Util class */
								.sleek-modules-inline-edit-hide {
									display: none;
								}

								/* Remove first field (the flex field) padding */
								form.acf-form > .acf-fields > .acf-field {
									padding: 0;
									border: 0;
								}

								/* Never collapse layouts */
								.acf-flexible-content .layout.-collapsed > .acf-fields,
								.acf-flexible-content .layout.-collapsed > .acf-table {
									display: block;
								}

								/* Remove flex field actions */
								.acf-flexible-content > .acf-actions {
									display: none;
								}

								/* Remove border from layouts */
								.acf-flexible-content .values .layout {
									margin: 0;
									border: 0;
									display: none;
								}

								.acf-flexible-content .values .layout[data-id="row-<?php echo $wp_query->query_vars['sleek_modules_inline_edit_index'] ?>"] {
									display: block;
								}

								/* Hide layout handle and controls */
								.acf-flexible-content .values .layout .acf-fc-layout-handle,
								.acf-flexible-content .values .layout .acf-fc-layout-controls {
									display: none;
								}

								/* Remove side padding and border from inner fields */
								.acf-flexible-content .values .layout .acf-fields > .acf-field {
									padding-left: 0;
									padding-right: 0;
									border: 0;
								}
							</style>
						</head>
						<body>
							<?php
								acf_form([
									'id' => 'acf-form-' . $wp_query->query_vars['sleek_modules_inline_edit_area'] . '-' . $wp_query->query_vars['sleek_modules_inline_edit_post_id'],
									'fields' => [$wp_query->query_vars['sleek_modules_inline_edit_area']],
									'post_id' => $wp_query->query_vars['sleek_modules_inline_edit_post_id']
								]);
							?>
							<?php wp_footer() ?>
							<script>
								acf.unload.active = false;
							</script>
							<?php if (isset($_GET['updated'])) : ?>
								<script>
									window.parent.postMessage({sleekModulesInlineEditUpdated: true}, '<?php echo home_url() ?>');
								</script>
							<?php endif ?>
						</body>
					</html>
					<?php
					exit;
				}
			}
		});
	}
});

# Add edit icon and dialog to flexible modules
add_action('after_setup_theme', function () {
	if (get_theme_support('sleek/modules/inline_edit') and current_user_can('edit_posts') and !is_admin()) {
		# Some basic styling for the dialog
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

		# Add a dialog for each flexible module area (could use one dialog for all areas but found no good hook to add it on)
		add_action('sleek/modules/pre_render_flexible', function ($where, $id) {
			?>
			<div class="dialog sleek-modules-inline-edit-dialog" id="dialog-sleek-modules-inline-edit-<?php echo $where ?>-<?php echo $id ?>">
				<iframe class="sleek-modules-inline-edit-iframe"></iframe>
			</div>
			<?php
		}, 10, 2);

		# Add toolbar above modules
		add_action('sleek/modules/pre_render_flexible_module', function ($where, $id, $module, $data, $index) {
			?>
			<nav class="sleek-modules-inline-edit">
				<a href="#dialog-sleek-modules-inline-edit-<?php echo $where ?>-<?php echo $id ?>"
					class="sleek-modules-inline-edit-module"
					data-dialog-data='<?php echo json_encode(['area' => $where, 'post_id' => $id, 'index' => $index]) ?>'>
					<?php printf(__('Edit %s', 'sleek'), \Sleek\Utils\convert_case($module, 'title')) ?>
				</a>
			</nav>
			<?php
		}, 10, 5);

		add_action('wp_footer', function () {
			?>
			<script>
				// Update iframe src when clicking module edit
				window.addEventListener('sleek-ui-dialog-trigger-open', function (e) {
					if (e.detail && e.detail.data) {
						var dialog = e.detail.dialog;
						var data = JSON.parse(e.detail.data);
						var iframe = dialog.querySelector('iframe');
						var src = '<?php echo home_url('/__SLEEK__/modules/inline-edit/') ?>' + data.area + '/' + data.post_id + '/' + data.index + '/';

						if (iframe && iframe.src !== src) {
							iframe.src = src;
						}
					}
				});

				// Reload page when form saves
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
