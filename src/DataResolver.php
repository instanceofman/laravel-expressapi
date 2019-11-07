<?php

namespace Isofman\LaravelExpressAPI;

use Exception;
use Illuminate\Support\Str;

/**
 * Class DataResolver
 * @package App\Libraries\Aliniex\ExpressAPI
 */
class DataResolver
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function resolve()
    {
        $meta = [
            'method'    => request()->method(),
            'data'      => request()->post('data'),
            'object'    => ucwords(request('object')),
            'filter'    => request('filter'),
            'limit'     => request('limit'),
            'do'        => request('do'),
            'with'      => !empty(request('with')) ?
                explode(',', request('with')) : null,
            'sort'      => request('sort'),
            'sort_by'   => request('sort_by')
        ];

        if (empty($meta['object'])) {
            abort(400, "Missing 'object'");
        }

        if (empty($meta['do'])) {
            abort(400, "Missing 'do'");
        }

        # Filter
        $meta['filter'] = !empty($meta['filter']) ?
            $this->parseFilter($meta['filter']) :
            [
                'self' => [], 'relationship' => []
            ];

        # Limit
        $meta['limit'] = !empty($meta['limit']) ? $meta['limit'] : 10;

        # Sort
        $meta['sort'] = !empty($meta['sort']) ? $meta['sort'] : 'latest';
        $meta['sort_by'] = !empty($meta['sort_by']) ? $meta['sort_by'] : 'id';

        # Run operation
        $result = $this->{$meta['do']}($meta);
        return $result;
    }

    /**
     * @param $rawFilter
     * @return array
     */
    protected function parseFilter($rawFilter)
    {
        $filter = [
            'self' => [],
            'relationship' => []
        ];
        $params = explode('|', $rawFilter);
        foreach ($params as $param) {
            preg_match('/([\w\s\-\.]+)\[([\w\~]+)\](.+)/', $param, $pairs);
            unset($pairs[0]);
            $pairs = array_values($pairs);
            $pairs[1] = $this->convertOperationSymbol($pairs[1]);
            if ($pairs[1] === 'like') {
                $prefix = substr($pairs[2], 0, 1);

                if ($prefix === '^') {
                    $pairs[2] = ltrim($pairs[2], '^') . '%';
                } else if ($prefix === '!') {
                    $pairs[2] = '%' . ltrim($pairs[2], '!');
                } else {
                    $pairs[2] = '%' . $pairs[2] . '%';
                }
            } else if ($pairs[1] === 'in') {
                $pairs[2] = explode(',', $pairs[2]);
            } else if ($pairs[1] === 'nin') {
                $pairs[2] = explode(',', $pairs[2]);
            }

            // Empty string
            if ($pairs[2] === '_e_')
                $pairs[2] = '';

            if (strpos($pairs[0], '.') === false) {
                $filter['self'][$pairs[0]] = $pairs;
            } else {
                $filter['relationship'][$pairs[0]] = $pairs;
            }
        }
        return $filter;
    }

    /**
     * @param $str
     * @return mixed
     */
    protected function convertOperationSymbol($str)
    {
        $map = [
            'eq' => '=',
            'neq' => '!=',
            'gt' => '>',
            'gte' => '>=',
            'lt' => '<',
            'lte' => '<=',
            'in' => 'in',
            'nin' => 'nin',
            '~' => 'like'
        ];
        return $map[$str];
    }

    # ------------------------------------ HOOKS --------------------------------------

    # ----------------------------------- OPERATIONS -----------------------------------

    protected function resolveObjectClass($object)
    {
        if(Str::contains($object, '_')) {
            return str_replace('_', "\\", $object);
        }

        return "App\\$object";
    }

    /**
     * @param $meta
     * @return mixed
     */
    protected function detail($meta)
    {
        $objectClass = $this->resolveObjectClass($meta['object']);
        $object = new $objectClass();

        $result = $object->where(array_values($meta['filter']['self']))->firstOrFail();
        return $result;
    }

    /**
     * @param $meta
     * @return mixed
     */
    protected function fetch($meta)
    {
        $objectClass = $this->resolveObjectClass($meta['object']);
        $object = new $objectClass();

        $selfFilters = array_values($meta['filter']['self']);
        $relationFilters = array_values($meta['filter']['relationship']);

        $collection = $object->where([]);

        foreach ($selfFilters as $filter) {
            if ($filter[1] === 'in') {
                $collection->whereIn($filter[0], $filter[2]);
            } else if ($filter[1] === 'nin') {
                $collection->whereNotIn($filter[0], $filter[2]);
            } else {
                $collection->where([$filter]);
            }
        }

        foreach ($relationFilters as $filter) {
            list($object, $field) = explode('.', $filter[0]);
            $operation = $filter[1];
            $value = $filter[2];
            $collection->whereHas($object, function ($q) use ($field, $operation, $value) {
                $q->where([[$field, $operation, $value]]);
            });
        }

        if ($meta['with']) {
            $collection->with($meta['with']);
        }

        if($meta['sort'] === 'latest') {
            $collection->orderBy($meta['sort_by'], 'desc');
        } else if($meta['sort'] === 'oldest') {
            $collection->orderBy($meta['sort_by'], 'asc');
        }

        $result = $collection->paginate($meta['limit'])->appends(request()->query());
        return $result;
    }
}
