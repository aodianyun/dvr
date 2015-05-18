<?php

// Make sure file is not cached (as it happens for example on iOS devices)
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$access_id = ''; //填入access_id
$access_key = '';//填入access_key
$upload_api = 'http://upload.dvr.aodianyun.com/v2'; //上传接口地址

//ajax error
if(isset($_GET['action']) && $_GET['action'] == 'error'){
    $key = 'error';
    $error = $_COOKIE[$key];
    echo $error;
    if($error){
        cookie($key,'');
    }
    exit;
}

// 5 minutes execution time
@set_time_limit(5 * 60);

// Get a file name
if(isset($_POST["name"])){
    $fileName = $_POST["name"];
}
elseif(isset($_GET["name"])){
    $fileName = $_GET["name"];
}
elseif(!empty($_FILES)){
    $fileName = $_FILES["file"]["name"];
}

// Chunking might be enabled
$chunk = 0;
$chunks = 0;
if(isset($_POST["chunk"])){
    $chunk = intval($_POST["chunk"]);
}
elseif(isset($_GET["chunk"])){
    $chunk = intval($_GET["chunk"]);
}
if(isset($_POST["chunks"])){
    $chunks = intval($_POST["chunks"]);
}
elseif(isset($_GET["chunks"])){
    $chunks = intval($_GET["chunks"]);
}

if(!empty($_FILES)){
    if($_FILES["file"]["error"] || !$_FILES["file"]["size"] || !is_uploaded_file($_FILES["file"]["tmp_name"])){
        cookie('error','无法移动上传的文件。');
        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to move uploaded file."}, "id" : "id"}');
    }
}

$part = urlencode(base64_encode(file_get_contents($_FILES["file"]["tmp_name"])));
$partNum = $chunk+1;//partNum从1开始
$param = array(
    'access_id'=>$access_id,
    'access_key'=>$access_key,
    'fileName'=>$fileName,
    'part'=>$part,
    'partNum'=>$partNum
);
$res = curl($upload_api.'/DVR.UploadPart','parameter='.json_encode($param));
if(!empty($res)){
    $res = json_decode($res,true);
    if($res['Flag'] != 100){
        cookie('error',$res['FlagString']);
        die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Part file upload api call failed."}, "id" : "id"}');
    }
}else{
    cookie('error','Part文件上传接口或网络异常');
    die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Part file upload api or network anomaly."}, "id" : "id"}');
}

// Check if file has been uploaded
if(!$chunks || $chunk == $chunks - 1){
    $param = array(
        'access_id'=>$access_id,
        'access_key'=>$access_key,
        'fileName'=>$fileName
    );
    $success = curl($upload_api.'/DVR.UploadComplete','parameter='.json_encode($param));
    if(!empty($success)){
        $res = json_decode($success,true);
        if($res['Flag'] != 100){
            cookie('error',$res['FlagString']);
            die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Upload complete api call failed."}, "id" : "id"}');
        }
    }else{
        cookie('error','上传完成接口或网络异常');
        die('{"jsonrpc" : "2.0", "error" : {"code": 105, "message": "Upload complete api or network anomaly."}, "id" : "id"}');
    }
}

// Return Success JSON-RPC response
die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');


//cookie
function cookie($key, $value='', $time=86400, $path='/'){
    $_COOKIE[$key] = $value;
    setcookie($key, $value, (time() + $time), $path);
}

//post upload file
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