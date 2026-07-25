{{--
This file is part of Dixlase Pages.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

Dixlase Pages is dual-licensed. You may use this file under either:

  (a) the GNU General Public License version 3 or later, as published
      by the Free Software Foundation; or

  (b) a commercial license agreement obtained from exc-D inc.

Unless you have entered into a commercial license agreement, this
file is governed by the GPL terms below.

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

<form id="pageUpdateForm" action="{{ route('dixlase-pages::admin.pages.update', ['page' => $page->id] ) }}" method="POST" class="min-w-0 overflow-hidden">
    @csrf
    @method('PATCH')

    <!-- id -->
    @include('components::form-hidden', [
        'name' => 'id',
        'value' => $page->id,
    ])

    <!-- フォーム -->
    @include('dixlase-pages::admin.pages.partials.form', [

    ])



</form>
@endsection

@push('styles')
<style @cspNonce>
/* メインコンテンツがflexbox min-width:autoで縮小しない問題を修正 */
#admin-main-content { min-width: 0; }
</style>
@endpush

<!-- 保存ボタンとモーダル -->
@section('save')
    @include('components::admin.save-button', [
        'id' => 'confirmationModal',
        'onclick' => "openModal('confirmationModal')",
        'title' => __('common.save_confirmation_title'),
        'label' => __('common.update'),
        'message' => __('dixlase-pages::admin/pages/edit.confirmation_message'),
        'confirm_label' => __('common.update'),
        'cancel_label' => __('common.back'),
        'form' => 'pageUpdateForm',
    ])
@endsection
