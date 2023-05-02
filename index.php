<?php

require 'vendor/autoload.php';

run();

function run() {
	$config = getConfig();

	$client = new GuzzleHttp\Client();

	$authHeaders = authenticate($client, $config);
	
	checkSubreddits($client, $authHeaders, $config);

	echo "Done!\n";
}

function authenticate($client, $config): array {
	$headers = [
		'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
	];
	
	$res = $client->request('POST', 'https://www.reddit.com/api/v1/access_token', [
		'auth' => [$config['reddit']['client_id'], $config['reddit']['client_secret']],
		'form_params' => [
			"grant_type" => 'password',
			'username' => $config['reddit']['username'],
			'password' => $config['reddit']['password'],
	
		],
		'headers' => $headers,
	]);
	
	$authData = json_decode($res->getBody(), true);
	
	$headers['Authorization'] = "bearer {$authData['access_token']}";

	return $headers;
}

function checkSubreddits($client, $authHeaders, $config) {

	$outputFile = getOutputFile($config);

	foreach ($config['subreddits'] as $subreddit) {

		$listings = getListings($subreddit, $client, $authHeaders, $config);
	
		$topListings = getTopListings($listings, $config);
		
		writeListingsToFile($outputFile, $topListings, $subreddit);
	}
}

function getOutputFile($config) {
	$nowDate = new \DateTimeImmutable('now', new DateTimeZone($config['timezone']));
	$now = $nowDate->format('Y-m-dTH-i-s');
	
	$file = fopen("output/{$now}.html", "w") or exit("Unable to open file!");

	return $file;
}

function getListings($subreddit, $client, $authHeaders, $config): array {
	$res = $client->request('GET', "https://oauth.reddit.com/r/{$subreddit}/hot", [
		'headers' => $authHeaders,
		'query' => [
			'limit' => $config['listings_to_check'],
		]
	]);
	
	return json_decode($res->getBody(), true);
}

function writeListingsToFile($outputFile, $topListings, $subreddit) {
	fwrite($outputFile, "<h2>/r/{$subreddit}</h2>");
		
	foreach ($topListings as $topListing) {
	
		$html = <<<EOD
		<p>
			<h2 style="margin: 3px 0px;">
				<a href="{$topListing['url']}" target="_blank">
					[{$topListing['ups']}] {$topListing['title']}
				</a>
			</h2>
			<small>{$topListing['created_utc']} ({$topListing['link_flair_css_class']})</small>
		</p>\n
		EOD;
	
		fwrite($outputFile, $html);
	}
}

function getTopListings($listings, $config): array {

	$formattedListings = [];

	foreach ($listings["data"]["children"] as $listing) {
		
		// this is unused now, but needed if you want to implement pagination
		$fullname = $listing['kind'] . '_' . $listing['data']['id'];
	
		$dt = new DateTimeImmutable('@'.$listing['data']['created_utc'], new DateTimeZone('UTC'));
		$formattedTime = $dt->setTimezone(new DateTimeZone($config['timezone']))->format('Y-m-d H:i:s T');
	
		$formattedListings[] = [
			'subreddit' => $listing['data']['subreddit'],
			'title' => $listing['data']['title'],
			'selftext' => $listing['data']['selftext'],
			'upvote_ratio' => $listing['data']['upvote_ratio'],
			'ups' => $listing['data']['ups'],
			'downs' => $listing['data']['downs'],
			'score' => $listing['data']['score'],
			'link_flair_css_class' => $listing['data']['link_flair_css_class'],
			'created_utc' => $formattedTime,
			'id' => $listing['data']['id'],
			'kind' => $listing['kind'],
			'url' => $listing['data']['url'],
		];
	}

	// sort all listings in descending order based on upvotes
	usort($formattedListings, function ($item1, $item2) {
		return $item2['ups'] <=> $item1['ups'];
	});
	
	// get number of top listings
	return array_slice($formattedListings, 0, $config['listings_to_get']);
}

function getConfig(): array {

	$configFileName = 'config.json';

	if (!file_exists($configFileName)) {
		exit("Can't find config.json file. Have you forgot to rename config.example.json file?");
	}

	$configString = file_get_contents($configFileName);
	$config = json_decode($configString, true);

	// max allowed is 100, past that use pagination
	if ($config['listings_to_check'] > 100) {
		$config['listings_to_check'] = 100;
	}

	// make sure you're not trying to get more listings than available
	if ($config['listings_to_get'] > $config['listings_to_check']) {
		$config['listings_to_get'] = $config['listings_to_check'];
	}

	return $config;
}
