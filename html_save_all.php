<?php
date_default_timezone_set('Asia/Shanghai');
header("content-Type: text/html; charset=utf-8"); //语言强制
header('Cache-Control:no-cache,must-revalidate');
header('Pragma:no-cache');
//===================================================================================================
//  下载解析 HTML 文件 所有信息
//===================================================================================================
//region
//1、下载网页文档

//2、解析得到图片列表
//3、解析得到js文件列表
//4、解析得到css文件列表

//5、根据图片列表下载图片  附加： 页面中有背景图片
//6、根据js文件列表下载js
//7、根据css文件列表下载css文件

//8、读取下载的css文件，得到css中图片文件列表
//9、解析css文件中图片地址
//10、根据css文件中图片列表下载图片

//11、所有文件下载完成，解析整个 文档，替换为本地路径
//12、生成文件名完成


// 保存html中的 资源 如 js css images 等等等
//默认 保存到 domain 文件夹名下 如： www.baidu.com  =》 baidu.com

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

$cache_ok = isset($_GET['cache_ok']) ? $_GET['cache_ok'] : false;
$SAVE_DIR = isset($_REQUEST['save_dir']) ? $_REQUEST['save_dir'] : '';
$cookieSiteurl = isset($_COOKIE['siteurl']) ? $_COOKIE['siteurl'] : '';
$ISMOBILE = isset($_REQUEST['ismobile']) ? true : false;

//endregion
//===================================================================================================
//  界面设计
//===================================================================================================
//region

//$cache_ok = false;
//$cache_ok = true;

echo "<html><head><title>根据网址或者源代码下载网页资源</title></head><body>";
echo "<fieldset>";
//缓存ok进入下载。
if($cache_ok){
    echo "<legend>消息提示框</legend>";
    echo "<form name='htmlsave' action='html_setp_down.php' method='post'  enctype='multipart/form-data' target='iframeState'>";
    echo " <iframe id='iframeState' name='iframeState' width='100%' height='300' frameborder='1'></iframe> <br >";
    echo "<input type='hidden' name='save_dir' value='{$SAVE_DIR}' />";
    echo "<input type='hidden' name='ismobile' value='{$ISMOBILE}' />";
    echo "【<input type='submit' value='开始下载' />】";
    echo "【<a href='html_save_all.php'>  返回主页 </a>】";
    echo "【<a id='nextBtn' target='iframeState'>  继续下载 </a>】";
    echo "【<a href='index.html'>  返回功能选择页面 </a>】";
    echo "</form>";
    echo "<h3>下载文件列表：</h3><hr />";
    echo "<div id='msg' style='height: 450px; overflow:auto;border: 1px solid green;'></div>";
    exit;
}
else{
    echo "<legend>请输入网址或者和源码</legend>";
    //1、 通过网址直接 保存
    echo "<form name='htmlsave' action='' method='post'  enctype='multipart/form-data'>";
    echo "1、网页源网址：<input id='siteurl' type='text' value='{$cookieSiteurl}' name='siteurl' style='width:800px;border: 2px solid green;height: 30px;' placeholder='请输入网址不带参数'/>";
    echo "【<input type='checkbox' name='ismobile' id='ismobile' value='1'><label for='ismobile'>手机版</label>】";
    echo "<span style='color:red'>* 必填（可以只写网址-网页自动下载，需要带上协议如：http://www.baidu.com） </span> <hr />";
    //2、通过直接复制 浏览器的源码  保存
    echo "2、网页源代码：<textarea name='sitecode' rows='20' cols='100' style='border: 2px solid green;' placeholder='请输入网页源代码'></textarea>";
    echo "<span style='color:red'>（填写源码后则网页只保存源码部分） </span> ";
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
$SITE_CODE = empty($_POST['sitecode']) ? '' : $_POST['sitecode'];

//是否转义
if(get_magic_quotes_gpc()){
    $SITE_CODE = stripslashes($SITE_CODE);
}

$SITE_URL = urldecode($SITE_URL);
$SITE_URL = trim($SITE_URL, '/');

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

//初始化 资源文件 数组
$useful_img_srcs = array();
$useful_js_srcs = array();
$useful_css_urls = array();
$useful_css_hrefs = array();

//一、根据网址得到 页面 源码
if(check_url($SITE_URL)){

    $SITE_DOMAIN = get_domian($SITE_URL);

    if(verity_url($SITE_URL)){

        $SITE_CODE = empty($SITE_CODE) ? get_html_code($SITE_URL, $ISMOBILE) : $SITE_CODE;
        $SITE_BODY = $SITE_CODE;
        //HTML代码替换为本地路径
        $SITE_BODY = str_ireplace($SITE_URL, '', $SITE_BODY);
        $SITE_BODY = str_ireplace('href="/', 'href="', $SITE_BODY);
        $SITE_BODY = str_ireplace("href='/", "href='", $SITE_BODY);
        $SITE_BODY = str_ireplace('src="/', 'src="', $SITE_BODY);
        $SITE_BODY = str_ireplace("src='/", "src='", $SITE_BODY);
        // 网页代码替换为本地END
//        $SITE_CODE = <<<HTML
//        <style>
//        .body{background: url(http://pmo7fc8f3.pic34.websiteonline.cn/upload/4_dhk1.jpg)}
//        .body{background: url('http://pmo7fc8f3.pic34.websiteonline.cn/upload/4_dhk2.jpg')}
//        .body{background: url("http://pmo7fc8f3.pic34.websiteonline.cn/upload/4_dhk3.jpg')}
//        .body{background: url('http://pmo7fc8f3.pic34.websiteonline.cn/upload/4_dhk4.jpg")}
//        .body{background: url("http://pmo7fc8f3.pic34.websiteonline.cn/upload/4_dhk5.jpg")}
//</style>
//<script type="text/javascript" src="http://pmo7fc8f3.pic34.websiteonline.cn/jquery-1.11.1.min1.js"></script>
//<script src="http://pmo7fc8f3.pic34.websiteonline.cn/jquery-1.11.1.min2.js"</script>
//<script type="text/javascript">
// console.log(549641);
//</script>
//<script src="http://pmo7fc8f3.pic34.websiteonline.cn/jquery-1.11.1.min3.js"
//<script type="text/javascript" src="http://pmo7fc8f3.pic34.websiteonline.cn/jquery-1.11.1.min4.js"></script>
//<script src="http://pmo7fc8f3.pic34.websiteonline.cn/jquery-1.11.1.min5.js"> </script>
//HTML;

        $SITE_CODE = pretreatment_html($SITE_CODE); //预处理HTML代码

        $SAVE_DIR = get_save_dir($SITE_URL);

        //解析文档
//1、图片url
        $useful_img_srcs = parse_img_src($SITE_CODE);
        $useful_img_srcs = array_unique($useful_img_srcs);
        $useful_img_srcs = array_values($useful_img_srcs);
//2、js url
        $useful_js_srcs = parse_js_src($SITE_CODE);
        $useful_js_srcs = array_unique($useful_js_srcs);
        $useful_js_srcs = array_values($useful_js_srcs);
//3、css url
        $useful_css_urls = parse_css_url($SITE_CODE);
        $useful_css_urls = array_unique($useful_css_urls);
        $useful_css_urls = array_values($useful_css_urls);
//4、页面中 css url 资源
        $useful_css_hrefs = parse_css_href($SITE_CODE);
        $useful_css_hrefs = array_unique($useful_css_hrefs);
        $useful_css_hrefs = array_values($useful_css_hrefs);


//        编码检测与转换 windows 系统
        $textEncode = get_encode($SAVE_DIR);
//        var_dump($textEncode);
//        exit;
        $is_chinese = chinese_test($SAVE_DIR);
        if(false !== $is_chinese){
            $SAVE_DIR = mb_convert_encoding($SAVE_DIR,'gb2312', $is_chinese);

            $SITE_URL = mb_convert_encoding($SITE_URL,'gb2312', $is_chinese);
//            $SAVE_DIR = iconv($is_chinese, 'gb2312//IGNORE', $SAVE_DIR);
        }

        if(!is_dir($SAVE_DIR)){
            mkdir($SAVE_DIR);
        }

        $dir_log = array('site_url'=>$SITE_URL, 'save_dir'=>$SAVE_DIR);

        file_put_contents($SAVE_DIR . '/' . 'dir.log', serialize($dir_log));
        file_put_contents($SAVE_DIR . '/' . 'useful_img_srcs.log', serialize($useful_img_srcs));
        file_put_contents($SAVE_DIR . '/' . 'useful_js_srcs.log', serialize($useful_js_srcs));
        file_put_contents($SAVE_DIR . '/' . 'useful_css_urls.log', serialize($useful_css_urls));
        file_put_contents($SAVE_DIR . '/' . 'useful_css_hrefs.log', serialize($useful_css_hrefs));

//        exit;
        //        保存当前 静态页面
//        $is_chinese = chinese_test($SAVE_DIR);
        if(false !== $is_chinese) {
            $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'utf-8', 'gb2312');
        }

        echo "
   <script>
        setTimeout(function(){location.href='html_save_all.php?cache_ok=1&save_dir=' + encodeURI('" . ($SAVE_DIR) . "') + '&ismobile=" . ($ISMOBILE) . "';},1000);
   </script>
";


//        文件操作转换为gb2312
        if(false !== $is_chinese) {
            $SAVE_DIR = mb_convert_encoding($SAVE_DIR, 'gb2312', 'utf-8');
        }
        file_put_contents($SAVE_DIR .'/'. get_domian($SITE_URL) . date('_Y-m-d-h-i-s') . '.html', $SITE_BODY);
        showMsg('下载资源解析完成！');

//        var_dump($SITE_CODE);
    }
    else{
        showMsg($SITE_URL . ' 网址不可用！');
    }
}else{
    if(empty($SITE_URL)){
        showMsg('请输入网址或者网页源代码！');
    }else{
        showMsg('网址格式错误！');
    }
}


//endregion
//===================================================================================================
//  解析函数库
//===================================================================================================
//region


/**
 * 预解析HTML内容
 * todo 此处替换规律还有待完善
 * @param $body
 * @return mixed
 */
function pretreatment_html(&$body){
//    exit($body);
    $body = str_replace(array("\r", "\n", "\r\n"), '', $body);
//    exit($body);
    $body = preg_replace("/(\t)+/", " ", $body);
//    exit($body);
    $body = preg_replace("/( )+/", " ", $body);

    $body = preg_replace(array("/src( )?=( )?/i", "/href( )?=( )?/i", "/( )?>/i"), array("src=", "href=", ">"), $body);

//    exit($body);
    return $body;
}

/**
 * 得到 文档中 <img src 的内容
 * @param $body
 * @param int $matchAtomic
 * @return array
 */
function parse_img_src(&$body, $matchAtomic = 1){
    $src_links = array();
    /**  图片的src */
    /*    $regex_src = '/<img.*?src=[\'|\\"](.*?(?:[\.gif | \.jpg]))[\'|\\"].*?\s?.*?[\/]?>/'; // 2017年7月4日01:32:38 xslooi 修改 增加 换行*/
//    $regex_src = '/<img.*?src=[\'|\\"](.*?(?:[\.gif | \.jpg| \.png]))[\?|\'|\\"]/i'; // 2018年7月21日15:39:09 xslooi 修改 到？ 之前结束
    $regex_src = '/<img .*?src=[\'|\\"]?(.*?)[\'|\\"].*?>/i'; // 2018年10月31日17:47:12 xslooi 分步骤获取 TODO此处未匹配 没有 '" 包裹的情况
    // 全部的 src
//    $regex_src = '/src=[\'|\\"]?(.*?)[\'|\\"].*?/i';
    preg_match_all($regex_src, $body, $src_links, PREG_SET_ORDER);
    $useful_src = array();
    foreach ($src_links as $key=>$value)
    {
        $useful_src[] = $value[$matchAtomic];
    }

    return $useful_src;
}

/**
 * 得到 文档中 css 的href 连接
 * @param $body
 * @param int $matchAtomic
 * @return array
 */
function parse_css_href(&$body, $matchAtomic = 1){
    // 匹配css 中的 样式
    $css_href_links = array();
//    $regex_css = "/<link[^<>]+href *\= *[\"']?([^ '\"]+)['\"]/i";
//    $regex_css = "/<link[^<>]+?href=['|\\\"](.*?(?:[\.css]))[\?|'|\\\"]/i";  //2018年7月21日15:43:20 xslooi 修改 到？前边
    $regex_css = "/<link .*?href=['|\"]?(.*?)['|\"].*?>/i";  //2018年11月1日10:26:23 xslooi 重新匹配预处理过的HTML

    preg_match_all($regex_css, $body, $css_href_links, PREG_SET_ORDER);
    $useful_css = array();
    foreach ($css_href_links as $item=>$value)
    {
        $useful_css[] = $value[$matchAtomic];
    }

    return $useful_css;
}

/**
 * 得到 css 文件中 url 的连接
 * @param $body_css
 * @param int $matchAtomic
 * @return array
 */
function parse_css_url(&$body_css, $matchAtomic = 1){
    $css_img_links = array();
//    $regex_css_img = "/url *\((.*)\)/i";
//    $regex_css_img = "/url *\([\'|\"]?(.*)[\'|\"]?\)/i"; //2017年10月7日16:41:54 xslooi 修改
//    $regex_css_img = "/url *\([\'|\"]?(.*)[\'|\"|\?]?\)/i"; //2018年7月21日15:44:47 xslooi 修改 到？号前边
    $regex_css_img = "/url\([\'|\"]?(.*?)[\'|\"]?\)/i"; //2018年11月1日10:30:04 xslooi 重新匹配预处理过的HTML

    preg_match_all($regex_css_img, $body_css, $css_img_links, PREG_SET_ORDER);
    $useful_css_imgs = array();
    foreach ($css_img_links as $item=>$value)
    {
        $useful_css_imgs[] = $value[$matchAtomic];
    }

    return $useful_css_imgs;
}


/**
 * 解析文档中 a 标签中的href
 * @param $body
 * @param int $matchAtomic 配模式数组编号 2 因为js 脚本中很多没有src属性
 * @return array|string
 */
function parse_js_src(&$body, $matchAtomic = 2){
    if(empty($body)){return '';}
    $js_src_links = array();
//    $regex_js_src = "/<script[^<>]+src *\= *[\"']?([^ '\"]+)['\"]/i";
//    $regex_js_src = "/<script[^<>]+?src *\= *['|\\\"](.*?(?:[\.js]))[\?|'|\\\"]/i";  //2018年7月21日15:41:57 xslooi 修改，到？号之前结束
/*    $regex_js_src = "/<script .[^>]*?src=['|\"]?(.*?)['|\"].*?>/i";  //2018年11月1日10:42:38 xslooi 重新匹配预处理过的HTML*/
    $regex_js_src = "/<script (.[^<>]*)?src=['|\"]?(.*?)['|\"].*?/i";  //2018年11月1日10:42:38 xslooi 重新匹配预处理过的HTML

//preg_match_all("/<a[^<>]+href *\= *[\"']?(http\:\/\/[^ '\"]+)/i", $body, $body_links, PREG_SET_ORDER);
    preg_match_all($regex_js_src, $body, $js_src_links, PREG_SET_ORDER);

    $useless_js_src = array();
    foreach ($js_src_links as $key=>$value) {
        $useless_js_src[] = $value[$matchAtomic];
    }

    return $useless_js_src;
}



//endregion
//===================================================================================================
//  其他函数库
//===================================================================================================
//region


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
    $arr = parse_url($url);
    $file = $arr['host'];
    $ext = substr($file,strpos($file,".")+1);
    return $ext;
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

//endregion