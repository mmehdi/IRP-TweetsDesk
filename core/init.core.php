<?php
	//Start session:
	session_start();

	//echo 'key: '. $_SESSION['account']['apiKey'];
	//die();
	//Create an exception page array:
//	$exceptions = array('register','login','review-export','tracklist','tracklist-details');
	$exceptions = array('register','login','gmp-test');

	//Find out the current page name:
	$exploded = explode('/', $_SERVER['SCRIPT_NAME']);
	$page = substr(end($exploded),0,-4);


	//Check if the current page is an exception and if a user is already stored in the session:
	if( in_array($page, $exceptions) === false){
		if (isset($_SESSION['account']) === false){
			header('Location: login.php');
			die();
		}
	}
	
	//API URL constant:

	//Application Authorization Key
	define("API_APP_KEY","ik3490jf890l3490kpwf904pk3owe0l");

	//Development mode:
	//define("APIURL","http://139.133.73.55:9090/ecosystem-social-twitter/service");
	define("APIURL","http://dtp-24.sncs.abdn.ac.uk:8080/ecosystem-social-twitter/service");
	
	define("DB_CONNECTION","host=localhost port=5432 dbname=tweetdesk user=postgres password=5L1ght1y");
	define("EXPORT_FOLDER","db_exports/");


	//Deployment mode:
	//define("APIURL","http://localhost:8080/smile-server/api-1.1");

	//Path to file:
	include dirname(__FILE__)."/inc/rest.inc.php";
	include dirname(__FILE__)."/inc/dynamic-form.inc.php";
	include dirname(__FILE__)."/inc/db-connector.inc.php";

	// include dirname(__FILE__)."/inc/user.inc.php";
?>