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

See `config/schema.sql` which pretty much maps to the [IMDb dataset schema](https://www.imdb.com/interfaces/):

| Table        | Description |
|--------------|-------------|
| titles | All the movies, short films, episodes |
| genres       | All genres. Action, Sci-Fi, Comedy, ... |
| titles_genres | Maps titles to genres |
| names        | All people with birth and death year |
| categories   | Main types of jobs. *Actor*, *Actress*, *Composer*, ... |
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

