<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */
$router->get('/', 'ApiController@index');

// dl route shouldn't require client headers so the download links can be public
$router->get('dl/{key}/{id}', ['as' => 'download', 'uses' => 'ApiController@download']);

$router->group(['middleware' => 'require_client_headers'], function () use ($router) {
    $router->get('stream/{key}/{id}', ['as' => 'stream', 'uses' => 'ApiController@stream']);
    $router->get('bytes/{key}/{id}', ['as' => 'bytes', 'uses' => 'ApiController@bytes']);

    // search
    $router->get('multisearch', ['as' => 'multisearch', 'uses' => 'ApiController@multisearch']);
    $router->get('minerva/search', ['as' => 'minerva.search', 'uses' => 'ApiController@minervaSearch']);

    $router->get('deemix/search', ['as' => 'deemix.search', 'uses' => 'ApiController@deemixSearchAudios']);
    $router->get('deemix/search/flacs', ['as' => 'deemix.search', 'uses' => 'ApiController@deemixSearchFlacs']);
    $router->get('deemix/search/artists', ['as' => 'deemix.search.artists', 'uses' => 'ApiController@deemixSearchArtists']);
    $router->get('deemix/search/albums', ['as' => 'deemix.search.albums', 'uses' => 'ApiController@deemixSearchAlbums']);

    $router->get('deemix/artists/{id}', ['as' => 'deemix.artist', 'uses' => 'ApiController@deemixArtist']);
    $router->get('deemix/albums/{id}', ['as' => 'deemix.album', 'uses' => 'ApiController@deemixAlbum']);

    // artists
    $router->get('search/artists', ['as' => 'search.artists', 'uses' => 'ApiController@deemixSearchArtists']);
    $router->get('artists/{id}', ['as' => 'artist', 'uses' => 'ApiController@deemixArtist']);
    $router->get('artists/{id}/audios', ['as' => 'artist.audios', 'uses' => 'ApiController@deemixArtistAudios']);
    $router->get('artists/{id}/albums', ['as' => 'artist.albums', 'uses' => 'ApiController@deemixArtistAlbums']);

    //albums
    $router->get('search/albums', ['as' => 'search.albums', 'uses' => 'ApiController@deemixSearchAlbums']);
    $router->get('albums/{id}', ['as' => 'album', 'uses' => 'ApiController@deemixAlbum']);

    // covers
    $router->get('cover/artists/{artist}[/{size}]', ['as' => 'artist.cover', 'uses' => 'CoverController@artistImage']);
    $router->get('cover/{key}/{id}[/{size}]', ['as' => 'cover', 'uses' => 'CoverController@cover']);

    // users
    $router->post('users/register/fcm', ['as' => 'users.register.fcm', 'uses' => 'UsersApiController@registerFcmToken']);
});
