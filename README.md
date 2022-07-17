# rmdb 
Imports IMDb datasets into relational database for easy querying

## Usage:
- Create MySQL database, import `config/schema.sql`
- Enter DB details in `config/config.json`
- `php rmdb/public/index.php [command]`

## Commands:
    help			- Print this help
    download		- Download zipped TSV files from datasets.imdbws.com. Needs 1.5GB+ of storage  
    extract			- Extract zipped TSV files. Needs 7GB+ of storage  
    import		        - Import everything, replace existing data. Runs all the below commands:  
    import-names		- Import cast & crew  
    import-titles		- Import movies, TV episodes, short films, ...  
    import-titles-akas	- Import foreign names for titles  
    import-episodes		- Import episode <> show/series relation  
    import-ratings		- Import average ratings for titles  
    import-principals	- Import directory, writers. Should be redundant if you import names

## Schema

See `config/schema.sql` which pretty much maps to the [IMDb dataset schema](https://www.imdb.com/interfaces/). Overview:

| Table        | Description |
|--------------|-------------|
| titles | All the movies, short films, episodes |
| genres       | All genres, like *Action*, *Sci-Fi*, *Comedy* |
| titles_genres | Maps titles to genres |
| names        | All people with birth and death year |
| categories   | Main types of jobs like *Actor*, *Actress*, *Composer* |
| jobs         | Freeform job titles like *Act Three written By*, *Live Show Editor*
| principals | Maps names to titles, category and job, ordered by *ordering* |
| principals_characters | Maps principals to a character name |
| professions | Main professions like *Producer*, *Stunts*, *casting Director* |
| names_primaryprofessions | Maps names to professions |
| names_knownfortitles | Maps people to titles they are most known for |
| titleakas | Foreign language / additional titles |
| titleakaattributes | Type of additional titles like *Fake Working Title*, *Berlin Film festival Title* |
| titleakatypes | Smaller list of title types, similar to titleakaattributes, like *Alternative*, *Working*, *IMDb Display* |
| titleakas_titleakaattributes | Maps titleakaattributes to titleakas |
| titleakas_titleakatypes | Maps titleakas to titleakatypes |
| episodes     | Connects episode titles to their shows |


## Examples

### Which characters did *Mark Ruffalo* Play in *Thor: Ragnarok*?

    SELECT character_display_name FROM principals_characters 
    WHERE principal_id = (
        SELECT id FROM principals 
        WHERE title_id =
            (SELECT id FROM titles WHERE title_type = "movie" AND primary_title = "Thor: Ragnarok")
        AND name_id =
            (SELECT id FROM names WHERE primary_name = "Mark Ruffalo")
    )

| character_display_name |
|------|
| Bruce Banner |
| Hulk |

### What's the genre distribution per year?

    SELECT start_year, COUNT(*) AS count FROM titles
    LEFT JOIN titles_genres AS tg1 ON tg1.title_id = titles.id
    WHERE tg1.genre_id = "drama" -- repeat for a few genres
    
    -- Exclude tv episodes etc.
    AND title_type = "movie"
    
    AND start_year < YEAR(CURRENT_DATE())
    
    GROUP BY start_year
    ORDER BY start_year ASC;

Although after 2000 the amount of movies made, or listed in IMDb, rises rapidly, so the data might need some normalization. Or limit to before 2000 like here:

<img width="829" alt="western-scifi-horror" src="https://user-images.githubusercontent.com/26400/179400898-0013149b-04dd-49f3-9cc8-3e8e0ca58cb4.png">

### The 50 highest rated horror comedies

    SELECT CONCAT("[", primary_title, "](https://www.imdb.com/title/", id, ")") AS primary_title, start_year, average_rating, num_votes FROM titles
    LEFT JOIN titles_genres AS tg1 ON tg1.title_id = titles.id
    LEFT JOIN titles_genres AS tg2 ON tg2.title_id = titles.id
    WHERE tg1.genre_id = "horror"
    AND tg2.genre_id = "comedy"
    
    -- Exclude TV episodes etc.
    AND title_type = "movie"
    
    -- Exclude little voted on movies where average_rating is often too high. Higher num_votes = more popular
    AND num_votes > 20000
    
    ORDER BY average_rating DESC, num_votes DESC
    
    LIMIT 50;

| primary_title | start_year | average_rating | num_votes |
|---------------|------------|----------------|-----------|
| [Shaun of the Dead](https://www.imdb.com/title/tt0365748) | 2004 | 7.9 | 547079 |
| [Evil Dead II](https://www.imdb.com/title/tt0092991) | 1987 | 7.7 | 161069 |
| [Zombieland](https://www.imdb.com/title/tt1156398) | 2009 | 7.6 | 562503 |
| [What We Do in the Shadows](https://www.imdb.com/title/tt3416742) | 2014 | 7.6 | 178609 |
| [One Cut of the Dead](https://www.imdb.com/title/tt7914416) | 2017 | 7.6 | 23294 |
| [Tucker and Dale vs Evil](https://www.imdb.com/title/tt1465522) | 2010 | 7.5 | 176660 |
| [An American Werewolf in London](https://www.imdb.com/title/tt0082010) | 1981 | 7.5 | 104122 |
| [Dead Alive](https://www.imdb.com/title/tt0103873) | 1992 | 7.5 | 96468 |
| [Stree](https://www.imdb.com/title/tt8108202) | 2018 | 7.5 | 33219 |
| [Army of Darkness](https://www.imdb.com/title/tt0106308) | 1992 | 7.4 | 174895 |
| [The Rocky Horror Picture Show](https://www.imdb.com/title/tt0073629) | 1975 | 7.4 | 148043 |
| [Bhool Bhulaiyaa](https://www.imdb.com/title/tt0995031) | 2007 | 7.4 | 26087 |
| [Gremlins](https://www.imdb.com/title/tt0087363) | 1984 | 7.3 | 219049 |
| [The Return of the Living Dead](https://www.imdb.com/title/tt0089907) | 1985 | 7.3 | 60200 |
| [House](https://www.imdb.com/title/tt0076162) | 1977 | 7.3 | 26640 |
| [The Lost Boys](https://www.imdb.com/title/tt0093437) | 1987 | 7.2 | 138911 |
| [Re-Animator](https://www.imdb.com/title/tt0089885) | 1985 | 7.2 | 63177 |
| [Bhoot Police](https://www.imdb.com/title/tt10083640) | 2021 | 7.2 | 26129 |
| [Tremors](https://www.imdb.com/title/tt0100814) | 1990 | 7.1 | 135475 |
| [The Frighteners](https://www.imdb.com/title/tt0116365) | 1996 | 7.1 | 87546 |
| [Little Shop of Horrors](https://www.imdb.com/title/tt0091419) | 1986 | 7.1 | 74525 |
| [The Fearless Vampire Killers](https://www.imdb.com/title/tt0061655) | 1967 | 7.1 | 31521 |
| [Dellamorte Dellamore](https://www.imdb.com/title/tt0109592) | 1994 | 7.1 | 21375 |
| [Bubba Ho-Tep](https://www.imdb.com/title/tt0281686) | 2002 | 6.9 | 48378 |
| [Warm Bodies](https://www.imdb.com/title/tt1588173) | 2013 | 6.8 | 229755 |
| [Ready or Not](https://www.imdb.com/title/tt7798634) | 2019 | 6.8 | 141645 |
| [Odd Thomas](https://www.imdb.com/title/tt1767354) | 2013 | 6.8 | 52720 |
| [Creepshow](https://www.imdb.com/title/tt0083767) | 1982 | 6.8 | 46916 |
| [Zombieland: Double Tap](https://www.imdb.com/title/tt1560220) | 2019 | 6.7 | 173135 |
| [Trick 'r Treat](https://www.imdb.com/title/tt0862856) | 2007 | 6.7 | 90509 |
| [Fresh](https://www.imdb.com/title/tt13403046) | 2022 | 6.7 | 38519 |
| [Housebound](https://www.imdb.com/title/tt3504048) | 2014 | 6.7 | 34328 |
| [Fido](https://www.imdb.com/title/tt0457572) | 2006 | 6.7 | 29187 |
| [Behind the Mask: The Rise of Leslie Vernon](https://www.imdb.com/title/tt0437857) | 2006 | 6.7 | 23800 |
| [Night of the Creeps](https://www.imdb.com/title/tt0091630) | 1986 | 6.7 | 23355 |
| [Happy Death Day](https://www.imdb.com/title/tt5308322) | 2017 | 6.6 | 136327 |
| [Death Becomes Her](https://www.imdb.com/title/tt0104070) | 1992 | 6.6 | 115999 |
| [May](https://www.imdb.com/title/tt0303361) | 2002 | 6.6 | 36838 |
| [Slither](https://www.imdb.com/title/tt0439815) | 2006 | 6.5 | 81269 |
| [Arachnophobia](https://www.imdb.com/title/tt0099052) | 1990 | 6.5 | 69144 |
| [The Witches of Eastwick](https://www.imdb.com/title/tt0094332) | 1987 | 6.5 | 69079 |
| [Bad Taste](https://www.imdb.com/title/tt0092610) | 1987 | 6.5 | 46735 |
| [Better Watch Out](https://www.imdb.com/title/tt4443658) | 2016 | 6.5 | 37711 |
| [Fright Night](https://www.imdb.com/title/tt1438176) | 2011 | 6.4 | 105215 |
| [Gremlins 2: The New Batch](https://www.imdb.com/title/tt0099700) | 1990 | 6.4 | 102085 |
| [Severance](https://www.imdb.com/title/tt0464196) | 2006 | 6.4 | 38791 |
| [The People Under the Stairs](https://www.imdb.com/title/tt0105121) | 1991 | 6.4 | 34750 |
| [Mayhem](https://www.imdb.com/title/tt4348012) | 2017 | 6.4 | 20928 |
| [The Babysitter](https://www.imdb.com/title/tt4225622) | 2017 | 6.3 | 89374 |
| [Dead Snow](https://www.imdb.com/title/tt1278340) | 2009 | 6.3 | 66839 |

## Popular Sci-Fi movies of the 70s

Note: No Star Wars, it only has Action, Adventure and Fantasy genres.

    SELECT CONCAT("[", primary_title, "](https://www.imdb.com/title/", id, ")") AS primary_title, start_year, average_rating, num_votes FROM titles
    LEFT JOIN titles_genres AS tg1 ON tg1.title_id = titles.id
    WHERE tg1.genre_id = "sci-fi"
    
    -- Exclude TV episodes etc.
    AND title_type = "movie"
    
    -- Exclude little voted on movies where average_rating is often too high. Higher num_votes = more popular
    AND num_votes > 10000
    
    AND start_year >= 1970
    AND start_year <= 1979
    
    ORDER BY average_rating DESC, num_votes DESC;

| primary_title | start_year | average_rating | num_votes |
|---------------|------------|----------------|-----------|
| [Alien](https://www.imdb.com/title/tt0078748) | 1979 | 8.5 | 855270 |
| [A Clockwork Orange](https://www.imdb.com/title/tt0066921) | 1971 | 8.3 | 811076 |
| [Ivan Vasilyevich Changes His Profession](https://www.imdb.com/title/tt0070233) | 1973 | 8.2 | 16458 |
| [Stalker](https://www.imdb.com/title/tt0079944) | 1979 | 8.1 | 131654 |
| [Solaris](https://www.imdb.com/title/tt0069293) | 1972 | 8 | 89827 |
| [Fantastic Planet](https://www.imdb.com/title/tt0070544) | 1973 | 7.7 | 30480 |
| [Close Encounters of the Third Kind](https://www.imdb.com/title/tt0075860) | 1977 | 7.6 | 198472 |
| [Superman](https://www.imdb.com/title/tt0078346) | 1978 | 7.4 | 172371 |
| [Invasion of the Body Snatchers](https://www.imdb.com/title/tt0077745) | 1978 | 7.4 | 59190 |
| [The Andromeda Strain](https://www.imdb.com/title/tt0066769) | 1971 | 7.2 | 36803 |
| [Sleeper](https://www.imdb.com/title/tt0070707) | 1973 | 7.1 | 42854 |
| [Time After Time](https://www.imdb.com/title/tt0080025) | 1979 | 7.1 | 18387 |
| [Soylent Green](https://www.imdb.com/title/tt0070723) | 1973 | 7 | 64000 |
| [The Boys from Brazil](https://www.imdb.com/title/tt0077269) | 1978 | 7 | 27798 |
| [Westworld](https://www.imdb.com/title/tt0070909) | 1973 | 6.9 | 57182 |
| [The Stepford Wives](https://www.imdb.com/title/tt0073747) | 1975 | 6.9 | 17631 |
| [Mad Max](https://www.imdb.com/title/tt0079501) | 1979 | 6.8 | 204229 |
| [Logan's Run](https://www.imdb.com/title/tt0074812) | 1976 | 6.8 | 56057 |
| [The Brood](https://www.imdb.com/title/tt0078908) | 1979 | 6.8 | 29614 |
| [Slaughterhouse-Five](https://www.imdb.com/title/tt0069280) | 1972 | 6.8 | 13000 |
| [THX 1138](https://www.imdb.com/title/tt0066434) | 1971 | 6.7 | 51240 |
| [Phantasm](https://www.imdb.com/title/tt0079714) | 1979 | 6.6 | 36464 |
| [Silent Running](https://www.imdb.com/title/tt0067756) | 1972 | 6.6 | 28965 |
| [The Man Who Fell to Earth](https://www.imdb.com/title/tt0074851) | 1976 | 6.6 | 26180 |
| [Rollerball](https://www.imdb.com/title/tt0073631) | 1975 | 6.6 | 24752 |
| [Horror Express](https://www.imdb.com/title/tt0068713) | 1972 | 6.5 | 10758 |
| [Star Trek: The Motion Picture](https://www.imdb.com/title/tt0079945) | 1979 | 6.4 | 89032 |
| [The Omega Man](https://www.imdb.com/title/tt0067525) | 1971 | 6.4 | 31325 |
| [Shivers](https://www.imdb.com/title/tt0073705) | 1975 | 6.4 | 20837 |
| [A Boy and His Dog](https://www.imdb.com/title/tt0072730) | 1975 | 6.4 | 17756 |
| [Escape from the Planet of the Apes](https://www.imdb.com/title/tt0067065) | 1971 | 6.3 | 35051 |
| [Rabid](https://www.imdb.com/title/tt0076590) | 1977 | 6.3 | 18535 |
| [The Fury](https://www.imdb.com/title/tt0077588) | 1978 | 6.3 | 15211 |
| [Moonraker](https://www.imdb.com/title/tt0079574) | 1979 | 6.2 | 99575 |
| [Death Race 2000](https://www.imdb.com/title/tt0072856) | 1975 | 6.2 | 27669 |
| [Dark Star](https://www.imdb.com/title/tt0069945) | 1974 | 6.2 | 24315 |
| [Conquest of the Planet of the Apes](https://www.imdb.com/title/tt0068408) | 1972 | 6.1 | 32523 |
| [The Crazies](https://www.imdb.com/title/tt0069895) | 1973 | 6.1 | 13134 |
| [Beneath the Planet of the Apes](https://www.imdb.com/title/tt0065462) | 1970 | 6 | 46579 |
| [The Black Hole](https://www.imdb.com/title/tt0078869) | 1979 | 5.9 | 25455 |
| [Piranha](https://www.imdb.com/title/tt0078087) | 1978 | 5.9 | 21507 |
| [Zardoz](https://www.imdb.com/title/tt0070948) | 1974 | 5.8 | 22467 |
| [Futureworld](https://www.imdb.com/title/tt0074559) | 1976 | 5.7 | 10713 |
| [Battle for the Planet of the Apes](https://www.imdb.com/title/tt0069768) | 1973 | 5.4 | 30854 |
