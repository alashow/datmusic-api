<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */
$app->get('/', 'ApiController@index');
$app->get('search', 'ApiController@search');
$app->get('dl/{key}/{id}', 'ApiController@download');
$app->get('dl/{key}/{id}/{bitrate}', 'ApiController@bitrateDownload');
$app->get('stream/{key}/{id}', 'ApiController@stream');
$app->get('bytes/{key}/{id}', 'ApiController@bytes');

$app->get('cover/{key}/{id}', 'CoverController@cover');
