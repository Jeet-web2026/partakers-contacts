<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\FormatsDates;
use App\Livewire\Traits\GeneratesUINs;
use App\Livewire\Traits\HasAddressCascade;
use App\Livewire\Traits\HasMaxConstants;
use App\Livewire\Traits\WithDocumentNames;
use App\Livewire\Traits\LoadsReferenceData;
use App\Livewire\Traits\WithContactValidation;
use App\Models\Admn_Cnta_Link_Mast;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Tag_Mast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateByLink extends Component
{
    use FormatsDates;
    use GeneratesUINs;
    use HasAddressCascade;
    use HasMaxConstants;
    use WithDocumentNames;
    use LoadsReferenceData;
    use WithFileUploads;
    use WithContactValidation;

    public $organization;

    public $link;

    public $linkExpiresAt;

    public $hasError = false;

    public $errorMessage = '';

    public $errorType = '';

    public $isSuccess = false;

    // Personal Details

    public $Prty = 'I';

    public $Prfx_UIN;

    public $FaNm = '';

    public $MiNm = '';

    public $LaNm = '';

    public $Gend = '';

    public $Blood_Grp;

    public $Brth_Dt;

    public $Anvy_Dt; // Added

    public $Deth_Dt; // Added

    #[Locked]
    public ?int $applicationUIN = null;

    public $Note;

    public $Prfl_Pict;

    public $existing_avatar;

    // Web Presence
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

    // Collections
    public array $emails = [];

    public array $phones = [];

    public array $landlines = [];

    public array $addresses = [];
    public array $bankAccounts = [];
    public array $documents = [];
    public array $references = [];

    public $allTags;
    // Reference Data
    public $allPrefixes;

    public $allCountries;

    public $addressTypes;

    public $BaddressTypes; // Added missing property

    public $bankOptions = [];

    public $allDocumentTypes = [];
    public $workTypes = [];

    public function mount(string $token): void
    {
        try {
            $this->validateAndLoadLink($token);

            if ($this->hasError) {
                return;
            }

            $this->applicationUIN = filter_var(
                request()->query('uin'),
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            ) ?: null;

            $this->loadCommonReferenceData();
            $this->initializeCollections();
            $this->initializeWithDocumentNames();
        } catch (\Exception $e) {
            Log::error('Error loading CreateByLink component: ' . $e->getMessage(), [
                'token' => $token,
                'exception' => $e,
            ]);
            $this->setError('System Error', 'An unexpected error occurred while loading this page.');
        }
    }



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
            'addresses',
            'addressTypes',
            'allCountries',
            'allPrefixes',
            'organization',
            'link',
            'linkExpiresAt',
            'hasError',
            'errorMessage',
            'errorType',
            'isSuccess',
            'bankOptions',
            'allDocumentTypes',
            'existing_avatar',
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
         | 4. Bank Attachments (Validation & Partner sync)
         |--------------------------------------------------*/
        if (preg_match('/bankAccounts\.(\d+)\.(temp_upload_1|temp_upload_2|Atch_Type_1|Atch_Type_2)/', $propertyName, $matches)) {
            $index = $matches[1];
            $field = $matches[2];

            // Clear previous errors for file uploads if it is a file field
            if (Str::startsWith($field, 'temp_upload')) {
                $this->resetErrorBag($propertyName);
            }

            try {
                // Validate only the field itself
                $this->validateOnly($propertyName);

                // ALSO validate the core bank fields to enforce bank details for attachments
                $this->validateOnly("bankAccounts.$index.Bank_Name_UIN");
                $this->validateOnly("bankAccounts.$index.Acnt_Numb");

                // IF a file is uploaded, validate the document type
                if ($field === 'temp_upload_1') {
                    $this->validateOnly("bankAccounts.$index.Atch_Type_1");
                } elseif ($field === 'temp_upload_2') {
                    $this->validateOnly("bankAccounts.$index.Atch_Type_2");
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                // If it's a file upload and it failed, clear it
                if (Str::startsWith($field, 'temp_upload')) {
                    $this->bankAccounts[$index][$field] = null;
                }
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
         | 6. Cross-validation: Bank Name ↔ Account Type ↔ Account Number
         |--------------------------------------------------*/
        if (preg_match('/bankAccounts\.(\d+)\.(Bank_Name_UIN|Acnt_Type|Acnt_Numb)/', $propertyName, $matches)) {
            $index = $matches[1];

            // Validate the changed field
            $this->validateOnly($propertyName);

            // Always validate all three related fields together for consistency
            $this->validateOnly("bankAccounts.$index.Bank_Name_UIN");
            $this->validateOnly("bankAccounts.$index.Acnt_Type");
            $this->validateOnly("bankAccounts.$index.Acnt_Numb");

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
                // ❌ Invalid → remove file reference
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

    public function render()
    {
        return view('livewire.contacts.create-by-link')->layout('components.layouts.guest');
    }

    private function validateAndLoadLink(string $token): void
    {
        $link = Admn_Cnta_Link_Mast::where('Tokn', $token)->first();

        if (!$link) {
            $this->setError('Invalid Link', 'This invitation link is not valid or does not exist.', self::ERROR_TYPE_INVALID);

            return;
        }

        if ($link->Is_Used) {
            $this->setError('Link Already Used', 'This invitation link has already been used.', self::ERROR_TYPE_USED);

            return;
        }

        if ($link->Expy_Dt && $link->Expy_Dt->isPast()) {
            $expiredDate = $link->Expy_Dt->format('M j, Y \a\t g:i A');
            $this->setError('Link Expired', "This invitation link expired on {$expiredDate}.", self::ERROR_TYPE_EXPIRED);

            return;
        }

        if (!$link->Is_Actv) {
            $this->setError('Link Inactive', 'This invitation link has been deactivated.', self::ERROR_TYPE_INACTIVE);

            return;
        }

        $this->link = $link;

        if ($link->Expy_Dt) {
            $this->linkExpiresAt = $link->Expy_Dt->toISOString();
        }

        $this->organization = DB::table('admn_orga_mast')
            ->where('Orga_UIN', $this->link->Admn_Orga_Mast_UIN)
            ->value('Orga_Name');
    }

    private function setError(string $title, string $message, string $type = self::ERROR_TYPE_INVALID): void
    {
        $this->hasError = true;
        $this->errorMessage = $message;
        $this->errorType = $type;

        Log::warning("Link access error: {$title}", [
            'message' => $message,
            'type' => $type,
        ]);
    }

    private function loadPrefixes()
    {
        try {
            return Admn_Prfx_Name_Mast::where('Stau_UIN', self::STATUS_ACTIVE)->orderBy('Prfx_Name')->get();
        } catch (\Exception $e) {
            return Admn_Prfx_Name_Mast::orderBy('Prfx_Name')->get();
        }
    }

    private function initializeCollections(): void
    {
        $this->addEmail();
        $this->addPhone();
        $this->addLandline();
        $this->addAddress();
        $this->addBank();
        $this->addReference();
        $this->addDocument();
    }

    // Collection Methods (Email, Phone, Landline, etc.)
    public function addEmail(): void
    {
        if (count($this->emails) < self::MAX_EMAILS) {
            $this->emails[] = [
                'Emai_Addr' => '',
                'Emai_Type' => 'self generated',
                'Is_Prmy' => empty($this->emails),
            ];
        }
    }

    public function removeEmail($index): void
    {
        $this->removeItem('emails', $index);
    }

    public function setPrimaryEmail($index): void
    {
        $this->setPrimaryItem('emails', $index);
    }

    public function addPhone(): void
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

    public function removePhone($index): void
    {
        $this->removeItem('phones', $index);
    }

    public function setPrimaryPhone($index): void
    {
        $this->setPrimaryItem('phones', $index);
    }

    public function addLandline(): void
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

    public function removeLandline($index): void
    {
        $this->removeItem('landlines', $index);
    }

    public function setPrimaryLandline($index): void
    {
        $this->setPrimaryItem('landlines', $index);
    }

    public function addAddress(): void
    {
        if (count($this->addresses) >= self::MAX_ADDRESSES) {
            return;
        }

        $india = collect($this->allCountries)->firstWhere('Admn_Cutr_Mast_UIN', self::COUNTRY_INDIA_UIN);
        $defaultCountryUIN = $india ? $india->Admn_Cutr_Mast_UIN : null;

        $this->addresses[] = $this->hydrateAddressFields([
            'Addr' => '',
            'Loca' => '',
            'Lndm' => '',
            'Admn_Addr_Type_Mast_UIN' => null,
            'Is_Prmy' => empty($this->addresses),
            'Admn_Cutr_Mast_UIN' => $defaultCountryUIN,
            'Admn_Stat_Mast_UIN' => null,
            'Admn_Dist_Mast_UIN' => null,
            'Admn_PinCode_Mast_UIN' => null,
        ]);
    }

    public function removeAddress(int $index): void
    {
        $this->resetErrorBag("addresses.$index");
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);

        if (collect($this->addresses)->where('Is_Prmy', true)->isEmpty() && !empty($this->addresses)) {
            $this->addresses[0]['Is_Prmy'] = true;
        }
    }

    public function addReference(): void
    {
        if (count($this->references) < self::MAX_REFERENCES) {
            $this->references[] = [
                'Refa_Name' => '',
                'Refa_Rsip' => '',
                'Refa_Phon' => '',
                'Refa_Emai' => '',
            ];
        }
    }

    public function removeReference(int $index): void
    {
        $this->removeItem('references', $index);
    }

    public function setPrimaryReference(int $index): void
    {
        $this->setPrimaryItem('references', $index);
    }

    public function setPrimaryAddress($index): void
    {
        $this->setPrimaryItem('addresses', $index);
        $this->syncPhoneCodeWithPrimaryAddress();
    }

    private function syncPhoneCodeWithPrimaryAddress(): void
    {
        try {
            $primaryAddress = collect($this->addresses)->firstWhere('Is_Prmy', true);

            if ($primaryAddress && !empty($primaryAddress['Admn_Cutr_Mast_UIN'])) {
                $country = Admn_Cutr_Mast::find($primaryAddress['Admn_Cutr_Mast_UIN']);

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

    public function addBank(): void
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
                'temp_upload_1' => null,
                'temp_upload_2' => null,
                'Atch_Path' => null,
                'Atch_Path_2' => null,
                'Atch_Type_1' => '',
                'Atch_Type_2' => '',
            ];
        }
    }

    public function removeBank(int $index): void
    {
        $this->removeItem('bankAccounts', $index);
    }

    public function setPrimaryBank(int $index): void
    {
        $this->setPrimaryItem('bankAccounts', $index, 'Prmy');
    }

    // Removed handleBankUpload and removeNewAttachment as they are replaced by Create-style uploads

    public function addDocument(): void
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

    public function removeDocument(int $index): void
    {
        if (isset($this->documents[$index]['Docu_Atch_Path'])) {
            $path = $this->documents[$index]['Docu_Atch_Path'];
            if ($path && !is_object($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        $this->resetErrorBag("documents.$index");
        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
    }

    public function setPrimaryDocument($index)
    {
        foreach ($this->documents as $key => &$doc) {
            $doc['Prmy'] = ($key === $index);
        }
    }



    public function toggleDocumentDropdown($index): void
    {
        $currentState = $this->documents[$index]['is_dropdown_open'] ?? false;

        foreach ($this->documents as &$doc) {
            $doc['is_dropdown_open'] = false;
        }

        $this->documents[$index]['is_dropdown_open'] = !$currentState;
    }

    public function selectDocumentType($docIndex, $typeId): void
    {
        if (!in_array($typeId, $this->documents[$docIndex]['selected_types'] ?? [])) {
            $this->documents[$docIndex]['selected_types'][] = $typeId;
        }
        $this->documents[$docIndex]['is_dropdown_open'] = false;
    }

    public function removeDocumentType($docIndex, $typeId): void
    {
        $this->documents[$docIndex]['selected_types'] = array_values(
            array_filter(
                $this->documents[$docIndex]['selected_types'] ?? [],
                fn($id) => $id != $typeId
            )
        );
    }

    public function removeDocumentAttachment($index): void
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

    public function removeProfilePicture(): void
    {
        $this->Prfl_Pict = null;
        $this->resetErrorBag('Prfl_Pict');
    }

    private function generateAttachmentFileName($extension = ''): string
    {
        $orgUIN = $this->link->Admn_Orga_Mast_UIN;
        $timestamp = now()->format('Ymd_His');
        $filename = "{$orgUIN}_{$timestamp}";

        if (!empty($extension)) {
            $filename .= ".{$extension}";
        }

        return $filename;
    }

    private function removeItem($arrayName, $index): void
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

    private function setPrimaryItem($arrayName, $index, $field = 'Is_Prmy'): void
    {
        foreach ($this->$arrayName as $idx => &$item) {
            $item[$field] = ($idx == $index);
        }
    }

    public function getAllCountriesCollectionProperty(): Collection
    {
        return collect($this->allCountries);
    }



    public function save(): void
    {
        try {
            if (!$this->link) {
                throw new \Exception('Invalid link session.');
            }

            $this->link->refresh();

            if (!$this->isLinkValid()) {
                $this->setError('Link Invalid', 'This link is no longer valid.');

                return;
            }

            $this->validate($this->rules(), $this->messages());

            if ($this->getErrorBag()->any()) {
                $this->dispatch('scroll-to-errors');

                return;
            }

            DB::transaction(function () {
                $userUIN = $this->link->CrBy ?? self::DEFAULT_CREATOR;
                $orgaUIN = $this->link->Admn_Orga_Mast_UIN;

                $contactId = $this->insertMainContact($userUIN, $orgaUIN);
                $this->insertEmails($contactId, $userUIN);
                $this->insertPhones($contactId, $userUIN);
                $this->insertLandlines($contactId, $userUIN);
                $this->insertAddresses($contactId, $userUIN);
                $this->insertBankAccounts($contactId, $userUIN, $orgaUIN);
                $this->insertReferences($contactId, $userUIN);
                // Removed insertEducations and insertWorkExperiences calls
                $this->insertDocuments($contactId, $userUIN, $orgaUIN);

                $this->insertByLinkTag($contactId, $userUIN);

                $this->link->markAsUsed();
            });

            $this->isSuccess = true;
            $this->dispatch('save-success', ['message' => 'Your information has been submitted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for link token: ' . ($this->link->Tokn ?? 'unknown'), [
                'errors' => $e->errors(),
            ]);
            $this->dispatch('scroll-to-errors');
            throw $e;
        } catch (\Exception $e) {

            Log::error('Error saving contact via link: ' . $e->getMessage(), [
                'exception' => $e,
                'token' => $this->link->Tokn ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Critical Error: ' . $e->getMessage());
            $this->dispatch('scroll-to-errors');
        }
    }



    private function isLinkValid(): bool
    {
        if ($this->link->Is_Used) {
            $this->setError('Link Already Used', 'This invitation link has already been used.', self::ERROR_TYPE_USED);

            return false;
        }

        if ($this->link->Expy_Dt && $this->link->Expy_Dt->isPast()) {
            $expiredDate = $this->link->Expy_Dt->format('M j, Y \a\t g:i A');
            $this->setError('Link Expired', "This invitation link expired on {$expiredDate}.", self::ERROR_TYPE_EXPIRED);

            return false;
        }

        if (!$this->link->Is_Actv) {
            $this->setError('Link Inactive', 'This invitation link has been deactivated.', self::ERROR_TYPE_INACTIVE);

            return false;
        }

        return true;
    }

    // ========================================================================
    // MAIN RULES
    // ========================================================================
    // Rules and messages are now handled by WithContactValidation trait.

    // ========================================================================
    // HELPER FUNCTIONS
    // ========================================================================

    public function validateUniqueUrl($attribute, $value, $fail)
    {
        if (empty($value)) {
            return;
        }
        $valNorm = rtrim(strtolower($value), '/');
        $fields = ['Web', 'LnDn', 'Twtr', 'FcBk', 'Intg', 'Yaho', 'Redt', 'Ytb'];

        foreach ($fields as $field) {
            $otherVal = $this->{$field} ?? null;
            if ($otherVal && rtrim(strtolower($otherVal), '/') === $valNorm) {
                // Ensure we aren't comparing the field to itself (if called from updated hook context)
                if ($attribute && Str::contains($attribute, $field)) {
                    continue;
                }

                if ($value !== $this->$field) {
                    $fail('This URL is duplicated in ' . ucfirst($field));

                    return;
                }
            }
        }
    }

    private function insertMainContact($userUIN, $orgaUIN): int
    {
        $contactId = $this->generateUniqueUIN('admn_user_mast', 'Admn_User_Mast_UIN');

        $avatarPath = $this->processProfilePicture();

        $insertData = [
            'Admn_User_Mast_UIN' => $contactId,
            'Hr_application_UIN' => $this->applicationUIN,
            'Prfx_UIN' => $this->Prfx_UIN ?: null,
            'FaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->FaNm)),
            'MiNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->MiNm)) ?: null,
            'LaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->LaNm)) ?: null,
            'Gend' => $this->Gend ?: null,
            'Blood_Grp' => $this->Blood_Grp ?: null,

            // Updated Date Logic using formatDateForDatabase
            'Brth_Dt' => $this->formatDateForDatabase($this->Brth_Dt),
            'Anvy_Dt' => $this->formatDateForDatabase($this->Anvy_Dt),
            'Deth_Dt' => $this->formatDateForDatabase($this->Deth_Dt),

            'Prfl_Pict' => $avatarPath,
            'Note' => trim($this->Note) ?: null, // Added Note
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
            'Admn_Grup_Mast_UIN' => null,
            'Is_Actv' => self::STATUS_ACTIVE,
            'Is_Vf' => self::STATUS_UNVERIFIED,
            'Prty' => 'I', // Assuming by link usually means Individual for now, adjust if needed
            'CrOn' => now(),
            'MoOn' => now(),
            'CrBy' => $userUIN,
        ];

        DB::table('admn_user_mast')->insert($insertData);

        Log::info("Contact created via link with UIN: {$contactId}");

        return $contactId;
    }

    private function processProfilePicture(): ?string
    {
        if (!$this->Prfl_Pict) {
            return null;
        }

        try {
            Log::info('Processing profile picture upload...');

            $extension = $this->Prfl_Pict->getClientOriginalExtension();
            $filename = $this->generateAttachmentFileName($extension);
            $filePath = $this->Prfl_Pict->storeAs('Attachment/Profile', $filename, 'public');

            if (!$filePath) {
                throw new \Exception('File storage returned false/null');
            }

            Log::info('File stored as: ' . $filePath);

            try {
                $fullPath = Storage::disk('public')->path($filePath);

                if (file_exists($fullPath) && class_exists('Intervention\Image\Facades\Image')) {
                    Image::make($fullPath)->save();
                }
            } catch (\Exception $e) {
                Log::warning('Image processing failed (but file upload succeeded): ' . $e->getMessage());
            }

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error storing profile picture: ' . $e->getMessage());

            return null;
        }
    }

    private function insertEmails(int $contactId, int $userUIN): void
    {
        $validEmails = collect($this->emails)->filter(
            fn($email) => !empty(trim($email['Emai_Addr'] ?? ''))
        );

        if ($validEmails->isEmpty()) {
            return;
        }

        $emailUINs = $this->generateBatchUINs('admn_cnta_emai_mast', 'Admn_Cnta_Emai_Mast_UIN', $validEmails->count());

        foreach ($validEmails as $index => $email) {
            DB::table('admn_cnta_emai_mast')->insert([
                'Admn_Cnta_Emai_Mast_UIN' => $emailUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Emai_Addr' => trim(strtolower($email['Emai_Addr'])),
                'Emai_Type' => $email['Emai_Type'] ?? 'self generated',
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

    private function insertPhones(int $contactId, int $userUIN): void
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

    private function insertLandlines(int $contactId, int $userUIN): void
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

    private function insertAddresses(int $contactId, int $userUIN): void
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

    private function insertReferences(int $contactId, int $userUIN): void
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
                'Refa_Rsip' => trim($reference['Refa_Rsip'] ?? '') ?: null,
                'Refa_Phon' => trim($reference['Refa_Phon'] ?? '') ?: null,
                'Refa_Emai' => trim(strtolower($reference['Refa_Emai'] ?? '')) ?: null,
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
                $extension = $bank['temp_upload_1']->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $atch1Path = $bank['temp_upload_1']->storeAs('Attachment/Bank', $filename, 'public');
            }

            if (!empty($bank['temp_upload_2'])) {
                $extension = $bank['temp_upload_2']->getClientOriginalExtension();
                $timestamp = now()->format('Ymd_His');
                $filename = $orgaUIN . "_{$timestamp}_2.{$extension}";
                $atch2Path = $bank['temp_upload_2']->storeAs('Attachment/Bank', $filename, 'public');
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

            if ($index > 0) {
                usleep(50000);
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

                // Validation already handled by trait rules
                $extension = $file->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $storagePath = $file->storeAs('Attachment/Document', $filename, 'public');

                Log::info("Document stored successfully: {$storagePath}");

                return $storagePath;
            }

            return $doc['Docu_Atch_Path'];
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            session()->flash('error', 'Error uploading document: ' . $e->getMessage());

            return null;
        }
    }

    public function clearDocumentFile($index)
    {
        if (isset($this->documents[$index])) {
            $this->documents[$index]['Docu_Atch_Path'] = null;
            $this->resetErrorBag("documents.$index.Docu_Atch_Path");
        }
    }

    private function insertByLinkTag(int $contactId, int $userUIN): void
    {
        $byLinkTag = Admn_Tag_Mast::where('Admn_Tag_Mast_UIN', self::BY_LINK_TAG_ID)->first();

        if (!$byLinkTag) {
            return;
        }

        $tagUIN = $this->generateUniqueUIN('admn_cnta_tag_mast', 'Admn_Cnta_Tag_Mast_UIN');

        DB::table('admn_cnta_tag_mast')->insert([
            'Admn_Cnta_Tag_Mast_UIN' => $tagUIN,
            'Admn_User_Mast_UIN' => $contactId,
            'Admn_Tag_Mast_UIN' => $byLinkTag->Admn_Tag_Mast_UIN,
            'CrOn' => now(),
            'MoOn' => now(),
            'CrBy' => $userUIN,
        ]);
    }

    private function cleanDataForDatabase($data): array
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
        ])->toArray();
    }

    public function cancel(): void
    {
        redirect()->route('contacts.index');
    }
}
