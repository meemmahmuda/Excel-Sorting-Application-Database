<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$pdo = new PDO("mysql:host=localhost;dbname=excel_sort","root","");

function loadData($content,$ext){
    $tmp=tempnam(sys_get_temp_dir(),'xls');
    file_put_contents($tmp,$content);
    if($ext=='csv'){
        $fp=fopen($tmp,'r'); $h=fgetcsv($fp);
        $d=[]; while($r=fgetcsv($fp)) $d[]=array_combine($h,$r); fclose($fp);
    } else {
        $rows=IOFactory::load($tmp)->getActiveSheet()->toArray();
        $h=array_shift($rows);
        $d=array_map(fn($r)=>array_combine($h,$r),$rows);
    }
    unlink($tmp); return $d;
}

$f1=$_POST['f1']; $f2=$_POST['f2'];
$c1=$_POST['c1']; $c2=$_POST['c2'];

$d1=loadData($pdo->query("SELECT file_content FROM excel_files WHERE filename='$f1'")->fetchColumn(), pathinfo($f1,PATHINFO_EXTENSION));
$d2=loadData($pdo->query("SELECT file_content FROM excel_files WHERE filename='$f2'")->fetchColumn(), pathinfo($f2,PATHINFO_EXTENSION));

$v1=array_column($d1,$c1); $v2=array_column($d2,$c2);

$out=[];
foreach($d1 as $r) if(!in_array($r[$c1],$v2)) $out[]=array_merge($r,['','']);
foreach($d2 as $r) if(!in_array($r[$c2],$v1)) $out[]=array_merge(array_fill(0,count($d1[0])+2,''),$r);

if($out){
    $s=new Spreadsheet(); $sh=$s->getActiveSheet();
    $sh->fromArray(array_merge(array_keys($d1[0]),['',''],array_keys($d2[0])),NULL,'A1');
    $sh->fromArray($out,NULL,'A2');

    $tmp=tempnam(sys_get_temp_dir(),'xls');
    IOFactory::createWriter($s,'Xlsx')->save($tmp);
    $content=file_get_contents($tmp); unlink($tmp);

    $outname="unmatched_".time().".xlsx";
    $pdo->prepare("INSERT INTO excel_files(filename,file_content,type) VALUES(?,?,?)")->execute([$outname,$content,'unmatched']);

    echo "<a href='download.php?file=$outname'>Download Unmatched</a>";
} else echo "No unmatched!";
