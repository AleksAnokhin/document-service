<?php

namespace App\Http\Controllers\Admin;

use App\BusinessData;
use App\BusinessUploads as BusinessUploads;
use App\DeclinesDescription;
use App\PersonUploads;
use App\Users;
use App\UsersToCheck;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class StatusesController extends Controller
{
    /**
     * Send final approve
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function finalApprove(Request $request)
    {
        $request->validate([
            'user_id' => 'required|numeric',
        ]);
        $user_id = $request->input('user_id');

        try {
            $user = Users::findOrFail($user_id);
        } catch(ModelNotFoundException $e) {
            return response()->json(['error' => null, 'status' => 'no such user'],400);
        }
        if($user->type === 'business') {
            $business_uploads = BusinessUploads::where('user_id', $user_id)->get();
            foreach($business_uploads as $file) {
                $file->status_id = 7;
                if(!$file->save()) return response()->json(['status' => 'error', 'error' => 'Cant save file status'],400);
            }
        } else if($user->type === 'person' || $user->type === 'merchant') {
            $person_uploads= PersonUploads::where('user_id', $user_id)->get();
            foreach($person_uploads as $file) {
                $file->status_id = 7;
                if(!$file->save()) return response()->json(['status' => 'error','error' => 'Cant save file status'],400);
            }
        }
        try {
            $user_to_check = UsersToCheck::where('user_id',$user_id)->firstOrFail();
        }catch(ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'error' => 'Cant find user to check and delete him'],400);
        }
       if(!$user_to_check->delete()) return response()->json(['status' => 'error', 'error' => 'cant delete users to check'],400);

       return response()->json(['error' => null, 'status' => 'ok'],200);

    }

    /**
     * Declines any client docs
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function declineDoc(Request $request)
    {
        $request->validate([
            'user_id' => 'required|numeric',
            'document' => 'required|string',
            'decline_id' => 'required|numeric',
            'description' => 'required|string'
        ]);
        $vars = [];
        $vars['user_id'] = $request->input('user_id');
        $vars['document'] = $request->input('document');
        $vars['decline_id'] = $request->input('decline_id');
        $vars['description'] = $request->input('description');

        try {
            $user = Users::findOrFail($vars['user_id']);
        } catch(ModelNotFoundException $e) {
            return response()->json(['error' => 'No such user', 'status' => 'error'],400);
        }
        if($user->type === 'business') {

          $business_ps = $this->handle($user, 'Business',$vars);
          if(!is_null($business_ps['error'])) return response()->json(['error' => $business_ps['error'], 'status' => 'error'],400);
          return response()->json(['error' => null, 'status' => 'ok'],200);

        } else if ($user->type === 'person' || $user->type === 'merchant') {
            $person_ps = $this->handle($user, 'Person', $vars);
            if(!is_null($person_ps['error'])) return response()->json(['error' => $person_ps['error'], 'status' => 'error'],400);
            return response()->json(['error' => null, 'status' => 'ok'],200);
        } else {
            return response()->json(['error' => 'Undefined users type', 'status' => 'error'],200);
        }
    }

    /**
     * Handle decline process
     * @param Model $user
     * @param string $datatype
     * @param array $vars
     * @return array
     */
    protected function handle(Model $user, string $datatype, array $vars)
    {
        $data = strtolower($datatype) . '_data';

        if(empty($user->$data->document_set)) return ['error' => 'Client has not uploaded any document', 'status' => 'error'];
        $document_set = unserialize($user->$data->document_set);
        if(!in_array($vars['document'],$document_set)) return ['error' => 'Client does not have such document', 'status' => 'error'];

        try {
            $decline = DeclinesDescription::where(['user_id' => $vars['user_id'], 'filename' => $vars['document']])->firstOrFail();
            $decline->decline_id = $vars['decline_id'];
            $decline->description = $vars['description'];
            if(!$decline->save()) return ['error' => 'Cant update client decline data'];

        }catch(ModelNotFoundException $exception) {

            $decline = new DeclinesDescription([
                'user_id' => $vars['user_id'],
                'filename' =>$vars['document'],
                'decline_id' => $vars['decline_id'],
                'description' => $vars['description']
            ]);

            if(!$decline->save()) return ['error' => 'Cant create new decline data for the client'];
        }
        $UploadClass = 'App\\' . $datatype . 'Uploads';
        try {
            $client_doc = $UploadClass::where(['user_id'=> $vars['user_id'],'name' => $vars['document'] ])->firstOrFail();
        } catch(ModelNotFoundException $exception) {
            return ['error' => 'Cant find clients document and update the  status', 'status' => 'error'];
        }

        $client_doc->status_id = 10;

        if(!$client_doc->save()) return ['error' => 'cant update the status of the document'];

        return ['error' => null, 'status' => 'ok'];
    }

}
