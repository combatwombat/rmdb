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

    public function importAll() {
        //$this->importNames();
        //$this->importTitles();
        //$this->importTitleAkas();
        //$this->importEpisodes();
        //$this->importRatings();
        $this->importPrincipals();
    }

    public function importNames() {

        // nconst	primaryName	birthYear	deathYear	primaryProfession	knownForTitles
        // nm0000001	Fred Astaire	1899	1987	soundtrack,actor,miscellaneous	tt0072308,tt0050419,tt0053137,tt0031983

        $numberItems = $this->helper->getFileLines($this->tmpDir . "name.basics.tsv") - 1;
        echo "Inserting " . $numberItems . " Names... \n";

        $startTime = microtime(true);

        $this->db->execute("TRUNCATE TABLE names");
        $this->db->execute("TRUNCATE TABLE professions");
        $this->db->execute("TRUNCATE TABLE names_primaryprofessions");
        $this->db->execute("TRUNCATE TABLE names_knownfortitles");

        $row = -1;
        $nameInserts = [];
        $professionInserts = [];
        $namesProfessionsInserts = [];
        $namesKnownfortitlesInserts = [];
        $existingProfessions = [];
        if (($handle = fopen($this->tmpDir . "name.basics.tsv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $row++;
                if ($row == 0) continue;

                $nameInserts[] = [
                    'id'            => $data[0],
                    'primary_name'  => $data[1],
                    'birth_year'    => intval($data[2]) != 0 ? intval($data[2]) : null,
                    'death_year'    => intval($data[3]) != 0 ? intval($data[3]) : null,
                ];


                $professions = explode(",", $data[4]);
                foreach ($professions as $profession) {
                    if (empty($profession)) continue;

                    $professionInsert = [
                        'id' => $profession,
                        'display_name' => ucwords(str_replace("_", " ", $profession))
                    ];
                    if (!in_array($profession, $existingProfessions)) {
                        $existingProfessions[] = $profession;
                        $professionInserts[] = $professionInsert;
                    }
                    $namesProfessionsInserts[] = [
                        'name_id' => $data[0],
                        'profession_id' => $profession
                    ];
                }

                if (!empty($data[5])) {
                    $knownfortitles = explode(",", $data[5]);
                    foreach ($knownfortitles as $knownfortitle) {
                        $namesKnownfortitlesInserts[] = [
                            'name_id' => $data[0],
                            'title_id' => $knownfortitle
                        ];
                    }
                }


                if ($row % 10000 == 0) {
                    $this->db->insertMulti("names", $nameInserts);
                    $nameInserts = [];

                    $this->db->insertMulti("professions", $professionInserts);
                    $professionInserts = [];

                    $this->db->insertMulti("names_primaryprofessions", $namesProfessionsInserts);
                    $namesProfessionsInserts = [];

                    $this->db->insertMulti("names_knownfortitles", $namesKnownfortitlesInserts);
                    $namesKnownfortitlesInserts = [];
                }

                if ($row % 1000 == 0) {
                    $nowTime = microtime(true);
                    $elapsedTime = $nowTime - $startTime;
                    $itemsPerSecond = ($row / $elapsedTime);
                    $itemsLeft = $numberItems - $row;
                    $secondsLeft = intval($itemsLeft / $itemsPerSecond);
                    echo chr(27) . "[0G"; // replace current line
                    echo "\rInserted " . $row . " / " . $numberItems . " Names. Time left: " . $this->helper->formatTime($secondsLeft) . "                ";
                }


            }
            fclose($handle);

            // some left?
            if (!empty($nameInserts)) {
                $this->db->insertMulti("names", $nameInserts);
            }

            if (!empty($professionInserts)) {
                $this->db->insertMulti("professions", $professionInserts);
            }

            if (!empty($namesProfessionsInserts)) {
                $this->db->insertMulti("names_primaryprofessions", $namesProfessionsInserts);
            }

            if (!empty($namesKnownfortitlesInserts)) {
                $this->db->insertMulti("names_knownfortitles", $namesKnownfortitlesInserts);
            }

            echo "\nInserted " . $row . " Names\n";
        }

    }

    public function importTitles() {
        // tconst	titleType	primaryTitle	originalTitle	isAdult	startYear	endYear	runtimeMinutes	genres
        // tt0000001	short	Carmencita	Carmencita	0	1894	\N	1	Documentary,Short


        $numberItems = $this->helper->getFileLines($this->tmpDir . "title.basics.tsv") - 1;
        echo "Inserting " . $numberItems . " Titles... \n";

        $startTime = microtime(true);

        $this->db->execute("TRUNCATE TABLE titles");
        $this->db->execute("TRUNCATE TABLE genres");
        $this->db->execute("TRUNCATE TABLE titles_genres");

        $row = -1;
        $titleInserts = [];
        $genreInserts = [];
        $titlesGenresInserts = [];
        $existingGenres = [];
        if (($handle = fopen($this->tmpDir . "title.basics.tsv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $row++;
                if ($row == 0) continue;

                $titleInserts[] = [
                    'id'                => $data[0],
                    'title_type'        => $data[1],
                    'primary_title'     => $data[2],
                    'original_title'    => $data[3],
                    'is_adult'          => $data[4] == 0 ? 0 : 1,
                    'start_year'        => intval($data[5]) != 0 ? intval($data[5]) : null,
                    'end_year'          => intval($data[6]) != 0 ? intval($data[6]) : null,
                    'runtime_minutes'   => intval($data[7]) != 0 ? intval($data[7]) : null,
                ];


                $genres = explode(",", $data[8]);
                foreach ($genres as $genre) {
                    if (empty($genre) || $genre == "\\N") continue;

                    $genreId = strtolower(str_replace(" ", "_", $genre));

                    $genreInsert = [
                        'id' => $genreId,
                        'display_name' => $genre
                    ];
                    if (!in_array($genreId, $existingGenres)) {
                        $existingGenres[] = $genreId;
                        $genreInserts[] = $genreInsert;
                    }
                    $titlesGenresInserts[] = [
                        'title_id' => $data[0],
                        'genre_id' => $genreId
                    ];
                }

                if ($row % 10000 == 0) {
                    $this->db->insertMulti("titles", $titleInserts);
                    $titleInserts = [];

                    $this->db->insertMulti("genres", $genreInserts);
                    $genreInserts = [];

                    $this->db->insertMulti("titles_genres", $titlesGenresInserts);
                    $titlesGenresInserts = [];

                }

                if ($row % 1000 == 0) {
                    $nowTime = microtime(true);
                    $elapsedTime = $nowTime - $startTime;
                    $titlesPerSecond = ($row / $elapsedTime);
                    $titlesLeft = $numberItems - $row;
                    $secondsLeft = intval($titlesLeft / $titlesPerSecond);
                    echo chr(27) . "[0G"; // replace current line
                    echo "\rInserted " . $row . " / " . $numberItems . " Titles. Time left: " . $this->helper->formatTime($secondsLeft) . "                ";
                }


            }
            fclose($handle);

            // some left?
            if (!empty($titleInserts)) {
                $this->db->insertMulti("titles", $titleInserts);
            }

            if (!empty($genreInserts)) {
                $this->db->insertMulti("genres", $genreInserts);
            }

            if (!empty($titlesGenresInserts)) {
                $this->db->insertMulti("titles_genres", $titlesGenresInserts);
            }


            echo "\nInserted " . $row . " Names\n";
        }

    }


    public function importTitleAkas() {
        // titleId	    ordering	title	                                            region	language	types	        attributes	isOriginalTitle
        // tt0000001	1	        Карменсіта	                                        UA	    \N	        imdbDisplay	    \N	        0
        // tt12854886	3	        Arsène Lapin et le collier de la princesse indienne	FR	    \N	        alternative	    \N	        0

        $numberItems = $this->helper->getFileLines($this->tmpDir . "title.akas.tsv") - 1;
        echo "Inserting " . $numberItems . " Title AKAs... \n";

        $startTime = microtime(true);

        $this->db->execute("TRUNCATE TABLE titleakas");

        $this->db->execute("TRUNCATE TABLE titleakatypes");
        $this->db->execute("TRUNCATE TABLE titleakas_titleakatypes");

        $this->db->execute("TRUNCATE TABLE titleakaattributes");
        $this->db->execute("TRUNCATE TABLE titleakas_titleakaattributes");

        $row = -1;
        $titleakaInserts = [];

        $titleakatypeInserts = [];
        $titleakasTitleakatypesInserts = [];
        $existingTitleakatypes = [];

        $titleakaattributeInserts = [];
        $titleakasTitleakaattributesInserts = [];
        $existingTitleakaattributes = [];
        if (($handle = fopen($this->tmpDir . "title.akas.tsv", "r")) !== FALSE) {
            while (($line = fgets($handle)) !== FALSE) {
                $line = str_replace('"', '', $line); // special case for tt3984412 which starts with an " and throws the CSV parser off
                $data = str_getcsv($line, "\t");

                $row++;
                if ($row == 0) continue;

                $titleakaId = $data[0] . "_" . $data[1];

                $titleakaInserts[] = [
                    'id'                => $titleakaId,
                    'title_id'          => $data[0],
                    'ordering'          => $data[1],
                    'title'             => $data[2],
                    'region'            => $data[3],
                    'language'          => $data[4],
                    'is_original_title' => intval($data[7]) != 0 ? intval($data[7]) : 0,
                ];


                $titleakatypes = explode(",", $data[5]);
                foreach ($titleakatypes as $titleakatype) {
                    if (empty($titleakatype) || $titleakatype == "\\N") continue;

                    $titleakatypeId = preg_replace('/[[:^print:]]/', '', strtolower(str_replace(" ", "_", $titleakatype)));

                    $titleakatypeInsert = [
                        'id' => $titleakatypeId,
                        'display_name' => ucwords(trim(str_replace("imdbDisplay", " IMDb Display", $titleakatype)))
                    ];
                    if (!in_array($titleakatypeId, $existingTitleakatypes)) {
                        $existingTitleakatypes[] = $titleakatypeId;
                        $titleakatypeInserts[] = $titleakatypeInsert;
                    }
                    $titleakasTitleakatypesInserts[] = [
                        'titleaka_id' => $titleakaId,
                        'titleakatype_id' => $titleakatypeId
                    ];
                }


                $titleakaattributes = explode(",", $data[6]);
                foreach ($titleakaattributes as $titleakaattribute) {
                    if (empty($titleakaattribute) || $titleakaattribute == "\\N") continue;

                    $titleakaattributeId = preg_replace('/[[:^print:]]/', '', strtolower(str_replace(" ", "_", $titleakaattribute)));

                    $titleakaattributeInsert = [
                        'id' => $titleakaattributeId,
                        'display_name' => ucwords($titleakaattribute)
                    ];
                    if (!in_array($titleakaattributeId, $existingTitleakaattributes)) {
                        $existingTitleakaattributes[] = $titleakaattributeId;
                        $titleakaattributeInserts[] = $titleakaattributeInsert;
                    }
                    $titleakasTitleakaattributesInserts[] = [
                        'titleaka_id' => $titleakaId,
                        'titleakaattribute_id' => $titleakaattributeId
                    ];
                }

                if ($row % 10000 == 0) {
                    $this->db->insertMulti("titleakas", $titleakaInserts);
                    $titleakaInserts = [];


                    $this->db->insertMulti("titleakatypes", $titleakatypeInserts);
                    $titleakatypeInserts = [];

                    $this->db->insertMulti("titleakas_titleakatypes", $titleakasTitleakatypesInserts);
                    $titleakasTitleakatypesInserts = [];


                    $this->db->insertMulti("titleakaattributes", $titleakaattributeInserts);
                    $titleakaattributeInserts = [];

                    $this->db->insertMulti("titleakas_titleakaattributes", $titleakasTitleakaattributesInserts);
                    $titleakasTitleakaattributesInserts = [];
                }

                if ($row % 1000 == 0) {
                    $nowTime = microtime(true);
                    $elapsedTime = $nowTime - $startTime;
                    $titlesPerSecond = ($row / $elapsedTime);
                    $titlesLeft = $numberItems - $row;
                    $secondsLeft = intval($titlesLeft / $titlesPerSecond);
                    echo chr(27) . "[0G"; // replace current line
                    echo "\rInserted " . $row . " / " . $numberItems . " Title AKAs. Time left: " . $this->helper->formatTime($secondsLeft) . "                ";
                }


            }
            fclose($handle);

            // some left?
            if (!empty($titleakaInserts)) {
                $this->db->insertMulti("titleakas", $titleakaInserts);
            }

            if (!empty($titleakatypeInserts)) {
                $this->db->insertMulti("titleakatypes", $titleakatypeInserts);
            }

            if (!empty($titleakasTitleakatypesInserts)) {
                $this->db->insertMulti("titleakas_titleakatypes", $titleakasTitleakatypesInserts);
            }

            if (!empty($titleakaattributeInserts)) {
                $this->db->insertMulti("titleakaattributes", $titleakaattributeInserts);
            }

            if (!empty($titleakasTitleakaattributesInserts)) {
                $this->db->insertMulti("titleakas_titleakaattributes", $titleakasTitleakaattributesInserts);
            }


            echo "\nInserted " . $row . " Title AKAs\n";
        }

    }


    public function importEpisodes() {
        // tconst	    parentTconst	seasonNumber	episodeNumber
        // tt0020666	tt15180956	    1	            2


        $numberItems = $this->helper->getFileLines($this->tmpDir . "title.episode.tsv") - 1;
        echo "Inserting " . $numberItems . " Episodes... \n";

        $startTime = microtime(true);

        $this->db->execute("TRUNCATE TABLE episodes");


        $row = -1;
        $episodeInserts = [];
        if (($handle = fopen($this->tmpDir . "title.episode.tsv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $row++;
                if ($row == 0) continue;

                $episodeInserts[] = [
                    'id'                => $data[0],
                    'parent_id'         => $data[1],
                    'season_number'     => intval($data[2]) == 0 ? null : $data[2],
                    'episode_number'    => intval($data[3]) == 0 ? null : $data[3]
                ];


                if ($row % 10000 == 0) {
                    $this->db->insertMulti("episodes", $episodeInserts);
                    $episodeInserts = [];
                }

                if ($row % 1000 == 0) {
                    $nowTime = microtime(true);
                    $elapsedTime = $nowTime - $startTime;
                    $titlesPerSecond = ($row / $elapsedTime);
                    $titlesLeft = $numberItems - $row;
                    $secondsLeft = intval($titlesLeft / $titlesPerSecond);
                    echo chr(27) . "[0G"; // replace current line
                    echo "\rInserted " . $row . " / " . $numberItems . " Episodes. Time left: " . $this->helper->formatTime($secondsLeft) . "                ";
                }


            }
            fclose($handle);

            // some left?
            if (!empty($episodeInserts)) {
                $this->db->insertMulti("episodes", $episodeInserts);
            }

            echo "\nInserted " . $row . " Episodes\n";
        }

    }


    //public function importRatings() {
    public function importRatings() {
        // tconst	    averageRating	numVotes
        // tt0000001	5.7	            1882


        $numberItems = $this->helper->getFileLines($this->tmpDir . "title.ratings.tsv") - 1;
        echo "Adding " . $numberItems . " Ratings... \n";

        $startTime = microtime(true);


        $row = -1;
        if (($handle = fopen($this->tmpDir . "title.ratings.tsv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $row++;
                if ($row == 0) continue;

                $this->db->update('titles', [
                    'average_rating'    => floatval($data[1]),
                    'num_votes'         => intval($data[2])
                ],
                [
                    'id'    => $data[0]
                ]);


                if ($row % 1000 == 0) {
                    $nowTime = microtime(true);
                    $elapsedTime = $nowTime - $startTime;
                    $titlesPerSecond = ($row / $elapsedTime);
                    $titlesLeft = $numberItems - $row;
                    $secondsLeft = intval($titlesLeft / $titlesPerSecond);
                    echo chr(27) . "[0G"; // replace current line
                    echo "\rInserted " . $row . " / " . $numberItems . " Episodes. Time left: " . $this->helper->formatTime($secondsLeft) . "                ";
                }


            }
            fclose($handle);

            echo "\nInserted " . $row . " Episodes\n";
        }

    }

    public function importPrincipals() {
        // tconst	    ordering	nconst	    category	job	            characters
        // tt0796739	2	        nm0494784	actor	    \N	            ["Giovani Pietro Carafa","Pedro de Arbues","Pope Gregry IX"]
        // tt3020244	7	        nm1039199	writer	    co-adaptation	\N

        $numberItems = $this->helper->getFileLines($this->tmpDir . "title.principals.tsv") - 1;
        echo "Inserting " . $numberItems . " Principals... \n";

        $startTime = microtime(true);

        $this->db->execute("TRUNCATE TABLE principals");

        $this->db->execute("TRUNCATE TABLE categories");
        $this->db->execute("TRUNCATE TABLE jobs");

        $this->db->execute("TRUNCATE TABLE principals_characters");


        $row = -1;
        $principalInserts = [];

        $categoryInserts = [];
        $existingCategories = [];

        $jobInserts = [];
        $existingJobs = [];

        $principalsCharactersInserts = [];

        if (($handle = fopen($this->tmpDir . "title.principals.tsv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $row++;
                if ($row == 0) continue;

                $principalId    = $row;
                $categoryId     = $data[3] == "\\N" ? null : preg_replace('/[[:^print:]]/', '', strtolower(str_replace(" ", "_", $data[3])));
                $jobId          = $data[4] == "\\N" ? null : preg_replace('/[[:^print:]]/', '', strtolower(str_replace(" ", "_", $data[4])));


                if (strlen($categoryId) > 100 || strlen($jobId) > 100) continue;

                $existingPrincipalsCharacters = [];

                $principalInserts[] = [
                    'id'            => $principalId,
                    'title_id'      => $data[0],
                    'name_id'       => $data[2],
                    'ordering'      => $data[1],
                    'category_id'   => $categoryId,
                    'job_id'        => $jobId,
                ];

                if ($categoryId && !in_array($categoryId, $existingCategories)) {
                    $categoryInserts[] = [
                        'code' => $categoryId,
                        'display_name' => ucwords(str_replace("_", " ", $categoryId))
                    ];
                    $existingCategories[] = $categoryId;
                }

                if ($jobId && !in_array($jobId, $existingJobs)) {
                    $jobInserts[] = [
                        'code' => $jobId,
                        'display_name' => ucwords(str_replace("_", " ", $jobId))
                    ];
                    $existingJobs[] = $jobId;
                }

                if ($data[5] !== "\\N") {
                    $possibleNewCharacters = json_decode($data[5], true);
                    foreach ($possibleNewCharacters as $possibleNewCharacter) {
                        $characterId = substr(preg_replace('/[[:^print:]]/', '', strtolower(str_replace(" ", "_", $possibleNewCharacter))), 0, 50);
                        $principalsCharactersId = $principalId . '_' . $characterId;

                        if (!in_array($principalsCharactersId, $existingPrincipalsCharacters)) {
                            $principalsCharactersInserts[] = [
                                'principal_id' => $principalId,
                                'character_display_name' => $possibleNewCharacter
                            ];
                            $existingPrincipalsCharacters[] = $principalsCharactersId;
                        }

                    }
                }


                if ($row % 10000 == 0) {
                    $this->db->insertMulti("principals", $principalInserts);
                    $principalInserts = [];

                    $this->db->insertMulti("categories", $categoryInserts);
                    $categoryInserts = [];

                    $this->db->insertMulti("jobs", $jobInserts);
                    $jobInserts = [];

                    $this->db->insertMulti("principals_characters", $principalsCharactersInserts);
                    $principalsCharactersInserts = [];

                }

                if ($row % 1000 == 0) {
                    $nowTime = microtime(true);
                    $elapsedTime = $nowTime - $startTime;
                    $titlesPerSecond = ($row / $elapsedTime);
                    $titlesLeft = $numberItems - $row;
                    $secondsLeft = intval($titlesLeft / $titlesPerSecond);
                    echo chr(27) . "[0G"; // replace current line
                    echo "\rInserted " . $row . " / " . $numberItems . " Principals. Time left: " . $this->helper->formatTime($secondsLeft) . "                ";
                }


            }
            fclose($handle);

            // some left?
            if (!empty($principalInserts)) {
                $this->db->insertMulti("principals", $principalInserts);
            }

            if (!empty($categoryInserts)) {
                $this->db->insertMulti("categories", $categoryInserts);
            }

            if (!empty($jobInserts)) {
                $this->db->insertMulti("jobs", $jobInserts);
            }

            if (!empty($principalsCharactersInserts)) {
                $this->db->insertMulti("principals_characters", $principalsCharactersInserts);
            }

            echo "\nInserted " . $row . " Principals\n";
        }


    }






}