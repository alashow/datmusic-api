<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */
$router->get('/', 'ApiController@index');

$router->get('search', 'v1\SearchApiController@search');

$router->get('dl/{key}/{id}', 'ApiController@download');
$router->get('dl/{key}/{id}/{bitrate}', 'ApiController@bitrateDownload');
$router->get('stream/{key}/{id}', 'ApiController@stream');
$router->get('bytes/{key}/{id}', 'ApiController@bytes');
$router->get('cover/artists/{artist}[/{size}]', 'CoverController@artistCover');
$router->get('cover/{key}/{id}[/{size}]', 'CoverController@cover');

$router->get('search/albums', 'ApiController@searchAlbums');
$router->get('search/artists', 'ApiController@searchArtists');
$router->get('artists/{artistId}', 'ApiController@getArtistAudios');
$router->get('artists/{artistId}/albums', 'ApiController@getArtistAlbums');
$router->get('albums/{albumId}', 'ApiController@getAlbumById');

$router->get('multisearch', 'ApiController@multisearch');
