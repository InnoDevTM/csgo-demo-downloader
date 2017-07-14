<!DOCTYPE html>
<html>
<head>
    <title>{index_title}</title>
	<meta charset="utf-8">
	<link rel="stylesheet" href="{url}template/css/style.css">
</head>

<body>

<header><img src="{url}images/logo.png"><br><header>


<table class="bordered">
    <thead>
    <tr>     
        <th width="">{server_name}</th>        
        <th width="">Адрес: {server_host}</th>        
        <th>Карта: {server_map}</th>
        <th width="150px">Игроки: {server_players}</th>
    </tr>
    </thead>
</table>

<div style="padding:10px"></div>

<table class="bordered">
    <thead>
    <tr>
        <th width="10px">№</th>        
        <th width="350px">Название файла</th>        
        <th>Карта</th>
        <th width="110px">Дата</th>
        <th width="70px">Демо</th>
    </tr>
    </thead>
	{block.start}
    <tr>
        <td>{content_id}</td>        
        <td>{content_name}</td>        
        <td>{content_map}</td>
        <td>{content_date}</td>
        <td>{content_download}</td>
    </tr>     
	{block.end}
</table>

</body>
</html>
