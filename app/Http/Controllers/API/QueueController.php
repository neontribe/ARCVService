<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;;
use Imtigger\LaravelJobStatus\JobStatus;
use RuntimeException;

class QueueController extends Controller
{
    /**
     * @param JobStatus $jobStatus
     * @return mixed
     */
    public function show(JobStatus $jobStatus): mixed
    {
        // get the job type
        $jobType = $jobStatus->type;
        $state = $jobStatus->status;
        if (method_exists($jobType, $state)) {
            return call_user_func("$jobType::$state", $jobStatus);
        }
        throw new RuntimeException("$jobType does not have a method for $state");
    }
}