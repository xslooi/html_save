<?php
/**
 *  步进 下载 资源文件
 */

date_default_timezone_set('Asia/Shanghai');
header("content-Type: text/html; charset=utf-8"); //语言强制
header('Cache-Control:no-cache,must-revalidate');
header('Pragma:no-cache');
//======================================================================================================================
//  参数获取
//======================================================================================================================
//region


//======================================================================================================================
//  下载逻辑
//======================================================================================================================
//region
echo "<h1 style='color: red;'>当前时间" . date("Y-m-d H:i:s") . '--' . getMillisecond() . "</h1><hr />";

$SAVE_DIR = isset($_REQUEST['save_dir']) ? $_REQUEST['save_dir'] : '';

//编码检测与转换 windows 系统
$is_chinese = chinese_test($SAVE_DIR);
//if((false !== $is_chinese) && ('gb2312' !== $is_chinese) ){
if((false !== $is_chinese)  ){
    $SAVE_DIR = mb_convert_encoding($SAVE_DIR,'gb2312',"utf-8");
}else{
    $SAVE_DIR = urldecode($SAVE_DIR);
}

//得到 网址缓存
if(file_exists('./' .$SAVE_DIR. '/'. 'har_config.php')){

    $useful_dir = include ($SAVE_DIR. '/'. 'har_config.php');

    $SAVE_DIR = empty($useful_dir['save_dir']) ? '' : $useful_dir['save_dir'];
    $HAR_PATH = empty($useful_dir['har_path']) ? '' : $useful_dir['har_path'];
    $ISMOBILE = empty($useful_dir['ismobile']) ? '' : $useful_dir['ismobile'];

}else{
    echo "不存在har_config.php缓存文件！请返回主页重新下载<br/>";
    exit;
}


//======================================================================================================================
//  下载资源调用函数
//======================================================================================================================
//region


//下载har文件资源
if(file_exists('./' .$SAVE_DIR. '/'. 'har_parse_content.php')){
    echo "<span style='color:green'>har file in url list is download ...</span><br/>";
    $har_content = include($SAVE_DIR. '/'. 'har_parse_content.php');
    $count = count($har_content);
    down_setp($count, 'har_parse_content.php', $har_content);
}else{
    echo "不存在har_parse_content.php资源文件或者下载已完成！<br/>";
}


//endregion下载资源调用函数===============================================================================================


echo "<h1 style='color:green'>========全部文件下载完成！=========</h1>";
echo "<script>alert('恭喜，全部文件下载完成！');</script>";
//删除配置和解析文件
unlink($SAVE_DIR. '/'. 'har_config.php');
unlink($HAR_PATH);


//======================================================================================================================
//  下边是处理函数
//======================================================================================================================
//region

/**
 * 步进 下载函数
 * @param $count
 * @param $filename
 * @param $downloadArray
 */
function down_setp($count, $filename, $downloadArray){
    global $SAVE_DIR;

//    检测编码与转换
    $is_chinese = chinese_test($SAVE_DIR);
    if(false !== $is_chinese) {
        $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'utf-8', 'gb2312');
    }

    $i = isset($_GET['setp']) ? intval($_GET['setp']) : -1;
    $i++;


    if($count <= $i){
        echo 'ok!';
        echo "

               <script>
                   parent.document.getElementById('msg').innerHTML += '<h1>{$filename}----下载完成！</h1>';
                   setTimeout(function(){location.href='har_setp_down.php?save_dir=' + encodeURI('" . ($SAVE_DIR) . "');},100);
               </script>
            
            ";

//        操作文件要转换成gb2312
        if(false !== $is_chinese) {
            $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'gb2312', 'utf-8');
        }

        //删除缓存资料
        unlink($SAVE_DIR. '/'. $filename);
        exit('success!');
    }

//    下载单个文件
    $down_result = curl_downfile($downloadArray[$i]['url']);

    if($down_result){
        echo "<span style='color:green'>URL：</span>" . $downloadArray[$i]['url'] . "----下载成功！<br /><hr />";
    }else{
        echo "<span style='color:red'>URL：</span>" . $downloadArray[$i]['url'] . "----下载失败！<br /><hr />";
    }


    if(false !== $is_chinese) {
        $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'utf-8', 'gb2312');
    }

    echo "
            <script>
             parent.document.getElementById('nextBtn').setAttribute('href', 'har_setp_down.php?setp=" . ($i+1) ."&save_dir=' + encodeURI('" . ($SAVE_DIR) . "'));
            </script>
            
           <script>    
              setTimeout(function(){location.href='har_setp_down.php?setp={$i}&save_dir=' + encodeURI('" . ($SAVE_DIR) . "');},100);
           </script>
           
           <script>
             parent.document.getElementById('msg').innerHTML += {$i} + '---->' + '". $downloadArray[$i]['url'] . "' + '<br />';
            </script>
            
            
        ";
    exit;
}


//======================================================================================================================
//  下边是处理函数库
//======================================================================================================================
//region


/**
 * 判断 URL 资源链接是否有效
 * @param $url
 * @return bool
 */
function verity_url($url)
{
    global $ISMOBILE;
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    //添加手机版UA
    if($ISMOBILE){
        $user_agent = 'Mozilla/5.0 (Linux; Android 9.0; BKL-AL20 Build/HUAWEIBKL-AL20; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.132 MQQBrowser/6.2 TBS/044409 Mobile Safari/537.36 wxwork/2.7.2 MicroMessenger/6.3.22 NetType/WIFI Language/zh';
    }

    // 模拟提交数据函数
    $curl = curl_init(); // 启动一个CURL会话

    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
//    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip'); //curl解压gzip页面内容

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在 只有在cURL低于7.28.1时CURLOPT_SSL_VERIFYHOST才支持使用1表示true，高于这个版本就需要使用2表示了（true也不行）。
    curl_setopt($curl, CURLOPT_USERAGENT, $user_agent); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
//    curl_setopt($curl, CURLOPT_POST, 0); // 发送一个常规的Post请求
//    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 1000); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 1); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    curl_setopt($curl, CURLOPT_ENCODING, ''); //允许执行gzip

    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
//        echo 'Errno'.curl_error($curl);//捕抓异常
        return false;
    }

    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $header = substr($tmpInfo, 0, $headerSize); // 根据头大小去获取头信息内容

    curl_close($curl); // 关闭CURL会话

    if(false === $header){
        return false;
    }

    if(is_array($header)){
        $header = json_encode($header);
    }

    if(false !== stripos($header, '200')){
        return true;
    }else{
        return false;
    }
}

/**
 * curl 下载 文件
 * @param $url  参数是 没有域名的 url 如： /upload/img/20170332.jpg
 * @return bool
 */
function curl_downfile($url)
{
    global $ISMOBILE;
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    //添加手机版UA
    if($ISMOBILE){
        $user_agent = 'Mozilla/5.0 (Linux; Android 9.0; BKL-AL20 Build/HUAWEIBKL-AL20; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.132 MQQBrowser/6.2 TBS/044409 Mobile Safari/537.36 wxwork/2.7.2 MicroMessenger/6.3.22 NetType/WIFI Language/zh';
    }

    $file_path = parse_downurl($url);

    if(!verity_url($url)){
        echo "<span style='color:red'>VERITY：</span>" . $url . "____URL验证失败！<br />";
        log_record($url, 'verity_url');
        return false;
    }

    echo "<span style='color:green'>URL：</span>" . $url . "____正在下载。。。<br />";

    if(createDir($file_path)){
//        echo "<span style='color:green'>PATH：</span>" . $file_path . "____目录创建成功！<br />";
    }else{
        log_record($file_path, 'createDIR');
//        echo "<span style='color:red'>PATH：</span>" . $file_path . "____目录创建失败！<br />";
    }

    if(file_exists($file_path)){
        return true;
    }

    //去除URL连接上面可能的引号
    //$url = preg_replace( '/(?:^['"]+|['"/]+$)/', '', $url );
    $fp = fopen($file_path,'wb');
    if(false === $fp){
        log_record($file_path, 'fopen');
        echo "<span style='color:red'>FILE：</span>" . $file_path . "____路径文件创建失败！<br />";
        return false;
    }
    else{
        echo "<span style='color:green'>PATH：</span>" . $file_path . "____路径文件创建成功！<br />";
    }

    //curl 库开始下载
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL, $url); // 要访问的地址

//    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);  //证书问题
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip'); //curl解压gzip页面内容

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在 只有在cURL低于7.28.1时CURLOPT_SSL_VERIFYHOST才支持使用1表示true，高于这个版本就需要使用2表示了（true也不行）。
    curl_setopt($curl, CURLOPT_USERAGENT, $user_agent); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
//    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
//    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 2000); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

    curl_setopt($curl,CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
//    curl_setopt($curl,CURLOPT_HTTPHEADER, array (
//        "Accept-Language: zh-cn",
//        "Accept-Encoding: identity"
//    ));

    curl_setopt($curl,CURLOPT_FILE, $fp); //设置下载文件名称

    try{
       $curl_rs = curl_exec($curl);
//       var_dump($curl_rs);
    }catch(Exception $e){
        log_record($e->getMessage(), 'curl down');
        var_dump($e->getMessage());
    }

    curl_close($curl);
    fclose($fp);

    return true;
}



/**
 * 根据url解析为文件保存path
 * 如：http://www.baidu.com/images/20178-8342.jpg 解析为 baidu.com/images/20178-8342.jpg
 * @param $url
 * @return string
 */
function parse_downurl($url){
    global $SAVE_DIR;

//    检测编码与转换
    $is_chinese = chinese_test($SAVE_DIR);
    if(false !== $is_chinese) {
        $save_dir = mb_convert_encoding($SAVE_DIR, 'gb2312', 'utf-8');
    }else{
        $save_dir = $SAVE_DIR;
    }

//    过滤问号
    if(false !== strpos($url, '?')){
        $url = substr($url, 0, strpos($url, '?'));
    }

//    过滤http 生成 文件 dir
    if('http' == substr($url ,0, 4)){
        $fileurl = substr($url, strpos($url, '/', 10));
        $fileurl = trim($fileurl, '/');
        $fileurl = $save_dir . '/' . $fileurl;
    }
    else{
        $fileurl = trim($url, '/');
        $fileurl = str_replace('\/\/', '\/', $fileurl);
        $fileurl = $save_dir . '/' . $fileurl;
    }

    return $fileurl;
}


/**
 * 创建 多层次目录 如：/uploads/allimg/20173323
 * @param $aimUrl
 * @return bool
 */
function createDir($aimUrl) {
    // 增加中文转码

    $aimUrl = substr($aimUrl,0,strrpos($aimUrl,'/'));
    if(is_dir($aimUrl)){return true;}
    $aimDir = '';
    $arr = explode('/', $aimUrl);
    $result = true;

    foreach ($arr as $str) {
        $aimDir .= $str . '/';
        if (!file_exists($aimDir)) {
            $result = mkdir($aimDir);
        }
    }

    return $result;
}

/**
 * 检测是否有中文字符
 * /[\x{4e00}-\x{9fa5}]+/u  UTF8编码:正则表达式匹配中文;
 * /[".chr(0xa1)."-".chr(0xff)."]+/  GB2312,GBK编码:正则表达式匹配中文;
 * @param $str
 * @return bool|string
 */
function chinese_test($str){

    // UTF8编码:正则表达式匹配中文;
    if(preg_match('/[\x{4e00}-\x{9fa5}]+/u',$str)){
        return 'utf-8';
    }
    // GB2312,GBK编码:正则表达式匹配中文;
    if(preg_match("/[".chr(0xa1)."-".chr(0xff)."]+/",$str)){
        return 'gb2312';
    }

    return false;
}

/**
 * 返回当前时间的毫秒数
 * @return float
 */
function getMillisecond() {
    list($s1, $s2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
}

/**
 * 写入错误日志，比如 url、创建文件夹、curl等等
 * @param $data
 */
function log_record($data, $type=''){
    global $SAVE_DIR;
//    检测编码与转换
    $is_chinese = chinese_test($SAVE_DIR);
    if(false !== $is_chinese) {
        $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'gb2312', 'utf-8');
    }

    file_put_contents('./' . $SAVE_DIR . '/down_error.log', $type . ': ' . trim(var_export($data, true), '\'') . "\r\n\r\n", FILE_APPEND);
}
//endregion