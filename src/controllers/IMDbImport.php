<?php

class IMDbImport extends \RTF\Controller {

    public $tmpDir = __DIR__ . '/../../tmp/';
    public $baseURL = 'https://datasets.imdbws.com/';
    public $fileNames;

    public function __construct($container) {
        parent::__construct($container);
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

        $this->import();

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

    public function import() {

        //
        //nconst	primaryName	birthYear	deathYear	primaryProfession	knownForTitles
        //nm0000001	Fred Astaire	1899	1987	soundtrack,actor,miscellaneous	tt0072308,tt0050419,tt0053137,tt0031983

        $numberNames = $this->helper->getFileLines($this->tmpDir . "name.basics.tsv") - 1;
        echo "Inserting " . $numberNames . " Names... \n";

        $startTime = microtime(true);

        $this->db->execute("TRUNCATE TABLE names");
        $row = -1;
        $inserts = [];
        if (($handle = fopen($this->tmpDir . "name.basics.tsv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $row++;
                if ($row == 0) continue;

                $inserts[] = [
                    'id'            => $data['0'],
                    'primary_name'  => $data['1'],
                    'birth_year'    => intval($data['2']) != 0 ? intval($data['2']) : null,
                    'death_year'    => intval($data['3']) != 0 ? intval($data['3']) : null,
                ];

                if ($row % 100 == 0) {
                    $this->db->insertMulti("names", $inserts);
                    $inserts = [];
                }

                if ($row % 1000 == 0) {
                    $nowTime = microtime(true);
                    $elapsedTime = $nowTime - $startTime;
                    $namesPerSecond = ($row / $elapsedTime);
                    $namesLeft = $numberNames - $row;
                    $secondsLeft = intval($namesLeft / $namesPerSecond);
                    echo chr(27) . "[0G"; // replace current line
                    echo "\rInserted " . $row . " / " . $numberNames . " Names. Time left: " . $this->helper->formatTime($secondsLeft) . "                ";
                }


            }
            fclose($handle);

            // some left?
            if (!empty($inserts)) {
                $this->db->insertMulti("names", $inserts);
            }

            echo "\nInserted " . ($row - 1) . " Names\n";
        }

    }



}