<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admn_Cnta_Link_Mast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class RedirectAuthController extends Controller
{
    private static function getGlobalApiKey(): string
    {
        return env('API_GLOBAL_KEY', 'apiKey');
    }

    public function generateInviteLink(Request $request): JsonResponse
    {
        // HR app sends these in the request body; the contact UI falls back to the session.
        $organizationUIN = $request->input('orga_uin') ?? session('selected_Orga_UIN');
        $userUIN         = $request->input('user_uin') ?? session('authenticated_user_uin');

        if (! $organizationUIN) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot generate an invite link. No organization is selected.',
            ], 422);
        }

        try {
            $token     = Str::random(40);
            $expiresAt = now()->addHours(24);

            Admn_Cnta_Link_Mast::create([
                'Tokn'               => $token,
                'Admn_Orga_Mast_UIN' => $organizationUIN,
                'Cnta_Tag'           => 'ByLink',
                'Is_Used'            => false,
                'Is_Actv'            => 100201,
                'Expy_Dt'            => $expiresAt,
                'CrOn'               => now(),
                'CrBy'               => $userUIN,
            ]);

            return response()->json([
                'success'    => true,
                'data'       => [
                    'link' => route('contact.create-by-link', ['token' => $token]),
                ],
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('generateInviteLink failed: ' . $e->getMessage(), [
                'org'       => $organizationUIN,
                'user'      => $userUIN,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not generate link.',
            ], 500);
        }
    }

    public function issueLink(Request $request)
    {
        $request->validate([
            'user_uin' => 'required|numeric',
            'orga_uin' => 'required|numeric',
        ]);

        $url = URL::temporarySignedRoute(
            'session.bootstrap',
            now()->addMinutes(20),
            ['user' => $request->user_uin, 'orga' => $request->orga_uin, 'page' => $request->input('page')]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'link' => $url
            ],
            'expires_at' => now()->addMinutes(20)->toDateTimeString(),
        ]);
    }

    public function handleRedirect(Request $request)
    {
        // Step 1: Validate API parameters
        $request->validate([
            'User_UIN' => 'required|numeric',
            'api_key' => 'required|string',
        ]);

        // Step 2: Verify global static key
        if ($request->api_key !== self::getGlobalApiKey()) {
            return $this->errorResponse('Invalid API key provided', $request->return_url);
        }

        $userUIN = $request->User_UIN;

        // Step 3: Verify User_UIN exists in admn_user_logi_mast table
        $user = DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->select('User_UIN', 'User_Name', 'Prmy_Emai', 'Stau_UIN')
            ->first();

        if (!$user) {
            Log::warning('User not found in admn_user_logi_mast', ['User_UIN' => $userUIN]);
            return $this->errorResponse('User not found with provided UIN', $request->return_url);
        }

        // Step 4: Check if user is active
        if (isset($user->Stau_UIN) && $user->Stau_UIN !== 100201) {
            Log::warning('User account is not active', ['User_UIN' => $userUIN, 'Stau_UIN' => $user->Stau_UIN]);
            return $this->errorResponse('User account is not active', $request->return_url);
        }

        Log::info('User verified in admn_user_logi_mast', ['User_UIN' => $userUIN, 'User_Name' => $user->User_Name]);

        // Ensure user is onboarded across all required tables if assigned as admin or contact person
        \App\Helpers\AuthHelper::ensureUserOrganizationAssociations($userUIN);

        // Step 5: Find all associations for this user in tbl_tran_user_assc table
        $userAssociations = DB::table('tbl_tran_user_assc')
            ->where('User_UIN', $userUIN)
            ->where('Stau_UIN', 100201)  // Only active associations
            ->select('User_Assc_UIN', 'Orga_UIN', 'User_UIN')
            ->get();

        Log::info('Found user associations', [
            'User_UIN' => $userUIN,
            'associations_count' => $userAssociations->count(),
            'associations' => $userAssociations->toArray()
        ]);

        if ($userAssociations->isEmpty()) {
            Log::warning('No active associations found for user', ['User_UIN' => $userUIN]);
            return $this->errorResponse('No active organizations found for this user', $request->return_url);
        }

        // Step 6: Get unique Orga_UIN values from associations
        $orgaUIDs = $userAssociations->pluck('Orga_UIN')->unique()->values();

        Log::info('Organization UIDs found', ['User_UIN' => $userUIN, 'Orga_UIDs' => $orgaUIDs->toArray()]);

        // Step 7: Get organization details from admn_orga_mast table
        $organizations = DB::table('admn_orga_mast')
            ->whereIn('Orga_UIN', $orgaUIDs)
            ->select('Orga_UIN', 'Orga_Name')
            ->get();

        Log::info('Organizations retrieved', [
            'User_UIN' => $userUIN,
            'organizations_count' => $organizations->count(),
            'organizations' => $organizations->toArray()
        ]);

        if ($organizations->isEmpty()) {
            Log::warning('No organizations found in admn_orga_mast', ['Orga_UIDs' => $orgaUIDs->toArray()]);
            return $this->errorResponse('No valid organizations found for this user', $request->return_url);
        }

        // Step 8: Set up authentication session
        session()->flush();
        session()->regenerate();

        session([
            'api_authenticated' => true,
            'authenticated_user_uin' => $userUIN,
            'api_auth_time' => now()->toISOString(),
            'auth_method' => 'api_redirect',
            'user_associations' => $userAssociations->toArray(),  // Store for reference
            'available_organizations' => $organizations->toArray()
        ]);

        session()->save();

        // Step 9: Log successful authentication and redirect to organization selection
        $this->logAuthentication($userUIN, null, true);

        return redirect()
            ->route('organization.select')
            // ->with('success', "Welcome {$user->User_Name}! Please select your organization to continue.")
            ->with('organizations_count', $organizations->count());
    }

    // ... other methods remain the same ...

    private function errorResponse(string $message, ?string $returnUrl = null)
    {
        Log::warning('API Authentication Failed', [
            'message' => $message,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ]);

        if ($returnUrl) {
            return redirect($returnUrl)->with('error', $message);
        }

        return view('auth.api-error', [
            'error_message' => $message,
            'error_code' => 'AUTH_FAILED'
        ]);
    }

    private function logAuthentication(int $userUIN, ?int $orgaUIN = null, bool $success = true, string $action = 'login')
    {
        Log::info('API Authentication Event', [
            'action' => $action,
            'user_uin' => $userUIN,
            'orga_uin' => $orgaUIN,
            'success' => $success,
            'session_id' => session()->getId(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ]);
    }
}
