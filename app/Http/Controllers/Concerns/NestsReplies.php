<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait NestsReplies
{
    /**
     * Turn root records into nested arrays, loading each level of replies in a single query.
     *
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $roots
     * @param  Closure(Collection<int, int>): Collection<int, TModel>  $loadChildren  Loads the replies of the given parent ids, oldest first.
     * @param  Closure(TModel, Collection<int, array<string, mixed>>): array<string, mixed>  $present
     * @return Collection<int, array<string, mixed>>
     */
    protected function nestReplies(Collection $roots, Closure $loadChildren, Closure $present): Collection
    {
        $byParent = collect();
        $level = $roots->pluck('id');

        while ($level->isNotEmpty()) {
            $children = $loadChildren($level);

            $byParent = $byParent->union($children->groupBy('parent_id'));
            $level = $children->pluck('id');
        }

        $build = function (Model $record) use (&$build, $byParent, $present): array {
            $replies = ($byParent->get($record->getKey()) ?? collect())->map(fn (Model $reply): array => $build($reply))->values();

            return $present($record, $replies);
        };

        return $roots->map($build)->values();
    }
}
