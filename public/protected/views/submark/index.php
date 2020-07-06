<?php
/* @var $this SubmarkController */

$this->breadcrumbs=array(
	'Subnautica Note Maker',
);
?>
<h1>Subnautica Note Maker</h1>
<div class="clearfix">
    <div class="half1">
        <form method="post">
            <?php echo CHtml::textArea("source", $source, ["cols"=>60, "rows"=>35]);?>
            <?php echo CHtml::submitButton("Preview"); ?>
            <?php echo CHtml::submitButton("Download PDF",["name"=>"pdf"]); ?>
        </form>
    </div>
    <div class="half2">
        <div id="submark">
            <?php echo $html;?>
        </div>
    </div>
</div>
