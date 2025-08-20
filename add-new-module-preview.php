<?php
# TODO: Add each template as separate choice in popup, correctly populating the template field on click
# TODO: Use potential new hooks to edit popup rather than using JS
namespace Sleek\Modules;

function get_module_data () {
	$path = get_stylesheet_directory() . '/modules/**/module.php';
	$moduleData = [];

	foreach (glob($path) as $file) {
		$moduleName = basename(dirname($file));
		$fullClassName = "\\Sleek\Modules\\" . \Sleek\Utils\convert_case($moduleName, 'pascal');
		$mod = new $fullClassName;
		$templates = $mod->templates();
		$meta = $mod->meta();
		$meta['templates'] = $templates;
		$moduleData[\Sleek\Utils\convert_case($moduleName, 'snake')] = $meta;
	}

	return $moduleData;
}

#################
# Style the popup
add_action('admin_head', function () {
	if (get_theme_support('sleek/modules/add_new_module_preview')) {
		$current_screen = get_current_screen();
		$is_gutenberg = true;

		if (method_exists($current_screen, 'is_block_editor') && !$current_screen->is_block_editor()) {
			$is_gutenberg = false;
		}

		if (!$is_gutenberg) {
			?>
			<style>
				#poststuff .acf-flexible-content > div.acf-fc-popup {
					all: unset;

					--sleek-anmp-cols: 1;

					background: white;

					position: fixed !important;
					left: 50% !important;
					top: 50% !important;
					transform: translate(-50%, -50%);
					z-index: 99999;

					width: 80vw;
					max-width: 80rem;
					max-height: 80vh;
					overflow: auto;

					margin: 0 !important;
					padding: 3rem;
					box-shadow: 0 0 0 100vw rgba(0, 0, 0, .8);
					border-radius: 1rem;
				}

				@media (min-width: 600px) {
					#poststuff .acf-flexible-content > div.acf-fc-popup {
						--sleek-anmp-cols: 3;
					}
				}

				@media (min-width: 800px) {
					#poststuff .acf-flexible-content > div.acf-fc-popup {
						--sleek-anmp-cols: 4;
					}
				}

				@media (min-width: 1200px) {
					#poststuff .acf-flexible-content > div.acf-fc-popup {
						--sleek-anmp-cols: 6;
					}
				}

				/* Module category */
				#poststuff .acf-flexible-content > div.acf-fc-popup section {
					all: unset;
					display: block;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup section:not(:last-child) {
					padding-bottom: 2rem;
					margin-bottom: 2rem;
					border-bottom: 1px solid #ccc;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup section h2 {
					all: unset;
					display: block;
					text-align: center;
					font-size: 1.5rem;
					font-weight: bold;
					color: #222;
					margin: 0 0 1rem;
					padding: 0;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup section:only-child h2 {
					display: none; /* NOTE: Hide category if only one */
				}

				/* List of modules */
				#poststuff .acf-flexible-content > div.acf-fc-popup ul {
					all: unset;
					display: grid;
					grid-gap: 1rem;
					grid-template-columns: repeat(var(--sleek-anmp-cols), minmax(0, 1fr));
					margin: 0;
					list-style: none;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup ul li {
					all: unset;
					white-space: normal;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup ul li > a {
					all: unset;

					background: white;

					display: block;
					position: relative;
					padding: 1rem;

					font-size: 1rem;
					font-weight: bold;
					color: #222;
					text-align: center;
					cursor: pointer;

					border-radius: 0.25rem;
					box-shadow: none;
					border-radius: 0.5rem;

					transform: scale(1);
					transition: all 0.25s ease;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup ul li > a:hover {
					background: white;
					z-index: 1;
					color: #222;
					transform: scale(1.1);
					box-shadow: 0 0.6rem 1.2rem rgba(0, 0, 0, 0.2), 0 0.4rem 0.4rem rgba(0, 0, 0, 0.25);
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup ul li > a figure {
					position: relative;
					margin: 0 auto 1rem;
					max-width: 8rem;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup ul li > a figure::before {
					display: block;
					content: "";
					padding-bottom: 56.25%;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup ul li > a figure img {
					position: absolute;
					left: 50%;
					top: 50%;
					transform: translate(-50%, -50%);
					max-width: 100%;
					max-height: 100%;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup ul li > a p {
					margin: 0.5rem 0 0;
					font-size: 0.75rem;
					font-weight: normal;
				}

				#poststuff .acf-flexible-content > div.acf-fc-popup ul li > a p + p {
					margin-top: 0;
				}
			</style>
			<?php
		}
	}
});

##################
# Script the popup
add_action('admin_footer', function () {
	if (get_theme_support('sleek/modules/add_new_module_preview')) {
		$current_screen = get_current_screen();
		$is_gutenberg = true;

		if (method_exists($current_screen, 'is_block_editor') && !$current_screen->is_block_editor()) {
			$is_gutenberg = false;
		}

		if (!$is_gutenberg) {
			?>
			<script>
				var moduleData = <?php echo json_encode(get_module_data()) ?>;

				document.querySelectorAll('script.tmpl-popup').forEach(template => {
					var groups = {"Uncategorized": []};
					var temp = document.createElement('div');

					// So we can work with the template DOM
					temp.innerHTML = template.innerHTML;

					// Create new module HTML
					temp.querySelectorAll('li').forEach(li => {
						var newModule = document.createElement('div');

						// Start off with the old HTML
						newModule.innerHTML = li.innerHTML;

						// Grab stuff we need
						var link = newModule.querySelector('a');
						var moduleName = link.dataset.layout || null;
						var moduleInfo = moduleData[moduleName] || {};
						var category = moduleInfo.category || 'Uncategorized';

						// Add description
						if (moduleInfo.description) {
							var description = document.createElement('p');

							description.innerHTML = moduleInfo.description;

							link.appendChild(description);
						}

						// Add screenshot
						var screenshot = document.createElement('figure');
						var src = '<?php echo get_stylesheet_directory_uri() ?>/vendor/powerbuoy/sleek-modules/icon-fallback.svg';

						if (moduleInfo.icon) {
							src = moduleInfo.icon;
						}
						else if (moduleInfo.screenshot) {
							src = moduleInfo.screenshot;
						}

						screenshot.innerHTML = '<img src="' + src + '">';

						link.prepend(screenshot);

						// Now group by category
						if (typeof groups[category] === 'undefined') {
							groups[category] = [];
						}

						groups[category].push(newModule);
					});

					// Sort by category name
					// https://stackoverflow.com/questions/5467129/sort-javascript-object-by-key
					var orderedGroups = Object.keys(groups).sort().reduce((obj, key) => {
						obj[key] = groups[key];

						return obj;
					}, {});

					// Now create group HTML
					var newTemplate = '<div>';

					for (var catName in orderedGroups) {
						// Make sure there are modules in the group (Uncategorized may be empty)
						if (groups[catName].length) {
							newTemplate += '<section><h2>' + catName + '</h2><ul>';

							groups[catName].forEach(module => {
								newTemplate += '<li>' + module.innerHTML + '</li>';
							});

							newTemplate += '</ul></section>';
						}
					}

					newTemplate += '</div>';

					// Replace current template
					template.innerHTML = newTemplate;
				});
			</script>
			<?php
		}
	}
});
