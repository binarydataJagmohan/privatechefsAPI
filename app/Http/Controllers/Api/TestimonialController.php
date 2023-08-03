<?php

namespace App\Http\Controllers\Api;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TestimonialController extends Controller
{
    public function save_testimonial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string', //|max:50|
            // 'stars' => 'required|numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'error' => $validator->errors(), 'data' => '']);
        }
        try {
            $testimonial = new Testimonial();
            $testimonial->user_id = $request->user_id;
            $testimonial->name = $request->name;
            $testimonial->description = $request->description;
            $testimonial->stars = $request->stars;
            if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('public/images/admin/testimonial',$imageName);
                $testimonial->image = $imageName;
            }
            $testimonial->save();
            if ($testimonial->save()) {
                $getalltestimonial = Testimonial::where('status', 'active')->orderBy('id', 'desc')->get();

                return response()->json(['status' => true, 'message' => 'testimonial details has been saved successfully', 'data' => $getalltestimonial]);
            } else {
                return response()->json(['status' => false, 'message' => 'Menu name already exit please choose different name', 'error' => '', 'data' => '']);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function gettestnomial()
    {
        try {
            $testimonial = Testimonial::where('status', 'active')->orderBy('id', 'desc')->get();
            return response()->json([
                'status' => true,
                'message' => "testimonial details fetched successfully",
                'data' => $testimonial
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function getSingleTestimonial(Testimonial $testimonial, $id)
    {
        try {
            $testimonial = Testimonial::find($id);
            if ($testimonial) {
                return response()->json(['status' => true, 'message' => "Single testimonial Data fetch successfully", 'testimonial' => $testimonial], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No Single testimonial data found"]);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function updateTestimonial(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string', //|max:50|
            // 'stars' => 'required|numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'error' => $validator->errors(), 'data' => '']);
        }
        try {
            $testimonial = Testimonial::findOrFail($id);
            $testimonial->name = $request->input('name');
            $testimonial->description = $request->input('description');
            $testimonial->stars = $request->input('stars');
            if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('public/images/admin/testimonial',$imageName);
                $testimonial->image = $imageName;
            }
            $testimonial->save();

            return response()->json(['message' => 'testimonial updated successfully', 'data' => $testimonial]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function testimonialDelete($id)
    {
        try {
            $testimonial = Testimonial::find($id);
            if (!$testimonial) {
                return response()->json(['status' => 'Testimonial not found'], 404);
            }
            $testimonial->status = 'deleted'; // Change the status to 'inactive'
            $testimonial->save();
            return response()->json(['status' => true, 'message' => 'Testimonial deleted', 'data' => $testimonial]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
