<div class="font-medium text-sm text-green-600"
     x-data="{ show: true }"
     x-init="setTimeout(() => show = false, 1500)"
     x-show="show"
     x-transition.opacity.duration.500ms>
    {{ __('Saved.') }}
</div>
