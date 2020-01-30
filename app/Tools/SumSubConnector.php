<?php


namespace App\Tools;


use App\BusinessUploads;
use App\Facades\DocumentorEncryptor;
use App\heritage_models\Country;
use App\heritage_models\User;
use App\PersonUploads;
use App\SumSub;
use App\Users;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Contracts\DocumentValidator as Validator;

class SumSubConnector implements Validator
{

    /**
     * Holds all process of creating new sum_sub_person
     * @param string|null $user_id
     * @param array $document_set
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function initPerson(string $user_id=null, array $document_set)
    {
        //getting essential data

        if(is_null($user_id) || !is_numeric($user_id)) {
            return ['error'=>'incorrect user id', 'status' => 'error'];
        }

        try {
            $documentor_user = Users::findOrfail($user_id);
        } catch (ModelNotFoundException $e) {
            return ['error'=> 'documentor_user not found - ' . $e->getMessage()];
        }

        $email = $documentor_user->email;

        try {
            $heritage_user = User::where('email',$email)->firstOrFail();

        }catch(ModelNotFoundException $e) {
            return ['error'=>'heritage_user not found - '. $e->getMessage()];
        }

        if(!in_array($heritage_user->language, getLang())) $heritage_user->language = config('heritage_language');
        $country_id = $documentor_user->person_entity->country_id;
        if(is_null($country_id)) return ['error'=>'person association has not been found'];
        $country = Country::where('id',$country_id)->first();

        $post_data = [
            "externalUserId" => $documentor_user->id,
            "lang" => $heritage_user->language,
            "info" => [
                "country" => $country->alpha3,
                "phone"   => '+' . $documentor_user->tel_prefix . $documentor_user->tel
            ],
            "requiredIdDocs" => [
                "country" => $country->alpha3
            ,
            "docSets" => [
                ["idDocSetType" => "IDENTITY", "types" => ["PASSPORT", "ID_CARD"], "subTypes" => ["FRONT_SIDE", "BACK_SIDE"]],
                ["idDocSetType" => "SELFIE", "types" => ["SELFIE"], "subTypes" => null],
                ["idDocSetType" => "PROOF_OF_RESIDENCE", "types" => ["UTILITY_BILL"], "subTypes" => null]
            ]
        ]];

        Log::info('Creating new sum_sub client',['post_array' => $post_data]);

        //creating new sum_sub_user

        $ps_new_user = self::addUser($post_data, $user_id);
        if(!is_null($ps_new_user['error'])) return ['error' => 'Error with creation of new sum_sub_user - ' . $ps_new_user['error']];

        //working with uploaded files

        $token = $ps_new_user['token'];
        $ps_upload_to_sum_sub = self::uploadToSumSub($user_id,$document_set, $documentor_user, $heritage_user, $token);

        if(!is_null($ps_upload_to_sum_sub['error'])) return ['error' => 'Error when uploading new files for sum_sub_user - ' . $ps_upload_to_sum_sub['error']];

        return ['error' => null, 'response' => $ps_upload_to_sum_sub];


    }


    /**
     * Create new sum_sub_user
     * @param array $data
     * @param int $user_id
     * @return array|\Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected static function addUser(array $data, int $user_id)
    {
        $auth = self::authenticate();
        if(!is_null($auth['error'])) return ['error' => 'authentication failed'];
        $token = $auth['data']->payload;
        if(!$token) return ['error' => 'authentication failed'];
        Log::info('Getting new user token',['token'=>$token]);
        $conf['url'] = env('SUM_SUB_URL'). 'resources/applicants';
        $new_user = DocumentorClient::postBearer($conf,$data,$token);
         if(!is_null($new_user['error'])) return ['error' => 'cant create new sum_sub_user'];
        Log::info('New sum_sub_user has been created',['new user' => $new_user['data']]);
        //create sum_sub instance
        $sum_sub_user = new SumSub([
            'user_id' => $user_id,
            'sum_sub_external_id' => $new_user['data']->externalUserId,
            'sum_sub_id' => $new_user['data']->id
        ]);
        if(!$sum_sub_user->save()) return ['error' => 'Failed to create new sum_sub_user'];
        return ['error' => null, 'user' => $new_user, 'token' => $token];
    }

    /**
     * Getting bearer token from sum_sub
     * @param array $data
     * @return array
     */
    protected static function authenticate()
    {
        $conf['username'] = env('SUM_SUB_USER');
        $conf['password'] = env('SUM_SUB_PASSWORD');
        $conf['url'] = env('SUM_SUB_URL'). 'resources/auth/login';
        return DocumentorClient::postBasic($conf);
    }


    /**
     * Methods that runs uploading process depending on the scenarion or document_set contents
     * @param int $user_id
     * @param array $document_set
     * @param Model $documentor_user
     * @param Model $heritage_user
     * @param string $token
     * @return array
     */
    protected static function uploadToSumSub(int $user_id, array $document_set, Model $documentor_user, Model $heritage_user, string $token)
    {

            if(in_array('passport',$document_set) && in_array('card', $document_set)) {
               $first_case = self::firstCase($user_id, $document_set, $documentor_user,$heritage_user,$token);
               if(!is_null($first_case['error'])) return $first_case;

            } elseif(in_array('passport', $document_set)) {

                $second_case =  self::secondCase($user_id, $document_set, $documentor_user,$heritage_user,$token);
                if(!is_null($second_case['error'])) return $second_case;

            } elseif(in_array('selfie', $document_set)) {
                $third_case =  self::thirdCase($user_id, $document_set, $documentor_user,$heritage_user,$token);
                if(!is_null($third_case['error'])) return $third_case;

            } elseif(in_array('dopdoc', $document_set)) {
                $fourth_case =  self::fourthCase($user_id, $document_set, $documentor_user,$heritage_user,$token);
                if(!is_null($fourth_case['error'])) return $fourth_case;

            } elseif(in_array('address',$document_set) && in_array('address_back', $document_set)) {
                $fifth_case =  self::fifthCase($user_id, $document_set, $documentor_user,$heritage_user,$token);
                if(!is_null($fifth_case['error'])) return $fifth_case;
            } elseif(in_array('address',$document_set)) {
                $sixth_case =  self::sixthCase($user_id, $document_set, $documentor_user,$heritage_user,$token);
                if(!is_null($sixth_case['error'])) return $sixth_case;
            }

            if(in_array('passport',$document_set) || in_array('selfie', $document_set) || in_array('address', $document_set)) {


                $sum_sub_id = (Users::find($user_id))->sumsub_entity->sum_sub_id;
                $conf['url'] = env('SUM_SUB_URL'). 'resources/applicants/' . $sum_sub_id . '/status/pending';
                $data = [];
                $ps_check_user_data = DocumentorClient::postBearer($conf,$data,$token);
                if(!is_null($ps_check_user_data['error'])) return $ps_check_user_data;
            }

            return ['error' => null, 'status' => 'ok'];
    }


    //****VARIANTS OF USER DOCUMENT SET***//

    /**
     * Case when user passport'suploads consists of two pages
     * @param int $user_id
     * @param array $document_set
     * @param Model $documentor_user
     * @param Model $heritage_user
     * @param string $token
     * @return array
     */
    protected static function firstCase(int $user_id, array $document_set, Model $documentor_user, Model $heritage_user, string $token)
    {
        //working with passport
        $params = [
          'doc' => 'passport',
          'type' => 'ID_CARD',
          'id_docs_sub_type' => 'FRONT_SIDE'
        ];
        $passport = self::uploadDocType($params, $user_id, $document_set, $documentor_user, $heritage_user, $token);
        if(!is_null($passport['error'])) return $passport;

        //working with card

        $params = [
          'doc' => 'card',
            'type' => 'ID_CARD',
            'id_docs_sub_type' => 'BACK_SIDE'
        ];

        $card = self::uploadDocType($params, $user_id, $document_set, $documentor_user, $heritage_user, $token);
        if(!is_null($card['error'])) return $card;

        return ['error' => null, 'status' => 'ok'];
    }

    /**
     * Case when passport's uploads consists of 1 page
     * @param int $user_id
     * @param array $document_set
     * @param Model $documentor_user
     * @param Model $heritage_user
     * @param string $token
     * @return array
     */
    protected static function secondCase(int $user_id, array $document_set, Model $documentor_user, Model $heritage_user, string $token)
    {
        //working with passport

        $params = [
            'doc' => 'passport',
            'type' => 'PASSPORT',
            'id_docs_sub_type' => null
        ];

        $passport = self::uploadDocType($params, $user_id, $document_set, $documentor_user, $heritage_user, $token);
        if(!is_null($passport['error'])) return $passport;
        return ['error' => null, 'status' => 'ok'];
    }

    /**
     * Case when user uploads selfie
     * @param int $user_id
     * @param array $document_set
     * @param Model $documentor_user
     * @param Model $heritage_user
     * @param string $token
     * @return array
     */
    protected static function thirdCase(int $user_id, array $document_set, Model $documentor_user, Model $heritage_user, string $token)
    {
        $params = [
            'doc' => 'selfie',
            'type' => 'SELFIE',
            'id_docs_sub_type' => null
        ];

        $selfie = self::uploadDocType($params, $user_id, $document_set, $documentor_user, $heritage_user, $token);
        if(!is_null($selfie['error'])) return $selfie;
        return ['error' => null, 'status' => 'ok'];

    }

    /**
     * Case when use uploads additional documents
     * @param int $user_id
     * @param array $document_set
     * @param Model $documentor_user
     * @param Model $heritage_user
     * @param string $token
     * @return array
     */
    protected static function fourthCase(int $user_id, array $document_set, Model $documentor_user, Model $heritage_user, string $token)
    {
        $params = [
            'doc' => 'dopdoc',
            'type' => 'OTHER',
            'id_docs_sub_type' => null
        ];
        $dopdoc = self::uploadDocType($params, $user_id, $document_set, $documentor_user, $heritage_user, $token);
        if(!is_null($dopdoc['error'])) return $dopdoc;
        return ['error' => null, 'status' => 'ok'];
    }

    /**
     * Case when user address'es uploads consists of two pages
     * @param int $user_id
     * @param array $document_set
     * @param Model $documentor_user
     * @param Model $heritage_user
     * @param string $token
     * @return array
     */
    protected static function fifthCase(int $user_id, array $document_set, Model $documentor_user, Model $heritage_user, string $token)
    {
        //if address has double pages

        $params = [
            'doc' => 'address',
            'type' => 'UTILITY_BILL',
            'id_docs_sub_type' => 'FRONT_SIDE'
        ];

        $address = self::uploadDocType($params, $user_id, $document_set, $documentor_user, $heritage_user, $token);
        if(!is_null($address['error'])) return $address;

        $params = [
            'doc' => 'address_back',
            'type' => 'UTILITY_BILL',
            'id_docs_sub_type' => 'BACK _SIDE'
        ];
        $address_back = self::uploadDocType($params, $user_id, $document_set, $documentor_user, $heritage_user, $token);
        if(!is_null($address_back['error'])) return $address_back;

        return ['error' => null, 'status' => 'ok'];

    }

    /**
     * Case when user's address document consists of one document
     * @param int $user_id
     * @param array $document_set
     * @param Model $documentor_user
     * @param Model $heritage_user
     * @param string $token
     * @return array
     */
    protected static function sixthCase(int $user_id, array $document_set, Model $documentor_user, Model $heritage_user, string $token)
    {
        $params = [
            'doc' => 'address',
            'type' => 'UTILITY_BILL',
            'id_docs_sub_type' => null
        ];
        $address = self::uploadDocType($params, $user_id, $document_set, $documentor_user, $heritage_user, $token);
        if(!is_null($address['error'])) return $address;

        return ['error' => null, 'status' => 'ok'];

    }

    /**
     * Method which uploads any user document to sum_sub
     * @param array $params
     * @param int $user_id
     * @param array $document_set
     * @param Model $documentor_user
     * @param Model $heritage_user
     * @param string $token
     * @return array
     */
    protected static function uploadDocType(array $params, int $user_id, array $document_set, Model $documentor_user, Model $heritage_user, string $token)
    {
        try {
            $person_doc = PersonUploads::where(['user_id' => $user_id,'name' => $params['doc']])->firstOrFail();
        } catch(ModelNotFoundException $e) {
            return ['error' => $params['doc'] .  ' file has not been found', 'status' => 'error'];
        }

        $path = $person_doc->path;

        $encryptedContent = Storage::get($path);
        $decriptedContent = DocumentorEncryptor::decrypt($encryptedContent,$documentor_user->uid);

        $random_name = Str::random(6);
        $upload_path = 'tmp/' . $documentor_user->uid . '/' . $params['doc'] . '/' . $random_name . '.' . $person_doc->type;
        $tmp_file_store = Storage::put($upload_path,$decriptedContent);
        $tmp_file = Storage::get($upload_path);


        $us = $documentor_user->sumsub_entity->sum_sub_id;
        $type = $params['type'];
        $idDocSubType = $params['id_docs_sub_type'];
        try {
            $country = Country::where('id', $heritage_user->country_id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return ['error' => 'Failed to find user country - ' . $e->getMessage()];
        }

        $post_data = [
            "metadata" => json_encode(["idDocType" => $type, "idDocSubType" => $idDocSubType, "country" =>$country->alpha3]), 'content' => $tmp_file
        ];

        $url = env('SUM_SUB_URL'). 'resources/applicants/' . $us . '/info/idDoc';
        $result = DocumentorClient::postCurl($token, $post_data,$url);

        if(!is_null($result['error'])) {

            return ['error' => 'Error connection with api sum_sub while uploading file - ' . $result['error'], 'status' => 'error'];
        }

        Storage::deleteDirectory('tmp/' . $documentor_user->uid);

        return ['error' => null, 'status' => 'ok'];
    }

    public static function repeatedPersonUploads(string $user_id=null, array $document_set)
    {
        if(in_array('selfie',$document_set)) {
            $post_data = [
                "includedCountries" => null,
                "excludedCountries" => null,
                "docSets" => [["idDocSetType" => "IDENTITY", "types" => ["PASSPORT", "ID_CARD"], "subTypes" => ["FRONT_SIDE", "BACK_SIDE"]],
                    ["idDocSetType" => "SELFIE", "types" => ["SELFIE"], "subTypes" => null],
                    ["idDocSetType" => "PROOF_OF_RESIDENCE", "types" => ["UTILITY_BILL"], "subTypes" => null],
                ]
            ];
            $auth = self::authenticate();
            if(!is_null($auth['error'])) return ['error' => 'authentication failed'];
            $token = $auth['data']->payload;
            if(!$token) return ['error' => 'authentication failed'];
            $sum_sub_id = (Users::find($user_id))->sumsub_entity->sum_sub_id;
            $conf['url'] = env('SUM_SUB_URL'). 'resources/applicants/' . $sum_sub_id . '/requiredIdDocs';
            $ps_update_user = DocumentorClient::postBearer($conf,$post_data,$token);

            if(!is_null($ps_update_user['error'])) return ['error' => 'cant update data of sum_sub_user'];
        }
        //upload process
        try {
            $documentor_user = Users::findOrfail($user_id);
        } catch (ModelNotFoundException $e) {
            return ['error'=> 'documentor_user not found - ' . $e->getMessage()];
        }

        $email = $documentor_user->email;

        try {
            $heritage_user = User::where('email',$email)->firstOrFail();

        }catch(ModelNotFoundException $e) {
            return ['error'=>'heritage_user not found - '. $e->getMessage()];
        }

        $ps_upload_to_sum_sub = self::uploadToSumSub($user_id,$document_set, $documentor_user, $heritage_user, $token);

        if(!is_null($ps_upload_to_sum_sub['error'])) return ['error' => 'Error when uploading new files for sum_sub_user - ' . $ps_upload_to_sum_sub['error']];
        return ['error' => null, 'response' => $ps_upload_to_sum_sub];
    }



    //********************************** WEB_HOOKS_LISTENER **********************************//


    public static function webHookListener(Request $request, string $type, string $reviewStatus, string $field)
    {
        $sum_sub_id = $request->input('applicantId');
        try {

            $sum_sub_user = SumSub::where('sum_sub_id', $sum_sub_id)->firstOrFail();

        } catch(ModelNotFoundException $exception) {
            Log::info('Sum_sub has sent data with applicant who does not exist in documentor database', ['applicant_id' => $request->input('applicantId')]);
            return response()->json(['status' => 'error', 'message' => 'Applicant has not been found'],400);
        }

        if($request->input('type') !== $type) {
            Log::info('Sum_sub has sent data with wrong data type', ['applicant_id' => $request->input('applicantId') , 'type' => $request->input('type')]);
            return response()->json(['status' => 'error', 'message' => 'Wrong applicant type'],400);
        }

        if($request->input('reviewStatus') !== $reviewStatus) {
            Log::info('Sum_sub has sent data with wrong reviewStatus', ['applicant_id' => $request->input('applicantId') , 'reviewStatus' => $request->input('reviewStatus')]);
            return response()->json(['status' => 'error', 'message' => 'Wrong reviewStatus'],400);
        }

        $sum_sub_user->$field = 1;

        if(!$sum_sub_user->save()) {
            Log::info('Error while saving sum_sub_status to documentor_database', ['applicant_id' => $request->input('applicantId')]);
            return response()->json(['status' => 'error', 'message' => 'Error while saving status to database'],400);
        }
        //setting new statuses for uploaded documents

        $status_id = statusTransformer($field);
        $user_id = $sum_sub_user->user_id;
        try {
            $documentor_user = Users::findOrFail($user_id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => 'Internal error'],400);
        }

        if($documentor_user->type === 'business') {
            $business_uploads = BusinessUploads::where('user_id', $user_id)->get();
            foreach($business_uploads as $file) {
                $file->status_id = $status_id;
                if(!$file->save()) return response()->json(['status' => 'error', 'message' => 'Internal error'],400);
            }
        } else if($documentor_user->type === 'person' || $documentor_user->type === 'merchant') {
            $person_uploads= PersonUploads::where('user_id', $user_id)->get();
            foreach($person_uploads as $file) {
                $file->status_id = $status_id;
                if(!$file->save()) return response()->json(['status' => 'error','message' => 'Internal error'],400);
            }
        }



        return response()->json(['status' => 'ok', 'message' => 'Success!'],200);

    }

    public static function webHookReviewedListener(Request $request)
    {
        $sum_sub_id = $request->input('applicantId');
        try {

            $sum_sub_user = SumSub::where('sum_sub_id', $sum_sub_id)->firstOrFail();

        } catch(ModelNotFoundException $exception) {
            Log::info('Sum_sub has sent data with applicant who does not exist in documentor database', ['applicant_id' => $request->input('applicantId')]);
            return response()->json(['status' => 'error', 'message' => 'Applicant has not been found'],400);
        }

        if($request->input('type') !== 'applicantReviewed') {
            Log::info('Sum_sub has sent data with wrong data type', ['applicant_id' => $request->input('applicantId') , 'type' => $request->input('type')]);
            return response()->json(['status' => 'error', 'message' => 'Wrong applicant type'],400);
        }

        if($request->input('reviewStatus') !== 'completed') {
            Log::info('Sum_sub has sent data with wrong reviewStatus', ['applicant_id' => $request->input('applicantId') , 'reviewStatus' => $request->input('reviewStatus')]);
            return response()->json(['status' => 'error', 'message' => 'Wrong reviewStatus'],400);
        }
        $sum_sub_user->reviewing = 1;
        $sum_sub_user->moderator_comment = $request->input('reviewResult')['moderationComment'];
        $sum_sub_user->client_comment = $request->input('reviewResult')['clientComment'];
        $sum_sub_user->reviewed_answer = $request->input('reviewResult')['reviewAnswer'];
        if($request->input('reviewAnswer') == "RED") $sum_sub_user->reviewd_rejected_type = $request->input('reviewResult')['reviewRejectType'];

        if(!$sum_sub_user->save()) {
            Log::info('Error while saving sum_sub_status to documentor_database', ['applicant_id' => $request->input('applicantId')]);
            return reponse()->json(['status' => 'error', 'message' => 'Error while saving status to database'],400);
        }

        $user_id = $sum_sub_user->user_id;
        try {
            $documentor_user = Users::findOrFail($user_id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => 'Internal error'],400);
        }

        if($documentor_user->type === 'business') {
            $business_uploads = BusinessUploads::where('user_id', $user_id)->get();
            foreach($business_uploads as $file) {
                $file->status_id = 4;
                if(!$file->save()) return response()->json(['status' => 'error', 'message' => 'Internal error'],400);
            }
        } else if($documentor_user->type === 'person' || $documentor_user->type === 'merchant') {
            $person_uploads= PersonUploads::where('user_id', $user_id)->get();
            foreach($person_uploads as $file) {
                $file->status_id = 4;
                if(!$file->save()) return response()->json(['status' => 'error','message' => 'Internal error'],400);
            }
        }



        return response()->json(['status' => 'ok', 'message' => 'Success!'],200);


    }

}


