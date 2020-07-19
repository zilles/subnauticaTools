<?php
class SubTools {
    public static $additions = [
        "tank"=>["width"=>2,"height"=>3],
        "doubletank"=>["width"=>2,"height"=>3],
        "seaglide"=>["width"=>2,"height"=>3],
        "scrapmetal"=>["width"=>2,"height"=>2],
        "propulsioncannon"=>["width"=>2,"height"=>2],
        "fins"=>["width"=>2,"height"=>2],
        "rebreather"=>["width"=>2,"height"=>2],
        "radiationsuit"=>["width"=>2,"height"=>2],
        "radiationhelmet"=>["width"=>2,"height"=>2],
        "radiationgloves"=>["width"=>2,"height"=>2],
        "reinforceddivesuit"=>["width"=>2,"height"=>2],
        "reinforcedgloves"=>["width"=>2,"height"=>2],
        "creaturedecoy"=>["width"=>1,"height"=>2],
        "constructor"=>["width"=>3,"height"=>3],
        "stasisrifle"=>["width"=>2,"height"=>2],
        "gravsphere"=>["width"=>2,"height"=>2],
        "smallstorage"=>["width"=>2,"height"=>2],
        "plasteeltank"=>["width"=>2,"height"=>3],
        "highcapacitytank"=>["width"=>3,"height"=>3],
        "ultraglidefins"=>["width"=>2,"height"=>2],
        "swimchargefins"=>["width"=>2,"height"=>2],
        "repulsioncannon"=>["width"=>2,"height"=>2],
        "stillsuit"=>["width"=>2,"height"=>2],
        "stalkeregg"=>["width"=>2,"height"=>2],
        "sandsharkegg"=>["width"=>2,"height"=>2],
        "jellyrayegg"=>["width"=>2,"height"=>2],
        "bonesharkegg"=>["width"=>2,"height"=>2],
        "crabsnakeegg"=>["width"=>2,"height"=>2],
        "shockeregg"=>["width"=>2,"height"=>2],
        "gasopodegg"=>["width"=>2,"height"=>2],
        "crabsquidegg"=>["width"=>2,"height"=>2],
        "lavalizardegg"=>["width"=>2,"height"=>2],
        "luggagebag"=>["width"=>2,"height"=>2],
        1809=>["width"=>2,"height"=>2],
        1810=>["width"=>2,"height"=>2],
        1811=>["width"=>2,"height"=>2],
        "labcontainer"=>["width"=>2,"height"=>2],
        "creepvineseedcluster"=>["width"=>2,"height"=>2],
        "creepvinepiece"=>["width"=>2,"height"=>2],
        "bloodoil"=>["width"=>2,"height"=>2],
        3063=>["width"=>2,"height"=>2],
        "kooshchunk"=>["width"=>2,"height"=>2],
        "bulbotreepiece"=>["width"=>2,"height"=>2],
        "orangemushroomspore"=>["width"=>2,"height"=>2],
        "purplevaseplantseed"=>["width"=>2,"height"=>2],
        "hangingfruit"=>["width"=>2,"height"=>2],
        "purplevegetable"=>["width"=>2,"height"=>2],
        "melon"=>["width"=>2,"height"=>2],
        "purplebraincoralpiece"=>["width"=>2,"height"=>2],
        "spikeplantseed"=>["width"=>2,"height"=>2],
        "bluepalmseed"=>["width"=>2,"height"=>2],
        "gabesfeatherseed"=>["width"=>2,"height"=>2],
        "seacrownseed"=>["width"=>2,"height"=>2],
        "membraintreeseed"=>["width"=>2,"height"=>2],
        "fernpalmseed"=>["width"=>2,"height"=>2],
        "orangepetalsplantseed"=>["width"=>2,"height"=>2],
        "eyesplantseed"=>["width"=>2,"height"=>2],
        "redgreententacleseed"=>["width"=>2,"height"=>2],
        "purplestalkseed"=>["width"=>2,"height"=>2],
        "redbasketplantseed"=>["width"=>2,"height"=>2],
        "redbushseed"=>["width"=>2,"height"=>2],
        "redconeplantseed"=>["width"=>2,"height"=>2],
        "shellgrassseed"=>["width"=>2,"height"=>2],
        "spottedleavesplantseed"=>["width"=>2,"height"=>2],
        "redrollplantseed"=>["width"=>2,"height"=>2],
        "purplebranchesseed"=>["width"=>2,"height"=>2],
        "snakemushroomspore"=>["width"=>2,"height"=>2],
        "maproomcamera"=>["width"=>2,"height"=>2],
        "reefbackegg"=>["width"=>2,"height"=>2],
        "labequipment1"=>["width"=>2,"height"=>2],
        "labequipment2"=>["width"=>2,"height"=>2],
        "labequipment3"=>["width"=>2,"height"=>2],
        "seatreaderpoop"=>["width"=>2,"height"=>2],
    ];


    public static function imagePathFromObject($a)
    {
        $obj = (object) $a;
        if (isset($obj->code))
            $src = "/images/$obj->code.png";
        else
            $src = "/images/$obj->num.png";
        return $src;
    }

    static protected $_image_data_cache = null;
    public static function loadImageData()
    {
        if (self::$_image_data_cache == null)
        {
            $array = [];
            $dir = dirname(__FILE__,2);
            $json = file_get_contents("$dir/data/image.json");
            if ($json)
                $array = json_decode($json,true);
            foreach($array as &$a)
            {
                $a["width"]=1;
                $a["height"]=1;
                $a['src'] = SubTools::imagePathFromObject($a);

                $adds = [];

                if (isset($a["code"]))
                    $adds = CHtml::value(self::$additions,$a["code"],$adds);
                if (isset($a["num"]))
                    $adds = CHtml::value(self::$additions,$a["num"], $adds);

                foreach($adds as $k=>$v)
                    $a[$k]=$v;
            }
            self::$_image_data_cache = $array;
        }
        return self::$_image_data_cache;
    }

    public static $_map_cache = null;
    public static function getMap()
    {
        if (self::$_map_cache === null)
        {
            self::$_map_cache = [];

            $array = SubTools::loadImageData();
            foreach($array as $a)
            {
                $obj = (object) $a;
                if (isset($obj->code))
                    self::$_map_cache[strtolower($obj->code)] = $a;
                if (isset($obj->num))
                    self::$_map_cache[strtolower($obj->num)] = $a;
                self::$_map_cache[strtolower($obj->name)] = $a;
            }
        }
        return self::$_map_cache;
    }

    public static function inventory($width, $height, $items)
    {
        $map = self::getMap();

        $list = [];
        $itemArray = explode(",", $items);
        foreach($itemArray as $item)
        {
            $item = trim($item);
            $count = 1;
            $parts = explode(":", $item);
            if (count($parts)>1)
            {
                $item = trim($parts[0]);
                $count = trim($parts[1]);
            }
            $obj = CHtml::value($map,$item);
            if ($obj == null)
                return "[Can't find $item]\n";
            $obj = (object)$obj;
            $current = CHtml::value($list, $obj->name);
            if ($current)
                $count += $current[1];
            $list[$obj->name] = [$obj, $count];
        }
        usort($list, function($a,$b) {
            $aobj = $a[0];
            $bobj = $b[0];
            $asize = $aobj->width * $aobj->height;
            $bsize = $bobj->width * $bobj->height;
            if ($asize != $bsize)
                return $bsize-$asize;
            $numa = CHtml::value($aobj,"num", 10000);
            $numb = CHtml::value($bobj,"num", 10000);
            return $numa-$numb;
        });

        $grid = [];
        for ($i=0; $i<$height; $i++)
            $grid[$i] = array_fill(0, $width, false);

        foreach($list as $item)
        {
            list($obj,$count) = $item;
            for ($n=0; $n<$count; $n++)
            {
                if (!self::addObject($grid, $obj))
                    return "Unable to add $obj->name #".($n+1);
            }
        }

        return self::htmlForGrid($grid);
    }

    public static function addObject(&$grid, $obj)
    {
        $height = count($grid);
        $width = $height == 0 ? 0 : count($grid[0]);

        for ($y=0; $y<$height; $y++)
        {
            for ($x=0; $x<$width; $x++)
            {
                if (self::tryObject($grid, $obj, $x, $y))
                {
                    $first = true;
                    for($j=$y; $j<$y+$obj->height; $j++)
                    {
                        for($i=$x; $i<$x+$obj->width; $i++)
                        {
                            $grid[$j][$i] = $first ? $obj : true;
                            $first = false;
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public static function tryObject($grid, $obj, $x, $y)
    {
        for($j=$y; $j<$y+$obj->height; $j++)
        {
            for($i=$x; $i<$x+$obj->width; $i++)
            {
                if (!isset($grid[$j][$i]) || $grid[$j][$i] !== false)
                    return false;
            }
        }
        return true;
    }

    public static function htmlForGrid($grid)
    {
        $html="";
        $height = count($grid);
        $width = $height == 0 ? 0 : count($grid[0]);
        $html.=CHtml::openTag("table",["class"=>"inventory"]);
        for ($y=0; $y<$height; $y++)
        {
            $html.=CHtml::openTag("tr");
            for ($x=0; $x<$width; $x++) {
                $obj = $grid[$y][$x];
                if ($obj===false)
                    $html.=CHtml::tag("td",[],CHtml::tag("div",["class"=>"empty"]));
                else if ($obj!==true)
                {
                    $class = "";
                    if ($obj->width>1)
                        $class="wid".$obj->width;
                    if ($obj->height>1)
                        $class.=" hgt".$obj->height;
                    $img = CHtml::image($obj->src, $obj->name, ["class"=>$class]);
                    $html.= CHtml::tag("td",["colspan"=>$obj->width, "rowspan"=>$obj->height], $img);
                }
            }
            $html.=CHtml::closeTag("tr");
        }
        $html.=CHtml::closeTag("table");
        return $html;
    }
}
