<?php
# TODO: Add each template as separate choice in popup, correctly populating the template field on click
# TODO: Use potential new hooks to edit popup rather than using JS
namespace Sleek\Modules;

function get_module_data () {
	$path = get_stylesheet_directory() . '/modules/**/module.php';
	$moduleData = [];

	foreach (glob($path) as $file) {
		$pathinfo = pathinfo($file);
		$moduleName = basename($pathinfo['dirname']);
		$templates = get_module_templates($moduleName);
		$readmePath = get_stylesheet_directory() . '/modules/' . $moduleName . '/README.md';
		$screenshotPath = get_stylesheet_directory() . '/modules/' . $moduleName . '/template.png';
		$screenshotUrl = get_stylesheet_directory_uri() . '/modules/' . $moduleName . '/template.png';

		$moduleData[\Sleek\Utils\convert_case($moduleName, 'snake')] = [
			'name' => $moduleName,
			'title' => \Sleek\Utils\convert_case($moduleName, 'title'),
			'readme' => file_exists($readmePath) ? file_get_contents($readmePath) : null,
			'screenshot' => file_exists($screenshotPath) ? $screenshotUrl : null,
			'templates' => $templates
		];
	}

	return $moduleData;
}

add_action('admin_head', function () {
	if (get_theme_support('sleek/modules/add_new_module_preview')) {
		?>
		<style>
			div.acf-fc-popup {
				--sleek-anmp-cols: 1;

				position: fixed;
				left: 50% !important;
				top: 50% !important;
				transform: translate(-50%, -50%);

				width: 80vw;
				max-width: 80rem;
				max-height: 80vw;
				overflow: auto;

				margin: 0 !important;
				padding: 3rem;
				box-shadow: 0 0 0 100vw rgba(0, 0, 0, .5);
			}

			@media (min-width: 600px) {
				div.acf-fc-popup {
					--sleek-anmp-cols: 3;
				}
			}

			@media (min-width: 800px) {
				div.acf-fc-popup {
					--sleek-anmp-cols: 4;
				}
			}

			@media (min-width: 1200px) {
				div.acf-fc-popup {
					--sleek-anmp-cols: 6;
				}
			}

			div.acf-fc-popup ul {
				display: grid;
				grid-gap: 2rem;
				grid-template-columns: repeat(var(--sleek-anmp-cols), minmax(0, 1fr));
				margin: 0;
				list-style: none;
			}

			div.acf-fc-popup ul li {
				white-space: normal;
			}

			div.acf-fc-popup ul li a {
				display: block;
				padding: 0;
				font-size: 18px;
				font-weight: bold;
			}

			div.acf-fc-popup ul li a:hover {
				background: transparent;
			}

			div.acf-fc-popup ul li a figure {
				margin: 0 0 16px;
			}

			div.acf-fc-popup ul li a figure img {
				width: 100%;
			}

			div.acf-fc-popup ul li a p {
				margin: 16px 0 0;
				font-size: 14px;
				font-weight: normal;
			}
		</style>
		<?php
	}
});

add_action('admin_footer', function () {
	if (get_theme_support('sleek/modules/add_new_module_preview')) {
		?>
		<script>
			var moduleData = <?php echo json_encode(get_module_data()) ?>;

			document.querySelectorAll('a.acf-button[data-name="add-layout"]').forEach(function (button) {
				button.addEventListener('mouseup', function (e) {
					setTimeout(function () {
						document.querySelectorAll('div.acf-fc-popup').forEach(function (popup) {
							var modules = popup.querySelectorAll('li');

							modules.forEach(function (mod) {
								var link = mod.querySelector('a');
								var moduleName = link.dataset.layout || null;
								var moduleInfo = moduleData[moduleName] || null;

								if (moduleInfo) {
									console.dir(moduleInfo);

									var screenshot = document.createElement('figure');

									if (moduleInfo.screenshot) {
										screenshot.innerHTML = '<img src="' + moduleInfo.screenshot + '">';
									}
									else {
										screenshot.innerHTML = '<img src="https://placehold.it/800x800?text=' + moduleInfo.title + '">';
									}

									link.prepend(screenshot);

									if (moduleInfo.readme) {
										var readme = document.createElement('p');

										readme.innerHTML = moduleInfo.readme;

										link.appendChild(readme);
									}
								}
							});
						});
					}, 50); // NOTE: Wait for popup to render
				});
			});
		</script>
		<?php
	}
});
