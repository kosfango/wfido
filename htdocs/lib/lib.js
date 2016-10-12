    function daemon(){
	check_new_messages.executePeriodically(document.variables.update_interval.value + '000',1440);
	if (document.variables.mode.value=='thread') {
	    scroll_thread('0');
	} else {
	    scroll_msglist();
	}

    }

    function zoomzoom(i){
	if (i.className=="ext-image-zoom") {
	    i.className="ext-image";
	} else {
	    i.className="ext-image-zoom";
	}
    }

    function show_or_hide_arealist() {
	if (document.getElementById('arealist').style.display == 'none'){
	    document.getElementById('arealist').style.height=document.body.offsetHeight-23;
	    document.getElementById('arealist').style.display='block';
            document.getElementById('plankaecho').innerHTML =  document.variables.area.value + '<img src="images/loading.gif" width=16 height=16>';
	    JsHttpRequest.query(
        	'lib/get_areas.php',
        	{
            	    'area': document.variables.area.value,
            	    'mode': document.variables.mode.value
        	},
        	function(result, errors) {
        	    if (result) {
                	document.getElementById('arealist-container').innerHTML =  result['text'];
        		document.getElementById('plankaecho').innerHTML =  document.variables.area.value + '<img src="images/expand.gif" height=16 width=16>';
            	    }
        	},
        	true
    	    );
	} else {
	    document.getElementById('arealist').style.display='none';
	}
    }


    function check_new_messages() {
        JsHttpRequest.query(
            'lib/check_new_messages.php',
            { 
		'area': document.variables.area.value
	    },
            function(result, errors) {
                if (result) {
		    if (result['lastmessage'] > document.variables.lastmessage.value) {
			update_list(document.variables.area.value,document.variables.hash.value,document.variables.mode.value);
		    }

                    document.variables.lastmessage.value =  result['lastmessage'];
                }
            },
            true  // disable caching
        );
    }

    function update_list (area,hash,mode) {
        JsHttpRequest.query(
            'lib/get_messages_list.php',
            { 
		'area': area,
		'hash': hash,
		'mode': mode
	    },
            function(result, errors) {
                if (result) {
            	    document.getElementById("msglist").innerHTML =  result['text'];
                }
            },
            true  // disable caching
        );
	
    }

    function scroll_thread(hash) {
	if (hash==0){
	    hash=document.variables.hash.value;
	}
	var thepoint = document.getElementById(hash).offsetTop;
	document.getElementById('thread').scrollTop = thepoint;
	scroll_msglist();
    }

    function scroll_msglist() {
	var thepoint = document.getElementById("selected").offsetTop;
	document.getElementById('msglist').scrollTop = thepoint - 64;
    }

    function change_visible(hash) {
	if (document.getElementById(hash + '_content').style.display == 'block'){
	    document.getElementById(hash + '_content').style.display = 'none';
	} else {
	    document.getElementById(hash + '_content').style.display = 'block';
	    scroll_thread(hash);
	}
    }


