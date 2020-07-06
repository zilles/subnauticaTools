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
        $results[] = [$name, $code, $src];
    }
}
