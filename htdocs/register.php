<?

header("Content-type: text/html; charset=koi8-r");

require ('config.php');
require ('lib/lib.php');

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();

$name=$_POST["name"];
$email=$_POST["email"];
$password=$_POST["password"];
$password2=$_POST["password2"];

if ($name and $email and $password and $password2 and $password==$password2){
  $confirm=md5(rand());
  mysql_query("insert into users set name='$name', email='$email', password='$password', registred=NOW(), confirm='$confirm', active=0");
  $point=mysql_insert_id();
  //подписка
  $result=mysql_query("select area_groups.area from area_groups join default_subscribe where default_subscribe.group=area_groups.group;");
  while($row=mysql_fetch_object($result)){
    mysql_query("insert into `subscribe` set `point`='$point', `area`='$row->area';");
  }
  //установка прав
  $result=mysql_query("select * from `default_perm`;");
  while ($row=mysql_fetch_object($result)){
    mysql_query("insert into `user_groups` set `point`='$point', `group`='$row->group', `perm`='$row->perm';");
  }
  mail ($email, "$mywww: activation", "Hello, $name! 

Congratulations! Your Fidonet address: $mynode.$point

To activate your account on $mywww, please click link below:

$mywww/activation.php?key=$confirm&point=$point


", 'From: '.$adminmail);

  print "<html>
<head>
<title>Регистрация</title>
</head>
<body>
<table width=100%  height=100% valign=center>
 <tr>
  <td align=center>
    <table border=0>
    <tr><td align=center>$name, теперь ваш фидошный адрес $mynode.$point.<br>
    На указанный при регистрации email было отправлено письмо с запросом на подтверждение регистрации.</td></tr>
    <tr><td align=center><a href='$mywww'>Войти</a></td></tr>
    </table>
   </td>
 </tr>
</form>
</table>
</body>
</html>
";

} else {
  print "<html>
<head>
<title>Регистрация</title>
</head>
<body>
<table width=100%  height=100% valign=center>
 <tr>
  <td align=center>
    <form name=register action='register.php' method='post'>
    <table border=0>
    <tr><td>Имя, Фамилия:</td><td><input name=name type=text value='$name'></td></tr>
    <tr><td>Email:</td><td><input name=email type=text value='$email'></td></tr>
    <tr><td>Пароль:</td><td><input name=password type=password value=''></td></tr>
    <tr><td>Пароль (еще раз):</td><td><input name=password2 type=password value=''></td></tr>
    <tr><td colspan=2 align=center><input type=submit value='Зарегистрироваться'></td></tr>
    </table>
    </form>
   </td>
 </tr>
</form>
</table>
</body>
</html>
";
}

?>