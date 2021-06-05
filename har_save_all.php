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
// 检测模块是否加载
//var_dump(get_loaded_extensions());
//var_dump(get_extension_funcs('openssl'));
//var_dump(get_defined_functions ());
//var_dump(extension_loaded('openssl'));
if(!function_exists('openssl_open')){
    exit('PHP_EXTENDS openssl Not Exists!');
}

//var_dump(ini_get('upload_max_filesize'));
//var_dump(ini_get('post_max_size'));

define('ROOT_DIR', str_replace("\\", '/', dirname(__FILE__)));
define('DEBUG_HAR', false); // 显示解析bug信息

$cache_ok = isset($_GET['cache_ok']) ? $_GET['cache_ok'] : false;
$SAVE_DIR = isset($_REQUEST['save_dir']) ? $_REQUEST['save_dir'] : '';
$cookieSiteurl = isset($_COOKIE['siteurl']) ? $_COOKIE['siteurl'] : '';

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
    echo " <iframe id='iframeState' name='iframeState' width='100%' height='300' frameborder='1'></iframe><br >";
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
    echo "1、保存目录或网址：<input type='text' value='{$cookieSiteurl}' name='siteurl' style='width:600px;border: 2px solid green;height: 30px;'/> ";
    echo "【<input type='checkbox' name='ismobile' id='ismobile' value='1' checked='checked'><label for='ismobile'>手机版</label>（仅对下载HTML有效）】";
    echo "<span style='color:red'>* 必填 （网站主域名需要带协议，可以 / 结尾，二级页面也用主域名不用带参数） </span> <hr />";
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
// 存储网页原网址
if(!empty($SITE_URL)){
    setcookie('siteurl', $SITE_URL, time() + 3600, '/');
}
else{
    if(empty($cookieSiteurl)){
        // 删除所有cookie
        echo "<script>
			+function(){
			    var cookies = document.cookie.split(\";\");
                var domain = '.'+location.host;

                if(cookies.length > 0)
                {
                    for (var i = 0; i < cookies.length; i++) {
                    var cookie = cookies[i];
                    var eqPos = cookie.indexOf(\"=\");
                    var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                    document.cookie = name + \"=;expires=Thu, 01 Jan 1970 00:00:00 GMT; Domain=\"+domain+\"; path=/\";
                    }
                }
			}();
</script>";
    }
}

if(empty($_POST['siteurl'])){showMsg('请输入保存目录或网址');exit;}
$ismobile = isset($_POST['ismobile']) ? true : false;

$save_path = '';
$upload_state = false;
$save_dir = get_domian($SITE_URL);

if(empty($save_dir)){
    showMsg('Save dir Error!'); exit;//上传失败，返回
}

if(!is_dir($save_dir)){
    mkdir($save_dir);
}

//简单的文件上传处理
if(isset($_FILES['har_file']) && empty($_FILES['har_file']['error'])){
    $ext = pathinfo($_FILES['har_file']['name'], PATHINFO_EXTENSION);
    if('har' != strtolower($ext)){
        showMsg('har_file Type Error! extension = ' . $ext, 'error');
        exit;//上传失败，返回
    }
    $save_path = ROOT_DIR . '/' . $save_dir . '/' . md5($_FILES['har_file']['name']) . '.' . $ext;
    $upload_state = move_uploaded_file($_FILES['har_file']['tmp_name'], $save_path);
}
else{
    showMsg('har_file upload Error!'); exit;//上传失败，返回
}

if($upload_state){
    debugMsg('$har_file OK 文件上传成功');
    $data = array('save_dir'=> $save_dir, 'har_path'=>$save_path, 'ismobile'=>$ismobile);
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

$SITE_BODY = get_html_code($SITE_URL, $ismobile);
$SITE_BODY = str_ireplace($replace_urls, '', $SITE_BODY);
foreach($replace_urls as $key=>$value){
    $replace_urls[$key] = str_ireplace(array('http:', 'https:'), '', $value);
}
$SITE_BODY = str_ireplace($replace_urls, '', $SITE_BODY);
$SITE_BODY = preg_replace("/src[ |\t]*=[ |\t]*\'\//i", "src='", $SITE_BODY);
$SITE_BODY = preg_replace("/src[ |\t]*=[ |\t]*\"\//i", "src=\"", $SITE_BODY);

$SITE_BODY = preg_replace("/href[ |\t]*=[ |\t]*\'\//i", "href='", $SITE_BODY);
$SITE_BODY = preg_replace("/href[ |\t]*=[ |\t]*\"\//i", "href=\"", $SITE_BODY);

$SITE_BODY = preg_replace('/url\s*\(/', 'url(', $SITE_BODY);
$SITE_BODY = str_ireplace("url('/", "url('", $SITE_BODY);
$SITE_BODY = str_ireplace('url(/', 'url(', $SITE_BODY);

//输出HTML
$save_html_path = $save_dir .'/'. get_domian($SITE_URL) . date('_Y-m-d-h-i-s') . '.html';
file_put_contents($save_html_path, $SITE_BODY);

//解析是否完成？
if($har_parse_rs){
    echo "
   <a href='har_save_all.php?cache_ok=1&save_dir='" . ($save_dir) . "'>解析完成跳转下载页面</a>
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
                    'mimeType' => isset($item['response']['content']['mimeType']) ? $item['response']['content']['mimeType'] : '',
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
function get_html_code($url, $ismobile=false){
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    //添加手机版UA
    if($ismobile){
        $user_agent = 'Mozilla/5.0 (Linux; Android 9.0; BKL-AL20 Build/HUAWEIBKL-AL20; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.132 MQQBrowser/6.2 TBS/044409 Mobile Safari/537.36 wxwork/2.7.2 MicroMessenger/6.3.22 NetType/WIFI Language/zh';
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
    curl_setopt($curl, CURLOPT_TIMEOUT, 10000); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

    curl_setopt($curl,CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
//    curl_setopt($curl,CURLOPT_HTTPHEADER, array (
//        "Accept-Language: zh-cn",
//        "Accept-Encoding: identity"
//    ));

//    curl_setopt($curl,CURLOPT_FILE, $fp); //设置下载文件名称

    try{
        $contents = curl_exec($curl);
    }catch(Exception $e){
        var_dump($e->getMessage());
    }

    curl_close($curl);
//    fclose($fp);
    return $contents;
}


/**
 * 显示信息
 * @param string $msg 显示信息
 * @param string $type 字体颜色 success: green, warning: orange, error: red, default: blue
 */
function showMsg($msg = '', $type='blue'){
    if('success' == $type){
        echo "<h1 style='color:green;'>提示信息：{$msg}</h1>";
    }
    elseif('warning' == $type){
        echo "<h1 style='color:orange;'>提示信息：{$msg}</h1>";
    }
    elseif('error' == $type){
        echo "<h1 style='color:red;'>提示信息：{$msg}</h1>";
    }
    else{
        echo "<h1 style='color:blue;'>提示信息：{$msg}</h1>";
    }
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

    if(empty($host)){
        return $url;
    }

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
 * bug：如有百度网址的资源则不能下载
 * @param $url
 * @return bool
 */
function is_skip_url($url){
    $is_skip = false;

    $skip_urls = array(
        'baidu.com',
        '53kf.com',
        'cnzz.com',
        'mmstat.com',
    );

    foreach($skip_urls as $item){
        if(false !== stripos($url, $item)){
            $is_skip = true;
            log_record($url);  //写入跳过日志
            break;
        }
    }

    return $is_skip;
}
//endregion