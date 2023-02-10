<?php
$app = require __DIR__ . '/../lib/RTF.php';

$app->container->set('config', '\RTF\Config');
$app->container->set('helper', '\RTF\Helper');

$app->container->set("db", new \RTF\DB($app->container->config('db.db'), $app->container->config('db.user'), $app->container->config('db.pass'), $app->container->config('db.host'), $app->container->config('db.charset')));

$app->cli('download', 'IMDbImport@download');
$app->cli('extract', 'IMDbImport@extract');

$app->cli('import', 'IMDbImport@import');
$app->cli('import-names', 'IMDbImport@importNames');
$app->cli('import-titles', 'IMDbImport@importTitles');
$app->cli('import-title-akas', 'IMDbImport@importTitleAkas');
$app->cli('import-episodes', 'IMDbImport@importEpisodes');
$app->cli('import-ratings', 'IMDbImport@importRatings');
$app->cli('import-principals', 'IMDbImport@importPrincipals');

$app->cli([null, 'help'], function() {
    echo "rmdb 0.1 - Imports IMDb datasets into relational database for easy querying\n\n";
    echo "Usage:\n";
    echo "- Create MySQL database, import config/schema.sql\n";
    echo "- Enter DB details in config/config.json\n";
    echo "- php index.php [command]\n\n";
    echo "Commands:\n";
    echo "help\t\t\t- Print this help\n";
    echo "download\t\t- Download zipped TSV files from datasets.imdbws.com. Needs 1.5GB+ of storage\n";
    echo "extract\t\t\t- Extract zipped TSV files. Needs 7GB+ of storage\n";
    echo "import\t\t\t- Import everything, replace existing data. Runs all the below commands:\n";
    echo "import-names\t\t- Import cast & crew\n";
    echo "import-titles\t\t- Import movies, TV episodes, short films, ...\n";
    echo "import-titles-akas\t- Import foreign names for titles\n";
    echo "import-episodes\t\t- Import episode <> show/series relation\n";
    echo "import-ratings\t\t- Import average ratings for titles\n";
    echo "import-principals\t- Import directors, writers. Should be redundant if you import names\n";
});

$app->run();