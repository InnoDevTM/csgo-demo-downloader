<?php
header('Content-type: text/html; charset=utf-8');
define('GUARD', true);
include 'inc/config.php';

try {
	include 'inc/function_zipcompressfile.php';

	for($i=1; $i<=sizeof($server); $i++) 
	{
		$handle = @opendir($server[$i]['patch']);
		if ($handle)
		{
			if(!file_exists('files/server_'.$i.'/')) mkdir('files/server_'.$i.'/', 0777);
		$etime = 180; //проверка времени изменения файла-не закачивать текущую демку
				while (false !== ($files = readdir($handle)))
				if (preg_match("/(.*).dem/", $files) ) {

		$stime=time();
                $ftime = filemtime($server[$i]['patch'].$files);
		$time=$stime-$ftime;
		if ($time>$etime){
					zipcompressfile($server[$i]['patch'], 'files/server_'.$i.'/', $files);
					unlink($server[$i]['patch'].$files);
					}
		}
			closedir($handle);
			//echo 'Сервер #'.$i.' - '.$files.' демо.<br>';
		} else {
			throw new Exception('Папка "'.$server[$i]['patch'].'" не найдена');
		}
	
		for($n=1; $n<=sizeof($server); $n++) 
		{
			$handle = @opendir('files/server_'.$n.'/');
			if ($handle)
			{	
				//время в секундах- 259200 - 3 дня- срок хранения демо. все, что старше- удаляется
				$etime = 259200;
					while (false !== ($files = readdir($handle)))
					if (preg_match("/(.*).dem/", $files) ) 
				{

					$stime=time();
						$ftime = filemtime('files/server_'.$n.'/'.$files);
						$time=$stime-$ftime;
						if ($time>$etime)
						{
							unlink('files/server_'.$n.'/'.$files);
						}
				}
			closedir($handle);
			}
		}
		echo 'Старые файлы удалены<br>';
	}

} catch (Exception $e) { 
	$error = $e->getMessage();	
	
	$html = file_get_contents('template/error.tpl');
	$html = str_replace('{title}', 'Ошибка', $html);
	$html = str_replace('{error}', $error, $html);
    echo $html;
}
?>