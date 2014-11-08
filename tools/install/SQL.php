<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 07.11.2014
 * Time: 11:01
 */

namespace mpf\tools\install;


use mpf\cli\Helper;
use mpf\datasources\sql\PDOConnection;

class SQL {
    protected static $retryCount = 0;

    public static $connected = false;

    /**
     * @var \mpf\datasources\sql\PDOConnection
     */
    public static $connection;

    public static function loadConfig() {
        echo "\n" . Helper::get()->color("DB Config: ") . "\n";
        do {
            $type = Helper::get()->input("SQL DB Type", "mysql");
            $host = Helper::get()->input("SQL Host", "localhost");
            $name = Helper::get()->input("SQL Name", "");
            $user = Helper::get()->input("SQL User", "root");
            $pass = Helper::get()->input("SQL Pass", "");
        } while (!SQL::testConnection($type, $host, $name, $user, $pass));
        return [$type, $host, $name, $user, $pass];
    }

    public static function testConnection($type, $host, $name, $user, $pass) {
        try {
            self::$connection = new PDOConnection([
                'dns' => "$type:dbname=$name;host=$host",
                'username' => $user,
                'password' => $pass
            ]);
        } catch (\Exception $e) {
            echo Helper::get()->color("Error: " . $e->getMessage(), Helper::CLIGHT_RED) . "\n\n\n";
            self::$retryCount++;
            self::$connected = false;
            if (self::$retryCount > 3) {
                return ('y' == Helper::get()->input("Skip this step?", 'n'));
            } else {
                return false;
            }
        }
        echo "\n" . Helper::get()->color("Connected!", Helper::CLIGHT_GREEN) . "\n";
        self::$connected = true;
        return true;
    }

    public static function importFromFile($filePath) {
        try {
            self::$connection->query(file_get_contents($filePath))->fetch();
        } catch (\Exception $e) {

        }
    }
} 