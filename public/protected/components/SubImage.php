<?php

use League\CommonMark\Delimiter\DelimiterInterface;
use League\CommonMark\Delimiter\Processor\DelimiterProcessorInterface;
use League\CommonMark\Inline\Element\AbstractStringContainer;
use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Inline\Element\Text;

class SubImage implements DelimiterProcessorInterface {
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
        $reference = trim($reference);

        $locker = $this->checkForLocker($reference);

        if ($locker == null)
        {
            $split = explode(":",$reference);
            if (count($split) == 2)
            {
                $reference = $split[0];
                $count = $split[1];
            }

            $map = SubTools::getMap();
            $obj = CHtml::value($map,strtolower($reference));

            if (!$obj)
            {
                $image = new Text("[Can't find $reference]");
                $closer->insertAfter($image);
            }
            else
            {
                $obj = (object)$obj;
                for ($i=0; $i<$count; $i++)
                {
                    // Create the outer element
                    $image = new Image($obj->src, $obj->name, $obj->name);

                    // Place the outer element into the AST
                    $closer->insertAfter($image);
                }
            }
        }
        else
        {
            $htmlDump = new HtmlDump($locker);
            $closer->insertAfter($htmlDump);
        }
    }

    public function checkForLocker($reference)
    {
        $lockerMarkers = [
            "locker"=>[6,8],
            "inventory"=>[6,8],
            "wall"=>[5,6],
            "waterproof"=>[4,4],
        ];

        if (preg_match('/^grid(-[^:]+)?:/', $reference, $matches))
        {
            $width = 6;
            $height = 8;

            if (isset($matches[1]))
            {
                $type = substr($matches[1],1);

                // look for 4x4
                if (preg_match('/^(\d+)x(\d+)$/', $type, $numbers))
                {
                    $width = $numbers[1];
                    $height = $numbers[2];
                    if ($width>10 || $height>10)
                        return "[size out of bounds]";
                }
                else
                {
                    $found = false;
                    foreach ($lockerMarkers as $name => $size) {
                        if ($type == $name) {
                            list($width, $height) = $size;
                            $found = true;
                        }
                    }
                    if (!$found)
                        return "[Don't know $type.  Try locker, walllocker, waterprooflocker, or 4x4]";
                }
            }
            $items = substr($reference, strlen($matches[0]));
            return SubTools::inventory($width, $height, $items);
        }

        return null;
    }
}