<?php
if (!defined("GUARD")) { header('Content-type: text/html; charset=utf-8'); die('Доступ запрещен!'); } // Защита файлов от прямого вызова

class Template {
    static $dir = '.';
	static $compiler = array();
	static $tmp = array();

	static function template_load($name) { // Функция подключения главного шаблона
        if ($name == '' || !file_exists(self::$dir . DIRECTORY_SEPARATOR . $name)) {
			header('Content-type: text/html; charset=utf-8');
			die ("Ошибка загрузки шаблона: ". $name); 
			return false;
		}
        self::$compiler = file_get_contents(self::$dir . DIRECTORY_SEPARATOR . $name);
        return self::$compiler;
    }
	
	static function subtemplate_load($find,$name) { // Функция подключения подшаблонов
        if ($name == '' || !file_exists(self::$dir . DIRECTORY_SEPARATOR . $name)) {
			header('Content-type: text/html; charset=utf-8');
			die ("Ошибка загрузки шаблона: ". $name); 
			return false;
		}
		self::$compiler = str_replace($find, file_get_contents(self::$dir . DIRECTORY_SEPARATOR . $name), self::$compiler);
        return self::$compiler;
    }
	
	static function block($array) {
		$block_start = strpos(self::$compiler, "{block.start}");
		$block_end = strpos(self::$compiler, "{block.end}");
		$content_main = substr(self::$compiler, $block_start+13, $block_end-$block_start-13);
		$content = '';
		foreach($array as $id => $tags)
		{
			$content_tmp = $content_main;
			foreach($tags as $tag_name=>$tag_value) 
			{
				$content_tmp = str_replace($tag_name, $tag_value, $content_tmp);
			}
			$content .= $content_tmp;
		}
		self::$compiler = preg_replace('|{block.start}.*{block.end}|Usi', $content, self::$compiler);
		return self::$compiler;
	}
	
	static function tag($find, $replace) { // Функция создания тегов
		self::$compiler = str_replace($find, $replace, self::$compiler);
		return self::$compiler;
	}
}
?>