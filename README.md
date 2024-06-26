# Random Words API

PHP API for Get Random Words with definition and pronunciation.  

## Built Using ✍️

- Data scraped from Different External Sources and Bundled with `csv` File
- Using PHP, PDO and MYSQL > convert `csv` file to sql data > `convert.php` : convert and store `csv` data into MYSQL database

```sql
CREATE TABLE words (
    id INT NOT NULL AUTO_INCREMENT,
    word VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL,
    definition TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    pronunciation VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (word)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- `env` Example data

```sh
DBHOST=localhost
DBNAME=xxxxxxxx
DBUSER=xxxxxxxxx
DBPASSWORD=xxxxxxxxxxxxxxxxxx
```

- `word.php` Get random Words data in Random Order from MYSQL Database
- `random.php` Powered by redis cache Store data in redis and Pick random data from redis memory if data not avilable it pick from MYSQL database and stored into redis memory
- `clean.php` - Clear Redis Cache
- `/telegram/bot.php` - Telegram Bot for Random Words with Pushbullet Alerts
- `'fetch.php`' - Get Random Words data from Main Source  

## Data

- Check `data` Folder for CSV File and SQL Data  

## API Credits ☑

Get Random Words (with pronunciation) for Free using this API - <https://github.com/mcnaveen/Random-Words-API>  

## Disclaimer ⚠

- We don't own any data or word. All belongs to the Respective owner of Website.  
- Using it for educational purpose only.  

## LICENSE ⚛

MIT
