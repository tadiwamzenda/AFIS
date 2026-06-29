<?php

namespace Modules\Auth\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class BtUserManager extends Component
{
    use WithPagination;

    // List state
    public string $search = '';

    // Form state
    public bool   $showForm   = false;
    public ?int   $editingId  = null;
    public string $name       = '';
    public string $email      = '';
    public string $role       = 'bt_support';
    public bool   $is_active  = true;
    public string $formError  = '';

    protected function rules(): array
    {
        $emailUnique = $this->editingId
            ? 'unique:users,email,' . $this->editingId
            : 'unique:users,email';

        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', $emailUnique],
            'role'      => ['required', 'in:bt_admin,bt_technician,bt_support'],
            'is_active' => ['boolean'],
        ];
    }

    public function openCreate(): void
    {
        $this->reset('name', 'email', 'role', 'is_active', 'editingId', 'formError');
        $this->role      = 'bt_support';
        $this->is_active = true;
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $user            = User::findOrFail($id);
        $this->editingId = $id;
        $this->name      = $user->name;
        $this->email     = $user->email;
        $this->role      = $user->role;
        $this->is_active = $user->is_active;
        $this->formError = '';
        $this->showForm  = true;
    }

    public function save(): void
    {
        $this->validate();
        $this->formError = '';

        try {
            if ($this->editingId) {
                User::findOrFail($this->editingId)->update([
                    'name'      => $this->name,
                    'email'     => $this->email,
                    'role'      => $this->role,
                    'is_active' => $this->is_active,
                ]);
                session()->flash('success', "{$this->name} updated successfully.");
            } else {
                User::create([
                    'name'             => $this->name,
                    'email'            => $this->email,
                    'role'             => $this->role,
                    'is_active'        => $this->is_active,
                    'navixy_user_id'   => 0,
                    'navixy_account_id'=> 0,
                    'navixy_instance'  => 1,
                ]);
                session()->flash('success', "{$this->name} added successfully.");
            }

            $this->showForm = false;
            $this->resetPage();

        } catch (\Throwable $e) {
            $this->formError = 'Error: ' . $e->getMessage();
        }
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
    }

    public function render()
    {
        $users = User::whereIn('role', User::BT_ROLES)
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20);

        return view('auth::livewire.bt-user-manager', compact('users'));
    }
}