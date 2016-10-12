<? header("Content-type: text/html; charset=koi8-r");

print "<html>
<head>
<title>Ссылка</title>
</head>
<body>
<table width=100%  height=100% valign=center>
 <tr>
   <td align=center>
Внимание! Указанная ниже ссылка не является частью данного сайта. Пройдя по ней, вы попадете на бескрайние просторы интернета,
где с вами может случиться что угодно. Вы готовы к этому? Если да, то смело тыкайте на ссылку.<br>
<a href=\"".$_SERVER['QUERY_STRING']."\">".$_SERVER['QUERY_STRING']."</a>
  </td>
</tr>
</form>
</table>
</body>
</html>";

