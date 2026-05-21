    function daemon(){
    if (typeof document.variables === 'undefined') {
        return;
    }

    check_new_messages.executePeriodically(
        document.variables.update_interval.value + '000',
        1440
    );

    if (document.variables.mode.value == 'thread') {
        scroll_thread(0);
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
    if (hash == 0) {
        if (document.variables && document.variables.hash) {
            hash = document.variables.hash.value;
        } else {
            return;
        }
    }

    var el = document.getElementById(hash);
    var box = document.getElementById('thread');

    if (!el || !box) {
        return;
    }

    box.scrollTop = el.offsetTop;
    scroll_msglist();
    }

    function scroll_msglist() {
        var selected = document.getElementById("selected");
        var msglist  = document.getElementById("msglist");
        if (!selected || !msglist) {
          return;
        }
        msglist.scrollTop = selected.offsetTop - 64;
        
	//var thepoint = document.getElementById("selected").offsetTop;
	//document.getElementById('msglist').scrollTop = thepoint - 64;
    }

    function change_visible(hash) {
        document.getElementById(hash + '_content')
    }



function message_editor_guard_collect_values(fields) {
    var values = [];
    for (var i = 0; i < fields.length; i++) {
        values.push(fields[i].value);
    }
    return values;
}

function message_editor_guard_is_dirty(editor) {
    var values = message_editor_guard_collect_values(editor.fields);
    for (var i = 0; i < values.length; i++) {
        if (values[i] != editor.initial[i]) {
            return true;
        }
    }
    return false;
}

function setup_message_editor_guard() {
    var forms = document.getElementsByTagName('form');
    var editors = [];
    for (var i = 0; i < forms.length; i++) {
        var fields = forms[i].querySelectorAll('textarea[name=text], input[name=subject], input[name=toname], input[name=toaddr], select[name=selarea]');
        if (!fields.length) {
            continue;
        }

        editors.push({
            form: forms[i],
            fields: fields,
            initial: message_editor_guard_collect_values(fields)
        });

        forms[i].addEventListener('submit', function() {
            this.setAttribute('data-submitting', '1');
        });
    }

    if (!editors.length) {
        return;
    }


    window.addEventListener('beforeunload', function(event) {
        for (var i = 0; i < editors.length; i++) {
            if (editors[i].form.getAttribute('data-submitting') == '1') {
                continue;
            }
            if (message_editor_guard_is_dirty(editors[i])) {
                event.preventDefault();
                event.returnValue = '';
                return '';
            }
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup_message_editor_guard);
} else {
    setup_message_editor_guard();
}
