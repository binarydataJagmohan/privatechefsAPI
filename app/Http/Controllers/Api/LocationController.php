<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LocationController extends Controller
{
    public function get_single_location($id)
    {
        $location = Location::where('id', $id)->where('status', 'active')->first();

        if (!$location) {
            return response()->json(['status' => false, 'message' => 'Location not found or inactive'], 404);
        }

        return response()->json(['status' => true, 'message' => 'Location fetched successfully', 'data' => $location]);
    }

    public function get_location_data()
    {
        try {
            $location = Location::where('status', 'active')->orderBy('created_at', 'desc')->get();
            return response()->json(['status' => true, 'message' => 'All Location fetched successfully', 'data' => $location], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function save_location(Request $request)
    {
        $location = new Location();

        $locationText = $request->location;
        $baseSlug = Str::slug($locationText);
        $slug = $baseSlug;

        // Ensure uniqueness
        $count = 1;
        while (\App\Models\Location::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count;
            $count++;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $randomNumber = mt_rand(1000000000, 9999999999);
            $imageName = $randomNumber . '_' . $file->getClientOriginalName();

            $file->move(public_path('images/location'), $imageName);
            $location->image = $imageName;
        }

        $location->location = $request->location;
        $location->slug = $slug;
        $location->heading_one = $request->heading_one;
        $location->peragraph_one = $request->peragraph_one;
        $location->heading_two = $request->heading_two;
        $location->heading_three = $request->heading_three;
        $location->peragraph_two = $request->peragraph_two;
        $location->heading_box_one = $request->heading_box_one;
        $location->peragraph_box_one = $request->peragraph_box_one;
        $location->heading_box_two = $request->heading_box_two;
        $location->peragraph_box_two = $request->peragraph_box_two;
        $location->heading_box_three = $request->heading_box_three;
        $location->peragraph_box_three = $request->peragraph_box_three;
        $location->heading_box_four = $request->heading_box_four;
        $location->peragraph_box_four = $request->peragraph_box_four;
        $location->heading_box_five = $request->heading_box_five;
        $location->peragraph_box_five = $request->peragraph_box_five;
        $location->heading_four = $request->heading_four;
        $location->peragraph_four = $request->peragraph_four;
        $location->heading_five = $request->heading_five;
        $location->peragraph_five = $request->peragraph_five;
        $location->heading_Six = $request->heading_Six;
        $location->save();

        return response()->json(['status' => true, 'message' => 'Location added successfully', 'data' => $location]);
    }

    public function update_location(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        // Check if location name has changed, then regenerate slug
        if ($location->location !== $request->location) {
            $locationText = $request->location;
            $baseSlug = Str::slug($locationText);
            $slug = $baseSlug;

            // Ensure uniqueness
            $count = 1;
            while (\App\Models\Location::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            $location->slug = $slug;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $randomNumber = mt_rand(1000000000, 9999999999);
            $imageName = $randomNumber . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/location'), $imageName);

            // Optionally delete old image
            if ($location->image && file_exists(public_path('images/location/' . $location->image))) {
                unlink(public_path('images/location/' . $location->image));
            }

            $location->image = $imageName;
        }

        $location->location = $request->location;
        $location->heading_one = $request->heading_one;
        $location->peragraph_one = $request->peragraph_one;
        $location->heading_two = $request->heading_two;
        $location->heading_three = $request->heading_three;
        $location->peragraph_two = $request->peragraph_two;
        $location->heading_box_one = $request->heading_box_one;
        $location->peragraph_box_one = $request->peragraph_box_one;
        $location->heading_box_two = $request->heading_box_two;
        $location->peragraph_box_two = $request->peragraph_box_two;
        $location->heading_box_three = $request->heading_box_three;
        $location->peragraph_box_three = $request->peragraph_box_three;
        $location->heading_box_four = $request->heading_box_four;
        $location->peragraph_box_four = $request->peragraph_box_four;
        $location->heading_box_five = $request->heading_box_five;
        $location->peragraph_box_five = $request->peragraph_box_five;
        $location->heading_four = $request->heading_four;
        $location->peragraph_four = $request->peragraph_four;
        $location->heading_five = $request->heading_five;
        $location->peragraph_five = $request->peragraph_five;
        $location->heading_Six = $request->heading_Six;
        $location->save();

        return response()->json(['status' => true, 'message' => 'Location updated successfully', 'data' => $location]);
    }

    public function delete_location($id)
    {
        $location = Location::findOrFail($id);

        // Delete image from storage if exists
        if ($location->image && file_exists(public_path('images/location/' . $location->image))) {
            unlink(public_path('images/location/' . $location->image));
        }

        $location->delete();

        return response()->json(['status' => true, 'message' => 'Location deleted successfully']);
    }
}
