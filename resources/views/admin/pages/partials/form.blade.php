{{--
This file is part of DixlasePages.

Copyright (C) 2025 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

<!-- Title -->
<div class="mb-4">
    @include('components::form.label', [
        'for' => 'title',
        'text' => 'pages-plugin::admin.features.pages.title',
    ])
    @include('components::form.text', [
        'id' => 'title',
        'name' => 'title',
        'value' => old('title', $page->title ?? ''),
        'required' => true,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('title')
    ])
</div>

<!-- Slug -->
<div class="mb-4">
    @include('components::form.label', [
        'for' => 'slug',
        'text' => 'pages-plugin::admin.features.pages.slug',
    ])
    @include('components::form.text', [
        'id' => 'slug',
        'name' => 'slug',
        'value' => old('slug', $page->slug ?? ''),
        'placeholder' => '自動生成されます（空白の場合）',
    ])
    @include('components::form.error', [
        'messages' => $errors->get('slug')
    ])
</div>

<!-- Content -->
<div class="mb-4">
    @include('components::form.label', [
        'for' => 'content',
        'text' => 'pages-plugin::admin.features.pages.content',
    ])
    @include('components::form.textarea', [
        'id' => 'content',
        'name' => 'content',
        'value' => old('content', $page->content ?? ''),
        'required' => true,
        'class' => 'min-h-48',
    ])
    @include('components::form.error', [
        'messages' => $errors->get('content')
    ])
</div>

<!-- Status -->
<div class="mb-4">
    @include('components::form.label', [
        'for' => 'status',
        'text' => 'pages-plugin::admin.features.pages.status',
    ])
    @include('components::form.radio-group', [
        'name' => 'status',
        'options' => [
            'draft' => [
                'label' => __('admin.common.status.draft'),
                'description' => __('admin.common.status.draft_description'),
            ],
            'published' => [
                'label' => __('admin.common.status.published'),
                'description' => __('admin.common.status.published_description'),
            ],
            'scheduled' => [
                'label' => __('admin.common.status.scheduled'),
                'description' => __('admin.common.status.scheduled_description'),
            ],
        ],
        'selected' => old('status', $page->status->value ?? 'draft'),
        'messages' => $errors->get('status')
    ])
</div>

<!-- Published At (日付指定時のみ表示) -->
<div class="mb-4" id="published-at-field" style="display: none;">
    @include('components::form.label', [
        'for' => 'published_at',
        'text' => 'pages-plugin::admin.features.pages.published_at',
    ])
    @include('components::form.text', [
        'id' => 'published_at',
        'name' => 'published_at',
        'type' => 'datetime-local',
        'value' => old('published_at', $page->published_at?->format('Y-m-d\TH:i') ?? ''),
    ])
    @include('components::form.error', [
        'messages' => $errors->get('published_at')
    ])
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusRadios = document.querySelectorAll('input[name="status"]');
    const publishedAtField = document.getElementById('published-at-field');
    
    function togglePublishedAtField() {
        const selectedStatus = document.querySelector('input[name="status"]:checked')?.value;
        if (selectedStatus === 'scheduled') {
            publishedAtField.style.display = 'block';
        } else {
            publishedAtField.style.display = 'none';
        }
    }
    
    // 初期表示
    togglePublishedAtField();
    
    // ラジオボタン変更時
    statusRadios.forEach(radio => {
        radio.addEventListener('change', togglePublishedAtField);
    });
});
</script>
@endpush
