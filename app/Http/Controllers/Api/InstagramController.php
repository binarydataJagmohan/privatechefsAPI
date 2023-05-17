<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;

class InstagramController extends Controller
{
    public function getInstagramImages()
    {
        try {
            $accessToken = 'IGQVJVV29rV0htTGQwZAmlJbU9QME9Obk90ZAExUeFBRWWlQZAWUtX29pQmF0MDBablR0djRJbHVkcE1US0ZAqcWtVZATFuMXg1OEwwTENZAcjFjNHNtRzR5UkZAPemFQbkJqSEMxQS1lM3BOaU1jYlYtaF90cwZDZD';
            // $userId = '30906308797';

            $fields = 'id,caption,media_type,media_url,thumbnail_url';

            $client = new Client();
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
