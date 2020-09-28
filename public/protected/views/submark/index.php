<?php
/* @var $this SubmarkController */
Yii::app()->clientScript->registerScriptFile('/js/lodash.min.js');
Yii::app()->clientScript->registerScriptFile('/js/submark.js', CClientScript::POS_END);



$this->breadcrumbs=array(
	'Subnautica Note Maker',
);
?>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
        crossorigin="anonymous"></script>


<h1>Subnautica Note Maker <?php echo CHtml::link("Help Screen", ["help"],["id"=>"helplink","target"=>"_blank"])?></h1>
<div class="clearfix">
    <div class="half1">
        Your source text:
        <form method="post">
            <?php echo CHtml::textArea("source", $source, ["cols"=>60, "rows"=>30]);?>
            <?php //echo CHtml::submitButton("Preview"); ?>
            <?php echo CHtml::submitButton("Download PDF",["name"=>"pdf"]); ?>
            <?php echo CHtml::submitButton("Download PNG Image",["name"=>"png"]); ?>
        </form>
    </div>
    <div class="half2">
        Preview:
        <div id="submark">
            <?php echo $html;?>
        </div>
    </div>
</div>
