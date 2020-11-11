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

				if ($meta['icon']) {
					$title = '<figure class="sleek-module-icon"><img src="' . $meta['icon'] . '"></figure> ' . $title;
				}
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
				width: 3rem;
				height: 3rem;
				margin: 0 0.5rem;
				display: inline-block;
				vertical-align: middle;
			}

			figure.sleek-module-icon img {
				width: 100%;
				height: 100%;
				object-fit: contain;
			}
		</style>
		<?php
	}
});
