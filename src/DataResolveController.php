<?php
namespace Isofman\LaravelExpressAPI;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class DataResolveController
 * @package Isofman\LaravelExpressAPI
 */
class DataResolveController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function index()
    {
        $dataResolver = new DataResolver();
        $result = $dataResolver->resolve();
        return response()->json($result);
    }
}
