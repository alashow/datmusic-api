<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */
$router->get('/', 'ApiController@index');
// dl route shouldn't require client headers so the download links can be used anywhere
$router->get('dl/{key}/{id}', ['as' => 'download', 'uses' => 'ApiController@download']);

// todo: delete after v1 app is deprecated
$router->get('search', ['as' => 'v1/search', 'uses' => 'v1\SearchApiController@search']);

// todo: require client headers after v1 app is deprecated
$router->get('stream/{key}/{id}', ['as' => 'stream', 'uses' => 'ApiController@stream']);
// todo: delete or require client headers after v1 app is deprecated
$router->get('bytes/{key}/{id}', ['as' => 'bytes', 'uses' => 'ApiController@bytes']);

$router->group(['middleware' => 'require_client_headers'], function () use ($router) {
    // search
    $router->get('multisearch', ['as' => 'multisearch', 'uses' => 'ApiController@multisearch']);
    $router->get('minerva/search', ['as' => 'minerva.search', 'uses' => 'ApiController@minervaSearch']);
    $router->get('deemix/search', ['as' => 'deemix.search', 'uses' => 'ApiController@deemixSearch']);

    // artists
    $router->get('search/artists', ['as' => 'search.artists', 'uses' => 'ApiController@searchArtists']);
    $router->get('artists/{id}', ['as' => 'artist', 'uses' => 'ApiController@getArtist']);
    $router->get('artists/{id}/audios', ['as' => 'artist.audios', 'uses' => 'ApiController@getArtistAudios']);
    $router->get('artists/{id}/albums', ['as' => 'artist.albums', 'uses' => 'ApiController@getArtistAlbums']);

    //albums
    $router->get('search/albums', ['as' => 'search.albums', 'uses' => 'ApiController@searchAlbums']);
    $router->get('albums/{id}', ['as' => 'album', 'uses' => 'ApiController@getAlbumById']);

    // covers
    $router->get('cover/artists/{artist}[/{size}]', ['as' => 'artist.cover', 'uses' => 'CoverController@artistImage']);
    $router->get('cover/{key}/{id}[/{size}]', ['as' => 'cover', 'uses' => 'CoverController@cover']);

    // users
    $router->post('users/register/fcm', ['as' => 'users.register.fcm', 'uses' => 'UsersApiController@registerFcmToken']);
});
