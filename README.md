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

### The 50 highest rated horror comedies

    SELECT CONCAT("[", primary_title, "](https://www.imdb.com/title/", id, ")") AS primary_title, start_year, average_rating, num_votes FROM titles
    LEFT JOIN titles_genres AS tg1 ON tg1.title_id = titles.id
    LEFT JOIN titles_genres AS tg2 ON tg2.title_id = titles.id
    WHERE tg1.genre_id = "horror"
    AND tg2.genre_id = "comedy"
    
    -- Exclude tv episodes etc.
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