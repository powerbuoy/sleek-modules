<?php
namespace Sleek\Modules;

#############################
# Get array of file meta data
# about module files
function get_file_meta () {
	$path = get_stylesheet_directory() . apply_filters('sleek_modules_path', '/modules/') . '**/module.php';
	$inflector = \ICanBoogie\Inflector::get('en');
	$files = [];

	foreach (glob($path) as $file) {
		$pathinfo = pathinfo($file);
		$basename = basename($pathinfo['dirname']);
		$snakeName = $inflector->underscore($basename);
		$className = $inflector->camelize($basename);
		$label = $inflector->titleize($basename);
		$labelPlural = $inflector->pluralize($label);
		$slug = str_replace('_', '-', $snakeName);

		$files[] = (object) [
			'pathinfo' => $pathinfo,
			'filename' => $pathinfo['filename'],
			'snakeName' => $snakeName,
			'className' => $className,
			'label' => $label,
			'labelPlural' => $labelPlural,
			'slug' => $slug,
			'path' => $file
		];
	}

	return $files;
}

####################
# Create all modules
if ($files = get_file_meta()) {
	foreach ($files as $file) {
		# Include the class
		require_once $file->path;

		# Create instance of class
		$fullClassName = "Sleek\Modules\\$file->className";

		$obj = new $fullClassName;

		# Run callback
		$obj->created();
	}
}

######################
# Render single module
# TODO: Support for template only module?
function render ($name, $fields = [], $template = null) {
	$inflector = \ICanBoogie\Inflector::get('en');
	$className = $inflector->camelize($name);
	$fullClassName = "Sleek\Modules\\$className";

	$obj = new $fullClassName($fields);

	$obj->render($template);
}
