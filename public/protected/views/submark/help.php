<h3>Subnautica Note Maker Documentation</h3>
<p>
        This is a tool for creating notes for your subnautica speedrun.  It uses the markdown language
        that converts text into html with an extension I've written to add graphics from the game.  You can then download that document as a pdf and print it out or keep it as an onscreen reference.
    </p>
    <p>
        <b>Warning: We don't currently save your source document so you are responsible for copying and pasting it into a text file if you want to keep it.  It will not be on the site next time you return.</b>
    </p>
    <p>
        Markdown is documented here:
    </p>
    <ul>
        <li>
            <a href="https://www.markdownguide.org/basic-syntax/" target="_blank">Basic Syntax</a>
        </li>
        <li>
            <a href="https://www.markdownguide.org/extended-syntax/" target="_blank">Extended Syntax</a>
            (We only support tables from the extended syntax currently)
        </li>
        <li>
            <a href="https://www.markdownguide.org/cheat-sheet/" target="_blank">Cheat Sheet</a>
        </li>
    </ul>
    <p>
        The subnautica image extension uses curly braces.  Images can be identified by one of three fields listed in the table at the bottom of this page:  Object number, Description, and Code.  Object number and code can be used in the Subnautica console to spawn objects, so you might already know them.  You can use them interchangeably.  The following all produces an image of metal salvage:
    </p>
    <ul>
        <li>
            {2}
        </li>
        <li>
            {Metal Salvage}
        </li>
        <li>
            {scrapmetal}
        </li>
    </ul>
    <p>
        You can also append a suffix after the image identifier to produce multiple of the same image.  So {scrapmetal:3} will show 3 images of scrap metal.
    </p>
    <h3>Planned Features (please request new features if you think so something)</h3>
    <ul>
        <li>
            Preview auto updates
        </li>
        <li>
            Download result as image
        </li>
        <li>
            Save your document to the site with a custom URL
        </li>
        <li>
            Add an extension to allow you to graphically fill a locker by specifying it's contents
        </li>
    </ul>
    <h3>Image Identifiers</h3>
<div id="submark">
    <table class="helplist">
        <thead>
        <tr>
            <th>Image</th>
            <th>#</th>
            <th>Name</th>
            <th>Code</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($array as $obj) {
            $o = (object) $obj;
            ?>
            <tr>
                <td><?php echo CHtml::image(SubTools::imagePathFromObject($obj),$o->name)?></td>
                <td><?php echo CHtml::value($o,"num")/*." ".$o->width." ".$o->height*/;?></td>
                <td><?php echo $o->name?></td>
                <td><?php echo CHtml::value($o,"code")?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
