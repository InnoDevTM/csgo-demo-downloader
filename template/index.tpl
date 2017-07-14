<!DOCTYPE html>
<html>
<head>
    <title>{index_title}</title>
	<meta charset="utf-8">
	<link rel="stylesheet" href="{url}template/css/style.css">
</head>

<body>

<header><img src="http://entra.xban.info/demo/entra-demo.png"></header>
<table class="bordered">
    <thead>
    <tr>
        <th width="10px">№</th>        
        <th width="200px">Название сервера</th>        
        <th>Карта</th>
        <th>IP:Порт</th>
        <th width="150px">Игроки</th>
        <th width="70px">Демок</th>
    </tr>
    </thead>
	{block.start}
    <tr>
        <td>{content_id}</td>        
        <td><a href="server.php?id={content_id}"class="a_button">{content_name}</a></td>        
        <td>{content_map}</td>
        <td>{content_host}</td>
        <td>{content_players}</td>
        <td>{content_demos}</td>
    </tr>     
	{block.end}
</table>
</body>
</html>
