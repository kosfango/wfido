<?

header("Content-type: text/html; charset=koi8-r");

require ('config.php');
require ('lib/lib.php');

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();

$point=$_GET["point"];
$key=$_GET["key"];

print "<html>
<head>
<title>Активация</title>
</head>
<body>
<table width=100%  height=100% valign=center>
 <tr>
  <td align=center>
    <table border=0>";


if ($point and $key){
  $result=mysql_query("select name from `users` where confirm='$key' and point='$point';");
  if (mysql_num_rows($result)){
    $row = mysql_fetch_object($result);
    mysql_query("update `users` set active='1' where confirm='$key' and point='$point';");
    print "
     <tr><td>".$row->name.", учетная запись активирована<br></td></tr>
     <tr><td align=right><a href='$mywww'>Войти</a></td></tr>
";
  } else {
  print "<form method=get action='$mywww/activation.php'>
     <tr><td align=center>$mynode.</td><td><input type=text name=point></td></tr>
     <tr><td align=center>Ключ</td><td><input type=text name=key></td></tr>
     <tr><td colspan=2 align=right align=center><input type=submit value='Активировать'</a></td></tr>
     <tr><td colspan=2 align=center><font color=red>Ключ не верен.<font></td></tr>
";

  }


} else {
  print "<form method=get action='$mywww/activation.php'>
     <tr><td align=center>$mynode.</td><td><input type=text name=point></td></tr>
     <tr><td align=center>Ключ</td><td><input type=text name=key></td></tr>
     <tr><td colspan=2 align=right align=center><input type=submit value='Активировать'</a></td></tr>";
}

print "
    </table>
   </td>
 </tr>
</form>
</table>
</body>
</html>
";

?>