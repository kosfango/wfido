<?

require ('config.php');
require ('lib/lib.php');

start_timer();

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();
fix_post();
$point=check_session($_COOKIE['SESSION']);

$area=substr($_GET["area"],0,128);
$page=substr($_GET["page"],0,128);
$string=substr($_GET["string"],0,256);


if(!$page) {
  $page=1;
}

$result_on_page=30;

$row = mysqli_fetch_object(mysqli_query($link, "select * from `users` where point='$point'"));
$myaddr=$mynode.".".$row->point;
$myname=$row->name;


print "<html>
<head>
<title>Online FTN reader - настройки</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=koi8-r\" />
<link rel=\"stylesheet\" href=\"css/settings.css\" type=\"text/css\" media=\"all\" />
</head>
<body>
<table border=0 width=100% height=100%>
 <tr height=1>
  <td>";

print planka("search");

print "
  </td>
 </tr>
 <tr>
  <td valign=top align=center>";




print "<form name=search action=search.php mode=get>
<input type=text name=string style='width: 50%' value='".stripslashes($string)."'><input type=submit value='Search'>
<select name=area>\n";

if ($area){
  print "<option value=''>All areas\n";
}else {
  print "<option value='' selected>All areas\n";
}

$result=mysqli_query($link, "select upper(areas.area) as area from `areas` join `subscribe` where subscribe.area=areas.area and subscribe.point='$point' order by areas.area;");
while ($row=mysqli_fetch_object($result)) {
  $selected="";
  if ( strtoupper($area)==$row->area) {
    $selected=" selected";
  }
  print "  <option value='$row->area' $selected> $row->area\n";

}

print "</select>
</form>\n";


if ($string){

//  $query="select SQL_CALC_FOUND_ROWS upper(area) as area,hash,fromname,date,subject,match(text) against ('$string' IN BOOLEAN MODE) as score from messages where match (text) against ('$string' IN BOOLEAN MODE)";
  $query="select SQL_CALC_FOUND_ROWS upper(area) as area,hash,fromname,date,subject,match(text) against ('$string') as score from messages where match (text) against ('$string')";
  if ($area) {
    $query=$query." and area='$area'";
  } else {
    $query=$query. " and area!=''";
  }
  $limit=($page-1)*$result_on_page;
  $query=$query." order by id desc limit $limit,$result_on_page;";
  $result=mysqli_query($link, $query);
  if (mysqli_num_rows($result)){
    $result2=mysqli_query($link, "select found_rows() as num;");
    $row2=mysqli_fetch_object($result2);
    print "Найдено $row2->num результатов<br>\n";
    $pages=(integer)($row2->num/$result_on_page);
    if($row2->num%$result_on_page){
      $pages++;
    }
    $pages_line="";
    $print_separator=0;
    for ($i=1; $i<=$pages; $i++){
      if ($pages<10 or $i==1 or ($page-4<$i and $i<$page+4) or $pages==$i) {
        if ($print_separator) {
	  $pages_line= $pages_line."...";
	  $print_separator=0;
	}
        if ($i==$page) {$pages_line= $pages_line."<b>";}
        $pages_line= $pages_line."<a href='search.php?string=$string&area=$area&page=$i'>$i</a> ";
        if ($i==$page) {$pages_line= $pages_line. "</b>";}
      } else {
        $print_separator=1;
      }
    }
    print "$pages_line<br>\n<div style='width: 100%; text-align: left;'>\n";
    while ($row=mysqli_fetch_object($result)){
      if (!$row->subject){
        $row->subject="(no subject)";
      }
      print "$row->fromname: <a href='index.php?area=$row->area&message=$row->hash'>$row->subject</a><br>\n$row->date, $row->area<br><br>\n";
    }
    print "</div><br>\n$pages_line";

  } else {
      print "К сожалению, поиск не дал результатов. Имейте в виду, что слова из 3х и менее букв игнорируются при поиске.";
  }
}

print "  </td>
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


function print_filtred_message($text,$string){
// вот тут надо подумать над тем, как выдавать превью найденных писем.
return "test";
}


?>