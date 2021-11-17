<?php
namespace Sleek\Modules;

#########################################
# Insert icon into flexible content title
add_action('after_setup_theme', function () {
	if (get_theme_support('sleek/modules/module_icons')) {
		add_filter('acf/fields/flexible_content/layout_title', function ($title, $field, $layout, $i) {
			$moduleName = $layout['name'];
			$fullClassName = "\\Sleek\Modules\\" . \Sleek\Utils\convert_case($moduleName, 'pascal');

			if (class_exists($fullClassName)) {
				$module = new $fullClassName;
				$meta = $module->meta();
				$fallbackIcon = get_stylesheet_directory_uri() . '/vendor/powerbuoy/sleek-modules/icon-fallback.svg';
				$title = '<figure class="sleek-module-icon"><img src="' . ($meta['icon'] ?? $fallbackIcon) . '"></figure> ' . $title;
			}

			return $title;
		}, 11, 4);
	}
});

################
# Style the icon
add_action('admin_head', function () {
	if (get_theme_support('sleek/modules/module_icons')) {
		?>
		<style>
			figure.sleek-module-icon {
				width: 48px;
				height: 48px;
				margin: 0 8px;
				display: inline-block;
				vertical-align: middle;
			}

			figure.sleek-module-icon img {
				width: 100%;
				height: 100%;
				object-fit: contain;
			}

			.acf-flexible-content .layout .acf-fc-layout-controls {
				top: 24px;
			}
		</style>
		<?php
	}
});
