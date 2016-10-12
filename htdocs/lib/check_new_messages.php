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

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();

$point=check_session($_COOKIE['SESSION']);
//Получаем инфо о юзере
$row = mysql_fetch_object(mysql_query("select * from `users` where point='$point'"));
$myaddr=$mynode.".".$row->point;
$myname=$row->name;

$return=0;

if ($area=='NETMAIL'){
  $result=mysql_query("select unix_timestamp(max(recieved)) as rec,unix_timestamp(current_timestamp) as cur from messages where area='' and (toaddr='$myaddr' or fromaddr='$myaddr') group by area;");
  if (mysql_num_rows($result)) {
    $row = mysql_fetch_object($result);
    $lastmessage=$row->rec;
    $current=$row->cur;
  }
} elseif (strtoupper($area)=='CARBONAREA'){
//доделать: включать письма только из тех эх, на которые есть права
  $result=mysql_query("select  area, unix_timestamp(max(recieved)) as rec,unix_timestamp(current_timestamp) as cur from messages where   toname='$myname' and area!='' group by toname;");
  $result2=mysql_query("select area, unix_timestamp(max(recieved)) as rec,unix_timestamp(current_timestamp) as cur from messages where fromname='$myname' and area!='' group by fromname ;");
  if (mysql_num_rows($result) or mysql_num_rows($result2)){
    $row = mysql_fetch_object($result);
    $row2 = mysql_fetch_object($result2);
    if ($row->rec > $row2->rec){
      $lastmessage=$row->rec;
      $current=$row->cur;
    } else {
      $lastmessage=$row2->rec;
      $current=$row2->cur;
    }
  }
} else {
//сюда надо добавить проверку прав на эху

  $result=mysql_query("select unix_timestamp(areas.recieved) as rec, unix_timestamp(current_timestamp) as cur from `areas` where areas.area='$area';");
  if (mysql_num_rows($result)) {
    $row = mysql_fetch_object($result);
    $lastmessage=$row->rec;
    $current=$row->cur;
  }
}

$GLOBALS['_RESULT'] = array(
      "area"   => $area,
      "lastmessage"  => $lastmessage,
      "currentdate"  => $current	
);

//print_r($GLOBALS['_RESULT']);

// This includes a PHP fatal error! It will go to the debug stream,
// frontend may intercept this and act a reaction.
if ($_REQUEST['str'] == 'error') {
  error_demonstration__make_a_mistake_calling_undefined_function();
}
?>
