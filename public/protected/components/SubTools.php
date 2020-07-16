<?php
class SubTools {
    public static function imagePathFromObject($a)
    {
        $obj = (object) $a;
        if (isset($obj->code))
            $src = "/images/$obj->code.png";
        else
            $src = "/images/$obj->num.png";
        return $src;
    }
}
