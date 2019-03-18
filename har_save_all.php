<?php

date_default_timezone_set('Asia/Shanghai');
header("content-Type: text/html; charset=utf-8"); //语言强制
header('Cache-Control:no-cache,must-revalidate');
header('Pragma:no-cache');
//===================================================================================================
// .Har文件解析并下载资源
//===================================================================================================
//region

//1、填写保存目录或网址
//2、选择har文件上传 （一次只能解析一个）下载完成后删除
//3、解析后等待下载

//endregion
//===================================================================================================
//  环境检测
//===================================================================================================
//region
if (version_compare(PHP_VERSION, '5.3.0', '<=')){
    exit('PHP_VERSION IS LOW ! >= 5.3');
}

if(!function_exists('curl_init')){
    exit('PHP_EXTENDS curl Not Exists!');
}

if(!function_exists('mb_detect_encoding')){
    exit('PHP_EXTENDS mb_* Not Exists!');
}

define('ROOT_DIR', str_replace("\\", '/', dirname(__FILE__)));
define('DEBUG_HAR', true);

$cache_ok = isset($_GET['cache_ok']) ? $_GET['cache_ok'] : false;
$SAVE_DIR = isset($_REQUEST['save_dir']) ? $_REQUEST['save_dir'] : '';
//endregion
//===================================================================================================
//  界面设计
//===================================================================================================
//region

//$cache_ok = false;
//$cache_ok = true;

echo "<html><head><title>解析浏览器的har文件下载资源</title></head><body>";
echo "<fieldset>";
//缓存ok进入下载。
if($cache_ok){
    echo "<legend>消息提示框</legend>";
    echo "<form name='htmlsave' action='har_setp_down.php' method='post'  enctype='multipart/form-data' target='iframeState'>";
    echo " <iframe id='iframeState' name='iframeState' width='800' height='300' frameborder='1'></iframe>";
    echo "<input type='hidden' name='save_dir' value='{$SAVE_DIR}' />";
    echo "【<input type='submit' value='开始下载' />】";
    echo "【<a href='har_save_all.php'>  返回主页 </a>】";
    echo "【<a id='nextBtn' target='iframeState'>  继续下载 </a>】";
    echo "【<a href='index.html'>  返回功能选择页面 </a>】";
    echo "</form>";
    echo "<h3>下载文件列表：</h3><hr />";
    echo "<div id='msg' style='height: 450px; overflow:auto;border: 1px solid green;'></div>";
    exit;
}
else{
    echo "<legend>请输入解析Har资料</legend>";
    //1、 通过网址直接 保存
    echo "<form name='htmlsave' action='' method='post'  enctype='multipart/form-data'>";
    echo "1、保存目录或网址：<input type='text' value='' name='siteurl' style='width:800px;border: 2px solid green;height: 30px;'/> <span style='color:red'>* 必填 </span> ";
    echo "<hr />";
//2、通过直接复制 浏览器的源码  保存
//    echo "2、网页源代码：<textarea name='sitecode' rows='20' cols='100' style='border: 2px solid green;'></textarea>";
    echo "2、Har文件选择：<input type='file' name='har_file' />";
    echo "【<a href='index.html'>  返回功能选择页面 </a>】<hr />";
    echo "<input type='submit' value='提交' style='width: 300px;height: 50px;' />";
    echo "</form>";
}
echo "</fieldset>";
echo "</body></html>";

//endregion
//===================================================================================================
//  后台处理逻辑
//===================================================================================================
//region

$SITE_URL = empty($_POST['siteurl']) ? '' : $_POST['siteurl'];
if(empty($_POST['siteurl'])){showMsg('请输入保存目录或网址');exit;}

$save_path = '';
$upload_state = false;
$save_dir = get_domian($SITE_URL);

if(!is_dir($save_dir)){
    mkdir($save_dir);
}

//简单的文件上传处理
if(isset($_FILES['har_file']) && empty($_FILES['har_file']['error'])){
    $save_path = ROOT_DIR . '/' . $save_dir . '/' . md5($_FILES['har_file']['name']) . '.' . pathinfo($_FILES['har_file']['name'], PATHINFO_EXTENSION);
    $upload_state = move_uploaded_file($_FILES['har_file']['tmp_name'], $save_path);
}else{
    showMsg('har_file upload Error!'); exit;//上传失败，返回
}

if($upload_state){
    debugMsg('$har_file OK 文件上传成功');
    $data = array('save_dir'=> $save_dir, 'har_path'=>$save_path);
    file_put_contents($save_dir . '/har_config.php', configFileFormat($data));
}

//获取配置
$har_config = include $save_dir . "/har_config.php";

//开始解析
$har_parse_rs = har_parse($har_config['har_path']);



//todo 直接下载url中的HTML保存，并删除http://www.xxx.com/
//1、保存HTML文件
//2、获取har文件解析里边的url 并解析成 协议加域名
//3、替换HTML文件里边的链接并保存

$replace_urls = array();
$har_parse_content_path = $save_dir . '/har_parse_content.php';
if(file_exists($har_parse_content_path)){
    $har_parse_content = include $har_parse_content_path;
    if(is_array($har_parse_content)){
        foreach($har_parse_content as $item){
            $_temp_url = substr($item['url'],0, strpos($item['url'], '/', 10)) . '/';
            if(is_skip_url($_temp_url)){continue;}
            $replace_urls[] = $_temp_url;
        }
    }
}

$replace_urls = array_unique($replace_urls);

//HTML 文件内容去掉网址链接
//1、全路径去掉网址
//2、去掉//开头的网址
//3、去掉/的相对路径网址

$SITE_BODY = get_html_code($SITE_URL);
$SITE_BODY = str_ireplace($replace_urls, '', $SITE_BODY);
foreach($replace_urls as $key=>$value){
    $replace_urls[$key] = str_ireplace(array('http:', 'https:'), '', $value);
}
$SITE_BODY = str_ireplace($replace_urls, '', $SITE_BODY);
$SITE_BODY = preg_replace("/src[ |\t]*=[ |\t]*\'\//i", "src='", $SITE_BODY);
$SITE_BODY = preg_replace("/src[ |\t]*=[ |\t]*\"\//i", "src=\"", $SITE_BODY);

$SITE_BODY = preg_replace("/href[ |\t]*=[ |\t]*\'\//i", "href='", $SITE_BODY);
$SITE_BODY = preg_replace("/href[ |\t]*=[ |\t]*\"\//i", "href=\"", $SITE_BODY);

$SITE_BODY = str_ireplace('(/', '(', $SITE_BODY);

//输出HTML
$save_html_path = $save_dir .'/'. get_domian($SITE_URL) . date('_Y-m-d-h-i-s') . '.html';
file_put_contents($save_html_path, $SITE_BODY);

//解析是否完成？
if($har_parse_rs){
    echo "
   <script>
        setTimeout(function(){location.href='har_save_all.php?cache_ok=1&save_dir=' + encodeURI('" . ($save_dir) . "');},100);
   </script>
";
}else{
    debugMsg('解析失败，请重试！');
}
//endregion
//===================================================================================================
//  解析函数库
//===================================================================================================
//region





//endregion
//===================================================================================================
//  其他函数库
//===================================================================================================
//region

/**
 * 根据文件路径解析har 文件。解析出文件的url列表和文件mimeType
 * @param $har_path
 * @return bool
 */
function har_parse($har_path){
    global $save_dir;

    $parse_state = false;
    $parse_result = array();

    if(file_exists($har_path)){
        debugMsg('$har_config OK');
        $har_body = file_get_contents($har_path);
        $har_body = json_decode($har_body, true);

        if($har_body){
            debugMsg('$har_body OK');

            foreach($har_body['log']['entries'] as $item){
                if(is_skip_url($item['request']['url'])){
                    continue;
                }
                //解析提取出来的参数
                $request_arr = array(
                    'url' => $item['request']['url'],
                    'mimeType' => $item['response']['content']['mimeType'],
                );
                $parse_result[] = $request_arr;
            }

            if(false !== file_put_contents($save_dir . '/har_parse_content.php', configFileFormat($parse_result))){
                $parse_state = true;
            }
        }else{
            debugMsg('$har_body Error');
        }
    }

    return $parse_state;
}


function debugMsg($message){
    if(defined('DEBUG_HAR') && DEBUG_HAR){
        echo "\r\n";
        echo "Line: " . __LINE__;
        echo "   File: " . __FILE__;
        echo "<h3 style='color:red;'>{$message}</h3>";
        echo "\r\n";
    }
}

/**
 * 输出 include return array(); 的PHP内容
 * @param $data
 * @return string
 */
function configFileFormat($data)
{
    return "<?php\r\n" . "return " . var_export($data, true) . ";";
}

/**
 * 下载 指定URL 的 HTML 文档
 * @param $url
 * @return mixed
 */
function get_html_code($url){
    $ch = curl_init();
    $timeout = 10;
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)");
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $contents = curl_exec($ch);
    curl_close($ch);
    return $contents;
}


/**
 * 显示信息
 * @param string $msg
 */
function showMsg($msg = ''){
    echo "<h1 style='color:red;'>提示信息：{$msg}</h1>";
}

/**
 * 判断url是否有效 （字符串验证）
 * @param $url
 * @return bool
 */
function check_url($url){
    if(!preg_match('/https?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$url)){
        return false;
    }
    return true;
}


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
 * 得到域名的domain 如：www.baidu.com =》 baidu.com
 * @param $url
 * @return string
 */
function get_domian($url){
    $host = parse_url($url, PHP_URL_HOST);

    if(1 < substr_count($host, '.')){
        $domain = substr($host,strpos($host,".") + 1);
    }else{
        $domain = $host;
    }

    return $domain;
}

/**
 * 根据网址设置保存目录
 * @param $url
 * @return mixed
 */
function get_save_dir($url){
    $url = str_ireplace('http://', '', $url);
    $url = str_ireplace('https://', '', $url);
    $url = str_ireplace('/', '_', $url);
//    增加转码
//    $url = iconv('utf-8', 'gb2312//IGNORE', $url);
    return $url;
}

/**
 * 根据字符串扫描编码
 * @param $str
 * @return false|string
 */
function get_encode($str){
    return mb_detect_encoding($str, array('ASCII','UTF-8','GB2312','GBK','BIG5'));
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
 * 写入错误日志，比如 url、创建文件夹、curl等等
 * @param $data
 */
function log_record($data){
    global $save_dir;
//    检测编码与转换
    $is_chinese = chinese_test($save_dir);
    if(false !== $is_chinese) {
        $save_dir = mb_convert_encoding($save_dir, 'gb2312', 'utf-8');
    }

    file_put_contents('./' . $save_dir . '/down_skip.log', trim(var_export($data, true), '\'') . "\r\n\r\n", FILE_APPEND);
}


/**
 * 是否跳过某个网址的资源？如：百度地图、53客服、cnzz等
 * TODO 此函数需要后期编辑，新增网址
 * @param $url
 * @return bool
 */
function is_skip_url($url){
    $is_skip = false;

    $skip_urls = array(
        'https://www.baidu.com/',
        'https://tb.53kf.com/',
        'https://s13.cnzz.com/',
        'https://libs.baidu.com/',
        'https://c.cnzz.com/',
        'https://cnzz.mmstat.com/',
        'https://z7.cnzz.com/',
    );

    $host = parse_url($url, PHP_URL_HOST);

    foreach($skip_urls as $item){
        if(false !== stripos($item, $host)){
            $is_skip = true;
            log_record($url);  //写入跳过日志
            break;
        }
    }

    return $is_skip;
}
//endregion