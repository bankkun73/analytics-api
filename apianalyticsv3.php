<?php
require_once 'src/Google_Client.php';
require_once 'src/contrib/Google_AnalyticsService.php';
$scriptUri = "http://".$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF'];
$client = new Google_Client();
//$client->setAccessType('online');
$client->setApplicationName('Toccata');
$client->setClientId($data[0]['client_id']); // client id
$client->setClientSecret($data[0]['client_secret']); //client secret
$client->setRedirectUri($scriptUri); //url redirect
$client->setDeveloperKey($data[0]['develop_key']); // API key
$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly')); //api scopes
$client->setApprovalPrompt('auto');

$client->setUseObjects(true);
// $service implements the client interface, has to be set before auth call
$service = new Google_AnalyticsService($client);

if (isset($_GET['logout'])) {
    unset($_SESSION['token']);
    die('Logged out.');
}

if (isset($_GET['code'])) {
    $client->authenticate();
    $_SESSION['token'] = $client->getAccessToken();
	  header("Location: ".$scriptUri);
}
if (isset($_SESSION['token'])) { // extract token from session and configure client
    $token = $_SESSION['token'];
    $client->setAccessToken($token);
}

if (!$client->getAccessToken()) { // auth call to google
    $authUrl = $client->createAuthUrl();
    header("Location: ".$authUrl);
    die;
}
// token expierd delete token and create new token
if($client->isAccessTokenExpired()) {
	echo "Token Expired";
	unset($_SESSION['token']);
	header("Location: ".$scriptUri);
}
try {
    $projectId = $data[0]['profile_id']; //profile id
    	
	if(isset($_GET['from']) && isset($_GET['to'])){
	//if call get select date show data
		$from = $_GET['from'];
		$to = $_GET['to'];
	}else{
	//default call get date
		$day = date("d");
		$month = date("m");
		$year = date("Y");
		//$from = date('Y-m-d', time()-15*24*60*60); //15 days ago
		$from = date("Y-m-d", mktime(0, 0, 0, $month, $day-15, $year));
		$to = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
		//$to = date('Y-m-d');
		//$to = date('Y-m-d', time());
		//$to = date('Y-m-d', time()-24*60*60); // 1 days ago
	}
	//Graph Page View
    $metrics = 'ga:visits,ga:pageviews,ga:uniquePageviews,ga:newVisits';
    $dimensions = 'ga:date,ga:year,ga:month,ga:day';
    $data = $service->data_ga->get('ga:'.$projectId, $from, $to, $metrics, array('dimensions' => $dimensions));
    
    $retData = array();
    $dataRow = array();
    $retData[0] = array('Month','Visits','Pageviews','UniqueViews','New Visits');
	foreach($data as $key => $row){
	//loob date query to json 
		if($key=="rows"){
			foreach($row as $key => $row1){
				$data = $row1[1]."/".$row1[2]."/".$row1[3];
				$retData[$key+1] = array($data,$row1[4],$row1[5],$row1[6],$row1[7]);
			}
		}
	}
} catch (Exception $e) {
  // if call data error
  //die($e->getCode());
  //die($e->getMessage());
	if($e->getCode()==403)
	{
  		/*do {
  		printf("%s (%d) [%s]\n", $e->getMessage(), $e->getCode(), get_class($e));
  		$text_message = explode(")",$e->getMessage());
  		} while($e = $e->getPrevious());*/
		$text_message = explode(")",$e->getMessage());
		$text = trim($text_message[1]);
		switch($text)
		{
			case 'User does not have sufficient permissions for this profile.':
									//error
									echo $e->getMessage();
								break;
								case 'Access Not Configured':
									//error
									echo $e->getMessage();
								break;
								default:
								  //error
									echo $e->getMessage();
								break;
							}
							die();
						}
						else{
							die($e->getMessage());
						}
					}
?>
<div id="line_chart"></div>
<script>
google.load("visualization", "1", {
  packages: ["corechart"]
});
$(function(){
  line_chart();
});
function drawChart2() {
  var data = google.visualization.arrayToDataTable(<?=$data_json?>);
  var options = {
    width: 'auto',
    height: '160',
    backgroundColor: 'transparent',
    colors: ['#3eb157', '#3660aa', '#d14836', '#dba26b', '#666666', '#f26645'],
    tooltip: {
      textStyle: {
        color: '#666666',
        fontSize: 13
      },
      showColorCode: true
    },
    legend: {
      textStyle: {
        color: 'black',
        fontSize: 12
      }
    },
    chartArea: {
      left: 100,
      top: 10
    },
    focusTarget: 'category',
    hAxis: {
      textStyle: {
        color: 'black',
        fontSize: 12
      }
    },
    vAxis: {
      textStyle: {
        color: 'black',
        fontSize: 12
      }
    },
    pointSize: 8,
    chartArea: {
      left: 60,
      top: 10,
      height: '80%'
    },
    lineWidth: 2,
  };

  var chart = new google.visualization.LineChart(document.getElementById('line_chart'));
  chart.draw(data, options);
}
</script>
