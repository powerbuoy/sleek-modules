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
				max-height: 80vh;
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

			div.acf-fc-popup ul li a p + p {
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
							// First insert additional templates
							popup.querySelectorAll('li').forEach(function (mod) {
								var link = mod.querySelector('a');
								var moduleName = link.dataset.layout || null;
								var moduleInfo = moduleData[moduleName] || null;
								var linkHTML = mod.innerHTML;

								link.setAttribute('data-template', 'template');

								// TODO
								if (false && moduleInfo.templates) {
									moduleInfo.templates.forEach(function (template) {
										if (template.filename !== 'template') {
											var templateLi = document.createElement('li');

											templateLi.innerHTML = linkHTML;

											var templateLink = templateLi.querySelector('a');

											templateLink.innerText += ' (' + template.title + ')';

											templateLink.setAttribute('data-template', template.filename);
											mod.parentNode.insertBefore(templateLi, mod.nextSibling);
										}
									});
								}
							});

							// Now insert additional data to all links
							popup.querySelectorAll('li').forEach(function (mod) {
								var link = mod.querySelector('a');
								var moduleName = link.dataset.layout || null;
								var moduleInfo = moduleData[moduleName] || null;
								var linkHTML = mod.innerHTML;

								if (moduleInfo) {
									if (moduleInfo.readme) {
										var readme = document.createElement('p');

										readme.innerHTML = moduleInfo.readme;

										link.appendChild(readme);
									}

									var screenshot = document.createElement('figure');
									var src = 'https://placehold.it/800x800?text=' + moduleInfo.title;

									if (link.dataset.template && moduleInfo.templates) {
										moduleInfo.templates.forEach(function (template) {
											if (template.filename === link.dataset.template) {
												if (template.screenshot) {
													src = template.screenshot;
												}

												if (template.readme) {
													var readme = document.createElement('p');

													readme.innerHTML = template.readme;

													link.appendChild(readme);
												}
											}
										});
									}

									screenshot.innerHTML = '<img src="' + src + '">';

									link.prepend(screenshot);
								}

								// TODO: Select the correct template
							/*	link.addEventListener('click', function () {
									console.log('Adding module ' + link.dataset.layout + ' with template ' + link.dataset.template);
								}); */
							});
						});
					}, 50); // NOTE: Wait for popup to render
				});
			});
		</script>
		<?php
	}
});
