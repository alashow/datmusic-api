<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */
$router->get('/', 'ApiController@index');
$router->get('search', 'ApiController@search');
$router->get('dl/{key}/{id}', 'ApiController@download');
$router->get('dl/{key}/{id}/{bitrate}', 'ApiController@bitrateDownload');
$router->get('stream/{key}/{id}', 'ApiController@stream');
$router->get('bytes/{key}/{id}', 'ApiController@bytes');

$router->get('cover/{key}/{id}', 'CoverController@cover');
