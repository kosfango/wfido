<?

require ('config.php');
require ('lib/lib.php');
require ('lib/sphinxapi.php');
start_timer();

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
fix_magic_quotes_gpc();
fix_post();
$point=check_session($_COOKIE['SESSION'] ?? '');

$area=substr($_GET["area"] ?? "",0,128);
$page=substr($_GET["page"] ?? "",0,128);
$string=substr($_GET["string"] ?? "",0,256);
$sort=substr($_GET["sort"] ?? "",0,32);
if ($sort!="relevance" and $sort!="mixed") {
  $sort="newest";
}


if(!$page) {
  $page=1;
}

$result_on_page=30;

$query = mysqli_query($link, "select * from `users` where point='$point'");
$row = mysqli_fetch_object($query);
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

$result=mysqli_query($link, "select upper(areas.area) as area from `areas` join `subscribe` where subscribe.area=areas.area and subscribe.point='$point' order by areas.area");
while ($row=mysqli_fetch_object($result)) {
  $selected="";
  if ( strtoupper($area)==$row->area) {
    $selected=" selected";
  }
  print "  <option value='$row->area' $selected> $row->area\n";

}

print "</select>
<select name=sort>\n";

if ($sort=="relevance"){
  print "<option value='newest'>Newest first\n";
  print "<option value='relevance' selected>Best match\n";
  print "<option value='mixed'>Best match + newest\n";
} elseif ($sort=="mixed") {
  print "<option value='newest'>Newest first\n";
  print "<option value='relevance'>Best match\n";
  print "<option value='mixed' selected>Best match + newest\n";
} else {
  print "<option value='newest' selected>Newest first\n";
  print "<option value='relevance'>Best match\n";
  print "<option value='mixed'>Best match + newest\n";
}

print "</select>
</form>\n";


if ($string){

  $search=new SphinxClient();
  $search->SetServer( $sphinx_host, $sphinx_port );
  $search->SetFieldWeights(array('subject' => 20, 'text' => 5));
  if ($sort=="relevance") {
    $search->SetSortMode( SPH_SORT_RELEVANCE );
  } elseif ($sort=="mixed") {
    $search->SetSortMode( SPH_SORT_RELEVANCE );
  } else {
    $search->SetSortMode( SPH_SORT_ATTR_DESC, 'msg' );
  }
  if ($area) {
    $area32=sprintf('%u', crc32(strtoupper($area)));
    $search->SetFilter('area32',array((int)$area32));
  }
  $offset=($page-1)*$result_on_page;
  $max_matches=max($offset+$result_on_page, 1000);
  $search->SetLimits($offset,$result_on_page,$max_matches);

  $search_string=build_sphinx_search_string($string);
  $result = $search->Query( mb_convert_encoding($search_string, 'UTF-8', 'KOI8-R'), "messages delta");
  if ( !empty($result["matches"]) ) { 
    print "Найдено ".$result['total_found']." совпадений<br>\n";
    $pages=(integer)($result['total_found']/$result_on_page);
    if($result['total_found']%$result_on_page){
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
        $pages_line= $pages_line."<a href='search.php?string=$string&area=$area&sort=$sort&page=$i'>$i</a> ";
        if ($i==$page) {$pages_line= $pages_line. "</b>";}
      } else {
        $print_separator=1;
      }
    }
    print "$pages_line<br>\n<div style='width: 100%; text-align: left;'>\n";
    $ids=array_keys($result["matches"]);
    $rows=get_info_by_ids($ids);
    foreach ($ids as $id) {
      if (empty($rows[$id])) {
        continue;
      }
      $row=$rows[$id];
      if (!$row->subject){
        $row->subject="(no subject)";
      }
      print "$row->fromname: <a href='index.php?area=$row->area&message=$row->hash'>$row->subject</a><br>\n$row->date, $row->area<br><br>\n";
    }
    print "</div><br>\n$pages_line";

  } else {
      print "К сожалению, ничего не найдено. Имейте в виду, что слова по 3м и менее буквам не индексируются для поиска.";
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


function build_sphinx_search_string($string){
$words= preg_split("/[[:space:]]+/", trim($string));
if (!is_array($words)) {
  $words=array(trim($string));
}
$search_string="@(subject,text) ";
foreach ($words as $word){
  if (!$word){
    continue;
  }
  $search_string .= " $word ";
}
return $search_string;
}

function get_info_by_ids($ids){
global $link;
  if (!$ids){
    return array();
  }
  $ids=array_map('intval',$ids);
  $ids=array_filter($ids);
  if (!$ids){
    return array();
  }
  $result=mysqli_query($link, "select id,date,hash,fromname,area,subject from `messages` where id in (".implode(",", $ids).")");
  $rows=array();
  while ($row=mysqli_fetch_object($result)){
    $rows[$row->id]=$row;
  }
  return $rows;
}

function print_filtred_message($text,$string){
// preview helper placeholder
return "test";
}


?>