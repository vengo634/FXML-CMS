<?php

$links=getPluginMetaKey("rek",true);
$maxRoll=0;
foreach($links as $k=>$v){
	//print_r(getArrayAvailableUserRoles());
	//print_r(explode(",",$v["src"]["roles"]));
	//print "rek:".is_access(getArrayAvailableUserRoles(),explode(",",$v["src"]["roles"]))."\n";
	if($v["src"]["work"]&&(empty($COUNTRY)||empty($v["src"]["country"])||strpos($v["src"]["country"],$COUNTRY)!==false)
		&&(empty($v["src"]["roles"])||!is_access(getArrayAvailableUserRoles(),explode(",",$v["src"]["roles"])))) {
		$priotity=ceil(abs($v["src"]["priority"]));
		if($priotity<1) $priotity=1;
		$advert[]=["x1"=>$maxRoll,"x2"=>($maxRoll+$priotity),"title"=>$v["src"]["title"],"country"=>$COUNTRY,"access"=>$v["src"]["country"],"stream_url"=>$v["src"]["stream_url"]];
		$maxRoll+=$priotity;
	}
	//$Channels[]=["logo_30x30"=>"","title"=>$v["src"]["title"]." ".date("d.m.Y H:i",$v["src"]["date"])." ".($v["src"]["work"]?"Запущена":"Остановлена")." [".$v["src"]["priority"]."] ".$v["src"]["country"],"playlist_url"=>"$PLUGIN[link]&mode=addurl&lid=$v[id]","infolink"=>$v["src"]["stream_url"]."<br>".$v["src"]["advertlink"],"description"=>"","menu"=>$menu]; 
}
if(count($advert)) $_PL["allvast"]="$PLUGIN[link]&mode=vast";
	
if(count($advert)&&0){
	$rand=rand(0,$maxRoll);
	foreach($advert as $k=>$v){
		if($rand<=$v["x2"]&&$rand>=$v["x1"]) break;
	}
	print_r($v);
}