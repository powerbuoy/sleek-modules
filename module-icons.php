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
				$fallbackIcon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIzMiIgY3k9IjMyIiByPSIzMiIgZmlsbD0iI0M0QzRDNCIvPjxwYXRoIGQ9Ik0yNy4zMjQyIDM4SDI1LjYyNzlMMTkuMTg1NSAyOC4xMzg3VjM4SDE3LjQ4OTNWMjUuMjAzMUgxOS4xODU1TDI1LjY0NTUgMzUuMTA4NFYyNS4yMDMxSDI3LjMyNDJWMzhaTTMwLjM5MTYgMzkuMDk4NkgyOC45OTQxTDM0LjMzNzkgMjUuMjAzMUgzNS43MjY2TDMwLjM5MTYgMzkuMDk4NlpNNDQuODA1NyAzNC42NjAySDM5LjQ0NDNMMzguMjQwMiAzOEgzNi41TDQxLjM4NjcgMjUuMjAzMUg0Mi44NjMzTDQ3Ljc1ODggMzhINDYuMDI3M0w0NC44MDU3IDM0LjY2MDJaTTM5Ljk1NDEgMzMuMjcxNUg0NC4zMDQ3TDQyLjEyNSAyNy4yODYxTDM5Ljk1NDEgMzMuMjcxNVoiIGZpbGw9ImJsYWNrIi8+PC9zdmc+';
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
