<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Match\PublicSearchRequest;
use App\Http\Resources\PublicProfileCardResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PublicProfileSearchController extends ApiController
{
    /**
     * GET /api/v1/public/profiles/search
     * Browse profiles without authentication (limited card fields).
     */
    public function search(PublicSearchRequest $request): JsonResponse
    {
        $query = User::with([
                'profile',
                'religiousDetail',
                'educationCareer',
                'lifestyle',
                'photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false)->where('is_primary', true),
            ])
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('religious_details', 'religious_details.user_id', '=', 'users.id')
            ->leftJoin('education_careers', 'education_careers.user_id', '=', 'users.id')
            ->leftJoin('lifestyles', 'lifestyles.user_id', '=', 'users.id')
            ->leftJoin('family_details', 'family_details.user_id', '=', 'users.id')
            ->select('users.*')
            ->where('users.role', 'user')
            ->where('users.is_active', true)
            ->where('users.is_banned', false)
            ->whereNotNull('users.email_verified_at');

        if ($request->filled('gender')) {
            $query->where('users.gender', $request->gender);
        }

        if ($request->filled('age_min')) {
            $query->whereDate('profiles.dob', '<=', now()->subYears((int) $request->age_min)->format('Y-m-d'));
        }
        if ($request->filled('age_max')) {
            $query->whereDate('profiles.dob', '>=', now()->subYears((int) $request->age_max)->format('Y-m-d'));
        }

        if ($request->filled('height_min')) {
            $query->where('profiles.height_cm', '>=', $request->height_min);
        }
        if ($request->filled('height_max')) {
            $query->where('profiles.height_cm', '<=', $request->height_max);
        }

        if ($request->filled('country')) {
            $query->where('profiles.country', $request->country);
        }
        if ($request->filled('state')) {
            $query->where('profiles.state', 'like', '%' . $request->state . '%');
        }
        if ($request->filled('city')) {
            $query->where('profiles.city', 'like', '%' . $request->city . '%');
        }
        if ($request->filled('nationality')) {
            $query->where('profiles.nationality', $request->nationality);
        }
        if ($request->filled('residing_status')) {
            $query->where('profiles.residing_status', $request->residing_status);
        }

        if ($request->filled('marital_status')) {
            $query->where('profiles.marital_status', $request->marital_status);
        }

        if ($request->filled('body_type')) {
            $query->where('profiles.body_type', $request->body_type);
        }
        if ($request->filled('complexion')) {
            $query->where('profiles.complexion', $request->complexion);
        }
        if ($request->filled('blood_group')) {
            $query->where('profiles.blood_group', $request->blood_group);
        }
        if ($request->filled('mother_tongue')) {
            $query->where('profiles.mother_tongue', $request->mother_tongue);
        }

        if ($request->filled('religion')) {
            $query->where('religious_details.religion', $request->religion);
        }
        if ($request->filled('caste')) {
            $query->where('religious_details.caste', $request->caste);
        }

        if ($request->filled('education')) {
            $query->where('education_careers.highest_education', $request->education);
        }
        if ($request->filled('profession')) {
            $query->where('education_careers.profession', $request->profession);
        }
        if ($request->filled('employed_in')) {
            $query->where('education_careers.employed_in', $request->employed_in);
        }

        if ($request->filled('income_min')) {
            $query->where('education_careers.annual_income_bdt', '>=', $request->income_min);
        }
        if ($request->filled('income_max')) {
            $query->where('education_careers.annual_income_bdt', '<=', $request->income_max);
        }

        if ($request->filled('diet')) {
            $query->where('lifestyles.diet', $request->diet);
        }
        if ($request->filled('smoking')) {
            $query->where('lifestyles.smoking', $request->smoking);
        }
        if ($request->filled('drinking')) {
            $query->where('lifestyles.drinking', $request->drinking);
        }

        if ($request->filled('has_children')) {
            $query->where('family_details.has_children', $request->has_children);
        }

        if ($request->filled('query')) {
            $kw = (string) $request->input('query');
            $query->where(function ($q) use ($kw) {
                $q->where('users.name', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.about_me', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.city', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.state', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.country', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.nationality', 'like', '%' . $kw . '%')
                  ->orWhere('religious_details.religion', 'like', '%' . $kw . '%')
                  ->orWhere('education_careers.profession', 'like', '%' . $kw . '%')
                  ->orWhere('education_careers.highest_education', 'like', '%' . $kw . '%')
                  ->orWhere('education_careers.employer_name', 'like', '%' . $kw . '%');
            });
        }

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'age_asc'    => $query->orderByRaw('profiles.dob DESC'),
            'age_desc'   => $query->orderByRaw('profiles.dob ASC'),
            'completion' => $query->orderBy('profiles.profile_completion_percentage', 'desc'),
            default      => $query->orderBy('users.id', 'desc'),
        };

        $paginator = $query->paginate(20);

        return $this->successResponse(
            PublicProfileCardResource::collection($paginator)->response()->getData(true),
            'Search results retrieved.'
        );
    }
}
