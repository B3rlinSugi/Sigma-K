export interface PaginationMeta {
  page: number;
  pageSize: number;
  total: number;
  totalPages: number;
  hasNextPage: boolean;
  hasPreviousPage: boolean;
  timestamp: string;
  requestId?: string;
}

export interface PaginatedResult<T> {
  data: T[];
  meta: PaginationMeta;
}
