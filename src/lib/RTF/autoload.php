<?php

// Load all missing classes, search all over the src folder
spl_autoload_register(function($class) {
    $dirs = getDirs(__DIR__ . '/../..');
    foreach ($dirs as $dir) {
        $file = $dir . '/' . str_replace("\\", "/", $class) . '.php';
        if (file_exists($file)) {
            include $file;
        }
    }
});

/**
 * Get all [sub-]directories in a given directory
 * @param $dir
 * @param array $results
 * @return array List of directories
 */
function getDirs($dir, &$results = array()) {
    $files = scandir($dir);
    foreach ($files as $file) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $file);
        if (is_dir($path) && $file !== "." && $file !== "..") {
            getDirs($path, $results);
            $results[] = $path;
        }
    }
    return $results;
}