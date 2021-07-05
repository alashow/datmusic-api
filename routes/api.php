<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */
$router->get('/', 'ApiController@index');

$router->get('search', ['as' => 'v1/search', 'uses' => 'v1\SearchApiController@search']);
$router->get('multisearch', ['as' => 'multisearch', 'uses' => 'ApiController@multisearch']);

// todo: remove/disable bitrate choosing from code
//$router->get('dl/{key}/{id}/{bitrate}', ['as' => 'downloadWithBitrate', 'uses' => 'ApiController@bitrateDownload']);

// audio
$router->get('dl/{key}/{id}', ['as' => 'download', 'uses' => 'ApiController@download']);
$router->get('stream/{key}/{id}', ['as' => 'stream', 'uses' => 'ApiController@stream']);
$router->get('bytes/{key}/{id}', ['as' => 'bytes', 'uses' => 'ApiController@bytes']);
$router->get('cover/{key}/{id}[/{size}]', ['as' => 'cover', 'uses' => 'CoverController@cover']);

// artists
$router->get('search/artists', ['as' => 'search.artists', 'uses' => 'ApiController@searchArtists']);
$router->get('artists/{id}', ['as' => 'artist', 'uses' => 'ApiController@getArtistAudios']);
$router->get('artists/{id}/albums', ['as' => 'artist.albums', 'uses' => 'ApiController@getArtistAlbums']);
$router->get('cover/artists/{artist}[/{size}]', ['as' => 'artist.cover', 'uses' => 'CoverController@artistCover']);

$router->get('search/albums', ['as' => 'search.albums', 'uses' => 'ApiController@searchAlbums']);
$router->get('albums/{id}', ['as' => 'album', 'uses' => 'ApiController@getAlbumById']);
