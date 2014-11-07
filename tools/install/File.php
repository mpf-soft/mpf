<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 07.11.2014
 * Time: 12:05
 */

namespace mpf\tools\install;


class File {

    protected static $tabContent = "    ";

    /**
     * @param $filePath
     * @param $original
     * @param $new
     * @return int
     */
    public static function searchAndReplace($filePath, $original, $new){
        return file_put_contents($filePath, str_replace($original, $new, file_get_contents($filePath)));
    }


    /**
     * @param $config
     * @param $filePath
     * @return int
     */
    public static function createConfig($config, $filePath){
        return file_put_contents($filePath, "<?php\n return [" . implode(",\n",self::toPHPString($config)) . "\n];\n");
    }

    /**
     * Returns php code for array
     * @param array $config
     * @return string[]
     */
    public static function toPHPString($config, $prefix = ""){
        $r = [];

        foreach ($config as $key=>$value){
            $key = addslashes($key);
            $line = $prefix . self::$tabContent . "\"$key\" => ";
            if (is_string($value)){
                $value = addslashes($value);
                $line .= "\"$value\"";
                $r[] = $line;
                continue;
            }
            $line .= "[" . implode(",\n", self::toPHPString($value, $prefix.self::$tabContent)) . "\n$prefix]";
            $r[] = $line;
        }

        return $r;
    }
} 