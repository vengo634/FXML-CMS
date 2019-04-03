<?php

if($_GET["mode"]=="vastevent"){
	$d=date("Ymd");
	if($_GET["event"]=="skip"||$_GET["event"]=="complete"){
		if($_GET["mid"]==substr(md5($secret.$ip.$_GET["tm"]),0,10)){
			$stat=getPluginMetaKey("rekstat$_GET[adid]_$d",false);
			$stat=$stat[0];
			print_r($stat);
			if(strpos($stat["src"],$_GET["mid"])===false) {				
				if(strpos($stat["src"],$ip)===false) {
					$stat["src"].="$ip;";
				}
				$stat["src"].="$_GET[mid];";
				$stat["inc"]++;
				savePluginMetaKey("rekstat$_GET[adid]_$d",$stat["src"],$stat["id"],$stat["inc"]);
			}
			else header("errorstat: retry m");
		}
		else header("errorstat: no valid tm");
	}
	exit;
}
elseif($_GET["mode"]=="vast"){
	$links=getPluginMetaKey("rek",true);
	$maxRoll=0;
	foreach($links as $k=>$v){
		if($v["src"]["work"]&&(empty($COUNTRY)||empty($v["src"]["country"])||strpos($v["src"]["country"],$COUNTRY)!==false)
			&&(empty($v["src"]["roles"])||!is_access(getArrayAvailableUserRoles(),explode(",",$v["src"]["roles"])))) {
			$priotity=ceil(abs($v["src"]["priority"]));
			if($priotity<1) $priotity=1;
			$advert[]=["id"=>$v["id"],"x1"=>$maxRoll,"x2"=>($maxRoll+$priotity),"title"=>$v["src"]["title"],"country"=>$COUNTRY,"access"=>$v["src"]["country"],"stream_url"=>$v["src"]["stream_url"],"advertlink"=>$v["src"]["advertlink"],"duration"=>$v["src"]["duration"],"skip"=>$v["src"]["skip"]];
			$maxRoll+=$priotity;
		}
	}
		
	if(count($advert)){
		$rand=rand(0,$maxRoll);
		foreach($advert as $k=>$v){
			if($rand<=$v["x2"]&&$rand>=$v["x1"]) break;
		}
		$arr=[];
		
		if(!empty($v["duration"])&&$v["duration"]>0){
			if($v["duration"]>600) $v["duration"]=600;
			$arr["Ad"]["InLine"]["Creatives"]["Creative"]["Linear"]["Duration"]=sec2hms($v["duration"]);
		}
		if($v["skip"]>5) $v["skip"]=5;
		elseif($v["skip"]<0) $v["skip"]=0;
		
		$d=date("Ymd");
		$stat=getPluginMetaKey("rekget$v[id]_$d",false);
		$stat=$stat[0];
		$m=substr(md5($secret.$ip.time()),0,10);
		$mid=$m."&tm=".time();
		if(preg_match("/\.(jpg|jpeg|gif|png)/i",$v["stream_url"])) {			
			$type="image/png";
		}
		else $type="video/mp4";
			//"complete"=>["@cdata"=>"$PLUGIN[link]&mode=vastevent&adid=$v[id]&event=complete"],			"skip"=>["@cdata"=>"$PLUGIN[link]&mode=vastevent&adid=$v[id]&event=skip"]
		$arr["Ad"]["InLine"]["Creatives"]["Creative"]["Linear"]["TrackingEvents"]["Tracking"][]=["@cdata"=>
			"$PLUGIN[link]&mode=vastevent&adid=$v[id]&event=start&mid=$mid","@attributes"=>["event"=>"start"]];
		$arr["Ad"]["InLine"]["Creatives"]["Creative"]["Linear"]["TrackingEvents"]["Tracking"][]=["@cdata"=>
			"$PLUGIN[link]&mode=vastevent&adid=$v[id]&event=complete&mid=$mid","@attributes"=>["event"=>"complete"]];
		$arr["Ad"]["InLine"]["Creatives"]["Creative"]["Linear"]["TrackingEvents"]["Tracking"][]=["@cdata"=>
			"$PLUGIN[link]&mode=vastevent&adid=$v[id]&event=skip&mid=$mid","@attributes"=>["event"=>"skip"]];
			
		$arr["Ad"]["InLine"]["Creatives"]["Creative"]["Linear"]["MediaFiles"]["MediaFile"]=["@cdata"=>$v["stream_url"],"@attributes"=>["type"=>"$type"]];
		if(!empty($v["advertlink"])) $arr["Ad"]["InLine"]["Creatives"]["Creative"]["Linear"]["VideoClicks"]["ClickThrough"]=["@cdata"=>$v["advertlink"]];
		
		$arr["Ad"]["InLine"]["Extensions"]["Extension"][]=["@cdata"=>sec2hms($v["skip"]),"@attributes"=>["type"=>"skipTime"]];
		$arr["Ad"]["InLine"]["Extensions"]["Extension"][]=["@cdata"=>"$v[title]","@attributes"=>["type"=>"linkTxt"]];
		//print_r($v);
		print createvast($arr);
		
		$stat["inc"]++;
		$stat["src"].="$ip;$m;";
		savePluginMetaKey("rekget$v[id]_$d",$stat["src"],$stat["id"],$stat["inc"]);
	}
	else print "<noad></noad>";
	exit;
}
include dirname(__FILE__)."/include.php";
$div.="<script src='$siteurl/include/plugin/$PLUGIN[id]/page.js'></script>\n";
$div.="Управление рекламой<br>
<i>Реклама будет показываться только перед запуском контента (видео, изображение, текст) со страниц <a href='/?do=/admin&act=addpage'>размещенных на этом сайте</a></i><br>";

if($AllowRead&&empty($_GET["lid"])){
	$links=getPluginMetaKey("rek",true);
	foreach($links as $k=>$v){
		$menu=[];
		if($AllowDelete) $menu[]=["logo_30x30"=>"$siteurl/include/templates/images/delete.png","title"=>"Удалить","playlist_url"=>"javascript:ConfirmMessage('Вы уверены что хотите удалить?',function(){OpenUrl('$PLUGIN[link]&mode=addurl&opt=delete&lid=".$v["id"]."')});"];
		 
		$stat_all=getPluginMetaKey("rekget$v[id]_%",false);
		$all_get=0;
		foreach($stat_all as $kk=>$vv) $all_get+=$vv["inc"];		
		$stat_today=getPluginMetaKey("rekget$v[id]_".date("Ymd"),false);
		
		$stat_all=getPluginMetaKey("rekstat$v[id]_%",false);
		$all_compl=0;
		foreach($stat_all as $kk=>$vv) $all_compl+=$vv["inc"];		
		$compl_today=getPluginMetaKey("rekstat$v[id]_".date("Ymd"),false);
		
		$Channels[]=["logo_30x30"=>"","title"=>$v["src"]["title"]." ".date("d.m.Y H:i",$v["src"]["date"])." ".($v["src"]["work"]?"Запущена":"Остановлена")." [".$v["src"]["priority"]."] Страны: ".$v["src"]["country"]." Роли откл.: ".$v["src"]["roles"],"playlist_url"=>"$PLUGIN[link]&mode=addurl&lid=$v[id]","infolink"=>$v["src"]["stream_url"]."<br>".$v["src"]["advertlink"],"description"=>"Получено всего: $all_get<br>Получено сегодня: ".$stat_today[0]["inc"]."<br>Просмотров всего: $all_compl<br>Просмотров сегодня: ".$compl_today[0]["inc"]."<br>","menu"=>$menu]; 
	}
}

if($AllowWrite){
		if($_GET["mode"]=="addurl"){
			if(!empty($_GET["lid"])) $link=getPluginMetaKey("rek",true,$_GET["lid"]);
			if($_GET["opt"]=="delete"){
				if($AllowDelete) {
					if(deletePluginMetaKey("rek",$_GET["lid"])) $div.="Удалено!<br>";
					else $div.="Ошибка удаления ссылки #$_GET[lid]!<br>";
				}
				else $div.="У вас недостаточно прав для удаления ссылок. Авторизуйтесь или обратитесь к администратору сайта!<br>";
				$Channels[]=["title"=>"Вернутья к списку","playlist_url"=>"$PLUGIN[link]","description"=>""];
			}
			elseif($_GET["upload"]=="file"){
				if(preg_match("/\.(jpg|png|gif|jpeg|mp4|ts|mpeg|mkv)$/i",$_FILES["file"]["name"])){
					if($logged) $dir="uploads/plugin/$PLUGIN[id]/$userinfo[id]";
					else $dir="uploads/plugin/$PLUGIN[id]/$_SERVER[REMOTE_ADDR]";
					if(!file_exists($dir)) mkdir($dir,0777,true);
					$path="$dir/".preg_replace("/[^a-z0-9_\.]/","_",$_FILES["file"]["name"]);
					move_uploaded_file($_FILES["file"]["tmp_name"],$path);
					print "$siteurl/".$path;
				}
				else print "Поддерживается .jpg|png|gif|jpeg|mp4|ts|mpeg|mkv";
				exit;
			}
			elseif(isset($_GET["url"])){
				if(strpos($_GET["url"],"http")===0&&filter_var($_GET["url"], FILTER_VALIDATE_URL)){
					$title=$_GET["title"];
					$ch=["title"=>$title,"stream_url"=>$_GET["url"],"advertlink"=>$_GET["advertlink"],"date"=>time(),"login"=>$userinfo["login"],"ip"=>$_SERVER["REMOTE_ADDR"],"initial"=>$_GET["initial"],"work"=>$_GET["work"],"country"=>$_GET["country"],"roles"=>$_GET["roles"],"priority"=>$_GET["priority"],"duration"=>$_GET["duration"],"skip"=>$_GET["skip"]];
					if(!empty($link["id"])){
						if(savePluginMetaKey("rek",json_encode($ch),$link["id"])) $div.="Реклама $_GET[url] сохранена<br>";
						else $div.="Error insert to database<br>";	
					}
					else{
						$links=getPluginMetaKey("rek",true);
						foreach($links as $k=>$v) {
							if($v["src"]["stream_url"]==$_GET["url"]){
								$div.="Такой URL $_GET[url] с названием ".$v["src"]["title"]." уже есть! Удалите его сначала<br>";
								$Channels[]=["title"=>"Вернутья к списку рекламы","playlist_url"=>"$PLUGIN[link]","description"=>""];
								$Channels[]=["title"=>"Вернуться назад","playlist_url"=>"javascript:OpenGoBack();","description"=>""];
								return;
							}
						}
							
						if(savePluginMetaKey("rek",json_encode($ch))) $div.="Ссылка $_GET[url] успешно добавлена<br>";
						else $div.="Error insert to database<br>";	
					}
					$Channels[]=["title"=>"Вернутья к списку ссылок","playlist_url"=>"$PLUGIN[link]","description"=>""];			
				}
				else {
					$div.="Невалидный URL $_GET[url]<br>";
					$Channels[]=["title"=>"Вернуться назад","playlist_url"=>"javascript:OpenGoBack();","description"=>""];
				}
				
			}
			elseif($_ISPC&&$AllowUpload){
					$div.="Добавить рекламу<br>";
					 $div.="
					 <a href=\"#\"> URL адрес</a> <div style=\"float:right;\">  </div> <br>URL адрес рекламного изображения/гиф анимации/видео<br><input id='payd_url' name='payd_url' value='".$link["src"]["stream_url"]."' size=70 /> <div id='video_adv' style='display:none;'></div><input type=\"file\" name=\"file_advert\" id=\"file_advert\" /><br>
					 Допустимый размер файла ".formatSizeInMb(detectMaxUploadFileSize())." <a href='https://www.google.com/search?q=php+увеличить+размер+загружаемого+файла'>как увеличить</a><br>
					 
<div style='clear:both;width:600px;margin-top:15px;'> 
	<a href='#'> Длительность в секундах</a> <div style='float:right;'>    </div><br>
	<span style='color:green;font-size:80%;'> </span> <div style='overflow-y:auto; max-height:400px;'>0 - автоматически<br><input type='number' id='payd_duration' name='payd_duration' value='".(isset($link["src"]["duration"])?$link["src"]["duration"]:"0")."' size=80 /><br><small>Обьязательно укажите время если реклама в виде картинки или анимации</small></div>
</div>
<div style='clear:both;width:600px;margin-top:15px;'> 
	<a href='#'> Кнопка пропустить через секунд (0 - 5)</a> <div style='float:right;'>    </div><br>
	<span style='color:green;font-size:80%;'> </span> <div style='overflow-y:auto; max-height:400px;'>0 - доступен пропуск сразу<br><input type='number' id='payd_skip' name='payd_duration' value='".(isset($link["src"]["skip"])?$link["src"]["skip"]:"0")."' size=80 /><br><small>Максимум можно указать 5 секунд</small></div>
</div>

			<div style=\"clear:both;width:600px;margin-top:15px;\"> 
			<a href=\"#\"> Название</a> <div style=\"float:right;\">  </div> <br>Название рекламы (выводится в названии ссылки перехода, можно оставить пустым)<br><input id='payd_title' name='payd_title' value='".$link["src"]["title"]."' size=70 />
		</div><div style=\"clear:both;width:600px;margin-top:15px;\"> 
			
			<a href=\"#\">Ссылка перехода при клике на рекламу</a> <div style=\"float:right;\">  </div> <br> Ссылка только на XML/M3U страницу(Можно оставить пустым)<br><input id='payd_advertlink' name='payd_advertlink' value='".$link["src"]["advertlink"]."' size=70 /> 
		</div>

			<div style=\"clear:both;width:600px;margin-top:15px;\"> 	
			<b>Страны</b>, - если пусто то показывать всем (через кому в формате RU,UA,BY,MD)<br>
			<input id='payd_country' name='payd_country' value='".$link["src"]["country"]."' size=70 />
			<br>
			
			<div style=\"clear:both;width:600px;margin-top:15px;\"> 	
			<b>Роли сайта освобожденные от этой рекламы</b>, если пусто то показывать всем. Через кому в формате 5,6,7 (<a href='/?do=/admin&act=role'>id ролей</a>)<br>
			Пользователь имеет роли от 0 до 4, Администратор - от 0 до 10, Важный пользователь 0-5. Если будет вхождение хоть в одну указанную ниже роль то будет освобождение от рекламы.<br>
			<input id='payd_roles' name='payd_roles' value='".$link["src"]["roles"]."' size=70 />
			<br>
			
			<br>
			<b>Приоритет</b> (0 - 100), - если вкл/ несколько реклам то частота показов будет = (Приоритет/(Сумма всех приоритетов))<br>
			<input type=number id='payd_priority' name='payd_priority' value='".(isset($link["src"]["priority"])?$link["src"]["priority"]:"50")."' size=2 />
			<br><br>
			<input type=checkbox id='payd_work' name='payd_work'".((isset($link["src"]["work"])&&!$link["src"]["work"])?"":"checked")." value='1' /> Включить показ
			<br><br>
			<a href=\"javascript:location='$PLUGIN[link]&mode=addurl&lid=$link[id]&url='+encodeURIComponent($('#payd_url').val())+'&roles='+encodeURIComponent($('#payd_roles').val())+'&title='+encodeURIComponent($('#payd_title').val())+'&advertlink='+encodeURIComponent($('#payd_advertlink').val())+'&country='+encodeURIComponent($('#payd_country').val())+'&priority='+encodeURIComponent($('#payd_priority').val())+'&work='+(($('#payd_work').prop('type')=='checkbox')?($('#payd_work').prop('checked')?encodeURIComponent($('#payd_work').val()):''):encodeURIComponent($('#payd_work').val()))+'&duration='+(($('#payd_duration').prop('type')=='checkbox')?($('#payd_duration').prop('checked')?encodeURIComponent($('#payd_duration').val()):''):encodeURIComponent($('#payd_duration').val()))+'&skip='+(($('#payd_skip').prop('type')=='checkbox')?($('#payd_skip').prop('checked')?encodeURIComponent($('#payd_skip').val()):''):encodeURIComponent($('#payd_skip').val()))+'';\"> ".(isset($link["id"])?"Сохранить изменения":"Добавить рекламу")."</a>
					 ";
					 $Channels[]=["title"=>"Вернутья к списку ссылок","playlist_url"=>"$PLUGIN[link]","description"=>""];
				}
				else{
					$div.="Добавить рекламу<br>";
					$Channels[]=["title"=>"URL адрес рекламного изображения/видео","value"=>$link["src"]["stream_url"],"playlist_url"=>"payd_url","description"=>"","search_on"=>"Введите URL адрес рекламного изображения/видео"];					
					$Channels[]=["title"=>"Длительность в секундах","type"=>"number","playlist_url"=>"payd_duration","description"=>"Обьязательно укажите время если реклама в виде картинки или анимации","value"=>"".(isset($link["src"]["duration"])?$link["src"]["duration"]:"0")."","search_on"=>"0 - автоматически"];
					
					$Channels[]=["title"=>"Кнопка пропустить через секунд (0 - 5)","playlist_url"=>"payd_skip","description"=>"Максимум можно указать 5 секунд","type"=>"number","value"=>"".(isset($link["src"]["skip"])?$link["src"]["skip"]:"0")."","search_on"=>"0 - доступен пропуск сразу"];
					
					$Channels[]=["title"=>"Название рекламы","value"=>$link["src"]["title"],"playlist_url"=>"payd_title","description"=>"выводится также в названии ссылки перехода, если есть","search_on"=>"Название (Можно оставить пустым)"];
					$Channels[]=["title"=>"Ссылка перехода при клике на рекламу","value"=>$link["src"]["advertlink"],"playlist_url"=>"payd_advertlink","description"=>"","search_on"=>"Ссылка должна вести на XML/M3U страницу"];					
					$Channels[]=["title"=>"Страны если пусто то показывать всем","value"=>$link["src"]["country"],"playlist_url"=>"payd_country","description"=>"","search_on"=>"через кому в формате RU,UA,BY,MD"];		
					$Channels[]=["title"=>"Роли сайта освобожденные от этой рекламы,  если пусто то показывать всем","value"=>$link["src"]["roles"],"playlist_url"=>"payd_country","description"=>"Пользователь имеет роли от 0 до 4, Администратор - от 0 до 10, Важный пользователь 0-5. Если будет вхождение хоть в одну указанную роль то будет освобождение от рекламы.<br>","search_on"=>"через кому в формате 1,2,3,4"];				
					$Channels[]=["title"=>"Приоритет","type"=>"number","playlist_url"=>"payd_priority","description"=>"","value"=>"".(isset($link["src"]["priority"])?$link["src"]["priority"]:"50")."","search_on"=>"(число 0 - 100)"];
					$Channels[]=["title"=>"Включить показ","playlist_url"=>"payd_work","description"=>"","value"=>"".((isset($link["src"]["work"])&&!$link["src"]["work"])?"0":"1")."","search_on"=>"(0 - выключить, 1 - включить)"];
					$Channels[]=["title"=>"Отправить","playlist_url"=>"$PLUGIN[link]&mode=addurl&lid=$link[id]&url=payd_url&title=payd_title&advertlink=payd_advertlink&country=payd_country&priority=payd_priority&work=payd_work&duration=payd_duration&skip=payd_skip","description"=>""];
					$Channels[]=["title"=>"Вернутья к списку ссылок","playlist_url"=>"$PLUGIN[link]","description"=>""];
				}
		}
		else $Channels[]=["logo_30x30"=>"","title"=>"Добавить рекламу","playlist_url"=>"$PLUGIN[link]&mode=addurl","description"=>"","menu"=>[]]; 
			
			
	
}

function sec2hms($secs) {
    $secs = round($secs);
    $secs = abs($secs);
    $hours = floor($secs / 3600) . ':';
    if ($hours == '0:') $hours = '00:';
    $minutes = substr('00' . floor(($secs / 60) % 60), -2) . ':';
    $seconds = substr('00' . $secs % 60, -2);
	return $hours . $minutes . $seconds;
}
function detectMaxUploadFileSize()
{
	/**
	 * Converts shorthands like "2M" or "512K" to bytes
	 *
	 * @param int $size
	 * @return int|float
	 * @throws Exception
	 */
	$normalize = function($size) {
		if (preg_match('/^(-?[\d\.]+)(|[KMG])$/i', $size, $match)) {
			$pos = array_search($match[2], array("", "K", "M", "G"));
			$size = $match[1] * pow(1024, $pos);
		} else {
			throw new Exception("Failed to normalize memory size '{$size}' (unknown format)");
		}
		return $size;
	};
	$limits = array();
	$limits[] = $normalize(ini_get('upload_max_filesize'));
	if (($max_post = $normalize(ini_get('post_max_size'))) != 0) {
		$limits[] = $max_post;
	}
	if (($memory_limit = $normalize(ini_get('memory_limit'))) != -1) {
		$limits[] = $memory_limit;
	}
	$maxFileSize = min($limits);
	return $maxFileSize;
}
function formatSizeInMb($size, $maxDecimals = 3, $mbSuffix = " MB")
{
	$mbSize = round($size / 1024 / 1024, $maxDecimals);
	return preg_replace("/\\.?0+$/", "", $mbSize) . $mbSuffix;
}
