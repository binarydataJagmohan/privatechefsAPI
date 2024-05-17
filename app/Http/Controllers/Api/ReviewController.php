<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reviews;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\User;

class ReviewController extends Controller
{
    
    public function addReviews(Request $request)
    {
        try {

            $review = Reviews::where('booking_id',$request->booking_id)->where('given_by_id',$request->given_by_id)->count();

               if($review <= 0 ) {

                $review = new Reviews();
                $review->booking_id =  $request->booking_id;
                $review->comment =  $request->comment;
                $review->given_by_id = $request->given_by_id;
                $review->given_to_id = $request->given_to_id;
                $review->stars = $request->stars;

                if($review->save()){

                     return response()->json(['status' => true, 'message' => 'Reviews has been given successfully', 'error' => '']);

                 }else {

                     return response()->json(['status' => true, 'message' => 'There has been for saving the menu', 'error' => '', 'data' => '']);
                 }

            }else {

             return response()->json(['status' => true, 'message' => 'You have already submitted a review for this booking', 'error' => '', 'data' => '']);

           }

            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

    }

    public function getAllChefReview($id)
        {
            try{


                if(is_numeric($id)){
                         $chef_id = $id;
                }else {
                     $chef = User::select('id')->where('slug',$id)->first();
                     $chef_id = $chef->id;
                }
                    
                $reviews = Reviews::Select('name','pic','stars','comment', 'reviews.created_at')->join('users','reviews.given_by_id','=','users.id')->where('given_to_id',$chef_id)->orderBy('reviews.id','desc')->get();

                if (!$reviews->isEmpty()) {

                    $averageRating = Reviews::where('given_to_id', $chef_id)
                        ->where('status', 'active')
                        ->avg('stars');

                        return response()->json([
                            'status' => true,
                            'message' => "Review details fetched successfully",
                            'data' => $reviews,
                            'averageRating' => $averageRating
                        ], 200);


                } else {
                        return response()->json([
                            'status' => true,
                            'message' => "Review details fetched successfully",
                            'data' => '',
                            'averageRating' => ''
                        ], 200);
                }

         }
         catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

}
