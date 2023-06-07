<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DB;

class InvoiceController extends Controller
{
    public function save_invoice(Request $request)
    {
        try {
            $invoice = new Invoice();
            $invoice->user_id = $request->user_id;
            $invoice->booking_id = $request->booking_id;
            $invoice->date = $request->date;
            $invoice->invoice_no = $request->invoice_no;
            $invoice->amount = $request->amount;
            $invoice->description = $request->description;
            $invoice->save();

            if ($invoice->save()) {
                return response()->json(['status' => true, 'message' => 'Invoice details has been save successfully'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'There has been error for storing the invoice', 'error' => '']);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_chef_invoice(Request $request)
    {
        try {
            $invoice = Invoice::select('users.name', 'users.surname', 'invoices.date', 'invoices.booking_id', 'invoices.id as invoiceID', 'invoices.invoice_no', 'invoices.amount as invoiceAmount')->join('bookings', 'invoices.booking_id', 'bookings.id')
                ->join('users', 'bookings.user_id', 'users.id')
                ->where('invoices.user_id', $request->id)->where('invoices.status', 'active')->get();
            return response()->json(['status' => true, 'message' => 'All Invoice fetched successfully', 'data' => $invoice], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function update_invoice(Request $request)
    {
        try {
            $invoice = Invoice::find($request->id);
            $invoice->user_id = $request->user_id;
            $invoice->booking_id = $request->booking_id;
            $invoice->date = $request->date;
            $invoice->invoice_no = $request->invoice_no;
            $invoice->amount = $request->amount;
            $invoice->description = $request->description;
            $invoice->save();

            if ($invoice->save()) {
                return response()->json(['status' => true, 'message' => 'Invoice details has been updated successfully'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'There has been error for updating the invoice', 'error' => '']);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_single_invoice(Request $request)
    {
        try {
            $invoice = Invoice::select('users.name', 'users.surname', 'invoices.date', 'invoices.booking_id', 'invoices.id as invoiceID', 'invoices.invoice_no', 'invoices.amount as invoiceAmount')->join('bookings', 'invoices.booking_id', 'bookings.id')
                ->join('users', 'bookings.user_id', 'users.id')
                ->where('invoices.id', $request->id)->where('invoices.status', 'active')->first();
            return response()->json(['status' => true, 'message' => 'All Invoice fetched successfully', 'data' => $invoice], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function delete_invoice(Request $request)
    {
        try {
            $invoice = Invoice::find($request->id);
            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invoice not found'
                ]);
            }
            $invoice->status = 'deleted';
            $invoice->save();

            return response()->json([
                'status' => true,
                'message' => 'Invoice deleted successfully'
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function get_all_invoice(Request $request)
    {
        try {
            $invoices = Invoice::select('u2.name as username', 'u2.surname as usersurname', 'u1.name as chefname', 'u1.surname as chefsurname', 'invoices.date', 'invoices.booking_id', 'invoices.id as invoiceID', 'invoices.invoice_no', 'invoices.amount as invoiceAmount', 'u1.phone as chefphoneno','invoices.id')
                ->join('users as u1', 'invoices.user_id', '=', 'u1.id')
                ->join('bookings', 'bookings.id', '=', 'invoices.booking_id')
                ->join('users as u2', 'bookings.user_id', '=', 'u2.id')
                ->where('invoices.status', 'active')
                ->get();
            return response()->json(['status' => true, 'message' => 'All Invoice fetched successfully', 'data' => $invoices], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function single_invoice(Request $request)
    {
        try {

            $invoice = Invoice::select(
                'invoices.booking_id as invoiceBooking',
                'applied_jobs.booking_id as appliedBooking',
                'applied_jobs.chef_id as chefname',
                'menus.menu_name',
                'cuisine.name',
                DB::raw('GROUP_CONCAT(dishes.item_name) as dish_names')
            )
            ->join('applied_jobs', 'invoices.booking_id', '=', 'applied_jobs.booking_id')
            ->join('users', 'applied_jobs.chef_id', '=', 'users.id')
            ->join('menus', 'applied_jobs.menu', '=', 'menus.id')
            ->join('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
            ->leftJoin('dishes', function ($join) {
                $join->on('applied_jobs.chef_id', '=', 'dishes.user_id');
            })
            ->where('invoices.id', $request->id)
            ->groupBy('invoices.booking_id')
            ->first();
        
            return response()->json(['status' => true, 'message' => 'All Invoice fetched successfully', 'data' => $invoice], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
