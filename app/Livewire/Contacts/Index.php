<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\HasMaxConstants;
use App\Models\Admn_Cnta_Link_Mast;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Dist_Mast;
use App\Models\Admn_Grup_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Stat_Mast;
use App\Models\Admn_Tag_Mast;
use App\Models\Admn_User_Mast;
use App\Services\CsvImportService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    use WithFileUploads;
    use HasMaxConstants, \App\Traits\LogsActivity, \App\Traits\ValidatesSubscriptionLimits;

    public ?string $prefixName = null;
    // --- UI State Properties ---
    public bool $showAdvancedSearch = false;
    public int $perPage = 100;
    // --- Search & Filter Properties ---
    public string $search = '';
    public string $sortField = 'CrOn';
    public string $sortDirection = 'desc';
    public $pincodeSuggestions = [];
    public $allCompanies = [];
    public $stateOptions = [];
    public $districtOptions = [];
    public $allGroups = [];

    public array $advancedSearch = [
    'FaNm'                          => '',
    'MiNm'                          => '',   // Middle Name — existing
    'LaNm'                          => '',
    'mobile'                        => '',
    'email'                         => '',
    'address'                       => '',
    'locality'                      => '',   // Location — existing
    'landmark'                      => '',
    'bank_account_number'           => '',   // Bank Account Number — new
    'registration_reference_number' => '',   // Registration / Reference Number — new
    'country'                       => '',
    'state'                         => '',
    'district'                      => '',
    'pincode'                       => '',
        'tags'                          => [],
        'groups'                        => [],
    ];


    #[Computed]
    public function allGroups()
    {
        return Admn_Grup_Mast::where('Admn_Orga_Mast_UIN', session('selected_Orga_UIN'))
            ->withCount('users')
            ->orderBy('Name')
            ->get();
    }

    /**
     * Download sample CSV with tag names as reference
     * Shows all available tags for the organization
     */
    public function downloadTagsSampleCsv()
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=sample_available_tags.csv',
        ];

        $organizationUIN = session('selected_Orga_UIN');

        $tags = Admn_Tag_Mast::query()
            ->where(function ($query) use ($organizationUIN) {
                $query->where('CrBy', 103);
                if ($organizationUIN) {
                    $query->orWhere('Admn_Orga_Mast_UIN', $organizationUIN);
                }
            })
            ->where('Stau', 100201)  // Only Active Tags
            ->where('Admn_Tag_Mast_UIN', '!=', 12) // Exclude Tag 12
            ->orderBy('Name')
            ->get();

        $callback = function () use ($tags) {
            $file = fopen('php://output', 'w');

            // Headers - showing how to use tags in imports
            fputcsv($file, ['Available Tag Names', 'Usage in CSV']);

            // Add header explanation row
            fputcsv($file, ['---', 'Use ANY of these tag names in the "Tag" column', '(Separate multiple tags with commas)']);

            // Data rows with usage example
            foreach ($tags as $tag) {
                fputcsv($file, [
                    $tag->Name,
                    '',
                    ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadPrefixesSampleCsv()
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=sample_name_prefixes.csv',
        ];

        $prefixes = Admn_Prfx_Name_Mast::orderBy('Prfx_Name')->get();

        $callback = function () use ($prefixes) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, ['Prefix Name', 'Prefix Description']);

            // Data rows
            foreach ($prefixes as $prefix) {
                fputcsv($file, [
                    $prefix->Prfx_Name,
                    $prefix->Prfx_Name_Desp ?: 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updatedAdvancedSearchPincode($value)
    {
        if (strlen($value) >= 2) {
            $this->pincodeSuggestions = \App\Models\Admn_PinCode_Mast::where('Code', 'like', $value . '%')
                ->select('Code')
                ->distinct()
                ->take(5)
                ->pluck('Code')
                ->all();
        } else {
            $this->pincodeSuggestions = [];
        }
    }

    /**
     * Sets the pincode value when a user clicks on a suggestion from the list.
     */
    public function selectPincode($pincode)
    {
        $this->advancedSearch['pincode'] = $pincode;
        $this->pincodeSuggestions = [];
    }

    // --- Modal Properties ---
    public bool $showInviteModal = false;
    public ?string $generatedInviteLink = null;
    public bool $showImportModal = false;
    public $csvFile;
    public ?array $importResults = null;
    // --- Data Properties ---
    public $allTags;

    #[On('refresh-contacts')]
    public function refreshContacts()
    {
        // This will trigger a re-render
    }

    public ?string $inviteError = null;

    public function mount()
    {
        $orgaUin = session('selected_Orga_UIN');
        if ($orgaUin) {
            $this->buildQuotaSnapshot((int) $orgaUin);
        }
        $this->loadGroups();
        $this->loadFilteredTags();
    }

    #[On('tags-updated')]
    public function refreshTags()
    {
        $this->loadFilteredTags();
    }

    private function loadGroups()
    {
        $this->allGroups = Admn_Grup_Mast::where('Admn_Orga_Mast_UIN', session('selected_Orga_UIN'))
            ->withCount('users')
            ->orderBy('Name')
            ->get();
    }

    /**
     * Load tags based on the specified criteria:
     * - Tags created by 'System' 103 OR belonging to the selected organization
     * - Including tag with UIN = 12
     */
    private function loadFilteredTags()
    {
        $organizationUIN = session('selected_Orga_UIN');

        $this->allTags = Admn_Tag_Mast::query()
            ->where(function ($query) use ($organizationUIN) {
                $query->where('CrBy', 103);
                if ($organizationUIN) {
                    $query->orWhere('Admn_Orga_Mast_UIN', $organizationUIN);
                }

                $query->orWhere('Admn_Tag_Mast_UIN', 12);
                $query->where('stau', 100201);  // Only Active Tags
            })
            ->orderBy('Name')
            ->get();
    }

    public function clearAdvancedSearch()
    {
        $this->reset('advancedSearch');
    }

    public function applyAdvancedSearch()
    {
        $this->showAdvancedSearch = false;
    }

    public function openAdvancedSearch()
    {
        $this->loadGroups();
        $this->reset('search');
        $this->showAdvancedSearch = true;
    }

    public function hasAdvancedFilters(): bool
    {
        return count(array_filter($this->advancedSearch, function ($value) {
            return !empty($value);
        })) > 0;
    }

    public function getAdvancedFilterCount(): int
    {
        return count(array_filter($this->advancedSearch, function ($value) {
            return !empty($value);
        }));
    }

    public function generateAvatar($contact)
    {
        if (!$contact) {
            return ['initials' => '??', 'color' => '#64748b'];
        }
        $firstName = $contact->FaNm ?? '';
        $lastName = $contact->LaNm ?? '';
        $initials = (substr($firstName, 0, 1) . substr($lastName, 0, 1));
        if (empty(trim($initials))) {
            $initials = '?';
        }
        $colors = ['#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#8b5cf6', '#d946ef'];
        $charSum = array_sum(array_map('ord', str_split($firstName . $lastName)));
        $color = $colors[$charSum % count($colors)];
        return ['initials' => strtoupper($initials), 'color' => $color];
    }

    public function openInviteModal()
    {
        $this->generatedInviteLink = null;
        $this->showInviteModal = true;
    }

    public function generateInviteLink()
    {
        $this->inviteError = null;
        $organizationUIN = session('selected_Orga_UIN');

        if (!$organizationUIN) {
            Log::warning('generateInviteLink: no organization selected', [
                'user' => session('authenticated_user_uin'),
            ]);
            $this->inviteError = 'Cannot generate an invite link. No organization is selected.';
            return;
        }

        try {
            $token = Str::random(40);

            Admn_Cnta_Link_Mast::create([
                'Tokn' => $token,
                'Admn_Orga_Mast_UIN' => $organizationUIN,
                'Cnta_Tag' => 'ByLink',
                'Is_Used' => false,
                'Is_Actv' => self::STATUS_ACTIVE,
                'Expy_Dt' => now()->addHours(24),
                'CrOn' => now(),
                'CrBy' => session('authenticated_user_uin'),
            ]);

            $this->generatedInviteLink = route('contact.create-by-link', ['token' => $token]);

            $this->logActivity('Link Generate', "Generated invite link for organization.");
        } catch (\Throwable $e) {
            Log::error('generateInviteLink failed: ' . $e->getMessage(), [
                'org' => $organizationUIN,
                'user' => session('authenticated_user_uin'),
                'exception' => $e,
            ]);
            $this->inviteError = 'Could not generate link: ' . $e->getMessage();
        }
    }

    public function openImportModal()
    {
        $this->csvFile = null;
        $this->importResults = null;
        $this->showImportModal = true;
    }

    /**
     * Download sample CSV with all columns including Tag support
     * Shows example data for all fields including tags
     */
    public function downloadSampleCsv()
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=sample_contacts.csv',
        ];

        // Updated columns array with Tag column
        $columns = [
            'Name Prefix',
            'Gender',
            'First Name',
            'Bank Account Number',
            'Last Name',
            'Company Name',
            'Designation',
            'Birthday (DD/MM/YYYY)',
            'Notes',
            'Phone Label',
            'Website',
            'Facebook',
            'Contry Code',
            'Phone',
            'Email',
            'Tag',  // NEW: Tag column
        ];

        // Updated sample data with example tag
        $data = [
            'Mr.',
            'Male',
            'John',
            '',
            'Doe',
            'Example Corp',
            'Manager',
            '15/05/1990',
            'This is a note.',
            'Office',
            'http://www.example.com',
            'http://www.facebook.com/johndoe',
            '91',
            '5551234567',
            'example@email.com',
            ''  // NEW: Example tags (comma-separated for multiple tags)
        ];

        // Another example row with single tag
        $data2 = [
            'Ms.',
            'Female',
            'Jane',
            'Marie',
            'Smith',
            'Tech Solutions',
            'Developer',
            '22/08/1992',
            'Another note.',
            'Mobile',
            'http://www.example.com/jane',
            'http://www.facebook.com/janesmith',
            '91',
            '5559876543',
            'jane@email.com',
            ''
        ];

        $callback = function () use ($columns, $data, $data2) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Row 1 with multiple tags
            fputcsv($file, $data);

            // Row 2 with single tag
            fputcsv($file, $data2);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import contacts from CSV file
     * Now supports tag assignment during import
     */
    public function importContacts()
    {
        if (!\App\Traits\ManagesSettings::hasPermission('Import')) {
            $this->addError('csvFile', 'You do not have permission to import contacts.');
            return;
        }

        $this->validate(['csvFile' => 'required|mimes:csv,txt|max:10240']);

        try {
            $currentOrgUIN = session('selected_Orga_UIN');

            \Log::info('Import attempt - Session Org UIN: ' . $currentOrgUIN);
            \Log::info('Import attempt - Auth User UIN: ' . session('authenticated_user_uin'));

            if (!$currentOrgUIN) {
                $this->addError('csvFile', 'No organization selected. Please select an organization first.');
                return;
            }

            $orgExists = \DB::table('admn_orga_mast')->where('Orga_UIN', $currentOrgUIN)->exists();
            if (!$orgExists) {
                $this->addError('csvFile', 'Selected organization not found.');
                return;
            }

            // Initialize the import service
            $importer = new CsvImportService();

            // Process the CSV file - now includes tag handling
            $results = $importer->process($this->csvFile->getRealPath(), $currentOrgUIN);

            if (!$results) {
                throw new \Exception('Import service returned null results');
            }

            $this->importResults = $results;

            \Log::info('Import results: ', $results);

            // Handle success/warning messages
            if ($results['imported'] > 0) {
                $message = "{$results['imported']} contact" . ($results['imported'] > 1 ? 's' : '') . ' imported successfully.';
                session()->flash('message', $message);
            }

            $dupCount = count($results['duplicates'] ?? []);
            $skipCount = $results['skipped'] ?? 0;
            $countToLog = $results['imported'] > 0 ? $results['imported'] : ($dupCount + $skipCount);

            if ($countToLog > 0 || !empty($results)) {
                $descMsg = "Imported {$results['imported']} contacts";
                if ($dupCount > 0) {
                    $descMsg .= ", {$dupCount} duplicates skipped";
                }
                if ($skipCount > 0) {
                    $descMsg .= ", {$skipCount} skipped";
                }
                $this->logActivity('Import Data', $descMsg . '.', max(1, $results['imported']));
            }

            if (!empty($results['duplicates'])) {
                $dupCount = count($results['duplicates']);
                $warning = "{$dupCount} duplicate" . ($dupCount > 1 ? 's' : '') . ' found and skipped.';
                session()->flash('warning', $warning);
            } elseif ($results['skipped'] > 0) {
                $warning = "{$results['skipped']} row" . ($results['skipped'] > 1 ? 's' : '') . ' skipped due to errors.';
                session()->flash('warning', $warning);
            }

            // Error handling
            if ($results['imported'] == 0 && $results['skipped'] > 0) {
                \Log::error('All rows were skipped during import', $results);
                $this->addError('csvFile', 'All rows were skipped during import. Please check the CSV format and tag names.');
            }

            if ($results['imported'] == 0 && $results['skipped'] == 0) {
                $this->addError('csvFile', 'No data was processed. Please check if the CSV file contains valid data.');
            }

            // Check if limit was reached
            if (isset($results['limit_message'])) {
                session()->flash('info', $results['limit_message']);
            }
        } catch (\Exception $e) {
            \Log::error('CSV Import Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('csvFile', 'There was an error processing your file: ' . $e->getMessage());
            $this->importResults = null;
        } finally {
            $this->csvFile = null;
        }
    }

    public function exportCsv()
    {
        if (!\App\Traits\ManagesSettings::hasPermission('Export')) {
            session()->flash('error', 'You do not have permission to export contacts.');
            return;
        }

        $query = $this->getFilteredQuery();

        $contacts = $query->with([
            'tags',
            'phones' => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
            'emails' => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
            'addresses' => fn($q) => $q->where('Is_Prmy', true)->with(['country', 'state', 'district', 'pincode']),
        ])->get();

        $this->logActivity('Export Data', "Exported {$contacts->count()} contacts to CSV.", 1);

        $filename = 'contacts_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $selectedColumns = session('export_selected_columns', []);
        
        $columnLabels = [
            'name_prefix'     => 'Name Prefix',
            'gender'          => 'Gender',
            'first_name'      => 'First Name',
            'middle_name'     => 'Middle Name',
            'last_name'       => 'Last Name',
            'birthday'        => 'Birthday (DD/MM/YYYY)',
            'self_employed'   => 'Self Employed',
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
            'primary_address' => 'Primary Address',
            'bank_name'       => 'Primary Bank Name',
            'bank_account'    => 'Primary Bank A/c Number',
            'bank_acc_type'   => 'Primary A/c Type',
            'bank_ifsc'       => 'Primary IFSC Code',
            'document_type'   => 'Primary Document Type',
            'document_number' => 'Primary Document Number',
            'ref_person_name' => 'Primary Reference Name',
            'ref_person_rel'  => 'Primary Reference Relationship',
            'ref_person_phone'=> 'Primary Reference Phone',
            'ref_person_email'=> 'Primary Reference Email',
            'tags'            => 'Tags',
            'groups'          => 'Groups',
            'website'         => 'Website',
            'facebook'        => 'Facebook',
            'twitter'         => 'Twitter',
            'linkedin'        => 'LinkedIn',
            'instagram'       => 'Instagram',
            'notes'           => 'Notes',
        ];

        // If no columns are selected, fallback to default setup
        if (empty($selectedColumns)) {
            $selectedColumns = collect($columnLabels)->map(fn($v, $k) => ['key' => $k])->toArray();
        }

        $headersArray = [];
        foreach ($selectedColumns as $col) {
            $headersArray[] = $columnLabels[$col['key']] ?? $col['key'];
        }

        $callback = function () use ($contacts, $headersArray, $selectedColumns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headersArray);

            foreach ($contacts as $contact) {
                // Helpers
                $phones = collect($contact->phones ?? []);
                $phone1 = $phones->get(0);
                $phone2 = $phones->get(1);
                $phone3 = $phones->get(2);

                $emails = collect($contact->emails ?? []);
                $email1 = $emails->get(0);
                $email2 = $emails->get(1);
                $email3 = $emails->get(2);

                $primaryAddress = collect($contact->addresses ?? [])->first();
                $fullAddressString = '';
                if ($primaryAddress) {
                    $addressParts = [
                        $primaryAddress->Addr,
                        $primaryAddress->Loca,
                        $primaryAddress->Lndm,
                        optional($primaryAddress->pincode)->Code,
                        optional($primaryAddress->district)->Name,
                        optional($primaryAddress->state)->Name,
                        optional($primaryAddress->country)->Name,
                    ];
                    $fullAddressString = implode(', ', array_filter($addressParts));
                }
                
                $primaryBank = collect($contact->bankAccounts ?? [])->where('Prmy', true)->first();
                $primaryDoc = collect($contact->documents ?? [])->where('Prmy', true)->first();
                $primaryRef = collect($contact->referencePersons ?? [])->where('Is_Prmy', true)->first();

                // Build Data Map
                $dataMap = [
                    'name_prefix'     => optional($contact->prefix)->Prfx_Name ?? '',
                    'gender'          => $contact->Gend ?? '',
                    'first_name'      => $contact->FaNm ?? '',
                    'middle_name'     => $contact->MiNm ?? '',
                    'last_name'       => $contact->LaNm ?? '',
                    'birthday'        => $contact->Brth_Dt ? \Carbon\Carbon::parse($contact->Brth_Dt)->format('d/m/Y') : '',
                    'self_employed'   => $contact->Prfl_Name ? $contact->Prfl_Name : 'No',
                    'phone1_uses_of'  => $phone1 ? $this->normalizePhoneTypeForExport($phone1->Phon_Type) : '',
                    'phone1_number'   => optional($phone1)->Phon_Numb ?? '',
                    'phone2_uses_of'  => $phone2 ? $this->normalizePhoneTypeForExport($phone2->Phon_Type) : '',
                    'phone2_number'   => optional($phone2)->Phon_Numb ?? '',
                    'phone3_uses_of'  => $phone3 ? $this->normalizePhoneTypeForExport($phone3->Phon_Type) : '',
                    'phone3_number'   => optional($phone3)->Phon_Numb ?? '',
                    'email1'          => optional($email1)->Emai_Addr ?? '',
                    'email2'          => optional($email2)->Emai_Addr ?? '',
                    'email3'          => optional($email3)->Emai_Addr ?? '',
                    'address_type'    => optional($primaryAddress)->Admn_Addr_Type_Mast_UIN ?? '',
                    'primary_address' => $fullAddressString,
                    'bank_name'       => optional($primaryBank)->Bank_Name ?? '',
                    'bank_account'    => optional($primaryBank)->Acnt_Numb ?? '',
                    'bank_acc_type'   => optional($primaryBank)->Acnt_Type ?? '',
                    'bank_ifsc'       => optional($primaryBank)->IFSC_Code ?? '',
                    'document_type'   => optional($primaryDoc)->Docu_Name ?? '',
                    'document_number' => optional($primaryDoc)->Regn_Numb ?? '',
                    'ref_person_name' => optional($primaryRef)->Refa_Name ?? '',
                    'ref_person_rel'  => optional($primaryRef)->Refa_Rsip ?? '',
                    'ref_person_phone'=> optional($primaryRef)->Refa_Phon ?? '',
                    'ref_person_email'=> optional($primaryRef)->Refa_Emai ?? '',
                    'tags'            => $contact->tags ? $contact->tags->pluck('Name')->implode(', ') : '',
                    'groups'          => optional($contact->group)->Name ?? '',
                    'website'         => $contact->Web ?? '',
                    'facebook'        => $contact->FcBk ?? '',
                    'twitter'         => $contact->Twtr ?? '',
                    'linkedin'        => $contact->LnDn ?? '',
                    'instagram'       => $contact->Intg ?? '',
                    'notes'           => $contact->Note ?? '',
                ];

                $row = [];
                foreach ($selectedColumns as $col) {
                    $row[] = $dataMap[$col['key']] ?? '';
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Normalize phone type for export (reverse of import normalization)
     */
    private function normalizePhoneTypeForExport($phoneType)
    {
        if (empty($phoneType)) {
            return 'Mobile';
        }

        $mapping = [
            'work' => 'Work',
            'home' => 'Home',
            'self' => 'Self',
            'office' => 'Work',
            'business' => 'Work',
        ];

        return $mapping[strtolower($phoneType)] ?? 'Mobile';
    }

    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatedAdvancedSearch($value, $key)
    {
        // Placeholder for advanced search updates
    }

    private function getFilteredQuery()
    {
        $query = Admn_User_Mast::query()
            ->with(['referencePersons',
                'phones' => fn($q) => $q->where('Is_Prmy', true),
                'emails' => fn($q) => $q->where('Is_Prmy', true),
                'addresses' => fn($q) => $q->where('Is_Prmy', true)->with(['country', 'state', 'district', 'pincode']),
                'tags',
                'group'
            ])
            ->where('Is_Actv', self::STATUS_ACTIVE)
            ->where('Admn_Orga_Mast_UIN', session('selected_Orga_UIN'));

        $query->when($this->search, fn($q, $v) => $q->search($v));

        $query->when(!empty($this->advancedSearch['FaNm']), fn($q) => $q->where('FaNm', 'like', "%{$this->advancedSearch['FaNm']}%"));
        // Middle Name — existing
        $query->when(!empty($this->advancedSearch['MiNm']), fn($q) => $q->where('MiNm', 'like', "%{$this->advancedSearch['MiNm']}%"));

        // Locality — existing
        $query->when(!empty($this->advancedSearch['locality']), fn($q) => $q->whereHas('addresses', fn($subQ) => $subQ->where('Loca', 'like', "%{$this->advancedSearch['locality']}%")));


        // Bank Account Number — search via admn_user_bank_mast (Acnt_Numb)
        $query->when(!empty($this->advancedSearch['bank_account_number']), function ($q) {
            $acnt = $this->advancedSearch['bank_account_number'];
            $q->whereHas('bankAccounts', fn($subQ) => $subQ->where('Acnt_Numb', 'like', "%{$acnt}%"));
        });

        $query->when(!empty($this->advancedSearch['LaNm']), fn($q) => $q->where('LaNm', 'like', "%{$this->advancedSearch['LaNm']}%"));
        $query->when(!empty($this->advancedSearch['mobile']), fn($q) => $q->whereHas('phones', fn($subQ) => $subQ->where('Phon_Numb', 'like', "%{$this->advancedSearch['mobile']}%")));
        $query->when(!empty($this->advancedSearch['address']), fn($q) => $q->whereHas('addresses', fn($subQ) => $subQ->where('Addr', 'like', "%{$this->advancedSearch['address']}%")));

        // Registration / Reference Number — search via admn_docu_mast (Regn_Numb)
        $query->when(!empty($this->advancedSearch['registration_reference_number']), function ($q) {
            $regn = $this->advancedSearch['registration_reference_number'];
            $q->whereHas('documents', fn($subQ) => $subQ->where('Regn_Numb', 'like', "%{$regn}%"));
        });

        $query->when(!empty($this->advancedSearch['landmark']), fn($q) => $q->whereHas('addresses', fn($subQ) => $subQ->where('Lndm', 'like', "%{$this->advancedSearch['landmark']}%")));
        $query->when(!empty($this->advancedSearch['country']), fn($q) => $q->whereHas('addresses', fn($subQ) => $subQ->where('Admn_Cutr_Mast_UIN', $this->advancedSearch['country'])));
        $query->when(!empty($this->advancedSearch['state']), fn($q) => $q->whereHas('addresses', fn($subQ) => $subQ->where('Admn_Stat_Mast_UIN', $this->advancedSearch['state'])));
        $query->when(!empty($this->advancedSearch['district']), fn($q) => $q->whereHas('addresses', fn($subQ) => $subQ->where('Admn_Dist_Mast_UIN', $this->advancedSearch['district'])));
        $query->when(!empty($this->advancedSearch['pincode']), fn($q) => $q->whereHas('addresses.pincode', fn($subQ) => $subQ->where('Code', $this->advancedSearch['pincode'])));

        $query->when(!empty($this->advancedSearch['email']), function ($q) {
            $email = $this->advancedSearch['email'];
            $q->where(function ($query) use ($email) {
                $query
                    ->whereHas('emails', function ($subQ) use ($email) {
                        $subQ->where('Emai_Addr', 'like', "%{$email}%");
                    })
                    ->orWhereHas('referencePersons', function ($subQ) use ($email) {
                        $subQ->where('Refa_Emai', 'like', "%{$email}%");
                    });
            });
        });

        $query->when(!empty($this->advancedSearch['tags']), function ($q) {
            $tagIds = $this->advancedSearch['tags'];
            $q->whereHas('tags', function ($subQ) use ($tagIds) {
                $subQ->whereIn('admn_tag_mast.Admn_Tag_Mast_UIN', $tagIds);
            });
        });

        $query->when(!empty($this->advancedSearch['groups']), function ($q) {
            $groupIds = $this->advancedSearch['groups'];
            $q->whereIn('Admn_Grup_Mast_UIN', $groupIds);
        });

        if (in_array($this->sortField, ['FaNm', 'personal_info'])) {
            $sortColumn = 'FaNm';
            $query->orderBy($sortColumn, $this->sortDirection);

            if ($sortColumn === 'FaNm') {
                $query->orderBy('LaNm', $this->sortDirection);
            }
        } else {
            $query->orderByRaw('CASE WHEN Is_Vf = 100206 THEN 0 ELSE 1 END ASC');
            $query->orderBy('CrOn', 'desc');
        }

        return $query;
    }

    public function deleteContact(int $contactUIN)
    {
        if (!\App\Traits\ManagesSettings::hasPermission('Delete')) {
            session()->flash('error', 'You do not have permission to delete contacts.');
            return;
        }

        $contact = Admn_User_Mast::find($contactUIN);

        if ($contact) {
            $contact->Is_Actv = 100202;
            $contact->Del_By = session('authenticated_user_uin');
            $contact->Del_On = now();
            $contact->save();

            $name = trim(($contact->FaNm ?? '') . ' ' . ($contact->LaNm ?? ''));
            $this->logActivity('Delete Entry', "Deleted contact: " . ($name ?: "ID:$contactUIN"));

            session()->flash('message', 'Contact successfully moved to trash.');
        }
    }

    #[Computed]
    public function countries()
    {
        return Admn_Cutr_Mast::orderBy('Name')->get();
    }

    public function updatedAdvancedSearchCountry($countryUIN)
    {
        $this->advancedSearch['state'] = '';
        $this->advancedSearch['district'] = '';
        $this->stateOptions = [];
        $this->districtOptions = [];

        if ($countryUIN) {
            $this->stateOptions = Admn_Stat_Mast::where('Admn_Cutr_Mast_UIN', $countryUIN)
                ->orderBy('Name')
                ->get();
        }
    }

    public function updatedAdvancedSearchState($stateUIN)
    {
        $this->advancedSearch['district'] = '';
        $this->districtOptions = [];

        if ($stateUIN) {
            $this->districtOptions = Admn_Dist_Mast::where('Admn_Stat_Mast_UIN', $stateUIN)
                ->orderBy('Name')
                ->get();
        }
    }

    public function render()
    {
        return view('livewire.contacts.index', [
            'contacts' => $this->getFilteredQuery()->paginate($this->perPage),
            'isPlanExpired' => $this->isPlanExpired(),
        ])->layout('components.layouts.app');
    }
}