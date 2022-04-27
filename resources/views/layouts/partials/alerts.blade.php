<div class="pb-5 w-full md:w-2/4 md:m-auto">
    @if (session('success'))
        <x-core.alerts.closable-success-alert title="Look at you go!" icon="far fa-grin">
            <p class="mt-3">{{ session('success') }}</p>
        </x-core.alerts.closable-success-alert>
    @endif

    @if (session('error'))
        <x-core.alerts.danger-alert title="Oh No!!" icon="far fa-sad-cry">
            <p class="mt-3">{{ session('error') }}</p>
        </x-core.alerts.danger-alert>
    @endif

    @if ($errors->any())
        <x-core.alerts.danger-alert title="Whoops!" icon="far fa-sad-cry">
            @foreach($errors->all() as $error)
                <p class="mt-3">{{ $error }}</p>
            @endforeach
        </x-core.alerts.danger-alert>
    @endif
</div>

