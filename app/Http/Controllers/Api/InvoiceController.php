<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\User;
use App\Models\AppliedJobs;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DB;
use App\Models\Menu;
use App\Models\MenuItems;

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
            $invoice = Invoice::select('users.name', 'users.surname', 'invoices.date', 'invoices.booking_id', 'invoices.id as invoiceID', 'invoices.description', 'invoices.invoice_no', 'invoices.amount as invoiceAmount')->join('bookings', 'invoices.booking_id', 'bookings.id')
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
            $invoices = Invoice::select('u2.name as username', 'u2.surname as usersurname', 'u1.name as chefname', 'u1.surname as chefsurname', 'invoices.date', 'invoices.booking_id', 'invoices.id as invoiceID', 'invoices.invoice_no', 'invoices.amount as invoiceAmount', 'u1.phone as chefphoneno', 'invoices.id')
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
                'invoices.id as invoice_id',
                'invoices.booking_id',
                'invoices.description',
                'dishes.id',
                'dish_categories.id as dish_category_id',
                'menus.menu_name',
                'a1.amount',
                'menus.id',
                'u1.name as chefname',
                'u1.surname as chefsurname',
                'u1.email as chefemail',
                'u1.address as chefaddress',
                'u1.phone as chefphone',
                'u2.name as username',
                'u2.surname as usersurname',
                'u2.email as useremail',
                'u2.address as useraddress',
                'u2.phone as userphone',
                DB::raw('GROUP_CONCAT(DISTINCT dishes.item_name) as dish_names'),
                DB::raw('GROUP_CONCAT(DISTINCT dish_categories.dish_category) as dish_category')
            )
                ->join('bookings', 'invoices.booking_id', '=', 'bookings.id')
                ->join('applied_jobs as a1', 'bookings.id', '=', 'a1.booking_id')
                ->join('menus', 'a1.menu', '=', 'menus.id')
                ->join('menu_items', 'menus.id', '=', 'menu_items.menu_id')
                ->join('dishes', function ($join) {
                    $join->on('menu_items.user_id', '=', 'dishes.user_id');
                })
                ->join('dish_categories', function ($join) {
                    $join->on('dishes.dish_category_id', '=', 'dish_categories.id');
                })
                ->join('users as u1', 'invoices.user_id', '=', 'u1.id')
                ->join('users as u2', 'bookings.user_id', '=', 'u2.id')
                ->where('invoices.id', $request->id)
                ->first();
// return $invoice;
            $dishNames = DB::table('dishes')
                ->select('dishes.item_name', 'dishes.type')
                ->join('menu_items', 'dishes.user_id', '=', 'menu_items.user_id')
                ->join('menus', 'menu_items.menu_id', '=', 'menus.id')
                ->join('applied_jobs', 'menus.id', '=', 'applied_jobs.menu')
                ->join('bookings', 'applied_jobs.booking_id', '=', 'bookings.id')
                ->join('invoices', 'bookings.id', '=', 'invoices.booking_id')
                ->where('invoices.id', $request->id)
                // ->distinct()
                ->get();

            return response()->json(['status' => true, 'message' => 'All Invoice fetched successfully', 'data' => $invoice, 'dishNames' => $dishNames], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function single_concierge_invoice(Request $request)
    {
        try {
            $invoice = Invoice::select(
                'invoices.id',
                'invoices.booking_id',
                'dishes.id',
                'dish_categories.id as dish_category_id',
                'menus.menu_name',
                'a1.amount',
                'menus.id',
                'u1.name as chefname',
                'u1.surname as chefsurname',
                'u1.email as chefemail',
                'u1.address as chefaddress',
                'u1.phone as chefphone',
                'u2.name as username',
                'u2.surname as usersurname',
                'u2.email as useremail',
                'u2.address as useraddress',
                'u2.phone as userphone'
            )
                ->join('bookings', 'invoices.booking_id', '=', 'bookings.id')
                ->join('applied_jobs as a1', 'bookings.id', '=', 'a1.booking_id')
                ->join('menus', 'a1.menu', '=', 'menus.id')
                ->join('menu_items', 'menus.id', '=', 'menu_items.menu_id')
                ->join('dishes', function ($join) {
                    $join->on('menu_items.user_id', '=', 'dishes.user_id');
                })
                ->join('dish_categories', function ($join) {
                    $join->on('dishes.dish_category_id', '=', 'dish_categories.id');
                })
                ->join('users as u1', 'invoices.user_id', '=', 'u1.id')
                ->join('users as u2', 'bookings.user_id', '=', 'u2.id')
                ->where('u1.status', '!=', 'deleted')
                ->where('invoices.id', $request->id)
                ->first();

            $dishNames = DB::table('dishes')
                ->select('dishes.item_name', 'dishes.type')
                ->join('menu_items', 'dishes.user_id', '=', 'menu_items.user_id')
                ->join('menus', 'menu_items.menu_id', '=', 'menus.id')
                ->join('applied_jobs', 'menus.id', '=', 'applied_jobs.menu')
                ->join('bookings', 'applied_jobs.booking_id', '=', 'bookings.id')
                ->join('invoices', 'bookings.id', '=', 'invoices.booking_id')
                ->where('invoices.id', $request->id)
                // ->distinct()
                ->get();

            // $dishNames = $dishNames->pluck('item_name', 'type')->toArray();


            return response()->json(['status' => true, 'message' => 'All Invoice fetched successfully', 'data' => $invoice, 'dishNames' => $dishNames], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
