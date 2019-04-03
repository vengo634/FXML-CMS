<?php
if(!defined('XMLCMS')) exit();
error_reporting(E_ERROR | E_PARSE);
$version="1.0.0";
//print_r($GLOBALS);
if(isset($_GET["box_mac"])){
	$box_mac=@$_GET["box_mac"];
}

if(isset($_GET["do"])){
	$do=@$_GET["do"];
}
else $do=""; 
$initial=(isset($_GET['initial']))?explode("|",$_GET['initial']):array();
$act=@$_GET["act"];
$p=@$_GET["p"];
$logged=false;
$userinfo=array();
$ip=$_SERVER['REMOTE_ADDR'];
include(dirname(__FILE__)."/geo/ipgeobase.php");
include(dirname(__FILE__)."/version.php");
$gb = new IPGeoBase();
$data = $gb->getRecord($ip);
$COUNTRY=$data["cc"];
if(isset($_GET["box_mac"])) {
	$_ISPC=false;
	foreach($_GET["cookie"] as $k=>$v){
		$_COOKIE[$k]=$v;
	}
}
else $_ISPC=true;
	
if(!file_exists("config.php")){
	header("Location: /install.php");
	exit;
}
else include "config.php";

include dirname(__FILE__).'/functions.php';

$_PL=array();
$_CH=array();
$_MENU=array();

if (version_compare(PHP_VERSION, '7.0.0','>=')) {
	include_once dirname(__FILE__).'/mysql.php';
}
$dbh=mysql_connect($host, $user, $password) or exit("Не удалось подключится к базе данных MySql");
$db=mysql_select_db($db);
mysql_query("set names 'utf8'");	
$modules=getModules();
foreach($modules as $k=>$v){
	if($v["settings"]["enabled"]) {
		include_once(dirname(__FILE__)."/module/$v[id]/header.php");
	}
}

$err="";

$q=get_menu(true);
$_PL["style"]["cssid"]["menu"]=$q["menuSets"];
if($q!=null){
	$a=$q["src"];
	if($logged&&$userinfo["role"]=="10") $_MENU[]=["title"=>"Админка","playlist_url"=>"$siteurl/?do=/admin"];
	foreach($a as $k=>$v){
		if($v["type"]=="module") {
			include_once(dirname(__FILE__)."/module/$v[title]/menu.php");
		}
		elseif($v["title"]=="main") $_MENU[]=["logo_30x30"=>$siteicon,"title"=>"$sitename","playlist_url"=>"$siteurl/"];
		elseif($v["title"]=="category"){
			$plugins=getPlugins("menu");
			$submenu=[];
			for($i=0;$i<count($plugins);$i++){
				if($do=="/plugin"&&$_GET["id"]==$plugins[$i]["id"]) $submenu[]=["logo_30x30"=>$plugins[$i]["icon"],"search_on"=>$plugins[$i]["search_on"],"presearch"=>$plugins[$i]["presearch"],"title"=>"<b>".$plugins[$i]["name"]."</b>","playlist_url"=>$plugins[$i]["link"]];
				else $submenu[]=["logo_30x30"=>$plugins[$i]["icon"],"search_on"=>$plugins[$i]["search_on"],"presearch"=>$plugins[$i]["presearch"],"title"=>"".$plugins[$i]["name"]."","playlist_url"=>$plugins[$i]["link"]];

				$div.=$tpl;
			}
			$cq=q("select * from {$dbprefix}category");
			foreach($cq as $kk=>$vv){ 
				if($do=="/category"&&$_GET["id"]==$vv["id"]) $submenu[]=["logo_30x30"=>$vv["icon"],"title"=>"<b>".$vv["title"]." ($vv[count])</b>","playlist_url"=>""];
				else $submenu[]=["logo_30x30"=>$vv["icon"],"title"=>$vv["title"]." ($vv[count])","playlist_url"=>"$siteurl/?do=/category&id=$vv[id]"];
			}
			$_MENU[]=["logo_30x30"=>"$siteurl/include/templates/images/menu.png","title"=>"Меню","playlist_url"=>"submenu","submenu"=>$submenu];
		}
		elseif($v["title"]=="new"){
			$qp=get_pages(""," limit 0,5","created desc");
			$submenu=[];
			foreach($qp as $kk=>$vv) $submenu[]=["logo_30x30"=>$vv["icon"],"title"=>$vv["title"],"playlist_url"=>"$siteurl/?do=/fml&id=".$vv["id"]];
			$_MENU[]=["title"=>"Последнее","playlist_url"=>"submenu","submenu"=>$submenu];
		}
		elseif($v["title"]=="pop"){
			$qp=get_pages(""," limit 0,5","view desc");
			$submenu=[];
			foreach($qp as $kk=>$vv) $submenu[]=["logo_30x30"=>$vv["icon"],"title"=>$vv["title"],"playlist_url"=>"$siteurl/?do=/fml&id=".$vv["id"]];
			$_MENU[]=["title"=>"Популярное","playlist_url"=>"submenu","submenu"=>$submenu];
		}
		elseif(isset($v["playlist_url"])){
			$_MENU[]=$v;
		}
	}
}
{
	$smenu="<ul id='menu' style='background-color:".$_PL["style"]["cssid"]["menu"]["background_color"].";'>"; 
	for($i=0;$i<count($_MENU);$i++){
		$a=$_MENU[$i];
		$smenu.="<li><a href='".(($a["playlist_url"]=="submenu")?"#":$a["playlist_url"].$a["stream_url"])."' style='color:".$_PL["style"]["cssid"]["menu"]["color"].";' >".(empty($a["logo_30x30"])?"":"<img align='left' src='$a[logo_30x30]' onerror=\"this.style.display='none'\" id='img_$pltag[$i]' width=20 height=18 style='margin: 2px;'/>")." ".$a["title"]."</a>";
		if($a["playlist_url"]=="submenu") {
			$smenu.="<ul id='sub$i' style='background-color:".$_PL["style"]["cssid"]["menu"]["background_color"].";'>";
			foreach($a["submenu"] as $k=>$v){
				$smenu.="<li><a href='".$v["playlist_url"]."'><img align='left' src='$v[logo_30x30]' onerror=\"this.style.display='none'\" id='img_$pltag[$i]' width=20 height=18 style='margin: 2px;color:".$_PL["style"]["cssid"]["menu"]["color"].";'/> ".$v["title"]."</a></li>";
			}
			$smenu.="</ul></li>";
		}
		else $smenu.="</li>";		
	}
	$smenu.="</ul><br clear='both'>";
	
	$t=file_get_contents("include/templates/web.xml");
	$t=str_replace("{LOGO}",$logo,$t);
	$t=str_replace("{MENU}",$smenu,$t);
	$t=str_replace("{COPYRIGHT}","© $sitename ".date("Y"),$t);
	if($do=="/admin"&&$logged&&$userinfo["role"]=="10") {
		if($_GET["act"]=="imgtofon"){
			if(preg_match("/\.(jpg|jpeg|png)$/i",$_FILES["file"]["name"])){
				$path="uploads/backgrounds/".preg_replace("/[^a-z0-9_\.]/","_",$_FILES["file"]["name"]);
				move_uploaded_file($_FILES["file"]["tmp_name"],$path);
				print "$siteurl/".$path;
			}
			else print "Поддерживается png,jpg,jpeg";
			exit;
		}
		if($_GET["act"]=="imgto64"){			
			//print_r($_FILES);$_FILES["file"]["name"];
			$f=resize_image($_FILES["file"]["tmp_name"],90,84,$_FILES["file"]["type"]);
			$path="uploads/icons/".preg_replace("/\..{2,4}$/","",$_FILES["file"]["name"]).".png";
			imagepng($f, $path);
			print "$siteurl/".$path;
			/*
			$f=file_get_contents($path);
			$type = pathinfo($path, PATHINFO_EXTENSION);
			print 'data:image/' . $type . ';base64,' . base64_encode($f);*/
			exit;
		}
		
		$link[]="/?do=/admin&act=addpage|Добавить страницу";
		$link[]="/?do=/admin&act=listpage|Список страниц";
		$link[]="/?do=/admin&act=cats|Категории";
		$link[]="/?do=/admin&act=user|Пользователи";
		$link[]="/?do=/admin&act=role|Роли сайта";
		$link[]="/?do=/admin&act=menu|Редактировать меню";
		$link[]="/?do=/admin&act=plugin|Плагины";
		$link[]="/?do=/admin&act=sets|Настройки сайта";
		$link[]="/?do=/admin&act=update|Обновление FXML CMS";
		$lmenu="";
		for($i=0;$i<count($link);$i++){
			$a=explode("|",$link[$i]);
			if(!$friendly_links) { 
				
			}
			$lmenu.="<a class='left_link' href='".$a[0]."'>".$a[1]."</a><br>";
		}
		if($act=="update"){
			$s=explode("|",file_get_contents("http://xml.forkplayer.tv/updates/version.php"));
			if(preg_match("/[^0-9\.]/",$s[0])) exit("err version updates");
			if(!empty($_GET["cmd"])){
				$s2=file_get_contents($_GET["cmd"]);
				file_put_contents("uploads/updates/updates_$s[0].zip",$s2);
				$zip = new ZipArchive;  
				$res = $zip->open("uploads/updates/updates_$s[0].zip");  
				if ($res === TRUE) {
					$nd="include_old_".time()."_$siteversion";
					rename("include",$nd);
					 $div.= "Предыдущые файлы сохранены в папке $nd<br>";
					 $zip->extractTo(".");  
					 $zip->close();  
					 include(dirname(__FILE__)."/version.php");
					 $div.= "extract ok<br>Обновления $s[0] распакованы!<br>Ваша текущая версия: $siteversion";
				} else {  
					$div.= "zip updates failed extract<br>";  
				} 
			}
			else{
				$div.="Ваша версия: $siteversion<br>";
				$div.="Последняя версия: $s[0] ($s[1])<br>";
				 if (version_compare($s[0],$siteversion, '>')) {
					$div.="<a href='/?do=/admin&act=update&cmd=".urlencode($s[2])."'>Обновить</a><br><br>Как обновить вручную?<br> Скачайте архив <a href='$s[2]'>$s[2]</a> и залейте с него папку include на сервер c заменой файлов";
				}
			}
		}
		if($act=="addpage"){
			if(empty($_GET["op"])) $div="<div style='text-align:center;font-weight:bold;'>Добавить новую страницу</div>
			Создать из файла XML/M3U/XSPF <form action='/?do=/admin&act=addpage&op=uploadfile' enctype='multipart/form-data' method='POST'>    <input type='hidden'>    <input name='file' type='file'>     <input type='submit' value='Создать'></form><br>
			<a href='/?do=/admin&act=addpage&op=createxml'>Создать пустую страницу</a> - страница со всеми возможностями<br>
			";
			else{
				if($_GET["op"]=="uploadfile"){
					$f=file_get_contents($_FILES["file"]["tmp_name"]);
					if(strpos($f,"<channel>")!==false){
						$pf=parsexml($f);
					}
					elseif(strpos($f,"EXTINF")!==false){
						$pf=parsem3u($f);
					}					
					elseif(preg_match("/<track>(.*?)<\/track>/is",$f)){
						$pf=parsexspf($f);
					}
					else $div="<b>Файл не содержит поддерживаемого формата страницы (XML,M3U,XSPF)</b><hr>";
					if(empty($pf["title"])) $pf["title"]=$_FILES["file"]["name"];
					if(empty($pf["icon"])) $pf["icon"]="$siteurl/include/templates/images/logo.png";
					$_GET["op"]="createxml";
				}
				if($_GET["op"]=="delete"){
					if(mysql_query("delete from {$dbprefix}page where id=\"".mysql_real_escape_string($_GET["id"])."\"")) $div.="Страница id #$_GET[id] удалена<hr>";
					else $div.="Такая страница id #$_GET[id] не найдена ".mysql_error()."<hr>";
					$act="listpage";
				}
				elseif($_GET["op"]=="savexml"){
					$ch=$_POST["pltag"];
					$ch["channels"]=json_decode($_POST["channels"],true);
					$category=",";
					foreach($_POST["category"] as $k=>$v) $category.="$v,";
					if(!empty($_GET["id"])){
						if(($id=mysql_query("update {$dbprefix}page set category=\"".mysql_real_escape_string($category)."\",src=\"".mysql_real_escape_string(json_encode($ch))."\", modified=".time().",icon=\"".mysql_real_escape_string($_POST["pltag"]["icon"])."\",title=\"".mysql_real_escape_string($_POST["pltag"]["title"])."\",seourl=\"".mysql_real_escape_string($_POST["seo"])."\",description=\"".mysql_real_escape_string($_POST["description"])."\",encrypt=\"".mysql_real_escape_string($_POST["encrypt"])."\",is_iptv=\"".mysql_real_escape_string($_POST["is_iptv"])."\",sticked=\"".mysql_real_escape_string($_POST["sticked"])."\" where id=\"".mysql_real_escape_string($_GET["id"])."\""))!==false) $div.="Страница #$_GET[id] $ch[title] сохранена<hr>";
						else $div.="Ошибка сохранения страницы ".mysql_error()."<hr>";
					}
					else{
						if(($id=mysql_query("insert into {$dbprefix}page set category=\"".mysql_real_escape_string($category)."\",src=\"".mysql_real_escape_string(json_encode($ch))."\",icon=\"".mysql_real_escape_string($_POST["pltag"]["icon"])."\",title=\"".mysql_real_escape_string($_POST["pltag"]["title"])."\",description=\"".mysql_real_escape_string($_POST["description"])."\", modified=".time().",encrypt=\"".mysql_real_escape_string($_POST["encrypt"])."\",is_iptv=\"".mysql_real_escape_string($_POST["is_iptv"])."\",sticked=\"".mysql_real_escape_string($_POST["sticked"])."\",author=".$userinfo["id"].",seourl=\"".mysql_real_escape_string($_POST["seo"])."\""))!==false) $div.="Страница $ch[title] создана<hr>";
					else $div.="Ошибка создания страницы ".mysql_error()."<hr>";
					}
					category_recount();
					$act="listpage";
					//print_r($ch);
				}
				if($_GET["op"]=="createxml"){
					$q=q("select max(id) from {$dbprefix}page",1);
					if(empty($q[0])) $news_id=1;
					else $news_id=$q[0]+1;
					if(isset($pf)){
						$div.="\n<script>\nvar qch=".json_encode($pf).";\n channels=qch.channels;\n</script>\n";
					}
					elseif(isset($_GET["editid"])){
						$qch=q("select * from {$dbprefix}page where id=\"".mysql_real_escape_string($_GET["editid"])."\"",1);
						$div.="\n<script>\nvar qch=".$qch["src"].";\n channels=qch.channels;\n</script>\n";
						$news_id=$qch["id"];
					}
					if(empty($news_id)) $div.="Страницы $_GET[editid] нет<br>";
					else{
						
						$div.="<script>var chtag=\"$chtag\".split('|');
						var pltag=\"$pltag\".split('|');
						</script>";
						$chtag=explode("|",$chtag);
						$pltag=explode("|",$pltag);
						$div.="Свойства страницы:".((isset($pf))?" загружено с файла ".$_FILES["file"]["name"]." (".$_FILES["file"]["size"]."byte)":"")."<br>ID страницы: $news_id<br>";
						$pltaginfo=explode("|","Заголовок|Иконка(введите url или загрузите jpg, png - если оставить пустым будет браться иконка с родительской ссылки)|Вид(список,плитка)|Фоновая картинка(изменить в <a href='/?do=/admin&act=sets'>настройках</a>)|url|server|next_page_url|prev_page_url|access|timeout|is_iptv|all_description|pageinfo");
						$div.="<form id='formxml' action='/?do=/admin&act=addpage&op=savexml&id=$_GET[editid]' method='POST'>";
						if(!isset($_GET["editid"])){
							$pltagvalue[0]="";
							$pltagvalue[1]="$siteicon";
						}
						$pltagvalue[3]="$sitebackground";
						for($i=0;$i<count($pltag);$i++){
							if($i<4){ 
								if($pltag[$i]=="background_image") {
									$div.="<input type='hidden' id='pltagid_$pltag[$i]' name='pltag[$pltag[$i]]' value='$pltagvalue[$i]' />";
									continue;
								}
								$div.="$pltaginfo[$i]";
								if($pltag[$i]=="icon"||$pltag[$i]=="background_image") $div.=" <img  align='left' src='$pltagvalue[$i]' onerror=\"this.style.display='none'\" id='img_$pltag[$i]' width=20 height=18 />";
								$div.="<br>";//id='pltagid_$pltag[$i]' 
								if($pltag[$i]=="typeList") $div.= "<input type='radio' checked='true' name='pltag[$pltag[$i]]' value='' /> Список <input type='radio' name='pltag[$pltag[$i]]' value='start' /> Плитка";
								else $div.="<input style='width:400px;' id='pltagid_$pltag[$i]' name='pltag[$pltag[$i]]' value='$pltagvalue[$i]' />";
								if($pltag[$i]=="icon") $div.=" <input type=\"file\" name=\"file_$pltag[$i]\" id=\"file_$pltag[$i]\" alt='ico_$news_id' />"; 
								$div.="<br>";							
							}
							else continue;						
						}
						$div.="Описание страницы<br>
						<div style=\"width: 450px;background: url($sitebackground);\">
						<textarea rows=10 cols=60 id='description' name='description' />$qch[description]</textarea></div>";
						$div.='
						<script>
							$("#description").htmlarea({
								css: "/include/templates/js/jHtml/jHtmlArea.Editor.css",
								toolbar: [
									["html","|","bold", "italic", "underline", "|", "forecolor"],
									["justifyLeft", "justifyCenter", "justifyRight","p"],
									["|", "image"]
								]}).parent().resizable({ alsoResize: $(this).find("iframe") });	
						</script>';
				
						$ch=q("select * from {$dbprefix}category");
						$div.="Категории (Удерживайте ctrl чтобы отметить несколько!)<br>";	
					
						$c="";
						foreach($ch as $k=>$v){
							for($j=0;$j<count($r);$j++) if($r[$j]!="") $dst.=get_role($r[$j])." ";
							$c.="<option value='$v[id]'".((isset($_GET["editid"])&&strpos($qch["category"],",$v[id],")!==false)?" selected":"").">$v[title] (".get_cat_roles($v["access"]).")</option>";
						}
						$div.="<select multiple name='category[]' style='height:80px;'>
						<option value=''>Нет. (Всем)</option>
						$c
						</select><br>
						";
						$div.="<input type='checkbox' name='sticked'".(($qch["sticked"]>0)?" checked":"")." value='1' /> Закрепить на главной<br>";
						$div.="<input type='checkbox' id='encrypt' name='encrypt'".(($qch["encrypt"]>0)?" checked":"")." value='1' /> Шифровать ссылки (только на видеопотоки stream_url)<br>";
						$div.="<input type='checkbox' name='is_iptv'".(($qch["is_iptv"]>0)?" checked":"")." value='1' /> Это страница со стримами  телеканалов (для отображения тв программы)<br>";
						$div.="<input type='hidden' value='' id='pl_channels' name='channels' /><br><input type='button' value='Сохранить' onclick='upload_page();' />
						</form>";
						$div.="<div id='edit' style='width:780px;padding: 10px;position:absolute;display:none;    z-index: 1;    margin: 100px 0px 0px 30px;borrder:1px solid gray;'></div> 
						<div id='pr' style='background: url(/include/templates/images/fon20.jpg);'>
						<div id='ch' style='padding: 4px 4px 250px 4px;font-size:20px;margin-top:20px;;min-height:600px;color: $sitecolor;'></div>
						</div>
						<script>
						var channelsmenu=".json_encode($_MENU).";
						
						".((isset($_GET["editid"])||isset($pf))?"for(var i=0;i<pltag.length;i++){
								if(pltag[i]=='background_image') continue;
								if(typeof qch[pltag[i]] == 'undefined') qch[pltag[i]]='';
								if(pltag[i]=='typeList'){
									if(qch.typeList=='start') $('[name=pltag\\\\[typeList\\\\]]').val(['start']);
								}
								if($('#pltagid_'+pltag[i])!=null) $('#pltagid_'+pltag[i]).val(qch[pltag[i]]);
								
								if(pltag[i]=='icon'||pltag[i]=='background_image'){
									$('#img_'+pltag[i]).prop('src', qch[pltag[i]]);
									$('#img_'+pltag[i]).show();
								}
							}
							for(var i=0;i<channels.length;i++){
								for(var j=0;j<chtag.length;j++) if(typeof channels[i][chtag[j]] == 'undefined') channels[i][chtag[j]]='';
							}
							listch();":"addch();")."</script>";
					}
				}
				
				
				
			}
			
		}
		if($act=="plugin"){
			$qe=q("select * from {$dbprefix}meta where `key`='[PLUGIN_ENABLED]'",1);
			if($_GET["op"]=="save"){
				$div="";
				$ch=[];
				foreach($_POST["op"] as $k=>$v){
					if($v) $ch[$k]=["enabled"=>1];
				}				
				
				if(!isset($qe["id"])) mysql_query("insert into {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', `key`=\"".mysql_real_escape_string('[PLUGIN_ENABLED]')."\", src=\"".mysql_real_escape_string(json_encode($ch))."\"");
				else mysql_query("update {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', src=\"".mysql_real_escape_string(json_encode($ch))."\" where id='$qe[id]'");
				$qe=q("select * from {$dbprefix}meta where `key`='[PLUGIN_ENABLED]'",1);
			}
			if(isset($qe["id"])) $a=json_decode($qe["src"],true);
			if(isset($_GET["id"])){
				$PLUGIN=getInfoPlugin($_GET["id"]); 
				$div.="<b>Настройки плагина</b><br>id: $PLUGIN[id]<br>Имя: $PLUGIN[name]<br><br>";
				if($PLUGIN["enabled"]){
					include dirname(__FILE__)."/plugin/$PLUGIN[id]/settings.php";
					$div.=$echo;
					
				}
				else $div.="Плагин $PLUGIN[id] $PLUGIN[name] выключен!<br>";
			}
			else{
				$d=scandir(dirname(__FILE__).'/plugin');
				$div.="<form id='formxml' action='/?do=/admin&act=plugin&op=save' method='POST'>";
				foreach($d as $k=>$v){
					if(is_dir(dirname(__FILE__)."/plugin/$v")&&$v!="."&&$v!=".."){
						$inf=getInfoPlugin($v);
						$div.="<input type='checkbox' name='op[$v]'".(isset($a[$v])?" checked":"")." value='1' title='$inf[description]' /> <a href='/?do=/plugin&id=$v'>$v</a> $inf[name] <a href='/?do=/admin&act=plugin&id=$v'>Настроить</a> $inf[version]<br><i>$inf[description]</i><hr>";
					}
				}
				
				$div.="<br><input type='submit' value='Сохранить' />
					</form><br>";
			}
		}
		if($act=="menu"){
			$qe=q("select * from {$dbprefix}meta where `key`='[MENU_ENABLED]'",1);
			$menuSets=json_decode($qe["src"],true);
			if($_GET["op"]=="save"){
				$div="";
				$ch=[];
				foreach($_POST["op"] as $k=>$v){
					if($v) $ch[]=["title"=>$k];
				}
				
				foreach($_POST["userlink"] as $k=>$v){
					$ch[]=json_decode($v,true);	
				}
				if(!empty($_POST["userlink"])) {
									
				}
				$menuSets=["enabled"=>$_POST["enabled"]];
				if($_POST["enabledbackground_color"]) $menuSets["backgroundColor"]=$_POST["background_color"];
				if($_POST["enabledcolor"]) $menuSets["color"]=$_POST["color"];
				if(!isset($qe["id"])) mysql_query("insert into {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', `key`=\"".mysql_real_escape_string('[MENU_ENABLED]')."\", src=\"".mysql_real_escape_string(json_encode($menuSets))."\"");				
				else mysql_query("update {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', src=\"".mysql_real_escape_string(json_encode($menuSets))."\" where id=\"".mysql_real_escape_string($qe["id"])."\"");
				
				$q=q("select id from {$dbprefix}meta where `key`='[MENU]'",1);
				if(isset($q["id"])){
						if(mysql_query("update {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."',src=\"".mysql_real_escape_string(json_encode($ch))."\" where id=\"".mysql_real_escape_string($q["id"])."\"")) $div.="Меню сохранено<hr>";
						else $div.="Ошибка сохранения меню ".mysql_error()."<hr>";
					}
					else{
						if(mysql_query("insert into {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', `key`=\"".mysql_real_escape_string('[MENU]')."\", src=\"".mysql_real_escape_string(json_encode($ch))."\"")) $div.="Меню создано<hr>";
						else $div.="Ошибка создания меню ".mysql_error()."<hr>";
					}
				header("Location: http://xml.forkplayer.tv/?do=/admin&act=menu&text=".urlencode($div));
				exit;
			}
			$div.="<b>Настройки меню</b><br>";
			$div.=$_GET["text"];
			$a=[];
			$q=get_menu(); 
			$q=$q["src"];
			$sm="";		
			for($i=0;$i<count($q);$i++){
				$a[$q[$i]["title"]]=1;
				if($q[$i]["type"]=="module"){
					$infModule=getInfoModule($q[$i]["title"]);
					$sm.="<input type='checkbox' name='userlink[".$q[$i]["title"]."]'".($a[$q[$i]["title"]]?" checked":"")." value='".str_replace("'","\\'",json_encode($q[$i]))."' /> Модуль ".$q[$i]["title"]." (".$infModule["name"].")<br>";
				}
				elseif(isset($q[$i]["playlist_url"]))
					$sm.="<input type='checkbox' name='userlink[]' checked value='".str_replace("'","\\'",json_encode($q[$i]))."' /> ".$q[$i]["title"]." (".$q[$i]["playlist_url"]." ".$q[$i]["stream_url"].")<br>";
			}
			
			$modules=getModules();
			foreach($modules as $k=>$v){
				$infModule=getInfoModule($v["id"]);
				if(!$a[$v["id"]]) $sm.="<input type='checkbox' name='userlink[".$v["id"]."]'".($a[$v["id"]]?" checked":"")." value='".str_replace("'","\\'",json_encode(["title"=>$v["id"],"type"=>"module"]))."' /> Модуль ".$v["id"]." (".$infModule["name"].")<br>";
			}
			$div.="<form id='formxml' action='/?do=/admin&act=menu&op=save' method='POST'>
				<input type='hidden' id='pltagid_title' name='o[title]' value='".$sitename."' />
				<input type='hidden' id='pltagid_icon' name='pltag[icon]' value='$siteicon' />
				<input type='hidden' id='pltagid_background_image' name='pltag[background_image]' value='$sitebackground' />
				<input type='hidden' id='pltagid_color' name='o[color]' value='$sitecolor' />
				<input type='checkbox' id='enabled' name='enabled'".($menuSets["enabled"]?" checked":"")." value='1' /> Отображать меню сайта<br>
				<input type='checkbox' id='enabledbackground_color' name='enabledbackground_color'".(!empty($menuSets["backgroundColor"])?" checked":"")." value='1' /> <input type='color' id='background_color' name='background_color' value='".(!empty($menuSets["backgroundColor"])?$menuSets["backgroundColor"]:"")."' /> Задать цвет фона меню<br>
				<input type='checkbox' id='enabledcolor' name='enabledcolor'".(!empty($menuSets["color"])?" checked":"")." value='1' />  <input type='color' id='color' name='color' value='".(!empty($menuSets["color"])?$menuSets["color"]:"")."' /> Задать цвет текста меню<br>
				<hr>
				Структура меню<br>
				<input type='checkbox' name='op[main]'".($a["main"]?" checked":"")." value='1' /> Главная<br> 
				<input type='checkbox' name='op[category]'".($a["category"]?" checked":"")." value='1' /> Выпадающий список всех категорий и плагинов<br>
				<input type='checkbox' name='op[new]'".($a["new"]?" checked":"")." value='1' /> Выпадающий список последних новых страниц<br>
				<input type='checkbox' name='op[pop]'".($a["pop"]?" checked":"")." value='1' /> Выпадающий список популярных страниц<br>
				$sm
				<input id='userlink' type='hidden' name='userlink[]' value='' />
				<a id='ch-1' href='javascript:edit(-1);'>Добавить свою ссылку в меню</a>
				<hr><br>
				
				<br><input type='submit' value='Сохранить' />
				</form><br>
				Пример отображения портала в ForkPlayer<br>";
				$div.="<div id='edit' style=' background-color: white;width:780px;padding: 10px;position:absolute;display:none;    z-index: 1;    margin: 100px 0px 0px 30px;borrder:1px solid gray;'></div> 
						<div id='pr' style='background: url(/include/templates/images/fon20.jpg);'>
						<div id='ch' style='padding: 4px 4px 250px 4px;font-size:20px;margin-top:20px;;min-height:600px;color: rgb(238, 238, 238);'></div>
						</div>
						<script>
						var chtag=\"$chtag\".split('|');
						var pltag=\"$pltag\".split('|');						
						var menu=".json_encode($_MENU).";
						var channelsmenu=menu;
						addch();
						</script>	
						";
			
		}		
		
		if($act=="sets"){
			$div.="Настройки сайта<br>";
			if($_GET["op"]=="save"){
				savePluginMetaKey("[MAINBEFORE]",$_POST["pltag"]["before"]);
				$f=file_get_contents("config.php");
				$f=preg_replace("/\\\$sitename.*?;/","\$sitename='".preg_replace("/('|\"|\\\)/","",$_POST["op"]["title"])."';",$f);
				$f=preg_replace("/\\\$sitebackground.*?;/","\$sitebackground='".preg_replace("/('|\"|\\\)/","",$_POST["pltag"]["background_image"])."';",$f);				
				$f=preg_replace("/\\\$sitecolor.*?;/","\$sitecolor='".preg_replace("/('|\"|\\\)/","",$_POST["op"]["color"])."';",$f);
				$f=preg_replace("/\\\$siteicon.*?;/","\$siteicon='".preg_replace("/('|\"|\\\)/","",$_POST["pltag"]["icon"])."';",$f);
				$f=preg_replace("/\\\$sitepageinfo.*?;/","\$sitepageinfo='".preg_replace("/('|\"|\\\)/","",$_POST["op"]["pageinfo"])."';",$f);
				$f=preg_replace("/\\\$typelistStart.*?;/","\$typelistStart='".preg_replace("/('|\"|\\\)/","",$_POST["pltag"]["typeList"])."';",$f);
				;
				$f=preg_replace("/\\\$sitechbkg.*?;/","\$sitechbkg='".preg_replace("/('|\"|\\\)/","",hex2rgba($_POST["op"]["chbkg"],$_POST["op"]["chbkgrange"]))."';",$f);
				if(file_put_contents("config.php",$f)) {
					print "Настройки сохранены в config.php<hr><a href='/?do=/admin&act=sets'>Продолжить</a>";
					
					exit;
				}
				else $div.= "Ошибка записи в файл! Запишите текст ниже в файл config.php вручную!<br><textarea cols=150 rows=30 >$f</textarea><hr>";
			}

			if(strpos($sitechbkg,"rgb")!==false)
				$sitechbkg=rgba2hex($sitechbkg);
			if(strpos($sitechcolor,"rgb")!==false)
				$sitechcolor=rgba2hex($sitechcolor);
			$sitechbkgC=substr($sitechbkg,0,7);
			$sitechbkgA=substr($sitechbkg,7,2);
			if(strlen($sitechbkgA)==2) $sitechbkgA=round(hexdec($sitechbkgA)/255,2);
			else $sitechbkgA="1.0";
			$before=getPluginMetaKey("[MAINBEFORE]",false);
			$before=$before[0]["src"];
			$div.="<form id='formxml' action='/?do=/admin&act=sets&op=save' method='POST'>
				Название
				<br>
				<input id='pltagid_title' name='op[title]' value='".$sitename."' /><br>
				Описание портала (256 символов, учитывается поисковой системой)
				<br>
				<input id='pltagid_pageinfo' name='op[pageinfo]' style='width:800px;' value='".$sitepageinfo."' /><br>
				Иконка главной страницы<br>
				<img  align='left' src='' onerror=\"this.style.display='none'\" id='img_icon' width=20 height=18 />
				<input style='width:400px;' id='pltagid_icon' name='pltag[icon]' value='$siteicon' />
				<input type=\"file\" name=\"file_icon\" id=\"file_icon\" /><br>
				
				Фон для всех страниц (1280x720)<br>
				<input style='width:400px;' id='pltagid_background_image' name='pltag[background_image]' value='$sitebackground' />
				<input type=\"file\" name=\"file_background_image\" id=\"file_background_image\" /><br>
				Вид главной страницы
				<input type='radio' ".($typelistStart==''?" checked":"")." name='pltag[typeList]' value='' /> Список <input type='radio' name='pltag[typeList]' ".($typelistStart=='start'?" checked":"")." value='start' /> Плитка<br>
				
				Цвет текста<br>
				<input id='pltagid_color' type='color' name='op[color]' value='$sitecolor' /><br>
				Фон ссылки при выделении<br>
				Цвет: <input id='pltagid_chbkg' type='color' name='op[chbkg]' value='$sitechbkgC' /> Прозрачность: прозр. <input id='pltagid_chbkgrange' name='op[chbkgrange]' type='range' min='0' max='1' step='0.05' value='$sitechbkgA'> непрозр. - если полностью прозр. то будет установлено none<br>
				Цвет текста ссылки при выделении<br>
				<input id='pltagid_chcolor' type='color' name='op[chcolor]' value='$sitechcolor' /><br>
				Текст вверху сайта на главной странице<br>
						<div style=\"width: fit-content;background: url($sitebackground);\">
						<textarea rows=18 cols=80 id='pltag_before' name='pltag[before]' />$before</textarea></div>".'
						<script>
							$("#pltag_before").htmlarea({
								css: "/include/templates/js/jHtml/jHtmlArea.Editor.css",
								toolbar: [
									["html","|","bold", "italic", "underline", "|", "forecolor"],
									["justifyLeft", "justifyCenter", "justifyRight","p"],
									["|", "image"]
								]});	
						</script>'."
				<br>
				<br><input type='submit' value='Сохранить' />
				</form><br>
				Пример отображения портала в ForkPlayer<br>";
				$div.="<div id='edit' style='width:780px;padding: 10px;position:absolute;display:none;    z-index: 1;    margin: 100px 0px 0px 30px;borrder:1px solid gray;'></div> 
						<div id='pr' style='background: url(/include/templates/images/fon20.jpg);'>
						<div id='ch' style='padding: 4px 4px 250px 4px;font-size:20px;margin-top:20px;;min-height:600px;color: rgb(238, 238, 238);'></div>
						</div>
						<script>
						var chtag=\"$chtag\".split('|');
						var pltag=\"$pltag\".split('|');						
						var menu=".json_encode($_MENU).";
						var channelsmenu=menu;
						addch();
						</script>	
						";
			
		}
		if($act=="addmac"){
			if($_GET["op"]=="add"){
				if(intval($_GET["dateto"])>0) $d=",dateto='".mysql_real_escape_string(time()+intval($_GET["dateto"])*24*3600)."'";
				else $d="";
				$mq=q("select * from {$dbprefix}device where mac='".mysql_real_escape_string($_GET["mac"])."'",1);
				if(!isset($mq[0])) {
					if(mysql_query("insert into {$dbprefix}device set mac='".mysql_real_escape_string($_GET["mac"])."'$d,role='".mysql_real_escape_string($_GET["roleid"])."',modified=NOW()")) $div.="Идентификатор $_GET[mac] еще ниразу не входил на сайт, добавлен в ".get_role($_GET["roleid"])."<br>";
					else print "Ошибка ".mysql_error()."<br>";
				}
				else{
					if(!empty($mq["userid"])) $mu=q("select * from {$dbprefix}users where id='".mysql_real_escape_string($mq["userid"])."'",1);
					if(mysql_query("update {$dbprefix}device set role='".mysql_real_escape_string($_GET["roleid"])."'$d,modified=NOW() where id='$mq[id]'")) $div.="Идентификатор $_GET[mac] добавлен в ".get_role($_GET["roleid"])."<br>Информация: ".((!empty($mq["ip"]))?"IP: ".$mq["ip"]." Вход на сайт ".date("d.m.Y H:i",$mq["last"]):"Не входил на сайт")."   <br>".((isset($mu["login"]))?" Логин: $mu[login] Email: $mu[email]":"");
				}
			$div.="<hr>";
			}
			if($p<2) $limit="0,500";
			else $limit=(($p-1)*500).",".(($p-1)*500+500);
			if(!empty($_GET["roleid"])) $ch=q("select * from {$dbprefix}device where role='".mysql_real_escape_string($_GET["roleid"])."' order by modified desc limit $limit");
			else $ch=q("select * from {$dbprefix}device order by modified desc limit $limit");
			$div.="Роль сайта: <a href='/?do=/admin&act=addmac&roleid=$_GET[roleid]'>".get_role($_GET["roleid"])."</a><hr>";
			
			$div.="
			ForkPlayerID это email адрес пользователя сайта http://forkplayer.tv/<br>
			Мак - это мак адрес из настроек ForkPlayer<br>
			<table>
			<tr><td>Мак или ForkPlayerID<br><input id='mac' /></td><td> На дней (0 - навсегда)<br>".'
			<input id="dateto" type="number" list="term" value="0" />
			<datalist id="term">  
				<option value="0">Навсегда</option>
				<option value="03">3 дня</option>
				<option value="30">30 дней</option>
				<option value="90">90 дней</option>
			</datalist>
    '."</td><td><br><input type='button' onclick=\"javascript:if(\$('#mac').val().match(/[a-z0-9]{12,16}/)||\$('#mac').val().match(/\S+@\S+\.\S+/)) location='/?do=/admin&act=addmac&op=add&roleid=$_GET[roleid]&mac='+\$('#mac').val()+'&dateto='+\$('#dateto').val(); else confirm('Мак адрес должен состоять из 12-16 символов латинницей и цифр в нижнем регистре без двоеточий. \\nИли ForkPlayerID должен быть в виде email');\" value='Добавить в ".get_role($_GET["roleid"])."' /></td></tr></table><table>
	<tr><td></td><td>Срок</td><td>Изменено</td><td>Последний вход</td><td>IP</td><td>Вх. раз</td></tr>";
		if(count($ch)<1) $div.="<tr><td>Нет.</td></tr>";  
			for($i=0;$i<count($ch);$i++){
				$div.="<tr><td title=\"".$ch[$i]["initial"]."\">".$ch[$i]["mac"]."</a></td><td style='".(($ch[$i]["dateto"]>0&&$ch[$i]["dateto"]<time())?"color:red;":"")."'> ".(($ch[$i]["dateto"]=="0")?"Безсрочно":date("d.m.Y",$ch[$i]["dateto"]))."</td><td> ".$ch[$i]["modified"]."</td><td> ".(($ch[$i]["last"]<1)?"Не входил":date("d.m.Y H:i",$ch[$i]["last"]))."</td> <td> ".$ch[$i]["ip"]."</td><td> ".$ch[$i]["c"]."</td><td><a href='/?do=/admin&act=addmac&op=delete&roleid=".$ch[$i]["id"]."'>Удалить из группы</a></td>";
				$div.="</tr>";
			}
			
			$div.="</table><br>";
		}
		if($act=="role"){ 
			if($p<2) $limit="0,50";
			else $limit=(($p-1)*50).",".(($p-1)*50+50);
			$ch=q("select * from {$dbprefix}role limit $limit");
			if(count($ch)<1) $div.="Нет.<hr>";  
			$div.="<table>";
			for($i=0;$i<count($ch);$i++){
				$cq=q("select count(id) from {$dbprefix}users where role='".$ch[$i]["id"]."'",1);
				$mq=q("select count(id) from {$dbprefix}device where role='".$ch[$i]["id"]."'",1);
				
				$div.="<tr><td><a href='/?do=/admin&act=editrole&roleid=".$ch[$i]["id"]."'>id #".$ch[$i]["id"]." ".$ch[$i]["name"]."</a></td><td> ".(($ch[$i]["id"]!=3)?"Количество: ".$cq[0]."":"")."</td>";
				if(preg_match("/[678]/",$ch[$i]["id"])) $div.="<td><a href='/?do=/admin&act=addmac&roleid=".$ch[$i]["id"]."'>Cписок устройств ($mq[0])</a> (мак адреса)</td>";
				$div.="</tr>";
			}
			$div.="</table><br>";
		}
		if($act=="adduser"){
			if($_GET["op"]=="delete"){
				if(mysql_query("delete from {$dbprefix}users where id=\"".mysql_real_escape_string($_GET["id"])."\"")) $div.="Пользователь id #$_GET[id] удален<hr>";
				else $div.="Такой пользователь с id #$_GET[id] не найден ".mysql_error()."<hr>";
				$act="user";
			} 
			elseif($_GET["op"]=="save"){
				$access=",";
				if(!filter_var($_POST["op"]["email"], FILTER_VALIDATE_EMAIL))  $err.="<br><span style='color:red;'>Email ".$_POST["op"]["email"]." не верный!</span><br>";
				elseif($_POST["op"]["pass1"]!=$_POST["op"]["pass1"]) $div.="Ошибка! Пароли не совпадают<br>";
				else if(!empty($_GET["id"])){
					if(empty($_POST["op"]["pass1"])) $setp="";
					else $setp=", password=\"".mysql_real_escape_string(md5($secret.$_POST["op"]["pass1"]))."\"";
					if(($id=mysql_query("update {$dbprefix}users set login=\"".mysql_real_escape_string($_POST["op"]["login"])."\",`email`=\"".mysql_real_escape_string($_POST["op"]["email"])."\" where id=\"".mysql_real_escape_string($_GET["id"])."\""))!==false) $div.="Пользователь ".$_GET["id"]." сохранен<br>";
					else $div.="Ошибка! ".mysql_error()."<br>";
				}
				else{
					if(strlen($_POST["op"]["pass1"])<4) $div.="Ошибка! Пароль должен иметь длину не меньше 4!<br>";
					elseif(register_user($_POST["op"]["login"],$_POST["op"]["email"],$_POST["op"]["pass1"],$_POST["op"]["mac"],$_POST["op"]["role"])) $div.="Пользователь ".$_POST["op"]["login"]." добавлен<br>";
					else $div.="Ошибка! ".mysql_error()."<br>";
				}
				$act="user";
				$div.="<hr>";
			}
			else{
				if(isset($_GET["editid"])){
						$q=q("select * from {$dbprefix}users where id='".mysql_real_escape_string($_GET["editid"])."'",1);
				}
				if(isset($_GET["editid"])&&!isset($q[0])) $div.="Ошибка! Такого пользователя нет<br>";
				else{
					get_role();
					$c=""; 
					foreach($ROLE as $k=>$v){
						if($k!=10&&$k!=3) $c.="<option value='$k'".((isset($_GET["editid"])&&$q["role"]==$k||!isset($_GET["editid"])&&4==$k)?" selected":"").">$v</option>";
					}
					$div.="".(isset($_GET["editid"])?"Редактирование пользователя":"Создать пользователя")." $_GET[editid]<br>
					<form id='formxml' action='/?do=/admin&act=adduser&op=save&id=$_GET[editid]' method='POST'>
					Логин<br>
					<input pattern=\"[A-Za-z0-9_]+\" name='op[login]' value='".(isset($_GET["editid"])?"$q[login]":"")."' /><br>
					Email<br>
					<input type='email' name='op[email]' value='".(isset($_GET["editid"])?"$q[email]":"")."' /><br>
					Мак адрес (не обязательно)<br>
					<input name='op[mac]' value='".(isset($_GET["editid"])?"$q[mac]":"")."' /><br>
					".(isset($_GET["editid"])?"Установить новый":"Задать")." пароль<br>
					<input type='password' name='op[pass1]' value='' /><br>
					Повторите пароль<br>
					<input type='password' name='op[pass2]' value='' /><br>
					
					Роль сайта (По умолчанию после регистрации дается роль Пользователь)<br>
					".($q["role"]==10?"Администратор":"
					<select name='op[role]' onchange=''>
					$c
					</select>")."
					<br><input type='submit' value='".(isset($_GET["editid"])?"Сохранить":"Создать")."' />
					</form>";
					
				}
			}
		}		
		if($act=="addcat"){
			if($_GET["op"]=="delete"){
				if(mysql_query("delete from {$dbprefix}category where id=\"".mysql_real_escape_string($_GET["id"])."\"")) $div.="Категория #".$_GET["id"]." удалена!";
				
			}
			elseif($_GET["op"]=="save"){
				$access=",";
				foreach($_POST["access"] as $k=>$v) $access.="$v,";
				if(!empty($_GET["id"])){
					if(($id=mysql_query("update {$dbprefix}category set title=\"".mysql_real_escape_string($_POST["op"]["title"])."\",icon=\"".mysql_real_escape_string($_POST["op"]["icon"])."\",`access`=\"".mysql_real_escape_string($access)."\",`showmain`=\"".mysql_real_escape_string($_POST["op"]["showmain"])."\",`showpage`=\"".mysql_real_escape_string($_POST["op"]["showpage"])."\",`webplayer`=\"".mysql_real_escape_string($_POST["webplayer"])."\",`onlymac`=\"".mysql_real_escape_string($_POST["op"]["onlymac"])."\",`typeList`=\"".mysql_real_escape_string($_POST["pltag"]["typeList"])."\", seo_url=\"".mysql_real_escape_string($_POST["op"]["seo_url"])."\",numsess=\"".mysql_real_escape_string($_POST["op"]["numsess"])."\",onlyfid=\"".mysql_real_escape_string($_POST["op"]["onlyfid"])."\" where id=\"".mysql_real_escape_string($_GET["id"])."\""))!==false) $div.="Категория ".$_GET["id"]." ".$_POST["op"]["title"]." сохранена<br>";
					else $div.="Ошибка! ".mysql_error()."<br>";
				}
				else{
					if(($id=mysql_query("insert into {$dbprefix}category set title=\"".mysql_real_escape_string($_POST["op"]["title"])."\",icon=\"".mysql_real_escape_string($_POST["op"]["icon"])."\",`access`=\"".mysql_real_escape_string($access)."\",`showmain`=\"".mysql_real_escape_string($_POST["op"]["showmain"])."\",`showpage`=\"".mysql_real_escape_string($_POST["op"]["showpage"])."\",`webplayer`=\"".mysql_real_escape_string($_POST["webplayer"])."\",numsess=\"".mysql_real_escape_string($_POST["op"]["numsess"])."\",`typeList`=\"".mysql_real_escape_string($_POST["pltag"]["typeList"])."\",`onlymac`=\"".mysql_real_escape_string($_POST["op"]["onlymac"])."\",onlyfid=\"".mysql_real_escape_string($_POST["op"]["onlyfid"])."\", seo_url=\"".mysql_real_escape_string($_POST["op"]["seo_url"])."\""))!==false) $div.="Категория ".$_POST["op"]["title"]." добавлена<br>";
					else $div.="Ошибка! ".mysql_error()."<br>";
				}
				$act="cats";
				$div.="<hr>";
			}
			else{
				if(isset($_GET["editid"])){
					$q=q("select * from {$dbprefix}category where id='".mysql_real_escape_string($_GET["editid"])."'",1);
				}
				if(isset($_GET["editid"])&&!isset($q[0])) $div.="Ошибка! Такой категории нет<br>";
				else{
					get_role();
					$c="";
					foreach($ROLE as $k=>$v){
						if($k==10||$k==9) continue;
						$c.="<option value='$k'".((isset($_GET["editid"])&&strpos($q["access"],",$k,")!==false)?" selected":"").">$v</option>";
					}
					$div.="Категория $_GET[editid]<br>
					<form action='/?do=/admin&act=addcat&op=save&id=$_GET[editid]' method='POST'>
					Название<br>
					<input name='op[title]' value='".(isset($_GET["editid"])?"$q[title]":"Новая категория")."' /><br>
					Иконка(введите url или загрузите jpg, png) <img  align='left' src='$q[icon]' onerror=\"this.style.display='none'\" id='img_icon' width=20 height=18 /><br><input style='width:400px;' id='pltagid_icon' name='op[icon]' value='$q[icon]' /> <input type=\"file\" name=\"file_icon\" id=\"file_icon\" alt='' /><br>
					Доступ (Гость - все неавторизованные, Пользователь - все авторизованные)<br>
					<select multiple name='access[]' onchange='' style='height:150px;' title='Удерживайте ctrl чтобы отметить несколько!'>
					<option value='0'".((!isset($_GET["editid"]) || isset($_GET["editid"])&&strpos($q["access"],",0,")!==false)?" selected":"").">Всем кроме заблокированных</option>
					$c
					</select><br>
					<input type='checkbox' name='op[onlyfid]'".(($q["onlyfid"]==1||!isset($_GET["editid"]))?" checked":"")." value=1 /> Доступ для пользователей только с ForkPlayer ID<br>
					<input type='checkbox' name='op[onlymac]'".(($q["onlymac"]==1||!isset($_GET["editid"]))?" checked":"")." value=1 /> Отключить доступ через web версию сайта<br>
					<br>
					Вид категории 
					<input type='radio' ".($q["typeList"]==''?" checked":"")." name='pltag[typeList]' value='' /> Список <input type='radio' name='pltag[typeList]' ".($q["typeList"]=='start'?" checked":"")." value='start' /> Плитка<br>
					<input type='checkbox' name='op[showpage]'".(($q["showpage"]==1||!isset($_GET["editid"]))?" checked":"")." value=1 /> Показывать страницы с этой категории на главной<br>
					<input type='checkbox' name='op[showmain]'".(($q["showmain"]==1||!isset($_GET["editid"]))?" checked":"")." value=1 /> Разместить ссылку на эту категорию на главной
					<br> 
					
					
					Количество устройств(сессий) пользователя<br> 
					<input name='op[numsess]' value='".(isset($_GET["editid"])?"$q[numsess]":"3")."' /><br>
					
					Разрешить показывать видеоплеер на web версии сайта<br> 
					<select name='webplayer'>
					<option value='0'".(($q["webplayer"]==0)?" selected":"").">Только для youtube, rutube</option>
					<option value='1'".(($q["webplayer"]==1)?" selected":"").">Для всех видео кроме IPTV</option>
					<option value='2'".(($q["webplayer"]==2)?" selected":"").">Для всех файлов</option>
					</select><br> 
					Разрешить показывать видеоплеер на xml версии сайта (через ForkPlayer и другие)<br> 
					Для всех файлов<br> 
					<br><input type='submit' value='".(isset($_GET["editid"])?"Сохранить":"Создать")."' />
					</form>";
				}
			}
		}
		if($act=="user"){
			if($p<2) $limit="0,50";
			else $limit=(($p-1)*50).",".(($p-1)*50+50);
			$ch=q("select * from {$dbprefix}users limit $limit");
			if(count($ch)<1) $div.="Нет.<hr>";  
			$div.="<table><tr><td></td> <td>Email</td> <td>Роль сайта</td> <td>ForkPlayer ID</td> <td></td> </tr>";
			for($i=0;$i<count($ch);$i++){
				$div.="<tr><td><a href='/?do=/admin&act=adduser&editid=".$ch[$i]["id"]."'>id #".$ch[$i]["id"]." ".$ch[$i]["login"]."</a></td><td> ".$ch[$i]["email"]."</td><td> ".get_role($ch[$i]["role"])." </td><td> ".$ch[$i]["forkplayerid"]."</td><td>".(($ch[$i]["role"]<10)?"<a href=\"javascript:if(confirm('Вы уверены что хотите удалить ".$ch[$i]["login"]."?')) location='/?do=/admin&act=adduser&op=delete&id=".$ch[$i]["id"]."';\">удалить</a>":"")."</td></tr>";
			}
			$div.="</table><br><hr><a href='/?do=/admin&act=adduser'>Создать пользователя</a><br>";
			
		}
		if($act=="cats"){
			$ch=q("select * from {$dbprefix}category");
			if(count($ch)<1) $div.="Нет.";
			$div.="<table>";
			for($i=0;$i<count($ch);$i++){
				$count=q("select count(id) from {$dbprefix}page where category like '%,".$ch[$i]["id"].",%'",1);
				$div.="<tr><td><img  align='left' src='".$ch[$i]["icon"]."' onerror=\"this.style.display='none'\" id='img_icon' width=20 height=18 /></td><td><a href='/?do=/admin&act=addcat&editid=".$ch[$i]["id"]."'>id #".$ch[$i]["id"]." ".$ch[$i]["title"]."</a></td><td> страниц:$count[0]</td><td> Доступ: ".get_cat_roles($ch[$i]["access"])." </td><td><a href=\"javascript:if(confirm('Вы уверены что хотите удалить категорию ".$ch[$i]["title"]."?')) location='/?do=/admin&act=addcat&op=delete&id=".$ch[$i]["id"]."';\">удалить</a></td></tr>";
			}
			$div.="</table><hr>";
			$div.="<a href='/?do=/admin&act=addcat'>Создать категорию</a><br>";
			
		}
		if($act=="listpage"){
			//if($p<2) $limit=" limit 0,50";
			//else $limit=" limit ".(($p-1)*50).",".(($p-1)*50+50);
			$ch=q("select * from {$dbprefix}page order by created desc$limit");
			$div.="<table>";
			for($i=0;$i<count($ch);$i++){
				$page=json_decode($ch[$i]["src"],true);
				$div.="<tr><td>id#".$ch[$i]["id"]."<td><a href='/?do=/fml&id=".$ch[$i]["id"]."'>$page[title]</a></td><td><a href='/?do=/admin&act=addpage&op=createxml&editid=".$ch[$i]["id"]."'>Редакт.</a></td><td> ссылок:".count($page["channels"])."</td><td style='max-width:420px;'>  ".get_cat_roles_by_id($ch[$i]["category"])." </td><td>".$ch[$i]["created"]."</td><td>".date("d.m.Y H:i",$ch[$i]["modified"])."</td><td> <a href=\"javascript:if(confirm('Вы уверены что хотите удалить $page[title]?')) location='/?do=/admin&act=addpage&op=delete&id=".$ch[$i]["id"]."';\">удалить</a></td></tr>";
			}
			$div.="</table>";
		}
		$content.="$div";
	}
	elseif($do=="/auth"){
		
		if(!$logged) $content.="<form method='post'>
		Логин:<br>
		<input name=login><br>
		Пароль:<br>
		<input type=password name=password><br>
		<input type=submit value='Войти'>
		</form>
		<br>
		<a href='/remind'>Забыли пароль</a>
		<a href='/register'>Регистрация</a>
		";
		else {header("Location: /");exit;}
	}	
	elseif($do=="/module"){
		include "include/module.php";
	}
	elseif($do=="/plugin"){
		include "include/plugin.php";
	}
	elseif($do=="/fml"){
		if(!empty($_GET["id"]))	$page=get_page($_GET["id"]);
		$a=json_decode($page["src"],true);
		$TITLE=$page["title"];
		$_PL=array_merge($_PL,$a);
		if($page["encrypt"]){
			for($i=0;$i<count($a["channels"]);$i++){
				if($a["channels"][$i]["stream_url"]!=""){
					$a["channels"][$i]["stream_url"]=fEncrypt($a["channels"][$i]["stream_url"]);
				}
			}
		}
		$_CH=$a["channels"];
		
		$tp=file_get_contents("include/templates/singlepage.xml");
		$tpl=str_replace("{TITLE}",$page["title"],$tp);
		$tpl=str_replace("{VIEW}",$page["view"],$tpl);
		$tpl=str_replace("{CATEGORY}",get_cat_roles_by_id($page["category"]),$tpl);
		$tpl=str_replace("{AUTHOR}",get_author($page["author"]),$tpl);
		$tpl=str_replace("{ICON}",$page["icon"],$tpl);
		$tpl=str_replace("{DESCRIPTION}",(empty($page["description"]))?"Описания нет.":$page["description"],$tpl);
		$tpl=str_replace("{DATE}",$page["created"],$tpl);
		$tpl=str_replace("{EDIT}",(($userinfo["role"]==10)?"<a href='/?do=/admin&act=addpage&op=createxml&editid=".$page["id"]."'>Редактировать</a>":""),$tpl);
		$div="";
		for($i=0;$i<count($a["channels"]);$i++){
			$ch=$a["channels"][$i];
			if($ch["stream_url"]!="") {
				if($page["webplayer"]==2||($page["webplayer"]==1&&!$page["is_iptv"])||$userinfo["role"]==10||preg_match("/(youtube\.com||rutube\.)/",$ch["stream_url"])){
					 $u="javascript: show_player(\"$ch[stream_url]\",$page[is_iptv],$i)";
				}
				else $u="#";
			}
			else $u=$ch["playlist_url"];
			$div.="<div style='clear:both;'>".($i+1).". <a id='ch$i' href='$u'>$ch[title]</a><div style='color:$sitecolor;font-size:85%;margin-left:14px;max-height:300px;overflow:hidden;background:url($sitebackground);'>".((empty($ch["description"]))?"":$ch["description"])."</div></div>";
		}
		$tpl=str_replace("{CONTENT}",$div,$tpl);

		$content.="$tpl
		<div id='player' style='display:none;top:0px;left:0px;position:absolute;width:640px;height:360px;'></div>";
	}
	elseif(empty($do)||$do=="/"||$do=="/category"){
		
		$lmenu="";
		if(empty($do)||$do=="/") { 
			$_PL["typeList"]=$typelistStart;
			$before=getPluginMetaKey("[MAINBEFORE]",false);
			$_PL["style"]["cssid"]["content"]["before"]=$before[0]["src"];
		}
		elseif($do=="/category") {
			$qc=q("select * from {$dbprefix}category where id='".mysql_real_escape_string($_GET["id"])."'",1);
			$_PL["typeList"]=$qc["typeList"];
		}
		
		$plugins=getPlugins("main");
		for($i=0;$i<count($plugins);$i++){
			if(!$_ISPC) $_CH[]=["logo_30x30"=>$plugins[$i]["logo_30x30"],"playlist_url"=>$plugins[$i]["link"],"title"=>$plugins[$i]["name"]];
			$lmenu.="<img align='left' src='".$plugins[$i]["logo_30x30"]."' onerror=\"this.style.display='none';\" height=16 width=18 style='margin:-2px 2px;' /><a href='".$plugins[$i]["link"]."'>".$plugins[$i]["name"]."</a><br>";
			

			$div.=$tpl;
		}
		
		$cq=q("select * from {$dbprefix}category where showmain='1'");
		for($i=0;$i<count($cq);$i++){
			
			if(!$_ISPC) $_CH[]=["logo_30x30"=>$cq[$i]["icon"],"playlist_url"=>"$siteurl/?do=/category&id=".$cq[$i]["id"],"title"=>($_GET["id"]==$cq[$i]["id"]?"<b>".$cq[$i]["title"]."</b>":$cq[$i]["title"])."(".$cq[$i]["count"].")"];
			
			$lmenu.="<img align='left' src='".$cq[$i]["icon"]."' height=16 width=18 style='margin:-2px 2px;' />";
			if($_GET["id"]==$cq[$i]["id"]) $lmenu.="<b>".$cq[$i]["title"]."</b> (".$cq[$i]["count"].")<br>";
			else $lmenu.="<a href='/?do=/category&id=".$cq[$i]["id"]."'>".$cq[$i]["title"]."</a>(".$cq[$i]["count"].")<br>";
		} 
		if($do=="/category"&&($_PL["typeList"]=="start"||$typelistStart=="start"))  $_CH=[];
		$pages=get_pages($_GET["id"]);
		if(count($pages)<1) {
			$div.="Нет страниц или нет доступа к этому разделу сайта!";
			$_PL["notify"]=$div;
		}
		if(!empty($_GET["id"])) $TITLE=get_cat_roles_by_id($_GET["id"],false)." ";
		$tp=file_get_contents("include/templates/page.xml");

		for($i=0;$i<count($pages)&&$i<$siteperpage;$i++){
			if(!$_ISPC) {
				$_CH[]=["title"=>$pages[$i]["title"],"playlist_url"=>"$siteurl/?do=/fml&id=".$pages[$i]["id"],"logo_30x30"=>$pages[$i]["icon"],"description"=>$pages[$i]["description"]];
			}
			$tpl=str_replace("{TITLE}",$pages[$i]["title"],$tp);
			$tpl=str_replace("{CATEGORY}",get_cat_roles_by_id($pages[$i]["category"],false),$tpl);
			$tpl=str_replace("{LINK}","/?do=/fml&id=".$pages[$i]["id"],$tpl);
			$tpl=str_replace("{EDIT}",(($userinfo["role"]==10)?"<a href='/?do=/admin&act=addpage&op=createxml&editid=".$pages[$i]["id"]."'>Редактировать</a>":""),$tpl);
			$tpl=str_replace("{DATE}","Дата ".$pages[$i]["created"]." ".($pages[$i]["sticked"]?"Прилеплена":""),$tpl);
			$tpl=str_replace("{DESCRIPTION}",(empty($pages[$i]["description"]))?"Описания нет.":$pages[$i]["description"],$tpl);			
			$tpl=str_replace("{AUTHOR}","Автор ".get_author($pages[$i]["author"]),$tpl);
			$tpl=str_replace("{ICON}",$pages[$i]["icon"],$tpl);
			
			$div.=$tpl;
			//print_r($pages);
		}
		
		$content.="$div";
	}
	$footerPlugins=getPlugins();
	foreach($footerPlugins as $k=>$v){
		if($v["enabled"]&&$v["includeFoorter"]) {
			$PLUGIN=$v; 
			include_once(dirname(__FILE__)."/plugin/$v[id]/footer.php");
		}
	}
		
			
	if(strpos($sitechbkg,"rgb")!==false)
		$sitechbkg=rgba2hex($sitechbkg);
	if(strpos($sitechcolor,"rgb")!==false)
		$sitechcolor=rgba2hex($sitechcolor);
	$sitechbkgC=substr($sitechbkg,0,7);
	$sitechbkgA=substr($sitechbkg,7,2);
	if(strlen($sitechbkgA)==2&&abs($sitechbkgA)==0) $sitechbkg=" ";	
	if(!empty($sitebackground)) $_PL["style"]["cssid"]["site"]["background"]="url($sitebackground)";
	if(!empty($sitechbkg)) $_PL["style"]["channels"]["parent"]["default"]["background"]="none";
	if(!empty($sitecolor)) {
		$_PL["style"]["channels"]["parent"]["default"]["color"]=$sitecolor;
		$_PL["style"]["cssid"]["infoList"]["color"]=$sitecolor;
		$_PL["style"]["cssid"]["site"]["color"]=$sitecolor;
		$_PL["style"]["cssid"]["navigate"]["color"]=$sitecolor;
	}		
	if(!empty($sitechbkg)) $_PL["style"]["channels"]["parent"]["selected"]["background"]="none $sitechbkg";
	if(!empty($sitechcolor)) $_PL["style"]["channels"]["parent"]["selected"]["color"]=$sitechcolor;
	
	$content.="<script>applyStyles(".json_encode($_PL["style"]).");</script>";
	$t=str_replace("{STYLE}","a:link, a:visited {
   color: $sitecolor;
   text-decoration:none;
}
a:hover {
   color: $sitechcolor;
   text-decoration:none;
}
",$t);
	$t=str_replace("{TITLE}",$TITLE.$sitename,$t);
	$t=str_replace("{SITENAME}",$sitename,$t);
	$t=str_replace("{SIDE}",$lmenu,$t);
	$t=str_replace("{CONTENT}",$err.$content,$t);

	if($_ISPC) print "$t";
	else{
		if(empty($TITLE)) $_PL["navigate"]=$_PL["title"]=$sitename;
		else {
			$_PL["navigate"]="$sitename &raquo; $TITLE";
			$_PL["title"]=$TITLE;
		}
		$_PL["icon"]=$siteicon;
		$_PL["menu"]=$_MENU;
		$_PL["channels"]=$_CH;

	
		if($_GET["box_client"]=="lg") print json_encode($_PL);
		else{ 
			//print_r($_PL);
			print cxmljson($_PL);
		}
	}
}



























