<?php

	$last = stat('status.txt');
	if ($last) { $last = $last['mtime']; }

	if ($last + 500 >= time()) {
		header('HTTP/1.1 200 OK', true, 200);
		header('Content-Type: text/plain; charset=utf-8');
		header('Last-Modified: ' . date(DATE_RFC2822, $last));
		header('Expires: ' . date(DATE_RFC2822, $last + 500));
		header('X-Status-Cache: from-cache');
		readfile('status.txt');
	} else {

		touch('status.txt');

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HTTPGET => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_URL => 'http://worlds.simcity.com/parallelworlds.json'
		));
		$resp = curl_exec();
		curl_close($curl); unset($curl);
		$resp = json_decode($resp, true);

		$total = count($resp['hosts']);
		$active = 0;
		foreach($resp['hosts'] as &$server) {
			if (
				count($server['statuses']) &&
				$server['statuses'][0]['status'] === 'available'
			) { $active += 1; }
		}

		if (($active / $total) >= 1) { $status = 'YES'; }
		elseif (($active / $total) >= 0.5) { $status = 'Maybe'; }
		else { $status = 'NO'; }
		file_put_contents('status.txt', $status);

		$last = time();

		header('HTTP/1.1 200 OK', true, 200);
		header('Content-Type: text/plain; charset=utf-8');
		header('Last-Modified: ' . date(DATE_RFC2822, $last));
		header('Expires: ' . date(DATE_RFC2822, $last + 500));
		header('X-Status-Cache: from-source');
		echo($status);

	}

?>