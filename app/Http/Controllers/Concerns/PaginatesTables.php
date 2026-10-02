<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginatesTables
{
    /**
     * Apply DataTables-style search, sorting and page size from the query string.
     *
     * Search matches any of `$searchable` (a dotted name such as `portfolio.name`
     * searches that relation). Sorting is limited to `$sortable`, otherwise the
     * ordering already on the query is kept.
     *
     * @param  Builder<*>  $query
     * @param  list<string>  $searchable
     * @param  list<string>  $sortable
     * @return LengthAwarePaginator<int, *>
     */
    protected function paginateTable(
        Builder $query,
        Request $request,
        array $searchable = [],
        array $sortable = [],
    ): LengthAwarePaginator {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '' && $searchable !== []) {
            $like = '%'.addcslashes($search, '\\%_').'%';

            $query->where(function (Builder $group) use ($searchable, $like): void {
                foreach ($searchable as $column) {
                    if (str_contains($column, '.')) {
                        [$relation, $relationColumn] = explode('.', $column, 2);
                        $group->orWhereHas($relation, fn (Builder $related) => $related->where($relationColumn, 'like', $like));
                    } else {
                        $group->orWhere($column, 'like', $like);
                    }
                }
            });
        }

        $sort = (string) $request->query('sort', '');

        if (in_array($sort, $sortable, true)) {
            $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

            $query->reorder($sort, $direction)->orderBy($query->getModel()->getQualifiedKeyName());
        }

        return $query->paginate($this->tablePerPage($request))->withQueryString();
    }

    /**
     * Table state echoed back to the page so the UI can reflect it.
     *
     * @param  list<string>  $sortable
     * @return array{search: string, sort: string|null, direction: string, per_page: int}
     */
    protected function tableFilters(Request $request, array $sortable = []): array
    {
        $sort = (string) $request->query('sort', '');

        return [
            'search' => trim((string) $request->query('search', '')),
            'sort' => in_array($sort, $sortable, true) ? $sort : null,
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
            'per_page' => $this->tablePerPage($request),
        ];
    }

    private function tablePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }
}
