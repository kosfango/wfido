<?

require ('config.php');
require ('lib/lib.php');

start_timer();

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();
fix_post();
$point=check_session($_COOKIE['SESSION']);

$mode=substr($_GET["mode"],0,128);


$row = mysql_fetch_object(mysql_query("select * from `users` where point='$point'"));
$myaddr=$mynode.".".$row->point;
$myname=$row->name;




$class_my="itemlist";
$class_areafix="itemlist";
$class_other="itemlist";
if ($mode=="my"){
  $class_my="selected";
}elseif ($mode=="other"){
  $class_other="selected";
}else{
  $class_areafix="selected";
}




print "
<html>
<head>
<title>Online FTN reader - настройки</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=koi8-r\" />
<link rel=\"stylesheet\" href=\"css/settings.css\" type=\"text/css\" media=\"all\" />
</head>
<body>
<table border=0 width=100% height=100%>
 <tr height=1>
  <td colspan=2>";

print planka("settings");

print "
  </td>
 </tr>
 <tr>
  <td class=\"itemlist\" valign=top width=150px>
   <p onClick=\"document.location='?mode=areafix';return false\" style=\"cursor: pointer;\" class=$class_areafix><a href=\"?mode=areafix\" class=itemlist>подписка</a></p>
   <p onClick=\"document.location='?mode=my';return false\" style=\"cursor: pointer;\"  class=$class_my><a href=\"?mode=my\" class=itemlist>персональные данные</a></p>
   <p onClick=\"document.location='?mode=other';return false\" style=\"cursor: pointer;\"  class=$class_other><a href=\"?mode=other\" class=itemlist>прочее</a></p>
  </td>
  <td valign=top>";


if ($mode=="my"){


  if ($_POST['save']){
    mysql_query("update `users` set `origin`='".$_POST['origin']."', `name`='".$_POST['name']."', `email`='".$_POST['email']."' where `point`='$point';");
    if ($_POST['newpassword'] and $_POST['newpassword']==$_POST['newpassword2']){
      if (mysql_num_rows(mysql_query("select * from `users` where `password`='".$_POST['oldpassword']."' and `point`='$point';"))){
	mysql_query("update `users` set `password`='".$_POST['newpassword']."' where `point`='$point';");
	$error="\n<tr><td align=center colspan=2><font color=green>OK, password updated</font></td></tr>";
      } else {
	$error="\n<tr><td align=center colspan=2><font color=red>incorrect old password</font></td></tr>";
      }
    }
  }
  $result=mysql_query("select * from `users` where point='$point'");
  $row=mysql_fetch_object($result);
  print "
   <form method=post action=\"?mode=my\">
   <table width=100%>
    <tr><td class=item>Real Name</td><td class=item><input type=text style='width: 100%;' name='name' value='$row->name'></td></tr>
    <tr><td class=item>Email</td><td class=item><input type=text style='width: 100%;' name='email' value='$row->email'></td></tr>
    <tr><td class=item>Origin</td><td class=item><input type=text style='width: 100%;' name='origin' value='$row->origin'></td></tr>
    <tr><td clospan=2>&nbsp</td></tr>
    <tr><td class=item>Old password</td><td class=item><input type=password style='width: 100%;' name='oldpassword' value='$oldpassword'></td></tr>
    <tr><td class=item>New password</td><td class=item><input type=password style='width: 100%;' name='newpassword' value='$newpassword'></td></tr>
    <tr><td class=item>New password</td><td class=item><input type=password style='width: 100%;' name='newpassword2' value='$newpassword2'></td></tr>
    <tr><td align=right colspan=2><input type=hidden name=\"save\" value=\"1\"><input type=submit value=\"Сохранить\"></tr>$error
   </table>
   </form>
";







} elseif ($mode=="other") {
  if ($_POST['save']){
    if ($_POST['close_old_session']){
      $_POST['close_old_session']=1;
    }else {
      $_POST['close_old_session']=0;
    }
    if ($_POST['ajax']){
      $_POST['ajax']=1;
    }else {
      $_POST['ajax']=0;
    }
	 if ($_POST['scale_img']){
      $_POST['scale_img']=1;
    }else {
      $_POST['scale_img']=0;
    }
	 if ($_POST['media_disabled']){
      $_POST['media_disabled']=1;
    }else {
      $_POST['media_disabled']=0;
    }
    mysql_query("update `users` set `limit`='".$_POST['nums']."', `close_old_session`='".$_POST['close_old_session']."', `ajax`='".$_POST['ajax']."', `scale_img`='".$_POST['scale_img']."', `scale_value`='".$_POST['pxls']."', `media_disabled`='".$_POST['media_disabled']."' where `point`='$point'");

  }
  print "
   <form method=post action=\"?mode=other\">
   <table width=100%>\n";

  $row=mysql_fetch_object(mysql_query("select `limit`,`close_old_session`,`ajax`,`scale_img`,`scale_value`,`media_disabled` from `users` where `point`='$point'"));
  if ($row->close_old_session) {
    $close_old_session=" checked";
  } else {
    $close_old_session="";
  }
  if ($row->ajax) {
    $ajax=" checked";
  } else {
    $ajax="";
  }
   if ($row->scale_img) {
    $scale_img=" checked";
  } else {
    $scale_img="";
  }
  if ($row->media_disabled) {
    $media_disabled=" checked";
  } else {
    $media_disabled="";
  }
  print "
    <tr><td class=item>В режиме messages показывать писем не больше, чем...</td><td class=item><input type=text name=nums value=$row->limit></td></tr>
    <tr><td class=item>При логине закрывать старые сессии</td><td class=item><input type=checkbox name=close_old_session $close_old_session></td></tr>
    <tr><td class=item>Использовать javascript-интерфейс</td><td class=item><input type=checkbox name=ajax $ajax></td></tr>
    <tr><td class=item>Масштабировать изображения</td><td class=item><input type=checkbox name=scale_img $scale_img></td></tr>
	<tr><td class=item>Масштабировать изображения до, пикселей</td><td class=item><input type=text name=pxls value=$row->scale_value></td></tr>
	<tr><td class=item>Показывать только ссылки на изображения/видео</td><td class=item><input type=checkbox name=media_disabled $media_disabled></td></tr>
	<tr><td align=right colspan=2><input type=hidden name=\"save\" value=\"1\"><input type=submit value=\"Сохранить\"></tr>
   <table>
   </form>";
} else {
  if ($_POST['save']){
    mysql_query("delete from `subscribe` where `point`='$point';");
    foreach ($_POST as $key=>$value) {
      if (substr($key,0,5)=="subs-"){
        $area=substr($key,5);
	mysql_query("insert into `subscribe` set `area`='$area', `point`='$point'");
      }
    }
  }
  print "
   <form method=post action=\"?mode=areafix\">
   <table width=100% border=0>";

  if ($_GET['order']=="messages"){
    $order="areas.messages desc";
  } else {
    $order="areas.area";
  }


print "
    <tr><td class=item align=center width=50%><a href='?mode=areafix&order=area'>имя конференции</a></td><td class=item align=center width=50%><a href='?mode=areafix&order=messages'>кол-во сообщений</a></td></tr>\n";

  $result=mysql_query("select upper(areas.area) as area, areas.messages as messages, subscribe.area as subs from areas join user_groups join area_groups left join subscribe on (areas.area=subscribe.area and subscribe.point='$point') where user_groups.point='$point' and user_groups.perm and area_groups.group=user_groups.group and areas.area=area_groups.area order by $order;");

  while($row=mysql_fetch_object($result)){
    if ($row->subs){
      $value=" checked";
    } else {
      $value="";
    }
    $echonameurlenc=urlencode($row->area);
    print "
      <tr><td class=item width=50%><input type=checkbox name='subs-$row->area' $value><a href='$webroot/index.php?area=$echonameurlenc'>$row->area</a></td><td class=item width=50%>$row->messages</td></tr>\n";
  }
  print"
    <tr><td align=right colspan=2><input type=hidden name=\"save\" value=\"1\"><input type=submit value=\"Сохранить\"></tr>$error
   </table>
   </form>\n";
}


print "
  </td>
 </tr>
</table>
<script type=\"text/javascript\">

function show_gen_time()
{
alert (\"".stop_timer()."\");
}
</script>

</body>
</html>
";


?>