# Levenshtein distance function for Doctrine and MySQL

A tiny Doctrine extension for the [Levenshtein distance](http://en.wikipedia.org/wiki/Levenshtein_distance) algorithm to be used directly in DQL. The `LEVENSHTEIN(s1, s2)` function returns the number of add, replace and delete operations needed to transform one string into another. The `LEVENSHTEIN_RATIO(s1, s2)` function returns the similarity of two strings in percent (`0 <= x <= 100`). They work in much the same way as the PHP built-in functions: [levenshtein()](http://us2.php.net/manual/en/function.levenshtein.php), [similar_text()](http://us2.php.net/manual/en/function.similar-text.php).

Just for reference, there are plenty of alternative/additional algorithms to compute phonetic similarity. This is by all means not a complete list:

* [Damerau-Levenhstein distance](http://en.wikipedia.org/wiki/Damerau%E2%80%93Levenshtein_distance)
* [Jaro-Winkler distance](http://en.wikipedia.org/wiki/Jaro%E2%80%93Winkler_distance)
* [Soundex](http://en.wikipedia.org/wiki/Jaro%E2%80%93Winkler_distance)
* [Metaphone](http://en.wikipedia.org/wiki/Metaphone)

## Define MySQL functions

* Sources: [1](http://stackoverflow.com/questions/4671378/levenshtein-mysql-php
), [2](http://www.artfulsoftware.com/infotree/queries.php#552)
* Copyright: Jason Rust

Execute the following commands to define the `LEVENSHTEIN` and `LEVENSHTEIN_RATIO` functions in the database. This needs to be done before you can use the functions in any query.

```sql
DELIMITER ;;;
CREATE DEFINER=`root`@`` FUNCTION `LEVENSHTEIN`(s1 VARCHAR(255), s2 VARCHAR(255)) RETURNS int(11) DETERMINISTIC
BEGIN
    DECLARE s1_len, s2_len, i, j, c, c_temp, cost INT;
    DECLARE s1_char CHAR;
    DECLARE cv0, cv1 VARBINARY(256);
    SET s1_len = CHAR_LENGTH(s1), s2_len = CHAR_LENGTH(s2), cv1 = 0x00, j = 1, i = 1, c = 0;
    IF s1 = s2 THEN
        RETURN 0;
    ELSEIF s1_len = 0 THEN
        RETURN s2_len;
    ELSEIF s2_len = 0 THEN
        RETURN s1_len;
    ELSE
        WHILE j <= s2_len DO
            SET cv1 = CONCAT(cv1, UNHEX(HEX(j))), j = j + 1;
        END WHILE;
        WHILE i <= s1_len DO
            SET s1_char = SUBSTRING(s1, i, 1), c = i, cv0 = UNHEX(HEX(i)), j = 1;
            WHILE j <= s2_len DO
                SET c = c + 1;
                IF s1_char = SUBSTRING(s2, j, 1) THEN SET cost = 0; ELSE SET cost = 1; END IF;
                SET c_temp = CONV(HEX(SUBSTRING(cv1, j, 1)), 16, 10) + cost;
                IF c > c_temp THEN SET c = c_temp; END IF;
                SET c_temp = CONV(HEX(SUBSTRING(cv1, j+1, 1)), 16, 10) + 1;
                IF c > c_temp THEN SET c = c_temp; END IF;
                SET cv0 = CONCAT(cv0, UNHEX(HEX(c))), j = j + 1;
            END WHILE;
            SET cv1 = cv0, i = i + 1;
        END WHILE;
    END IF;
    RETURN c;
END;;;
```

```sql
DELIMITER ;;;
CREATE DEFINER=`root`@`` FUNCTION `LEVENSHTEIN_RATIO`(s1 VARCHAR(255), s2 VARCHAR(255)) RETURNS int(11) DETERMINISTIC
BEGIN
    DECLARE s1_len, s2_len, max_len INT;
    SET s1_len = LENGTH(s1), s2_len = LENGTH(s2);
    IF s1_len > s2_len THEN SET max_len = s1_len; ELSE SET max_len = s2_len; END IF;
    RETURN ROUND((1 - LEVENSHTEIN(s1, s2) / max_len) * 100);
END;;;
```

## Symfony2 configuration

```yaml
# app/config/config.yml

doctrine:
  orm:
    entity_managers:
      default:
        dql:
          numeric_functions:
            levenshtein: Fza\MysqlDoctrineLevenshteinFunction\DQL\LevenshteinFunction
            levenshtein_ratio: Fza\MysqlDoctrineLevenshteinFunction\DQL\LevenshteinRatioFunction
```

## Query example

```php
$em = $this->getEntityManager();
$query = $em->createQuery('SELECT u FROM User u WHERE LEVENSHTEIN_RATIO(u.name, :nameQuery) > :minSimilarity');
$query->setParameter('nameQuery', 'michael');
$query->setParameter('minSimilarity', 50)
$matchingUsers = $query->getResult();
```

## License

Copyright (c) 2015 [Felix Zandanel](http://felix.zandanel.me/)  
Licensed under the MIT license.

See LICENSE for more info.
