#!/usr/bin/perl
use FTN::Pkt;
use DBD::mysql;


#===Config===
$inbound='/home/fidonet/var/fidonet/inbound/';
$mynode='2:5020/1519';
$my_tech_link='2:5020/1519';
$my_tech_link_password='';

$sql_base="wfido";
$sql_user="USER";
$sql_pass="PASS";
$sql_host="127.0.0.1";
#===Config===

$dbh = DBI->connect("DBI:mysql:$sql_base;host=$sql_host", $sql_user,$sql_pass );
$sth = $dbh -> prepare ("set names cp866;");
$sth -> execute;

$sth = $dbh->prepare("SELECT id, fromname, toname, subject, text, fromaddr, toaddr, origin, area, reply, unix_timestamp(date) as timeshtamp FROM outbox where sent='0' and approve='1';");
$sth->execute();
while (my $ref = $sth->fetchrow_hashref()) {
    print "$ref->{'fromname'} ($ref->{'fromaddr'}) - $ref->{'area'}\n";

    # меняем переводы строк dos на unix
    $text=$ref->{'text'};
    $text=~ s/\x0D\x0A/\n/g;
    $text=~ s/\&\#8212\;/\-/g;
    $text=~ s/\&\#171\;/\"/g;
    $text=~ s/\&\#187\;/\"/g;

    my $pkt = new FTN::Pkt (
    	fromaddr => $my_tech_link,
    	toaddr   => $mynode,
    	password => $my_tech_link_password,
    	inbound  => $inbound
    );

    if ($ref->{'area'} eq "NETMAIL") {

	my $msg = new FTN::Msg(
    	    fromname => $ref->{'fromname'},
    	    toname   => $ref->{'toname'},
    	    subj     => $ref->{'subject'},
    	    text     => $text,
    	    fromaddr => $ref->{'fromaddr'},
    	    toaddr   => $ref->{'toaddr'},
    	    origin   => $ref->{'origin'},
    	    tearline => 'wfido',
    	    area     => "",
    	    reply    => $ref->{'reply'},
    	    date     => $ref->{'timeshtamp'},
    	    pid      => 'wfido 0.2',
    	    tid      => 1
	);
	$pkt->add_msg($msg);    
    } else {

	my $msg = new FTN::Msg(
    	    fromname => $ref->{'fromname'},
    	    toname   => $ref->{'toname'},
    	    subj     => $ref->{'subject'},
    	    text     => $text,
    	    fromaddr => $ref->{'fromaddr'},
    	    origin   => $ref->{'origin'},
    	    tearline => 'wfido',
    	    area     => $ref->{'area'},
    	    reply    => $ref->{'reply'},
    	    date     => $ref->{'timeshtamp'},
    	    pid      => 'wfido 0.2',
    	    tid      => 1
	);
	$pkt->add_msg($msg);    
    }
    $pkt->write_pkt();
    $sth2 = $dbh->prepare("update `outbox` set sent=1 where id=\"".$ref->{'id'}."\";");
    $sth2->execute();

    $sth2->finish();
}
$sth->finish();
