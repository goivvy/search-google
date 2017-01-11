<?php
ini_set('display_errors', 0); 
require_once ('src/jpgraph.php');
require_once ('src/jpgraph_line.php');
require_once ('src/jpgraph_scatter.php');
require_once ('src/jpgraph_utils.inc.php');

$db = new PDO('mysql:host=localhost;dbname=seo','seo_user','seo_pass');

$sth = $db->query("SELECT * from search where paint > 0 order by query,position");

$data = array();

$reg = array();
foreach($sth as $row){

 $data[$row['query']]['x'][] = $row['position'];
 $data[$row['query']]['y'][] = log($row['paint']);
 
 $reg['x'][] = $row['position'];
 $reg['y'][] = log($row['paint']);

}

$graph = new Graph(1200,600);
$graph->SetScale("lin");

$theme_class=new UniversalTheme;

$graph->SetTheme($theme_class);
$graph->img->SetAntiAliasing(false);
$graph->SetBox(false);

$graph->img->SetAntiAliasing();

$graph->yaxis->HideZeroLabel();
$graph->yaxis->HideLine(false);
$graph->yaxis->HideTicks(false,false);
$graph->yaxis->title->Set('Logarithm of Start Render Time');
$graph->xaxis->title->Set('Google Search Result Position');

$graph->xgrid->Show();
$graph->xgrid->SetLineStyle("solid");
#$graph->xaxis->SetTickLabels(array('A','B','C','D'));
$graph->xgrid->SetColor('#E3E3E3');

$test = 0;
foreach($data as $q => $rr){
$p = new ScatterPlot($rr['y'],$rr['x']);
$graph->Add($p);
$p->mark->SetWidth(0.7);
if($test++ == 100) break;
}

$p->setLegend('URL start render time and position');

$lr = new LinearRegression($reg['x'],$reg['y']);
list($xx,$yy) = $lr->GetY(0,50);
list( $stderr, $corr ) = $lr->GetStat();
$pp = new LinePlot($yy,$xx);
$pp->setLegend('Regression Line');
$graph->Add($pp);
$pp->setColor("#000000");
$pp->setStyle('solid');
$pp->setWeight(20);

$graph->legend->SetFrameWeight(1);
$slope = round( ($yy[50] - $yy[1])/50, 4);
$graph->title->set('Start Render Time vs Google Search Position. Regression Line Slope = '.$slope);

$graph->Stroke();
