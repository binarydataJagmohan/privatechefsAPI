<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function save_contact(Request $request){
        try {
            
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required',
                'phone_no' => 'required',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $contact = new Contact();
            $contact->name = $request->input('name');
            $contact->email = $request->input('email');
            $contact->phone_no = $request->input('phone_no');
            $contact->message = $request->input('message');
            $contact->save();

            return response()->json([
                'status' => true,
                'message' => 'Contact saved successfully.',
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
