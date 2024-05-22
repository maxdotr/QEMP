## Package Info

- **Author**: Maxwell Rodgers
- **Version**: 1.0
- **Last Updated**: May 22nd, 2024

# Quick & Easy: PHP -> MySQL Library

## Overview

This library provides a comprehensive set of functions for handling common database operations with PHP and MySQL. It supports insert, update, delete, join, and select queries and includes data sanitization and purification methods to ensure data integrity and security.

## Requirements

- This library requires the `ezyang/htmlpurifier` library to function correctly.
- MySQL connection object (`$conn`) and HTMLPurifier object (`$purifier`) need to be initialized before using this library's functions.

## Setup

Initialize your `$conn` and `$purifier` in an includes file before utilizing this library to avoid repeatedly passing the connection object to functions. Here's an example of how you might set this up:

```php
// includes.php
<?php 
    $docpath = "/path/to/your/program/resources";
    require_once $docpath . "/html_purifier/library/HTMLPurifier.auto.php";
    $config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($config);
    require($docpath . "/connect.php");
    $conn = get_connection();
    require($docpath . "/QEMP.php");
?>

// anything_else.php
<?php
    include_once "includes.php";
    // Ready to use!
    $items = select_where("cars", array("make"=>"ford"));
    // etc...
?>
```

## Usage

Once the `$conn` and `$purifier` are set up, you can perform database operations by simply including your setup file and using the functions provided by this library. For example:

```php
include_once "includes.php";
$items = select_where("cars", array("make" => "ford"));
// Perform other database operations as needed
```