/** Shape of Laravel's default `LengthAwarePaginator::toArray()` JSON. */
export type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  prev_page_url: string | null;
  next_page_url: string | null;
};

/** Table state echoed back by `PaginatesTables::tableFilters()`. */
export type TableFilters = {
  search: string;
  sort: string | null;
  direction: 'asc' | 'desc';
  per_page: number;
};

export type TableColumn = {
  /** Field name sent as `?sort=`; only used when `sortable` is true. */
  key?: string;
  label: string;
  sortable?: boolean;
  align?: 'start' | 'end';
};
