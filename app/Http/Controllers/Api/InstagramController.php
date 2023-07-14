<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;

class InstagramController extends Controller
{
    public function getInstagramImages()
    {
        try {
            $access_token = 'IGQVJXbEJVVVByd0NOdWJxNFMxbXJtTTlXaGlmZA3A5dGdReWVkdkdCX2w5a1F6V2NrZAUpPN2F0aVA0ZAm1pcWpSNmtmNWdhazYtUjIwNUpjNF9WMDVvREpGVW9hQUdVZAmVKNldZAcnVJRFFfd0llT2pZAUAZDZD';
            $fields = 'id,caption,media_type,media_url,thumbnail_url';

           // $client = new Client();
        //    $url = "https://graph.instagram.com/me/media?fields={$fields}&access_token={$access_token}";

        $url = "https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token={$access_token}";
// $url  = "https://graph.instagram.com/access_token?grant_type=ig_exchange_token&
// client_secret=81945f1dedd6cadd969dfbcdadd2e7dc&access_token=IGQVJYOTR6ZA3l1ZAnRBNTdMNTNoQTh2TTlMZA081a3ljb3k0OXVxU01TWnVfX09PUl8xM2VJZAkZAHQVp3ZADFhdmhkNGNycTRvVjlTXzlMLTBFM3ctUGpOTlNPOXRqTE95bVRUUGpfY1ZAJQ2tNdUxlNE5NRAZDZD";


            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            if ($response === false) {
                // cURL request failed
                $error = curl_error($ch);
                curl_close($ch);
                return response()->json(['error' => 'Failed to retrieve images', 'message' => $error], 500);
            }

            curl_close($ch);

            $decodedResponse = json_decode($response, true);
//return $decodedResponse;
            if (isset($decodedResponse['error'])) {
                // Error response from Instagram API
                return response()->json(['error' => 'Failed to retrieve images', 'message' => $decodedResponse['error']['message']], 500);
            }

            $images = $decodedResponse['data'];
            return response()->json(['status' => true, 'message' => '', 'data' => $images], 200);

            return response()->json(['status' => true, 'message' => '', 'data' => $images], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve images',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

// https://graph.instagram.com/3536361556640340/media?fields=id,media_type,media_url,caption&access_token=IGQVJXOWkyWFlOb3hQQUotNkpGOC1oM0lla25vd09adGprcWs5U2NfQThpRlJ5cFIzOVZARbXpxQk91OGg3MFhZAUi1KSjJQV0o4bURRcGpFVmRZAWUdFWDZAZAeDJOeWZA6cFRTTGF5RkR4bVhsQzhERnNTdwZDZD


// /me/media?fields=thumbnail_url,media_url 

//  
