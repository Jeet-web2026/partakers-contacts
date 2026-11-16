<?php

namespace App\Livewire\Contacts;
use App\Livewire\Traits\HasMaxConstants;
use App\Livewire\Traits\FormatsDates;
use App\Livewire\Traits\GeneratesUINs;
use App\Livewire\Traits\HasAddressCascade;

use App\Livewire\Traits\LoadsReferenceData;
use App\Livewire\Traits\WithComments;
use App\Livewire\Traits\WithContactValidation;
use App\Livewire\Traits\WithDocumentNames;
use App\Traits\LogsActivity;
use App\Traits\ManagesSettings;
use App\Traits\ValidatesSubscriptionLimits;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Grup_Mast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{

    use HasMaxConstants;
    use FormatsDates;
    use GeneratesUINs;
    use HasAddressCascade;
    use LoadsReferenceData;
    use WithComments, WithContactValidation, WithDocumentNames, WithFileUploads, LogsActivity, ManagesSettings, ValidatesSubscriptionLimits;

    // ============================================
    // BASIC PROPERTIES
    // ============================================
    public $contactId;

    public $contact;

    public $MoOn;
    public $noteCreatedBy;
    public $noteCreatedAt;

    // Note Sidebar
    public bool $showCreateModal = false;

    public string $newNoteContent = '';

    public string $activeCommentTab = '';


    public ?int $verticalId = 5;

    // ============================================
    // PERSONAL DETAILS PROPERTIES
    // ============================================
    public $Prfx_UIN = null;

    public $FaNm = '';

    public $MiNm = '';

    public $LaNm = '';

    public $Gend = '';

    public $Blood_Grp = '';

    public $Brth_Dt;

    public $Anvy_Dt;

    public $Deth_Dt;

    public $Prfl_Pict;

    public $existing_avatar;

    // ============================================
    // EMPLOYMENT PROPERTIES (REMOVED)
    // ============================================
    public $Note = '';

    // ============================================
    // WEB PRESENCE PROPERTIES
    // ============================================
    public $Web = '';

    public $FcBk = '';

    public $Twtr = '';

    public $LnDn = '';

    public $Intg = '';

    public $Yaho = '';

    public $Redt = '';

    public $Ytb = '';

    // Present Employment
    public $Empl_Type = 'job';
    public $Comp_Name = '';
    public $Comp_Dsig = '';
    public $Comp_LdLi = '';
    public $Comp_Desp = '';
    public $Comp_Emai = '';
    public $Comp_Web = '';
    public $Comp_Addr = '';
    
    // Feature Enablement
    public bool $attachmentEnabled = true;

    // ============================================
    // PARTY TYPE
    // ============================================

    public $Prty = 'I';

    public array $partyOptions = [
        'I' => 'Individual/Personal',
        'B' => 'Business/Organization',
    ];

    // ============================================
    // COLLECTION PROPERTIES
    // ============================================

    public array $emails = [];

    public array $phones = [];

    public array $landlines = [];

    public array $contactNotes = [];

    public ?string $newNote = null;

    public array $addresses = [];

    public array $bankAccounts = [];

    public array $documents = [];

    public array $references = [];


    public array $selectedTags = [];

    public array $availableGroups = [];

    // ============================================
    // REFERENCE DATA PROPERTIES
    // ============================================
    public $allTags = [];

    public $allCountries = [];

    public $allPrefixes = [];

    public $addressTypes = [];

    public $BaddressTypes = [];

    public $bankOptions = [];

    public $allDocumentTypes = [];
    public $workTypes = [];

    public ?int $assignedGroupId = null;

    protected function setDefaultQuickCommentTab(): void
    {
        $comments = $this->getQuickComments();

        if ($comments->isNotEmpty()) {
            $this->activeCommentTab = trim($comments->keys()->first());
        }
    }

    // ============================================
    // MOUNT
    // ============================================

    public function mount()
    {
        if (!\App\Traits\ManagesSettings::hasPermission('Add')) {
            abort(403, 'Unauthorized access: You do not have permission to add contacts.');
        }

        try {
            $this->setDefaultQuickCommentTab();
            $this->Prty = 'I';
            $this->loadCommonReferenceData();
            $this->loadAvailableGroups();
            $this->initializeCollections();
            $this->initializeWithDocumentNames();
            $this->noteCreatedBy = session('authenticated_user_uin');
            $this->noteCreatedAt = now();

            $this->initializeFeatureEnablement();
        } catch (\Exception $e) {
            Log::error('Mount Error: ' . $e->getMessage());
            session()->flash('error', 'Error loading form data: ' . $e->getMessage());
        }
    }

    // ============================================
    // LOAD AVAILABLE GROUPS
    // ============================================

    private function loadAvailableGroups()
    {
        try {
            $orgUIN = session('selected_Orga_UIN');

            if (!$orgUIN) {
                Log::warning('No organization selected when loading available groups');
                $this->availableGroups = [];

                return;
            }

            $this->availableGroups = Admn_Grup_Mast::where('Admn_Orga_Mast_UIN', $orgUIN)
                ->where('Is_Actv', self::STATUS_ACTIVE)
                ->orderBy('Name')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading available groups: ' . $e->getMessage());
            $this->availableGroups = [];
        }
    }

    // ============================================
    // INITIALIZE COLLECTIONS
    // ============================================
    private function initializeCollections()
    {
        $this->addEmail();
        $this->addPhone();
        $this->addLandline();
        $this->addAddress();
        $this->addReference();

        $this->addBank();
        $this->addDocument();

    }





    // ============================================
    // EMAIL METHODS
    // ============================================
    public function addEmail()
    {
        if (count($this->emails) < self::MAX_EMAILS) {
            $this->emails[] = [
                'Emai_Addr' => '',
                'Emai_Type' => 'Self Generated',
                'Is_Prmy' => empty($this->emails),
            ];
        }
    }

    public function removeEmail($index)
    {
        $this->removeItem('emails', $index);
    }

    public function setPrimaryEmail($index)
    {
        $this->setPrimaryItem('emails', $index);
    }

    // ============================================
    // PHONE METHODS
    // ============================================
    public function addPhone()
    {
        if (count($this->phones) < self::MAX_PHONES) {
            $this->phones[] = [
                'Phon_Numb' => '',
                'Phon_Type' => 'self',
                'Cutr_Code' => self::PHONE_CODE_INDIA,
                'Has_WtAp' => false,
                'Has_Telg' => false,
                'Is_Prmy' => empty($this->phones),
            ];
        }
    }

    public function removePhone($index)
    {
        $this->removeItem('phones', $index);
    }

    public function setPrimaryPhone($index)
    {
        $this->setPrimaryItem('phones', $index);
    }

    // ============================================
    // LANDLINE METHODS
    // ============================================
    public function addLandline()
    {
        if (count($this->landlines) < self::MAX_LANDLINES) {
            $this->landlines[] = [
                'Land_Numb' => '',
                'Land_Type' => 'home',
                'Cutr_Code' => self::PHONE_CODE_INDIA,
                'Is_Prmy' => empty($this->landlines),
            ];
        }
    }

    public function removeLandline($index)
    {
        $this->removeItem('landlines', $index);
    }

    public function setPrimaryLandline($index)
    {
        $this->setPrimaryItem('landlines', $index);
    }

    // ============================================
    // ADDRESS METHODS
    // ============================================
    public function addAddress()
    {
        if (count($this->addresses) >= self::MAX_ADDRESSES) {
            return;
        }

        $india = $this
            ->getAllCountriesCollectionProperty()
            ->firstWhere('Admn_Cutr_Mast_UIN', self::COUNTRY_INDIA_UIN);

        $defaultId = $india ? $india->Admn_Cutr_Mast_UIN : null;

        $this->addresses[] = $this->hydrateAddressFields([
            'Addr' => '',
            'Loca' => '',
            'Lndm' => '',
            'Admn_Addr_Type_Mast_UIN' => null,
            'Is_Prmy' => empty($this->addresses),
            'Admn_Cutr_Mast_UIN' => $defaultId,
            'Admn_Stat_Mast_UIN' => null,
            'Admn_Dist_Mast_UIN' => null,
            'Admn_PinCode_Mast_UIN' => null,
        ]);
    }

    public function removeAddress($index)
    {
        $this->resetErrorBag("addresses.$index");
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);

        if (collect($this->addresses)->where('Is_Prmy', true)->isEmpty() && !empty($this->addresses)) {
            $this->addresses[0]['Is_Prmy'] = true;
        }
    }

    public function setPrimaryAddress($index)
    {
        $this->setPrimaryItem('addresses', $index);
        $this->syncPhoneCodeWithPrimaryAddress();
    }

    private function syncPhoneCodeWithPrimaryAddress()
    {
        try {
            $primaryAddress = collect($this->addresses)->firstWhere('Is_Prmy', true);

            if ($primaryAddress && !empty($primaryAddress['Admn_Cutr_Mast_UIN'])) {
                $country = Admn_Cutr_Mast::find($primaryAddress['Admn_Cutr_Mast_UIN'])->where('Stau_UIN', self::STATUS_ACTIVE)->first();

                if ($country && $country->Phon_Code) {
                    foreach ($this->phones as &$phone) {
                        $phone['Cutr_Code'] = $country->Phon_Code;
                    }

                    $this->dispatch('primary-country-changed', newPhoneCode: $country->Phon_Code);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing phone code: ' . $e->getMessage());
        }
    }

    // ============================================
    // BANK ACCOUNT METHODS
    // ============================================
    public function addBank()
    {
        if (count($this->bankAccounts) < self::MAX_BANKS) {
            $this->bankAccounts[] = [
                'Bank_Name_UIN' => '',
                'Bank_Brnc_Name' => '',
                'Acnt_Type' => '',
                'Acnt_Numb' => '',
                'IFSC_Code' => '',
                'Swift_Code' => '',
                'Prmy' => false,
                'Atch_Type_1' => '',
                'Atch_Type_2' => '',
                'temp_upload_1' => null,
                'temp_upload_2' => null,
                'Atch_Path' => null,
                'Atch_Path_2' => null,
            ];
        }
    }

    public function removeBank($index)
    {
        $this->removeItem('bankAccounts', $index);
    }

    public function setPrimaryBank(int $index)
    {
        $this->setPrimaryItem('bankAccounts', $index, 'Prmy');
    }

    // ============================================
    // DOCUMENT METHODS
    // ============================================
    public function addDocument()
    {
        if (count($this->documents) < self::MAX_DOCUMENTS) {
            $this->documents[] = [
                'Docu_Name' => '',
                'selected_types' => [],
                'Regn_Numb' => '',
                'Admn_Cutr_Mast_UIN' => '',
                'Auth_Issd' => '',
                'Vald_From' => '',
                'Vald_Upto' => '',
                'Docu_Atch_Path' => null,
                'is_dropdown_open' => false,
                'Prmy' => count($this->documents) === 0,
            ];
        }
    }

    public function setPrimaryDocument($index)
    {
        foreach ($this->documents as $key => &$doc) {
            $doc['Prmy'] = ($key === $index);
        }
    }

    public function removeDocument($index)
    {
        if (isset($this->documents[$index]['Docu_Atch_Path'])) {
            $path = $this->documents[$index]['Docu_Atch_Path'];
            if ($path && !is_object($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
    }

    public function addReference()
    {
        if (count($this->references) < static::MAX_REFERENCES) {
            $this->references[] = ['Refa_Name' => '', 'Refa_Rsip' => '', 'Refa_Phon' => '', 'Refa_Emai' => ''];
        }
    }

    public function removeReference($index)
    {
        unset($this->references[$index]);
        $this->references = array_values($this->references);
    }

    public function setPrimaryReference($index)
    {
        $this->setPrimaryItem('references', $index);
    }

    public function clearDocumentFile($index)
    {
        if (isset($this->documents[$index])) {
            $this->documents[$index]['Docu_Atch_Path'] = null;
            $this->resetErrorBag("documents.$index.Docu_Atch_Path");
        }
    }

    public function selectDocumentType($docIndex, $typeId)
    {
        if (!in_array($typeId, $this->documents[$docIndex]['selected_types'] ?? [])) {
            $this->documents[$docIndex]['selected_types'][] = $typeId;
        }
        $this->documents[$docIndex]['is_dropdown_open'] = false;
    }

    public function removeDocumentType($docIndex, $typeId)
    {
        $this->documents[$docIndex]['selected_types'] = array_values(
            array_filter(
                $this->documents[$docIndex]['selected_types'] ?? [],
                fn($id) => $id != $typeId
            )
        );
    }

    public function removeDocumentAttachment($index)
    {
        if (!isset($this->documents[$index])) {
            return;
        }

        $path = $this->documents[$index]['Docu_Atch_Path'] ?? null;

        if ($path && !is_object($path)) {
            Storage::disk('public')->delete($path);
        }

        $this->documents[$index]['Docu_Atch_Path'] = null;
        $this->resetErrorBag("documents.$index.Docu_Atch_Path");
    }

    // ============================================
    // NOTES/SIDEBAR METHODS
    // ============================================

    public function openCreateNoteModal(): void
    {
        $this->newNoteContent = '';
        $this->activeCommentTab = 'PAN';
        $this->showCreateModal = true;
    }

    public function closeCreateNoteModal(): void
    {
        $this->showCreateModal = false;
        // $this->newNoteContent = '';
    }

    public function addCommentToNote(string $comment): void
    {
        $trimmedComment = trim($comment);
        if ($trimmedComment === '') {
            return;
        }

        $this->newNoteContent = trim($this->newNoteContent);
        $this->newNoteContent = $this->newNoteContent
            ? "{$this->newNoteContent}\n{$trimmedComment}"
            : $trimmedComment;
    }

    // ============================================
    // PROFILE PICTURE METHODS
    // ============================================
    public function removeProfilePicture()
    {
        $this->Prfl_Pict = null;
        $this->resetErrorBag('Prfl_Pict');
    }

    // ============================================
    // UTILITY METHODS
    // ============================================
    public function updated($propertyName)
    {
        /* -------------------------------------------------
         | 1. Selected Tags (normalize + validate as whole)
         |--------------------------------------------------*/
        if (Str::startsWith($propertyName, 'selectedTags')) {
            $this->normalizeSelectedTags();

            // ✅ Use trait rules only (NO manual rules/messages)
            $this->validateOnly('selectedTags');

            return;
        }

        /* -------------------------------------------------
         | 2. Skip validation for UI / reference-only props
         |--------------------------------------------------*/
        $skipValidation = [
            'Prty',
            'addressTypes',
            'allTags',
            'allCountries',
            'allPrefixes',
            'bankOptions',
            'allDocumentTypes',
            'contactId',
            'MoOn',
            'Empl_Type',
            'showNoteSidebar',
            'pincodeSearch',
        ];

        if (
            Str::contains($propertyName, $skipValidation) ||
            Str::endsWith($propertyName, 'pincodeSearch')
        ) {
            return;
        }

        /* -------------------------------------------------
         | 3. Name sanitization (non-business)
         |--------------------------------------------------*/
        if (in_array($propertyName, ['FaNm', 'MiNm', 'LaNm']) && $this->Prty !== 'B') {
            $this->$propertyName = preg_replace('/[^a-zA-Z ]/', '', $this->$propertyName);
        }

        /* -------------------------------------------------
         | 4. Bank Attachments (temp_upload_1/2)
         |--------------------------------------------------*/
        if (preg_match('/bankAccounts\.(\d+)\.temp_upload_(1|2)/', $propertyName, $matches)) {
            $index = $matches[1];
            $uploadNum = $matches[2];

            // Clear previous errors for this specific upload
            $this->resetErrorBag("bankAccounts.$index.temp_upload_$uploadNum");

            try {
                // Validate using TRAIT rules
                $this->validateOnly($propertyName);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Invalid: clear the field
                $this->bankAccounts[$index]["temp_upload_$uploadNum"] = null;
                throw $e;
            }

            return;
        }


        /* -------------------------------------------------
         | 5. Skip boolean flags (UX choice)
         |--------------------------------------------------*/
        if (Str::contains($propertyName, ['Has_WtAp', 'Has_Telg', 'Is_Prmy'])) {
            return;
        }

        /* -------------------------------------------------
         | 6. Cross-validation: Bank Name ↔ Account Type ↔ Account Number ↔ Attachments
         |--------------------------------------------------*/
        if (preg_match('/bankAccounts\.(\d+)\.(Bank_Name_UIN|Acnt_Type|Acnt_Numb|Atch_Type_1|Atch_Type_2)/', $propertyName, $matches)) {
            $index = $matches[1];

            // Validate the changed field
            $this->validateOnly($propertyName);

            // Always validate relevant combinations
            if (Str::contains($propertyName, ['Atch_Type_1', 'Atch_Type_2'])) {
                $this->validateOnly("bankAccounts.$index.Atch_Type_1");
                $this->validateOnly("bankAccounts.$index.Atch_Type_2");
            } else {
                $this->validateOnly("bankAccounts.$index.Bank_Name_UIN");
                $this->validateOnly("bankAccounts.$index.Acnt_Type");
                $this->validateOnly("bankAccounts.$index.Acnt_Numb");
            }

            return;
        }

        /* -------------------------------------------------
         | 7. Cross-validation: Document Dates (From ↔ To)
         |--------------------------------------------------*/
        if (
            str_starts_with($propertyName, 'documents.') &&
            str_ends_with($propertyName, '.Docu_Atch_Path')
        ) {
            $index = explode('.', $propertyName)[1];

            // Clear old error
            $this->resetErrorBag("documents.$index.Docu_Atch_Path");

            try {
                // Validate using trait rules
                $this->validateOnly($propertyName);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Invalid remove file reference
                $this->documents[$index]['Docu_Atch_Path'] = null;
                throw $e;
            }

            return;
        }


        /* -------------------------------------------------
         | 8. Default validation (trait-driven)
         |--------------------------------------------------*/
        $this->validateOnly($propertyName);
    }


    private function removeItem($arrayName, $index)
    {
        if (count($this->$arrayName) <= 1) {
            return;
        }

        $this->resetErrorBag("$arrayName.$index");
        unset($this->{$arrayName}[$index]);
        $this->{$arrayName} = array_values($this->{$arrayName});

        if (collect($this->$arrayName)->where('Is_Prmy', true)->isEmpty() && !empty($this->$arrayName)) {
            $this->{$arrayName}[0]['Is_Prmy'] = true;
        }
    }

    private function setPrimaryItem($arrayName, $index, $field = 'Is_Prmy')
    {
        foreach ($this->$arrayName as $idx => &$item) {
            $item[$field] = ($idx == $index);
        }
    }

    private function generateAttachmentFileName($extension = ''): string
    {
        $orgUIN = session('selected_Orga_UIN');
        $timestamp = now()->format('Ymd_His');
        $filename = "{$orgUIN}_{$timestamp}";

        if (!empty($extension)) {
            $filename .= ".{$extension}";
        }

        return $filename;
    }

    private function normalizeSelectedTags(): void
    {
        if (empty($this->selectedTags) || !is_array($this->selectedTags)) {
            $this->selectedTags = [];

            return;
        }

        $result = [];
        foreach ($this->selectedTags as $k => $v) {
            // Keep numeric values (from checkbox value)
            if (is_numeric($v)) {
                $result[] = (int) $v;

                continue;
            }

            // Some DOM/Livewire combinations may send keys like "0"=>true or "tag_0"=>true.
            // If key is numeric and truthy, treat key as the id.
            if (is_numeric($k) && ($v === true || $v === 'on' || $v === '1' || $v === 1)) {
                $result[] = (int) $k;

                continue;
            }
        }

        $this->selectedTags = array_values(array_unique($result));
    }

    // ============================================
    // SAVE METHOD
    // ============================================
    public function save()
    {
        try {
            // Normalize selectedTags before final validation/save
            $this->normalizeSelectedTags();

            $this->validate();
            $userUIN = session('authenticated_user_uin');
            $orgaUIN = session('selected_Orga_UIN');

            if (!$userUIN) {
                throw new \Exception('User not authenticated.');
            }

            if (!$orgaUIN) {
                throw new \Exception('Organization not selected. Please select an organization first.');
            }

            // Live check (bypasses the cached session quota snapshot) so a plan
            // that expired or was refunded mid-session is caught immediately,
            // not only after the next org switch/login refreshes the snapshot.
            if ($this->isPlanExpired()) {
                $this->addError('general', 'No active subscription plan found or plan expired. Please upgrade or renew your plan.');
                return;
            }

            if (!$this->canAddEntry()) {
                $this->addError('general', 'Subscription entry limit exceeded. Please upgrade your plan.');
                return;
            }

            DB::transaction(function () use ($userUIN, $orgaUIN) {
                $contactId = $this->insertMainContact($userUIN, $orgaUIN);
                $this->insertEmails($contactId, $userUIN);
                $this->insertPhones($contactId, $userUIN);
                $this->insertLandlines($contactId, $userUIN);
                $this->insertAddresses($contactId, $userUIN);
                $this->insertTags($contactId, $userUIN);
                $this->insertReferences($contactId, $userUIN);
                // Removed insertEducations and insertWorkExperiences calls
                $this->insertBankAccounts($contactId, $userUIN, $orgaUIN);
                $this->insertDocuments($contactId, $userUIN, $orgaUIN);
                $this->insertContactNotes($contactId, $userUIN);
                $this->attachPendingNoteIfExists($contactId, $userUIN, $orgaUIN);

                // Increment entry utilization
                $this->incrementUtilization('Ulzn_Ntry', 1);
            });

            // Log activity: check if any document files were actually uploaded
            $description = "Created contact: " . trim($this->FaNm . ' ' . $this->MiNm . ' ' . $this->LaNm);
            $hasUploadedFiles = collect($this->documents)->contains(function ($doc) {
                return !empty($doc['Docu_Atch_Path']) || !empty($doc['file']);
            }) || !empty($this->Prfl_Pict);
            $action = $hasUploadedFiles ? 'Add with Attachment' : 'New Entry';
            $this->logActivity($action, $description);

            session()->flash('success', 'Contact has been created successfully.');

            return redirect()->route('contacts.index');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            session()->flash('error', 'Validation failed. Please check the highlighted fields below.');
            $this->dispatch('scroll-to-errors');
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error saving contact: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            session()->flash('error', 'Critical Error: ' . $e->getMessage());
            $this->dispatch('scroll-to-errors');
        }
    }

    // ============================================
    // INSERT METHODS
    // ============================================
    private function insertMainContact($userUIN, $orgaUIN)
    {
        $contactId = $this->generateUniqueUIN('admn_user_mast', 'Admn_User_Mast_UIN');
        $avatarPath = null;

        if ($this->Prfl_Pict) {
            $fileSizeMB = $this->bytesToMB($this->Prfl_Pict->getSize());
            if (!$this->canAddStorage($fileSizeMB)) {
                throw new \Exception('Storage limit exceeded. Cannot upload profile picture.');
            }
            try {
                $extension = $this->Prfl_Pict->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $filePath = $this->Prfl_Pict->storeAs('Attachment/Profile', $filename, 'public');
                $fullPath = Storage::disk('public')->path($filePath);

                if (file_exists($fullPath) && class_exists('Intervention\Image\ImageManagerStatic')) {
                    Image::make($fullPath)->save();
                }

                $avatarPath = $filePath;
                $this->incrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Add with Attachment', 'Profile Picture uploaded', 1, 'MB', $fileSizeMB);
            } catch (\Exception $e) {
                Log::error('Error processing profile picture: ' . $e->getMessage());
                session()->flash('error', 'Error uploading profile picture: ' . $e->getMessage());
            }
        }

        $insertData = [
            'Admn_User_Mast_UIN' => $contactId,
            'Prfx_UIN' => $this->Prfx_UIN ?: null,
            'FaNm' => $this->Prty === 'B' ? trim($this->FaNm) : preg_replace('/[^a-zA-Z ]/', '', trim($this->FaNm)),
            'MiNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->MiNm)) ?: null,
            'LaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->LaNm)) ?: null,
            'Gend' => $this->Gend ?: null,
            'Blood_Grp' => $this->Blood_Grp ?: null,

            // ✅ UPDATED DATE LOGIC
            'Brth_Dt' => $this->formatDateForDatabase($this->Brth_Dt),
            'Anvy_Dt' => $this->formatDateForDatabase($this->Anvy_Dt),
            'Deth_Dt' => $this->formatDateForDatabase($this->Deth_Dt),

            'Prfl_Pict' => $avatarPath,
            'Web' => trim($this->Web) ?: null,
            'FcBk' => trim($this->FcBk) ?: null,
            'Twtr' => trim($this->Twtr) ?: null,
            'LnDn' => trim($this->LnDn) ?: null,
            'Intg' => trim($this->Intg) ?: null,
            'Yaho' => trim($this->Yaho) ?: null,
            'Redt' => trim($this->Redt) ?: null,
            'Ytb' => trim($this->Ytb) ?: null,

            'Empl_Type' => $this->Empl_Type ?: 'job',
            'Comp_Name' => trim($this->Comp_Name) ?: null,
            'Comp_Dsig' => trim($this->Comp_Dsig) ?: null,
            'Comp_LdLi' => trim($this->Comp_LdLi) ?: null,
            'Comp_Desp' => trim($this->Comp_Desp) ?: null,
            'Comp_Emai' => trim($this->Comp_Emai) ?: null,
            'Comp_Web' => trim($this->Comp_Web) ?: null,
            'Comp_Addr' => trim($this->Comp_Addr) ?: null,

            'Admn_Orga_Mast_UIN' => $orgaUIN,
            'Admn_Grup_Mast_UIN' => $this->assignedGroupId ?: null,
            'Is_Actv' => self::STATUS_ACTIVE,
            'Is_Vf' => self::STATUS_VERIFIED,
            'Prty' => trim($this->Prty) ?: null,
            'CrOn' => now(),
            'MoOn' => now(),
            'CrBy' => $userUIN,
        ];

        DB::table('admn_user_mast')->insert($insertData);

        return $contactId;
    }

    // Save a note temporarily

    public function saveNewNote(): void
    {
        $note = trim($this->newNoteContent ?? '');
        if ($note === '') {
            $this->dispatch('toast-error', message: 'Note cannot be empty!');

            return;
        }

        session()->put('pending_contact_note', $note);
        $this->dispatch('toast-success', message: 'Note saved. It will attach once the contact is created.');
        $this->newNoteContent = '';
        $this->closeCreateNoteModal();
    }

    private function insertEmails($contactId, $userUIN)
    {
        $validEmails = collect($this->emails)->filter(
            fn($email) => !empty(trim(strtolower($email['Emai_Addr']) ?? ''))
        );

        if ($validEmails->isEmpty()) {
            return;
        }

        $emailUINs = $this->generateBatchUINs('admn_cnta_emai_mast', 'Admn_Cnta_Emai_Mast_UIN', $validEmails->count());

        foreach ($validEmails as $index => $email) {
            DB::table('admn_cnta_emai_mast')->insert([
                'Admn_Cnta_Emai_Mast_UIN' => $emailUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Emai_Addr' => strtolower(trim($email['Emai_Addr'])),
                'Emai_Type' => $email['Emai_Type'] ?? 'Self Generated',
                'Is_Prmy' => $email['Is_Prmy'] ?? false,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertPhones($contactId, $userUIN)
    {
        $validPhones = collect($this->phones)->filter(
            fn($phone) => !empty(trim($phone['Phon_Numb'] ?? ''))
        );

        if ($validPhones->isEmpty()) {
            return;
        }

        $phoneUINs = $this->generateBatchUINs('admn_cnta_phon_mast', 'Admn_Cnta_Phon_Mast_UIN', $validPhones->count());

        foreach ($validPhones as $index => $phone) {
            DB::table('admn_cnta_phon_mast')->insert([
                'Admn_Cnta_Phon_Mast_UIN' => $phoneUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Phon_Numb' => trim($phone['Phon_Numb']),
                'Phon_Type' => $phone['Phon_Type'] ?? 'self',
                'Cutr_Code' => $phone['Cutr_Code'] ?? self::PHONE_CODE_INDIA,
                'Has_WtAp' => $phone['Has_WtAp'] ?? false,
                'Has_Telg' => $phone['Has_Telg'] ?? false,
                'Is_Prmy' => $phone['Is_Prmy'] ?? false,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertLandlines($contactId, $userUIN)
    {
        $validLandlines = collect($this->landlines)->filter(
            fn($landline) => !empty(trim($landline['Land_Numb'] ?? ''))
        );

        if ($validLandlines->isEmpty()) {
            return;
        }

        $landlineUINs = $this->generateBatchUINs('admn_cnta_land_mast', 'Admn_Cnta_Land_Mast_UIN', $validLandlines->count());

        foreach ($validLandlines as $index => $landline) {
            DB::table('admn_cnta_land_mast')->insert([
                'Admn_Cnta_Land_Mast_UIN' => $landlineUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Land_Numb' => trim($landline['Land_Numb']),
                'Land_Type' => $landline['Land_Type'] ?? 'home',
                'Cutr_Code' => $landline['Cutr_Code'] ?? self::PHONE_CODE_INDIA,
                'Is_Prmy' => $landline['Is_Prmy'] ?? false,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertAddresses($contactId, $userUIN)
    {
        $validAddresses = collect($this->addresses)->filter(
            fn($address) => !empty(trim($address['Addr'] ?? '')) || !empty($address['Admn_PinCode_Mast_UIN'])
        );

        if ($validAddresses->isEmpty()) {
            return;
        }

        $addressUINs = $this->generateBatchUINs('admn_cnta_addr_mast', 'Admn_Cnta_Addr_Mast_UIN', $validAddresses->count());

        foreach ($validAddresses->values() as $index => $address) {
            $cleanAddress = $this->cleanDataForDatabase($address);

            DB::table('admn_cnta_addr_mast')->insert([
                'Admn_Cnta_Addr_Mast_UIN' => $addressUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Addr' => trim($cleanAddress['Addr'] ?? '') ?: null,
                'Loca' => trim($cleanAddress['Loca'] ?? '') ?: null,
                'Lndm' => trim($cleanAddress['Lndm'] ?? '') ?: null,
                'Admn_Addr_Type_Mast_UIN' => $cleanAddress['Admn_Addr_Type_Mast_UIN'] ?? null,
                'Is_Prmy' => $cleanAddress['Is_Prmy'] ?? false,
                'Admn_Cutr_Mast_UIN' => $cleanAddress['Admn_Cutr_Mast_UIN'] ?? null,
                'Admn_Stat_Mast_UIN' => $cleanAddress['Admn_Stat_Mast_UIN'] ?? null,
                'Admn_Dist_Mast_UIN' => $cleanAddress['Admn_Dist_Mast_UIN'] ?? null,
                'Admn_PinCode_Mast_UIN' => $cleanAddress['Admn_PinCode_Mast_UIN'] ?? null,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertReferences($contactId, $userUIN)
    {
        $validReferences = collect($this->references)->filter(
            fn($reference) => !empty(trim($reference['Refa_Name'] ?? ''))
        );

        if ($validReferences->isEmpty()) {
            return;
        }

        $referenceUINs = $this->generateBatchUINs('admn_cnta_refa_mast', 'Admn_Cnta_Refa_Mast_UIN', $validReferences->count());

        foreach ($validReferences->values() as $index => $reference) {
            DB::table('admn_cnta_refa_mast')->insert([
                'Admn_Cnta_Refa_Mast_UIN' => $referenceUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Refa_Name' => trim($reference['Refa_Name']),
                'Refa_Rsip' => trim($reference['Refa_Rsip']) ?: null,
                'Refa_Phon' => trim($reference['Refa_Phon']) ?: null,
                'Refa_Emai' => trim($reference['Refa_Emai']) ?: null,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    // ============================================

    private function insertTags($contactId, $userUIN)
    {
        $validTags = collect($this->selectedTags)->filter(fn($tagId) => !empty($tagId));

        if ($validTags->isEmpty()) {
            return;
        }

        $tagUINs = $this->generateBatchUINs('admn_cnta_tag_mast', 'Admn_Cnta_Tag_Mast_UIN', $validTags->count());

        foreach ($validTags as $index => $tagId) {
            DB::table('admn_cnta_tag_mast')->insert([
                'Admn_Cnta_Tag_Mast_UIN' => $tagUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Admn_Tag_Mast_UIN' => $tagId,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertBankAccounts($contactId, $userUIN, $orgaUIN)
    {
        $validBanks = collect($this->bankAccounts)->filter(
            fn($bank) => !empty($bank['Bank_Name_UIN']) && !empty(trim($bank['Acnt_Numb'] ?? ''))
        );

        if ($validBanks->isEmpty()) {
            return;
        }

        $bankUINs = $this->generateBatchUINs('admn_user_bank_mast', 'Admn_User_Bank_Mast_UIN', $validBanks->count());

        foreach ($validBanks->values() as $index => $bank) {
            $currentBankUIN = $bankUINs[$index];

            // Process Attachments
            $atch1Path = null;
            $atch2Path = null;

            if (!empty($bank['temp_upload_1'])) {
                $fileSizeMB = $this->bytesToMB($bank['temp_upload_1']->getSize());
                if (!$this->canAddStorage($fileSizeMB)) {
                    throw new \Exception('Storage limit exceeded. Cannot upload Bank Document 1.');
                }
                $extension = $bank['temp_upload_1']->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $atch1Path = $bank['temp_upload_1']->storeAs('Attachment/Bank', $filename, 'public');
                $this->incrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Add with Attachment', 'Bank Document 1 uploaded', 1, 'MB', $fileSizeMB);
            }

            if (!empty($bank['temp_upload_2'])) {
                $fileSizeMB = $this->bytesToMB($bank['temp_upload_2']->getSize());
                if (!$this->canAddStorage($fileSizeMB)) {
                    throw new \Exception('Storage limit exceeded. Cannot upload Bank Document 2.');
                }
                $extension = $bank['temp_upload_2']->getClientOriginalExtension();
                $timestamp = now()->format('Ymd_His');
                $filename = session('selected_Orga_UIN') . "_{$timestamp}_2.{$extension}";
                $atch2Path = $bank['temp_upload_2']->storeAs('Attachment/Bank', $filename, 'public');
                $this->incrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Add with Attachment', 'Bank Document 2 uploaded', 1, 'MB', $fileSizeMB);
            }

            DB::table('admn_user_bank_mast')->insert([
                'Admn_User_Bank_Mast_UIN' => $currentBankUIN,
                'Admn_User_Mast_UIN' => $contactId,
                'Admn_Orga_Mast_UIN' => $orgaUIN,
                'Bank_Name_UIN' => $bank['Bank_Name_UIN'],
                'Bank_Brnc_Name' => trim($bank['Bank_Brnc_Name']) ?: null,
                'Acnt_Type' => $bank['Acnt_Type'] ?: null,
                'Acnt_Numb' => trim($bank['Acnt_Numb']),
                'IFSC_Code' => trim($bank['IFSC_Code']) ?: null,
                'Swift_Code' => trim($bank['Swift_Code']) ?: null,
                'Prmy' => $bank['Prmy'] ?? false,
                'Stau' => self::STATUS_ACTIVE,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            // Sync to admn_bank_attachments
            if ($atch1Path || $atch2Path) {
                $attachUIN = $this->generateUniqueUIN('admn_bank_attachments', 'Admn_Bank_Attachment_UIN');
                DB::table('admn_bank_attachments')->insert([
                    'Admn_Bank_Attachment_UIN' => $attachUIN,
                    'Admn_User_Bank_Mast_UIN' => $currentBankUIN,
                    'Atch_Path' => $atch1Path,
                    'Atch_Path_2' => $atch2Path,
                    'Atch_Type_1' => $bank['Atch_Type_1'] ?: null,
                    'Atch_Type_2' => $bank['Atch_Type_2'] ?: null,
                    'Orgn_Name' => !empty($bank['temp_upload_1']) ? $bank['temp_upload_1']->getClientOriginalName() : (!empty($bank['temp_upload_2']) ? $bank['temp_upload_2']->getClientOriginalName() : null),
                    'CrOn' => now(),
                    'MoOn' => now(),
                    'CrBy' => $userUIN,
                    'MoBy' => $userUIN,
                ]);
            }
        }
    }

    private function insertDocuments($contactId, $userUIN, $orgaUIN)
    {
        $validDocuments = collect($this->documents)->filter(
            fn($doc) => !empty($doc['selected_types']) && !empty(trim($doc['Regn_Numb'] ?? ''))
        );

        if ($validDocuments->isEmpty()) {
            return;
        }

        $totalDocRecords = $validDocuments->sum(
            fn($doc) => count(is_array($doc['selected_types'] ?? []) ? $doc['selected_types'] : [])
        );

        $docUINs = $this->generateBatchUINs('admn_docu_mast', 'Admn_Docu_Mast_UIN', $totalDocRecords);

        $uinIndex = 0;
        foreach ($validDocuments as $doc) {
            $docAttachmentPath = $this->storeDocumentFile($doc);
            $docTypes = is_array($doc['selected_types'] ?? []) ? $doc['selected_types'] : [];

            foreach ($docTypes as $typeId) {
                DB::table('admn_docu_mast')->insert([
                    'Admn_Docu_Mast_UIN' => $docUINs[$uinIndex++],
                    'Admn_User_Mast_UIN' => $contactId,
                    'Admn_Orga_Mast_UIN' => $orgaUIN,
                    'Admn_Cutr_Mast_UIN' => $doc['Admn_Cutr_Mast_UIN'] ?: null,
                    'Admn_Docu_Type_Mast_UIN' => $typeId,
                    'Docu_Name' => trim($doc['Docu_Name']) ?: null,
                    'Auth_Issd' => trim($doc['Auth_Issd']) ?: null,
                    'Regn_Numb' => trim($doc['Regn_Numb']),
                    'Prmy' => $doc['Prmy'] ?? false,

                    // ✅ UPDATED DATE LOGIC
                    'Vald_From' => $this->formatDateForDatabase($doc['Vald_From']),
                    'Vald_Upto' => $this->formatDateForDatabase($doc['Vald_Upto']),

                    'Docu_Atch_Path' => $docAttachmentPath,
                    'Stau' => self::STATUS_ACTIVE,
                    'CrOn' => now(),
                    'MoOn' => now(),
                    'CrBy' => $userUIN,
                ]);

                if ($uinIndex > 1) {
                    usleep(50000);
                }
            }
        }
    }

    private function storeDocumentFile($doc)
    {
        try {
            if (empty($doc['Docu_Atch_Path'])) {
                return null;
            }

            if (is_object($doc['Docu_Atch_Path'])) {
                $file = $doc['Docu_Atch_Path'];

                $fileSizeMB = $this->bytesToMB($file->getSize());
                if (!$this->canAddStorage($fileSizeMB)) {
                    throw new \Exception('Storage limit exceeded. Cannot upload document.');
                }

                // Validation already handled by trait rules
                $extension = $file->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $storagePath = $file->storeAs('Attachment/Document', $filename, 'public');

                Log::info("Document stored successfully: {$storagePath}");
                
                $this->incrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Add with Attachment', 'Document uploaded', 1, 'MB', $fileSizeMB);

                return $storagePath;
            }

            return $doc['Docu_Atch_Path'];
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            session()->flash('error', 'Error uploading document: ' . $e->getMessage());

            return null;
        }
    }



    private function attachPendingNoteIfExists(int $contactId, string $userUIN, int $orgaUIN): void
    {
        $pending = session('pending_contact_note');
        if (!$pending) {
            return;
        }

        $noteUIN = $this->generateUniqueUIN('admn_cnta_note_mast', 'Admn_Cnta_Note_Mast_UIN');
        DB::table('admn_cnta_note_mast')->insert([
            'Admn_Cnta_Note_Mast_UIN' => $noteUIN,
            'Admn_User_Mast_UIN' => $contactId,
            'Admn_Orga_Mast_UIN' => $orgaUIN,
            'Note_Detl' => trim($pending),
            'CrBy' => $userUIN,
            'CrOn' => now(),
        ]);

        session()->forget('pending_contact_note');
    }

    private function insertContactNotes($contactId, $userUIN)
    {
        if (!empty(trim($this->newNoteContent))) {
            $noteUIN = $this->generateUniqueUIN('admn_cnta_note_mast', 'Admn_Cnta_Note_Mast_UIN');
            DB::table('admn_cnta_note_mast')->insert([
                'Admn_Cnta_Note_Mast_UIN' => $noteUIN,
                'Admn_User_Mast_UIN' => $contactId,
                'Admn_Orga_Mast_UIN' => session('selected_Orga_UIN'),
                'Note_Detl' => trim($this->newNoteContent),
                'CrBy' => $userUIN,
                'CrOn' => now(),
                'MoBy' => null,
                'MoOn' => null,
            ]);
        }
    }

    // ============================================
    // CLEANUP & UTILITY METHODS
    // ============================================
    private function cleanDataForDatabase($data)
    {
        return collect($data)->except([
            'id',
            'statesForDropdown',
            'districtsForDropdown',
            'pincodeResults',
            'pincodeSearch',
            'pincodesForDropdown',
            'existing_docs',
            'is_dropdown_open',
            'temp_upload',
            'newAttachments',
            'existing_attachments',
            'Admn_User_Bank_Mast_UIN',
            'Admn_Cnta_Addr_Mast_UIN',
            'Admn_Cnta_Emai_Mast_UIN',
            'Admn_Cnta_Phon_Mast_UIN',
            'Admn_Cnta_Land_Mast_UIN',
            'Admn_Cnta_Refa_Mast_UIN',
        ])->toArray();
    }

    // ============================================
    // CANCEL & HELPERS
    // ============================================
    public function cancel()
    {
        return redirect()->route('contacts.index');
    }

    public function getDocumentNameOptions()
    {
        return $this->getDocumentNameOptions();
    }

    public function addNote()
    {
        if (count($this->contactNotes) < self::MAX_NOTES) {
            $this->contactNotes[] = [
                'Note_Detl' => '',
                'created_at' => now(),
                'temp_id' => uniqid(),
            ];
        }
    }



    public function deleteNote($index)
    {
        if (isset($this->contactNotes[$index])) {
            $this->contactNotes[$index]['pending_delete'] = true;
            $this->contactNotes[$index]['delete_time'] = time();
            $this->dispatch('note-marked-for-deletion', noteIndex: $index);
        }
    }

    public function undoDeleteNote($index)
    {
        if (isset($this->contactNotes[$index])) {
            unset($this->contactNotes[$index]['pending_delete']);
            unset($this->contactNotes[$index]['delete_time']);
        }
    }

    // ============================================
    // RENDER
    // ============================================



    public function updatedBankAccounts($value, $key)
    {
        if (Str::contains($key, ['temp_upload_1', 'temp_upload_2', 'Atch_Type_1', 'Atch_Type_2'])) {
            $index = Str::between($key, 'bankAccounts.', '.');
            
            // Validate the changed field
            $this->validateOnly('bankAccounts' . Str::after($key, 'bankAccounts'));

            // ALSO validate the core bank fields to enforce bank details for attachments
            $this->validateOnly("bankAccounts.$index.Bank_Name_UIN");
            $this->validateOnly("bankAccounts.$index.Acnt_Numb");

            // IF a file is uploaded, validate the document type
            if (Str::contains($key, 'temp_upload_1')) {
                $this->validateOnly("bankAccounts.$index.Atch_Type_1");
            } elseif (Str::contains($key, 'temp_upload_2')) {
                $this->validateOnly("bankAccounts.$index.Atch_Type_2");
            }
        }
    }

    private function initializeFeatureEnablement()
    {
        $this->attachmentEnabled = \App\Traits\ManagesSettings::hasPermission('Attachment');
    }

    public function render()
    {
        return view('livewire.contacts.create', [
            'availableGroups' => $this->availableGroups,
        ])->layout('components.layouts.app');
    }
}
