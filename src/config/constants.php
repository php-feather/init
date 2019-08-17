<?php
define("ABS_PATH", $_SERVER['DOCUMENT_ROOT']);
define("VIEWS_PATH", $_SERVER['DOCUMENT_ROOT'].'/views');
define("PUBLIC_PATH", $_SERVER['DOCUMENT_ROOT'].'/public');
define('CTRL_NAMESPACE',"Feather\\Init\\Controllers\\");
define('REDIRECT_DATA_KEY' ,'redirect_data');
define('AUTH_USER_KEY' ,'auth_user');
define('CUR_REQ_KEY','cur_req');
define('PREV_REQ_KEY','prev_req');
define('OLD_URI','old_uri');
define('SESSION_LIFETIME',$session_lifetime);