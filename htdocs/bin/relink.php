#!/usr/bin/php

<?
if ( isset($_SERVER["REMOTE_ADDR"])) {
if ($_SERVER["REMOTE_ADDR"]) {
  print "This script must be run from command line\n";
  exit;
}
}
require (dirname($_SERVER["SCRIPT_FILENAME"]).'/../config.php');
require (dirname($_SERVER["SCRIPT_FILENAME"]).'/../lib/lib.php');

if (file_exists($linker_lock_file)) {
  print "Lock file exist! Another linker is running?\n";
  exit;
}

touch($linker_lock_file);

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);

// ������� ��������� ������� ��� ���������
mysqli_query($link, "create temporary table `tmp` (
   `id` bigint(64) NOT NULL,
   `msgid` varchar(128) NULL,
   `reply` varchar(128) NULL,
   `recieved` datetime NULL,
   `area` text NULL,
   `thread` text NULL,
   `level` bigint(64) NOT NULL,
   `inthread` bigint(64) NOT NULL,
   `fromname` varchar(128) NULL,
   `fromaddr` varchar(128) NULL,
   `hash` varchar(128) NULL,
   `date` varchar(128) NULL,
   `subject` text NULL
) ENGINE=InnoDB CHARSET=utf8");

// ������� ��������� ������� ��� ������
  mysqli_query($link, "CREATE temporary TABLE `tmp_threads` (
  `area` varchar(128) NOT NULL default '',
  `thread` varchar(128) NOT NULL default '',
  `hash` varchar(128) NOT NULL default '',
  `subject` text NOT NULL,
  `author` varchar(128) NOT NULL default '',
  `author_address` varchar(128) NOT NULL default '',
  `author_date` varchar(128) NOT NULL default '',
  `last_author` varchar(128) NOT NULL default '',
  `last_author_address` varchar(128) NOT NULL default '',
  `last_author_date` varchar(128) NOT NULL default '',
  `num` bigint(20) NOT NULL default '0',
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `area_2` (`area`,`thread`),
  KEY `area` (`area`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

if ($argv['1']) {
  $area=$argv['1'];
} else {
  $area="";
}

if ($area) { 
  $query=("select upper(area) as area from `areas` where area=\"$area\";");
} else {
  $query=("select upper(area) as area from `areas` where area!='' order by area;");
  echo "match";
}
$result=mysqli_query($link, $query);
while ($row=mysqli_fetch_object($result)) {
  $strict = "SET sql_mode = ''";
  mysqli_query($link, $strict);

  // �� ������ ������ ������� ��������� �������
  mysqli_query($link, "delete from `tmp`;");
  // ��������� ��������� ������� �������� �� ��������� ���
  mysqli_query($link, "insert into `tmp` (id, msgid, reply, recieved,area,thread,level,inthread,fromname,fromaddr,hash,date,subject) 
                           select id, msgid, reply, recieved,area,thread,level,inthread,fromname,fromaddr,hash,date,subject
                            from `messages` where area=\"$row->area\";");

  print $row->area ."\n";
  $area_end=0;
  while ($area_end==0){
    $result2=mysqli_query($link, "select msgid,reply,subject,fromname,fromaddr,date,recieved,hash from `tmp` order by recieved limit 1;");
    if (mysqli_num_rows($result2)){
      $row2=mysqli_fetch_object($result2);
      $thread_info = array ( 
	'area'			=> $row->area,
	'thread'		=> find_begin($row2->msgid),
	'subject'		=> $row2->subject,
	'author'		=> $row2->fromname,
	'author_address'	=> $row2->fromaddr,
	'author_date'		=> $row2->date,
	'last_author'		=> $row2->fromname,
	'last_author_address'	=> $row2->fromaddr,
	'last_author_date'	=> $row2->date,
	'num'			=> 0,
	'lastupdate'		=> $row2->recieved,
	'last_hash'		=> $row2->hash
      );
//      print_r ($thread_info);
      $thread_info=set_thread($thread_info);
//
//      var_dump($row2->recieved);
//      print_r ($thread_info);
    } else {
      $area_end=1;
    }
    save_thread($thread_info);
  }
  mysqli_query($link, "delete from `threads` where area=\"$row->area\";");
  mysqli_query($link, "insert into `threads` select * from `tmp_threads`;");
  mysqli_query($link, "delete from `tmp_threads` where area=\"$row->area\"';");


}

unlink($linker_lock_file);

function find_begin($msgid){
  global $link;
  $result=mysqli_query($link, "select reply from `tmp` where msgid=\"$msgid\"");
  if(mysqli_num_rows($result)){
    $row=mysqli_fetch_object($result);
    if ($row->reply) {
      $return=find_begin($row->reply);
      if (!$return) {
	$return=$msgid;
      }
    } else {
      $return=$msgid;
    }
  } else {
    $return=0;
  }
  return $return;
}

function set_thread($thread_info,$msgid=0,$level=0){
  global $link;
//  var_dump($thread_info['lastupdate']);
  if (!$msgid){ $msgid=$thread_info['thread']; }
  mysqli_query($link, "delete from `tmp` where msgid=\"$msgid\";");
  mysqli_query($link, "update `messages` set thread=\"".$thread_info['thread']."\", inthread=\"".$thread_info['num']."\", level=\"$level\" where msgid=\"$msgid\" and area=\"".$thread_info['area']."\";");
  $thread_info['num']++;
  $result=mysqli_query($link, "select msgid,recieved,date,fromaddr,fromname,hash,subject from `tmp` where reply=\"$msgid\" order by recieved;");
  while ($row=mysqli_fetch_object($result)){
//var_dump($row->hash);
    if (match_text($row->subject,$thread_info['subject'])){
      if ($row->recieved > $thread_info['lastupdate']){ 
        $thread_info['lastupdate']=$row->recieved; 
        $thread_info['last_author']=$row->fromname; 
        $thread_info['last_author_address']=$row->fromaddr; 
        $thread_info['last_author_date']=$row->date; 
        $thread_info['last_hash']=$row->hash; 
      }
      var_dump($row->recieved);
      $tread_info=set_thread($thread_info,$row->msgid,$level+1);
    }else{
      $new_thread_info = array ( 
	'area'			=> $thread_info['area'],
	'thread'		=> $row->msgid,
	'subject'		=> $row->subject,
	'author'		=> $row->fromname,
	'author_address'	=> $row->fromaddr,
	'author_date'		=> $row->date,
	'last_author'		=> $row->fromname,
	'last_author_address'	=> $row->fromaddr,
	'last_author_date'	=> $row->date,
	'num'			=> 0,
	'lastupdate'		=> $row->recieved,
	'last_hash'		=> $row->hash
	);
      $new_thread_info=set_thread($new_thread_info);
      save_thread($new_thread_info);
    }
  }
  return $thread_info;
}

function match_text($str1,$str2){
  $str1=preg_replace('/^Re/','',$str1);
  $str2=preg_replace('/^Re/','',$str2);
  $str1=preg_replace('/�/','H',$str1);
  $str2=preg_replace('/�/','H',$str2);
  $str1=preg_replace('/(:|\^| |[0-9]|\[.*\]|\(no subject\))/','',$str1);
  $str2=preg_replace('/(:|\^| |[0-9]|\[.*\]|\(no subject\))/','',$str2);
  if (levenshtein($str1,$str2, 1,10,1) < 20) {
    return 1;
  }else{
    print "|\n";
    return 0;
  }
}

function save_thread($thread_info){
    global $link;
    mysqli_query($link, "
	insert into `tmp_threads` set 
	  area=\"".$thread_info['area']."\", 
          thread=\"".$thread_info['thread']."\", 
          hash=\"".$thread_info['last_hash']."\", 
	  subject=\"".$thread_info['subject']."\",
	  author=\"".$thread_info['author']."\", 
	  author_address=\"".$thread_info['author_address']."\", 
          author_date=\"".$thread_info['author_date']."\", 
	  last_author=\"".$thread_info['last_author']."\",
	  last_author_date=\"".$thread_info['last_author_date']."\",
	  num=\"".$thread_info['num']."\", 
	  lastupdate=\"".$thread_info['lastupdate']."\";
    ");

}

?>