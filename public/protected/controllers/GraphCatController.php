<?php
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Block\Element\Paragraph;

class GraphCatController extends Controller
{
    public function actionIndex()
    {
        $this->render("index");
    }
}