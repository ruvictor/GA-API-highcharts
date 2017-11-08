<?php
// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';
class GA{
	function initializeAnalytics()
	{
	  // Creates and returns the Analytics Reporting service object.

	  // Use the developers console and download your service account
	  // credentials in JSON format. Place them in this directory or
	  // change the key file location if necessary.
	  $KEY_FILE_LOCATION = __DIR__ . '/PAI-dw55dw5w5was.json';

	  // Create and configure a new client object.
	  $client = new Google_Client();
	  $client->setApplicationName("Project");
	  $client->setAuthConfig($KEY_FILE_LOCATION);
	  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
	  $analytics = new Google_Service_Analytics($client);

	  return $analytics;
	}

	function getFirstProfileId($analytics) {
	  // Get the user's first view (profile) ID.

	  // Get the list of accounts for the authorized user.
	  $accounts = $analytics->management_accounts->listManagementAccounts();

	  if (count($accounts->getItems()) > 0) {
		$items = $accounts->getItems();
		$firstAccountId = $items[0]->getId();

		// Get the list of properties for the authorized user.
		$properties = $analytics->management_webproperties
			->listManagementWebproperties($firstAccountId);

		if (count($properties->getItems()) > 0) {
		  $items = $properties->getItems();
		  $firstPropertyId = $items[0]->getId();

		  // Get the list of views (profiles) for the authorized user.
		  $profiles = $analytics->management_profiles
			  ->listManagementProfiles($firstAccountId, $firstPropertyId);

		  if (count($profiles->getItems()) > 0) {
			$items = $profiles->getItems();

			// Return the first view (profile) ID.
			return $items[0]->getId();

		  } else {
			throw new Exception('No views (profiles) found for this user.');
		  }
		} else {
		  throw new Exception('No properties found for this user.');
		}
	  } else {
		throw new Exception('No accounts found for this user.');
	  }
	}
	
	function OrganicResults($analytics, $profileId, $start_date, $end_date) {
	  // Calls the Core Reporting API and queries for the number of sessions
	  // for the last seven days.
	$optParams = array( //OPTINAL SETTINGS
	'dimensions'=>'ga:source',
	'filters'=>'ga:medium==organic',
	'metrics'=>'ga:sessions'
			  );
	   return $analytics->data_ga->get(
		   'ga:' . $profileId,
		   $start_date,
		   $end_date,
		   'ga:sessions',
		   $optParams
		   );
	}
	function NonOrganicResults($analytics, $profileId, $start_date, $end_date) {
	  // Calls the Core Reporting API and queries for the number of sessions
	  // for the last seven days.
	$optParams = array( //OPTINAL SETTINGS
	'dimensions'=>'ga:source',
	'filters'=>'ga:medium!=organic',
	'metrics'=>'ga:sessions'
			  );
	   return $analytics->data_ga->get(
		   'ga:' . $profileId,
		   $start_date,
		   $end_date,
		   'ga:sessions',
		   $optParams
		   );
	}
	function printResults($organic, $nonorganic) {
	  if ($organic || $nonorganic) {
		?>
	<div id="container" style="display: table;width:100%; height: 300px; margin: 0 auto"></div>
	<script>
	Highcharts.chart('container', {
		colors: ['#4572a7', '#aa4643'],
		chart: {
			type: 'column'
		},
		title: {
			text: 'Total visits'
		},
		xAxis: {
			categories: [
			<?php
			$first  = strtotime('first day this month');
			for ($j = 5; $j >=0; $j--){
				echo '"'.date("m/Y", strtotime(" -$j month", $first)).'",';
			}
			?>
			]
		},
		yAxis: {
			min: 0,
			title: {
				text: 'Total visits by month'
			},
			stackLabels: {
				enabled: true,
				style: {
					fontWeight: 'bold',
					color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
				}
			}
		},
		legend: {
			align: 'right',
			x: -30,
			verticalAlign: 'top',
			y: 25,
			floating: true,
			backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
			borderColor: '#CCC',
			borderWidth: 1,
			shadow: false
		},
		tooltip: {
			headerFormat: '<b>{point.x}</b><br/>',
			pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
		},
		plotOptions: {
			column: {
				stacking: 'normal',
				dataLabels: {
					enabled: true,
					color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
				}
			}
		},
		series: [{
			name: 'Organic Visits',
			data: [
			<?php 
			for ($j = 5; $j >=0; $j--){
			echo $organic[$j]['totalsForAllResults']['ga:sessions'].', '; 
			}
			?>
			]
		}, {
			name: 'Non-Organic Visits',
			data: [		
			<?php 
			for ($j = 5; $j >=0; $j--){
			echo $nonorganic[$j]['totalsForAllResults']['ga:sessions'].', '; 
			}
			?>
			]
		}]
	});
	</script>
		<?php
	  } else {
		print "No results found.\n";
	  }
	}
	function OutputData(){
		$analytics = $this->initializeAnalytics();
		$profile = $this->getFirstProfileId($analytics);
		$organic = array();
		$nonorganic = array();
		for ($j = 5; $j >= 0; $j--){
			$start_date = date('Y-m', strtotime("-$j month")) . '-01';
			$d = new DateTime($start_date);
			$end_date = $d->format('Y-m-t');
			$organic[$j] = $this->OrganicResults($analytics, $profile, $start_date, $end_date);
			$nonorganic[$j] = $this->NonOrganicResults($analytics, $profile, $start_date, $end_date);
		}
		$this->printResults($organic, $nonorganic);

	}
}
?>