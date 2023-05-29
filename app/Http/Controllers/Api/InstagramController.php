<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;

class InstagramController extends Controller
{
    public function getInstagramImages()
    {
        try {
            $access_token = 'IGQVJYQ0J0MXcwMEJDZAXVGWUlBLWc3SUZA1cDdWQXB1cmFaVzIzQVd4NmhIZAjNleVV5RDZAuaUFfRC1hbE8tVVRWbDBZAQi1YUWRNd29KLTRHQ1lHWW8wbEkwSVRvNEIwVGx5MVBrdzdB';
            $fields = 'id,caption,media_type,media_url,thumbnail_url';

           // $client = new Client();
           $url = "https://graph.instagram.com/me/media?fields={$fields}&access_token={$access_token}";
// $url  = "https://graph.instagram.com/access_token?grant_type=ig_refresh_token&
// client_secret=055ced48abb1a82c9ad93b2cf22af4cc&access_token=IGQVJVRWtjR25oS2VMRGxwcVlDTmFEWTNORjhUTzRYUFFDT1Q3WnlmOGFuT09iWlBMYlRZALU1nRkdLUFVtbXM0WjhCd2poU2puZAFp4bHFLa1BPSk5vYkhrd29PSVNDdFYxQnZAOZAURRQzBmUHA0OFVlSwZDZD";

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
