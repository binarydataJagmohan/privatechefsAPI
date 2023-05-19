<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;

class InstagramController extends Controller
{
    public function getInstagramImages()
    {
        try {
            $accessToken = 'IGQVJWbUp2SnpEZA2wxS21LZA2hIY3ZADYlU5elpSUDBGeDJ2eVZAjZAEZAWNFlyXzFpb0FYNXJlWW1TSkxUSmNwbE5FRi0ta3B2eGFNMW5pX0NVZAWh4VW11TnpKaTRRdl9ORWRpVlBuZA3lsdUk0YWUyUFRURQZDZD';
            // $userId = '30906308797';
            $fields = 'id,caption,media_type,media_url,thumbnail_url';
            $secret_id = '3032c979a515e79dcd31c71d2336f30e0';

           // $client = new Client();
           $url = "https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret={$secret_id}&access_token={$accessToken}";

            $url = "https://graph.instagram.com/me/media?fields={$fields}&access_token={$accessToken}";

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
