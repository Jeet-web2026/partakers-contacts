<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\FormatsDates;
use App\Livewire\Traits\GeneratesUINs;
use App\Livewire\Traits\HasAddressCascade;
use App\Livewire\Traits\HasMaxConstants;

use App\Livewire\Traits\LoadsReferenceData;
use App\Livewire\Traits\WithComments;
use App\Livewire\Traits\WithContactValidation;
use App\Livewire\Traits\WithDocumentNames;
use App\Traits\LogsActivity;
use App\Traits\ManagesSettings;
use App\Traits\ValidatesSubscriptionLimits;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Docu_Mast;
use App\Models\Admn_Grup_Mast;
use App\Models\Admn_User_Bank_Mast;
use App\Models\AdmnBankAttachment;
use App\Models\AdmnCntaNoteMast;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use FormatsDates;
    use GeneratesUINs;
    use HasMaxConstants, WithComments, WithContactValidation, WithDocumentNames, WithFileUploads, LoadsReferenceData, HasAddressCascade, LogsActivity, ManagesSettings, ValidatesSubscriptionLimits;

    public $contactId;

    public $contact;

    public $MoOn;

    public $Prty = 'I';

    public array $partyOptions = [
        'I' => 'Individual/Personal',
        'B' => 'Business/Organization',
    ];

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

    public $Note = '';

    public ?int $assignedGroupId = null;

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

    public bool $showNoteSidebar = false;

    public array $existingNotes = [];

    public ?string $newNoteContent = null;

    public string $activeCommentTab = 'PAN';

    public ?int $currentUserId = null;

    public ?int $organizationUIN = null;

    public int $verticalId = 5;

    public $isVerified = false;

    public $allTags = [];

    public $allCountries = [];

    public $allPrefixes = [];

    public $addressTypes = [];

    public $BaddressTypes = [];

    public $bankOptions = [];

    public $allDocumentTypes = [];
    public $workTypes = [];

    public array $emails = [];

    public array $phones = [];

    public array $landlines = [];

    public array $addresses = [];

    public array $bankAccounts = [];

    public array $documents = [];
    public array $references = [];


    public array $selectedTags = [];
    protected function setDefaultQuickCommentTab(): void
    {
        $comments = $this->getQuickComments();

        if ($comments->isNotEmpty()) {
            $this->activeCommentTab = trim($comments->keys()->first());
        }
    }
    public function mount($contact)
    {
        if (!\App\Traits\ManagesSettings::hasPermission('Edit')) {
            abort(403, 'Unauthorized access: You do not have permission to edit contacts.');
        }

        $this->setDefaultQuickCommentTab();
        $this->initializeContactId($contact);
        try {
            $this->loadCommonReferenceData();
            // Load tags with contactId to allow tag 12 if present
            $this->allTags = $this->loadTags($this->contactId);
            $this->initializeWithDocumentNames();
            $this->currentUserId = session('authenticated_user_uin');
            $this->organizationUIN = session('selected_Orga_UIN');
            $this->verticalId = 5;
            $this->loadContactDetails();
            $this->loadContactNotesHistory();
            $this->initializeFeatureEnablement();
        } catch (\Exception $e) {
            Log::error('Mount Error: ' . $e->getMessage());
            session()->flash('error', 'Error loading data: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Reload tags fresh on every render so new tags created in Settings appear immediately
        $this->allTags = $this->loadTags($this->contactId);

        return view('livewire.contacts.edit', [
            'availableGroups' => $this->getAvailableGroups(),
            'currentAssignedGroup' => $this->assignedGroupId,
        ])->layout('components.layouts.app');
    }

    public function loadContactNotesHistory(): void
    {
        if (!isset($this->contactId) || !isset($this->currentUserId)) {
            return;
        }
        try {
            $this->existingNotes = AdmnCntaNoteMast::forContact($this->contactId)
                ->whereNull('DelOn')
                ->orderByRaw('IsPinned DESC, CrOn DESC')
                ->get()
                ->map(function ($note) {
                    return [
                        'id' => $note->Admn_Cnta_Note_Mast_UIN,
                        'content' => $this->formatNoteContent($note->Note_Detl),
                        'createdBy' => $note->getCreatorName(),
                        'createdByUIN' => $note->CrBy,
                        'createdOn' => $note->CrOn,
                        'isPinned' => (bool) $note->IsPinned,
                        'canManage' => $note->canBeManagedBy($this->currentUserId),
                    ];
                })
                ->toArray();
        } catch (\Throwable $e) {
            Log::error('Load Notes Error: ' . $e->getMessage());
            $this->existingNotes = [];
        }
    }

    public function openNoteSidebar(): void
    {
        $this->showNoteSidebar = true;
        $this->activeCommentTab = 'PAN';
        $this->newNoteContent = '';
        $this->loadContactNotesHistory();
    }

    public function closeNoteSidebar(): void
    {
        $this->showNoteSidebar = false;
        $this->newNoteContent = '';
    }

    public function toggleNoteSidebar(): void
    {
        $this->showNoteSidebar ? $this->closeNoteSidebar() : $this->openNoteSidebar();
    }

    private function formatNoteContent($content): string
    {
        if (empty($content)) {
            return '';
        }

        // Clean up excessive whitespace: tabs, multiple spaces, multiple newlines
        $formatted = preg_replace('/\t+/', ' ', $content); // Replace tabs with single space
        $formatted = preg_replace('/ +/', ' ', $formatted); // Replace multiple spaces with single space
        $formatted = preg_replace('/\n\s*\n/', "\n", $formatted); // Replace multiple newlines with single newline
        $formatted = trim($formatted); // Final trim

        return $formatted;
    }

    public function saveNote(): void
    {
        $noteContent = $this->formatNoteContent($this->newNoteContent ?? '');

        if ($noteContent === '') {
            $this->dispatch('toast-error', message: 'Note cannot be empty!');

            return;
        }
        try {
            $userUIN = session('authenticated_user_uin');
            // ✅ REPLACED: $maxUIN + 1 → generateUniqueUIN()
            $noteUIN = $this->generateUniqueUIN('admn_cnta_note_mast', 'Admn_Cnta_Note_Mast_UIN');

            AdmnCntaNoteMast::create([
                'Admn_Cnta_Note_Mast_UIN' => $noteUIN,
                'Admn_User_Mast_UIN' => $this->contactId,
                'Admn_Orga_Mast_UIN' => $this->organizationUIN,
                'Vertical_ID' => $this->verticalId ?? 5,
                'Note_Detl' => $noteContent,
                'CrOn' => now(),
                'CrBy' => $userUIN,
            ]);
            $this->logActivity('Edit Entry', "Added Note to Contact [ID: {$this->contactId}]", 1);
            $this->dispatch('toast-success', message: 'Note saved successfully!');
            $this->newNoteContent = '';
            $this->loadContactNotesHistory();
        } catch (\Throwable $e) {
            Log::error('Note Save Error: ' . $e->getMessage());
            $this->dispatch('toast-error', message: 'Failed to save note.');
        }
    }

    public function addCommentToNote($comment): void
    {
        $trimmedComment = trim($comment);
        if ($trimmedComment === '') {
            return;
        }
        $this->newNoteContent = trim($this->newNoteContent ?? '');
        $this->newNoteContent = $this->newNoteContent ? "{$this->newNoteContent}\n{$trimmedComment}" : $trimmedComment;
    }

    public function pinNote($noteId): void
    {
        try {
            $note = AdmnCntaNoteMast::findOrFail($noteId);
            $userUIN = session('authenticated_user_uin');
            $note->update([
                'IsPinned' => true,
                'PinnedOn' => now(),
                'PinnedBy' => $userUIN,
            ]);
            $this->dispatch('toast-success', message: 'Note pinned!');
            $this->loadContactNotesHistory();
        } catch (\Throwable $e) {
            Log::error('Pin Note Error: ' . $e->getMessage());
            $this->dispatch('toast-error', message: 'Failed to pin note!');
        }
    }

    public function unpinNote($noteId): void
    {
        try {
            $note = AdmnCntaNoteMast::findOrFail($noteId);
            $note->update([
                'IsPinned' => false,
                'PinnedOn' => null,
                'PinnedBy' => null,
            ]);
            $this->dispatch('toast-success', message: 'Note unpinned!');
            $this->loadContactNotesHistory();
        } catch (\Throwable $e) {
            Log::error('Unpin Note Error: ' . $e->getMessage());
            $this->dispatch('toast-error', message: 'Failed to unpin note!');
        }
    }

    public function deleteNote($noteId): void
    {
        try {
            $userUIN = session('authenticated_user_uin');
            $note = AdmnCntaNoteMast::findOrFail($noteId);
            $createdAt = Carbon::parse($note->CrOn);
            if ($note->CrBy !== $userUIN || $createdAt->diffInHours(now()) > 48) {
                $this->dispatch('toast-error', message: 'You cannot delete this note now.');

                return;
            }
            $note->update([
                'DelOn' => now(),
                'DelBy' => $userUIN,
            ]);
            $this->logActivity('Delete Entry', "Deleted Note from Contact [ID: {$this->contactId}]", 1);
            $this->dispatch('toast-success', message: 'Note deleted!');
            $this->loadContactNotesHistory();
        } catch (\Throwable $e) {
            Log::error('Delete Note Error: ' . $e->getMessage());
            $this->dispatch('toast-error', message: 'Failed to delete note!');
        }
    }

    public function canDeleteNote(array $note): bool
    {
        if (!isset($note['createdByUIN'], $note['createdOn'])) {
            return false;
        }
        $currentUser = session('authenticated_user_uin');
        if ($note['createdByUIN'] !== $currentUser) {
            return false;
        }
        $createdAt = Carbon::parse($note['createdOn']);

        return $createdAt->diffInHours(now()) <= 48;
    }

    private function initializeContactId($contact)
    {
        if (is_object($contact)) {
            $this->contact = $contact;
            $this->contactId = $contact->Admn_User_Mast_UIN;
            $this->MoOn = $contact->MoOn ?? null;
            $this->Prty = $contact->Prty ?? 'I';
        } else {
            $this->contactId = $contact;
            $this->contact = null;
        }
    }

    private function loadContactDetails()
    {
        $contact = $this->contact ?? DB::table('admn_user_mast')
            ->where('Admn_User_Mast_UIN', $this->contactId)
            ->first();
        if (!$contact) {
            return redirect()->route('contacts.index');
        }
        $this->mapContactFields($contact);
        $this->loadEmails();
        $this->loadPhones();
        $this->loadLandlines();
        $this->loadAddresses();
        $this->loadSelectedTags();
        $this->loadBankAccounts();
        $this->loadDocuments();
        $this->loadReferences();
        // Removed loadEducations and loadWorkExperiences calls
    }

    private function mapContactFields($contact)
    {
        $fields = [
            'Prfx_UIN',
            'FaNm',
            'MiNm',
            'LaNm',
            'Gend',
            'Blood_Grp',
            'Brth_Dt',
            'Anvy_Dt',
            'Deth_Dt',
            'Note',
            'MoOn',
            'Web',
            'FcBk',
            'Twtr',
            'LnDn',
            'Intg',
            'Yaho',
            'Redt',
            'Ytb',
            'Empl_Type',
            'Comp_Name',
            'Comp_Dsig',
            'Comp_LdLi',
            'Comp_Desp',
            'Comp_Emai',
            'Comp_Web',
            'Comp_Addr',
        ];
        foreach ($fields as $field) {
            $this->$field = $contact->$field ?? null;
        }
        $this->existing_avatar = $contact->Prfl_Pict ?? null;
        $this->Prty = $contact->Prty ?? 'I';
        $this->assignedGroupId = $contact->Admn_Grup_Mast_UIN ? (int) $contact->Admn_Grup_Mast_UIN : null;
        $this->isVerified = ($contact->Is_Vf ?? null) === self::STATUS_VERIFIED;
    }







    private function loadEmails()
    {
        $this->emails = $this->loadRelatedData(
            'admn_cnta_emai_mast',
            'Admn_Cnta_Emai_Mast_UIN',
            fn($item) => [
                'Emai_Addr' => $item->Emai_Addr,
                'Emai_Type' => $item->Emai_Type,
                'Is_Prmy' => (bool) $item->Is_Prmy,
            ]
        );
        if (empty($this->emails)) {
            $this->addEmail();
        }
    }

    public function addEmail()
    {
        if (count($this->emails) < self::MAX_EMAILS) {
            $this->emails[] = [
                'Emai_Addr' => '',
                'Emai_Type' => 'self generated',
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

    private function loadPhones()
    {
        $this->phones = $this->loadRelatedData(
            'admn_cnta_phon_mast',
            'Admn_Cnta_Phon_Mast_UIN',
            fn($item) => [
                'Phon_Numb' => $item->Phon_Numb,
                'Phon_Type' => $item->Phon_Type,
                'Cutr_Code' => $item->Cutr_Code,
                'Has_WtAp' => (bool) $item->Has_WtAp,
                'Has_Telg' => (bool) $item->Has_Telg,
                'Is_Prmy' => (bool) $item->Is_Prmy,
            ]
        );
        if (empty($this->phones)) {
            $this->addPhone();
        }
    }

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

    private function loadLandlines()
    {
        $this->landlines = $this->loadRelatedData(
            'admn_cnta_land_mast',
            'Admn_Cnta_Land_Mast_UIN',
            fn($item) => [
                'Land_Numb' => $item->Land_Numb,
                'Land_Type' => $item->Land_Type,
                'Cutr_Code' => $item->Cutr_Code,
                'Is_Prmy' => (bool) $item->Is_Prmy,
            ]
        );
        if (empty($this->landlines)) {
            $this->addLandline();
        }
    }

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

    private function loadAddresses()
    {
        $this->addresses = DB::table('admn_cnta_addr_mast as a')
            ->leftJoin('admn_pincode_mast as p', 'a.Admn_PinCode_Mast_UIN', '=', 'p.Admn_PinCode_Mast_UIN')
            ->where('a.Admn_User_Mast_UIN', $this->contactId)
            ->select('a.*', 'p.Code as pincode_value')
            ->orderBy('a.Is_Prmy', 'desc')
            ->get()
            ->map(fn($addr) => $this->hydrateAddressFields((array) $addr, $addr->Admn_Cnta_Addr_Mast_UIN))
            ->toArray();
        if (empty($this->addresses)) {
            $this->addAddress();
        }
    }

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

    private function loadReferences()
    {
        $this->references = $this->loadRelatedData(
            'admn_cnta_refa_mast',
            'Admn_Cnta_Refa_Mast_UIN',
            fn($item) => [
                'Refa_Name' => $item->Refa_Name,
                'Refa_Rsip' => $item->Refa_Rsip,
                'Refa_Phon' => $item->Refa_Phon,
                'Refa_Emai' => $item->Refa_Emai,
                'Is_Prmy'   => $item->Is_Prmy ?? false,
            ]
        );
        if (empty($this->references)) {
            $this->addReference();
        }
    }

    public function addReference()
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

    public function removeReference($index)
    {
        $this->removeItem('references', $index);
    }

    public function setPrimaryReference($index)
    {
        $this->setPrimaryItem('references', $index);
    }

    private function syncPhoneCodeWithPrimaryAddress()
    {
        try {
            $primaryAddress = collect($this->addresses)->firstWhere('Is_Prmy', true);
            if ($primaryAddress && !empty($primaryAddress['Admn_Cutr_Mast_UIN'])) {
                $country = Admn_Cutr_Mast::where('Stau_UIN', self::STATUS_ACTIVE) // status Active
                    ->where('Admn_Cutr_Mast_UIN', $primaryAddress['Admn_Cutr_Mast_UIN'])
                    ->first();

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

    private function loadBankAccounts()
    {
        $this->bankAccounts = DB::table('admn_user_bank_mast as b')
            ->leftJoin('admn_bank_attachments as ba', 'b.Admn_User_Bank_Mast_UIN', '=', 'ba.Admn_User_Bank_Mast_UIN')
            ->where('b.Admn_User_Mast_UIN', $this->contactId)
            ->where('b.Stau', self::STATUS_ACTIVE)
            ->select('b.*', 'ba.Atch_Path', 'ba.Atch_Path_2', 'ba.Atch_Type_1', 'ba.Atch_Type_2')
            ->get()
            ->map(fn($bank) => [
                'Admn_User_Bank_Mast_UIN' => $bank->Admn_User_Bank_Mast_UIN,
                'Bank_Name_UIN' => $bank->Bank_Name_UIN,
                'Bank_Brnc_Name' => $bank->Bank_Brnc_Name,
                'Acnt_Type' => $bank->Acnt_Type,
                'Acnt_Numb' => $bank->Acnt_Numb,
                'IFSC_Code' => $bank->IFSC_Code,
                'Swift_Code' => $bank->Swift_Code,
                'Prmy' => (bool) $bank->Prmy,
                'Atch_Path' => $bank->Atch_Path,
                'Atch_Path_2' => $bank->Atch_Path_2,
                'Atch_Type_1' => $bank->Atch_Type_1,
                'Atch_Type_2' => $bank->Atch_Type_2,
                'temp_upload_1' => null,
                'temp_upload_2' => null,
            ])
            ->toArray();
        if (empty($this->bankAccounts)) {
            $this->addBank();
        }
    }

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
                'Atch_Path' => null,
                'Atch_Path_2' => null,
                'Atch_Type_1' => '',
                'Atch_Type_2' => '',
                'temp_upload_1' => null,
                'temp_upload_2' => null,
            ];
        }
    }

    public function clearBankAttachment($index, $num)
    {
        $num = (int)$num;
        $pathField = $num === 1 ? 'Atch_Path' : 'Atch_Path_2';
        $tempField = $num === 1 ? 'temp_upload_1' : 'temp_upload_2';

        // If there was an existing path, we might want to track it for deletion on save
        // For now, just null it out in the array. 
        $this->bankAccounts[$index][$pathField] = null;
        $this->bankAccounts[$index][$tempField] = null;
    }

    public function removeBank($index)
    {
        $this->removeItem('bankAccounts', $index);
    }

    public function setPrimaryBank(int $index)
    {
        $this->setPrimaryItem('bankAccounts', $index, 'Prmy');
    }

    protected function handleBankUpload(int $index): void
    {
        // Ensure arrays exist
        $this->bankAccounts[$index]['newAttachments'] ??= [];

        // Clear previous errors for this bank index
        $this->resetErrorBag("bankAccounts.$index.newAttachments");

        foreach ($this->bankAccounts[$index]['temp_upload'] as $file) {

            // Temporarily push file
            $this->bankAccounts[$index]['newAttachments'][] = $file;

            $fileIndex = array_key_last($this->bankAccounts[$index]['newAttachments']);

            try {
                // ✅ Validate THIS file using TRAIT rules & messages
                $this->validateOnly(
                    "bankAccounts.$index.newAttachments.$fileIndex"
                );
            } catch (\Illuminate\Validation\ValidationException $e) {
                // ❌ Invalid → remove file from ready list
                unset($this->bankAccounts[$index]['newAttachments'][$fileIndex]);

                // Reindex array after removal
                $this->bankAccounts[$index]['newAttachments'] =
                    array_values($this->bankAccounts[$index]['newAttachments']);

                // 🔥 IMPORTANT: rethrow so Livewire keeps the trait error
                throw $e;
            }
        }

        // Clear temp upload input
        $this->bankAccounts[$index]['temp_upload'] = [];
    }




    public function removeNewAttachment($bankIndex, $attachmentIndex)
    {
        if (isset($this->bankAccounts[$bankIndex]['newAttachments'][$attachmentIndex])) {
            array_splice($this->bankAccounts[$bankIndex]['newAttachments'], $attachmentIndex, 1);
        }
    }

    public function deleteExistingAttachment($bankIndex, $attachmentId)
    {
        try {
            $attachment = AdmnBankAttachment::find($attachmentId);
            if ($attachment) {
                Storage::disk('public')->delete($attachment->Atch_Path);
                $attachment->delete();
                $this->bankAccounts[$bankIndex]['existing_attachments'] = array_values(
                    array_filter(
                        $this->bankAccounts[$bankIndex]['existing_attachments'],
                        fn($a) => $a['Admn_Bank_Attachment_UIN'] != $attachmentId
                    )
                );
                session()->flash('success', 'Attachment deleted successfully.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting attachment: ' . $e->getMessage());
            session()->flash('error', 'Failed to delete attachment.');
        }
    }

    /**
     * Load documents from database
     */
    private function loadDocuments(): void
    {
        $existing = Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)
            ->where('Stau', self::STATUS_ACTIVE)
            ->orderBy('Regn_Numb')
            ->orderBy('Docu_Atch_Path')
            ->get();

        if ($existing->isEmpty()) {
            $this->documents = [];
            // REMOVED: $this->addDocument(); // Do not auto-add a blank document either, unless desired
            // If you want to auto-add a blank one, keep it, but ensure 'Prmy' is false in addDocument
            $this->addDocument();

            return;
        }

        $grouped = $existing->groupBy('Regn_Numb');

        $this->documents = $grouped->map(function ($group) {
            $withAttachment = $group->firstWhere('Docu_Atch_Path', '!=', null);
            $first = $withAttachment ?? $group->first();

            return [
                'Admn_Docu_Mast_UIN' => $first->Admn_Docu_Mast_UIN,
                'Docu_Name' => $first->Docu_Name ?? '',
                'Regn_Numb' => $first->Regn_Numb ?? '',
                'selected_types' => $group->pluck('Admn_Docu_Type_Mast_UIN')->toArray(),
                'Admn_Cutr_Mast_UIN' => $first->Admn_Cutr_Mast_UIN ?? null,
                'Auth_Issd' => $first->Auth_Issd ?? '',
                'Vald_From' => optional($first->Vald_From)->format('Y-m-d'),
                'Vald_Upto' => optional($first->Vald_Upto)->format('Y-m-d'),
                'Docu_Atch_Path' => $first->Docu_Atch_Path,
                'existing_file_path' => $first->Docu_Atch_Path,
                'is_dropdown_open' => false,
                'Prmy' => (bool) ($first->Prmy ?? false),
            ];
        })->values()->toArray();
    }

    /**
     * Add a new document entry
     */
    public function addDocument(): void
    {
        if (count($this->documents) >= self::MAX_DOCUMENTS) {
            return;
        }

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
            'Prmy' => false, // CHANGE: Always false by default
        ];
    }

    // In app/Livewire/Contacts/Edit.php

    public function setPrimaryDocument($index)
    {
        // 1. Validate index exists
        if (!isset($this->documents[$index])) {
            return;
        }

        // 2. Update Local State (Visual Feedback)
        foreach ($this->documents as $key => &$doc) {
            $doc['Prmy'] = ($key === $index);
        }

        // 3. Perform Live Database Update
        $targetDoc = $this->documents[$index];
        $regnNumb = $targetDoc['Regn_Numb'] ?? null;
        $userUIN = $this->contactId;

        // We can only update the DB if the document has been saved (has a Registration Number)
        if (!empty($regnNumb) && $userUIN) {
            try {
                DB::transaction(function () use ($userUIN, $regnNumb) {
                    // Step A: Set 'Prmy' to 0 for ALL documents belonging to this user
                    Admn_Docu_Mast::where('Admn_User_Mast_UIN', $userUIN)
                        ->update(['Prmy' => 0]);

                    // Step B: Set 'Prmy' to 1 for the specific Registration Number group
                    Admn_Docu_Mast::where('Admn_User_Mast_UIN', $userUIN)
                        ->where('Regn_Numb', $regnNumb)
                        ->update(['Prmy' => 1]);
                });
            } catch (\Exception $e) {
                Log::error('Live Primary Update Failed: ' . $e->getMessage());
                $this->dispatch('toast-error', message: 'Failed to update database.');
            }
        } else {
            // Fallback for new/unsaved documents (they exist in array but not in DB yet)
            $this->dispatch('toast-info', message: 'Set as primary. Click Update to persist.');
        }
    }

    public function removeDocument($index)
    {
        if (!isset($this->documents[$index])) {
            return;
        }

        $document = $this->documents[$index];

        try {
            // Delete existing file from storage
            $filePath = $document['existing_file_path'] ?? null;
            if ($filePath && is_string($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            // Delete from database if it exists
            if (!empty($document['Regn_Numb'])) {
                Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)
                    ->where('Regn_Numb', $document['Regn_Numb'])
                    ->delete();
            }

            // Remove from local array
            unset($this->documents[$index]);
            $this->documents = array_values($this->documents);

            // REMOVED: The logic that checked if primary was missing and set documents[0] to true.

            Log::info("Document removed from database: Regn_Numb={$document['Regn_Numb']}");
        } catch (\Exception $e) {
            Log::error('Error removing document: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleDocumentDropdown($index)
    {
        $currentState = $this->documents[$index]['is_dropdown_open'] ?? false;
        foreach ($this->documents as &$doc) {
            $doc['is_dropdown_open'] = false;
        }
        $this->documents[$index]['is_dropdown_open'] = !$currentState;
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
            $fileSizeMB = 0;
            if (Storage::disk('public')->exists($path)) {
                $fileSizeMB = $this->bytesToMB(Storage::disk('public')->size($path));
                Storage::disk('public')->delete($path);
            }
            if ($fileSizeMB > 0) {
                $this->decrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Delete Entry', 'Document Attachment deleted', 1, 'MB', -$fileSizeMB);
            }
        }
        $this->documents[$index]['Docu_Atch_Path'] = null;
        $this->documents[$index]['existing_file_path'] = null;
        $this->resetErrorBag("documents.$index.Docu_Atch_Path");
    }

    public function clearDocumentFile($index)
    {
        if (isset($this->documents[$index])) {
            $this->documents[$index]['Docu_Atch_Path'] = null;
            $this->resetErrorBag("documents.$index.Docu_Atch_Path");
        }
    }

    private function loadSelectedTags()
    {
        $this->selectedTags = DB::table('admn_cnta_tag_mast as ct')
            ->join('admn_tag_mast as t', 't.Admn_Tag_Mast_UIN', '=', 'ct.Admn_Tag_Mast_UIN')
            ->where('ct.Admn_User_Mast_UIN', $this->contactId)
            ->where('t.stau', self::STATUS_ACTIVE)  // ✅ Only Active Tags
            ->pluck('ct.Admn_Tag_Mast_UIN')
            ->toArray();
    }

    public function removeProfilePicture()
    {
        if ($this->existing_avatar) {
            $fileSizeMB = 0;
            if (Storage::disk('public')->exists($this->existing_avatar)) {
                $fileSizeMB = $this->bytesToMB(Storage::disk('public')->size($this->existing_avatar));
                Storage::disk('public')->delete($this->existing_avatar);
            }
            if ($fileSizeMB > 0) {
                $this->decrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Delete Entry', 'Profile Picture deleted', 1, 'MB', -$fileSizeMB);
            }
            $this->existing_avatar = null;
        }
        $this->Prfl_Pict = null;
        $this->resetErrorBag('Prfl_Pict');
    }

    private function loadRelatedData($table, $pk, $mapper)
    {
        return DB::table($table)
            ->where('Admn_User_Mast_UIN', $this->contactId)
            ->orderBy(Str::contains($table, ['emai', 'phon', 'addr']) ? 'Is_Prmy' : $pk, 'desc')
            ->get()
            ->map(fn($item) => array_merge(['id' => $item->$pk], $mapper($item)))
            ->toArray();
    }

    private function removeItem($arrayName, $index)
    {
        if (count($this->$arrayName) <= 1) {
            return;
        }
        $this->resetErrorBag("$arrayName.$index");
        unset($this->{$arrayName}[$index]);
        $this->{$arrayName} = array_values($this->{$arrayName});
        if (collect($this->$arrayName)->where('Is_Prmy', true)->isEmpty() && !empty($this->{$arrayName})) {
            $this->{$arrayName}[0]['Is_Prmy'] = true;
        }
    }

    private function setPrimaryItem($arrayName, $index, $field = 'Is_Prmy')
    {
        foreach ($this->$arrayName as $key => &$item) {
            $item[$field] = ($key === $index);
        }
    }

    private function getAvailableGroups()
    {
        $orgUIN = session('selected_Orga_UIN');

        return Admn_Grup_Mast::where('Admn_Orga_Mast_UIN', $orgUIN)
            ->orderBy('Name')
            ->get();
    }

    private function generateAttachmentFileName($extension = ''): string
    {
        $orgUIN = session('selected_Orga_UIN');
        $contactUIN = $this->contactId;
        $timestamp = now()->format('Ymd_His');
        $filename = "{$orgUIN}_{$contactUIN}_{$timestamp}";
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

    /**
     * Save only the group assignment and show success message.
     */
    public function saveGroupOnly(): void
    {
        $orgUIN = session('selected_Orga_UIN');
        $authUIN = session('authenticated_user_uin');

        DB::table('admn_user_mast')
            ->where('Admn_User_Mast_UIN', $this->contactId)
            ->where('Admn_Orga_Mast_UIN', $orgUIN)
            ->update([
                'Admn_Grup_Mast_UIN' => $this->assignedGroupId ?: null,
                'MoBy' => $authUIN,
                'MoOn' => now()->timestamp,
            ]);

        if ($this->assignedGroupId) {
            $groupName = Admn_Grup_Mast::find($this->assignedGroupId)?->Name ?? 'selected group';
        } else {
            $groupName = 'None (cleared)';
        }

        session()->flash('group_saved', "Group saved successfully: {$groupName}");
    }

    public function save()
    {
        try {
            if ($this->isPlanExpired()) {
                $this->addError('general', 'No active subscription plan found or plan expired. Please upgrade or renew your plan.');
                return;
            }

            $this->normalizeSelectedTags();
            $this->validate();
            DB::beginTransaction();

            $userUIN = session('authenticated_user_uin');
            $orgUIN = session('selected_Orga_UIN');

            $contactData = [
                'Prty' => trim($this->Prty) ?: null,
                'Prfx_UIN' => $this->Prfx_UIN,
                'FaNm' => $this->Prty === 'B' ? trim($this->FaNm) : preg_replace('/[^a-zA-Z ]/', '', trim($this->FaNm)),
                'MiNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->MiNm)) ?: null,
                'LaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->LaNm)) ?: null,
                'Gend' => $this->Gend,
                'Blood_Grp' => $this->Blood_Grp,

                // ✅ UPDATED DATE LOGIC
                'Brth_Dt' => $this->formatDateForDatabase($this->Brth_Dt),
                'Anvy_Dt' => $this->formatDateForDatabase($this->Anvy_Dt),
                'Deth_Dt' => $this->formatDateForDatabase($this->Deth_Dt),

                'Note' => $this->Note,
                'Web' => $this->Web,
                'FcBk' => $this->FcBk,
                'Twtr' => $this->Twtr,
                'LnDn' => $this->LnDn,
                'Intg' => $this->Intg,
                'Yaho' => $this->Yaho,
                'Redt' => $this->Redt,
                'Ytb' => $this->Ytb,
                'Empl_Type' => $this->Empl_Type ?: 'job',
                'Comp_Name' => trim($this->Comp_Name) ?: null,
                'Comp_Dsig' => trim($this->Comp_Dsig) ?: null,
                'Comp_LdLi' => trim($this->Comp_LdLi) ?: null,
                'Comp_Desp' => trim($this->Comp_Desp) ?: null,
                'Comp_Emai' => trim($this->Comp_Emai) ?: null,
                'Comp_Web' => trim($this->Comp_Web) ?: null,
                'Comp_Addr' => trim($this->Comp_Addr) ?: null,
                'Admn_Grup_Mast_UIN' => $this->assignedGroupId,
                // 'Is_Vf' => self::STATUS_VERIFIED,
                'MoOn' => now(),
                'MoBy' => $userUIN,
            ];

            if ($this->Prfl_Pict) {
                try {
                    $fileSizeMB = $this->bytesToMB($this->Prfl_Pict->getSize());
                    if (!$this->canAddStorage($fileSizeMB)) {
                        throw new \Exception('Storage limit exceeded. Cannot upload profile picture.');
                    }
                    if ($this->existing_avatar) {
                        $oldSizeMB = 0;
                        if (Storage::disk('public')->exists($this->existing_avatar)) {
                            $oldSizeMB = $this->bytesToMB(Storage::disk('public')->size($this->existing_avatar));
                            Storage::disk('public')->delete($this->existing_avatar);
                        }
                        if ($oldSizeMB > 0) {
                            $this->decrementUtilization('Ulzn_Spce', $oldSizeMB);
                            $this->logActivity('Delete Entry', 'Profile Picture replaced/deleted', 1, 'MB', -$oldSizeMB);
                        }
                    }
                    $extension = $this->Prfl_Pict->getClientOriginalExtension();
                    $filename = $this->generateAttachmentFileName($extension);
                    $filePath = $this->Prfl_Pict->storeAs('Attachment/Profile', $filename, 'public');
                    $fullPath = Storage::disk('public')->path($filePath);

                    if (file_exists($fullPath) && class_exists('Intervention\Image\ImageManagerStatic')) {
                        Image::make($fullPath)->save();
                    }
                    $contactData['Prfl_Pict'] = $filePath;
                    $this->incrementUtilization('Ulzn_Spce', $fileSizeMB);
                    $this->logActivity('Edit with Attachment', 'Profile Picture uploaded', 1, 'MB', $fileSizeMB);
                } catch (\Exception $e) {
                    Log::error('Error processing profile picture: ' . $e->getMessage());
                    session()->flash('error', 'Error uploading profile picture: ' . $e->getMessage());
                }
            } elseif ($this->existing_avatar) {
                $contactData['Prfl_Pict'] = $this->existing_avatar;
            } else {
                $contactData['Prfl_Pict'] = null;
            }

            DB::table('admn_user_mast')
                ->where('Admn_User_Mast_UIN', $this->contactId)
                ->update($contactData);

            $this->syncEmails($userUIN, $orgUIN);
            $this->syncPhones($userUIN, $orgUIN);
            $this->syncLandlines($userUIN, $orgUIN);
            $this->syncAddresses($userUIN, $orgUIN);
            $this->syncTags($userUIN, $orgUIN);
            $this->syncBankAccounts($userUIN, $orgUIN);
            $this->syncDocuments($userUIN);
            $this->syncReferences($userUIN, $orgUIN);

            DB::commit();

            $description = "Updated contact: " . trim($this->FaNm . ' ' . $this->MiNm . ' ' . $this->LaNm);
            $hasUploadedFiles = collect($this->documents)->contains(function ($doc) {
                return !empty($doc['Docu_Atch_Path']) || !empty($doc['file']);
            }) || !empty($this->Prfl_Pict);
            $action = $hasUploadedFiles ? 'Edit with Attachment' : 'Edit Entry';
            $this->logActivity($action, $description);

            session()->flash('success', 'Contact updated successfully!');

            return redirect()->route('contacts.index', $this->contactId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation failed', ['errors' => $e->errors()]);
            session()->flash('error', 'Validation failed. Please check the highlighted fields.');
            $this->dispatch('scroll-to-errors');
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contact Update Error: ' . $e->getMessage());
            session()->flash('error', 'Critical Error: ' . $e->getMessage());
            $this->dispatch('scroll-to-errors');
        }
    }

    private function syncEmails($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_emai_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->emails as $email) {
            if (!empty($email['Emai_Addr'])) {
                // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
                $emailUIN = $this->generateUniqueUIN('admn_cnta_emai_mast', 'Admn_Cnta_Emai_Mast_UIN');

                DB::table('admn_cnta_emai_mast')->insert([
                    'Admn_Cnta_Emai_Mast_UIN' => $emailUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Emai_Addr' => trim(strtolower($email['Emai_Addr'])),
                    'Emai_Type' => $email['Emai_Type'] ?? 'self generated',
                    'Is_Prmy' => (bool) ($email['Is_Prmy'] ?? false),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncPhones($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_phon_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->phones as $phone) {
            if (!empty($phone['Phon_Numb'])) {
                // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
                $phoneUIN = $this->generateUniqueUIN('admn_cnta_phon_mast', 'Admn_Cnta_Phon_Mast_UIN');

                DB::table('admn_cnta_phon_mast')->insert([
                    'Admn_Cnta_Phon_Mast_UIN' => $phoneUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Phon_Numb' => $phone['Phon_Numb'],
                    'Phon_Type' => $phone['Phon_Type'] ?? 'self',
                    'Cutr_Code' => $phone['Cutr_Code'] ?? self::PHONE_CODE_INDIA,
                    'Has_WtAp' => (bool) ($phone['Has_WtAp'] ?? false),
                    'Has_Telg' => (bool) ($phone['Has_Telg'] ?? false),
                    'Is_Prmy' => (bool) ($phone['Is_Prmy'] ?? false),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncLandlines($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_land_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->landlines as $landline) {
            if (!empty($landline['Land_Numb'])) {
                // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
                $landlineUIN = $this->generateUniqueUIN('admn_cnta_land_mast', 'Admn_Cnta_Land_Mast_UIN');

                DB::table('admn_cnta_land_mast')->insert([
                    'Admn_Cnta_Land_Mast_UIN' => $landlineUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Admn_Orga_Mast_UIN' => $orgUIN,
                    'Land_Numb' => $landline['Land_Numb'],
                    'Land_Type' => $landline['Land_Type'] ?? 'home',
                    'Cutr_Code' => $landline['Cutr_Code'] ?? self::PHONE_CODE_INDIA,
                    'Is_Prmy' => (bool) ($landline['Is_Prmy'] ?? false),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncAddresses($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_addr_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->addresses as $address) {
            if (!empty($address['Addr']) || !empty($address['Admn_PinCode_Mast_UIN'])) {
                // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
                $addressUIN = $this->generateUniqueUIN('admn_cnta_addr_mast', 'Admn_Cnta_Addr_Mast_UIN');

                DB::table('admn_cnta_addr_mast')->insert([
                    'Admn_Cnta_Addr_Mast_UIN' => $addressUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Addr' => $address['Addr'] ?? null,
                    'Loca' => $address['Loca'] ?? null,
                    'Lndm' => $address['Lndm'] ?? null,
                    'Admn_Addr_Type_Mast_UIN' => $address['Admn_Addr_Type_Mast_UIN'] ?? null,
                    'Admn_Cutr_Mast_UIN' => $address['Admn_Cutr_Mast_UIN'] ?? null,
                    'Admn_Stat_Mast_UIN' => $address['Admn_Stat_Mast_UIN'] ?? null,
                    'Admn_Dist_Mast_UIN' => $address['Admn_Dist_Mast_UIN'] ?? null,
                    'Admn_PinCode_Mast_UIN' => $address['Admn_PinCode_Mast_UIN'] ?? null,
                    'Is_Prmy' => (bool) ($address['Is_Prmy'] ?? false),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncTags($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_tag_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->selectedTags as $tagId) {
            // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
            $tagMapUIN = $this->generateUniqueUIN('admn_cnta_tag_mast', 'Admn_Cnta_Tag_Mast_UIN');

            DB::table('admn_cnta_tag_mast')->insert([
                'Admn_Cnta_Tag_Mast_UIN' => $tagMapUIN,
                'Admn_User_Mast_UIN' => $this->contactId,
                'Admn_Tag_Mast_UIN' => $tagId,
                'CrOn' => now(),
                'CrBy' => $userUIN,
            ]);
        }
    }

    private function syncReferences($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_refa_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->references as $ref) {
            if (!empty($ref['Refa_Name'])) {
                $refUIN = $this->generateUniqueUIN('admn_cnta_refa_mast', 'Admn_Cnta_Refa_Mast_UIN');

                DB::table('admn_cnta_refa_mast')->insert([
                    'Admn_Cnta_Refa_Mast_UIN' => $refUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Refa_Name' => trim($ref['Refa_Name']),
                    'Refa_Rsip' => trim($ref['Refa_Rsip'] ?? ''),
                    'Refa_Phon' => trim($ref['Refa_Phon'] ?? ''),
                    'Refa_Emai' => trim(strtolower($ref['Refa_Emai'] ?? '')),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }


    private function syncBankAccounts($userUIN, $orgUIN)
    {
        $existingBankIds = Admn_User_Bank_Mast::where('Admn_User_Mast_UIN', $this->contactId)
            ->where('Stau', self::STATUS_ACTIVE)
            ->pluck('Admn_User_Bank_Mast_UIN')
            ->toArray();

        foreach ($this->bankAccounts as $bank) {
            if (empty($bank['Acnt_Numb'])) {
                continue;
            }

            $bankId = $bank['Admn_User_Bank_Mast_UIN'] ?? null;

            // Handle Attachments
            $atch1Path = $bank['Atch_Path'] ?? null;
            $atch2Path = $bank['Atch_Path_2'] ?? null;

            if (!empty($bank['temp_upload_1'])) {
                $fileSizeMB = $this->bytesToMB($bank['temp_upload_1']->getSize());
                if (!$this->canAddStorage($fileSizeMB)) {
                    throw new \Exception('Storage limit exceeded. Cannot upload Bank Document 1.');
                }
                // Delete old file if exists
                if ($atch1Path && Storage::disk('public')->exists($atch1Path)) {
                    $oldSizeMB = $this->bytesToMB(Storage::disk('public')->size($atch1Path));
                    Storage::disk('public')->delete($atch1Path);
                    $this->decrementUtilization('Ulzn_Spce', $oldSizeMB);
                    $this->logActivity('Delete Entry', 'Bank Document 1 replaced', 1, 'MB', -$oldSizeMB);
                }
                $extension = $bank['temp_upload_1']->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $atch1Path = $bank['temp_upload_1']->storeAs('Attachment/Bank', $filename, 'public');
                $this->incrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Edit with Attachment', 'Bank Document 1 uploaded', 1, 'MB', $fileSizeMB);
            }

            if (!empty($bank['temp_upload_2'])) {
                $fileSizeMB = $this->bytesToMB($bank['temp_upload_2']->getSize());
                if (!$this->canAddStorage($fileSizeMB)) {
                    throw new \Exception('Storage limit exceeded. Cannot upload Bank Document 2.');
                }
                // Delete old file if exists
                if ($atch2Path && Storage::disk('public')->exists($atch2Path)) {
                    $oldSizeMB = $this->bytesToMB(Storage::disk('public')->size($atch2Path));
                    Storage::disk('public')->delete($atch2Path);
                    $this->decrementUtilization('Ulzn_Spce', $oldSizeMB);
                    $this->logActivity('Delete Entry', 'Bank Document 2 replaced', 1, 'MB', -$oldSizeMB);
                }
                $extension = $bank['temp_upload_2']->getClientOriginalExtension();
                $timestamp = now()->format('Ymd_His');
                $filename = $orgUIN . "_{$timestamp}_2.{$extension}";
                $atch2Path = $bank['temp_upload_2']->storeAs('Attachment/Bank', $filename, 'public');
                $this->incrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Edit with Attachment', 'Bank Document 2 uploaded', 1, 'MB', $fileSizeMB);
            }

            $bankData = [
                'Bank_Name_UIN' => !empty($bank['Bank_Name_UIN']) ? $bank['Bank_Name_UIN'] : null,
                'Bank_Brnc_Name' => $bank['Bank_Brnc_Name'],
                'Acnt_Type' => $bank['Acnt_Type'],
                'Acnt_Numb' => $bank['Acnt_Numb'],
                'IFSC_Code' => $bank['IFSC_Code'],
                'Swift_Code' => $bank['Swift_Code'],
                'Prmy' => (bool) ($bank['Prmy'] ?? false),
            ];

            $targetBankUIN = $bankId;
            if ($bankId && in_array($bankId, $existingBankIds)) {
                Admn_User_Bank_Mast::where('Admn_User_Bank_Mast_UIN', $bankId)->update($bankData + [
                    'MoOn' => now(),
                    'MoBy' => $userUIN,
                ]);

                $existingBankIds = array_diff($existingBankIds, [$bankId]);
            } else {
                $targetBankUIN = $this->generateUniqueUIN('admn_user_bank_mast', 'Admn_User_Bank_Mast_UIN');
                Admn_User_Bank_Mast::create($bankData + [
                    'Admn_User_Bank_Mast_UIN' => $targetBankUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Admn_Orga_Mast_UIN' => $orgUIN,
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }

            // Sync to admn_bank_attachments
            if ($atch1Path || $atch2Path) {
                $existingAttach = DB::table('admn_bank_attachments')
                    ->where('Admn_User_Bank_Mast_UIN', $targetBankUIN)
                    ->first();

                $attachData = [
                    'Atch_Path' => $atch1Path,
                    'Atch_Path_2' => $atch2Path,
                    'Atch_Type_1' => $bank['Atch_Type_1'] ?: null,
                    'Atch_Type_2' => $bank['Atch_Type_2'] ?: null,
                    'MoOn' => now(),
                    'MoBy' => $userUIN,
                ];

                if (!empty($bank['temp_upload_1'])) {
                    $attachData['Orgn_Name'] = $bank['temp_upload_1']->getClientOriginalName();
                } elseif (!empty($bank['temp_upload_2'])) {
                    $attachData['Orgn_Name'] = $bank['temp_upload_2']->getClientOriginalName();
                }

                if ($existingAttach) {
                    DB::table('admn_bank_attachments')
                        ->where('Admn_Bank_Attachment_UIN', $existingAttach->Admn_Bank_Attachment_UIN)
                        ->update($attachData);
                } else {
                    $attachUIN = $this->generateUniqueUIN('admn_bank_attachments', 'Admn_Bank_Attachment_UIN');
                    DB::table('admn_bank_attachments')->insert($attachData + [
                        'Admn_Bank_Attachment_UIN' => $attachUIN,
                        'Admn_User_Bank_Mast_UIN' => $targetBankUIN,
                        'CrOn' => now(),
                        'CrBy' => $userUIN,
                    ]);
                }
            } else {
                // If no attachments, ensure no orphan record exists in attachments table
                DB::table('admn_bank_attachments')->where('Admn_User_Bank_Mast_UIN', $targetBankUIN)->delete();
            }
        }

        if (!empty($existingBankIds)) {
            // Cleanup files and attachments before deleting records
            $banksToDelete = Admn_User_Bank_Mast::whereIn('Admn_User_Bank_Mast_UIN', $existingBankIds)->get();
            foreach ($banksToDelete as $ob) {
                if ($ob->Atch_Path && Storage::disk('public')->exists($ob->Atch_Path)) {
                    Storage::disk('public')->delete($ob->Atch_Path);
                }
                if ($ob->Atch_Path_2 && Storage::disk('public')->exists($ob->Atch_Path_2)) {
                    Storage::disk('public')->delete($ob->Atch_Path_2);
                }
            }
            DB::table('admn_bank_attachments')->whereIn('Admn_User_Bank_Mast_UIN', $existingBankIds)->delete();
            Admn_User_Bank_Mast::whereIn('Admn_User_Bank_Mast_UIN', $existingBankIds)->delete();
        }
    }

    private function syncDocuments($userUIN)
    {
        try {
            $validDocs = collect($this->documents)->filter(
                fn($d) => !empty($d['selected_types']) && !empty(trim($d['Regn_Numb'] ?? ''))
            );

            $existing = Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)->get();

            if ($validDocs->isEmpty()) {
                // Delete all if no valid docs
                foreach ($existing as $doc) {
                    if ($doc->Docu_Atch_Path) {
                        Storage::disk('public')->delete($doc->Docu_Atch_Path);
                    }
                }
                Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)->delete();

                return;
            }

            $keptRegns = $validDocs->pluck('Regn_Numb')->unique()->toArray();
            $toDelete = $existing->whereNotIn('Regn_Numb', $keptRegns);

            if ($toDelete->isNotEmpty()) {
                foreach ($toDelete as $doc) {
                    if ($doc->Docu_Atch_Path && Storage::disk('public')->exists($doc->Docu_Atch_Path)) {
                        Storage::disk('public')->delete($doc->Docu_Atch_Path);
                    }
                }
                Admn_Docu_Mast::destroy($toDelete->pluck('Admn_Docu_Mast_UIN'));
            }

            foreach ($validDocs as $doc) {
                $path = $this->storeDocumentFile($doc);
                $group = $existing->where('Regn_Numb', $doc['Regn_Numb']);
                $removedTypes = $group->whereNotIn('Admn_Docu_Type_Mast_UIN', $doc['selected_types']);

                if ($removedTypes->isNotEmpty()) {
                    Admn_Docu_Mast::destroy($removedTypes->pluck('Admn_Docu_Mast_UIN'));
                }

                foreach ($doc['selected_types'] as $typeId) {
                    $record = $group->firstWhere('Admn_Docu_Type_Mast_UIN', $typeId);

                    $data = [
                        'Docu_Name' => $doc['Docu_Name'] ?? '',
                        'Prmy' => $doc['Prmy'] ? 1 : 0,
                        'Admn_Cutr_Mast_UIN' => $doc['Admn_Cutr_Mast_UIN'] ?? null,
                        'Auth_Issd' => $doc['Auth_Issd'] ?? '',
                        'Vald_From' => $this->formatDateForDatabase($doc['Vald_From']),
                        'Vald_Upto' => $this->formatDateForDatabase($doc['Vald_Upto']),
                        'Docu_Atch_Path' => $path,
                        'MoOn' => now(),
                        'MoBy' => $userUIN,
                    ];

                    if ($record) {
                        DB::table('admn_docu_mast')
                            ->where('Admn_Docu_Mast_UIN', $record->Admn_Docu_Mast_UIN)
                            ->update($data);
                    } else {
                        $newId = $this->generateUniqueUIN('admn_docu_mast', 'Admn_Docu_Mast_UIN');

                        DB::table('admn_docu_mast')->insert(array_merge($data, [
                            'Admn_Docu_Mast_UIN' => $newId,
                            'Admn_User_Mast_UIN' => $this->contactId,
                            'Admn_Orga_Mast_UIN' => session('selected_Orga_UIN'),
                            'Admn_Docu_Type_Mast_UIN' => $typeId,
                            'Regn_Numb' => $doc['Regn_Numb'],
                            'Stau' => self::STATUS_ACTIVE,
                            'CrOn' => now(),
                            'CrBy' => $userUIN,
                        ]));
                        usleep(50000);
                    }
                }
            }

            Log::info("Documents synced successfully for user: {$this->contactId}");
        } catch (\Exception $e) {
            Log::error('Error syncing documents: ' . $e->getMessage());
            throw $e;
        }
    }

    private function storeDocumentFile($doc)
    {
        try {
            if (empty($doc['Docu_Atch_Path'])) {
                return null;
            }
            if (is_string($doc['Docu_Atch_Path'])) {
                return $doc['Docu_Atch_Path'];
            }
            if (is_object($doc['Docu_Atch_Path'])) {
                $file = $doc['Docu_Atch_Path'];

                $fileSizeMB = $this->bytesToMB($file->getSize());
                if (!$this->canAddStorage($fileSizeMB)) {
                    throw new \Exception("Storage limit exceeded. Cannot upload document '{$file->getClientOriginalName()}'.");
                }

                // Convert the config MAX_DOCUMENT_FILE_SIZE (in KB) to bytes for size comparison
                if ($file->getSize() > (self::MAX_DOCUMENT_FILE_SIZE * 1024)) {
                    Log::warning("Document exceeds 200KB: {$file->getClientOriginalName()}");
                    throw new \Exception("Document '{$file->getClientOriginalName()}' exceeds 200KB limit.");
                }
                $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    Log::warning("Invalid document type: {$file->getMimeType()}");
                    throw new \Exception('Invalid document type. Allowed: PDF, JPG, PNG, WEBP.');
                }
                $extension = $file->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $storagePath = $file->storeAs('Attachment/Document', $filename, 'public');
                Log::info("Document stored successfully: {$storagePath}");

                $this->incrementUtilization('Ulzn_Spce', $fileSizeMB);
                $this->logActivity('Edit with Attachment', 'Document uploaded', 1, 'MB', $fileSizeMB);

                return $storagePath;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            throw $e;
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
         | 6. Cross-validation: Bank Name ↔ Account Number
         |--------------------------------------------------*/
        if (preg_match('/bankAccounts\.(\d+)\.(Bank_Name_UIN|Acnt_Numb)/', $propertyName, $matches)) {
            $index = $matches[1];

            $this->validateOnly($propertyName);

            if (str_contains($propertyName, 'Bank_Name_UIN')) {
                $this->validateOnly("bankAccounts.$index.Acnt_Numb");
            } else {
                $this->validateOnly("bankAccounts.$index.Bank_Name_UIN");
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









    private function initializeFeatureEnablement()
    {
        $this->attachmentEnabled = \App\Traits\ManagesSettings::hasPermission('Attachment');
    }
}
