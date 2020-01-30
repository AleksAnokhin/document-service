<?php

namespace App\Http\Controllers;

use Bschmitt\Amqp\Facades\Amqp as Amqp;
use Illuminate\Http\Request;
use App\Business;
use Illuminate\Support\Facades\Validator as Validator;

class BusinessController extends Controller
{

    /**
     * Create business user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createBusiness(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'name' => 'required|string',
            'nalog_num' => 'required|string',
            'reg_num' => 'required|integer',
            'country_legal' => 'required|integer',
            'biz_profile' => 'required|string',
            'city_legal' => 'required|string',
            'street_legal' => 'required|string',
            'zip_legal' => 'required|string',
            'country_actual' => 'required|string',
            'city_actual' => 'required|string',
            'street_actual' => 'required|string',
            'zip_actual' => 'required|string',
            'ben1_name' => 'required|string',
            'ben1_surname' => 'required|string',
            'dir_name' => 'required|string',
            'dir_surname' => 'required|string',
            'tel_prefix' => 'required|string',
            'tel_time' => 'required|string|min:6',
            'pep' => 'required|integer',
            'us' => 'required|integer',
        ]);
        try {
            $business = new Business([
                'user_id' => $request->user_id,
                'name' => $request->name,
                'reg_num' => $request->reg_num,
                'biz_profile' => $request->biz_profile,
                'country_legal' => $request->country_legal,
                'city_legal' => $request->city_legal,
                'street_legal' => $request->street_legal,
                'zip_legal' => $request->zip_legal,
                'country_actual' => $request->country_actual,
                'city_actual' => $request->city_actual,
                'zip_actual' => $request->zip_actual,
                'ben1_name' => $request->ben1_name,
                'ben1_surname' => $request->ben1_surname,
                'dir_name' => $request->dir_name,
                'dir_surname' => $request->dir_surname,
                'tel_prefix' => $request->tel_prefix,
                'tel_time' => $request->tel_time,
                'pep' => $request->pep,
                'us'  => $request->us
            ]);
        } catch(\Error $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }

        if($business->save()) {

            //sending message to rabbitmq

            Amqp::publish('documentor_pipeline', 'New business has been created' , ['queue' => 'documentor_pipeline']);

            return response()->json([
                'message' => 'Successfully created business!'
            ], 201);
        }
        return response()->json(['message'=> 'Business has not been created'],400 );
    }

    /**
     * Get all business
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(Request $request)
    {
        return response()->json(Business::all());
    }

    /**
     * Get precise business by id
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBusiness(int $id)
    {
        return response()->json(Business::find($id));
    }

    /**
     * Update precise business
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBusiness(Request $request, int $id)
    {
        $business = Business::find($id);
        if(!empty($business)) {
            $data = $request->all();
            foreach ($data as $key=>$val) {
                if(isset($business->$key)) {
                    try {
                        $business->$key = $val;
                    }catch (\Error $e) {
                        return response()->json(['error'=> $e->getMessage()],400);
                    }
                }
            }
            if($business->save())  return response()->json(['message'=> 'Business has been updated successfully'],200);

        }
        return response()->json(['message'=> 'Business not found'],404 );
    }

    /**
     * Delete precise business
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBusiness(int $id)
    {
        $business = Business::find($id);
        if((empty($business))) return response()->json(['message'=> 'Business not found'],400 );
        if($business->delete()) return response()->json(['message'=> 'Business has been deleted successfully'],200);
        return response()->json(['message'=> 'Business has not been deleted successfully'],400);
    }




}
