<?php

namespace CLI;

class IMDbImport extends \RTF\Controller {

    public $tmpDir = __DIR__ . '/../../../tmp/';
    public $baseURL = 'https://datasets.imdbws.com/';
    public $fileNames;

    public function __construct() {
        $this->fileNames = [
            "name.basics.tsv.gz",
            "title.akas.tsv.gz",
            "title.basics.tsv.gz",
            "title.crew.tsv.gz",
            "title.episode.tsv.gz",
            "title.principals.tsv.gz",
            "title.ratings.tsv.gz"
        ];
    }

    public function execute() {

        echo "Importing data from IMDb\n";

        $this->download();

        $this->extract();

        //echo $this->config->get('db/db');
    }

    public function download() {

        echo "Downloading from " . $this->baseURL . ":\n";

        foreach ($this->fileNames as $fileName) {
            echo "Downloading " . $fileName . "... ";
            $res = file_put_contents($this->tmpDir . $fileName, file_get_contents($this->baseURL . $fileName));
            if ($res) {echo "ok\n";} else {echo "error\n"; return;}
        }

        echo "Done\n";
    }

    public function extract() {

        foreach ($this->fileNames as $fileName) {
            echo "Extracting " . $fileName . "... ";

            $bufferSize = 4096;
            $outFileName = str_replace('.gz', '', $fileName);

            $file = gzopen($this->tmpDir . $fileName, 'rb');
            $outFile = fopen($this->tmpDir . $outFileName, 'wb');

            while (!gzeof($file)) {
                fwrite($outFile, gzread($file, $bufferSize));
            }

            fclose($outFile);
            gzclose($file);

            echo "ok\n";
        }

    }
}