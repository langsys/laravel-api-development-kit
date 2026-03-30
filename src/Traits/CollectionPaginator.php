<?php

namespace Langsys\ApiKit\Traits;

use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

trait CollectionPaginator
{
    public function applyPagination(Collection $collection, array $additionalResponseData = [])
    {
        $validation = [
            'page' => 'sometimes|numeric|min:1',
            'records_per_page' => 'sometimes|numeric|min:1',
        ];
        $request = request();

        Validator::make($request->all(), $validation)->validate();

        if (!$request->page) {
            $response = ['status' => true];

            if (!empty($additionalResponseData)) {
                $response = array_merge($response, $additionalResponseData);
            }
            $response['data'] = $collection;
            return $response;
        }

        $pageNumber = $request->page;
        $showPerPage = $request->records_per_page ?? config('api-kit.pagination.default_records_per_page', 10);

        $totalItems = $collection->count();

        return self::paginator(
            $collection->forPage($pageNumber, $showPerPage),
            $totalItems,
            $showPerPage,
            $pageNumber,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
            $additionalResponseData
        );
    }

    protected static function paginator($items, $total, $perPage, $currentPage, $options, $additionalResponseData = [])
    {
        $paginatedData = Container::getInstance()->makeWith(
            LengthAwarePaginator::class,
            compact('items', 'total', 'perPage', 'currentPage', 'options')
        );

        return self::paginatorOutput($paginatedData, $additionalResponseData);
    }

    protected static function paginatorOutput($paginatedData, $additionalResponseData = [])
    {
        $items = array_values($paginatedData->items());
        $paginatedData = $paginatedData->toArray();

        $result['status'] = true;
        $format = [
            'page' => 'current_page',
            'records_per_page' => 'per_page',
            'page_count' => 'last_page',
            'total_records' => 'total',
        ];
        foreach ($format as $key => $value) {
            $result[$key] = $paginatedData[$value];
        }

        if (!empty($additionalResponseData)) {
            $result = array_merge($result, $additionalResponseData);
        }

        $result['data'] = $items;

        return $result;
    }
}
