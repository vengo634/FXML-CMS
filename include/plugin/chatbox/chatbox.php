<?php
if(!defined('XMLCMS')) exit();

$MyRoles=getArrayAvailableUserRoles();
$AllowRead=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowread"]));
$AllowWrite=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowwrite"]));
$AllowDelete=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowdelete"]));
$divpc="Чат<br>$_GET[text]<br>"; 
if($AllowWrite&&isset($_GET["room"])){
	if($_GET["cmd"]=="send"){
		if($_GET["opt"]=="delete"){
			if($AllowDelete) { 
				$div="";
				$ch=getPluginMetaKey("messages$_GET[room]",true,$_GET["lid"]);
				if(isset($ch["id"])){
					if(deletePluginMetaKey("messages$_GET[room]",$_GET["lid"])) $div.="Удалено ".$ch["src"]["title"]." от ".($ch["src"]["login"]!=null?$ch["src"]["login"]:$ch["src"]["ip"])."!<br>";
					else $div.="Ошибка удаления сообщения #$_GET[lid]!<br>";
				}
				else  $div.="Cообщение #$_GET[lid] не найдено!<br>";
			}
			else $div.="У вас недостаточно прав для удаления ссылок. Авторизуйтесь или обратитесь к администратору сайта!<br>";
			$Channels[]=["location"=>1,"logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"Вернуться в чат","playlist_url"=>"$PLUGIN[link]&room=$_GET[room]&text=$div"];
		}
		elseif(empty($_GET["lid"])){			
			$link=getPluginMetaKey("messages$_GET[room]",true);
			$ch=$link[0]["src"];
			
			if($ch["login"]==$userinfo["login"]&&$ch["ip"]==$_SERVER["REMOTE_ADDR"]) {
				$ch["title"]=$_GET["search"]."<br><i><small>modified ".date("d.m.Y H:i")."</small></i>";
				savePluginMetaKey("messages$_GET[room]",json_encode($ch),$link[0]["id"]);
			}
			else{
				$ch=["logo_30x30"=>"","title"=>$_GET["search"],"playlist_url"=>"","date"=>time(),"login"=>$userinfo["login"],"ip"=>$_SERVER["REMOTE_ADDR"],"initial"=>$_GET["initial"],"views"=>0,"reports"=>[]];
				savePluginMetaKey("messages$_GET[room]",json_encode($ch));
			}
			$Channels[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"Вернуться в чат","playlist_url"=>"$PLUGIN[link]&room=$_GET[room]"];
			
		}
		else{
			$link=getPluginMetaKey("messages$_GET[room]",true,$_GET["lid"]);
			$ch=$link["src"];
			if($ch["reports"][0]["login"]==$userinfo["login"]&&$ch["reports"][0]["ip"]==$_SERVER["REMOTE_ADDR"]) $ch["reports"][0]["title"]=$_GET["search"]."<br><i>modified</i>";
			else array_unshift($ch["reports"],["title"=>$_GET["search"],"date"=>time(),"login"=>$userinfo["login"],"ip"=>$_SERVER["REMOTE_ADDR"],"initial"=>$_GET["initial"]]);
			savePluginMetaKey("messages$_GET[room]",json_encode($ch),$link["id"]);
			$divpc.="<br>";
			$Channels[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"Вернуться в чат","playlist_url"=>"$PLUGIN[link]&room=$_GET[room]"];
		
		}
	}
	
	$Channels[]=["title"=>(empty($_GET["lid"])?"Добавить сообщение":"Написать ответ"),"playlist_url"=>"$PLUGIN[link]&room=$_GET[room]&lid=$_GET[lid]&cmd=send","description"=>"","search_on"=>"Введите текст"]; 	
}

if($AllowRead){
	if(!isset($_GET["room"])){
		foreach($PLUGIN["settings"]["rooms"] as $k=>$v){
			if($v["enable"]) $Channels[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"$v[name]","playlist_url"=>"$PLUGIN[link]&room=$k","description"=>"$v[description]"]; 	
		}
		if(count($Channels)==0) $Channels[]=["logo_30x30"=>"Не заданы rooms в settings плагина chatbox","title"=>"","playlist_url"=>"","description"=>""]; 
	}
	else{
		$TITLE=$PLUGIN["settings"]["rooms"][$_GET["room"]]["name"]." ".$TITLE;
		$divpc.="<b>".$PLUGIN["settings"]["rooms"][$_GET["room"]]["name"]."</b><br>";
		if(!empty($_GET["lid"])){
			$Channels[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"Вернуться в чат","playlist_url"=>"$PLUGIN[link]&room=$_GET[room]"];
			
			$messages=getPluginMetaKey("messages$_GET[room]",true,$_GET["lid"]);
			$divpc.=($messages["src"]["login"]!=null?$messages["src"]["login"]:$messages["src"]["ip"])." пишет ".date("d.m.Y H:i",$messages["src"]["date"])."</i>
			<br>".$messages["src"]["title"]."<br>";
			foreach($messages["src"]["reports"] as $k=>$v){
				$Channels[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"<i>".($v["login"]!=null?$v["login"]:$v["ip"])." пишет</i> ".(!$_ISPC?$v["title"]:""),"playlist_url"=>"","description"=>(!$_ISPC?"Нажмите чтобы ответить<br>":"")."<span style='font-size:85%;color:gray;'>".date("d.m.Y H:i",$v["date"])."</span><br>".$v["title"]."<br>"]; 
			}
		}
		else{
			$Channels[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"Вернуться к выбору чата","playlist_url"=>"$PLUGIN[link]"];
			
			$messages=getPluginMetaKey("messages$_GET[room]",true);
			foreach($messages as $k=>$v){
				foreach($v["src"]["reports"] as $kk=>$vv) $reports.="<div style='margin:2px;font-size:90%;background-color:gray;color:white;'>".date("d.m.Y H:i",$vv["date"])."<br><i>".($vv["login"]!=null?$vv["login"]:$vv["ip"])." ответил</i><br> $vv[title]</div>";
				$menu=[];
				if($AllowDelete||$v["uid"]==$userinfo["id"]){
					if($_ISPC) $menu[]=["logo_30x30"=>"$siteurl/include/templates/images/delete.png","title"=>"Удалить","playlist_url"=>"javascript:ConfirmMessage('Вы уверены что хотите удалить?',function(){OpenUrl('$PLUGIN[link]&room=$_GET[room]&cmd=send&opt=delete&lid=".$v["id"]."')});"];
					else $links[$i]["src"]["menu"][]=["logo_30x30"=>"$siteurl/include/templates/images/delete.png","title"=>"Удалить","description"=>"Вы уверены что хотите удалить?","playlist_url"=>"confirm","confirm"=>["$PLUGIN[link]&room=$_GET[room]&cmd=send&opt=delete&lid=".$v["id"],""]];
				}
				$menu[]=["title"=>"Ответить","playlist_url"=>"$PLUGIN[link]&room=$_GET[room]&lid=$v[id]"];
				
				$Channels[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"<i>".($v["src"]["login"]!=null?$v["src"]["login"]:$v["src"]["ip"])." пишет</i> ".(!$_ISPC?$v["src"]["title"]:""),"playlist_url"=>"$PLUGIN[link]&room=$_GET[room]&lid=$v[id]","description"=>(!$_ISPC?"Нажмите чтобы ответить<br>":"")."<span style='font-size:85%;color:gray;'>".date("d.m.Y H:i",$v["src"]["date"])."</span><br>".$v["src"]["title"]."<br>$reports","menu"=>$menu]; 	
			}
		}
		
	}
	
}
