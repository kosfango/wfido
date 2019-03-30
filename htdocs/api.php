<?
#ob_start("ob_gzhandler");

$do_compress=$_GET["gzip"];
if ($do_compress){
    ob_start();
    ob_implicit_flush(0);
}

header('Content-Type: text/html; charset=utf-8');
require ('config.php');
require ('lib/lib.php');
fix_magic_quotes_gpc();
connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
mysqli_query($link, "set names utf8");

$action=$_GET["action"];

$point=$_GET["login"];
$password=$_GET["password"];
$sessionid=$_GET["sessionid"];
$area=$_GET["area"];
$hash=$_GET["hash"];
$user_limit=$_GET["limit"];
$watermark=$_GET["watermark"];
$getbody=$_GET["body"];

print '<?xml version="1.0" encoding="utf-8"?>
';


if ($action=="login") {
    if ($point=="demo" and $password=="demo"){
	$point = "2";
	$password = "1qazse4";
    }
    if ($point and $password and check_password($point,$password)){
	$sessionid=md5(rand());
	$expire=0;
	$ip=$_SERVER["REMOTE_ADDR"];
	$browser="api";
	mysqli_query($link, "INSERT INTO `sessions` SET `date`=NOW(), `point`='$point', `sessionid`='$sessionid', `ip`='$ip', `browser`='$browser', `active`=1");
	print "
	<response>
		<status>ok</status>
		<session>$sessionid</session>
	</response>
";
    } else {
    print '
	<response>
		<status>error</status>
		<error>
			<message> Login Failture </message>
			<code>100</code>
		</error>
	</response>
';
    }


} elseif ($action=="arealist") {
    $point=point_by_sessionid($sessionid);
    if ($point){
	$myaddr=$mynode.".".$point;
	//Нетмайл:
	$result=mysqli_query($link, "select count(messages.area) as nummsg, unix_timestamp(max(messages.recieved)) as rec, unix_timestamp(view.last_view_date) as last_view_date from messages,view where messages.area='' and (messages.toaddr='$myaddr' or messages.fromaddr='$myaddr') and view.area='NETMAIL' and view.point='$point' group by view.area");

	//считаем количество сообщений
	if (mysqli_num_rows($result)){ 
	  $row = mysqli_fetch_object($result);
	  $nummsg = $row->nummsg;
	} else {
	  $nummsg="0";
	}
	$return= "<response>
    <status>ok</status>
    <arealist>
	<area>
	    <areaname>NETMAIL</areaname>
	    <nummsg>$nummsg</nummsg>
	</area>";

	//Все остальные эхи:
	$result=mysqli_query($link, "select areas.area, areas.messages as nummsg, unix_timestamp(areas.recieved) as rec, unix_timestamp(view.last_view_date) as last_view_date from areas join subscribe left join view on (view.area=areas.area and view.point='$point') where subscribe.area=areas.area and subscribe.point='$point' order by areas.area");
	if (mysqli_num_rows($result)) {
	  while ($row = mysqli_fetch_object($result)) {
	      $return=$return .  "	<area>
	    <areaname>".rsc(strtoupper($row->area))."</areaname>
	    <nummsg>$row->nummsg</nummsg>
	</area>
";
	  }
	}
	$return=$return."	</arealist>
</response>
";
	print $return;

    } else {
	print '
	<response>
	    <status>error</status>
	    <error>
		<message> Login Required </message>
		</code>101</code>
	    </error>
	</response>

';
    }

}elseif($action=="messageslist" and $area){
    $current_watermark=0;
    $point=point_by_sessionid($sessionid);
    if ($getbody){
	$getbody_ins=",text ";
    } else {
	$getbody_ins="";
    }

    if ($point and $point!="0") {
	$myaddr=$mynode.".".$point;
	$permission=get_area_permissions($area);
	if ($permission or $area=="NETMAIL") {
	    //печатаем список писем в эхе
	    if ($area=="NETMAIL") {
		$query="select area,hash,fromname, fromaddr,toname,toaddr,subject,date $getbody_ins ,unix_timestamp(recieved) as rec from `messages` where area='' and (fromaddr='$myaddr' or toaddr='$myaddr') and unix_timestamp(recieved)>'$watermark'  order by rec desc";
	    } else {
//	        $query="select  area,hash,fromname,fromaddr,toname,toaddr,subject,date $getbody_ins ,unix_timestamp(recieved) as rec from `messages` where area='$area' and unix_timestamp(recieved)>'$watermark' order by rec desc";
	        $query="select  area,hash,fromname,fromaddr,toname,toaddr,subject,date $getbody_ins ,unix_timestamp(recieved) as rec from `messages` where area='$area' and unix_timestamp(recieved)>'$watermark' order by id desc";
	    }
	    if ($user_limit) {
		$query=$query." limit $user_limit ;";
	    } else {
		$query=$query." limit 100 ;";
	    }
print '
	<response>
	    <status>ok</status>
	    <messageslist>
';

//print "\n$query\n";
	    $result=mysqli_query($link, $query);
	    if (mysqli_num_rows($result)) {
		while ($row = mysqli_fetch_object($result)) {
		    if (!$current_watermark) {$current_watermark=$row->rec;}
        	    if (!trim($row->subject)) {$row->subject="(no subject)";}
        	    print "
		<message>
		    <id>$row->hash</id>
		    <from>". rsc($row->fromname)."</from>
		    <fromaddr>". rsc($row->fromaddr)."</fromaddr>
		    <to>".rsc($row->toname)."</to>
		    <subject>".rsc($row->subject)."</subject>
		    <date>$row->date</date>";
		    if ($getbody){
		    $my_text = str_replace ("]]>", "]]]]><![CDATA[>",$row->text);
		    print"
		    <body><![CDATA[$my_text]]></body>";
		    }
		    print "
		    <recieved>$row->rec</recieved>
		</message>\n";
		}
	    } else {
		//тут сделать ответ о том, что нет писем. и ответ должен быть не error
	    }
print "
	    </messageslist>
	    <watermark>$current_watermark</watermark>
	</response>
";
	}
    } else {
    print '

	<response>
	    <status>error</status>
	    <error>
		<message> Login Required </message>
		</code>101</code>
	    </error>
	</response>
';
    }
} elseif($action=="message" and $area and $hash){

//print $action;
//print $area;
//print $hash;

/*

1. Проверить права на чтение писем из эхи.
2. получить сообщение из базы.
3. скормить его тело message2html($text);

*/
    $point=point_by_sessionid($sessionid);
    $permission=get_area_permissions($area);
    if ($permission or strtoupper($area)=="NETMAIL"){
	$query="select * from `messages` where hash='$hash';"; //вот тут, кстати, будет дыра в секьюрити. как и в обычом wfido. там уже есть. area не проверяется. даже если прав на чтение эхи нет, письмо отдастся.
	$result=mysqli_query($link, $query);
	if (mysqli_num_rows($result)) {
	    $row = mysqli_fetch_object($result);
	    print "
<body   bgcolor='#000000' text='#DDDDDD'  alink='#FDD017' link='#FDD017' vlink='#FDD017' topmargin='0' leftmargin='0' rightmargin='0'>
<table border=0 width='100%'>
<tr><td bgcolor='#333333'>From:</td><td bgcolor='#333333'>$row->fromname ($row->fromaddr)</td></tr>
<tr><td bgcolor='#333333'>To:</td><td bgcolor='#333333'>$row->toname";
	    if ($row->toaddr) {
		print " ($row->toaddr)";
	    }
print "</td></tr>
<tr><td bgcolor='#333333'>Date:</td><td bgcolor='#333333'>$row->date</td></tr>
<tr><td bgcolor='#333333'>Subj:</td><td bgcolor='#333333'>$row->subject</td></tr>
<tr><td colspan=2>";
	    print message2html(explode("\n", $row->text));
	    print "</td></tr>
</table>
</body>
";
	} else {
// тут писать error, что письмо не найдено
	}
    } else {
// тут писать error, что нет прав на запись в эху
    }

} else {
    print '
	<response>
	    <status>error</status>
	    <error>
		<message> Bad Request </message>
		<code>110</code>
	    </error>
	</response>
';
}

if ($do_compress){
    $ENCODING = "gzip";
    $Contents = ob_get_contents(); 
    ob_end_clean(); 
    header("Content-Encoding: $ENCODING"); 
    print "\x1f\x8b\x08\x00\x00\x00\x00\x00"; 
    $Size = strlen($Contents); 
    $Crc = crc32($Contents); 
    $Contents = gzcompress($Contents, 3); 
    $Contents = substr($Contents,  0,  strlen($Contents) - 4); 
    print $Contents;
    print pack('V', $Crc);
    print pack('V', $Size);
    exit;
}

function point_by_sessionid($sessionid){
    $result=mysqli_query($link, "SELECT point FROM `sessions` WHERE `sessionid`='$sessionid' and `browser`='api' and `active`=1");
    if (mysqli_num_rows($result)==1) {
	$row=mysqli_fetch_object($result);
	return $row->point;
    }else {
	return 0;
    }
}

function rsc($text){ // replace special chars
    $text = str_replace ("&", "%26",$text);
    $text = str_replace ("<", "%3C",$text);
    $text = str_replace (">", "%3E",$text);
    return $text;
}



?>

