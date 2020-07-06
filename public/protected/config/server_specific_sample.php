<?php

// Note that merge recursive is handy for adding elements to the tree
// but that it can't be used to replace items in the tree (it will instead
// merge them into an array at that level).  If you need to replace items
// try doing specific assigns.

$config = CMap::mergeArray($config, array(
    'params'=>array(
        'debug'=>true,  // SET TO FALSE ON LIVE SITE
        'weasyprint'=>'C:\\Users\\zilles\\AppData\\Local\\Programs\\Python\\Python37\\Scripts\\weasyprint',
    ))
);


