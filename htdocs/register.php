<?

header("Content-type: text/html; charset=koi8-r");
define('CAPTCHA_COOKIE', 'imgcaptcha_');

require ('config.php');
require ('lib/lib.php');
connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();

$name=$_POST["name"];
$email=$_POST["email"];
$password=$_POST["password"];
$password2=$_POST["password2"];
$captcha=$_POST["captcha"];

if ($name and $email and check_email($email) != 1 and domain_exists($email) and $password and $password2 and $password==$password2 and md5($captcha) == @$_COOKIE[CAPTCHA_COOKIE]){
  $confirm=md5(rand());
  mysqli_query($link, "insert into users set name='$name', email='$email', password='$password', registred=NOW(), confirm='$confirm', active=0");
  $point=mysqli_insert_id($link);
  //подписка
  $result=mysqli_query($link, "select area_groups.area from area_groups join default_subscribe where default_subscribe.group=area_groups.group");
  while($row=mysqli_fetch_object($result)){
    mysqli_query($link, "insert into `subscribe` set `point`='$point', `area`='$row->area'");
  }
  //установка прав
  $result=mysqli_query($link, "select * from `default_perm`");
  while ($row=mysqli_fetch_object($result)){
    mysqli_query($link, "insert into `user_groups` set `point`='$point', `group`='$row->group', `perm`='$row->perm'");
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
$error='<font color="red">Enter all fields!</font>';
if(!empty($_POST['captcha']) and md5($_POST['captcha']) != @$_COOKIE[CAPTCHA_COOKIE]) {
	$error='<font color="red">Wrong captcha code!</font>';
}
if($_POST['password'] != $_POST['password2']) {
	$error='<font color="red">Passwords mismatch!</font>';
}
if(!empty($_POST['email']) and !domain_exists($_POST['email'])) {
	$error='<font color="red">Invalid email!</font>';
}

if(md5($_POST['captcha']) == @$_COOKIE[CAPTCHA_COOKIE] and !empty($_POST['email']) and check_email($_POST['email']) == 1) {
	$error='<font color="red">Email already exists!</font>';
}
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
    <tr><td>Введите код с картинки:</td><td><input name=captcha type=text value='$captcha'> </td><td><img title=\"Щелкните для изменения кода\" alt=\"Captcha\" src=\"jcaptcha.php\" style=\"border: 1px solid black\" onclick=\"this.src='jcaptcha.php?id=' + (+new Date());\"></td></td></tr>
    <tr><td></td><td>$error</td></tr>
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