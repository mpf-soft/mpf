<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 07.11.2014
 * Time: 10:21
 */

namespace mpf\tools;


use mpf\base\MPFObject;
use mpf\cli\Helper;
use mpf\tools\install\File;
use mpf\tools\install\SQL;

class Installer extends MPFObject {

    public static $APP_CONFIG_DIR;

    protected static $data = [
        'mpf\\interfaces\\LogAwareObjectInterface' => [
            'loggers' => ['mpf\\loggers\\InlineWebLogger']
        ],
        'mpf\\interfaces\\TranslatableObjectInterface' => [
            'translator' => '\\mpf\\translators\\ArrayFile'
        ],
        'mpf\\web\\AssetsPublisher' => [
            'developmentMode' => true // change it to true when working on widgets or any other classes that publish assets that are changed during development
        ],
        'mpf\\base\\App' => [
            //        'cacheEngineClass' => '\\mpf\\datasources\\redis\\Cache'
        ],
        'mpf\\helpers\\MailHelper' => [
        ]
    ];

    public static function baseAppWithSQL() {
        if ('n' == Helper::get()->input("Execute initial setup? (creates db, config file, changes user password salt)", 'y')){
            return;
        }
        self::$APP_CONFIG_DIR = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        echo "Config folder " . self::$APP_CONFIG_DIR . '.. \n';
        echo "updating htdocs rights..   ";
        chmod(dirname(dirname(self::$APP_CONFIG_DIR)). DIRECTORY_SEPARATOR.'htdocs'.DIRECTORY_SEPARATOR.'__assets', '0777');
        echo "done\n";
        $conf = SQL::loadConfig();
        self::$data['mpf\\datasources\\sql\\PDOConnection'] = [
            'dns' => "{$conf[0]}:dbname={$conf[2]};host={$conf[1]}",
            'username' => $conf[3],
            'password' => $conf[4]
        ];
        self::$data['mpf\\base\\App']['title'] = Helper::get()->input("App Long Title", 'New MPF App');
        self::$data['mpf\\base\\App']['shortTitle'] = Helper::get()->input("App Short Name", 'app');
        if (SQL::$connected) {
            echo "\n" . Helper::get()->startAction("importing DB from file..");
            SQL::importFromFile(self::$APP_CONFIG_DIR . '__db_' . $conf[0] . '.sql');
            echo Helper::get()->endAction();

            self::createAdminUser();
        }
        if ('y' == Helper::get()->input("Use redis cache?", 'y')){
            self::$data['mpf\\base\\App']['cacheEngineClass'] = '\\mpf\\datasources\\redis\\Cache';
        }
        self::mailConfig();
        self::writeConfig();
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
        $userId = SQL::$connection->table('users')->insert([
            'name' => $name,
            'email' => $email,
            'status' => \app\models\User::STATUS_ACTIVE,
            'password' => \app\models\User::hashPassword($password) //hash password using password hash from user model
        ], 'ignore'); // don't create if an user is already there
        $groups = SQl::$connection->table('users_groups')->get();
        $table = SQL::$connection->table('users2groups');
        foreach ($groups as $group){ // add all possible groups to this user.
            $table->insert([
                'group_id' => $group['id'],
                'user_id' => $userId
            ], 'IGNORE');
        }
        echo "\n" . Helper::get()->color("DONE!", Helper::CLIGHT_GREEN) . "\n";
    }

    protected static function mailConfig(){
        echo "Setup MailHelper (from && reply-to data): \n";
        echo "You can configure multiple setups to be used by different scenarious. To use a different setup other than".
            "`default` just complete in `from` param the name of the setup. It will automatically get the info from the config\n";
        $key = 'default';
        do {
            echo "Set-up $key options:\n";
            self::$data['mpf\\helpers\\MailHelper'][$key] = [
                'email' => Helper::get()->input("From Email Address", 'admin@mydomain.com'),
                'name' => Helper::get()->input("From Name", "MPF App"),
                'reply-to' => [
                    'email' => Helper::get()->input("Reply-to Email Address", "no-reply@mydomain.com"),
                    'name' => Helper::get()->input("Reply-to Name", '')
                ]
            ];
        } while ('n' != ($key = Helper::get()->input("Next setup(n - to stop)", 'n')));
    }

    protected static function writeConfig() {
        File::createConfig(self::$data, self::$APP_CONFIG_DIR . 'config.inc.php');
    }

} 