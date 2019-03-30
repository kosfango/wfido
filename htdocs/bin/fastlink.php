#!/usr/bin/php

<?
if (isset($_SERVER["REMOTE_ADDR"])) {
  print "This script must be run from command line\n";
  exit;
}

require (dirname($_SERVER["SCRIPT_FILENAME"]).'/../config.php');
require (dirname($_SERVER["SCRIPT_FILENAME"]).'/../lib/lib.php');

if (file_exists($linker_lock_file)) {
  print "Lock file exist! Another linker is running?\n";
  exit;
}

touch($linker_lock_file);

connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);

// создаем временную таблицу для сообщений
mysqli_query($link, "create temporary table `tmp` (
   `id` bigint(64) NOT NULL,
   `msgid` varchar(128) NULL,
   `reply` varchar(128) NULL,
   `recieved` datetime NULL,
   `area` text NULL,
   `thread` varchar(128) NULL,
   `level` bigint(64) NOT NULL,
   `inthread` bigint(64) NOT NULL,
   `fromname` varchar(128) NULL,
   `fromaddr` varchar(128) NULL,
   `hash` varchar(128) NULL,
   `date` varchar(128) NULL,
   `subject` text NULL,
    KEY `recieved_key` (`recieved`),
    KEY `thread_recieved_key` (`thread`,`recieved`),
    KEY `thread_key` (`thread`),
    KEY `reply_key` (`reply`),
    KEY `reply_recieved_key` (`reply`,`recieved`),
    KEY `msgid_key` (`msgid`)
) ENGINE=InnoDB CHARSET=utf8");



if (isset($argv['1'])) {
  $area=$argv['1'];
} else {
  $area="";
}

if ($area) { 
  $query=("select upper(area) as area from `messages` where thread='' and area='$area' group by area;");
} else { 
  $query=("select upper(area) as area from `messages` where thread='' and area!='' group by area order by area;");
}
$result=mysqli_query($link, $query);
while ($row=mysqli_fetch_object($result)) {
  $strict = "SET sql_mode = ''";
  mysqli_query($link, $strict);
  // на всякий случай очищаем временную таблицу
  mysqli_query($link, "delete from `tmp`;");
  // заполняем временную таблицу письмами из линкуемой эхи
  mysqli_query($link, "insert into `tmp` (id, msgid, reply, recieved,area,thread,level,inthread,fromname,fromaddr,hash,date,subject) 
                           select id, msgid, reply, recieved,area,thread,level,inthread,fromname,fromaddr,hash,date,subject
                            from `messages` where area=\"$row->area\";");

  print $row->area." ";
  $area_end=0;
  while ($area_end==0){ //отлов тредов. каждое новое письмо попадает как минимум в один тред (а может быть и связующим звеном двух тредов. тогда их придется слить в один)
    $result2=mysqli_query($link, "select msgid,reply,subject,fromname,fromaddr,date,recieved,hash from `tmp` where thread='' order by recieved limit 1;");
    if (mysqli_num_rows($result2)){
      $row2=mysqli_fetch_object($result2);
      $thread_info = array ( //заполняем инфо о треде информацией о найденном сообщении
	'area'			=> $row->area,
	'thread'		=> $row2->msgid,
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
      if ($row2->reply==0) { //поле reply пустое. следовательно, сообщние первое в треде
        if(!mysqli_num_rows(mysqli_query($link, "select msgid from `tmp` where reply='$row2->msgid';"))){ // первое и единственное в треде.
          $thread_info=new_thread($thread_info); // создаем тред с всего одним письмом
	  print("n");
        } else { //сообщение первое, но не единственное в треде. линкуем цепочку
          $thread_info=set_thread($thread_info); // устанавливаем цепочку тредов, попутно корректируя инфо о треде
	  print("N");
        }
      }else{ //сообщение не первое в треде
	$result3=mysqli_query($link, "select thread,msgid,level,inthread,subject from `tmp` where msgid='$row2->reply';"); //ищем собщение, на которое оно отвечает
        if(!mysqli_num_rows($result3)){ // ничего не найдено. следовательно, пока оно первое.
          if(!mysqli_num_rows(mysqli_query($link, "select msgid from `tmp` where reply='$row2->msgid';"))){//и единственное в треде.
            $thread_info=new_thread($thread_info); // создаем тред с всего одним письмом
	    print("t");
          } else { //пока первое, но не единственное в треде
            $thread_info=set_thread($thread_info); // устанавливаем цепочку тредов, попутно корректируя инфо о треде
	    print("T");
          }
        } else { //у нас есть письмо, на которое отвечает это сообщение.
	  $row3=mysqli_fetch_object($result3);
          if ($row3->thread) { // сообщение пришло в уже существующий тред.
            if (mysqli_num_rows(mysqli_query($link, "select msgid from `tmp` where reply='$row2->msgid';"))){ //сообщение не последнее в будущем треде.
              //медленная линковка:
              $thread_info['thread']=find_begin($row2->msgid); // находим начало треда
              $thread_info=set_thread($thread_info); // устанавливаем цепочку тредов, попутно корректируя инфо о треде
  	      print("s");
//!вот тут можно продумать вариант, когда нам падает сразу два и более сообщений из одного треда.
//!надо где-то в памяти держать массив новых сообщений, по которому рекурсивно пробегаться и находить начало-конец.
//!потом можно будет линковать цепочками.

            } else { // сообщение последнее в треде, на него никто не отвечает. просто добавляем в тред.
//!здесь надо иметь в виду, что могут быть два новых письма, являющихся ответом на одно и то же письмо из треда.
//!в таком случае можно будет еще немного оптимизировать.
              if (match_text($thread_info['subject'],$row3->subject)){ //если сабж существенно не менялся
                $thread_info['thread']=$row3->thread;
	        $thread_info=add_to_thread($thread_info,$row2->msgid,$row2->reply,$row3->level,$row3->inthread);
	      } else { //если сабж сильно поменяли, то это новый тред
        	$thread_info=new_thread($thread_info); // создаем тред с всего одним письмом
	      }
  	      print("f");
            }
          } else { //сообщение пришло в неотлинкованный тред
//!аналогично, неотлинковано может быть только сообщение уровнем выше. тогда не имеет смысла перелинковывать всё, достаточно
//!только построить цепочки из пришедщих сообщений и вписать их в тред
            $thread_info['thread']=find_begin($row2->msgid); // находим начало треда
            $thread_info=set_thread($thread_info); // устанавливаем цепочку тредов, попутно корректируя инфо о треде
	    print("S");
          }
        }
      }
    } else {
      $area_end=1;
    } 
    // сливаем инфо о треде в таблицу threads
    save_thread($thread_info);
  }
//с эхой закончили
  print "\n";
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



function new_thread($thread_info){ //создание нового треда из одного письма
  global $link;
  mysqli_query($link, "delete from `tmp` where msgid=\"".$thread_info['thread']."\";");
  mysqli_query($link, "update `messages` set thread=\"".$thread_info['thread']."\", inthread=\"0\", level=\"0\" where msgid=\"".$thread_info['thread']."\" and area=\"".$thread_info['area']."\";");
  $thread_info['num']=1;
  return $thread_info;
}

function add_to_thread($thread_info,$msgid,$reply,$level,$inthread){ //добавления письма к уже существующему треду
  global $link;
  //выясняем level и inthread:
  // получаем список соседних сообщений ( у которых reply такой же).
  $result=mysqli_query($link, "select level, max(inthread) as inthread from `tmp` where reply='$reply' and thread=\"".$thread_info['thread']."\" and area=\"".$thread_info['area']."\" group by reply;");
  if (mysqli_num_rows($result)){ // если соседние сообщения есть
    $row=mysqli_fetch_object($result);
    // level берем от них, inthread - максимальное+1
    $level=$row->level;
    $inthread=$row->inthread+1;
  } else { //если соседних сообщений нет, то есть только родительское
  //берем level и inthread родителя, увеличиваем их на 1.
    $level++;
    $inthread++;
  }
  // "сдвигаем" вниз все сообщения из треда, у которых inthread больше или равен нашему.
  mysqli_query($link, "update `messages` set inthread=inthread+1 where area=\"".$thread_info['area']."\" and thread=\"".$thread_info['thread']."\" and inthread >= \"$inthread\";");
  // выставляем level, inthread, thread для нового сообщения
  mysqli_query($link, "update `messages` set thread=\"".$thread_info['thread']."\", inthread=\"$inthread\", level=\"$level\" where msgid=\"".$msgid."\" and area=\"".$thread_info['area']."\";");
  // подчищаем это новое сообщение из tmp
  mysqli_query($link, "delete from `tmp` where msgid=\"$msgid\";");
  // выясняем, сколько сейчас в треде сообщений
  $query=mysqli_query($link, "select num from `threads` where area=\"".$thread_info['area']."\" and thread=\"".$thread_info['thread']."\"");
  $row=mysqli_fetch_object($query);
  $thread_info['num']=$row->num + 1;
  return $thread_info;
}




function set_thread($thread_info,$msgid=0,$level=0){
  global $link;
  if (!$msgid){ $msgid=$thread_info['thread']; }
  mysqli_query($link, "delete from `tmp` where msgid=\"$msgid\";");
  mysqli_query($link, "update `messages` set thread=\"".$thread_info['thread']."\", inthread=\"".$thread_info['num']."\", level=\"$level\" where msgid=\"$msgid\" and area=\"".$thread_info['area']."\";");
  $thread_info['num']++;
  if ($level) { //иногда случается, что письмо приходит раньше ответа. и в этом случае ответ уже может быть отдельным тредом.
                // так что имеет смысл принудительно убивать треды, образованные от всех писем, у которых level>0
    mysqli_query($link, "delete from thread where area=\"".$thread_info['area']."\" and thread=\"$msgid\";");
  }
  $result=mysqli_query($link, "select msgid,recieved,date,fromaddr,fromname,hash,subject from `tmp` where reply=\"$msgid\" order by recieved;");
  while ($row=mysqli_fetch_object($result)){
    if (match_text($row->subject,$thread_info['subject'])){
      if ($row->recieved > $thread_info['lastupdate']){ 
        $thread_info['lastupdate']=$row->recieved; 
        $thread_info['last_author']=$row->fromname; 
        $thread_info['last_author_address']=$row->fromaddr; 
        $thread_info['last_author_date']=$row->date; 
        $thread_info['last_hash']=$row->hash; 
      }
      $thread_info=set_thread($thread_info,$row->msgid,$level+1);
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
  $str1=preg_replace('/Н/','H',$str1);
  $str2=preg_replace('/Н/','H',$str2);
  $str1=preg_replace('/(:|\^| |[0-9]|\[.*\]|\(no subject\))/','',$str1);
  $str2=preg_replace('/(:|\^| |[0-9]|\[.*\]|\(no subject\))/','',$str2);
  if (levenshtein($str1,$str2, 1,10,1) < 20) {
    return 1;
  }else{
    print "|";
    return 0;
  }
  return 1;
}

function save_thread($thread_info){
    global $link;
    mysqli_query($link, "
	replace into `threads` set 
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
