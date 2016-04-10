<?php

/**
 * Author: [Gavin](https://guojianxiang.com)
 */

$filePath = '/data/base/sphinx_xml/new/ciku.txt' ;
$handle = @fopen($filePath, "r");
if ($handle) {
	$i = 1 ;
	$count = 0 ;
	while (!feof($handle)) {
		$buffer = '' ;

		$buffer = fgets($handle, 4096);
		$buffer =  trim(htmlspecialchars(mb_convert_encoding($buffer, 'utf-8', 'gbk'))) ;
		if($buffer && !$next){
			//key
			$keyword = $buffer ;
			$reply = '' ;
			$next = 1 ;
		}elseif($next){
			$count ++ ;
			//reply
			$reply = $buffer ;
			//每1000个写入一个xml文件
			$xmlStr .= "<sphinx:document id=\"$count\"><word>".$keyword."</word><reply>".$reply."</reply></sphinx:document>" ;
			if($count%1000==0){
				//写入文件
				$xmlName = '2015_'.(intval($count/1000)).'.xml' ;
				file_put_contents('/data/base/sphinx_xml/'.$xmlName, $xmlStr) ;
				$xmlStr = '' ;
			}
			echo $keyword.'---'.$reply.'=='.$count;
			$keyword = '' ;
			$next = 0 ;
		}else{

		}

		$i++ ;
	}
	fclose($handle);
}