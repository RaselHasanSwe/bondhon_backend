@extends('admin.layout')
@section('title', 'Notification Detail')
@section('page-title', 'Notification Detail')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-8">

        {{-- Breadcrumb --}}
        <nav class="mb-3" aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.web.notifications.history') }}" style="color:var(--gold)">
                        Notification History
                    </a>
                </li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </nav>

        <div class="table-card overflow-hidden">
            {{-- Colour stripe --}}
            <div style="height:4px;background:{{ $row->is_read ? '#e5e7eb' : 'var(--gold)' }}"></div>

            <div class="p-4 p-md-5">

                {{-- Header --}}
                <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                    <div>
                        <span class="badge bg-secondary fw-normal mb-2" style="font-size:11px">
                            {{ ucwords(str_replace('_', ' ', $row->type)) }}
                        </span>
                        <h5 class="fw-bold mb-0">{{ $row->data['title'] ?? 'Notification' }}</h5>
                    </div>
                    @if($row->is_read)
                        <span class="badge bg-success-subtle text-success flex-shrink-0">
                            <i class="bi bi-check2-circle me-1"></i>User Read
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning flex-shrink-0">
                            <i class="bi bi-dot me-1"></i>User Unread
                        </span>
                    @endif
                </div>

                {{-- Message --}}
                @if(!empty($row->data['message']))
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <p class="mb-0 text-secondary" style="white-space:pre-wrap;line-height:1.7">{{ $row->data['message'] }}</p>
                    </div>
                @endif

                {{-- Meta rows --}}
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <div class="p-3 border rounded-3 h-100">
                            <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.05em">Recipient</div>
                            <div class="fw-semibold">{{ $row->user_name }}</div>
                            <div class="text-muted small">{{ $row->user_email }}</div>
                            <div class="mt-1">
                                <a href="{{ route('admin.web.users') }}?search={{ urlencode($row->user_email) }}"
                                   class="small" style="color:var(--gold)">
                                    <i class="bi bi-person me-1"></i>View User
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 border rounded-3 h-100">
                            <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.05em">Timestamps</div>
                            <div class="small mb-1">
                                <span class="text-muted">Sent:</span>
                                <strong>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, H:i:s') }}</strong>
                            </div>
                            @if($row->read_at)
                                <div class="small">
                                    <span class="text-muted">Read at:</span>
                                    <strong>{{ \Carbon\Carbon::parse($row->read_at)->format('d M Y, H:i:s') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Extra data fields --}}
                @php
                    $dataArr  = is_array($row->data) ? $row->data : [];
                    $extraKeys = array_diff(array_keys($dataArr), ['title', 'message', 'icon']);
                @endphp
                @if(count($extraKeys) > 0)
                    <div class="mb-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em">Additional Data</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered small mb-0">
                                <thead class="table-light">
                                    <tr><th>Key</th><th>Value</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($extraKeys as $key)
                                        <tr>
                                            <td class="text-muted fw-semibold" style="width:40%">{{ $key }}</td>
                                            <td>{{ is_array($dataArr[$key]) ? json_encode($dataArr[$key]) : $dataArr[$key] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Notification ID --}}
                <div class="text-muted small mb-4">
                    <span class="fw-semibold">Notification ID:</span>
                    <code class="ms-1">{{ $row->id }}</code>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.web.notifications.history') }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to History
                    </a>
                    <a href="{{ route('admin.web.broadcast') }}"
                       class="btn btn-sm fw-semibold"
                       style="background:var(--gold);border-color:var(--gold);color:#fff;">
                        <i class="bi bi-megaphone me-1"></i>Send New Notification
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

