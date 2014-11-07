<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 07.11.2014
 * Time: 10:21
 */

namespace mpf\tools;


use mpf\base\Object;
use mpf\cli\Helper;
use mpf\tools\install\File;
use mpf\tools\install\SQL;

class Installer extends Object {

    public static $APP_CONFIG_DIR;

    protected static $data = [
        'mpf\\interfaces\\LogAwareObjectInterface' => array(
            'loggers' => array('mpf\\loggers\\InlineWebLogger')
        ),
        'mpf\\interfaces\\TranslatableObjectInterface' => array(
            'translator' => '\\mpf\\translators\\ArrayFile'
        ),
        'mpf\\web\\AssetsPublisher' => array(
            'developmentMode' => true // change it to true when working on widgets or any other classes that publish assets that are changed during development
        ),
        'mpf\\base\\App' => array(//        'cacheEngineClass' => '\\mpf\\datasources\\redis\\Cache'
        )];

    public static function baseAppWithSQL() {
        self::$APP_CONFIG_DIR = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        self::loadSQLConfig();
        if (SQL::$connected) {
            echo "\n" . Helper::get()->startAction("importing DB from file..");
            SQL::importFromFile(self::$APP_CONFIG_DIR . '__db_' . self::$data[0] . '.sql');
            echo Helper::get()->endAction();

            self::createAdminUser();
        }
        self::checkRedis();
        self::writeConfig();
    }


    protected static function loadSQLConfig() {
        echo "\n" . Helper::get()->color("DB Config: ") . "\n";
        do {
            $type = Helper::get()->input("SQL DB Type", "mysql");
            $host = Helper::get()->input("SQL Host", "localhost");
            $name = Helper::get()->input("SQL Name", "");
            $user = Helper::get()->input("SQL User", "root");
            $pass = Helper::get()->input("SQL Pass", "");
        } while (!SQL::testConnection($type, $host, $name, $user, $pass));
        self::$data['db'] = [$type, $host, $name, $user, $pass];
    }

    protected static function createAdminUser() {
        echo "Create New Admin User: \n";
        do {
            $passwordSalt = Helper::get()->input("Password Salt(min 6 chars - used to encrypt passwords)", uniqid("D#A") . "D#\$D");
        } while (strlen($passwordSalt) < 6);
        File::searchAndReplace(dirname(self::$APP_CONFIG_DIR) . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php', "'342!$!@D#ASDA3d44'", "'$passwordSalt'");
        $name = Helper::get()->input("Name");
        $email = Helper::get()->input("Email");
        $password = Helper::get()->passwordInput("Password");
        SQL::$connection->table('users')->insert([
            'name' => $name,
            'email' => $email,
            'password' => \app\models\User::hashPassword($password) //hash password using password hash from user model
        ], 'ignore'); // don't create if an user is already there
        echo "\n" . Helper::get()->color("DONE!", Helper::CLIGHT_GREEN) . "\n";

    }

    protected static function checkRedis() {
        if ('y' == Helper::get()->input('Use redis cache?', 'y')) {
            self::$data['mpf\\base\\App'] = [
                'cacheEngineClass' => '\\mpf\\datasources\\redis\\Cache'
            ];
        }
    }

    protected static function writeConfig() {
        File::createConfig(self::$data, self::$APP_CONFIG_DIR . 'config.inc.php');
    }

} 