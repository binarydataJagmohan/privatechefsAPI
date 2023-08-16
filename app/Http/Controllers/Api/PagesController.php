<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Pages;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PagesController extends Controller
{
    public function get_settings()
    {
        try {
            $data = Pages::where('status', 'active')->get();
            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_single_setting(Request $request)
    {
        try {
            $data = Pages::where('id', $request->id)->where('status', 'active')->first();
            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function update_setting(Request $request)
    {
        try {
            $receipt = Pages::find($request->id);
            $receipt->meta_tag = $request->meta_tag;
            $receipt->meta_desc = $request->meta_desc;
            $receipt->save();
            if ($receipt) {
                return response()->json(['status' => true, 'message' => "Page has been updated succesfully"], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the page"], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_slug_setting(Request $request)
    {
        try {
            $data = Pages::where('slug', $request->slug)->where('status', 'active')->first();
            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function top_rated_chef(Request $request)
    {
        try {
            $receipt = User::find($request->id);
            $receipt->top_rated = implode(',', $request->top_rated);
            $receipt->save();
            if ($receipt) {
                return response()->json(['status' => true, 'message' => "Top rated chef has been updated succesfully"], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the top rated chef"], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_top_rated_chef(Request $request)
    {
        try {
            $data = User::where('id', 1)->first();
                return response()->json(['status' => true, 'data'=>$data], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
