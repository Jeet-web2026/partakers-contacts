<?php

namespace App\Http\Controllers;

use App\Models\Admn_Cnta_Link_Mast;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Dist_Mast;
use App\Models\Admn_Grup_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Stat_Mast;
use App\Models\Admn_Tag_Mast;
use App\Models\Admn_User_Mast;
use App\Models\Admn_Bank_Mast;
use App\Models\Admn_Docu_Type_Mast;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;

class ContactExportController extends Controller
{
    use LogsActivity;

    private array $allColumnLabels = [
        'name_prefix'     => 'Name Prefix',
        'first_name'      => 'First Name',
        'middle_name'     => 'Middle Name',
        'last_name'       => 'Last Name',
        'birthday'        => 'Birthday (DD/MM/YYYY)',
        'gender'          => 'Gender',
        'phone1_uses_of'  => 'Phone 1 Uses of',
        'phone1_number'   => 'Phone 1 Number',
        'phone2_uses_of'  => 'Phone 2 Uses of',
        'phone2_number'   => 'Phone 2 Number',
        'phone3_uses_of'  => 'Phone 3 Uses of',
        'phone3_number'   => 'Phone 3 Number',
        'email1'          => 'Email 1',
        'email2'          => 'Email 2',
        'email3'          => 'Email 3',
        'address_type'    => 'Address Type',
        'primary_address' => 'Address',
        'bank_name'       => 'Bank Name',
        'bank_account'    => 'Bank A/c Number',
        'bank_acc_type'   => 'A/c Type',
        'bank_ifsc'       => 'IFSC Code',
        'document_name'   => 'Document Name',
        'document_type'   => 'Document Type',
        'document_number' => 'Document Number',
        'tags'            => 'Tags',
        'groups'          => 'Groups',
        'website'         => 'Website',
        'facebook'        => 'Facebook',
        'twitter'         => 'Twitter',
        'linkedin'        => 'LinkedIn',
        'instagram'       => 'Instagram',
        'notes'           => 'Notes',
    ];

    public function exportCsv(Request $request)
    {
        $selectedColumnsData = session('export_selected_columns', []);
        session()->forget('export_selected_columns');

        $org = session('selected_Orga_UIN')
            ?? session('orga_uin')
            ?? session('organization_uin')
            ?? $request->header('X-Organization-UIN');

        if (!$org && session('authenticated_user_uin')) {
            $org = \Illuminate\Support\Facades\DB::table('admn_user_mast')
                ->where('Admn_User_Mast_UIN', session('authenticated_user_uin'))
                ->value('Admn_Orga_Mast_UIN')
                ?? \Illuminate\Support\Facades\DB::table('tbl_tran_user_assc')
                ->where('User_UIN', session('authenticated_user_uin'))
                ->value('Orga_UIN');
        }

        $query = Admn_User_Mast::where('Admn_Orga_Mast_UIN', $org)
            ->where('Is_Actv', 100201);

        // ============================================================
        // EXTRACT FILTERS
        // ============================================================
        $filters = $this->extractFilters($selectedColumnsData);
        \Illuminate\Support\Facades\Log::info('Export Filters Debug:', $filters);

        // ============================================================
        // ADDRESS TYPE FILTER — Hard AND constraint
        //
        // Case 1 — "All" / no specific address type selected:
        //   Return all contacts.
        //   In CSV → show their PRIMARY address + its type.
        //
        // Case 2 — Specific type selected (e.g. "Home"):
        //   Return only contacts who HAVE an address of that type.
        //   In CSV → show THAT specific address + its type (not necessarily primary).
        // ============================================================
        $specificAddressTypeSelected = isset($filters['address_type'])
                                       && $filters['address_type'] !== ''
                                       && $filters['address_type'] !== 'all';

        if ($specificAddressTypeSelected) {
            $query->whereHas('addresses', function ($q) use ($filters) {
                $q->where('Admn_Addr_Type_Mast_UIN', $filters['address_type']);
            });
        }

        // ============================================================
        // DOCUMENT FILTER — Hard AND constraint
        //
        // Case 1 — "All" / no specific document selected:
        //   Return all contacts.
        //   In CSV → show their PRIMARY document.
        //
        // Case 2 — Specific document name selected (e.g. "PAN Card"):
        //   Return only contacts who HAVE that document (primary or not).
        //   In CSV → show THAT specific document.
        // ============================================================
        $specificDocumentSelected = isset($filters['document_name'])
                                    && $filters['document_name'] !== ''
                                    && $filters['document_name'] !== 'all';

        if ($specificDocumentSelected) {
            $query->whereHas('documents', function ($q) use ($filters) {
                $q->where('Docu_Name', $filters['document_name']);
            });
        }

        // ============================================================
        // GENDER FILTER
        // ============================================================
        if (!empty($filters['gender']) && $filters['gender'] !== 'all') {
            $query->where('Gend', $filters['gender']);
        }

        // ============================================================
        // BANK NAME FILTER — Hard AND constraint
        // ============================================================
        if (!empty($filters['bank_name'])) {
            $query->whereHas('bankAccounts', function ($q) use ($filters) {
                $q->where('Bank_Name_UIN', $filters['bank_name']);
            });
        }

        // ============================================================
        // EAGER LOAD RELATIONSHIPS
        //
        // For addresses:
        //   Case 1 (All types): eager-load only the PRIMARY address
        //   Case 2 (Specific type): eager-load addresses of THAT type only
        // ============================================================
        $contacts = $query->with([
            'tags',
            'prefix',
            'phones'    => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
            'emails'    => fn($q) => $q->orderBy('Is_Prmy', 'desc'),

            'addresses' => function ($q) use ($specificAddressTypeSelected, $filters) {
                if ($specificAddressTypeSelected) {
                    // Load addresses of the selected type (ordered so primary comes first)
                    $q->where('Admn_Addr_Type_Mast_UIN', $filters['address_type'])
                      ->orderBy('Is_Prmy', 'desc');
                } else {
                    // Load only the primary address
                    $q->where('Is_Prmy', true);
                }
                $q->with(['country', 'state', 'district', 'pincode', 'type']);
            },

            'bankAccounts' => fn($q) => $q->orderBy('Prmy', 'desc')->with('bank'),
            'documents'    => fn($q) => $q->orderBy('Prmy', 'desc')->with('documentType'),
            'group',
        ])->get();

        $selectedColumns = $this->extractColumnKeys($selectedColumnsData);

        $filename = 'contacts_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $csvHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $allColumnLabels = $this->allColumnLabels;

        $callback = function () use (
            $contacts,
            $selectedColumns,
            $allColumnLabels,
            $filters,
            $specificDocumentSelected,
            $specificAddressTypeSelected
        ) {
            $file = fopen('php://output', 'w');

            // Write CSV header row
            $headerRow = [];
            foreach ($selectedColumns as $key) {
                if (isset($allColumnLabels[$key])) {
                    $headerRow[] = $allColumnLabels[$key];
                }
            }
            fputcsv($file, $headerRow);

            foreach ($contacts as $contact) {

                // PHONES
                $phones = $contact->phones;
                $phone1 = $phones->get(0);
                $phone2 = $phones->get(1);
                $phone3 = $phones->get(2);

                // EMAILS
                $emails = $contact->emails;
                $email1 = $emails->get(0);
                $email2 = $emails->get(1);
                $email3 = $emails->get(2);

                // ------------------------------------------------
                // ADDRESS SELECTION
                //
                // Case 1 — "All" types:
                //   Eager-loaded only Is_Prmy = true → just pick first().
                //
                // Case 2 — Specific type (e.g. "Home"):
                //   Eager-loaded all addresses of that type, ordered by Is_Prmy desc.
                //   → Pick the primary one among them; fallback to first.
                //   This means if a contact has a "Home" address that is also primary,
                //   that shows. If "Home" exists but is not primary, it still shows.
                // ------------------------------------------------
                $loadedAddresses = $contact->addresses;

                if ($specificAddressTypeSelected) {
                    // Among the loaded type-filtered addresses, prefer primary
                    $targetAddress = $loadedAddresses->firstWhere('Is_Prmy', true)
                                  ?? $loadedAddresses->first();
                } else {
                    // Only the primary address was loaded
                    $targetAddress = $loadedAddresses->first();
                }

                $fullAddressString = '';
                $addressType       = '';

                if ($targetAddress) {
                    $addressParts = [
                        $targetAddress->Addr,
                        $targetAddress->Loca,
                        $targetAddress->Lndm,
                        optional($targetAddress->pincode)->Code,
                        optional($targetAddress->district)->Name,
                        optional($targetAddress->state)->Name,
                        optional($targetAddress->country)->Name,
                    ];
                    $fullAddressString = implode(', ', array_filter($addressParts));
                    $addressType       = optional($targetAddress->type)->Name ?? '';
                }

                // ------------------------------------------------
                // BANK ACCOUNT — Primary if exists, otherwise first
                // ------------------------------------------------
                $bankAccounts = $contact->bankAccounts;
                $primaryBank  = $bankAccounts->firstWhere('Prmy', 1) ?? $bankAccounts->first();

                $bankName    = optional(optional($primaryBank)->bank)->Bank_Name ?? '';
                $bankAccount = optional($primaryBank)->Acnt_Numb ?? '';
                $bankAccType = optional($primaryBank)->Acnt_Type ?? '';
                $bankIfsc    = optional($primaryBank)->IFSC_Code ?? '';

                // ------------------------------------------------
                // DOCUMENT SELECTION
                //
                // Case 1 — "All": show PRIMARY document, fallback to first.
                // Case 2 — Specific name: show THAT document (guaranteed to exist).
                // ------------------------------------------------
                $documents = $contact->documents;

                if ($specificDocumentSelected) {
                    $targetDoc = $documents->firstWhere('Docu_Name', $filters['document_name']);
                } else {
                    $targetDoc = $documents->firstWhere('Prmy', 1) ?? $documents->first();
                }

                $docName   = optional($targetDoc)->Docu_Name ?? '';
                $docType   = optional(optional($targetDoc)->documentType)->Docu_Name ?? '';
                $docNumber = optional($targetDoc)->Regn_Numb ?? '';

                // FULL DATA MAP
                $allData = [
                    'name_prefix'     => optional($contact->prefix)->Prfx_Name ?? '',
                    'first_name'      => $contact->FaNm ?? '',
                    'middle_name'     => $contact->MiNm ?? '',
                    'last_name'       => $contact->LaNm ?? '',
                    'birthday'        => $contact->Brth_Dt
                                            ? \Carbon\Carbon::parse($contact->Brth_Dt)->format('d/m/Y')
                                            : '',
                    'gender'          => $contact->Gend ?? '',
                    'phone1_uses_of'  => $phone1 ? $this->normalizePhoneTypeForExport($phone1->Phon_Type) : '',
                    'phone1_number'   => $this->formatPhoneWithApps($phone1),
                    'phone2_uses_of'  => $phone2 ? $this->normalizePhoneTypeForExport($phone2->Phon_Type) : '',
                    'phone2_number'   => $this->formatPhoneWithApps($phone2),
                    'phone3_uses_of'  => $phone3 ? $this->normalizePhoneTypeForExport($phone3->Phon_Type) : '',
                    'phone3_number'   => $this->formatPhoneWithApps($phone3),
                    'email1'          => optional($email1)->Emai_Addr ?? '',
                    'email2'          => optional($email2)->Emai_Addr ?? '',
                    'email3'          => optional($email3)->Emai_Addr ?? '',
                    'address_type'    => $addressType,       // ← type name of the selected/primary address
                    'primary_address' => $fullAddressString, // ← full address string
                    'bank_name'       => $bankName,
                    'bank_account'    => $bankAccount,
                    'bank_acc_type'   => $bankAccType,
                    'bank_ifsc'       => $bankIfsc,
                    'document_name'   => $docName,
                    'document_type'   => $docType,
                    'document_number' => $docNumber,
                    'tags'            => $contact->tags->pluck('Name')->implode(', '),
                    'groups'          => optional($contact->group)->Name ?? '',
                    'website'         => $contact->Web  ?? '',
                    'facebook'        => $contact->FcBk ?? '',
                    'twitter'         => $contact->Twtr ?? '',
                    'linkedin'        => $contact->LnDn ?? '',
                    'instagram'       => $contact->Intg ?? '',
                    'notes'           => $contact->Note ?? '',
                ];

                $row = [];
                foreach ($selectedColumns as $key) {
                    if (isset($allColumnLabels[$key])) {
                        $row[] = $allData[$key] ?? '';
                    }
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        $this->logActivity('Export Data', "Exported {$contacts->count()} contacts to CSV.", 1);

        return response()->stream($callback, 200, $csvHeaders);
    }

    private function extractFilters(array $selectedColumnsData): array
    {
        $filters = [
            'gender'        => null,
            'address_type'  => null,
            'bank_name'     => null,
            'document_name' => null,
        ];

        foreach ($selectedColumnsData as $item) {
            $key         = is_array($item) ? ($item['key']          ?? null) : $item;
            $filterValue = is_array($item) ? ($item['filter_value'] ?? null) : null;

            if (in_array($key, ['gender', 'address_type', 'bank_name', 'document_name'])) {
                $filters[$key] = $filterValue;
            }
        }

        return $filters;
    }

    private function extractColumnKeys(array $selectedColumnsData): array
    {
        $keys = [];

        foreach ($selectedColumnsData as $item) {
            $key    = is_array($item) ? ($item['key'] ?? $item) : $item;
            $keys[] = $key;

            // Auto-inject 'primary_address' immediately after 'address_type'
            // because the frontend has no separate checkbox for the address value.
            // Selecting "Address Type" implicitly means: also export the full address.
            if ($key === 'address_type' && !in_array('primary_address', $keys)) {
                $keys[] = 'primary_address';
            }
        }

        return $keys;
    }

    private function normalizePhoneTypeForExport(?string $phoneType): string
    {
        if (empty($phoneType)) {
            return 'Mobile';
        }

        $mapping = [
            'work'     => 'Work',
            'home'     => 'Home',
            'self'     => 'Self',
            'office'   => 'Work',
            'business' => 'Work',
        ];

        return $mapping[strtolower($phoneType)] ?? 'Mobile';
    }

    private function formatPhoneWithApps($phone): string
    {
        if (!$phone || !$phone->Phon_Numb) {
            return '';
        }

        $number     = $phone->Phon_Numb;
        $indicators = [];

        if ($phone->Has_WtAp) {
            $indicators[] = 'WhatsApp';
        }
        if ($phone->Has_Telg) {
            $indicators[] = 'Telegram';
        }

        if (!empty($indicators)) {
            return $number . ' (' . implode(', ', $indicators) . ')';
        }

        return $number;
    }
}