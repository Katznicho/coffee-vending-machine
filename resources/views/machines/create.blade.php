<x-app-layout>
    <x-dashboard-layout title="Add machine">
        @include('machines._form', ['machine' => new \App\Models\Machine()])
    </x-dashboard-layout>
</x-app-layout>
