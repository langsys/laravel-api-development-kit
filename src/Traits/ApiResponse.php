<?php

namespace Langsys\ApiKit\Traits;

use Langsys\ApiKit\Enums\HttpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

trait ApiResponse
{
    use OrderableCollections, FilterableCollection, CollectionPaginator;

    private $simpleResponse = [
        'status' => true,
    ];

    private $successResponse = [
        'status' => true,
        'page' => 1,
        'page_count' => 1,
        'records_per_page' => 1,
        'total_records' => 1,
        'data' => [],
        'errors' => [],
    ];

    private $errorResponse = [
        'status' => false,
        'data' => [],
        'error' => '',
    ];

    public function response($data, $code)
    {
        return response()->json($data, $code)->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function resourceResponse($data, $additionalData = [], $code = 200)
    {
        if (!empty($additionalData)) {
            $this->simpleResponse = [...$this->simpleResponse, ...$additionalData];
        }

        $this->simpleResponse['data'] = $data;

        return $this->response($this->simpleResponse, $code);
    }

    protected function resourceListResponse(Collection $resourceCollection, array $additionalData = [], int $code = 200, bool $paginated = true, bool $ordered = true, bool $filtered = true)
    {
        $resourceClass = $this->inferResourceClass($resourceCollection);

        if ($filtered) {
            $resourceCollection = $this->applyFiltering($resourceCollection, $resourceClass);
        }

        if ($ordered) {
            $resourceCollection = $this->applyOrdering($resourceCollection, $resourceClass);
        }

        if ($paginated) {
            $response = $this->applyPagination($resourceCollection, $additionalData);
        } else {
            $response = $this->simpleResponse = [...$this->simpleResponse, ...$additionalData];
            $response['data'] = $resourceCollection;
        }

        return $this->response($response, $code);
    }

    protected function successResponse($data = false, $code = 200)
    {
        if ($data) {
            $this->simpleResponse['data'] = $data;
        }

        return $this->response($this->simpleResponse, $code);
    }

    protected function errorResponse($error, $code)
    {
        if (is_array($error)) {
            $this->errorResponse['error'] = $error['message'] ?? (is_array($error) ? reset($error) : $error);
            if (config('app.debug')) {
                $this->errorResponse['debug'] = $error;
            }
        } else {
            $this->errorResponse['error'] = $error;
        }

        return $this->response($this->errorResponse, $code);
    }

    protected function noContentResponse(): JsonResponse
    {
        return $this->response([], HttpCode::NO_CONTENT->value);
    }

    private function inferResourceClass(Collection $collection): ?string
    {
        $first = $collection->first();
        if (!$first) {
            return null;
        }

        return get_class($first);
    }
}
