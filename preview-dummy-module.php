<?php
namespace Sleek\Modules;

add_action('init', function () {
	add_filter('query_vars', function ($vars) {
		$vars[] = 'sleek_modules_preview_dummy_module_module';
		$vars[] = 'sleek_modules_preview_dummy_module_template';

		return $vars;
	});

	add_rewrite_rule(
		'^__SLEEK__/modules/dummy-module-preview/([^/]+)/([^/]+)/?$',
		'index.php?sleek_modules_preview_dummy_module_module=$matches[1]&sleek_modules_preview_dummy_module_template=$matches[2]',
		'top'
	);

	add_action('template_redirect', function () {
		global $wp_query;

		if (isset($wp_query->query_vars['sleek_modules_preview_dummy_module_module'])) {
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
						render_dummy(
							$wp_query->query_vars['sleek_modules_preview_dummy_module_module'],
							$wp_query->query_vars['sleek_modules_preview_dummy_module_template']
						);
					?>
					<?php wp_footer() ?>
				</body>
			</html>
			<?php

			exit;
		}
	});
});
