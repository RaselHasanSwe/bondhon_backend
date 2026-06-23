@extends('admin.layout')
@section('title', 'Pages')
@section('page-title', 'Pages / CMS')

@section('content')
<div class="table-card">
    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
        <h6 class="fw-bold mb-0"><i class="bi bi-file-text me-2" style="color:var(--gold)"></i>All Pages</h6>
        <small class="text-muted">Content managed here is served to the public website</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                <tr>
                    <td class="text-muted small">{{ $page->id }}</td>
                    <td class="fw-semibold">{{ $page->title }}</td>
                    <td><code class="small">{{ $page->slug }}</code></td>
                    <td>
                        @if($page->is_published)
                            <span class="badge bg-success">Published</span>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $page->sort_order }}</td>
                    <td class="text-muted small">{{ $page->updated_at->format('d M Y, H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.web.pages.edit', $page->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No pages found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

