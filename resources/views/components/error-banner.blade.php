@props([])

{{--
    Banner for errors that are not anchored to a visible form field, for
    example a modal decision (approval queue) or a status conflict.
--}}
@if ($errors->any())
    <div class="mb-6 rounded-md border border-red-300 bg-red-50 px-3.5 py-3 text-xs leading-relaxed text-red-800" role="alert">
        <p class="font-bold">Please fix the following:</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-4">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
