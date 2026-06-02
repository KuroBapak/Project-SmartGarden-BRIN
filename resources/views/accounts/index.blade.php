<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Akun') }}
        </h2>
    </x-slot>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .modal-backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .input-glow:focus { box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
    </style>

    <div class="py-12" x-data="accountsApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Daftar Pengguna</h3>
                            <p class="text-xs text-gray-500">Kelola akun yang memiliki akses ke dashboard</p>
                        </div>
                        <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Akun
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Lengkap</th>
                                    <th scope="col" class="px-6 py-3">Email</th>
                                    <th scope="col" class="px-6 py-3">Role</th>
                                    <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr class="bg-white border-b">
                                        <td class="px-6 py-4 font-medium text-gray-900">{{ $user->name }}</td>
                                        <td class="px-6 py-4">{{ $user->email }}</td>
                                        <td class="px-6 py-4">
                                            @if($user->role === 'master_admin')
                                                <span class="bg-purple-100 text-purple-800 text-[10px] font-bold px-2.5 py-0.5 rounded uppercase">Master Admin</span>
                                            @else
                                                <span class="bg-gray-100 text-gray-800 text-[10px] font-bold px-2.5 py-0.5 rounded uppercase">User</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <button @click="openEditModal({{ $user->toJson() }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                            @if(auth()->id() !== $user->id)
                                                <button @click="openDeleteModal({{ $user->id }}, '{{ $user->name }}')" class="text-red-600 hover:text-red-900">Hapus</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════ MODAL: CREATE / EDIT ══════════ --}}
        <div x-show="showUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
             x-transition @keydown.escape.window="showUserModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg transform" @click.outside="showUserModal = false">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingUserId ? 'Edit Akun' : 'Tambah Akun Baru'"></h3>
                    <button @click="showUserModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form :action="editingUserId ? '{{ url('accounts') }}/' + editingUserId : '{{ route('accounts.store') }}'" method="POST" class="p-5 space-y-4">
                    @csrf
                    <template x-if="editingUserId"><input type="hidden" name="_method" value="PUT"></template>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" x-model="form.name" class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full" required>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" x-model="form.email" class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full" required>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">
                            Password <span x-show="editingUserId" class="text-xs text-gray-400 font-normal">(Kosongkan jika tidak ingin diubah)</span>
                        </label>
                        <input type="password" name="password" class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full" :required="!editingUserId">
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full" :required="!editingUserId">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showUserModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-sm">
                            <span x-text="editingUserId ? 'Simpan Perubahan' : 'Tambah Akun'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ══════════ MODAL: DELETE CONFIRMATION ══════════ --}}
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
             x-transition @keydown.escape.window="showDeleteModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm" @click.outside="showDeleteModal = false">
                <div class="p-6 text-center">
                    <div class="mx-auto w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Hapus Akun?</h3>
                    <p class="text-sm text-gray-500 mb-5">Hapus akses <span class="font-bold text-gray-700" x-text="deletingUserName"></span>?</p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                        <form :action="'{{ url('accounts') }}/' + deletingUserId" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition shadow-sm">Ya, Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function accountsApp() {
            return {
                showUserModal: false,
                editingUserId: null,
                form: { name: '', email: '' },

                showDeleteModal: false,
                deletingUserId: null,
                deletingUserName: '',

                openCreateModal() {
                    this.editingUserId = null;
                    this.form = { name: '', email: '' };
                    this.showUserModal = true;
                },

                openEditModal(user) {
                    this.editingUserId = user.id;
                    this.form = { name: user.name, email: user.email };
                    this.showUserModal = true;
                },

                openDeleteModal(id, name) {
                    this.deletingUserId = id;
                    this.deletingUserName = name;
                    this.showDeleteModal = true;
                }
            }
        }
    </script>
</x-app-layout>
