#!/usr/bin/perl

sub filter
{
    my @chars=('a'..'z','A'..'Z','0'..'9','_');
    my $random_string;
    foreach (1..10) {
	$random_string.=$chars[rand @chars];
    }
    $now=time();
    open (XML,">/home/fidonet/var/fidonet/xml/$random_string.xml");
    print (XML "<data>
<fromname>$fromname</fromname>
<fromaddr>$fromaddr</fromaddr>
<toname>$toname</toname>
<toaddr>$toaddr</toaddr>
<area>$area</area>
<subject>$subject</subject>
<text>$text</text>
<pktfrom>$pktfrom</pktfrom>
<date>$date</date>
<attr>$attr</attr>
<secure>$secure</secure>
<recieved>$now</recieved>
</data>");

    close (XML);
    return "";
}

sub scan
{
    my @chars=('a'..'z','A'..'Z','0'..'9','_');
    my $random_string;
    foreach (1..10) {
	$random_string.=$chars[rand @chars];
    }
    $now=time();
    open (XML,">/home/fidonet/var/fidonet/xml/$random_string.xml");
    print (XML "<data>
<fromname>$fromname</fromname>
<fromaddr>$fromaddr</fromaddr>
<toname>$toname</toname>
<toaddr>$toaddr</toaddr>
<area>$area</area>
<subject>$subject</subject>
<text>$text</text>
<pktfrom>$pktfrom</pktfrom>
<date>$date</date>
<attr>$attr</attr>
<secure>$secure</secure>
<recieved>$now</recieved>
</data>");

    close (XML);
    return "";
}

sub route
{
     return "";
}

sub tossbad
{
    return "";
}

sub hpt_exit
{
}

sub process_pkt
{
    return "";
}

sub pkt_done
{
}

sub after_unpack
{
}

sub before_pack
{
}
