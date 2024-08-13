<?php

/**
 * Quick & Easy: PHP -> MySQL - Library for PHP and MySQL Common Database Operations
 * 
 * This library provides a set of functions to handle common database operations
 * including insert, update, delete, join, and select queries. It includes sanitization 
 * and purification methods to ensure data integrity and security.
 * 
 * NOTE: This library requires the ezyang/htmlpurifier library.
 * 
 * TO USE: This library requires vars with the signatures $conn and $purifier,
 * where $conn is your MySQL connection object and $purifier is your HTMLpurifier object.
 * $conn and $purifier must be declared before calling a function from this library, as the functions
 * access the global scope to use them (To prevent having to pass in $conn to all your functions). I reccomend
 * to initialize them in in an includes file before you use this library. 
 * 
 * EX:
 *      includes.php:
 *          <?php 
 *              $docpath = //Your program's resources
 *              require_once $docpath . "/html_purifier/library/HTMLPurifier.auto.php";
 *              $config = HTMLPurifier_Config::createDefault();
 *              $purifier = new HTMLPurifier($config);
 *              require($docpath . "/connect.php");
 *              $conn = get_connection();
 *          ?>
 *      anything_else.php:
 *          <?php
 *              include_once "includes.php";
 *              /// Ready to use!
 *              $items = select_where("cars", array("make"=>"ford"));
 *              etc...
 *          ?>
 * 
 * @requires   ezyang/htmlpurifier
 * @author     Maxwell Rodgers
 * @version    1.0
 * @updated    May 17th, 2024
 */

 /*********************************   RESERVED WORDS  ***************************************/
 /******************************************************************************************/
 $mysql_reserved_words = array(
    "ADD", "ALL", "ALTER", "ANALYZE", "AND", "AS", "ASC", "ASENSITIVE", "BEFORE", "BETWEEN", 
    "BIGINT", "BINARY", "BLOB", "BOTH", "BY", "CALL", "CASCADE", "CASE", "CHANGE", "CHAR", 
    "CHARACTER", "CHECK", "COLLATE", "COLUMN", "CONDITION", "CONSTRAINT", "CONTINUE", 
    "CONVERT", "CREATE", "CROSS", "CUBE", "CUME_DIST", "CURRENT_DATE", "CURRENT_TIME", 
    "CURRENT_TIMESTAMP", "CURRENT_USER", "CURSOR", "DATABASE", "DATABASES", "DAY_HOUR", 
    "DAY_MICROSECOND", "DAY_MINUTE", "DAY_SECOND", "DEC", "DECIMAL", "DECLARE", "DEFAULT", 
    "DELAYED", "DELETE", "DENSE_RANK", "DESC", "DESCRIBE", "DETERMINISTIC", "DISTINCT", 
    "DISTINCTROW", "DIV", "DOUBLE", "DROP", "DUAL", "EACH", "ELSE", "ELSEIF", "EMPTY", 
    "ENCLOSED", "ESCAPED", "EXCEPT", "EXISTS", "EXIT", "EXPLAIN", "FALSE", "FETCH", "FIRST_VALUE", 
    "FLOAT", "FLOAT4", "FLOAT8", "FOR", "FORCE", "FOREIGN", "FROM", "FULLTEXT", "FUNCTION", 
    "GENERATED", "GET", "GRANT", "GROUP", "GROUPING", "GROUPS", "HAVING", "HIGH_PRIORITY", 
    "HOUR_MICROSECOND", "HOUR_MINUTE", "HOUR_SECOND", "IF", "IGNORE", "IN", "INDEX", "INFILE", 
    "INNER", "INOUT", "INSENSITIVE", "INSERT", "INT", "INT1", "INT2", "INT3", "INT4", "INT8", 
    "INTEGER", "INTERVAL", "INTO", "IO_AFTER_GTIDS", "IO_BEFORE_GTIDS", "IS", "ITERATE", 
    "JOIN", "JSON_TABLE", "KEY", "KEYS", "KILL", "LAG", "LAST_VALUE", "LATERAL", "LEAD", 
    "LEADING", "LEAVE", "LEFT", "LIKE", "LIMIT", "LINEAR", "LINES", "LOAD", "LOCALTIME", 
    "LOCALTIMESTAMP", "LOCK", "LONG", "LONGBLOB", "LONGTEXT", "LOOP", "LOW_PRIORITY", "MASTER_BIND", 
    "MASTER_SSL_VERIFY_SERVER_CERT", "MATCH", "MAXVALUE", "MEDIUMBLOB", "MEDIUMINT", "MEDIUMTEXT", 
    "MIDDLEINT", "MINUTE_MICROSECOND", "MINUTE_SECOND", "MOD", "MODIFIES", "NATURAL", "NOT", 
    "NO_WRITE_TO_BINLOG", "NTH_VALUE", "NTILE", "NULL", "NUMERIC", "OF", "ON", "OPTIMIZE", 
    "OPTIMIZER_COSTS", "OPTION", "OPTIONALLY", "OR", "ORDER", "OUT", "OUTER", "OUTFILE", 
    "OVER", "PARTITION", "PERCENT_RANK", "PRECISION", "PRIMARY", "PROCEDURE", "PURGE", 
    "RANGE", "RANK", "READ", "READS", "READ_WRITE", "REAL", "RECURSIVE", "REFRENCE", "REGEXP", 
    "RELEASE", "RENAME", "REPEAT", "REPLACE", "REQUIRE", "RESIGNAL", "RESTRICT", "RETURN", 
    "REVOKE", "RIGHT", "RLIKE", "ROW", "ROWS", "ROW_NUMBER", "SCHEMA", "SCHEMAS", "SECOND_MICROSECOND", 
    "SELECT", "SENSITIVE", "SEPARATOR", "SET", "SHOW", "SIGNAL", "SMALLINT", "SPATIAL", 
    "SPECIFIC", "SQL", "SQLEXCEPTION", "SQLSTATE", "SQLWARNING", "SQL_BIG_RESULT", "SQL_CALC_FOUND_ROWS", 
    "SQL_SMALL_RESULT", "SSL", "STARTING", "STORED", "STRAIGHT_JOIN", "SYSTEM", "TABLE", 
    "TERMINATED", "THEN", "TINYBLOB", "TINYINT", "TINYTEXT", "TO", "TRAILING", "TRIGGER", 
    "TRUE", "UNDO", "UNION", "UNIQUE", "UNLOCK", "UNSIGNED", "UPDATE", "USAGE", "USE", "USING", 
    "UTC_DATE", "UTC_TIME", "UTC_TIMESTAMP", "VALUES", "VARBINARY", "VARCHAR", "VARCHARACTER", 
    "VARYING", "VIRTUAL", "WHEN", "WHERE", "WHILE", "WINDOW", "WITH", "WRITE", "XOR", 
    "YEAR_MONTH", "ZEROFILL"
);

 /*********************************   DATA CLEANERS  ***************************************/
 /******************************************************************************************/

/**
 * Prepares data for display in a select field by escaping HTML entities and replacing certain ampersands.
 * 
 * @param string $myvar The input string to be sanitized.
 * @return string The sanitized string.
 */
function prep_select_display_data($myvar)
{
    $tempvar = htmlspecialchars($myvar, ENT_QUOTES);
    $tempvar = preg_replace("/&amp;/", "&", $tempvar);
    return $tempvar;
}

/**
 * Prepares data for inserting into the database by purifying the input and escaping special characters.
 * 
 * @param object $conn The database connection object.
 * @param object $purifier The HTML purifier object.
 * @param string $var_name The input string to be sanitized.
 * @return string The sanitized string.
 */
function prep_insert_field_data_v2($conn, $purifier, $var_name)
{
    $temp_var = $purifier->purify($var_name);
    $temp_var = mysqli_real_escape_string($conn, $temp_var);

    return $temp_var;
}

/******************************************************************************************/
/**********************************  MySQL Functions  *************************************/

/**
 * Inserts data into a specified table.
 * 
 * Example usage:
 *      insert("cars", array("make"=>"ford","model"=>"focus","year"=>"2024"));
 * Returns:
 *      "INSERT INTO `cars` (`make`,`model`,`year`) VALUES (`ford`,`focus`,`2024`)"
 * 
 * @param string $table The table name.
 * @param array $insert_arr The associative array of key-value pairs to insert.
 * @return string The query string for debug purposes.
 */
function insert($table, $insert_arr)
{
    global $conn, $purifier;

    //Cleaning
    foreach($insert_arr as $key => $value)
    {
        $insert_arr[$key] = prep_insert_field_data_v2($conn, $purifier, $value);
    }

    //Creating the query
    $query_string = "INSERT INTO " . "`" .$table . "`";
    $id_string = "";
    $value_string = "";

    foreach($insert_arr as $key => $value)
    {
        $id_string .= "`" . $key . "`,";
        $value_string .= "'" . $value . "',";
    }

    //Removing unneeded commas
    $id_string = substr_replace($id_string, '', -1);
    $value_string = substr_replace($value_string, '', -1);

    //Finish formatting
    $id_string = "(" . $id_string . ")";
    $value_string = "VALUES (" . $value_string . ")";
    $query_string = $query_string . " " . $id_string . " " . $value_string;

    mysqli_query($conn, $query_string);
    return $query_string;
}

/**
 * Gets the id of the last inserted item
 * 
 */
function get_last_insert_id()
{
    global $conn;

    $query_string = "SELECT LAST_INSERT_ID()";
    $result = mysqli_query($conn, $query_string);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (int) $row['LAST_INSERT_ID()'];
    } else {
        return null;
    }
}

/**
 * Updates data in a specified table.
 * 
 * Example usage:
 *      update("cars", array("model"=>"fusion"), array("model"=>"focus"));
 * Returns:
 *     "UPDATE `cars` SET `model`='fusion' WHERE `make`='focus'"
 * 
 * @param string $table The table name.
 * @param array $insert_arr The associative array of key-value pairs to update.
 * @param array $where_arr The associative array of conditions for the update.
 * @return string The query string for debug purposes.
 */
function update($table, $insert_arr, $where_arr)
{
    global $conn, $purifier;

    //Cleaning
    foreach($insert_arr as $key => $value)
    {
        $insert_arr[$key] = prep_insert_field_data_v2($conn, $purifier, $value);
    }

    //Creating the query
    $query_string = "UPDATE `" .$table . "` SET ";
    $set_clauses = [];
    foreach($insert_arr as $key => $value)
    {
        $set_clauses[] = "`" . $key . "` = '" . $value . "'";
    }
    $query_string .= implode(', ', $set_clauses);

    $query_string = perform_where($query_string, $where_arr);

    mysqli_query($conn, $query_string);
    return $query_string;
}

/**
 * Deletes data from a specified table.
 * 
 * Example usage:
 *      delete("cars", array("make"=>"ford"));
 * Returns:
 *      "DELETE FROM `cars` WHERE `make`='ford'"
 * 
 * @param string $table The table name.
 * @param array $where_arr The associative array of conditions for the deletion.
 * @return string The query string for debug purposes.
 */
function delete_where($table, $where_arr)
{
    global $conn;

    $query_string = "DELETE FROM " . "`" . $table . "` ";

    $query_string = perform_where($query_string, $where_arr);

    mysqli_query($conn, $query_string);
    return $query_string;
}

/**
 * Selects all data from a specified table.
 * 
 * Example usage:
 *      select_all("cars");
 * Returns:
 *      [["id"=>0,"make"=>"foo","model"=>"bar","year"=>"etc"],["id"=>1,"make"=>"bar","model"=>"foo","year"=>"etc"],[etc],[etc]...]
 * 
 * @param string $table The table name.
 * @return array An array of associative arrays, each representing a row of data.
 */
function select_all($table)
{
    global $conn;

    $query_string = perform_select($table, ["*"]);
    
    //cleaning
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects all data from a specified table with conditions.
 * 
 * Example usage:
 *      select_where("cars", array("make"=>"ford"));
 * Returns:
 *      [["id"=>0,"make"=>"ford","model"=>"focus","year"=>"2024"],["id"=>1,"make"=>"ford","model"=>"fusion","year"=>"2023"],[etc],[etc]...]
 * 
 * @param string $table The table name.
 * @param array $where_arr The associative array of conditions for the selection.
 * @return array An array of associative arrays, each representing a row of data.
 */
function select_where($table, $where_arr)
{
    global $conn;

    $query_string = perform_select($table, ["*"]);

    $query_string = perform_where($query_string, $where_arr);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects specific columns from a table with conditions and specified ordering.
 * 
 * Example usage:
 *      select_where_order_by("cars", array("make","model"), array("color"=>"red"), array("make"=>"ASC"));
 * Returns:
 *      [["make"=>"bar","model"=>"foo"],["make"=>"foo","model"=>"bar"],[etc],[etc]...]
 * 
 * @param string $table The table name.
 * @param array $where_arr The associative array of conditions for the selection.
 * @param array $order_by The associative array specifying the order of the results.
 * @return array An array of associative arrays, each representing a row of data.
 */
function select_where_order_by($table, $where_arr, $order_by)
{
    global $conn;

    $query_string = perform_select($table, ["*"]);

    $query_string = perform_where($query_string, $where_arr);
    
    $query_string = perform_order_by($query_string, $order_by);
    
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects all data from a specified table with specified ordering.
 * 
 * Example usage:
 *      select_all_order_by("cars", array("id"=>"DESC"));
 * Returns:
 *      [["id"=>1,"make"=>"bar","model"=>"foo","year"=>"etc"],["id"=>0,"make"=>"foo","model"=>"bar","year"=>"etc"],[etc],[etc]...]
 * 
 * @param string $table The table name.
 * @param array $order_by The associative array specifying the order of the results.
 * @return array An array of associative arrays, each representing a row of data.
 */
function select_all_order_by($table, $order_by)
{
    global $conn;

    $query_string = perform_select($table, ["*"]);

    $query_string = perform_order_by($query_string, $order_by);

    //cleaning
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects specific columns from a table.
 * 
 * Example usage:
 *      select_specific("cars", array("model","year"));
 * Returns:
 *      [["model"=>"focus","year"=>"2024"],["model"=>"fusion","year"=>"2023"],[etc],[etc]...]
 * 
 * @param string $table The table name.
 * @param array $items The array of columns to select.
 * @return array An array of associative arrays, each representing a row of data.
 */
function select_specific($table, $items)
{
    global $conn;

    $query_string = perform_select($table, $items);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects specific columns from a table with specified ordering.
 * 
 * Example usage:
 *      select_specific_order_by("cars", array("model", "year"), array("id"=>"DESC"));
 * Returns:
 *      [["model"=>"fusion","year"=>"2024"],["model"=>"focus","year"=>"2023"],[etc],[etc]...]
 * 
 * @param string $table The table name.
 * @param array $items The array of columns to select.
 * @param array $order_by The associative array specifying the order of the results.
 * @return array An array of associative arrays, each representing a row of data.
 */
function select_specific_order_by($table, $items, $order_by)
{
    global $conn;

    $query_string = perform_select($table, $items);

    $query_string = perform_order_by($query_string, $order_by);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects specific columns from a table with conditions.
 * 
 * Example usage:
 *      select_specific_where("cars", array("make","model"), array("color"=>"red"));
 * Returns:
 *      [["make"=>"foo","model"=>"bar"],["make"=>"bar","model"=>"foo"],[etc],[etc]...]
 * 
 * @param string $table The table name.
 * @param array $items The array of columns to select.
 * @param array $where_arr The associative array of conditions for the selection.
 * @return array An array of associative arrays, each representing a row of data.
 */
function select_specific_where($table, $items, $where_arr)
{
    global $conn;

    $query_string = perform_select($table, $items);

    $query_string = perform_where($query_string, $where_arr);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects specific columns from a table with conditions and specified ordering.
 * 
 * Example usage:
 *      select_specific_where_order_by("cars", array("make","model"), array("color"=>"red"), array("make"=>"ASC"));
 * Returns:
 *      [["make"=>"bar","model"=>"foo"],["make"=>"foo","model"=>"bar"],[etc],[etc]...]
 * 
 * @param string $table The table name.
 * @param array $items The array of columns to select.
 * @param array $where_arr The associative array of conditions for the selection.
 * @param array $order_by The associative array specifying the order of the results.
 * @return array An array of associative arrays, each representing a row of data.
 */
function select_specific_where_order_by($table, $items, $where_arr, $order_by)
{
    global $conn;

    $query_string = perform_select($table, $items);

    $query_string = perform_where($query_string, $where_arr);
    
    $query_string = perform_order_by($query_string, $order_by);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Joins and selects all data from multiple tables.
 * 
 * Example usage:
 *      join_select_all(array("cars"=>"id", "owners"=>"car_id"));
 * Returns:
 *      //Joined data from cars and owners tables
 *      [["car_id"=>1,"make"=>"ford","owner_name"=>"John"],["car_id"=>2,"make"=>"toyota","owner_name"=>"Jane"],[etc],[etc]...]
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @return array An array of associative arrays, each representing a row of joined data.
 */
function join_select_all($join_arr, $switch = null)
{
    global $conn;

    $query_string = perform_join($join_arr, ["*"], $switch);
    
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Joins and selects data from multiple tables with conditions.
 * 
 * Example usage:
 *      join_select_where(array("cars"=>"id", "owners"=>"car_id"), array("make"=>"ford"));
 * Returns:
 *      //Joined data from cars and owners tables where make is 'ford'
 *      [["car_id"=>1,"make"=>"ford","owner_name"=>"John"],["car_id"=>2,"make"=>"ford","owner_name"=>"Jane"],[etc],[etc]...]
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $where_arr The associative array of conditions for the selection.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @return array An array of associative arrays, each representing a row of joined data.
 */
function join_select_where($join_arr, $where_arr, $switch = null)
{
    global $conn;

    $query_string = perform_join($join_arr, ['*'], $switch);

    $query_string = perform_where($query_string, $where_arr);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Joins and selects data from multiple tables with conditions and specified ordering.
 * 
 * Example usage:
 *      join_select_where_order_by(array("cars"=>"id", "owners"=>"car_id"), array("make"=>"ford"), array("owner_name"=>"ASC"));
 * Returns:
 *      //Joined data from cars and owners tables where make is 'ford', ordered by owner_name ascending
 *      [["car_id"=>1,"make"=>"ford","owner_name"=>"John"],["car_id"=>2,"make"=>"ford","owner_name"=>"Jane"],[etc],[etc]...]
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $where_arr The associative array of conditions for the selection.
 * @param array $order_by The associative array specifying the order of the results.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @return array An array of associative arrays, each representing a row of joined data.
 */
function join_select_where_order_by($join_arr, $where_arr, $order_by, $switch = null)
{
    global $conn;

    $query_string = perform_join($join_arr, ['*'], $switch);

    $query_string = perform_where($query_string, $where_arr);
    
    $query_string = perform_order_by($query_string, $order_by);
	
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Joins and selects all data from multiple tables with specified ordering.
 * 
 * Example usage:
 *      join_select_all_order_by(array("cars"=>"id", "owners"=>"car_id"), array("owner_name"=>"ASC"));
 * Returns:
 *      //Joined data from cars and owners tables ordered by owner_name ascending
 *      [["car_id"=>1,"make"=>"ford","owner_name"=>"John"],["car_id"=>2,"make"=>"toyota","owner_name"=>"Jane"],[etc],[etc]...]
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $order_by The associative array specifying the order of the results.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @return array An array of associative arrays, each representing a row of joined data.
 */
function join_select_all_order_by($join_arr, $order_by, $switch = null)
{
    global $conn;

    $query_string = perform_join($join_arr, ["*"], $switch);

    $query_string = perform_order_by($query_string, $order_by);

    //cleaning
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Joins and selects specific columns from multiple tables.
 * 
 * Example usage:
 *      join_select_specific(array("cars"=>"id", "owners"=>"car_id"), array("make","owner.owner_name"));
 * Returns:
 *      //Joined data from cars and owners tables with specified columns
 *      [["make"=>"ford","owner_name"=>"John"],["make"=>"toyota","owner_name"=>"Jane"],[etc],[etc]...]
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $items The array of columns to select.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @return array An array of associative arrays, each representing a row of joined data.
 */
function join_select_specific($join_arr, $items, $switch = null)
{
    global $conn;

    $query_string = perform_join($join_arr, $items, $switch);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Joins and selects specific columns from multiple tables with specified ordering.
 * 
 * Example usage:
 *      join_select_specific_order_by(array("cars"=>"id", "owners"=>"car_id"), array("make","owner_name"), array("owner.owner_name"=>"ASC"));
 * Returns:
 *      //Joined data from cars and owners tables with specified columns and ordering
 *      [["make"=>"ford","owner_name"=>"John"],["make"=>"toyota","owner_name"=>"Jane"],[etc],[etc]...]
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $items The array of columns to select.
 * @param array $order_by The associative array specifying the order of the results.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @return array An array of associative arrays, each representing a row of joined data.
 */
function join_select_specific_order_by($join_arr, $items, $order_by, $switch = null)
{
    global $conn;

    $query_string = perform_join($join_arr, $items, $switch);

    $query_string = perform_order_by($query_string, $order_by);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Joins and selects specific columns from multiple tables with conditions.
 * 
 * Example usage:
 *      join_select_specific_where(array("cars"=>"id", "owners"=>"car_id"), array("make","owner_name"), array("make"=>"ford"));
 * Returns:
 *      //Joined data from cars and owners tables with specified columns and conditions
 *      [["make"=>"ford","owner_name"=>"John"],["make"=>"ford","owner_name"=>"Jane"],[etc],[etc]...]
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $items The array of columns to select.
 * @param array $where_arr The associative array of conditions for the selection.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @return array An array of associative arrays, each representing a row of joined data.
 */
function join_select_specific_where($join_arr, $items, $where_arr, $switch = null)
{
    global $conn;

    $query_string = perform_join($join_arr, $items, $switch);

    $query_string = perform_where($query_string, $where_arr);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Joins and selects specific columns from multiple tables with conditions and specified ordering.
 * 
 * Example usage:
 *      join_select_specific_where_order_by(array("cars"=>"id", "owners"=>"car_id"), array("make","owner_name"), array("make"=>"ford"), array("owner_name"=>"ASC"));
 * Returns:
 *      //Joined data from cars and owners tables with specified columns, conditions, and ordering
 *      [["make"=>"ford","owner_name"=>"John"],["make"=>"ford","owner_name"=>"Jane"],[etc],[etc]...]
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $items The array of columns to select.
 * @param array $where_arr The associative array of conditions for the selection.
 * @param array $order_by The associative array specifying the order of the results.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @return array An array of associative arrays, each representing a row of joined data.
 */
function join_select_specific_where_order_by($join_arr, $items, $where_arr, $order_by, $switch = null)
{
    global $conn;

    $query_string = perform_join($join_arr, $items, $switch);

    $query_string = perform_where($query_string, $where_arr);
    
    $query_string = perform_order_by($query_string, $order_by);

    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects distinc element in a column from specified table.
 * 
 * @param string $table The table the column is in
 * @param string $column The column in which to select distinc elements
 */
function select_distinct($table, $column)
{
    global $conn;
    $query_string = "SELECT DISTINCT " . $column . " FROM " . $table ."";
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects distinc element in a column from specified table, where a value is specified.
 * 
 * @param string $table The table the column is in
 * @param string $column The column in which to select distinc elements
 * @param array $where_arr The associative array of conditions for the selection.
 */
function select_distinct_where($table, $column, $where_arr)
{
    global $conn;
    $query_string = "SELECT DISTINCT " . $column . " FROM " . $table ."";
    $query_string = perform_where($query_string, $where_arr);
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects distinc element in a column from specified table, where a value is specified and sorted in declared order.
 * 
 * @param string $table The table the column is in
 * @param string $column The column in which to select distinc elements
 * @param array $where_arr The associative array of conditions for the selection.
 * @param array $order_by The associative array specifying the order of the results.
 */
function select_distinct_where_order_by($table, $column, $where_arr, $order_by)
{
    global $conn;
    $query_string = "SELECT DISTINCT " . $column . " FROM " . $table ."";
    $query_string = perform_where($query_string, $where_arr);
    $query_string = perform_order_by($query_string, $order_by);
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Selects distinc element in a column from specified table, where a value is specified and sorted in declared order.
 * 
 * @param string $table The table the column is in
 * @param string $column The column in which to select distinc elements
 * @param array $where_arr The associative array of conditions for the selection.
 * @param array $switch An associative array in the format ['table'=>'switch to']. When declared, after encountering specified table in join arr, will start joining by the specified column from that table
 * For example, if the join arr is ["cars"=>"id", "owners"=>"car_id", "boats"=>"owner_id"] and switch is declared as ["owners"=>"owner_id"], after performing the join between cars and owners on car id, a join will be performed between owners=>owner_id and boats=>owner_id.
 * @param array $order_by The associative array specifying the order of the results.
 */
function join_select_distinct_where_order_by($join_arr, $column, $where_arr, $order_by, $switch = null)
{
    global $conn;
    $query_string = perform_join($join_arr, ['*'], $switch);
    $distinct_string = "SELECT DISTINCT " . $column;
    $query_string = str_replace("SELECT *", $distinct_string, $query_string);
    $query_string = perform_where($query_string, $where_arr);
    $query_string = perform_order_by($query_string, $order_by);
    return clean_and_return_rows($conn, $query_string);
}

/**
 * Constructs a SQL join query string.
 * 
 * @param array $join_arr The associative array specifying the tables and join conditions.
 * @param array $items The array of columns to select.
 * @param array $switch OPTIONAL, when this table is reached, main table and column joined from switch to this item in the array
 * @return string The constructed SQL query string.
 */
function perform_join($join_arr, $items, $switch = null)
{
    $query_string = "";
    $items_string = "";
    $index = 0;
    $main_table = "";
    $on_column = "";

    foreach($items as $item)
    {
        $items_string .= " " . $item . ",";
    }

    substr_replace($query_string, '', -1);

    foreach($join_arr as $key => $value)
    {
        if($index == 0)
        {
            $main_table = $key;
            $on_column = $value;
            $query_string .= "SELECT * FROM " . "" . $main_table . " ";
        } else 
        {    
            $query_string .= "JOIN " . $key . " ON " . $key . "." . $value . "=" . $main_table . "." . $on_column . " ";

            if(isset($switch)){
                foreach($switch as $switch_key => $switch_value)
                if($key == $switch_key)
                {
                    $main_table = $key;
                    $on_column = $switch_value;
                }
            }
        }
        $index++;
    }

    return $query_string;
}

/**
 * Constructs a SQL select query string.
 * 
 * @param string $table The table name.
 * @param array $items The array of columns to select.
 * @return string The constructed SQL query string.
 */
function perform_select($table, $items)
{
    $query_string = "SELECT";

    foreach($items as $item)
    {
        $query_string .= " " . clean_reserved_words($item) . ",";
    }

    //Remove last comma
    $query_string = substr_replace($query_string, '', -1);

    return $query_string .= " FROM " . "" . clean_reserved_words($table) . "";
}

/**
 * Adds a WHERE clause to a SQL query string.
 * 
 * @param string $query_string The SQL query string.
 * @param array $where_arr The associative array of conditions.
 * @return string The SQL query string with the WHERE clause.
 */
function perform_where($query_string, $where_arr)
{
    $where_clauses = [];
    foreach($where_arr as $key => $value)
    {
        $where_clauses[] = "" . clean_reserved_words($key) . " = '" . clean_reserved_words($value) . "'";
    }
    if (!empty($where_clauses)) {
        $query_string .= " WHERE " . implode(' AND ', $where_clauses);
    }
    return $query_string;
}

/**
 * Adds an ORDER BY clause to a SQL query string.
 * 
 * @param string $query_string The SQL query string.
 * @param array $order_by The associative array specifying the order of the results.
 * @return string The SQL query string with the ORDER BY clause.
 */
function perform_order_by($query_string, $order_by)
{
    $query_string .= " ORDER BY ";

    //Creating the query
    foreach($order_by as $key => $value)
    {
       $query_string .= "" . clean_reserved_words($key) . " " . $value . ",";
    }
    
    //Remove last comma
    return(substr_replace($query_string, '', -1));
}

/**
 * Cleans and returns rows from a SQL query result.
 * 
 * @param object $conn The database connection object.
 * @param string $query_string The SQL query string.
 * @return array An array of associative arrays, each representing a row of data.
 */
function clean_and_return_rows($conn, $query_string) {
    echo $query_string;
    $result = mysqli_query($conn, $query_string);
    $rows = array();
    while ($row = mysqli_fetch_array($result)) {
        foreach ($row as $key => $value) 
        {
            $row[$key] = prep_select_display_data($value);
        }

        $rows[] = $row;
    }

    return $rows;
}

function clean_reserved_words($word)
{
    global $mysql_reserved_words;
    if(in_array(strtoupper(trim($word)), $mysql_reserved_words))
    {
        return "'" . $word . "'";
    }
    else
    {
        return $word;
    }
    
}

