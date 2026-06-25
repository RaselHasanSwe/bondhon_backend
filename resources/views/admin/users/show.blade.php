@extends('admin.layout')
@section('title', 'User Details')
@section('page-title', 'User Details')

@section('content')
@php
    $faceSession = $user->faceScanSession;
    $profile = $user->profile;
    $religious = $user->religiousDetail;
    $family = $user->familyDetail;
    $education = $user->educationCareer;
    $lifestyle = $user->lifestyle;
    $horoscope = $user->horoscopeDetail;
    $partner = $user->partnerPreference;

    $statusColor = $user->trashed() ? 'secondary' : ($user->is_banned ? 'danger' : 'success');
    $statusLabel = $user->trashed() ? 'Deleted' : ($user->is_banned ? 'Banned' : 'Active');

    $initials = collect(explode(' ', $user->name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
@endphp

<style>
    .ud-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .ud-card {
        background: #fff;
        border: 1px solid rgba(0,0,0,.07);
        border-radius: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        margin-bottom: 1.25rem;
    }
    .ud-card-header {
        padding: 1rem 1.4rem .6rem;
        border-bottom: 1px solid rgba(0,0,0,.07);
        display: flex; align-items: center; gap: .55rem;
    }
    .ud-card-header i { font-size: 1.1rem; color: #6366f1; }
    .ud-card-header h6 { margin: 0; font-weight: 700; font-size: .875rem; letter-spacing: .02em; color: #1e1e2e; }
    .ud-card-body { padding: 1.1rem 1.4rem; }
    .ud-meta-row { display: flex; flex-wrap: wrap; gap: .25rem 0; }
    .ud-meta-item { width: 50%; padding: .35rem 0; font-size: .82rem; }
    .ud-meta-item .label { color: #6b7280; font-weight: 500; display: block; font-size: .75rem; margin-bottom: 1px; }
    .ud-meta-item .value { color: #111827; font-weight: 500; }
    .ud-meta-full { width: 100% !important; }
    .face-img-wrap img { height: 110px; width: 100%; object-fit: cover; border-radius: 10px; }
    .face-img-wrap .cap-label { font-size: .72rem; font-weight: 600; color: #374151; margin-top: 5px; }
    .face-img-wrap .cap-time { font-size: .68rem; color: #9ca3af; }
    .ud-action-btn { border-radius: 10px; font-size: .83rem; font-weight: 600; padding: .55rem 1rem; }
    .photo-grid img { border-radius: 10px; aspect-ratio: 1/1; object-fit: cover; width: 100%; border: 1px solid rgba(0,0,0,.08); }
    .section-divider { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #9ca3af; margin-bottom: .75rem; }
    .status-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .ud-sticky { position: sticky; top: 1rem; }
    @media (max-width: 1199px) { .ud-sticky { position: static; } }
    @media (max-width: 767px) { .ud-meta-item { width: 100%; } }
</style>

<div class="row g-4">

    {{-- ===== LEFT SIDEBAR ===== --}}
    <div class="col-12 col-xl-3">
        <div class="ud-sticky">

            {{-- Identity card --}}
            <div class="ud-card">
                <div class="ud-card-body text-center py-4">
                    <div class="ud-avatar mx-auto mb-3">{{ $initials }}</div>
                    <h5 class="fw-bold mb-1 fs-6">{{ $user->name }}</h5>
                    <p class="text-muted mb-2" style="font-size:.78rem">{{ $user->email }}</p>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge rounded-pill bg-{{ $statusColor }}">
                            <span class="status-dot bg-{{ $statusColor === 'success' ? 'white' : 'white' }}"></span>
                            {{ $statusLabel }}
                        </span>
                        <span class="badge rounded-pill bg-light text-dark border">{{ ucfirst($user->role) }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="text-start px-1">
                        <div class="ud-meta-row">
                            <div class="ud-meta-item">
                                <span class="label">User ID</span>
                                <span class="value">#{{ $user->id }}</span>
                            </div>
                            <div class="ud-meta-item">
                                <span class="label">Gender</span>
                                <span class="value">{{ ucfirst($user->gender ?? '—') }}</span>
                            </div>
                            <div class="ud-meta-item">
                                <span class="label">Plan</span>
                                <span class="value">{{ ucfirst($user->subscription_plan) }}</span>
                            </div>
                            <div class="ud-meta-item">
                                <span class="label">Profile ID</span>
                                <span class="value">{{ $user->profile->profile_id ?? '—' }}</span>
                            </div>
                            <div class="ud-meta-item ud-meta-full">
                                <span class="label">Email Verified</span>
                                <span class="value">{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y') : 'Not verified' }}</span>
                            </div>
                            <div class="ud-meta-item ud-meta-full">
                                <span class="label">Joined</span>
                                <span class="value">{{ $user->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            @if($user->subscription_expires_at)
                            <div class="ud-meta-item ud-meta-full">
                                <span class="label">Subscription Expires</span>
                                <span class="value">{{ $user->subscription_expires_at->format('d M Y, h:i A') }}</span>
                            </div>
                            @endif
                            @if($user->deleted_at)
                            <div class="ud-meta-item ud-meta-full">
                                <span class="label text-danger">Deleted At</span>
                                <span class="value text-danger">{{ $user->deleted_at->format('d M Y, h:i A') }}</span>
                            </div>
                            @endif
                            @if($user->is_banned)
                            <div class="ud-meta-item ud-meta-full">
                                <span class="label text-danger">Banned At</span>
                                <span class="value text-danger">{{ $user->banned_at?->format('d M Y, h:i A') ?? '—' }}</span>
                            </div>
                            <div class="ud-meta-item ud-meta-full">
                                <span class="label text-danger">Ban Reason</span>
                                <span class="value text-danger">{{ $user->ban_reason ?? '—' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="ud-card">
                <div class="ud-card-header">
                    <i class="bi bi-lightning-charge-fill text-warning"></i>
                    <h6>Quick Actions</h6>
                </div>
                <div class="ud-card-body d-grid gap-2">
                    @if($user->is_banned)
                        <form method="POST" action="{{ route('admin.web.users.ban-toggle', $user->id) }}"
                              onsubmit="return confirm('Reactivate this user account? They will be able to sign in again.');">
                            @csrf
                            <button type="submit" class="btn ud-action-btn w-100 btn-success">
                                <i class="bi bi-check-circle me-1"></i>
                                Reactivate Account
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn ud-action-btn w-100 btn-danger" data-bs-toggle="modal" data-bs-target="#banUserModal">
                            <i class="bi bi-slash-circle me-1"></i>
                            Ban User
                        </button>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ===== RIGHT MAIN CONTENT ===== --}}
    <div class="col-12 col-xl-9">

        {{-- ===== FACE SCAN ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-person-bounding-box"></i>
                <h6>Face Scan Verification</h6>
                @if($faceSession)
                    <span class="badge ms-auto rounded-pill
                        {{ $faceSession->status === 'approved' ? 'bg-success' : ($faceSession->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                        {{ ucfirst($faceSession->status) }}
                    </span>
                @endif
            </div>
            <div class="ud-card-body">
                @if($faceSession)
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if($faceSession->completed_at)
                            <span class="badge bg-light text-dark border"><i class="bi bi-send-check me-1"></i>Submitted {{ $faceSession->completed_at->format('d M Y, h:i A') }}</span>
                        @endif
                        @if($faceSession->reviewed_at)
                            <span class="badge bg-light text-dark border"><i class="bi bi-eye-fill me-1"></i>Reviewed {{ $faceSession->reviewed_at->format('d M Y, h:i A') }}</span>
                        @endif
                    </div>

                    @if($faceSession->review_note)
                        <div class="alert alert-info py-2 px-3 small mb-3 rounded-3">
                            <i class="bi bi-info-circle me-1"></i><strong>Latest note:</strong> {{ $faceSession->review_note }}
                        </div>
                    @endif

                    @php
                        $reviewHistory = ($faceSession->metadata['review_history'] ?? []) ?: [];
                        $archivedSubmissions = ($faceSession->metadata['archived_submissions'] ?? []) ?: [];
                    @endphp

                    @if($faceSession->status === 'submitted')
                        <p class="small fw-semibold text-muted mb-2"><i class="bi bi-images me-1"></i>Current Submission (awaiting review)</p>
                    @elseif($faceSession->captures->isNotEmpty())
                        <p class="small fw-semibold text-muted mb-2"><i class="bi bi-images me-1"></i>Current Captures</p>
                    @endif

                    {{-- Capture images --}}
                    <div class="row g-2">
                        @forelse($faceSession->captures as $capture)
                            <div class="col-4 col-sm-3 col-md-2">
                                <div class="face-img-wrap">
                                    <img src="{{ asset('storage/' . $capture->image_path) }}"
                                         alt="{{ $capture->capture_key }}">
                                    <div class="cap-label">{{ str_replace('-', ' ', ucfirst($capture->capture_key)) }}</div>
                                    <div class="cap-time">{{ $capture->captured_at?->format('h:i A') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted mb-0 small"><i class="bi bi-images me-1"></i>No captures stored yet.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Approve/Reject only for new submissions awaiting review --}}
                    @if($faceSession->status === 'submitted')
                        <div class="d-flex gap-2 mt-3 pt-3">
                            <form method="POST" action="{{ route('admin.web.users.face-scan-review', $user->id) }}">
                                @csrf
                                <input type="hidden" name="decision" value="approved">
                                <button class="btn btn-success ud-action-btn">
                                    <i class="bi bi-check-circle me-1"></i>Approve
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger ud-action-btn"
                                    data-bs-toggle="modal" data-bs-target="#rejectFaceScanModal">
                                <i class="bi bi-x-circle me-1"></i>Reject
                            </button>
                        </div>
                    @elseif($faceSession->status === 'approved')
                        <p class="text-muted small mt-3 pt-3 border-top mb-0"><i class="bi bi-check-circle text-success me-1"></i>Face scan approved. No further action needed.</p>
                    @elseif($faceSession->status === 'rejected')
                        <p class="text-muted small mt-3 pt-3 border-top mb-0"><i class="bi bi-info-circle me-1"></i>Rejected — waiting for the user to log in and submit a new face scan.</p>
                    @else
                        <p class="text-muted small mt-3 pt-3 border-top mb-0"><i class="bi bi-hourglass-split me-1"></i>User has not completed a submission yet.</p>
                    @endif

                    <hr>

                    @if(count($archivedSubmissions) > 0)
                        <div class="mb-3 mt-3">
                            <p class="small fw-semibold text-muted mb-2"><i class="bi bi-archive me-1"></i>Previous Submissions</p>
                            @foreach(array_reverse($archivedSubmissions) as $index => $submission)
                                <div class="border rounded-3 p-3 mb-2 bg-light">
                                    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                                        <span class="badge {{ ($submission['decision'] ?? '') === 'approved' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($submission['decision'] ?? '—') }}
                                        </span>
                                        @if(!empty($submission['reviewed_at']))
                                            <span class="small text-muted">{{ \Carbon\Carbon::parse($submission['reviewed_at'])->format('d M Y, h:i A') }}</span>
                                        @endif
                                    </div>
                                    @if(!empty($submission['reason']))
                                        <p class="small mb-2"><strong>Reason:</strong> {{ $submission['reason'] }}</p>
                                    @endif
                                    @if(!empty($submission['captures']))
                                        <div class="row g-2">
                                            @foreach($submission['captures'] as $capture)
                                                <div class="col-4 col-sm-3 col-md-2">
                                                    <div class="face-img-wrap">
                                                        <img src="{{ asset('storage/' . $capture['image_path']) }}"
                                                             alt="{{ $capture['capture_key'] ?? 'capture' }}">
                                                        <div class="cap-label">{{ str_replace('-', ' ', ucfirst($capture['capture_key'] ?? '')) }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <p class="text-muted mb-0 small"><i class="bi bi-exclamation-circle me-1"></i>This user has not completed the face-scan step.</p>
                @endif
            </div>
        </div>

        {{-- ===== PROFILE DETAILS ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-person-lines-fill"></i>
                <h6>Profile Details</h6>
            </div>
            <div class="ud-card-body">
                @if($profile)
                    <div class="section-divider">Basic Information</div>
                    <div class="ud-meta-row mb-3">
                        <div class="ud-meta-item"><span class="label">Full Name</span><span class="value">{{ $user->name ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Nick Name</span><span class="value">{{ $profile->nick_name ?? '—' }}</span></div>
                        <div class="ud-meta-item">
                            <span class="label">Date of Birth</span>
                            <span class="value">
                                @if($profile->dob)
                                    {{ \Carbon\Carbon::parse($profile->dob)->format('d M Y') }}
                                    <span class="badge bg-light text-dark border ms-1">{{ \Carbon\Carbon::parse($profile->dob)->age }} yrs</span>
                                @else —
                                @endif
                            </span>
                        </div>
                        <div class="ud-meta-item"><span class="label">Profile Created For</span><span class="value">{{ humanize($profile->profile_created_for) ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Looking For</span><span class="value">{{ humanize($profile->looking_for) ??  '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Marital Status</span><span class="value">{{ humanize($profile->marital_status) ?? '—' }}</span></div>
                    </div>

                    <div class="section-divider">Physical Attributes</div>
                    <div class="ud-meta-row mb-3">
                        <div class="ud-meta-item"><span class="label">Height</span><span class="value">{{ $profile->height_cm ? $profile->height_cm . ' cm' : '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Weight</span><span class="value">{{ $profile->weight_kg ? $profile->weight_kg . ' kg' : '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Body Type</span><span class="value">{{ humanize($profile->body_type) ?? $profile->body_type ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Complexion</span><span class="value">{{ humanize($profile->complexion) ?? $profile->complexion ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Eye Color</span><span class="value">{{ humanize($profile->eye_color) ?? $profile->eye_color ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Hair Color</span><span class="value">{{ humanize($profile->hair_color) ?? $profile->hair_color ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Blood Group</span><span class="value">{{ humanize($profile->blood_group) ?? $profile->blood_group ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Disability</span><span class="value">{{ humanize($profile->disability) ?? $profile->disability ?? '—' }}</span></div>
                    </div>

                    <div class="section-divider">Location & Background</div>
                    <div class="ud-meta-row mb-3">
                        <div class="ud-meta-item"><span class="label">Mother Tongue</span><span class="value">{{ humanize($profile->mother_tongue) ?? $profile->mother_tongue ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Nationality</span><span class="value">{{ humanize($profile->nationality) ?? $profile->nationality ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Country</span><span class="value">{{ humanize($profile->country) ?? $profile->country ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">State / Division</span><span class="value">{{ humanize($profile->state) ?? $profile->state ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">City</span><span class="value">{{ humanize($profile->city) ?? $profile->city ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Postal Code</span><span class="value">{{ $profile->postal_code ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Residing Status</span><span class="value">{{ humanize($profile->residing_status) ?? $profile->residing_status ?? '—' }}</span></div>
                    </div>

                    <div class="section-divider">About</div>
                    <div class="ud-meta-row">
                        <div class="ud-meta-item ud-meta-full">
                            <span class="label">About Me</span>
                            <span class="value">{{ $profile->about_me ?? '—' }}</span>
                        </div>
                        <div class="ud-meta-item ud-meta-full">
                            <span class="label">What I'm Looking For</span>
                            <span class="value">{{ $profile->what_looking_for ?? '—' }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0 small">No profile data available.</p>
                @endif
            </div>
        </div>

        {{-- ===== RELIGIOUS DETAILS ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-moon-stars-fill"></i>
                <h6>Religious Details</h6>
            </div>
            <div class="ud-card-body">
                @if($religious)
                    <div class="ud-meta-row">
                        <div class="ud-meta-item"><span class="label">Religion</span><span class="value">{{ humanize($religious->religion) ?? $religious->religion ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Caste</span><span class="value">{{ humanize($religious->caste) ?? $religious->caste ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Sub Caste</span><span class="value">{{ humanize($religious->sub_caste) ?? $religious->sub_caste ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Gotra</span><span class="value">{{ humanize($religious->gotra) ?? $religious->gotra ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Manglik Status</span><span class="value">{{ humanize($religious->manglik_status) ?? $religious->manglik_status ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Religiousness</span><span class="value">{{ humanize($religious->religiousness) ?? $religious->religiousness ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Religiousness</span><span class="value">{{ humanize($religious->religiousness) ?? $religious->religiousness ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Prayer Frequency</span><span class="value">{{ humanize($religious->pray) ?? $religious->pray ?? '—' }}</span></div>
                    </div>
                @else
                    <p class="text-muted mb-0 small">No religious details available.</p>
                @endif
            </div>
        </div>

        {{-- ===== FAMILY DETAILS ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-people-fill"></i>
                <h6>Family Details</h6>
            </div>
            <div class="ud-card-body">
                @if($family)
                    <div class="ud-meta-row">
                        <div class="ud-meta-item"><span class="label">Family Type</span><span class="value">{{ humanize($family->family_type) ?? $family->family_type ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Family Status</span><span class="value">{{ humanize($family->family_status) ?? $family->family_status ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Family Income</span><span class="value">{{ $family->family_income_bdt_per_month ? '৳ ' . number_format($family->family_income_bdt_per_month) . '/mo' : '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Father's Occupation</span><span class="value">{{ humanize($family->father_occupation) ?? $family->father_occupation ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Mother's Occupation</span><span class="value">{{ humanize($family->mother_occupation) ?? $family->mother_occupation ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Brothers</span><span class="value">{{ $family->brothers_count ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Sisters</span><span class="value">{{ $family->sisters_count ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Has Children</span><span class="value">{{ humanize($family->has_children) ?? $family->has_children ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Child Living Status</span><span class="value">{{ $family->child_living_status ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Family Values</span><span class="value">{{ humanize($family->family_values) ?? $family->family_values ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Sibling Position</span><span class="value">{{ humanize        ($family->sibling_position) ?? $family->sibling_position ?? '—' }}</span></div>
                    </div>
                @else
                    <p class="text-muted mb-0 small">No family details available.</p>
                @endif
            </div>
        </div>

        {{-- ===== EDUCATION & CAREER ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-mortarboard-fill"></i>
                <h6>Education & Career</h6>
            </div>
            <div class="ud-card-body">
                @if($education)
                    <div class="ud-meta-row">
                        <div class="ud-meta-item"><span class="label">Highest Education</span><span class="value">{{humanize($education->highest_education) ?? $education->highest_education ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">College / University</span><span class="value">{{ humanize($education->college_university) ?? $education->college_university ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Institution & Year</span><span class="value">{{ humanize($education->institution_name_year) ?? $education->institution_name_year ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Employer</span><span class="value">{{ humanize($education->employer_name) ?? $education->employer_name ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Job Location</span><span class="value">{{ humanize($education->job_location) ?? $education->job_location ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Designation</span><span class="value">{{ humanize($education->designation) ?? $education->designation ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Experience</span><span class="value">{{ $education->experience_years ? $education->experience_years . ' years' : '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Profession</span><span class="value">{{ humanize        ($education->profession) ?? $education->profession ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Employed In</span><span class="value">{{ humanize($education->employed_in) ?? $education->employed_in ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Annual Income</span><span class="value">{{ $education->annual_income_bdt ? '৳ ' . number_format($education->annual_income_bdt) : '—' }}</span></div>
                    </div>
                @else
                    <p class="text-muted mb-0 small">No education / career data available.</p>
                @endif
            </div>
        </div>

        {{-- ===== LIFESTYLE ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-stars"></i>
                <h6>Lifestyle</h6>
            </div>
            <div class="ud-card-body">
                @if($lifestyle)
                    <div class="ud-meta-row">
                        <div class="ud-meta-item"><span class="label">Diet</span><span class="value">{{ humanize($lifestyle->diet) ?? $lifestyle->diet ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Smoking</span><span class="value">{{ humanize($lifestyle->smoking) ?? $lifestyle->smoking ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Drinking</span><span class="value">{{ humanize($lifestyle->drinking) ?? $lifestyle->drinking ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Eye Wear</span><span class="value">{{ humanize($lifestyle->eye_wear) ?? $lifestyle->eye_wear ?? '—' }}</span></div>
                        @if($lifestyle->hobbies)
                        <div class="ud-meta-item ud-meta-full">
                            <span class="label">Hobbies</span>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($lifestyle->hobbies as $hobby)
                                    <span class="badge bg-light text-dark border rounded-pill">{{ humanize($hobby) ?? $hobby }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                @else
                    <p class="text-muted mb-0 small">No lifestyle data available.</p>
                @endif
            </div>
        </div>

        {{-- ===== HOROSCOPE ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-moon-fill"></i>
                <h6>Horoscope Details</h6>
            </div>
            <div class="ud-card-body">
                @if($horoscope)
                    <div class="ud-meta-row">
                        <div class="ud-meta-item"><span class="label">Birth Place</span><span class="value">{{ $horoscope->birth_place ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Birth Time</span><span class="value">{{ $horoscope->birth_time ? date('h:i A', strtotime($horoscope->birth_time)) : '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Rashi</span><span class="value">{{ $horoscope->rashi ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Nakshatra</span><span class="value">{{ $horoscope->nakshatra ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Manglik</span>
                            <span class="value">
                                @if(isset($horoscope->manglik))
                                    <span class="badge rounded-pill {{ $horoscope->manglik > 0 ? 'bg-warning text-dark' : 'bg-success' }}">
                                        {{ $horoscope->manglik > 0 ? 'Yes' : 'No' }}
                                    </span>
                                @else —
                                @endif
                            </span>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0 small">No horoscope details available.</p>
                @endif
            </div>
        </div>

        {{-- ===== PARTNER PREFERENCE ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-heart-fill text-danger" style="color:#e11d48 !important"></i>
                <h6>Partner Preference</h6>
            </div>
            <div class="ud-card-body">
                @if($partner)
                    <div class="section-divider">Age & Physical</div>
                    <div class="ud-meta-row mb-3">
                        <div class="ud-meta-item"><span class="label">Age Range</span><span class="value">{{ $partner->age_min ?? '—' }} – {{ $partner->age_max ?? '—' }} yrs</span></div>
                        <div class="ud-meta-item"><span class="label">Height Range</span><span class="value">{{ $partner->height_min_cm ?? '—' }} – {{ $partner->height_max_cm ?? '—' }} cm</span></div>
                        <div class="ud-meta-item"><span class="label">Body Type</span><span class="value">{{ json_list($partner->body_type) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Complexion</span><span class="value">{{ json_list($partner->complexion) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Blood Group</span><span class="value">{{ json_list($partner->blood_group) }}</span></div>
                    </div>

                    <div class="section-divider">Religion & Values</div>
                    <div class="ud-meta-row mb-3">
                        <div class="ud-meta-item"><span class="label">Religion</span><span class="value">{{ json_list($partner->religion) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Caste</span><span class="value">{{ json_list($partner->caste) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Religiousness</span><span class="value">{{ json_list($partner->religiousness) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Prayer Frequency</span><span class="value">{{ json_list($partner->pray) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Manglik Status</span><span class="value">{{ json_list($partner->manglik_status) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Rashi / Zodiac</span><span class="value">{{ json_list($partner->rashi) }}</span></div>
                    </div>

                    <div class="section-divider">Family & Lifestyle</div>
                    <div class="ud-meta-row mb-3">
                        <div class="ud-meta-item"><span class="label">Marital Status</span><span class="value">{{ json_list($partner->marital_status) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Family Type</span><span class="value">{{ json_list($partner->family_type) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Family Values</span><span class="value">{{ json_list($partner->family_values) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Has Children</span><span class="value">{{ ucfirst($partner->has_children) ?? '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Child Living Status</span><span class="value">{{ json_list($partner->child_living_status) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Diet</span><span class="value">{{ json_list($partner->diet) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Smoking Acceptable</span>
                            <span class="value"><span class="badge rounded-pill {{ $partner->smoking_acceptable == 1 ? 'bg-success' : 'bg-danger' }}">{{ $partner->smoking_acceptable == 1 ? 'Yes' : 'No' }}</span></span>
                        </div>
                        <div class="ud-meta-item"><span class="label">Drinking Acceptable</span>
                            <span class="value"><span class="badge rounded-pill {{ $partner->drinking_acceptable == 1 ? 'bg-success' : 'bg-danger' }}">{{ $partner->drinking_acceptable == 1 ? 'Yes' : 'No' }}</span></span>
                        </div>
                    </div>

                    <div class="section-divider">Career & Education</div>
                    <div class="ud-meta-row mb-3">
                        <div class="ud-meta-item"><span class="label">Annual Income (min)</span><span class="value">{{ $partner->income_min_bdt ? '৳ ' . number_format($partner->income_min_bdt) : '—' }}</span></div>
                        <div class="ud-meta-item"><span class="label">Working Status</span><span class="value">{{ json_list($partner->working_status) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Employed In</span><span class="value">{{ json_list($partner->employed_in) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Profession</span><span class="value">{{ json_list($partner->profession) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Minimum Education</span><span class="value">{{ json_list($partner->education) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Mother Tongue</span><span class="value">{{ json_list($partner->mother_tongue) }}</span></div>
                    </div>

                    <div class="section-divider">Location Preferences</div>
                    <div class="ud-meta-row">
                        <div class="ud-meta-item"><span class="label">Country</span><span class="value">{{ json_list($partner->country) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Residing Status</span><span class="value">{{ json_list($partner->pref_residing_status) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Division (BD)</span><span class="value">{{ json_list($partner->pref_divisions) }}</span></div>
                        <div class="ud-meta-item"><span class="label">District (BD)</span><span class="value">{{ json_list($partner->pref_districts) }}</span></div>
                        <div class="ud-meta-item"><span class="label">Province (CA)</span><span class="value">{{ json_list($partner->pref_provinces) }}</span></div>
                        <div class="ud-meta-item"><span class="label">State (USA)</span><span class="value">{{ json_list($partner->pref_states) }}</span></div>
                    </div>
                @else
                    <p class="text-muted mb-0 small">No partner preference data available.</p>
                @endif
            </div>
        </div>

        {{-- ===== PROFILE PHOTOS ===== --}}
        <div class="ud-card">
            <div class="ud-card-header">
                <i class="bi bi-images"></i>
                <h6>Profile Photos</h6>
                <span class="badge bg-light text-dark border ms-auto">{{ $user->photos->count() }} photo{{ $user->photos->count() !== 1 ? 's' : '' }}</span>
            </div>
            <div class="ud-card-body">
                @if($user->photos->count())
                    <div class="row g-3 photo-grid">
                        @foreach($user->photos as $photo)
                            <div class="col-6 col-sm-4 col-md-3">
                                <img src="{{ asset('storage/' . $photo->file_path) }}" alt="Photo">
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0 small"><i class="bi bi-image me-1"></i>No photos uploaded yet.</p>
                @endif
            </div>
        </div>

    </div>{{-- /col right --}}
</div>

@if(!$user->is_banned)
<div class="modal fade" id="rejectFaceScanModal" tabindex="-1" aria-labelledby="rejectFaceScanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.web.users.face-scan-review', $user->id) }}">
                @csrf
                <input type="hidden" name="decision" value="rejected">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectFaceScanModalLabel">
                        <i class="bi bi-x-circle text-danger me-2"></i>Reject Face Scan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Rejecting the face scan for <strong>{{ $user->name }}</strong> will require them to submit a new scan. Captured images will be kept for review.
                    </p>
                    <div class="mb-3">
                        <label for="face_scan_reject_reason" class="form-label fw-semibold small">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea
                            id="face_scan_reject_reason"
                            name="review_note"
                            rows="4"
                            class="form-control @error('review_note') is-invalid @enderror"
                            placeholder="Explain why this face scan was rejected…"
                            required
                            maxlength="2000"
                        >{{ old('review_note') }}</textarea>
                        @error('review_note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Shown to the user on the face verification page.</small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="face_scan_send_email" name="send_email_notification" {{ old('send_email_notification') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="face_scan_send_email">
                            Send email notification to user
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Reject Face Scan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="banUserModal" tabindex="-1" aria-labelledby="banUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.web.users.ban-toggle', $user->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="banUserModalLabel">
                        <i class="bi bi-slash-circle text-danger me-2"></i>Ban User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Banning <strong>{{ $user->name }}</strong> will immediately revoke their access and sign them out of all devices.
                    </p>
                    <div class="mb-3">
                        <label for="ban_reason" class="form-label fw-semibold small">Reason for Ban <span class="text-danger">*</span></label>
                        <textarea
                            id="ban_reason"
                            name="ban_reason"
                            rows="4"
                            class="form-control @error('ban_reason') is-invalid @enderror"
                            placeholder="Describe why this account is being suspended…"
                            required
                            minlength="10"
                            maxlength="2000"
                        >{{ old('ban_reason') }}</textarea>
                        @error('ban_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimum 10 characters. Shown to the user when they attempt to log in.</small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="send_email_notification" name="send_email_notification" {{ old('send_email_notification') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="send_email_notification">
                            Send email notification to user
                        </label>
                        <div class="form-text">If checked, an email with the ban reason will be sent to {{ $user->email }}.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-slash-circle me-1"></i>Confirm Ban
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($errors->has('ban_reason') && !$user->is_banned)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('banUserModal')).show();
    });
</script>
@endif

@if($errors->has('review_note') && !$user->is_banned)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('rejectFaceScanModal')).show();
    });
</script>
@endif
@endsection
