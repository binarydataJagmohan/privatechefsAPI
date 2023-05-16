<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;

class InstagramController extends Controller
{
    public function getImages()
    {
            $accessToken = 'IGQVJXOWkyWFlOb3hQQUotNkpGOC1oM0lla25vd09adGprcWs5U2NfQThpRlJ5cFIzOVZARbXpxQk91OGg3MFhZAUi1KSjJQV0o4bURRcGpFVmRZAWUdFWDZAZAeDJOeWZA6cFRTTGF5RkR4bVhsQzhERnNTdwZDZD';
            // $userId = '30906308797';
        
            $fields = 'id,caption,media_type,media_url,thumbnail_url';
        
            $client = new Client();
            $response = $client->get("https://graph.instagram.com/me/media?fields={$fields}&access_token={$accessToken}");
            $images = json_decode($response->getBody()->getContents(), true)['data'];
            return response()->json($images);
        
    }
}

// https://graph.instagram.com/3536361556640340/media?fields=id,media_type,media_url,caption&access_token=IGQVJXOWkyWFlOb3hQQUotNkpGOC1oM0lla25vd09adGprcWs5U2NfQThpRlJ5cFIzOVZARbXpxQk91OGg3MFhZAUi1KSjJQV0o4bURRcGpFVmRZAWUdFWDZAZAeDJOeWZA6cFRTTGF5RkR4bVhsQzhERnNTdwZDZD


// /me/media?fields=thumbnail_url,media_url 

// https://graph.instagram.com/me/media?fields=thumbnail_url,media_url&access_token=IGQVJXOWkyWFlOb3hQQUotNkpGOC1oM0lla25vd09adGprcWs5U2NfQThpRlJ5cFIzOVZARbXpxQk91OGg3MFhZAUi1KSjJQV0o4bURRcGpFVmRZAWUdFWDZAZAeDJOeWZA6cFRTTGF5RkR4bVhsQzhERnNTdwZDZD

