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
//�������� ���� � �����
$query=mysqli_query($link, "select * from `users` where point='$point'");
$row=mysqli_fetch_object($query);
$myaddr=$mynode.".".$row->point;
$myname=$row->name;

$return=0;

if ($area=='NETMAIL'){
  $result=mysqli_query($link, "select unix_timestamp(max(recieved)) as rec,unix_timestamp(current_timestamp) as cur from messages where area='' and (toaddr='$myaddr' or fromaddr='$myaddr') group by area");
  if (mysqli_num_rows($result)) {
    $row = mysqli_fetch_object($result);
    $lastmessage=$row->rec;
    $current=$row->cur;
  }
} elseif (strtoupper($area)=='CARBONAREA'){
//��������: �������� ������ ������ �� ��� ��, �� ������� ���� �����
  $result=mysqli_query($link, "select  area, unix_timestamp(max(recieved)) as rec,unix_timestamp(current_timestamp) as cur from messages where   toname='$myname' and area!='' group by toname");
  $result2=mysqli_query($link, "select area, unix_timestamp(max(recieved)) as rec,unix_timestamp(current_timestamp) as cur from messages where fromname='$myname' and area!='' group by fromname");
  if (mysqli_num_rows($result) or mysqli_num_rows($result2)){
    $row = mysqli_fetch_object($result);
    $row2 = mysqli_fetch_object($result2);
    if ($row->rec > $row2->rec){
      $lastmessage=$row->rec;
      $current=$row->cur;
    } else {
      $lastmessage=$row2->rec;
      $current=$row2->cur;
    }
  }
} else {
//���� ���� �������� �������� ���� �� ���

  $result=mysqli_query($link, "select unix_timestamp(areas.recieved) as rec, unix_timestamp(current_timestamp) as cur from `areas` where areas.area='$area'");
  if (mysqli_num_rows($result)) {
    $row = mysqli_fetch_object($result);
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
