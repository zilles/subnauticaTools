<?php

/**
 * Class GetImageDataCommand
 * This command reads a wiki page to map the wiki images to codes and descriptions
 */
class GetImageDataCommand extends CConsoleCommand
{
    public function run($args)
    {
//        $data = file_get_contents("https://subnautica.fandom.com/wiki/Spawn_IDs");
//        file_put_contents("Spawn_IDs", $data);
        $data = file_get_contents("Spawn_IDs");
        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTML($data);
//        DOMRecursiveIterator::xmltree_dump($dom);
        $tables = $dom->getElementsByTagName("table");
        $results = [];
        foreach($tables as $table)
            $this->processTable($dom, $results, $table);

        $uls = $dom->getElementsByTagName("ul");
        foreach($uls as $ul)
            $this->processUl($dom, $results, $ul);


        $dir = dirname(__FILE__,2);
        $json = json_encode($results, JSON_PRETTY_PRINT);
        file_put_contents("$dir/data/image.json", $json);
    }

    public function processTable($dom, &$results, $table)
    {
        $classes = $table->getAttribute("class");
        if ($classes != "article-table sortable")
            return;

        /*
        DOMRecursiveIterator::xmltree_dump($table);
        exit;
        */

        $trs = $table->getElementsByTagName("tr");
        foreach($trs as $tr)
            $this->processTr($dom, $results, $tr);

    }

    public function nonstandard($message, $el)
    {
        echo "$message:\n" ;
        DOMRecursiveIterator::xmltree_dump($el);
        exit;
    }

    public function processTr($dom, &$results, $tr)
    {
        $src = null;
        $name = null;
        $code = null;

        /*
        DOMRecursiveIterator::xmltree_dump($tr);
        exit;
        */

        $tds = $tr->getElementsByTagName("td");
        if (count($tds) !=2)
            return $this->nonstandard("Didn't find exactly 2 tds",$tr);

        $imgs = $tds[0]->getElementsByTagName("img");
        $img_count = count($imgs);
        if ($img_count <1)
            return $this->nonstandard("Found no image in first td",$tr);

        $src = $imgs[$img_count-1]->getAttribute("src");

        $xpath = new DOMXPath($dom);
        $texts = $xpath->query('a/text()',$tds[0]);
        if (count($texts) != 1)
            return $this->nonstandard("Found no label in image td",$tr);
        $name = $texts[0]->textContent;

        $texts = $xpath->query('code/text()',$tds[1]);
        if (count($texts) != 1)
            return $this->nonstandard("Found no code in 2nd td",$tr);
        $code = $texts[0]->textContent;

//        echo "$name($code): $src\n";
        // download image
        $local_img = $this->imagePath($code);
        if (!file_exists($local_img))
        {
            $src = preg_replace('/latest.*/', 'latest', $src);
            $imagedata = file_get_contents($src);
            file_put_contents($local_img, $imagedata);
        }

        $results[] = ["name"=>$name, "code"=>$code];
    }

    public function imagePath($code, $num = null)
    {
        $dir = dirname(__FILE__,3);
        $name = $code? $code: $num;
        return "$dir/images/$name.png";
    }

    public function processUl($dom, &$results, $ul)
    {
        $lis = $ul->getElementsByTagName("li");
        if (count($lis)< 500)
            return;

        $count = 0;
        $xpath = new DOMXPath($dom);
        foreach ($lis as $li)
        {
            //DOMRecursiveIterator::xmltree_dump($li);

            $texts = $xpath->query('text()',$li);
            if (count($texts)>0)
            {
                $num = trim($texts[0]->textContent);
                $extra = "";
                if (count($texts)>1)
                    $extra = trim($texts[1]->textContent);
                if (is_numeric($num))
                {
                    // number identified

                    $xpath = new DOMXPath($dom);
                    $texts = $xpath->query('a/text()',$li);
                    $links = $xpath->query('a/@href',$li);
                    if (count($texts) > 0 && count($links)>0)
                    {
                        $name = $texts[0]->textContent;
                        $link = $links[0]->value;

                        // We have a number and a name with a link let's look for a code
                        $code = null;
                        $codes = $xpath->query('i/text()',$li);
                        if (count($codes)>0)
                            $code = $codes[0]->textContent;

                        if (($extra == "" || $extra =="-" || $extra == "Egg") && stristr($link,"Cut_Content")==false && stristr($link,"Large_Resource_Deposits")==false)
                        {
                            if ($extra == "Egg")
                                $name.=" Egg";

                            $existing = false;
                            foreach($results as &$result)
                            {
                                if (($code && isset($result['code']) && $result['code']==$code) || $result['name']==$name)
                                {
                                    $existing = true;
                                    $result['num'] = $num;
                                }
                            }
                            if (!$existing)
                            {
                                echo "$num: $name: $link: $code: $extra\n";
                                $image = $this->imagePath($code, $num);
                                if (file_exists($image) || $this->getImage($image, $link))
                                {
                                    $record = [
                                        "num" => $num,
                                        "name" => $name,
                                    ];
                                    if ($code)
                                        $record['code']=$code;
                                    $results[]= $record;
                                }
                            }
                        }
                    }
                }
            }

            /*
            if ($count++ > 5)
                exit();
            */
        }
    }

    public function getImage($imagefile, $link)
    {
        return false;
        $url = "https://subnautica.fandom.com$link";
        $data = file_get_contents($url);
        /*
        file_put_contents("page.html", $data);
        $data = file_get_contents("page.html");
        */
        $dom = new DOMDocument;
        $dom->loadHTML($data);
        $xpath = new DOMXPath($dom);
        $cutcontent = $xpath->query("//h1[text() = 'Cut Content']",$dom);
        if (count($cutcontent)>0)
        {
            echo "Cut Content\n";
            return false;
        }
        $images = $xpath->query("//*[@data-source='image4']//a/img",$dom);
        // 2nd try based on size
        if(count($images)==0)
            $images = $xpath->query("//*[@data-source='size']//a/img",$dom);
        if(count($images)==0)
            $images = $xpath->query("//*[@data-source='bioreactor']//a/img",$dom);
        if(count($images)==0)
            $images = $xpath->query("//*[@data-source='reap']//a/img",$dom);

        if(count($images)>0)
        {
            $srcs = [];
            foreach($images as $image)
            {
                $attr = $image->hasAttribute("data-src") ? "data-src" : "src";
                $srcs[] = $image->getAttribute($attr);
            }
            $num = null;
            if (count($srcs)>1)
            {
                print_r($srcs);
                while (!isset($srcs[$num]) && $num != "s" && $num != "e")
                {
                    echo "Enter number or s to skip, e to exit: ";
                    $num = readline();
                }
                if ($num == "e")
                    exit;
                if ($num == "s")
                    return false;
            }
            else $num = 0;
            $src = $srcs[$num];
            $src = preg_replace('/latest.*/', 'latest', $src);
            $bits = file_get_contents($src);
            file_put_contents($imagefile, $bits);
        }
        else
        {
            echo "Unable to find image in page $url\n";
            $num = null;
            while ($num != "s" && $num != "e")
            {
                echo "Enter s to skip, e to exit: ";
                $num = readline();
            }
            if ($num == "e")
                exit;
            if ($num == "s")
                return false;
        }
        return true;
    }
}
