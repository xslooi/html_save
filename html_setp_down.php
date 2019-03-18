<?php
/**
 *  步进 下载 资源文件
 */

date_default_timezone_set('Asia/Shanghai');
header("content-Type: text/html; charset=utf-8"); //语言强制
header('Cache-Control:no-cache,must-revalidate');
header('Pragma:no-cache');
//===================================================================================================
//  参数获取
//===================================================================================================
//region


//===================================================================================================
//  下载逻辑
//===================================================================================================
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

$SITE_URL = "";

//得到 网址缓存
if(file_exists('./' .$SAVE_DIR. '/'. 'dir.log')){

    $useful_dir = unserialize(file_get_contents($SAVE_DIR. '/'. 'dir.log'));
    $SITE_URL = empty($useful_dir['site_url']) ? '' : $useful_dir['site_url'];
    $SAVE_DIR = empty($useful_dir['save_dir']) ? '' : $useful_dir['save_dir'];
}else{
    echo "不存在dir.log缓存文件！请返回主页重新下载<br/>";
    exit;
}


//===================================================================================================
//  下载资源调用函数
//===================================================================================================
//region

//下载css文件资源
if(file_exists('./' .$SAVE_DIR. '/'. 'useful_css_hrefs.log')){
    echo "useful_css_hrefs is download ...<br/>";
    $useful_css_hrefs = unserialize(file_get_contents($SAVE_DIR. '/'. 'useful_css_hrefs.log'));
    $count = count($useful_css_hrefs);
    down_setp($count, 'useful_css_hrefs.log', $useful_css_hrefs);
}else{
    echo "不存在css资源文件或者下载已完成！<br/>";
}

//下载 图片资源
if(file_exists('./' .$SAVE_DIR. '/'. 'useful_img_srcs.log')){
    echo "useful_img_srcs  is download ...<br/>";
    $useful_css_hrefs = unserialize(file_get_contents($SAVE_DIR. '/'. 'useful_img_srcs.log'));
    $count = count($useful_css_hrefs);
    down_setp($count, 'useful_img_srcs.log',$useful_css_hrefs);
}else{
    echo "不存在img资源文件或者下载已完成！<br/>";
}
//下载js资源
if(file_exists('./' .$SAVE_DIR. '/'. 'useful_js_srcs.log')){
    echo "useful_js_srcs  is download ...<br/>";
    $useful_css_hrefs = unserialize(file_get_contents($SAVE_DIR. '/'. 'useful_js_srcs.log'));
    $count = count($useful_css_hrefs);
//    echo $count;exit;
    down_setp($count, 'useful_js_srcs.log', $useful_css_hrefs);
}else{
    echo "不存在js资源文件或者下载已完成！<br/>";
}

//下载css_url中资源
if(file_exists('./' .$SAVE_DIR. '/'. 'useful_css_urls.log')){
    echo "useful_css_urls  is download ...<br/>";
    $useful_css_urls = unserialize(file_get_contents($SAVE_DIR. '/'. 'useful_css_urls.log'));
    $count = count($useful_css_urls);
    down_setp($count, 'useful_css_urls.log', $useful_css_urls);
}else{
    echo "不存在css_url中资源文件或者下载已完成！<br/>";
}
//exit;
//下载css文件中的资源文件
if(file_exists('./' .$SAVE_DIR. '/'. 'useful_cssfile_url.log')){
    echo "useful_cssfile_url  is download ...<br/>";
    $useful_css_hrefs = unserialize(file_get_contents($SAVE_DIR. '/'. 'useful_cssfile_url.log'));
    $useful_css_hrefs = array_unique($useful_css_hrefs);
    $useful_css_hrefs = array_values($useful_css_hrefs);
    $count = count($useful_css_hrefs);
    down_setp($count, 'useful_cssfile_url.log', $useful_css_hrefs);
}else{
    echo "不存在css_file 中的资源文件或者下载已完成！<br/>";
}
//endregion下载资源调用函数==================================================================


echo "<h1>========全部文件下载完成！=========</h1>";
echo "<script>alert('恭喜，全部文件下载完成！');</script>";


//===================================================================================================
//  下边是处理函数
//===================================================================================================
//region

/**
 * 步进 下载函数
 * @param $count
 * @param $filename
 * @param $downloadArray
 */
function down_setp($count, $filename, $downloadArray){
    global $SAVE_DIR;

//    var_dump($filename);exit;
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
                   setTimeout(function(){location.href='html_setp_down.php?save_dir=' + encodeURI('" . ($SAVE_DIR) . "');},100);
               </script>
            
            ";

//        操作文件要转换成gb2312
        if(false !== $is_chinese) {
            $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'gb2312', 'utf-8');
        }

        unlink($SAVE_DIR. '/'. $filename);
        exit('success!');
    }

//    下载单个文件
    $down_result = curl_downfile($downloadArray[$i], $filename);

    if($down_result){
        echo $downloadArray[$i] . "----下载完成！<br /><hr />";
    }else{
        log_record($downloadArray[$i]);
        echo $downloadArray[$i] . "----下载失败！<br /><hr />";
    }


//    去掉 ？ 后边的 参数再 判断
     if(false !== strpos($downloadArray[$i], '?')){
         $downloadArray[$i] = substr($downloadArray[$i], 0, strpos($downloadArray[$i], '?'));
     }


//    解析已经下载的 css 文件里边的 资源内容
    if('useful_css_hrefs.log' == $filename){
        echo "下载的是css文件！<br />";

        $fileurl = parse_downurl($downloadArray[$i]);
        $css_codes = file_get_contents($fileurl);

        //css 文件中的链接处理
        $first_css = substr($downloadArray[$i], 0, strrpos($downloadArray[$i], '/'));
        $sec_css = substr($first_css, 0, strrpos($first_css, '/'));
        $third_css = substr($sec_css, 0, strrpos($sec_css, '/')); //todo 未判断目录

        $css_in_urls_p = parse_css_url($css_codes);


//      保存css中的url资源文件
        $css_in_urls = array();
        foreach($css_in_urls_p as $item=>$value){
            $value = trim($value, '\'');
            $value = trim($value, '\"');
            //css 目录内判断
            $first_str = substr($value, 0, 1);
            $second_str = substr($value, 0, 2);
            if('.' != $first_str && '/' != $first_str && 'h' != strtolower($first_str)){
                $css_in_urls[] = $first_css . '/' . $value;
            }
            elseif('//' == $second_str){
                $css_in_urls[] = 'http:' . $value;
            }
            elseif('/' == $first_str){
                $css_in_urls[] = $value;
            }
            elseif('../../' == substr($value, 0, 6)){
                $css_in_urls[] = $third_css . '/' . substr($value, 6);
            }
            elseif('../' == substr($value, 0, 3)){
                $css_in_urls[] = $sec_css . '/' . substr($value, 3);
            }
            else{
                $css_in_urls[] = $value;
            }

        }
//        保存css文件
        if(file_exists('./' .$SAVE_DIR. '/'. 'useful_cssfile_url.log')){
            $useful_css_urls = unserialize(file_get_contents($SAVE_DIR. '/'. 'useful_cssfile_url.log'));
            $useful_css_urls = array_merge($useful_css_urls, $css_in_urls);
//            去除重复重新排列键名
            $useful_css_urls = array_unique($useful_css_urls);
            $useful_css_urls = array_values($useful_css_urls);

            file_put_contents($SAVE_DIR . '/' . 'useful_cssfile_url.log', serialize($useful_css_urls));
        }else{
            file_put_contents($SAVE_DIR . '/' . 'useful_cssfile_url.log', serialize($css_in_urls));
        }

    }

//    1、是css文件、
//    2、读取css 文件 解析css文件成URL列表
//    3、保存成css 资源列表文件  附加
    if(false !== $is_chinese) {
        $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'utf-8', 'gb2312');
    }

    echo "
           正在下载{$i}----{$downloadArray[$i]} <br />
            <script>
             parent.document.getElementById('nextBtn').setAttribute('href', 'html_setp_down.php?setp=" . ($i+1) ."&save_dir=' + encodeURI('" . ($SAVE_DIR) . "'));
            </script>
            
           <script>    
              setTimeout(function(){location.href='html_setp_down.php?setp={$i}&save_dir=' + encodeURI('" . ($SAVE_DIR) . "');},100);
           </script>
           
           <script>
             parent.document.getElementById('msg').innerHTML += {$i} + '---->' + '". $downloadArray[$i] . "' + '<br />';
            </script>
            
            
        ";
    exit;
}


//===================================================================================================
//  下边是处理函数库
//===================================================================================================
//region


/**
 * 判断 URL 资源链接是否有效
 * @param $url
 * @return bool
 */
function verity_url($url)
{
    // 配置header
    stream_context_set_default(
        array(
            'http' => array(
                'method' => 'GET',
                'header' =>
                    "User-Agent: Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)\r\n" .
                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                    "Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2\r\n" .
                    "Accept-Encoding: gzip, deflate\r\n" .
                    "Connection: close\r\n"
            )
        )
    );
    //此处@ ：因为有url是无效的
    $header = get_headers($url,1);
    if(false === $header){
        return false;
    }
    //此处@： 是因为 header 内部值有数组
//    var_dump($header);
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
function curl_downfile($url, $filename='')
{

    $fileurl = parse_downurl($url, $filename);
    $url = get_http_url($url);

    echo 'File Save : ';
    echo($fileurl);
    echo '<br />';
//exit;
    echo 'File Get : ';
    echo($url);
    echo '<br />';

//    exit;
    if(!verity_url($url)){
        log_record($url);
        return false;
    }
    echo $url . "____正在下载。。。<br />";
//exit;

    if(createDir($fileurl)){
        echo $fileurl . "____目录创建成功！<br />";
    }else{
        log_record($fileurl);
        echo "目录创建失败 <br />";
    }
//exit;
    if(file_exists($fileurl)){
//        echo $fileurl . "____文件已存在！<br />";
        return true;
    }

    //去除URL连接上面可能的引号
    //$url = preg_replace( '/(?:^['"]+|['"/]+$)/', '', $url );
    $fp = fopen($fileurl,'wb');
    if(false === $fp){
        log_record($fileurl);
        echo '' . $fileurl . '创建失败！';
        return false;
    }


    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL, $url);
    curl_setopt($curl,CURLOPT_FILE, $fp);
    curl_setopt($curl,CURLOPT_HEADER,0);
    curl_setopt($curl,CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($curl,CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
    curl_setopt($curl,CURLOPT_TIMEOUT,300);
    curl_setopt($curl,CURLOPT_HTTP_VERSION, '1.0');
    curl_setopt($curl,CURLOPT_HTTPHEADER, array (
        "Accept-Language: zh-cn",
        "Accept-Encoding: identity"
    ));

    try{
        curl_exec($curl);
    }catch(Exception $e){
        log_record($e->getMessage());
        var_dump($e->getMessage());
    }

    curl_close($curl);
    fclose($fp);

    return true;
}



/**
 * 得到 文件url 如：http://www.baidu.com/images/20178-8342.jpg
 * @param $url
 * @return string
 */
function parse_downurl($url, $filename=''){
    global $SAVE_DIR;
//    $SAVE_DIR = '';
//    检测编码与转换
    $is_chinese = chinese_test($SAVE_DIR);
    if(false !== $is_chinese) {
        $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'gb2312', 'utf-8');
    }

    $fileurl = '';
    $save_dir = get_save_dir($SAVE_DIR);

//    过滤问号
    if(false !== strpos($url, '?')){
        $url = substr($url, 0, strpos($url, '?'));
//        file_put_contents('url.log', $url, FILE_APPEND);
//        exit;
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

    //根据下载资源文件名修改文件名后缀 如：.css .js 等
    if(!empty($filename)){
        switch($filename){
            case 'useful_css_hrefs.log':
                if('.css' != strtolower(substr($fileurl, -4))){
                    log_record($fileurl);
                    $fileurl .= '.css';
                }
                break;
            case 'useful_js_srcs.log':
                if('.js' != strtolower(substr($fileurl, -3))){
                    log_record($fileurl);
                    $fileurl .= '.js';
                }
                break;
        }
    }


//var_dump($fileurl);
//    exit;
    return $fileurl;
}

/**
 * 得到 文件绝对 URL ：http://www.cnvcn.cn/uploads/allimg/170214/1-1F2142319400-L.png
 * @param $url
 * @return string
 */
function get_http_url($url){
    global $SITE_URL;
    $parse_url = parse_url($SITE_URL);

//    echo $url;
//    exit;

    $fileurl = '';
    if(empty($SITE_URL)){ exit('http_url Empty!');}


    if('http:' == substr($url ,0, 5) || 'https:' == substr($url ,0, 6)){
        $fileurl = $url;
    }
    elseif('//' == substr($url ,0, 2)){
        $fileurl = 'http:' . $url;
    }
    elseif('/' == substr($url ,0, 1)){
        $fileurl = trim($url, '/');
        $fileurl = str_replace('\/\/', '\/', $fileurl);
        $fileurl = $parse_url['scheme'] . '://' . $parse_url['host'] . '/' . $fileurl;
    }
    elseif(-1 < strpos($url, '?')){
        exit($url . 'is Parameter!');
    }else{
        //如果有 index.html
//        如果后缀没有index.html
        $fileurl = dirname($SITE_URL);
        $fileurl = $fileurl . '/' . $url;
    }


//    echo $fileurl;exit;
    //    检测编码与转换
    $is_chinese = chinese_test($fileurl);
    if(false !== $is_chinese) {
        $fileurl = mb_convert_encoding($fileurl, 'utf-8', 'gb2312');
    }
//    echo $fileurl;exit;

    return $fileurl;
}

/**
 * 得到域名的domain 如：www.baidu.com =》 baidu.com
 * @param $url
 * @return string
 */
function get_domian($url){
    $arr = parse_url($url);
    $file = $arr['host'];
    $ext = substr($file,strpos($file,".")+1);
    return $ext;
}


function get_save_dir($url){

    $url = str_ireplace('http://', '', $url);
    $url = str_ireplace('https://', '', $url);
    $url = str_ireplace('/', '_', $url);
    return $url;
}


/**
 * 得到 css 文件中 url 的连接
 * @param $body_css
 * @param int $matchAtomic
 * @return array
 */
function parse_css_url(&$body_css, $matchAtomic = 1){
    $css_img_links = array();
    $regex_css_img = "/url *\((.{5,100})\)/iU";  //U是非贪婪模式 5,100 不处理base64编码
    preg_match_all($regex_css_img, $body_css, $css_img_links, PREG_SET_ORDER);
    $useful_css_imgs = array();
    foreach ($css_img_links as $item=>$value)
    {
        $useful_css_imgs[] = $value[$matchAtomic];
    }
    return $useful_css_imgs;
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
function log_record($data){
    global $SAVE_DIR;
//    检测编码与转换
    $is_chinese = chinese_test($SAVE_DIR);
    if(false !== $is_chinese) {
        $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'gb2312', 'utf-8');
    }

    $save_dir = get_save_dir($SAVE_DIR);

    file_put_contents($save_dir . '/down_error.log', var_export($data, true) . "\r\n\r\n", FILE_APPEND);
}
//endregion