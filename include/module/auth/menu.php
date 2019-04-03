<?php

if(!$logged) $_MENU[]=["title"=>"Авторизация","playlist_url"=>"$siteurl/?do=/module&id=auth&act=login"];
else $_MENU[]=["logo_30x30"=>"$siteurl/include/templates/images/user.png","title"=>$userinfo["login"],"playlist_url"=>"submenu","submenu"=>[["title"=>"Профиль","playlist_url"=>"$siteurl/?do=/module&id=auth&act=profile"],["title"=>"Выйти","playlist_url"=>"$siteurl/?do=/module&id=auth&act=logout"]]];