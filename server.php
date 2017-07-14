<?php
define('GUARD', true);
include 'inc/config.php';

$url = $_SERVER['SCRIPT_NAME'];
$url_array = explode('/', $_SERVER['SCRIPT_NAME']);
unset($url_array[count($url_array)-1]);
$url = implode('/', $url_array);
$url = $url.'/';

try {
	include 'inc/class_Template.php';
	include 'inc/function_protocol.php';
	Template::template_load('template/server.tpl');

	// Контент
	if(!isset($_GET['id'])) throw new Exception('Не введен ID сервера');
	if(isset($_GET['id']) and !isset($server[$_GET['id']])) throw new Exception('Сервер "'.$_GET['id'].'" не найден');
	$id = $_GET['id'];
	if ($handle = opendir('files/server_'.$_GET['id'])) {
		$i = 0;
		$content = array();
		$files = array();
		while (($file = readdir($handle)) !== false) {
			if($file == '.' or $file == '..') continue;
			$files[] = $file;
		}
		rsort($files);
		foreach ($files as $file) {
			if($file == '.' or $file == '..') continue;
			$data = explode("-", $file);
			$map = explode(".", $data[4]);		// Здесь карту мы узнаем из собственно названия демки
			 /*
    		Вот здесь начинается самое интерестное, так как я не смог добиться отображение даты и времени корректно, через функцию filectime
			сделал костыль, беру просто из названия демки, разбиваю название через функцию explode и просто достаю тот массив который мне нужно
 			*/
			$date = $data[1];					// Достаю массив с датой
			$year = substr($date, 0, 4);		// Беру первые 4 числа, это будет год
			$month = substr($date, 4, 2);		// Беру последующие 2 числа после 4х первых, это месяц
			$day = substr($date, 6, 2);			// Беру остальные 2 числа после 6ти первых, это день
			$time = $data[2];					// Достаю массив с временем, он выглядит примерно так 010514
			$hours = substr($time, 0, 2); 		// Обрезаю первые два символа, это будет у нас часы
			$minutes = substr($time, 2, 2); 	// Обрезаю следуюшие 2 символа, это будет у нас минуты
			$content[] = array(
				'{content_id}'=>++$i,
				'{content_name}'=>$file, 
				'{content_map}'=>$map[0],
				'{content_date}'=>$year . "." . $month . "." . $day . " " . $hours . ":" . $minutes,
				'{content_download}'=>'<a href="{url}files/server_'.$_GET['id'].'/'.$file.'" class="a_button">Скачать</a>
			');
		}
		if(count($content)<1) 
			$content[] = array(
				'{content_id}'=>1, 
				'{content_name}'=>'Файлов нет', 
				'{content_map}'=>'',
				'{content_date}'=>'', 
				'{content_download}'=>''
			);
	}
	// Конец контента
	$data = lgsl_query_live('source' , $server[$_GET['id']]['ip'], $server[$_GET['id']]['port'], $server[$_GET['id']]['port'], $server[$_GET['id']]['port'], 's');
	Template::tag('{server_name}', $data['s']['name']);
	Template::tag('{server_host}', $server[$_GET['id']]['ip'].':'.$server[$_GET['id']]['port']);
	Template::tag('{server_map}', $data['s']['map']);
	Template::tag('{server_players}', $data['s']['players'].'/'.$data['s']['playersmax']);

	Template::block($content);
	Template::tag('{index_title}', $main['name']);
	Template::tag('{url}', $url);

	echo Template::$compiler;
} catch (Exception $e) { 
	$html = file_get_contents('template/error.tpl');
	$html = str_replace('{title}', 'Ошибка', $html);
	$html = str_replace('{error}', $e->getMessage(), $html);
    echo $html;
}
?>