<?php
@set_time_limit(0);
$access_id = ''; //填入access_id
$access_key = '';//填入access_key
$upload_api = 'http://upload.dvr.aodianyun.com/v2'; //上传接口地址

$fileName = 'demo.flv';//上传的文件路径

$offset = 0;
$flag = 0;
$sliceSize = 5 * 1024 * 1024;//分片大小
$partNum = 1;
$fileSize = filesize($fileName);

while ($flag != 1){
	$dataSize = 0;

    //计算本次上传分片的大小
    if ($offset + $sliceSize > $fileSize){
        $dataSize = $fileSize - $offset;
    }else{
        $dataSize = $sliceSize;
    }
	$part = urlencode(base64_encode(file_get_contents($fileName, false, NULL, $offset, $dataSize)));
	$param = array(
	    'access_id'=>$access_id,
	    'access_key'=>$access_key,
	    'fileName'=>$fileName,
	    'part'=>$part,
	    'partNum'=>$partNum
	);
	$res = curl($upload_api.'/DVR.UploadPart','parameter='.json_encode($param));
	echo $res.'<br>';
	if (!empty($res) && $flag == 0) {
		$res = json_decode($res,true);
		if($res['Flag'] == 100){
			$partNum++;
        	$offset += $dataSize;
    	}
    }

    if($offset == $fileSize){
    	$flag = 1;
    	$param = array(
	        'access_id'=>$access_id,
	        'access_key'=>$access_key,
	        'fileName'=>$fileName
	    );
	    echo curl($upload_api.'/DVR.UploadComplete','parameter='.json_encode($param));
    }
}

function curl($url,$data){
	$curl = curl_init();
	curl_setopt($curl,CURLOPT_URL,$url);
	curl_setopt($curl,CURLOPT_HEADER,false);
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl,CURLOPT_NOBODY,true);
	curl_setopt($curl,CURLOPT_POST,true);
	curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
	$return_str = curl_exec($curl);
	curl_close($curl);
	return $return_str;
}