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
	Template::template_load('template/index.tpl');

	$content = array();
	// Контент
	for($i=1; $i<=sizeof($server); $i++) {
		$data = lgsl_query_live('source' , $server[$i]['ip'], $server[$i]['port'], $server[$i]['port'], $server[$i]['port'], 's');
		$content[] = array(
			'{content_id}'=>$i, 
			'{content_name}'=>$data['s']['name'], 
			'{content_map}'=>$data['s']['map'], 
			'{content_host}'=>$server[$i]['ip'].':'.$server[$i]['port'], 
			'{content_players}'=>$data['s']['players'].'/'.$data['s']['playersmax'], 
			'{content_demos}'=>count(array_filter(glob('files/server_'.$i.'/*'), 'is_file'))
		);
	}
	// Конец контента
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