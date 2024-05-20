<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Receipt;
use App\Models\ReceiptImage;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReceiptController extends Controller
{
    public function save_receipt(Request $request)
    {
        try {
            $totalAmount = DB::table('receipts')
                ->where('booking_id', $request->booking_id)
                ->sum('amount') + $request->amount;
            
            $receipt = new Receipt();
            $receipt->user_id = $request->user_id;
            $receipt->booking_id = $request->booking_id;
            $receipt->amount = $request->amount;
            $receipt->description = $request->description;
            $receipt->order_date = $request->order_date;
            $savedata = $receipt->save();


            if ($savedata) {
                return response()->json(['status' => true, 'message' => "Receipt has been stored succesfully", 'data' => $receipt], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for storing the receipt", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_receipt(Request $request)
    {
        try {
            $receipt = Receipt::join('bookings', 'receipts.booking_id', 'bookings.id')
            ->join('users as u1','receipts.user_id','u1.id')
            ->join('users as u2','bookings.user_id','u2.id')
            ->select('bookings.created_at as booking_date', 'receipts.*','u1.name as chefname','u2.name as username')
            ->where('receipts.status', 'active')
            ->orderBy('receipts.id','DESC')
            ->get();
            if ($receipt) {
                return response()->json(['status' => true, 'message' => "All receipt fetched successfully", 'data' => $receipt], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the receipt", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_chef_receipt(Request $request)
    {
        try {
            $receipt = Receipt::join('bookings', 'receipts.booking_id', 'bookings.id')->select('bookings.created_at as booking_date', 'receipts.*')->where('receipts.user_id',$request->id)->where('receipts.status', 'active')->get();
            if ($receipt) {
                return response()->json(['status' => true, 'message' => "All receipt fetched successfully", 'data' => $receipt], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the receipt", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_single_receipt(Request $request)
    {
        try {
            $receipt = Receipt::find($request->id);
            $receipt_img = ReceiptImage::where('receipt_id', $request->id)->orderBy('id', 'DESC')->where('status', 'active')->get();

            if ($receipt) {
                return response()->json([
                    'status' => true,
                    'message' => 'Single receipt data fetched successfully.',
                    'data' => $receipt,
                    'receiptImg' =>  $receipt_img
                ]);
            } else {
                return response()->json([
                    'status' => true,
                    'message' => 'There has been error for fetching the receipt data',
                    'data' => ''
                ]);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function update_receipt(Request $request)
    {
        try {
            $receipt = Receipt::find($request->id);
            // $receipt->user_id = $request->user_id;
            $receipt->booking_id = $request->booking_id;
            $receipt->amount = $request->amount;
            $receipt->description = $request->description;
            $receipt->order_date = $request->order_date;
            $receipt->save();
            if ($receipt) {
                return response()->json(['status' => true, 'message' => "Receipt has been updated succesfully"], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the receipt"], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function update_receipt_images(Request $request)
    {
        try {
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    $randomNumber = mt_rand(1000000000, 9999999999);
                    $imageName = $randomNumber . $image->getClientOriginalName();
                    $image->move('images/chef/receipt', $imageName);
                    $receipt_img = new ReceiptImage();
                    $receipt_img->receipt_id = $request->id;
                    $receipt_img->image = $imageName;
                    $receipt_img->save();
                }
            }
            return response()->json(['status' => true, 'message' => "Receipt Images has been updated succesfully"], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function deleteReceipt(Request $request)
    {
        try {
            $receipt = Receipt::find($request->id);
            if (!$receipt) {
                return response()->json([
                    'status' => false,
                    'message' => 'Villa not found'
                ]);
            }
            $receipt->status = 'deleted';
            $receipt->save();
            ReceiptImage::where('receipt_id', $request->id)->update([
                'status' => 'deleted'
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Receipt  deleted successfully'
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function get_single_receipt_admin(Request $request)
    {
        try {
            $receipt = Receipt::select('u1.name as chefname','u1.surname as chefsurname','u1.pic as chefpic','u1.phone as chefphone','u1.email as chefemail','u2.email as useremail','u1.address as chefaddress','u2.name as username','u2.surname as usersurname','u2.phone as userphone','u2.address as useraddress','receipts.id','receipts.order_date as orderdate','receipts.amount', 'receipts.booking_id', 'bookings.location', 'bookings.phone as booking_phone', 'bookings.lat', 'bookings.lng')
            ->join('users as u1','receipts.user_id','u1.id')
            ->join('bookings','receipts.booking_id','bookings.id')
            ->join('users as u2','bookings.user_id','u2.id')
            ->where('receipts.id',$request->id)
            ->first();
            if ($receipt) {
                return response()->json(['status' => true, 'message' => "All receipt fetched successfully", 'data' => $receipt], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the receipt", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_concierge_receipt(Request $request)
    {
        try {
            $receipt = Receipt::join('bookings', 'receipts.booking_id', 'bookings.id')
            ->join('users as u1','receipts.user_id','u1.id')
            ->join('users as u2','bookings.user_id','u2.id')
            ->select('bookings.created_at as booking_date', 'receipts.*','u1.name as chefname','u2.name as username')
            ->where('receipts.status', 'active')
            ->orderBy('receipts.id','DESC')
            ->where('u1.status','!=','deleted')
            ->where('u1.created_by',$request->id)
            ->get();
            if ($receipt) {
                return response()->json(['status' => true, 'message' => "All receipt fetched successfully", 'data' => $receipt], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the receipt", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
