<x-layouts.app title="My Requests" active="requests.index">
    <x-slot:breadcrumbs>
        <span>Workspace</span>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">My Requests</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Employee workspace"
        title="My requests"
        description="Review the status and approval history of your leave and permission requests."
    >
        <x-slot:actions>
            <x-ui.button :href="route('requests.create')">
                <x-icon name="plus" class="h-[17px] w-[17px]" />
                New request
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <div class="overflow-hidden rounded-lg border border-neutral-300 bg-white shadow-sm">
        @forelse ($leaveRequests as $leaveRequest)
            <x-request-card :leave-request="$leaveRequest" />
        @empty
            <x-empty-state
                title="You haven't submitted any requests yet"
                description="Your submitted requests will appear here with their status, dates, and approval history."
            >
                <x-slot:actions>
                    <x-ui.button :href="route('requests.create')">
                        <x-icon name="plus" class="h-[17px] w-[17px]" />
                        New request
                    </x-ui.button>
                </x-slot:actions>
            </x-empty-state>
        @endforelse
    </div>
</x-layouts.app>
