<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 23.03.2015
 * Time: 15:45
 */

namespace mpf\helpers;


use mpf\base\Helper;

class FileHelper extends Helper{
    public function isImage($path){
        return true;
    }

    public function upload($name, $location){
        return move_uploaded_file($_FILES[$name]['tmp_name'], $location);
    }

    public function getMime($path){
        return exec("file -i -b $path");
    }
}