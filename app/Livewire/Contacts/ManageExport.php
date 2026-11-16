<?php

namespace App\Livewire\Contacts;

use App\Models\Admn_Addr_Type_Mast;
use App\Models\Admn_Bank_Name;
use App\Models\Admn_Docu_Type_Mast;
use App\Livewire\Traits\WithDocumentNames;
use Livewire\Attributes\On;
use Livewire\Component;

class ManageExport extends Component
{
    use WithDocumentNames;
    public bool $showExportModal = false;

    public array $columns = [
        'name_prefix'     => ['label' => 'Name Prefix',           'checked' => true],
        'first_name'      => ['label' => 'First Name',            'checked' => true],
        'middle_name'     => ['label' => 'Middle Name',           'checked' => false],
        'last_name'       => ['label' => 'Last Name',             'checked' => true],
        'birthday'        => ['label' => 'Birthday (DD/MM/YYYY)', 'checked' => false],
        'gender'          => ['label' => 'Gender',                'checked' => true, 'filter_value' => 'all'],
        //'self_employed'   => ['label' => 'Self Employed',         'checked' => false],
        
        'phone1_uses_of'    => ['label' => 'Phone 1 Uses of',         'checked' => true],
        'phone1_number'   => ['label' => 'Phone 1 Number',        'checked' => true],
        
        'phone2_uses_of'    => ['label' => 'Phone 2 Uses of',         'checked' => false],
        'phone2_number'   => ['label' => 'Phone 2 Number',        'checked' => false],
        
        'phone3_uses_of'    => ['label' => 'Phone 3 Uses of',         'checked' => false],
        'phone3_number'   => ['label' => 'Phone 3 Number',        'checked' => false],
        
        'email1'          => ['label' => 'Email 1',               'checked' => true],
        'email2'          => ['label' => 'Email 2',               'checked' => false],
        'email3'          => ['label' => 'Email 3',               'checked' => false],
        
        'address_type'    => ['label' => 'Address Type',          'checked' => false, 'filter_value' => 'all'],
        //'primary_address' => ['label' => 'Address',       'checked' => true],
        
        'bank_name'       => ['label' => 'Bank Name',     'checked' => false, 'filter_value' => ''],
        'bank_account'    => ['label' => 'Bank A/c Number', 'checked' => false],
        'bank_acc_type'   => ['label' => 'A/c Type',      'checked' => false],
        'bank_ifsc'       => ['label' => 'IFSC Code',     'checked' => false],
        
        'document_type'   => ['label' => 'Document Type', 'checked' => false],
        'document_name'   => ['label' => 'Document Name', 'checked' => false, 'filter_value' => ''],
        'document_number' => ['label' => 'Document Number', 'checked' => false],
        
        'ref_person_name' => ['label' => 'Primary Reference Name',   'checked' => false],
        'ref_person_rel'  => ['label' => 'Primary Reference Relationship', 'checked' => false],
        'ref_person_phone'=> ['label' => 'Primary Reference Phone',  'checked' => false],
        'ref_person_email'=> ['label' => 'Primary Reference Email',  'checked' => false],
        
        'tags'            => ['label' => 'Tags',                  'checked' => true],
        'groups'          => ['label' => 'Groups',                'checked' => false],
        'website'         => ['label' => 'Website',               'checked' => false],
        'facebook'        => ['label' => 'Facebook',              'checked' => false],
        'twitter'         => ['label' => 'Twitter',               'checked' => false],
        'linkedin'        => ['label' => 'LinkedIn',              'checked' => false],
        'instagram'       => ['label' => 'Instagram',             'checked' => false],
        'notes'           => ['label' => 'Notes',                 'checked' => false],
    ];

    // Dropdown options
    public array $genderOptions = [];
    public array $addressTypeOptions = [];
    public array $bankOptions = [];
    public array $documentTypeOptions = [];

    public function mount()
    {
        // Load dropdown data
        $this->genderOptions = ['Male', 'Female','Private Limited','Public Limited','Partnership','Sole Proprietorship','LLP','NGO','Other'];
        $this->addressTypeOptions = Admn_Addr_Type_Mast::pluck('Name', 'Admn_Addr_Type_Mast_UIN')->toArray();
        $this->bankOptions = Admn_Bank_Name::orderBy('Bank_Name')->pluck('Bank_Name', 'Bank_UIN')->toArray();
        $this->initializeWithDocumentNames();
    }

    #[On('openExportModal')]
    public function openModal(): void
    {
        $this->showExportModal = true;
    }

    public function closeModal(): void
    {
        $this->showExportModal = false;
    }

    public function selectAll(): void
    {
        foreach ($this->columns as $key => $col) {
            $this->columns[$key]['checked'] = true;
        }
    }

    public function deselectAll(): void
    {
        foreach ($this->columns as $key => $col) {
            $this->columns[$key]['checked'] = false;
        }
    }

    public function getSelectedCount(): int
    {
        return collect($this->columns)->filter(fn($col) => $col['checked'])->count();
    }

    public function exportCsv(): void
    {
        $selectedColumns = collect($this->columns)
            ->filter(fn($col) => $col['checked'])
            ->map(function($col, $key) {
                return [
                    'key' => $key,
                    'filter_value' => $col['filter_value'] ?? null
                ];
            })
            ->toArray();

        if (empty($selectedColumns)) {
            session()->flash('export_error', 'Please select at least one column to export.');
            return;
        }

        session(['export_selected_columns' => $selectedColumns]);
        session()->save();

        $this->redirect(route('contacts.export.csv'), navigate: false);
    }

    public function render()
    {
        return view('livewire.contacts.manage-export');
    }
}