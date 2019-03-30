<?
require ('config.php');
require ('lib/lib.php');
start_timer();

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();
fix_post();


$point=check_session($_COOKIE['SESSION']);
if ($point!=$adminpoint){
  print "Access denied";
  exit;
}

$mode=substr($_GET["mode"],0,128);


print "<html>
<head>
<title>Online FTN reader - админка</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=koi8-r\" />
<link rel=\"stylesheet\" href=\"css/admin.css\" type=\"text/css\" media=\"all\" />
</head>
<body>
";

$query = mysqli_query($link, "select * from `users` where point='$point'");
$row = mysqli_fetch_object($query);
$myaddr=$mynode.".".$row->point;
$myname=$row->name;




$class_areas="itemlist";
$class_users="itemlist";
$class_moder="itemlist";
$class_sent="itemlist";
$class_default="itemlist";
$class_groups="itemlist";
if ($mode=="areas"){
  $class_areas="selected";
}elseif ($mode=="users"){
  $class_users="selected";
}elseif ($mode=="default"){
  $class_default="selected";
}elseif ($mode=="groups"){
  $class_groups="selected";
}elseif ($mode=="sent"){
  $class_sent="selected";
}else{
  $class_moder="selected";
}
print "<table width=100% height=100%>
 <tr height=1>
  <td colspan=2>";

print planka("admin");

print "  </td>
 </tr>
 <tr>
  <td valign=top width=150px class=\"itemlist\">
   <p onClick=\"document.location='?mode=moder';return false\" style=\"cursor: pointer;\" class=\"$class_moder\"><a href=admin.php?mode=moder class=itemlist>Премодерация</a></p>
   <p onClick=\"document.location='?mode=sent';return false\" style=\"cursor: pointer;\" class=\"$class_sent\"><a href=admin.php?mode=sent class=itemlist>Отправленные</a></p>
   <p onClick=\"document.location='?mode=users';return false\" style=\"cursor: pointer;\" class=\"$class_users\"><a href=admin.php?mode=users class=itemlist>Пользователи</a></p>
   <p onClick=\"document.location='?mode=default';return false\" style=\"cursor: pointer;\" class=\"$class_default\"><a href=admin.php?mode=default class=itemlist>Настройки по-умолчанию</a></p>
   <p onClick=\"document.location='?mode=areas';return false\" style=\"cursor: pointer;\" class=\"$class_areas\"><a href=admin.php?mode=areas class=itemlist>Эхоконференции</a></p>
   <p onClick=\"document.location='?mode=groups';return false\" style=\"cursor: pointer;\" class=\"$class_groups\"><a href=admin.php?mode=groups class=itemlist>Группы</a></p>
  </td>
  <td valign=top>
";



if ($mode=="areas"){
if (isset($_POST['save']))
{
  if ($_POST['save']){
    foreach (getRealInput('POST') as $key=>$value) {
      if (substr($key,0,9)=="checkbox-"){
        $area=substr($key,9);
	mysqli_query($link, "replace into `area_groups` set area='".$area."', area_groups.group='".$_POST['set_group']."'");
      }
    }
  }
}
  print "<form method=post action='?mode=areas'> 
<table width=100%>
 <tr><td class=header>area</td><td class=header>group</td></tr>";
   $result=mysqli_query($link, "select distinct upper(areas.area) as area, groups.name as grp from areas left join area_groups on areas.area=area_groups.area  left join groups on area_groups.group=groups.id order by area");
  while ($row = mysqli_fetch_object($result)){
    if (!$row->grp){
      $group="<font color=red>группа не задана</font>";
    } else {
      $group=$row->grp;
    }
    print " <tr><td class=item>$row->area</td><td class=item><input type=checkbox name=\"checkbox-$row->area\">$group</td></tr>\n";
  }
  print "<tr><td align=right class=item>Сменить для выбранных эхоконференций группу на</td><td class=item><select name=set_group style=\"width: 100%;\">";
  $result=mysqli_query($link, "select id,name from groups");
  while ($row=mysqli_fetch_object($result)){
    print "<option value=\"$row->id\"> $row->name";
  }
  print "</select></td></tr>
 <tr><td colspan=2 align=right><input type=hidden name=\"save\" value=\"1\"><input type=submit value=\"Сохранить\"></td></tr>
</table></form>";


}elseif ($mode=="groups"){
if (isset($_POST['save']))
{
  if ($_POST['save']){
    foreach (getRealInput('POST') as $key=>$value) {
      if (substr($key,0,5)=="text-" and $value){
        $id=substr($key,5);
	mysqli_query($link, "replace into `groups` set id='".$id."', name='".$value."'");
      }
    }
    if ($_POST['new_group']){
      mysqli_query($link, "insert into `groups` set name='".$_POST['new_group']."'");
    }
  }
}
  print "<form method=post action='?mode=groups'> 
<table width=100%>
 <tr><td class=header>group name</td><td class=header>rename to</td></tr>";
  $result=mysqli_query($link, "select * from groups");
  while ($row = mysqli_fetch_object($result)){
    print " <tr><td class=item>$row->name</td><td class=item><input type=text name=\"text-$row->id\" value=\"\"></td></tr>\n";
  }
  print "
 <tr><td align=right class=item> создать новую группу</td><td class=item><input type=text name=\"new_group\" value=\"\"></td></tr>
 <tr><td colspan=2 align=right><input type=hidden name=\"save\" value=\"1\"><input type=submit value=\"Сохранить\"></td></tr>
</table></form>";



}elseif ($mode=="users"){
if (isset($_POST['save']))
{
  if ($_POST['save']){
    foreach ($_POST as $key=>$value) {
      if (substr($key,0,5)=="perm-" and $value){
        $key=substr($key,5);
	list($user,$group)=explode("-",$key);
	if ($value=="deny"){
	  mysqli_query($link, "delete from `user_groups` where `point`='$user' and `group`='$group'");
	}elseif($value=="read"){
	  mysqli_query($link, "replace into `user_groups` set `point`='$user', `group`='$group', `perm`='1'");
	}elseif($value=="premod"){
	  mysqli_query($link, "replace into `user_groups` set `point`='$user', `group`='$group', `perm`='2'");
	}elseif($value=="antispam"){
	  mysqli_query($link, "replace into `user_groups` set `point`='$user', `group`='$group', `perm`='4'");
	}elseif($value=="write"){
	  mysqli_query($link, "replace into `user_groups` set `point`='$user', `group`='$group', `perm`='3'");
	}
      }
    }
  }
}
  print "<form method=post action='?mode=users'> 
<table width=100%>
 <tr><td class=header>user info</td><td class=header>groups</td></tr>\n";
  $result=mysqli_query($link, "select point,name,email,active from users order by point");
    while ($row = mysqli_fetch_object($result)){
    if ($row->active) {
      $active="<font color=green>active</font>";
    } else {
      $active="<font color=red>not active</font>";
    }
    print " <tr>
  <td class=item valign=top>$row->name($mynode.$row->point, $row->email), $active</td>
  <td class=item>\n";
    $result2=mysqli_query($link, "select groups.name as groupname, groups.id as groupid, user_groups.point as point, user_groups.perm as `perm` from groups left join user_groups on (groups.id=user_groups.group and user_groups.point='$row->point')");
    while ($row2 = mysqli_fetch_object($result2)){
      $read="";
      $write="";
      $premod="";
      $deny="";
      $antispam="";
      if ($row2->perm){ 
	if ($row2->perm=="3"){
	  $write=" selected";
	} elseif ($row2->perm=="2") {
	  $premod=" selected";
	} elseif ($row2->perm=="4") {
	  $antispam=" selected";
	} else {
	  $read=" selected";
	}
      } else {
	$deny=" selected";
      }
      print "
   <select name=perm-$row->point-$row2->groupid>
     <option value=read $read>read
     <option value=premod $premod>premoderated
     <option value=antispam $antispam>antispam
     <option value=write $write>read-write
     <option value=deny $deny>deny
   </select>
    $row2->groupname<br>\n";
    }
    print "  </td>
 </tr>";
  }
  print "
 <tr>
  <td colspan=2 align=right>
   <input type=hidden name=\"save\" value=\"1\">
   <input type=submit value=\"Сохранить\">
  </td>
 </tr>
</table>
</form>";
}elseif ($mode=="default"){
if (isset($_POST['save']))
{
  if ($_POST['save']){
    mysqli_query($link, "delete from `default`");
    mysqli_query($link, "delete from `default_perm`");
    mysqli_query($link, "delete from `default_subscribe`");
    foreach ($_POST as $key=>$value) {
      if (substr($key,0,6)=="group-" and $value){
        $group_id=substr($key,6);
	if ($value=="write") {
	  mysqli_query($link, "insert into `default_perm` set `group`='$group_id', `perm`='3'");
	} elseif ($vale=="antispam") {
	  mysqli_query($link, "insert into `default_perm` set `group`='$group_id', `perm`='4'");
	} elseif ($value=="premod") {
	  mysqli_query($link, "insert into `default_perm` set `group`='$group_id', `perm`='2'");
	} elseif ($value=="read") {
	  mysqli_query($link, "insert into `default_perm` set `group`='$group_id', `perm`='1'");
	}
      } elseif (substr($key,0,5)=="subs-" and $value){
        $group_id=substr($key,5);
	mysqli_query($link, "insert into `default_subscribe` set `group`='$group_id'");
      }
    }
    if ($_POST['origin']){
      mysqli_query($link, "insert into `default` set `value`='".$_POST['origin']."', `key`='origin'");
    }
  }
}
  print "<form method=post action='?mode=default'>\n";
  $result=mysqli_query($link, "select * from `default` where `key`='origin'");
  $row = mysqli_fetch_object($result);
  print"<table width=100%>
<tr><td class=header colspan=2>Default settings</td></tr>
<tr><td class=\"item\">Origin:</td><td><input type=text name=origin zise=128 style=\"width: 100%\" value='$row->value'></td></tr>
</table>
<table width=100%>
 <tr><td class=header>group</td><td class=header>Default permissions</td><td class=header>subscribe</td></tr>";
  $result=mysqli_query($link, "select groups.name as group_name, groups.id as group_id, default_perm.perm as `perm`, default_subscribe.group as subscribe from groups left join `default_perm` on (groups.id=default_perm.group) left join `default_subscribe` on (groups.id=default_subscribe.group)");
  while ($row = mysqli_fetch_object($result)){
    $read="";
    $write="";
    $deny="";
    $premod="";
    $subscribe="";
    $antispam="";
    if ($row->perm=="3"){
      $write=" selected";
    } elseif ($row->perm=="4"){
      $antispam=" selected";
    } elseif ($row->perm=="2"){
      $premod=" selected";
    } elseif ($row->perm=="1"){
      $read=" selected";
    } else {
      $deny=" selected";
    }
    if ($row->group_id=='1'){
      $subscribe=" disabled";
    } elseif ($row->subscribe){
      $subscribe=" checked=\"1\"";
    }
    print "
 <tr>
  <td class=item>$row->group_name</td>
  <td class=item><select name=\"group-$row->group_id\">
   <option value=read $read>read
   <option value=premod $premod>premoderated
   <option value=antispam $antispam>antispam
   <option value=write $write>read-write
   <option value=deny $deny>deny
   </select>
  </td>
  <td class=item><input type=checkbox name=\"subs-$row->group_id\" $subscribe></td>
 </tr>\n";
  }
  print "
 <tr><td colspan=3 align=right><input type=hidden name=\"save\" value=\"1\"><input type=submit value=\"Сохранить\"></td></tr>
</table>
</form>";


} elseif ($mode=="sent") {
  $hash=substr($_GET["message"],0,128);
  $result=mysqli_query($link, "select outbox.area,outbox.fromname,outbox.toname,outbox.fromaddr,outbox.toaddr,outbox.subject,outbox.date,outbox.hash, groups.name as grp from `outbox` left join `area_groups` on (area_groups.area=outbox.area) left join `groups` on (area_groups.group=groups.id) where outbox.sent='1' order by outbox.date desc");

  if (mysqli_num_rows($result)) {
    print "<table width=\"100%\" height=\"100%\">
<tr height=20%><td valign=top>
<div name=\"msglist\" id=\"msglist\"  style=\"height: 150px; overflow: auto; border: 0\">
<table width=100%>";
    while ($row=mysqli_fetch_object($result)){
      if (!$hash){
        $hash=$row->hash;
      }
      if ($hash==$row->hash){
        $class="selected";
        $element_id=" name=\"selected\", id=\"selected\" ";
      } else {
        $class="msglist";
        $element_id="";
      }
      print "
<tr onClick=\"document.location='?mode=sent&message=$row->hash';return false\" style=\"cursor: pointer;\" $element_id>
<td class=\"$class\">".strtoupper($row->area)." ($row->grp)</td>
<td class=\"$class\">$row->fromname</td>
<td class=\"$class\">$row->toname</td>
<td class=\"$class\"><a href=\"?mode=sent&message=$row->hash\">$row->subject</a></td>
<td class=\"$class\">$row->date</td>
</tr>";
    }
    print "
</table></div>
</td></tr>
<tr height=80%><td valign=top>
<div style=\"word-break:break-all; width:100%; height:100%; overflow: auto; background-color: #F5F5F5\">\n
";


    $result=mysqli_query($link, "select * from `outbox` where hash='$hash'");
    if (mysqli_num_rows($result) or $mode=="new") {
      $row = mysqli_fetch_object($result);

      print "
<table width=100% height=100%>
<tr height=1%><td class=\"messagehead\" width=\"33%\">From: $row->fromname ($row->fromaddr)</td>
<td class=\"messagehead\" width=\"33%\">To: $row->toname ($row->toaddr)</td>
<td class=\"messagehead\" width=\"33%\">Date: $row->date</td>
<tr height=1%><td colspan=2 class=\"messagehead\">Subject: $row->subject</td>
<td align=right class=\"messagehead\">
<tr height=98%><td colspan=3 class=\"message\" valign=top>";
      $text=explode("\n", $row->text);
      print message2html($text);
    

      print "</tr></td></table>";

    } else {
      print "нет такого письма";
    }


    print "</div></td></tr></table>";
  } else {
    print "&nbsp;";
  }

} else {
  $hash=substr($_GET["message"],0,128);
  if ($_GET["action"]=="approve"){
    mysqli_query($link, "update `outbox` set aprove='1' where hash='$hash'");
  } elseif ($_GET["action"]=="reject") {
      $result=mysqli_query($link, "select * from `outbox` where hash='$hash'");
      $row=mysqli_fetch_object($result);
      mysqli_query($link, "
insert into `outbox`
set area='NETMAIL', fromname='Sysop', toname='$row->fromname', subject='Письмо не прошло премодерацию', text='
Привет, $row->fromname!
Твоё письмо в $row->area не прошло премодерацию.

Письмо, которое не было отправлено:
=======================================
AREA: $row->area
FROM: $row->fromname($row->fromaddr)
TO:   $row->toname($row->toaddr)
=======================================
$row->text
', fromaddr='$mynode', toaddr='$row->fromaddr', origin='Bad robot', reply='', date=now(), hash='".md5(rand())."', sent='0', aprove='1'");

      mysqli_query($link, "update `outbox` set sent='1' where hash='$hash'");
  } elseif ($_GET["action"]=="drop") {
      mysqli_query($link, "update `outbox` set sent='1' where hash='$hash'");
  }  
  $result=mysqli_query($link, "select outbox.area,outbox.fromname,outbox.toname,outbox.fromaddr,outbox.toaddr,outbox.subject,outbox.date,outbox.hash, groups.name as grp from `outbox` left join `area_groups` on (area_groups.area=outbox.area) left join `groups` on (area_groups.group=groups.id) where outbox.sent='0' and outbox.aprove='0'");

  if (mysqli_num_rows($result)) {
    print "<table width=\"100%\" height=\"100%\">
<tr height=20%><td valign=top>
<div name=\"msglist\" id=\"msglist\"  style=\"height: 150px; overflow: auto; border: 0\">
<table width=100%>";
    while ($row=mysqli_fetch_object($result)){
      if (!$hash){
        $hash=$row->hash;
      }
      if ($hash==$row->hash){
        $class="selected";
        $element_id=" name=\"selected\", id=\"selected\" ";
      } else {
        $class="msglist";
        $element_id="";
      }
      print "
<tr onClick=\"document.location='?mode=moder&message=$row->hash';return false\" style=\"cursor: pointer;\" $element_id>
<td class=\"$class\">".strtoupper($row->area)." ($row->grp)</td>
<td class=\"$class\">$row->fromname</td>
<td class=\"$class\">$row->toname</td>
<td class=\"$class\"><a href=\"?mode=moder&message=$row->hash\">$row->subject</a></td>
<td class=\"$class\">$row->date</td>
</tr>";
    }
    print "
</table></div>
</td></tr>
<tr height=80%><td valign=top>
<div style=\"word-break:break-all; width:100%; height:100%; overflow: auto; background-color: #F5F5F5\">\n
";


    $result=mysqli_query($link, "select * from `outbox` where hash='$hash'");
    if (mysqli_num_rows($result) or $mode=="new") {
      $row = mysqli_fetch_object($result);

      print "
<table width=100% height=100%>
<tr height=1%><td class=\"messagehead\" width=\"33%\">From: $row->fromname ($row->fromaddr)</td>
<td class=\"messagehead\" width=\"33%\">To: $row->toname ($row->toaddr)</td>
<td class=\"messagehead\" width=\"33%\">Date: $row->date</td>
<tr height=1%><td colspan=2 class=\"messagehead\">Subject: $row->subject</td>
<td align=right class=\"messagehead\"><a href='?mode=moder&message=$row->hash&action=approve'>approve</a> <a href='?mode=moder&message=$row->hash&action=drop'>drop</a> <a href='?mode=moder&message=$row->hash&action=reject'>reject</a></td>
<tr height=98%><td colspan=3 class=\"message\" valign=top>";
      $text=explode("\n", $row->text);
      print message2html($text);
    

      print "</tr></td></table>";

    } else {
      print "нет такого письма";
    }


    print "</div></td></tr></table>";
  } else {
    print "&nbsp;";
  }
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