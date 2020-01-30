<?php

namespace App\Http\Middleware;

use App\Users;
use Closure;
use phpDocumentor\Reflection\Types\Object_;

class ValidatePersonUpload
{

    protected $valid_extensions = ['pdf','jpg','png','jpeg'];
    protected $valid_size = 1000000;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user_id = $request->user()->id;
        $user = Users::find($user_id);


        $sum_sub_id = empty($user->sumsub_entity) ? null :  $user->sumsub_entity->sum_sub_id;

        //check if user has already been created and has uploaded documents

        if(!empty($sum_sub_id)) {
            //todo create validation rules for this case
            return $next($request);
        }



        $request->validate([
            'type_passport' => 'required|string',
            'type_bank'     => 'required|string',
            'kyc_input'     => 'required|string',
        ]);

        if(!$request->hasFile('passport')) return response()->json(['message'=>'Request does not contain passport'],400);
        if(!$request->hasFile('selfie')) return response()->json(['message'=>'Request does not contain selfie'],400);
        if(!$request->hasFile('address')) return response()->json(['message'=>'Request does not contain address'],400);
        if(!$request->hasFile('taxpayer_form')) return response()->json(['message'=>'Request does not contain taxpayer_form'],400);

        foreach($request->allFiles() as $file) {
            if(!$this->validateFile($file)) return response()->json(['error'=> $file->getClientOriginalName() .' has not passed validation' ],400);
        }
        return $next($request);
    }

    /**
     * Validate file-size and file-extension
     * @param object $file
     */
    protected function validateFile(object $file)
    {
        if($file->getSize() > $this->valid_size)  return false;
        $ext = $file->getClientOriginalExtension();
        if(!in_array($ext, $this->valid_extensions)) return false;
        return true;
    }


}
