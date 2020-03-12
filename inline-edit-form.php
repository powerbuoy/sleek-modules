<?php acf_form_head() ?>

<!DOCTYPE html>

<html <?php language_attributes() ?> <?php body_class() ?>>

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

			/* This style isn't added by acf_form_head() */
			.screen-reader-text {
				display: none;
			}

			/* Full size media modal (because it looks really weird with a modal inside a modal) */
			.media-modal {
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

		<?php if (isset($_GET['updated'])) : ?>
			<script>
				window.parent.postMessage({sleekModulesInlineEditUpdated: true}, '<?php echo home_url() ?>');
			</script>
		<?php endif ?>

	</body>

</html>
