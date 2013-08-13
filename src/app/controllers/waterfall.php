<?php
/**
 * Controller actions for waterfall functions.
 */
$app->get('/waterfall', function () use ($app) {
	 $request = $app->request();
	$profiles = new Xhgui_Profiles($app->db->results);
	$requestTimes = array();
	$keys = array("remote_addr", 'request_start', 'request_end');
	foreach ($keys as $key) {
        $search[$key] = $request->get($key);
    }
	$result = $profiles->getAll(array(        
		'sort' => 'time',
		'direction' => 'asc',
		'conditions' => $search
    ));

    $count = count($result['results']);
    $height = 50 + (30 * $count);

    $paging = array(
        'total_pages' => $result['totalPages'],
        'page' => $result['page'],
        'sort' => 'asc',
        'direction' => $result['direction']
    );

	$app->render('waterfall/list.twig', array(
		'remote_addr' => $search['remote_addr'],
		'request_start' => $search['request_start'],
		'request_end' => $search['request_end'],
		'height' => $height,
		'runs' => $result['results'],
		'search' => $search,
		'paging' => $paging,
		'base_url' => 'waterfall',
	));
	})->name('waterfall');

$app->get('/waterfall/data', function () use ($app) {
	header('Content-Type: application/json');
	 $request = $app->request();
	$profiles = new Xhgui_Profiles($app->db->results);
	$requestTimes = array();
	$keys = array("remote_addr", 'request_start', 'request_end');
	foreach ($keys as $key) {
        $search[$key] = $request->get($key);
    }
	$result = $profiles->getAll(array(        
		'sort' => 'time',
		'direction' => 'asc',
		'conditions' => $search
    ));
    $datas = array();
    foreach($result['results'] as $r)
    {
    	$meta = $r->getMeta();
    	$profile = $r->getProfile();
    	$requestTimes[] = $meta['SERVER']['REQUEST_TIME'];
    	$duration = $profile['main()']['wt'];
    	$start = $meta['SERVER']['REQUEST_TIME'];
    	$end = $start + ($duration / 1000000);
    	$title = $meta['url'];
    	$data = array(
    		'title' => $title,
    		'subtitle' => '',
    		'start' => ($start + rand(1,1000) / 1000) * 1000,
    		'duration' => $duration / 1000      //Convert to correct scale
    	);
    	$datas[] = $data;
    }
    
    
    echo json_encode($datas);

	exit;
})->name('waterfall.data');