<?php

namespace App\Http\Controllers;

use App\SumSub;
use App\Tools\SumSubConnector;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebHookController extends Controller
{
    /**
     * Route for webhook sum_sub_user creation
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applicantCreated(Request $request)
    {
       return SumSubConnector::webHookListener($request, 'applicantCreated', 'init', 'created');
    }

    /**
     * Route for webhook sum_sub pending
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applicantPending(Request $request)
    {
        return SumSubConnector::webHookListener($request, 'applicantPending', 'pending', 'pending');
    }

    /**
     * Route for webhook sum_sub on hold
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applicantOnHold(Request $request)
    {
        return SumSubConnector::webHookListener($request, 'applicantOnHold', 'onHold', 'on_hold');
    }

    /**
     * Route for webhook sum_sub_use prechecked
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applicantPrechecked(Request $request)
    {
        return SumSubConnector::webHookListener($request, 'applicantPrechecked', 'queued', 'prechecked');
    }

    /**
     * Final sum_sub webhook
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applicantReviewed(Request $request)
    {
        return SumSubConnector::webHookReviewedListener($request);
    }
}
