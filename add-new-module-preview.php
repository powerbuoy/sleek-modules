<?php
# TODO: Add each template as separate choice in popup, correctly populating the template field on click
# TODO: Use potential new hooks to edit popup rather than using JS
namespace Sleek\Modules;

function get_module_data () {
	$path = get_stylesheet_directory() . '/modules/**/module.php';
	$moduleData = [];

	foreach (glob($path) as $file) {
		$moduleName = basename(dirname($file));
		$fullClassName = "Sleek\Modules\\" . \Sleek\Utils\convert_case($moduleName, 'pascal');

		$mod = new $fullClassName;
		$templates = $mod->templates();
		$meta = $mod->meta();

		$moduleData[\Sleek\Utils\convert_case($moduleName, 'snake')] = [
			'name' => $moduleName,
			'title' => $meta['title'],
			'readme' => $meta['readme'],
			'screenshot' => $meta['screenshot'],
			'icon' => $meta['icon'],
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

				background: white;

				position: fixed;
				left: 50% !important;
				top: 50% !important;
				transform: translate(-50%, -50%);

				width: 80vw;
				max-width: 80rem;
				max-height: 80vh;
				overflow: auto;

				margin: 0 !important;
				padding: 3rem;
				box-shadow: 0 0 0 100vw rgba(0, 0, 0, .8);
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

			div.acf-fc-popup ul li > a {
				background: white;

				display: block;
				position: relative;
				padding: 1.5rem;

				font-size: 18px;
				font-weight: bold;
				color: #222;
				text-align: center;

				border-radius: 0.25rem;
				box-shadow: none;
				transform: scale(1);
				transition: all 0.4s ease;
			}

			div.acf-fc-popup ul li > a:hover {
				background: white;
				z-index: 1;
				color: #222;
				transform: scale(1.1);
				box-shadow: 0 0.6rem 1.2rem rgba(0, 0, 0, 0.2), 0 0.4rem 0.4rem rgba(0, 0, 0, 0.25);
			}

			div.acf-fc-popup ul li > a figure {
				position: relative;
				margin: 0 0 16px;
			}

			div.acf-fc-popup ul li > a figure::before {
				display: block;
				content: "";
				padding-bottom: 56.25%;
			}

			div.acf-fc-popup ul li > a figure img {
				position: absolute;
				left: 50%;
				top: 50%;
				transform: translate(-50%, -50%);
				max-width: 100%;
				max-height: 100%;
			}

			div.acf-fc-popup ul li > a p {
				margin: 16px 0 0;
				font-size: 12px;
				font-weight: normal;
			}

			div.acf-fc-popup ul li > a p + p {
				margin-top: 0;
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

			document.querySelectorAll('a[data-name="add-layout"]').forEach(function (button) {
				button.addEventListener('click', function (e) {
					// NOTE: Wait for popup to render
					setTimeout(function () {
						// Go through every acf-fc-popup element (there should only be one)
						document.querySelectorAll('div.acf-fc-popup').forEach(function (popup) {
							// Now insert additional data to all links
							popup.querySelectorAll('li').forEach(function (mod) {
								var link = mod.querySelector('a');
								var moduleName = link.dataset.layout || null;
								var moduleInfo = moduleData[moduleName] || null;

								if (moduleInfo) {
									if (moduleInfo.readme) {
										var readme = document.createElement('p');

										readme.innerHTML = moduleInfo.readme;

										link.appendChild(readme);
									}

									var screenshot = document.createElement('figure');
									var src = 'https://placehold.it/800x800?text=' + moduleInfo.title;

									if (moduleInfo.icon) {
										src = moduleInfo.icon;
									}
									else if (moduleInfo.screenshot) {
										src = moduleInfo.screenshot;
									}

									screenshot.innerHTML = '<img src="' + src + '">';

									link.prepend(screenshot);
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
