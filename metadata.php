<?php
define("SERVER_NAME", "https://api.zbx1425.tk:8953/bcs-src");
$isCLI = ($argc>1 && $argv[1]=="cli") || (strpos(php_sapi_name(), "cli")!==false); 
//php-cgi called from cli with additional parameter
if ($isCLI) header_remove();
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"]) || $isCLI){
	if (isset($_GET["act"])){
		print("<button onclick=\"location.href='metadata.php'\">返回</a></button>");
		onFileUpdate(true);
	} else {
		$needRefresh = false;
		if (is_file("index/packs.json") && is_file("index/authors.json")){
			//echo "<table>";
			$metadataTime = min(filemtime("index/packs.json"), filemtime("index/authors.json"));
			foreach (glob("*", GLOB_ONLYDIR) as $author) {
				if (strpos($author,".")===false) continue;
				foreach (RScanDir($author,"",true) as $file){
					//echo "<tr><td>".date("m-d H:i:s", filemtime($file))."</td><td>".$file."</td></tr>";
					if (filemtime($file) > $metadataTime){
						$needRefresh = true;
						break;
					}
				}
			}
			//echo "<tr><td>".date("m-d H:i:s", filemtime("."))."</td><td>.</td></tr>";
			if (filemtime(".") > $metadataTime) $needRefresh = true;
			//echo "</table>";
		} else {
			$needRefresh = true;
		}
		if ($needRefresh){
			onFileUpdate();
			if (!$isCLI){
				print("<button onclick=\"location.href='index.php'\">返回</a></button>");
				print("<p>元数据刷新成功!</p>");
			}
		} else if (!$isCLI) {
			print("<button onclick=\"location.href='metadata.php?act'\">刷新</a></button>");
			print("<p>未检测到新文件。</p>");
			print("<p>按钮强制手动刷新。</p>");
		}
	}
}

function onFileUpdate($debug = false){
	$archs = array("ob", "h2", "b5");
	$metadata = array();
	$maintainers = array();
	if ($debug) echo "<ol>";
	foreach (glob("*", GLOB_ONLYDIR) as $author) {
		if (!is_file($author."/author.ini")) continue;
		if ($debug) echo "<li>".$author."</li><ol>";
		$authordata = processName(parse_ini_string(
			characet(file_get_contents($author."/author.ini"))), false);
		if ($authordata === null) continue;
		tryFile($authordata, "Description", $author."/author.txt");
		tryFile($authordata, "Description", $author."/author.html");
		$authordata["ID"] = implode("@", explode(".", $author, 2));
		foreach (RScanDir($author, ".ini") as $packset){
			if ($packset == $author."/author.ini") continue;
			$packdata = processName(parse_ini_string(
			characet(file_get_contents($packset))), true);
			if ($packdata === null) continue;
			$nameparts = explode("_", basename($packset, ".ini"));
			$pnwve = dirname($packset)."/".$nameparts[0];
			if (count($nameparts) < 2) continue;
			if ($debug) echo "<li>".$packset."</li><ol>";
			tryFile($packdata, "Thumbnail", $pnwve.".jpg");
			tryFile($packdata, "Thumbnail", $pnwve.".png");
			tryFile($packdata, "Thumbnail", file_ext_replace($packset,".jpg"));
			tryFile($packdata, "Thumbnail", file_ext_replace($packset,".png"));
			tryFile($packdata, "Description", $pnwve.".txt");
			tryFile($packdata, "Description", $pnwve.".html");
			tryFile($packdata, "Description", file_ext_replace($packset,".txt"));
			tryFile($packdata, "Description", file_ext_replace($packset,".html"));
			$packdata["ID"] = $nameparts[0]; $packdata["Version"] = $nameparts[1];
			$packdata["Author"] = $authordata["ID"];
			$packdata["TimeStamp"] = filemtime($packset);
			foreach ($archs as $arch){
				$archpack = file_ext_replace($packset,"_".$arch.".zip");
				if (!is_file($archpack)) continue;
				if ($debug) echo "<li>".$archpack."</li>";
				$packdata["FileSize_".strtoupper($arch)] = filesize_formatted($archpack);
				$packdata["File_".strtoupper($arch)] = SERVER_NAME."/".spaceEncode($archpack);			
			}
			array_push($metadata, $packdata);
			if ($debug) echo "</ol>";
		}
		array_push($maintainers, $authordata);
		if ($debug) echo "</ol>";
	}
	if (!is_dir("index")) mkdir("index");
	file_put_contents("index/authors.json", json_encode($maintainers));
	usort($metadata, "packageSort");
	file_put_contents("index/packs.json", json_encode($metadata));
	if ($debug) echo "</ol>";
}

function tryFile(&$target, $key, $file){
	if (is_file($file)){
		$target[$key]=SERVER_NAME."/".spaceEncode($file);
	}
}

function spaceEncode($url){
	return str_replace(" ","%20",$url);
}

function filesize_formatted($path){
    $size = filesize($path);
    $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB'); //This should be enough
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}

function processName($packdata, $route){
	if ($route){
		$filter = array("Name_LO", "Name_EN", "Name_SA", 
		"Origin_LO", "Origin_EN", "Origin_SA",
		"Homepage", "Description", "AutoOpen", "ForceView");
		$default = array("ID"=>"", "Description"=>"",
		"Version"=>"1.0", "Author"=>"", "TimeStamp"=>0);
		if (!processLOEN($packdata, "Name")) return null;
		processLOEN($packdata, "Origin");
	} else {
		$filter = array("Name_LO", "Name_EN", "Name_SA", "Homepage", "Description");
		$default = array("ID"=>"", "Description"=>"");
		if (!processLOEN($packdata, "Name")) return null;
	}
	return array_merge($default, array_intersect_key($packdata, array_flip($filter)));
}

function processLOEN(&$packdata, $tag){
	if (isset($packdata[$tag."_LO"]) && isset($packdata[$tag."_EN"])){
		
	} else if (isset($packdata[$tag."_LO"])){
		$packdata[$tag."_EN"] = $packdata[$tag."_LO"];
	} else if (isset($packdata[$tag."_EN"])){
		$packdata[$tag."_LO"] = $packdata[$tag."_EN"];
	} else {
		return false;
	}
	return true;
}

function packageSort($a, $b){
	if ($a["Author"]!=$b["Author"]) return strcmp($a["Author"], $b["Author"]);
	if ($a["Name_EN"]!=$b["Name_EN"]) return strcmp($a["Name_EN"], $b["Name_EN"]);
	return -version_compare($a["Version"], $b["Version"]);
}

function file_ext_replace($filename, $newext){
    return preg_replace('/.[^.]*$/', $newext, $filename);
}

function endWith($haystack, $needle) {   
	$length = strlen($needle);  
	if($length == 0){    
      return true;  
	}  
	return (substr($haystack, -$length) === $needle);
}

function characet($data){
  if( !empty($data) ){   
    $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;  
    if( $fileType != 'UTF-8'){  
      $data = mb_convert_encoding($data ,'utf-8' , $fileType);  
    }  
  }  
  return $data;   
}

function RScanDir($dir, $ext="", $includeDir = false){
    $file_arr = scandir($dir);
    $files = array();
    foreach($file_arr as $item){
        if($item!=".." && $item !="." && $item[0]!="."){
            if(is_dir($dir."/".$item)){
				foreach (RScanDir($dir."/".$item, $ext) as $item){
				    array_push($files, $item);
				}
				if ($includeDir) array_push($files, $item);
            }else{
				if (endWith($item, $ext)) array_push($files, $dir."/".$item);
            }
        }
    }
	if ($includeDir) array_push($files, $dir);
    return $files;
}
?>