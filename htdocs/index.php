<? header("Content-type: text/html; charset=koi8-r");

require ('config.php');
require ('lib/lib.php');
fix_magic_quotes_gpc();
start_timer();

    $hash=substr($_GET["message"] ?? "",0,128);
    $mode=substr($_GET["mode"] ?? "",0,128);
if (isset($_POST["subject"])) {
	$subject=$_POST["subject"] ?? "";
	$reply=$_POST["reply"] ?? "";
	$toaddr=$_POST["toaddr"] ?? "";
	$toname=$_POST["toname"] ?? "";
}

$text=preg_replace('/\&#8211;/','-',$_POST["text"] ?? "");

if (isset($_GET["area"]) && $_GET["area"]) {
    $area=strtoupper(substr($_GET["area"],0,128));
} else {
    $area="NETMAIL";
}

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);

if (isset($_GET["logout"])) {
    logout($_COOKIE['SESSION'] ?? '');
}
if (isset($_GET["login"])) {
    login($_POST["login"] ?? "",$_POST["password"] ?? "",$_POST["remember"] ?? "");
    exit;
}
{
    //var_dump($_COOKIE);
    $point=check_session($_COOKIE['SESSION'] ?? '');
}

//Получаем инфо о юзере
$query = mysqli_query($link, "select * from `users` where point='$point'");
$row = mysqli_fetch_object($query);
$myaddr=$mynode.".".$row->point;
$myname=$row->name;
if ($row->origin) {
    $myorigin=$row->origin;
} else {
    $myorigin=$defaultorigin;
}
if ($row->ajax) {
  $use_ajax=1;
} else {
  $use_ajax=0;
}

// Получаем права на эху. 4 - антиспам, 3 - rw, 2 - премодерация, 1 - ro, 0 - прав нет.
$permission=get_area_permissions($area);

if ($mode=="ansver" and $toname and ($area!="NETMAIL" or $toaddr)){
  if ($_POST["selarea"]=="") {
	  $area="NETMAIL";
  }
  else {
	  $area=$_POST["selarea"];
  }  
//пихаем сообщение в базу, в таблицу outbox.
  if ($permission=="3") { //полный доступ
		$hash=md5(rand());
    mysqli_query($link, "insert into `outbox` set area='$area', fromname='$myname', toname='$toname', subject='$subject', text='$text', fromaddr='$myaddr', toaddr='$toaddr', origin='$myorigin', reply='$reply', date=now(), hash='$hash', sent='0', aprove='1'");
  }
	elseif ($permission=="2") { //доступ с премодерацией
		$hash=md5(rand());
		mysqli_query($link, "insert into `outbox` set area='$area', fromname='$myname', toname='$toname', subject='$subject', text='$text', fromaddr='$myaddr', toaddr='$toaddr', origin='$myorigin', reply='$reply', date=now(), hash='$hash', sent='0', aprove='0'");
  } 
	elseif ($permission=="4") { //доступ через антиспам
		$hash=md5(rand());
		if (antispam($subject, $text)) { // возможно, спам. отправляем на премодерацию.
      mysqli_query($link, "insert into `outbox` set area='$area', fromname='$myname', toname='$toname', subject='$subject', text='$text', fromaddr='$myaddr', toaddr='$toaddr', origin='$myorigin', reply='$reply', date=now(), hash='$hash', sent='0', aprove='0'");
    } 
		else { // не спам. отправляем в эху.
			mysqli_query($link, "insert into `outbox` set area='$area', fromname='$myname', toname='$toname', subject='$subject', text='$text', fromaddr='$myaddr', toaddr='$toaddr', origin='$myorigin', reply='$reply', date=now(), hash='$hash', sent='0', aprove='1'");
    }
  }
	else { // если права 1 или 0, значит прав на запись нет.
		mysqli_query($link, "
insert into `outbox`
set area='NETMAIL', fromname='Sysop', toname='$fromname', subject='��� ���� �� ������ � ���', text='
������, $myname!
� ���������, � ���� ��� ���� �� ������ � $area.

������, ������� �� ���� ����������:
=======================================
AREA: $area
FROM: $myname($myaddr)
TO:   $toname($toaddr)
=======================================
$text
* Origin: $myorigin
', fromaddr='$mynode', toaddr='$myaddr', origin='Bad robot', reply='', date=now(), hash='$hash', sent='0', aprove='1'");
	}
//    header ('HTTP/1.1 301 Moved Permanently');
	header ('Location: ?area='.$area."&message=".$hash);
	exit;
} 
elseif ($mode=="add_to_favorites"){
  mysqli_query($link, "insert ignore into `favorites` set point='$point', message='$hash', uniq_index='$point-$hash'");
}
elseif ($mode=="remove_from_favorites"){
  mysqli_query($link, "delete from `favorites` where point='$point' and message='$hash'");
}
if ($mode=="delete"){
  mysqli_query($link, "delete from `messages` where `hash` = '$hash'");
}

print "<html>
<head>
<title>Online FTN reader</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=koi8-r\" />
<link rel=\"stylesheet\" href=\"css/ftn.css\" type=\"text/css\" media=\"all\" />
</head>";
if ($use_ajax) {
    print "<body onload=\"daemon();\">\n";
}
else {
  if ($mode=="thread" or $mode=="tree") {
    print "<body onload=\"scroll_thread('0');\">\n";
  }
	else {
//			print "<body onload=\"scroll_msglist();\">\n";
			print "<body onload=\"if (typeof scroll_msglist === 'function') scroll_msglist();\">\n";
  }
}
print "<table width=\"100%\" height=\"100%\">
<tr height=1%><td colspan=2>";

if ($mode=="thread" or $mode=="tree") {
  print planka("thread");
}
else {
  print planka("messages");
}
print "</td></tr>
<tr height=100%>";

//print "<span onclick=\"update_list(document.variables.area.value,document.variables.hash.value,document.variables.mode.value)\";>update</span>";

if ($use_ajax){
  print '<div name=arealist id=arealist class=arealist style="height: 400px; width: 200px; overflow: auto; border: 1px solid #999; position: absolute; left: 0px; top: 20px; display: none; z-index: 9999; background: #fff;"><div class="arealist-container" id="arealist-container"></div></div>';
}
else {
  print "<td valign=top width=150px class=\"echolist\">
<div name=arealist id=arealist class=arealist style=\"height: 100%; width: 150px; overflow: auto; border: 1\">";
//Рисуем список эх
//Нетмайл:
	$result=mysqli_query($link, "select count(messages.area) as nummsg, unix_timestamp(max(messages.recieved)) as rec, unix_timestamp(view.last_view_date) as last_view_date from messages,view where messages.area='' and (messages.toaddr='$myaddr' or messages.fromaddr='$myaddr') and view.area='NETMAIL' and view.point='$point' group by view.area");

//считаем количество сообщений
	$netmail_last_view_date = 0;
	$netmail_rec = 0;
	if (mysqli_num_rows($result)){ 
		$row = mysqli_fetch_object($result);
		$nummsg = $row->nummsg;
		$netmail_last_view_date = $row->last_view_date ?? 0;
		$netmail_rec = $row->rec ?? 0;
	}
	else {
		$nummsg="0";
	}
//если эха выбрана, то выделяем ее цветом
		$newmessages="";
	if ($area=="NETMAIL") {
		$class="selected";
	}
	else {
		$class="netmail";
		if (($netmail_last_view_date - $netmail_rec) < 0){
			$newmessages="*";
		}
		else {
    $newmessages="";
		}
	}
		print "<p onClick=\"document.location='?area=NETMAIL';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=NETMAIL\"  class=\"netmail\">NETMAIL</a> ($nummsg) $newmessages</p>\n";
//Карбонка:
	$result=mysqli_query($link, "select count(view.last_view_date) as nummsg, messages.area, unix_timestamp(max(messages.recieved)) as rec, unix_timestamp(view.last_view_date) as last_view_date from messages,view where messages.area!='' and messages.toname='$myname' and view.area='CARBONAREA' and view.point='$point' group by view.last_view_date");
	$result2=mysqli_query($link, "select count(view.last_view_date) as nummsg, messages.area, unix_timestamp(max(messages.recieved)) as rec, unix_timestamp(view.last_view_date) as last_view_date from messages,view where messages.fromname='$myname' and messages.area!='' and view.area='CARBONAREA' and view.point='$point' group by view.last_view_date");
	$carbon_last_view_date = 0;
	$carbon_rec = 0;
	$carbon2_last_view_date = 0;
	$carbon2_rec = 0;
	if (mysqli_num_rows($result) or mysqli_num_rows($result2)){
		$row = mysqli_fetch_object($result);
		$row2 = mysqli_fetch_object($result2);
		$nummsg = (int)($row->nummsg ?? 0) + (int)($row2->nummsg ?? 0);
		$carbon_last_view_date = $row->last_view_date ?? 0;
		$carbon_rec = $row->rec ?? 0;
		$carbon2_last_view_date = $row2->last_view_date ?? 0;
		$carbon2_rec = $row2->rec ?? 0;
	}
	else {
		$nummsg="0";
	}
	if ($area=="CARBONAREA") {
		$class="selected";
		$newmessages="";
	}
	else {
		$class="carbonarea";
		if ((($carbon_last_view_date - $carbon_rec) < 0) or (($carbon2_last_view_date - $carbon2_rec) < 0)){
			$newmessages="*";
		}
		else {
			$newmessages="";
		}
	}
	print "<p onClick=\"document.location='?area=CARBONAREA';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=CARBONAREA\" class=\"carbonarea\">CARBONAREA</a> ($nummsg) $newmessages</p>\n";
//Все остальные эхи:
	$result=mysqli_query($link, "select distinct areas.area, areas.messages as nummsg, unix_timestamp(areas.recieved) as rec, unix_timestamp(view.last_view_date) as last_view_date from areas join subscribe left join view on (view.area=areas.area and view.point='$point') where subscribe.area=areas.area and subscribe.point='$point' order by areas.area");
	if (mysqli_num_rows($result)) {
		while ($row = mysqli_fetch_object($result)) {
			if ($area==strtoupper($row->area)) {
				$class="selected";
				$newmessages="";
			}
			else {
				$class="echo";
				if (($row->last_view_date - $row->rec) < 0){
					$newmessages="*";
				}
				else {
					$newmessages="";
				}
			}
			$area_url = urlencode($row->area);
			if ($mode=="thread" or $mode=="tree"){
				print "<p onClick=\"document.location='?area=$area_url&mode=thread';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=$area_url&mode=thread\" class=\"echo\">$row->area</a> ($row->nummsg) $newmessages</p>\n";
			} 
			else {
				print "<p onClick=\"document.location='?area=$area_url';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=$area_url\" class=\"echo\">$row->area</a> ($row->nummsg) $newmessages</p>\n";
			}
		}
	}

//Outbox - уже написанные, но еще не отправленные письма.
	if ($area=="OUTBOX") {
		$class="selected";
	}
	else {
		$class="outbox";
	}
	print "<p onClick=\"document.location='?area=OUTBOX';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=OUTBOX\" class=\"outbox\">OUTBOX</a></p>\n";
//Favorites - избранное.
	if ($area=="FAVORITES") {
		$class="selected";
	}
	else {
		$class="favorites";
	}
	print "<p onClick=\"document.location='?area=FAVORITES';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=FAVORITES\" class=\"favorites\">FAVORITES</a></p>\n";
	print "</div>";
	print "</td>";
}
print "<td width=100%>
<table width=\"100%\" height=\"100%\">
<tr height=20%><td valign=top>
<div name=\"msglist\" id=\"msglist\"  style=\"height: 180px; overflow: auto; border: 0\">\n";

// режим тредов. работает только для обычных эх. не работает для нетмайла, карбонки, избранного и исходящего.
if (($mode=="thread" or $mode=="tree") and $area!="OUTBOX" and $area!="CARBONAREA" and $area!="FAVORITES" and $area!="NETMAIL"){
  if ($permission) {
//печатаем список тредов в эхе
    if (!$hash) {
      $hash=get_area_last_message($area);
    }
    print "\n<table width=100%>";
    $result=mysqli_query($link, "select thread from `messages` where hash='$hash'");
    $thread_selected="";
    if (mysqli_num_rows($result)){
      $row=mysqli_fetch_object($result);
      $thread_selected=$row->thread;
    }
    $result=mysqli_query($link, "
      select 
        subject, hash, last_author_date as date, unix_timestamp(lastupdate) as rec,
        threads.thread as thread, last_author as fromname, unix_timestamp(last_view_date) as lastview, num 
      from `threads` left join ( 
        select
          *
        from
  	`view_thread`
        where
  	point='$point' and area='$area'
      ) s0
      on 
        (threads.thread=s0.thread)
      where
        threads.area='$area'
      order
        by lastupdate desc
  ");


    $header_text="&nbsp;";
    while ($row=mysqli_fetch_object($result)){
      if (!trim($row->subject)) {$row->subject="(no subject)";}
			if ($row->rec>$row->lastview and $row->thread==$thread_selected) {
				$class="newselected";
				$header_text="<font color=green>$area</font>: $row->subject";
				$element_id=" name=\"selected\", id=\"selected\" ";
			}
			elseif ($row->rec>$row->lastview) {
				$class="new";
				$element_id="";
			}
			elseif ($row->thread==$thread_selected){
				$class="selected";
				$header_text="<font color=green>$area</font>: $row->subject";
				$element_id=" name=\"selected\", id=\"selected\" ";
			}
			else {
				$class="msglist";
				$element_id="";
			}
			print "<tr $element_id  onClick=\"document.location='?area=$area&message=$row->hash&mode=thread';return false\" ><td class=$class><a href=\"?area=$area&message=$row->hash&mode=thread\">".txt2html($row->subject)."</a></td><td class=$class>$row->num</td><td class=$class>$row->date ($row->fromname)</td></tr>\n";
    }
    print "</table>\n";
  }
	else {
		$header_text="<center>� ��� ��� ���� ��� ��������� ���� ��������������, ���� �������������� �� ����������.</center>";
  }
  print "</div>
</td></tr>
<tr height=1%><td class=\"messagehead\">
$header_text
</td></tr>
<tr height=80%><td valign=top>
<div style=\"word-break: break-all; width: 100%; height: 100%; overflow-y: scroll; background-color: #F5F5F5\" id='thread'>
	";
  if ($permission) {
//печатаем содержимое треда
    if ($mode=="tree"){
      $result=mysqli_query($link, "select level from `messages` where hash='$hash' and area='$area'");
      $row = mysqli_fetch_object($result);
      $level_first=$row->level;
    }
		else {// $mode=="thread"
      $level_first=0;
    }
    $print_start=0;
    $thread="";
    $hash_new=""; // если хотя бы одно новое сообщение есть, то запомним его в этой переменной.
    $result=mysqli_query($link, "select messages.msgid as msgid ,messages.reply as reply ,messages.hash,messages.level,messages.date,unix_timestamp(messages.recieved) as rec, messages.fromname,messages.fromaddr,messages.subject,messages.text,messages.thread as thread, unix_timestamp(view_thread.last_view_date) as lastview from `messages` left join `view_thread` on (messages.thread=view_thread.thread and view_thread.point='$point' and view_thread.area='$area') where messages.thread = (select thread from `messages` where hash='$hash') and messages.area='$area' order by inthread");
    while ($row = mysqli_fetch_object($result)){
      $thread=$row->thread;
      $class="thread";
      $display="none";
      if (($row->hash==$hash and $mode=="tree") or $row->level=='0') {
        $print_start=1;
      }
			elseif ($row->level<=$level_first) {
        $print_start=0;
      }
      if ($row->rec>$row->lastview) {
        $class="threadnew";
        $display="block";
        if (!$hash_new) {
          $hash_new=$row->hash;
        }
      }
			elseif ($row->hash==$hash){
        $display="block";
      }
      if (!$row->level) {
        if ($parent_message=get_hash_by_msgid($row->reply,$area)){
          print "<div class=thread_informer>���� ���� �������� ������� �� ������ �� ������� �����: <a href='?area=$area&message=$parent_message&mode=thread'>...</a></div>";
				}
      }
      print "<div id=\"$row->hash\" style='margin-left: ".($row->level)."0;' class=$class><div onclick=\"change_visible('$row->hash'); return false\" style='cursor: pointer;'>$row->date, $row->fromname ($row->fromaddr): $row->subject</div>\n";
      print "<div id=\"".$row->hash."_content\" style='display: $display; background-color: #FFFFFF;'>\n";
      $text=explode("\n", $row->text);
      print message2html($text);
      print "<div style='width: 100%; text-align: right;'><a href='?area=$area&message=$row->hash&mode=tree'>tree</a> <a href='?area=$area&message=$row->hash&mode=source'>source</a> <a href='?area=$area&message=$row->hash&mode=reply'>reply</a></div></div>\n</div>\n";
    }
// Устанавливаем метку со временем последнего просмотра треда.
    if ($thread){
      set_thread_last_view($area, $thread);
    }
  }
  print "</div></td></tr>
	";
}
else {
  if ($permission or $area=="NETMAIL" or $area=="CARBONAREA" or $area=="FAVORITES" or $area=="OUTBOX") {
//печатаем список писем в эхе
    $area_last_read_date=get_area_last_view($area);
    $last_viewed_message_hash=get_area_last_message($area);
    if ($area=="NETMAIL") {
      $query="select area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec from `messages` where area='' and (fromaddr='$myaddr' or toaddr='$myaddr') order by rec desc";
    }
		elseif ($area=="CARBONAREA") {
//так было:
//      $query="select  area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec  from `messages` where (toname='$myname' or fromname='$myname')and area!='' order by rec desc";
//так стало:
//создаем временную таблицу для всех откарбоненных писем
      mysqli_query($link, "CREATE temporary TABLE `tmp` (
          `fromname` varchar(255) NOT NULL default '',
          `fromaddr` text NOT NULL,
          `toname` varchar(255) NOT NULL default '',
          `toaddr` text NOT NULL,
					`area` varchar(128) NOT NULL default '',
          `subject` text NOT NULL,
          `date` text NOT NULL,
					`msgid` varchar(128) NOT NULL default '',
          `reply` varchar(128) NOT NULL default '',
					`hash` varchar(64) NOT NULL default '',
          `recieved` datetime NOT NULL default '0000-00-00 00:00:00'
			) CHARSET=utf8" );

//карбоним в нее сообщения
      mysqli_query($link, "insert into `tmp` (`fromname`, `fromaddr`, `toname`, `toaddr`, `area`, `subject`, `date`,`msgid`, `reply`, `hash`, `recieved`)
                               select `fromname`, `fromaddr`, `toname`, `toaddr`, `area`, `subject`, `date`,`msgid`, `reply`, `hash`, `recieved`
                               from `messages` where toname='$myname' and area!='NETMAIL' and area!=''");
      mysqli_query($link, "insert into `tmp` (`fromname`, `fromaddr`, `toname`, `toaddr`, `area`, `subject`, `date`,`msgid`, `reply`, `hash`, `recieved`)
                               select `fromname`, `fromaddr`, `toname`, `toaddr`, `area`, `subject`, `date`,`msgid`, `reply`, `hash`, `recieved`
                               from `messages` where fromname='$myname' and area!='NETMAIL' and area!=''");
      $query="select  area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec  from `tmp` order by rec desc";
    }
		elseif ($area=="OUTBOX") {
      $query="select area,fromname,toname,fromaddr,toaddr,subject,date,hash from `outbox` where fromaddr='$myaddr' and sent='0' order by date desc";
    }
		elseif ($area=="FAVORITES") {
      $query="select messages.area as area,messages.fromname as fromname,messages.toname as toname,messages.subject as subject,messages.date as data,messages.hash as hash from `messages` join `favorites` where messages.hash=favorites.message and point='$point' order by date desc";
    }
		else {
#      $query="select  area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec  from `messages` where area='$area' order by rec desc";
      $query="select  area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec  from `messages` where area='$area' order by id desc";
#      $query="select  area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec  from `messages` where area='$area'";
    }
    $query2=mysqli_query($link, "select `limit` from `users` where `point`='$point'");
    $row=mysqli_fetch_object($query2);
    $user_limit=$row->limit;
    if ($user_limit) {
      $query=$query." limit $user_limit ;";
    }
		else {
      $query=$query." ;";
    }
    $result=mysqli_query($link, $query);
    if (mysqli_num_rows($result)) {
      print "<table width=100%>\n";
      while ($row = mysqli_fetch_object($result)) {
//если не указано, какое именно письмо нас интересует, то выбираем последнее просмотренное.
//если последнее просмотренное не указано, то показывать будем первое же письмо
        if (!$hash and $last_viewed_message_hash) {
          $hash=$last_viewed_message_hash;
        } elseif (!$hash){
          $hash=$row->hash;
        }
//все письма с датой получения больше, чем дата последнего захода в эху, считаем новыми. и выделяем.
//выделенное письмо (то на котором стоит "курсор") так же отмечаем
        if(($area_last_read_date - $row->rec) < 0 and $hash==$row->hash) {
          $class="newselected";
          $element_id=" name=\"selected\", id=\"selected\" ";
        }
				elseif(($area_last_read_date - $row->rec) < 0) {
          $class="new";
          $element_id="";
        }
				elseif ($hash==$row->hash){ 
          $class="selected";
          $element_id=" name=\"selected\", id=\"selected\" ";
        }
				else {
          $class="msglist";
          $element_id="";
        }
//собственно, печатем строчку с письмом.
        if (!trim($row->subject)) {$row->subject="(no subject)";}
        if ($area=="CARBONAREA") {
          print "
<tr style=\"cursor: pointer;\" $element_id>
<td onClick=\"document.location='?area=$row->area&message=$row->hash';return false\" class=\"$class\">".strtoupper($row->area)."</td>
<td onClick=\"document.location='?area=CARBONAREA&message=$row->hash';return false\" class=\"$class\">$row->fromname</td>
<td onClick=\"document.location='?area=CARBONAREA&message=$row->hash';return false\" class=\"$class\">$row->toname</td>
<td onClick=\"document.location='?area=CARBONAREA&message=$row->hash';return false\" class=\"$class\"><a href=\"?area=CARBONAREA&message=$row->hash\">".txt2html($row->subject)."</a></td>
<td onClick=\"document.location='?area=CARBONAREA&message=$row->hash';return false\" class=\"$class\">$row->date</td>
</tr>\n";
        }
				elseif ($area=="FAVORITES") {

          print "
<tr style=\"cursor: pointer;\" $element_id>
<td onClick=\"document.location='?area=$row->area&message=$row->hash';return false\" class=\"$class\">"; 
          if ($row->area){
            print strtoupper($row->area);
          }
					else {
      	    print "NETMAIL";
          }
          print "</td>
<td onClick=\"document.location='?area=FAVORITES&message=$row->hash';return false\" class=\"$class\">$row->fromname</td>
<td onClick=\"document.location='?area=FAVORITES&message=$row->hash';return false\" class=\"$class\">$row->toname</td>
<td onClick=\"document.location='?area=FAVORITES&message=$row->hash';return false\" class=\"$class\"><a href=\"?area=CARBONAREA&message=$row->hash\">".txt2html($row->subject)."</a></td>
<td onClick=\"document.location='?area=FAVORITES&message=$row->hash';return false\" class=\"$class\">$row->date</td>
</tr>\n";
        }
				elseif ($area=="OUTBOX"){
        print "
<tr onClick=\"document.location='?area=OUTBOX&message=$row->hash';return false\" style=\"cursor: pointer;\" $element_id>
<td class=\"$class\">".strtoupper($row->area)."</td>
<td class=\"$class\">$row->fromname</td>
<td class=\"$class\">$row->toname</td>
<td class=\"$class\"><a href=\"?area=OUTBOX&message=$row->hash\">".txt2html($row->subject)."</a></td>
<td class=\"$class\">$row->date</td>
</tr>\n";
        }
				else {
					$area_url = urlencode($row->area);
					print "
<tr onClick=\"document.location='?area=$area_url&message=$row->hash';return false\" style=\"cursor: pointer;\" $element_id>
<td class=\"$class\">$row->fromname</td>
<td class=\"$class\">$row->toname</td>
<td class=\"$class\"><a href=\"?area=$area_url&message=$row->hash\">".txt2html($row->subject)."</a></td>
<td class=\"$class\">$row->date</td>
</tr>\n";
        }
      }
      print "</table>\n";
    }
		else{
      if ($area=='NETMAIL') {
        $text="� �������� ���� ��� �����. �� ������ �������� ����, � ������ ���������, ���� ���-������ ������� ���.";
      } elseif ($area=='OUTBOX') {
        $text="��� �������������� ��������� �����.";
      } elseif ($area=='FAVORITES') {
        $text="� ��������� ���� �����.";
      } elseif ($area=='CARBONAREA') {
        $text="� �������� ���� �����. ���� ���������� ������������ ��� ��� ������������ ���� ������ �� ��������������.";
      } else {
        $text="?";
      }
      print "
<table width=100% height=100%>
<tr valign=center>
<td align=center>$text</td>
</tr>
</table>\n";
    }
  }
  print "</div>
</td></tr>";
  if ($permission or $area=="NETMAIL" or $area=="CARBONAREA" or $area=="FAVORITES" or $area=="OUTBOX") {
// Рисуем тело письма
  print "<tr height=80%><td valign=top>
<div style='width: 100%; height: 100%; min-height:150px; word-break: break-all; overflow: scroll; background-color: \"#FFFFFF\"'>\n";
    if ($area=="OUTBOX"){
      $query="select * from `outbox` where hash='$hash' and fromaddr='$myaddr' and sent='0';";
    }
		else {
      $query="select * from `messages` where hash='$hash';";
    }
    $result=mysqli_query($link, $query);
    if (mysqli_num_rows($result) or $mode=="new") {
      $row = mysqli_fetch_object($result);
//доделать: рисовать в шапке "new" и "reply" только в том случае, если права на запись в эху есть.
      print "<table width=100% height=10>
<tr height=1%><td class=\"messagehead\" width=\"33%\">From: $row->fromname ($row->fromaddr)</td>
<td class=\"messagehead\" width=\"33%\">To: $row->toname ($row->toaddr)</td>
<td class=\"messagehead\" width=\"33%\">Date: $row->date</td>
<tr height=1%><td colspan=2 class=\"messagehead\">Subject: ".txt2html($row->subject)."</td>
<td align=right class=\"messagehead\">";
      if ($area!="OUTBOX") {
        print "<a href=\"?area=$row->area&message=$row->hash\"><img src=\"images/message.gif\" width=16 height=16 border=0 alt=\"view message\" title=\"view message\"></a>
               <a href=\"?area=$row->area&message=$row->hash&mode=source\"><img src=\"images/source.gif\" width=16 height=16 border=0 alt=\"view source\" title=\"view source\"></a>
               <a href=\"?area=$row->area&message=$row->hash&mode=reply\"><img src=\"images/reply.gif\" alt=\"reply\" title=\"reply\" border=0 width=16 height=16></a>";
// рисование дерева, начиная с текущего письма. а надо ли?
        if ($area!="CARBONAREA"){
          print " <a href=\"?area=$area&mode=new\"><img src=\"images/new.gif\" width=16 height=16 border=0 alt=\"new message\" title=\"new\"></a> ";
        }
        if ($area!="FAVORITES") {
          print "<a href=\"?area=$row->area&message=$row->hash&mode=add_to_favorites\"><img src=\"images/add_to_favorites.gif\" alt=\" add to favorites\" title=\"add to favorites\"  width=16 height=16 border=0></a> ";
        }
				else {
          print "<a href=\"?area=FAVORITES&message=$row->hash&mode=remove_from_favorites\"><img src=\"images/delete_from_favorites.gif\" alt=\" delete from favorites\" title=\"delete from favorites\"  width=16 height=16 border=0></a> ";
        }
          if ($area=="NETMAIL"){
//          print "<a href=\"?area=".rawurlencode($row->area)."&message=$row->hash&mode=delete\">delete</a>";
						print "<a href=\"?area=".rawurlencode($row->area)."&message=$row->hash&mode=delete\"><img src=\"images/trash.gif\" alt=\" delete message\" title=\"delete message\"  width=16 height=16 border=0></a> ";
        }
      }
      print "</td></tr>\n</table>\n";
  
      $text=explode("\n", $row->text);
      if ($mode=="source"){
        print message2source($text);
      }
			elseif ($mode=="reply"){
        $reply_to_name=explode(" ", $row->fromname);
        $quoute_string="";
				foreach ($reply_to_name as $quote_string_tmp){
          $quoute_string=$quoute_string.substr($quote_string_tmp,0,1);
        }
        print "<table width=100% height=90%><tr height=90%><td><form method=post action=\"?area=$row->area&message=$row->hash&mode=ansver\" style=\"width: 100%; height: 98%;\">\n<textarea name=text style=\"width: 100%; height: 100%;\">\n";        print "Hello, $reply_to_name[0]!\n\n";
        print message2textarea($text, $reply_to_name);
        print "\n� ���������� �����������, $myname.\n</textarea></td></tr>\n<tr height=10%><td>Subject: <input type=text name=subject value=\"$row->subject\">\nTo: <input type=text name=toname value=\"$row->fromname\">";
        if (($area=="NETMAIL") || ($mode=="reply")){
    				//if ($area == "NETMAIL") {	
					print "\nAddress: <input type=text name=\"toaddr\" value=\"$row->fromaddr\">";
				//}
					if ($mode=="reply") {
						print "\nArea: <select name='selarea'>";
						if ($area) {
							print "\n<option value=''>NETMAIL";
						}
						else {
							print "\n<option value='' selected>All areas";
						}
						$result=mysqli_query($link, "select upper(areas.area) as area from `areas` join `subscribe` where subscribe.area=areas.area and subscribe.point='$point' order by areas.area");
//						while ($row3=mysqli_fetch_object($result)) {
//							$selected="";
//							if ( strtoupper($area)==$row3->area) {
//								$selected=" selected";
//							}
//							else {
//								$selected=" selected";
//							}
//							print "\n<option value=$row3->area $selected> $row3->area";
//						}
while ($row3=mysqli_fetch_object($result)) {
    $selected = (strtoupper($area) == $row3->area) ? " selected" : "";
    print "\n<option value='$row3->area'$selected>$row3->area</option>";
}
						print "\n<option value=$area $selected> $area";
						print "\n</select>";
					}
        }
        print "<input type=hidden name=reply value=\"$row->msgid\">\n";
        print "<input type=submit value=\"���������\"></form></td></tr></table>\n";
      }
			elseif ($mode=="new"){
        print "
<table width=100% height=90%><tr height=90%><td>
<form method=post action=\"?area=$row->area&mode=ansver\" style=\"width: 100%; height: 98%\">
<textarea name=text style=\"width: 100%; height: 100%\">
Hello!


� ���������� �����������, $myname.
</textarea></td></tr>
<tr height=10%><td>Subject: <input type=text name=subject value=\"\">
To: <input type=text name=toname value=\"All\">";
        if ($area=="NETMAIL"){
					print "\nAddress: <input type=text name=\"toaddr\" value=\"\">";
        }
        print "<input type=hidden name=selarea value=\"$area\">\n";
        print"<input type=submit value=\"���������\"></form></td></tr>\n</table>\n";
			}
			else {
        print message2html($text);
      }
    }
		else {
      print "<table width=100%>
<tr height=1%><td class=\"messagehead\">&nbsp;</td></tr>
<tr height=1%><td colspan=2 class=\"messagehead\" align=right>";
      if ($area!="CARBONAREA" and $area!="OUTBOX"){
        print " <a href=\"?area=$area&mode=new\"><img src=\"images/new.gif\" width=16 height=16 border=0 alt=\"new message\" title=\"new\"></a>";
      }
			else {
        print "&nbsp;";
      }
			print "</td></tr>
</table>\n";
			if ($area!="OUTBOX"){
				print "<br><br><br><br><table width=100%><tr height=20%><td align=\"center\">Your message was sent and will be displayed after tossing process!</td><tr></table>\n";
			}
    }
  }
	else {
		print "</div>
</td></tr>
<tr height=1%><td class=\"messagehead\">
<center>� ��� ��� ���� ��� ��������� ���� ��������������, ���� �������������� �� ����������.</center>
</td></tr>
<tr height=80%><td valign=top>
<div style=\"word-break:break-all; width:100%; height:100%; overflow: auto; background-color: #F5F5F5\" id='thread'>
		";
  }
}

if (!$use_ajax){
//Оставляем метку с датой последнего просмотра эхи и указатель на последнее просмотренное письмо
  set_area_last_view($area,$hash);
}

if (isset($hash_new)) {
  $hash=$hash_new;
}

print "</div>
</td></tr>
</table>
</td></tr>
</table>
";
if ($use_ajax ) {
  print "<div style='display: none;'>
<form name=variables>
  <input name=lastmessage type=hidden>
  <input name=mode value='$mode' type=hidden>
  <input name=hash value='$hash' type=hidden>
  <input name=area value='$area' type=hidden>
  <input name=update_interval value='60' type=hidden>
</form>
";
}
print "<script type=\"text/javascript\">

function show_gen_time() {
  alert (\"".stop_timer()."\");
}
</script>";
?>

<script src="lib/JsHttpRequest.js?v=<?php echo filemtime('lib/JsHttpRequest.js'); ?>"></script>
<script src="lib/lib.js?v=<?php echo filemtime('lib/lib.js'); ?>"></script>
<script src="lib/periodicalExecuter.js?v=<?php echo filemtime('lib/periodicalExecuter.js'); ?>"></script>
</div>
</body>
<html>
