{{--
This file is part of Dixlase Pages.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')

<form id="pageCreateForm" action="{{ route('dixlase-pages::admin.pages.store') }}" method="POST" class="min-w-0 overflow-hidden">
    @csrf
    <!-- フォーム -->
    @include('dixlase-pages::admin.pages.partials.form', [

    ])
</form>
@endsection

@push('styles')
<style @cspNonce>
#admin-main-content { min-width: 0; }
</style>
@endpush

<!-- 保存ボタンとモーダル -->
@section('save')
    @include('components::admin.save-button', [
        'id' => 'confirmationModal',
        'onclick' => "openModal('confirmationModal')",
        'title' => __('common.save_confirmation_title'),
        'label' => __('common.create'),
        'message' => __('dixlase-pages::admin/pages/create.confirmation_message'),
        'confirm_label' => __('common.create'),
        'cancel_label' => __('common.back'),
        'form' => 'pageCreateForm',
    ])
@endsection