<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        
        Log::info('ApiAuthenticated Middleware Check', [
            'url' => $request->fullUrl(),
            'session_id' => session()->getId(),
            'authenticated_user_uin' => session('authenticated_user_uin'),
        ]);

        // 1. Priority: Check URL for token first
        $token = $request->query('token');

        if (!$token) {
            // 2. Priority: If no URL token, check existing session
            if (session()->has('authenticated_user_uin')) {
                session(['api_authenticated' => true]);
                return $next($request);
            }

            // 3. Priority: Dev fallback (user's hardcoded testing token)
            $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L3BhcnRha2VkaWdpdGFsIiwiYXVkIjoiaHR0cDovL2xvY2FsaG9zdC9wYXJ0YWtlZGlnaXRhbCIsImlhdCI6MTc3NDk3OTMyNywiYWNjZXNzX3Rva2VuIjoiYmU4OWY3M2ZiOGRjYWQwMjQ3ODE3YjY0ZmNiMWViIiwiVXNlcl9VSU4iOiIxNzY2MDYxNTcyIn0.N4mJamxqL6cbtaOjvr7LiIpm2_nKeAlPAkuAZtGj5JE";
        }

        if (!$token) {
            return $this->unauthorized($request);
        }

        try {
            // Call Partakers API
            $response = Http::timeout(10)->post(
                config('services.partakers.user_api'),
                ['token' => $token]
            );
            

            if (!$response->ok()) {
                Log::error('Partakers API response not OK', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->view('errors.sessionexpires', [], 401);
            }

            $data = $response->json();

            if (empty($data['data'])) {
                return $this->unauthorized($request);
            }

            $primaryUserUIN = $data['data'][0]['User_UIN'] ?? null;
            if (!$primaryUserUIN) {
                return $this->unauthorized($request);
            }

            foreach ($data['data'] as $orgData) {
                $userUIN = $orgData['User_UIN'] ?? null;
                $orgaUIN = $orgData['Orga_UIN'] ?? null;

                if (!$userUIN || !$orgaUIN) continue;

                // 1. Sync admn_orga_mast
                try {
                    $orgExists = \Illuminate\Support\Facades\DB::table('admn_orga_mast')
                        ->where('Orga_UIN', $orgaUIN)
                        ->exists();

                    if (!$orgExists) {
                        \Illuminate\Support\Facades\DB::table('admn_orga_mast')->insert([
                            'Orga_UIN'           => $orgaUIN,
                            'Orga_Type_UIN'      => $orgData['Orga_Type_UIN'] ?? 1500000001,
                            'Orga_Name'          => $orgData['Orga_Name'] ?? 'Unknown Organization',
                            'orga_Logo'          => '',
                            'Dt_of_Incp'         => now()->timestamp,
                            'Fnal_Year_Strt_Dt'  => now()->timestamp,
                            'Actn_Strt_Dt'       => now()->timestamp,
                            'Web_Addr'           => '',
                            'Cnta_Pers'          => $userUIN,
                            'Admt_Pers_1st'      => $orgData['Admt_Pers_1st'] ?? $userUIN,
                            'Admt_Pers_2nd'      => $orgData['Admt_Pers_2nd'] ?? 0,
                            'Admt_Pers_3rd'      => $orgData['Admt_Pers_3rd'] ?? 0,
                            'Admt_Pers_4th'      => $orgData['Admt_Pers_4th'] ?? 0,
                            'Assc_Part_Code'     => 0,
                            'Sytm_Admt_Pers'     => $userUIN,
                            'Eft_Dt'             => now()->timestamp,
                            'Renw_Dt'            => now()->timestamp,
                            'Expy_Dt'            => now()->addYears(1)->timestamp,
                            'Orga_Del_Dt'        => 0,
                            'Stau_UIN'           => 100201,
                            'Stat_UIN'           => 100201,
                            'pin_UIN'            => '',
                            'Prmy_Emai'          => $orgData['Prmy_Emai'] ?? '',
                            'Prmy_Emai_Vefi'     => 0,
                            'mpos_key'           => 0,
                            'CrOn'               => now()->format('Y-m-d H:i:s'),
                            'CrBy'               => $userUIN,
                            'MoOn'               => now()->format('Y-m-d H:i:s'),
                            'MoBy'               => $userUIN,
                        ]);
                    }
                } catch (\Exception $dbEx) {
                    Log::warning('admn_orga_mast sync skipped: ' . $dbEx->getMessage());
                }

                // 2. Sync admn_user_logi_mast
                try {
                    $userExists = \Illuminate\Support\Facades\DB::table('admn_user_logi_mast')
                        ->where('User_UIN', $userUIN)
                        ->exists();

                    if (!$userExists) {
                        \Illuminate\Support\Facades\DB::table('admn_user_logi_mast')->insert([
                            'User_UIN' => $userUIN,
                            'User_Name' => $orgData['User_Name'] ?? 'Unknown User',
                            'Prmy_Emai' => $orgData['Prmy_Emai'] ?? null,
                            'Prmy_MoNo' => $orgData['Prmy_MoNo'] ?? null,
                            'User_Logo' => $orgData['User_Logo'] ?? null,
                        ]);
                    }
                } catch (\Exception $dbEx) {
                    Log::warning('admn_user_logi_mast sync skipped: ' . $dbEx->getMessage());
                }

                // 3. User-Organization Relation
                try {
                    // 3a. Resolve FK: look up (or create) the "Admin" label for this org
                    $asscLbel = \Illuminate\Support\Facades\DB::table('admn_user_assc_lbel_mast')
                        ->where('Orga_UIN', $orgaUIN)
                        ->where('User_Assc_Lbel_Name', 'Admin')
                        ->first();

                    if ($asscLbel) {
                        $orgaUserAsscUIN = $asscLbel->Orga_User_Assc_UIN;
                    } else {
                        $orgaUserAsscUIN = $this->generateUniqueUIN('admn_user_assc_lbel_mast', 'Orga_User_Assc_UIN');
                        \Illuminate\Support\Facades\DB::table('admn_user_assc_lbel_mast')->insert([
                            'Orga_User_Assc_UIN'  => $orgaUserAsscUIN,
                            'Orga_UIN'            => $orgaUIN,
                            'User_Assc_Lbel_Name' => 'Admin',
                            'User_Assc_Lbel_Desp' => 'Organization Administrator',
                            'CrBy'                => (string) $userUIN,
                            'CrOn'                => now()->format('Y-m-d H:i:s'),
                        ]);
                    }

                    // 3b. Insert relation using the resolved FK
                    $relaExists = \Illuminate\Support\Facades\DB::table('admn_user_orga_rela')
                        ->where('User_UIN', $userUIN)
                        ->where('Orga_UIN', $orgaUIN)
                        ->exists();

                    if (!$relaExists) {
                        \Illuminate\Support\Facades\DB::table('admn_user_orga_rela')->insert([
                            'User_Assc_UIN'      => $this->generateUniqueUIN('admn_user_orga_rela', 'User_Assc_UIN'),
                            'Orga_User_Assc_UIN' => $orgaUserAsscUIN,
                            'User_UIN'           => $userUIN,
                            'Orga_UIN'           => $orgaUIN,
                            'Stau_UIN'           => 100201,
                            'CrOn'               => now()->format('Y-m-d H:i:s'),
                            'CrBy'               => (string) $userUIN,
                        ]);
                    }
                } catch (\Exception $dbEx) {
                    Log::warning('admn_user_orga_rela sync skipped: ' . $dbEx->getMessage());
                }

                // 4. Admin Permissions Sync
                try {
                    $isAdmin = in_array((string)$userUIN, [
                        (string)($orgData['Admt_Pers_1st'] ?? ''),
                        (string)($orgData['Admt_Pers_2nd'] ?? ''),
                        (string)($orgData['Admt_Pers_3rd'] ?? ''),
                        (string)($orgData['Admt_Pers_4th'] ?? ''),
                        (string)($orgData['Cnta_Pers'] ?? ''),
                        (string)($orgData['Sytm_Admt_Pers'] ?? ''),
                    ], true);

                    if ($isAdmin) {
                        $asscExists = \Illuminate\Support\Facades\DB::table('tbl_tran_user_assc')
                            ->where('Orga_UIN', $orgaUIN)
                            ->where('User_UIN', $userUIN)
                            ->exists();

                        if (!$asscExists) {
                            \Illuminate\Support\Facades\DB::table('tbl_tran_user_assc')->insert([
                                'User_Assc_UIN' => $this->generateUniqueUIN('tbl_tran_user_assc', 'User_Assc_UIN'),
                                'Orga_User_Assc_UIN' => $this->generateUniqueUIN('tbl_tran_user_assc', 'Orga_User_Assc_UIN'),
                                'Orga_UIN' => $orgaUIN,
                                'User_UIN' => $userUIN,
                                'can_add' => 1,
                                'can_edit' => 1,
                                'can_atch' => 1,
                                'can_del' => 1,
                                'can_link' => 1,
                                'can_rept' => 1,
                                'can_impt' => 1,
                                'can_expt' => 1,
                                'mast_grp' => 1,
                                'mast_tag' => 1,
                                'mast_notes' => 1,
                                'is_admin' => 1,
                                'vert_uin' => 112,
                                'Expy' => 99999,
                                'Stau_UIN' => 100201,
                                'CrOn' => now()->timestamp,
                                'CrBy' => $userUIN,
                                'MoOn' => now()->timestamp,
                                'MoBy' => $userUIN,
                            ]);
                        } else {
                            \Illuminate\Support\Facades\DB::table('tbl_tran_user_assc')
                                ->where('Orga_UIN', $orgaUIN)
                                ->where('User_UIN', $userUIN)
                                ->update(['is_admin' => 1]);
                        }
                    }
                } catch (\Exception $dbEx) {
                    Log::warning('tbl_tran_user_assc sync skipped: ' . $dbEx->getMessage());
                }
            }

            // Ensure associations across all required tables for admin user
            \App\Helpers\AuthHelper::ensureUserOrganizationAssociations($primaryUserUIN);

            // Save session
            session([
                'token' => $token,
                'authenticated_user_uin' => $primaryUserUIN,
                'user_data' => $data['data'][0],
                'Regi_Addr' => $data['data'][0]['Regi_Addr'] ?? null,
                'api_authenticated' => true,
            ]);

            // If a token was provided in the query string, clear any existing organization selection
            // to force re-selection, and redirect to a clean URL without the token.
            if ($request->has('token')) {
                Log::info('Token found in URL, clearing organization selection and redirecting to clean URL');
                session()->forget('selected_Orga_UIN');
                
                return redirect($request->fullUrlWithQuery(['token' => null]));
            }

        } catch (\Exception $e) {
            Log::error('API Auth Failed: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->unauthorized($request);
        }

        return $next($request);
    }

    private function unauthorized(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'API authentication required'
            ], 401);
        }

        return response()->view('auth.api-error', [
            'error_message' => 'API authentication required.',
            'error_code' => 'NOT_AUTHENTICATED'
        ], 401);
    }

    private function generateUniqueUIN(string $table, string $pkColumn)
    {
        for ($i = 0; $i < 50; $i++) {
            $microtime = microtime(true);
            $timestampPart = ((int) ($microtime * 1000000)) % 1000000;
            $randomPart = mt_rand(100, 999);
            // Hybrid logic mimicking GeneratesUINs.php Base UIN
            $newUIN = 1500000000 + ($timestampPart * 1000) + $randomPart;

            if (!\Illuminate\Support\Facades\DB::table($table)->where($pkColumn, $newUIN)->exists()) {
                return $newUIN;
            }
        }

        // Fallback
        return (\Illuminate\Support\Facades\DB::table($table)->max($pkColumn) ?? 1500000000) + 1;
    }
}