<?php

namespace App\Http\Controllers;

use App\Person as Person;
use Bschmitt\Amqp\Facades\Amqp;
use Illuminate\Http\Request;

class PersonController extends Controller
{


    /**
     * Create new Person
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPerson(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'name' => 'required|string',
            'surname' => 'required|string',
            'country_id' => 'required|integer',
            'city' => 'required|string',
            'street' => 'required|string',
            'zip' => 'required|string',
            'tel_prefix' => 'required|string',
            'tel_time' => 'required|string|min:6',
            'pep' => 'required|integer',
            'us' => 'required|integer',
        ]);
        $person = new Person([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'surname' => $request->surname,
            'country_id' => $request->country_id,
            'city' => $request->city,
            'street' => $request->street,
            'zip' => $request->zip,
            'tel_prefix' => $request->tel_prefix,
            'tel_time' => $request->tel_time,
            'pep' => $request->pep,
            'us'  => $request->us
        ]);
        if($person->save()) {

            //sending message to rabbitmq

            Amqp::publish('documentor_pipline', 'New person has been created' , ['queue' => 'documentor_pipline']);

            return response()->json([
                'message' => 'Successfully created person!'
            ], 201);
        }
        return response()->json(['message'=> 'Person has not been created'],400 );

    }

    /**
     * Get all persons
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(Request $request)
    {
        return response()->json(Person::all());
    }

    /**
     * Returns precise person
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerson(int $id)
    {
        return response()->json(Person::find($id));
    }

    /**
     * Update precise person
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePerson(Request $request, int $id)
    {
        $person = Person::find($id);
        if(!empty($person)) {
            $data = $request->all();
            foreach ($data as $key=>$val) {
                if(isset($person->$key)) $person->$key = $val;
            }
            if($person->save())  return response()->json(['message'=> 'Person has been updated successfully'],200);
        }
        return response()->json(['message'=> 'Person not found'],404 );
    }

    /**
     * Delete precise person
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePerson(int $id)
    {
        $person = Person::find($id);
        if((empty($person))) return response()->json(['message'=> 'Person not found'],400 );
        if($person->delete()) return response()->json(['message'=> 'Person has been deleted successfully'],200);
        return response()->json(['message'=> 'Person has not been deleted successfully'],400);
    }


}
