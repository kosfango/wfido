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
connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();


$point=check_session($_COOKIE['SESSION']);
//Получаем инфо о юзере
$row = mysql_fetch_object(mysql_query("select * from `users` where point='$point'"));
$myaddr=$mynode.".".$row->point;
$myname=$row->name;

//Рисуем список эх

//Нетмайл:
$result=mysql_query("select count(messages.area) as nummsg, unix_timestamp(max(messages.recieved)) as rec, unix_timestamp(view.last_view_date) as last_view_date from messages,view where messages.area='' and (messages.toaddr='$myaddr' or messages.fromaddr='$myaddr') and view.area='NETMAIL' and view.point='$point' group by view.area;");

//считаем количество сообщений
if (mysql_num_rows($result)){ 
  $row = mysql_fetch_object($result);
  $nummsg = $row->nummsg;
} else {
  $nummsg="0";
}
//если эха выбрана, то выделяем ее цветом
if ($area=="NETMAIL") {
  $class="selected";
} else {
  $class="netmail";
  if (($row->last_view_date - $row->rec) < 0){
    $newmessages="*";
  } else {
    $newmessages="";
  }
}
$return= "<p onClick=\"document.location='?area=NETMAIL';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=NETMAIL\"  class=\"netmail\">NETMAIL</a> ($nummsg) $newmessages</p>\n";

//Карбонка:
$result=mysql_query("select count(view.last_view_date) as nummsg, messages.area, unix_timestamp(max(messages.recieved)) as rec, unix_timestamp(view.last_view_date) as last_view_date from messages,view where  messages.area!='' and messages.toname='$myname' and view.area='CARBONAREA' and view.point='$point' group by view.last_view_date;");
$result2=mysql_query("select count(view.last_view_date) as nummsg, messages.area, unix_timestamp(max(messages.recieved)) as rec, unix_timestamp(view.last_view_date) as last_view_date from messages,view where messages.fromname='$myname' and messages.area!='' and view.area='CARBONAREA' and view.point='$point' group by view.last_view_date;");
if (mysql_num_rows($result) or mysql_num_rows($result2)){
  $row = mysql_fetch_object($result);
  $row2 = mysql_fetch_object($result2);
  $nummsg = $row->nummsg + $row2->nummsg;
} else {
  $nummsg="0";
}
if ($area=="CARBONAREA") {
  $class="selected";
  $newmessages="";
} else {
  $class="carbonarea";
  if ((($row->last_view_date - $row->rec) < 0) or (($row2->last_view_date - $row2->rec) < 0)){
    $newmessages="*";
  } else {
    $newmessages="";
  }
}
$return=$return . "<p onClick=\"document.location='?area=CARBONAREA';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=CARBONAREA\" class=\"carbonarea\">CARBONAREA</a> ($nummsg) $newmessages</p>\n";

//Все остальные эхи:
$result=mysql_query("select areas.area, areas.messages as nummsg, unix_timestamp(areas.recieved) as rec, unix_timestamp(view.last_view_date) as last_view_date from areas join subscribe left join view on (view.area=areas.area and view.point='$point') where subscribe.area=areas.area and subscribe.point='$point' order by areas.area;");
if (mysql_num_rows($result)) {
  while ($row = mysql_fetch_object($result)) {
    if ($area==strtoupper($row->area)) {
      $class="selected";
      $newmessages="";
    } else {
      $class="echo";
      if (($row->last_view_date - $row->rec) < 0){
        $newmessages="*";
      } else {
        $newmessages="";
      }
    }
    if ($mode=="thread"){
      $return=$return .  "<p onClick=\"document.location='?area=$row->area&mode=thread';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=$row->area&mode=thread\" class=\"echo\">$row->area</a> ($row->nummsg) $newmessages</p>\n";
    } else {
      $return=$return .  "<p onClick=\"document.location='?area=$row->area';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=$row->area\" class=\"echo\">$row->area</a> ($row->nummsg) $newmessages</p>\n";
    }
  }
}

//Outbox - уже написанные, но еще не отправленные письма.
if ($area=="OUTBOX") {
  $class="selected";
} else {
  $class="outbox";
}
$return=$return .  "<p onClick=\"document.location='?area=OUTBOX';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=OUTBOX\" class=\"outbox\">OUTBOX</a></p>\n";

//Favorites - избранное.
if ($area=="FAVORITES") {
  $class="selected";
} else {
  $class="favorites";
}
$return=$return .  "<p onClick=\"document.location='?area=FAVORITES';return false\" style=\"cursor: pointer;\" class=\"$class\"><a href=\"?area=FAVORITES\" class=\"favorites\">FAVORITES</a></p>\n";

$GLOBALS['_RESULT'] = array(
      "area"   => $area,
      "mode"   => $mode,
      "text"  => $return
);

//print_r($GLOBALS['_RESULT']);

// This includes a PHP fatal error! It will go to the debug stream,
// frontend may intercept this and act a reaction.
if ($_REQUEST['str'] == 'error') {
  error_demonstration__make_a_mistake_calling_undefined_function();
}
?>
