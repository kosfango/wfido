#!/usr/bin/perl

use DBD::mysql;
use Digest::Perl::MD5 'md5_hex';

#===Config===
$xml_spool="/home/fidonet/var/fidonet/xml";
$sql_base="wfido";
$sql_user="USER";
$sql_pass="PASS";
$sql_host="127.0.0.1";
#===Config===

$dbh = DBI->connect("DBI:mysql:$sql_base;host=$sql_host", $sql_user,$sql_pass );
$sth = $dbh -> prepare ("set names cp866;");
$rv = $sth -> execute;


@files = <$xml_spool/*.xml>;
foreach $file (@files) {
  open (XML, $file);
  $query="insert ignore into `messages` set ";
  $string="";
  $msgid="";
  $reply="";
  while ($string = <XML>){
   $string=~ s/\\/\\\\/g;
   $string=~ s/\'/\\\'/g;
   $string=~ s/\`/\\\`/g;
   if ($string=~ /<fromname>/ and $string=~/<\/fromname>/ ){
     $string=~ s/.*<fromname>//;
     $string=~ s/<\/fromname>.*//g;
     chop($string);
     $query = $query . "fromname='$string'";
   } elsif ($string=~ /<fromaddr>/ and $string=~/<\/fromaddr>/ ){
     $string=~ s/.*<fromaddr>//;
     $string=~ s/<\/fromaddr>.*//g;
     chop($string);
     $query = $query . ", fromaddr='$string'";
   } elsif ($string=~ /<toname>/ and $string=~/<\/toname>/ ){
     $string=~ s/.*<toname>//;
     $string=~ s/<\/toname>.*//g;
     chop($string);
     $query = $query . ", toname='$string'";
   } elsif ($string=~ /<toaddr>/ and $string=~/<\/toaddr>/ ){
     $string=~ s/.*<toaddr>//;
     $string=~ s/<\/toaddr>.*//g;
     chop($string);
     $query = $query . ", toaddr='$string'";
   } elsif ($string=~ /<area>/ and $string=~/<\/area>/ ){
     $string=~ s/.*<area>//;
     $string=~ s/<\/area>.*//g;
     chop($string);
     if ($string eq "NetmailArea") {$string="";}
     $area=$string;
     $query = $query . ", area='$string'";
   } elsif ($string=~ /<subject>/ and $string=~/<\/subject>/ ){
     $string=~ s/.*<subject>//;
     $string=~ s/<\/subject>.*//g;
     chop($string);
     $query = $query . ", subject='$string'";
   } elsif ($string=~ /<text>/ and $string=~/<\/text>/ ){
     $string=~ s/.*<text>//;
     $string=~ s/<\/text>.*//g;
     chop($string);
     $query = $query . ", text='$string'";
     $msgid=$string;
     $msgid=~ s/.*\x0D\x01MSGID\:\ //;
     $msgid=~ s/\x0D.*//;
     if ($string=~ /\x0D\x01REPLY\:\ /) { 
        $reply=$string;
        $reply=~ s/.*\x0D\x01REPLY\:\ //;
        $reply=~ s/\x0D.*//;
     } else {
	$reply=0;
     }
     $query = $query . ", reply='$reply', msgid='$msgid'";
   } elsif ($string=~ /<pktfrom>/ and $string=~/<\/pktfrom>/ ){
     $string=~ s/.*<pktfrom>//;
     $string=~ s/<\/pktfrom>.*//g;
     chop($string);
     $query = $query . ", pktfrom='$string'";
   } elsif ($string=~ /<date>/ and $string=~/<\/date>/ ){
     $string=~ s/.*<date>//;
     $string=~ s/<\/date>.*//g;
     chop($string);
     $query = $query . ", date='$string'";
   } elsif ($string=~ /<attr>/ and $string=~/<\/attr>/ ){
     $string=~ s/.*<attr>//;
     $string=~ s/<\/attr>.*//g;
     chop($string);
     $query = $query . ", attr='$string'";
   } elsif ($string=~ /<secure>/ and $string=~/<\/secure>/ ){
     $string=~ s/.*<secure>//;
     $string=~ s/<\/secure>.*//g;
     chop($string);
     $query = $query . ", secure='$string'";
   } elsif ($string=~ /<recieved>/ and $string=~/<\/recieved>/ ){
     $string=~ s/.*<recieved>//;
     $string=~ s/<\/recieved>.*//g;
     chop($string);
     $recieved=$string;
     $query = $query . ", recieved=from_unixtime('$string')";
   }
  }


  $hash = md5_hex($area.$msgid.$reply.$date);
  $query = $query . ", hash='" . $hash . "';";
  $query =~ s/\x0D/\n/g;
  $query =~ s/\x01/\@/g;
  $sth = $dbh -> prepare ($query);
  $rv = $sth -> execute;


  if ($area and $area ne 'NETMAIL'){
    $sth = $dbh -> prepare ("update `areas` set recieved=from_unixtime('$recieved'), messages=messages+1 where area='$area';");
    $rv = $sth -> execute;
    if ($rv eq "0E0") { #эхи в таблице нет, ничего не обновилось. создаем.
	print "New area: $area\n";
       $dbh->do("replace into `areas` set area='$area', recieved=from_unixtime('$recieved'), messages=1;");
    }
  }
  print "$file - $area - $msgid - $hash\n";
  close (XML);
  `mv $file $xml_spool/archive/`
}
