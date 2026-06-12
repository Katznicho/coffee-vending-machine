<x-app-layout>
    <x-dashboard-layout title="Edit machine">
        @include('machines._form', ['machine' => $machine])
    </x-dashboard-layout>
</x-app-layout>
