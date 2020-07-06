<?php

use League\CommonMark\Delimiter\DelimiterInterface;
use League\CommonMark\Delimiter\Processor\DelimiterProcessorInterface;
use League\CommonMark\Inline\Element\AbstractStringContainer;
use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Inline\Element\Text;

class SubImage implements DelimiterProcessorInterface {
    public static $_cache = null;

    public function getMap()
    {
        if (self::$_cache === null)
        {
            self::$_cache = [];

            $dir = dirname(__FILE__,2);
            $json = file_get_contents("$dir/data/image.json");
            if ($json)
            {
                $array = json_decode($json);
                foreach($array as $a)
                {
                    list($name,$code, $src) = $a;
                    self::$_cache[strtolower($name)] = $src;
                    self::$_cache[strtolower($code)] = $src;
                }
            }
        }
        return self::$_cache;
    }
    public function getOpeningCharacter():string
    {
        return "{";
    }

    public function getClosingCharacter():string
    {
        return "}";
    }

    public function getMinLength():int
    {
        return 1;
    }

    public function getDelimiterUse(DelimiterInterface $opener, DelimiterInterface $closer):int
    {
        return 1;
    }

    /**
     * Process the matched delimiters, e.g. by wrapping the nodes between opener
     * and closer in a new node, or appending a new node after the opener.
     *
     * Note that removal of the delimiter from the delimiter nodes and detaching
     * them is done by the caller.
     *
     * @param AbstractStringContainer $opener       The node that contained the opening delimiter
     * @param AbstractStringContainer $closer       The node that contained the closing delimiter
     * @param int                     $delimiterUse The number of delimiters that were used
     *
     * @return void
     */
    public function process(AbstractStringContainer $opener, AbstractStringContainer $closer, int $delimiterUse)
    {
        $reference = "";
        $count = 1;

        // Add everything between $opener and $closer (exclusive) to the new outer element
        $tmp = $opener->next();
        while ($tmp !== null && $tmp !== $closer) {
            $next = $tmp->next();
            $reference .= $tmp->getContent();
            $tmp->setContent("");
            $tmp = $next;
        }

        $split = explode(":",$reference);
        if (count($split) == 2)
        {
            $reference = $split[0];
            $count = $split[1];
        }

        $map = $this->getMap();
        $src = CHtml::value($map,strtolower($reference));

//        $src = "https://vignette.wikia.nocookie.net/subnautica/images/7/78/Copper_Ore.png/revision/latest";

        if (!$src)
        {
            $image = new Text("[Can't find $reference]");
            $closer->insertAfter($image);
        }
        else
        {
            $src = preg_replace('/latest.*/', 'latest', $src);
            for ($i=0; $i<$count; $i++)
            {
                // Create the outer element
                if ($src)
                    $image = new Image($src, $reference, $reference);

                // Place the outer element into the AST
                $closer->insertAfter($image);
            }
        }
    }
}