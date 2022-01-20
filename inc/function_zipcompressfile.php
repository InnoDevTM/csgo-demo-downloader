<?php
if (!defined("GUARD")) { header('Content-type: text/html; charset=utf-8'); die('Доступ запрещен!'); } // Защита файлов от прямого вызова

function zipcompressfile($source,$destination,$filename,$level=false)
{
	$dest=$destination.$filename.'.zip';
	$mode='wb'.$level;
	$error=false;
	//echo $source.$filename.' -> '.$dest.'<br>';
	if($fp_out=gzopen($dest,$mode))
	{
		if($fp_in=fopen($source.$filename,'rb'))
		{
			while(!feof($fp_in))
				gzwrite($fp_out,fread($fp_in,1024*512));
			fclose($fp_in);
		}
		else
			$error=true;
		gzclose($fp_out);
	}
	else $error=true;
	if($error) return false;
	else return $dest;
}
?>
