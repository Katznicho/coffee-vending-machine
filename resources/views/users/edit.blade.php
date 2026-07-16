<x-app-layout>
    <x-dashboard-layout title="Edit user">
        @include('users._form', ['user' => $user])
    </x-dashboard-layout>
</x-app-layout>
