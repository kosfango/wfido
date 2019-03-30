<?
require ('../config.php');
require ('lib.php');
require_once ('JsHttpRequest.php');
// Init JsHttpRequest and specify the encoding. It's important!
$JsHttpRequest = new JsHttpRequest("koi8-r");
// Fetch request parameters.
$hash = $_REQUEST['hash'];
$GLOBALS['_RESULT'] = array(
      "hash"   => $hash,
      "md5"   => md5($hash)
);
// Everything we print will go to 'errors' parameter.
?>
<?php
print_r($GLOBALS['_RESULT']);
// This includes a PHP fatal error! It will go to the debug stream,
// frontend may intercept this and act a reaction.
if ($_REQUEST['str'] == 'error') {
  error_demonstration__make_a_mistake_calling_undefined_function();
}

?>