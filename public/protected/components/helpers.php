<?php

function yiiparam($name, $default = null)
{
    return CHtml::value(Yii::app()->params, $name, $default);
}
