<?php include('../core/init.core.php');?>
<?php
header("Content-Type: application/json");

$from = $_GET['from'];
$to = $_GET['to'];
$frequency = $_GET['frequency'];
$firstCall = $_GET['firstCall'];

$time_field = 'created_at';

if($firstCall=="true")//if first call
	$where = '';

else if($from!="" || $to!="") {
       //begin Where
      $where ='WHERE (';
      //from date
      if(trim($from)!=""){
        $from = "'".$from."'";
        //create where clause
        $where = $where.$time_field.' >= '.$from;
      }
      //to date
      if(trim($to)!=""){
        //if from date set, create AND clause
        if(trim($from)!="")
             $where = $where . ' AND ';

        //add commas to string
        $to = "'".$to."'";
        //create where clause
        $where = $where.$time_field.' <= '.$to;
      }
      //close Where
        $where = $where.' )';
}

else if ($from==="" && $to===""){ //if frequency
		$from = date('Y-m-d H:i:s',strtotime('-1 '.$frequency)-3600);
		$to = date('Y-m-d H:i:s',time()-3600);
		//$from = date('Y-m-d H:i:s',strtotime('-1 '.$frequency));
		//$to = date('Y-m-d H:i:s',time());
		$where = "WHERE ".$time_field.">="."'".$from."'"." AND ".$time_field."<="."'".$to."'";	
}

	

$select = "SELECT text,".$time_field." FROM tweet ".$where." ORDER BY ".$time_field." desc";

//$select = "SELECT text,time_stamp,author,original_tweet_id from tweet WHERE time_stamp>= '2014-02-01 13:56:43' ORDER BY time_stamp LIMIT 10000";
	//$db_results = db_fetch($tweets_query);

//echo $select;
//die();

$db_results = db_fetch($select);
//$db_results = array_reverse($db_results);
//echo $db_results;

//60=1minute
//60*60=1hour (3600)
//60*60*24=1day (86400)
//60*60*24*10=10days (864000)

$last_row = pg_fetch_array($db_results, 0);
$first_row = pg_fetch_array($db_results, (pg_num_rows($db_results)-1));


//echo $db_array;

$begin = strtotime($first_row[$time_field]);
$end = strtotime($last_row[$time_field]);

$seconds = ($end-$begin);
$hours = ($end-$begin)/3600;
$days = ($end-$begin)/86400;

/*echo '<br/>first row date: '.($timestamps[0]);
echo '<br/>last row date: '.($timestamps[count($timestamps)-1]);
echo '<br/>time difference in seconds : '.$seconds;
echo '<br/>time difference in hours : '.$hours;
echo '<br/>time difference in days : '.$days;
*/

$interval = $seconds/23; //graph scale
$loop_time = $begin; //initiale with begining timestamp
$intervals=array();

//$loop_time = $loop_time+$interval;

while(($loop_time)<=$end && pg_num_rows($db_results)>0){
	//$intervals[]=date('Y-m-d H:i:s',$loop_time);

	//echo '<br/>time: '.	date('Y-m-d H:i:s',$loop_time);
	//echo '<br/>time: '.	gmdate('Y-m-d H:i:s',);

	//$hashtag_query = "SELECT text from tweet WHERE ".$time_field.">="."'".date('Y-m-d H:i:s',$loop_time-$interval)."'"." AND ".$time_field."<="."'".date('Y-m-d H:i:s',$loop_time)."'";
	$hashtag_array=array();	
	//concat query SELECT concat(LOWER(hashtag.text), ' (', count(hashtag.text) ,')') as hashtag from hashtag, tweet where hashtag.tweet_fk=tweet.id AND tweet.created_at >= '2014-06-10 00:00:00' AND tweet.created_at >= '2014-06-11 00:00:00' GROUP BY LOWER(hashtag.text) ORDER BY count(hashtag.text) desc
	$hashtag_query = "SELECT ('#'||LOWER(hashtag.text)|| ' ('|| count(hashtag.text) ||')') as hashtag from hashtag, tweet WHERE hashtag.tweet_fk=tweet.id AND tweet.created_at >= "."'".date('Y-m-d H:i:s',$loop_time)."'"." AND tweet.created_at < "."'".date('Y-m-d H:i:s',$loop_time+$interval)."'"." GROUP BY LOWER(hashtag.text) ORDER BY count(hashtag.text) DESC";
	$tweet_count_query = "SELECT count(id) from tweet WHERE created_at >= "."'".date('Y-m-d H:i:s',$loop_time)."'"." AND created_at < "."'".date('Y-m-d H:i:s',$loop_time+$interval)."'";

	$db_results = db_fetch($hashtag_query);
	

	for($ri = 0; $ri < pg_num_rows($db_results); $ri++) {
		if($ri>4)//get top 5 hashtags
			break;

		$row = pg_fetch_array($db_results, $ri);
    	$hashtag=$row['hashtag'];
		$hashtag_array[] = $hashtag;
	}

	$db_results = db_fetch($tweet_count_query);
	$tweets_count=pg_fetch_result($db_results,0);
	//print_r($hashtag_array);
	//$author_count=pg_num_rows($db_results);
	if(count($hashtag_array)>0)
		$hashtags = implode("<br/>",$hashtag_array);
	else
		$hashtags = 'No hashtags found.';
	//$author_count=sql_count($db_array,date('Y-m-d H:i:s',$loop_time-$interval),date('Y-m-d H:i:s',$loop_time),'author',true);

	$hover = '<div class="morris-hover-row-label">'.(string)date("Y-m-d H:i:s",$loop_time).'</div><div class="morris-hover-point" style="color: #0b62a4">Tweets: '.$tweets_count.'</div><div class="morris-hover-point" style="font-color:black; color: #7A92A3">Hashtags: </div>'.$hashtags.'';

	$intervals[] = array("timestamp"=>(string)date('Y-m-d H:i:s',$loop_time),
						"tweets"=>$tweets_count,
						"hover"=>$hover);

	$loop_time = $loop_time+$interval;


}
http_response_code(200);
echo json_encode($intervals);


function calculateScale($seconds){

$minute = 60;
$hour = $minute*60;
$day = $hour*24;
$week = $day*7;
$month = $week * 4;
$half_year = $month*6;
$year = $half_year*2;

	if($seconds>=$year)
		return 11; //12 months 
	else if($seconds>=$half_year)
		return 5; //6 months
	else if($seconds>=$month)
		return 14; //15 days
	else if($seconds>=$week)
		return 6; //7 days
	else if($seconds>=$day)
		return 11; //12 hours
	else if($seconds>=$hour)
		return 

}
?>