<?php
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Block\Element\Paragraph;

class SubmarkController extends Controller
{

    public function convertMarkdown($source)
    {
        spl_autoload_unregister(array('YiiBase','autoload'));
        require Yii::getPathOfAlias('application.vendor').DIRECTORY_SEPARATOR.'autoload.php';
        spl_autoload_register(array('YiiBase','autoload'));
        // Obtain a pre-configured Environment with all the CommonMark parsers/renderers ready-to-go
        $environment = Environment::createCommonMarkEnvironment();
        // Add this extension
        $environment->addExtension(new TableExtension());
        $environment->addDelimiterProcessor(new SubImage());
        $environment->addInlineRenderer(HtmlDump::class, new HtmlDumpRenderer(), 0);

        $environment->addBlockRenderer(Paragraph::class,     new ParagraphOverrideRenderer(),     0);

        $converter = new CommonMarkConverter(['html_input' => 'escape', 'allow_unsafe_links' => false], $environment);
        $html = $converter->convertToHtml($source);

        return $html;
    }

    public function defaultSource()
    {
        return <<<EOL
#### Glitchless Any% Survival

* Make **scanner** {copper} {titanium} {acidmushroom:2}
* Make a seaglide, knife and std tank.  
  {copper:3} {titanium:5} {creepvineseedcluster:2} {acidmushroom:2}
* *Profit* {Purple Tablet:3}

|Step|Result|
|-|-|
|Get fruit {creepvineseedcluster}|Make Lube {lubricant}| 
|Get tooth {stalkertooth}|Make Enamel {enameledglass}| 

Loadout:  
{grid:seaglide,doubletank,quartz:2,titanium:12,cave sulfur,battery,knife,scanner,builder,firstaidkit,table coral,scrapmetal:3}
{grid:seaglide,doubletank,scanner,knife,builder,lasercutter,gold:3,crystalline sulfur:4,aerogel:2,lithium:14,purple tablet,kyanite:4,nickel:3}
EOL;
    }

	public function actionIndex()
	{
	    $source = CHtml::value($_POST, "source",$this->defaultSource());
	    $html="";

	    if ($source)
        {
            $html = $this->convertMarkdown($source);

            $pdf = isset($_POST["pdf"]);
            $png = isset($_POST["png"]);
            if ($pdf || $png)
            {
                $htmlWithShell = $this->renderPartial('pdf', [
                    'html'=>$html,
                ], true);
                $this->sendPDF($htmlWithShell, $png);
                exit;
            }
        }


		$this->render('index', [
		    "source"=>$source,
            "html"=>$html,
        ]);
	}

    public static function fileURL($path)
    {
        $real = realpath($path);
        if ($real)
        {
            $real = str_replace(DIRECTORY_SEPARATOR, "/", $real);
            if (substr($real, 0,1) !== '/')
                $real = '/'.$real;

            return "file://".$real;
        }

        throw new CException("Attempting to create file url from invalid path: ".$path);
    }

    public static function my_exec($cmd, $input='', $cwd=null)
    {
        $runtime = dirname(__DIR__).DIRECTORY_SEPARATOR."runtime";
        $outfile = tempnam($runtime, "cmd");
        $errfile = tempnam($runtime, "cmd");
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("file", $outfile, "w"),
            2 => array("file", $errfile, "w")
        );
        $proc=proc_open($cmd, $descriptorspec, $pipes, $cwd);
        if (is_resource($proc)) {
            fwrite($pipes[0], $input);
            fclose($pipes[0]);
            $rtn=proc_close($proc);
            $stdout = file_get_contents($outfile);
            $stderr = file_get_contents($errfile);
            unlink($outfile);
            unlink($errfile);
            return array('stdout'=>$stdout,
                'stderr'=>$stderr,
                'return'=>$rtn
            );
        }

        return array('stdout'=>'', 'stderr'=>'', 'return'=>-1 );
    }

    public static function streamFile($file, $name, $attachment = true, $content_type = 'application/pdf')
    {
        $type = $attachment? "attachment" : "inline";
        header('Pragma:');
        header('Cache-Control: private,no-cache');
        header("Content-Type: $content_type");
        header('Content-Disposition: '.$type.'; filename="'.str_replace('"','\\"',$name).'"');
        readfile($file);
    }

    public function sendPDF($html, $png = false)
    {
        $html = str_replace('/images','images', $html);
        $runtime = dirname(__DIR__).DIRECTORY_SEPARATOR."runtime";
        $finalFile = $pdfFile = tempnam($runtime, "pdf");
        $htmlFile = tempnam($runtime, "html");
        file_put_contents($htmlFile, $html);

        $baseurl = self::fileURL("")."/";

        // Get a file path to the public directory
        $weasyprint = CHtml::value(Yii::app()->params,"weasyprint","weasyprint");
//        $format = $png ? "png -r 192" : "pdf";
        $cmd = "$weasyprint -u $baseurl -e utf8 -v $htmlFile $pdfFile";
        $results = self::my_exec($cmd);
        unlink($htmlFile);
        if ($results['return'] != 0)
            throw new CException($results['stderr']."\n".$results['stdout']);

        // write results to log file for testing
        file_put_contents("protected/runtime/weasy.log", print_r($results, true),FILE_APPEND | LOCK_EX );

        if (file_exists($pdfFile) && $png)
        {
            $finalFile = $pngFile = "{$pdfFile}-1.png";
            $pdftoppm = CHtml::value(Yii::app()->params,"pdftoppm","pdftoppm");
            $cmd = "$pdftoppm -png $pdfFile $pdfFile";
            $results = self::my_exec($cmd);
            unlink($pdfFile);
            if ($results['return'] != 0)
                throw new CException($results['stderr']."\n".$results['stdout']);
            // write results to log file for testing
            file_put_contents("protected/runtime/weasy.log", print_r($results, true),FILE_APPEND | LOCK_EX );

            // delete any extra pages
            foreach (range(2,1000) as $page)
            {
                $extra = "{$pdfFile}-$page.png";
                if (file_exists($extra))
                    unlink($extra);
                else
                    break;
            }
        }

        if (file_exists($finalFile))
        {
            $mime = $png ? "image/png" : "application/pdf";
            $name = "SubnauticaNotes.".($png? "png":"pdf");
            self::streamFile($finalFile, $name, true, $mime);
           unlink($finalFile);
        }

    }

    // Uncomment the following methods and override them if needed
	/*
	public function filters()
	{
		// return the filter configuration for this controller, e.g.:
		return array(
			'inlineFilterName',
			array(
				'class'=>'path.to.FilterClass',
				'propertyName'=>'propertyValue',
			),
		);
	}

	public function actions()
	{
		// return external action classes, e.g.:
		return array(
			'action1'=>'path.to.ActionClass',
			'action2'=>array(
				'class'=>'path.to.AnotherActionClass',
				'propertyName'=>'propertyValue',
			),
		);
	}
	*/

    public function actionHelp()
    {
        $array = SubTools::loadImageData();
        usort($array, function($a, $b) {
            $numa = CHtml::value($a,"num", 10000);
            $numb = CHtml::value($b,"num", 10000);
            return $numa-$numb;
        });
        $this->render("help", [
            "array"=>$array,
        ]);
    }

    public function actionTest()
    {
        $test = "tank,seaglide,scrapmetal:3,quartz:2,titanium:12,cave sulfur,battery,knife,scanner,builder,firstaidkit,table coral";
        $html =  SubTools::inventory(6,8,$test);
        $this->render("test",[
            "src"=>$test,
            "html"=>$html,
        ]);
    }


    public function actionMarkdown()
    {
        $source = CHtml::value($_POST, "source","");
        $html = $this->convertMarkdown($source);
        echo $html;
        return false;
    }
}