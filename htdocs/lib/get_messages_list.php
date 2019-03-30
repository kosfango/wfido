<?

require_once ('../config.php');
require_once ('lib.php');
require_once ('JsHttpRequest.php');
$JsHttpRequest = new JsHttpRequest("koi8-r");
if ($_REQUEST['area']) {
    $area=strtoupper(substr($_REQUEST['area'],0,128));
} else {
    $area="NETMAIL";
}

if ($_REQUEST['mode']=='thread'){
    $mode='thread';
} else {
    $mode='';
}

if ($_REQUEST['hash']){
    $hash=substr($_REQUEST['hash'],0,128);
} else {
    $hash='';
}
connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();


$point=check_session($_COOKIE['SESSION']);
//�������� ���� � �����
$query=mysqli_query($link, "select * from `users` where point='$point'");
$row=mysqli_fetch_object($query);
$myaddr=$mynode.".".$row->point;
$myname=$row->name;
$permission=get_area_permissions($area);


if (($mode=="thread" or $mode=="tree") and $area!="OUTBOX" and $area!="CARBONAREA" and $area!="FAVORITES" and $area!="NETMAIL"){
  if ($permission) {
    //�������� ������ ������ � ���
    if (!$hash) {
        $hash=get_area_last_message($area);
    }
    $return= "\n<table width=100%>";
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
      } elseif ($row->rec>$row->lastview) {
        $class="new";
        $element_id="";
      } elseif ($row->thread==$thread_selected){
        $class="selected";
        $header_text="<font color=green>$area</font>: $row->subject";
        $element_id=" name=\"selected\", id=\"selected\" ";
      } else {
        $class="msglist";
        $element_id="";
      }
      $return=$return. "<tr $element_id  onClick=\"document.location='?area=$area&message=$row->hash&mode=thread';return false\" ><td class=$class><a href=\"?area=$area&message=$row->hash&mode=thread\">".txt2html($row->subject)."</a></td><td class=$class>$row->num</td><td class=$class>$row->date ($row->fromname)</td></tr>\n";
    }
    $return=$return. "</table>\n";
  } else {
    $header_text="<center>� ��� ��� ���� ��� ��������� ���� ��������������, ���� �������������� �� ����������.</center>";
  }
} elseif ($permission or $area=="NETMAIL" or $area=="CARBONAREA" or $area=="FAVORITES" or $area=="OUTBOX") {
    //�������� ������ ����� � ���
    $area_last_read_date=get_area_last_view($area);
    $last_viewed_message_hash=get_area_last_message($area);
    if ($area=="NETMAIL") {
      $query="select area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec from `messages` where area='' and (fromaddr='$myaddr' or toaddr='$myaddr') order by rec desc";
    } elseif ($area=="CARBONAREA") {

      //������� ��������� ������� ��� ���� ������������� �����
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
	) CHARSET=utf8;" );

      //�������� � ��� ���������
      mysqli_query($link, "insert into `tmp` (`fromname`, `fromaddr`, `toname`, `toaddr`, `area`, `subject`, `date`,`msgid`, `reply`, `hash`, `recieved`)
                               select `fromname`, `fromaddr`, `toname`, `toaddr`, `area`, `subject`, `date`,`msgid`, `reply`, `hash`, `recieved`
                               from `messages` where toname='$myname' and area!=''");
      mysqli_query($link, "insert into `tmp` (`fromname`, `fromaddr`, `toname`, `toaddr`, `area`, `subject`, `date`,`msgid`, `reply`, `hash`, `recieved`)
                               select `fromname`, `fromaddr`, `toname`, `toaddr`, `area`, `subject`, `date`,`msgid`, `reply`, `hash`, `recieved`
                               from `messages` where fromname='$myname' and area!=''");
      $query="select  area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec  from `tmp` order by rec desc";

    } elseif ($area=="OUTBOX") {
      $query="select area,fromname,toname,fromaddr,toaddr,subject,date,hash from `outbox` where fromaddr='$myaddr' and sent='0' order by date desc";
    } elseif ($area=="FAVORITES") {
      $query="select messages.area as area,messages.fromname as fromname,messages.toname as toname,messages.subject as subject,messages.date as data,messages.hash as hash from `messages` join `favorites` where messages.hash=favorites.message and point='$point' order by date desc";
    } else {
      $query="select  area,hash,fromname,toname,subject,date,unix_timestamp(recieved) as rec  from `messages` where area='$area' order by id desc";
    }
    $query2=mysqli_query($link, "select `limit` from `users` where `point`='$point'");
    $row=mysqli_fetch_object($query2);
    $user_limit=$row->limit;
    if ($user_limit) {
      $query=$query." limit $user_limit ;";
    } else {
      $query=$query." ;";
    }

    $result=mysqli_query($link, $query);
    if (mysqli_num_rows($result)) {
      $return= "<table width=100%>\n";
      while ($row = mysqli_fetch_object($result)) {
        //���� �� �������, ����� ������ ������ ��� ����������, �� �������� ��������� �������������.
        //���� ��������� ������������� �� �������, �� ���������� ����� ������ �� ������
        if (!$hash and $last_viewed_message_hash) {
          $hash=$last_viewed_message_hash;
        } elseif (!$hash){
          $hash=$row->hash;
        }
        //��� ������ � ����� ��������� ������, ��� ���� ���������� ������ � ���, ������� ������. � ��������.
        //���������� ������ (�� �� ������� ����� "������") ��� �� ��������
        if(($area_last_read_date - $row->rec) < 0 and $hash==$row->hash) {
          $class="newselected";
          $element_id=" name=\"selected\", id=\"selected\" ";
        }elseif(($area_last_read_date - $row->rec) < 0) {
          $class="new";
          $element_id="";
        }elseif ($hash==$row->hash){ 
          $class="selected";
          $element_id=" name=\"selected\", id=\"selected\" ";
        } else {
          $class="msglist";
          $element_id="";
        }
        //����������, ������� ������� � �������.
        if (!trim($row->subject)) {$row->subject="(no subject)";}
        if ($area=="CARBONAREA") {
	$return=$return. "
<tr style=\"cursor: pointer;\" $element_id>
<td onClick=\"document.location='?area=$row->area&message=$row->hash';return false\" class=\"$class\">".strtoupper($row->area)."</td>
<td onClick=\"document.location='?area=CARBONAREA&message=$row->hash';return false\" class=\"$class\">$row->fromname</td>
<td onClick=\"document.location='?area=CARBONAREA&message=$row->hash';return false\" class=\"$class\">$row->toname</td>
<td onClick=\"document.location='?area=CARBONAREA&message=$row->hash';return false\" class=\"$class\"><a href=\"?area=CARBONAREA&message=$row->hash\">".txt2html($row->subject)."</a></td>
<td onClick=\"document.location='?area=CARBONAREA&message=$row->hash';return false\" class=\"$class\">$row->date</td>
</tr>\n";

        } elseif ($area=="FAVORITES") {

          $return=$return. "
<tr style=\"cursor: pointer;\" $element_id>
<td onClick=\"document.location='?area=$row->area&message=$row->hash';return false\" class=\"$class\">"; 
          if ($row->area){
            $return=$return. strtoupper($row->area);
          } else {
      	    $return=$return. "NETMAIL";
          }
          $return=$return. "</td>
<td onClick=\"document.location='?area=FAVORITES&message=$row->hash';return false\" class=\"$class\">$row->fromname</td>
<td onClick=\"document.location='?area=FAVORITES&message=$row->hash';return false\" class=\"$class\">$row->toname</td>
<td onClick=\"document.location='?area=FAVORITES&message=$row->hash';return false\" class=\"$class\"><a href=\"?area=CARBONAREA&message=$row->hash\">".txt2html($row->subject)."</a></td>
<td onClick=\"document.location='?area=FAVORITES&message=$row->hash';return false\" class=\"$class\">$row->date</td>
</tr>\n";
        } elseif ($area=="OUTBOX"){
          $return=$return. "
<tr onClick=\"document.location='?area=OUTBOX&message=$row->hash';return false\" style=\"cursor: pointer;\" $element_id>
<td class=\"$class\">".strtoupper($row->area)."</td>
<td class=\"$class\">$row->fromname</td>
<td class=\"$class\">$row->toname</td>
<td class=\"$class\"><a href=\"?area=OUTBOX&message=$row->hash\">".txt2html($row->subject)."</a></td>
<td class=\"$class\">$row->date</td>
</tr>\n";
        } else {
$area_url=urlencode($row->area);
          $return=$return. "
          <tr onClick=\"document.location='?area=$area_url&message=$row->hash';return false\" style=\"cursor: pointer;\" $element_id>
          <td class=\"$class\">$row->fromname</td>
          <td class=\"$class\">$row->toname</td>
          <td class=\"$class\"><a href=\"?area=$area_url&message=$row->hash\">".txt2html($row->subject)."</a></td>
          <td class=\"$class\">$row->date</td>
          </tr>\n";
        }
      }
      $return=$return. "</table>\n";
    }else{
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
      $return=$return. "
<table width=100% height=100%>
<tr valign=center>
<td align=center>$text</td>
</tr>
</table>\n";

    }
}

set_area_last_view($area,$hash);
$GLOBALS['_RESULT'] = array(
      "area"   => $area,
      "mode"   => $mode,
      "hash"   => $hash,
      "text"  => $return
);

//print_r($GLOBALS['_RESULT']);

// This includes a PHP fatal error! It will go to the debug stream,
// frontend may intercept this and act a reaction.
if ($_REQUEST['str'] == 'error') {
  error_demonstration__make_a_mistake_calling_undefined_function();
}
?>
