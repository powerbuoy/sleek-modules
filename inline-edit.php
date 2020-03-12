<?php
namespace Sleek\Modules;

# Render ACF form head stuff like CSS/JS etc
add_action('get_header', function () {
	if (current_user_can('edit_posts') and !is_admin()) {
		acf_form_head();
	}
});

add_action('after_setup_theme', function () {
	if (current_user_can('edit_posts') and !is_admin()) {
		# jQuery is required for acf form...
		remove_theme_support('sleek/disable_jquery');

		# Render the ACF form above each flexible module field
		add_action('sleek/modules/pre_render_flexible', function ($where, $id) {
			echo '<div class="dialog" id="dialog-edit-modules-' . $where . '">';
			echo '<h2>Edit Module</h2>';

			acf_form([
				'fields' => [$where]
			]);

			echo '</div>';
		}, 10, 2);

		# Render an edit button above each module
		add_action('sleek/modules/pre_render_flexible_module', function ($where, $id, $module, $data, $index) {
			?>
			<nav class="sleek-modules-inline-edit">
				<a href="#dialog-edit-modules-<?php echo $where ?>"
					class="sleek-modules-inline-edit-module"
					data-dialog-data='<?php echo json_encode(['where' => $where, 'id' => $id, 'module' => $module, 'index' => $index]) ?>'>
					<?php _e('Edit Module', 'sleek') ?>
				</a>
				<a href="#dialog-module-info-<?php echo $module ?>">
					<?php _e('Show Module Info', 'sleek') ?>
				</a>
			</nav>
			<div id="dialog-module-info-<?php echo $module ?>" class="dialog">
				<h2><?php echo $module ?></h2>
				<dl>
					<dt><?php _e('Post ID') ?></dt>
					<dd><?php echo $id ?></dd>

					<dt><?php _e('Module area') ?></dt>
					<dd><?php echo $where ?></dd>

					<dt><?php _e('Index') ?></dt>
					<dd><?php echo $index ?></dd>
				</dl>
				<pre><?php var_dump($data) ?></pre>
			</div>
			<?php
		}, 10, 5);

		# Clean up the acf form
		add_action('wp_head', function () {
			?>
			<style>
				/* Hide messages */
				div.dialog[id^="dialog-edit-modules-"] #message {
					display: none;
				}

				/* Util class */
				div.dialog[id^="dialog-edit-modules-"] .sleek-modules-inline-edit-hide {
					display: none;
				}

				/* Remove first field (the flex field) padding */
				div.dialog[id^="dialog-edit-modules-"] #acf-form > .acf-fields > .acf-field {
					padding: 0;
					border: 0;
				}

				/* Never collapse layouts */
				div.dialog[id^="dialog-edit-modules-"] .acf-flexible-content .layout.-collapsed > .acf-fields,
				div.dialog[id^="dialog-edit-modules-"] .acf-flexible-content .layout.-collapsed > .acf-table {
					display: block;
				}

				/* Remove flex field actions */
				div.dialog[id^="dialog-edit-modules-"] .acf-flexible-content .acf-actions {
					display: none;
				}

				/* Remove border from layouts */
				div.dialog[id^="dialog-edit-modules-"] .acf-flexible-content .values .layout {
					margin: 0;
					border: 0;
				}

				/* Hide layout handle and controls */
				div.dialog[id^="dialog-edit-modules-"] .acf-flexible-content .values .layout .acf-fc-layout-handle,
				div.dialog[id^="dialog-edit-modules-"] .acf-flexible-content .values .layout .acf-fc-layout-controls {
					display: none;
				}

				/* Remove side padding and border from inner fields */
				div.dialog[id^="dialog-edit-modules-"] .acf-flexible-content .values .layout .acf-fields > .acf-field {
					padding-left: 0;
					padding-right: 0;
					border: 0;
				}
			</style>
			<?php
		});

		# Listen to dialog open to re-arrange the editor
		add_action('wp_footer', function () {
			?>
			<script>
				window.addEventListener('sleek-ui-dialog-trigger-open', function (e) {
					if (e.detail && e.detail.data) {
						var dialog = e.detail.dialog;
						var data = JSON.parse(e.detail.data);
						var allLayouts = dialog.querySelectorAll('.acf-flexible-content > .values > .layout');
						var clickedLayout = dialog.querySelector('.acf-flexible-content > .values > .layout[data-id="row-' + data.index + '"]');

						if (allLayouts) {
							allLayouts.forEach(function (el) {
								el.classList.add('sleek-modules-inline-edit-hide');
							});
						}
						if (clickedLayout) {
							clickedLayout.classList.remove('sleek-modules-inline-edit-hide');
						}
					}
				});
			</script>
			<?php
		});
	}
});
