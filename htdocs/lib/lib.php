<?

function connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass){
    mysql_connect($sql_host, $sql_user, $sql_pass) or die("Couldn't connect to mysql server");
    mysql_select_db($sql_base) or die("Couldn't connect to database");
    mysql_query("set names koi8r;");
}

//Заэкранировать все спецсимволы в массиве
//источник: http://www.ddvhouse.ru/articles/text/7/
function addslashes_for_array(&$arr)
{
   foreach($arr as $k=>$v)
   {
       if (is_array($v))
       {
           addslashes_for_array($v);
           $arr[$k] = $v;
       }
       else
       {
           $arr[$k] = addslashes($v);
       }
   }
}

/*
function addslashes_for_array($value){
  if (is_array($value)) {
    $value=array_map('addslashes_for_array',$value);
  } else {
    $value=addslashes($value);
  }
  return $value;
}
*/

//Заэкранировать все спецсимволы в переданных от юзера строках
//источник: http://www.ddvhouse.ru/articles/text/7/
function fix_magic_quotes_gpc(){
   if (!get_magic_quotes_gpc())
   {
       addslashes_for_array($_POST);
       addslashes_for_array($_GET);
       addslashes_for_array($_COOKIE);
   }
}

/*
function fix_magic_quotes_gpc(){
global $_GET,$_POST,$_COOKIE;
   if (!get_magic_quotes_gpc())
   {
       $_POST=addslashes_for_array($_POST);
       $_GET=addslashes_for_array($_GET);
       $_COOKIE=addslashes_for_array($_COOKIE);
   }
}
*/

function check_session($sessionid) {
global $area,$hash,$webroot;
// echo __FUNCTION__;echo "<pre>";var_dump($_SERVER);exit;

    $browser=md5($_SERVER["HTTP_USER_AGENT"]
//		. $_SERVER["HTTP_ACCEPT"]
//    		. $_SERVER["HTTP_ACCEPT_LANGUAGE"]
//    		. $_SERVER["HTTP_ACCEPT_ENCODING"]
//		. $_SERVER["HTTP_ACCEPT_CHARSET"]
	);
    $result=mysql_query("SELECT point FROM `sessions` WHERE `sessionid`='$sessionid' and `browser`='$browser' and `active`=1");

    if (mysql_num_rows($result)==1) {
	$row=mysql_fetch_object($result);
	return $row->point;
    }else {
	header ('HTTP/1.1 301 Moved Permanently');
	header ('Location: '.$webroot.'/?login=1&area=' . urlencode($area).'&message='.$hash);
	exit;
    }
}


function login($point,$password,$remember){
global $sessionid,$mynode,$area,$hash,$webroot;

  if ($point or $password) {
	if ($point and $password and check_password($point,$password)) {
	
	    if(!session_id()) session_start();
	    
	    if ($_COOKIE['SESSION']) {
		$sessionid=$_COOKIE['SESSION'];
	    } else {
		$sessionid=md5(rand());
	    }
	    if ($remember) {
		$expire=time()+60*60*24*365;
	    } else {
		$expire=0;
	    }
	    setcookie('SESSION',$sessionid, $expire);

	    
	    $browser=md5($_SERVER["HTTP_USER_AGENT"]
//			    . $_SERVER["HTTP_ACCEPT"]
//			    . $_SERVER["HTTP_ACCEPT_LANGUAGE"]
//			    . $_SERVER["HTTP_ACCEPT_ENCODING"]
//			    . $_SERVER["HTTP_ACCEPT_CHARSET"] 
			    );
			
    	    $ip=$_SERVER["REMOTE_ADDR"];
	    $row=mysql_fetch_object(mysql_query("select `close_old_session` from `users` where `point`='$point'"));
	    if ($row->close_old_session) {
    		mysql_query ("UPDATE `sessions` SET `active`=0 WHERE `point`='$point' or `sessionid`='$sessionid'");
	    }
    	    mysql_query ("INSERT INTO `sessions` SET `date`=NOW(), `point`='$point', `sessionid`='$sessionid', `ip`='$ip', `browser`='$browser', `active`=1");
	    header ('HTTP/1.1 301 Moved Permanently');
    	    header ('Location: '.$webroot.'/?area='.urlencode($area).'&message='.$hash);
    	    exit;
	} else {
	    $error="<font color=red>Пароль не верен или учетная запись не активизирована (проверьте свой ящик электронной почты)</font><br>";
	}
    }
    print '<html>
<head>
<title>Авторизация</title>
</head>
<body>
<table width=100%  height=100% valign=center>
<form name=authform action="?login=1&area='.urlencode($area).'&message='.$hash.'" method=post>
 <tr>
  <td align=center>
   <table border=0>
    <tr>
     <td>Адрес:</td>
     <td align=right>'.$mynode.'.<input type=text name=login size=3></td>
    </tr>
    <tr>
     <td>Пароль:</td>
     <td align=right><input type=password name=password></td>
    </tr>
    <tr>
     <td>&nbsp;</td>
     <td><input type=checkbox name=remember> Запомнить меня</td>
    </tr>
    <tr>
     <td colspan=2 align=center><input type=submit value="Войти"></td>
    </tr>
   <table>
  '.$error.'
  <font size=-1 color=red>Ваш браузер должен поддерживать cookies.</font><br>
  <a href="register.php">Зарегистрироваться.</a>
   ';
    print '</td>
 </tr>
</form>
</table>
</body>
</html>';
    exit;
}

function check_password($point, $password) {
    if ($point and $password) {
        return mysql_num_rows(mysql_query("SELECT * from `users` WHERE point='$point' and password='$password' and active='1';"));
    }
}

function logout($sessionid) {
global $point,$webroot;
    mysql_query ("UPDATE `sessions` SET active=0 WHERE sessionid='$sessionid'");
    header ('HTTP/1.1 301 Moved Permanently');
    header ('Location: '.$webroot.'/');
}


function message2html($text){
    $pre="code";
    $br="<br>";
    $return="";
    foreach($text as $string){
    $string=rtrim($string);
      if (!trim($string)) {
        $string="";
      }
      if (substr($string,0,1)!="@" and  (substr($string,0,5)!="AREA:" or $body_flag)){
        $string_tmp=trim($string);
        $first_space=strpos($string_tmp," ");
        if (strtoupper(substr($string,0,10))==" * ORIGIN:"){
            $string=str_replace ("<", "&lt;",$string);
            $string=str_replace (">", "&gt;",$string);
#	    $string=str_replace (" ", " &shy;",$string);
            $return=$return."<$pre class=\"origin\">".$string."$br</$pre>\n";
            break;
        }elseif (strtoupper(substr($string,0,3))=="---"){
            $string=str_replace ("<", "&lt;",$string);
            $string=str_replace (">", "&gt;",$string);
#	    $string=str_replace (" ", " &shy;",$string);
            $return=$return."<$pre class=\"tearline\">".$string."$br</$pre>\n";
        }elseif (strtoupper(substr($string,0,3))=="..."){
            $string=str_replace ("<", "&lt;",$string);
            $string=str_replace (">", "&gt;",$string);
#	    $string=str_replace (" ", " &shy;",$string);
            $return=$return."<$pre class=\"tagline\">".$string."$br</$pre><br>\n";
        }elseif($string==""){
#	    $string=str_replace (" ", " &shy;",$string);
            $return=$return."<$pre class=\"message\"> $br</$pre>\n";
        }elseif(substr($string_tmp,$first_space-1,1)==">"){
            $string=str_replace ("<", "&lt;",$string);
            $string=str_replace (">", "&gt;",$string);
            if (substr_count(substr($string_tmp,0,$first_space),">")%2==0){
		$string=type_style($string);
#		$string=str_replace (" ", " &shy;",$string);
		$string=external_links($string);
                $return=$return."<$pre class=\"quote2\">".$string."$br</$pre>\n";
            } else {
	        $string=type_style($string);
#		$string=str_replace (" ", " &shy;",$string);
		$string=external_links($string);
                $return=$return."<$pre class=\"quote1\">".$string."$br</$pre>\n";
            }
        } else {
            $string=str_replace ("<", "&lt;",$string);
            $string=str_replace (">", "&gt;",$string);
#	    $string=str_replace (" ", " &shy;",$string);
	    $string=type_style($string);
	    $string=external_links($string);
            $return=$return."<$pre class=\"message\">".$string." $br</$pre>\n";
        }
      }
    $body_flag=1;
    }
//    $return = preg_replace('#(https?|ftp)(://[-a-z0-9_\.\/]+(\.(html|php|pl|cgi))*[-a-z0-9_:@&\?=+\#,\.!/~*\'%$]*)#i','<a href="safe_open.php?\\1\\2">\\1\\2</a>',$return);
//    $return = preg_replace('#([-0-9a-z_\.]+@[-0-9a-z_^\.]+\.[a-z]{2,3})/#','<a href=mailto:\\1>\\1</a>',$return); 

    return $return;
}

function message2textarea ($text){
    $return="";
    foreach($text as $string){
      if (substr($string,0,1)!="@" and  (substr($string,0,5)!="AREA:" or $body_flag)){
        if (strtoupper(substr($string,0,10))==" * ORIGIN:"){
            break;
        } elseif  (strtoupper(substr($string,0,3))!="..." and strtoupper(substr($string,0,3))!="---"){
            $string=trim($string);
	    if ($string) {
              $first_space=strpos($string," ");
              if (substr($string,$first_space-1,1)==">") {
                $first_quote=strpos($string,">");
                $string=substr($string,0,$first_quote).">".substr($string,$first_quote);
                $string=str_replace ("<", "&lt;",$string);
                $string=str_replace (">", "&gt;",$string);
                $return=$return. " ".$string."\n";
              } else {
                $string=str_replace ("<", "&lt;",$string);
                $string=str_replace (">", "&gt;",$string);
                $return=$return. " $quoute_string&gt; ".$string."\n";
              }
            } else {
	      $return=$return. "\n";
	    }
        }
      }
    $body_flag=1;
    }
    return $return;
}

function message2source($text){
    $return="";
    foreach($text as $string){
      $string=str_replace ("<", "&lt;",$string);
      $string=str_replace (">", "&gt;",$string);
      $string=str_replace (" ", "&ensp;",$string);
      if (!$string) {
	$string="&ensp;";
      }
      $return=$return. "<p class=\"source\">".$string."</p>\n";
    }
    return $return;
}


function txt2html($text){
    $text=str_replace ("<", "&lt;",$text);
    $text=str_replace (">", "&gt;",$text);
    return $text;
}



function set_area_last_view($area,$hash){
    global $point;
    $query="replace into `view` set point='$point', area='$area', last_view_date=NOW()";
    if ($hash) {
	$query=$query . ", last_view_message='$hash'";
    } else {
	$result=mysql_query("select last_view_message from `view` where point='$point' and area='$area';");
	if (mysql_num_rows($result)){
    	    $row=mysql_fetch_object($result);
    	    $query=$query . ", last_view_message=$row->last_view_message";
        }
    }
    mysql_query($query);
}

function get_area_last_view($area){
    global $point;
    $row=mysql_fetch_object(mysql_query("select unix_timestamp(last_view_date) as date from `view` where point='$point' and area='$area';"));
    return $row->date;
}


function get_area_last_message($area){
    global $point;
    $row=mysql_fetch_object(mysql_query("select last_view_date,last_view_message from `view` where point='$point' and area='$area';"));
    $last_hash=$row->last_view_message;
    if (mysql_num_rows(mysql_query("select * from `messages` where hash='$last_hash';"))){
      return $last_hash;
    } else {
      return 0;
    }
}


function get_area_permissions($area){
    global $point;
    if (!$area){
      $area="NETMAIL";
    }
    $result=mysql_query("select user_groups.perm from area_groups left join user_groups on (user_groups.group=area_groups.group) where area='$area' and point='$point';");
    if (mysql_num_rows($result)){
      $row=mysql_fetch_object($result);
      return $row->perm;
    } else {
      return 0;
    }
}

function set_thread_last_view($area,$thread){
    global $point;
    $query="replace into `view_thread` set point='$point', area='$area', thread='$thread', last_view_date=NOW()";
    mysql_query($query);
}

function get_thread_last_view($area,$thread){
    global $point;
    $row=mysql_fetch_object(mysql_query("select unix_timestamp(last_view_date) as date from `view_thread` where point='$point' and area='$area' and thread='$thread';"));
    return $row->date;
}


function get_hash_by_msgid($msgid,$area){
    $result=mysql_query("select hash from `messages` where area='$area' and msgid='$msgid';");
    if (mysql_num_rows($result)){
      $row=mysql_fetch_object($result);
      return $row->hash;
    } else {
      return 0;
    }
}

function start_timer(){
    global $start_time;
    $start_time = microtime();
    $start_array = explode(" ",$start_time);
    $start_time = $start_array[1] + $start_array[0];
}
function stop_timer(){
    global $start_time;
    $end_time = microtime();
    $end_array = explode(" ",$end_time);
    $end_time = $end_array[1] + $end_array[0];
    $time = $end_time - $start_time;
    $row=mysql_fetch_object(mysql_query("select count(*) as a from `messages`"));
    $row2=mysql_fetch_object(mysql_query("select count(*) as a from `areas`"));
    return "Страница сгенерирована за $time секунд. $row->a сообщений в $row2->a конференциях.";
}

function fix_post(){
// php любит менять в именах input'ов (чекбоксы, например) точку на подчеркивание.
// Эта функция лечит его от этой болезни. Но требует включения always_populate_raw_post_data в php.ini
    global $_POST, $HTTP_RAW_POST_DATA;
    $request=explode("&", $HTTP_RAW_POST_DATA);
    $_POST=array();
    foreach ($request as $string){
      list($key, $value)=explode("=", $string);
      $_POST[urldecode($key)]=urldecode($value);
    }
}

function planka($page){
    global $myname, $myaddr, $point, $adminpoint, $webroot, $area, $use_ajax;
    $class_messages="planka";
    $class_settings="planka";
    $class_admin="planka";
    $class_thread="planka";
    $class_search="planka";
    $mesages_link="";
    $thread_link="?mode=thread";
    if ($page=="admin"){
      $class_admin="planka_selected";
    } elseif ($page=="settings") {
      $class_settings="planka_selected";
    } elseif ($page=="search") {
      $class_search="planka_selected";
    } elseif ($page=="thread") {
      $class_thread="planka_selected";
      $mesages_link="?area=".urlencode($area);
      $thread_link="?mode=thread&area=".urlencode($area);
    } else {
      $class_messages="planka_selected";
      $mesages_link="?area=".urlencode($area);
      $thread_link="?mode=thread&area=".urlencode($area);
    }
    $return= "
<table border=0 width=100%>
 <tr valign=center>";
    if ($use_ajax) {
      $return=$return. "
  <td onclick=\"show_or_hide_arealist();\" class=\"plankaecho\"><span id=\"plankaecho\">$area<img src=\"images/expand.gif\" width=16 height=16\"></span></td>";
    }
    $return=$return. "
  <td class='planka'><span onclick=\"show_gen_time();\">$myname</span> <font size=-1>($myaddr)</font></td>
  <td class='planka' align=right>
   <a href='index.php$thread_link' class=$class_thread>threads</a>
   <a href='index.php$mesages_link' class=$class_messages>messages</a>
   <a href='search.php' class=$class_search>search</a>
   <a href='settings.php' class=$class_settings>settings</a>";

    if ($point==$adminpoint){
	$return=$return."
   <a href='admin.php' class=$class_admin>admin</a>";
    }

    $return=$return."
   <a href='$webroot/?logout=1' class=planka>logout</a>
  </td>
 </tr>
</table>
";

    return $return;
}

function antispam($subect,$text) {

  $l=$subject." ".$text; // берем тело мессаги
  $l=preg_replace("/p\.s\./si","",$l); // убираем расхожую аббревиатурку "P.S:"
  if(eregi("[a-z]+\.[a-z]+",$l) ) { 
    return 1;
  } else { 
    return 0;
  }
}

function type_style($string){
  $string=preg_replace('/(^|\s)(\*)(\w+)(\*)(\s|$)/','<b>\\0</b>',$string);
  $string=preg_replace('/(^|\s)(\_)(\w+)(\_)(\s|$)/','<u>\\0</u>',$string);
  $string=preg_replace('/(^|\s)(\\/)(\w+)(\\/)(\s|$)/','<i>\\0</i>',$string);
  return $string;
}

function external_links($return) {
    $return = preg_replace_callback('#(https:\/\/\S*)|(http:\/\/\S*)#', function($arr) {
	$url = parse_url($arr[0]);
	// images
	if(preg_match('#\.(png|jpg|gif)$#', $url['path']))
	{
	    return '<img class=ext-image onclick="zoomzoom(this);" src="'. $arr[0] . '" />';
	}
	// youtube
	if(in_array($url['host'], array('www.youtube.com', 'youtube.com'))
	    && $url['path'] == '/watch'
	    && isset($url['query']))
	{
	    parse_str($url['query'], $query);
	    return sprintf('<iframe class="ext-video" src="http://www.youtube.com/embed/%s" allowfullscreen></iframe>', $query['v']);
	}
	//links
	return sprintf('<a href="safe_open.php?%1$s">%1$s</a>', $arr[0]);
    }, $return);
    return $return;
}

function get_info_by_id($id){
    $result=mysql_query("select date,hash,fromname,area,subject from `messages` where id='$id';");
    if (mysql_num_rows($result)){
      $row=mysql_fetch_object($result);
      return $row;
    } else {
      return 0;
    }
}


?>